<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Topic;
use App\Repositories\TopicRepository;
use App\Validators\TopicValidator;

class TopicService
{
    private TopicRepository $repo;
    private TopicValidator $validator;

    public function __construct(TopicRepository $repo, TopicValidator $validator)
    {
        $this->repo      = $repo;
        $this->validator = $validator;
    }

    /**
     * @return array{data: array, count: int}
     */
    public function listAll(): array
    {
        $topics = $this->repo->findAll();
        if (empty($topics)) {
            throw new NotFoundException('Темы не найдены');
        }
        $data = array_map(static fn(Topic $t) => $t->toArray(), $topics);
        return ['data' => $data, 'count' => count($data)];
    }

    /**
     * @return array{data: array, count: int}
     */
    public function listByModule(string $identifier): array
    {
        $topics = is_numeric($identifier)
            ? $this->repo->findByModuleId((int)$identifier)
            : $this->repo->findByModuleTitle($identifier);

        if (empty($topics)) {
            throw new NotFoundException('Темы по модулю не найдены');
        }
        $data = array_map(static fn(Topic $t) => $t->toArray(), $topics);
        return ['data' => $data, 'count' => count($data)];
    }

    public function create(array $input): array
    {
        $clean = $this->validator->validate($input);
        $newId = $this->repo->create($clean['module_id'], $clean['title'], $clean['description']);
        $topic = $this->repo->findOne($newId);
        if ($topic === null) {
            throw new NotFoundException('Не удалось получить созданную тему');
        }
        return $topic->toArray();
    }

    public function update(int $id, array $input): array
    {
        if (!$this->repo->exists($id)) {
            throw new NotFoundException('Тема не найдена');
        }
        $clean = $this->validator->validate($input);
        $this->repo->update($id, $clean['module_id'], $clean['title'], $clean['description']);
        $topic = $this->repo->findOne($id);
        return $topic->toArray();
    }

    public function delete(int $id): array
    {
        $topic = $this->repo->findOne($id);
        if ($topic === null) {
            throw new NotFoundException("Тема с ID $id не найдена");
        }
        $this->repo->delete($id);
        return ['id' => $topic->id, 'title' => $topic->title];
    }
}
