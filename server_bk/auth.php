<?php
/**
 * Authentication Helper
 */

require_once __DIR__ . '/config.php';

class Auth {
    
    private static $passwordFile = __DIR__ . '/admin_password.php';
    
    /**
     * Get current password hash
     */
    private static function getPasswordHash() {
        // Check if password file exists
        if (file_exists(self::$passwordFile)) {
            include self::$passwordFile;
            if (isset($ADMIN_PASSWORD_HASH)) {
                return $ADMIN_PASSWORD_HASH;
            }
        }
        // Fallback to config
        return ADMIN_PASSWORD_HASH;
    }
    
    /**
     * Start secure session
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::startSession();
        
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['last_activity'])) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            self::logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Login
     */
    public static function login($username, $password) {
        self::startSession();
        
        // Check login attempts
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            if (time() - $_SESSION['last_attempt'] < LOGIN_COOLDOWN) {
                $wait = LOGIN_COOLDOWN - (time() - $_SESSION['last_attempt']);
                return ['success' => false, 'message' => "Too many attempts. Wait {$wait} seconds."];
            }
            $_SESSION['login_attempts'] = 0;
        }
        
        // Verify credentials
        $storedHash = self::getPasswordHash();
        if ($username === ADMIN_USERNAME && password_verify($password, $storedHash)) {
            $_SESSION['admin_id'] = 1;
            $_SESSION['admin_username'] = $username;
            $_SESSION['last_activity'] = time();
            $_SESSION['login_attempts'] = 0;
            
            return ['success' => true];
        }
        
        // Track failed attempt
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt'] = time();
        
        $remaining = MAX_LOGIN_ATTEMPTS - $_SESSION['login_attempts'];
        return ['success' => false, 'message' => "Invalid credentials. {$remaining} attempts remaining."];
    }
    
    /**
     * Update password
     */
    public static function updatePassword($newPassword) {
        // Generate new hash
        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        // Create password file
        $content = "<?php\n";
        $content .= "/**\n";
        $content .= " * Admin Password Hash\n";
        $content .= " * AUTO-GENERATED - DO NOT MODIFY\n";
        $content .= " */\n\n";
        $content .= "\$ADMIN_PASSWORD_HASH = '" . $newHash . "';\n";
        
        // Write to file
        if (file_put_contents(self::$passwordFile, $content, LOCK_EX) === false) {
            return ['success' => false, 'message' => 'Failed to write password file. Check permissions.'];
        }
        
        return ['success' => true];
    }
    
    /**
     * Logout
     */
    public static function logout() {
        self::startSession();
        session_destroy();
    }
    
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }
    
    /**
     * Get current admin
     */
    public static function getCurrentAdmin() {
        self::startSession();
        return $_SESSION['admin_username'] ?? null;
    }
}
