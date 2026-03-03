<?php
function setPassedTest($connect, $user_id, $test_id) {
    
    $user_id = (int)$user_id;
    $test_id = (int)$test_id;

    // Сначала проверяем, есть ли вообще запись о прохождении
    $check_query = "SELECT * FROM `user_test_passed` WHERE `user_id` = $user_id AND `test_id` = $test_id";
    $result = mysqli_query($connect, $check_query);
    
    if(mysqli_num_rows($result) > 0){
        // Запись есть - обновляем статус на пройден
        $row = mysqli_fetch_assoc($result);
        $passed_id = $row['id'];
        
        $update_query = "UPDATE `user_test_passed` 
                        SET `is_passed` = 1 
                        WHERE `id` = $passed_id";
        $update_result = mysqli_query($connect, $update_query);
        
        if($update_result){
            // После UPDATE делаем SELECT, чтобы получить обновленные данные
            $select_query = "SELECT * FROM `user_test_passed` WHERE `id` = $passed_id";
            $select_result = mysqli_query($connect, $select_query);
            $updated_row = mysqli_fetch_assoc($select_result);
            $bool_passed = $updated_row['is_passed'] == 1 ? true : false;
            
            http_response_code(200);
            $res = [
                "status" => true,
                "message" => "Статус теста обновлен на пройденный",
                "data" => $bool_passed
            ];
            return json_encode($res);
        } else {
            http_response_code(500);
            $res = [
                "status" => false,
                "message" => "Ошибка при обновлении статуса"
            ];
            return json_encode($res);
        }
    }

    // Записи нет - создаем новую
    $insert_query = "INSERT INTO `user_test_passed`(`user_id`, `test_id`, `is_passed`) 
                    VALUES ($user_id, $test_id, 1)";   
    $insert_result = mysqli_query($connect, $insert_query);
        
    if($insert_result){
        // Получаем ID новой записи
        $new_id = mysqli_insert_id($connect);
        
        // Получаем созданную запись
        $select_query = "SELECT * FROM `user_test_passed` WHERE `id` = $new_id";
        $select_result = mysqli_query($connect, $select_query);
        $new_row = mysqli_fetch_assoc($select_result);
        $bool_passed = $new_row['is_passed'] == 1 ? true : false;
        
        http_response_code(200);
        $res = [
            "status" => true,
            "message" => "Тест пройден",
            "data" => $bool_passed
        ];
        return json_encode($res);
    } else {
        http_response_code(500);
        $res = [
            "status" => false,
            "message" => "Ошибка при сохранении результата"
        ];
        return json_encode($res);
    }
}


function getPassedTest($connect, $user_id, $test_id) {
    
    $user_id = (int)$user_id;
    $test_id = (int)$test_id;

    $result = mysqli_query($connect, "SELECT * FROM `user_test_passed` 
                                     WHERE `user_id` = $user_id AND `test_id` = $test_id");
    
    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        $bool_passed = $row['is_passed'] == 1 ? true : false;

        http_response_code(200);
        $res = [
            "status" => true,
            "message" => $bool_passed ? "Тест пройден" : "Тест не пройден",
            "data" => $bool_passed
        ];
        return json_encode($res);
    } else {
        // Записи нет - значит точно не пройден
        http_response_code(200);
        $res = [
            "status" => true,
            "message" => "Тест не пройден (нет записи)",
            "data" => false
        ];
        return json_encode($res);
    }
}
?>