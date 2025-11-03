<?php
/**
 * DPA Tool - User Management
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin'); // Only Admin can manage users

$org_id = get_current_org_id();

// Get all users with their roles and departments
$query = "SELECT u.*, r.role_name, d.dept_name, o.org_name
          FROM users u
          LEFT JOIN roles r ON u.role_id = r.role_id
          LEFT JOIN departments d ON u.dept_id = d.dept_id
          LEFT JOIN organizations o ON u.org_id = o.org_id
          WHERE u.org_id = ?
          ORDER BY u.last_name, u.first_name";
$stmt = db_query($query, [$org_id]);
$users = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - User Management</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
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
                        <h2>User Management</h2>
                        <h5>Manage system users and access control</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-users"></i> User Accounts
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="users_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New User
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="usersTable">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Role</th>
                                                <th>Department</th>
                                                <th>Status</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'N/A'; ?></td>
                                                <td>
                                                    <?php
                                                    $role_class = '';
                                                    switch ($user['role_name']) {
                                                        case 'Admin':
                                                            $role_class = 'danger';
                                                            break;
                                                        case 'DPO':
                                                            $role_class = 'primary';
                                                            break;
                                                        case 'Department Owner':
                                                            $role_class = 'info';
                                                            break;
                                                        case 'Staff':
                                                            $role_class = 'default';
                                                            break;
                                                        case 'Auditor':
                                                            $role_class = 'warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $role_class; ?>">
                                                        <?php echo htmlspecialchars($user['role_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['dept_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($user['status']) {
                                                        case 'active':
                                                            $status_class = 'success';
                                                            break;
                                                        case 'inactive':
                                                            $status_class = 'default';
                                                            break;
                                                        case 'locked':
                                                            $status_class = 'danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($user['last_login']) {
                                                        echo format_datetime($user['last_login'], 'd M Y H:i');
                                                    } else {
                                                        echo '<span class="text-muted">Never</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <a href="users_edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-xs" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <?php if ($user['user_id'] != get_current_user_id()): ?>
                                                    <a href="users_delete.php?id=<?php echo $user['user_id']; ?>" class="btn btn-danger btn-xs" title="Deactivate"
                                                       onclick="return confirm('Are you sure you want to deactivate this user?');">
                                                        <i class="fa fa-ban"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
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

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').dataTable({
                "order": [[0, "asc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
