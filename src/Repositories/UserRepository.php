<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends AbstractRepository
{
    protected string $table = 'users';

    public function findById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `users` WHERE id = :id', ['id' => $id]);
    }

    public function findByUsername(string $username): ?User
    {
        $row = $this->db->fetch(
            'SELECT * FROM `users` WHERE username = :username LIMIT 1',
            ['username' => $username]
        );
        return $row === null ? null : User::fromArray($row);
    }

    public function existsByUsername(string $username): bool
    {
        $row = $this->db->fetch(
            'SELECT 1 FROM `users` WHERE username = :username LIMIT 1',
            ['username' => $username]
        );
        return $row !== null;
    }

    /**
     * Insert new user, return the new ID.
     */
    public function create(string $username, string $hash): int
    {
        $this->db->execute(
            'INSERT INTO `users` (username, password) VALUES (:username, :password)',
            ['username' => $username, 'password' => $hash]
        );
        return (int)$this->db->lastInsertId();
    }
}
