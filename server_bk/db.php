<?php
/**
 * Database Connection and License Manager
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
}

class LicenseManager {
    
    /**
     * Get private key (64 bytes secret key)
     */
    private static function getPrivateKey() {
        $key = base64_decode(PRIVATE_KEY);
        
        // If keypair format (96 bytes), extract secret key (first 64 bytes)
        if (strlen($key) === 96) {
            $key = substr($key, 0, 64);
        }
        
        if (strlen($key) !== 64) {
            throw new Exception("Invalid private key length: " . strlen($key));
        }
        
        return $key;
    }
    
    /**
     * Sign response data
     */
    public static function signResponse($data) {
        $key = self::getPrivateKey();
        
        $payload = json_encode($data);
        if ($payload === false) {
            throw new Exception("JSON encode failed");
        }
        
        $signature = sodium_crypto_sign_detached($payload, $key);
        
        return [
            'payload' => base64_encode($payload),
            'signature' => base64_encode($signature)
        ];
    }
    
    /**
     * Verify license
     */
    public static function verify($licenseKey, $deviceId, $nonce, $timestamp) {
        $pdo = Database::getInstance()->getPdo();
        
        // Check timestamp (prevent replay attacks)
        $serverTime = time();
        if (abs($serverTime - $timestamp) > MAX_CLOCK_SKEW) {
            return [
                'valid' => false,
                'message' => 'Request expired or clock skew too large',
                'nonce' => $nonce,
                'server_time' => $serverTime
            ];
        }
        
        // Get license from database
        $stmt = $pdo->prepare("SELECT *, customer_name as username FROM licenses WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch();
        
        if (!$license) {
            return [
                'valid' => false,
                'message' => 'License not found',
                'nonce' => $nonce,
                'server_time' => $serverTime
            ];
        }
        
        // Check if revoked
        if ($license['revoked']) {
            return [
                'valid' => false,
                'message' => 'License has been revoked',
                'nonce' => $nonce,
                'server_time' => $serverTime
            ];
        }
        
        // Check expiry
        $expiry = strtotime($license['expiry_date']);
        if ($expiry < $serverTime) {
            return [
                'valid' => false,
                'message' => 'License expired',
                'nonce' => $nonce,
                'server_time' => $serverTime
            ];
        }
        
        // Check device limit
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE license_id = ?");
        $stmt->execute([$license['id']]);
        $deviceCount = $stmt->fetchColumn();
        
        // Check if this device already registered
        $stmt = $pdo->prepare("SELECT * FROM devices WHERE license_id = ? AND device_id = ?");
        $stmt->execute([$license['id'], $deviceId]);
        $existingDevice = $stmt->fetch();
        
        if (!$existingDevice && $deviceCount >= $license['max_devices']) {
            return [
                'valid' => false,
                'message' => 'Maximum devices reached (' . $license['max_devices'] . ')',
                'nonce' => $nonce,
                'server_time' => $serverTime
            ];
        }
        
        // Register/update device
        if ($existingDevice) {
            $stmt = $pdo->prepare("UPDATE devices SET last_seen = NOW() WHERE id = ?");
            $stmt->execute([$existingDevice['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO devices (license_id, device_id, activated_at, last_seen) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$license['id'], $deviceId]);
        }
        
        // Calculate days remaining
        $daysLeft = ceil(($expiry - $serverTime) / 86400);
        
        // Log verification
        self::logRequest($licenseKey, $deviceId, 'verify', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        
        return [
            'valid' => true,
            'license_key' => $licenseKey,
            'username' => $license['username'],
            'email' => $license['email'],
            'expiry' => $expiry,
            'days_left' => $daysLeft,
            'max_devices' => $license['max_devices'],
            'nonce' => $nonce,
            'server_time' => $serverTime
        ];
    }
    
    /**
     * Log request for audit
     */
    public static function logRequest($licenseKey, $deviceId, $action, $ip) {
        try {
            $pdo = Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("INSERT INTO audit_log (license_key, device_id, action, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$licenseKey, $deviceId, $action, $ip]);
        } catch (Exception $e) {
            error_log("Failed to log request: " . $e->getMessage());
        }
    }
    
    /**
     * Create new license
     */
    public static function createLicense($licenseKey, $username, $email, $validityDays = null, $maxDevices = null) {
        $pdo = Database::getInstance()->getPdo();
        
        $validityDays = $validityDays ?? DEFAULT_LICENSE_VALIDITY;
        $maxDevices = $maxDevices ?? DEFAULT_MAX_DEVICES;
        
        $expiryDate = date('Y-m-d H:i:s', strtotime("+{$validityDays} days"));
        
        $stmt = $pdo->prepare("INSERT INTO licenses (license_key, customer_name, email, expiry_date, max_devices, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$licenseKey, $username, $email, $expiryDate, $maxDevices]);
        
        return $pdo->lastInsertId();
    }
    
    /**
     * Get license details
     */
    public static function getLicense($licenseId) {
        $pdo = Database::getInstance()->getPdo();
        
        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE id = ?");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch();
        
        if ($license) {
            $stmt = $pdo->prepare("SELECT * FROM devices WHERE license_id = ? ORDER BY activated_at DESC");
            $stmt->execute([$licenseId]);
            $license['devices'] = $stmt->fetchAll();
        }
        
        return $license;
    }
    
    /**
     * Revoke license
     */
    public static function revokeLicense($licenseId) {
        $pdo = Database::getInstance()->getPdo();
        
        $stmt = $pdo->prepare("UPDATE licenses SET revoked = 1 WHERE id = ?");
        $stmt->execute([$licenseId]);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Get all licenses
     */
    public static function getAllLicenses($search = '', $status = '') {
        $pdo = Database::getInstance()->getPdo();
        
        $sql = "SELECT * FROM licenses WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (license_key LIKE ? OR username LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status === 'active') {
            $sql .= " AND revoked = 0 AND expiry_date > NOW()";
        } elseif ($status === 'expired') {
            $sql .= " AND expiry_date <= NOW()";
        } elseif ($status === 'revoked') {
            $sql .= " AND revoked = 1";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
