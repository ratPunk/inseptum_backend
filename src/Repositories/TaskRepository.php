<?php
declare(strict_types=1);

namespace App\Repositories;

class TaskRepository extends AbstractRepository
{
    protected string $table = 'tasks';

    public function findAllWithJoins(): array
    {
        return $this->db->fetchAll(
            'SELECT tasks.*, topics.title AS topic_title, modules.title AS module_title
             FROM tasks
             LEFT JOIN topics  ON tasks.topic_id = topics.id
             LEFT JOIN modules ON topics.module_id = modules.id'
        );
    }

    public function findOneWithJoins(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT tasks.*, topics.title AS topic_title, modules.title AS module_title
             FROM tasks
             LEFT JOIN topics  ON tasks.topic_id = topics.id
             LEFT JOIN modules ON topics.module_id = modules.id
             WHERE tasks.id = :id',
            ['id' => $id]
        );
    }

    public function findShortById(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT title, description FROM `tasks` WHERE id = :id',
            ['id' => $id]
        );
    }
}
