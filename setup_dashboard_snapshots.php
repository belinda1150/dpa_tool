<?php
/**
 * Setup Dashboard Snapshots Table
 * Run this once to create the table and populate initial data
 */

require_once 'config/database.php';
require_once 'config/config.php';

echo "<h2>Setting up Dashboard Snapshots</h2>";
echo "<pre>";

// Create table
echo "Creating dashboard_snapshots table...\n";

$create_table_sql = "CREATE TABLE IF NOT EXISTS `dashboard_snapshots` (
  `snapshot_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` int(11) UNSIGNED NOT NULL,
  `snapshot_date` date NOT NULL,
  `compliance_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_ropa` int(11) DEFAULT 0,
  `validated_ropa` int(11) DEFAULT 0,
  `total_dpia` int(11) DEFAULT 0,
  `approved_dpia` int(11) DEFAULT 0,
  `total_risks` int(11) DEFAULT 0,
  `open_risks` int(11) DEFAULT 0,
  `total_incidents` int(11) DEFAULT 0,
  `active_incidents` int(11) DEFAULT 0,
  `total_dsr` int(11) DEFAULT 0,
  `pending_dsr` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`snapshot_id`),
  UNIQUE KEY `unique_org_date` (`org_id`, `snapshot_date`),
  KEY `org_id` (`org_id`),
  KEY `snapshot_date` (`snapshot_date`),
  CONSTRAINT `dashboard_snapshots_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `organizations` (`org_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

try {
    $dpa_db->query($create_table_sql);
    echo "✓ Table created successfully!\n\n";
} catch (Exception $e) {
    echo "✓ Table already exists or error: " . $e->getMessage() . "\n\n";
}

// Run initial snapshot
echo "Running initial snapshot...\n";
include 'cron_daily_snapshot.php';

echo "\n</pre>";
echo "<h3 style='color: green;'>✓ Setup Complete!</h3>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>
