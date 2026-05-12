<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

abstract class AbstractRepository
{
    protected Database $db;
    /** @var string Database table name */
    protected string $table = '';

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM `{$this->table}` WHERE id = :id", ['id' => $id]);
    }

    public function all(string $orderBy = 'id'): array
    {
        $orderBy = preg_replace('/[^a-zA-Z0-9_]/', '', $orderBy);
        return $this->db->fetchAll("SELECT * FROM `{$this->table}` ORDER BY `$orderBy`");
    }

    public function deleteById(int $id): int
    {
        return $this->db->execute("DELETE FROM `{$this->table}` WHERE id = :id", ['id' => $id]);
    }
}
