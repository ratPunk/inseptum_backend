-- Migration: Create articles table
-- Stores article metadata; DOCX files are stored in /storage/articles/

USE inseptum;

CREATE TABLE IF NOT EXISTS articles (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255)    NOT NULL,
    description TEXT            DEFAULT NULL,
    filename    VARCHAR(255)    NOT NULL,
    category_id INT UNSIGNED    NOT NULL,
    author_id   INT UNSIGNED    DEFAULT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_articles_category (category_id),
    KEY idx_articles_author   (author_id),
    CONSTRAINT fk_articles_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_articles_author   FOREIGN KEY (author_id)   REFERENCES users (id)        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
