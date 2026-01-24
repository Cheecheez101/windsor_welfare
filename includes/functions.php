<?php
function format_money($amount) {
    return number_format($amount, 2);
}

function calculate_token($savings) {
    return $savings * 0.20;
}

function log_audit($pdo, $action, $table_name, $record_id, $old_values = null, $new_values = null) {
    $user_id = $_SESSION['user_id'] ?? null;
    $old_values = is_array($old_values) ? json_encode($old_values) : $old_values;
    $new_values = is_array($new_values) ? json_encode($new_values) : $new_values;
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $table_name, $record_id, $old_values, $new_values]);
}

function generate_password_reset_token() {
    return bin2hex(random_bytes(32));
}

function send_password_reset_email($email, $token) {
    // For now, we'll just return the reset link
    // In a real application, you'd send an email
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/windsor_welfare/reset_password.php?token=" . $token;
    return $reset_link;
}

function validate_password_reset_token($pdo, $token) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function update_user_password($pdo, $user_id, $new_password) {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
    return $stmt->execute([$hashed_password, $user_id]);
}

function initiate_password_reset($pdo, $identifier) {
    // Find user by employee_id or username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_id = ? OR username = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    // Generate reset token
    $token = generate_password_reset_token();
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Update user with reset token
    $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $user['id']]);

    // Log the password reset request
    log_audit($pdo, 'password_reset_requested', 'users', $user['id'], null, ['reset_token_generated' => true]);

    return [
        'user' => $user,
        'token' => $token,
        'reset_link' => send_password_reset_email($user['email'] ?? '', $token)
    ];
}