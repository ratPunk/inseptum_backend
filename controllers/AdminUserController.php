<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Models\User;
use App\Helpers\JwtHelper;

class AdminUserController extends Controller
{
    private User      $userModel;
    private JwtHelper $jwt;
    private Logger    $logger;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwt       = new JwtHelper();
        $this->logger    = Logger::getInstance();
    }

    private function requireAdmin(): array
    {
        $token   = $this->jwt->fromHeader();
        $payload = $token ? $this->jwt->verify($token) : null;

        if (!$payload) {
            $this->error('Unauthorized', 401);
        }

        $user = $this->userModel->findById((int)$payload['sub']);
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            $this->error('Forbidden', 403);
        }

        return $user;
    }

    // GET /api/admin/users
    public function index(array $params = []): void
    {
        $this->requireAdmin();

        $result = $this->userModel->getAll([
            'search'     => $_GET['search'] ?? '',
            'role'       => $_GET['role'] ?? '',
            'sort_by'    => $_GET['sort_by'] ?? 'created_at',
            'sort_order' => $_GET['sort_order'] ?? 'desc',
            'page'       => $_GET['page'] ?? 1,
            'limit'      => $_GET['limit'] ?? 12,
        ]);

        $this->json($result);
    }

    // GET /api/admin/users/{id}
    public function show(array $params = []): void
    {
        $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);

        $user = $this->userModel->findByIdFull($id);
        if (!$user) {
            $this->error('User not found', 404);
        }

        $this->json(['user' => $user]);
    }

    // POST /api/admin/users
    public function create(array $params = []): void
    {
        $this->requireAdmin();
        $body = $this->getBody();

        $missing = $this->requireFields($body, ['name', 'login', 'password']);
        if ($missing) {
            $this->error('Missing required fields: ' . implode(', ', $missing), 422);
        }

        $name     = trim($body['name']);
        $login    = trim($body['login']);
        $password = $body['password'];
        $role     = $body['role'] ?? 'user';
        $email    = isset($body['email']) ? trim($body['email']) : null;

        if (!in_array($role, ['admin', 'user'])) {
            $this->error('Invalid role', 422);
        }

        if ($this->userModel->loginExists($login)) {
            $this->error('Login is already taken', 409);
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters', 422);
        }

        $userId = $this->userModel->adminCreate($name, $login, $password, $role, $email);
        $user   = $this->userModel->findByIdFull($userId);

        $this->logger->info('Admin created user', ['user_id' => $userId, 'login' => $login]);

        $this->json(['message' => 'User created', 'user' => $user], 201);
    }

    // PUT /api/admin/users/{id}
    public function update(array $params = []): void
    {
        $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $body = $this->getBody();

        $existing = $this->userModel->findByIdFull($id);
        if (!$existing) {
            $this->error('User not found', 404);
        }

        // Check login uniqueness if changed
        if (isset($body['login']) && $body['login'] !== $existing['login']) {
            if ($this->userModel->loginExists($body['login'])) {
                $this->error('Login is already taken', 409);
            }
        }

        if (isset($body['role']) && !in_array($body['role'], ['admin', 'user'])) {
            $this->error('Invalid role', 422);
        }

        if (!empty($body['password']) && strlen($body['password']) < 6) {
            $this->error('Password must be at least 6 characters', 422);
        }

        $this->userModel->adminUpdate($id, $body);
        $user = $this->userModel->findByIdFull($id);

        $this->logger->info('Admin updated user', ['user_id' => $id]);

        $this->json(['message' => 'User updated', 'user' => $user]);
    }

    // DELETE /api/admin/users/{id}
    public function delete(array $params = []): void
    {
        $admin = $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);

        if ($id === (int)$admin['id']) {
            $this->error('Cannot delete yourself', 400);
        }

        $existing = $this->userModel->findByIdFull($id);
        if (!$existing) {
            $this->error('User not found', 404);
        }

        $this->userModel->adminDelete($id);

        $this->logger->info('Admin deleted user', ['user_id' => $id, 'login' => $existing['login']]);

        $this->json(['message' => 'User deleted']);
    }

    // PATCH /api/admin/users/{id}/role
    public function changeRole(array $params = []): void
    {
        $admin = $this->requireAdmin();
        $id = (int)($params['id'] ?? 0);
        $body = $this->getBody();

        $role = $body['role'] ?? '';
        if (!in_array($role, ['admin', 'user'])) {
            $this->error('Invalid role', 422);
        }

        if ($id === (int)$admin['id']) {
            $this->error('Cannot change your own role', 400);
        }

        $existing = $this->userModel->findByIdFull($id);
        if (!$existing) {
            $this->error('User not found', 404);
        }

        $this->userModel->updateRole($id, $role);
        $user = $this->userModel->findByIdFull($id);

        $this->logger->info('Admin changed user role', ['user_id' => $id, 'new_role' => $role]);

        $this->json(['message' => 'Role updated', 'user' => $user]);
    }
}
