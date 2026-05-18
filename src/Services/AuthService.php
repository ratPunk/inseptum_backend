<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;

class AuthService
{
    private UserRepository $users;
    private UserValidator $validator;

    public function __construct(UserRepository $users, UserValidator $validator)
    {
        $this->users     = $users;
        $this->validator = $validator;
    }

    /**
     * Register a new user. Returns the public payload to embed under "data".
     *
     * @throws \App\Exceptions\ValidationException
     * @throws ConflictException
     */
    public function register(string $username, string $password, string $confirm): array
    {
        $clean = $this->validator->validateRegister([
            'username'         => $username,
            'password'         => $password,
            'confirm_password' => $confirm,
        ]);

        if ($this->users->existsByUsername($clean['username'])) {
            throw new ConflictException('Пользователь с таким именем уже существует');
        }

        $hash = password_hash($clean['password'], PASSWORD_DEFAULT);
        $id   = $this->users->create($clean['username'], $hash);

        // Match the legacy api/register.php response shape (plus role).
        return [
            'user_id'    => $id,
            'username'   => $clean['username'],
            'role'       => 'user',
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @throws \App\Exceptions\ValidationException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function login(string $username, string $password): array
    {
        $clean = $this->validator->validateLogin([
            'username' => $username,
            'password' => $password,
        ]);

        $user = $this->users->findByUsername($clean['username']);
        if ($user === null) {
            throw new NotFoundException('Пользователь не найден');
        }

        if (!password_verify($clean['password'], $user->passwordHash)) {
            throw new UnauthorizedException('Неверный пароль');
        }

        return $user->toPublicArray();
    }
}
