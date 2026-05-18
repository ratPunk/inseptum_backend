<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Repositories\AiAuditRepository;

/**
 * Глобальный «кран» по дневному бюджету.
 * Если суммарная стоимость за сегодня >= лимита — отказываем (fallback).
 */
final class CircuitBreaker
{
    private AiAuditRepository $audit;
    private float $dailyBudgetUsd;

    public function __construct(AiAuditRepository $audit, array $cfg)
    {
        $this->audit          = $audit;
        $this->dailyBudgetUsd = (float)($cfg['daily_budget_usd'] ?? 0);
    }

    public function isOpen(): bool
    {
        if ($this->dailyBudgetUsd <= 0) {
            return false; // лимит не задан — не блокируем
        }
        $today = gmdate('Y-m-d');
        return $this->audit->dailyCostUsd($today) >= $this->dailyBudgetUsd;
    }

    public static function estimateCost(int $tokensIn, int $tokensOut, array $cfg): float
    {
        $in  = (float)($cfg['cost_per_1k_in']  ?? 0);
        $out = (float)($cfg['cost_per_1k_out'] ?? 0);
        return round(($tokensIn / 1000.0) * $in + ($tokensOut / 1000.0) * $out, 6);
    }
}
