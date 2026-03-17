<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Machine;
use App\Entity\Process;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    private Process $process;

    protected function setUp(): void
    {
        $this->process = new Process();
    }

    // установка cpu
    public function testSetCpu(): void
    {
        $this->process->setCpu(50);
        $this->assertEquals(50, $this->process->getCpu());
    }

    // установка memory
    public function testSetMemory(): void
    {
        $this->process->setMemory(200);
        $this->assertEquals(200, $this->process->getMemory());
    }

    // установка машины
    public function testSetMachine(): void
    {
        $machine = new Machine();
        $this->process->setMachine($machine);

        $this->assertSame($machine, $this->process->getMachine());
    }

    // 
    public function testChainSetters(): void
    {
        $result = $this->process
            ->setCpu(30)
            ->setMemory(100);

        $this->assertSame($this->process, $result);
    }

    // машина изначально равна null
    public function testMachineInitiallyNull(): void
    {
        $this->assertNull($this->process->getMachine());
    }

    // setMachine возвращает static (цепочка вызовов)
    public function testSetMachineChaining(): void
    {
        $machine = new Machine();
        $result = $this->process->setMachine($machine);

        $this->assertSame($this->process, $result);
    }
}