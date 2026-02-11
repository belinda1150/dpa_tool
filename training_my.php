<?php
/**
 * My Training Dashboard
 * User's personal view of assigned training courses
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Handle training completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_training'])) {
    $assign_id = intval($_POST['assign_id']);
    $score = isset($_POST['score']) ? intval($_POST['score']) : null;

    // Verify this assignment belongs to the current user
    $verify_query = "SELECT ta.*, t.training_title, t.passing_score
                     FROM training_assignments ta
                     JOIN training t ON ta.training_id = t.training_id
                     WHERE ta.assign_id = ? AND ta.user_id = ?";
    $verify_stmt = db_query($verify_query, [$assign_id, $user_id]);
    $assignment = db_fetch_one($verify_stmt);

    if ($assignment && $assignment['status'] !== 'completed') {
        // Update assignment status
        $update_query = "UPDATE training_assignments
                        SET status = 'completed', completed_at = NOW(), score = ?
                        WHERE assign_id = ?";
        db_query($update_query, [$score, $assign_id]);

        log_audit($org_id, $user_id, 'COMPLETE', 'training', $assignment['training_id'],
            "Completed training: " . $assignment['training_title'] . ($score ? " (Score: $score%)" : ""));

        // Check if passed
        if ($assignment['passing_score'] && $score) {
            if ($score >= $assignment['passing_score']) {
                set_flash_message('Training completed successfully! You passed with ' . $score . '%.', 'success');
            } else {
                set_flash_message('Training completed, but you did not meet the passing score (' . $assignment['passing_score'] . '%). Please review and retake.', 'warning');
            }
        } else {
            set_flash_message('Training completed successfully!', 'success');
        }

        redirect('training_my.php');
    }
}

// Handle start training
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_training'])) {
    $assign_id = intval($_POST['assign_id']);

    // Update status to in_progress
    $update_query = "UPDATE training_assignments
                    SET status = 'in_progress', started_at = NOW()
                    WHERE assign_id = ? AND user_id = ? AND status = 'assigned'";
    db_query($update_query, [$assign_id, $user_id]);

    set_flash_message('Training started. Good luck!', 'info');
    redirect('training_my.php');
}

// Fetch all assigned training
$query = "SELECT ta.*,
          t.training_title, t.training_type, t.description, t.content_path,
          t.duration_minutes, t.passing_score,
          creator.first_name as creator_first, creator.last_name as creator_last
          FROM training_assignments ta
          JOIN training t ON ta.training_id = t.training_id
          LEFT JOIN users creator ON t.created_by = creator.user_id
          WHERE ta.user_id = ?
          ORDER BY
            CASE ta.status
              WHEN 'assigned' THEN 1
              WHEN 'in_progress' THEN 2
              WHEN 'completed' THEN 3
            END,
            ta.due_at ASC";

$stmt = db_query($query, [$user_id]);
$assignments = db_fetch_all($stmt);

// Calculate statistics
$total_assigned = count($assignments);
$completed_count = 0;
$in_progress_count = 0;
$not_started_count = 0;
$overdue_count = 0;

foreach ($assignments as $assignment) {
    if ($assignment['status'] === 'completed') $completed_count++;
    if ($assignment['status'] === 'in_progress') $in_progress_count++;
    if ($assignment['status'] === 'assigned') $not_started_count++;
    if ($assignment['status'] !== 'completed' && strtotime($assignment['due_at']) < time()) {
        $overdue_count++;
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - My Training</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .training-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .training-card.assigned {
            border-left: 5px solid #f0ad4e;
        }
        .training-card.in_progress {
            border-left: 5px solid #5bc0de;
        }
        .training-card.completed {
            border-left: 5px solid #5cb85c;
        }
        .training-card.overdue {
            border-left: 5px solid #d9534f;
            background-color: #fff5f5;
        }
    </style>
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
                        <h2>My Training Dashboard</h2>
                        <h5>Your assigned training courses</h5>
                    </div>
                </div>
                <hr />

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-graduation-cap fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_assigned; ?></div>
                            <div>Total Assigned</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-check-circle fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $completed_count; ?></div>
                            <div>Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-play-circle fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $in_progress_count; ?></div>
                            <div>In Progress</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-exclamation-triangle fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $overdue_count; ?></div>
                            <div>Overdue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Training Assignments -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-list"></i> My Training Courses
                </div>
                <div class="card-body">
                    <?php if (empty($assignments)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            You have no training courses assigned yet.
                        </div>
                    <?php else: ?>

                        <?php foreach ($assignments as $assignment): ?>
                            <?php
                            $is_overdue = ($assignment['status'] !== 'completed' && strtotime($assignment['due_at']) < time());
                            $card_class = $is_overdue ? 'overdue' : $assignment['status'];
                            ?>

                            <div class="training-card <?php echo $card_class; ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 style="margin-top: 0;">
                                            <i class="fa fa-graduation-cap"></i>
                                            <?php echo htmlspecialchars($assignment['training_title']); ?>
                                        </h4>

                                        <?php if ($assignment['description']): ?>
                                            <p><?php echo htmlspecialchars($assignment['description']); ?></p>
                                        <?php endif; ?>

                                        <div>
                                            <span class="badge bg-secondary">
                                                <?php
                                                $types = [
                                                    'data_protection' => 'Data Protection',
                                                    'security_awareness' => 'Security',
                                                    'privacy' => 'Privacy',
                                                    'compliance' => 'Compliance',
                                                    'incident_response' => 'Incident Response',
                                                    'other' => 'Other'
                                                ];
                                                echo $types[$assignment['training_type']] ?? 'Unknown';
                                                ?>
                                            </span>

                                            <?php if ($assignment['duration_minutes']): ?>
                                                <span class="badge bg-info text-dark">
                                                    <i class="fa fa-clock-o"></i> <?php echo $assignment['duration_minutes']; ?> mins
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($assignment['passing_score']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    Passing Score: <?php echo $assignment['passing_score']; ?>%
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <hr style="margin: 10px 0;">

                                        <small class="text-muted">
                                            <i class="fa fa-user"></i> Created by: <?php echo htmlspecialchars($assignment['creator_first'] . ' ' . $assignment['creator_last']); ?>
                                            |
                                            <i class="fa fa-calendar"></i> Assigned: <?php echo date('d M Y', strtotime($assignment['assigned_at'])); ?>
                                            |
                                            <i class="fa fa-clock-o"></i> Due: <strong <?php echo $is_overdue ? 'class="text-danger"' : ''; ?>><?php echo date('d M Y', strtotime($assignment['due_at'])); ?></strong>
                                            <?php if ($is_overdue): ?>
                                                <span class="badge bg-danger">OVERDUE</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <?php if ($assignment['status'] === 'completed'): ?>
                                                <h3 style="color: #5cb85c; margin-top: 0;">
                                                    <i class="fa fa-check-circle"></i> COMPLETED
                                                </h3>
                                                <?php if ($assignment['completed_at']): ?>
                                                    <p class="text-muted">
                                                        Completed on:<br>
                                                        <?php echo date('d M Y, H:i', strtotime($assignment['completed_at'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($assignment['score']): ?>
                                                    <h4 class="<?php echo $assignment['score'] >= $assignment['passing_score'] ? 'text-success' : 'text-danger'; ?>">
                                                        Score: <?php echo $assignment['score']; ?>%
                                                    </h4>
                                                <?php endif; ?>

                                            <?php elseif ($assignment['status'] === 'in_progress'): ?>
                                                <h4 style="color: #5bc0de; margin-top: 0;">
                                                    <i class="fa fa-spinner fa-spin"></i> IN PROGRESS
                                                </h4>
                                                <?php if ($assignment['started_at']): ?>
                                                    <p class="text-muted">
                                                        Started on:<br>
                                                        <?php echo date('d M Y, H:i', strtotime($assignment['started_at'])); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($assignment['content_path'] && file_exists($assignment['content_path'])): ?>
                                                    <a href="<?php echo htmlspecialchars($assignment['content_path']); ?>" class="btn btn-info btn-block" target="_blank">
                                                        <i class="fa fa-book"></i> Open Training Material
                                                    </a>
                                                <?php endif; ?>

                                                <button type="button" class="btn btn-success btn-block" data-bs-toggle="modal" data-bs-target="#completeModal<?php echo $assignment['assign_id']; ?>">
                                                    <i class="fa fa-check"></i> Mark as Complete
                                                </button>

                                            <?php else: // assigned ?>
                                                <h4 style="color: #f0ad4e; margin-top: 0;">
                                                    <i class="fa fa-clock-o"></i> NOT STARTED
                                                </h4>
                                                <p class="text-muted">You have not started this training yet.</p>

                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="assign_id" value="<?php echo $assignment['assign_id']; ?>">
                                                    <button type="submit" name="start_training" class="btn btn-primary btn-block btn-lg">
                                                        <i class="fa fa-play"></i> Start Training
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Complete Training Modal -->
                            <?php if ($assignment['status'] === 'in_progress'): ?>
                            <div class="modal fade" id="completeModal<?php echo $assignment['assign_id']; ?>" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            <h4 class="modal-title">Complete Training</h4>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p>Have you completed the training: <strong><?php echo htmlspecialchars($assignment['training_title']); ?></strong>?</p>

                                                <?php if ($assignment['passing_score']): ?>
                                                    <div class="form-group">
                                                        <label for="score<?php echo $assignment['assign_id']; ?>">Your Score (%) <span class="text-danger">*</span></label>
                                                        <input type="number" class="form-control" id="score<?php echo $assignment['assign_id']; ?>" name="score"
                                                               min="0" max="100" required>
                                                        <span class="form-text">Passing score: <?php echo $assignment['passing_score']; ?>%</span>
                                                    </div>
                                                <?php endif; ?>

                                                <input type="hidden" name="assign_id" value="<?php echo $assignment['assign_id']; ?>">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="complete_training" class="btn btn-success">
                                                    <i class="fa fa-check"></i> Mark as Complete
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    <?php endif; ?>
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
<script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
</body>
</html>
