-- =============================================
-- Add Documentation Files to Document Repository
-- Date: November 2025
-- Purpose: Insert docs folder markdown files into documents table
-- =============================================

-- First, add 'documentation' type to doc_type enum
ALTER TABLE `documents`
MODIFY COLUMN `doc_type` ENUM('DPA', 'DSA', 'policy', 'certification', 'audit_report', 'contract', 'consent', 'evidence', 'documentation', 'other') DEFAULT 'other';

-- Insert documentation files into documents table
-- Note: Replace org_id=1 and uploaded_by=1 with your actual values

INSERT INTO `documents` (
    `org_id`,
    `doc_name`,
    `doc_type`,
    `description`,
    `file_path`,
    `file_size`,
    `mime_type`,
    `version`,
    `uploaded_by`,
    `status`
) VALUES
(
    1, -- Your organization ID
    'User Manual',
    'documentation',
    'Complete user manual for the Zimbabwe DPA Tool covering all features and functionality',
    'docs/USER_MANUAL.md',
    0,
    'text/markdown',
    '1.0',
    1, -- Admin user ID
    'active'
),
(
    1,
    'Quick Start Guide',
    'documentation',
    'Quick start guide to get up and running with the DPA Tool',
    'docs/QUICK_START_GUIDE.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'Role Guide',
    'documentation',
    'Guide explaining different user roles and their permissions in the system',
    'docs/ROLE_GUIDE.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'Admin Setup Guide',
    'documentation',
    'Administrator guide for initial system setup and configuration',
    'docs/ADMIN_SETUP_GUIDE.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'Mandatory Documents Guide',
    'documentation',
    'Detailed guide on mandatory documents required for GDPR/DPA compliance',
    'docs/MANDATORY_DOCUMENTS_GUIDE.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'Mandatory Documents Checklist',
    'documentation',
    'Checklist of all mandatory documents for compliance tracking',
    'docs/MANDATORY_DOCUMENTS_CHECKLIST.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'Mandatory Documents Quick Reference',
    'documentation',
    'Quick reference card for mandatory documentation requirements',
    'docs/MANDATORY_DOCUMENTS_QUICK_REFERENCE.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'README',
    'documentation',
    'Project README with overview and important information',
    'docs/README.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'BRD Compliance Report',
    'documentation',
    'Business Requirements Document compliance and implementation report',
    'docs/BRD_COMPLIANCE_REPORT.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
),
(
    1,
    'Password Security Implementation',
    'documentation',
    'Technical documentation on password security implementation and migration',
    'docs/PASSWORD_SECURITY_IMPLEMENTATION.md',
    0,
    'text/markdown',
    '1.0',
    1,
    'active'
);

-- Verify the insertions
SELECT doc_id, doc_name, doc_type, file_path
FROM documents
WHERE doc_type = 'documentation'
ORDER BY doc_name;
