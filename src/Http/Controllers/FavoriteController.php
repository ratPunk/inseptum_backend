<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Services\FavoriteService;

class FavoriteController extends AbstractController
{
    private FavoriteService $service;

    public function __construct(FavoriteService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /getfavorite — список избранного пользователя по типу.
     */
    public function index(Request $request, array $params): JsonResponse
    {
        $userId = (int)$request->input('user_id', 0);
        $type   = (string)$request->input('favorite_type', '');
        $result = $this->service->list($type, $userId);
        $message = $type === 'article' ? 'Статьи найдены' : 'tests найдены';
        return $this->success($result['data'], $message, 200, ['count' => $result['count']]);
    }

    /**
     * POST /setfavorite — toggle избранного.
     */
    public function toggle(Request $request, array $params): JsonResponse
    {
        $userId      = (int)$request->input('user_id', 0);
        $favoriteId  = (int)$request->input('favorite_id', 0);
        $type        = (string)$request->input('favorite_type', '');
        $result = $this->service->toggle($type, $userId, $favoriteId);

        if ($type === 'article') {
            $msg = $result['added'] ? 'Статья добавлена в избранное' : 'Статья удалена из избранного';
        } else {
            $msg = $result['added'] ? 'Тест добавлен в избранное' : 'Тест удален из избранного';
        }
        return $this->success(null, $msg, 200);
    }
}
