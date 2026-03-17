<?php

namespace App\Tests\Validator;

use App\Entity\Machine;
use App\Validator\MachineValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MachineValidatorTest extends KernelTestCase
{
    private MachineValidator $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $symfonyValidator = static::getContainer()->get(ValidatorInterface::class);
        $this->validator = new MachineValidator($symfonyValidator);
    }

    // валидная машина проходит валидацию без ошибок
    public function testValidMachinePassesValidation(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);

        $errors = $this->validator->validate($machine);

        $this->assertEmpty($errors);
    }

    // машина с нулевым cpu не проходит валидацию
    public function testInvalidCpuZeroFailsValidation(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(0);
        $machine->setTotalMemory(8);

        $errors = $this->validator->validate($machine);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('totalCpu', $errors);
    }

    // машина с отрицательным cpu не проходит валидацию
    public function testInvalidCpuNegativeFailsValidation(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(-1);
        $machine->setTotalMemory(8);

        $errors = $this->validator->validate($machine);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('totalCpu', $errors);
    }

    // машина с нулевой памятью не проходит валидацию
    public function testInvalidMemoryZeroFailsValidation(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(0);

        $errors = $this->validator->validate($machine);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('totalMemory', $errors);
    }

    // машина с отрицательной памятью не проходит валидацию
    public function testInvalidMemoryNegativeFailsValidation(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(-8);

        $errors = $this->validator->validate($machine);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('totalMemory', $errors);
    }

    // оба невалидных поля возвращают несколько ошибок
    public function testBothInvalidFieldsReturnMultipleErrors(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(-1);
        $machine->setTotalMemory(-1);

        $errors = $this->validator->validate($machine);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('totalCpu', $errors);
        $this->assertArrayHasKey('totalMemory', $errors);
    }
}
