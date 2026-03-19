<?php

namespace App\Tests\Validator;

use App\Entity\Process;
use App\Validator\ProcessValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProcessValidatorTest extends KernelTestCase
{
    private ProcessValidator $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $symfonyValidator = static::getContainer()->get(ValidatorInterface::class);
        $this->validator = new ProcessValidator($symfonyValidator);
    }

    // валидный процесс проходит валидацию без ошибок
    public function testValidProcessPassesValidation(): void
    {
        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);

        $errors = $this->validator->validate($process);

        $this->assertEmpty($errors);
    }

    // процесс с нулевым cpu не проходит валидацию
    public function testInvalidCpuZeroFailsValidation(): void
    {
        $process = new Process();
        $process->setCpu(0);
        $process->setMemory(4);

        $errors = $this->validator->validate($process);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('cpu', $errors);
    }

    // процесс с отрицательным cpu не проходит валидацию
    public function testInvalidCpuNegativeFailsValidation(): void
    {
        $process = new Process();
        $process->setCpu(-5);
        $process->setMemory(4);

        $errors = $this->validator->validate($process);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('cpu', $errors);
    }

    // процесс с нулевой памятью не проходит валидацию
    public function testInvalidMemoryZeroFailsValidation(): void
    {
        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(0);

        $errors = $this->validator->validate($process);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('memory', $errors);
    }

    // процесс с отрицательной памятью не проходит валидацию
    public function testInvalidMemoryNegativeFailsValidation(): void
    {
        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(-4);

        $errors = $this->validator->validate($process);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('memory', $errors);
    }

    // оба невалидных поля возвращают несколько ошибок
    public function testBothInvalidFieldsReturnMultipleErrors(): void
    {
        $process = new Process();
        $process->setCpu(0);
        $process->setMemory(0);

        $errors = $this->validator->validate($process);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('cpu', $errors);
        $this->assertArrayHasKey('memory', $errors);
    }
}
