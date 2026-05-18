<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\UserRepository;
use App\Validators\UserAdminValidator;

/**
 * Admin-panel CRUD for users. Distinct from AuthService (register/login):
 *   - never returns the password hash;
 *   - hashes passwords with bcrypt on create/update.
 */
class UserService
{
    private UserRepository $users;
    private UserAdminValidator $validator;

    public function __construct(UserRepository $users, UserAdminValidator $validator)
    {
        $this->users     = $users;
        $this->validator = $validator;
    }

    /**
     * @return array{data: array, count: int}
     */
    public function listAll(): array
    {
        $rows = $this->users->findAllPublic();
        return ['data' => $rows, 'count' => count($rows)];
    }

    public function getOne(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('Не указан ID пользователя');
        }
        $row = $this->users->findPublicById($id);
        if ($row === null) {
            throw new NotFoundException('Пользователь не найден');
        }
        return $row;
    }

    public function create(array $input): array
    {
        $clean = $this->validator->validateCreate($input);

        if ($this->users->existsByUsername($clean['username'])) {
            throw new ConflictException('Пользователь с таким именем уже существует');
        }

        $hash = password_hash($clean['password'], PASSWORD_BCRYPT);
        $id   = $this->users->createWithRole($clean['username'], $hash, $clean['role']);

        $row = $this->users->findPublicById($id);
        if ($row === null) {
            throw new NotFoundException('Не удалось получить созданного пользователя');
        }
        return $row;
    }

    public function update(int $id, array $input): array
    {
        if ($id <= 0) {
            throw new ValidationException('Не указан ID пользователя');
        }
        if (!$this->users->exists($id)) {
            throw new NotFoundException("Пользователь с ID $id не найден");
        }

        $clean = $this->validator->validateUpdate($input);

        if (isset($clean['username']) && $this->users->existsByUsernameExcept($clean['username'], $id)) {
            throw new ConflictException('Пользователь с таким именем уже существует');
        }

        $fields = [];
        if (isset($clean['username'])) {
            $fields['username'] = $clean['username'];
        }
        if (isset($clean['role'])) {
            $fields['role'] = $clean['role'];
        }
        if (isset($clean['password']) && $clean['password'] !== '') {
            $fields['password'] = password_hash($clean['password'], PASSWORD_BCRYPT);
        }

        if (!empty($fields)) {
            $this->users->update($id, $fields);
        }

        $row = $this->users->findPublicById($id);
        return $row ?? [];
    }

    public function delete(int $id): array
    {
        if ($id <= 0) {
            throw new ValidationException('Не указан ID пользователя');
        }
        $row = $this->users->findPublicById($id);
        if ($row === null) {
            throw new NotFoundException("Пользователь с ID $id не найден");
        }
        $this->users->delete($id);
        return ['id' => (int)$row['id'], 'username' => (string)$row['username']];
    }
}
