<?php
/**
 * Upload Document
 * Upload new document with version control and entity linking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_name = trim($_POST['doc_name']);
    $doc_type = $_POST['doc_type'];
    $description = trim($_POST['description'] ?? '');
    $version = trim($_POST['version'] ?? '1.0');
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $review_date = !empty($_POST['review_date']) ? $_POST['review_date'] : null;
    $link_entities = $_POST['link_entities'] ?? [];

    // Validate file upload
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash_message('Please select a file to upload.', 'error');
        redirect('documents_upload.php');
    }

    $file = $_FILES['document_file'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_name = $file['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Allowed file types
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar', '7z'];

    if (!in_array($file_ext, $allowed_extensions)) {
        set_flash_message('Invalid file type. Allowed types: ' . implode(', ', $allowed_extensions), 'error');
        redirect('documents_upload.php');
    }

    // Check file size (50MB limit)
    if ($file_size > 50 * 1024 * 1024) {
        set_flash_message('File too large. Maximum size is 50MB.', 'error');
        redirect('documents_upload.php');
    }

    // Create upload directory
    $upload_dir = 'uploads/documents/' . $org_id . '/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $unique_filename = time() . '_' . uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $unique_filename;

    // Calculate file hash
    $file_hash = hash_file('sha256', $file_tmp);

    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    // Move uploaded file
    if (!move_uploaded_file($file_tmp, $file_path)) {
        set_flash_message('Failed to upload file.', 'error');
        redirect('documents_upload.php');
    }

    // Determine status based on expiry date
    $status = 'active';
    if ($expiry_date) {
        $days_to_expiry = (strtotime($expiry_date) - time()) / (60 * 60 * 24);
        if ($days_to_expiry < 0) {
            $status = 'expired';
        } elseif ($days_to_expiry <= 30) {
            $status = 'expiring_soon';
        }
    }

    // Insert document record
    $insert_query = "INSERT INTO documents (
                     org_id, doc_name, doc_type, description, file_path,
                     file_size, file_hash, mime_type, version,
                     expiry_date, review_date, status, uploaded_by
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = db_query($insert_query, [
        $org_id, $doc_name, $doc_type, $description, $file_path,
        $file_size, $file_hash, $mime_type, $version,
        $expiry_date, $review_date, $status, $user_id
    ]);

    $doc_id = db_insert_id();

    // Create entity links
    if (!empty($link_entities)) {
        foreach ($link_entities as $link) {
            if (empty($link)) continue;

            // Parse link format: "entity_type:entity_id"
            $parts = explode(':', $link);
            if (count($parts) === 2) {
                $entity_type = $parts[0];
                $entity_id = intval($parts[1]);

                $link_query = "INSERT INTO document_links (doc_id, entity_type, entity_id)
                              VALUES (?, ?, ?)";
                db_query($link_query, [$doc_id, $entity_type, $entity_id]);
            }
        }
    }

    log_audit($org_id, $user_id, 'CREATE', 'document', $doc_id,
        "Uploaded document: $doc_name ($version)");

    set_flash_message('Document uploaded successfully!', 'success');
    redirect("documents_view.php?id=$doc_id");
}

// Fetch linkable entities for dropdown
$ropa_list = db_fetch_all(db_query("SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? ORDER BY activity_name", [$org_id]));
$dpia_list = db_fetch_all(db_query("SELECT dpia_id, dpia_title FROM dpia WHERE org_id = ? ORDER BY dpia_title", [$org_id]));
$risks_list = db_fetch_all(db_query("SELECT risk_id, risk_title FROM risks WHERE org_id = ? AND status != 'archived' ORDER BY risk_title", [$org_id]));
$incidents_list = db_fetch_all(db_query("SELECT incident_id, incident_title FROM incidents WHERE org_id = ? ORDER BY detected_at DESC LIMIT 20", [$org_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Upload Document</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 4px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .drop-zone:hover {
            border-color: #3498db;
            background-color: #f0f8ff;
        }
        .drop-zone.dragging {
            border-color: #27ae60;
            background-color: #e8f5e9;
        }
    </style>
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
                        <h2>Upload Document</h2>
                        <h5>Upload and manage compliance documents</h5>
                    </div>
                </div>
                <hr />

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="row">
            <div class="col-md-8">
                <!-- File Upload -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <i class="fa fa-file"></i> Document File
                    </div>
                    <div class="panel-body">
                        <div class="drop-zone" id="dropZone">
                            <i class="fa fa-cloud-upload fa-4x text-muted"></i>
                            <h4>Drag & Drop File Here</h4>
                            <p class="text-muted">or click to browse</p>
                            <input type="file" name="document_file" id="fileInput" style="display: none;" required>
                        </div>

                        <div id="fileInfo" style="display: none; margin-top: 15px;">
                            <div class="alert alert-success">
                                <strong><i class="fa fa-check-circle"></i> File Selected:</strong>
                                <span id="fileName"></span>
                                <span id="fileSize" class="pull-right"></span>
                            </div>
                        </div>

                        <small class="text-muted">
                            Supported formats: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG, ZIP, RAR, 7Z<br>
                            Maximum file size: 50MB
                        </small>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <i class="fa fa-info-circle"></i> Document Details
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Document Name <span class="text-danger">*</span></label>
                            <input type="text" name="doc_name" class="form-control" required
                                   placeholder="e.g., Data Protection Agreement, Privacy Policy 2025">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Document Type <span class="text-danger">*</span></label>
                                    <select name="doc_type" class="form-control" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="DPA">DPA (Data Processing Agreement)</option>
                                        <option value="DSA">DSA (Data Sharing Agreement)</option>
                                        <option value="policy">Policy</option>
                                        <option value="certification">Certification</option>
                                        <option value="audit_report">Audit Report</option>
                                        <option value="contract">Contract</option>
                                        <option value="consent">Consent Form/Record</option>
                                        <option value="evidence">Evidence/Proof</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Version</label>
                                    <input type="text" name="version" class="form-control" value="1.0"
                                           placeholder="e.g., 1.0, 2.1, 3.0">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="4"
                                      placeholder="Brief description of the document purpose and contents"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="form-control"
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">For time-sensitive documents</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Review Date (Optional)</label>
                                    <input type="date" name="review_date" class="form-control"
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">When should this document be reviewed?</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Link to Entities (Optional) -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <i class="fa fa-link"></i> Link to Records (Optional)
                    </div>
                    <div class="panel-body">
                        <p class="text-muted">Link this document to relevant records in your system</p>

                        <div id="linksContainer">
                            <div class="link-row" style="margin-bottom: 10px;">
                                <div class="row">
                                    <div class="col-md-10">
                                        <select name="link_entities[]" class="form-control">
                                            <option value="">-- Select Entity to Link --</option>
                                            <optgroup label="ROPA Entries">
                                                <?php foreach ($ropa_list as $ropa): ?>
                                                    <option value="ropa:<?php echo $ropa['ropa_id']; ?>">
                                                        <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="DPIAs">
                                                <?php foreach ($dpia_list as $dpia): ?>
                                                    <option value="dpia:<?php echo $dpia['dpia_id']; ?>">
                                                        <?php echo htmlspecialchars($dpia['dpia_title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Risks">
                                                <?php foreach ($risks_list as $risk): ?>
                                                    <option value="risk:<?php echo $risk['risk_id']; ?>">
                                                        <?php echo htmlspecialchars($risk['risk_title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Incidents">
                                                <?php foreach ($incidents_list as $incident): ?>
                                                    <option value="incident:<?php echo $incident['incident_id']; ?>">
                                                        <?php echo htmlspecialchars($incident['incident_title']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-success btn-block" onclick="addLinkRow()">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-md-4">
                <!-- Action Buttons -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fa fa-upload"></i> Upload Document
                        </button>
                        <a href="documents_list.php" class="btn btn-default btn-block">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>

                <!-- Upload Guidelines -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <i class="fa fa-lightbulb-o"></i> Document Management Tips
                    </div>
                    <div class="panel-body">
                        <ul style="font-size: 12px; margin: 0;">
                            <li><strong>Naming:</strong> Use clear, descriptive names</li>
                            <li><strong>Version Control:</strong> Update version when making changes</li>
                            <li><strong>Expiry Dates:</strong> Set for contracts, certifications, and agreements</li>
                            <li><strong>Review Dates:</strong> Schedule periodic reviews for policies</li>
                            <li><strong>Linking:</strong> Link to relevant ROPA, DPIA, or risk records</li>
                            <li><strong>Access:</strong> Documents are accessible to all authorized users</li>
                        </ul>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <i class="fa fa-shield"></i> Security Notice
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 12px; margin: 0;">
                            <strong>Confidentiality:</strong> Do not upload documents containing:
                        </p>
                        <ul style="font-size: 11px; margin-top: 5px;">
                            <li>Passwords or API keys</li>
                            <li>Unencrypted financial data</li>
                            <li>Personal data of identifiable individuals (unless necessary)</li>
                        </ul>
                        <p style="font-size: 11px; margin: 0;">
                            All uploads are logged for audit purposes.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </form>

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
    const dropZone = $('#dropZone');
    const fileInput = $('#fileInput');
    const fileInfo = $('#fileInfo');
    const fileName = $('#fileName');
    const fileSize = $('#fileSize');

    // Click to browse
    dropZone.on('click', function() {
        fileInput.click();
    });

    // File input change
    fileInput.on('change', function() {
        if (this.files.length > 0) {
            displayFileInfo(this.files[0]);
        }
    });

    // Drag & drop events
    dropZone.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragging');
    });

    dropZone.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');
    });

    dropZone.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');

        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            fileInput[0].files = files;
            displayFileInfo(files[0]);
        }
    });

    function displayFileInfo(file) {
        fileName.text(file.name);
        fileSize.text(formatBytes(file.size));
        fileInfo.show();
        dropZone.css('background-color', '#e8f5e9');
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
});

// Add link row
function addLinkRow() {
    const container = $('#linksContainer');
    const firstRow = container.find('.link-row:first');
    const newRow = firstRow.clone();
    newRow.find('select').val('');
    newRow.find('.btn-success').removeClass('btn-success').addClass('btn-danger')
        .html('<i class="fa fa-minus"></i>')
        .attr('onclick', 'removeLinkRow(this)');
    container.append(newRow);
}

function removeLinkRow(btn) {
    $(btn).closest('.link-row').remove();
}
</script>
</body>
</html>
