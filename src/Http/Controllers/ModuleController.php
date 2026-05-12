<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\ModuleService;

class ModuleController extends AbstractController
{
    private ModuleService $service;

    public function __construct(ModuleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success(
            $result['data'],
            'Модули найдены',
            200,
            ['count' => $result['count']]
        );
    }

    public function show(Request $request, array $params): JsonResponse
    {
        $identifier = (string)($params['id'] ?? '');
        $module = $this->service->getByIdentifier($identifier);
        return $this->success($module, 'Модуль найден', 200);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $payload = $this->extractFormData($request);
        $module  = $this->service->create($payload);
        return $this->success($module, 'Модуль успешно создан', 200);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('module_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID модуля', 400);
        }
        $payload = $this->extractFormData($request);
        $module  = $this->service->update($id, $payload);
        return $this->success($module, 'Модуль успешно обновлен', 200);
    }

    public function delete(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('module_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID модуля', 400);
        }
        $data = $this->service->delete($id);
        return $this->success($data, 'Модуль успешно удален', 200);
    }

    /**
     * Accepts either:
     *   - { "form_data": { title, description, ... } }  (legacy front)
     *   - flat body: { title, description, ... }
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
