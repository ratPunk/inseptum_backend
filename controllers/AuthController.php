<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Helpers\JwtHelper;

class AuthController extends Controller
{
    private User      $userModel;
    private JwtHelper $jwt;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwt       = new JwtHelper();
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/register
    // Body: { "name": "...", "login": "...", "password": "..." }
    // -------------------------------------------------------------------------
    public function register(array $params = []): void
    {
        $body = $this->getBody();

        $missing = $this->requireFields($body, ['name', 'login', 'password']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $name     = trim($body['name']);
        $login    = trim($body['login']);
        $password = $body['password'];

        if (strlen($login) < 2) {
            $this->error('Login must be at least 2 characters', 422);
        }

        if (strlen($name) < 2) {
            $this->error('Name must be at least 2 characters', 422);
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters', 422);
        }

        if ($this->userModel->loginExists($login)) {
            $this->error('Login is already taken', 409);
        }

        $userId = $this->userModel->create($name, $login, $password);
        $user   = $this->userModel->findById($userId);

        $token = $this->jwt->generate(['sub' => $userId, 'login' => $login]);

        // Strip password from response
        unset($user['password']);

        $this->json([
            'message' => 'Registration successful',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/login
    // Body: { "login": "...", "password": "..." }
    // -------------------------------------------------------------------------
    public function login(array $params = []): void
    {
        $body = $this->getBody();

        $missing = $this->requireFields($body, ['login', 'password']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $login    = trim($body['login']);
        $password = $body['password'];

        $user = $this->userModel->findByLogin($login);

        // Use a generic message to avoid user enumeration
        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->error('Invalid login or password', 401);
        }

        $token = $this->jwt->generate(['sub' => $user['id'], 'login' => $user['login']]);

        // Strip password from response
        unset($user['password']);

        $this->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/auth/me
    // Header: Authorization: Bearer <token>
    // -------------------------------------------------------------------------
    public function me(array $params = []): void
    {
        $token   = $this->jwt->fromHeader();
        $payload = $token ? $this->jwt->verify($token) : null;

        if (!$payload) {
            $this->error('Unauthorized', 401);
        }

        $user = $this->userModel->findById((int)$payload['sub']);

        if (!$user) {
            $this->error('User not found', 404);
        }

        $this->json(['user' => $user]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/logout
    // Stateless JWT: client simply discards the token.
    // This endpoint exists as a clean contract for the frontend.
    // -------------------------------------------------------------------------
    public function logout(array $params = []): void
    {
        $this->json(['message' => 'Logged out successfully']);
    }
}
