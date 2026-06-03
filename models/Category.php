<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Category
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, slug, icon, sort_order, created_at FROM categories ORDER BY sort_order ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, slug, icon, sort_order, created_at FROM categories WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, slug, icon, sort_order, created_at FROM categories WHERE slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $slug, string $icon = '', int $sortOrder = 0): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug, icon, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$name, $slug, $icon, $sortOrder]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $name, string $slug, string $icon = '', int $sortOrder = 0): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET name = ?, slug = ?, icon = ?, sort_order = ? WHERE id = ?'
        );
        return $stmt->execute([$name, $slug, $icon, $sortOrder, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getArticleCount(int $categoryId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM articles WHERE category_id = ?');
        $stmt->execute([$categoryId]);
        return (int)$stmt->fetchColumn();
    }
}