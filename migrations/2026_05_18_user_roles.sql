-- 2026_05_18_user_roles.sql
-- Adds a `role` column to `users`, migrates existing admins from the
-- `admins` table into `users`, then drops the `admins` table.
--
-- Roles:
--   'user'       - default end user
--   'spectator'  - read-only admin
--   'moderator'  - limited admin actions
--   'admin'      - full admin
--
-- Safe to re-run partially: uses IF NOT EXISTS / IF EXISTS where possible.

SET FOREIGN_KEY_CHECKS = 0;

-- 1) Add role column to users (default 'user').
ALTER TABLE `users`
    ADD COLUMN `role` ENUM('user','spectator','moderator','admin')
        NOT NULL DEFAULT 'user' AFTER `password`;

-- 2) Migrate existing admins into users. We try to match by username first;
--    if not found, we insert. Admin password hashes are copied as-is.
INSERT INTO `users` (`username`, `password`, `role`, `created_at`)
SELECT a.`username`, a.`password`, a.`role`, NOW()
FROM `admins` a
LEFT JOIN `users` u ON u.`username` = a.`username`
WHERE u.`id` IS NULL;

-- 2b) If username already existed in users, just promote their role
--     to match the admins table entry.
UPDATE `users` u
JOIN `admins` a ON a.`username` = u.`username`
SET u.`role` = a.`role`;

-- 3) Drop the now-redundant admins table.
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;
