-- Migration: Add role column to users table
-- Run this to enable admin/user role support

USE inseptum;

-- Add role column with default 'user'
ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER password;

-- Optionally set your admin account (replace 1 with the actual admin user id)
-- UPDATE users SET role = 'admin' WHERE id = 1;
