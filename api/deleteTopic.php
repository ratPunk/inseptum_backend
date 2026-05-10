<?php
function deleteTopic($connect, $topic_id) {
    // Проверяем, передан ли ID
    if (!$topic_id) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "Не указан ID темы"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Проверяем, существует ли тема с таким ID
    $check_sql = "SELECT * FROM `topics` WHERE id = $topic_id";
    $check_query = mysqli_query($connect, $check_sql);
    
    if (!$check_query || mysqli_num_rows($check_query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Тема с ID $topic_id не найден"
        ], JSON_UNESCAPED_UNICODE);
    }

    // Получаем данные темы перед удалением (для информации)
    $topic_data = mysqli_fetch_assoc($check_query);
    
    // Выполняем удаление
    $delete_sql = "DELETE FROM `topics` WHERE id = $topic_id";
    $delete_query = mysqli_query($connect, $delete_sql);
    
    if ($delete_query) {
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Темы успешно удален",
            "data" => [
                "id" => $topic_data['id'],
                "title" => $topic_data['title']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при удалении темы: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
}