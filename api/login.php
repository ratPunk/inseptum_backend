<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function loginUser($connect, $username, $password){

    if(empty($username) || empty($password) || empty($confirmPassword)){

        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Данные пользователя не заполнены"
        ];
    }

    $username = mysqli_real_escape_string($connect, $username);
    $charsToRemove = [" ", "'", "\"", ";"];
    $username = str_replace($charsToRemove, '', $username);

    $password = mysqli_real_escape_string($connect, $password);
    $password = str_replace(" ", "", $password);

    $user = mysqli_query($connect, "SELECT * FROM users WHERE username = '".$username."'");
    $user = mysqli_fetch_assoc($user);
    if($user === null){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Пользователь не найден"
        ];
    }elseif(!password_verify($password, $user["password"])){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Неверные данные"
        ];
    }else{
        http_response_code(200);
        $res = [
            "status" => true,
            "message" => "User logged in",
            "data" => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'created_at' => $user['created_at']
            ]
        ];
    }

    return json_encode($res);
}