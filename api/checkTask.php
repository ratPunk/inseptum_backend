<?php
// api/checkTask.php

function checkTask($connect, $data) {
    $task_id = $data['taskId'] ?? null;
    $user_code = $data['code'] ?? '';
    $user_id = $data['user_id'] ?? null;
    $max_chars = 5000;

    // 1. Первичная проверка данных
    if (!$task_id || empty(trim($user_code))) {
        return json_encode(["success" => false, "message" => "Код не может быть пустым."]);
    }

    // 2. Валидация длины (защита базы и токенов)
    if (mb_strlen($user_code) > $max_chars) {
        return json_encode(["success" => false, "message" => "Код слишком длинный (макс. $max_chars симв.)"]);
    }

    // 3. Быстрая проверка синтаксиса (скобки)
    $open_braces = substr_count($user_code, '{');
    $close_braces = substr_count($user_code, '}');
    if ($open_braces !== $close_braces) {
        return json_encode([
            "success" => false, 
            "message" => "Ошибка синтаксиса: не совпадает количество фигурных скобок { }."
        ]);
    }

    // 4. Получаем данные задачи из БД для промпта ИИ
    $query = "SELECT title, description FROM tasks WHERE id = ?";
    $stmt = mysqli_prepare($connect, $query);
    mysqli_stmt_bind_param($stmt, "i", $task_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $task = mysqli_fetch_assoc($result);

    if (!$task) {
        return json_encode(["success" => false, "message" => "Задача не найдена в базе данных."]);
    }

    // --- ЭТАП 5: ИНТЕГРАЦИЯ С ИИ ---
    // Здесь мы позже добавим реальный запрос к API (OpenAI/Anthropic/Proxy)
    
    // Имитация задержки и ответа (для теста фронтенда)
    $mock_ai_success = true; 
    $mock_comment = "ИИ проверил задачу '" . $task['title'] . "': Решение корректно, логика соблюдена.";

    return json_encode([
        "success" => $mock_ai_success,
        "message" => $mock_comment
    ]);
}