<?php
/**
 * Migration Script - Add profile_picture column
 * Run this file once in your browser: http://localhost/data_protection/run_migration.php
 */

require_once 'config/database.php';

try {
    // Check if column already exists
    $check_query = "SHOW COLUMNS FROM users LIKE 'profile_picture'";
    $result = db_query($check_query);
    $column_exists = db_fetch_one($result);

    if ($column_exists) {
        echo "Column 'profile_picture' already exists in users table.<br>";
    } else {
        // Add the column
        $alter_query = "ALTER TABLE users ADD COLUMN profile_picture VARCHAR(500) NULL DEFAULT NULL AFTER phone";
        db_query($alter_query);
        echo "Successfully added 'profile_picture' column to users table.<br>";
    }

    echo "<br><strong>Migration completed successfully!</strong><br>";
    echo "<a href='users.php'>Go to Users Management</a>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
