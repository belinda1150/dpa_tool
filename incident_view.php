<?php
/**
 * View Incident Page
 * Display incident details with POTRAZ notification tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($incident_id <= 0) {
    set_flash_message('Invalid incident ID.', 'danger');
    redirect('incident_list.php');
}

// Handle POTRAZ notification form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'notify_potraz') {
    $potraz_ref = sanitize_input($_POST['potraz_ref'] ?? '');
    $potraz_notes = sanitize_input($_POST['potraz_notes'] ?? '');

    if (!empty($potraz_ref)) {
        $query = "UPDATE incidents SET
                  potraz_notified_date = NOW(),
                  potraz_reference = ?,
                  potraz_notes = ?
                  WHERE incident_id = ? AND org_id = ?";

        db_query($query, [$potraz_ref, $potraz_notes, $incident_id, $org_id]);
        log_audit($org_id, $user_id, 'incident', $incident_id, 'update', "POTRAZ notified: $potraz_ref");
        set_flash_message('POTRAZ notification recorded successfully!', 'success');
        redirect("incident_view.php?id=$incident_id");
    }
}

// Fetch incident details
$query = "SELECT i.*,
          u.first_name as handler_first, u.last_name as handler_last,
          reporter.first_name as reporter_first, reporter.last_name as reporter_last,
          dept.dept_name,
          pa.activity_name,
          TIMESTAMPDIFF(HOUR, i.incident_date, NOW()) as hours_since,
          TIMESTAMPDIFF(HOUR, i.incident_date, i.potraz_notified_date) as hours_to_notification
          FROM incidents i
          LEFT JOIN users u ON i.incident_handler_id = u.user_id
          LEFT JOIN users reporter ON i.reported_by = reporter.user_id
          LEFT JOIN departments dept ON i.dept_id = dept.dept_id
          LEFT JOIN processing_activities pa ON i.ropa_id = pa.ropa_id
          WHERE i.incident_id = ? AND i.org_id = ?";

$stmt = db_query($query, [$incident_id, $org_id]);
$incident = db_fetch_one($stmt);

if (!$incident) {
    set_flash_message('Incident not found.', 'danger');
    redirect('incident_list.php');
}

// Fetch audit log
$audit_query = "SELECT al.*, u.first_name, u.last_name
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.user_id
                WHERE al.entity_type = 'incident' AND al.entity_id = ?
                ORDER BY al.created_at DESC LIMIT 10";
$audit_logs = db_fetch_all(db_query($audit_query, [$incident_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Incident</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-exclamation-triangle"></i> View Incident
                    <small>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="incident_list.php">Incidents</a></li>
                    <li class="active">View Incident</li>
                </ol>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type']); ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <!-- POTRAZ Notification Alert -->
    <?php if ($incident['is_data_breach'] == 1 && empty($incident['potraz_notified_date'])): ?>
        <?php
        $hours_remaining = BREACH_NOTIFICATION_HOURS - $incident['hours_since'];
        $is_overdue = $hours_remaining < 0;
        ?>
        <div class="alert alert-<?php echo $is_overdue ? 'danger' : 'warning'; ?>">
            <i class="fa fa-warning"></i>
            <strong><?php echo $is_overdue ? 'OVERDUE' : 'URGENT'; ?>:</strong>
            <?php if ($is_overdue): ?>
                POTRAZ notification is <?php echo abs($hours_remaining); ?> hours OVERDUE!
            <?php else: ?>
                POTRAZ must be notified within <?php echo floor($hours_remaining); ?> hours
                (<?php echo floor($hours_remaining / 24); ?> days, <?php echo $hours_remaining % 24; ?> hrs remaining)
            <?php endif; ?>
            <a href="#notifyModal" data-toggle="modal" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-send"></i> Notify POTRAZ Now
            </a>
        </div>
    <?php endif; ?>

    <!-- Incident Summary -->
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-<?php echo $incident['is_data_breach'] ? 'danger' : 'primary'; ?>">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?php echo htmlspecialchars($incident['incident_title']); ?>
                        <?php if ($incident['is_data_breach']): ?>
                            <span class="label label-danger pull-right">DATA BREACH</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr><th width="30%">Incident ID:</th><td>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></td></tr>
                        <tr><th>Type:</th><td><span class="label label-default"><?php echo htmlspecialchars($incident['incident_type']); ?></span></td></tr>
                        <tr><th>Severity:</th>
                            <td>
                                <?php
                                $sev_class = ['low'=>'success','medium'=>'info','high'=>'warning','critical'=>'danger'][$incident['severity']] ?? 'default';
                                echo "<span class='label label-$sev_class'>".strtoupper($incident['severity'])."</span>";
                                ?>
                            </td>
                        </tr>
                        <tr><th>Occurred:</th><td><?php echo date('d M Y, H:i', strtotime($incident['incident_date'])); ?></td></tr>
                        <tr><th>Department:</th><td><?php echo htmlspecialchars($incident['dept_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Linked ROPA:</th><td><?php echo htmlspecialchars($incident['activity_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Handler:</th><td><?php echo $incident['handler_first'] ? htmlspecialchars($incident['handler_first'].' '.$incident['handler_last']) : '<span class="text-muted">Unassigned</span>'; ?></td></tr>
                        <tr><th>Reported By:</th><td><?php echo htmlspecialchars($incident['reporter_first'].' '.$incident['reporter_last']); ?></td></tr>
                        <tr><th>Status:</th>
                            <td>
                                <?php
                                $st_class = ['open'=>'danger','investigating'=>'warning','contained'=>'info','resolved'=>'primary','closed'=>'success'][$incident['status']] ?? 'default';
                                echo "<span class='label label-$st_class'>".strtoupper($incident['status'])."</span>";
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?php if ($incident['is_data_breach']): ?>
                <div class="panel panel-danger">
                    <div class="panel-heading"><h4 style="margin:0;">POTRAZ Notification Status</h4></div>
                    <div class="panel-body text-center">
                        <?php if ($incident['potraz_notified_date']): ?>
                            <i class="fa fa-check-circle" style="font-size:48px;color:#27ae60;"></i>
                            <h4>Notified</h4>
                            <p><?php echo date('d M Y, H:i', strtotime($incident['potraz_notified_date'])); ?></p>
                            <p><strong>Ref:</strong> <?php echo htmlspecialchars($incident['potraz_reference']); ?></p>
                            <?php if ($incident['hours_to_notification'] <= BREACH_NOTIFICATION_HOURS): ?>
                                <span class="label label-success">Within 72 hours</span>
                            <?php else: ?>
                                <span class="label label-warning">Late (<?php echo $incident['hours_to_notification']; ?>h)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fa fa-clock-o" style="font-size:48px;color:#e74c3c;"></i>
                            <h4>Not Notified</h4>
                            <p><?php echo floor($incident['hours_since']); ?> hours elapsed</p>
                            <a href="#notifyModal" data-toggle="modal" class="btn btn-danger btn-block">
                                <i class="fa fa-send"></i> Notify POTRAZ
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Description & Details -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-file-text"></i> Incident Description</h4></div>
                <div class="panel-body"><p><?php echo nl2br(htmlspecialchars($incident['incident_description'])); ?></p></div>
            </div>
        </div>
    </div>

    <?php if ($incident['is_data_breach']): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-warning">
                <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-database"></i> Data Breach Details</h4></div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tr><th>Affected Data Subjects:</th><td><?php echo $incident['affected_data_subjects'] ?? 'Unknown'; ?></td></tr>
                        <tr><th>Data Types Affected:</th><td><?php echo nl2br(htmlspecialchars($incident['data_types_affected'])); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel panel-info">
                <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-flash"></i> Immediate Actions Taken</h4></div>
                <div class="panel-body"><p><?php echo nl2br(htmlspecialchars($incident['immediate_actions'] ?: 'None recorded')); ?></p></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-flash"></i> Immediate Actions Taken</h4></div>
                <div class="panel-body"><p><?php echo nl2br(htmlspecialchars($incident['immediate_actions'] ?: 'None recorded')); ?></p></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Audit Log -->
    <?php if (!empty($audit_logs)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading"><h4 class="panel-title"><i class="fa fa-history"></i> Activity History</h4></div>
                <div class="panel-body">
                    <table class="table table-striped">
                        <thead><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                            <tr>
                                <td><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['first_name'].' '.$log['last_name']); ?></td>
                                <td><span class="label label-<?php echo ['create'=>'success','update'=>'info','delete'=>'danger'][$log['action']] ?? 'default'; ?>"><?php echo ucfirst($log['action']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['details'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <a href="incident_edit.php?id=<?php echo $incident_id; ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Update Incident</a>
                    <a href="incident_list.php" class="btn btn-default"><i class="fa fa-list"></i> Back to List</a>
                    <a href="incident_export.php?id=<?php echo $incident_id; ?>" class="btn btn-primary pull-right"><i class="fa fa-download"></i> Export Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- POTRAZ Notification Modal -->
<div class="modal fade" id="notifyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="incident_view.php?id=<?php echo $incident_id; ?>">
                <input type="hidden" name="action" value="notify_potraz">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Record POTRAZ Notification</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>POTRAZ Reference Number <span class="text-danger">*</span></label>
                        <input type="text" name="potraz_ref" class="form-control" required placeholder="e.g., POTRAZ/DPA/2025/001">
                    </div>
                    <div class="form-group">
                        <label>Notification Notes</label>
                        <textarea name="potraz_notes" class="form-control" rows="3" placeholder="Additional details about the notification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-send"></i> Record Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
