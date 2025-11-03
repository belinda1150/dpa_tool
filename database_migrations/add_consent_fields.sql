-- =============================================
-- Consent Table Migration
-- Adds missing fields for enhanced consent management
-- Run this ONCE on your database
-- =============================================

USE dpa_tool;

-- Add missing columns to consents table
ALTER TABLE `consents`
ADD COLUMN `subject_name` VARCHAR(255) DEFAULT NULL AFTER `subject_ref`,
ADD COLUMN `subject_email` VARCHAR(255) DEFAULT NULL AFTER `subject_name`,
ADD COLUMN `subject_phone` VARCHAR(50) DEFAULT NULL AFTER `subject_email`,
ADD COLUMN `subject_id_number` VARCHAR(100) DEFAULT NULL AFTER `subject_phone`,
ADD COLUMN `purpose` VARCHAR(255) DEFAULT NULL AFTER `ropa_id`,
ADD COLUMN `purpose_description` TEXT DEFAULT NULL AFTER `purpose`,
ADD COLUMN `consent_date` DATE DEFAULT NULL AFTER `purpose_description`,
ADD COLUMN `consent_evidence` TEXT DEFAULT NULL AFTER `consent_text`,
ADD COLUMN `expiry_date` DATE DEFAULT NULL AFTER `expires_at`,
ADD COLUMN `data_categories` TEXT DEFAULT NULL AFTER `expiry_date`,
ADD COLUMN `retention_period` VARCHAR(255) DEFAULT NULL AFTER `data_categories`,
ADD COLUMN `withdrawal_method` TEXT DEFAULT NULL AFTER `withdrawal_reason`,
ADD COLUMN `withdrawal_method_used` VARCHAR(255) DEFAULT NULL AFTER `withdrawal_method`,
ADD COLUMN `created_by` INT(11) UNSIGNED DEFAULT NULL AFTER `withdrawal_method_used`,
ADD COLUMN `withdrawn_by` INT(11) UNSIGNED DEFAULT NULL AFTER `created_by`,
ADD COLUMN `renewal_notes` TEXT DEFAULT NULL AFTER `withdrawn_by`,
ADD COLUMN `renewed_from_consent_id` INT(11) UNSIGNED DEFAULT NULL AFTER `renewal_notes`,
ADD COLUMN `renewed_to_consent_id` INT(11) UNSIGNED DEFAULT NULL AFTER `renewed_from_consent_id`,
ADD COLUMN `withdrawal_date` TIMESTAMP NULL DEFAULT NULL AFTER `withdrawn_at`;

-- Modify status enum to include 'active' and 'renewed'
ALTER TABLE `consents`
MODIFY COLUMN `status` ENUM('active', 'granted', 'withdrawn', 'expired', 'renewed') DEFAULT 'active';

-- Modify consent_method enum to support more options
ALTER TABLE `consents`
MODIFY COLUMN `consent_method` VARCHAR(100) DEFAULT 'digital';

-- Add foreign keys
ALTER TABLE `consents`
ADD CONSTRAINT `fk_consent_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
ADD CONSTRAINT `fk_consent_withdrawn_by` FOREIGN KEY (`withdrawn_by`) REFERENCES `users` (`user_id`);

-- Add indexes for better performance
CREATE INDEX `idx_consent_status` ON `consents` (`status`);
CREATE INDEX `idx_consent_expiry` ON `consents` (`expiry_date`);
CREATE INDEX `idx_consent_email` ON `consents` (`subject_email`);

-- Update existing records to have 'active' status instead of 'granted'
UPDATE `consents` SET `status` = 'active' WHERE `status` = 'granted';

-- Populate consent_date from captured_at for existing records
UPDATE `consents` SET `consent_date` = DATE(`captured_at`) WHERE `consent_date` IS NULL;

-- Populate expiry_date from expires_at for existing records
UPDATE `consents` SET `expiry_date` = DATE(`expires_at`) WHERE `expiry_date` IS NULL AND `expires_at` IS NOT NULL;

-- =============================================
-- Migration Complete
-- =============================================

SELECT 'Consent table migration completed successfully!' as Status;
