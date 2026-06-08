-- Migration: Add passed column to user_tests

USE inseptum;

ALTER TABLE user_tests
    ADD COLUMN passed TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 if user reached passing_score threshold'
        AFTER percentage;
