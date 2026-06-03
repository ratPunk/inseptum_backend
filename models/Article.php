<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Article
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findAll(?int $categoryId = null): array
    {
        if ($categoryId !== null) {
            $stmt = $this->db->prepare(
                'SELECT a.*, c.name AS category_name, c.slug AS category_slug
                 FROM articles a
                 JOIN categories c ON c.id = a.category_id
                 WHERE a.category_id = ?
                 ORDER BY a.created_at DESC'
            );
            $stmt->execute([$categoryId]);
        } else {
            $stmt = $this->db->query(
                'SELECT a.*, c.name AS category_name, c.slug AS category_slug
                 FROM articles a
                 JOIN categories c ON c.id = a.category_id
                 ORDER BY a.created_at DESC'
            );
        }

        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT a.*, c.name AS category_name, c.slug AS category_slug
             FROM articles a
             JOIN categories c ON c.id = a.category_id
             WHERE a.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $title, ?string $description, string $filename, int $categoryId, ?int $authorId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO articles (title, description, filename, category_id, author_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([$title, $description, $filename, $categoryId, $authorId]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $title, ?string $description, ?string $filename, ?int $categoryId): bool
    {
        if ($filename !== null) {
            $stmt = $this->db->prepare(
                'UPDATE articles SET title = ?, description = ?, filename = ?, category_id = ?, updated_at = NOW() WHERE id = ?'
            );
            return $stmt->execute([$title, $description, $filename, $categoryId, $id]);
        }

        $stmt = $this->db->prepare(
            'UPDATE articles SET title = ?, description = ?, category_id = ?, updated_at = NOW() WHERE id = ?'
        );
        return $stmt->execute([$title, $description, $categoryId, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM articles WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getFilename(int $id): ?string
    {
        $stmt = $this->db->prepare('SELECT filename FROM articles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $result = $stmt->fetchColumn();
        return $result ?: null;
    }
}