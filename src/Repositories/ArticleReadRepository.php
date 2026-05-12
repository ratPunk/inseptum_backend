<?php
declare(strict_types=1);

namespace App\Repositories;

class ArticleReadRepository extends AbstractRepository
{
    protected string $table = 'user_article_read';

    public function findByUserAndArticle(int $userId, int $articleId): ?array
    {
        return $this->db->fetch(
            'SELECT id, user_id, article_id, is_read, read_at, progress_percent, created_at
             FROM `user_article_read`
             WHERE article_id = :aid AND user_id = :uid
             LIMIT 1',
            ['aid' => $articleId, 'uid' => $userId]
        );
    }

    public function createUnread(int $userId, int $articleId): int
    {
        $this->db->execute(
            'INSERT INTO `user_article_read` (user_id, article_id, is_read, read_at, progress_percent, created_at)
             VALUES (:uid, :aid, 0, NULL, 0, NOW())',
            ['uid' => $userId, 'aid' => $articleId]
        );
        return (int)$this->db->lastInsertId();
    }

    /**
     * Upsert: если строка есть — обновляет как прочитанную; иначе создаёт прочитанной.
     */
    public function markRead(int $userId, int $articleId): void
    {
        try {
            $this->db->execute(
                'INSERT INTO `user_article_read` (user_id, article_id, is_read, read_at, progress_percent, created_at)
                 VALUES (:uid, :aid, 1, NOW(), 100, NOW())
                 ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW(), progress_percent = 100',
                ['uid' => $userId, 'aid' => $articleId]
            );
            return;
        } catch (\Throwable $e) {
            // fallback ниже
        }

        $affected = $this->db->execute(
            'UPDATE `user_article_read`
             SET is_read = 1, read_at = NOW(), progress_percent = 100
             WHERE article_id = :aid AND user_id = :uid',
            ['aid' => $articleId, 'uid' => $userId]
        );

        if ($affected === 0) {
            $this->db->execute(
                'INSERT INTO `user_article_read` (user_id, article_id, is_read, read_at, progress_percent, created_at)
                 VALUES (:uid, :aid, 1, NOW(), 100, NOW())',
                ['uid' => $userId, 'aid' => $articleId]
            );
        }
    }

    public static function format(array $row): array
    {
        return [
            'id'               => (int)$row['id'],
            'user_id'          => (int)$row['user_id'],
            'article_id'       => (int)$row['article_id'],
            'is_read'          => (bool)((int)$row['is_read']),
            'read_at'          => $row['read_at'],
            'progress_percent' => (int)$row['progress_percent'],
            'created_at'       => $row['created_at'],
        ];
    }
}
