<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\TestService;

class TestController extends AbstractController
{
    private TestService $service;

    public function __construct(TestService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success($result['data'], 'Тесты найдены', 200, ['count' => $result['count']]);
    }

    public function show(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('Тест не найден', 404);
        }
        $data = $this->service->getOne($id);
        return $this->success($data, 'Тест найден', 200);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $data = $request->input();
        $file = $request->file('file');
        $payload = $this->service->create($data, $file);
        return $this->success($payload, 'Тест успешно создан', 201);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('test_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID теста', 400);
        }
        $data = $request->input();
        $file = $request->file('file');
        $payload = $this->service->update($id, $data, $file);
        return $this->success($payload, 'Данные обновлены', 200);
    }

    public function delete(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('test_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID теста', 400);
        }
        $payload = $this->service->delete($id);
        return $this->success($payload, 'Тест и связи удалены', 200);
    }

    /**
     * POST /gettestfile — вопросы теста (без правильных ответов).
     * Опционально: filter по question_id.
     */
    public function file(Request $request, array $params): JsonResponse
    {
        $testId     = (int)$request->input('test_id', 0);
        $questionId = $request->input('question_id', null);
        $questionId = $questionId === null || $questionId === '' ? null : (int)$questionId;
        if ($testId <= 0) {
            return $this->error('Не указан ID теста', 400);
        }
        $data = $this->service->getTestFile($testId, $questionId);
        return $this->success($data, 'Тест найден', 200);
    }

    /**
     * POST /gettestresults — подсчёт правильных ответов.
     */
    public function results(Request $request, array $params): JsonResponse
    {
        $testId      = (int)$request->input('test_id', 0);
        $userAnswers = $request->input('user_answers', []);
        if ($testId <= 0) {
            return $this->error('Не указан ID теста', 400);
        }
        if (!is_array($userAnswers)) {
            return $this->error('Не переданы ответы пользователя', 400);
        }
        $count = $this->service->getCorrectAnswersCount($testId, $userAnswers);
        return $this->success($count, 'Результаты получены', 200);
    }

    /**
     * POST /setpassedtest — отметить тест как пройденный.
     */
    public function setPassed(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $testId = (int)$request->input('test_id', 0);
        $passed = $this->service->markPassed($userId, $testId);
        return $this->success($passed, $passed ? 'Тест пройден' : 'Статус теста обновлен', 200);
    }

    /**
     * POST /getpassedtest — получить статус прохождения теста.
     */
    public function getPassed(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $testId = (int)$request->input('test_id', 0);
        $passed = $this->service->isPassed($userId, $testId);
        return $this->success($passed, $passed ? 'Тест пройден' : 'Тест не пройден', 200);
    }

    /**
     * POST /getpassedtests — список ID всех пройденных тестов пользователя.
     * Batch-эндпоинт, чтобы избежать N запросов с фронта.
     */
    public function getPassedList(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $result = $this->service->listPassedByUser($userId);
        return $this->success($result['data'], 'Пройденные тесты получены', 200, ['count' => $result['count']]);
    }
}
