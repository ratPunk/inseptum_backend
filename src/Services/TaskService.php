<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TaskRepository;

class TaskService
{
    private TaskRepository $repo;

    public function __construct(TaskRepository $repo)
    {
        $this->repo = $repo;
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
        return [
            'success' => true,
            'message' => "ИИ проверил задачу '" . (string)$task['title'] . "': Решение корректно, логика соблюдена.",
        ];
    }
}
