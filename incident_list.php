<?php
/**
 * Incidents & Breach Manager List Page
 * Display all security incidents and data breaches with 72-hour notification tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/stats_card.php';

// Require login
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all incidents for the organization
$query = "SELECT i.*,
          u.first_name as handler_first,
          u.last_name as handler_last,
          creator.first_name as creator_first,
          creator.last_name as creator_last,
          TIMESTAMPDIFF(HOUR, i.detected_at, NOW()) as hours_since_incident,
          TIMESTAMPDIFF(HOUR, i.detected_at, i.notified_at) as hours_to_notification
          FROM incidents i
          LEFT JOIN users u ON i.detected_by = u.user_id
          LEFT JOIN users creator ON i.created_by = creator.user_id
          WHERE i.org_id = ?
          ORDER BY i.detected_at DESC, i.created_at DESC";

$stmt = db_query($query, [$org_id]);
$incidents = db_fetch_all($stmt);

// Calculate statistics
$total_incidents = count($incidents);
$data_breaches = 0;
$open_incidents = 0;
$closed_incidents = 0;
$notification_overdue = 0;
$high_severity = 0;
$critical_severity = 0;

foreach ($incidents as $incident) {
    if ($incident['notifiable'] == 1) {
        $data_breaches++;
    }

    if ($incident['status'] === 'open' || $incident['status'] === 'investigating') {
        $open_incidents++;
    } elseif ($incident['status'] === 'resolved' || $incident['status'] === 'closed') {
        $closed_incidents++;
    }

    // Check if breach notification is overdue (72 hours)
    if ($incident['notifiable'] == 1 && empty($incident['notified_at'])) {
        if ($incident['hours_since_incident'] >= BREACH_NOTIFICATION_HOURS) {
            $notification_overdue++;
        }
    }

    if ($incident['severity'] === 'serious') {
        $high_severity++;
    } elseif ($incident['severity'] === 'critical') {
        $critical_severity++;
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Incidents</title>
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

                <div class="row">
                    <div class="col-md-12">
                        <h2>Incidents & Breach Manager</h2>
                        <h5>Security incidents and data breach tracking</h5>
                    </div>
                </div>
                <hr />

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type']); ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Critical Alerts -->
    <?php if ($notification_overdue > 0): ?>
        <div class="alert alert-danger">
            <i class="fa fa-warning"></i>
            <strong>URGENT:</strong> <?php echo $notification_overdue; ?> data breach<?php echo $notification_overdue > 1 ? 'es' : ''; ?> exceeded 72-hour POTRAZ notification deadline!
            <a href="#" onclick="$('#incidentsTable').DataTable().search('Breach').draw(); return false;" class="btn btn-danger btn-sm pull-right">
                <i class="fa fa-filter"></i> View Overdue Breaches
            </a>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <?php
    render_stats_row([
        [
            'value' => $total_incidents,
            'label' => 'Incidents',
            'icon' => 'fa-exclamation-circle',
            'color' => 'blue'
        ],
        [
            'value' => $data_breaches,
            'label' => 'Data Breaches',
            'icon' => 'fa-shield',
            'color' => $data_breaches > 0 ? 'red' : 'blue'
        ],
        [
            'value' => $notification_overdue,
            'label' => 'Overdue',
            'icon' => 'fa-clock',
            'color' => $notification_overdue > 0 ? 'red' : 'green'
        ],
        [
            'value' => $open_incidents,
            'label' => 'Open',
            'icon' => 'fa-folder-open',
            'color' => 'brown'
        ]
    ]);
    ?>

    <!-- Breach Notification Countdown -->
    <?php
    // Get breaches within 72 hours that haven't been notified
    $urgent_breaches = [];
    foreach ($incidents as $incident) {
        if ($incident['notifiable'] == 1 && empty($incident['notified_at'])) {
            $hours_remaining = BREACH_NOTIFICATION_HOURS - $incident['hours_since_incident'];
            if ($hours_remaining > 0 && $hours_remaining <= BREACH_NOTIFICATION_HOURS) {
                $incident['hours_remaining'] = $hours_remaining;
                $urgent_breaches[] = $incident;
            }
        }
    }
    ?>

    <?php if (!empty($urgent_breaches)): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-clock-o"></i> Urgent: Breaches Requiring POTRAZ Notification (72-Hour Deadline)
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Incident ID</th>
                                        <th>Incident Title</th>
                                        <th>Occurred</th>
                                        <th>Time Remaining</th>
                                        <th>Progress</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($urgent_breaches as $breach): ?>
                                        <?php
                                        $hours_remaining = $breach['hours_remaining'];
                                        $hours_elapsed = BREACH_NOTIFICATION_HOURS - $hours_remaining;
                                        $progress_pct = ($hours_elapsed / BREACH_NOTIFICATION_HOURS) * 100;

                                        // Determine urgency color
                                        if ($hours_remaining <= 6) {
                                            $urgency_class = 'danger';
                                        } elseif ($hours_remaining <= 24) {
                                            $urgency_class = 'warning';
                                        } else {
                                            $urgency_class = 'info';
                                        }
                                        ?>
                                        <tr class="<?php echo $urgency_class; ?>">
                                            <td>INC-<?php echo str_pad($breach['incident_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td><strong><?php echo htmlspecialchars($breach['incident_title']); ?></strong></td>
                                            <td><?php echo date('d M Y, H:i', strtotime($breach['detected_at'])); ?></td>
                                            <td>
                                                <strong><?php echo floor($hours_remaining); ?> hours</strong>
                                                (<?php echo floor($hours_remaining / 24); ?> days, <?php echo $hours_remaining % 24; ?> hrs)
                                            </td>
                                            <td>
                                                <div class="progress" style="margin-bottom: 0;">
                                                    <div class="progress-bar progress-bar-<?php echo $urgency_class; ?>"
                                                         style="width: <?php echo $progress_pct; ?>%">
                                                        <?php echo round($progress_pct); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="incident_view.php?id=<?php echo $breach['incident_id']; ?>"
                                                   class="btn btn-danger btn-sm">
                                                    <i class="fa fa-eye"></i> View & Notify
                                                </a>
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
    <?php endif; ?>

    <!-- Incidents List -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-list"></i> All Incidents
                        <a href="incident_add.php" class="btn btn-danger btn-sm pull-right">
                            <i class="fa fa-plus"></i> Report New Incident
                        </a>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($incidents)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No incidents have been reported yet. Click "Report New Incident" to create your first incident entry.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="incidentsTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Incident ID</th>
                                        <th>Title</th>
                                        <th>Severity</th>
                                        <th>Notifiable</th>
                                        <th>Detected</th>
                                        <th>Detected By</th>
                                        <th>POTRAZ Status</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $incident): ?>
                                        <?php
                                        // Severity badges
                                        $severity_class = [
                                            'minor' => 'success',
                                            'serious' => 'warning',
                                            'critical' => 'danger'
                                        ];
                                        $sev_class = $severity_class[$incident['severity']] ?? 'default';

                                        // Status badges
                                        $status_class = [
                                            'open' => 'danger',
                                            'investigating' => 'warning',
                                            'contained' => 'info',
                                            'resolved' => 'primary',
                                            'closed' => 'success'
                                        ];
                                        $st_class = $status_class[$incident['status']] ?? 'default';

                                        // POTRAZ notification status
                                        $potraz_status = 'N/A';
                                        $potraz_class = 'default';

                                        if ($incident['notifiable'] == 1) {
                                            if (!empty($incident['notified_at'])) {
                                                $potraz_status = 'Notified';
                                                $potraz_class = 'success';

                                                if ($incident['hours_to_notification'] <= BREACH_NOTIFICATION_HOURS) {
                                                    $potraz_status .= ' (' . $incident['hours_to_notification'] . 'h)';
                                                } else {
                                                    $potraz_status .= ' (LATE)';
                                                    $potraz_class = 'warning';
                                                }
                                            } else {
                                                if ($incident['hours_since_incident'] >= BREACH_NOTIFICATION_HOURS) {
                                                    $potraz_status = 'OVERDUE';
                                                    $potraz_class = 'danger';
                                                } else {
                                                    $hours_left = BREACH_NOTIFICATION_HOURS - $incident['hours_since_incident'];
                                                    $potraz_status = floor($hours_left) . 'h remaining';
                                                    $potraz_class = $hours_left <= 6 ? 'danger' : 'warning';
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>INC-<?php echo str_pad($incident['incident_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($incident['incident_title']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($incident['description'] ?? '', 0, 80)); ?><?php if (strlen($incident['description'] ?? '') > 80) echo '...'; ?></small>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $sev_class; ?>">
                                                    <?php echo ucfirst($incident['severity']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($incident['notifiable'] == 1): ?>
                                                    <span class="label label-danger">YES</span>
                                                <?php else: ?>
                                                    <span class="text-muted">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($incident['detected_at'])); ?>
                                                <br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($incident['detected_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                if ($incident['handler_first']) {
                                                    echo htmlspecialchars($incident['handler_first'] . ' ' . $incident['handler_last']);
                                                } else {
                                                    echo '<span class="text-muted">Unassigned</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $potraz_class; ?>">
                                                    <?php echo $potraz_status; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $st_class; ?>">
                                                    <?php echo ucfirst($incident['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-xs">
                                                    <a href="incident_view.php?id=<?php echo $incident['incident_id']; ?>"
                                                       class="btn btn-info" title="View Incident">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="incident_edit.php?id=<?php echo $incident['incident_id']; ?>"
                                                       class="btn btn-warning" title="Update Incident">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($incidents)): ?>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="incident_add.php" class="btn btn-danger">
                                <i class="fa fa-plus"></i> Report New Incident
                            </a>
                            <a href="incident_export.php" class="btn btn-primary">
                                <i class="fa fa-download"></i> Export Incidents
                            </a>
                        </div>
                        <div class="col-sm-6 text-right">
                            <p class="text-muted" style="margin-top: 8px;">
                                Showing <?php echo $total_incidents; ?> incident<?php echo $total_incidents != 1 ? 's' : ''; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
    // Initialize DataTables
    $('#incidentsTable').DataTable({
        "order": [[4, "desc"]], // Sort by detected date (descending)
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "columnDefs": [
            { "orderable": false, "targets": [8] } // Actions column not sortable
        ]
    });
});
</script>
</body>
</html>
