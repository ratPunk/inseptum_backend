<?php
function createArticle($connect, $form_data, $file) {
    // 1. Проверка полей
    if (empty($form_data['title']) || empty($form_data['description']) || empty($form_data['topic']) || !$file) {
        http_response_code(422);
        return json_encode(["status" => false, "message" => "Заполните все поля"], JSON_UNESCAPED_UNICODE);
    }

    // 2. Валидация загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return json_encode(["status" => false, "message" => "Ошибка загрузки: " . $file['error']], JSON_UNESCAPED_UNICODE);
    }

    // 3. Настройка пути (поднимаемся из api/ в корень и заходим в articlesFolder)
    $uploadDir = dirname(__DIR__) . '/articlesFolder/'; 
    
    // Берем оригинальное имя файла
    $originalName = basename($file['name']);
    $uploadFile = $uploadDir . $originalName;

    // ПРОВЕРКА НА СУЩЕСТВОВАНИЕ
    if (file_exists($uploadFile)) {
        return json_encode([
            "status" => false, 
            "message" => "Файл с названием '$originalName' уже существует. Пожалуйста, переименуйте файл."
        ], JSON_UNESCAPED_UNICODE);
    }

    // 4. Проверка типа (через расширение, так как finfo не работает)
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension !== 'docx') {
        return json_encode(["status" => false, "message" => "Разрешены только файлы .docx"], JSON_UNESCAPED_UNICODE);
    }

    // 5. Сохранение файла
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (!move_uploaded_file($file['tmp_name'], $uploadFile)) {
        return json_encode(["status" => false, "message" => "Не удалось сохранить файл на сервере"], JSON_UNESCAPED_UNICODE);
    }

    // 6. Запись в БД
    $title = mysqli_real_escape_string($connect, $form_data['title']);
    $description = mysqli_real_escape_string($connect, $form_data['description']);
    $topic_id = (int)$form_data['topic'];
    $dbFilePath = $originalName; // В базу пишем просто имя файла

    $create_sql = "INSERT INTO `articles`(`topic_id`, `title`, `description`, `file_path`, `created_at`) 
                   VALUES ('$topic_id', '$title', '$description', '$dbFilePath', CURRENT_TIMESTAMP())";
    
    $create_query = mysqli_query($connect, $create_sql);

    if (!$create_query) {
        unlink($uploadFile); // Удаляем файл, если не удалось записать в БД
        http_response_code(500);
        return json_encode(["status" => false, "message" => "Ошибка БД: " . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
    }

    // 7. Получение данных для фронтенда
    $newId = mysqli_insert_id($connect);
    $query = mysqli_query($connect, "SELECT 
            articles.id, articles.title, articles.description, 
            modules.title AS module_title, topics.title AS topic_title,
            articles.test_id, tests.title AS test_title,
            articles.task_id, tasks.title AS task_title,
            articles.file_path, articles.created_at
        FROM articles 
        LEFT JOIN topics ON articles.topic_id = topics.id 
        LEFT JOIN modules ON topics.module_id = modules.id 
        LEFT JOIN tests ON articles.test_id = tests.id
        LEFT JOIN tasks ON articles.task_id = tasks.id
        WHERE articles.id = $newId");

    $article_data = mysqli_fetch_assoc($query);
    
    return json_encode([
        "status" => true,
        "message" => "Статья успешно создана",
        "data" => $article_data
    ], JSON_UNESCAPED_UNICODE);
}