<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getTopics($connect, $module_id = null) {
    if ($module_id) {
        $module_id = (int)$module_id;
        $query = mysqli_query($connect, "SELECT topics.*, modules.title AS module_title FROM topics LEFT JOIN modules ON topics.module_id = modules.id WHERE module_id = $module_id ORDER BY id");
        
        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Тема не найдена"
            ]);
        }
        
        $topics = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $topics[] = $row; 
        }
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Темы найдены",
            "data" => $topics, // Массив всех записей
            "count" => count($topics)
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        $query = mysqli_query($connect, "SELECT topics.*, modules.title AS module_title FROM topics LEFT JOIN modules ON topics.module_id = modules.id ORDER BY module_id");

        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Темы не найдены"
            ]);
        }

        $topics = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $topics[] = $row; 
        }

        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Темы найдены",
            "data" => $topics, // Массив всех записей
            "count" => count($topics)
        ], JSON_UNESCAPED_UNICODE);


    }
}