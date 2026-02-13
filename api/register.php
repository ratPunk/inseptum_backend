<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function registerUser($connect, $username, $password, $confirmPassword){

    if(empty($username) || empty($password) || empty($confirmPassword)){

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

    if(mb_strlen($username) < 3 || mb_strlen($username) > 20){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Неверная длина имени"
        ];
        return json_encode($res);
    }

    $user = mysqli_query($connect, "SELECT * FROM users WHERE username = '".$username."'");
    if(mysqli_num_rows($user) > 0){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Пользователь с таким именем уже существует"
        ];
        return json_encode($res);
    }

    $password = mysqli_real_escape_string($connect, $password);
    $password = str_replace(" ", "", $password);

    $confirmPassword = mysqli_real_escape_string($connect, $confirmPassword);
    $confirmPassword = str_replace(" ", "", $confirmPassword);

    if(mb_strlen($password) < 3){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Пароль слишком простой"
        ];
        return json_encode($res);
    }

    if($password != $confirmPassword){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Пароли не совпадают"
        ];
        return json_encode($res);
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    $createUser = mysqli_query($connect, "INSERT INTO users (username, password) VALUES ('$username', '$password')");
    
    if($createUser){
        $userId = mysqli_insert_id($connect);
        http_response_code(201);
        $res = [
            "status" => true,
            "message" => "User registered",
            "data" => [
                'user_id' => $userId,
                'username' => $username,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }

    return json_encode($res);
}