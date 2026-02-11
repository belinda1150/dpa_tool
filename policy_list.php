<?php
/**
 * Policy List Page
 * Display all organizational policies with acknowledgement tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all policies
$query = "SELECT p.*,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
          COUNT(DISTINCT pa.ack_id) as acknowledgement_count,
          (SELECT COUNT(*) FROM users WHERE org_id = ? AND status = 'active') as total_users
          FROM policies p
          LEFT JOIN users u ON p.created_by = u.user_id
          LEFT JOIN policy_acknowledgements pa ON p.policy_id = pa.policy_id
          WHERE p.org_id = ?
          GROUP BY p.policy_id
          ORDER BY p.created_at DESC";

$stmt = db_query($query, [$org_id, $org_id]);
$policies = db_fetch_all($stmt);

// Calculate statistics
$total_policies = count($policies);
$draft_count = 0;
$published_count = 0;
$archived_count = 0;
$overdue_review_count = 0;

foreach ($policies as $policy) {
    if ($policy['status'] === 'draft') $draft_count++;
    if ($policy['status'] === 'published') $published_count++;
    if ($policy['status'] === 'archived') $archived_count++;
    if ($policy['review_due_date'] && strtotime($policy['review_due_date']) < time() && $policy['status'] === 'published') {
        $overdue_review_count++;
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Policies</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
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
        .status-published { background-color: #5cb85c; color: white; }
        .status-archived { background-color: #777; color: white; }
        .overdue-badge {
            background-color: #d9534f;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Policy Management</h2>
                        <h5>Organizational policies and documentation</h5>
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
                            <i class="fa fa-file-text fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_policies; ?></div>
                            <div>Total Policies</div>
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
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $published_count; ?></div>
                            <div>Published</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-pencil fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $draft_count; ?></div>
                            <div>Drafts</div>
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
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $overdue_review_count; ?></div>
                            <div>Overdue Review</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php if (is_admin() || is_dpo()): ?>
                    <a href="policy_add.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add New Policy
                    </a>
                    <?php endif; ?>
                    <a href="training_list.php" class="btn btn-info">
                        <i class="fa fa-graduation-cap"></i> View Training Courses
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Policies Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-table"></i> Organizational Policies
                    <div class="float-end">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search policies..." style="width: 200px; display: inline-block;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($policies)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No policies created yet. Click "Add New Policy" to create one.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="policiesTable">
                                <thead>
                                    <tr>
                                        <th>Policy Title</th>
                                        <th>Type</th>
                                        <th>Version</th>
                                        <th>Status</th>
                                        <th>Acknowledgements</th>
                                        <th>Review Due</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($policies as $policy): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($policy['policy_title']); ?></strong>
                                                <?php if ($policy['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($policy['description'], 0, 80)) . (strlen($policy['description']) > 80 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php
                                                    $types = [
                                                        'data_protection' => 'Data Protection',
                                                        'privacy' => 'Privacy',
                                                        'security' => 'Security',
                                                        'retention' => 'Retention',
                                                        'breach' => 'Breach Response',
                                                        'other' => 'Other'
                                                    ];
                                                    echo $types[$policy['policy_type']] ?? 'Unknown';
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($policy['version']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $policy['status']; ?>">
                                                    <?php echo strtoupper($policy['status']); ?>
                                                </span>
                                                <?php if ($policy['status'] === 'published' && $policy['review_due_date'] && strtotime($policy['review_due_date']) < time()): ?>
                                                    <span class="overdue-badge">OVERDUE</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $ack_percentage = $policy['total_users'] > 0
                                                    ? round(($policy['acknowledgement_count'] / $policy['total_users']) * 100)
                                                    : 0;
                                                ?>
                                                <div class="progress" style="margin-bottom: 0;">
                                                    <div class="progress-bar bg-<?php echo $ack_percentage >= 80 ? 'success' : ($ack_percentage >= 50 ? 'warning' : 'danger'); ?>"
                                                         role="progressbar"
                                                         style="width: <?php echo $ack_percentage; ?>%">
                                                        <?php echo $ack_percentage; ?>%
                                                    </div>
                                                </div>
                                                <small class="text-muted"><?php echo $policy['acknowledgement_count']; ?> of <?php echo $policy['total_users']; ?> users</small>
                                            </td>
                                            <td>
                                                <?php if ($policy['review_due_date']): ?>
                                                    <?php echo date('d M Y', strtotime($policy['review_due_date'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($policy['created_at'])); ?>
                                                <br><small class="text-muted">by <?php echo htmlspecialchars($policy['created_by_name']); ?></small>
                                            </td>
                                            <td>
                                                <a href="policy_view.php?id=<?php echo $policy['policy_id']; ?>" class="btn btn-info btn-sm" title="View Policy">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <?php if (is_admin() || is_dpo()): ?>
                                                <a href="policy_add.php?id=<?php echo $policy['policy_id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fa fa-edit"></i>
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

<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
<script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#policiesTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>
</body>
</html>
