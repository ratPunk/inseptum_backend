<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\ModuleType;

class TaskRepository extends AbstractRepository
{
    protected string $table = 'tasks';

    /**
     * Задача с подтянутыми модулем/типом модуля и связанной статьёй (+ тестом через статью).
     * Цепочка: tasks → modules → module_types и tasks ← articles → tests.
     */
    /**
     * Подзапрос гарантирует одну строку статьи на задачу — иначе при нескольких
     * статьях, ссылающихся на одну задачу, в выдаче появились бы дубликаты.
     */
    private const SELECT = "SELECT
            tasks.id,
            tasks.title,
            tasks.description,
            tasks.difficulty,
            tasks.created_at,
            tasks.module_id,
            modules.title AS module_title,
            module_types.id   AS mt_id,
            module_types.slug AS mt_slug,
            module_types.name AS mt_name,
            module_types.icon AS mt_icon,
            module_types.highlight_language AS mt_highlight_language,
            module_types.color AS mt_color,
            articles.id      AS article_id,
            articles.title   AS article_title,
            articles.test_id AS test_id,
            tests.title      AS test_title
         FROM tasks
         LEFT JOIN modules      ON tasks.module_id        = modules.id
         LEFT JOIN module_types ON modules.module_type_id = module_types.id
         LEFT JOIN articles     ON articles.id = (
             SELECT MIN(a.id) FROM articles a WHERE a.task_id = tasks.id
         )
         LEFT JOIN tests        ON articles.test_id       = tests.id";

    public function findAllWithJoins(): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' ORDER BY tasks.id');
        return array_map([self::class, 'embedRelations'], $rows);
    }

    public function findOneWithJoins(int $id): ?array
    {
        $row = $this->db->fetch(self::SELECT . ' WHERE tasks.id = :id LIMIT 1', ['id' => $id]);
        return $row === null ? null : self::embedRelations($row);
    }

    public function findShortById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT title, description FROM `tasks` WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Move mt_* prefixed columns into a nested `module_type` object and
     * normalise related id types. Strips raw mt_* columns from the flat row.
     */
    private static function embedRelations(array $row): array
    {
        $row['module_type'] = ModuleType::embeddedFromPrefixedRow($row);
        foreach (['mt_id', 'mt_slug', 'mt_name', 'mt_icon', 'mt_highlight_language', 'mt_color'] as $col) {
            unset($row[$col]);
        }
        $row['id']         = isset($row['id']) ? (int)$row['id'] : null;
        $row['module_id']  = isset($row['module_id']) && $row['module_id'] !== null ? (int)$row['module_id'] : null;
        $row['article_id'] = isset($row['article_id']) && $row['article_id'] !== null ? (int)$row['article_id'] : null;
        $row['test_id']    = isset($row['test_id']) && $row['test_id'] !== null ? (int)$row['test_id'] : null;
        return $row;
    }

    // -----------------------------------------------------------------
    //                          CRUD (admin)
    // -----------------------------------------------------------------

    public function exists(int $id): bool
    {
        $row = $this->db->fetch('SELECT id FROM `tasks` WHERE id = :id', ['id' => $id]);
        return $row !== null;
    }

    /**
     * Insert a new task. `description` may be null.
     */
    public function create(string $title, ?string $description, string $difficulty): int
    {
        $this->db->execute(
            'INSERT INTO `tasks` (`title`, `description`, `difficulty`)
             VALUES (:title, :description, :difficulty)',
            [
                'title'       => $title,
                'description' => $description,
                'difficulty'  => $difficulty,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Partial update. Only whitelisted columns are applied.
     *
     * @param array<string,mixed> $fields  Allowed keys: title, description, difficulty.
     */
    public function update(int $id, array $fields): int
    {
        $allowed = ['title', 'description', 'difficulty'];
        $set = [];
        $params = ['id' => $id];
        foreach ($fields as $col => $value) {
            if (!in_array($col, $allowed, true)) {
                continue;
            }
            $set[] = "`$col` = :$col";
            $params[$col] = $value;
        }
        if (empty($set)) {
            return 0;
        }
        $sql = 'UPDATE `tasks` SET ' . implode(', ', $set) . ' WHERE id = :id';
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `tasks` WHERE id = :id', ['id' => $id]);
    }
}
