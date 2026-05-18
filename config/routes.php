<?php
declare(strict_types=1);

/**
 * Application route definitions.
 *
 * @var \App\Core\Router $router  (provided by Application::bootstrap())
 */

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ModuleTypeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;

// ---------- Module Types ----------
$router->get('/module-types',           [ModuleTypeController::class, 'index']);
$router->get('/module-types/{id}',      [ModuleTypeController::class, 'show']);
$router->post('/createmoduletype',      [ModuleTypeController::class, 'create']);
$router->post('/updatemoduletype',      [ModuleTypeController::class, 'update']);
$router->post('/updatemoduletype/{id}', [ModuleTypeController::class, 'update']);
$router->put('/updatemoduletype/{id}',  [ModuleTypeController::class, 'update']);
$router->post('/deletemoduletype',      [ModuleTypeController::class, 'delete']);
$router->post('/deletemoduletype/{id}', [ModuleTypeController::class, 'delete']);
$router->delete('/deletemoduletype/{id}', [ModuleTypeController::class, 'delete']);

// ---------- Modules ----------
$router->get('/modules',           [ModuleController::class, 'index']);
$router->get('/modules/{id}',      [ModuleController::class, 'show']);
$router->post('/createmodule',     [ModuleController::class, 'create']);
$router->post('/updatemodule',     [ModuleController::class, 'update']);
$router->post('/deletemodule',     [ModuleController::class, 'delete']);

// ---------- Articles ----------
// Legacy semantics:
//   GET /articles           -> all articles
//   GET /article/{id}       -> single article
$router->get('/articles',          [ArticleController::class, 'index']);
$router->get('/article/{id}',      [ArticleController::class, 'show']);
$router->post('/createarticle',    [ArticleController::class, 'create']);
$router->post('/updatearticle',    [ArticleController::class, 'update']);
$router->post('/deletearticle',    [ArticleController::class, 'delete']);

// ---------- Article File / Read progress ----------
$router->get('/articlefile/{id}',                 [ArticleController::class, 'file']);
$router->get('/readarticle/{id}/{user_id}',       [ArticleController::class, 'readShow']);
$router->post('/readarticle',                     [ArticleController::class, 'readMark']);

// ---------- Tests ----------
$router->get('/tests',             [TestController::class, 'index']);
$router->get('/tests/{id}',        [TestController::class, 'show']);
$router->post('/createtest',       [TestController::class, 'create']);
$router->post('/updatetest',       [TestController::class, 'update']);
$router->post('/updatetest/{id}',  [TestController::class, 'update']);
$router->post('/tests',            [TestController::class, 'create']);
$router->post('/tests/{id}',       [TestController::class, 'update']);
$router->delete('/tests/{id}',     [TestController::class, 'delete']);

// ---------- Test File / Results / Passed ----------
$router->post('/gettestfile',      [TestController::class, 'file']);
$router->post('/gettestresults',   [TestController::class, 'results']);
$router->post('/setpassedtest',    [TestController::class, 'setPassed']);
$router->post('/getpassedtest',    [TestController::class, 'getPassed']);
// Batch: список ID всех пройденных тестов пользователя.
$router->post('/getpassedtests',   [TestController::class, 'getPassedList']);

// ---------- Tasks ----------
$router->get('/tasks',             [TaskController::class, 'index']);
$router->get('/tasks/{id}',        [TaskController::class, 'show']);
$router->post('/checktask',        [TaskController::class, 'check']);
$router->post('/createtask',       [TaskController::class, 'create']);
$router->post('/updatetask',       [TaskController::class, 'update']);
$router->post('/updatetask/{id}',  [TaskController::class, 'update']);
$router->post('/deletetask',       [TaskController::class, 'delete']);
$router->post('/deletetask/{id}',  [TaskController::class, 'delete']);
$router->delete('/deletetask/{id}',[TaskController::class, 'delete']);
// Прохождение задач:
$router->post('/setpassedtask',    [TaskController::class, 'setPassed']);
$router->post('/getpassedtask',    [TaskController::class, 'getPassed']);
// Batch: список ID всех пройденных задач пользователя.
$router->post('/getpassedtasks',   [TaskController::class, 'getPassedList']);

// ---------- Favorites ----------
$router->post('/getfavorite',      [FavoriteController::class, 'index']);
$router->post('/setfavorite',      [FavoriteController::class, 'toggle']);

// ---------- Auth (Stage 2) ----------
$router->post('/register',         [AuthController::class, 'register']);
$router->post('/login',            [AuthController::class, 'login']);

// ---------- Users (admin CRUD) ----------
$router->get('/users',             [UserController::class, 'index']);
$router->get('/users/{id}',        [UserController::class, 'show']);
$router->post('/createuser',       [UserController::class, 'create']);
$router->post('/updateuser',       [UserController::class, 'update']);
$router->post('/updateuser/{id}',  [UserController::class, 'update']);
$router->post('/deleteuser',       [UserController::class, 'delete']);
$router->post('/deleteuser/{id}',  [UserController::class, 'delete']);
$router->delete('/deleteuser/{id}',[UserController::class, 'delete']);
