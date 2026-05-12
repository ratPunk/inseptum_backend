<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use App\Http\Request;

/**
 * Lightweight router with `{param}` placeholder support.
 * Handlers are stored as [ControllerClass::class, 'method'].
 */
class Router
{
    /** @var array<string, array<int, array{pattern:string, params:string[], handler:array}>> */
    private array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $method = strtoupper($method);
        $params = [];
        $pattern = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';
        $this->routes[$method][] = ['pattern' => $pattern, 'params' => $params, 'handler' => $handler];
    }

    public function get(string $path, array $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, array $handler): void    { $this->add('PUT', $path, $handler); }
    public function delete(string $path, array $handler): void { $this->add('DELETE', $path, $handler); }

    /**
     * @return array{handler: array, params: array<string,string>}
     */
    public function match(Request $request): array
    {
        $method = $request->method();
        $path   = rtrim($request->path(), '/');
        if ($path === '') {
            $path = '/';
        }
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['params'] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }
                return ['handler' => $route['handler'], 'params' => $params];
            }
        }
        throw new NotFoundException("Route not found: $method $path");
    }
}
