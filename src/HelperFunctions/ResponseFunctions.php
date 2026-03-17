<?php

namespace App\HelperFunctions;

use App\Entity\Process;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFunctions
{
    public static function error(string $message, int $status = 400): JsonResponse {
        return new JsonResponse(['ошибка' => $message], $status);
    }

    public static function errors(array $errors, int $status = 422): JsonResponse {
        return new JsonResponse(['ошибки' => $errors], $status);
    }

    public static function success(mixed $data, int $status = 200): JsonResponse {
        return new JsonResponse(['данные' => $data], $status);
    }

    public static function printAppStatus(mixed $machineResources): JsonResponse {
        $result = array_map(fn($items) => [
            'id машины'             => $items[0]->getId(),
            'неиспользовано cpu'    => $items[0]->getTotalCpu() - $items[1],
            'неиспользовано memory' => $items[0]->getTotalMemory() - $items[2],
            'процессы'              => array_map(fn($pr) => [
                'id'     => $pr->getId(),
                'cpu'    => $pr->getCpu(),
                'memory' => $pr->getMemory(),
    ], $items[3]),
], $machineResources);

        if (empty($result)) $result = 'пока нет ни одной машины';

        return new JsonResponse([
            'состояние сервиса' => $result
        ], 200);
    } 

    public static function errorDeleteMachine(mixed $orphanedProcesses, int $status = 400): JsonResponse {
        $result = array_map(fn(Process $process) => [
            'id'     => $process->getId(),
            'cpu'    => $process->getCpu(),
            'memory' => $process->getMemory(),
        ], $orphanedProcesses);
        
        return new JsonResponse([
            'машина не удалена - не удалось найти машины для следующих процессов:' => $result
        ], $status);
    } 
}