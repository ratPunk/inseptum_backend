<?php
declare(strict_types=1);

namespace App\Exceptions;

class DatabaseException extends AppException
{
    public function __construct(string $message = 'Database error', int $statusCode = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
