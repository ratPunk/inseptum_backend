<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Models\User;
use App\Repositories\AdminRepository;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;

class AuthService
{
    private UserRepository $users;
    private AdminRepository $admins;
    private UserValidator $validator;

    public function __construct(UserRepository $users, AdminRepository $admins, UserValidator $validator)
    {
        $this->users     = $users;
        $this->admins    = $admins;
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

        // Match the legacy api/register.php response shape exactly.
        return [
            'user_id'    => $id,
            'username'   => $clean['username'],
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

    /**
     * @throws \App\Exceptions\ValidationException
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function adminLogin(string $username, string $password): array
    {
        $clean = $this->validator->validateLogin([
            'username' => $username,
            'password' => $password,
        ]);

        $admin = $this->admins->findByUsername($clean['username']);
        if ($admin === null) {
            throw new NotFoundException('Пользователь не найден');
        }

        if (!password_verify($clean['password'], $admin->passwordHash)) {
            throw new UnauthorizedException('Неверный пароль');
        }

        // Legacy api/adminLogin.php returned only user_id + username (no created_at).
        return [
            'user_id'  => $admin->id,
            'username' => $admin->username,
        ];
    }
}
