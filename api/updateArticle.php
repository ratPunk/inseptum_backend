<?php
function updateArticle($connect, $article_id, $form_data, $file) {
    if (!$article_id) {
        return json_encode(["status" => false, "message" => "ID статьи не указан"], JSON_UNESCAPED_UNICODE);
    }

    // 1. Получаем текущие данные статьи, чтобы знать имя старого файла
    $current_sql = mysqli_query($connect, "SELECT file_path FROM articles WHERE id = " . (int)$article_id);
    $current_article = mysqli_fetch_assoc($current_sql);
    if (!$current_article) {
        return json_encode(["status" => false, "message" => "Статья не найдена"], JSON_UNESCAPED_UNICODE);
    }

    $title = mysqli_real_escape_string($connect, $form_data['title']);
    $description = mysqli_real_escape_string($connect, $form_data['description']);
    $topic_id = (int)$form_data['topic'];
    $newFileName = $current_article['file_path']; // По умолчанию оставляем старый файл

    // 2. Если загружен новый файл
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = dirname(__DIR__) . '/articlesFolder/';
        $originalName = basename($file['name']);
        $uploadFile = $uploadDir . $originalName;

        // Проверка на существование (если имя файла другое)
        if ($originalName !== $current_article['file_path'] && file_exists($uploadFile)) {
            return json_encode(["status" => false, "message" => "Файл с таким именем уже есть"], JSON_UNESCAPED_UNICODE);
        }

        // Проверка расширения
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension !== 'docx') {
            return json_encode(["status" => false, "message" => "Только .docx"], JSON_UNESCAPED_UNICODE);
        }

        // Удаляем старый файл перед сохранением нового
        $oldFile = $uploadDir . $current_article['file_path'];
        if (file_exists($oldFile)) unlink($oldFile);

        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            $newFileName = $originalName;
        }
    }

    // 3. Обновляем БД
    $update_sql = "UPDATE `articles` SET 
                   `title` = '$title', 
                   `description` = '$description', 
                   `topic_id` = '$topic_id', 
                   `file_path` = '$newFileName' 
                   WHERE `id` = " . (int)$article_id;

    if (mysqli_query($connect, $update_sql)) {
        // Возвращаем полные данные (с джоинами), как при создании
        $query = mysqli_query($connect, "SELECT articles.*, topics.title AS topic_title, modules.title AS module_title 
            FROM articles 
            LEFT JOIN topics ON articles.topic_id = topics.id 
            LEFT JOIN modules ON topics.module_id = modules.id 
            WHERE articles.id = $article_id");
        return json_encode(["status" => true, "data" => mysqli_fetch_assoc($query)], JSON_UNESCAPED_UNICODE);
    }
}