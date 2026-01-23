<?php
/**
 * DPA Tool - Delete/Terminate Vendor
 * Soft delete by setting status to terminated
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

$vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$vendor_id) {
    set_flash_message('Invalid vendor ID.', 'error');
    redirect('vendor_list.php');
}

// Fetch vendor
$query = "SELECT * FROM vendors WHERE vendor_id = ? AND org_id = ?";
$stmt = db_query($query, [$vendor_id, $org_id]);
$vendor = db_fetch_one($stmt);

if (!$vendor) {
    set_flash_message('Vendor not found.', 'error');
    redirect('vendor_list.php');
}

if ($vendor['status'] === 'terminated') {
    set_flash_message('Vendor is already terminated.', 'error');
    redirect('vendor_list.php');
}

// Check if vendor is linked to any ROPA entries
$ropa_query = "SELECT COUNT(*) as count FROM vendor_ropa WHERE vendor_id = ?";
$stmt = db_query($ropa_query, [$vendor_id]);
$ropa_count = db_fetch_one($stmt)['count'];

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    $termination_reason = sanitize_input($_POST['termination_reason'] ?? '');

    // Update vendor status to terminated
    $update_query = "UPDATE vendors SET
                     status = 'terminated',
                     terminated_at = NOW(),
                     notes = CONCAT(IFNULL(notes, ''), '\n\nTermination Reason: ', ?)
                     WHERE vendor_id = ? AND org_id = ?";
    $stmt = db_query($update_query, [$termination_reason, $vendor_id, $org_id]);

    if ($stmt) {
        log_audit($org_id, $user_id, 'vendor', $vendor_id, 'terminate');
        set_flash_message('Vendor has been terminated successfully.', 'success');
        redirect('vendor_list.php');
    } else {
        set_flash_message('Error terminating vendor.', 'error');
        redirect('vendor_view.php?id=' . $vendor_id);
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Terminate Vendor</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Terminate Vendor</h2>
                        <h5>Confirm vendor termination</h5>
                    </div>
                </div>
                <hr />

                <div class="row">
                    <div class="col-md-6 col-md-offset-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-warning"></i> Confirm Termination</h3>
                            </div>
                            <div class="panel-body">
                                <div class="alert alert-danger">
                                    <strong>Warning!</strong> You are about to terminate the following vendor:
                                </div>

                                <table class="table table-bordered">
                                    <tr>
                                        <th>Vendor Name:</th>
                                        <td><strong><?php echo htmlspecialchars($vendor['vendor_name']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Reference:</th>
                                        <td><?php echo htmlspecialchars($vendor['vendor_ref']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type:</th>
                                        <td><?php echo ucwords(str_replace('_', ' ', $vendor['vendor_type'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Current Status:</th>
                                        <td><?php echo ucfirst($vendor['status']); ?></td>
                                    </tr>
                                    <?php if ($ropa_count > 0): ?>
                                    <tr>
                                        <th>Linked ROPA:</th>
                                        <td>
                                            <span class="text-warning">
                                                <i class="fa fa-warning"></i> <?php echo $ropa_count; ?> processing activities linked
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>

                                <form method="POST" action="vendor_delete.php?id=<?php echo $vendor_id; ?>">
                                    <div class="form-group">
                                        <label for="termination_reason">Reason for Termination</label>
                                        <textarea name="termination_reason" id="termination_reason" class="form-control" rows="3"
                                                  placeholder="Please provide a reason for terminating this vendor..."></textarea>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="confirm" value="1" class="btn btn-danger">
                                            <i class="fa fa-times"></i> Confirm Termination
                                        </button>
                                        <a href="vendor_view.php?id=<?php echo $vendor_id; ?>" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Cancel
                                        </a>
                                    </div>
                                </form>
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
