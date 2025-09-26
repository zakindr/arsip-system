<?php
require_once 'includes/database.php';

$db = Database::getInstance();

// Create users table
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Create default users
$adminPass = password_hash('admin123', PASSWORD_DEFAULT);
$userPass = password_hash('user123', PASSWORD_DEFAULT);

$db->exec("INSERT IGNORE INTO users (username, password, role) VALUES 
    ('admin', '$adminPass', 'admin'),
    ('user', '$userPass', 'user')");

// Ensure mytable exists with correct structure
$db->exec("CREATE TABLE IF NOT EXISTS mytable (
    `NO` DOUBLE,
    `NOMOR ARSIP` VARCHAR(1024) NULL,
    `KODE KLASIFIKASI` VARCHAR(1024) NULL,
    `PERIHAL` VARCHAR(1024) NULL,
    `BENTUK REDAKSI` VARCHAR(1024) NULL,
    `TINGKAT PERKEMBANGAN` VARCHAR(1024) NULL,
    `URAIAN` VARCHAR(1024) NULL,
    `TAHUN` VARCHAR(1024) NULL,
    `FILE` VARCHAR(1024)
)");

echo "<div style='font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; background: #f8f9fa; border-radius: 5px;'>";
echo "<h2>Setup Completed!</h2>";
echo "<p>Database dan tabel berhasil dibuat.</p>";
echo "<h3>Default Users:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / admin123</li>";
echo "<li><strong>User:</strong> user / user123</li>";
echo "</ul>";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Go to Home</a></p>";
echo "</div>";
?>