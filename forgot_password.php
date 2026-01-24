<?php
session_start();
require_once 'includes/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $reset_code = trim($_POST['reset_code'] ?? '');

    if (!empty($reset_code)) {
        // Handle admin-provided reset code
        $stmt = $pdo->prepare("SELECT * FROM users WHERE password_reset_token LIKE ? AND password_reset_expires > NOW()");
        $stmt->execute([$reset_code . '%']);
        $user = $stmt->fetch();

        if ($user) {
            // Redirect to reset password page with the full token
            header("Location: reset_password.php?token=" . $user['password_reset_token']);
            exit();
        } else {
            $error = "Invalid or expired reset code. Please contact an administrator for a new reset code.";
        }
    } elseif (empty($identifier)) {
        $error = "Please enter your Employee ID or Username";
    } else {
        // Handle regular password reset request
        require_once 'includes/functions.php';

        $result = initiate_password_reset($pdo, $identifier);

        if ($result) {
            $message = "Password reset initiated successfully! Since email is not configured, please contact an administrator with this reset code: <strong>" . $result['token'] . "</strong>";
        } else {
            $error = "User not found. Please check your Employee ID or Username.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Windsor Tea Factory Staff Welfare</title>
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
        }

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
            <h1><i class="fas fa-key"></i> Password Reset</h1>
            <p>Enter your Employee ID/Username or use a reset code from an administrator</p>
        </div>

        <form method="POST" class="reset-form">
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

            <div class="form-group">
                <label for="reset_code">Reset Code <small style="color: #6c757d;">(if provided by admin)</small></label>
                <input type="text" id="reset_code" name="reset_code"
                       placeholder="Enter reset code from administrator"
                       value="<?php echo htmlspecialchars($_POST['reset_code'] ?? ''); ?>">
                <i class="fas fa-hashtag"></i>
            </div>

            <div style="text-align: center; margin: 20px 0; color: #6c757d; font-size: 14px;">
                <strong>OR</strong>
            </div>

            <div class="form-group">
                <label for="identifier">Employee ID / Username</label>
                <input type="text" id="identifier" name="identifier"
                       placeholder="Enter your Employee ID or Username"
                       value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
                <i class="fas fa-user"></i>
            </div>

            <button type="submit" class="reset-btn">
                <i class="fas fa-paper-plane"></i> Continue
            </button>
        </form>

        <div class="reset-footer">
            <p>
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </p>
            <p>
                New member? <a href="register.php"><i class="fas fa-user-plus"></i> Register Here</a>
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('identifier');

            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });

            input.focus();
        });
    </script>
</body>
</html>