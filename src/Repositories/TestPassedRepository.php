<?php
declare(strict_types=1);

namespace App\Repositories;

class TestPassedRepository extends AbstractRepository
{
    protected string $table = 'user_test_passed';

    public function findOne(int $userId, int $testId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM `user_test_passed` WHERE user_id = :uid AND test_id = :tid LIMIT 1',
            ['uid' => $userId, 'tid' => $testId]
        );
    }

    /**
     * Все записи о тестах пользователя.
     * @param bool $onlyPassed Если true — только реально пройденные (is_passed = 1).
     */
    public function listByUser(int $userId, bool $onlyPassed = true): array
    {
        $sql = 'SELECT * FROM `user_test_passed` WHERE user_id = :uid';
        if ($onlyPassed) {
            $sql .= ' AND is_passed = 1';
        }
        return $this->db->fetchAll($sql, ['uid' => $userId]);
    }

    public function markPassed(int $userId, int $testId): array
    {
        $existing = $this->findOne($userId, $testId);
        if ($existing !== null) {
            $this->db->execute(
                'UPDATE `user_test_passed` SET is_passed = 1 WHERE id = :id',
                ['id' => (int)$existing['id']]
            );
        } else {
            $this->db->execute(
                'INSERT INTO `user_test_passed` (user_id, test_id, is_passed) VALUES (:uid, :tid, 1)',
                ['uid' => $userId, 'tid' => $testId]
            );
        }
        return $this->findOne($userId, $testId) ?? [];
    }
}
