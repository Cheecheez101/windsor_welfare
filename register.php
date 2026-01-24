<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = trim($_POST['employee_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($employee_id) || empty($full_name) || empty($password) || empty($confirm_password)) {
        $error = "Employee ID, Full Name, Password, and Confirm Password are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if employee_id exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_id = ?");
        $stmt->execute([$employee_id]);
        if ($stmt->fetch()) {
            $error = "Employee ID already exists";
        } else {
            // Insert new member
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $join_date = date('Y-m-d');

            $stmt = $pdo->prepare("
                INSERT INTO users (employee_id, full_name, phone, email, password, role, join_date, department, status)
                VALUES (?, ?, ?, ?, ?, 'member', ?, ?, 'active')
            ");

            if ($stmt->execute([$employee_id, $full_name, $phone, $email, $hashed_password, $join_date, $department])) {
                $success = "Registration successful! You can now log in with your Employee ID.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Registration - Windsor Welfare</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Member Registration</h2>
        <p>Join the Windsor Welfare system by providing your details below.</p>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="employee_id">Employee ID *</label>
                <input type="text" name="employee_id" id="employee_id" required placeholder="e.g., EMP001">
                <small>Your unique employee identifier</small>
            </div>

            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" name="full_name" id="full_name" required placeholder="Enter your full name">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" name="phone" id="phone" placeholder="e.g., +254 712 345 678">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email" placeholder="your.email@company.com">
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <select name="department" id="department">
                    <option value="">-- Select Department --</option>
                    <option value="Administration">Administration</option>
                    <option value="Finance">Finance</option>
                    <option value="Human Resources">Human Resources</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Operations">Operations</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Sales">Sales</option>
                    <option value="Customer Service">Customer Service</option>
                    <option value="Procurement">Procurement</option>
                    <option value="Logistics">Logistics</option>
                    <option value="Quality Assurance">Quality Assurance</option>
                    <option value="Research & Development">Research & Development</option>
                    <option value="Legal">Legal</option>
                    <option value="Security">Security</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" name="password" id="password" required placeholder="Minimum 6 characters">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Re-enter your password">
            </div>

            <button type="submit">Register as Member</button>
        </form>
        <p><a href="login.php">Already have an account? Login with Employee ID</a></p>
    </div>
</body>
</html>