<?php
declare(strict_types=1);

use App\Support\Env;

/**
 * Конфигурация AI-проверки задач.
 * Ключ и url берутся из .env (см. .env.example).
 *
 * Жёсткие лимиты применяются ко ВСЕМ запросам (даже к нормальным).
 * Это защита бюджета, а не наказание пользователя.
 */
return [
    'provider'        => 'claudehub',
    'base_url'        => rtrim((string)Env::get('CLAUDE_BASE_URL', 'https://api.claudehub.fun'), '/'),
    'api_key'         => (string)Env::get('CLAUDE_API_KEY', ''),
    'model'           => (string)Env::get('CLAUDE_MODEL', 'claude-haiku-4-5'),
    'anthropic_version' => '2023-06-01',

    'max_tokens'      => 500,
    'temperature'     => 0.2,
    'timeout_sec'     => 25,
    'retry_count'     => 1,                                  // 1 ретрай при невалидном JSON
    'prompt_version'  => (string)Env::get('AI_PROMPT_VERSION', 'v1'),

    // Кэш
    'cache_ttl_days'  => 7,

    // Rate limits — окна и максимумы
    'rate_limits'     => [
        'minute' => 3,
        'hour'   => 20,
        'day'    => 50,
    ],

    // Глобальный бюджет (USD) на сутки. Сверх — circuit breaker.
    'daily_budget_usd' => Env::getFloat('AI_DAILY_BUDGET_USD', 2.00),
    // Грубая оценка стоимости (haiku-4.5; обновите при смене модели).
    'cost_per_1k_in'   => 0.001,
    'cost_per_1k_out'  => 0.005,

    // Анти-абуз
    'min_code_chars'      => 15,
    'max_code_chars'      => 5000,
    'abuse_block_minutes' => 15,
    'abuse_threshold'     => 3,

    // Соль для хеширования IP в audit log.
    'ip_salt'             => (string)Env::get('AI_IP_SALT', 'change_me_please'),
];
