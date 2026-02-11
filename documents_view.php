<?php
/**
 * View Document Details
 * Display document information, links, and version history
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get document ID
$doc_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$doc_id) {
    set_flash_message('Invalid document ID.', 'error');
    redirect('documents_list.php');
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (is_admin() || is_dpo()) {
        // Fetch document to delete file
        $doc_query = "SELECT file_path FROM documents WHERE doc_id = ? AND org_id = ?";
        $doc = db_fetch_one(db_query($doc_query, [$doc_id, $org_id]));

        if ($doc) {
            // Delete file
            if (file_exists($doc['file_path'])) {
                unlink($doc['file_path']);
            }

            // Delete database record (cascade will delete links)
            $delete_query = "DELETE FROM documents WHERE doc_id = ? AND org_id = ?";
            db_query($delete_query, [$doc_id, $org_id]);

            log_audit($org_id, $user_id, 'DELETE', 'document', $doc_id, "Deleted document");

            set_flash_message('Document deleted successfully.', 'success');
            redirect('documents_list.php');
        }
    }
}

// Fetch document details
$query = "SELECT d.*,
          CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
          u.email as uploaded_by_email
          FROM documents d
          LEFT JOIN users u ON d.uploaded_by = u.user_id
          WHERE d.doc_id = ? AND d.org_id = ?";

$stmt = db_query($query, [$doc_id, $org_id]);
$doc = db_fetch_one($stmt);

if (!$doc) {
    set_flash_message('Document not found.', 'error');
    redirect('documents_list.php');
}

// Fetch linked entities
$links_query = "SELECT dl.*,
                CASE dl.entity_type
                    WHEN 'ropa' THEN (SELECT activity_name FROM processing_activities WHERE ropa_id = dl.entity_id)
                    WHEN 'dpia' THEN (SELECT dpia_title FROM dpia WHERE dpia_id = dl.entity_id)
                    WHEN 'risk' THEN (SELECT risk_title FROM risks WHERE risk_id = dl.entity_id)
                    WHEN 'incident' THEN (SELECT incident_title FROM incidents WHERE incident_id = dl.entity_id)
                    WHEN 'dsr' THEN CONCAT('DSR-', LPAD(dl.entity_id, 5, '0'))
                    WHEN 'consent' THEN CONCAT('CNS-', LPAD(dl.entity_id, 5, '0'))
                END as entity_name
                FROM document_links dl
                WHERE dl.doc_id = ?
                ORDER BY dl.entity_type, dl.created_at DESC";

$links_stmt = db_query($links_query, [$doc_id]);
$links = db_fetch_all($links_stmt);

// Format file size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Get file extension and icon
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
$status_badge = $status_badges[$doc['status']] ?? 'secondary';

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Document</title>
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
                        <h2><?php echo htmlspecialchars($doc['doc_name']); ?></h2>
                        <h5>v<?php echo htmlspecialchars($doc['version']); ?></h5>
                    </div>
                </div>
                <hr />

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-success" download>
                        <i class="fa fa-download"></i> Download
                    </a>
                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" class="btn btn-info" target="_blank">
                        <i class="fa fa-external-link"></i> Open in New Tab
                    </a>
                    <a href="documents_list.php" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    <?php if (is_admin() || is_dpo()): ?>
                        <button type="button" class="btn btn-danger float-end" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <!-- Document Information -->
            <div class="card border-primary">
                <div class="card-header">
                    <i class="fa fa-info-circle"></i> Document Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Document Name:</th>
                            <td><strong><?php echo htmlspecialchars($doc['doc_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Document Type:</th>
                            <td>
                                <span class="badge bg-secondary" style="font-size: 12px;">
                                    <?php echo strtoupper($doc['doc_type']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Version:</th>
                            <td><?php echo htmlspecialchars($doc['version']); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-<?php echo $status_badge; ?>" style="font-size: 12px;">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($doc['description']): ?>
                        <tr>
                            <th>Description:</th>
                            <td><?php echo nl2br(htmlspecialchars($doc['description'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($doc['expiry_date']): ?>
                        <tr>
                            <th>Expiry Date:</th>
                            <td>
                                <?php echo date('d F Y', strtotime($doc['expiry_date'])); ?>
                                <?php if (strtotime($doc['expiry_date']) < time()): ?>
                                    <span class="badge bg-danger">EXPIRED</span>
                                <?php elseif ((strtotime($doc['expiry_date']) - time()) / (60 * 60 * 24) <= 30): ?>
                                    <span class="badge bg-warning text-dark">EXPIRING SOON</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($doc['review_date']): ?>
                        <tr>
                            <th>Review Date:</th>
                            <td><?php echo date('d F Y', strtotime($doc['review_date'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Markdown Content Viewer (for .md files) -->
            <?php if ($file_ext === 'md' && file_exists($doc['file_path'])): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-file-text"></i> Document Content
                </div>
                <div class="card-body" style="background: white; padding: 30px; line-height: 1.8;">
                    <?php
                    // Simple markdown parser
                    function parseMarkdown($text) {
                        // Headers
                        $text = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $text);
                        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
                        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
                        $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);

                        // Bold
                        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

                        // Italic
                        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

                        // Code blocks
                        $text = preg_replace('/```([a-z]*)\n(.+?)```/s', '<pre><code class="language-$1">$2</code></pre>', $text);

                        // Inline code
                        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

                        // Links
                        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2" target="_blank">$1</a>', $text);

                        // Unordered lists
                        $text = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $text);
                        $text = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $text);

                        // Ordered lists
                        $text = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $text);

                        // Blockquotes
                        $text = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $text);

                        // Horizontal rules
                        $text = preg_replace('/^---$/m', '<hr>', $text);

                        // Line breaks - convert double newlines to paragraphs
                        $text = preg_replace('/\n\n/', '</p><p>', $text);
                        $text = '<p>' . $text . '</p>';

                        // Clean up empty paragraphs and fix nesting
                        $text = preg_replace('/<p>\s*<\/p>/', '', $text);
                        $text = preg_replace('/<p>(<h[1-6]>)/', '$1', $text);
                        $text = preg_replace('/(<\/h[1-6]>)<\/p>/', '$1', $text);
                        $text = preg_replace('/<p>(<ul>)/', '$1', $text);
                        $text = preg_replace('/(<\/ul>)<\/p>/', '$1', $text);
                        $text = preg_replace('/<p>(<pre>)/', '$1', $text);
                        $text = preg_replace('/(<\/pre>)<\/p>/', '$1', $text);
                        $text = preg_replace('/<p>(<blockquote>)/', '$1', $text);
                        $text = preg_replace('/(<\/blockquote>)<\/p>/', '$1', $text);
                        $text = preg_replace('/<p>(<hr>)/', '$1', $text);
                        $text = preg_replace('/(<hr>)<\/p>/', '$1', $text);

                        return $text;
                    }

                    $markdown_content = file_get_contents($doc['file_path']);
                    echo parseMarkdown($markdown_content);
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Linked Entities -->
            <?php if (!empty($links)): ?>
            <div class="card border-info">
                <div class="card-header">
                    <i class="fa fa-link"></i> Linked Records (<?php echo count($links); ?>)
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Record Type</th>
                                <th>Record Name</th>
                                <th>Linked On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($links as $link): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo strtoupper($link['entity_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($link['entity_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo date('d M Y', strtotime($link['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $view_urls = [
                                        'ropa' => 'ropa_view.php?id=',
                                        'dpia' => 'dpia_view.php?id=',
                                        'risk' => 'risk_view.php?id=',
                                        'incident' => 'incident_view.php?id=',
                                        'dsr' => 'dsr_view.php?id=',
                                        'consent' => 'consent_view.php?id='
                                    ];
                                    $view_url = $view_urls[$link['entity_type']] ?? '#';
                                    ?>
                                    <a href="<?php echo $view_url . $link['entity_id']; ?>" class="btn btn-info btn-sm">
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

        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <!-- File Details -->
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-file-o"></i> File Details
                </div>
                <div class="card-body">
                    <table class="table table-borderless" style="font-size: 12px;">
                        <tr>
                            <th>File Name:</th>
                            <td><?php echo htmlspecialchars(basename($doc['file_path'])); ?></td>
                        </tr>
                        <tr>
                            <th>File Size:</th>
                            <td><?php echo formatBytes($doc['file_size']); ?></td>
                        </tr>
                        <tr>
                            <th>File Type:</th>
                            <td><?php echo strtoupper($file_ext); ?></td>
                        </tr>
                        <tr>
                            <th>MIME Type:</th>
                            <td><?php echo htmlspecialchars($doc['mime_type']); ?></td>
                        </tr>
                        <tr>
                            <th>SHA-256 Hash:</th>
                            <td style="word-break: break-all; font-family: monospace;">
                                <?php echo htmlspecialchars(substr($doc['file_hash'], 0, 32)); ?>...
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Upload Information -->
            <div class="card border-success">
                <div class="card-header">
                    <i class="fa fa-user"></i> Upload Information
                </div>
                <div class="card-body">
                    <p style="margin: 0; font-size: 12px;">
                        <strong>Uploaded By:</strong><br>
                        <?php echo htmlspecialchars($doc['uploaded_by_name']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($doc['uploaded_by_email']); ?></small>
                    </p>
                    <hr style="margin: 10px 0;">
                    <p style="margin: 0; font-size: 12px;">
                        <strong>Upload Date:</strong><br>
                        <?php echo date('d F Y, H:i', strtotime($doc['uploaded_at'])); ?>
                    </p>
                    <hr style="margin: 10px 0;">
                    <p style="margin: 0; font-size: 12px;">
                        <strong>Last Updated:</strong><br>
                        <?php echo date('d F Y, H:i', strtotime($doc['updated_at'])); ?>
                    </p>
                </div>
            </div>

            <!-- Document Stats -->
            <?php if (!empty($links) || $doc['expiry_date'] || $doc['review_date']): ?>
            <div class="card border-warning">
                <div class="card-header">
                    <i class="fa fa-bar-chart"></i> Quick Stats
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($links)): ?>
                    <h3 style="margin: 10px 0;"><?php echo count($links); ?></h3>
                    <p class="text-muted" style="margin: 0;">Linked Records</p>
                    <hr style="margin: 10px 0;">
                    <?php endif; ?>

                    <?php if ($doc['expiry_date']): ?>
                        <?php $days_to_expiry = (strtotime($doc['expiry_date']) - time()) / (60 * 60 * 24); ?>
                        <h3 style="margin: 10px 0; color: <?php echo $days_to_expiry < 0 ? '#e74c3c' : ($days_to_expiry <= 30 ? '#f39c12' : '#27ae60'); ?>">
                            <?php echo $days_to_expiry < 0 ? 'EXPIRED' : round($days_to_expiry) . ' days'; ?>
                        </h3>
                        <p class="text-muted" style="margin: 0;">
                            <?php echo $days_to_expiry < 0 ? 'Past Expiry' : 'Until Expiry'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

            </div>
        </div>
    </div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <div class="modal-header" style="background-color: #d9534f; color: white;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    <h4 class="modal-title"><i class="fa fa-trash"></i> Delete Document</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong><i class="fa fa-exclamation-triangle"></i> Warning:</strong>
                        This will permanently delete the document file and all its metadata. This action cannot be undone.
                    </div>
                    <p>Are you sure you want to delete this document?</p>
                    <p><strong><?php echo htmlspecialchars($doc['doc_name']); ?></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash"></i> Confirm Delete
                    </button>
                </div>
            </form>
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
