-- Миграция: таблицы для AI-проверки задач.
-- Применять один раз. Хранение: utf8mb4, InnoDB.

CREATE TABLE IF NOT EXISTS `ai_check_cache` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cache_key`      CHAR(64) NOT NULL,                  -- sha256(task_id|model|prompt_version|normalized_code)
    `task_id`        INT UNSIGNED NOT NULL,
    `code_hash`      CHAR(64) NOT NULL,                  -- sha256(normalized_code) для дебага
    `code_snapshot`  MEDIUMTEXT NOT NULL,                -- сам код (для ручного аудита, можно очищать)
    `model`          VARCHAR(64) NOT NULL,
    `prompt_version` VARCHAR(16) NOT NULL,
    `response_json`  MEDIUMTEXT NOT NULL,                -- валидированный ответ AI
    `verdict`        ENUM('passed','failed','off_topic','invalid_code') NOT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`     TIMESTAMP NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cache_key` (`cache_key`),
    KEY `idx_task` (`task_id`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_rate_limits` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subject`    VARCHAR(80) NOT NULL,                   -- 'user:7' | 'ip:<hash>'
    `window_key` VARCHAR(32) NOT NULL,                   -- 'minute:202605181254' | 'hour:...' | 'day:...'
    `counter`    INT UNSIGNED NOT NULL DEFAULT 0,
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_subject_window` (`subject`, `window_key`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_audit_log` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NULL,
    `ip_hash`     CHAR(64) NOT NULL,
    `task_id`     INT UNSIGNED NOT NULL,
    `code_hash`   CHAR(64) NOT NULL,
    `cache_hit`   TINYINT(1) NOT NULL DEFAULT 0,
    `verdict`     VARCHAR(16) NULL,
    `is_on_topic` TINYINT(1) NULL,
    `tokens_in`   INT UNSIGNED NULL,
    `tokens_out`  INT UNSIGNED NULL,
    `cost_usd`    DECIMAL(10,6) NULL,
    `latency_ms`  INT UNSIGNED NULL,
    `error_code`  VARCHAR(32) NULL,
    `abuse_flag`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user`  (`user_id`, `created_at`),
    KEY `idx_ip`    (`ip_hash`, `created_at`),
    KEY `idx_task`  (`task_id`),
    KEY `idx_date`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
