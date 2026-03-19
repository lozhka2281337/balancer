<?php

namespace App\Tests\Controller;

use App\Entity\Machine;
use App\Tests\ControllerTestCase;

class AppControllerTest extends ControllerTestCase
{
    // GET /status возвращает 200 и пустое состояние при отсутствии машин
    public function testGetStatusReturnsOkWithNoMachines(): void
    {
        $this->client->request('GET', '/status');

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('состояние сервиса', $data);
        $this->assertEquals('пока нет ни одной машины', $data['состояние сервиса']);
    }

    // GET /status возвращает 200 и список машин при наличии машин
    public function testGetStatusReturnsOkWithMachines(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(8);
        $machine->setTotalMemory(16);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $this->client->request('GET', '/status');

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('состояние сервиса', $data);
        $this->assertIsArray($data['состояние сервиса']);
        $this->assertCount(1, $data['состояние сервиса']);
    }

    // GET /status отображает машины, отсортированные по нагрузке
    public function testGetStatusReturnsMachinesSortedByLoad(): void
    {
        $machine1 = new Machine();
        $machine1->setTotalCpu(4);
        $machine1->setTotalMemory(8);
        $this->entityManager->persist($machine1);

        $machine2 = new Machine();
        $machine2->setTotalCpu(16);
        $machine2->setTotalMemory(32);
        $this->entityManager->persist($machine2);

        $this->entityManager->flush();

        $this->client->request('GET', '/status');

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getResponseData();
        $this->assertArrayHasKey('состояние сервиса', $data);
        $this->assertCount(2, $data['состояние сервиса']);
    }

    // GET /status показывает правильные поля для каждой машины
    public function testGetStatusReturnsCorrectMachineFields(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(8);
        $machine->setTotalMemory(16);
        $this->entityManager->persist($machine);
        $this->entityManager->flush();

        $this->client->request('GET', '/status');

        $this->assertResponseStatusCodeSame(200);

        $data = $this->getResponseData();
        $machineData = $data['состояние сервиса'][0];

        $this->assertArrayHasKey('id машины', $machineData);
        $this->assertArrayHasKey('неиспользовано cpu', $machineData);
        $this->assertArrayHasKey('неиспользовано memory', $machineData);
        $this->assertArrayHasKey('процессы', $machineData);
        $this->assertEquals(8, $machineData['неиспользовано cpu']);
        $this->assertEquals(16, $machineData['неиспользовано memory']);
    }
}
