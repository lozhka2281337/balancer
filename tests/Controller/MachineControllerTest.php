<?php

namespace App\Tests\Controller;

use App\Entity\Machine;
use App\Entity\Process;
use App\Tests\ControllerTestCase;

class MachineControllerTest extends ControllerTestCase
{
    // POST /machine с валидными данными создаёт машину и возвращает 201
    public function testAddMachineSuccess(): void
    {
        $this->postJson('/machine', ['cpu' => 8, 'memory' => 16]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('id машины', $data);
        $this->assertIsInt($data['id машины']);
    }

    // POST /machine с невалидным JSON возвращает 422
    public function testAddMachineWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/machine',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-valid-json'
        );

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('json невалидный', $data['ошибка']);
    }

    // POST /machine с отсутствующими полями возвращает 422
    public function testAddMachineWithMissingFields(): void
    {
        $this->postJson('/machine', ['cpu' => 4]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('поля cpu и memory должны быть заполнены', $data['ошибка']);
    }

    // POST /machine с нулевым cpu возвращает 422
    public function testAddMachineWithZeroCpu(): void
    {
        $this->postJson('/machine', ['cpu' => 0, 'memory' => 8]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибки', $data);
        $this->assertArrayHasKey('totalCpu', $data['ошибки']);
    }

    // POST /machine с отрицательным cpu возвращает 422
    public function testAddMachineWithNegativeCpu(): void
    {
        $this->postJson('/machine', ['cpu' => -4, 'memory' => 8]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибки', $data);
    }

    // POST /machine с нулевой памятью возвращает 422
    public function testAddMachineWithZeroMemory(): void
    {
        $this->postJson('/machine', ['cpu' => 4, 'memory' => 0]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибки', $data);
        $this->assertArrayHasKey('totalMemory', $data['ошибки']);
    }

    // DELETE /machine с валидным id удаляет машину и возвращает 201
    public function testRemoveMachineSuccess(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $id = $machine->getId();

        $this->deleteJson('/machine', ['id' => $id]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('данные', $data);
        $this->assertEquals('машина успешно удалена', $data['данные']);
    }

    // DELETE /machine без поля id возвращает 422
    public function testRemoveMachineWithMissingId(): void
    {
        $this->deleteJson('/machine', []);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('поле id должно быть заполнено', $data['ошибка']);
    }

    // DELETE /machine с нечисловым id возвращает 422
    public function testRemoveMachineWithInvalidId(): void
    {
        $this->deleteJson('/machine', ['id' => 'not-a-number']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('поле id должно быть числом', $data['ошибка']);
    }

    // DELETE /machine с несуществующим id возвращает 422
    public function testRemoveMachineWithNonExistentId(): void
    {
        $this->deleteJson('/machine', ['id' => 9999]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('машины с таким id нет', $data['ошибка']);
    }

    // DELETE /machine с процессами возвращает ошибку, если процессы не могут быть перемещены
    public function testRemoveMachineFailsWhenProcessesCannotBeRelocated(): void
    {
        // создаём единственную машину с процессом
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($machine);
        $this->entityManager->persist($process);
        $this->entityManager->flush();

        $this->deleteJson('/machine', ['id' => $machine->getId()]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('машина не удалена - не удалось найти машины для следующих процессов:', $data);
    }
}
