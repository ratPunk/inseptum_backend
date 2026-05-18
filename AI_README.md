# AI-проверка задач (Claude via claudehub.fun)

Эндпоинт `POST /checktask` делегирует проверку решения реальному Claude через прокси `https://api.claudehub.fun`. Полный архитектурный документ — `../inseptum_frontend/plans/ai-task-checker.md`.

## Установка

1. `cp .env.example .env`, заполнить `CLAUDE_API_KEY=`.
2. Применить миграцию:
   ```bash
   mysql -uroot inseptum < migrations/2026_05_18_ai_task_checker.sql
   ```
3. (опц.) изменить модель/лимиты в `config/ai.php` или через `.env`.

## Контракт `POST /checktask` (новый, обратносовместимый)

```json
{
  "success": true,
  "message": "Решение принято!",
  "details": {
    "verdict": "passed",
    "is_on_topic": true,
    "feedback": "Решение корректно."
  },
  "cached": false,
  "error_code": null,
  "retry_after": null
}
```

`error_code` ∈ `RATE_LIMIT | AI_UNAVAILABLE | INVALID_CODE | OFF_TOPIC | BUDGET_EXCEEDED | ABUSE_BLOCK | null`.

## Узлы

| Файл | Назначение |
|---|---|
| `src/Support/Env.php` | Чтение `.env` без зависимостей. |
| `config/ai.php` | Все настройки AI. |
| `src/Services/Ai/SolutionGuard.php` | Пред-валидация + детект prompt injection. |
| `src/Services/Ai/RateLimiter.php` | Лимиты по `user_id + ip` (мин/час/сутки) в MySQL. |
| `src/Services/Ai/CircuitBreaker.php` | Кран по дневному бюджету USD. |
| `src/Services/Ai/PromptBuilder.php` | System prompt + обёртка `<user_code>`. |
| `src/Services/Ai/ClaudeHubClient.php` | cURL → Anthropic Messages API. |
| `src/Services/Ai/ResponseParser.php` | Извлечение и валидация JSON-ответа. |
| `src/Services/Ai/TaskCheckerService.php` | Оркестратор всего пайплайна. |
| `src/Repositories/Ai{Cache,RateLimit,Audit}Repository.php` | БД. |

## Защита

- **Пустые/мусорные коды** — режутся `SolutionGuard` до AI.
- **Дубли** — мгновенный ответ из `ai_check_cache` (TTL 7 дней).
- **Лимиты** — 3/мин · 20/час · 50/сутки (по `user_id` И по `ip`).
- **Prompt injection** — экранирование `</user_code>`, жёсткий system, abuse-флаг + автоблок после порога.
- **Off-topic** — Claude возвращает `verdict="off_topic"`.
- **Сломанный JSON** — 1 ретрай `temperature=0`, потом fallback (без кэша).
- **Финансовая утечка** — circuit breaker по `daily_budget_usd`.
- **Утечка ключа** — `.env` в `.gitignore`.
