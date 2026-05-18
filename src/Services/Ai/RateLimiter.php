<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Repositories\AiRateLimitRepository;

/**
 * Лимиты по двум субъектам одновременно: user_id и IP.
 * Превышение хотя бы одного → отказ.
 */
final class RateLimiter
{
    private AiRateLimitRepository $repo;
    /** @var array{minute:int,hour:int,day:int} */
    private array $limits;
    private string $ipSalt;

    public function __construct(AiRateLimitRepository $repo, array $cfg)
    {
        $this->repo   = $repo;
        $this->limits = [
            'minute' => (int)($cfg['rate_limits']['minute'] ?? 3),
            'hour'   => (int)($cfg['rate_limits']['hour']   ?? 20),
            'day'    => (int)($cfg['rate_limits']['day']    ?? 50),
        ];
        $this->ipSalt = (string)($cfg['ip_salt'] ?? 'salt');
    }

    public function hashIp(string $ip): string
    {
        return hash('sha256', $ip . '|' . $this->ipSalt);
    }

    /**
     * Проверяет ВСЕ окна по обоим субъектам.
     * Возвращает [ok, retryAfterSec, windowName].
     *
     * NB: вызывается ДО выполнения дорогой операции — но мы уже инкрементируем,
     * чтобы избежать race. Это «pessimistic» подход.
     *
     * @return array{ok:bool, retry_after:int, window:?string}
     */
    public function consume(?int $userId, string $ipHash): array
    {
        $subjects = ['ip:' . $ipHash];
        if ($userId !== null && $userId > 0) {
            $subjects[] = 'user:' . $userId;
        }

        $now = time();
        $windows = [
            ['name' => 'minute', 'key' => 'minute:' . gmdate('YmdHi', $now), 'ttl' => 60,       'limit' => $this->limits['minute']],
            ['name' => 'hour',   'key' => 'hour:'   . gmdate('YmdH',  $now), 'ttl' => 3600,     'limit' => $this->limits['hour']],
            ['name' => 'day',    'key' => 'day:'    . gmdate('Ymd',   $now), 'ttl' => 86400,    'limit' => $this->limits['day']],
        ];

        foreach ($windows as $w) {
            foreach ($subjects as $s) {
                $count = $this->repo->increment($s, $w['key'], $w['ttl']);
                if ($count > $w['limit']) {
                    return [
                        'ok'          => false,
                        'retry_after' => $w['ttl'],
                        'window'      => $w['name'],
                    ];
                }
            }
        }

        return ['ok' => true, 'retry_after' => 0, 'window' => null];
    }
}
