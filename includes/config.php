<?php
// Database configuration
$host = 'windsorwelfare.cxucag6oyc0b.eu-north-1.rds.amazonaws.com'; // RDS endpoint
$dbname = 'windsor_welfare';  // Correct DB name with underscore
$username = 'welfare_user';   // Dedicated app user (recommended)
$password = 'StrongPassword123!'; // Replace with your chosen app user password
$port = 3306;                 // Default MySQL port
$ssl_ca = '/etc/ssl/certs/global-bundle.pem'; // Path to Amazon RDS CA cert on EC2

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_CA => $ssl_ca,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false // optional, can enforce true
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
