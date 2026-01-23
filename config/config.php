<?php
/**
 * DPA Tool - Main Configuration
 * Version: 1.0
 * Date: October 2025
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application settings
define('APP_NAME', 'Bantu DPO');
define('APP_VERSION', '1.0');
define('APP_URL', 'http://localhost/data_protection/');
define('APP_TIMEZONE', 'Africa/Harare');

// Set timezone
date_default_timezone_set(APP_TIMEZONE);

// Security settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('ENABLE_2FA', false);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 10485760); // 10MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png']);

// Compliance settings
define('DSR_SLA_DAYS', 30);
define('BREACH_NOTIFICATION_HOURS', 72);
define('CONSENT_EXPIRY_ALERT_DAYS', 30);
define('DPIA_THRESHOLD_SUBJECTS', 10000);
define('RISK_ACCEPTABLE_THRESHOLD', 6);

// Vendor Risk Management settings
define('VENDOR_REVIEW_ALERT_DAYS', 30);
define('VENDOR_DPA_EXPIRY_ALERT_DAYS', 60);
define('VENDOR_CONTRACT_EXPIRY_ALERT_DAYS', 60);
define('VENDOR_HIGH_RISK_THRESHOLD', 15);
define('VENDOR_CRITICAL_RISK_THRESHOLD', 20);

// Email settings (comment out if not using external email config)
// require_once dirname(dirname(__DIR__)) . '/config/email.php';

// Database connection
require_once 'database.php';

// Error reporting (disable in production)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Helper functions
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function sanitize_input($data) {
    if ($data === null || $data === '') {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function safe_html($value, $default = '') {
    // Safe HTML output - handles NULL values for PHP 8.1+
    if ($value === null || $value === '') {
        return $default;
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_date($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

function calculate_due_date($start_date, $days) {
    return date('Y-m-d', strtotime($start_date . ' + ' . $days . ' days'));
}

function is_overdue($due_date) {
    return strtotime($due_date) < time();
}

function days_until($date) {
    $now = time();
    $target = strtotime($date);
    return floor(($target - $now) / (60 * 60 * 24));
}

// Generate unique reference number
function generate_reference($prefix = 'REF') {
    return $prefix . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

// Check if file type is allowed
function is_allowed_file($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_FILE_TYPES);
}

// Calculate file hash
function calculate_file_hash($filepath) {
    return hash_file('sha256', $filepath);
}

// Log audit trail
function log_audit($org_id, $user_id, $entity_type, $entity_id, $action, $old_values = null, $new_values = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $query = "INSERT INTO audit_log (org_id, user_id, entity_type, entity_id, action, old_values, new_values, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $old_json = $old_values ? json_encode($old_values) : null;
    $new_json = $new_values ? json_encode($new_values) : null;

    db_query($query, [$org_id, $user_id, $entity_type, $entity_id, $action, $old_json, $new_json, $ip_address, $user_agent]);
}

// Create notification
function create_notification($org_id, $user_id, $type, $title, $message, $entity_type = null, $entity_id = null, $link_url = null, $priority = 'medium') {
    $query = "INSERT INTO notifications (org_id, user_id, notification_type, title, message, entity_type, entity_id, link_url, priority)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    db_query($query, [$org_id, $user_id, $type, $title, $message, $entity_type, $entity_id, $link_url, $priority]);
}
?>
