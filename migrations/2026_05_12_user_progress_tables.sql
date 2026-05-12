-- =====================================================================
-- Migration: user progress / favorites support tables
-- Date:      2026-05-12
-- Purpose:
--   1) Favorites for tasks  (user_task_favorite)
--   2) Passed tasks         (user_task_passed)
--   3) Ensures referenced  user_test_passed / user_article_favorite /
--      user_test_favorite / user_article_read tables exist (idempotent).
-- =====================================================================

-- ----- Favorites: articles --------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_article_favorite` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `article_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_article` (`user_id`, `article_id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_article_id` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----- Favorites: tests -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_test_favorite` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `test_id`    INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_test` (`user_id`, `test_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----- Favorites: tasks  (NEW) ---------------------------------------------
CREATE TABLE IF NOT EXISTS `user_task_favorite` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `task_id`    INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_task` (`user_id`, `task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----- Passed: tests --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_test_passed` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `test_id`    INT(11) NOT NULL,
  `is_passed`  TINYINT(1) NOT NULL DEFAULT 0,
  `passed_at`  TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_test` (`user_id`, `test_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----- Passed: tasks  (NEW) ------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_task_passed` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `task_id`    INT(11) NOT NULL,
  `is_passed`  TINYINT(1) NOT NULL DEFAULT 0,
  `passed_at`  TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_task` (`user_id`, `task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----- Articles read progress (idempotent guard) ---------------------------
CREATE TABLE IF NOT EXISTS `user_article_read` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11) NOT NULL,
  `article_id`       INT(11) NOT NULL,
  `is_read`          TINYINT(1) NOT NULL DEFAULT 0,
  `read_at`          TIMESTAMP NULL DEFAULT NULL,
  `progress_percent` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_article` (`user_id`, `article_id`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_article_id` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
