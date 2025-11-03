<?php
/**
 * Assign Training to Staff
 * Bulk assign training courses to users or departments
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_role(['Admin', 'DPO']);

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get pre-selected training if specified
$preselected_training = isset($_GET['training_id']) ? intval($_GET['training_id']) : 0;

// Fetch all active training courses
$training_query = "SELECT training_id, training_title, training_type, duration_minutes, due_days
                   FROM training
                   WHERE org_id = ? AND status = 'active'
                   ORDER BY training_title ASC";
$training_stmt = db_query($training_query, [$org_id]);
$trainings = db_fetch_all($training_stmt);

// Fetch all active users
$users_query = "SELECT u.user_id, u.first_name, u.last_name, u.email,
                d.dept_name as department, r.role_name as role
                FROM users u
                LEFT JOIN departments d ON u.dept_id = d.dept_id
                LEFT JOIN roles r ON u.role_id = r.role_id
                WHERE u.org_id = ? AND u.status = 'active'
                ORDER BY d.dept_name, u.last_name, u.first_name";
$users_stmt = db_query($users_query, [$org_id]);
$users = db_fetch_all($users_stmt);

// Get unique departments
$departments = array_unique(array_filter(array_column($users, 'department')));

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_training = intval($_POST['training_id']);
    $assignment_type = $_POST['assignment_type'];
    $custom_due_at = !empty($_POST['custom_due_at']) ? $_POST['custom_due_at'] : null;

    // Get the training details
    $training_query = "SELECT training_title, due_days FROM training WHERE training_id = ? AND org_id = ?";
    $training_stmt = db_query($training_query, [$selected_training, $org_id]);
    $training_info = db_fetch_one($training_stmt);

    if (!$training_info) {
        set_flash_message('Invalid training course selected.', 'error');
        redirect('training_assign.php');
    }

    $assigned_users = [];

    if ($assignment_type === 'all') {
        // Assign to all users
        $assigned_users = array_column($users, 'user_id');
    } elseif ($assignment_type === 'department' && !empty($_POST['departments'])) {
        // Assign to specific departments
        $selected_departments = $_POST['departments'];
        foreach ($users as $user) {
            if (in_array($user['department'], $selected_departments)) {
                $assigned_users[] = $user['user_id'];
            }
        }
    } elseif ($assignment_type === 'individual' && !empty($_POST['user_ids'])) {
        // Assign to specific users
        $assigned_users = $_POST['user_ids'];
    }

    // Calculate due date
    if ($custom_due_at) {
        $due_at = $custom_due_at;
    } else {
        $due_at = date('Y-m-d', strtotime('+' . $training_info['due_days'] . ' days'));
    }

    $assigned_count = 0;
    $skipped_count = 0;

    foreach ($assigned_users as $target_user_id) {
        // Check if already assigned
        $check_query = "SELECT assign_id FROM training_assignments
                        WHERE training_id = ? AND user_id = ? AND status != 'completed'";
        $check_stmt = db_query($check_query, [$selected_training, $target_user_id]);
        $existing = db_fetch_one($check_stmt);

        if ($existing) {
            $skipped_count++;
            continue;
        }

        // Create assignment
        $insert_query = "INSERT INTO training_assignments
                        (training_id, user_id, due_at, status)
                        VALUES (?, ?, ?, 'assigned')";
        db_query($insert_query, [$selected_training, $target_user_id, $due_at]);
        $assigned_count++;

        // Create notification for the user
        create_notification(
            $org_id, $target_user_id, 'training_assigned',
            'New Training Assigned',
            "You have been assigned training: " . $training_info['training_title'] . ". Due date: " . date('d M Y', strtotime($due_at)),
            'training', $selected_training, "training_my.php", 'medium'
        );

        log_audit($org_id, $user_id, 'ASSIGN', 'training', $selected_training,
            "Assigned training to user ID $target_user_id");
    }

    if ($assigned_count > 0) {
        set_flash_message("Training assigned to $assigned_count user(s) successfully. $skipped_count already assigned.", 'success');
    } else {
        set_flash_message("No new assignments made. All selected users were already assigned this training.", 'warning');
    }

    redirect('training_list.php');
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Assign Training</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .user-checkbox {
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
            border-radius: 3px;
        }
        .user-checkbox:hover {
            background-color: #f9f9f9;
        }
        .assignment-section {
            display: none;
        }
        .assignment-section.active {
            display: block;
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

    <!-- Page Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-users"></i> Assign Training to Staff
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="training_list.php">Training</a></li>
                    <li class="active">Assign Training</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-graduation-cap"></i> Training Assignment
                </div>
                <div class="panel-body">

                    <?php if (empty($trainings)): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            No active training courses available. Please create and activate a training course first.
                            <br><br>
                            <a href="training_add.php" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Create Training Course
                            </a>
                        </div>
                    <?php elseif (empty($users)): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i>
                            No active users available for training assignment.
                        </div>
                    <?php else: ?>

                    <form method="POST" id="assignmentForm">

                        <!-- Select Training -->
                        <div class="form-group">
                            <label for="training_id">Select Training Course <span class="text-danger">*</span></label>
                            <select class="form-control" id="training_id" name="training_id" required>
                                <option value="">-- Choose a training course --</option>
                                <?php foreach ($trainings as $training): ?>
                                    <option value="<?php echo $training['training_id']; ?>"
                                            <?php echo $training['training_id'] == $preselected_training ? 'selected' : ''; ?>
                                            data-due-days="<?php echo $training['due_days']; ?>">
                                        <?php echo htmlspecialchars($training['training_title']); ?>
                                        (<?php echo $training['duration_minutes'] ? $training['duration_minutes'] . ' mins' : 'No duration set'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Assignment Type -->
                        <div class="form-group">
                            <label>Assignment Type <span class="text-danger">*</span></label>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="assignment_type" value="all" id="type_all" required>
                                    Assign to <strong>All Staff</strong> (<?php echo count($users); ?> users)
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="assignment_type" value="department" id="type_department">
                                    Assign to <strong>Specific Departments</strong>
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="assignment_type" value="individual" id="type_individual">
                                    Assign to <strong>Individual Users</strong>
                                </label>
                            </div>
                        </div>

                        <!-- Department Selection -->
                        <div id="department_section" class="assignment-section">
                            <div class="form-group">
                                <label>Select Departments</label>
                                <?php foreach ($departments as $dept): ?>
                                    <?php
                                    $dept_user_count = count(array_filter($users, function($u) use ($dept) {
                                        return $u['department'] === $dept;
                                    }));
                                    ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="departments[]" value="<?php echo htmlspecialchars($dept); ?>">
                                            <strong><?php echo htmlspecialchars($dept); ?></strong> (<?php echo $dept_user_count; ?> users)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Individual User Selection -->
                        <div id="individual_section" class="assignment-section">
                            <div class="form-group">
                                <label>Select Users</label>
                                <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                                    <?php
                                    $current_dept = '';
                                    foreach ($users as $staff):
                                        if ($staff['department'] !== $current_dept):
                                            if ($current_dept !== '') echo '</div>';
                                            $current_dept = $staff['department'];
                                            echo '<h5 style="margin-top: 15px; color: #337ab7;"><strong>' . htmlspecialchars($current_dept ?: 'No Department') . '</strong></h5>';
                                            echo '<div style="margin-left: 20px;">';
                                        endif;
                                    ?>
                                        <div class="checkbox user-checkbox">
                                            <label>
                                                <input type="checkbox" name="user_ids[]" value="<?php echo $staff['user_id']; ?>">
                                                <strong><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($staff['email']); ?> - <?php echo htmlspecialchars($staff['role']); ?></small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($current_dept !== '') echo '</div>'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Due Date -->
                        <div class="form-group">
                            <label for="custom_due_at">Custom Due Date (Optional)</label>
                            <input type="date" class="form-control" id="custom_due_at" name="custom_due_at">
                            <span class="help-block">Leave blank to use the training's default due period (will be calculated from today)</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-check"></i> Assign Training
                            </button>
                            <a href="training_list.php" class="btn btn-default btn-lg">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>

                    </form>

                    <?php endif; ?>

                </div>
            </div>

            <!-- Help Panel -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fa fa-info-circle"></i> Training Assignment Guidelines
                </div>
                <div class="panel-body">
                    <ul>
                        <li><strong>Mandatory Training:</strong> Data protection awareness training should be assigned to all staff annually</li>
                        <li><strong>Role-Based Assignment:</strong> Assign specialized training based on job roles (e.g., incident response for IT)</li>
                        <li><strong>Department-Wide Training:</strong> Use department assignments for team-specific training</li>
                        <li><strong>Due Dates:</strong> Set realistic completion deadlines (typically 30 days for comprehensive training)</li>
                        <li><strong>Notifications:</strong> Users will receive email notifications when training is assigned</li>
                        <li><strong>Duplicate Prevention:</strong> The system will skip users who are already assigned incomplete training</li>
                    </ul>
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
    // Show/hide assignment sections based on radio selection
    $('input[name="assignment_type"]').on('change', function() {
        $('.assignment-section').removeClass('active');

        if ($(this).val() === 'department') {
            $('#department_section').addClass('active');
        } else if ($(this).val() === 'individual') {
            $('#individual_section').addClass('active');
        }
    });

    // Form validation
    $('#assignmentForm').on('submit', function(e) {
        var assignmentType = $('input[name="assignment_type"]:checked').val();

        if (assignmentType === 'department') {
            if ($('input[name="departments[]"]:checked').length === 0) {
                alert('Please select at least one department.');
                e.preventDefault();
                return false;
            }
        } else if (assignmentType === 'individual') {
            if ($('input[name="user_ids[]"]:checked').length === 0) {
                alert('Please select at least one user.');
                e.preventDefault();
                return false;
            }
        }

        return confirm('Are you sure you want to assign this training to the selected users?');
    });
});
</script>
</body>
</html>
