-- Add profile_picture column to users table
-- Run this SQL in phpMyAdmin or MySQL command line

ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(500) NULL DEFAULT NULL AFTER `phone`;
