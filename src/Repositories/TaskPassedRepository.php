<?php
declare(strict_types=1);

namespace App\Repositories;

class TaskPassedRepository extends AbstractRepository
{
    protected string $table = 'user_task_passed';

    public function findOne(int $userId, int $taskId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM `user_task_passed` WHERE user_id = :uid AND task_id = :tid LIMIT 1',
            ['uid' => $userId, 'tid' => $taskId]
        );
    }

    /**
     * Все пройденные задачи пользователя.
     * Возвращает массив строк (id, user_id, task_id, is_passed, passed_at, ...).
     */
    public function listByUser(int $userId, bool $onlyPassed = true): array
    {
        $sql = 'SELECT * FROM `user_task_passed` WHERE user_id = :uid';
        if ($onlyPassed) {
            $sql .= ' AND is_passed = 1';
        }
        return $this->db->fetchAll($sql, ['uid' => $userId]);
    }

    public function markPassed(int $userId, int $taskId): array
    {
        $existing = $this->findOne($userId, $taskId);
        if ($existing !== null) {
            $this->db->execute(
                'UPDATE `user_task_passed` SET is_passed = 1, passed_at = NOW() WHERE id = :id',
                ['id' => (int)$existing['id']]
            );
        } else {
            $this->db->execute(
                'INSERT INTO `user_task_passed` (user_id, task_id, is_passed, passed_at) VALUES (:uid, :tid, 1, NOW())',
                ['uid' => $userId, 'tid' => $taskId]
            );
        }
        return $this->findOne($userId, $taskId) ?? [];
    }
}
