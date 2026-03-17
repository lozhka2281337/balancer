<?php

namespace App\Tests\Unit\HelperFunctions;

use App\Entity\Machine;
use App\Entity\Process;
use App\HelperFunctions\ResponseFunctions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFunctionsTest extends TestCase
{
    // error возвращает JsonResponse с кодом 400 по умолчанию
    public function testErrorReturnsJsonResponseWithDefaultStatus(): void
    {
        $response = ResponseFunctions::error('something went wrong');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('something went wrong', $data['ошибка']);
    }

    // error возвращает JsonResponse с кастомным статусом
    public function testErrorReturnsJsonResponseWithCustomStatus(): void
    {
        $response = ResponseFunctions::error('not found', 404);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('not found', $data['ошибка']);
    }

    // errors возвращает JsonResponse со списком ошибок и кодом 422 по умолчанию
    public function testErrorsReturnsJsonResponseWithDefaultStatus(): void
    {
        $errors = ['поле cpu обязательно', 'поле memory обязательно'];
        $response = ResponseFunctions::errors($errors);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data['ошибки']);
        $this->assertCount(2, $data['ошибки']);
    }

    // errors возвращает JsonResponse с кастомным статусом
    public function testErrorsReturnsJsonResponseWithCustomStatus(): void
    {
        $response = ResponseFunctions::errors(['ошибка валидации'], 400);

        $this->assertEquals(400, $response->getStatusCode());
    }

    // success возвращает JsonResponse с данными и кодом 200 по умолчанию
    public function testSuccessReturnsJsonResponseWithDefaultStatus(): void
    {
        $data = ['id' => 1, 'cpu' => 10];
        $response = ResponseFunctions::success($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $decoded = json_decode($response->getContent(), true);
        $this->assertEquals($data, $decoded['данные']);
    }

    // success возвращает JsonResponse с кастомным статусом
    public function testSuccessReturnsJsonResponseWithCustomStatus(): void
    {
        $response = ResponseFunctions::success(['id' => 5], 201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    // printAppStatus возвращает JsonResponse с состоянием сервиса
    public function testPrintAppStatusReturnsJsonResponse(): void
    {
        $machine = new Machine();
        $machine->setTotalCpu(100);
        $machine->setTotalMemory(200);

        $process = new Process();
        $process->setCpu(30);
        $process->setMemory(60);

        // machineResources: [[машина, usedCpu, usedMemory, [процессы]]]
        $machineResources = [
            [$machine, 30, 60, [$process]],
        ];

        $response = ResponseFunctions::printAppStatus($machineResources);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('состояние сервиса', $data);

        $status = $data['состояние сервиса'];
        $this->assertCount(1, $status);
        $this->assertEquals(70, $status[0]['неиспользовано cpu']);
        $this->assertEquals(140, $status[0]['неиспользовано memory']);
        $this->assertCount(1, $status[0]['процессы']);
        $this->assertEquals(30, $status[0]['процессы'][0]['cpu']);
        $this->assertEquals(60, $status[0]['процессы'][0]['memory']);
    }

    // printAppStatus возвращает JsonResponse для пустого списка машин
    public function testPrintAppStatusWithEmptyMachineResources(): void
    {
        $response = ResponseFunctions::printAppStatus([]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['состояние сервиса']);
    }

    // errorDeleteMachine возвращает JsonResponse с осиротевшими процессами
    public function testErrorDeleteMachineReturnsJsonResponse(): void
    {
        $process = new Process();
        $process->setCpu(20);
        $process->setMemory(40);

        $response = ResponseFunctions::errorDeleteMachine([$process]);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $key = 'машина не удалена - не удалось найти машины для следующих процессов:';
        $this->assertArrayHasKey($key, $data);
        $this->assertCount(1, $data[$key]);
        $this->assertEquals(20, $data[$key][0]['cpu']);
        $this->assertEquals(40, $data[$key][0]['memory']);
    }

    // errorDeleteMachine возвращает JsonResponse с кастомным статусом
    public function testErrorDeleteMachineWithCustomStatus(): void
    {
        $response = ResponseFunctions::errorDeleteMachine([], 422);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
