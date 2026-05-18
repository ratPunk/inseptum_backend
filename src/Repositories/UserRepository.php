<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository extends AbstractRepository
{
    protected string $table = 'users';

    /** Columns returned to the client (never the password hash). */
    private const PUBLIC_COLS = 'id, username, role, created_at';

    public function findById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `users` WHERE id = :id', ['id' => $id]);
    }

    public function findPublicById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT ' . self::PUBLIC_COLS . ' FROM `users` WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findAllPublic(): array
    {
        return $this->db->fetchAll(
            'SELECT ' . self::PUBLIC_COLS . ' FROM `users` ORDER BY id'
        );
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
     * Check whether a given username is taken by a user OTHER than $excludeId.
     */
    public function existsByUsernameExcept(string $username, int $excludeId): bool
    {
        $row = $this->db->fetch(
            'SELECT 1 FROM `users` WHERE username = :username AND id <> :id LIMIT 1',
            ['username' => $username, 'id' => $excludeId]
        );
        return $row !== null;
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch('SELECT id FROM `users` WHERE id = :id', ['id' => $id]);
        return $row !== null;
    }

    /**
     * Insert new user, return the new ID. Used by AuthService (default role = 'user').
     */
    public function create(string $username, string $hash): int
    {
        $this->db->execute(
            'INSERT INTO `users` (username, password) VALUES (:username, :password)',
            ['username' => $username, 'password' => $hash]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Insert new user with an explicit role (admin panel use).
     */
    public function createWithRole(string $username, string $hash, string $role): int
    {
        $this->db->execute(
            'INSERT INTO `users` (username, password, role)
             VALUES (:username, :password, :role)',
            ['username' => $username, 'password' => $hash, 'role' => $role]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Partial update. Only whitelisted columns are applied; password is hashed by the caller.
     *
     * @param array<string,mixed> $fields  Allowed keys: username, password, role.
     */
    public function update(int $id, array $fields): int
    {
        $allowed = ['username', 'password', 'role'];
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
        $sql = 'UPDATE `users` SET ' . implode(', ', $set) . ' WHERE id = :id';
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `users` WHERE id = :id', ['id' => $id]);
    }
}
