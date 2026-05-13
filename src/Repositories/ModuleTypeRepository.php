<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\ModuleType;

class ModuleTypeRepository extends AbstractRepository
{
    protected string $table = 'module_types';

    /** @return ModuleType[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll('SELECT * FROM `module_types` ORDER BY id');
        return array_map([ModuleType::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?ModuleType
    {
        $row = $this->db->fetch('SELECT * FROM `module_types` WHERE id = :id', ['id' => $id]);
        return $row === null ? null : ModuleType::fromArray($row);
    }

    public function findBySlug(string $slug): ?ModuleType
    {
        $row = $this->db->fetch(
            'SELECT * FROM `module_types` WHERE LOWER(`slug`) = LOWER(:slug) LIMIT 1',
            ['slug' => $slug]
        );
        return $row === null ? null : ModuleType::fromArray($row);
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch('SELECT id FROM `module_types` WHERE id = :id', ['id' => $id]);
        return $row !== null;
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId !== null) {
            $row = $this->db->fetch(
                'SELECT id FROM `module_types` WHERE LOWER(`slug`) = LOWER(:slug) AND id <> :id LIMIT 1',
                ['slug' => $slug, 'id' => $exceptId]
            );
        } else {
            $row = $this->db->fetch(
                'SELECT id FROM `module_types` WHERE LOWER(`slug`) = LOWER(:slug) LIMIT 1',
                ['slug' => $slug]
            );
        }
        return $row !== null;
    }

    public function create(array $fields): int
    {
        $this->db->execute(
            'INSERT INTO `module_types` (`slug`, `name`, `icon`, `highlight_language`, `color`)
             VALUES (:slug, :name, :icon, :highlight_language, :color)',
            [
                'slug'               => $fields['slug'],
                'name'               => $fields['name'],
                'icon'               => $fields['icon'],
                'highlight_language' => $fields['highlight_language'] ?? null,
                'color'              => $fields['color'] ?? null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $fields): int
    {
        if (empty($fields)) {
            return 0;
        }
        $allowed = ['slug', 'name', 'icon', 'highlight_language', 'color'];
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
        return $this->db->execute(
            'UPDATE `module_types` SET ' . implode(', ', $set) . ' WHERE id = :id',
            $params
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `module_types` WHERE id = :id', ['id' => $id]);
    }

    /**
     * Number of modules currently referencing this module type.
     */
    public function countModules(int $id): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS cnt FROM `modules` WHERE `module_type_id` = :id',
            ['id' => $id]
        );
        return $row === null ? 0 : (int)$row['cnt'];
    }
}
