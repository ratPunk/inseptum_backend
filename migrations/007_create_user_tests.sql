-- Migration: Create user_tests table
-- Tracks user progress and results for each test attempt

USE inseptum;

CREATE TABLE IF NOT EXISTS user_tests (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED    NOT NULL,
    test_id         INT UNSIGNED    NOT NULL,
    status          ENUM('not_started', 'in_progress', 'completed') NOT NULL DEFAULT 'not_started',
    score           INT UNSIGNED    NOT NULL DEFAULT 0,
    max_score       INT UNSIGNED    NOT NULL DEFAULT 0,
    percentage      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    started_at      DATETIME        DEFAULT NULL,
    completed_at    DATETIME        DEFAULT NULL,
    attempt         INT UNSIGNED    NOT NULL DEFAULT 1,
    answers_json    JSON            DEFAULT NULL COMMENT 'User answers for this attempt',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_user_tests_user (user_id),
    KEY idx_user_tests_test (test_id),
    KEY idx_user_tests_status (status),
    UNIQUE KEY uk_user_test_attempt (user_id, test_id, attempt),
    CONSTRAINT fk_user_tests_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_user_tests_test FOREIGN KEY (test_id) REFERENCES tests (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;