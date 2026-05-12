<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\ValidationException;

class FavoriteRepository extends AbstractRepository
{
    private const TABLES = [
        'article' => ['table' => 'user_article_favorite', 'column' => 'article_id'],
        'test'    => ['table' => 'user_test_favorite',    'column' => 'test_id'],
    ];

    private function meta(string $type): array
    {
        if (!isset(self::TABLES[$type])) {
            throw new ValidationException('Неверное значение favorite_type');
        }
        return self::TABLES[$type];
    }

    public function listByUser(string $type, int $userId): array
    {
        $meta = $this->meta($type);
        return $this->db->fetchAll(
            "SELECT * FROM `{$meta['table']}` WHERE `user_id` = :uid",
            ['uid' => $userId]
        );
    }

    public function exists(string $type, int $userId, int $favoriteId): bool
    {
        $meta = $this->meta($type);
        $row = $this->db->fetch(
            "SELECT 1 FROM `{$meta['table']}` WHERE `user_id` = :uid AND `{$meta['column']}` = :fid LIMIT 1",
            ['uid' => $userId, 'fid' => $favoriteId]
        );
        return $row !== null;
    }

    public function add(string $type, int $userId, int $favoriteId): void
    {
        $meta = $this->meta($type);
        $this->db->execute(
            "INSERT INTO `{$meta['table']}` (`user_id`, `{$meta['column']}`) VALUES (:uid, :fid)",
            ['uid' => $userId, 'fid' => $favoriteId]
        );
    }

    public function remove(string $type, int $userId, int $favoriteId): void
    {
        $meta = $this->meta($type);
        $this->db->execute(
            "DELETE FROM `{$meta['table']}` WHERE `user_id` = :uid AND `{$meta['column']}` = :fid",
            ['uid' => $userId, 'fid' => $favoriteId]
        );
    }
}
