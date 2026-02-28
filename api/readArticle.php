<?php
function getReadArticle($connect, $article_id = null, $user_id = null) {
    if (!$article_id || !$user_id) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "ID статьи и пользователя обязательны"
        ]);
    }

    $article_id = (int)$article_id;
    $user_id = (int)$user_id;
    
    // Пытаемся найти существующую запись
    $query = mysqli_query($connect, "SELECT * FROM user_article_read WHERE article_id = $article_id AND user_id = $user_id");
    
    // Если записи нет - создаём
    if (!$query || mysqli_num_rows($query) === 0) {
        $insert = mysqli_query($connect, "
            INSERT INTO user_article_read 
            (user_id, article_id, is_read, read_at, progress_percent, created_at) 
            VALUES 
            ($user_id, $article_id, FALSE, NOW(), 0, NOW())
        ");
        
        if (!$insert) {
            http_response_code(500);
            return json_encode([
                "status" => false,
                "message" => "Ошибка при создании записи"
            ]);
        }
        
        // Получаем созданную запись
        $newQuery = mysqli_query($connect, "SELECT * FROM user_article_read WHERE article_id = $article_id AND user_id = $user_id");
        $articlesArray = mysqli_fetch_assoc($newQuery);
        $bool_is_read = $articlesArray['is_read'] === '1' ? true : false;

        $articles = [
            "id" => $articlesArray['id'],
            "user_id" => $articlesArray['user_id'],
            "article_id" => $articlesArray['article_id'],
            "is_read" => $bool_is_read,
            "read_at" => $articlesArray['read_at'],
            "progress_percent" => $articlesArray['progress_percent'],
            "created_at" => $articlesArray['created_at']
        ];

        http_response_code(201);
        return json_encode([
            "status" => true,
            "message" => "Новая запись создана",
            "data" => $articles
        ], JSON_UNESCAPED_UNICODE);
    }
    
    // Если запись существует - возвращаем её
    $articlesArray = mysqli_fetch_assoc($query);
    $bool_is_read = $articlesArray['is_read'] === '1' ? true : false;

    $articles = [
        "id" => $articlesArray['id'],
        "user_id" => $articlesArray['user_id'],
        "article_id" => $articlesArray['article_id'],
        "is_read" => $bool_is_read,
        "read_at" => $articlesArray['read_at'],
        "progress_percent" => $articlesArray['progress_percent'],
        "created_at" => $articlesArray['created_at']
    ];
    
    http_response_code(200);
    return json_encode([
        "status" => true,
        "message" => "Запись найдена",
        "data" => $articles
    ], JSON_UNESCAPED_UNICODE);
}

function setReadArticle($connect, $article_id = null, $user_id = null){
    if (!$article_id || !$user_id) {
        http_response_code(400);
        return json_encode([
            "status" => false,
            "message" => "ID статьи и пользователя обязательны"
        ]);
    }

    $article_id = (int)$article_id;
    $user_id = (int)$user_id;
    
    $query = mysqli_query($connect, "UPDATE user_article_read SET is_read = TRUE, read_at = NOW() WHERE article_id = $article_id AND user_id = $user_id");
    
    if ($query) {
        $newQuery = mysqli_query($connect, "SELECT * FROM user_article_read WHERE article_id = $article_id AND user_id = $user_id");
        $articlesArray = mysqli_fetch_assoc($newQuery);
        $bool_is_read = $articlesArray['is_read'] === '1' ? true : false;

        $articles = [
            "id" => $articlesArray['id'],
            "user_id" => $articlesArray['user_id'],
            "article_id" => $articlesArray['article_id'],
            "is_read" => $bool_is_read,
            "read_at" => $articlesArray['read_at'],
            "progress_percent" => $articlesArray['progress_percent'],
            "created_at" => $articlesArray['created_at']
        ];

        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Запись обновлена",
            "data" => $articles
        ], JSON_UNESCAPED_UNICODE);
    }

    http_response_code(500);
    return json_encode([
        "status" => false,
        "message" => "Ошибка при обновлении записи"
    ]);
}
?>