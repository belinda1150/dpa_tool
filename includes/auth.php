<?php
/**
 * DPA Tool - Authentication Functions
 * Version: 1.0
 * Date: October 2025
 */

require_once dirname(__DIR__) . '/config/config.php';

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['dpa_user_id']) && isset($_SESSION['dpa_org_id']);
}

// Get current user ID
function get_current_user_id() {
    return $_SESSION['dpa_user_id'] ?? null;
}

// Get current organization ID
function get_current_org_id() {
    return $_SESSION['dpa_org_id'] ?? null;
}

// Get current user role
function get_current_user_role() {
    return $_SESSION['dpa_role_name'] ?? null;
}

// Get current user department
function get_current_dept_id() {
    return $_SESSION['dpa_dept_id'] ?? null;
}

// Get current user data
function get_current_dpa_user() {
    if (!is_logged_in()) {
        return null;
    }

    $user_id = get_current_user_id();
    $query = "SELECT u.*, r.role_name, o.org_name, d.dept_name
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.role_id
              LEFT JOIN organizations o ON u.org_id = o.org_id
              LEFT JOIN departments d ON u.dept_id = d.dept_id
              WHERE u.user_id = ?";

    $stmt = db_query($query, [$user_id]);
    return db_fetch_one($stmt);
}

// Login user
function login_user($email, $password) {
    $query = "SELECT u.*, r.role_name
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.role_id
              WHERE u.email = ? AND u.status = 'active'";

    $stmt = db_query($query, [$email]);
    $user = db_fetch_one($stmt);

    // Verify password - support both bcrypt hashes and plaintext (for migration)
    $password_valid = false;
    $needs_hash_update = false;

    if ($user) {
        // Check if password is already hashed (bcrypt format)
        if (substr($user['password'], 0, 4) === '$2y$') {
            // Use bcrypt verification for hashed passwords
            $password_valid = password_verify($password, $user['password']);
        } else {
            // Direct comparison for plaintext passwords (migration support)
            $password_valid = ($password === $user['password']);
            $needs_hash_update = true; // Flag to hash the password after login
        }
    }

    if ($password_valid) {
        // If password was plaintext, hash it now for security
        if ($needs_hash_update) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $hash_query = "UPDATE users SET password = ? WHERE user_id = ?";
            db_query($hash_query, [$hashed_password, $user['user_id']]);
        }

        // Set session variables
        $_SESSION['dpa_user_id'] = $user['user_id'];
        $_SESSION['dpa_org_id'] = $user['org_id'];
        $_SESSION['dpa_role_id'] = $user['role_id'];
        $_SESSION['dpa_role_name'] = $user['role_name'];
        $_SESSION['dpa_dept_id'] = $user['dept_id'];
        $_SESSION['dpa_user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['dpa_user_email'] = $user['email'];
        $_SESSION['dpa_last_activity'] = time();

        // Update last login
        $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        db_query($update_query, [$user['user_id']]);

        // Log audit
        log_audit($user['org_id'], $user['user_id'], 'user', $user['user_id'], 'login');

        // Check if password reset is required
        if ($user['password_reset_required'] == 1) {
            // Redirect to password reset page
            $_SESSION['dpa_password_reset_required'] = true;
        }

        return true;
    }

    return false;
}

// Logout user
function logout_user() {
    if (is_logged_in()) {
        $org_id = get_current_org_id();
        $user_id = get_current_user_id();

        // Log audit
        log_audit($org_id, $user_id, 'user', $user_id, 'logout');

        // Destroy session
        session_unset();
        session_destroy();
    }
}

// Check session timeout
function check_session_timeout() {
    if (isset($_SESSION['dpa_last_activity'])) {
        $inactive_time = time() - $_SESSION['dpa_last_activity'];

        if ($inactive_time > SESSION_TIMEOUT) {
            logout_user();
            return false;
        }
    }

    $_SESSION['dpa_last_activity'] = time();
    return true;
}

// Require login
function require_login() {
    if (!is_logged_in() || !check_session_timeout()) {
        redirect('../index.php?status=Session%20Expired');
        exit();
    }
}

// Check permission
function has_permission($required_role) {
    $current_role = get_current_user_role();

    $role_hierarchy = [
        'Admin' => 5,
        'DPO' => 4,
        'Department Owner' => 3,
        'Staff' => 2,
        'Auditor' => 1
    ];

    $current_level = $role_hierarchy[$current_role] ?? 0;
    $required_level = $role_hierarchy[$required_role] ?? 0;

    return $current_level >= $required_level;
}

// Require permission
function require_permission($required_role) {
    if (!has_permission($required_role)) {
        set_flash_message('You do not have permission to access this page.', 'error');
        redirect('dashboard.php');
        exit();
    }
}

// Require specific role(s) - accepts array of allowed roles
function require_role($allowed_roles) {
    $current_role = get_current_user_role();

    // Convert to array if single role provided
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // Check if user's role is in allowed roles
    if (!in_array($current_role, $allowed_roles)) {
        set_flash_message('You do not have permission to access this page.', 'error');
        redirect('dashboard.php');
        exit();
    }
}

// Check if user is DPO
function is_dpo() {
    return get_current_user_role() === 'DPO' || get_current_user_role() === 'Admin';
}

// Check if user is Admin
function is_admin() {
    return get_current_user_role() === 'Admin';
}

// Register new user
function register_user($data) {
    // Hash password with bcrypt
    $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);

    $query = "INSERT INTO users (org_id, role_id, dept_id, first_name, last_name, email, password, phone, status)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')";

    $stmt = db_query($query, [
        $data['org_id'],
        $data['role_id'],
        $data['dept_id'] ?? null,
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $hashed_password,
        $data['phone'] ?? null
    ]);

    if ($stmt) {
        $user_id = db_insert_id();
        log_audit($data['org_id'], get_current_user_id(), 'user', $user_id, 'create');
        return $user_id;
    }

    return false;
}

// Update user
function update_user($user_id, $data) {
    $query = "UPDATE users SET
              role_id = ?,
              dept_id = ?,
              first_name = ?,
              last_name = ?,
              phone = ?,
              status = ?
              WHERE user_id = ?";

    $stmt = db_query($query, [
        $data['role_id'],
        $data['dept_id'] ?? null,
        $data['first_name'],
        $data['last_name'],
        $data['phone'] ?? null,
        $data['status'] ?? 'active',
        $user_id
    ]);

    if ($stmt) {
        log_audit(get_current_org_id(), get_current_user_id(), 'user', $user_id, 'update');
        return true;
    }

    return false;
}

// Change password
function change_password($user_id, $new_password) {
    // Hash password with bcrypt
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $query = "UPDATE users SET password = ?, password_reset_required = 0 WHERE user_id = ?";
    $stmt = db_query($query, [$hashed_password, $user_id]);

    if ($stmt) {
        log_audit(get_current_org_id(), get_current_user_id(), 'user', $user_id, 'update', null, ['action' => 'password_changed']);
        return true;
    }

    return false;
}

// Get all users for organization
function get_org_users($org_id = null) {
    if ($org_id === null) {
        $org_id = get_current_org_id();
    }

    $query = "SELECT u.*, r.role_name, d.dept_name
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.role_id
              LEFT JOIN departments d ON u.dept_id = d.dept_id
              WHERE u.org_id = ?
              ORDER BY u.last_name, u.first_name";

    $stmt = db_query($query, [$org_id]);
    return db_fetch_all($stmt);
}

// Get user by ID
function get_user_by_id($user_id) {
    $query = "SELECT u.*, r.role_name, d.dept_name
              FROM users u
              LEFT JOIN roles r ON u.role_id = r.role_id
              LEFT JOIN departments d ON u.dept_id = d.dept_id
              WHERE u.user_id = ?";

    $stmt = db_query($query, [$user_id]);
    return db_fetch_one($stmt);
}

// Delete user
function delete_user($user_id) {
    $query = "UPDATE users SET status = 'inactive' WHERE user_id = ?";
    $stmt = db_query($query, [$user_id]);

    if ($stmt) {
        log_audit(get_current_org_id(), get_current_user_id(), 'user', $user_id, 'delete');
        return true;
    }

    return false;
}
?>
