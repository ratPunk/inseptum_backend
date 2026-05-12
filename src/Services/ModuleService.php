<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Models\Module;
use App\Repositories\ModuleRepository;
use App\Validators\ModuleValidator;

class ModuleService
{
    private ModuleRepository $repo;
    private ModuleValidator $validator;

    public function __construct(ModuleRepository $repo, ModuleValidator $validator)
    {
        $this->repo      = $repo;
        $this->validator = $validator;
    }

    /**
     * @return array{data: array, count: int}
     */
    public function listAll(): array
    {
        $modules = $this->repo->findAll();
        if (empty($modules)) {
            throw new NotFoundException('Модули не найдены');
        }
        $data = array_map(static fn(Module $m) => $m->toArray(), $modules);
        return ['data' => $data, 'count' => count($data)];
    }

    public function getByIdentifier(string $identifier): array
    {
        $module = is_numeric($identifier)
            ? $this->repo->findOne((int)$identifier)
            : $this->repo->findByTitle($identifier);

        if ($module === null) {
            throw new NotFoundException('Модуль не найден');
        }
        return $module->toArray();
    }

    public function create(array $input): array
    {
        $clean = $this->validator->validateCreate($input);
        $newId = $this->repo->create($clean['title'], $clean['description']);
        $module = $this->repo->findOne($newId);
        if ($module === null) {
            throw new NotFoundException('Не удалось получить созданный модуль');
        }
        return $module->toArray();
    }

    public function update(int $id, array $input): array
    {
        if (!$this->repo->exists($id)) {
            throw new NotFoundException("Модуль с ID $id не найден");
        }
        $clean = $this->validator->validateUpdate($input);
        $this->repo->update($id, [
            'title'       => $clean['title'],
            'description' => $clean['description'],
        ]);
        $module = $this->repo->findOne($id);
        return $module->toArray();
    }

    public function delete(int $id): array
    {
        $module = $this->repo->findOne($id);
        if ($module === null) {
            throw new NotFoundException("Модуль с ID $id не найден");
        }
        $this->repo->delete($id);
        return ['id' => $module->id, 'title' => $module->title];
    }
}
