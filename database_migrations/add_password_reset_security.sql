-- Database Migration: Password Security Implementation
-- Date: October 2025
-- Purpose: Add password reset column and mark existing users for mandatory password reset

-- Step 1: Add password_reset_required column
ALTER TABLE `users`
ADD COLUMN `password_reset_required` TINYINT(1) DEFAULT 0 AFTER `password`;

-- Step 2: Mark all existing users with plain text passwords for mandatory reset
-- This ensures they reset their password on next login
UPDATE `users`
SET `password_reset_required` = 1
WHERE `password` NOT LIKE '$2y$%';
-- Only flag users whose passwords don't start with $2y$ (bcrypt hash identifier)
-- This way, if some passwords are already hashed, they won't be flagged

-- Step 3: Add index for faster lookups
ALTER TABLE `users`
ADD INDEX `idx_password_reset` (`password_reset_required`);

-- Verification Query (run this to check it worked):
-- SELECT user_id, email, password, password_reset_required FROM users;

-- Expected result:
-- Both existing users (natiemoyo2001@gmail.com and test@gmail.com)
-- should have password_reset_required = 1

-- Note: After users reset their passwords:
-- 1. Their password will be hashed (starting with $2y$10$...)
-- 2. password_reset_required will be set back to 0
-- 3. They can log in normally with their new password
