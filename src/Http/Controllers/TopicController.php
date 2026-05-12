<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\TopicService;

class TopicController extends AbstractController
{
    private TopicService $service;

    public function __construct(TopicService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success($result['data'], 'Темы найдены', 200, ['count' => $result['count']]);
    }

    public function byModule(Request $request, array $params): JsonResponse
    {
        $identifier = (string)($params['id'] ?? '');
        $result = $this->service->listByModule($identifier);
        return $this->success($result['data'], 'Темы найдены', 200, ['count' => $result['count']]);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $payload = $this->extractFormData($request);
        $data = $this->service->create($payload);
        return $this->success($data, 'Тема успешно создана', 200);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('topic_id', 0));
        if ($id <= 0) {
            return $this->error('Неопределённая тема', 400);
        }
        $payload = $this->extractFormData($request);
        $data = $this->service->update($id, $payload);
        return $this->success($data, 'Тема успешно обновлена', 200);
    }

    public function delete(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('topic_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID темы', 400);
        }
        $data = $this->service->delete($id);
        return $this->success($data, 'Тема успешно удалена', 200);
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
