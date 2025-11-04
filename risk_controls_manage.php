<?php
/**
 * DPA Tool - Manage Risk Controls
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Get risk ID
$risk_id = intval($_GET['id'] ?? 0);

if (!$risk_id) {
    set_flash_message('Invalid risk ID', 'error');
    redirect('risk_list.php');
}

// Get risk details
$risk_query = "SELECT r.*, d.dept_name FROM risks r
               LEFT JOIN departments d ON r.dept_id = d.dept_id
               WHERE r.risk_id = ? AND r.org_id = ?";
$risk_stmt = db_query($risk_query, [$risk_id, $org_id]);
$risk = db_fetch_one($risk_stmt);

if (!$risk) {
    set_flash_message('Risk not found', 'error');
    redirect('risk_list.php');
}

// Handle adding control
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $control_id = intval($_POST['control_id'] ?? 0);
    $implementation_status = sanitize_input($_POST['implementation_status'] ?? 'planned');
    $effectiveness = !empty($_POST['effectiveness']) ? sanitize_input($_POST['effectiveness']) : null;
    $notes = sanitize_input($_POST['notes'] ?? '');

    if ($control_id) {
        // Check if already linked
        $check_query = "SELECT * FROM risk_controls WHERE risk_id = ? AND control_id = ?";
        $check_stmt = db_query($check_query, [$risk_id, $control_id]);

        if (!db_fetch_one($check_stmt)) {
            $insert_query = "INSERT INTO risk_controls (risk_id, control_id, implementation_status, effectiveness, notes)
                            VALUES (?, ?, ?, ?, ?)";
            db_query($insert_query, [$risk_id, $control_id, $implementation_status, $effectiveness, $notes]);

            log_audit($org_id, $user_id, 'risk_control', $risk_id, 'create');
            set_flash_message('Control linked successfully', 'success');
        } else {
            set_flash_message('Control already linked to this risk', 'error');
        }
    }
    redirect("risk_controls_manage.php?id=$risk_id");
}

// Handle updating control
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $risk_control_id = intval($_POST['risk_control_id'] ?? 0);
    $implementation_status = sanitize_input($_POST['implementation_status'] ?? 'planned');
    $effectiveness = !empty($_POST['effectiveness']) ? sanitize_input($_POST['effectiveness']) : null;
    $verification_date = !empty($_POST['verification_date']) ? $_POST['verification_date'] : null;
    $notes = sanitize_input($_POST['notes'] ?? '');

    if ($risk_control_id) {
        $update_query = "UPDATE risk_controls SET
                        implementation_status = ?,
                        effectiveness = ?,
                        verification_date = ?,
                        notes = ?
                        WHERE risk_control_id = ? AND risk_id = ?";
        db_query($update_query, [$implementation_status, $effectiveness, $verification_date, $notes, $risk_control_id, $risk_id]);

        log_audit($org_id, $user_id, 'risk_control', $risk_id, 'update');
        set_flash_message('Control updated successfully', 'success');
    }
    redirect("risk_controls_manage.php?id=$risk_id");
}

// Handle removing control
if (isset($_GET['remove'])) {
    $risk_control_id = intval($_GET['remove']);
    $delete_query = "DELETE FROM risk_controls WHERE risk_control_id = ? AND risk_id = ?";
    db_query($delete_query, [$risk_control_id, $risk_id]);

    log_audit($org_id, $user_id, 'risk_control', $risk_id, 'delete');
    set_flash_message('Control removed successfully', 'success');
    redirect("risk_controls_manage.php?id=$risk_id");
}

// Get linked controls
$linked_query = "SELECT rc.*, c.control_name, c.control_type, c.framework_ref
                FROM risk_controls rc
                JOIN controls c ON rc.control_id = c.control_id
                WHERE rc.risk_id = ?
                ORDER BY c.control_name";
$linked_stmt = db_query($linked_query, [$risk_id]);
$linked_controls = db_fetch_all($linked_stmt);

// Get available controls not yet linked
$available_query = "SELECT * FROM controls
                   WHERE org_id = ?
                   AND control_id NOT IN (
                       SELECT control_id FROM risk_controls WHERE risk_id = ?
                   )
                   ORDER BY control_name";
$available_stmt = db_query($available_query, [$org_id, $risk_id]);
$available_controls = db_fetch_all($available_stmt);

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Manage Risk Controls</title>
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
                        <h2>Manage Risk Controls</h2>
                        <h5>Link and manage controls for: <strong><?php echo htmlspecialchars($risk['risk_title']); ?></strong></h5>
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
                        <a href="risk_view.php?id=<?php echo $risk_id; ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Risk
                        </a>
                        <hr>
                    </div>
                </div>

                <!-- Linked Controls -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <i class="fa fa-shield"></i> Linked Controls (<?php echo count($linked_controls); ?>)
                            </div>
                            <div class="panel-body">
                                <?php if (empty($linked_controls)): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No controls linked to this risk yet. Add controls below to mitigate this risk.
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Control Name</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Effectiveness</th>
                                                <th>Verification Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($linked_controls as $lc): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($lc['control_name']); ?></strong>
                                                    <?php if ($lc['framework_ref']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($lc['framework_ref']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="label label-default"><?php echo ucfirst($lc['control_type']); ?></span></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($lc['implementation_status']) {
                                                        case 'planned': $status_class = 'default'; break;
                                                        case 'in_progress': $status_class = 'info'; break;
                                                        case 'implemented': $status_class = 'success'; break;
                                                        case 'verified': $status_class = 'primary'; break;
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $lc['implementation_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($lc['effectiveness']): ?>
                                                    <span class="label label-<?php echo $lc['effectiveness'] == 'high' ? 'success' : ($lc['effectiveness'] == 'medium' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($lc['effectiveness']); ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="text-muted">Not assessed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $lc['verification_date'] ? format_date($lc['verification_date'], 'd M Y') : 'Not verified'; ?></td>
                                                <td>
                                                    <button class="btn btn-warning btn-xs" data-toggle="modal" data-target="#editModal<?php echo $lc['risk_control_id']; ?>">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </button>
                                                    <a href="risk_controls_manage.php?id=<?php echo $risk_id; ?>&remove=<?php echo $lc['risk_control_id']; ?>"
                                                       class="btn btn-danger btn-xs"
                                                       onclick="return confirm('Remove this control from the risk?');">
                                                        <i class="fa fa-trash"></i> Remove
                                                    </a>
                                                </td>
                                            </tr>

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $lc['risk_control_id']; ?>" tabindex="-1" role="dialog">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="post">
                                                            <input type="hidden" name="action" value="update">
                                                            <input type="hidden" name="risk_control_id" value="<?php echo $lc['risk_control_id']; ?>">
                                                            <div class="modal-header">
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                <h4 class="modal-title">Edit Control Status</h4>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="form-group">
                                                                    <label>Implementation Status</label>
                                                                    <select name="implementation_status" class="form-control">
                                                                        <option value="planned" <?php echo $lc['implementation_status'] == 'planned' ? 'selected' : ''; ?>>Planned</option>
                                                                        <option value="in_progress" <?php echo $lc['implementation_status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                        <option value="implemented" <?php echo $lc['implementation_status'] == 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                                                        <option value="verified" <?php echo $lc['implementation_status'] == 'verified' ? 'selected' : ''; ?>>Verified</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Effectiveness</label>
                                                                    <select name="effectiveness" class="form-control">
                                                                        <option value="">Not assessed</option>
                                                                        <option value="high" <?php echo $lc['effectiveness'] == 'high' ? 'selected' : ''; ?>>High</option>
                                                                        <option value="medium" <?php echo $lc['effectiveness'] == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                                        <option value="low" <?php echo $lc['effectiveness'] == 'low' ? 'selected' : ''; ?>>Low</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Verification Date</label>
                                                                    <input type="date" name="verification_date" class="form-control"
                                                                           value="<?php echo $lc['verification_date'] ?? ''; ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label>Notes</label>
                                                                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($lc['notes'] ?? ''); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Control -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <i class="fa fa-plus"></i> Link New Control
                            </div>
                            <div class="panel-body">
                                <?php if (empty($available_controls)): ?>
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> All available controls are already linked to this risk. <a href="controls_add.php">Create a new control</a> if needed.
                                </div>
                                <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="add">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Select Control <span class="text-danger">*</span></label>
                                                <select name="control_id" class="form-control" required>
                                                    <option value="">-- Choose Control --</option>
                                                    <?php foreach ($available_controls as $ac): ?>
                                                    <option value="<?php echo $ac['control_id']; ?>">
                                                        <?php echo htmlspecialchars($ac['control_name']); ?>
                                                        (<?php echo ucfirst($ac['control_type']); ?>)
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Implementation Status</label>
                                                <select name="implementation_status" class="form-control">
                                                    <option value="planned">Planned</option>
                                                    <option value="in_progress">In Progress</option>
                                                    <option value="implemented">Implemented</option>
                                                    <option value="verified">Verified</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Effectiveness</label>
                                                <select name="effectiveness" class="form-control">
                                                    <option value="">Not assessed</option>
                                                    <option value="high">High</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="low">Low</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Notes</label>
                                                <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-link"></i> Link Control
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> About Controls
                            </div>
                            <div class="panel-body">
                                <p><strong>Controls</strong> are measures that reduce the likelihood or impact of risks.</p>

                                <h5>Implementation Status:</h5>
                                <ul>
                                    <li><strong>Planned:</strong> Control identified but not started</li>
                                    <li><strong>In Progress:</strong> Being implemented</li>
                                    <li><strong>Implemented:</strong> In place and operational</li>
                                    <li><strong>Verified:</strong> Tested and confirmed effective</li>
                                </ul>

                                <p class="text-muted"><small><i class="fa fa-lightbulb-o"></i> Tip: Link multiple controls to a single risk for defense in depth.</small></p>
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
    <script src="assets/js/custom.js"></script>
</body>
</html>
