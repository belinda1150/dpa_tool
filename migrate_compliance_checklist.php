<?php
/**
 * Migration Script - Create compliance_responses table
 * Run once: http://localhost/data_protection/migrate_compliance_checklist.php
 */

require_once 'config/database.php';

try {
    // Check if table already exists
    $check = $dpa_db->query("SHOW TABLES LIKE 'compliance_responses'");
    if ($check->num_rows > 0) {
        echo "Table 'compliance_responses' already exists.<br>";
    } else {
        $sql = "CREATE TABLE compliance_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            org_id INT NOT NULL,
            item_key VARCHAR(50) NOT NULL,
            response ENUM('yes','no','partial','na') DEFAULT NULL,
            notes TEXT,
            updated_by INT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_org_item (org_id, item_key),
            FOREIGN KEY (org_id) REFERENCES organizations(org_id),
            FOREIGN KEY (updated_by) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $dpa_db->query($sql);

        if ($dpa_db->error) {
            echo "Error creating table: " . $dpa_db->error . "<br>";
        } else {
            echo "Successfully created 'compliance_responses' table.<br>";
        }
    }

    echo "<br><strong>Migration completed!</strong><br>";
    echo "<a href='compliance_checklist.php'>Go to CDPA Compliance Checklist</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
