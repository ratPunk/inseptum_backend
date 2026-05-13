<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Topic;

class TopicRepository extends AbstractRepository
{
    protected string $table = 'topics';

    private const SELECT_WITH_MODULE = "SELECT
            topics.*,
            modules.title AS module_title,
            module_types.id   AS mt_id,
            module_types.slug AS mt_slug,
            module_types.name AS mt_name,
            module_types.icon AS mt_icon,
            module_types.highlight_language AS mt_highlight_language,
            module_types.color AS mt_color
        FROM topics
        LEFT JOIN modules      ON topics.module_id     = modules.id
        LEFT JOIN module_types ON modules.module_type_id = module_types.id";

    /** @return Topic[] */
    public function findAll(): array
    {
        $rows = $this->db->fetchAll(self::SELECT_WITH_MODULE . ' ORDER BY module_id, topics.id');
        return array_map([Topic::class, 'fromArray'], $rows);
    }

    /** @return Topic[] */
    public function findByModuleId(int $moduleId): array
    {
        $rows = $this->db->fetchAll(
            self::SELECT_WITH_MODULE . ' WHERE module_id = :mid ORDER BY topics.id',
            ['mid' => $moduleId]
        );
        return array_map([Topic::class, 'fromArray'], $rows);
    }

    /** @return Topic[] */
    public function findByModuleTitle(string $moduleTitle): array
    {
        $rows = $this->db->fetchAll(
            self::SELECT_WITH_MODULE . ' WHERE LOWER(modules.title) = LOWER(:title) ORDER BY topics.id',
            ['title' => $moduleTitle]
        );
        return array_map([Topic::class, 'fromArray'], $rows);
    }

    public function findOne(int $id): ?Topic
    {
        $row = $this->db->fetch(self::SELECT_WITH_MODULE . ' WHERE topics.id = :id', ['id' => $id]);
        return $row === null ? null : Topic::fromArray($row);
    }

    public function create(int $moduleId, string $title, string $description): int
    {
        $this->db->execute(
            'INSERT INTO `topics` (`module_id`, `title`, `description`) VALUES (:mid, :title, :description)',
            ['mid' => $moduleId, 'title' => $title, 'description' => $description]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $moduleId, string $title, string $description): int
    {
        return $this->db->execute(
            'UPDATE `topics` SET `module_id` = :mid, `title` = :title, `description` = :description WHERE `id` = :id',
            ['mid' => $moduleId, 'title' => $title, 'description' => $description, 'id' => $id]
        );
    }

    public function delete(int $id): int
    {
        return $this->db->execute('DELETE FROM `topics` WHERE id = :id', ['id' => $id]);
    }

    public function exists(int $id): bool
    {
        $row = $this->db->fetch('SELECT id FROM `topics` WHERE id = :id', ['id' => $id]);
        return $row !== null;
    }
}
