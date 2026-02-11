<?php
/**
 * View Policy Details
 * Display policy information and allow staff to acknowledge
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get policy ID from URL
$policy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$policy_id) {
    set_flash_message('Invalid policy ID.', 'error');
    redirect('policy_list.php');
}

// Fetch policy details
$query = "SELECT p.*,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
          u.email as created_by_email
          FROM policies p
          LEFT JOIN users u ON p.created_by = u.user_id
          WHERE p.policy_id = ? AND p.org_id = ?";

$stmt = db_query($query, [$policy_id, $org_id]);
$policy = db_fetch_one($stmt);

if (!$policy) {
    set_flash_message('Policy not found.', 'error');
    redirect('policy_list.php');
}

// Check if current user has acknowledged this policy
$ack_query = "SELECT * FROM policy_acknowledgements
              WHERE policy_id = ? AND user_id = ?";
$ack_stmt = db_query($ack_query, [$policy_id, $user_id]);
$has_acknowledged = db_fetch_one($ack_stmt) !== null;

// Get acknowledgement statistics
$stats_query = "SELECT
                COUNT(DISTINCT pa.ack_id) as ack_count,
                (SELECT COUNT(*) FROM users WHERE org_id = ? AND status = 'active') as total_users
                FROM policy_acknowledgements pa
                WHERE pa.policy_id = ?";
$stats_stmt = db_query($stats_query, [$org_id, $policy_id]);
$stats = db_fetch_one($stats_stmt);

// Get list of users who have acknowledged
$ack_list_query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as user_name,
                   pa.acknowledged_at, d.dept_name
                   FROM policy_acknowledgements pa
                   JOIN users u ON pa.user_id = u.user_id
                   LEFT JOIN departments d ON u.dept_id = d.dept_id
                   WHERE pa.policy_id = ?
                   ORDER BY pa.acknowledged_at DESC";
$ack_list_stmt = db_query($ack_list_query, [$policy_id]);
$acknowledgements = db_fetch_all($ack_list_stmt);

// Handle acknowledgement submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acknowledge'])) {
    if (!$has_acknowledged) {
        $insert_query = "INSERT INTO policy_acknowledgements (policy_id, user_id, acknowledged_at)
                         VALUES (?, ?, NOW())";
        db_query($insert_query, [$policy_id, $user_id]);

        log_audit($org_id, $user_id, 'ACKNOWLEDGE', 'policy', $policy_id,
            "Acknowledged policy: " . $policy['policy_title']);

        set_flash_message('Policy acknowledged successfully.', 'success');
        redirect('policy_view.php?id=' . $policy_id);
    }
}

// Status badge color
$status_colors = [
    'draft' => 'warning',
    'published' => 'success',
    'archived' => 'default'
];
$status_color = $status_colors[$policy['status']] ?? 'secondary';

$ack_percentage = $stats['total_users'] > 0
    ? round(($stats['ack_count'] / $stats['total_users']) * 100)
    : 0;

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Policy</title>
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
                        <h2>Policy Details</h2>
                        <h5><?php echo htmlspecialchars($policy['policy_title']); ?></h5>
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

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php if (is_admin() || is_dpo()): ?>
                    <a href="policy_add.php?id=<?php echo $policy_id; ?>" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit Policy
                    </a>
                    <?php endif; ?>
                    <a href="policy_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    <?php if ($policy['document_path'] && file_exists($policy['document_path'])): ?>
                    <a href="<?php echo htmlspecialchars($policy['document_path']); ?>" class="btn btn-success" target="_blank">
                        <i class="fa fa-download"></i> Download Policy Document
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-6">

            <!-- Policy Information -->
            <div class="card border-primary">
                <div class="card-header">
                    <i class="fa fa-info-circle"></i> Policy Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Policy Title:</th>
                            <td><strong><?php echo htmlspecialchars($policy['policy_title']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Policy Type:</th>
                            <td>
                                <?php
                                $types = [
                                    'data_protection' => 'Data Protection',
                                    'privacy' => 'Privacy',
                                    'security' => 'Security',
                                    'retention' => 'Data Retention',
                                    'breach' => 'Breach Response',
                                    'other' => 'Other'
                                ];
                                echo $types[$policy['policy_type']] ?? 'Unknown';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Version:</th>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($policy['version']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?php echo $status_color; ?>" style="font-size: 14px; padding: 8px 12px;">
                                    <?php echo strtoupper($policy['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($policy['description']): ?>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo nl2br(htmlspecialchars($policy['description'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($policy['published_at']): ?>
                        <tr>
                            <th>Published Date:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($policy['published_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($policy['review_due_date']): ?>
                        <tr>
                            <th>Review Due:</th>
                            <td>
                                <?php
                                $review_date = strtotime($policy['review_due_date']);
                                $is_overdue = $review_date < time();
                                ?>
                                <?php echo date('d F Y', $review_date); ?>
                                <?php if ($is_overdue && $policy['status'] === 'published'): ?>
                                    <span class="badge bg-danger">OVERDUE</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-clock-o"></i> Record Metadata
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Created By:</th>
                            <td>
                                <?php echo htmlspecialchars($policy['created_by_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($policy['created_by_email']); ?></small>
                            </td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($policy['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($policy['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-md-6">

            <!-- Acknowledgement Status -->
            <div class="card border-<?php echo $has_acknowledged ? 'success' : 'warning'; ?>">
                <div class="card-header">
                    <i class="fa fa-check-square-o"></i> Your Acknowledgement Status
                </div>
                <div class="card-body">
                    <?php if ($has_acknowledged): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle fa-3x float-start" style="margin-right: 15px;"></i>
                            <h4>You have acknowledged this policy</h4>
                            <p>Thank you for reviewing and acknowledging this policy.</p>
                        </div>
                    <?php else: ?>
                        <?php if ($policy['status'] === 'published'): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle fa-3x float-start" style="margin-right: 15px;"></i>
                            <h4>Acknowledgement Required</h4>
                            <p>Please read the policy document and acknowledge that you have understood it.</p>
                        </div>
                        <form method="POST" onsubmit="return confirm('By clicking OK, you confirm that you have read and understood this policy.');">
                            <button type="submit" name="acknowledge" class="btn btn-success btn-lg btn-block">
                                <i class="fa fa-check"></i> I Have Read and Understood This Policy
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            This policy is currently in <strong><?php echo $policy['status']; ?></strong> status and does not require acknowledgement.
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acknowledgement Statistics -->
            <div class="card border-info">
                <div class="card-header">
                    <i class="fa fa-bar-chart"></i> Acknowledgement Statistics
                </div>
                <div class="card-body">
                    <h4>Overall Acknowledgement Rate</h4>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar bg-<?php echo $ack_percentage >= 80 ? 'success' : ($ack_percentage >= 50 ? 'warning' : 'danger'); ?>"
                             role="progressbar"
                             style="width: <?php echo $ack_percentage; ?>%; font-size: 16px; line-height: 30px;">
                            <?php echo $ack_percentage; ?>%
                        </div>
                    </div>
                    <p class="text-center">
                        <strong><?php echo $stats['ack_count']; ?></strong> of <strong><?php echo $stats['total_users']; ?></strong> staff members have acknowledged
                    </p>

                    <?php if (is_admin() || is_dpo()): ?>
                    <hr>
                    <h4>Recent Acknowledgements</h4>
                    <?php if (!empty($acknowledgements)): ?>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <ul class="list-unstyled">
                                <?php foreach (array_slice($acknowledgements, 0, 10) as $ack): ?>
                                    <li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                                        <i class="fa fa-user"></i> <strong><?php echo htmlspecialchars($ack['user_name']); ?></strong>
                                        <?php if (!empty($ack['dept_name'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($ack['dept_name']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-muted">
                                            <i class="fa fa-clock-o"></i> <?php echo date('d M Y, H:i', strtotime($ack['acknowledged_at'])); ?>
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No acknowledgements yet.</p>
                    <?php endif; ?>
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
