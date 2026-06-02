<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Minimal JWT implementation (HS256) without external dependencies.
 * For production with high security requirements, use firebase/php-jwt.
 */
class JwtHelper
{
    private string $secret;
    private int    $ttl; // seconds

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'change_me_in_env';
        $this->ttl    = (int)($_ENV['JWT_TTL'] ?? 3600);
    }

    /**
     * Generate a signed JWT token.
     */
    public function generate(array $payload): string
    {
        $header = $this->base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $payload['iat'] = time();
        $payload['exp'] = time() + $this->ttl;

        $body      = $this->base64url(json_encode($payload));
        $signature = $this->base64url(hash_hmac('sha256', "{$header}.{$body}", $this->secret, true));

        return "{$header}.{$body}.{$signature}";
    }

    /**
     * Verify and decode a JWT token.
     * Returns the payload array or null if invalid/expired.
     */
    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $body, $signature] = $parts;

        $expectedSig = $this->base64url(hash_hmac('sha256', "{$header}.{$body}", $this->secret, true));

        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $payload = json_decode($this->base64urlDecode($body), true);

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Extract the bearer token from the Authorization header.
     */
    public function fromHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
