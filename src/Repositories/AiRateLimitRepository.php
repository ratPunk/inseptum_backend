<?php
declare(strict_types=1);

namespace App\Repositories;

/**
 * Лимиты запросов в БД (без Redis).
 * subject = "user:7" или "ip:<hash>".
 * window_key = "minute:YYYYMMDDHHMM", "hour:YYYYMMDDHH", "day:YYYYMMDD".
 */
class AiRateLimitRepository extends AbstractRepository
{
    protected string $table = 'ai_rate_limits';

    /**
     * Атомарно: +1 к счётчику окна. Возвращает текущее значение счётчика после инкремента.
     * Если запись отсутствует — создаётся с expires_at = now + ttlSec.
     */
    public function increment(string $subject, string $windowKey, int $ttlSec): int
    {
        // INSERT or UPDATE
        $sql = 'INSERT INTO `ai_rate_limits` (subject, window_key, counter, expires_at) '
             . 'VALUES (:s, :w, 1, DATE_ADD(NOW(), INTERVAL :ttl SECOND)) '
             . 'ON DUPLICATE KEY UPDATE counter = counter + 1';
        $this->db->execute($sql, ['s' => $subject, 'w' => $windowKey, 'ttl' => $ttlSec]);

        $row = $this->db->fetch(
            'SELECT counter FROM `ai_rate_limits` WHERE subject = :s AND window_key = :w LIMIT 1',
            ['s' => $subject, 'w' => $windowKey]
        );
        return (int)($row['counter'] ?? 1);
    }

    public function current(string $subject, string $windowKey): int
    {
        $row = $this->db->fetch(
            'SELECT counter FROM `ai_rate_limits` WHERE subject = :s AND window_key = :w AND expires_at > NOW() LIMIT 1',
            ['s' => $subject, 'w' => $windowKey]
        );
        return (int)($row['counter'] ?? 0);
    }

    /** Сумма по периоду (для глобального бюджета). */
    public function sumDaily(string $dayKey): int
    {
        $row = $this->db->fetch(
            'SELECT SUM(counter) AS c FROM `ai_rate_limits` WHERE window_key = :w',
            ['w' => $dayKey]
        );
        return (int)($row['c'] ?? 0);
    }

    public function gc(int $limit = 500): int
    {
        return $this->db->execute('DELETE FROM `ai_rate_limits` WHERE expires_at <= NOW() LIMIT ' . (int)$limit);
    }
}
