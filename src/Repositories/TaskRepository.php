<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\ModuleType;

class TaskRepository extends AbstractRepository
{
    protected string $table = 'tasks';

    private const SELECT = "SELECT
            tasks.*
         FROM tasks";

    public function findAllWithJoins(): array
    {
        $rows = $this->db->fetchAll(self::SELECT);
        return array_map([self::class, 'embedModuleType'], $rows);
    }

    public function findOneWithJoins(int $id): ?array
    {
        $row = $this->db->fetch(self::SELECT . ' WHERE tasks.id = :id', ['id' => $id]);
        return $row === null ? null : self::embedModuleType($row);
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
     * strip them from the flat row.
     */
    private static function embedModuleType(array $row): array
    {
        $row['module_type'] = ModuleType::embeddedFromPrefixedRow($row);
        foreach (['mt_id', 'mt_slug', 'mt_name', 'mt_icon', 'mt_highlight_language', 'mt_color'] as $col) {
            unset($row[$col]);
        }
        return $row;
    }
}
