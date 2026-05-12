<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\FavoriteRepository;

class FavoriteService
{
    private FavoriteRepository $repo;

    public function __construct(FavoriteRepository $repo)
    {
        $this->repo = $repo;
    }

    public function list(string $type, int $userId): array
    {
        $rows = $this->repo->listByUser($type, $userId);
        if (empty($rows)) {
            if ($type === 'article') {
                $entity = 'Статьи';
            } elseif ($type === 'task') {
                $entity = 'Задачи';
            } else {
                $entity = 'Тесты';
            }
            throw new NotFoundException($entity . ' не найдены');
        }
        return ['data' => $rows, 'count' => count($rows)];
    }

    /**
     * Toggle: если есть — убираем, иначе — добавляем.
     *
     * @return array{added: bool}
     */
    public function toggle(string $type, int $userId, int $favoriteId): array
    {
        if ($userId <= 0 || $favoriteId <= 0) {
            throw new ValidationException('Неверные user_id/favorite_id');
        }
        if ($this->repo->exists($type, $userId, $favoriteId)) {
            $this->repo->remove($type, $userId, $favoriteId);
            return ['added' => false];
        }
        $this->repo->add($type, $userId, $favoriteId);
        return ['added' => true];
    }
}
