<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$message = '';
$error = '';
$valid_token = false;
$user = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $user = validate_password_reset_token($pdo, $token);

    if ($user) {
        $valid_token = true;
    } else {
        $error = "Invalid or expired reset token. Please request a new password reset.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        if (update_user_password($pdo, $user['id'], $password)) {
            log_audit($pdo, 'password_reset_completed', 'users', $user['id'], null, ['password_updated' => true]);
            $message = "Password updated successfully! You can now <a href='login.php'>login</a> with your new password.";
            $valid_token = false; // Prevent further form submission
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Windsor Tea Factory Staff Welfare</title>
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

        .reset-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            position: relative;
        }

        .reset-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .reset-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .reset-header h1 {
            font-size: 28px;
            font-weight: 300;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .reset-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .reset-form {
            padding: 40px 30px;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            padding-right: 50px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group i {
            position: absolute;
            right: 15px;
            top: 45px;
            color: #6c757d;
            font-size: 18px;
            cursor: pointer;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            display: none;
        }

        .password-strength.weak { color: #dc3545; }
        .password-strength.medium { color: #ffc107; }
        .password-strength.strong { color: #28a745; }

        .reset-btn {
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

        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .reset-btn:active {
            transform: translateY(0);
        }

        .message, .error-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .reset-footer {
            text-align: center;
            padding: 0 30px 30px;
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .reset-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .reset-footer a:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .requirements h4 {
            margin-bottom: 10px;
            color: #495057;
            font-size: 14px;
        }

        .requirements ul {
            margin: 0;
            padding-left: 20px;
            font-size: 13px;
            color: #6c757d;
        }

        .requirements li {
            margin-bottom: 5px;
        }

        @media (max-width: 480px) {
            .reset-header {
                padding: 30px 20px;
            }

            .reset-header h1 {
                font-size: 24px;
            }

            .reset-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1><i class="fas fa-lock"></i> Set New Password</h1>
            <p><?php echo $valid_token ? 'Enter your new password below' : 'Invalid reset link'; ?></p>
        </div>

        <?php if ($valid_token): ?>
            <form method="POST" class="reset-form" id="resetForm">
                <?php if ($message): ?>
                    <div class="message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="requirements">
                    <h4><i class="fas fa-shield-alt"></i> Password Requirements</h4>
                    <ul>
                        <li>At least 6 characters long</li>
                        <li>Both passwords must match</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your new password">
                    <i class="fas fa-eye" id="togglePassword"></i>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Confirm your new password">
                    <i class="fas fa-eye" id="toggleConfirmPassword"></i>
                </div>

                <button type="submit" class="reset-btn">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>
        <?php else: ?>
            <div class="reset-form">
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error ?: 'Invalid reset link'); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="reset-footer">
            <p>
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </p>
            <p>
                Need help? <a href="forgot_password.php"><i class="fas fa-key"></i> Request New Reset</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmInput = document.getElementById('confirm_password');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordStrength = document.getElementById('passwordStrength');
            const form = document.getElementById('resetForm');

            // Password visibility toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });

            // Password strength indicator
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = [];

                if (password.length >= 6) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^A-Za-z0-9]/)) strength++;

                passwordStrength.style.display = password.length > 0 ? 'block' : 'none';

                if (strength < 2) {
                    passwordStrength.textContent = 'Weak password';
                    passwordStrength.className = 'password-strength weak';
                } else if (strength < 4) {
                    passwordStrength.textContent = 'Medium strength';
                    passwordStrength.className = 'password-strength medium';
                } else {
                    passwordStrength.textContent = 'Strong password';
                    passwordStrength.className = 'password-strength strong';
                }
            });

            // Form validation
            if (form) {
                form.addEventListener('submit', function(e) {
                    const password = passwordInput.value;
                    const confirm = confirmInput.value;

                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }

                    if (password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return false;
                    }
                });
            }

            // Auto-focus first input
            if (passwordInput) passwordInput.focus();
        });
    </script>
</body>
</html>