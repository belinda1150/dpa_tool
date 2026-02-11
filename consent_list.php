<?php
/**
 * Consent Management List
 * Track and manage data subject consents
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all consents
$query = "SELECT c.*,
          DATEDIFF(c.expiry_date, NOW()) as days_to_expiry
          FROM consents c
          WHERE c.org_id = ?
          ORDER BY c.consent_date DESC";

$consents = db_fetch_all(db_query($query, [$org_id]));

// Calculate statistics
$total = count($consents);
$active = 0;
$withdrawn = 0;
$expired = 0;
$expiring_soon = 0;

foreach ($consents as $consent) {
    if ($consent['status'] == 'active') {
        $active++;
        if ($consent['expiry_date'] && $consent['days_to_expiry'] <= 30 && $consent['days_to_expiry'] > 0) {
            $expiring_soon++;
        }
    } elseif ($consent['status'] == 'withdrawn') {
        $withdrawn++;
    } elseif ($consent['status'] == 'expired') {
        $expired++;
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Consent Management</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap5.css?v=2" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Consent Management</h2>
                        <h5>Manage consent records and compliance - CDPA s.8</h5>
                    </div>
                </div>
                <hr />

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $total; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-list"></i> Total Consents
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $active; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-check-circle"></i> Active
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $withdrawn; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-times-circle"></i> Withdrawn
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $expired; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-calendar-times-o"></i> Expired
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Soon Alert -->
    <?php if ($expiring_soon > 0): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <strong><i class="fa fa-clock-o"></i> Expiring Soon:</strong>
                <?php echo $expiring_soon; ?> consent(s) will expire within 30 days. Consider renewal or obtaining new consent.
                <a href="#" onclick="$('#consentsTable').DataTable().column(5).search('expiring').draw(); return false;" class="btn btn-warning btn-sm float-end">
                    <i class="fa fa-filter"></i> Show Expiring
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add New Consent Button -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-list"></i> All Consents
                    </h3>
                    <div class="float-end" style="margin-top: -22px;">
                        <a href="consent_add.php" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus"></i> Record New Consent
                        </a>
                        <a href="consent_export.php?format=pdf" class="btn btn-info btn-sm">
                            <i class="fa fa-file-pdf-o"></i> Export Register
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($consents)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No consents recorded yet. Click "Record New Consent" to start tracking data subject consents.
                        </div>
                    <?php else: ?>
                        <table id="consentsTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Consent ID</th>
                                    <th>Data Subject</th>
                                    <th>Purpose</th>
                                    <th>Consent Date</th>
                                    <th>Status</th>
                                    <th>Expiry Alert</th>
                                    <th>Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consents as $consent): ?>
                                <tr>
                                    <td>
                                        <strong>CNS-<?php echo str_pad($consent['consent_id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($consent['subject_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($consent['subject_email']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($consent['purpose']); ?></strong>
                                        <?php if ($consent['purpose_description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($consent['purpose_description'], 0, 50)) . '...'; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($consent['consent_date'])); ?></td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'active' => 'success',
                                            'withdrawn' => 'warning',
                                            'expired' => 'danger'
                                        ][$consent['status']] ?? 'secondary';
                                        echo "<span class='badge bg-$status_class'>" . ucfirst($consent['status']) . "</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($consent['status'] == 'active' && $consent['expiry_date']) {
                                            if ($consent['days_to_expiry'] <= 0) {
                                                echo "<span class='badge bg-danger'><i class='fa fa-exclamation-triangle'></i> Expired</span>";
                                            } elseif ($consent['days_to_expiry'] <= 30) {
                                                echo "<span class='badge bg-warning text-dark'><i class='fa fa-clock-o'></i> {$consent['days_to_expiry']} days</span>";
                                            } else {
                                                echo "<span class='badge bg-success'><i class='fa fa-check'></i> Valid</span>";
                                            }
                                        } elseif ($consent['status'] == 'withdrawn') {
                                            echo "<span class='text-muted'>N/A</span>";
                                        } else {
                                            echo "<span class='text-muted'>No Expiry</span>";
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($consent['consent_method']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="consent_view.php?id=<?php echo $consent['consent_id']; ?>" class="btn btn-info" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="consent_export.php?id=<?php echo $consent['consent_id']; ?>&format=pdf" class="btn btn-primary" title="Export">
                                                <i class="fa fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Panel -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-info-circle"></i> About Consent Management</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>What is Consent?</strong></p>
                            <p>
                                Under CDPA Section 8, consent is one of the lawful bases for processing personal data.
                                Consent must be:
                            </p>
                            <ul>
                                <li><strong>Freely given</strong> - No coercion or negative consequences for refusing</li>
                                <li><strong>Specific</strong> - Clear purpose for processing</li>
                                <li><strong>Informed</strong> - Data subject understands what they're consenting to</li>
                                <li><strong>Unambiguous</strong> - Clear affirmative action required</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Consent Management Requirements:</strong></p>
                            <ul>
                                <li>Keep records of when and how consent was obtained</li>
                                <li>Make it easy for data subjects to withdraw consent</li>
                                <li>Withdrawal must be as easy as giving consent</li>
                                <li>Review and refresh consent periodically</li>
                                <li>Stop processing immediately upon withdrawal (CDPA s.22)</li>
                                <li>Document the consent mechanism used</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                        <strong><i class="fa fa-warning"></i> Important:</strong>
                        Consent is only one of several lawful bases for processing under CDPA s.8. Other bases include:
                        contract performance, legal obligation, vital interests, public task, and legitimate interests.
                        Not all processing requires consent.
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
<script src="assets/js/dataTables/jquery.dataTables.js"></script>
<script src="assets/js/dataTables/dataTables.bootstrap5.js"></script>
<script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
<script>
$(document).ready(function() {
    $('#consentsTable').DataTable({
        "order": [[3, "desc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": 7 }
        ]
    });
});
</script>
</body>
</html>
