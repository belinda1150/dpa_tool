<?php
/**
 * DPA Tool - Data Retention Policy Manager
 * CRUD for retention policies with ROPA linkage
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();
$can_edit = is_admin() || is_dpo();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['policy_name'] ?? '');
        $period = trim($_POST['retention_period'] ?? '');
        $days = !empty($_POST['retention_days']) ? intval($_POST['retention_days']) : null;
        $justification = trim($_POST['justification'] ?? '');

        if (!empty($name) && !empty($period)) {
            $query = "INSERT INTO retention_policies (org_id, policy_name, retention_period, retention_days, justification)
                      VALUES (?, ?, ?, ?, ?)";
            db_query($query, [$org_id, $name, $period, $days, $justification]);
            log_audit($org_id, $user_id, 'retention_policy', db_insert_id(), 'create', null, ['policy_name' => $name]);
            set_flash_message('Retention policy added successfully.', 'success');
        } else {
            set_flash_message('Policy name and retention period are required.', 'danger');
        }
        redirect('retention_manage.php');
    }

    if ($action === 'edit') {
        $id = intval($_POST['retention_id'] ?? 0);
        $name = trim($_POST['policy_name'] ?? '');
        $period = trim($_POST['retention_period'] ?? '');
        $days = !empty($_POST['retention_days']) ? intval($_POST['retention_days']) : null;
        $justification = trim($_POST['justification'] ?? '');

        if ($id > 0 && !empty($name) && !empty($period)) {
            $query = "UPDATE retention_policies SET policy_name = ?, retention_period = ?, retention_days = ?, justification = ?
                      WHERE retention_id = ? AND org_id = ?";
            db_query($query, [$name, $period, $days, $justification, $id, $org_id]);
            log_audit($org_id, $user_id, 'retention_policy', $id, 'update', null, ['policy_name' => $name]);
            set_flash_message('Retention policy updated.', 'success');
        }
        redirect('retention_manage.php');
    }

    if ($action === 'delete') {
        $id = intval($_POST['retention_id'] ?? 0);
        if ($id > 0) {
            // Check for linked ROPA entries
            $stmt = db_query("SELECT COUNT(*) as cnt FROM processing_activities WHERE retention_id = ? AND org_id = ?", [$id, $org_id]);
            $linked = db_fetch_one($stmt);
            if ($linked['cnt'] > 0) {
                set_flash_message('Cannot delete: this policy is linked to ' . $linked['cnt'] . ' ROPA entries. Remove the linkage first.', 'danger');
            } else {
                db_query("DELETE FROM retention_policies WHERE retention_id = ? AND org_id = ?", [$id, $org_id]);
                log_audit($org_id, $user_id, 'retention_policy', $id, 'delete');
                set_flash_message('Retention policy deleted.', 'success');
            }
        }
        redirect('retention_manage.php');
    }
}

// Fetch policies with ROPA counts
$query = "SELECT rp.*, COUNT(pa.ropa_id) as ropa_count
          FROM retention_policies rp
          LEFT JOIN processing_activities pa ON rp.retention_id = pa.retention_id AND pa.org_id = rp.org_id
          WHERE rp.org_id = ?
          GROUP BY rp.retention_id
          ORDER BY rp.policy_name";
$stmt = db_query($query, [$org_id]);
$policies = db_fetch_all($stmt);

// Stats
$total_policies = count($policies);
$policies_no_days = 0;
$total_ropa_linked = 0;
foreach ($policies as $p) {
    if (empty($p['retention_days'])) $policies_no_days++;
    $total_ropa_linked += $p['ropa_count'];
}

// ROPA entries without retention
$stmt = db_query("SELECT COUNT(*) as cnt FROM processing_activities WHERE org_id = ? AND (retention_id IS NULL OR retention_id = 0)", [$org_id]);
$ropa_no_retention = db_fetch_one($stmt)['cnt'];

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Retention Policies</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap5.css?v=2" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/module-styles.css" rel="stylesheet" />
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
                        <h2>Data Retention Policies</h2>
                        <h5>Manage data retention schedules (CDPA s.9)</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h3><?php echo $total_policies; ?></h3>
                                <p class="text-muted mb-0">Total Policies</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h3><?php echo $total_ropa_linked; ?></h3>
                                <p class="text-muted mb-0">Linked ROPA Entries</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h3><?php echo $ropa_no_retention; ?></h3>
                                <p class="text-muted mb-0">ROPA Without Retention</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h3><?php echo $policies_no_days; ?></h3>
                                <p class="text-muted mb-0">Incomplete Policies</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($ropa_no_retention > 0): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong><?php echo $ropa_no_retention; ?> processing activities</strong> do not have a retention policy assigned.
                    <a href="ropa_list.php">Review ROPA entries</a>
                </div>
                <?php endif; ?>

                <!-- Action Bar -->
                <?php if ($can_edit): ?>
                <div class="mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fa fa-plus"></i> Add Retention Policy
                    </button>
                </div>
                <?php endif; ?>

                <!-- Policies Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-clock-o"></i> Retention Policies
                    </div>
                    <div class="card-body">
                        <?php if (empty($policies)): ?>
                            <div class="alert alert-info"><i class="fa fa-info-circle"></i> No retention policies defined yet.</div>
                        <?php else: ?>
                        <table id="retentionTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Policy Name</th>
                                    <th>Retention Period</th>
                                    <th>Days</th>
                                    <th>Justification</th>
                                    <th>Linked ROPA</th>
                                    <th>Created</th>
                                    <?php if ($can_edit): ?><th width="120">Actions</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($policies as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['policy_name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['retention_period']); ?></td>
                                    <td>
                                        <?php if (!empty($p['retention_days'])): ?>
                                            <span class="badge bg-primary"><?php echo $p['retention_days']; ?> days</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['justification'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($p['ropa_count'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $p['ropa_count']; ?> entries</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_date($p['created_at'], 'd M Y'); ?></td>
                                    <?php if ($can_edit): ?>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-edit"
                                                data-id="<?php echo $p['retention_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($p['policy_name']); ?>"
                                                data-period="<?php echo htmlspecialchars($p['retention_period']); ?>"
                                                data-days="<?php echo $p['retention_days'] ?? ''; ?>"
                                                data-justification="<?php echo htmlspecialchars($p['justification'] ?? ''); ?>"
                                                data-bs-toggle="modal" data-bs-target="#editModal">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-delete"
                                                data-id="<?php echo $p['retention_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($p['policy_name']); ?>"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php if ($can_edit): ?>
    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Retention Policy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Policy Name <span class="text-danger">*</span></label>
                            <input type="text" name="policy_name" class="form-control" required placeholder="e.g. Employee Records Retention">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Retention Period <span class="text-danger">*</span></label>
                            <input type="text" name="retention_period" class="form-control" required placeholder="e.g. 7 years after termination">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Retention Days</label>
                            <input type="number" name="retention_days" class="form-control" min="1" placeholder="e.g. 2555">
                            <div class="form-text">Numeric equivalent in days for automated tracking</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Justification</label>
                            <textarea name="justification" class="form-control" rows="3" placeholder="Legal basis or business reason for this retention period"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="retention_id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Retention Policy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Policy Name <span class="text-danger">*</span></label>
                            <input type="text" name="policy_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Retention Period <span class="text-danger">*</span></label>
                            <input type="text" name="retention_period" id="edit_period" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Retention Days</label>
                            <input type="number" name="retention_days" id="edit_days" class="form-control" min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Justification</label>
                            <textarea name="justification" id="edit_justification" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="retention_id" id="delete_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Retention Policy</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="delete_name"></strong>?</p>
                        <p class="text-danger small">This action cannot be undone. Policies linked to ROPA entries cannot be deleted.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap5.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/global-search.js"></script>
    <script>
    $(document).ready(function() {
        $('#retentionTable').DataTable({
            "order": [[0, "asc"]],
            "pageLength": 25
        });

        // Edit modal population
        $(document).on('click', '.btn-edit', function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_name').val($(this).data('name'));
            $('#edit_period').val($(this).data('period'));
            $('#edit_days').val($(this).data('days'));
            $('#edit_justification').val($(this).data('justification'));
        });

        // Delete modal population
        $(document).on('click', '.btn-delete', function() {
            $('#delete_id').val($(this).data('id'));
            $('#delete_name').text($(this).data('name'));
        });
    });
    </script>
</body>
</html>
