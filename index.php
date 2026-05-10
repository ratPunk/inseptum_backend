<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // В продакшене заменить на конкретный домен
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database.php';
require __DIR__ . '/controller.php';
require __DIR__ . '/api/register.php';
require __DIR__ . '/api/login.php';
require __DIR__ . '/api/modules.php';
require __DIR__ . '/api/topics.php';
require __DIR__ . '/api/articles.php';
require __DIR__ . '/api/getArticleFile.php';
require __DIR__ . '/api/readArticle.php';
require __DIR__ . '/api/tests.php';
require __DIR__ . '/api/checkTask.php';
require __DIR__ . '/api/getTestFile.php';
require __DIR__ . '/api/getFavorite.php';
require __DIR__ . '/api/setFavorite.php';
require __DIR__ . '/api/passedTEst.php';
require __DIR__ . '/api/tasks.php';
require __DIR__ . '/api/adminLogin.php';

require __DIR__ . '/api/createmodule.php';
require __DIR__ . '/api/deleteModule.php';
require __DIR__ . '/api/updateModule.php';
require __DIR__ . '/api/createTopic.php';
require __DIR__ . '/api/deleteTopic.php';
require __DIR__ . '/api/updateTopic.php';
require __DIR__ . '/api/deleteArticle.php';
require __DIR__ . '/api/createArticle.php';
require __DIR__ . '/api/updateArticle.php';

require __DIR__ . '/api/updateTest.php';
require __DIR__ . '/api/createTest.php';

$method = $_SERVER['REQUEST_METHOD'];
$url = $_GET['url'] ?? '';
$params = $url ? explode('/', trim($url, '/')) : [];

$type = $params[0] ?? '';
$id = $params[1] ?? '';
$user_id = $params[2] ?? '';

if($method == 'GET') {
    if($type == 'users'){
        $response = getUsers($connect);
        echo $response;
    }elseif($type == 'modules'){
        $response = getModules($connect, $id);
        echo $response;
    }elseif($type == 'topics'){
        $response = getTopics($connect, $id);
        echo $response;
    }elseif($type == 'articles'){
        $response = getArticles($connect, $id);
        echo $response;
    }elseif($type == 'article'){
        $response = getArticle($connect, $id);
        echo $response;
    }
    elseif($type == 'articlefile'){
        $response = getArticleFile($connect, $id);
        echo $response;
    }elseif($type == 'readarticle'){
        $response = getReadArticle($connect,  $id, $user_id);
        echo $response;
    }elseif($type == 'tests'){
        $response = getTests($connect, $id);
        echo $response;
    }elseif($type == 'tasks'){
        $response = getTasks($connect, $id);
        echo $response;
    }

}elseif($method == 'POST') {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    $article_id = $data['article_id'] ?? '';
    $user_id = $data['user_id'] ?? '';

    $test_id = $data['test_id'] ?? '';
    $question_id = $data['question_id'] ?? '';
    $user_answers = $data['user_answers'] ?? '';

    $favorite_id = $data['favorite_id'] ?? '';
    $favorite_type = $data['favorite_type'] ?? '';

    $module_id = $data['module_id'] ?? '';
    $topic_id = $data['topic_id'] ?? '';

    $form_data = $data['form_data'] ?? '';

    $post_form_data = $_POST;
    $file = $_FILES['file'] ?? null;

    $article_id = $data['article_id'] ?? $post_form_data['article_id'] ?? '';

    if($type == 'users'){
        $response  = createUser($connect, $username, $password);
        echo $response;
    }elseif($type == 'register'){
        $response = registerUser($connect, $username, $password, $confirmPassword);
        echo $response;
    }elseif($type == 'login'){
        $response = loginUser($connect, $username, $password);
        echo $response;
    }elseif($type == 'readarticle'){
        $response = setReadArticle($connect,  $article_id, $user_id);
        echo $response;
    }elseif($type == 'gettestfile'){
        $response = getTestFile($connect, $test_id, $question_id);
        echo $response;
    }elseif($type === 'gettestresults'){
        $response = getCorrectAnswers($connect, $test_id, $user_answers);
        echo $response;
    }elseif($type == 'getfavorite'){
        $response = getFavorite($connect, $user_id, $favorite_type);
        echo $response;
    }elseif($type == 'setfavorite'){
        $response = setFavorite($connect, $user_id, $favorite_id, $favorite_type);
        echo $response;
    }elseif($type == 'setpassedtest'){
        $response = setPassedTest($connect, $user_id, $test_id);
        echo $response;
    }elseif($type == 'getpassedtest'){
        $response = getPassedTest($connect, $user_id, $test_id);
        echo $response;
    }elseif($type == 'adminlogin'){
        $response = adminLogin($connect, $username, $password);
        echo $response;
    }elseif($type == 'createmodule'){
        $response = createmodule($connect, $form_data);
        echo $response;
    }elseif($type == 'deletemodule'){
        $response = deleteModule($connect, $module_id);
        echo $response;
    }elseif($type == 'updatemodule'){
        $response = updateModule($connect, $module_id, $form_data);
        echo $response;
    }elseif($type == 'createtopic'){
        $response = createTopic($connect, $form_data);
        echo $response;
    }elseif($type == 'deletetopic'){
        $response = deleteTopic($connect, $topic_id);
        echo $response;
    }elseif($type == 'updatetopic'){
        $response = updateTopic($connect, $topic_id, $form_data);
        echo $response;
    }elseif($type == 'deletearticle'){
        $response = deleteArticle($connect, $article_id);
        echo $response;
    }elseif($type == 'createarticle'){
        $response = createArticle($connect, $post_form_data, $file);
        echo $response;
    }elseif($type == 'updatearticle'){
        $response = updateArticle($connect, $article_id, $post_form_data, $file);
        echo $response;
    }elseif($type == 'checktask'){
        $response = checkTask($connect, $data);
        echo $response;
    }elseif ($type == 'createtest') {
        echo createTest($connect, $_POST, $_FILES['file'] ?? null);
    } elseif ($type == 'updatetest') {
        // Если ты передаешь ID в URL (например /updatetest/15)
        echo updateTest($connect, $id, $_POST, $_FILES['file'] ?? null);
    } elseif ($type == 'tests') {
        if ($id) {
            // Запрос на /tests/15 -> Обновление
            echo updateTest($connect, $id, $_POST, $_FILES['file'] ?? null);
        } else {
            // Запрос на /tests -> Создание
            echo createTest($connect, $_POST, $_FILES['file'] ?? null);
        } // Закрываем else
    } // Закрываем elseif ($type == 'tests')

} elseif ($method == 'DELETE') { // Теперь этот блок стоит на своем месте
    if ($type == 'tests' && $id) {
        require_once __DIR__ . '/api/deleteTest.php';
        echo deleteTest($connect, $id);
    } else {
        http_response_code(405);
        echo json_encode(["status" => false, "message" => "Method not allowed or ID missing"]);
    }
}