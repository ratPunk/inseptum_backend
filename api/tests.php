<?php
function getTests($connect, $test_id = null) {
    if (!$test_id) {

        $query = mysqli_query($connect, "SELECT tests.*, modules.title AS module_title FROM tests LEFT JOIN articles ON tests.id = articles.test_id LEFT JOIN topics ON articles.topic_id = topics.id LEFT JOIN modules ON topics.module_id = modules.id");

        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Тесты не найдены"
            ]);
        }
        
        $tests = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $tests[] = $row; 
        }
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Тесты найдены",
            "data" => $tests,
            "count" => count($tests)
        ], JSON_UNESCAPED_UNICODE);

    }else{
        $test_id = (int)$test_id;
        $query = mysqli_query($connect, "SELECT tests.*, modules.title AS module_title FROM tests LEFT JOIN articles ON tests.id = articles.test_id LEFT JOIN topics ON articles.topic_id = topics.id LEFT JOIN modules ON topics.module_id = modules.id WHERE tests.id = $test_id");
        
        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Тест не найден"
                ]);
        }
        
        $test = mysqli_fetch_assoc($query);
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Тест найден",
            "data" => $test
        ], JSON_UNESCAPED_UNICODE);
    }

   
    
}