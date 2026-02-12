<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getModules($connect, $id = null) {
    if ($id) {
        // Если передан ID - получаем один модуль
        $id = (int)$id;
        $query = mysqli_query($connect, "SELECT * FROM `modules` WHERE id = $id");
        
        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Модуль не найден"
            ]);
        }
        
        $result = mysqli_fetch_assoc($query);
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Модуль найден",
            "data" => $result // Одна запись
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Если ID не передан - получаем ВСЕ модули
        $query = mysqli_query($connect, "SELECT * FROM `modules` ORDER BY id");
        
        if (!$query) {
            http_response_code(500);
            return json_encode([
                "status" => false,
                "message" => "Ошибка запроса: " . mysqli_error($connect)
            ]);
        }
        
        if (mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Модули не найдены"
            ]);
        }
        
        $modules = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $modules[] = $row; // Добавляем каждую запись в массив
        }
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Модули найдены",
            "data" => $modules, // Массив всех записей
            "count" => count($modules)
        ], JSON_UNESCAPED_UNICODE);
    }
}