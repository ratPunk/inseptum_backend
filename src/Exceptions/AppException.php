<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Base application exception. Carries an HTTP status code.
 */
class AppException extends Exception
{
    protected int $statusCode = 500;

    public function __construct(string $message = '', int $statusCode = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
