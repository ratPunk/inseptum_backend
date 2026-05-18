<?php
declare(strict_types=1);

namespace App\Repositories;

class AiCacheRepository extends AbstractRepository
{
    protected string $table = 'ai_check_cache';

    public function findFresh(string $cacheKey): ?array
    {
        return $this->db->fetch(
            'SELECT id, response_json, verdict, created_at FROM `ai_check_cache` WHERE cache_key = :k AND expires_at > NOW() LIMIT 1',
            ['k' => $cacheKey]
        );
    }

    public function put(
        string $cacheKey,
        int $taskId,
        string $codeHash,
        string $codeSnapshot,
        string $model,
        string $promptVersion,
        string $responseJson,
        string $verdict,
        int $ttlDays
    ): void {
        $sql = 'INSERT INTO `ai_check_cache` '
             . '(cache_key, task_id, code_hash, code_snapshot, model, prompt_version, response_json, verdict, expires_at) '
             . 'VALUES (:k, :tid, :ch, :cs, :m, :pv, :rj, :v, DATE_ADD(NOW(), INTERVAL :ttl DAY)) '
             . 'ON DUPLICATE KEY UPDATE response_json = VALUES(response_json), verdict = VALUES(verdict), expires_at = VALUES(expires_at)';
        $this->db->execute($sql, [
            'k'   => $cacheKey,
            'tid' => $taskId,
            'ch'  => $codeHash,
            'cs'  => $codeSnapshot,
            'm'   => $model,
            'pv'  => $promptVersion,
            'rj'  => $responseJson,
            'v'   => $verdict,
            'ttl' => $ttlDays,
        ]);
    }

    public function gc(int $limit = 500): int
    {
        return $this->db->execute('DELETE FROM `ai_check_cache` WHERE expires_at <= NOW() LIMIT ' . (int)$limit);
    }
}
