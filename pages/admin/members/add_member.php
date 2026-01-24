<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/role_auth.php';
require_admin();
require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = password_hash('password', PASSWORD_DEFAULT); // default password

    if (empty($full_name) || empty($employee_id)) {
        $error = "Full name and employee ID are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, employee_id, phone, email, department, password, role) VALUES (?, ?, ?, ?, ?, ?, 'member')");
            $stmt->execute([$full_name, $employee_id, $phone, $email, $department, $password]);
            $success = "Member added successfully! Default password is 'password'.";
        } catch (PDOException $e) {
            $error = "Error adding member: " . $e->getMessage();
        }
    }
}
?>
<h2>Add New Member</h2>

<?php if (isset($success)) echo "<p style='color:green'>$success</p>"; ?>
<?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>

<form method="POST">
    <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" required>
    </div>
    <div class="form-group">
        <label>Employee ID</label>
        <input type="text" name="employee_id" required>
    </div>
    <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone">
    </div>
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email">
    </div>
    <div class="form-group">
        <label>Department</label>
        <select name="department" required>
            <option value="">Select Department</option>
            <option value="Administration">Administration</option>
            <option value="Finance">Finance</option>
            <option value="HR">HR</option>
            <option value="IT">IT</option>
            <option value="Operations">Operations</option>
            <option value="Marketing">Marketing</option>
            <option value="Sales">Sales</option>
            <option value="Customer Service">Customer Service</option>
            <option value="Procurement">Procurement</option>
            <option value="Logistics">Logistics</option>
            <option value="QA">QA</option>
            <option value="R&D">R&D</option>
            <option value="Legal">Legal</option>
            <option value="Security">Security</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Other">Other</option>
        </select>
    </div>
    <button type="submit">Add Member</button>
</form>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/windsor_welfare/includes/footer.php'; ?>