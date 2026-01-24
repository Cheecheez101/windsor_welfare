<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

$member_id = $_GET['id'] ?? '';
if (!$member_id || !is_numeric($member_id)) {
    header("Location: list_members.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    echo "<p>Member not found.</p>";
    require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = trim($_POST['role'] ?? 'member');
    $status = trim($_POST['status'] ?? 'active');

    // Validate role
    if (!in_array($role, ['member', 'admin'])) {
        $role = 'member';
    }

    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }

    if (empty($full_name)) {
        $error = "Full name is required.";
    } elseif ($role === 'member' && empty($employee_id)) {
        $error = "Employee ID is required for members.";
    } elseif ($role === 'admin' && empty($employee_id)) {
        // For admins, employee_id can be null, so we'll set it to null
        $employee_id = null;
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, employee_id = ?, phone = ?, email = ?, join_date = ?, department = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$full_name, $employee_id, $phone, $email, $join_date, $department, $role, $status, $member_id]);
            $success = "User updated successfully!";

            // Log the role change if it occurred
            if ($member['role'] !== $role) {
                require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';
                log_audit($pdo, 'UPDATE', 'users', $member_id,
                    json_encode(['role' => $member['role']]),
                    json_encode(['role' => $role])
                );
            }

            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    try {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/functions.php';

        // Generate password reset token
        $token = generate_password_reset_token();
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24 hours for admin-initiated resets

        $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $member_id]);

        log_audit($pdo, 'admin_password_reset_initiated', 'users', $member_id, null, ['reset_token_generated' => true]);

        // Store the reset code for display
        $_SESSION['reset_code'] = substr($token, 0, 8);

        // Redirect to show success message
        header("Location: edit_member.php?id=" . $member_id . "&reset=success");
        exit();
    } catch (PDOException $e) {
        $error = "Error initiating password reset: " . $e->getMessage();
    }
}
?>

<h2>Edit User</h2>
<p>Edit user details and permissions. <strong>Note:</strong> Changing a user's role will affect their access to the system.</p>

<?php if ($success): ?>
    <div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<form method="POST">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Employee ID <small style="color: #6c757d;">(required for members)</small></label>
            <input type="text" name="employee_id" value="<?php echo htmlspecialchars($member['employee_id'] ?? ''); ?>" id="employee_id_field">
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Department</label>
            <select name="department">
                <option value="">-- Select Department --</option>
                <option value="Administration" <?php echo ($member['department'] === 'Administration') ? 'selected' : ''; ?>>Administration</option>
                <option value="Finance" <?php echo ($member['department'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                <option value="Human Resources" <?php echo ($member['department'] === 'Human Resources') ? 'selected' : ''; ?>>Human Resources</option>
                <option value="Information Technology" <?php echo ($member['department'] === 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                <option value="Operations" <?php echo ($member['department'] === 'Operations') ? 'selected' : ''; ?>>Operations</option>
                <option value="Marketing" <?php echo ($member['department'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                <option value="Sales" <?php echo ($member['department'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                <option value="Customer Service" <?php echo ($member['department'] === 'Customer Service') ? 'selected' : ''; ?>>Customer Service</option>
                <option value="Procurement" <?php echo ($member['department'] === 'Procurement') ? 'selected' : ''; ?>>Procurement</option>
                <option value="Logistics" <?php echo ($member['department'] === 'Logistics') ? 'selected' : ''; ?>>Logistics</option>
                <option value="Quality Assurance" <?php echo ($member['department'] === 'Quality Assurance') ? 'selected' : ''; ?>>Quality Assurance</option>
                <option value="Research & Development" <?php echo ($member['department'] === 'Research & Development') ? 'selected' : ''; ?>>Research & Development</option>
                <option value="Legal" <?php echo ($member['department'] === 'Legal') ? 'selected' : ''; ?>>Legal</option>
                <option value="Security" <?php echo ($member['department'] === 'Security') ? 'selected' : ''; ?>>Security</option>
                <option value="Maintenance" <?php echo ($member['department'] === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                <option value="Other" <?php echo ($member['department'] === 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <div class="form-group">
            <label>Join Date</label>
            <input type="date" name="join_date" value="<?php echo htmlspecialchars($member['join_date'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required onchange="toggleEmployeeId(this.value)">
                <option value="member" <?php echo ($member['role'] === 'member') ? 'selected' : ''; ?>>Member (Staff)</option>
                <option value="admin" <?php echo ($member['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="active" <?php echo ($member['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($member['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
    </div>
    <div style="margin-top: 30px;">
        <button type="submit" style="background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">Update User</button>
    </div>
</form>

<!-- Password Reset Section -->
<div style="margin-top: 40px; padding: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; border: 1px solid #dee2e6;">
    <h3 style="margin-top: 0; color: #2c3e50; display: flex; align-items: center;">
        <i class="fas fa-key" style="margin-right: 10px; color: #667eea;"></i>
        Password Management
    </h3>
    <p style="color: #6c757d; margin-bottom: 20px;">Initiate a password reset for this user. They will receive a secure reset code to set their own password.</p>

    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div style="background: linear-gradient(135deg, #d4edda, #a8d5ba); color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <i class="fas fa-check-circle"></i> <strong>Password reset initiated successfully!</strong><br>
            Share this reset code with the user: <strong style="font-size: 18px;"><?php echo htmlspecialchars($_SESSION['reset_code'] ?? ''); unset($_SESSION['reset_code']); ?></strong><br>
            <small>The user can use this code to set their own password securely at the login page.</small>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return confirm('Are you sure you want to initiate a password reset for this user? They will be able to set their own password.')">
        <input type="hidden" name="reset_password" value="1">
        <button type="submit" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s ease;">
            <i class="fas fa-key"></i> Initiate Password Reset
        </button>
    </form>
</div>

<!-- Password Reset Section -->
<div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
    <h3 style="margin-top: 0; color: #2c3e50;">Password Management</h3>
    <p style="color: #6c757d; margin-bottom: 20px;">Reset the user's password. A new secure password will be generated and displayed.</p>

    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            Password reset successfully! The new password is: <strong><?php echo htmlspecialchars($_SESSION['temp_password'] ?? ''); unset($_SESSION['temp_password']); ?></strong>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return confirm('Are you sure you want to reset this user\'s password? The current password will be permanently lost.')">
        <input type="hidden" name="reset_password" value="1">
        <button type="submit" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
            <i class="fas fa-key"></i> Reset Password
        </button>
    </form>
</div>

<script>
function toggleEmployeeId(role) {
    const employeeIdField = document.getElementById('employee_id_field');
    const employeeIdLabel = employeeIdField.previousElementSibling;

    if (role === 'admin') {
        employeeIdField.required = false;
        employeeIdField.placeholder = 'Optional for admins';
        employeeIdLabel.innerHTML = 'Employee ID <small style="color: #6c757d;">(optional for admins)</small>';
    } else {
        employeeIdField.required = true;
        employeeIdField.placeholder = 'Required for members';
        employeeIdLabel.innerHTML = 'Employee ID <small style="color: #6c757d;">(required for members)</small>';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.querySelector('select[name="role"]');
    toggleEmployeeId(roleSelect.value);
});
</script>

<p><a href="list_members.php">← Back to users list</a></p>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>