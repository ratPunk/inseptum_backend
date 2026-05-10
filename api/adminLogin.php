<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function adminLogin($connect, $username, $password){

    if(empty($username) || empty($password)){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Данные пользователя не заполнены"
        ];
        return json_encode($res);
    }

    $username = mysqli_real_escape_string($connect, $username);
    $charsToRemove = [" ", "'", "\"", ";"];
    $username = str_replace($charsToRemove, '', $username);

    $password = mysqli_real_escape_string($connect, $password);
    $password = str_replace(" ", "", $password);

    $admin = mysqli_query($connect, "SELECT * FROM admins WHERE username = '".$username."'");
    $admin = mysqli_fetch_assoc($admin);

    if(!$admin){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Пользователь не найден"
        ];
        return json_encode($res);
    }
    
    if(!password_verify($password, $admin["password"])){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Неверный пароль"
        ];
        return json_encode($res);
    }
    
    http_response_code(200);
    $res = [
        "status" => true,
        "message" => "admin logged in",
        "data" => [
            'user_id' => $admin['id'],
            'username' => $admin['username']
        ]
    ];
    
    return json_encode($res);
}