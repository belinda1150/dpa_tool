<?php
/**
 * DPA Tool - View ROPA Entry
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();
$ropa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$ropa_id) {
    set_flash_message('Invalid ROPA entry.', 'error');
    redirect('ropa_list.php');
}

// Get ROPA entry
$query = "SELECT pa.*, d.dept_name, p.purpose_name, lb.basis_name, lb.cdpa_reference,
          rp.policy_name, rp.retention_period, u.first_name, u.last_name, u.email as creator_email
          FROM processing_activities pa
          LEFT JOIN departments d ON pa.dept_id = d.dept_id
          LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
          LEFT JOIN lawful_basis lb ON pa.lawful_basis_id = lb.lawful_basis_id
          LEFT JOIN retention_policies rp ON pa.retention_id = rp.retention_id
          LEFT JOIN users u ON pa.created_by = u.user_id
          WHERE pa.ropa_id = ? AND pa.org_id = ?";
$stmt = db_query($query, [$ropa_id, $org_id]);
$ropa = db_fetch_one($stmt);

if (!$ropa) {
    set_flash_message('ROPA entry not found.', 'error');
    redirect('ropa_list.php');
}

// Get subject categories
$query = "SELECT sc.category_name, sc.is_vulnerable
          FROM ropa_subject_categories rsc
          JOIN subject_categories sc ON rsc.subject_category_id = sc.subject_category_id
          WHERE rsc.ropa_id = ?";
$stmt = db_query($query, [$ropa_id]);
$subject_categories = db_fetch_all($stmt);

// Get data categories
$query = "SELECT dc.category_name, dc.is_special_category
          FROM ropa_data_categories rdc
          JOIN data_categories dc ON rdc.data_category_id = dc.data_category_id
          WHERE rdc.ropa_id = ?";
$stmt = db_query($query, [$ropa_id]);
$data_categories = db_fetch_all($stmt);

// Get recipients
$query = "SELECT r.recipient_name, r.recipient_type, r.country
          FROM ropa_recipients rr
          JOIN recipients r ON rr.recipient_id = r.recipient_id
          WHERE rr.ropa_id = ?";
$stmt = db_query($query, [$ropa_id]);
$recipients = db_fetch_all($stmt);

// Get storage locations
$query = "SELECT sl.location_name, sl.location_type, sl.country, sl.provider
          FROM ropa_storage_locations rsl
          JOIN storage_locations sl ON rsl.location_id = sl.location_id
          WHERE rsl.ropa_id = ?";
$stmt = db_query($query, [$ropa_id]);
$storage_locations = db_fetch_all($stmt);

// Get linked DPIAs
$query = "SELECT dpia_id, dpia_title, status, residual_risk_score, created_at
          FROM dpia WHERE ropa_id = ? ORDER BY created_at DESC";
$stmt = db_query($query, [$ropa_id]);
$dpias = db_fetch_all($stmt);

// Get linked incidents
$query = "SELECT incident_id, incident_ref, incident_title, severity, status, detected_at
          FROM incidents WHERE ropa_id = ? ORDER BY detected_at DESC LIMIT 5";
$stmt = db_query($query, [$ropa_id]);
$incidents = db_fetch_all($stmt);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View ROPA</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .section-title {
            background-color: #f5f5f5;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #00a950;
            font-weight: bold;
        }
        .info-row {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fafafa;
            border-left: 3px solid #ddd;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .badge-list .badge {
            margin-right: 5px;
            margin-bottom: 5px;
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
                        <h2>View Processing Activity</h2>
                        <h5><?php echo htmlspecialchars($ropa['activity_name']); ?></h5>
                    </div>
                </div>
                <hr />

                <div class="row">
                    <div class="col-md-12">
                        <!-- Action Buttons -->
                        <div class="btn-group float-end" style="margin-bottom: 15px;">
                            <a href="ropa_edit.php?id=<?php echo $ropa_id; ?>" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="ropa_export.php?id=<?php echo $ropa_id; ?>" class="btn btn-success">
                                <i class="fa fa-download"></i> Export PDF
                            </a>
                            <a href="ropa_list.php" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div class="clearfix"></div>

                        <!-- Status and Flags -->
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Status:</strong>
                                        <?php
                                        $status_class = $ropa['status'] == 'validated' ? 'success' : ($ropa['status'] == 'draft' ? 'warning' : 'default');
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($ropa['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-9">
                                        <strong>Risk Flags:</strong>
                                        <?php if ($ropa['has_special_categories']): ?>
                                            <span class="badge bg-danger">
                                                <i class="fa fa-exclamation-circle"></i> Special Categories
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ropa['has_minors']): ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fa fa-child"></i> Involves Minors
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ropa['has_cross_border']): ?>
                                            <span class="badge bg-info text-dark">
                                                <i class="fa fa-globe"></i> Cross-Border Transfer
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ropa['estimated_data_subjects'] >= DPIA_THRESHOLD_SUBJECTS): ?>
                                            <span class="badge bg-primary">
                                                <i class="fa fa-users"></i> Large Scale (<?php echo number_format($ropa['estimated_data_subjects']); ?> subjects)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="card border-primary">
                            <div class="card-header">
                                <i class="fa fa-info-circle"></i> Basic Information
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <div class="info-label">Activity Name:</div>
                                    <div><?php echo htmlspecialchars($ropa['activity_name']); ?></div>
                                </div>

                                <?php if ($ropa['description']): ?>
                                <div class="info-row">
                                    <div class="info-label">Description:</div>
                                    <div><?php echo nl2br(htmlspecialchars($ropa['description'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Department:</div>
                                            <div><?php echo htmlspecialchars($ropa['dept_name'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Data Source:</div>
                                            <div><?php echo ucfirst($ropa['data_source']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Processing Purpose:</div>
                                            <div><?php echo htmlspecialchars($ropa['purpose_name'] ?? 'Not specified'); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-row">
                                            <div class="info-label">Lawful Basis:</div>
                                            <div>
                                                <?php if ($ropa['basis_name']): ?>
                                                    <strong><?php echo htmlspecialchars($ropa['basis_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($ropa['cdpa_reference']); ?></small>
                                                <?php else: ?>
                                                    <span class="text-danger">Not specified</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Subjects and Categories -->
                        <div class="card border-info">
                            <div class="card-header">
                                <i class="fa fa-users"></i> Data Subjects and Categories
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Categories of Data Subjects:</div>
                                        <div class="badge-list">
                                            <?php if (!empty($subject_categories)): ?>
                                                <?php foreach ($subject_categories as $cat): ?>
                                                    <span class="badge <?php echo $cat['is_vulnerable'] ? 'badge-warning' : 'badge-info'; ?>">
                                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">None specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Categories of Personal Data:</div>
                                        <div class="badge-list">
                                            <?php if (!empty($data_categories)): ?>
                                                <?php foreach ($data_categories as $cat): ?>
                                                    <span class="badge <?php echo $cat['is_special_category'] ? 'badge-danger' : 'badge-success'; ?>">
                                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">None specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-row" style="margin-top: 15px;">
                                    <div class="info-label">Estimated Number of Data Subjects:</div>
                                    <div><?php echo number_format($ropa['estimated_data_subjects']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recipients and Storage -->
                        <div class="card border-warning">
                            <div class="card-header">
                                <i class="fa fa-share-alt"></i> Recipients and Storage
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Recipients / Third Parties:</div>
                                        <?php if (!empty($recipients)): ?>
                                            <ul class="list-unstyled">
                                                <?php foreach ($recipients as $recipient): ?>
                                                    <li>
                                                        <i class="fa fa-building"></i>
                                                        <strong><?php echo htmlspecialchars($recipient['recipient_name']); ?></strong>
                                                        <small class="text-muted">
                                                            (<?php echo ucfirst($recipient['recipient_type']); ?>
                                                            <?php if ($recipient['country']): ?>
                                                                - <?php echo htmlspecialchars($recipient['country']); ?>
                                                            <?php endif; ?>)
                                                        </small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted">No recipients specified</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Storage Locations:</div>
                                        <?php if (!empty($storage_locations)): ?>
                                            <ul class="list-unstyled">
                                                <?php foreach ($storage_locations as $location): ?>
                                                    <li>
                                                        <i class="fa fa-database"></i>
                                                        <strong><?php echo htmlspecialchars($location['location_name']); ?></strong>
                                                        <small class="text-muted">
                                                            (<?php echo ucfirst($location['location_type']); ?>
                                                            <?php if ($location['country']): ?>
                                                                - <?php echo htmlspecialchars($location['country']); ?>
                                                            <?php endif; ?>)
                                                        </small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <p class="text-muted">No storage locations specified</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Retention and Security -->
                        <div class="card border-success">
                            <div class="card-header">
                                <i class="fa fa-shield"></i> Retention and Security
                            </div>
                            <div class="card-body">
                                <div class="info-row">
                                    <div class="info-label">Retention Policy:</div>
                                    <div>
                                        <?php if ($ropa['policy_name']): ?>
                                            <strong><?php echo htmlspecialchars($ropa['policy_name']); ?></strong>
                                            (<?php echo htmlspecialchars($ropa['retention_period']); ?>)
                                        <?php else: ?>
                                            <span class="text-muted">Not specified</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($ropa['security_measures']): ?>
                                <div class="info-row">
                                    <div class="info-label">Security Measures:</div>
                                    <div><?php echo nl2br(htmlspecialchars($ropa['security_measures'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- DPIA Requirement Check -->
                        <?php
                        $dpia_required = (
                            $ropa['has_special_categories'] ||
                            $ropa['has_minors'] ||
                            $ropa['has_cross_border'] ||
                            $ropa['estimated_data_subjects'] >= DPIA_THRESHOLD_SUBJECTS
                        );
                        ?>

                        <?php if ($dpia_required && empty($dpias)): ?>
                        <!-- DPIA Required Alert -->
                        <div class="alert alert-warning" style="border-left: 4px solid #f0ad4e;">
                            <h4><i class="fa fa-exclamation-triangle"></i> DPIA Required</h4>
                            <p><strong>This processing activity requires a Data Protection Impact Assessment (DPIA).</strong></p>
                            <p>A DPIA is mandatory because this activity involves:</p>
                            <ul>
                                <?php if ($ropa['has_special_categories']): ?>
                                <li><i class="fa fa-check"></i> <strong>Special categories of personal data</strong> (e.g., health, biometrics, criminal records)</li>
                                <?php endif; ?>
                                <?php if ($ropa['has_minors']): ?>
                                <li><i class="fa fa-check"></i> <strong>Processing of minors' data</strong> (children under 18 years)</li>
                                <?php endif; ?>
                                <?php if ($ropa['has_cross_border']): ?>
                                <li><i class="fa fa-check"></i> <strong>Cross-border data transfers</strong></li>
                                <?php endif; ?>
                                <?php if ($ropa['estimated_data_subjects'] >= DPIA_THRESHOLD_SUBJECTS): ?>
                                <li><i class="fa fa-check"></i> <strong>Large-scale processing</strong> (<?php echo number_format($ropa['estimated_data_subjects']); ?> data subjects - threshold: <?php echo number_format(DPIA_THRESHOLD_SUBJECTS); ?>)</li>
                                <?php endif; ?>
                            </ul>
                            <p style="margin-top: 15px;">
                                <a href="dpia_wizard.php?ropa_id=<?php echo $ropa_id; ?>" class="btn btn-warning btn-lg">
                                    <i class="fa fa-shield"></i> Start DPIA Assessment
                                </a>
                            </p>
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                As required by CDPA s.24-25, a DPIA must be conducted before processing begins or when there are significant changes to the processing.
                            </small>
                        </div>
                        <?php endif; ?>

                        <!-- Linked DPIAs -->
                        <?php if (!empty($dpias)): ?>
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-shield"></i> Linked DPIAs (<?php echo count($dpias); ?>)
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>DPIA Title</th>
                                            <th>Status</th>
                                            <th>Residual Risk</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dpias as $dpia): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dpia['dpia_title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $dpia['status'] == 'approved' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($dpia['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $dpia['residual_risk_score']; ?></td>
                                            <td><?php echo format_date($dpia['created_at'], 'd M Y'); ?></td>
                                            <td>
                                                <a href="dpia_view.php?id=<?php echo $dpia['dpia_id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if ($dpia_required): ?>
                                <div style="margin-top: 10px;">
                                    <a href="dpia_wizard.php?ropa_id=<?php echo $ropa_id; ?>" class="btn btn-primary btn-sm">
                                        <i class="fa fa-plus"></i> Create New DPIA
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Linked Incidents -->
                        <?php if (!empty($incidents)): ?>
                        <div class="card border-danger">
                            <div class="card-header">
                                <i class="fa fa-warning"></i> Related Incidents
                            </div>
                            <div class="card-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Title</th>
                                            <th>Severity</th>
                                            <th>Status</th>
                                            <th>Detected</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incidents as $incident): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($incident['incident_ref']); ?></td>
                                            <td><?php echo htmlspecialchars($incident['incident_title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $incident['severity'] == 'critical' ? 'danger' : ($incident['severity'] == 'serious' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($incident['severity']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo ucfirst($incident['status']); ?></td>
                                            <td><?php echo format_date($incident['detected_at'], 'd M Y'); ?></td>
                                            <td>
                                                <a href="incident_view.php?id=<?php echo $incident['incident_id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Metadata -->
                        <div class="card">
                            <div class="card-header">
                                <i class="fa fa-clock-o"></i> Record Information
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Created By:</div>
                                        <div><?php echo htmlspecialchars($ropa['first_name'] . ' ' . $ropa['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($ropa['creator_email']); ?></small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Created Date:</div>
                                        <div><?php echo format_datetime($ropa['created_at'], 'd M Y H:i'); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Last Updated:</div>
                                        <div><?php echo format_datetime($ropa['updated_at'], 'd M Y H:i'); ?></div>
                                    </div>
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
