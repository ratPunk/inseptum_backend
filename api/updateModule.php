<?php
function updateModule($connect, $module_id, $form_data) {
    // Проверяем, передан ли ID
    if (!$module_id) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Не указан ID модуля"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Экранируем ID
    $module_id = (int)$module_id;

    // Проверяем, существует ли модуль
    $check_sql = "SELECT * FROM `modules` WHERE id = $module_id";
    $check_query = mysqli_query($connect, $check_sql);
    
    if (!$check_query || mysqli_num_rows($check_query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Модуль с ID $module_id не найден"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Извлекаем данные из form_data
    $title = isset($form_data['title']) ? mysqli_real_escape_string($connect, $form_data['title']) : '';
    $description = isset($form_data['description']) ? mysqli_real_escape_string($connect, $form_data['description']) : '';
    $status = isset($form_data['status']) ? mysqli_real_escape_string($connect, $form_data['status']) : '';

    // Проверяем обязательные поля
    if (empty($title)) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Название модуля обязательно"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Формируем запрос на обновление
    $update_sql = "UPDATE `modules` SET 
                   title = '$title', 
                   description = '$description', 
                   status = '$status' 
                   WHERE id = $module_id";
    
    $update_query = mysqli_query($connect, $update_sql);
    
    if ($update_query) {
        // Получаем обновленные данные
        $result_query = mysqli_query($connect, "SELECT * FROM `modules` WHERE id = $module_id");
        $updated_data = mysqli_fetch_assoc($result_query);
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Модуль успешно обновлен",
            "data" => [
                "id" => (int)$updated_data['id'],
                "title" => $updated_data['title'],
                "description" => $updated_data['description'],
                "status" => $updated_data['status']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при обновлении модуля: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
}