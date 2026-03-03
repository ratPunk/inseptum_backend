<?php
function getFavorite($connect, $user_id, $favorite_type ) {
    $user_id = (int)$user_id;

    if($favorite_type === 'article'){
        $result = mysqli_query($connect, "SELECT * FROM `user_article_favorite` WHERE `user_id` = $user_id");
        $favorite = [];
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
            $favorite[] = $row;
            }

            http_response_code(200);
            return json_encode([
                "status" => true,
                "message" => "Статьи найдены",
                "data" => $favorite,
                "count" => count($favorite)
            ], JSON_UNESCAPED_UNICODE);
        } 
        else {
            http_response_code(404);
            return json_encode([
                "status" => false,
                "message" => "Статьи не найдены"
            ]);
    }
    }elseif($favorite_type === 'test'){
        
    }else{
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Тип избранного не найден"
        ]);
    }
}