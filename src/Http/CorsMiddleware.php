<?php
declare(strict_types=1);

namespace App\Http;

class CorsMiddleware
{
    /** @var list<string> Allowed origins ('*' = any) */
    private array $allowedOrigins;

    /** @var string */
    private string $allowedMethods = 'GET, POST, PUT, DELETE, OPTIONS';

    /** @var string */
    private string $allowedHeaders = 'Content-Type, Authorization, X-Requested-With';

    /**
     * @param list<string> $allowedOrigins  e.g. ['https://inseptum.ru'] or ['*']
     */
    public function __construct(array $allowedOrigins = ['*'])
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * Send CORS headers and handle preflight.
     * Call this BEFORE any routing / controller logic.
     *
     * @return bool true if this was a preflight OPTIONS request (caller should exit)
     */
    public function handle(): bool
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Determine the value for Access-Control-Allow-Origin
        if (in_array('*', $this->allowedOrigins, true)) {
            $allowOrigin = '*';
        } elseif ($origin !== '' && in_array($origin, $this->allowedOrigins, true)) {
            $allowOrigin = $origin;
        } else {
            // Origin not in whitelist — still send headers so the browser gets
            // a proper CORS rejection instead of a network error.
            $allowOrigin = $this->allowedOrigins[0] ?? '';
        }

        header("Access-Control-Allow-Origin: {$allowOrigin}");
        header("Access-Control-Allow-Methods: {$this->allowedMethods}");
        header("Access-Control-Allow-Headers: {$this->allowedHeaders}");
        header('Access-Control-Max-Age: 86400');

        if (($allowOrigin !== '*') && $origin !== '') {
            header('Vary: Origin');
        }

        // Handle preflight
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            return true;
        }

        return false;
    }
}
