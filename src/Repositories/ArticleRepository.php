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
            modules.title AS module_title,
            module_types.id   AS mt_id,
            module_types.slug AS mt_slug,
            module_types.name AS mt_name,
            module_types.icon AS mt_icon,
            module_types.highlight_language AS mt_highlight_language,
            module_types.color AS mt_color,
            articles.topic_id,
            topics.title AS topic_title,
            articles.test_id,
            tests.title AS test_title,
            articles.task_id,
            tasks.title AS task_title,
            articles.file_path,
            articles.created_at
        FROM articles
        LEFT JOIN topics       ON articles.topic_id      = topics.id
        LEFT JOIN modules      ON topics.module_id       = modules.id
        LEFT JOIN module_types ON modules.module_type_id = module_types.id
        LEFT JOIN tests        ON articles.test_id       = tests.id
        LEFT JOIN tasks        ON articles.task_id       = tasks.id";

    /** @return Article[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll(self::SELECT . ' ORDER BY modules.id, topics.id, articles.id');
        return array_map([Article::class, 'fromArray'], $rows);
    }

    /** @return Article[] */
    public function findByTopicId(int $topicId): array
    {
        $rows = $this->db->fetchAll(
            self::SELECT . ' WHERE topic_id = :tid ORDER BY modules.id, topics.id, articles.id',
            ['tid' => $topicId]
        );
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

    public function create(int $topicId, string $title, string $description, string $filePath): int
    {
        $this->db->execute(
            'INSERT INTO `articles` (`topic_id`, `title`, `description`, `file_path`, `created_at`)
             VALUES (:tid, :title, :description, :file, CURRENT_TIMESTAMP())',
            ['tid' => $topicId, 'title' => $title, 'description' => $description, 'file' => $filePath]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $topicId, string $title, string $description, string $filePath): int
    {
        return $this->db->execute(
            'UPDATE `articles` SET `title` = :title, `description` = :description, `topic_id` = :tid, `file_path` = :file WHERE `id` = :id',
            ['title' => $title, 'description' => $description, 'tid' => $topicId, 'file' => $filePath, 'id' => $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `articles` WHERE id = :id', ['id' => $id]);
    }
}
