<?php
function getTasks($connect, $task_id = null) {
    // Выбираем задачи и присоединяем название темы и модуля
    $sql = "SELECT 
                tasks.*, 
                topics.title AS topic_title,
                modules.title AS module_title 
            FROM tasks 
            LEFT JOIN topics ON tasks.topic_id = topics.id 
            LEFT JOIN modules ON topics.module_id = modules.id";

    if (!$task_id) {
        $query = mysqli_query($connect, $sql);

        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Задачи не найдены"
            ], JSON_UNESCAPED_UNICODE);
        }
        
        $tasks = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $tasks[] = $row; 
        }
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Задачи найдены",
            "data" => $tasks,
            "count" => count($tasks)
        ], JSON_UNESCAPED_UNICODE);

    } else {
        $task_id = (int)$task_id;
        $query = mysqli_query($connect, $sql . " WHERE tasks.id = $task_id");
        
        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Задача не найдена"
            ], JSON_UNESCAPED_UNICODE);
        }
        
        $task = mysqli_fetch_assoc($query);
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Задача найдена",
            "data" => $task
        ], JSON_UNESCAPED_UNICODE);
    }
}