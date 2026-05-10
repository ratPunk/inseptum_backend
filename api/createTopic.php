<?php
function createTopic($connect, $form_data) {

    if (!$form_data || !$form_data['title'] || !$form_data['description'] || !$form_data['module_id']) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Не все поля заполнены"
        ], JSON_UNESCAPED_UNICODE);
    }

    $form_data['module_id'] = (int)$form_data['module_id'];
    
    // Выполняем создание
    $create_sql = "INSERT INTO `topics` (`module_id`, `title`, `description`) VALUES ('{$form_data['module_id']}', '{$form_data['title']}', '{$form_data['description']}')";
    $create_query = mysqli_query($connect, $create_sql);

    if (!$create_query) {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при создании темы: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }

    $newId = mysqli_insert_id($connect);

    // Получаем созданную тему вместе с названием модуля
    $query = mysqli_query($connect, "SELECT topics.*, modules.title AS module_title FROM topics LEFT JOIN modules ON topics.module_id = modules.id WHERE topics.id = $newId");
    $topic_data = mysqli_fetch_assoc($query);

    if(!$topic_data){
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при получении созданной темы: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
    
    http_response_code(200);
    return json_encode([
        "status" => true,
        "message" => "Тема успешно создана",
        "data" => $topic_data
    ], JSON_UNESCAPED_UNICODE);
}