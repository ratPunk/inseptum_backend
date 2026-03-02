<?php

function getTestFile($connect, $test_id, $question_id = null) {
    $TEST_FOLDER = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'testsFolder' . DIRECTORY_SEPARATOR;

    $test_id = (int)$test_id;
    $query = mysqli_query($connect, "SELECT file_path FROM tests WHERE id = $test_id");

    if (!$query || mysqli_num_rows($query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Тест по id не найден"
        ]);
    }

    $row = mysqli_fetch_assoc($query);
    $file_path = $row['file_path'];

    $json_string = file_get_contents($TEST_FOLDER . $file_path . '.json');
    $test_object = json_decode($json_string);

    if ($test_object === null) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Файл теста не найден"
        ]);
    }

    // Удаляем correctAnswer из всех вопросов (теперь $testObject - это массив)
    if (is_array($test_object)) {
        foreach ($test_object as $question) {
            unset($question->correctAnswer);
        }
    }

    // ФИЛЬТРАЦИЯ ПО ID ВОПРОСА
if ($question_id !== null && is_array($test_object)) {
    $question_id = (int)$question_id;
    $found_question = null;
    
    foreach ($test_object as $question) {
        if ($question->id === $question_id) {
            $found_question = $question;
            break;
        }
    }
    
    // Преобразуем в объект (или оставляем null если не найден)
    $testObject = $found_question;
}

http_response_code(200);
return json_encode([
    "status" => true,
    "message" => "Тест найден",
    "data" => $testObject  // теперь может быть объектом или null
], JSON_UNESCAPED_UNICODE);
}

function getCorrectAnswers($connect, $test_id, $user_answers){
    $TEST_FOLDER = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'testsFolder' . DIRECTORY_SEPARATOR;

    $test_id = (int)$test_id;

    $query = mysqli_query($connect, "SELECT file_path FROM tests WHERE id = $test_id");

    if (!$query || mysqli_num_rows($query) === 0) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Тест по id не найден"
        ]);
    }

    $row = mysqli_fetch_assoc($query);
    $file_path = $row['file_path'];

    $json_string = file_get_contents($TEST_FOLDER . $file_path . '.json');
    $test_object = json_decode($json_string);

    if ($test_object === null) {
        http_response_code(404);
        return json_encode([
            "status" => false,
            "message" => "Файл теста не найден"
        ]);
    }

    if (is_array($test_object)) {
        foreach ($test_object as $question) {
            unset($question->answers);
            unset($question->question);
        }
    }

    $correct_answers_count = 0;

    // if(is_array($test_object) && is_array(value: $user_answers)){
        
    // }

    $user_array = array_column($user_answers, "answer", "questionId");
    $correct_answers_array = array_column($test_object, "correctAnswer", "id");

    if(is_array($test_object) && is_array(value: $user_answers)){
        foreach($correct_answers_array as $id => $correctAnswer){
            if($user_array[$id] == $correctAnswer){
                $correct_answers_count++;
            }
        }
    }


    http_response_code(200);
    return json_encode([
    "status" => true,
    "message" => "Результаты получены",
    "data" => $correct_answers_count 
    ], JSON_UNESCAPED_UNICODE);


    // if (is_array($testObject)) {
    //     foreach ($testObject as $question) {
    //         unset($question->correctAnswer);
    //     }
    // }

    // [
    //     {
    //         questionId: 1,
    //         answer: 'A'
    //     },
    //     {
    //         questionId: 2,
    //         answer: 'B'
    //     }
    // ]

    // [
    // {
    //     "id": 1,
    //     "question": "Как можно подключить Bootstrap в проекте?",
    //     "answers": [
    //         "npm install bootstrap",
    //         "yarn add bootstrap",
    //         "composer require twbs/bootstrap",
    //         "bower install bootstrap",
    //         "npm install --save bootstrap",
    //         "Внутривенно"
    //     ],
    //     "correctAnswer": "npm install bootstrap"
    // },
    // {
    //     "id": 2,
    //     "question": "Какой CDN ссылку нужно добавить в `<head>` для подключения CSS Bootstrap?",
    //     "answers": [
    //         "https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css",
    //         "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css",
    //         "https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css",
    //         "https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
    //     ],
    //     "correctAnswer": "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    // }
    // ]


}