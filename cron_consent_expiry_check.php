<?php
/**
 * Consent Expiry Check - Cron Job
 * Run daily to check for expiring consents and send notifications
 *
 * Setup: Add to Windows Task Scheduler or Linux cron:
 * Windows: schtasks /create /tn "ConsentExpiryCheck" /tr "php C:\xampp\htdocs\data_protection\cron_consent_expiry_check.php" /sc daily /st 08:00
 * Linux: 0 8 * * * /usr/bin/php /path/to/cron_consent_expiry_check.php
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting consent expiry check...\n";

// Get all organizations
$orgs_query = "SELECT org_id, org_name FROM organizations WHERE status = 'active'";
$orgs = db_fetch_all(db_query($orgs_query, []));

$total_alerts = 0;
$total_expired = 0;

foreach ($orgs as $org) {
    echo "Checking organization: {$org['org_name']} (ID: {$org['org_id']})\n";

    // Find consents expiring in 30 days, 14 days, and 7 days
    $expiry_thresholds = [
        ['days' => 30, 'priority' => 'low', 'title' => 'Consent Expiring in 30 Days'],
        ['days' => 14, 'priority' => 'medium', 'title' => 'Consent Expiring in 14 Days'],
        ['days' => 7, 'priority' => 'high', 'title' => 'Consent Expiring in 7 Days'],
        ['days' => 0, 'priority' => 'critical', 'title' => 'Consent Expired Today']
    ];

    foreach ($expiry_thresholds as $threshold) {
        $query = "SELECT c.consent_id, c.subject_name, c.subject_email, c.purpose,
                  c.expiry_date, DATEDIFF(c.expiry_date, NOW()) as days_to_expiry,
                  c.created_by
                  FROM consents c
                  WHERE c.org_id = ?
                  AND c.status = 'active'
                  AND c.expiry_date IS NOT NULL
                  AND DATEDIFF(c.expiry_date, NOW()) = ?
                  AND NOT EXISTS (
                      SELECT 1 FROM notifications n
                      WHERE n.entity_type = 'consent'
                      AND n.entity_id = c.consent_id
                      AND n.message LIKE ?
                      AND DATE(n.created_at) = CURDATE()
                  )";

        $like_pattern = '%' . $threshold['title'] . '%';
        $stmt = db_query($query, [$org['org_id'], $threshold['days'], $like_pattern]);
        $expiring_consents = db_fetch_all($stmt);

        foreach ($expiring_consents as $consent) {
            echo "  - Found: {$consent['purpose']} for {$consent['subject_name']} (expires in {$consent['days_to_expiry']} days)\n";

            // Get DPO and admins for this organization
            $recipients_query = "SELECT user_id FROM users
                                WHERE org_id = ?
                                AND role IN ('Admin', 'DPO')
                                AND status = 'active'";
            $recipients = db_fetch_all(db_query($recipients_query, [$org['org_id']]));

            // Also notify the person who created the consent
            if ($consent['created_by']) {
                $recipients[] = ['user_id' => $consent['created_by']];
            }

            // Remove duplicates
            $recipients = array_unique($recipients, SORT_REGULAR);

            $message = "{$threshold['title']}: Consent '{$consent['purpose']}' for {$consent['subject_name']} " .
                      ($threshold['days'] > 0
                        ? "will expire on " . date('d M Y', strtotime($consent['expiry_date']))
                        : "has expired today") .
                      ". Consider renewal or obtaining fresh consent.";

            // Send notification to each recipient
            foreach ($recipients as $recipient) {
                create_notification(
                    $org['org_id'],
                    $recipient['user_id'],
                    'consent_expiring',
                    $threshold['title'],
                    $message,
                    'consent',
                    $consent['consent_id'],
                    "consent_view.php?id={$consent['consent_id']}",
                    $threshold['priority']
                );
                $total_alerts++;
            }

            if ($threshold['days'] == 0) {
                $total_expired++;
            }
        }
    }

    // Auto-update expired consents
    $update_query = "UPDATE consents
                    SET status = 'expired'
                    WHERE org_id = ?
                    AND status = 'active'
                    AND expiry_date IS NOT NULL
                    AND expiry_date < CURDATE()";
    $updated = db_query($update_query, [$org['org_id']]);

    if ($updated) {
        $affected = db_affected_rows();
        if ($affected > 0) {
            echo "  - Auto-expired {$affected} consent(s)\n";
        }
    }
}

echo "\n[" . date('Y-m-d H:i:s') . "] Consent expiry check completed.\n";
echo "Total notifications sent: $total_alerts\n";
echo "Total consents expired: $total_expired\n";
echo "Done.\n";
?>
