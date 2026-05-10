<?php
function updateTopic($connect, $topic_id, $form_data) {

    if(!$topic_id){
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Неопределённая тема"
        ], JSON_UNESCAPED_UNICODE);
    }

    if (!$form_data || !$form_data['title'] || !$form_data['description'] || !$form_data['module_id']) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Не все поля заполнены"
        ], JSON_UNESCAPED_UNICODE);
    }

    $form_data['module_id'] = (int)$form_data['module_id'];

    // Проверяем, существует ли тема
    $check_sql = "SELECT id FROM `topics` WHERE id = '$topic_id'";
    $check_query = mysqli_query($connect, $check_sql);
    
    if (mysqli_num_rows($check_query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Тема не найдена"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Выполняем обновление
    $update_sql = "UPDATE `topics` SET 
                   `module_id` = '{$form_data['module_id']}', 
                   `title` = '{$form_data['title']}', 
                   `description` = '{$form_data['description']}' 
                   WHERE `id` = '$topic_id'";
    
    $update_query = mysqli_query($connect, $update_sql);

    if (!$update_query) {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при обновлении темы: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }

    // Получаем обновленные данные вместе с названием модуля
    $query = mysqli_query($connect, "SELECT topics.*, modules.title AS module_title FROM topics LEFT JOIN modules ON topics.module_id = modules.id WHERE topics.id = '$topic_id'");
    $topic_data = mysqli_fetch_assoc($query);

    if(!$topic_data){
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при получении обновленной темы: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
    
    http_response_code(200);
    return json_encode([
        "status" => true,
        "message" => "Тема успешно обновлена",
        "data" => $topic_data
    ], JSON_UNESCAPED_UNICODE);
}