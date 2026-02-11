<?php
/**
 * DPA Tool - Edit Department
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

// Get department ID
$dept_id = intval($_GET['id'] ?? 0);

if (!$dept_id) {
    set_flash_message('Invalid department ID', 'error');
    redirect('departments.php');
}

// Get department details
$dept_query = "SELECT * FROM departments WHERE dept_id = ? AND org_id = ?";
$dept_stmt = db_query($dept_query, [$dept_id, $org_id]);
$department = db_fetch_one($dept_stmt);

if (!$department) {
    set_flash_message('Department not found', 'error');
    redirect('departments.php');
}

// Get users for department head selection
$users_query = "SELECT user_id, first_name, last_name, email FROM users WHERE org_id = ? AND status = 'active' ORDER BY first_name, last_name";
$users_stmt = db_query($users_query, [$org_id]);
$users = db_fetch_all($users_stmt);

// Get user count for this department
$count_query = "SELECT COUNT(*) as user_count FROM users WHERE dept_id = ?";
$count_stmt = db_query($count_query, [$dept_id]);
$count_result = db_fetch_one($count_stmt);
$user_count = $count_result['user_count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name = sanitize_input($_POST['dept_name'] ?? '');
    $dept_head_user_id = !empty($_POST['dept_head_user_id']) ? intval($_POST['dept_head_user_id']) : null;
    $description = sanitize_input($_POST['description'] ?? '');

    // Validation
    if (empty($dept_name)) {
        $errors[] = 'Department name is required';
    } else {
        // Check if department name already exists (excluding current department)
        $check_query = "SELECT dept_id FROM departments WHERE org_id = ? AND dept_name = ? AND dept_id != ?";
        $check_stmt = db_query($check_query, [$org_id, $dept_name, $dept_id]);
        if (db_fetch_one($check_stmt)) {
            $errors[] = 'Department name already exists';
        }
    }

    if (empty($errors)) {
        $query = "UPDATE departments SET
                  dept_name = ?,
                  dept_head_user_id = ?,
                  description = ?
                  WHERE dept_id = ? AND org_id = ?";

        $stmt = db_query($query, [$dept_name, $dept_head_user_id, $description, $dept_id, $org_id]);

        if ($stmt) {
            log_audit($org_id, get_current_user_id(), 'department', $dept_id, 'update');
            set_flash_message('Department updated successfully', 'success');
            redirect('departments.php');
        } else {
            $errors[] = 'Failed to update department';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Edit Department</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
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
                        <h2>Edit Department</h2>
                        <h5>Update department information</h5>
                    </div>
                </div>
                <hr />

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-edit"></i> Department Information
                            </div>
                            <div class="card-body">
                                <form method="post" action="departments_edit.php?id=<?php echo $dept_id; ?>">
                                    <div class="form-group">
                                        <label>Department Name <span class="text-danger">*</span></label>
                                        <input type="text" name="dept_name" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['dept_name'] ?? $department['dept_name']); ?>"
                                               placeholder="e.g., Human Resources, IT, Finance"
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label>Department Head</label>
                                        <select name="dept_head_user_id" class="form-control">
                                            <option value="">-- Select Department Head --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['user_id']; ?>"
                                                    <?php
                                                    $selected_head = isset($_POST['dept_head_user_id']) ? $_POST['dept_head_user_id'] : $department['dept_head_user_id'];
                                                    echo ($selected_head == $user['user_id']) ? 'selected' : '';
                                                    ?>>
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text">Select the user responsible for this department (optional)</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="4"
                                                  placeholder="Describe the department's role and responsibilities"><?php echo htmlspecialchars($_POST['description'] ?? $department['description']); ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Department
                                        </button>
                                        <a href="departments.php" class="btn btn-secondary">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-header">
                                <i class="fa fa-info-circle"></i> Department Details
                            </div>
                            <div class="card-body">
                                <dl>
                                    <dt>Department ID</dt>
                                    <dd><?php echo $department['dept_id']; ?></dd>

                                    <dt>Users in Department</dt>
                                    <dd>
                                        <span class="badge badge-info"><?php echo $user_count; ?></span>
                                        <?php echo $user_count == 1 ? 'user' : 'users'; ?>
                                    </dd>

                                    <dt>Created</dt>
                                    <dd><?php echo format_datetime($department['created_at'], 'd M Y H:i'); ?></dd>

                                    <dt>Last Updated</dt>
                                    <dd><?php echo format_datetime($department['updated_at'], 'd M Y H:i'); ?></dd>
                                </dl>
                            </div>
                        </div>

                        <?php if ($user_count > 0): ?>
                        <div class="card border-warning">
                            <div class="card-header">
                                <i class="fa fa-exclamation-triangle"></i> Warning
                            </div>
                            <div class="card-body">
                                <p><strong>Note:</strong> This department has <?php echo $user_count; ?> <?php echo $user_count == 1 ? 'user' : 'users'; ?> assigned to it.</p>
                                <p>Deleting this department will unassign these users.</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
</body>
</html>
