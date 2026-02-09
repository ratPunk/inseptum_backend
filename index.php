<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // В продакшене заменить на конкретный домен
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database.php';
require __DIR__ . '/controller.php';
require __DIR__ . '/api/register.php';
require __DIR__ . '/api/login.php';

$method = $_SERVER['REQUEST_METHOD'];
$url = $_GET['url'] ?? '';
$params = $url ? explode('/', trim($url, '/')) : [];

$type = $params[0] ?? '';
$id = $params[1] ?? '';

if($method == 'GET') {
    if($type == 'users'){
        $response = getUsers($connect);
        echo $response;
    }
}elseif($method == 'POST') {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if($type == 'users'){
        $response  = createUser($connect, $username, $password);
        echo $response;
    }elseif($type == 'register'){
        $response = registerUser($connect, $username, $password, $confirmPassword);
        echo $response;
    }elseif($type == 'login'){
        $response = loginUser($connect, $username, $password);
        echo $response;
    }
}