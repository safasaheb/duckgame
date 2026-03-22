<?php
// Shared database bootstrap for the project.

$DB_HOST = 'duckrundb1.c944ysk6m0y8.ap-south-1.rds.amazonaws.com';
$DB_USER = 'admin';
$DB_PASS = 'Duckrun26_';
$DB_NAME = 'mario_game';
$table = 'manage';


$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
$conn->select_db($DB_NAME);
$conn->set_charset('utf8mb4');

$conn->query("CREATE TABLE IF NOT EXISTS `$table` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    high_score INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$hasPassword = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'password'");
if ($hasPassword && $hasPassword->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN password VARCHAR(255) NULL");
}

$hasRole = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'role'");
if ($hasRole && $hasRole->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
}

$hasHighScore = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'high_score'");
if ($hasHighScore && $hasHighScore->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN high_score INT NOT NULL DEFAULT 0");
}

$hasCreatedAt = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");
if ($hasCreatedAt && $hasCreatedAt->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
}

// Keep one row per case-insensitive username.
$conn->query("DELETE m1 FROM `$table` m1
              INNER JOIN `$table` m2
                ON LOWER(m1.username) = LOWER(m2.username)
               AND m1.id < m2.id");
$hasUsernameUnique = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = 'uq_manage_username'");
if ($hasUsernameUnique && $hasUsernameUnique->num_rows === 0) {
    $conn->query("ALTER TABLE `$table` ADD UNIQUE KEY uq_manage_username (username)");
}

// One-time migration from legacy users table if it exists.
$hasUsers = $conn->query("SHOW TABLES LIKE 'users'");
if ($hasUsers && $hasUsers->num_rows > 0) {
    $manageCountRes = $conn->query("SELECT COUNT(*) AS c FROM `$table`");
    $manageCount = 0;
    if ($manageCountRes) {
        $manageCountRow = $manageCountRes->fetch_assoc();
        $manageCount = (int)($manageCountRow['c'] ?? 0);
    }
    if ($manageCount === 0) {
        $conn->query("INSERT IGNORE INTO `$table` (username, password)
                      SELECT username, password FROM users");
    }
}
