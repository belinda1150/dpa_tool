<?php
/**
 * DPA Tool - ROPA List
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();

// Get all ROPA entries
$query = "SELECT pa.*, d.dept_name, p.purpose_name, lb.basis_name, u.first_name, u.last_name
          FROM processing_activities pa
          LEFT JOIN departments d ON pa.dept_id = d.dept_id
          LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
          LEFT JOIN lawful_basis lb ON pa.lawful_basis_id = lb.lawful_basis_id
          LEFT JOIN users u ON pa.created_by = u.user_id
          WHERE pa.org_id = ?
          ORDER BY pa.created_at DESC";
$stmt = db_query($query, [$org_id]);
$ropa_entries = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - ROPA Register</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap.css?v=2" rel="stylesheet" />
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
                        <h2>Record of Processing Activities (ROPA)</h2>
                        <h5>Manage all personal data processing activities - CDPA s.24-25</h5>
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
                                        <i class="fa fa-list-alt"></i> Processing Activities Register
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="ropa_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New Activity
                                        </a>
                                        <a href="ropa_export.php" class="btn btn-success btn-sm">
                                            <i class="fa fa-download"></i> Export Register
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="ropaTable">
                                        <thead>
                                            <tr>
                                                <th>Activity Name</th>
                                                <th>Department</th>
                                                <th>Purpose</th>
                                                <th>Lawful Basis</th>
                                                <th>Status</th>
                                                <th>Flags</th>
                                                <th>Created By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ropa_entries as $entry): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($entry['activity_name']); ?></td>
                                                <td><?php echo htmlspecialchars($entry['dept_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($entry['purpose_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php if ($entry['basis_name']): ?>
                                                        <span class="label label-info"><?php echo htmlspecialchars($entry['basis_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="label label-danger">Missing</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($entry['status']) {
                                                        case 'validated':
                                                            $status_class = 'success';
                                                            break;
                                                        case 'draft':
                                                            $status_class = 'warning';
                                                            break;
                                                        case 'archived':
                                                            $status_class = 'default';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($entry['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($entry['has_special_categories']): ?>
                                                        <span class="label label-danger" title="Contains Special Categories">
                                                            <i class="fa fa-exclamation-circle"></i> Special
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($entry['has_minors']): ?>
                                                        <span class="label label-warning" title="Involves Minors">
                                                            <i class="fa fa-child"></i> Minors
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($entry['has_cross_border']): ?>
                                                        <span class="label label-info" title="Cross-Border Transfer">
                                                            <i class="fa fa-globe"></i> X-Border
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
                                                <td>
                                                    <a href="ropa_view.php?id=<?php echo $entry['ropa_id']; ?>" class="btn btn-info btn-xs" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="ropa_edit.php?id=<?php echo $entry['ropa_id']; ?>" class="btn btn-warning btn-xs" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <?php if (is_dpo()): ?>
                                                    <a href="ropa_delete.php?id=<?php echo $entry['ropa_id']; ?>" class="btn btn-danger btn-xs" title="Archive"
                                                       onclick="return confirm('Are you sure you want to archive this entry?');">
                                                        <i class="fa fa-archive"></i>
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
            $('#ropaTable').dataTable({
                "order": [[0, "asc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
