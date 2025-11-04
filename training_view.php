<?php
/**
 * View Training Course Details
 * Display training information and completion statistics
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get training ID from URL
$training_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$training_id) {
    set_flash_message('Invalid training ID.', 'error');
    redirect('training_list.php');
}

// Fetch training details
$query = "SELECT t.*,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
          u.email as created_by_email
          FROM training t
          LEFT JOIN users u ON t.created_by = u.user_id
          WHERE t.training_id = ? AND t.org_id = ?";

$stmt = db_query($query, [$training_id, $org_id]);
$training = db_fetch_one($stmt);

if (!$training) {
    set_flash_message('Training course not found.', 'error');
    redirect('training_list.php');
}

// Get completion statistics
$stats_query = "SELECT
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as not_started_count,
                SUM(CASE WHEN due_at < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_count
                FROM training_assignments
                WHERE training_id = ?";
$stats_stmt = db_query($stats_query, [$training_id]);
$stats = db_fetch_one($stats_stmt);

// Get recent completions
$completions_query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as user_name,
                      ta.completed_at, ta.score, d.dept_name
                      FROM training_assignments ta
                      JOIN users u ON ta.user_id = u.user_id
                      LEFT JOIN departments d ON u.dept_id = d.dept_id
                      WHERE ta.training_id = ? AND ta.status = 'completed'
                      ORDER BY ta.completed_at DESC
                      LIMIT 10";
$completions_stmt = db_query($completions_query, [$training_id]);
$completions = db_fetch_all($completions_stmt);

// Status badge color
$status_colors = [
    'active' => 'success',
    'inactive' => 'default'
];
$status_color = $status_colors[$training['status']] ?? 'default';

$completion_rate = $stats['total_assigned'] > 0
    ? round(($stats['completed_count'] / $stats['total_assigned']) * 100)
    : 0;

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Training</title>
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
                        <h2>Training Course Details</h2>
                        <h5><?php echo htmlspecialchars($training['training_title']); ?></h5>
                    </div>
                </div>
                <hr />

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <?php if (is_admin() || is_dpo()): ?>
                    <a href="training_add.php?id=<?php echo $training_id; ?>" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit Training
                    </a>
                    <a href="training_assign.php?training_id=<?php echo $training_id; ?>" class="btn btn-success">
                        <i class="fa fa-users"></i> Assign to Staff
                    </a>
                    <?php endif; ?>
                    <a href="training_list.php" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    <?php if ($training['content_path'] && file_exists($training['content_path'])): ?>
                    <a href="<?php echo htmlspecialchars($training['content_path']); ?>" class="btn btn-info" target="_blank">
                        <i class="fa fa-download"></i> Download Training Content
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-6">

            <!-- Training Information -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-info-circle"></i> Training Information
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Training Title:</th>
                            <td><strong><?php echo htmlspecialchars($training['training_title']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Training Type:</th>
                            <td>
                                <?php
                                $types = [
                                    'data_protection' => 'Data Protection',
                                    'security_awareness' => 'Security Awareness',
                                    'privacy' => 'Privacy',
                                    'compliance' => 'Compliance',
                                    'incident_response' => 'Incident Response',
                                    'other' => 'Other'
                                ];
                                echo $types[$training['training_type']] ?? 'Unknown';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="label label-<?php echo $status_color; ?>" style="font-size: 14px; padding: 8px 12px;">
                                    <?php echo strtoupper($training['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($training['description']): ?>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo nl2br(htmlspecialchars($training['description'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($training['duration_minutes']): ?>
                        <tr>
                            <th>Duration:</th>
                            <td><i class="fa fa-clock-o"></i> <?php echo $training['duration_minutes']; ?> minutes</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($training['passing_score']): ?>
                        <tr>
                            <th>Passing Score:</th>
                            <td><?php echo $training['passing_score']; ?>%</td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Default Due Period:</th>
                            <td><?php echo $training['due_days']; ?> days after assignment</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Metadata -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-clock-o"></i> Record Metadata
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Created By:</th>
                            <td>
                                <?php echo htmlspecialchars($training['created_by_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($training['created_by_email']); ?></small>
                            </td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($training['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($training['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-md-6">

            <!-- Completion Statistics -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fa fa-bar-chart"></i> Completion Statistics
                </div>
                <div class="panel-body">
                    <h4>Overall Completion Rate</h4>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar progress-bar-<?php echo $completion_rate >= 80 ? 'success' : ($completion_rate >= 50 ? 'warning' : 'danger'); ?>"
                             role="progressbar"
                             style="width: <?php echo $completion_rate; ?>%; font-size: 16px; line-height: 30px;">
                            <?php echo $completion_rate; ?>%
                        </div>
                    </div>
                    <p class="text-center">
                        <strong><?php echo $stats['completed_count']; ?></strong> of <strong><?php echo $stats['total_assigned']; ?></strong> assigned users have completed
                    </p>

                    <hr>

                    <div class="row">
                        <div class="col-xs-6">
                            <div class="panel panel-default">
                                <div class="panel-body text-center">
                                    <h3 style="margin: 0; color: #5cb85c;"><?php echo $stats['completed_count']; ?></h3>
                                    <p style="margin: 0; color: #777;">Completed</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="panel panel-default">
                                <div class="panel-body text-center">
                                    <h3 style="margin: 0; color: #5bc0de;"><?php echo $stats['in_progress_count']; ?></h3>
                                    <p style="margin: 0; color: #777;">In Progress</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="panel panel-default">
                                <div class="panel-body text-center">
                                    <h3 style="margin: 0; color: #f0ad4e;"><?php echo $stats['not_started_count']; ?></h3>
                                    <p style="margin: 0; color: #777;">Not Started</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="panel panel-default">
                                <div class="panel-body text-center">
                                    <h3 style="margin: 0; color: #d9534f;"><?php echo $stats['overdue_count']; ?></h3>
                                    <p style="margin: 0; color: #777;">Overdue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Completions -->
            <div class="panel panel-success">
                <div class="panel-heading">
                    <i class="fa fa-check-circle"></i> Recent Completions
                </div>
                <div class="panel-body">
                    <?php if (!empty($completions)): ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <ul class="list-unstyled">
                                <?php foreach ($completions as $completion): ?>
                                    <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                        <i class="fa fa-user"></i> <strong><?php echo htmlspecialchars($completion['user_name']); ?></strong>
                                        <?php if ($completion['score']): ?>
                                            <span class="label label-<?php echo $completion['score'] >= $training['passing_score'] ? 'success' : 'danger'; ?>">
                                                Score: <?php echo $completion['score']; ?>%
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($completion['dept_name'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($completion['dept_name']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-muted">
                                            <i class="fa fa-clock-o"></i> <?php echo date('d M Y, H:i', strtotime($completion['completed_at'])); ?>
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No completions yet.</p>
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
    <script src="assets/js/jquery.metisMenu.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
