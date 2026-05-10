<?php
function deleteTest($connect, $id) {
    $id = (int)$id;

    // 1. Убираем привязку в статьях (ставим NULL)
    mysqli_query($connect, "UPDATE `articles` SET `test_id` = NULL WHERE `test_id` = $id");

    // 2. Получаем путь к файлу, чтобы удалить его с диска
    $res = mysqli_query($connect, "SELECT `file_path` FROM `tests` WHERE `id` = $id");
    $test = mysqli_fetch_assoc($res);
    
    if ($test && file_exists($test['file_path'])) {
        unlink($test['file_path']);
    }

    // 3. Удаляем сам тест
    $query = "DELETE FROM `tests` WHERE `id` = $id";
    
    if (mysqli_query($connect, $query)) {
        return json_encode(["status" => true, "message" => "Тест и связи удалены"]);
    }
    
    return json_encode(["status" => false, "message" => "Ошибка удаления"]);
}