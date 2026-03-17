<?php

namespace App\Tests\Repository;

use App\Entity\Machine;
use App\Entity\Process;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;
use App\Tests\DatabaseTestCase;

class ProcessRepositoryTest extends DatabaseTestCase
{
    private ProcessRepository $processRepository;
    private MachineRepository $machineRepository;
    private Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processRepository = static::getContainer()->get(ProcessRepository::class);
        $this->machineRepository = static::getContainer()->get(MachineRepository::class);

        // создаём машину для процессов
        $this->machine = new Machine();
        $this->machine->setTotalCpu(16);
        $this->machine->setTotalMemory(32);
        $this->machineRepository->store($this->machine);
    }

    // store сохраняет процесс и возвращает его с назначенным id
    public function testStorePersistsProcess(): void
    {
        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($this->machine);

        $stored = $this->processRepository->store($process);

        $this->assertNotNull($stored->getId());
        $this->assertEquals(2, $stored->getCpu());
        $this->assertEquals(4, $stored->getMemory());
    }

    // remove удаляет процесс из базы данных
    public function testRemoveDeletesProcess(): void
    {
        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($this->machine);
        $this->processRepository->store($process);

        $id = $process->getId();
        $this->processRepository->remove($process);

        $found = $this->processRepository->find($id);
        $this->assertNull($found);
    }

    // findMachineProcesses возвращает процессы на указанной машине
    public function testFindMachineProcessesReturnsMachineProcesses(): void
    {
        $process1 = new Process();
        $process1->setCpu(2);
        $process1->setMemory(4);
        $process1->setMachine($this->machine);
        $this->processRepository->store($process1);

        $process2 = new Process();
        $process2->setCpu(1);
        $process2->setMemory(2);
        $process2->setMachine($this->machine);
        $this->processRepository->store($process2);

        $processes = $this->processRepository->findMachineProcesses($this->machine);

        $this->assertCount(2, $processes);
    }

    // findMachineProcesses возвращает пустой массив при отсутствии процессов
    public function testFindMachineProcessesReturnsEmptyWhenNoProcesses(): void
    {
        $processes = $this->processRepository->findMachineProcesses($this->machine);

        $this->assertIsArray($processes);
        $this->assertEmpty($processes);
    }

    // move переназначает процесс на другую машину
    public function testMoveReassignsProcessToNewMachine(): void
    {
        $targetMachine = new Machine();
        $targetMachine->setTotalCpu(8);
        $targetMachine->setTotalMemory(16);
        $this->machineRepository->store($targetMachine);

        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($this->machine);
        $this->processRepository->store($process);

        $this->processRepository->move($process, $targetMachine);
        $this->processRepository->saveChanges();

        $this->entityManager->refresh($process);
        $this->assertEquals($targetMachine->getId(), $process->getMachine()->getId());
    }

    // saveChanges сохраняет изменения без повторного flush
    public function testSaveChangesFlushesEntityManager(): void
    {
        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($this->machine);

        $this->entityManager->persist($process);
        $this->processRepository->saveChanges();

        $this->assertNotNull($process->getId());
    }
}
