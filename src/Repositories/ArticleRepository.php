<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Article;

class ArticleRepository extends AbstractRepository
{
    protected string $table = 'articles';

    private const SELECT = "SELECT
            articles.id,
            articles.title,
            articles.description,
            articles.test_id,
            tests.title AS test_title,
            articles.task_id,
            tasks.title AS task_title,
            articles.file_path,
            articles.created_at
        FROM articles
        LEFT JOIN tests        ON articles.test_id       = tests.id
        LEFT JOIN tasks        ON articles.task_id       = tasks.id";

    /** @return Article[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' ORDER BY articles.id');
        return array_map([Article::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?Article
    {
        $row = $this->db->fetch(self::SELECT . ' WHERE articles.id = :id', ['id' => $id]);
        return $row === null ? null : Article::fromArray($row);
    }

    public function rawById(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM `articles` WHERE id = :id', ['id' => $id]);
    }

    public function create(string $title, string $description, string $filePath): int
    {
        $this->db->execute(
            'INSERT INTO `articles` (`title`, `description`, `file_path`, `created_at`)
             VALUES (:title, :description, :file, CURRENT_TIMESTAMP())',
            ['title' => $title, 'description' => $description, 'file' => $filePath]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $title, string $description, string $filePath): int
    {
        return $this->db->execute(
            'UPDATE `articles` SET `title` = :title, `description` = :description, `file_path` = :file WHERE `id` = :id',
            ['title' => $title, 'description' => $description, 'file' => $filePath, 'id' => $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `articles` WHERE id = :id', ['id' => $id]);
    }
}
