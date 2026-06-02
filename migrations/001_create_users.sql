-- Run this once to set up the database schema.

CREATE DATABASE IF NOT EXISTS inseptum
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE inseptum;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100)    NOT NULL,
    email      VARCHAR(255)    NOT NULL,
    password   VARCHAR(255)    NOT NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
