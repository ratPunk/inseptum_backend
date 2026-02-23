<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/../convert.php';

function getArticleFile($connect, $article_id = null) {
    if ($article_id) {
        $sql = "SELECT file_path FROM `articles` WHERE id = $article_id";
        $query = mysqli_query($connect, $sql);

        if (!$query || mysqli_num_rows($query) === 0) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Статья не найдена"
            ]);
        }

        $row = mysqli_fetch_assoc($query);
        $html = getArticleFromFile($row['file_path']);

        if (!$html) {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Статья не найдена",
                "data" => $html
            ]);
        }
        
        http_response_code(200);
        return json_encode([
            "status" => true,
            "message" => "Статья найдена",
            "data" => $html
        ], JSON_UNESCAPED_UNICODE);
    }else{
       
    }
}