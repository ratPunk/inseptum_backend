-- Migration: Extend tests.status ENUM to include 'draft' and 'archived'
-- Also fix time_limit comment (was 'seconds', frontend sends minutes)

USE inseptum;

ALTER TABLE tests
    MODIFY COLUMN status ENUM('active', 'inactive', 'draft', 'archived')
        NOT NULL DEFAULT 'draft';
