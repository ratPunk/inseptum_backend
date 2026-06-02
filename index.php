<?php

declare(strict_types=1);

// ─── Autoloader ──────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    // Namespace prefix: App\ → backend/
    $prefix = 'App\\';
    $base   = __DIR__ . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', strtolower(explode('\\', $relative)[0])) . '/'
              . implode('/', array_slice(explode('\\', $relative), 1)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// ─── Environment ─────────────────────────────────────────────────────────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// ─── CORS ────────────────────────────────────────────────────────────────────
$allowedOrigins = array_filter(array_map(
    'trim',
    explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173')
));

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true) || in_array('*', $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
} else {
    header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? ''));
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Routes ──────────────────────────────────────────────────────────────────
use App\Core\Router;
use App\Controllers\AuthController;

$router = new Router();

$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login',    [AuthController::class, 'login']);
$router->get('/api/auth/me',        [AuthController::class, 'me']);
$router->post('/api/auth/logout',   [AuthController::class, 'logout']);

// Health check
$router->get('/api/health', function () {
    echo json_encode(['status' => 'ok', 'time' => date('c')]);
    exit;
});

$router->dispatch();
