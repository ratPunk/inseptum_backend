<?php
declare(strict_types=1);

namespace App\Validators;

/**
 * Validates admin-panel user CRUD payloads. Login/register validation
 * lives in UserValidator; this validator is for create/update from the
 * admin UI where the `role` field is explicitly set and the password is
 * optional on update.
 */
class UserAdminValidator extends AbstractValidator
{
    private const USERNAME_MIN = 3;
    private const USERNAME_MAX = 20;
    private const PASSWORD_MIN = 3;
    private const ALLOWED_ROLES = ['user', 'spectator', 'moderator', 'admin'];

    /**
     * @return array{username:string, password:string, role:string}
     */
    public function validateCreate(array $data): array
    {
        $username = $this->sanitizeUsername((string)($data['username'] ?? ''));
        $password = $this->sanitizePassword((string)($data['password'] ?? ''));
        $role     = (string)($data['role'] ?? 'user');

        if ($username === '') {
            $this->addError('username', 'Имя пользователя обязательно');
        } else {
            $len = mb_strlen($username);
            if ($len < self::USERNAME_MIN || $len > self::USERNAME_MAX) {
                $this->addError('username', 'Неверная длина имени');
            }
        }

        if ($password === '') {
            $this->addError('password', 'Пароль обязателен');
        } elseif (mb_strlen($password) < self::PASSWORD_MIN) {
            $this->addError('password', 'Пароль слишком простой');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $this->addError('role', 'Недопустимая роль');
        }

        $this->failIfErrors('Ошибка валидации пользователя');

        return [
            'username' => $username,
            'password' => $password,
            'role'     => $role,
        ];
    }

    /**
     * Partial update. Returns only the keys actually provided (password only
     * when non-empty), so callers can skip hashing when omitted.
     *
     * @return array{username?:string, password?:string, role?:string}
     */
    public function validateUpdate(array $data): array
    {
        $out = [];

        if (array_key_exists('username', $data)) {
            $username = $this->sanitizeUsername((string)$data['username']);
            if ($username !== '') {
                $len = mb_strlen($username);
                if ($len < self::USERNAME_MIN || $len > self::USERNAME_MAX) {
                    $this->addError('username', 'Неверная длина имени');
                } else {
                    $out['username'] = $username;
                }
            }
        }

        if (array_key_exists('password', $data)) {
            $password = $this->sanitizePassword((string)$data['password']);
            if ($password !== '') {
                if (mb_strlen($password) < self::PASSWORD_MIN) {
                    $this->addError('password', 'Пароль слишком простой');
                } else {
                    $out['password'] = $password;
                }
            }
        }

        if (array_key_exists('role', $data)) {
            $role = (string)$data['role'];
            if ($role !== '') {
                if (!in_array($role, self::ALLOWED_ROLES, true)) {
                    $this->addError('role', 'Недопустимая роль');
                } else {
                    $out['role'] = $role;
                }
            }
        }

        $this->failIfErrors('Ошибка валидации пользователя');

        return $out;
    }
}
