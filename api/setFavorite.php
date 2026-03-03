<?php
function setFavorite($connect, $user_id, $favorite_id, $favorite_type){

    $favorite_id = (int)$favorite_id;
    $user_id = (int)$user_id;

    if($favorite_type === 'article'){
        $result = mysqli_query($connect, "SELECT `user_id`, `article_id` FROM `user_article_favorite` WHERE `user_id` = $user_id AND `article_id` = $favorite_id");

        if(mysqli_num_rows($result) > 0){
            $result = mysqli_query($connect, "DELETE FROM `user_article_favorite` WHERE `user_id` = $user_id AND `article_id` = $favorite_id");

            http_response_code(200);
            $res = [
                "status" => true,
                "message" => "Статья удалена из избранного"
            ];
            return json_encode($res);
        }

        $result = mysqli_query($connect, "INSERT INTO `user_article_favorite`(`user_id`, `article_id`) VALUES ($user_id, $favorite_id)");   
        
        if($result){
            http_response_code(200);
            $res = [
                "status" => true,
                "message" => "Статья добавлена в избранное"
            ];
            return json_encode($res);
        } else {
            http_response_code(500);
            $res = [
                "status" => false,
                "message" => "Ошибка при добавлении: " . mysqli_error($connect)
            ];
            return json_encode($res);
        }
        
    } elseif($favorite_type === 'test'){
        $result = mysqli_query($connect, "SELECT `user_id`, `test_id` FROM `user_test_favorite` WHERE `user_id` = $user_id AND `test_id` = $favorite_id");

        if(mysqli_num_rows($result) > 0){
            $result = mysqli_query($connect, "DELETE FROM `user_test_favorite` WHERE `user_id` = $user_id AND `test_id` = $favorite_id");

            http_response_code(200);
            $res = [
                "status" => true,
                "message" => "Тест удален из избранного"
            ];
            return json_encode($res);
        }

        $result = mysqli_query($connect, "INSERT INTO `user_test_favorite`(`user_id`, `test_id`) VALUES ($user_id, $favorite_id)");   
        
        if($result){
            http_response_code(200);
            $res = [
                "status" => true,
                "message" => "Тест добавлен в избранное"
            ];
            return json_encode($res);
        } else {
            http_response_code(500);
            $res = [
                "status" => false,
                "message" => "Ошибка при добавлении: " . mysqli_error($connect)
            ];
            return json_encode($res);
        }
        
    } else {
        http_response_code(400);
        $res = [
            "status" => false,
            "message" => "Неверное значение favorite_type"
        ];
        return json_encode($res);
    }
}
?>