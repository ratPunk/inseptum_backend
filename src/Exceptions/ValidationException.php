<?php
declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends HttpException
{
    private array $errors;

    public function __construct(string $message = 'Validation failed', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 400, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
