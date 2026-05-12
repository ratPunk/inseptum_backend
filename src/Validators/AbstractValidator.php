<?php
declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Base class for input validators. Subclasses populate $errors via helper
 * methods and call validate() to throw ValidationException when needed.
 */
abstract class AbstractValidator
{
    /** @var array<string,string> */
    protected array $errors = [];

    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    protected function failIfErrors(string $message = 'Validation failed'): void
    {
        if (!empty($this->errors)) {
            $errors = $this->errors;
            $this->errors = [];
            throw new ValidationException($message, $errors);
        }
    }

    /**
     * Sanitize a username field the same way the legacy code does:
     * strip spaces, single/double quotes and semicolons.
     */
    protected function sanitizeUsername(string $value): string
    {
        return str_replace([' ', "'", '"', ';'], '', $value);
    }

    protected function sanitizePassword(string $value): string
    {
        return str_replace(' ', '', $value);
    }
}
