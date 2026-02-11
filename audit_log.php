<?php
/**
 * DPA Tool - Audit Log Viewer
 * Browse and filter the system audit trail
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

// Admin/DPO only
if (!is_admin() && !is_dpo()) {
    set_flash_message('You do not have permission to view the audit log.', 'danger');
    redirect('dashboard.php');
}

$org_id = get_current_org_id();

// Filter parameters
$filter_entity = $_GET['entity'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query
$where = "al.org_id = ?";
$params = [$org_id];

if (!empty($filter_entity)) {
    $where .= " AND al.entity_type = ?";
    $params[] = $filter_entity;
}
if (!empty($filter_action)) {
    $where .= " AND al.action = ?";
    $params[] = $filter_action;
}
if ($filter_user > 0) {
    $where .= " AND al.user_id = ?";
    $params[] = $filter_user;
}
if (!empty($filter_date_from)) {
    $where .= " AND al.created_at >= ?";
    $params[] = $filter_date_from . ' 00:00:00';
}
if (!empty($filter_date_to)) {
    $where .= " AND al.created_at <= ?";
    $params[] = $filter_date_to . ' 23:59:59';
}

// Fetch logs
$query = "SELECT al.*, u.first_name, u.last_name
          FROM audit_log al
          LEFT JOIN users u ON al.user_id = u.user_id
          WHERE $where
          ORDER BY al.created_at DESC
          LIMIT 1000";
$stmt = db_query($query, $params);
$logs = db_fetch_all($stmt);

// Stats
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT entity_type) as entity_types
    FROM audit_log WHERE org_id = ?";
$stmt = db_query($stats_query, [$org_id]);
$stats = db_fetch_one($stmt);

// Get distinct entity types and actions for filters
$stmt = db_query("SELECT DISTINCT entity_type FROM audit_log WHERE org_id = ? ORDER BY entity_type", [$org_id]);
$entity_types = db_fetch_all($stmt);

$stmt = db_query("SELECT DISTINCT action FROM audit_log WHERE org_id = ? ORDER BY action", [$org_id]);
$actions = db_fetch_all($stmt);

// Get users for filter
$stmt = db_query("SELECT user_id, first_name, last_name FROM users WHERE org_id = ? ORDER BY first_name", [$org_id]);
$users = db_fetch_all($stmt);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Audit Log</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap5.css?v=2" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/module-styles.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .audit-detail-row { background: var(--bg-tertiary); }
        .audit-detail-row td { padding: 12px 20px; }
        .json-display {
            font-family: monospace;
            font-size: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 8px 12px;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .filter-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
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
                        <h2>Audit Log</h2>
                        <h5>System activity trail</h5>
                    </div>
                </div>
                <hr />

                <!-- Stats -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h3><?php echo number_format($stats['total']); ?></h3>
                                <p class="text-muted mb-0">Total Entries</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h3><?php echo number_format($stats['today']); ?></h3>
                                <p class="text-muted mb-0">Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['unique_users']; ?></h3>
                                <p class="text-muted mb-0">Active Users</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h3><?php echo $stats['entity_types']; ?></h3>
                                <p class="text-muted mb-0">Entity Types</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <form method="GET" class="filter-bar">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small">Entity Type</label>
                            <select name="entity" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($entity_types as $et): ?>
                                <option value="<?php echo htmlspecialchars($et['entity_type']); ?>" <?php echo $filter_entity === $et['entity_type'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($et['entity_type'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Action</label>
                            <select name="action" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($actions as $a): ?>
                                <option value="<?php echo htmlspecialchars($a['action']); ?>" <?php echo $filter_action === $a['action'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($a['action'])); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">User</label>
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="0">All</option>
                                <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>" <?php echo $filter_user == $u['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">From</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">To</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fa fa-filter"></i> Filter</button>
                        </div>
                    </div>
                    <?php if (!empty($filter_entity) || !empty($filter_action) || $filter_user > 0 || !empty($filter_date_from) || !empty($filter_date_to)): ?>
                    <div class="mt-2">
                        <a href="audit_log.php" class="small"><i class="fa fa-times"></i> Clear filters</a>
                        <span class="small text-muted ms-2"><?php echo count($logs); ?> results</span>
                    </div>
                    <?php endif; ?>
                </form>

                <!-- Log Table -->
                <div class="card">
                    <div class="card-header">
                        <i class="fa fa-history"></i> Audit Trail
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="alert alert-info"><i class="fa fa-info-circle"></i> No audit log entries found.</div>
                        <?php else: ?>
                        <table id="auditTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Entity</th>
                                    <th>Action</th>
                                    <th>Entity ID</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $i => $log): ?>
                                <tr class="audit-main-row" data-index="<?php echo $i; ?>" style="cursor:pointer;">
                                    <td class="text-center">
                                        <?php if (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                                        <i class="fa fa-plus-circle text-muted toggle-icon"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_datetime($log['created_at'], 'd M Y H:i:s'); ?></td>
                                    <td><?php echo htmlspecialchars(($log['first_name'] ?? 'System') . ' ' . ($log['last_name'] ?? '')); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($log['entity_type'])); ?></span></td>
                                    <td>
                                        <?php
                                        $action_colors = ['create' => 'success', 'update' => 'primary', 'updated' => 'primary', 'delete' => 'danger', 'login' => 'info', 'logout' => 'warning'];
                                        $ac = $action_colors[$log['action']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $ac; ?>"><?php echo htmlspecialchars(ucfirst($log['action'])); ?></span>
                                    </td>
                                    <td><?php echo $log['entity_id']; ?></td>
                                    <td><small><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></small></td>
                                </tr>
                                <?php if (!empty($log['old_values']) || !empty($log['new_values'])): ?>
                                <tr class="audit-detail-row" id="detail_<?php echo $i; ?>" style="display:none;">
                                    <td colspan="7">
                                        <div class="row">
                                            <?php if (!empty($log['old_values'])): ?>
                                            <div class="col-md-6">
                                                <strong>Previous Values:</strong>
                                                <div class="json-display"><?php echo htmlspecialchars(json_encode(json_decode($log['old_values']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $log['old_values']); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($log['new_values'])): ?>
                                            <div class="col-md-6">
                                                <strong>New Values:</strong>
                                                <div class="json-display"><?php echo htmlspecialchars(json_encode(json_decode($log['new_values']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: $log['new_values']); ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($log['user_agent'])): ?>
                                        <div class="mt-2"><small class="text-muted">User Agent: <?php echo htmlspecialchars($log['user_agent']); ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
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
        $('#auditTable').DataTable({
            "order": [[1, "desc"]],
            "pageLength": 25,
            "columnDefs": [
                { "orderable": false, "targets": [0] }
            ]
        });

        // Toggle detail rows
        $(document).on('click', '.audit-main-row', function() {
            var idx = $(this).data('index');
            var detail = $('#detail_' + idx);
            var icon = $(this).find('.toggle-icon');
            if (detail.length) {
                detail.toggle();
                icon.toggleClass('fa-plus-circle fa-minus-circle');
            }
        });
    });
    </script>
</body>
</html>
