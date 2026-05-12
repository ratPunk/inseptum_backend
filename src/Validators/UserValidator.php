<?php
declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

/**
 * Validates user-facing inputs (register / login). Sanitizes username/password
 * the same way the legacy api/register.php and api/login.php scripts did.
 */
class UserValidator extends AbstractValidator
{
    private const USERNAME_MIN = 3;
    private const USERNAME_MAX = 20;
    private const PASSWORD_MIN = 3;

    /**
     * @return array{username:string, password:string, confirm_password:string}
     */
    public function validateRegister(array $data): array
    {
        $username        = (string)($data['username'] ?? '');
        $password        = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if ($username === '' || $password === '' || $confirmPassword === '') {
            throw new ValidationException('Данные пользователя не заполнены');
        }

        $username        = $this->sanitizeUsername($username);
        $password        = $this->sanitizePassword($password);
        $confirmPassword = $this->sanitizePassword($confirmPassword);

        $len = mb_strlen($username);
        if ($len < self::USERNAME_MIN || $len > self::USERNAME_MAX) {
            throw new ValidationException('Неверная длина имени');
        }

        if (mb_strlen($password) < self::PASSWORD_MIN) {
            throw new ValidationException('Пароль слишком простой');
        }

        if ($password !== $confirmPassword) {
            throw new ValidationException('Пароли не совпадают');
        }

        return [
            'username'         => $username,
            'password'         => $password,
            'confirm_password' => $confirmPassword,
        ];
    }

    /**
     * @return array{username:string, password:string}
     */
    public function validateLogin(array $data): array
    {
        $username = (string)($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');

        if ($username === '' || $password === '') {
            throw new ValidationException('Данные пользователя не заполнены');
        }

        $username = $this->sanitizeUsername($username);
        $password = $this->sanitizePassword($password);

        return [
            'username' => $username,
            'password' => $password,
        ];
    }
}
