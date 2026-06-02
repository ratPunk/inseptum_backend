<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a user by their login.
     */
    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, login, password, created_at FROM users WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, login, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Create a new user. Returns the new user's ID.
     */
    public function create(string $name, string $login, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, login, password, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$name, $login, $hash]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Check if a login is already taken.
     */
    public function loginExists(string $login): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Verify a plain-text password against the stored hash.
     */
    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
