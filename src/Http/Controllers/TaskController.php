<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\TaskService;

class TaskController extends AbstractController
{
    private TaskService $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success($result['data'], 'Задачи найдены', 200, ['count' => $result['count']]);
    }

    public function show(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('Не указан ID задачи', 400);
        }
        $task = $this->service->getOne($id);
        return $this->success($task, 'Задача найдена', 200);
    }

    /**
     * POST /checktask — проверка решения задачи (legacy формат: {success, message}).
     */
    public function check(Request $request, array $params): JsonResponse
    {
        $taskId = (int)$request->input('taskId', 0);
        $code   = (string)$request->input('code', '');
        $userId = (int)$request->input('user_id', 0) ?: null;
        $payload = $this->service->check($taskId, $code, $userId);
        // Отдаём в legacy-формате — не оборачиваем в success/data.
        return new JsonResponse($payload, 200);
    }

    /**
     * POST /setpassedtask — отметить задачу как пройденную.
     */
    public function setPassed(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $taskId = (int)$request->input('task_id', 0);
        $passed = $this->service->markPassed($userId, $taskId);
        return $this->success($passed, $passed ? 'Задача пройдена' : 'Статус задачи обновлен', 200);
    }

    /**
     * POST /getpassedtask — получить статус прохождения одной задачи.
     */
    public function getPassed(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $taskId = (int)$request->input('task_id', 0);
        $passed = $this->service->isPassed($userId, $taskId);
        return $this->success($passed, $passed ? 'Задача пройдена' : 'Задача не пройдена', 200);
    }

    /**
     * POST /getpassedtasks — список ID пройденных задач пользователя (batch).
     */
    public function getPassedList(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $result = $this->service->listPassedByUser($userId);
        return $this->success($result['data'], 'Пройденные задачи получены', 200, ['count' => $result['count']]);
    }
}
