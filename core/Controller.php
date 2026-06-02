<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Send a JSON success response.
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send a JSON error response.
     */
    protected function error(string $message, int $status = 400): void
    {
        $this->json(['error' => $message], $status);
    }

    /**
     * Get and decode the JSON request body.
     */
    protected function getBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON body', 400);
        }

        return $data ?? [];
    }

    /**
     * Validate required fields in an array.
     * Returns missing field names or empty array if all present.
     */
    protected function requireFields(array $data, array $fields): array
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}
