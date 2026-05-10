<?php
function deleteArticle($connect, $article_id) {
    // 1. Проверяем, передан ли ID
    if (!$article_id) {
        http_response_code(400);
        return json_encode(["status" => false, "message" => "Не указан ID статьи"], JSON_UNESCAPED_UNICODE);
    }

    $article_id = (int)$article_id; // Защита от SQL-инъекций

    // 2. Получаем данные статьи (нам нужно поле file_path)
    $check_sql = "SELECT * FROM `articles` WHERE id = $article_id";
    $check_query = mysqli_query($connect, $check_sql);
    
    if (!$check_query || mysqli_num_rows($check_query) === 0) {
        http_response_code(404);
        return json_encode(["status" => false, "message" => "Статья не найдена"], JSON_UNESCAPED_UNICODE);
    }

    $article_data = mysqli_fetch_assoc($check_query);
    $fileName = $article_data['file_path'];

    // 3. Выполняем удаление записи из БД
    $delete_sql = "DELETE FROM `articles` WHERE id = $article_id";
    $delete_query = mysqli_query($connect, $delete_sql);
    
    if ($delete_query) {
        // 4. УДАЛЕНИЕ ФАЙЛА С ДИСКА
        // Поднимаемся из api/ в корень и заходим в articlesFolder
        $filePath = dirname(__DIR__) . '/articlesFolder/' . $fileName;

        if (!empty($fileName) && file_exists($filePath)) {
            if (!unlink($filePath)) {
                // Если файл не удалился (например, нет прав), можно либо логировать, 
                // либо вернуть успех по БД, но с предупреждением о файле
                $fileMessage = " Запись удалена, но возникла ошибка при удалении файла.";
            } else {
                $fileMessage = " Файл также удален.";
            }
        } else {
            $fileMessage = " Файл на сервере не найден.";
        }

        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Статья успешно удалена." . $fileMessage,
            "data" => [
                "id" => $article_data['id'],
                "title" => $article_data['title']
            ]
        ], JSON_UNESCAPED_UNICODE);

    } else {
        http_response_code(500);
        return json_encode([
            "status" => false,
            "message" => "Ошибка при удалении записи из БД: " . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
    }
}