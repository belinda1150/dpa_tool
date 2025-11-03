-- =============================================
-- Risk Table Migration
-- Adds missing fields for enhanced risk management
-- Run this ONCE on your database
-- =============================================

USE dpa_tool;

-- Add missing columns to risks table
ALTER TABLE `risks`
ADD COLUMN `risk_owner_id` INT(11) UNSIGNED DEFAULT NULL AFTER `created_by`,
ADD COLUMN `residual_likelihood` INT(1) DEFAULT NULL AFTER `impact`,
ADD COLUMN `residual_impact` INT(1) DEFAULT NULL AFTER `residual_likelihood`,
ADD COLUMN `treatment_strategy` VARCHAR(50) DEFAULT 'mitigate' AFTER `risk_source`,
ADD COLUMN `treatment_description` TEXT DEFAULT NULL AFTER `treatment_strategy`,
ADD COLUMN `review_date` DATE DEFAULT NULL AFTER `residual_impact`,
ADD COLUMN `risk_source` VARCHAR(255) DEFAULT NULL AFTER `risk_category`;

-- Add foreign key constraints
ALTER TABLE `risks`
ADD CONSTRAINT `fk_risk_owner` FOREIGN KEY (`risk_owner_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- Add indexes for better performance
CREATE INDEX `idx_risk_owner` ON `risks` (`risk_owner_id`);
CREATE INDEX `idx_risk_status` ON `risks` (`status`);
CREATE INDEX `idx_risk_category` ON `risks` (`risk_category`);
CREATE INDEX `idx_risk_review_date` ON `risks` (`review_date`);

-- =============================================
-- Migration Complete
-- =============================================

SELECT 'Risk table migration completed successfully!' as Status;
