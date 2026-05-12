<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\AuthService;

class AuthController extends AbstractController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function register(Request $r, array $params): JsonResponse
    {
        $username = (string)$r->input('username', '');
        $password = (string)$r->input('password', '');
        $confirm  = (string)$r->input('confirm_password', '');

        $data = $this->auth->register($username, $password, $confirm);

        return $this->success($data, 'User registered', 201);
    }

    public function login(Request $r, array $params): JsonResponse
    {
        $username = (string)$r->input('username', '');
        $password = (string)$r->input('password', '');

        $data = $this->auth->login($username, $password);

        return $this->success($data, 'User logged in', 200);
    }
}
