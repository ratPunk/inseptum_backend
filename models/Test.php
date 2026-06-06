<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Test
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(?string $category = null, ?string $difficulty = null, ?string $status = 'active'): array
    {
        $where = [];
        $params = [];

        if ($status !== null) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($category !== null) {
            $where[] = 'category = ?';
            $params[] = $category;
        }

        if ($difficulty !== null) {
            $where[] = 'difficulty = ?';
            $params[] = $difficulty;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare(
            "SELECT id, title, description, category, difficulty, max_score, status, time_limit, created_at, updated_at
             FROM tests $whereClause ORDER BY created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, title, description, category, difficulty, max_score, json_path, status, time_limit, created_at, updated_at
             FROM tests WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getContent(int $id): ?array
    {
        $test = $this->findById($id);
        if (!$test) {
            return null;
        }

        $jsonPath = __DIR__ . '/../storage/tests/' . $test['json_path'];
        if (!file_exists($jsonPath)) {
            return null;
        }

        $content = json_decode(file_get_contents($jsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $content;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO tests (title, description, category, difficulty, max_score, json_path, status, time_limit, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['category'] ?? 'general',
            $data['difficulty'] ?? 'medium',
            $data['max_score'] ?? 0,
            $data['json_path'],
            $data['status'] ?? 'active',
            $data['time_limit'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowed = ['title', 'description', 'category', 'difficulty', 'max_score', 'json_path', 'status', 'time_limit'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $stmt = $this->db->prepare(
            'UPDATE tests SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tests WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getJsonPath(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT json_path FROM tests WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }
}