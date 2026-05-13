<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\ModuleTypeService;

class ModuleTypeController extends AbstractController
{
    private ModuleTypeService $service;

    public function __construct(ModuleTypeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success(
            $result['data'],
            'Типы модулей найдены',
            200,
            ['count' => $result['count']]
        );
    }

    public function show(Request $request, array $params): JsonResponse
    {
        $identifier = (string)($params['id'] ?? '');
        $type = $this->service->getByIdentifier($identifier);
        return $this->success($type, 'Тип модуля найден', 200);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $payload = $this->extractFormData($request);
        $type    = $this->service->create($payload);
        return $this->success($type, 'Тип модуля успешно создан', 200);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID типа модуля', 400);
        }
        $payload = $this->extractFormData($request);
        $type    = $this->service->update($id, $payload);
        return $this->success($type, 'Тип модуля успешно обновлён', 200);
    }

    public function delete(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID типа модуля', 400);
        }
        $data = $this->service->delete($id);
        return $this->success($data, 'Тип модуля успешно удалён', 200);
    }

    private function extractFormData(Request $request): array
    {
        $formData = $request->input('form_data');
        if (is_array($formData) && !empty($formData)) {
            return $formData;
        }
        return $request->input() ?: [];
    }
}
