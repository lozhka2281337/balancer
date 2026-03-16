<?php

namespace App\Tests\Unit\Service;

use App\Entity\Machine;
use App\Entity\Process;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;
use App\Service\AddProcessFunctions;
use PHPUnit\Framework\TestCase;

class AddProcessFunctionsTest extends TestCase
{
    private AddProcessFunctions $service;
    private ProcessRepository $processRepository;
    private MachineRepository $machineRepository;

    protected function setUp(): void
    {
        $this->processRepository = $this->createMock(ProcessRepository::class);
        $this->machineRepository = $this->createMock(MachineRepository::class);

        $this->service = new AddProcessFunctions(
            $this->processRepository,
            $this->machineRepository
        );
    }

    // машина подходит (достаточно ресурсов)
    public function testIsMachineSuitable(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(100);

        // мок - нет других процессов на машине
        $this->processRepository
            ->method('findMachineProcesses')
            ->with($machine)
            ->willReturn([]);

        $result = $this->service->isMachineSuitable($machine, $process);

        $this->assertTrue($result);
    }

    // машина не подходит (недостаточно cpu)
    public function testIsMachineNotSuitableByyCpu(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(10);
        $machine->setTotalMemory(500);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(100);

        $this->processRepository
            ->method('findMachineProcesses')
            ->with($machine)
            ->willReturn([]);

        $result = $this->service->isMachineSuitable($machine, $process);

        $this->assertFalse($result);
    }

    // машина не подходит (недостаточно memory)
    public function testIsMachineNotSuitableByMemory(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(50);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(100);

        $this->processRepository
            ->method('findMachineProcesses')
            ->with($machine)
            ->willReturn([]);

        $result = $this->service->isMachineSuitable($machine, $process);

        $this->assertFalse($result);
    }

    // машина подходит, но есть уже использованные ресурсы
    public function testIsMachineSuitableWithUsedResources(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        $existingProcess = new Process();
        $existingProcess->setCpu(30);
        $existingProcess->setMemory(200);

        $newProcess = new Process();
        $newProcess->setCpu(40);
        $newProcess->setMemory(200);

        // мок - машина уже имеет existingProcess
        $this->processRepository
            ->method('findMachineProcesses')
            ->with($machine)
            ->willReturn([$existingProcess]);

        // проверяем: хватает ли места для newProcess
        $result = $this->service->isMachineSuitable($machine, $newProcess);

        $this->assertTrue($result);
    }

    // SearchMachine находит первую подходящую машину
    public function testSearchMachineReturnsSuitableMachine(): void
    {
        $machine1 = new Machine();
        $machine1->setTotalCpu(10);
        $machine1->setTotalMemory(50);

        $machine2 = new Machine();
        $machine2->setTotalCpu(100);
        $machine2->setTotalMemory(500);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(100);

        $this->machineRepository
            ->method('findAll')
            ->willReturn([$machine1, $machine2]);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturn([]);

        $result = $this->service->SearchMachine($process);

        $this->assertSame($machine2, $result);
    }

    // SearchMachine возвращает null, если нет подходящей машины
    public function testSearchMachineReturnsNullWhenNoSuitable(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(5);
        $machine->setTotalMemory(25);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(100);

        $this->machineRepository
            ->method('findAll')
            ->willReturn([$machine]);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturn([]);

        $result = $this->service->SearchMachine($process);

        $this->assertNull($result);
    }
}