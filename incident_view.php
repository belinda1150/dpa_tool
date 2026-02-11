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
    $notification_method = sanitize_input($_POST['notification_method'] ?? '');

    if (!empty($potraz_ref)) {
        $query = "UPDATE incidents SET
                  notified_at = NOW(),
                  potraz_ref = ?,
                  notification_method = ?
                  WHERE incident_id = ? AND org_id = ?";

        db_query($query, [$potraz_ref, $notification_method, $incident_id, $org_id]);
        log_audit($org_id, $user_id, 'incident', $incident_id, 'update', "POTRAZ notified: $potraz_ref");
        set_flash_message('POTRAZ notification recorded successfully!', 'success');
        redirect("incident_view.php?id=$incident_id");
    }
}

// Fetch incident details
$query = "SELECT i.*,
          u.first_name as detector_first, u.last_name as detector_last,
          creator.first_name as creator_first, creator.last_name as creator_last,
          pa.activity_name,
          TIMESTAMPDIFF(HOUR, i.detected_at, NOW()) as hours_since,
          TIMESTAMPDIFF(HOUR, i.detected_at, i.notified_at) as hours_to_notification
          FROM incidents i
          LEFT JOIN users u ON i.detected_by = u.user_id
          LEFT JOIN users creator ON i.created_by = creator.user_id
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
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
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
                        <h2>View Incident</h2>
                        <h5>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></h5>
                    </div>
                </div>
                <hr />

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type']); ?> alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <!-- POTRAZ Notification Alert -->
    <?php if ($incident['notifiable'] == 1 && empty($incident['notified_at'])): ?>
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
            <a href="#notifyModal" data-bs-toggle="modal" class="btn btn-danger btn-sm float-end">
                <i class="fa fa-send"></i> Notify POTRAZ Now
            </a>
        </div>
    <?php endif; ?>

    <!-- Incident Summary -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-<?php echo $incident['notifiable'] ? 'danger' : 'primary'; ?>">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo htmlspecialchars($incident['incident_title']); ?>
                        <?php if ($incident['notifiable']): ?>
                            <span class="badge bg-danger float-end">DATA BREACH</span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th width="30%">Incident ID:</th><td>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></td></tr>
                        <tr><th>Type:</th><td><span class="badge bg-secondary"><?php echo htmlspecialchars($incident['incident_type']); ?></span></td></tr>
                        <tr><th>Severity:</th>
                            <td>
                                <?php
                                $sev_class = ['low'=>'success','medium'=>'info','high'=>'warning','critical'=>'danger'][$incident['severity']] ?? 'secondary';
                                echo "<span class='badge bg-$sev_class'>".strtoupper($incident['severity'])."</span>";
                                ?>
                            </td>
                        </tr>
                        <tr><th>Detected:</th><td><?php echo date('d M Y, H:i', strtotime($incident['detected_at'])); ?></td></tr>
                        <tr><th>Linked ROPA:</th><td><?php echo htmlspecialchars($incident['activity_name'] ?? 'N/A'); ?></td></tr>
                        <tr><th>Detected By:</th><td><?php echo $incident['detector_first'] ? htmlspecialchars($incident['detector_first'].' '.$incident['detector_last']) : '<span class="text-muted">Unknown</span>'; ?></td></tr>
                        <tr><th>Created By:</th><td><?php echo htmlspecialchars($incident['creator_first'].' '.$incident['creator_last']); ?></td></tr>
                        <tr><th>Status:</th>
                            <td>
                                <?php
                                $st_class = ['open'=>'danger','investigating'=>'warning','contained'=>'info','resolved'=>'primary','closed'=>'success'][$incident['status']] ?? 'secondary';
                                echo "<span class='badge bg-$st_class'>".strtoupper($incident['status'])."</span>";
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?php if ($incident['notifiable']): ?>
                <div class="card border-danger">
                    <div class="card-header"><h4 style="margin:0;">POTRAZ Notification Status</h4></div>
                    <div class="card-body text-center">
                        <?php if ($incident['notified_at']): ?>
                            <i class="fa fa-check-circle" style="font-size:48px;color:#27ae60;"></i>
                            <h4>Notified</h4>
                            <p><?php echo date('d M Y, H:i', strtotime($incident['notified_at'])); ?></p>
                            <p><strong>Ref:</strong> <?php echo htmlspecialchars($incident['potraz_ref']); ?></p>
                            <?php if ($incident['hours_to_notification'] <= BREACH_NOTIFICATION_HOURS): ?>
                                <span class="badge bg-success">Within 72 hours</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Late (<?php echo $incident['hours_to_notification']; ?>h)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fa fa-clock-o" style="font-size:48px;color:#e74c3c;"></i>
                            <h4>Not Notified</h4>
                            <p><?php echo floor($incident['hours_since']); ?> hours elapsed</p>
                            <a href="#notifyModal" data-bs-toggle="modal" class="btn btn-danger btn-block">
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
            <div class="card">
                <div class="card-header"><h4 class="card-title"><i class="fa fa-file-text"></i> Incident Description</h4></div>
                <div class="card-body"><p><?php echo nl2br(htmlspecialchars($incident['description'])); ?></p></div>
            </div>
        </div>
    </div>

    <?php if ($incident['notifiable']): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header"><h4 class="card-title"><i class="fa fa-database"></i> Data Breach Details</h4></div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>Affected Data Subjects:</th><td><?php echo $incident['affected_data_subjects'] ?? 'Unknown'; ?></td></tr>
                        <tr><th>Data Types Affected:</th><td><?php echo nl2br(htmlspecialchars($incident['data_types_affected'])); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header"><h4 class="card-title"><i class="fa fa-flash"></i> Immediate Actions Taken</h4></div>
                <div class="card-body"><p><?php echo nl2br(htmlspecialchars($incident['immediate_actions'] ?: 'None recorded')); ?></p></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card border-info">
                <div class="card-header"><h4 class="card-title"><i class="fa fa-flash"></i> Immediate Actions Taken</h4></div>
                <div class="card-body"><p><?php echo nl2br(htmlspecialchars($incident['immediate_actions'] ?: 'None recorded')); ?></p></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Audit Log -->
    <?php if (!empty($audit_logs)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title"><i class="fa fa-history"></i> Activity History</h4></div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
                        <tbody>
                            <?php foreach ($audit_logs as $log): ?>
                            <tr>
                                <td><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['first_name'].' '.$log['last_name']); ?></td>
                                <td><span class="badge bg-<?php echo ['create'=>'success','update'=>'info','delete'=>'danger'][$log['action']] ?? 'secondary'; ?>"><?php echo ucfirst($log['action']); ?></span></td>
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
            <div class="card">
                <div class="card-body">
                    <a href="incident_edit.php?id=<?php echo $incident_id; ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Update Incident</a>
                    <a href="incident_list.php" class="btn btn-secondary"><i class="fa fa-list"></i> Back to List</a>
                    <a href="incident_export.php?id=<?php echo $incident_id; ?>" class="btn btn-primary float-end"><i class="fa fa-download"></i> Export Report</a>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h4 class="modal-title">Record POTRAZ Notification</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>POTRAZ Reference Number <span class="text-danger">*</span></label>
                        <input type="text" name="potraz_ref" class="form-control" required placeholder="e.g., POTRAZ/DPA/2025/001">
                    </div>
                    <div class="form-group">
                        <label>Notification Method</label>
                        <select name="notification_method" class="form-control">
                            <option value="">-- Select Method --</option>
                            <option value="Email">Email</option>
                            <option value="Phone">Phone</option>
                            <option value="Online Portal">Online Portal</option>
                            <option value="Letter">Letter</option>
                            <option value="In Person">In Person</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-send"></i> Record Notification</button>
                </div>
            </form>
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
