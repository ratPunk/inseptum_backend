<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getUsers($connect){
    $users = mysqli_query($connect, "SELECT * FROM users");
    if(mysqli_num_rows($users) === 0){
        http_response_code(404);
        $res = [
            "status" => false,
            "message" => "Users not found"
        ];
    }else{
        $users = mysqli_fetch_all($users);
        $res = [
            "status" => true,
            "message" => "Users found",
            "data" => $users
        ];
        
    }
    return json_encode($res);
}

function createUser($connect, $username, $password){

    if(empty($username) || empty($password)){

        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Данные пользователя не заполнены"
        ];
        return json_encode($res);

    }
    elseif(mb_strlen($username) < 3 || mb_strlen($password) < 3 || mb_strlen($username) > 20){

        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Неверная длина имени или пароля"
        ];
        return json_encode($res);
    
    }

    $username = mysqli_real_escape_string($connect, $username);
    $charsToRemove = [" ", "'", "\"", ";"];
    $username = str_replace($charsToRemove, '', $username);

    $user = mysqli_query($connect, "SELECT * FROM users WHERE username = '".$username."'");

    if(mysqli_num_rows($user) > 0){
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Пользователь с таким именем уже существует"
        ];
    }

    $password = mysqli_real_escape_string($connect, $password);
    $password = str_replace(" ", "", $password);
    $password = password_hash($password, PASSWORD_DEFAULT);

    $createUser = mysqli_query($connect, "INSERT INTO users (username, password) VALUES ('".$username."','".$password."')");
    

    if($createUser === true){
        http_response_code(201);
        $res = [
            "status" => true,
            "message" => "User created"
        ];
    }else{
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "User not created"
        ];
    }
    return json_encode($res);
}

