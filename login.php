<?php
// login.php
session_start();
require_once 'includes/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');   // can be employee_id or username
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = "All fields required";
    } else {
        // Try login as member (employee_id) first
        $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = ? AND role = 'member'");
        $stmt->execute([$login_id]);
        $user = $stmt->fetch();

        // If not found, try as admin (username)
        if (!$user) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
            $stmt->execute([$login_id]);
            $user = $stmt->fetch();
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['employee_id']   = $user['employee_id'];

            if ($user['role'] === 'admin') {
                header("Location: pages/admin/dashboard.php");
            } else {
                header("Location: pages/member/dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid credentials";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Windsor Tea Factory Staff Welfare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
        }

        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .login-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px 15px 46px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-group input {
            padding-left: 20px;
            padding-right: 52px;
        }

        .password-input-wrap {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #6c757d;
            font-size: 18px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: #495057;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 45px;
            color: #6c757d;
            font-size: 18px;
            pointer-events: none;
        }

        .password-toggle i {
            position: static;
            color: inherit;
            font-size: 18px;
            pointer-events: none;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .forgot-password {
            text-align: center;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .error-message {
            background: #fee;
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .error-message i {
            margin-right: 10px;
            font-size: 16px;
        }

        .login-footer {
            text-align: center;
            padding: 0 30px 30px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .login-footer p {
            margin: 15px 0;
            color: #6c757d;
            font-size: 14px;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
            color: #764ba2;
        }

        .divider {
            margin: 20px 0;
            position: relative;
            text-align: center;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                max-width: none;
            }

            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-form {
                padding: 30px 20px;
            }
        }

        /* Animation for form entrance */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: slideIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-leaf"></i> Windsor Welfare</h1>
            <p>Staff Welfare Management System</p>
        </div>

        <form method="POST" class="login-form">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="login_id">Employee ID / Username</label>
                <input type="text" id="login_id" name="login_id" required
                       placeholder="Enter your Employee ID or Username"
                       value="<?php echo htmlspecialchars($_POST['login_id'] ?? ''); ?>">
                <i class="fas fa-user input-icon"></i>
            </div>

            <div class="form-group password-group">
                <label for="password">Password</label>
                <div class="password-input-wrap">
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password">
                    <button type="button" class="password-toggle" id="password_toggle" aria-label="Show password" title="Show password">
                        <i class="fas fa-eye" id="password_toggle_icon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> Login to Dashboard
            </button>

            <div class="forgot-password">
                <a href="forgot_password.php"><i class="fas fa-key"></i> Forgot Password?</a>
            </div>
        </form>

        <div class="login-footer">
            <div class="divider">
                <span>Quick Links</span>
            </div>

            <p>
                <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
            </p>
            <p>
                New member? <a href="register.php"><i class="fas fa-user-plus"></i> Register Here</a>
            </p>
        </div>
    </div>

    <script>
        // Add focus effects and validation
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input');

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });

            // Auto-focus first input
            document.getElementById('login_id').focus();

            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('password_toggle');
            const passwordToggleIcon = document.getElementById('password_toggle_icon');

            if (passwordInput && passwordToggle && passwordToggleIcon) {
                passwordToggle.addEventListener('click', function () {
                    const showing = passwordInput.type === 'text';
                    passwordInput.type = showing ? 'password' : 'text';
                    passwordToggleIcon.classList.toggle('fa-eye', showing);
                    passwordToggleIcon.classList.toggle('fa-eye-slash', !showing);
                    passwordToggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                    passwordToggle.setAttribute('title', showing ? 'Show password' : 'Hide password');
                });
            }
        });
    </script>
</body>
</html>