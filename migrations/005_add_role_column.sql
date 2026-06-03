-- Migration: Add role column to users table
USE inseptum;
ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER login;

-- Set first user as admin (uncomment to apply)
-- UPDATE users SET role = 'admin' WHERE id = 1;
