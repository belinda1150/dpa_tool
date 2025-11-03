<?php
/**
 * DPA Tool - Database Configuration
 * Version: 1.0
 * Date: October 2025
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'dpa_tool');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Create database connection
try {
    $dpa_db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($dpa_db->connect_error) {
        die("Connection failed: " . $dpa_db->connect_error);
    }

    // Set charset
    $dpa_db->set_charset(DB_CHARSET);

} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Function to execute queries safely
function db_query($query, $params = []) {
    global $dpa_db;

    $stmt = $dpa_db->prepare($query);

    if (!$stmt) {
        return ['error' => $dpa_db->error];
    }

    if (!empty($params)) {
        $types = '';
        $values = [];

        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $param;
        }

        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();

    return $stmt;
}

// Function to fetch all results
function db_fetch_all($stmt) {
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch single row
function db_fetch_one($stmt) {
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get last insert ID
function db_insert_id() {
    global $dpa_db;
    return $dpa_db->insert_id;
}

// Function to escape strings
function db_escape($string) {
    global $dpa_db;
    return $dpa_db->real_escape_string($string);
}

// Function to close connection
function db_close() {
    global $dpa_db;
    $dpa_db->close();
}
?>
