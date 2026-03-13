<?php

namespace App\HelperFunctions;

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

    public static function errorDeleteMachine($orphanedProcesses, int $status = 400): JsonResponse {
        return new JsonResponse([
            'не удалось удалить машину, ' => $orphanedProcesses
        ]);
    } 
}