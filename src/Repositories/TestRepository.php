<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Test;

class TestRepository extends AbstractRepository
{
    protected string $table = 'tests';

    private const SELECT = "SELECT
            tests.*,
            articles.title AS article_title,
            modules.title AS module_title
        FROM tests
        LEFT JOIN articles ON tests.id = articles.test_id
        LEFT JOIN topics ON articles.topic_id = topics.id
        LEFT JOIN modules ON topics.module_id = modules.id";

    /** @return Test[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll(self::SELECT);
        return array_map([Test::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?Test
    {
        $row = $this->db->fetch(self::SELECT . ' WHERE tests.id = :id', ['id' => $id]);
        return $row === null ? null : Test::fromArray($row);
    }

    public function rawById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `tests` WHERE id = :id', ['id' => $id]);
    }

    public function create(string $title, string $description, int $timeLimit, int $questionCount, string $filePath): int
    {
        $this->db->execute(
            'INSERT INTO `tests` (`title`, `description`, `time_limit`, `question_count`, `file_path`)
             VALUES (:title, :description, :time_limit, :question_count, :file)',
            [
                'title' => $title,
                'description' => $description,
                'time_limit' => $timeLimit,
                'question_count' => $questionCount,
                'file' => $filePath,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $fields): int
    {
        if (empty($fields)) {
            return 0;
        }
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $col => $value) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                continue;
            }
            $set[] = "`$col` = :$col";
            $params[$col] = $value;
        }
        if (empty($set)) {
            return 0;
        }
        return $this->db->execute(
            'UPDATE `tests` SET ' . implode(', ', $set) . ' WHERE id = :id',
            $params
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `tests` WHERE id = :id', ['id' => $id]);
    }

    public function detachFromArticles(int $testId): int
    {
        return $this->db->execute(
            'UPDATE `articles` SET `test_id` = NULL WHERE `test_id` = :id',
            ['id' => $testId]
        );
    }

    public function attachToTopic(int $testId, int $topicId): int
    {
        return $this->db->execute(
            'UPDATE `articles` SET `test_id` = :tid WHERE `topic_id` = :topic LIMIT 1',
            ['tid' => $testId, 'topic' => $topicId]
        );
    }
}
