<?php
/**
 * DPA Tool - Department Management
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin'); // Only Admin can manage departments

$org_id = get_current_org_id();

// Get all departments with department head information
$query = "SELECT d.*,
          u.first_name AS head_first_name,
          u.last_name AS head_last_name,
          (SELECT COUNT(*) FROM users WHERE dept_id = d.dept_id) AS user_count
          FROM departments d
          LEFT JOIN users u ON d.dept_head_user_id = u.user_id
          WHERE d.org_id = ?
          ORDER BY d.dept_name";
$stmt = db_query($query, [$org_id]);
$departments = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Department Management</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap5.css?v=2" rel="stylesheet" />
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
                        <h2>Department Management</h2>
                        <h5>Manage organizational departments</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-building"></i> Departments
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <a href="departments_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New Department
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="departmentsTable">
                                        <thead>
                                            <tr>
                                                <th>Department Name</th>
                                                <th>Department Head</th>
                                                <th>Description</th>
                                                <th>Users</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($departments)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    <i class="fa fa-info-circle"></i> No departments found. Click "Add New Department" to create one.
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($departments as $dept): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($dept['dept_name']); ?></strong></td>
                                                <td>
                                                    <?php
                                                    if ($dept['head_first_name']) {
                                                        echo htmlspecialchars($dept['head_first_name'] . ' ' . $dept['head_last_name']);
                                                    } else {
                                                        echo '<span class="text-muted">Not assigned</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars(substr($dept['description'] ?? 'N/A', 0, 100)); ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $dept['user_count']; ?></span>
                                                    <?php echo $dept['user_count'] == 1 ? 'user' : 'users'; ?>
                                                </td>
                                                <td><?php echo format_date($dept['created_at'], 'd M Y'); ?></td>
                                                <td>
                                                    <a href="departments_edit.php?id=<?php echo $dept['dept_id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="departments_delete.php?id=<?php echo $dept['dept_id']; ?>" class="btn btn-danger btn-sm" title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this department? Users in this department will be unassigned.');">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap5.js"></script>
    <script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
    <script>
        $(document).ready(function() {
            $('#departmentsTable').dataTable({
                "order": [[0, "asc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
