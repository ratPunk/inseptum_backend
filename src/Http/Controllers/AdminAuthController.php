<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\AuthService;

class AdminAuthController extends AbstractController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function login(Request $r, array $params): JsonResponse
    {
        $username = (string)$r->input('username', '');
        $password = (string)$r->input('password', '');

        $data = $this->auth->adminLogin($username, $password);

        return $this->success($data, 'admin logged in', 200);
    }
}
