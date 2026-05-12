<?php
declare(strict_types=1);

/**
 * Front controller. All HTTP requests are routed here by .htaccess.
 */

// CORS — keep parity with legacy index.php
header('Access-Control-Allow-Origin: *');  // TODO: replace with concrete origin in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../vendor/autoload.php';

$app = new \App\Core\Application(dirname(__DIR__));
$app->run();
