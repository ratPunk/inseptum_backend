<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\ArticleService;

class ArticleController extends AbstractController
{
    private ArticleService $service;

    public function __construct(ArticleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request, array $params): JsonResponse
    {
        $result = $this->service->listAll();
        return $this->success($result['data'], 'Статьи найдены', 200, ['count' => $result['count']]);
    }

    public function byTopic(Request $request, array $params): JsonResponse
    {
        $topicId = (int)($params['id'] ?? 0);
        if ($topicId <= 0) {
            return $this->error('Не указан ID темы', 400);
        }
        $result = $this->service->listByTopic($topicId);
        return $this->success($result['data'], 'Статьи найдены', 200, ['count' => $result['count']]);
    }

    public function show(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('Статья не найдена', 404);
        }
        $data = $this->service->getOne($id);
        return $this->success($data, 'Статья найдена', 200);
    }

    public function create(Request $request, array $params): JsonResponse
    {
        $data = $request->input();
        $file = $request->file('file');
        $article = $this->service->create($data, $file);
        return $this->success($article, 'Статья успешно создана', 200);
    }

    public function update(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('article_id', 0));
        if ($id <= 0) {
            return $this->error('ID статьи не указан', 400);
        }
        $data = $request->input();
        $file = $request->file('file');
        $article = $this->service->update($id, $data, $file);
        return $this->success($article, 'Статья успешно обновлена', 200);
    }

    public function delete(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? $request->input('article_id', 0));
        if ($id <= 0) {
            return $this->error('Не указан ID статьи', 400);
        }
        $result = $this->service->delete($id);
        return $this->success($result['data'], $result['message'], 200);
    }

    /**
     * GET /articlefile/{id} — отдаёт HTML-конвертацию docx.
     */
    public function file(Request $request, array $params): JsonResponse
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return $this->error('Не указан ID статьи', 400);
        }
        $html = $this->service->getArticleHtml($id);
        return $this->success($html, 'Статья найдена', 200);
    }

    /**
     * GET /readarticle/{id}/{user_id} — получить (или создать) запись о прочтении.
     */
    public function readShow(Request $request, array $params): JsonResponse
    {
        $articleId = (int)($params['id'] ?? 0);
        $userId    = (int)($params['user_id'] ?? 0);
        if ($articleId <= 0 || $userId <= 0) {
            return $this->error('ID статьи и пользователя обязательны', 400);
        }
        $result = $this->service->getReadStatus($articleId, $userId);
        $message = $result['created'] ? 'Новая запись создана' : 'Запись найдена';
        $status  = $result['created'] ? 201 : 200;
        return $this->success($result['data'], $message, $status);
    }

    /**
     * POST /readarticle — отметить статью как прочитанную.
     */
    public function readMark(Request $request, array $params): JsonResponse
    {
        $articleId = (int)$request->input('article_id', 0);
        $userId    = (int)$request->input('user_id', 0);
        if ($articleId <= 0 || $userId <= 0) {
            return $this->error('ID статьи и пользователя обязательны', 400);
        }
        $data = $this->service->markAsRead($articleId, $userId);
        return $this->success($data, 'Запись обновлена', 200);
    }
}
