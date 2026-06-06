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
        // Use IFNULL as a fallback in case the role column doesn't exist yet
        $stmt = $this->db->prepare('SELECT id, name, login, password, IFNULL(role, "user") AS role, created_at FROM users WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    /**
     * Find a user by their ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, name, login, IFNULL(role, "user") AS role, created_at FROM users WHERE id = ? LIMIT 1');
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

    /**
     * Get all users with search, role filter, and pagination.
     */
    public function getAll(array $options = []): array
    {
        $search = $options['search'] ?? '';
        $role = $options['role'] ?? '';
        $sortBy = $options['sort_by'] ?? 'created_at';
        $sortOrder = strtoupper($options['sort_order'] ?? 'DESC');
        $page = max(1, (int)($options['page'] ?? 1));
        $limit = max(1, min(100, (int)($options['limit'] ?? 12)));
        $offset = ($page - 1) * $limit;

        $allowedSort = ['name', 'login', 'created_at', 'role'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'created_at';
        if (!in_array($sortOrder, ['ASC', 'DESC'])) $sortOrder = 'DESC';

        $where = [];
        $params = [];

        if ($search) {
            $where[] = '(name LIKE ? OR login LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($role && in_array($role, ['admin', 'user'])) {
            $where[] = 'role = ?';
            $params[] = $role;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $countSql = "SELECT COUNT(*) FROM users {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch
        $sql = "SELECT id, name, login, email, IFNULL(role, 'user') AS role, created_at FROM users {$whereClause} ORDER BY {$sortBy} {$sortOrder} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    /**
     * Admin create user with role.
     */
    public function adminCreate(string $name, string $login, string $password, string $role = 'user', ?string $email = null): int
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (name, login, password, email, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$name, $login, $hash, $email, $role]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update user fields.
     */
    public function adminUpdate(int $id, array $data): bool
    {
        $sets = [];
        $params = [];

        if (isset($data['name'])) {
            $sets[] = 'name = ?';
            $params[] = $data['name'];
        }
        if (isset($data['login'])) {
            $sets[] = 'login = ?';
            $params[] = $data['login'];
        }
        if (isset($data['email'])) {
            $sets[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (isset($data['role'])) {
            $sets[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (!empty($data['password'])) {
            $sets[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($sets)) return false;

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a user by ID.
     */
    public function adminDelete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Update user role.
     */
    public function updateRole(int $id, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE users SET role = ? WHERE id = ?');
        return $stmt->execute([$role, $id]);
    }

    /**
     * Find user by ID including email.
     */
    public function findByIdFull(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, name, login, email, IFNULL(role, 'user') AS role, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
