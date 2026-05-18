<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TaskPassedRepository;
use App\Repositories\TaskRepository;
use App\Services\Ai\TaskCheckerService;

class TaskService
{
    private TaskRepository $repo;
    private TaskPassedRepository $passedRepo;
    private TaskCheckerService $checker;

    public function __construct(TaskRepository $repo, TaskPassedRepository $passedRepo, TaskCheckerService $checker)
    {
        $this->repo       = $repo;
        $this->passedRepo = $passedRepo;
        $this->checker    = $checker;
    }

    public function listAll(): array
    {
        $rows = $this->repo->findAllWithJoins();
        if (empty($rows)) {
            throw new NotFoundException('Задачи не найдены');
        }
        return ['data' => $rows, 'count' => count($rows)];
    }

    public function getOne(int $id): array
    {
        $row = $this->repo->findOneWithJoins($id);
        if ($row === null) {
            throw new NotFoundException('Задача не найдена');
        }
        return $row;
    }

    /**
     * Проверка решения задачи через AI (Claude).
     *
     * @return array{success:bool,message:string,details:?array,cached:bool,error_code:?string,retry_after:?int}
     */
    public function check(int $taskId, string $userCode, ?int $userId, string $clientIp): array
    {
        if ($taskId <= 0) {
            throw new ValidationException('Не указан taskId.');
        }

        $task = $this->repo->findOneWithJoins($taskId);
        if ($task === null) {
            throw new NotFoundException('Задача не найдена в базе данных.');
        }

        // Минимальный snapshot задачи для AI-промпта.
        $taskPayload = [
            'id'          => (int)$task['id'],
            'title'       => (string)($task['title'] ?? ''),
            'description' => $task['description'] ?? null,
            'language'    => $task['module_type']['highlight_language']
                ?? $task['module_title']
                ?? null,
        ];

        $result = $this->checker->check($taskPayload, $userCode, $userId, $clientIp);

        // Если AI признал задачу решённой — фиксируем прохождение
        if (!empty($result['success']) && $userId !== null && $userId > 0) {
            try {
                $this->passedRepo->markPassed($userId, $taskId);
            } catch (\Throwable $e) {
                // не валим ответ пользователю, если запись не удалась
            }
        }

        return $result;
    }

    public function markPassed(int $userId, int $taskId): bool
    {
        if ($userId <= 0 || $taskId <= 0) {
            throw new ValidationException('Не указаны user_id и task_id');
        }
        $row = $this->passedRepo->markPassed($userId, $taskId);
        return (int)($row['is_passed'] ?? 0) === 1;
    }

    public function isPassed(int $userId, int $taskId): bool
    {
        if ($userId <= 0 || $taskId <= 0) {
            throw new ValidationException('Не указаны user_id и task_id');
        }
        $row = $this->passedRepo->findOne($userId, $taskId);
        return $row !== null && (int)$row['is_passed'] === 1;
    }

    /**
     * @return array{data: int[], count: int}
     */
    public function listPassedByUser(int $userId): array
    {
        if ($userId <= 0) {
            throw new ValidationException('Не указан user_id');
        }
        $rows = $this->passedRepo->listByUser($userId, true);
        $ids = array_map(static fn(array $r) => (int)$r['task_id'], $rows);
        return ['data' => $ids, 'count' => count($ids)];
    }
}
