-- Vendor and Third-Party Risk Management Module
-- Database Tables Migration
-- Run this SQL to add vendor management capabilities

-- =====================================================
-- Table: vendors - Core vendor information
-- =====================================================
CREATE TABLE IF NOT EXISTS `vendors` (
  `vendor_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` int(11) UNSIGNED NOT NULL,
  `vendor_ref` varchar(50) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `vendor_type` enum('processor','supplier','contractor','cloud_provider','consultant','other') NOT NULL DEFAULT 'processor',
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `primary_contact_name` varchar(255) DEFAULT NULL,
  `primary_contact_email` varchar(255) DEFAULT NULL,
  `primary_contact_phone` varchar(50) DEFAULT NULL,
  `dpo_name` varchar(255) DEFAULT NULL,
  `dpo_email` varchar(255) DEFAULT NULL,
  `dpa_status` enum('none','pending','under_review','signed','expired') DEFAULT 'none',
  `dpa_signed_date` date DEFAULT NULL,
  `dpa_expiry_date` date DEFAULT NULL,
  `contract_ref` varchar(100) DEFAULT NULL,
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `data_categories_processed` text DEFAULT NULL,
  `processing_purpose` text DEFAULT NULL,
  `data_volume` enum('low','medium','high','very_high') DEFAULT 'medium',
  `access_type` enum('no_access','view_only','process','store','full_access') DEFAULT 'process',
  `has_subprocessors` tinyint(1) DEFAULT 0,
  `subprocessor_details` text DEFAULT NULL,
  `transfers_data_internationally` tinyint(1) DEFAULT 0,
  `data_transfer_countries` text DEFAULT NULL,
  `status` enum('pending_review','approved','active','suspended','terminated') DEFAULT 'pending_review',
  `criticality` enum('low','medium','high','critical') DEFAULT 'medium',
  `approved_by` int(11) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `last_review_date` date DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `review_frequency_months` int(11) DEFAULT 12,
  `notes` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`vendor_id`),
  KEY `idx_vendor_org` (`org_id`),
  KEY `idx_vendor_status` (`status`),
  KEY `idx_vendor_next_review` (`next_review_date`),
  KEY `idx_vendor_dpa_expiry` (`dpa_expiry_date`),
  KEY `idx_vendor_name` (`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: vendor_risk_assessments - Risk scoring per vendor
-- =====================================================
CREATE TABLE IF NOT EXISTS `vendor_risk_assessments` (
  `assessment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` int(11) UNSIGNED NOT NULL,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `assessment_ref` varchar(50) NOT NULL,
  `assessment_type` enum('initial','periodic','incident_triggered','contract_renewal') DEFAULT 'initial',
  `assessment_date` date NOT NULL,
  `data_security_likelihood` int(11) DEFAULT NULL,
  `data_security_impact` int(11) DEFAULT NULL,
  `compliance_likelihood` int(11) DEFAULT NULL,
  `compliance_impact` int(11) DEFAULT NULL,
  `operational_likelihood` int(11) DEFAULT NULL,
  `operational_impact` int(11) DEFAULT NULL,
  `financial_likelihood` int(11) DEFAULT NULL,
  `financial_impact` int(11) DEFAULT NULL,
  `reputational_likelihood` int(11) DEFAULT NULL,
  `reputational_impact` int(11) DEFAULT NULL,
  `inherent_risk_score` decimal(5,2) DEFAULT NULL,
  `inherent_risk_level` enum('low','medium','high','critical') DEFAULT NULL,
  `residual_risk_score` decimal(5,2) DEFAULT NULL,
  `residual_risk_level` enum('low','medium','high','critical') DEFAULT NULL,
  `treatment_strategy` enum('mitigate','accept','transfer','avoid') DEFAULT 'mitigate',
  `treatment_plan` text DEFAULT NULL,
  `treatment_owner_id` int(11) UNSIGNED DEFAULT NULL,
  `treatment_due_date` date DEFAULT NULL,
  `key_findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('draft','in_progress','completed','approved') DEFAULT 'draft',
  `assessed_by` int(11) UNSIGNED NOT NULL,
  `approved_by` int(11) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`assessment_id`),
  KEY `idx_assessment_vendor` (`vendor_id`),
  KEY `idx_assessment_org` (`org_id`),
  KEY `idx_assessment_date` (`assessment_date`),
  KEY `idx_assessment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: vendor_certifications - Track vendor certifications
-- =====================================================
CREATE TABLE IF NOT EXISTS `vendor_certifications` (
  `certification_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `certification_type` enum('iso_27001','iso_27701','soc2_type1','soc2_type2','gdpr_compliant','hipaa','pci_dss','other') NOT NULL,
  `certification_name` varchar(255) NOT NULL,
  `issuing_body` varchar(255) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('valid','expired','pending_renewal') DEFAULT 'valid',
  `notes` text DEFAULT NULL,
  `verified_by` int(11) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`certification_id`),
  KEY `idx_cert_vendor` (`vendor_id`),
  KEY `idx_cert_expiry` (`expiry_date`),
  KEY `idx_cert_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: vendor_due_diligence - Track questionnaires
-- =====================================================
CREATE TABLE IF NOT EXISTS `vendor_due_diligence` (
  `diligence_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` int(11) UNSIGNED NOT NULL,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `questionnaire_type` enum('security','privacy','business_continuity','financial','custom') NOT NULL,
  `questionnaire_name` varchar(255) NOT NULL,
  `sent_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `pass_threshold` decimal(5,2) DEFAULT 70.00,
  `result` enum('pending','passed','failed','conditional') DEFAULT 'pending',
  `findings` text DEFAULT NULL,
  `remediation_required` tinyint(1) DEFAULT 0,
  `remediation_notes` text DEFAULT NULL,
  `remediation_due_date` date DEFAULT NULL,
  `reviewed_by` int(11) UNSIGNED DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`diligence_id`),
  KEY `idx_diligence_vendor` (`vendor_id`),
  KEY `idx_diligence_org` (`org_id`),
  KEY `idx_diligence_due` (`due_date`),
  KEY `idx_diligence_result` (`result`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- Table: vendor_ropa - Link vendors to ROPA entries
-- =====================================================
CREATE TABLE IF NOT EXISTS `vendor_ropa` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED NOT NULL,
  `relationship_type` enum('processor','recipient','storage') DEFAULT 'processor',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_vendor_ropa` (`vendor_id`, `ropa_id`),
  KEY `idx_vendor_ropa_vendor` (`vendor_id`),
  KEY `idx_vendor_ropa_ropa` (`ropa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
