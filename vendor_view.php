<?php
/**
 * DPA Tool - View Vendor
 * Display detailed vendor information with assessments, certifications, and due diligence
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Get vendor ID
$vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$vendor_id) {
    set_flash_message('Invalid vendor ID.', 'error');
    redirect('vendor_list.php');
}

// Fetch vendor details
$query = "SELECT v.*,
          creator.first_name as created_by_first, creator.last_name as created_by_last,
          approver.first_name as approver_first, approver.last_name as approver_last
          FROM vendors v
          LEFT JOIN users creator ON v.created_by = creator.user_id
          LEFT JOIN users approver ON v.approved_by = approver.user_id
          WHERE v.vendor_id = ? AND v.org_id = ?";
$stmt = db_query($query, [$vendor_id, $org_id]);
$vendor = db_fetch_one($stmt);

if (!$vendor) {
    set_flash_message('Vendor not found.', 'error');
    redirect('vendor_list.php');
}

// Fetch risk assessments
$assess_query = "SELECT vra.*,
                 assessor.first_name as assessor_first, assessor.last_name as assessor_last,
                 approver.first_name as approver_first, approver.last_name as approver_last
                 FROM vendor_risk_assessments vra
                 LEFT JOIN users assessor ON vra.assessed_by = assessor.user_id
                 LEFT JOIN users approver ON vra.approved_by = approver.user_id
                 WHERE vra.vendor_id = ?
                 ORDER BY vra.assessment_date DESC";
$stmt = db_query($assess_query, [$vendor_id]);
$assessments = db_fetch_all($stmt);

// Fetch certifications
$cert_query = "SELECT vc.*,
               verifier.first_name as verifier_first, verifier.last_name as verifier_last
               FROM vendor_certifications vc
               LEFT JOIN users verifier ON vc.verified_by = verifier.user_id
               WHERE vc.vendor_id = ?
               ORDER BY vc.expiry_date DESC";
$stmt = db_query($cert_query, [$vendor_id]);
$certifications = db_fetch_all($stmt);

// Fetch due diligence records
$diligence_query = "SELECT vdd.*,
                    creator.first_name as creator_first, creator.last_name as creator_last,
                    reviewer.first_name as reviewer_first, reviewer.last_name as reviewer_last
                    FROM vendor_due_diligence vdd
                    LEFT JOIN users creator ON vdd.created_by = creator.user_id
                    LEFT JOIN users reviewer ON vdd.reviewed_by = reviewer.user_id
                    WHERE vdd.vendor_id = ?
                    ORDER BY vdd.created_at DESC";
$stmt = db_query($diligence_query, [$vendor_id]);
$due_diligence = db_fetch_all($stmt);

// Fetch linked ROPA entries
$ropa_query = "SELECT pa.ropa_id, pa.activity_name, pa.status, vr.relationship_type
               FROM vendor_ropa vr
               JOIN processing_activities pa ON vr.ropa_id = pa.ropa_id
               WHERE vr.vendor_id = ?
               ORDER BY pa.activity_name";
$stmt = db_query($ropa_query, [$vendor_id]);
$linked_ropa = db_fetch_all($stmt);

// Fetch audit log
$audit_query = "SELECT al.*, u.first_name, u.last_name
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.user_id
                WHERE al.entity_type = 'vendor' AND al.entity_id = ?
                ORDER BY al.created_at DESC LIMIT 10";
$stmt = db_query($audit_query, [$vendor_id]);
$audit_logs = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();

// Helper functions
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

function get_risk_badge($level) {
    $badges = [
        'critical' => '<span class="badge bg-danger">Critical</span>',
        'high' => '<span class="badge bg-warning text-dark">High</span>',
        'medium' => '<span class="badge bg-info text-dark">Medium</span>',
        'low' => '<span class="badge bg-success">Low</span>'
    ];
    return $badges[$level] ?? '<span class="badge bg-secondary">Not Assessed</span>';
}

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

function get_cert_badge($status) {
    $badges = [
        'valid' => '<span class="badge bg-success">Valid</span>',
        'expired' => '<span class="badge bg-danger">Expired</span>',
        'pending_renewal' => '<span class="badge bg-warning text-dark">Pending Renewal</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function get_diligence_badge($result) {
    $badges = [
        'pending' => '<span class="badge bg-secondary">Pending</span>',
        'passed' => '<span class="badge bg-success">Passed</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>',
        'conditional' => '<span class="badge bg-warning text-dark">Conditional</span>'
    ];
    return $badges[$result] ?? '<span class="badge bg-secondary">' . ucfirst($result) . '</span>';
}

// Get latest assessment for summary
$latest_assessment = !empty($assessments) ? $assessments[0] : null;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Vendor</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .info-row { margin-bottom: 10px; }
        .info-label { font-weight: bold; color: #666; }
        .section-title { border-bottom: 2px solid #337ab7; padding-bottom: 5px; margin-bottom: 15px; color: #337ab7; }
        .risk-summary { padding: 15px; background: #f5f5f5; border-radius: 5px; margin-bottom: 15px; }
        .risk-category { margin-bottom: 10px; }
        .risk-category .label { font-size: 11px; }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-8">
                        <h2><?php echo htmlspecialchars($vendor['vendor_name']); ?></h2>
                        <h5>
                            <span class="text-muted"><?php echo htmlspecialchars($vendor['vendor_ref']); ?></span>
                            &nbsp;|&nbsp;
                            <?php echo get_status_badge($vendor['status']); ?>
                            &nbsp;|&nbsp;
                            <span class="text-muted"><?php echo ucwords(str_replace('_', ' ', $vendor['vendor_type'])); ?></span>
                        </h5>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="vendor_edit.php?id=<?php echo $vendor_id; ?>" class="btn btn-warning">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a href="vendor_assessment_add.php?vendor_id=<?php echo $vendor_id; ?>" class="btn btn-primary">
                            <i class="fa fa-clipboard"></i> New Assessment
                        </a>
                        <a href="vendor_list.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-8">

                        <!-- Vendor Details -->
                        <div class="card border-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-building"></i> Vendor Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">Country:</span>
                                            <?php echo htmlspecialchars($vendor['country'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Website:</span>
                                            <?php if ($vendor['website']): ?>
                                                <a href="<?php echo htmlspecialchars($vendor['website']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($vendor['website']); ?>
                                                </a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Criticality:</span>
                                            <?php
                                            $crit_badges = [
                                                'low' => '<span class="badge bg-success">Low</span>',
                                                'medium' => '<span class="badge bg-info text-dark">Medium</span>',
                                                'high' => '<span class="badge bg-warning text-dark">High</span>',
                                                'critical' => '<span class="badge bg-danger">Critical</span>'
                                            ];
                                            echo $crit_badges[$vendor['criticality']] ?? $vendor['criticality'];
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">Primary Contact:</span>
                                            <?php echo htmlspecialchars($vendor['primary_contact_name'] ?? 'N/A'); ?>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Email:</span>
                                            <?php if ($vendor['primary_contact_email']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($vendor['primary_contact_email']); ?>">
                                                    <?php echo htmlspecialchars($vendor['primary_contact_email']); ?>
                                                </a>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Phone:</span>
                                            <?php echo htmlspecialchars($vendor['primary_contact_phone'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($vendor['description']): ?>
                                <div class="info-row">
                                    <span class="info-label">Description:</span><br>
                                    <?php echo nl2br(htmlspecialchars($vendor['description'])); ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($vendor['address']): ?>
                                <div class="info-row">
                                    <span class="info-label">Address:</span><br>
                                    <?php echo nl2br(htmlspecialchars($vendor['address'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- DPA & Contract -->
                        <div class="card border-success">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-file-text"></i> DPA & Contract</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">DPA Status:</span>
                                            <?php echo get_dpa_badge($vendor['dpa_status']); ?>
                                        </div>
                                        <?php if ($vendor['dpa_signed_date']): ?>
                                        <div class="info-row">
                                            <span class="info-label">DPA Signed:</span>
                                            <?php echo format_date($vendor['dpa_signed_date'], 'd M Y'); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($vendor['dpa_expiry_date']): ?>
                                        <div class="info-row">
                                            <span class="info-label">DPA Expiry:</span>
                                            <?php
                                            $days = days_until($vendor['dpa_expiry_date']);
                                            $class = $days < 0 ? 'text-danger' : ($days <= 60 ? 'text-warning' : '');
                                            ?>
                                            <span class="<?php echo $class; ?>">
                                                <?php echo format_date($vendor['dpa_expiry_date'], 'd M Y'); ?>
                                                <?php if ($days < 0): ?>
                                                    <strong>(EXPIRED)</strong>
                                                <?php elseif ($days <= 60): ?>
                                                    (<?php echo $days; ?> days remaining)
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($vendor['contract_ref']): ?>
                                        <div class="info-row">
                                            <span class="info-label">Contract Ref:</span>
                                            <?php echo htmlspecialchars($vendor['contract_ref']); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($vendor['contract_start_date']): ?>
                                        <div class="info-row">
                                            <span class="info-label">Contract Period:</span>
                                            <?php echo format_date($vendor['contract_start_date'], 'd M Y'); ?>
                                            <?php if ($vendor['contract_end_date']): ?>
                                                - <?php echo format_date($vendor['contract_end_date'], 'd M Y'); ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <div class="info-row">
                                            <span class="info-label">Review Frequency:</span>
                                            <?php echo $vendor['review_frequency_months']; ?> months
                                        </div>
                                        <?php if ($vendor['next_review_date']): ?>
                                        <div class="info-row">
                                            <span class="info-label">Next Review:</span>
                                            <?php
                                            $review_days = days_until($vendor['next_review_date']);
                                            $review_class = $review_days < 0 ? 'text-danger' : ($review_days <= 30 ? 'text-warning' : '');
                                            ?>
                                            <span class="<?php echo $review_class; ?>">
                                                <?php echo format_date($vendor['next_review_date'], 'd M Y'); ?>
                                                <?php if ($review_days < 0): ?>
                                                    <strong>(OVERDUE)</strong>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Processing Details -->
                        <div class="card border-warning">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-database"></i> Data Processing Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">Data Volume:</span>
                                            <?php echo ucwords(str_replace('_', ' ', $vendor['data_volume'])); ?>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">Access Type:</span>
                                            <?php echo ucwords(str_replace('_', ' ', $vendor['access_type'])); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <span class="info-label">Uses Sub-processors:</span>
                                            <?php echo $vendor['has_subprocessors'] ? '<span class="text-warning">Yes</span>' : 'No'; ?>
                                        </div>
                                        <div class="info-row">
                                            <span class="info-label">International Transfers:</span>
                                            <?php echo $vendor['transfers_data_internationally'] ? '<span class="text-warning">Yes</span>' : 'No'; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($vendor['data_categories_processed']): ?>
                                <div class="info-row">
                                    <span class="info-label">Data Categories:</span><br>
                                    <?php echo nl2br(htmlspecialchars($vendor['data_categories_processed'])); ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($vendor['processing_purpose']): ?>
                                <div class="info-row">
                                    <span class="info-label">Processing Purpose:</span><br>
                                    <?php echo nl2br(htmlspecialchars($vendor['processing_purpose'])); ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($vendor['has_subprocessors'] && $vendor['subprocessor_details']): ?>
                                <div class="info-row">
                                    <span class="info-label">Sub-processor Details:</span><br>
                                    <?php echo nl2br(htmlspecialchars($vendor['subprocessor_details'])); ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($vendor['transfers_data_internationally'] && $vendor['data_transfer_countries']): ?>
                                <div class="info-row">
                                    <span class="info-label">Transfer Countries:</span>
                                    <?php echo htmlspecialchars($vendor['data_transfer_countries']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Risk Assessments -->
                        <div class="card border-danger">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fa fa-line-chart"></i> Risk Assessments
                                    <a href="vendor_assessment_add.php?vendor_id=<?php echo $vendor_id; ?>" class="btn btn-sm btn-secondary float-end">
                                        <i class="fa fa-plus"></i> New Assessment
                                    </a>
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($assessments)): ?>
                                    <p class="text-muted text-center">
                                        <i class="fa fa-info-circle"></i> No risk assessments yet.
                                        <a href="vendor_assessment_add.php?vendor_id=<?php echo $vendor_id; ?>">Create first assessment</a>
                                    </p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Risk Score</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assessments as $assess): ?>
                                                <tr>
                                                    <td><?php echo format_date($assess['assessment_date'], 'd M Y'); ?></td>
                                                    <td><?php echo ucwords(str_replace('_', ' ', $assess['assessment_type'])); ?></td>
                                                    <td>
                                                        <?php echo get_risk_badge($assess['inherent_risk_level']); ?>
                                                        <small>(<?php echo number_format($assess['inherent_risk_score'], 1); ?>)</small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $assess_badges = [
                                                            'draft' => '<span class="badge bg-secondary">Draft</span>',
                                                            'in_progress' => '<span class="badge bg-info text-dark">In Progress</span>',
                                                            'completed' => '<span class="badge bg-primary">Completed</span>',
                                                            'approved' => '<span class="badge bg-success">Approved</span>'
                                                        ];
                                                        echo $assess_badges[$assess['status']] ?? $assess['status'];
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <a href="vendor_assessment_view.php?id=<?php echo $assess['assessment_id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Certifications -->
                        <div class="card border-info">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fa fa-certificate"></i> Certifications
                                    <a href="vendor_certification_add.php?vendor_id=<?php echo $vendor_id; ?>" class="btn btn-sm btn-secondary float-end">
                                        <i class="fa fa-plus"></i> Add Certification
                                    </a>
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($certifications)): ?>
                                    <p class="text-muted text-center">
                                        <i class="fa fa-info-circle"></i> No certifications recorded.
                                        <a href="vendor_certification_add.php?vendor_id=<?php echo $vendor_id; ?>">Add certification</a>
                                    </p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Certification</th>
                                                    <th>Issuing Body</th>
                                                    <th>Expiry</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($certifications as $cert): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($cert['certification_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo strtoupper(str_replace('_', ' ', $cert['certification_type'])); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($cert['issuing_body'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($cert['expiry_date']): ?>
                                                            <?php echo format_date($cert['expiry_date'], 'd M Y'); ?>
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo get_cert_badge($cert['status']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Due Diligence -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fa fa-clipboard"></i> Due Diligence
                                    <a href="vendor_diligence_add.php?vendor_id=<?php echo $vendor_id; ?>" class="btn btn-sm btn-secondary float-end">
                                        <i class="fa fa-plus"></i> Add Questionnaire
                                    </a>
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($due_diligence)): ?>
                                    <p class="text-muted text-center">
                                        <i class="fa fa-info-circle"></i> No due diligence records.
                                        <a href="vendor_diligence_add.php?vendor_id=<?php echo $vendor_id; ?>">Add questionnaire</a>
                                    </p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Questionnaire</th>
                                                    <th>Type</th>
                                                    <th>Score</th>
                                                    <th>Result</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($due_diligence as $dd): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($dd['questionnaire_name']); ?></td>
                                                    <td><?php echo ucwords(str_replace('_', ' ', $dd['questionnaire_type'])); ?></td>
                                                    <td>
                                                        <?php if ($dd['score'] !== null): ?>
                                                            <?php echo number_format($dd['score'], 1); ?>%
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo get_diligence_badge($dd['result']); ?></td>
                                                    <td><?php echo format_date($dd['completed_date'] ?? $dd['created_at'], 'd M Y'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column -->
                    <div class="col-md-4">

                        <!-- Risk Summary -->
                        <?php if ($latest_assessment): ?>
                        <div class="card border-danger">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-warning"></i> Latest Risk Assessment</h3>
                            </div>
                            <div class="card-body">
                                <div class="text-center" style="margin-bottom: 15px;">
                                    <h2><?php echo get_risk_badge($latest_assessment['inherent_risk_level']); ?></h2>
                                    <p class="text-muted">
                                        Score: <?php echo number_format($latest_assessment['inherent_risk_score'], 1); ?>/25
                                        <br>
                                        <small>Assessed: <?php echo format_date($latest_assessment['assessment_date'], 'd M Y'); ?></small>
                                    </p>
                                </div>

                                <?php
                                // Display category scores
                                $categories = [
                                    'data_security' => 'Data Security',
                                    'compliance' => 'Compliance',
                                    'operational' => 'Operational',
                                    'financial' => 'Financial',
                                    'reputational' => 'Reputational'
                                ];
                                foreach ($categories as $key => $label):
                                    $likelihood = $latest_assessment[$key . '_likelihood'];
                                    $impact = $latest_assessment[$key . '_impact'];
                                    if ($likelihood && $impact):
                                        $score = $likelihood * $impact;
                                        $class = $score >= 15 ? 'danger' : ($score >= 10 ? 'warning' : ($score >= 5 ? 'info' : 'success'));
                                ?>
                                <div class="risk-category">
                                    <small><?php echo $label; ?>:</small>
                                    <span class="badge bg-<?php echo $class; ?> float-end"><?php echo $score; ?></span>
                                </div>
                                <?php
                                    endif;
                                endforeach;
                                ?>

                                <?php if ($latest_assessment['treatment_strategy']): ?>
                                <hr>
                                <div class="info-row">
                                    <span class="info-label">Treatment:</span>
                                    <?php echo ucfirst($latest_assessment['treatment_strategy']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Linked Processing Activities -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-link"></i> Linked ROPA</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($linked_ropa)): ?>
                                    <p class="text-muted text-center">No linked processing activities</p>
                                <?php else: ?>
                                    <ul class="list-unstyled">
                                        <?php foreach ($linked_ropa as $ropa): ?>
                                        <li style="margin-bottom: 8px;">
                                            <a href="ropa_view.php?id=<?php echo $ropa['ropa_id']; ?>">
                                                <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                            </a>
                                            <br><small class="text-muted"><?php echo ucfirst($ropa['relationship_type']); ?></small>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Vendor DPO -->
                        <?php if ($vendor['dpo_name'] || $vendor['dpo_email']): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-user-secret"></i> Vendor DPO</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($vendor['dpo_name']): ?>
                                <div class="info-row">
                                    <span class="info-label">Name:</span>
                                    <?php echo htmlspecialchars($vendor['dpo_name']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($vendor['dpo_email']): ?>
                                <div class="info-row">
                                    <span class="info-label">Email:</span>
                                    <a href="mailto:<?php echo htmlspecialchars($vendor['dpo_email']); ?>">
                                        <?php echo htmlspecialchars($vendor['dpo_email']); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Metadata -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-info-circle"></i> Metadata</h3>
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <span class="info-label">Created:</span>
                                    <?php echo format_datetime($vendor['created_at'], 'd M Y H:i'); ?>
                                    <br><small class="text-muted">by <?php echo htmlspecialchars($vendor['created_by_first'] . ' ' . $vendor['created_by_last']); ?></small>
                                </div>
                                <?php if ($vendor['approved_by']): ?>
                                <div class="info-row">
                                    <span class="info-label">Approved:</span>
                                    <?php echo format_datetime($vendor['approved_at'], 'd M Y H:i'); ?>
                                    <br><small class="text-muted">by <?php echo htmlspecialchars($vendor['approver_first'] . ' ' . $vendor['approver_last']); ?></small>
                                </div>
                                <?php endif; ?>
                                <div class="info-row">
                                    <span class="info-label">Last Updated:</span>
                                    <?php echo format_datetime($vendor['updated_at'], 'd M Y H:i'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Log -->
                        <?php if (!empty($audit_logs)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-history"></i> Recent Activity</h3>
                            </div>
                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($audit_logs as $log): ?>
                                <div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                                    <small class="text-muted"><?php echo format_datetime($log['created_at'], 'd M Y H:i'); ?></small>
                                    <br>
                                    <strong><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></strong>
                                    <?php echo ucfirst($log['action']); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-cog"></i> Actions</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($vendor['status'] === 'pending_review' && (has_permission('DPO') || is_admin())): ?>
                                <a href="vendor_approve.php?id=<?php echo $vendor_id; ?>&action=approve" class="btn btn-success btn-block" onclick="return confirm('Approve this vendor?');">
                                    <i class="fa fa-check"></i> Approve Vendor
                                </a>
                                <?php endif; ?>

                                <?php if ($vendor['status'] === 'approved'): ?>
                                <a href="vendor_approve.php?id=<?php echo $vendor_id; ?>&action=activate" class="btn btn-success btn-block" onclick="return confirm('Activate this vendor?');">
                                    <i class="fa fa-play"></i> Activate Vendor
                                </a>
                                <?php endif; ?>

                                <?php if ($vendor['status'] === 'active'): ?>
                                <a href="vendor_approve.php?id=<?php echo $vendor_id; ?>&action=suspend" class="btn btn-warning btn-block" onclick="return confirm('Suspend this vendor?');">
                                    <i class="fa fa-pause"></i> Suspend Vendor
                                </a>
                                <?php endif; ?>

                                <?php if ($vendor['status'] === 'suspended'): ?>
                                <a href="vendor_approve.php?id=<?php echo $vendor_id; ?>&action=activate" class="btn btn-success btn-block" onclick="return confirm('Reactivate this vendor?');">
                                    <i class="fa fa-play"></i> Reactivate Vendor
                                </a>
                                <?php endif; ?>

                                <?php if ($vendor['status'] !== 'terminated'): ?>
                                <a href="vendor_delete.php?id=<?php echo $vendor_id; ?>" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to terminate this vendor? This action cannot be undone.');">
                                    <i class="fa fa-times"></i> Terminate Vendor
                                </a>
                                <?php endif; ?>
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
