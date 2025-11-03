<?php
/**
 * DPA Tool - Add New User
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin');

$org_id = get_current_org_id();
$errors = [];

// Get roles
$roles_query = "SELECT * FROM roles ORDER BY role_name";
$roles_stmt = db_query($roles_query);
$roles = db_fetch_all($roles_stmt);

// Get departments
$dept_query = "SELECT * FROM departments WHERE org_id = ? ORDER BY dept_name";
$dept_stmt = db_query($dept_query, [$org_id]);
$departments = db_fetch_all($dept_stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $dept_id = !empty($_POST['dept_id']) ? intval($_POST['dept_id']) : null;
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        // Check if email already exists
        $check_query = "SELECT user_id FROM users WHERE email = ?";
        $check_stmt = db_query($check_query, [$email]);
        if (db_fetch_one($check_stmt)) {
            $errors[] = 'Email already exists';
        }
    }
    if (empty($role_id)) {
        $errors[] = 'Role is required';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $data = [
            'org_id' => $org_id,
            'role_id' => $role_id,
            'dept_id' => $dept_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ];

        $user_id = register_user($data);

        if ($user_id) {
            set_flash_message('User created successfully', 'success');
            redirect('users.php');
        } else {
            $errors[] = 'Failed to create user';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add New User</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Add New User</h2>
                        <h5>Create a new system user account</h5>
                    </div>
                </div>
                <hr />

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-user-plus"></i> User Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="users_add.php">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" name="first_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Email <span class="text-danger">*</span></label>
                                                <input type="email" name="email" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Phone</label>
                                                <input type="text" name="phone" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Role <span class="text-danger">*</span></label>
                                                <select name="role_id" class="form-control" required>
                                                    <option value="">-- Select Role --</option>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo $role['role_id']; ?>"
                                                            <?php echo (isset($_POST['role_id']) && $_POST['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($role['role_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="help-block">Assign user role and permissions</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <select name="dept_id" class="form-control">
                                                    <option value="">-- Select Department --</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                        <option value="<?php echo $dept['dept_id']; ?>"
                                                            <?php echo (isset($_POST['dept_id']) && $_POST['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Password <span class="text-danger">*</span></label>
                                                <input type="password" name="password" class="form-control"
                                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                                <small class="help-block">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Confirm Password <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" class="form-control"
                                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Create User
                                        </button>
                                        <a href="users.php" class="btn btn-default">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> Role Descriptions
                            </div>
                            <div class="panel-body">
                                <dl>
                                    <dt>Admin</dt>
                                    <dd>Full system access and configuration</dd>

                                    <dt>DPO</dt>
                                    <dd>Data Protection Officer - Approvals and oversight</dd>

                                    <dt>Department Owner</dt>
                                    <dd>Department-level data management</dd>

                                    <dt>Staff</dt>
                                    <dd>Basic data entry and viewing</dd>

                                    <dt>Auditor</dt>
                                    <dd>Read-only access for auditing</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
