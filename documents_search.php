<?php
/**
 * Advanced Document Search
 * Search documents by name, description, type, dates, and content
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

$results = [];
$search_performed = false;

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_performed = true;

    $search_term = trim($_GET['search_term'] ?? '');
    $doc_type = $_GET['doc_type'] ?? 'all';
    $status = $_GET['status'] ?? 'all';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $expiry_status = $_GET['expiry_status'] ?? 'all';

    // Build query
    $query = "SELECT d.*,
              CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
              COUNT(DISTINCT dl.link_id) as link_count
              FROM documents d
              LEFT JOIN users u ON d.uploaded_by = u.user_id
              LEFT JOIN document_links dl ON d.doc_id = dl.doc_id
              WHERE d.org_id = ?";

    $params = [$org_id];

    // Search term (name or description)
    if (!empty($search_term)) {
        $query .= " AND (d.doc_name LIKE ? OR d.description LIKE ?)";
        $search_pattern = '%' . $search_term . '%';
        $params[] = $search_pattern;
        $params[] = $search_pattern;
    }

    // Document type filter
    if ($doc_type !== 'all') {
        $query .= " AND d.doc_type = ?";
        $params[] = $doc_type;
    }

    // Status filter
    if ($status !== 'all') {
        $query .= " AND d.status = ?";
        $params[] = $status;
    }

    // Date range filter
    if (!empty($date_from)) {
        $query .= " AND d.uploaded_at >= ?";
        $params[] = $date_from . ' 00:00:00';
    }

    if (!empty($date_to)) {
        $query .= " AND d.uploaded_at <= ?";
        $params[] = $date_to . ' 23:59:59';
    }

    // Expiry status filter
    if ($expiry_status === 'expired') {
        $query .= " AND d.expiry_date < CURDATE()";
    } elseif ($expiry_status === 'expiring_soon') {
        $query .= " AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($expiry_status === 'valid') {
        $query .= " AND (d.expiry_date IS NULL OR d.expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY))";
    }

    $query .= " GROUP BY d.doc_id ORDER BY d.uploaded_at DESC";

    $stmt = db_query($query, $params);
    $results = db_fetch_all($stmt);
}

// Format file size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Search Documents</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

<div class="container-fluid">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Advanced Document Search</h2>
                        <h5>Search and filter documents by multiple criteria</h5>
                    </div>
                </div>
                <hr />

    <!-- Search Form -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-primary">
                <div class="card-header">
                    <i class="fa fa-filter"></i> Search Criteria
                </div>
                <div class="card-body">
                    <form method="GET" action="documents_search.php">
                        <input type="hidden" name="search" value="1">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Search Term</label>
                                    <input type="text" name="search_term" class="form-control"
                                           value="<?php echo htmlspecialchars($_GET['search_term'] ?? ''); ?>"
                                           placeholder="Search in document name and description...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Document Type</label>
                                    <select name="doc_type" class="form-control">
                                        <option value="all">All Types</option>
                                        <option value="DPA" <?php echo ($_GET['doc_type'] ?? '') === 'DPA' ? 'selected' : ''; ?>>DPA</option>
                                        <option value="DSA" <?php echo ($_GET['doc_type'] ?? '') === 'DSA' ? 'selected' : ''; ?>>DSA</option>
                                        <option value="policy" <?php echo ($_GET['doc_type'] ?? '') === 'policy' ? 'selected' : ''; ?>>Policy</option>
                                        <option value="certification" <?php echo ($_GET['doc_type'] ?? '') === 'certification' ? 'selected' : ''; ?>>Certification</option>
                                        <option value="audit_report" <?php echo ($_GET['doc_type'] ?? '') === 'audit_report' ? 'selected' : ''; ?>>Audit Report</option>
                                        <option value="contract" <?php echo ($_GET['doc_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="consent" <?php echo ($_GET['doc_type'] ?? '') === 'consent' ? 'selected' : ''; ?>>Consent</option>
                                        <option value="evidence" <?php echo ($_GET['doc_type'] ?? '') === 'evidence' ? 'selected' : ''; ?>>Evidence</option>
                                        <option value="other" <?php echo ($_GET['doc_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="all">All Status</option>
                                        <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="expiring_soon" <?php echo ($_GET['status'] ?? '') === 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon</option>
                                        <option value="expired" <?php echo ($_GET['status'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        <option value="archived" <?php echo ($_GET['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Upload Date From</label>
                                    <input type="date" name="date_from" class="form-control"
                                           value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Upload Date To</label>
                                    <input type="date" name="date_to" class="form-control"
                                           value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Expiry Status</label>
                                    <select name="expiry_status" class="form-control">
                                        <option value="all">All</option>
                                        <option value="expired" <?php echo ($_GET['expiry_status'] ?? '') === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        <option value="expiring_soon" <?php echo ($_GET['expiry_status'] ?? '') === 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon (30 days)</option>
                                        <option value="valid" <?php echo ($_GET['expiry_status'] ?? '') === 'valid' ? 'selected' : ''; ?>>Valid/No Expiry</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa fa-search"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <a href="documents_search.php" class="btn btn-secondary btn-sm">
                                    <i class="fa fa-refresh"></i> Clear Filters
                                </a>
                                <a href="documents_list.php" class="btn btn-secondary btn-sm">
                                    <i class="fa fa-arrow-left"></i> Back to Documents
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    <?php if ($search_performed): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card border-success">
                <div class="card-header">
                    <i class="fa fa-list"></i> Search Results
                    <span class="badge" style="background-color: white; color: #27ae60; margin-left: 10px;">
                        <?php echo count($results); ?> document<?php echo count($results) !== 1 ? 's' : ''; ?> found
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($results)): ?>
                        <div class="alert alert-warning">
                            <i class="fa fa-info-circle"></i>
                            No documents match your search criteria. Try adjusting your filters.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Document Name</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Size</th>
                                        <th>Uploaded By</th>
                                        <th>Upload Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $doc): ?>
                                        <?php
                                        $status_badges = [
                                            'active' => 'success',
                                            'expiring_soon' => 'warning',
                                            'expired' => 'danger',
                                            'archived' => 'default'
                                        ];
                                        $status_badge = $status_badges[$doc['status']] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <a href="documents_view.php?id=<?php echo $doc['doc_id']; ?>">
                                                        <?php echo htmlspecialchars($doc['doc_name']); ?>
                                                    </a>
                                                </strong>
                                                <?php if ($doc['description']): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($doc['description'], 0, 80)) . (strlen($doc['description']) > 80 ? '...' : ''); ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if ($doc['link_count'] > 0): ?>
                                                    <br><small class="text-info">
                                                        <i class="fa fa-link"></i> <?php echo $doc['link_count']; ?> link<?php echo $doc['link_count'] > 1 ? 's' : ''; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo strtoupper($doc['doc_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $status_badge; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $doc['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatBytes($doc['file_size']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['uploaded_by_name']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?></td>
                                            <td>
                                                <a href="documents_view.php?id=<?php echo $doc['doc_id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-success btn-sm" download>
                                                    <i class="fa fa-download"></i>
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
        </div>
    </div>
    <?php endif; ?>

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
