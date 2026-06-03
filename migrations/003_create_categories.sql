-- Migration: Create categories table
-- Stores article sections/categories like HTML, CSS, JavaScript, etc.

USE inseptum;

CREATE TABLE IF NOT EXISTS categories (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)    NOT NULL,
    slug       VARCHAR(100)    NOT NULL,
    icon       VARCHAR(255)    DEFAULT '',
    sort_order INT UNSIGNED    NOT NULL DEFAULT 0,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default categories
INSERT INTO categories (name, slug, icon, sort_order) VALUES
    ('HTML',      'html',      '',  1),
    ('CSS',       'css',       '',  2),
    ('JavaScript','javascript', '', 3),
    ('TypeScript','typescript', '', 4),
    ('PHP',       'php',       '',  5),
    ('SQL',       'sql',       '',  6),
    ('Linux',     'linux',     '',  7),
    ('Git',       'git',       '',  8),
    ('Docker',    'docker',    '',  9),
    ('Другое',    'other',     '', 10)
ON DUPLICATE KEY UPDATE name = VALUES(name);
