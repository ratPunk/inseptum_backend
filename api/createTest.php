<?php
function createTest($connect, $data, $file) {
    // Обязательные поля
    $title = mysqli_real_escape_string($connect, $data['title']);
    $description = mysqli_real_escape_string($connect, $data['description'] ?? '');
    $time_limit = (int)($data['time_limit'] ?? 20);
    $topic_id = (int)($data['topic_id'] ?? 0); // Получаем ID темы из формы

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return json_encode(["status" => false, "message" => "Файл не загружен или поврежден"]);
    }

    // 1. Читаем JSON и считаем вопросы
    $fileContent = file_get_contents($file['tmp_name']);
    $questions = json_decode($fileContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(["status" => false, "message" => "Ошибка валидации JSON: " . json_last_error_msg()]);
    }

    $question_count = is_array($questions) ? count($questions) : 0;

    // 2. Генерируем имя и путь (сохраняем в testsFolder)
    $fileNameWithExt = time() . '_' . basename($file['name']); // Например: 1775150629_test.json
    $targetPath = 'testsFolder/' . $fileNameWithExt;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $fileNameOnly = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

        $query = "INSERT INTO `tests` (`title`, `description`, `time_limit`, `question_count`, `file_path`) 
              VALUES ('$title', '$description', $time_limit, $question_count, '$fileNameOnly')";
    
        if (mysqli_query($connect, $query)) {
            return json_encode(["status" => false, "message" => "Не удалось сохранить файл в testsFolder"]);
        }
    }

    // 3. Запись в базу
    $query = "INSERT INTO `tests` (`title`, `description`, `time_limit`, `question_count`, `file_path`) 
              VALUES ('$title', '$description', $time_limit, $question_count, '$targetPath')";

    if (mysqli_query($connect, $query)) {
        $test_id = mysqli_insert_id($connect);

        // Привязываем тест к теме в таблице articles
        if ($topic_id > 0) {
            mysqli_query($connect, "UPDATE `articles` SET `test_id` = $test_id WHERE `topic_id` = $topic_id LIMIT 1");
        }

        http_response_code(201);
        return json_encode([
            "status" => true,
            "message" => "Тест успешно создан",
            "data" => [
                "id" => $test_id,
                "title" => $title,
                "question_count" => $question_count,
                "file_path" => $targetPath
            ]
        ], JSON_UNESCAPED_UNICODE);
    }

    return json_encode(["status" => false, "message" => "Ошибка базы данных: " . mysqli_error($connect)]);
}