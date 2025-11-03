<?php
/**
 * DPA Tool - Add New Department
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

// Get users for department head selection
$users_query = "SELECT user_id, first_name, last_name, email FROM users WHERE org_id = ? AND status = 'active' ORDER BY first_name, last_name";
$users_stmt = db_query($users_query, [$org_id]);
$users = db_fetch_all($users_stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name = sanitize_input($_POST['dept_name'] ?? '');
    $dept_head_user_id = !empty($_POST['dept_head_user_id']) ? intval($_POST['dept_head_user_id']) : null;
    $description = sanitize_input($_POST['description'] ?? '');

    // Validation
    if (empty($dept_name)) {
        $errors[] = 'Department name is required';
    } else {
        // Check if department name already exists in this organization
        $check_query = "SELECT dept_id FROM departments WHERE org_id = ? AND dept_name = ?";
        $check_stmt = db_query($check_query, [$org_id, $dept_name]);
        if (db_fetch_one($check_stmt)) {
            $errors[] = 'Department name already exists';
        }
    }

    if (empty($errors)) {
        $query = "INSERT INTO departments (org_id, dept_name, dept_head_user_id, description)
                  VALUES (?, ?, ?, ?)";

        $stmt = db_query($query, [$org_id, $dept_name, $dept_head_user_id, $description]);

        if ($stmt) {
            $dept_id = db_insert_id();
            log_audit($org_id, get_current_user_id(), 'department', $dept_id, 'create');
            set_flash_message('Department created successfully', 'success');
            redirect('departments.php');
        } else {
            $errors[] = 'Failed to create department';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add New Department</title>
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
                        <h2>Add New Department</h2>
                        <h5>Create a new organizational department</h5>
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
                                <i class="fa fa-building"></i> Department Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="departments_add.php">
                                    <div class="form-group">
                                        <label>Department Name <span class="text-danger">*</span></label>
                                        <input type="text" name="dept_name" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['dept_name'] ?? ''); ?>"
                                               placeholder="e.g., Human Resources, IT, Finance"
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label>Department Head</label>
                                        <select name="dept_head_user_id" class="form-control">
                                            <option value="">-- Select Department Head --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['user_id']; ?>"
                                                    <?php echo (isset($_POST['dept_head_user_id']) && $_POST['dept_head_user_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="help-block">Select the user responsible for this department (optional)</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="4"
                                                  placeholder="Describe the department's role and responsibilities"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Create Department
                                        </button>
                                        <a href="departments.php" class="btn btn-default">
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
                                <i class="fa fa-info-circle"></i> About Departments
                            </div>
                            <div class="panel-body">
                                <p><strong>Departments</strong> help organize your data protection activities by business unit.</p>

                                <p><strong>Benefits:</strong></p>
                                <ul>
                                    <li>Assign users to departments</li>
                                    <li>Track ROPA entries by department</li>
                                    <li>Filter risks and incidents</li>
                                    <li>Generate department-specific reports</li>
                                </ul>

                                <p class="text-muted"><small><i class="fa fa-lightbulb-o"></i> Tip: Assign department heads to enable better accountability and oversight.</small></p>
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
