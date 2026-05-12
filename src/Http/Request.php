<?php
declare(strict_types=1);

namespace App\Http;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $server;
    private array $headers;
    private array $files;

    public function __construct(string $method, string $path, array $query, array $body, array $server = [], array $headers = [], array $files = [])
    {
        $this->method  = strtoupper($method);
        $this->path    = '/' . ltrim($path, '/');
        $this->query   = $query;
        $this->body    = $body;
        $this->server  = $server;
        $this->headers = $headers;
        $this->files   = $files;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // When .htaccess rewrites with ?url=..., honour that to be base-path agnostic.
        if (isset($_GET['url']) && $_GET['url'] !== '') {
            $path = '/' . ltrim((string)$_GET['url'], '/');
        } else {
            $uri  = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        }

        $query = $_GET;
        unset($query['url']);

        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $body = array_merge($body, $decoded);
                }
            }
        }

        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];

        return new self($method, $path, $query, $body, $_SERVER, $headers, $_FILES);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function method(): string { return $this->method; }
    public function path(): string   { return $this->path; }

    public function query(?string $key = null, $default = null)
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function input(?string $key = null, $default = null)
    {
        return $key === null ? $this->body : ($this->body[$key] ?? $default);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $name, $default = null)
    {
        return $this->headers[$name] ?? $this->headers[strtolower($name)] ?? $default;
    }
}
