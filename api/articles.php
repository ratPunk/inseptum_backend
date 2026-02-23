<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getArticles($connect, $topic_id = null) {
    if ($topic_id) {
        $topic_id = (int)$topic_id;
        $query = mysqli_query($connect, "SELECT articles.*, modules.title AS module_title FROM articles LEFT JOIN topics ON articles.topic_id = topics.id LEFT JOIN modules ON topics.module_id = modules.id WHERE topic_id = $topic_id ORDER BY id");
        
        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Статьи не найдены"
            ]);
        }
        
        $articles = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $articles[] = $row; 
        }
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Статьи найдены",
            "data" => $articles, // Массив всех записей
            "count" => count($articles)
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        $query = mysqli_query($connect, "SELECT articles.*, modules.title AS module_title FROM articles LEFT JOIN topics ON articles.topic_id = topics.id LEFT JOIN modules ON topics.module_id = modules.id ORDER BY module_id");

        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Темы не найдены"
            ]);
        }

        $articles = [];
        while ($row = mysqli_fetch_assoc($query)) {
            $row['created_at'] = explode(' ', $row['created_at'])[0]; 
            $articles[] = $row; 
        }

        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Статьи найдены",
            "data" => $articles, // Массив всех записей
            "count" => count($articles)
        ], JSON_UNESCAPED_UNICODE);


    }
}

function getArticle($connect, $artcle_id = null) {
    if (!$artcle_id) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "статья не найдена"
        ]);
    }

    $artcle_id = (int)$artcle_id;
    $query = mysqli_query($connect, "SELECT articles.*, modules.title AS module_title FROM articles LEFT JOIN topics ON articles.topic_id = topics.id LEFT JOIN modules ON topics.module_id = modules.id WHERE articles.id = $artcle_id");
        
    if (!$query || mysqli_num_rows($query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Статья не найдена"
            ]);
    }
        
    $articles = mysqli_fetch_assoc($query);
        
    http_response_code(200);
    return json_encode([
        "status" => true,
        "message" => "Статьи найдены",
        "data" => $articles
    ], JSON_UNESCAPED_UNICODE);
    
}