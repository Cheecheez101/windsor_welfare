<?php
// Database configuration
$host = 'localhost'; // Local MySQL server
$dbname = 'windsor_welfare';  // Database name
$username = 'root';   // Default XAMPP MySQL username
$password = ''; // Default XAMPP MySQL password (empty)
$port = 3306;                 // Default MySQL port
// $ssl_ca = '/etc/ssl/certs/global-bundle.pem'; // Path to Amazon RDS CA cert on EC2

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
