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
    // Body: { "name": "...", "email": "...", "password": "..." }
    // -------------------------------------------------------------------------
    public function register(array $params = []): void
    {
        $body = $this->getBody();

        $missing = $this->requireFields($body, ['name', 'email', 'password']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $name     = trim($body['name']);
        $email    = strtolower(trim($body['email']));
        $password = $body['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address', 422);
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters', 422);
        }

        if ($this->userModel->emailExists($email)) {
            $this->error('Email is already registered', 409);
        }

        $userId = $this->userModel->create($name, $email, $password);
        $user   = $this->userModel->findById($userId);

        $token = $this->jwt->generate(['sub' => $userId, 'email' => $email]);

        $this->json([
            'message' => 'Registration successful',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/login
    // Body: { "email": "...", "password": "..." }
    // -------------------------------------------------------------------------
    public function login(array $params = []): void
    {
        $body = $this->getBody();

        $missing = $this->requireFields($body, ['email', 'password']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $email    = strtolower(trim($body['email']));
        $password = $body['password'];

        $user = $this->userModel->findByEmail($email);

        // Use a generic message to avoid user enumeration
        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->error('Invalid email or password', 401);
        }

        $token = $this->jwt->generate(['sub' => $user['id'], 'email' => $user['email']]);

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
