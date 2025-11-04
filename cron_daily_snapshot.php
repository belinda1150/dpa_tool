<?php
/**
 * Daily Dashboard Snapshot
 * Saves daily compliance scores for trend analysis
 * Run this script daily via cron or Windows Task Scheduler
 *
 * Windows Task Scheduler:
 * Command: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\data_protection\cron_daily_snapshot.php
 * Schedule: Daily at midnight
 *
 * Linux Cron:
 * 0 0 * * * /usr/bin/php /path/to/data_protection/cron_daily_snapshot.php
 */

require_once 'config/database.php';
require_once 'config/config.php';

echo "Starting daily dashboard snapshot...\n";

// Get all organizations
$org_query = "SELECT org_id FROM organizations WHERE status = 'active'";
$org_stmt = db_query($org_query, []);
$organizations = db_fetch_all($org_stmt);

foreach ($organizations as $org) {
    $org_id = $org['org_id'];

    echo "Processing organization ID: $org_id\n";

    // Get ROPA Statistics
    $query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) as validated
        FROM processing_activities WHERE org_id = ?";
    $stmt = db_query($query, [$org_id]);
    $ropa_stats = db_fetch_one($stmt);

    // Get DPIA Statistics
    $query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
        FROM dpia WHERE org_id = ?";
    $stmt = db_query($query, [$org_id]);
    $dpia_stats = db_fetch_one($stmt);

    // Get Risk Statistics
    $query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open
        FROM risks WHERE org_id = ?";
    $stmt = db_query($query, [$org_id]);
    $risk_stats = db_fetch_one($stmt);

    // Get Incident Statistics
    $query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('open', 'investigating') THEN 1 ELSE 0 END) as active
        FROM incidents WHERE org_id = ?";
    $stmt = db_query($query, [$org_id]);
    $incident_stats = db_fetch_one($stmt);

    // Get DSR Statistics
    $query = "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('received', 'in_progress') THEN 1 ELSE 0 END) as pending
        FROM dsr_requests WHERE org_id = ?";
    $stmt = db_query($query, [$org_id]);
    $dsr_stats = db_fetch_one($stmt);

    // Calculate Compliance Score (same logic as dashboard.php)
    $compliance_score = 0;

    // ROPA completion (20 points)
    if ($ropa_stats['total'] > 0) {
        $compliance_score += ($ropa_stats['validated'] / $ropa_stats['total']) * 20;
    }

    // DPIA completion (15 points)
    if ($dpia_stats['total'] > 0) {
        $compliance_score += ($dpia_stats['approved'] / $dpia_stats['total']) * 15;
    }

    // Risk management (15 points)
    if ($risk_stats['total'] > 0) {
        $closed_risks = $risk_stats['total'] - $risk_stats['open'];
        $compliance_score += ($closed_risks / $risk_stats['total']) * 15;
    }

    // Incident response (15 points)
    if ($incident_stats['total'] > 0) {
        $resolved_incidents = $incident_stats['total'] - $incident_stats['active'];
        $compliance_score += ($resolved_incidents / $incident_stats['total']) * 15;
    } else {
        $compliance_score += 15; // No incidents is good
    }

    // DSR completion (15 points)
    if ($dsr_stats['total'] > 0) {
        $completed = $dsr_stats['total'] - $dsr_stats['pending'];
        $compliance_score += ($completed / $dsr_stats['total']) * 15;
    } else {
        $compliance_score += 15; // No pending DSRs is good
    }

    // Other factors (20 points) - simplified for now
    $compliance_score += 20;

    $compliance_score = round($compliance_score, 2);

    // Insert or update snapshot
    $snapshot_query = "INSERT INTO dashboard_snapshots
                      (org_id, snapshot_date, compliance_score,
                       total_ropa, validated_ropa, total_dpia, approved_dpia,
                       total_risks, open_risks, total_incidents, active_incidents,
                       total_dsr, pending_dsr)
                      VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE
                      compliance_score = VALUES(compliance_score),
                      total_ropa = VALUES(total_ropa),
                      validated_ropa = VALUES(validated_ropa),
                      total_dpia = VALUES(total_dpia),
                      approved_dpia = VALUES(approved_dpia),
                      total_risks = VALUES(total_risks),
                      open_risks = VALUES(open_risks),
                      total_incidents = VALUES(total_incidents),
                      active_incidents = VALUES(active_incidents),
                      total_dsr = VALUES(total_dsr),
                      pending_dsr = VALUES(pending_dsr)";

    $snapshot_stmt = db_query($snapshot_query, [
        $org_id,
        $compliance_score,
        $ropa_stats['total'],
        $ropa_stats['validated'],
        $dpia_stats['total'],
        $dpia_stats['approved'],
        $risk_stats['total'],
        $risk_stats['open'],
        $incident_stats['total'],
        $incident_stats['active'],
        $dsr_stats['total'],
        $dsr_stats['pending']
    ]);

    echo "  Saved snapshot: Compliance Score = $compliance_score%\n";
}

echo "Daily snapshot completed successfully!\n";
?>
