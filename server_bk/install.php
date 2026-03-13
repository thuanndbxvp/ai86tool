<?php
/**
 * Database Installation Script
 * Run once to set up database tables
 * DELETE THIS FILE AFTER INSTALLATION!
 */

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET " . DB_CHARSET);
    $pdo->exec("USE " . DB_NAME);
    
    // Drop existing tables if schema changed (optional - remove in production)
    $pdo->exec("DROP TABLE IF EXISTS devices");
    $pdo->exec("DROP TABLE IF EXISTS audit_log");
    $pdo->exec("DROP TABLE IF EXISTS licenses");
    
    // Create licenses table
    $pdo->exec("CREATE TABLE licenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        license_key VARCHAR(32) UNIQUE NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        expiry_date DATETIME NOT NULL,
        max_devices INT DEFAULT 2,
        revoked TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_license_key (license_key),
        INDEX idx_revoked (revoked)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create devices table
    $pdo->exec("CREATE TABLE devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        license_id INT NOT NULL,
        device_id VARCHAR(64) NOT NULL,
        activated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_device (license_id, device_id),
        INDEX idx_device_id (device_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Create audit_log table
    $pdo->exec("CREATE TABLE audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        license_key VARCHAR(32) NOT NULL,
        device_id VARCHAR(64) NOT NULL,
        action VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_license_key (license_key),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    echo "<h1>Installation Successful!</h1>";
    echo "<p>Database tables created successfully.</p>";
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li><strong>DELETE THIS FILE (install.php) IMMEDIATELY!</strong></li>";
    echo "<li>Login to admin panel with: admin / admin123</li>";
    echo "<li>Change default password after first login</li>";
    echo "<li>Create your first license key</li>";
    echo "</ol>";
    echo "<p><a href='admin/'>Go to Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Installation Failed</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database credentials in config.php</p>";
}
