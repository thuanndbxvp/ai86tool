<?php
/**
 * Configuration File
 * DO NOT EXPOSE THIS FILE PUBLICLY
 */

// Prevent direct access
if (!defined('LICENSE_SYSTEM')) {
    define('LICENSE_SYSTEM', true);
}

// Database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'gomhuong1_syn');
define('DB_PASS', 'Ddz@xG[g^u}5PZlv');
define('DB_NAME', 'gomhuong1_syn');
define('DB_CHARSET', 'utf8mb4');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_COOLDOWN', 300); // 5 minutes

// License settings
define('MAX_CLOCK_SKEW', 120); // 2 minutes
define('DEFAULT_MAX_DEVICES', 2);
define('DEFAULT_LICENSE_VALIDITY', 365); // days

// Ed25519 Private Key (64 bytes) - for signing responses
// This is the SECRET KEY - keep it secure!
define('PRIVATE_KEY', 'In61R39R6QY7YGC1uGL3MN3JG4VyWLeoR1cpSS/aDeh2pglksq3PZmrTbXYv8VRoL+MyHAVy/JQ9FhciEaFaHg==');

// Public key (for reference, not used on server)
define('ED25519_PUBLIC_KEY', 'dqYJZLKtz2Zq0212L/FUaC/jMhwFcvyUPRYXIhGhWh4=');

// Admin credentials (change after first login!)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('admin123', PASSWORD_ARGON2ID));

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Create logs directory if not exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
