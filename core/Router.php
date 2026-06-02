<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route.
     */
    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route.
     */
    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'handler' => $handler,
        ];
    }

    /**
     * Dispatch the current request to the matching route.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base path prefix if running in a subdirectory
        $basePath = $_ENV['APP_BASE_PATH'] ?? '';
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPath($route['path'], $uri);
            if ($params !== null) {
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }

    /**
     * Match a route pattern against a URI.
     * Returns an array of named params on match, or null on no match.
     * Supports {param} placeholders.
     */
    private function matchPath(string $pattern, string $uri): ?array
    {
        $pattern = '/^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', str_replace('/', '\/', $pattern)) . '$/';

        if (preg_match($pattern, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->$method($params);
        } else {
            $handler($params);
        }
    }
}
