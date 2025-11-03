<?php
/**
 * Training List Page
 * Display all training courses with completion tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all training courses
$query = "SELECT t.*,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
          COUNT(DISTINCT ta.assign_id) as assignment_count,
          SUM(CASE WHEN ta.status = 'completed' THEN 1 ELSE 0 END) as completed_count
          FROM training t
          LEFT JOIN users u ON t.created_by = u.user_id
          LEFT JOIN training_assignments ta ON t.training_id = ta.training_id
          WHERE t.org_id = ?
          GROUP BY t.training_id
          ORDER BY t.created_at DESC";

$stmt = db_query($query, [$org_id]);
$trainings = db_fetch_all($stmt);

// Calculate statistics
$total_trainings = count($trainings);
$active_count = 0;
$draft_count = 0;
$archived_count = 0;

foreach ($trainings as $training) {
    if ($training['status'] === 'active') $active_count++;
    if ($training['status'] === 'inactive') $archived_count++;
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Training Courses</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-draft { background-color: #f0ad4e; color: white; }
        .status-active { background-color: #5cb85c; color: white; }
        .status-archived { background-color: #777; color: white; }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

<div class="container-fluid">

    <!-- Page Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-graduation-cap"></i> Training Management
                    <small>Staff training and awareness programs</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="#">Policy & Training</a></li>
                    <li class="active">Training</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-graduation-cap fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_trainings; ?></div>
                            <div>Total Courses</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-play-circle fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $active_count; ?></div>
                            <div>Active</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-pencil fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $draft_count; ?></div>
                            <div>Drafts</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-archive fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $archived_count; ?></div>
                            <div>Archived</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <?php if (is_admin() || is_dpo()): ?>
                    <a href="training_add.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Create New Training Course
                    </a>
                    <a href="training_assign.php" class="btn btn-info">
                        <i class="fa fa-users"></i> Assign Training to Staff
                    </a>
                    <?php endif; ?>
                    <a href="training_my.php" class="btn btn-success">
                        <i class="fa fa-user"></i> My Training Dashboard
                    </a>
                    <a href="policy_list.php" class="btn btn-default">
                        <i class="fa fa-file-text"></i> View Policies
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Training Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Training Courses
                    <div class="pull-right">
                        <input type="text" id="searchInput" class="form-control input-sm" placeholder="Search training..." style="width: 200px; display: inline-block;">
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (empty($trainings)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No training courses created yet. Click "Create New Training Course" to add one.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="trainingsTable">
                                <thead>
                                    <tr>
                                        <th>Training Title</th>
                                        <th>Type</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Assigned/Completed</th>
                                        <th>Completion Rate</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trainings as $training): ?>
                                        <?php
                                        $completion_rate = $training['assignment_count'] > 0
                                            ? round(($training['completed_count'] / $training['assignment_count']) * 100)
                                            : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($training['training_title']); ?></strong>
                                                <?php if ($training['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($training['description'], 0, 80)) . (strlen($training['description']) > 80 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="label label-default">
                                                    <?php
                                                    $types = [
                                                        'data_protection' => 'Data Protection',
                                                        'security_awareness' => 'Security',
                                                        'privacy' => 'Privacy',
                                                        'compliance' => 'Compliance',
                                                        'incident_response' => 'Incident Response',
                                                        'other' => 'Other'
                                                    ];
                                                    echo $types[$training['training_type']] ?? 'Unknown';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($training['duration_minutes']): ?>
                                                    <i class="fa fa-clock-o"></i> <?php echo $training['duration_minutes']; ?> mins
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $training['status']; ?>">
                                                    <?php echo strtoupper($training['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo $training['completed_count']; ?></strong> / <?php echo $training['assignment_count']; ?>
                                                <?php if ($training['assignment_count'] == 0): ?>
                                                    <br><small class="text-muted">Not assigned yet</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="progress" style="margin-bottom: 0;">
                                                    <div class="progress-bar progress-bar-<?php echo $completion_rate >= 80 ? 'success' : ($completion_rate >= 50 ? 'warning' : 'danger'); ?>"
                                                         role="progressbar"
                                                         style="width: <?php echo $completion_rate; ?>%">
                                                        <?php echo $completion_rate; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($training['created_at'])); ?>
                                                <br><small class="text-muted">by <?php echo htmlspecialchars($training['created_by_name']); ?></small>
                                            </td>
                                            <td>
                                                <a href="training_view.php?id=<?php echo $training['training_id']; ?>" class="btn btn-info btn-xs" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if (is_admin() || is_dpo()): ?>
                                                <a href="training_add.php?id=<?php echo $training['training_id']; ?>" class="btn btn-primary btn-xs" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="training_assign.php?training_id=<?php echo $training['training_id']; ?>" class="btn btn-success btn-xs" title="Assign">
                                                    <i class="fa fa-users"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
<script src="assets/js/custom.js"></script>
<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#trainingsTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>
</body>
</html>
