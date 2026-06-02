-- Migration: Add login column to existing users table
-- Run this if you already have a users table with email

USE inseptum;

-- Add login column after name
ALTER TABLE users ADD COLUMN login VARCHAR(50) NOT NULL AFTER name;

-- Drop the email unique constraint (optional, keep if you still use email)
-- ALTER TABLE users DROP INDEX uq_users_email;

-- Add unique constraint for login
ALTER TABLE users ADD UNIQUE INDEX uq_users_login (login);