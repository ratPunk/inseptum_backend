<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Test;

class TestRepository extends AbstractRepository
{
    protected string $table = 'tests';

    /**
     * Тест с подтянутыми модулем/типом модуля и связанной статьёй (+ задачей через статью).
     * Цепочка: tests → modules → module_types и tests ← articles → tasks.
     */
    /**
     * Подзапрос: одна (минимальная по id) статья, ссылающаяся на текущий тест.
     * Используется, чтобы избежать дублирования строк теста, если несколько
     * статей привязаны к одному и тому же тесту.
     */
    private const SELECT = "SELECT
            tests.id,
            tests.title,
            tests.description,
            tests.file_path,
            tests.time_limit,
            tests.question_count,
            tests.created_at,
            tests.module_id,
            modules.title AS module_title,
            module_types.id   AS mt_id,
            module_types.slug AS mt_slug,
            module_types.name AS mt_name,
            module_types.icon AS mt_icon,
            module_types.highlight_language AS mt_highlight_language,
            module_types.color AS mt_color,
            articles.id      AS article_id,
            articles.title   AS article_title,
            articles.task_id AS task_id,
            tasks.title      AS task_title
        FROM tests
        LEFT JOIN modules      ON tests.module_id        = modules.id
        LEFT JOIN module_types ON modules.module_type_id = module_types.id
        LEFT JOIN articles     ON articles.id = (
            SELECT MIN(a.id) FROM articles a WHERE a.test_id = tests.id
        )
        LEFT JOIN tasks        ON articles.task_id       = tasks.id";

    /** @return Test[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' ORDER BY tests.id');
        return array_map([Test::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?Test
    {
        $row = $this->db->fetch(self::SELECT . ' WHERE tests.id = :id LIMIT 1', ['id' => $id]);
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
}
