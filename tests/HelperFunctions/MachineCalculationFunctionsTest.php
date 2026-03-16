<?php

namespace App\Tests\Unit\HelperFunctions;

use App\Entity\Machine;
use App\Entity\Process;
use App\HelperFunctions\MachineCalculationFunctions;
use App\Repository\ProcessRepository;
use PHPUnit\Framework\TestCase;

class MachineCalculationFunctionsTest extends TestCase
{
    private MachineCalculationFunctions $machinecalculator;
    private ProcessRepository $processRepository;

    protected function setUp(): void
    {
        // создаем мок
        $this->processRepository = $this->createMock(ProcessRepository::class);
        $this->machinecalculator = new MachineCalculationFunctions($this->processRepository);
    }

    // loadRating с нулевой нагрузкой
    public function testLoadRatingWithZeroResources(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        $loadRating = $this->machinecalculator->loadRating($machine, 0, 0);

        $this->assertEquals(0.0, $loadRating);
    }

    // loadRating при полной нагрузке на CPU
    public function testLoadRatingWithFullCpu(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        $loadRating = $this->machinecalculator->loadRating($machine, 100, 0);

        $this->assertEquals(1.0, $loadRating);
    }

    // loadRating возвращает максимум (CPU перегружена)
    public function testLoadRatingReturnMaxOfBoth(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        // CPU перегружена, Memory нет
        $loadRating = $this->machinecalculator->loadRating($machine, 90, 100);

        // max(100/500, 90/100) = max(0.2, 0.9) = 0.9
        $this->assertEquals(0.9, $loadRating);
    }

    // resourceCalculation без процессов
    public function testResourceCalculationWithNoProcesses(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        // делаем мок - машина не имеет процессов
        $this->processRepository
            ->method('findMachineProcesses')
            ->with($machine)
            ->willReturn([]);

        [$usedCpu, $usedMemory, $processes] = $this->machinecalculator->resourceCalculation($machine);

        $this->assertEquals(0, $usedCpu);
        $this->assertEquals(0, $usedMemory);
        $this->assertEmpty($processes);
    }

    // resourceCalculation с процессами
    public function testResourceCalculationWithProcesses(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(500);

        // создаем тестовые процессы
        $process1 = new Process();
        $process1->setCpu(20);
        $process1->setMemory(100);

        $process2 = new Process();
        $process2->setCpu(30);
        $process2->setMemory(150);

        // мок - машина имеет эти процессы
        $this->processRepository
            ->method('findMachineProcesses')
            ->with($machine)
            ->willReturn([$process1, $process2]);

        [$usedCpu, $usedMemory, $processes] = $this->machinecalculator->resourceCalculation($machine);

        $this->assertEquals(50, $usedCpu);      
        $this->assertEquals(250, $usedMemory);  
        $this->assertCount(2, $processes);
    }
}