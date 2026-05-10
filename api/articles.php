<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getArticles($connect, $topic_id = null) {
    if ($topic_id) {
        $topic_id = (int)$topic_id;
        $query = mysqli_query($connect, "SELECT 
            articles.id,
            articles.title,
            articles.description,
            modules.title AS module_title,
            articles.topic_id,
            topics.title AS topic_title,
            articles.test_id,
            tests.title AS test_title,
            articles.task_id,
            tasks.title AS task_title,
            articles.file_path,
            articles.created_at
        FROM articles 
        LEFT JOIN topics ON articles.topic_id = topics.id 
        LEFT JOIN modules ON topics.module_id = modules.id 
        LEFT JOIN tests ON articles.test_id = tests.id
        LEFT JOIN tasks ON articles.task_id = tasks.id
        WHERE topic_id = $topic_id 
        ORDER BY modules.id, topics.id, articles.id");
        
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
            "data" => $articles,
            "count" => count($articles)
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        $query = mysqli_query($connect, "SELECT 
            articles.id,
            articles.title,
            articles.description,
            modules.title AS module_title,
            articles.topic_id,
            topics.title AS topic_title,
            articles.test_id,
            tests.title AS test_title,
            articles.task_id,
            tasks.title AS task_title,
            articles.file_path,
            articles.created_at
        FROM articles 
        LEFT JOIN topics ON articles.topic_id = topics.id 
        LEFT JOIN modules ON topics.module_id = modules.id 
        LEFT JOIN tests ON articles.test_id = tests.id
        LEFT JOIN tasks ON articles.task_id = tasks.id
        ORDER BY modules.id, topics.id, articles.id");

        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Статьи не найдены"
            ]);
        }

        $articles = [];
        while ($row = mysqli_fetch_assoc($query)) {
            if (isset($row['created_at'])) {
                $row['created_at'] = explode(' ', $row['created_at'])[0]; 
            }
            $articles[] = $row; 
        }

        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Статьи найдены",
            "data" => $articles,
            "count" => count($articles)
        ], JSON_UNESCAPED_UNICODE);
    }
}

function getArticle($connect, $article_id = null) {
    if (!$article_id) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Статья не найдена"
        ]);
    }

    $article_id = (int)$article_id;
    $query = mysqli_query($connect, "SELECT 
        articles.id,
        articles.title,
        articles.description,
        modules.title AS module_title,
        articles.topic_id,
        topics.title AS topic_title,
        articles.test_id,
        tests.title AS test_title,
        articles.task_id,
        tasks.title AS task_title,
        articles.file_path,
        articles.created_at
    FROM articles 
    LEFT JOIN topics ON articles.topic_id = topics.id 
    LEFT JOIN modules ON topics.module_id = modules.id 
    LEFT JOIN tests ON articles.test_id = tests.id
    LEFT JOIN tasks ON articles.task_id = tasks.id
    WHERE articles.id = $article_id");
        
    if (!$query || mysqli_num_rows($query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Статья не найдена"
        ]);
    }
        
    $article = mysqli_fetch_assoc($query);
        
    http_response_code(200);
    return json_encode([
        "status" => true,
        "message" => "Статья найдена",
        "data" => $article
    ], JSON_UNESCAPED_UNICODE);
}
?>