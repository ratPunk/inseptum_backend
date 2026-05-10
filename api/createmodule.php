<?php
function createmodule($connect, $form_data) {

    if (!$form_data || !$form_data['title'] || !$form_data['description']) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Не все поля заполнены"
        ], JSON_UNESCAPED_UNICODE);
    }

    
    // Выполняем создание
    $create_sql = "INSERT INTO `modules` (`title`, `description`) VALUES ('{$form_data['title']}', '{$form_data['description']}')";
    $create_query = mysqli_query($connect, $create_sql);

    if (!$create_query) {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при создании модуля: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }

    $newId = mysqli_insert_id($connect);

    $query = mysqli_query($connect, "SELECT * FROM `modules` where id = $newId");
    $module_data = mysqli_fetch_assoc($query);

    if(!$module_data){
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при создании модуля: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }

    $modules = [
            "id" => $module_data['id'],
            "title" => $module_data['title'],
            "slug" => strtolower($module_data['title']),
            "description" => $module_data['description']
        ];
    
    if ($modules) {
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Модуль успешно создан",
            "data" => $modules
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при создании модуля: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
}