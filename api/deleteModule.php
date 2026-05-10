<?php
function deleteModule($connect, $module_id) {
    // Проверяем, передан ли ID
    if (!$module_id) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Не указан ID модуля"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Проверяем, существует ли модуль с таким ID
    $check_sql = "SELECT * FROM `modules` WHERE id = $module_id";
    $check_query = mysqli_query($connect, $check_sql);
    
    if (!$check_query || mysqli_num_rows($check_query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Модуль с ID $module_id не найден"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Получаем данные модуля перед удалением (для информации)
    $module_data = mysqli_fetch_assoc($check_query);
    
    // Выполняем удаление
    $delete_sql = "DELETE FROM `modules` WHERE id = $module_id";
    $delete_query = mysqli_query($connect, $delete_sql);
    
    if ($delete_query) {
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Модуль успешно удален",
            "data" => [
                "id" => $module_data['id'],
                "title" => $module_data['title']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при удалении модуля: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
}