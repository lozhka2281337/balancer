<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Machine;
use PHPUnit\Framework\TestCase;

class MachineTest extends TestCase
{
    private Machine $machine;
    
    protected function setUp(): void
    {
        $this->machine = new Machine();
    }

    // установка cpu
    public function testSetTotalCpu(): void
    {
        $this->machine->setTotalCpu(100);
        
        $this->assertEquals(100, $this->machine->getTotalCpu());
    }

    // установка memory
    public function testSetTotalMemory(): void
    {
        $this->machine->setTotalMemory(500);
        
        $this->assertEquals(500, $this->machine->getTotalMemory());
    }

    // добавление процесса
    public function testAddProcess(): void
    {
        $process = new \App\Entity\Process();
        $process->setCpu(10);
        $process->setMemory(50);

        $this->machine->addProcess($process);

        $this->assertCount(1, $this->machine->getProcesses());
        $this->assertTrue($this->machine->getProcesses()->contains($process));
    }

    // удаление процесса
    public function testRemoveProcess(): void
    {
        $process = new \App\Entity\Process();
        $this->machine->addProcess($process);

        $this->machine->removeProcess($process);

        $this->assertEmpty($this->machine->getProcesses());
    }
}