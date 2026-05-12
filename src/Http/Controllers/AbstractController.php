<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;

abstract class AbstractController
{
    protected function success($data = null, string $message = 'OK', int $status = 200, array $extra = []): JsonResponse
    {
        $payload = ['status' => true, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        foreach ($extra as $k => $v) {
            $payload[$k] = $v;
        }
        return new JsonResponse($payload, $status);
    }

    protected function error(string $message, int $status = 400, array $extra = []): JsonResponse
    {
        $payload = ['status' => false, 'message' => $message];
        foreach ($extra as $k => $v) {
            $payload[$k] = $v;
        }
        return new JsonResponse($payload, $status);
    }
}
