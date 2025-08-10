<?php
// config/db.php

// Database credentials
$dbHost = '127.0.0.1';
$dbName = 'StudentIS';
$dbUser = 'root';
$dbPass = ''; // XAMPP default password is empty
$dbCharset = 'utf8mb4';

// 1) MySQLi Connection
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

// Check MySQLi connection
if ($mysqli->connect_error) {
    error_log("MySQLi Connection Error ({$mysqli->connect_errno}): {$mysqli->connect_error}");
    die('MySQLi database connection failed. Check logs.');
}

// Set charset
if (!$mysqli->set_charset($dbCharset)) {
    error_log("Error loading character set utf8mb4: {$mysqli->error}");
    die('Character set setting failed.');
}

// 2) PDO Connection
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepares
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    error_log("PDO Connection Error: " . $e->getMessage());
    die('PDO database connection failed. Check logs.');
}

// Example usage (optional):
// $stmt = $pdo->query("SELECT * FROM users");
// $result = $stmt->fetchAll();
?>

