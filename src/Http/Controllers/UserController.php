<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\UserService;

class UserController extends AbstractController
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success(
            $result['data'],
            'Пользователи найдены',
            200,
            ['count' => $result['count']]
        );
    }

    public function show(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('Не указан ID пользователя', 400);
        }
        $user = $this->service->getOne($id);
        return $this->success($user, 'Пользователь найден', 200);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $payload = $this->extractFormData($request);
        $user    = $this->service->create($payload);
        return $this->success($user, 'Пользователь успешно создан', 200);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $payload = $this->extractFormData($request);
        $id = (int)($params['id']
            ?? $payload['id']
            ?? $payload['user_id']
            ?? $request->input('id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID пользователя', 400);
        }
        $user = $this->service->update($id, $payload);
        return $this->success($user, 'Пользователь успешно обновлен', 200);
    }

    public function delete(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id']
            ?? $request->input('id', 0)
            ?? $request->input('user_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID пользователя', 400);
        }
        $data = $this->service->delete($id);
        return $this->success($data, 'Пользователь успешно удален', 200);
    }

    /**
     * Accepts either:
     *   - { "form_data": { ... } }  (legacy front)
     *   - flat body: { ... }
     */
    private function extractFormData(Request $request): array
    {
        $formData = $request->input('form_data');
        if (is_array($formData) && !empty($formData)) {
            return $formData;
        }
        return $request->input() ?: [];
    }
}
