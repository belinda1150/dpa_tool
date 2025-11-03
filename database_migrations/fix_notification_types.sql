-- Database Migration: Fix Notification Types
-- Date: October 2025
-- Purpose: Add missing notification types to the notifications table ENUM

-- Current ENUM has limited values, but the application uses many more notification types
-- This migration expands the ENUM to include all notification types used throughout the application

ALTER TABLE `notifications`
MODIFY COLUMN `notification_type` ENUM(
    -- Existing types (from original schema)
    'consent_expiry',
    'dsr_sla',
    'breach_sla',
    'risk_overdue',
    'training_due',
    'document_expiry',
    'dpia_pending',
    'system',

    -- New types (currently used in application but missing from ENUM)
    'risk_assigned',
    'dsr_assigned',
    'dsr_new',
    'cross_border_high_risk',
    'consent_expiring',
    'dpia_approved',
    'dpia_rejected',
    'dpia_revision_required',
    'incident_assigned',
    'data_breach',
    'policy_published',
    'training_assigned'
) DEFAULT 'system';

-- Note: Run this migration BEFORE testing the notification system
-- Without this migration, creating notifications with the new types will fail
