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
     * Find a user by their email address.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Create a new user. Returns the new user's ID.
     */
    public function create(string $name, string $email, string $password): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$name, $email, $hash]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Check if an email is already taken.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

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
