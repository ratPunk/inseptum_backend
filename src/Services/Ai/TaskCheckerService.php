<?php
declare(strict_types=1);

namespace App\Services\Ai;

use App\Core\Logger;
use App\Repositories\AiAuditRepository;
use App\Repositories\AiCacheRepository;

/**
 * Оркестратор AI-проверки. Сшивает: guard → audit-abuse → rate-limit → cache →
 * circuit-breaker → Claude → parser → cache-put → audit-log.
 *
 * Результат — фиксированная структура для контроллера:
 *   ['success'=>bool,'message'=>string,'details'=>['verdict'=>?,'is_on_topic'=>?,'feedback'=>?],
 *    'cached'=>bool,'error_code'=>?string,'retry_after'=>?int]
 *
 * success=true означает только verdict='passed'. Всё остальное (failed/off_topic/invalid_code) — success=false.
 */
final class TaskCheckerService
{
    /** @var array<string,mixed> */
    private array $cfg;
    private SolutionGuard $guard;
    private PromptBuilder $prompt;
    private ClaudeHubClient $client;
    private ResponseParser $parser;
    private RateLimiter $limiter;
    private CircuitBreaker $breaker;
    private AiCacheRepository $cache;
    private AiAuditRepository $audit;
    private Logger $logger;

    public function __construct(
        array $cfg,
        SolutionGuard $guard,
        PromptBuilder $prompt,
        ClaudeHubClient $client,
        ResponseParser $parser,
        RateLimiter $limiter,
        CircuitBreaker $breaker,
        AiCacheRepository $cache,
        AiAuditRepository $audit,
        Logger $logger
    ) {
        $this->cfg     = $cfg;
        $this->guard   = $guard;
        $this->prompt  = $prompt;
        $this->client  = $client;
        $this->parser  = $parser;
        $this->limiter = $limiter;
        $this->breaker = $breaker;
        $this->cache   = $cache;
        $this->audit   = $audit;
        $this->logger  = $logger;
    }

    /**
     * @param array{id:int,title:string,description?:?string,language?:?string} $task
     */
    public function check(array $task, string $userCode, ?int $userId, string $clientIp): array
    {
        $ipHash = $this->limiter->hashIp($clientIp);

        // 1) Гард + нормализация
        $g = $this->guard->check($userCode);
        $codeHash = hash('sha256', $g['normalized']);

        if (!$g['ok']) {
            $this->audit->log([
                'user_id'    => $userId, 'ip_hash' => $ipHash, 'task_id' => (int)$task['id'],
                'code_hash'  => $codeHash, 'cache_hit' => false, 'verdict' => null,
                'error_code' => $g['reason'], 'abuse_flag' => $g['abuse'],
            ]);
            return $this->localFailure($g['reason']);
        }

        // 2) Авто-блокировка после серии abuse-флагов
        $abuseCount = $this->audit->recentAbuseCount(
            $userId,
            $ipHash,
            (int)($this->cfg['abuse_block_minutes'] ?? 15)
        );
        if ($g['abuse'] && $abuseCount >= (int)($this->cfg['abuse_threshold'] ?? 3)) {
            $this->audit->log([
                'user_id'    => $userId, 'ip_hash' => $ipHash, 'task_id' => (int)$task['id'],
                'code_hash'  => $codeHash, 'cache_hit' => false,
                'error_code' => 'ABUSE_BLOCK', 'abuse_flag' => true,
            ]);
            return [
                'success'    => false,
                'message'    => 'Слишком много некорректных попыток. Попробуйте позже.',
                'details'    => null,
                'cached'     => false,
                'error_code' => 'ABUSE_BLOCK',
                'retry_after'=> (int)($this->cfg['abuse_block_minutes'] ?? 15) * 60,
            ];
        }

        // 3) Rate limit — ВСЕГДА (даже для нормальных запросов)
        $rl = $this->limiter->consume($userId, $ipHash);
        if (!$rl['ok']) {
            $this->audit->log([
                'user_id'    => $userId, 'ip_hash' => $ipHash, 'task_id' => (int)$task['id'],
                'code_hash'  => $codeHash, 'cache_hit' => false,
                'error_code' => 'RATE_LIMIT', 'abuse_flag' => $g['abuse'],
            ]);
            return [
                'success'    => false,
                'message'    => 'Слишком много запросов. Подождите и попробуйте снова.',
                'details'    => null,
                'cached'     => false,
                'error_code' => 'RATE_LIMIT',
                'retry_after'=> $rl['retry_after'],
            ];
        }

        // 4) Cache lookup
        $cacheKey = $this->cacheKey((int)$task['id'], $g['normalized']);
        $cached = $this->cache->findFresh($cacheKey);
        if ($cached !== null) {
            $parsed = json_decode((string)$cached['response_json'], true);
            if (is_array($parsed) && isset($parsed['verdict'])) {
                $this->audit->log([
                    'user_id'  => $userId, 'ip_hash' => $ipHash, 'task_id' => (int)$task['id'],
                    'code_hash'=> $codeHash, 'cache_hit' => true,
                    'verdict'  => $parsed['verdict'], 'is_on_topic' => $parsed['is_on_topic'] ?? null,
                    'abuse_flag' => $g['abuse'],
                ]);
                return $this->shape($parsed, true, null, null);
            }
        }

        // 5) Circuit breaker (дневной бюджет)
        if ($this->breaker->isOpen()) {
            $this->audit->log([
                'user_id'    => $userId, 'ip_hash' => $ipHash, 'task_id' => (int)$task['id'],
                'code_hash'  => $codeHash, 'cache_hit' => false,
                'error_code' => 'BUDGET_EXCEEDED', 'abuse_flag' => $g['abuse'],
            ]);
            return [
                'success'    => false,
                'message'    => 'Сервис проверки временно перегружен. Попробуйте позже.',
                'details'    => null,
                'cached'     => false,
                'error_code' => 'BUDGET_EXCEEDED',
                'retry_after'=> 3600,
            ];
        }

        // 6) Вызов Claude
        $escaped = SolutionGuard::escapeForPrompt($g['normalized']);
        $userMsg = $this->prompt->buildUserMessage($task, (string)($task['language'] ?? ''), $escaped);

        $resp = $this->client->complete(PromptBuilder::SYSTEM_PROMPT, $userMsg);
        $parsed = $resp['ok'] ? $this->parser->parse($resp['text']) : ['ok' => false, 'error' => $resp['error']];

        // 7) Один ретрай с temperature=0, если ответ невалиден
        if (!$parsed['ok'] && $resp['ok']) {
            $resp2 = $this->client->complete(PromptBuilder::SYSTEM_PROMPT, $userMsg, 0.0);
            if ($resp2['ok']) {
                $resp = $resp2;
                $parsed = $this->parser->parse($resp2['text']);
            }
        }

        $cost = CircuitBreaker::estimateCost($resp['usage']['in'] ?? 0, $resp['usage']['out'] ?? 0, $this->cfg);

        if (!$resp['ok'] || !$parsed['ok']) {
            $this->audit->log([
                'user_id'    => $userId, 'ip_hash' => $ipHash, 'task_id' => (int)$task['id'],
                'code_hash'  => $codeHash, 'cache_hit' => false,
                'tokens_in'  => $resp['usage']['in']  ?? 0,
                'tokens_out' => $resp['usage']['out'] ?? 0,
                'cost_usd'   => $cost,
                'latency_ms' => $resp['latency_ms']   ?? 0,
                'error_code' => 'AI_UNAVAILABLE',
                'abuse_flag' => $g['abuse'],
            ]);
            $this->logger->error('AI check failed', [
                'ai_error' => $resp['error'] ?? null,
                'parse'    => $parsed['error'] ?? null,
            ]);
            return [
                'success'    => false,
                'message'    => 'Сервис проверки временно недоступен. Попробуйте ещё раз позже.',
                'details'    => null,
                'cached'     => false,
                'error_code' => 'AI_UNAVAILABLE',
                'retry_after'=> 30,
            ];
        }

        // 8) Записываем в кэш
        $this->cache->put(
            $cacheKey,
            (int)$task['id'],
            $codeHash,
            $g['normalized'],
            (string)$this->cfg['model'],
            (string)$this->cfg['prompt_version'],
            json_encode([
                'verdict'     => $parsed['verdict'],
                'is_on_topic' => $parsed['is_on_topic'],
                'feedback'    => $parsed['feedback'],
            ], JSON_UNESCAPED_UNICODE),
            $parsed['verdict'],
            (int)$this->cfg['cache_ttl_days']
        );

        $this->audit->log([
            'user_id'     => $userId,
            'ip_hash'     => $ipHash,
            'task_id'     => (int)$task['id'],
            'code_hash'   => $codeHash,
            'cache_hit'   => false,
            'verdict'     => $parsed['verdict'],
            'is_on_topic' => $parsed['is_on_topic'],
            'tokens_in'   => $resp['usage']['in']  ?? 0,
            'tokens_out'  => $resp['usage']['out'] ?? 0,
            'cost_usd'    => $cost,
            'latency_ms'  => $resp['latency_ms']   ?? 0,
            'abuse_flag'  => $g['abuse'],
        ]);

        return $this->shape($parsed, false, null, null);
    }

    private function cacheKey(int $taskId, string $normalized): string
    {
        return hash('sha256', $taskId . '|' . $this->cfg['model'] . '|' . $this->cfg['prompt_version'] . '|' . $normalized);
    }

    /** Локальные отказы (guard) — не вызываем AI. */
    private function localFailure(?string $reason): array
    {
        $map = [
            'TOO_SHORT'     => 'Код слишком короткий для проверки.',
            'TOO_LONG'      => 'Код слишком длинный.',
            'ONLY_COMMENTS' => 'Напишите само решение, а не только комментарии.',
        ];
        return [
            'success'    => false,
            'message'    => $map[$reason] ?? 'Код не прошёл предварительную проверку.',
            'details'    => null,
            'cached'     => false,
            'error_code' => 'INVALID_CODE',
            'retry_after'=> 0,
        ];
    }

    /** Приведение распарсенного ответа к публичному контракту. */
    private function shape(array $parsed, bool $cached, ?string $errorCode, ?int $retryAfter): array
    {
        $verdict  = $parsed['verdict'] ?? 'failed';
        $passed   = $verdict === 'passed';
        $messages = [
            'passed'       => 'Решение принято!',
            'failed'       => 'Решение не проходит проверку.',
            'off_topic'    => 'Это поле для решения задачи, а не свободный чат.',
            'invalid_code' => 'Код содержит синтаксические ошибки.',
        ];
        return [
            'success'    => $passed,
            'message'    => $messages[$verdict] ?? 'Проверка завершена.',
            'details'    => [
                'verdict'     => $verdict,
                'is_on_topic' => (bool)($parsed['is_on_topic'] ?? false),
                'feedback'    => (string)($parsed['feedback'] ?? ''),
            ],
            'cached'     => $cached,
            'error_code' => $errorCode ?: ($passed ? null : ($verdict === 'off_topic' ? 'OFF_TOPIC' : null)),
            'retry_after'=> $retryAfter,
        ];
    }
}
