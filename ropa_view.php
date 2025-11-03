<?php
/**
 * DPA Tool - View ROPA Entry
 * Version: 1.0
 * Date: October 2025
 */

require_once '../config/config.php';
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
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
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
                        <div class="btn-group pull-right" style="margin-bottom: 15px;">
                            <a href="ropa_edit.php?id=<?php echo $ropa_id; ?>" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="ropa_export.php?id=<?php echo $ropa_id; ?>" class="btn btn-success">
                                <i class="fa fa-download"></i> Export PDF
                            </a>
                            <a href="ropa_list.php" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div class="clearfix"></div>

                        <!-- Status and Flags -->
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Status:</strong>
                                        <?php
                                        $status_class = $ropa['status'] == 'validated' ? 'success' : ($ropa['status'] == 'draft' ? 'warning' : 'default');
                                        ?>
                                        <span class="label label-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($ropa['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-9">
                                        <strong>Risk Flags:</strong>
                                        <?php if ($ropa['has_special_categories']): ?>
                                            <span class="label label-danger">
                                                <i class="fa fa-exclamation-circle"></i> Special Categories
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ropa['has_minors']): ?>
                                            <span class="label label-warning">
                                                <i class="fa fa-child"></i> Involves Minors
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ropa['has_cross_border']): ?>
                                            <span class="label label-info">
                                                <i class="fa fa-globe"></i> Cross-Border Transfer
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ropa['estimated_data_subjects'] >= DPIA_THRESHOLD_SUBJECTS): ?>
                                            <span class="label label-primary">
                                                <i class="fa fa-users"></i> Large Scale (<?php echo number_format($ropa['estimated_data_subjects']); ?> subjects)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> Basic Information
                            </div>
                            <div class="panel-body">
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
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-users"></i> Data Subjects and Categories
                            </div>
                            <div class="panel-body">
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
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <i class="fa fa-share-alt"></i> Recipients and Storage
                            </div>
                            <div class="panel-body">
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
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <i class="fa fa-shield"></i> Retention and Security
                            </div>
                            <div class="panel-body">
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

                        <!-- Linked DPIAs -->
                        <?php if (!empty($dpias)): ?>
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-shield"></i> Linked DPIAs
                            </div>
                            <div class="panel-body">
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
                                                <span class="label label-<?php echo $dpia['status'] == 'approved' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($dpia['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $dpia['residual_risk_score']; ?></td>
                                            <td><?php echo format_date($dpia['created_at'], 'd M Y'); ?></td>
                                            <td>
                                                <a href="dpia_view.php?id=<?php echo $dpia['dpia_id']; ?>" class="btn btn-info btn-xs">
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

                        <!-- Linked Incidents -->
                        <?php if (!empty($incidents)): ?>
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <i class="fa fa-warning"></i> Related Incidents
                            </div>
                            <div class="panel-body">
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
                                                <span class="label label-<?php echo $incident['severity'] == 'critical' ? 'danger' : ($incident['severity'] == 'serious' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($incident['severity']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo ucfirst($incident['status']); ?></td>
                                            <td><?php echo format_date($incident['detected_at'], 'd M Y'); ?></td>
                                            <td>
                                                <a href="incident_view.php?id=<?php echo $incident['incident_id']; ?>" class="btn btn-info btn-xs">
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
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-clock-o"></i> Record Information
                            </div>
                            <div class="panel-body">
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

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
