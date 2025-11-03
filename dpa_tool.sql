-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 02, 2025 at 05:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dpa_tool`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` int(11) UNSIGNED NOT NULL,
  `action` enum('create','read','update','delete','approve','reject','export') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `org_id`, `user_id`, `entity_type`, `entity_id`, `action`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-28 15:59:54'),
(2, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 07:46:07'),
(3, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 08:55:11'),
(4, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 08:55:43'),
(5, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 09:34:41'),
(6, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 15:45:21'),
(7, 1, 1, 'dpia', 1, 'create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 15:48:49'),
(8, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 05:13:07'),
(9, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 05:16:36'),
(10, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 06:17:08'),
(11, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 06:17:27'),
(12, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 08:59:04'),
(13, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-30 08:59:43'),
(14, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 06:09:29'),
(15, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:33:25'),
(16, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:33:40'),
(17, 1, 1, 'user', 2, 'create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:46:25'),
(18, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:46:35'),
(19, 1, 2, 'user', 2, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:46:44'),
(20, 1, 2, 'user', 2, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:47:13'),
(21, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:47:20'),
(22, 1, 1, 'department', 2, 'create', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 07:52:24'),
(23, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:02:24'),
(24, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:21:32'),
(25, 1, 1, 'user', 1, 'update', NULL, '{\"action\":\"mandatory_password_reset\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:22:18'),
(26, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:32:12'),
(27, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:35:45'),
(28, 1, 1, 'user', 2, 'update', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:36:36'),
(29, 1, 1, 'user', 2, 'update', NULL, '{\"action\":\"force_password_reset\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 15:36:36'),
(30, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:17:51'),
(31, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:18:37'),
(32, 1, 1, 'user', 1, 'update', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:19:29'),
(33, 1, 1, 'user', 1, 'update', NULL, '{\"action\":\"force_password_reset\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:19:29'),
(34, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:26:13'),
(35, 1, 1, 'user', 1, 'update', NULL, '{\"action\":\"mandatory_password_reset\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:26:32'),
(36, 1, 1, 'user', 2, 'update', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:28:38'),
(37, 1, 1, 'user', 2, 'update', NULL, '{\"action\":\"force_password_reset\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 16:28:38'),
(38, 1, 1, 'user', 1, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 20:15:51'),
(39, 1, 2, 'user', 2, '', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 03:45:49'),
(40, 1, 2, 'user', 2, 'update', NULL, '{\"action\":\"mandatory_password_reset\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 03:46:21');

-- --------------------------------------------------------

--
-- Table structure for table `consents`
--

CREATE TABLE `consents` (
  `consent_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `subject_ref` varchar(255) NOT NULL,
  `purpose_id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED DEFAULT NULL,
  `consent_method` varchar(100) DEFAULT 'digital',
  `consent_text` text DEFAULT NULL,
  `privacy_notice_version` varchar(50) DEFAULT NULL,
  `captured_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','granted','withdrawn','expired','renewed') DEFAULT 'active',
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `withdrawal_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `subject_name` varchar(255) DEFAULT NULL,
  `subject_email` varchar(255) DEFAULT NULL,
  `subject_phone` varchar(50) DEFAULT NULL,
  `subject_id_number` varchar(100) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `purpose_description` text DEFAULT NULL,
  `consent_date` date DEFAULT NULL,
  `consent_evidence` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `data_categories` text DEFAULT NULL,
  `retention_period` varchar(255) DEFAULT NULL,
  `withdrawal_method` text DEFAULT NULL,
  `withdrawal_method_used` varchar(255) DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `withdrawn_by` int(11) UNSIGNED DEFAULT NULL,
  `renewal_notes` text DEFAULT NULL,
  `renewed_from_consent_id` int(11) UNSIGNED DEFAULT NULL,
  `renewed_to_consent_id` int(11) UNSIGNED DEFAULT NULL,
  `withdrawal_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `controls`
--

CREATE TABLE `controls` (
  `control_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `control_name` varchar(255) NOT NULL,
  `control_description` text DEFAULT NULL,
  `control_type` enum('preventive','detective','corrective') DEFAULT 'preventive',
  `framework_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cross_border_transfers`
--

CREATE TABLE `cross_border_transfers` (
  `cb_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED DEFAULT NULL,
  `destination_country` varchar(100) NOT NULL,
  `recipient_org` varchar(255) NOT NULL,
  `recipient_contact` varchar(255) DEFAULT NULL,
  `data_type_transferred` text DEFAULT NULL,
  `transfer_frequency` enum('one-time','periodic','continuous') DEFAULT 'periodic',
  `safeguard_id` int(11) UNSIGNED DEFAULT NULL,
  `safeguard_details` text DEFAULT NULL,
  `potraz_notification_ref` varchar(100) DEFAULT NULL,
  `status` enum('pending','submitted','approved','blocked') DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `is_high_risk` tinyint(1) DEFAULT 0,
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_snapshots`
--

CREATE TABLE `dashboard_snapshots` (
  `snap_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `snapshot_date` date NOT NULL,
  `kpis_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`kpis_json`)),
  `compliance_score` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_categories`
--

CREATE TABLE `data_categories` (
  `data_category_id` int(11) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `is_special_category` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `data_categories`
--

INSERT INTO `data_categories` (`data_category_id`, `category_name`, `is_special_category`, `description`) VALUES
(1, 'Personal Identifiers', 0, 'Name, ID number, passport'),
(2, 'Contact Information', 0, 'Email, phone, address'),
(3, 'Financial Data', 0, 'Bank details, payment info'),
(4, 'Health Information', 1, 'Medical records, health status'),
(5, 'Biometric Data', 1, 'Fingerprints, facial recognition'),
(6, 'Criminal Records', 1, 'Criminal history'),
(7, 'Racial/Ethnic Origin', 1, 'Race, ethnicity'),
(8, 'Political Opinions', 1, 'Political beliefs'),
(9, 'Religious Beliefs', 1, 'Religious affiliation'),
(10, 'Employment Data', 0, 'Job title, salary, performance'),
(11, 'Education Records', 0, 'Grades, qualifications'),
(12, 'Location Data', 0, 'GPS, IP address');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `dept_name` varchar(255) NOT NULL,
  `dept_head_user_id` int(11) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `org_id`, `dept_name`, `dept_head_user_id`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'IT', NULL, NULL, '2025-10-31 07:45:42', '2025-10-31 07:45:42'),
(2, 1, 'TENDDAi', 1, '', '2025-10-31 07:52:24', '2025-10-31 07:52:24');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `doc_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `doc_name` varchar(255) NOT NULL,
  `doc_type` enum('DPA','DSA','policy','certification','audit_report','contract','consent','evidence','documentation','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_hash` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `version` varchar(50) DEFAULT '1.0',
  `expiry_date` date DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `status` enum('active','expiring_soon','expired','archived') DEFAULT 'active',
  `uploaded_by` int(11) UNSIGNED NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`doc_id`, `org_id`, `doc_name`, `doc_type`, `description`, `file_path`, `file_size`, `file_hash`, `mime_type`, `version`, `expiry_date`, `review_date`, `status`, `uploaded_by`, `uploaded_at`, `updated_at`) VALUES
(1, 1, 'User Manual', 'documentation', 'Complete user manual for the Zimbabwe DPA Tool covering all features and functionality', 'docs/USER_MANUAL.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(2, 1, 'Quick Start Guide', 'documentation', 'Quick start guide to get up and running with the DPA Tool', 'docs/QUICK_START_GUIDE.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(3, 1, 'Role Guide', 'documentation', 'Guide explaining different user roles and their permissions in the system', 'docs/ROLE_GUIDE.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(4, 1, 'Admin Setup Guide', 'documentation', 'Administrator guide for initial system setup and configuration', 'docs/ADMIN_SETUP_GUIDE.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(5, 1, 'Mandatory Documents Guide', 'documentation', 'Detailed guide on mandatory documents required for GDPR/DPA compliance', 'docs/MANDATORY_DOCUMENTS_GUIDE.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(6, 1, 'Mandatory Documents Checklist', 'documentation', 'Checklist of all mandatory documents for compliance tracking', 'docs/MANDATORY_DOCUMENTS_CHECKLIST.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(7, 1, 'Mandatory Documents Quick Reference', 'documentation', 'Quick reference card for mandatory documentation requirements', 'docs/MANDATORY_DOCUMENTS_QUICK_REFERENCE.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(9, 1, 'BRD Compliance Report', 'documentation', 'Business Requirements Document compliance and implementation report', 'docs/BRD_COMPLIANCE_REPORT.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40'),
(10, 1, 'Password Security Implementation', 'documentation', 'Technical documentation on password security implementation and migration', 'docs/PASSWORD_SECURITY_IMPLEMENTATION.md', 0, NULL, 'text/markdown', '1.0', NULL, NULL, 'active', 1, '2025-11-02 04:07:40', '2025-11-02 04:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `document_links`
--

CREATE TABLE `document_links` (
  `link_id` int(11) UNSIGNED NOT NULL,
  `doc_id` int(11) UNSIGNED NOT NULL,
  `entity_type` enum('ropa','dpia','vendor','risk','incident','dsr','consent') NOT NULL,
  `entity_id` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dpia`
--

CREATE TABLE `dpia` (
  `dpia_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED DEFAULT NULL,
  `dpia_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `screening_result` enum('needed','not_needed') DEFAULT 'needed',
  `screening_reason` text DEFAULT NULL,
  `inherent_risk_score` int(11) DEFAULT 0,
  `residual_risk_score` int(11) DEFAULT 0,
  `status` enum('draft','submitted','approved','rework','rejected') DEFAULT 'draft',
  `approver_id` int(11) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dpia`
--

INSERT INTO `dpia` (`dpia_id`, `org_id`, `ropa_id`, `dpia_title`, `description`, `screening_result`, `screening_reason`, `inherent_risk_score`, `residual_risk_score`, `status`, `approver_id`, `approved_at`, `approval_notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'test', '', 'needed', '', 0, 0, 'draft', NULL, NULL, NULL, 1, '2025-10-29 15:48:49', '2025-10-29 15:48:49');

-- --------------------------------------------------------

--
-- Table structure for table `dpia_risks`
--

CREATE TABLE `dpia_risks` (
  `dpia_risk_id` int(11) UNSIGNED NOT NULL,
  `dpia_id` int(11) UNSIGNED NOT NULL,
  `risk_title` varchar(255) NOT NULL,
  `risk_description` text DEFAULT NULL,
  `risk_category` enum('confidentiality','integrity','availability','compliance') DEFAULT 'confidentiality',
  `likelihood` int(11) NOT NULL CHECK (`likelihood` between 1 and 5),
  `impact` int(11) NOT NULL CHECK (`impact` between 1 and 5),
  `inherent_score` int(11) GENERATED ALWAYS AS (`likelihood` * `impact`) STORED,
  `mitigation_measures` text DEFAULT NULL,
  `residual_likelihood` int(11) DEFAULT NULL CHECK (`residual_likelihood` between 1 and 5),
  `residual_impact` int(11) DEFAULT NULL CHECK (`residual_impact` between 1 and 5),
  `residual_score` int(11) GENERATED ALWAYS AS (case when `residual_likelihood` is not null and `residual_impact` is not null then `residual_likelihood` * `residual_impact` else NULL end) STORED,
  `responsible_user_id` int(11) UNSIGNED DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('identified','mitigating','closed') DEFAULT 'identified',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dpia_steps`
--

CREATE TABLE `dpia_steps` (
  `dpia_step_id` int(11) UNSIGNED NOT NULL,
  `dpia_id` int(11) UNSIGNED NOT NULL,
  `step_number` int(11) NOT NULL,
  `step_type` varchar(100) NOT NULL,
  `step_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`step_data`)),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dpia_steps`
--

INSERT INTO `dpia_steps` (`dpia_step_id`, `dpia_id`, `step_number`, `step_type`, `step_data`, `completed_at`, `created_at`) VALUES
(1, 1, 1, 'screening', '{\"screening_result\":\"needed\",\"screening_reason\":\"\"}', '2025-10-29 15:48:49', '2025-10-29 15:48:49');

-- --------------------------------------------------------

--
-- Table structure for table `dsr_actions`
--

CREATE TABLE `dsr_actions` (
  `dsr_action_id` int(11) UNSIGNED NOT NULL,
  `dsr_id` int(11) UNSIGNED NOT NULL,
  `action_description` text DEFAULT NULL,
  `assigned_to` int(11) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dsr_requests`
--

CREATE TABLE `dsr_requests` (
  `dsr_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `request_ref` varchar(50) NOT NULL,
  `request_type` enum('access','rectification','erasure','objection','portability','restriction') NOT NULL,
  `subject_name` varchar(255) DEFAULT NULL,
  `subject_ref` varchar(255) NOT NULL,
  `subject_email` varchar(255) DEFAULT NULL,
  `subject_phone` varchar(50) DEFAULT NULL,
  `request_details` text DEFAULT NULL,
  `verification_method` varchar(255) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) UNSIGNED DEFAULT NULL,
  `received_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_at` timestamp GENERATED ALWAYS AS (`received_at` + interval 30 day) STORED,
  `status` enum('received','in_progress','completed','rejected') DEFAULT 'received',
  `rejection_reason` text DEFAULT NULL,
  `response_summary` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `assigned_to` int(11) UNSIGNED DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `incident_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED DEFAULT NULL,
  `incident_ref` varchar(50) NOT NULL,
  `incident_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('minor','serious','critical') DEFAULT 'minor',
  `notifiable` tinyint(1) DEFAULT 0,
  `data_subjects_affected` int(11) DEFAULT 0,
  `data_categories_affected` text DEFAULT NULL,
  `how_detected` text DEFAULT NULL,
  `detected_at` timestamp NULL DEFAULT NULL,
  `detected_by` int(11) UNSIGNED DEFAULT NULL,
  `notified_at` timestamp NULL DEFAULT NULL,
  `notification_method` varchar(255) DEFAULT NULL,
  `potraz_ref` varchar(100) DEFAULT NULL,
  `root_cause` text DEFAULT NULL,
  `corrective_actions` text DEFAULT NULL,
  `status` enum('open','investigating','notified','resolved','closed') DEFAULT 'open',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_actions`
--

CREATE TABLE `incident_actions` (
  `incident_action_id` int(11) UNSIGNED NOT NULL,
  `incident_id` int(11) UNSIGNED NOT NULL,
  `action_type` enum('investigation','notification','containment','remediation','communication') DEFAULT 'investigation',
  `action_description` text DEFAULT NULL,
  `assigned_to` int(11) UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lawful_basis`
--

CREATE TABLE `lawful_basis` (
  `lawful_basis_id` int(11) UNSIGNED NOT NULL,
  `basis_name` varchar(100) NOT NULL,
  `basis_description` text DEFAULT NULL,
  `cdpa_reference` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lawful_basis`
--

INSERT INTO `lawful_basis` (`lawful_basis_id`, `basis_name`, `basis_description`, `cdpa_reference`) VALUES
(1, 'Consent', 'Data subject has given explicit consent', 'CDPA s.22-23'),
(2, 'Contract', 'Processing necessary for contract performance', 'CDPA s.22'),
(3, 'Legal Obligation', 'Required by law', 'CDPA s.22'),
(4, 'Vital Interest', 'Necessary to protect life', 'CDPA s.22'),
(5, 'Public Task', 'Necessary for public interest', 'CDPA s.22'),
(6, 'Legitimate Interest', 'Legitimate interests of controller', 'CDPA s.22');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `notification_type` enum('consent_expiry','dsr_sla','breach_sla','risk_overdue','training_due','document_expiry','dpia_pending','system','risk_assigned','dsr_assigned','dsr_new','cross_border_high_risk','consent_expiring','dpia_approved','dpia_rejected','dpia_revision_required','incident_assigned','data_breach','policy_published','training_assigned') DEFAULT 'system',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) UNSIGNED DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `org_id` int(11) UNSIGNED NOT NULL,
  `org_name` varchar(255) NOT NULL,
  `org_email` varchar(255) NOT NULL,
  `org_phone` varchar(50) DEFAULT NULL,
  `org_address` text DEFAULT NULL,
  `org_registration_number` varchar(100) DEFAULT NULL,
  `org_type` enum('controller','processor','both') DEFAULT 'controller',
  `potraz_registration_ref` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`org_id`, `org_name`, `org_email`, `org_phone`, `org_address`, `org_registration_number`, `org_type`, `potraz_registration_ref`, `status`, `created_at`, `updated_at`) VALUES
(1, 'TENDDAI', 'belindamoyo109@gmail.com', '0785677611', '31 kendon court', 'ZIM0001', 'both', 'ZIM01', 'active', '2025-10-28 15:27:33', '2025-10-28 15:27:33');

-- --------------------------------------------------------

--
-- Table structure for table `policies`
--

CREATE TABLE `policies` (
  `policy_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `policy_title` varchar(255) NOT NULL,
  `policy_type` enum('data_protection','privacy','security','retention','breach','other') DEFAULT 'data_protection',
  `version` varchar(50) DEFAULT '1.0',
  `description` text DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `review_due_date` date DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `policy_acknowledgements`
--

CREATE TABLE `policy_acknowledgements` (
  `ack_id` int(11) UNSIGNED NOT NULL,
  `policy_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `acknowledged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `processing_activities`
--

CREATE TABLE `processing_activities` (
  `ropa_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `dept_id` int(11) UNSIGNED DEFAULT NULL,
  `activity_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `purpose_id` int(11) UNSIGNED DEFAULT NULL,
  `lawful_basis_id` int(11) UNSIGNED DEFAULT NULL,
  `retention_id` int(11) UNSIGNED DEFAULT NULL,
  `data_source` enum('internal','external','both') DEFAULT 'internal',
  `has_special_categories` tinyint(1) DEFAULT 0,
  `has_minors` tinyint(1) DEFAULT 0,
  `has_cross_border` tinyint(1) DEFAULT 0,
  `estimated_data_subjects` int(11) DEFAULT 0,
  `security_measures` text DEFAULT NULL,
  `status` enum('draft','validated','archived') DEFAULT 'draft',
  `created_by` int(11) UNSIGNED NOT NULL,
  `validated_by` int(11) UNSIGNED DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purposes`
--

CREATE TABLE `purposes` (
  `purpose_id` int(11) UNSIGNED NOT NULL,
  `purpose_name` varchar(255) NOT NULL,
  `purpose_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purposes`
--

INSERT INTO `purposes` (`purpose_id`, `purpose_name`, `purpose_description`, `created_at`) VALUES
(1, 'test', NULL, '2025-10-31 15:37:57');

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
--

CREATE TABLE `recipients` (
  `recipient_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_type` enum('processor','controller','authority','other') DEFAULT 'processor',
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retention_policies`
--

CREATE TABLE `retention_policies` (
  `retention_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `policy_name` varchar(255) NOT NULL,
  `retention_period` varchar(100) NOT NULL,
  `retention_days` int(11) DEFAULT NULL,
  `justification` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risks`
--

CREATE TABLE `risks` (
  `risk_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `dept_id` int(11) UNSIGNED DEFAULT NULL,
  `dpia_id` int(11) UNSIGNED DEFAULT NULL,
  `risk_title` varchar(255) NOT NULL,
  `risk_description` text DEFAULT NULL,
  `risk_category` enum('operational','technical','legal','reputational') DEFAULT 'operational',
  `owner_id` int(11) UNSIGNED DEFAULT NULL,
  `likelihood` int(11) NOT NULL CHECK (`likelihood` between 1 and 5),
  `impact` int(11) NOT NULL CHECK (`impact` between 1 and 5),
  `inherent_score` int(11) GENERATED ALWAYS AS (`likelihood` * `impact`) STORED,
  `residual_likelihood` int(11) DEFAULT NULL CHECK (`residual_likelihood` between 1 and 5),
  `residual_impact` int(11) DEFAULT NULL CHECK (`residual_impact` between 1 and 5),
  `residual_score` int(11) GENERATED ALWAYS AS (case when `residual_likelihood` is not null and `residual_impact` is not null then `residual_likelihood` * `residual_impact` else NULL end) STORED,
  `status` enum('open','mitigating','closed') DEFAULT 'open',
  `due_date` date DEFAULT NULL,
  `closed_date` date DEFAULT NULL,
  `closure_notes` text DEFAULT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `risk_owner_id` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_controls`
--

CREATE TABLE `risk_controls` (
  `risk_control_id` int(11) UNSIGNED NOT NULL,
  `risk_id` int(11) UNSIGNED NOT NULL,
  `control_id` int(11) UNSIGNED NOT NULL,
  `implementation_status` enum('planned','in_progress','implemented','verified') DEFAULT 'planned',
  `effectiveness` enum('high','medium','low') DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `role_description`, `permissions`, `created_at`) VALUES
(1, 'Admin', 'Full system access and configuration', NULL, '2025-10-28 13:20:02'),
(2, 'DPO', 'Data Protection Officer - Approvals and oversight', NULL, '2025-10-28 13:20:02'),
(3, 'Department Owner', 'Department-level data management', NULL, '2025-10-28 13:20:02'),
(4, 'Staff', 'Basic data entry and viewing', NULL, '2025-10-28 13:20:02'),
(5, 'Auditor', 'Read-only access for auditing', NULL, '2025-10-28 13:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `ropa_data_categories`
--

CREATE TABLE `ropa_data_categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED NOT NULL,
  `data_category_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ropa_recipients`
--

CREATE TABLE `ropa_recipients` (
  `id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED NOT NULL,
  `recipient_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ropa_storage_locations`
--

CREATE TABLE `ropa_storage_locations` (
  `id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED NOT NULL,
  `location_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ropa_subject_categories`
--

CREATE TABLE `ropa_subject_categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `ropa_id` int(11) UNSIGNED NOT NULL,
  `subject_category_id` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safeguards`
--

CREATE TABLE `safeguards` (
  `safeguard_id` int(11) UNSIGNED NOT NULL,
  `safeguard_name` varchar(255) NOT NULL,
  `safeguard_description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safeguards`
--

INSERT INTO `safeguards` (`safeguard_id`, `safeguard_name`, `safeguard_description`) VALUES
(1, 'Consent', 'Data subject consent'),
(2, 'Standard Contractual Clauses (SCC)', 'Standard data protection clauses'),
(3, 'Adequacy Decision', 'Country with adequate protection'),
(4, 'Encryption', 'End-to-end encryption'),
(5, 'Binding Corporate Rules', 'Internal data protection rules'),
(6, 'Other', 'Other safeguard measures');

-- --------------------------------------------------------

--
-- Table structure for table `storage_locations`
--

CREATE TABLE `storage_locations` (
  `location_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `location_name` varchar(255) NOT NULL,
  `location_type` enum('on-premise','cloud','hybrid') NOT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `security_measures` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subject_categories`
--

CREATE TABLE `subject_categories` (
  `subject_category_id` int(11) UNSIGNED NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `is_vulnerable` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject_categories`
--

INSERT INTO `subject_categories` (`subject_category_id`, `category_name`, `is_vulnerable`, `description`) VALUES
(1, 'Employees', 0, 'Current staff members'),
(2, 'Customers', 0, 'Clients and customers'),
(3, 'Suppliers', 0, 'Vendors and service providers'),
(4, 'Students/Learners', 1, 'Educational institution students'),
(5, 'Minors', 1, 'Persons under 18 years'),
(6, 'Patients', 1, 'Healthcare recipients'),
(7, 'Website Visitors', 0, 'Online visitors'),
(8, 'Job Applicants', 0, 'Recruitment candidates');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED DEFAULT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `org_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, NULL, 'dsr_sla_days', '30', 'integer', 'Default SLA days for DSR requests', '2025-10-28 13:20:05'),
(2, NULL, 'breach_notification_hours', '72', 'integer', 'Hours to notify POTRAZ of data breach', '2025-10-28 13:20:05'),
(3, NULL, 'consent_expiry_alert_days', '30', 'integer', 'Days before consent expiry to send alert', '2025-10-28 13:20:05'),
(4, NULL, 'dpia_threshold_subjects', '10000', 'integer', 'Number of data subjects to trigger DPIA', '2025-10-28 13:20:05'),
(5, NULL, 'risk_acceptable_threshold', '6', 'integer', 'Maximum acceptable residual risk score', '2025-10-28 13:20:05'),
(6, NULL, 'session_timeout_minutes', '30', 'integer', 'User session timeout in minutes', '2025-10-28 13:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `training`
--

CREATE TABLE `training` (
  `training_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `training_title` varchar(255) NOT NULL,
  `training_type` enum('data_protection','privacy','security','compliance','other') DEFAULT 'data_protection',
  `description` text DEFAULT NULL,
  `content_path` varchar(500) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `passing_score` int(11) DEFAULT NULL,
  `due_days` int(11) DEFAULT 365,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_assignments`
--

CREATE TABLE `training_assignments` (
  `assign_id` int(11) UNSIGNED NOT NULL,
  `training_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `due_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `status` enum('assigned','in_progress','completed','overdue') DEFAULT 'assigned',
  `certificate_path` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `role_id` int(11) UNSIGNED NOT NULL,
  `dept_id` int(11) UNSIGNED DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_required` tinyint(1) DEFAULT 0,
  `phone` varchar(50) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','locked') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `org_id`, `role_id`, `dept_id`, `first_name`, `last_name`, `email`, `password`, `password_reset_required`, `phone`, `two_factor_enabled`, `two_factor_secret`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, 'belinda', 'moyo', 'natiemoyo2001@gmail.com', '$2y$10$jYT8FswKhWlppEVyCCyT3.hbk0hISAFfzske6s.lU47.d5ycfXBHe', 0, '&lt;br /&gt;&lt;b&gt;Deprecated&lt;/b&gt;:  htmlsp', 0, NULL, 'active', '2025-11-01 16:26:13', '2025-10-28 15:30:16', '2025-11-01 16:26:32'),
(2, 1, 3, 1, 'test', 'test', 'test@gmail.com', '$2y$10$32/Lw6jiO7HmjoxUiMW/Y.At9nwLINkdc2nGDGqM0VMKSRyYhGi9i', 0, '0775075122', 0, NULL, 'active', '2025-11-02 03:45:49', '2025-10-31 07:46:25', '2025-11-02 03:46:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `org_id` int(11) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `entity_lookup` (`entity_type`,`entity_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `consents`
--
ALTER TABLE `consents`
  ADD PRIMARY KEY (`consent_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `purpose_id` (`purpose_id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `subject_ref` (`subject_ref`);

--
-- Indexes for table `controls`
--
ALTER TABLE `controls`
  ADD PRIMARY KEY (`control_id`),
  ADD KEY `org_id` (`org_id`);

--
-- Indexes for table `cross_border_transfers`
--
ALTER TABLE `cross_border_transfers`
  ADD PRIMARY KEY (`cb_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `safeguard_id` (`safeguard_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `dashboard_snapshots`
--
ALTER TABLE `dashboard_snapshots`
  ADD PRIMARY KEY (`snap_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `snapshot_date` (`snapshot_date`);

--
-- Indexes for table `data_categories`
--
ALTER TABLE `data_categories`
  ADD PRIMARY KEY (`data_category_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD KEY `org_id` (`org_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `document_links`
--
ALTER TABLE `document_links`
  ADD PRIMARY KEY (`link_id`),
  ADD KEY `doc_id` (`doc_id`),
  ADD KEY `entity_lookup` (`entity_type`,`entity_id`);

--
-- Indexes for table `dpia`
--
ALTER TABLE `dpia`
  ADD PRIMARY KEY (`dpia_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `approver_id` (`approver_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `dpia_risks`
--
ALTER TABLE `dpia_risks`
  ADD PRIMARY KEY (`dpia_risk_id`),
  ADD KEY `dpia_id` (`dpia_id`),
  ADD KEY `responsible_user_id` (`responsible_user_id`);

--
-- Indexes for table `dpia_steps`
--
ALTER TABLE `dpia_steps`
  ADD PRIMARY KEY (`dpia_step_id`),
  ADD KEY `dpia_id` (`dpia_id`);

--
-- Indexes for table `dsr_actions`
--
ALTER TABLE `dsr_actions`
  ADD PRIMARY KEY (`dsr_action_id`),
  ADD KEY `dsr_id` (`dsr_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `dsr_requests`
--
ALTER TABLE `dsr_requests`
  ADD PRIMARY KEY (`dsr_id`),
  ADD UNIQUE KEY `request_ref` (`request_ref`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD UNIQUE KEY `incident_ref` (`incident_ref`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `detected_by` (`detected_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `incident_actions`
--
ALTER TABLE `incident_actions`
  ADD PRIMARY KEY (`incident_action_id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `assigned_to` (`assigned_to`);

--
-- Indexes for table `lawful_basis`
--
ALTER TABLE `lawful_basis`
  ADD PRIMARY KEY (`lawful_basis_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`org_id`),
  ADD UNIQUE KEY `org_email` (`org_email`);

--
-- Indexes for table `policies`
--
ALTER TABLE `policies`
  ADD PRIMARY KEY (`policy_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `policy_acknowledgements`
--
ALTER TABLE `policy_acknowledgements`
  ADD PRIMARY KEY (`ack_id`),
  ADD KEY `policy_id` (`policy_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `processing_activities`
--
ALTER TABLE `processing_activities`
  ADD PRIMARY KEY (`ropa_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `purpose_id` (`purpose_id`),
  ADD KEY `lawful_basis_id` (`lawful_basis_id`),
  ADD KEY `retention_id` (`retention_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `purposes`
--
ALTER TABLE `purposes`
  ADD PRIMARY KEY (`purpose_id`);

--
-- Indexes for table `recipients`
--
ALTER TABLE `recipients`
  ADD PRIMARY KEY (`recipient_id`),
  ADD KEY `org_id` (`org_id`);

--
-- Indexes for table `retention_policies`
--
ALTER TABLE `retention_policies`
  ADD PRIMARY KEY (`retention_id`),
  ADD KEY `org_id` (`org_id`);

--
-- Indexes for table `risks`
--
ALTER TABLE `risks`
  ADD PRIMARY KEY (`risk_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `dpia_id` (`dpia_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `risk_controls`
--
ALTER TABLE `risk_controls`
  ADD PRIMARY KEY (`risk_control_id`),
  ADD KEY `risk_id` (`risk_id`),
  ADD KEY `control_id` (`control_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `ropa_data_categories`
--
ALTER TABLE `ropa_data_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `data_category_id` (`data_category_id`);

--
-- Indexes for table `ropa_recipients`
--
ALTER TABLE `ropa_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `ropa_storage_locations`
--
ALTER TABLE `ropa_storage_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `ropa_subject_categories`
--
ALTER TABLE `ropa_subject_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ropa_id` (`ropa_id`),
  ADD KEY `subject_category_id` (`subject_category_id`);

--
-- Indexes for table `safeguards`
--
ALTER TABLE `safeguards`
  ADD PRIMARY KEY (`safeguard_id`);

--
-- Indexes for table `storage_locations`
--
ALTER TABLE `storage_locations`
  ADD PRIMARY KEY (`location_id`),
  ADD KEY `org_id` (`org_id`);

--
-- Indexes for table `subject_categories`
--
ALTER TABLE `subject_categories`
  ADD PRIMARY KEY (`subject_category_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `org_setting` (`org_id`,`setting_key`);

--
-- Indexes for table `training`
--
ALTER TABLE `training`
  ADD PRIMARY KEY (`training_id`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `training_assignments`
--
ALTER TABLE `training_assignments`
  ADD PRIMARY KEY (`assign_id`),
  ADD KEY `training_id` (`training_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `org_id` (`org_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `idx_password_reset` (`password_reset_required`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `consents`
--
ALTER TABLE `consents`
  MODIFY `consent_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `controls`
--
ALTER TABLE `controls`
  MODIFY `control_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cross_border_transfers`
--
ALTER TABLE `cross_border_transfers`
  MODIFY `cb_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_snapshots`
--
ALTER TABLE `dashboard_snapshots`
  MODIFY `snap_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_categories`
--
ALTER TABLE `data_categories`
  MODIFY `data_category_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `doc_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `document_links`
--
ALTER TABLE `document_links`
  MODIFY `link_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dpia`
--
ALTER TABLE `dpia`
  MODIFY `dpia_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dpia_risks`
--
ALTER TABLE `dpia_risks`
  MODIFY `dpia_risk_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dpia_steps`
--
ALTER TABLE `dpia_steps`
  MODIFY `dpia_step_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dsr_actions`
--
ALTER TABLE `dsr_actions`
  MODIFY `dsr_action_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dsr_requests`
--
ALTER TABLE `dsr_requests`
  MODIFY `dsr_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `incident_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_actions`
--
ALTER TABLE `incident_actions`
  MODIFY `incident_action_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lawful_basis`
--
ALTER TABLE `lawful_basis`
  MODIFY `lawful_basis_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `org_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `policies`
--
ALTER TABLE `policies`
  MODIFY `policy_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `policy_acknowledgements`
--
ALTER TABLE `policy_acknowledgements`
  MODIFY `ack_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `processing_activities`
--
ALTER TABLE `processing_activities`
  MODIFY `ropa_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purposes`
--
ALTER TABLE `purposes`
  MODIFY `purpose_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recipients`
--
ALTER TABLE `recipients`
  MODIFY `recipient_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `retention_policies`
--
ALTER TABLE `retention_policies`
  MODIFY `retention_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risks`
--
ALTER TABLE `risks`
  MODIFY `risk_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_controls`
--
ALTER TABLE `risk_controls`
  MODIFY `risk_control_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ropa_data_categories`
--
ALTER TABLE `ropa_data_categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ropa_recipients`
--
ALTER TABLE `ropa_recipients`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ropa_storage_locations`
--
ALTER TABLE `ropa_storage_locations`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ropa_subject_categories`
--
ALTER TABLE `ropa_subject_categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `safeguards`
--
ALTER TABLE `safeguards`
  MODIFY `safeguard_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `storage_locations`
--
ALTER TABLE `storage_locations`
  MODIFY `location_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subject_categories`
--
ALTER TABLE `subject_categories`
  MODIFY `subject_category_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `training`
--
ALTER TABLE `training`
  MODIFY `training_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_assignments`
--
ALTER TABLE `training_assignments`
  MODIFY `assign_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `fk_audit_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `consents`
--
ALTER TABLE `consents`
  ADD CONSTRAINT `fk_consent_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_consent_purpose` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`purpose_id`),
  ADD CONSTRAINT `fk_consent_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE SET NULL;

--
-- Constraints for table `controls`
--
ALTER TABLE `controls`
  ADD CONSTRAINT `fk_control_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `cross_border_transfers`
--
ALTER TABLE `cross_border_transfers`
  ADD CONSTRAINT `fk_cb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_cb_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cb_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cb_safeguard` FOREIGN KEY (`safeguard_id`) REFERENCES `safeguards` (`safeguard_id`);

--
-- Constraints for table `dashboard_snapshots`
--
ALTER TABLE `dashboard_snapshots`
  ADD CONSTRAINT `fk_snapshot_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_dept_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `fk_doc_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doc_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `document_links`
--
ALTER TABLE `document_links`
  ADD CONSTRAINT `fk_doc_link_doc` FOREIGN KEY (`doc_id`) REFERENCES `documents` (`doc_id`) ON DELETE CASCADE;

--
-- Constraints for table `dpia`
--
ALTER TABLE `dpia`
  ADD CONSTRAINT `fk_dpia_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_dpia_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_dpia_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dpia_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE SET NULL;

--
-- Constraints for table `dpia_risks`
--
ALTER TABLE `dpia_risks`
  ADD CONSTRAINT `fk_dpia_risk_dpia` FOREIGN KEY (`dpia_id`) REFERENCES `dpia` (`dpia_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dpia_risk_user` FOREIGN KEY (`responsible_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `dpia_steps`
--
ALTER TABLE `dpia_steps`
  ADD CONSTRAINT `fk_dpia_step_dpia` FOREIGN KEY (`dpia_id`) REFERENCES `dpia` (`dpia_id`) ON DELETE CASCADE;

--
-- Constraints for table `dsr_actions`
--
ALTER TABLE `dsr_actions`
  ADD CONSTRAINT `fk_dsr_action_dsr` FOREIGN KEY (`dsr_id`) REFERENCES `dsr_requests` (`dsr_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dsr_action_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `dsr_requests`
--
ALTER TABLE `dsr_requests`
  ADD CONSTRAINT `fk_dsr_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_dsr_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_dsr_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dsr_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `fk_incident_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_incident_detector` FOREIGN KEY (`detected_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_incident_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_incident_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE SET NULL;

--
-- Constraints for table `incident_actions`
--
ALTER TABLE `incident_actions`
  ADD CONSTRAINT `fk_incident_action_incident` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`incident_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_incident_action_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `policies`
--
ALTER TABLE `policies`
  ADD CONSTRAINT `fk_policy_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_policy_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `policy_acknowledgements`
--
ALTER TABLE `policy_acknowledgements`
  ADD CONSTRAINT `fk_policy_ack_policy` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`policy_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_policy_ack_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `processing_activities`
--
ALTER TABLE `processing_activities`
  ADD CONSTRAINT `fk_ropa_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_ropa_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ropa_lawful_basis` FOREIGN KEY (`lawful_basis_id`) REFERENCES `lawful_basis` (`lawful_basis_id`),
  ADD CONSTRAINT `fk_ropa_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ropa_purpose` FOREIGN KEY (`purpose_id`) REFERENCES `purposes` (`purpose_id`),
  ADD CONSTRAINT `fk_ropa_retention` FOREIGN KEY (`retention_id`) REFERENCES `retention_policies` (`retention_id`);

--
-- Constraints for table `recipients`
--
ALTER TABLE `recipients`
  ADD CONSTRAINT `fk_recipient_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `retention_policies`
--
ALTER TABLE `retention_policies`
  ADD CONSTRAINT `fk_retention_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `risks`
--
ALTER TABLE `risks`
  ADD CONSTRAINT `fk_risk_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_risk_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_risk_dpia` FOREIGN KEY (`dpia_id`) REFERENCES `dpia` (`dpia_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_risk_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_risk_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `risk_controls`
--
ALTER TABLE `risk_controls`
  ADD CONSTRAINT `fk_risk_control_control` FOREIGN KEY (`control_id`) REFERENCES `controls` (`control_id`),
  ADD CONSTRAINT `fk_risk_control_risk` FOREIGN KEY (`risk_id`) REFERENCES `risks` (`risk_id`) ON DELETE CASCADE;

--
-- Constraints for table `ropa_data_categories`
--
ALTER TABLE `ropa_data_categories`
  ADD CONSTRAINT `fk_ropa_data_category` FOREIGN KEY (`data_category_id`) REFERENCES `data_categories` (`data_category_id`),
  ADD CONSTRAINT `fk_ropa_data_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE CASCADE;

--
-- Constraints for table `ropa_recipients`
--
ALTER TABLE `ropa_recipients`
  ADD CONSTRAINT `fk_ropa_recipient_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `recipients` (`recipient_id`),
  ADD CONSTRAINT `fk_ropa_recipient_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE CASCADE;

--
-- Constraints for table `ropa_storage_locations`
--
ALTER TABLE `ropa_storage_locations`
  ADD CONSTRAINT `fk_ropa_location_location` FOREIGN KEY (`location_id`) REFERENCES `storage_locations` (`location_id`),
  ADD CONSTRAINT `fk_ropa_location_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE CASCADE;

--
-- Constraints for table `ropa_subject_categories`
--
ALTER TABLE `ropa_subject_categories`
  ADD CONSTRAINT `fk_ropa_subject_category` FOREIGN KEY (`subject_category_id`) REFERENCES `subject_categories` (`subject_category_id`),
  ADD CONSTRAINT `fk_ropa_subject_ropa` FOREIGN KEY (`ropa_id`) REFERENCES `processing_activities` (`ropa_id`) ON DELETE CASCADE;

--
-- Constraints for table `storage_locations`
--
ALTER TABLE `storage_locations`
  ADD CONSTRAINT `fk_storage_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_setting_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `training`
--
ALTER TABLE `training`
  ADD CONSTRAINT `fk_training_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_training_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE;

--
-- Constraints for table `training_assignments`
--
ALTER TABLE `training_assignments`
  ADD CONSTRAINT `fk_training_assign_training` FOREIGN KEY (`training_id`) REFERENCES `training` (`training_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_training_assign_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_org` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
