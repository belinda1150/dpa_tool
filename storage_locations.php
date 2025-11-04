<?php
/**
 * DPA Tool - Storage Locations Management
 * Version: 1.0
 * Date: November 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin'); // Only Admin can manage storage locations

$org_id = get_current_org_id();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $location_name = trim($_POST['location_name'] ?? '');
        $location_type = trim($_POST['location_type'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $security_measures = trim($_POST['security_measures'] ?? '');

        // Validation
        if (empty($location_name)) {
            $errors[] = 'Location name is required';
        }
        if (empty($location_type)) {
            $errors[] = 'Location type is required';
        }

        if (empty($errors)) {
            $query = "INSERT INTO storage_locations (org_id, location_name, location_type, provider, country, region, security_measures)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            db_query($query, [$org_id, $location_name, $location_type, $provider, $country, $region, $security_measures]);

            log_audit($org_id, get_current_user_id(), 'storage_location', db_insert_id(), 'create');
            set_flash_message('Storage location added successfully', 'success');
            redirect('storage_locations.php');
        }
    }

    if ($action === 'edit') {
        $location_id = intval($_POST['location_id'] ?? 0);
        $location_name = trim($_POST['location_name'] ?? '');
        $location_type = trim($_POST['location_type'] ?? '');
        $provider = trim($_POST['provider'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $security_measures = trim($_POST['security_measures'] ?? '');

        // Validation
        if (empty($location_name)) {
            $errors[] = 'Location name is required';
        }
        if (empty($location_type)) {
            $errors[] = 'Location type is required';
        }

        if (empty($errors)) {
            $query = "UPDATE storage_locations
                      SET location_name = ?, location_type = ?, provider = ?, country = ?, region = ?, security_measures = ?
                      WHERE location_id = ? AND org_id = ?";
            db_query($query, [$location_name, $location_type, $provider, $country, $region, $security_measures, $location_id, $org_id]);

            log_audit($org_id, get_current_user_id(), 'storage_location', $location_id, 'update');
            set_flash_message('Storage location updated successfully', 'success');
            redirect('storage_locations.php');
        }
    }

    if ($action === 'delete') {
        $location_id = intval($_POST['location_id'] ?? 0);

        // Check if location is being used in ROPA
        $check_query = "SELECT COUNT(*) as count FROM ropa_storage_locations WHERE location_id = ?";
        $stmt = db_query($check_query, [$location_id]);
        $result = db_fetch_one($stmt);

        if ($result['count'] > 0) {
            $errors[] = 'Cannot delete storage location - it is being used in ' . $result['count'] . ' ROPA entries';
        } else {
            $query = "DELETE FROM storage_locations WHERE location_id = ? AND org_id = ?";
            db_query($query, [$location_id, $org_id]);

            log_audit($org_id, get_current_user_id(), 'storage_location', $location_id, 'delete');
            set_flash_message('Storage location deleted successfully', 'success');
            redirect('storage_locations.php');
        }
    }
}

// Get all storage locations
$query = "SELECT * FROM storage_locations WHERE org_id = ? ORDER BY location_name";
$stmt = db_query($query, [$org_id]);
$locations = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Storage Locations</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap.css?v=2" rel="stylesheet" />
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
                        <h2>Storage Locations</h2>
                        <h5>Manage data storage locations for ROPA entries</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addLocationModal">
                            <i class="fa fa-plus"></i> Add Storage Location
                        </button>
                    </div>
                </div>
                <br />

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                Storage Locations
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="locationsTable">
                                        <thead>
                                            <tr>
                                                <th>Location Name</th>
                                                <th>Type</th>
                                                <th>Provider</th>
                                                <th>Country</th>
                                                <th>Region</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($locations as $location): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($location['location_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $type = $location['location_type'];
                                                    $label_class = ($type === 'cloud') ? 'label-primary' : (($type === 'hybrid') ? 'label-info' : 'label-default');
                                                    ?>
                                                    <span class="label <?php echo $label_class; ?>"><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($location['provider'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($location['country'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($location['region'] ?? '-'); ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-xs" onclick="editLocation(<?php echo htmlspecialchars(json_encode($location)); ?>)">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-xs" onclick="deleteLocation(<?php echo $location['location_id']; ?>, '<?php echo htmlspecialchars(addslashes($location['location_name'])); ?>')">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
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

    <!-- Add Location Modal -->
    <div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="storage_locations.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add Storage Location</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Location Name <span class="text-danger">*</span></label>
                            <input type="text" name="location_name" class="form-control" required
                                   placeholder="e.g., Main Data Center, AWS S3 Bucket">
                        </div>
                        <div class="form-group">
                            <label>Location Type <span class="text-danger">*</span></label>
                            <select name="location_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="on-premise">On-Premise</option>
                                <option value="cloud">Cloud</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Provider</label>
                            <input type="text" name="provider" class="form-control"
                                   placeholder="e.g., AWS, Azure, Internal">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" class="form-control"
                                   placeholder="e.g., Zimbabwe, South Africa">
                        </div>
                        <div class="form-group">
                            <label>Region</label>
                            <input type="text" name="region" class="form-control"
                                   placeholder="e.g., Harare, Cape Town, EU-West-1">
                        </div>
                        <div class="form-group">
                            <label>Security Measures</label>
                            <textarea name="security_measures" class="form-control" rows="3"
                                      placeholder="Optional: Describe security measures in place"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Add Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Location Modal -->
    <div class="modal fade" id="editLocationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="storage_locations.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="location_id" id="edit_location_id">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Edit Storage Location</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Location Name <span class="text-danger">*</span></label>
                            <input type="text" name="location_name" id="edit_location_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Location Type <span class="text-danger">*</span></label>
                            <select name="location_type" id="edit_location_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="on-premise">On-Premise</option>
                                <option value="cloud">Cloud</option>
                                <option value="hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Provider</label>
                            <input type="text" name="provider" id="edit_provider" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" id="edit_country" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Region</label>
                            <input type="text" name="region" id="edit_region" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Security Measures</label>
                            <textarea name="security_measures" id="edit_security_measures" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Update Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Location Modal -->
    <div class="modal fade" id="deleteLocationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <form method="post" action="storage_locations.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="location_id" id="delete_location_id">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Confirm Deletion</h4>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete the storage location "<strong id="delete_location_name"></strong>"?</p>
                        <p class="text-danger"><i class="fa fa-warning"></i> This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </form>
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
        $('#locationsTable').DataTable({
            "order": [[0, "asc"]],
            "pageLength": 25
        });
    });

    function editLocation(location) {
        $('#edit_location_id').val(location.location_id);
        $('#edit_location_name').val(location.location_name);
        $('#edit_location_type').val(location.location_type);
        $('#edit_provider').val(location.provider || '');
        $('#edit_country').val(location.country || '');
        $('#edit_region').val(location.region || '');
        $('#edit_security_measures').val(location.security_measures || '');
        $('#editLocationModal').modal('show');
    }

    function deleteLocation(locationId, locationName) {
        $('#delete_location_id').val(locationId);
        $('#delete_location_name').text(locationName);
        $('#deleteLocationModal').modal('show');
    }
    </script>
</body>
</html>
