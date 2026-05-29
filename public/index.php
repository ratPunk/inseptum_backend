<?php
declare(strict_types=1);

/**
 * Front controller. All HTTP requests are routed here by .htaccess.
 */

require __DIR__ . '/../vendor/autoload.php';

// CORS middleware — handles headers + preflight for every request
$cors = new \App\Http\CorsMiddleware(['https://inseptum.ru', 'http://localhost:5173']);
if ($cors->handle()) {
    exit(); // preflight — nothing else to do
}

$app = new \App\Core\Application(dirname(__DIR__));
$app->run();
