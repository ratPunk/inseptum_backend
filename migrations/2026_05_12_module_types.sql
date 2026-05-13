-- Migration: introduce module_types table and FK from modules.
-- Safe to run multiple times.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ---------------------------------------------------------------------------
-- 1) Table module_types
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `module_types` (
    `id`                 INT(11)      NOT NULL AUTO_INCREMENT,
    `slug`               VARCHAR(64)  NOT NULL,
    `name`               VARCHAR(100) NOT NULL,
    `icon`               VARCHAR(80)  NOT NULL,
    `highlight_language` VARCHAR(40)  DEFAULT NULL,
    `color`              VARCHAR(20)  DEFAULT NULL,
    `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_module_types_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- 2) Seed default module types
-- ---------------------------------------------------------------------------
INSERT INTO `module_types` (`slug`, `name`, `icon`, `highlight_language`, `color`) VALUES
    ('bootstrap', 'Bootstrap',         'FaBootstrap',  'css',        '#7952B3'),
    ('html',      'HTML',              'FaHtml5',      'html',       '#E34F26'),
    ('php',       'PHP',               'FaPhp',        'php',        '#777BB4'),
    ('javascript','JavaScript',        'FaJs',         'javascript', '#F7DF1E'),
    ('database',  'Базы данных',       'FaDatabase',   'sql',        '#00758F'),
    ('structure', 'Структуры данных',  'TbBinaryTree', 'javascript', '#4CAF50')
ON DUPLICATE KEY UPDATE
    `name`               = VALUES(`name`),
    `icon`               = VALUES(`icon`),
    `highlight_language` = VALUES(`highlight_language`),
    `color`              = VALUES(`color`);

-- ---------------------------------------------------------------------------
-- 3) Add module_type_id to modules (nullable for migration). Use a guarded
--    block so re-runs don't fail with "Duplicate column".
-- ---------------------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'modules'
       AND COLUMN_NAME  = 'module_type_id'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `modules` ADD COLUMN `module_type_id` INT(11) NULL DEFAULT NULL AFTER `description`',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Index on module_type_id (guarded).
SET @idx_exists := (
    SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'modules'
       AND INDEX_NAME   = 'idx_modules_module_type_id'
);
SET @sql := IF(@idx_exists = 0,
    'ALTER TABLE `modules` ADD KEY `idx_modules_module_type_id` (`module_type_id`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 4) Backfill module_type_id by matching modules.title -> module_types.slug
-- ---------------------------------------------------------------------------
UPDATE `modules` m
JOIN   `module_types` mt
       ON LOWER(mt.`slug`) = LOWER(m.`title`)
SET    m.`module_type_id` = mt.`id`
WHERE  m.`module_type_id` IS NULL;

-- Aliases for known historical titles.
UPDATE `modules` m
JOIN   `module_types` mt ON mt.`slug` = 'javascript'
SET    m.`module_type_id` = mt.`id`
WHERE  m.`module_type_id` IS NULL
   AND LOWER(m.`title`) IN ('js', 'javascript');

UPDATE `modules` m
JOIN   `module_types` mt ON mt.`slug` = 'database'
SET    m.`module_type_id` = mt.`id`
WHERE  m.`module_type_id` IS NULL
   AND LOWER(m.`title`) IN ('database', 'базы данных', 'db', 'sql');

UPDATE `modules` m
JOIN   `module_types` mt ON mt.`slug` = 'structure'
SET    m.`module_type_id` = mt.`id`
WHERE  m.`module_type_id` IS NULL
   AND LOWER(m.`title`) IN ('structure', 'структуры данных');

-- ---------------------------------------------------------------------------
-- 5) FK (guarded). Use ON DELETE SET NULL: removing a type does not cascade,
--    application layer prevents it when modules are still attached.
-- ---------------------------------------------------------------------------
SET @fk_exists := (
    SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA    = DATABASE()
       AND TABLE_NAME      = 'modules'
       AND CONSTRAINT_NAME = 'fk_modules_module_type'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE `modules` ADD CONSTRAINT `fk_modules_module_type` FOREIGN KEY (`module_type_id`) REFERENCES `module_types`(`id`) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
