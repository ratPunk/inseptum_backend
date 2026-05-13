<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Module;

class ModuleRepository extends AbstractRepository
{
    protected string $table = 'modules';

    private const SELECT = "SELECT
            modules.*,
            module_types.id   AS mt_id,
            module_types.slug AS mt_slug,
            module_types.name AS mt_name,
            module_types.icon AS mt_icon,
            module_types.highlight_language AS mt_highlight_language,
            module_types.color AS mt_color
        FROM `modules`
        LEFT JOIN `module_types` ON modules.module_type_id = module_types.id";

    /**
     * @return Module[]
     */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' ORDER BY modules.id');
        return array_map([Module::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?Module
    {
        $row = $this->db->fetch(self::SELECT . ' WHERE modules.id = :id', ['id' => $id]);
        return $row === null ? null : Module::fromArray($row);
    }

    public function findByTitle(string $title): ?Module
    {
        $row = $this->db->fetch(
            self::SELECT . ' WHERE LOWER(modules.`title`) = LOWER(:title) LIMIT 1',
            ['title' => $title]
        );
        return $row === null ? null : Module::fromArray($row);
    }

    public function create(string $title, string $description, ?int $moduleTypeId = null): int
    {
        $this->db->execute(
            'INSERT INTO `modules` (`title`, `description`, `module_type_id`)
             VALUES (:title, :description, :module_type_id)',
            [
                'title'          => $title,
                'description'    => $description,
                'module_type_id' => $moduleTypeId,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $fields): int
    {
        if (empty($fields)) {
            return 0;
        }
        $allowed = ['title', 'description', 'module_type_id'];
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
        $sql = "UPDATE `modules` SET " . implode(', ', $set) . " WHERE id = :id";
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `modules` WHERE id = :id', ['id' => $id]);
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch('SELECT id FROM `modules` WHERE id = :id', ['id' => $id]);
        return $row !== null;
    }
}
