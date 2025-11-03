<?php
/**
 * DPA Tool - Recipients Management
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('DPO'); // DPO and above can manage recipients

$org_id = get_current_org_id();

// Get all recipients with usage count
$query = "SELECT r.*,
          (SELECT COUNT(*) FROM ropa_recipients WHERE recipient_id = r.recipient_id) AS usage_count
          FROM recipients r
          WHERE r.org_id = ?
          ORDER BY r.recipient_name";
$stmt = db_query($query, [$org_id]);
$recipients = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Recipients Management</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
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
                        <h2>Recipients Management</h2>
                        <h5>Manage data recipients and third parties</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-building"></i> Recipients / Third Parties
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="recipients_add.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Add New Recipient
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="recipientsTable">
                                        <thead>
                                            <tr>
                                                <th>Recipient Name</th>
                                                <th>Type</th>
                                                <th>Country</th>
                                                <th>Contact Person</th>
                                                <th>Email</th>
                                                <th>Usage</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recipients)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    <i class="fa fa-info-circle"></i> No recipients found. Click "Add New Recipient" to create one.
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($recipients as $recipient): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($recipient['recipient_name']); ?></strong></td>
                                                <td>
                                                    <?php
                                                    $type_labels = [
                                                        'processor' => '<span class="label label-info">Processor</span>',
                                                        'controller' => '<span class="label label-primary">Controller</span>',
                                                        'authority' => '<span class="label label-warning">Authority</span>',
                                                        'other' => '<span class="label label-default">Other</span>'
                                                    ];
                                                    echo $type_labels[$recipient['recipient_type']] ?? ucfirst($recipient['recipient_type']);
                                                    ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($recipient['country'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($recipient['contact_person'] ?? '-'); ?></td>
                                                <td>
                                                    <?php if ($recipient['email']): ?>
                                                        <a href="mailto:<?php echo htmlspecialchars($recipient['email']); ?>">
                                                            <?php echo htmlspecialchars($recipient['email']); ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info"><?php echo $recipient['usage_count']; ?></span>
                                                    <?php echo $recipient['usage_count'] == 1 ? 'ROPA' : 'ROPAs'; ?>
                                                </td>
                                                <td>
                                                    <a href="recipients_edit.php?id=<?php echo $recipient['recipient_id']; ?>" class="btn btn-warning btn-xs" title="Edit">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="recipients_delete.php?id=<?php echo $recipient['recipient_id']; ?>" class="btn btn-danger btn-xs" title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this recipient?<?php if ($recipient['usage_count'] > 0) echo ' This recipient is used in ' . $recipient['usage_count'] . ' ROPA entries.'; ?>');">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
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

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        $(document).ready(function() {
            $('#recipientsTable').dataTable({
                "order": [[0, "asc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
