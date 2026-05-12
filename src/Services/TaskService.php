<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TaskPassedRepository;
use App\Repositories\TaskRepository;

class TaskService
{
    private TaskRepository $repo;
    private TaskPassedRepository $passedRepo;

    public function __construct(TaskRepository $repo, TaskPassedRepository $passedRepo)
    {
        $this->repo       = $repo;
        $this->passedRepo = $passedRepo;
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
     * Проверка решения задачи (заглушка, ранее имитировала ИИ).
     * Возвращает массив, который контроллер сериализует в legacy-формат
     * { success: bool, message: string }.
     */
    public function check(int $taskId, string $userCode, ?int $userId = null, int $maxChars = 5000): array
    {
        if ($taskId <= 0 || trim($userCode) === '') {
            throw new ValidationException('Код не может быть пустым.');
        }
        if (mb_strlen($userCode) > $maxChars) {
            throw new ValidationException("Код слишком длинный (макс. $maxChars симв.)");
        }

        $open  = substr_count($userCode, '{');
        $close = substr_count($userCode, '}');
        if ($open !== $close) {
            return [
                'success' => false,
                'message' => 'Ошибка синтаксиса: не совпадает количество фигурных скобок { }.',
            ];
        }

        $task = $this->repo->findShortById($taskId);
        if ($task === null) {
            throw new NotFoundException('Задача не найдена в базе данных.');
        }

        // TODO: интеграция с ИИ (OpenAI/Anthropic/Proxy). Сейчас — мок-ответ.
        // Если решение «успешно» и есть user_id — фиксируем прохождение задачи.
        if ($userId !== null && $userId > 0) {
            $this->passedRepo->markPassed($userId, $taskId);
        }

        return [
            'success' => true,
            'message' => "ИИ проверил задачу '" . (string)$task['title'] . "': Решение корректно, логика соблюдена.",
        ];
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
     * Список ID всех пройденных задач пользователя (batch).
     *
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
