<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\ModuleType;
use App\Repositories\ModuleTypeRepository;
use App\Validators\ModuleTypeValidator;

class ModuleTypeService
{
    private ModuleTypeRepository $repo;
    private ModuleTypeValidator $validator;

    public function __construct(ModuleTypeRepository $repo, ModuleTypeValidator $validator)
    {
        $this->repo      = $repo;
        $this->validator = $validator;
    }

    /**
     * @return array{data: array, count: int}
     */
    public function listAll(): array
    {
        $types = $this->repo->findAll();
        $data  = array_map(static fn(ModuleType $t) => $t->toArray(), $types);
        return ['data' => $data, 'count' => count($data)];
    }

    public function getByIdentifier(string $identifier): array
    {
        $type = is_numeric($identifier)
            ? $this->repo->findOne((int)$identifier)
            : $this->repo->findBySlug($identifier);
        if ($type === null) {
            throw new NotFoundException('Тип модуля не найден');
        }
        return $type->toArray();
    }

    public function create(array $input): array
    {
        $clean = $this->validator->validateCreate($input);
        if ($this->repo->slugExists($clean['slug'])) {
            throw new ValidationException('Тип модуля с таким slug уже существует', ['slug' => 'Slug должен быть уникальным']);
        }
        $newId = $this->repo->create($clean);
        $type  = $this->repo->findOne($newId);
        if ($type === null) {
            throw new NotFoundException('Не удалось получить созданный тип модуля');
        }
        return $type->toArray();
    }

    public function update(int $id, array $input): array
    {
        if (!$this->repo->exists($id)) {
            throw new NotFoundException("Тип модуля с ID $id не найден");
        }
        $clean = $this->validator->validateUpdate($input);
        if ($this->repo->slugExists($clean['slug'], $id)) {
            throw new ValidationException('Тип модуля с таким slug уже существует', ['slug' => 'Slug должен быть уникальным']);
        }
        $this->repo->update($id, $clean);
        $type = $this->repo->findOne($id);
        return $type === null ? [] : $type->toArray();
    }

    public function delete(int $id): array
    {
        $type = $this->repo->findOne($id);
        if ($type === null) {
            throw new NotFoundException("Тип модуля с ID $id не найден");
        }
        $used = $this->repo->countModules($id);
        if ($used > 0) {
            throw new ConflictException(
                "Невозможно удалить тип модуля: к нему привязано модулей: $used"
            );
        }
        $this->repo->delete($id);
        return ['id' => $type->id, 'slug' => $type->slug];
    }
}
