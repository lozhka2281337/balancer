<?php

namespace App\Tests\Controller;

use App\Entity\Machine;
use App\Entity\Process;
use App\Tests\ControllerTestCase;

class ProcessControllerTest extends ControllerTestCase
{
    // POST /process с валидными данными создаёт процесс и возвращает 200
    public function testAddProcessSuccess(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(8);
        $machine->setTotalMemory(16);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('id процесса', $data);
        $this->assertArrayHasKey('расположен на машине с id', $data);
        $this->assertIsInt($data['id процесса']);
        $this->assertEquals($machine->getId(), $data['расположен на машине с id']);
    }

    // POST /process с невалидным JSON возвращает 422
    public function testAddProcessWithInvalidJson(): void
    {
        $this->client->request(
            'POST',
            '/process',
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

    // POST /process с отсутствующими полями возвращает 422
    public function testAddProcessWithMissingFields(): void
    {
        $this->postJson('/process', ['cpu' => 2]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('поля cpu и memory должны быть заполнены', $data['ошибка']);
    }

    // POST /process с нулевым cpu возвращает 422
    public function testAddProcessWithZeroCpu(): void
    {
        $this->postJson('/process', ['cpu' => 0, 'memory' => 4]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибки', $data);
        $this->assertArrayHasKey('cpu', $data['ошибки']);
    }

    // POST /process с отрицательной памятью возвращает 422
    public function testAddProcessWithNegativeMemory(): void
    {
        $this->postJson('/process', ['cpu' => 2, 'memory' => -4]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибки', $data);
        $this->assertArrayHasKey('memory', $data['ошибки']);
    }

    // POST /process без доступных машин возвращает 422
    public function testAddProcessWithNoAvailableMachine(): void
    {
        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('не нашлось подходящей машины', $data['ошибка']);
    }

    // POST /process возвращает 422 если ресурсов недостаточно на всех машинах
    public function testAddProcessWhenMachineHasInsufficientResources(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(2);
        $machine->setTotalMemory(4);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $this->postJson('/process', ['cpu' => 8, 'memory' => 16]);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('не нашлось подходящей машины', $data['ошибка']);
    }

    // DELETE /process с валидным id удаляет процесс и возвращает 200
    public function testRemoveProcessSuccess(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(8);
        $machine->setTotalMemory(16);
        $this->entityManager->persist($machine);

        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($machine);
        $this->entityManager->persist($process);

        $this->entityManager->flush();

        $this->deleteJson('/process', ['id' => $process->getId()]);

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('данные', $data);
        $this->assertEquals('процесс успешно удален', $data['данные']);
    }

    // DELETE /process без поля id возвращает 422
    public function testRemoveProcessWithMissingId(): void
    {
        $this->deleteJson('/process', []);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('поле id должно быть заполнено', $data['ошибка']);
    }

    // DELETE /process с нечисловым id возвращает 422
    public function testRemoveProcessWithNonNumericId(): void
    {
        $this->deleteJson('/process', ['id' => 'abc']);

        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('поле id должно быть числом', $data['ошибка']);
    }

    // DELETE /process с несуществующим id возвращает 400
    public function testRemoveProcessWithNonExistentId(): void
    {
        $this->deleteJson('/process', ['id' => 9999]);

        $this->assertResponseStatusCodeSame(400);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('ошибка', $data);
        $this->assertEquals('процесса с таким id нет', $data['ошибка']);
    }

    // DELETE /process с невалидным JSON возвращает 422
    public function testRemoveProcessWithInvalidJson(): void
    {
        $this->client->request(
            'DELETE',
            '/process',
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
}
