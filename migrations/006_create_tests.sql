-- Migration: Create tests table
-- Stores test metadata for quick search/filtering; full content in JSON files

USE inseptum;

CREATE TABLE IF NOT EXISTS tests (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title           VARCHAR(255)    NOT NULL,
    description     TEXT            DEFAULT NULL,
    category        VARCHAR(100)    NOT NULL DEFAULT 'general',
    difficulty      ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
    max_score       INT UNSIGNED    NOT NULL DEFAULT 0,
    json_path       VARCHAR(500)    NOT NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    time_limit      INT UNSIGNED    DEFAULT NULL COMMENT 'Time limit in seconds, NULL = no limit',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_tests_category (category),
    KEY idx_tests_status (status),
    KEY idx_tests_difficulty (difficulty)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
