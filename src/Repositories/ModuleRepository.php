<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Module;

class ModuleRepository extends AbstractRepository
{
    protected string $table = 'modules';

    /**
     * @return Module[]
     */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM `modules` ORDER BY id");
        return array_map([Module::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?Module
    {
        $row = $this->db->fetch('SELECT * FROM `modules` WHERE id = :id', ['id' => $id]);
        return $row === null ? null : Module::fromArray($row);
    }

    public function findByTitle(string $title): ?Module
    {
        $row = $this->db->fetch(
            'SELECT * FROM `modules` WHERE LOWER(`title`) = LOWER(:title) LIMIT 1',
            ['title' => $title]
        );
        return $row === null ? null : Module::fromArray($row);
    }

    public function create(string $title, string $description): int
    {
        $this->db->execute(
            'INSERT INTO `modules` (`title`, `description`) VALUES (:title, :description)',
            ['title' => $title, 'description' => $description]
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
            // whitelist column names
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
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
