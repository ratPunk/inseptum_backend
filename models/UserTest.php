<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class UserTest
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ut.*, t.title AS test_title, t.category, t.difficulty
             FROM user_tests ut
             JOIN tests t ON t.id = ut.test_id
             WHERE ut.user_id = ?
             ORDER BY ut.updated_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findLatest(int $userId, int $testId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_tests
             WHERE user_id = ? AND test_id = ?
             ORDER BY attempt DESC LIMIT 1'
        );
        $stmt->execute([$userId, $testId]);
        return $stmt->fetch() ?: null;
    }

    public function findAllAttempts(int $userId, int $testId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_tests
             WHERE user_id = ? AND test_id = ?
             ORDER BY attempt ASC'
        );
        $stmt->execute([$userId, $testId]);
        return $stmt->fetchAll() ?: [];
    }

    public function startAttempt(int $userId, int $testId, int $maxScore): int
    {
        $stmt = $this->db->prepare(
            'SELECT MAX(attempt) FROM user_tests WHERE user_id = ? AND test_id = ?'
        );
        $stmt->execute([$userId, $testId]);
        $lastAttempt = (int)$stmt->fetchColumn();
        $nextAttempt = $lastAttempt + 1;

        $stmt = $this->db->prepare(
            'INSERT INTO user_tests (user_id, test_id, status, score, max_score, percentage, started_at, attempt, created_at, updated_at)
             VALUES (?, ?, "in_progress", 0, ?, 0.00, NOW(), ?, NOW(), NOW())'
        );
        $stmt->execute([$userId, $testId, $maxScore, $nextAttempt]);
        return (int)$this->db->lastInsertId();
    }

    public function completeAttempt(int $id, int $score, int $maxScore, array $answers): bool
    {
        $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100, 2) : 0;

        $stmt = $this->db->prepare(
            'UPDATE user_tests
             SET status = "completed", score = ?, max_score = ?, percentage = ?, completed_at = NOW(), answers_json = ?, updated_at = NOW()
             WHERE id = ?'
        );
        return $stmt->execute([$score, $maxScore, $percentage, json_encode($answers), $id]);
    }

    public function getUserProgress(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                COUNT(DISTINCT test_id) AS tests_attempted,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS tests_completed,
                AVG(CASE WHEN status = "completed" THEN percentage ELSE NULL END) AS avg_percentage,
                MAX(CASE WHEN status = "completed" THEN percentage ELSE NULL END) AS best_percentage
             FROM user_tests
             WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [
            'tests_attempted' => 0,
            'tests_completed' => 0,
            'avg_percentage' => 0,
            'best_percentage' => 0,
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_tests WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}