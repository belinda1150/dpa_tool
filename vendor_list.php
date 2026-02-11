<?php
/**
 * DPA Tool - Vendor List
 * Display all vendors with risk indicators and statistics
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Fetch vendors with latest assessment scores and certification counts
$query = "SELECT v.*,
          u.first_name as created_by_first, u.last_name as created_by_last,
          approver.first_name as approver_first, approver.last_name as approver_last,
          (SELECT vra.inherent_risk_score
           FROM vendor_risk_assessments vra
           WHERE vra.vendor_id = v.vendor_id AND vra.status IN ('completed', 'approved')
           ORDER BY vra.assessment_date DESC LIMIT 1) as latest_risk_score,
          (SELECT vra.inherent_risk_level
           FROM vendor_risk_assessments vra
           WHERE vra.vendor_id = v.vendor_id AND vra.status IN ('completed', 'approved')
           ORDER BY vra.assessment_date DESC LIMIT 1) as latest_risk_level,
          (SELECT COUNT(*) FROM vendor_certifications vc
           WHERE vc.vendor_id = v.vendor_id AND vc.status = 'valid') as valid_certs,
          (SELECT COUNT(*) FROM vendor_ropa vr WHERE vr.vendor_id = v.vendor_id) as ropa_count
          FROM vendors v
          LEFT JOIN users u ON v.created_by = u.user_id
          LEFT JOIN users approver ON v.approved_by = approver.user_id
          WHERE v.org_id = ?
          ORDER BY v.created_at DESC";

$stmt = db_query($query, [$org_id]);
$vendors = db_fetch_all($stmt);

// Calculate statistics
$total_vendors = count($vendors);
$active_vendors = 0;
$high_risk_vendors = 0;
$pending_review = 0;
$expiring_contracts = 0;
$expiring_dpas = 0;
$overdue_reviews = 0;

foreach ($vendors as $vendor) {
    if ($vendor['status'] === 'active') $active_vendors++;
    if ($vendor['status'] === 'pending_review') $pending_review++;
    if ($vendor['latest_risk_score'] >= VENDOR_HIGH_RISK_THRESHOLD) $high_risk_vendors++;

    // Check for expiring contracts (within 60 days)
    if (!empty($vendor['contract_end_date']) &&
        strtotime($vendor['contract_end_date']) <= strtotime('+' . VENDOR_CONTRACT_EXPIRY_ALERT_DAYS . ' days') &&
        strtotime($vendor['contract_end_date']) > time()) {
        $expiring_contracts++;
    }

    // Check for expiring DPAs
    if (!empty($vendor['dpa_expiry_date']) && $vendor['dpa_status'] === 'signed' &&
        strtotime($vendor['dpa_expiry_date']) <= strtotime('+' . VENDOR_DPA_EXPIRY_ALERT_DAYS . ' days') &&
        strtotime($vendor['dpa_expiry_date']) > time()) {
        $expiring_dpas++;
    }

    // Check for overdue reviews
    if (!empty($vendor['next_review_date']) &&
        strtotime($vendor['next_review_date']) < time() &&
        $vendor['status'] === 'active') {
        $overdue_reviews++;
    }
}

// Get flash message
$flash = get_flash_message();

// Helper function for risk level badge
function get_risk_badge($level, $score = null) {
    $badges = [
        'critical' => '<span class="badge bg-danger">Critical</span>',
        'high' => '<span class="badge bg-warning text-dark">High</span>',
        'medium' => '<span class="badge bg-info text-dark">Medium</span>',
        'low' => '<span class="badge bg-success">Low</span>'
    ];
    return $badges[$level] ?? '<span class="badge bg-secondary">Not Assessed</span>';
}

// Helper function for status badge
function get_status_badge($status) {
    $badges = [
        'pending_review' => '<span class="badge bg-warning text-dark">Pending Review</span>',
        'approved' => '<span class="badge bg-info text-dark">Approved</span>',
        'active' => '<span class="badge bg-success">Active</span>',
        'suspended' => '<span class="badge bg-danger">Suspended</span>',
        'terminated' => '<span class="badge bg-secondary">Terminated</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

// Helper function for DPA status badge
function get_dpa_badge($status) {
    $badges = [
        'none' => '<span class="badge bg-secondary">None</span>',
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'under_review' => '<span class="badge bg-info text-dark">Under Review</span>',
        'signed' => '<span class="badge bg-success">Signed</span>',
        'expired' => '<span class="badge bg-danger">Expired</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Vendor Management</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap5.css?v=2" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .stat-card {
            border-left: 4px solid #337ab7;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .stat-card.warning { border-left-color: #f0ad4e; }
        .stat-card.danger { border-left-color: #d9534f; }
        .stat-card.success { border-left-color: #5cb85c; }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        .stat-card .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .vendor-type-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            background: #eee;
            color: #666;
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
                        <h2>Vendor Management</h2>
                        <h5>Manage vendors and third-party risk</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_vendors; ?></div>
                            <div class="stat-label">Total Vendors</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card success">
                            <div class="stat-value"><?php echo $active_vendors; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card warning">
                            <div class="stat-value"><?php echo $pending_review; ?></div>
                            <div class="stat-label">Pending Review</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card danger">
                            <div class="stat-value"><?php echo $high_risk_vendors; ?></div>
                            <div class="stat-label">High Risk</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card warning">
                            <div class="stat-value"><?php echo $expiring_dpas; ?></div>
                            <div class="stat-label">DPAs Expiring</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card <?php echo $overdue_reviews > 0 ? 'danger' : ''; ?>">
                            <div class="stat-value"><?php echo $overdue_reviews; ?></div>
                            <div class="stat-label">Overdue Reviews</div>
                        </div>
                    </div>
                </div>

                <!-- Vendor Register -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-building"></i> Vendor Register
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <a href="vendor_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New Vendor
                                        </a>
                                        <a href="vendor_export.php" class="btn btn-success btn-sm">
                                            <i class="fa fa-download"></i> Export
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="vendorTable">
                                        <thead>
                                            <tr>
                                                <th>Vendor Name</th>
                                                <th>Type</th>
                                                <th>Country</th>
                                                <th>Status</th>
                                                <th>Risk Level</th>
                                                <th>DPA Status</th>
                                                <th>Certifications</th>
                                                <th>Next Review</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($vendors)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted">
                                                    <i class="fa fa-info-circle"></i> No vendors found. Click "Add New Vendor" to create one.
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($vendors as $vendor): ?>
                                            <tr>
                                                <td>
                                                    <strong><a href="vendor_view.php?id=<?php echo $vendor['vendor_id']; ?>">
                                                        <?php echo htmlspecialchars($vendor['vendor_name']); ?>
                                                    </a></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($vendor['vendor_ref']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="vendor-type-badge">
                                                        <?php echo ucwords(str_replace('_', ' ', $vendor['vendor_type'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($vendor['country'] ?? 'N/A'); ?></td>
                                                <td><?php echo get_status_badge($vendor['status']); ?></td>
                                                <td>
                                                    <?php echo get_risk_badge($vendor['latest_risk_level'], $vendor['latest_risk_score']); ?>
                                                    <?php if ($vendor['latest_risk_score']): ?>
                                                        <small class="text-muted">(<?php echo number_format($vendor['latest_risk_score'], 1); ?>)</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo get_dpa_badge($vendor['dpa_status']); ?>
                                                    <?php if ($vendor['dpa_expiry_date'] && $vendor['dpa_status'] === 'signed'): ?>
                                                        <?php
                                                        $days_until = days_until($vendor['dpa_expiry_date']);
                                                        if ($days_until <= VENDOR_DPA_EXPIRY_ALERT_DAYS && $days_until > 0): ?>
                                                            <br><small class="text-warning"><i class="fa fa-warning"></i> Expires in <?php echo $days_until; ?> days</small>
                                                        <?php elseif ($days_until <= 0): ?>
                                                            <br><small class="text-danger"><i class="fa fa-warning"></i> Expired</small>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($vendor['valid_certs'] > 0): ?>
                                                        <span class="badge badge-success"><?php echo $vendor['valid_certs']; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($vendor['next_review_date']): ?>
                                                        <?php
                                                        $review_days = days_until($vendor['next_review_date']);
                                                        $review_class = '';
                                                        if ($review_days < 0) $review_class = 'text-danger';
                                                        elseif ($review_days <= VENDOR_REVIEW_ALERT_DAYS) $review_class = 'text-warning';
                                                        ?>
                                                        <span class="<?php echo $review_class; ?>">
                                                            <?php echo format_date($vendor['next_review_date'], 'd M Y'); ?>
                                                        </span>
                                                        <?php if ($review_days < 0): ?>
                                                            <br><small class="text-danger"><i class="fa fa-warning"></i> Overdue</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not set</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="vendor_view.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-info btn-sm" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="vendor_edit.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <?php if ($vendor['status'] !== 'terminated'): ?>
                                                    <a href="vendor_assessment_add.php?vendor_id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-primary btn-sm" title="New Assessment">
                                                        <i class="fa fa-clipboard"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap5.js"></script>
    <script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
    <script>
        $(document).ready(function() {
            <?php if (!empty($vendors)): ?>
            $('#vendorTable').dataTable({
                "order": [[0, "asc"]],
                "pageLength": 25,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                "columnDefs": [
                    { "orderable": false, "targets": [8] }
                ]
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
