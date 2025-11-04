<?php
/**
 * Document Repository - List View
 * Browse and manage organizational documents
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get filter parameters
$doc_type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT d.*,
          CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
          COUNT(DISTINCT dl.link_id) as link_count
          FROM documents d
          LEFT JOIN users u ON d.uploaded_by = u.user_id
          LEFT JOIN document_links dl ON d.doc_id = dl.doc_id
          WHERE d.org_id = ?";

$params = [$org_id];

if ($doc_type_filter !== 'all') {
    $query .= " AND d.doc_type = ?";
    $params[] = $doc_type_filter;
}

if ($status_filter !== 'all') {
    $query .= " AND d.status = ?";
    $params[] = $status_filter;
}

$query .= " GROUP BY d.doc_id ORDER BY d.uploaded_at DESC";

$stmt = db_query($query, $params);
$documents = db_fetch_all($stmt);

// Calculate statistics
$total_docs = count($documents);
$total_size = 0;
$by_type = [];
$expiring_soon = 0;
$expired = 0;

foreach ($documents as $doc) {
    $total_size += $doc['file_size'];

    $doc_type = $doc['doc_type'];
    if (!isset($by_type[$doc_type])) {
        $by_type[$doc_type] = 0;
    }
    $by_type[$doc_type]++;

    if ($doc['status'] === 'expiring_soon') $expiring_soon++;
    if ($doc['status'] === 'expired') $expired++;
}

// Format total size
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
    <title><?php echo APP_NAME; ?> - Document Repository</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .doc-type-badge {
            font-size: 11px;
            padding: 4px 8px;
        }
        .file-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .doc-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .doc-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
                        <h2>Document Repository</h2>
                        <h5>Governance & e-Filing</h5>
                    </div>
                </div>
                <hr />

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-file fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_docs; ?></div>
                            <div>Total Documents</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-database fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo formatBytes($total_size); ?></div>
                            <div>Total Storage</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-clock-o fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $expiring_soon; ?></div>
                            <div>Expiring Soon</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-calendar-times-o fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $expired; ?></div>
                            <div>Expired</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons & Filters -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <a href="documents_upload.php" class="btn btn-primary">
                        <i class="fa fa-upload"></i> Upload Document
                    </a>
                    <a href="documents_search.php" class="btn btn-info">
                        <i class="fa fa-search"></i> Advanced Search
                    </a>

                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-filter"></i> Filter by Type
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="?type=all&status=<?php echo $status_filter; ?>">All Types</a></li>
                                <li role="separator" class="divider"></li>
                                <li><a href="?type=DPA&status=<?php echo $status_filter; ?>">DPA</a></li>
                                <li><a href="?type=DSA&status=<?php echo $status_filter; ?>">DSA</a></li>
                                <li><a href="?type=policy&status=<?php echo $status_filter; ?>">Policy</a></li>
                                <li><a href="?type=certification&status=<?php echo $status_filter; ?>">Certification</a></li>
                                <li><a href="?type=audit_report&status=<?php echo $status_filter; ?>">Audit Report</a></li>
                                <li><a href="?type=contract&status=<?php echo $status_filter; ?>">Contract</a></li>
                                <li><a href="?type=consent&status=<?php echo $status_filter; ?>">Consent</a></li>
                                <li><a href="?type=evidence&status=<?php echo $status_filter; ?>">Evidence</a></li>
                                <li><a href="?type=documentation&status=<?php echo $status_filter; ?>">Documentation</a></li>
                                <li><a href="?type=other&status=<?php echo $status_filter; ?>">Other</a></li>
                            </ul>
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-filter"></i> Filter by Status
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="?type=<?php echo $doc_type_filter; ?>&status=all">All Status</a></li>
                                <li role="separator" class="divider"></li>
                                <li><a href="?type=<?php echo $doc_type_filter; ?>&status=active">Active</a></li>
                                <li><a href="?type=<?php echo $doc_type_filter; ?>&status=expiring_soon">Expiring Soon</a></li>
                                <li><a href="?type=<?php echo $doc_type_filter; ?>&status=expired">Expired</a></li>
                                <li><a href="?type=<?php echo $doc_type_filter; ?>&status=archived">Archived</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents List -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-table"></i> Documents
                    <?php if ($doc_type_filter !== 'all'): ?>
                        <span class="label label-info"><?php echo strtoupper($doc_type_filter); ?></span>
                    <?php endif; ?>
                    <?php if ($status_filter !== 'all'): ?>
                        <span class="label label-warning"><?php echo strtoupper($status_filter); ?></span>
                    <?php endif; ?>
                    <div class="pull-right">
                        <input type="text" id="searchInput" class="form-control input-sm" placeholder="Quick search..." style="width: 200px; display: inline-block;">
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (empty($documents)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No documents found. Click "Upload Document" to add documents to your repository.
                        </div>
                    <?php else: ?>
                        <div id="documentsContainer">
                            <?php foreach ($documents as $doc): ?>
                                <?php
                                // Determine file icon
                                $file_ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                                $icon_class = 'fa-file-o';
                                $icon_color = '#95a5a6';

                                if (in_array($file_ext, ['pdf'])) {
                                    $icon_class = 'fa-file-pdf-o';
                                    $icon_color = '#e74c3c';
                                } elseif (in_array($file_ext, ['doc', 'docx'])) {
                                    $icon_class = 'fa-file-word-o';
                                    $icon_color = '#3498db';
                                } elseif (in_array($file_ext, ['xls', 'xlsx'])) {
                                    $icon_class = 'fa-file-excel-o';
                                    $icon_color = '#27ae60';
                                } elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                                    $icon_class = 'fa-file-image-o';
                                    $icon_color = '#9b59b6';
                                } elseif (in_array($file_ext, ['zip', 'rar', '7z'])) {
                                    $icon_class = 'fa-file-archive-o';
                                    $icon_color = '#f39c12';
                                } elseif (in_array($file_ext, ['md', 'markdown'])) {
                                    $icon_class = 'fa-file-text-o';
                                    $icon_color = '#16a085';
                                }

                                // Status badge
                                $status_badges = [
                                    'active' => 'success',
                                    'expiring_soon' => 'warning',
                                    'expired' => 'danger',
                                    'archived' => 'default'
                                ];
                                $status_badge = $status_badges[$doc['status']] ?? 'default';
                                ?>

                                <div class="doc-card">
                                    <div class="row">
                                        <div class="col-md-1 text-center">
                                            <i class="fa <?php echo $icon_class; ?> file-icon" style="color: <?php echo $icon_color; ?>"></i>
                                        </div>
                                        <div class="col-md-7">
                                            <h4 style="margin-top: 0;">
                                                <a href="documents_view.php?id=<?php echo $doc['doc_id']; ?>">
                                                    <?php echo htmlspecialchars($doc['doc_name']); ?>
                                                </a>
                                            </h4>
                                            <?php if ($doc['description']): ?>
                                                <p class="text-muted" style="margin-bottom: 5px;">
                                                    <?php echo htmlspecialchars(substr($doc['description'], 0, 120)) . (strlen($doc['description']) > 120 ? '...' : ''); ?>
                                                </p>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <i class="fa fa-user"></i> <?php echo htmlspecialchars($doc['uploaded_by_name']); ?>
                                                <i class="fa fa-calendar" style="margin-left: 10px;"></i> <?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?>
                                                <i class="fa fa-hdd-o" style="margin-left: 10px;"></i> <?php echo formatBytes($doc['file_size']); ?>
                                                <?php if ($doc['link_count'] > 0): ?>
                                                    <i class="fa fa-link" style="margin-left: 10px;"></i> <?php echo $doc['link_count']; ?> link<?php echo $doc['link_count'] > 1 ? 's' : ''; ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-2">
                                            <span class="label label-default doc-type-badge">
                                                <?php echo strtoupper($doc['doc_type']); ?>
                                            </span>
                                            <br>
                                            <span class="label label-<?php echo $status_badge; ?> doc-type-badge" style="margin-top: 5px;">
                                                <?php echo ucfirst(str_replace('_', ' ', $doc['status'])); ?>
                                            </span>
                                            <?php if ($doc['expiry_date'] && strtotime($doc['expiry_date']) > time()): ?>
                                                <br><small class="text-muted" style="display: block; margin-top: 5px;">
                                                    Expires: <?php echo date('d M Y', strtotime($doc['expiry_date'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2 text-right">
                                            <a href="documents_view.php?id=<?php echo $doc['doc_id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-success btn-sm" download>
                                                <i class="fa fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
<script src="assets/js/custom.js"></script>
<script>
$(document).ready(function() {
    // Quick search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#documentsContainer .doc-card').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>
</body>
</html>
