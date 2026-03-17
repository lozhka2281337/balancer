<?php

namespace App\Tests\Unit\Service;

use App\Entity\Machine;
use App\Entity\Process;
use App\HelperFunctions\MachineCalculationFunctions;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;
use App\Service\DeleteMachineFunction;
use PHPUnit\Framework\TestCase;

class DeleteMachineFunctionTest extends TestCase
{
    private ProcessRepository $processRepository;
    private MachineRepository $machineRepository;
    private MachineCalculationFunctions $machineCalculator;
    private DeleteMachineFunction $service;

    protected function setUp(): void
    {
        $this->processRepository = $this->createMock(ProcessRepository::class);
        $this->machineRepository = $this->createMock(MachineRepository::class);
        $this->machineCalculator = new MachineCalculationFunctions($this->processRepository);

        $this->service = new DeleteMachineFunction(
            $this->processRepository,
            $this->machineRepository,
            $this->machineCalculator
        );
    }

    private function createMachineWithId(int $id, int $cpu, int $memory): Machine
    {
        $machine = new Machine();
        $machine->setTotalCpu($cpu);
        $machine->setTotalMemory($memory);

        $reflection = new \ReflectionProperty(Machine::class, 'id');
        $reflection->setValue($machine, $id);

        return $machine;
    }

    // checkDeleteMachine возвращает пустой список осиротевших когда нет процессов
    public function testCheckDeleteMachineWithNoProcesses(): void
    {
        $deletingMachine = $this->createMachineWithId(1, 100, 100);
        $otherMachine = $this->createMachineWithId(2, 100, 100);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturn([]);

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$deletingMachine, $otherMachine]);

        [$orphanedProcesses, $machineResources] = $this->service->checkDeleteMachine($deletingMachine);

        $this->assertEmpty($orphanedProcesses);
        $this->assertCount(1, $machineResources);
    }

    // checkDeleteMachine успешно перераспределяет все процессы
    public function testCheckDeleteMachineAllProcessesRelocated(): void
    {
        $deletingMachine = $this->createMachineWithId(1, 100, 100);
        $targetMachine = $this->createMachineWithId(2, 100, 100);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(50);

        // процессы удаляемой машины
        $this->processRepository
            ->method('findMachineProcesses')
            ->with($deletingMachine)
            ->willReturn([$process]);

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$deletingMachine, $targetMachine]);

        // на целевой машине нет своих процессов
        $this->machineCalculator = $this->createMock(MachineCalculationFunctions::class);
        $this->machineCalculator
            ->method('resourceCalculation')
            ->willReturn([0, 0, []]);

        $this->machineCalculator
            ->method('loadRating')
            ->willReturn(0.0);

        $this->service = new DeleteMachineFunction(
            $this->processRepository,
            $this->machineRepository,
            $this->machineCalculator
        );

        [$orphanedProcesses, $machineResources] = $this->service->checkDeleteMachine($deletingMachine);

        $this->assertEmpty($orphanedProcesses);
        // процесс добавлен в список машины
        $this->assertContains($process, $machineResources[0][3]);
    }

    // checkDeleteMachine возвращает осиротевшие процессы когда нет свободных машин
    public function testCheckDeleteMachineWithOrphanedProcesses(): void
    {
        $deletingMachine = $this->createMachineWithId(1, 100, 100);
        $otherMachine = $this->createMachineWithId(2, 10, 10);

        $bigProcess = new Process();
        $bigProcess->setCpu(50);
        $bigProcess->setMemory(50);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($deletingMachine, $bigProcess) {
                if ($machine->getId() === $deletingMachine->getId()) {
                    return [$bigProcess];
                }
                return [];
            });

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$deletingMachine, $otherMachine]);

        [$orphanedProcesses, $machineResources] = $this->service->checkDeleteMachine($deletingMachine);

        $this->assertCount(1, $orphanedProcesses);
        $this->assertSame($bigProcess, $orphanedProcesses[0]);
    }

    // deleteMachine успешно удаляет машину и переносит процессы
    public function testDeleteMachineSucceeds(): void
    {
        $deletingMachine = $this->createMachineWithId(1, 100, 100);
        $targetMachine = $this->createMachineWithId(2, 100, 100);

        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(50);

        $this->processRepository
            ->method('findMachineProcesses')
            ->with($deletingMachine)
            ->willReturn([$process]);

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$deletingMachine, $targetMachine]);

        $this->machineCalculator = $this->createMock(MachineCalculationFunctions::class);
        $this->machineCalculator
            ->method('resourceCalculation')
            ->willReturn([0, 0, []]);

        $this->machineCalculator
            ->method('loadRating')
            ->willReturn(0.0);

        $this->service = new DeleteMachineFunction(
            $this->processRepository,
            $this->machineRepository,
            $this->machineCalculator
        );

        $this->processRepository->expects($this->once())->method('move')->with($process, $targetMachine);
        $this->machineRepository->expects($this->once())->method('delete')->with($deletingMachine);
        $this->processRepository->expects($this->once())->method('saveChanges');

        $result = $this->service->deleteMachine($deletingMachine);

        $this->assertEmpty($result);
    }

    // deleteMachine возвращает осиротевшие процессы и не удаляет машину
    public function testDeleteMachineFailsWithOrphanedProcesses(): void
    {
        $deletingMachine = $this->createMachineWithId(1, 100, 100);
        $otherMachine = $this->createMachineWithId(2, 10, 10);

        $bigProcess = new Process();
        $bigProcess->setCpu(50);
        $bigProcess->setMemory(50);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($deletingMachine, $bigProcess) {
                if ($machine->getId() === $deletingMachine->getId()) {
                    return [$bigProcess];
                }
                return [];
            });

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$deletingMachine, $otherMachine]);

        $this->machineRepository->expects($this->never())->method('delete');
        $this->processRepository->expects($this->never())->method('saveChanges');

        $result = $this->service->deleteMachine($deletingMachine);

        $this->assertCount(1, $result);
        $this->assertSame($bigProcess, $result[0]);
    }
}
