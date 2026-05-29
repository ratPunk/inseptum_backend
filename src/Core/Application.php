<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;
use Throwable;

class Application
{
    private Container $container;
    private Router $router;
    private Logger $logger;
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath  = rtrim($basePath, '/\\');
        $this->container = new Container();
        $this->router    = new Router();
        $this->logger    = new Logger($this->basePath . '/storage/logs/app.log');

        $this->bootstrap();
    }

    private function bootstrap(): void
    {
        // Определяем окружение: APP_ENV может быть задана на уровне сервера/хостинга.
        $appEnv  = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');
        $envFile = $appEnv !== '' ? "/.env.{$appEnv}" : '/.env';

        // Загружаем .env до чтения любых конфигов, которые могут на него опираться.
        \App\Support\Env::load($this->basePath . $envFile);

        $dbConfig = require $this->basePath . '/config/database.php';
        $database = new Database($dbConfig);

        $this->container->instance(Container::class, $this->container);
        $this->container->instance(Router::class, $this->router);
        $this->container->instance(Logger::class, $this->logger);
        $this->container->instance(Database::class, $database);
        $this->container->instance(Application::class, $this);

        // Конфиг AI отдаём в DI как именованный инстанс (массив).
        $aiConfigFile = $this->basePath . '/config/ai.php';
        $aiConfig = is_file($aiConfigFile) ? (array)require $aiConfigFile : [];
        $this->container->instance('ai.config', $aiConfig);

        // Регистрируем AI-сервисы, которым в конструктор нужен массив-конфиг
        // (контейнер не умеет авто-резолвить скаляры).
        $container = $this->container;
        $logger    = $this->logger;
        $container->bind(\App\Services\Ai\SolutionGuard::class, static function () use ($aiConfig) {
            return new \App\Services\Ai\SolutionGuard($aiConfig);
        });
        $container->bind(\App\Services\Ai\PromptBuilder::class, static function () {
            return new \App\Services\Ai\PromptBuilder();
        });
        $container->bind(\App\Services\Ai\ClaudeHubClient::class, static function ($c) use ($aiConfig, $logger) {
            return new \App\Services\Ai\ClaudeHubClient($aiConfig, $logger);
        });
        $container->bind(\App\Services\Ai\ResponseParser::class, static function () {
            return new \App\Services\Ai\ResponseParser();
        });
        $container->bind(\App\Services\Ai\RateLimiter::class, static function ($c) use ($aiConfig) {
            return new \App\Services\Ai\RateLimiter(
                $c->get(\App\Repositories\AiRateLimitRepository::class),
                $aiConfig
            );
        });
        $container->bind(\App\Services\Ai\CircuitBreaker::class, static function ($c) use ($aiConfig) {
            return new \App\Services\Ai\CircuitBreaker(
                $c->get(\App\Repositories\AiAuditRepository::class),
                $aiConfig
            );
        });
        $container->bind(\App\Services\Ai\TaskCheckerService::class, static function ($c) use ($aiConfig, $logger) {
            return new \App\Services\Ai\TaskCheckerService(
                $aiConfig,
                $c->get(\App\Services\Ai\SolutionGuard::class),
                $c->get(\App\Services\Ai\PromptBuilder::class),
                $c->get(\App\Services\Ai\ClaudeHubClient::class),
                $c->get(\App\Services\Ai\ResponseParser::class),
                $c->get(\App\Services\Ai\RateLimiter::class),
                $c->get(\App\Services\Ai\CircuitBreaker::class),
                $c->get(\App\Repositories\AiCacheRepository::class),
                $c->get(\App\Repositories\AiAuditRepository::class),
                $logger
            );
        });

        $basePath = $this->basePath;
        $logger   = $this->logger;
        $this->container->bind(
            \App\Support\DocxConverter::class,
            static function () use ($basePath, $logger) {
                return new \App\Support\DocxConverter($basePath . '/articlesFolder/', $logger);
            }
        );

        $routesFile = $this->basePath . '/config/routes.php';
        if (is_file($routesFile)) {
            $router = $this->router;
            require $routesFile;
        }
    }

    public function container(): Container { return $this->container; }
    public function router(): Router       { return $this->router; }
    public function logger(): Logger       { return $this->logger; }

    public function run(): void
    {
        try {
            $request = Request::fromGlobals();
            $matched = $this->router->match($request);
            [$class, $method] = $matched['handler'];
            $controller = $this->container->get($class);
            $response = $controller->{$method}($request, $matched['params']);
            if (!$response instanceof Response) {
                $response = new JsonResponse($response);
            }
            $response->send();
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handleException(Throwable $e): void
    {
        $status  = 500;
        $payload = ['status' => false, 'message' => 'Internal Server Error'];

        if ($e instanceof ValidationException) {
            $status  = $e->getStatusCode();
            $payload = ['status' => false, 'message' => $e->getMessage()];
            $errors  = $e->getErrors();
            if (!empty($errors)) {
                $payload['errors'] = $errors;
            }
        } elseif ($e instanceof NotFoundException) {
            $status  = $e->getStatusCode();
            $payload = ['status' => false, 'message' => $e->getMessage()];
        } elseif ($e instanceof AppException) {
            $status  = $e->getStatusCode();
            $payload = ['status' => false, 'message' => $e->getMessage()];
        }

        $this->logger->error($e->getMessage(), [
            'class' => get_class($e),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);

        (new JsonResponse($payload, $status))->send();
    }
}
