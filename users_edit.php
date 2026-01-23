<?php
/**
 * DPA Tool - Edit User
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin');

$org_id = get_current_org_id();
$errors = [];

// Get user ID
$user_id = intval($_GET['id'] ?? 0);

if (!$user_id) {
    set_flash_message('Invalid user ID', 'error');
    redirect('users.php');
}

// Get user details
$user = get_user_by_id($user_id);

if (!$user || $user['org_id'] != $org_id) {
    set_flash_message('User not found', 'error');
    redirect('users.php');
}

// Get roles
$roles_query = "SELECT * FROM roles ORDER BY role_name";
$roles_stmt = db_query($roles_query);
$roles = db_fetch_all($roles_stmt);

// Get departments
$dept_query = "SELECT * FROM departments WHERE org_id = ? ORDER BY dept_name";
$dept_stmt = db_query($dept_query, [$org_id]);
$departments = db_fetch_all($dept_stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 0);
    $dept_id = !empty($_POST['dept_id']) ? intval($_POST['dept_id']) : null;
    $status = sanitize_input($_POST['status'] ?? 'active');
    $force_password_reset = isset($_POST['force_password_reset']) ? 1 : 0;

    // Validation
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    if (empty($role_id)) {
        $errors[] = 'Role is required';
    }

    // Validate phone number
    if (!empty($phone) && !preg_match('/^[\+]?[\d\s\-\(\)]{7,20}$/', $phone)) {
        $errors[] = 'Invalid phone number format. Use digits, spaces, dashes, or parentheses (7-20 characters).';
    }

    // Handle profile picture upload
    $profile_picture_path = $user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($file_ext, $allowed_image_types)) {
            $errors[] = 'Profile picture must be JPG, PNG, or GIF';
        } elseif ($file['size'] > 2097152) { // 2MB
            $errors[] = 'Profile picture must be less than 2MB';
        } else {
            $upload_dir = UPLOAD_PATH . 'profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) {
                    unlink($user['profile_picture']);
                }
                $profile_picture_path = 'uploads/profiles/' . $new_filename;
            } else {
                $errors[] = 'Failed to upload profile picture';
            }
        }
    }

    // Password change (optional)
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($new_password)) {
        if (strlen($new_password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
    }

    if (empty($errors)) {
        $data = [
            'role_id' => $role_id,
            'dept_id' => $dept_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'status' => $status,
            'profile_picture' => $profile_picture_path
        ];

        $success = update_user($user_id, $data);

        // Update password if provided
        if ($success && !empty($new_password)) {
            change_password($user_id, $new_password);
        }

        // Force password reset if checked
        if ($success && $force_password_reset) {
            $reset_query = "UPDATE users SET password_reset_required = 1 WHERE user_id = ?";
            db_query($reset_query, [$user_id]);
            log_audit($org_id, get_current_user_id(), 'user', $user_id, 'update', null, ['action' => 'force_password_reset']);
        }

        if ($success) {
            set_flash_message('User updated successfully', 'success');
            redirect('users.php');
        } else {
            $errors[] = 'Failed to update user';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Edit User</title>
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
                        <h2>Edit User</h2>
                        <h5>Update user account information</h5>
                    </div>
                </div>
                <hr />

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
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-edit"></i> User Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="users_edit.php?id=<?php echo $user_id; ?>" enctype="multipart/form-data">

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Profile Picture</label>
                                                <div style="margin-bottom: 10px;">
                                                    <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>"
                                                             alt="Current Profile Picture"
                                                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd;">
                                                    <?php else: ?>
                                                        <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #ddd; display: flex; align-items: center; justify-content: center; border: 2px solid #ccc;">
                                                            <i class="fa fa-user" style="font-size: 50px; color: #999;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="file" name="profile_picture" class="form-control" accept="image/jpeg,image/png,image/gif">
                                                <small class="help-block">Upload JPG, PNG, or GIF (Max 2MB)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>First Name <span class="text-danger">*</span></label>
                                                <input type="text" name="first_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? $user['first_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Last Name <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? $user['last_name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" class="form-control"
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                                <small class="help-block">Email cannot be changed</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Phone</label>
                                                <input type="text" name="phone" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Role <span class="text-danger">*</span></label>
                                                <select name="role_id" class="form-control" required>
                                                    <option value="">-- Select Role --</option>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo $role['role_id']; ?>"
                                                            <?php echo (isset($_POST['role_id']) ? $_POST['role_id'] : $user['role_id']) == $role['role_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($role['role_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <select name="dept_id" class="form-control">
                                                    <option value="">-- Select Department --</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                        <option value="<?php echo $dept['dept_id']; ?>"
                                                            <?php echo (isset($_POST['dept_id']) ? $_POST['dept_id'] : $user['dept_id']) == $dept['dept_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Status <span class="text-danger">*</span></label>
                                                <select name="status" class="form-control" required>
                                                    <option value="active" <?php echo (isset($_POST['status']) ? $_POST['status'] : $user['status']) == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo (isset($_POST['status']) ? $_POST['status'] : $user['status']) == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="locked" <?php echo (isset($_POST['status']) ? $_POST['status'] : $user['status']) == 'locked' ? 'selected' : ''; ?>>Locked</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h4>Change Password (Optional)</h4>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>New Password</label>
                                                <input type="password" name="new_password" class="form-control"
                                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                                <small class="help-block">Leave blank to keep current password</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Confirm New Password</label>
                                                <input type="password" name="confirm_password" class="form-control"
                                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="force_password_reset" value="1">
                                                    <strong>Force Password Reset on Next Login</strong>
                                                </label>
                                                <p class="help-block" style="margin-left: 20px;">
                                                    Check this box to require the user to reset their password the next time they log in.
                                                    This is useful for security purposes or when you've set a temporary password.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update User
                                        </button>
                                        <a href="users.php" class="btn btn-default">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> User Details
                            </div>
                            <div class="panel-body">
                                <dl>
                                    <dt>User ID</dt>
                                    <dd><?php echo $user['user_id']; ?></dd>

                                    <dt>Created</dt>
                                    <dd><?php echo format_datetime($user['created_at'], 'd M Y H:i'); ?></dd>

                                    <dt>Last Login</dt>
                                    <dd>
                                        <?php
                                        if ($user['last_login']) {
                                            echo format_datetime($user['last_login'], 'd M Y H:i');
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </dd>

                                    <dt>2FA Enabled</dt>
                                    <dd><?php echo $user['two_factor_enabled'] ? 'Yes' : 'No'; ?></dd>
                                </dl>
                            </div>
                        </div>

                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <i class="fa fa-exclamation-triangle"></i> Warning
                            </div>
                            <div class="panel-body">
                                <p><strong>Note:</strong> Changing a user's role will affect their access permissions immediately.</p>
                                <p>Setting status to 'Inactive' will prevent the user from logging in.</p>
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
            // Phone validation function
            function isValidPhone(phone) {
                var phoneRegex = /^[\+]?[\d\s\-\(\)]{7,20}$/;
                return phoneRegex.test(phone);
            }

            // Real-time phone validation
            $('input[name="phone"]').on('blur', function() {
                var phone = $(this).val().trim();
                if (phone && !isValidPhone(phone)) {
                    $(this).css('border-color', '#d9534f');
                    if (!$(this).next('.validation-error').length) {
                        $(this).after('<small class="validation-error text-danger">Please enter a valid phone number (7-20 digits)</small>');
                    }
                } else {
                    $(this).css('border-color', '');
                    $(this).next('.validation-error').remove();
                }
            });

            // Form validation on submit
            $('form').on('submit', function(e) {
                var phone = $('input[name="phone"]').val().trim();

                if (phone && !isValidPhone(phone)) {
                    e.preventDefault();
                    alert('Phone number format is invalid. Use digits, spaces, dashes, or parentheses (7-20 characters).');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
