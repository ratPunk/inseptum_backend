<?php
declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Пред-валидация кода ДО вызова AI.
 * Цель: не тратить токены на пустоту, мусор и явный prompt-injection.
 *
 * Возвращает массив:
 *   ['ok' => bool, 'reason' => string|null, 'abuse' => bool, 'normalized' => string]
 */
final class SolutionGuard
{
    /** @var array<string,mixed> */
    private array $cfg;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * Анти-инжект сигнатуры (триггер abuse-флага, но НЕ автоматический бан —
     * блокировка после превышения порога считается в TaskService через AuditRepository).
     */
    private const ABUSE_PATTERNS = [
        '/ignore (the )?(previous|above|all) instructions?/i',
        '/disregard (the )?(previous|above|all) instructions?/i',
        '/you are (now )?(a|an) /i',
        '/system\s*:/i',
        '/assistant\s*:/i',
        '/<\/?user_code>/i',
        '/```\s*user/i',
        '/forget (everything|all)/i',
        '/(act|pretend) as (a|an)?/i',
        '/reveal (the |your )?(system )?prompt/i',
        '/расскажи|напиши стих|анекдот|погод[аы]/iu', // явные «не по теме»
    ];

    public function check(string $code): array
    {
        $normalized = self::normalize($code);
        $len = mb_strlen($normalized);

        if ($len < (int)($this->cfg['min_code_chars'] ?? 15)) {
            return ['ok' => false, 'reason' => 'TOO_SHORT', 'abuse' => false, 'normalized' => $normalized];
        }
        if ($len > (int)($this->cfg['max_code_chars'] ?? 5000)) {
            return ['ok' => false, 'reason' => 'TOO_LONG', 'abuse' => false, 'normalized' => $normalized];
        }

        // Только комментарии?
        $stripped = trim(preg_replace('#/\*[\s\S]*?\*/|//[^\r\n]*|\#[^\r\n]*#', '', $normalized));
        if ($stripped === '' || mb_strlen($stripped) < 8) {
            return ['ok' => false, 'reason' => 'ONLY_COMMENTS', 'abuse' => false, 'normalized' => $normalized];
        }

        // Анти-инжект: НЕ блокируем, но помечаем abuse=true.
        $abuse = false;
        foreach (self::ABUSE_PATTERNS as $rx) {
            if (preg_match($rx, $normalized)) {
                $abuse = true;
                break;
            }
        }

        return ['ok' => true, 'reason' => null, 'abuse' => $abuse, 'normalized' => $normalized];
    }

    /**
     * Канонизация для cache-ключа: убираем CRLF, обрезаем хвостовые пробелы строк,
     * схлопываем пустые строки. Регистр НЕ трогаем (важно для PHP/JS).
     */
    public static function normalize(string $code): string
    {
        $code = str_replace(["\r\n", "\r"], "\n", $code);
        $lines = array_map('rtrim', explode("\n", $code));
        // схлопываем 3+ пустых подряд → 1
        $out = [];
        $emptyRun = 0;
        foreach ($lines as $l) {
            if ($l === '') {
                if (++$emptyRun > 1) continue;
            } else {
                $emptyRun = 0;
            }
            $out[] = $l;
        }
        return trim(implode("\n", $out));
    }

    /**
     * Экранирование закрывающего тега `</user_code>` внутри пользовательского ввода,
     * чтобы юзер не смог "выйти" из своего блока в промпте.
     */
    public static function escapeForPrompt(string $code): string
    {
        // Вставляем zero-width-space между `<` и `/user_code>` — Claude не парсит как тег,
        // но в выводе ничего не ломается визуально.
        return preg_replace('#</\s*user_code\s*>#i', '<\u200B/user_code>', $code) ?? $code;
    }
}
