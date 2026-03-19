<?php

namespace App\Tests\Integration;

use App\Entity\Machine;
use App\Entity\Process;
use App\Tests\ControllerTestCase;

class ProcessAllocationTest extends ControllerTestCase
{
    // создание машины, добавление процесса и проверка состояния
    public function testFullAllocationScenario(): void
    {
        // создаём машину
        $this->postJson('/machine', ['cpu' => 8, 'memory' => 16]);
        $this->assertResponseStatusCodeSame(201);
        $machineData = $this->getResponseData();
        $machineId = $machineData['id машины'];

        // добавляем процесс
        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);
        $this->assertResponseStatusCodeSame(200);
        $processData = $this->getResponseData();
        $this->assertEquals($machineId, $processData['расположен на машине с id']);

        // проверяем состояние
        $this->client->request('GET', '/status');
        $this->assertResponseStatusCodeSame(200);
        $statusData = $this->getResponseData();

        $this->assertIsArray($statusData['состояние сервиса']);
        $this->assertCount(1, $statusData['состояние сервиса']);
        $this->assertCount(1, $statusData['состояние сервиса'][0]['процессы']);
        $this->assertEquals(6, $statusData['состояние сервиса'][0]['неиспользовано cpu']);
        $this->assertEquals(12, $statusData['состояние сервиса'][0]['неиспользовано memory']);
    }

    // добавление процесса и его удаление восстанавливает свободные ресурсы
    public function testProcessDeletionRestoresFreeResources(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(8);
        $machine->setTotalMemory(16);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        // добавляем процесс
        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);
        $this->assertResponseStatusCodeSame(200);
        $processId = $this->getResponseData()['id процесса'];

        // удаляем процесс
        $this->deleteJson('/process', ['id' => $processId]);
        $this->assertResponseStatusCodeSame(200);

        // проверяем, что ресурсы освободились
        $this->client->request('GET', '/status');
        $statusData = $this->getResponseData();

        $this->assertEquals(8, $statusData['состояние сервиса'][0]['неиспользовано cpu']);
        $this->assertEquals(16, $statusData['состояние сервиса'][0]['неиспользовано memory']);
        $this->assertCount(0, $statusData['состояние сервиса'][0]['процессы']);
    }

    // удаление машины с процессом завершается ошибкой при отсутствии другой машины
    public function testMachineDeletionFailsWithOrphanedProcesses(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(4);
        $machine->setTotalMemory(8);
        $this->entityManager->persist($machine);

        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($machine);
        $this->entityManager->persist($process);
        $this->entityManager->flush();

        // пытаемся удалить единственную машину с процессом
        $this->deleteJson('/machine', ['id' => $machine->getId()]);
        $this->assertResponseStatusCodeSame(422);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('машина не удалена - не удалось найти машины для следующих процессов:', $data);
        $this->assertCount(1, $data['машина не удалена - не удалось найти машины для следующих процессов:']);
    }

    // удаление машины с процессом успешно переносит процесс на другую машину
    public function testMachineDeletionWithProcessRelocation(): void
    {
        // создаём две машины
        $machine1 = new Machine();
        $machine1->setTotalCpu(4);
        $machine1->setTotalMemory(8);
        $this->entityManager->persist($machine1);

        $machine2 = new Machine();
        $machine2->setTotalCpu(8);
        $machine2->setTotalMemory(16);
        $this->entityManager->persist($machine2);

        $process = new Process();
        $process->setCpu(2);
        $process->setMemory(4);
        $process->setMachine($machine1);
        $this->entityManager->persist($process);

        $this->entityManager->flush();

        // удаляем первую машину — процесс должен переехать на вторую
        $this->deleteJson('/machine', ['id' => $machine1->getId()]);
        $this->assertResponseStatusCodeSame(201);

        $data = $this->getResponseData();
        $this->assertEquals('машина успешно удалена', $data['данные']);

        // проверяем, что процесс теперь на второй машине
        $this->client->request('GET', '/status');
        $statusData = $this->getResponseData();

        $this->assertCount(1, $statusData['состояние сервиса']);
        $this->assertCount(1, $statusData['состояние сервиса'][0]['процессы']);
        $this->assertEquals($machine2->getId(), $statusData['состояние сервиса'][0]['id машины']);
    }

    // добавление нескольких процессов распределяет их по машинам
    public function testMultipleProcessesAllocatedToMachines(): void
    {
        // создаём машину с достаточными ресурсами
        $machine = new Machine();
        $machine->setTotalCpu(16);
        $machine->setTotalMemory(32);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        // добавляем три процесса
        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);
        $this->assertResponseStatusCodeSame(200);

        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);
        $this->assertResponseStatusCodeSame(200);

        $this->postJson('/process', ['cpu' => 2, 'memory' => 4]);
        $this->assertResponseStatusCodeSame(200);

        // проверяем состояние
        $this->client->request('GET', '/status');
        $statusData = $this->getResponseData();

        $this->assertCount(3, $statusData['состояние сервиса'][0]['процессы']);
        $this->assertEquals(10, $statusData['состояние сервиса'][0]['неиспользовано cpu']);
        $this->assertEquals(20, $statusData['состояние сервиса'][0]['неиспользовано memory']);
    }
}
