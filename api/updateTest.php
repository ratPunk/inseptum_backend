<?php
function updateTest($connect, $test_id, $data, $file) {
    $test_id = (int)$test_id;
    $title = mysqli_real_escape_string($connect, $data['title']);
    $description = mysqli_real_escape_string($connect, $data['description'] ?? '');
    $time_limit = (int)($data['time_limit'] ?? 20);

    // Базовые поля для обновления
    $updateFields = "`title` = '$title', `description` = '$description', `time_limit` = $time_limit";

    // Если прилетел новый файл
    if ($file && $file['size'] > 0) {
        $fileContent = file_get_contents($file['tmp_name']);
        $questions = json_decode($fileContent, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $question_count = count($questions);
            
            // 1. Генерируем уникальное имя для физического сохранения
            $fileNameWithExt = time() . '_' . basename($file['name']);
            $targetPath = 'testsFolder/' . $fileNameWithExt;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // 2. Извлекаем "чистое" имя без расширения для базы данных
                $fileNameOnly = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
                
                // 3. В базу пишем ТОЛЬКО имя файла
                $updateFields .= ", `file_path` = '$fileNameOnly', `question_count` = $question_count";
                
                // (Опционально) Удаление старого файла
                $oldFileRes = mysqli_query($connect, "SELECT file_path FROM tests WHERE id = $test_id");
                $oldFileData = mysqli_fetch_assoc($oldFileRes);
                if ($oldFileData && !empty($oldFileData['file_path'])) {
                    $oldPath = 'testsFolder/' . $oldFileData['file_path'] . '.json';
                    if (file_exists($oldPath)) { unlink($oldPath); }
                }
            }
        }
    }

    $query = "UPDATE `tests` SET $updateFields WHERE `id` = $test_id";

    if (mysqli_query($connect, $query)) {
        // Возвращаем актуальные данные
        $res = mysqli_query($connect, "SELECT * FROM tests WHERE id = $test_id");
        return json_encode([
            "status" => true, 
            "message" => "Данные обновлены",
            "data" => mysqli_fetch_assoc($res)
        ], JSON_UNESCAPED_UNICODE);
    }

    return json_encode(["status" => false, "message" => "Ошибка обновления"]);
}