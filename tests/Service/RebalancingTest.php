<?php

namespace App\Tests\Unit\Service;

use App\Entity\Machine;
use App\Entity\Process;
use App\HelperFunctions\MachineCalculationFunctions;
use App\Repository\MachineRepository;
use App\Repository\ProcessRepository;
use App\Service\Rebalancing;
use PHPUnit\Framework\TestCase;

class RebalancingTest extends TestCase
{
    private ProcessRepository $processRepository;
    private MachineRepository $machineRepository;
    private MachineCalculationFunctions $machineCalculator;
    private Rebalancing $service;

    protected function setUp(): void
    {
        $this->processRepository = $this->createMock(ProcessRepository::class);
        $this->machineRepository = $this->createMock(MachineRepository::class);
        $this->machineCalculator = new MachineCalculationFunctions($this->processRepository);

        $this->service = new Rebalancing(
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

    // processSelection возвращает null при отсутствии процессов на исходной машине
    public function testProcessSelectionReturnsNullWhenNoProcesses(): void
    {
        $sourceMachine = $this->createMachineWithId(1, 100, 100);
        $targetMachine = $this->createMachineWithId(2, 100, 100);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturn([]);

        $result = $this->service->processSelection($sourceMachine, $targetMachine);

        $this->assertNull($result);
    }

    // processSelection находит подходящий процесс для переноса
    public function testProcessSelectionReturnsProcessWhenSuitable(): void
    {
        $sourceMachine = $this->createMachineWithId(1, 200, 200);
        $targetMachine = $this->createMachineWithId(2, 200, 200);

        $p1 = new Process();
        $p1->setCpu(100);
        $p1->setMemory(100);

        $p2 = new Process();
        $p2->setCpu(50);
        $p2->setMemory(50);

        // на исходной машине 2 процесса, на целевой - нет
        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($sourceMachine, $p1, $p2) {
                if ($machine->getId() === $sourceMachine->getId()) {
                    return [$p1, $p2];
                }
                return [];
            });

        $result = $this->service->processSelection($sourceMachine, $targetMachine);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Process::class, $result);
    }

    // processSelection возвращает null когда целевая машина переполнена
    public function testProcessSelectionReturnsNullWhenTargetTooFull(): void
    {
        $sourceMachine = $this->createMachineWithId(1, 10, 10);
        $targetMachine = $this->createMachineWithId(2, 10, 10);

        $process = new Process();
        $process->setCpu(10);
        $process->setMemory(10);

        // целевая машина уже полностью занята
        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($sourceMachine, $process) {
                if ($machine->getId() === $sourceMachine->getId()) {
                    return [$process];
                }
                $existing = new Process();
                $existing->setCpu(10);
                $existing->setMemory(10);
                return [$existing];
            });

        $result = $this->service->processSelection($sourceMachine, $targetMachine);

        $this->assertNull($result);
    }

    // moveProcess возвращает null когда в списке только исходная машина
    public function testMoveProcessReturnsNullWhenOnlySourceMachineAvailable(): void
    {
        $machine = $this->createMachineWithId(1, 100, 100);

        $machineLoads = [
            [0.5, $machine],
        ];

        $result = $this->service->moveProcess($machineLoads, $machine);

        $this->assertNull($result);
    }

    // moveProcess возвращает null когда нет подходящего процесса для переноса
    public function testMoveProcessReturnsNullWhenNoSuitableProcess(): void
    {
        // исходная машина полностью загружена, перенос невозможен
        $sourceMachine = $this->createMachineWithId(1, 10, 10);
        $targetMachine = $this->createMachineWithId(2, 10, 10);

        $process = new Process();
        $process->setCpu(10);
        $process->setMemory(10);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($sourceMachine, $process) {
                if ($machine->getId() === $sourceMachine->getId()) {
                    return [$process];
                }
                $existing = new Process();
                $existing->setCpu(10);
                $existing->setMemory(10);
                return [$existing];
            });

        $machineLoads = [
            [0.1, $targetMachine],
            [1.0, $sourceMachine],
        ];

        $result = $this->service->moveProcess($machineLoads, $sourceMachine);

        $this->assertNull($result);
    }

    // moveProcess успешно переносит процесс и возвращает целевую машину
    public function testMoveProcessReturnsTargetWhenProcessMoved(): void
    {
        $sourceMachine = $this->createMachineWithId(1, 200, 200);
        $targetMachine = $this->createMachineWithId(2, 200, 200);

        $p1 = new Process();
        $p1->setCpu(100);
        $p1->setMemory(100);

        $p2 = new Process();
        $p2->setCpu(50);
        $p2->setMemory(50);

        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($sourceMachine, $p1, $p2) {
                if ($machine->getId() === $sourceMachine->getId()) {
                    return [$p1, $p2];
                }
                return [];
            });

        $this->processRepository->expects($this->once())->method('move');
        $this->processRepository->expects($this->once())->method('saveChanges');

        $machineLoads = [
            [0.0, $targetMachine],
            [0.75, $sourceMachine],
        ];

        $result = $this->service->moveProcess($machineLoads, $sourceMachine);

        $this->assertSame($targetMachine, $result);
    }

    // rebalance возвращает пустой массив при отсутствии машин
    public function testRebalanceReturnsEmptyArrayWhenNoMachines(): void
    {
        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([]);

        $result = $this->service->rebalance();

        $this->assertEmpty($result);
    }

    // rebalance возвращает пустой массив когда нагрузка уже сбалансирована
    public function testRebalanceReturnsEmptyArrayWhenAlreadyBalanced(): void
    {
        $m1 = $this->createMachineWithId(1, 100, 100);
        $m2 = $this->createMachineWithId(2, 100, 100);

        $p1 = new Process();
        $p1->setCpu(90);
        $p1->setMemory(90);

        $p2 = new Process();
        $p2->setCpu(90);
        $p2->setMemory(90);

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$m1, $m2]);

        $this->processRepository
            ->method('findAll')
            ->willReturn([$p1, $p2]);

        // обе машины загружены одинаково (load = 0.9)
        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($m1, $m2, $p1, $p2) {
                if ($machine->getId() === $m1->getId()) return [$p1];
                if ($machine->getId() === $m2->getId()) return [$p2];
                return [];
            });

        $result = $this->service->rebalance();

        $this->assertEmpty($result);
    }

    // rebalance возвращает пару машин после успешного переноса процесса
    public function testRebalanceReturnsMachinesPairWhenProcessMoved(): void
    {
        $m1 = $this->createMachineWithId(1, 200, 200);
        $m2 = $this->createMachineWithId(2, 200, 200);

        $p1 = new Process();
        $p1->setCpu(100);
        $p1->setMemory(100);

        $p2 = new Process();
        $p2->setCpu(50);
        $p2->setMemory(50);

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$m1, $m2]);

        // findAll() возвращает 1 процесс → maxIterations = 1 (ровно один проход цикла)
        $this->processRepository
            ->method('findAll')
            ->willReturn([$p1]);

        // m1 загружена на 75% (150/200), m2 пустая (0%)
        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($m1, $p1, $p2) {
                if ($machine->getId() === $m1->getId()) {
                    return [$p1, $p2];
                }
                return [];
            });

        $this->processRepository->expects($this->once())->method('move');
        $this->processRepository->expects($this->once())->method('saveChanges');

        $result = $this->service->rebalance();

        $this->assertCount(1, $result);
        $this->assertSame($m1, $result[0][0]);
        $this->assertSame($m2, $result[0][1]);
    }

    // rebalance возвращает пустой массив когда нет возможности перенести процесс
    public function testRebalanceReturnsEmptyArrayWhenNoProcessCanMove(): void
    {
        $m1 = $this->createMachineWithId(1, 10, 10);
        $m2 = $this->createMachineWithId(2, 10, 10);

        $process = new Process();
        $process->setCpu(10);
        $process->setMemory(10);

        $this->machineRepository
            ->method('getAllMachines')
            ->willReturn([$m1, $m2]);

        $this->processRepository
            ->method('findAll')
            ->willReturn([$process]);

        // m1 полностью загружена (10/10 = 1.0), m2 пустая
        $this->processRepository
            ->method('findMachineProcesses')
            ->willReturnCallback(function (Machine $machine) use ($m1, $process) {
                if ($machine->getId() === $m1->getId()) {
                    return [$process];
                }
                return [];
            });

        $this->processRepository->expects($this->never())->method('move');

        $result = $this->service->rebalance();

        $this->assertEmpty($result);
    }
}
