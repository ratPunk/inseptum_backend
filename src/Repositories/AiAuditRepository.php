<?php
declare(strict_types=1);

namespace App\Repositories;

class AiAuditRepository extends AbstractRepository
{
    protected string $table = 'ai_audit_log';

    public function log(array $row): void
    {
        $sql = 'INSERT INTO `ai_audit_log` '
             . '(user_id, ip_hash, task_id, code_hash, cache_hit, verdict, is_on_topic, '
             . ' tokens_in, tokens_out, cost_usd, latency_ms, error_code, abuse_flag) '
             . 'VALUES (:uid, :ip, :tid, :ch, :hit, :v, :on, :ti, :to, :cost, :lat, :ec, :ab)';
        $this->db->execute($sql, [
            'uid'  => $row['user_id']     ?? null,
            'ip'   => $row['ip_hash']     ?? '',
            'tid'  => (int)($row['task_id'] ?? 0),
            'ch'   => $row['code_hash']   ?? '',
            'hit'  => !empty($row['cache_hit']) ? 1 : 0,
            'v'    => $row['verdict']     ?? null,
            'on'   => isset($row['is_on_topic']) ? (int)(bool)$row['is_on_topic'] : null,
            'ti'   => $row['tokens_in']   ?? null,
            'to'   => $row['tokens_out']  ?? null,
            'cost' => $row['cost_usd']    ?? null,
            'lat'  => $row['latency_ms']  ?? null,
            'ec'   => $row['error_code']  ?? null,
            'ab'   => !empty($row['abuse_flag']) ? 1 : 0,
        ]);
    }

    /** Сумма стоимости за день — для circuit breaker. */
    public function dailyCostUsd(string $date): float
    {
        $row = $this->db->fetch(
            'SELECT COALESCE(SUM(cost_usd), 0) AS s FROM `ai_audit_log` WHERE DATE(created_at) = :d',
            ['d' => $date]
        );
        return (float)($row['s'] ?? 0.0);
    }

    /** Кол-во abuse-флагов от subject за последние N минут — для авто-блокировки. */
    public function recentAbuseCount(?int $userId, string $ipHash, int $minutes): int
    {
        if ($userId !== null && $userId > 0) {
            $row = $this->db->fetch(
                'SELECT COUNT(*) AS c FROM `ai_audit_log` WHERE user_id = :u AND abuse_flag = 1 AND created_at > DATE_SUB(NOW(), INTERVAL :m MINUTE)',
                ['u' => $userId, 'm' => $minutes]
            );
        } else {
            $row = $this->db->fetch(
                'SELECT COUNT(*) AS c FROM `ai_audit_log` WHERE ip_hash = :ip AND abuse_flag = 1 AND created_at > DATE_SUB(NOW(), INTERVAL :m MINUTE)',
                ['ip' => $ipHash, 'm' => $minutes]
            );
        }
        return (int)($row['c'] ?? 0);
    }
}
