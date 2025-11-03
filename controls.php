<?php
/**
 * DPA Tool - Controls Management
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('DPO'); // DPO and Admin can manage controls

$org_id = get_current_org_id();

// Get all controls with usage count
$query = "SELECT c.*,
          (SELECT COUNT(*) FROM risk_controls rc WHERE rc.control_id = c.control_id) AS usage_count
          FROM controls c
          WHERE c.org_id = ?
          ORDER BY c.control_name";
$stmt = db_query($query, [$org_id]);
$controls = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Control Library</title>
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
                        <h2>Control Library</h2>
                        <h5>Manage security and compliance controls</h5>
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
                                        <i class="fa fa-shield"></i> Security Controls
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="controls_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New Control
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="controlsTable">
                                        <thead>
                                            <tr>
                                                <th>Control Name</th>
                                                <th>Type</th>
                                                <th>Framework Reference</th>
                                                <th>Description</th>
                                                <th>Used In Risks</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($controls)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">
                                                    <i class="fa fa-info-circle"></i> No controls found. Click "Add New Control" to create one.
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($controls as $control): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($control['control_name']); ?></strong></td>
                                                <td>
                                                    <?php
                                                    $type_class = '';
                                                    switch ($control['control_type']) {
                                                        case 'preventive':
                                                            $type_class = 'primary';
                                                            break;
                                                        case 'detective':
                                                            $type_class = 'info';
                                                            break;
                                                        case 'corrective':
                                                            $type_class = 'warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $type_class; ?>">
                                                        <?php echo ucfirst($control['control_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($control['framework_ref'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars(substr($control['control_description'] ?? '', 0, 100)); ?><?php echo strlen($control['control_description'] ?? '') > 100 ? '...' : ''; ?></td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $control['usage_count']; ?></span>
                                                    <?php echo $control['usage_count'] == 1 ? 'risk' : 'risks'; ?>
                                                </td>
                                                <td>
                                                    <a href="controls_edit.php?id=<?php echo $control['control_id']; ?>" class="btn btn-warning btn-xs" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <?php if ($control['usage_count'] == 0): ?>
                                                    <a href="controls_delete.php?id=<?php echo $control['control_id']; ?>" class="btn btn-danger btn-xs" title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this control?');">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <button class="btn btn-danger btn-xs" disabled title="Cannot delete - in use">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
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

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        $(document).ready(function() {
            $('#controlsTable').dataTable({
                "order": [[0, "asc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
