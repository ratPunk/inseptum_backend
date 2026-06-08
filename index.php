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

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Logger ─────────────────────────────────────────────────────────────────
use App\Core\Logger;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\ArticleController;
use App\Controllers\AdminArticleController;
use App\Controllers\AdminUserController;
use App\Controllers\TestController;
use App\Controllers\AdminTestController;

$router = new Router();

// Initialize logger and register global PHP error / exception handlers
$logger = Logger::getInstance();
$logger->registerHandlers();
$router->setLogger($logger);

// Auth
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login',    [AuthController::class, 'login']);
$router->get('/api/auth/me',        [AuthController::class, 'me']);
$router->post('/api/auth/logout',   [AuthController::class, 'logout']);

// Categories
$router->get('/api/categories',            [CategoryController::class, 'index']);
$router->get('/api/categories/{id}',       [CategoryController::class, 'show']);
$router->post('/api/categories',           [CategoryController::class, 'create']);
$router->put('/api/categories/{id}',       [CategoryController::class, 'update']);
$router->delete('/api/categories/{id}',    [CategoryController::class, 'delete']);

// Articles
$router->get('/api/articles',                           [ArticleController::class, 'index']);
$router->get('/api/articles/{id}',                      [ArticleController::class, 'show']);
$router->get('/api/articles/{id}/content',              [ArticleController::class, 'content']);
$router->get('/api/articles/{id}/toc',                  [ArticleController::class, 'toc']);
$router->get('/api/articles/images/{articleId}/{filename}', [ArticleController::class, 'serveImage']);
$router->post('/api/articles',                          [ArticleController::class, 'create']);
$router->put('/api/articles/{id}',                      [ArticleController::class, 'update']);
$router->delete('/api/articles/{id}',                   [ArticleController::class, 'delete']);

// Admin Articles (with filtering, sorting, pagination)
$router->get('/api/admin/articles',       [AdminArticleController::class, 'index']);
$router->get('/api/admin/articles/{id}', [AdminArticleController::class, 'show']);
$router->get('/api/admin/articles/{id}/download', [AdminArticleController::class, 'download']);
$router->post('/api/admin/articles',     [AdminArticleController::class, 'create']);
$router->put('/api/admin/articles/{id}', [AdminArticleController::class, 'update']);
$router->delete('/api/admin/articles/{id}', [AdminArticleController::class, 'delete']);
$router->get('/api/admin/categories',    [AdminArticleController::class, 'categories']);

// Admin Users
$router->get('/api/admin/users',              [AdminUserController::class, 'index']);
$router->get('/api/admin/users/{id}',         [AdminUserController::class, 'show']);
$router->post('/api/admin/users',             [AdminUserController::class, 'create']);
$router->put('/api/admin/users/{id}',         [AdminUserController::class, 'update']);
$router->delete('/api/admin/users/{id}',      [AdminUserController::class, 'delete']);
$router->patch('/api/admin/users/{id}/role',  [AdminUserController::class, 'changeRole']);

// Health check
$router->get('/api/health', function () {
    echo json_encode(['status' => 'ok', 'time' => date('c')]);
    exit;
});

// ── Temporary upload diagnostics (remove after fix is confirmed) ─────────────
$router->get('/api/debug/upload', function () {
    $storageBase = realpath(__DIR__ . '/storage') ?: __DIR__ . '/storage';
    $articlesDir = $storageBase . '/articles';
    $logsDir     = $storageBase . '/logs';
    $testsDir    = $storageBase . '/tests';

    $stat = function (string $dir): array {
        if (!is_dir($dir)) {
            return ['exists' => false];
        }
        return [
            'exists'    => true,
            'writable'  => is_writable($dir),
            'perms'     => substr(sprintf('%o', fileperms($dir)), -4),
            'owner_uid' => fileowner($dir),
            'owner_gid' => filegroup($dir),
        ];
    };

    // ── Попытка реально записать файл ────────────────────────────────────────
    $testFile   = $articlesDir . '/_write_test_' . time() . '.tmp';
    $writeOk    = false;
    $writeError = null;
    error_clear_last();
    $writeOk  = @file_put_contents($testFile, 'test') !== false;
    $writeError = error_get_last();
    if ($writeOk) {
        @unlink($testFile);
    }

    // ── uid/gid процесса через /proc/self/status ──────────────────────────────
    $procUid = null;
    $procGid = null;
    if (is_readable('/proc/self/status')) {
        $status = file_get_contents('/proc/self/status');
        if (preg_match('/^Uid:\s+(\d+)/m', $status, $m)) {
            $procUid = (int)$m[1];
        }
        if (preg_match('/^Gid:\s+(\d+)/m', $status, $m)) {
            $procGid = (int)$m[1];
        }
    }

    // ── uid/gid через shell (если разрешён) ───────────────────────────────────
    $shellId = null;
    if (function_exists('shell_exec')) {
        $shellId = trim((string)@shell_exec('id 2>/dev/null'));
    }

    echo json_encode([
        '__DIR__'                => __DIR__,
        'storage_base'           => $storageBase,
        'articles'               => $stat($articlesDir),
        'logs'                   => $stat($logsDir),
        'tests'                  => $stat($testsDir),
        'write_test'             => [
            'success'   => $writeOk,
            'php_error' => $writeError,
        ],
        'process_uid'            => $procUid,
        'process_gid'            => $procGid,
        'shell_id'               => $shellId,
        'upload_tmp_dir'         => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        'upload_tmp_writable'    => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()),
        'upload_max_filesize'    => ini_get('upload_max_filesize'),
        'post_max_size'          => ini_get('post_max_size'),
        'open_basedir'           => ini_get('open_basedir') ?: '(none)',
        'php_version'            => PHP_VERSION,
        'os'                     => PHP_OS,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
});

// Tests
$router->get('/api/tests',                      [TestController::class, 'index']);
$router->get('/api/tests/{id}',                 [TestController::class, 'show']);
$router->get('/api/tests/{id}/content',         [TestController::class, 'content']);
$router->post('/api/tests/{id}/start',          [TestController::class, 'start']);
$router->post('/api/tests/{id}/submit',         [TestController::class, 'submit']);
$router->get('/api/tests/{id}/progress',        [TestController::class, 'progress']);
$router->get('/api/user/tests-progress',        [TestController::class, 'userProgress']);

// Admin Tests
$router->get('/api/admin/tests',                      [AdminTestController::class, 'index']);
$router->get('/api/admin/tests/{id}',                 [AdminTestController::class, 'show']);
$router->get('/api/admin/tests/{id}/download',        [AdminTestController::class, 'download']);
$router->post('/api/admin/tests',                     [AdminTestController::class, 'create']);
$router->post('/api/admin/tests/{id}',                [AdminTestController::class, 'update']);
$router->put('/api/admin/tests/{id}',                 [AdminTestController::class, 'update']);
$router->delete('/api/admin/tests/{id}',              [AdminTestController::class, 'delete']);

$router->dispatch();
