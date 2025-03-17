<?php
// File: app/middleware/AuthMiddleware.php

class AuthMiddleware {
    private $db;
    private $sessionTimeout = 3600; // 1 hour session timeout
    private $maxLoginAttempts = 5; // Maximum failed login attempts
    private $lockoutDuration = 900; // 15 minutes lockout duration
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool Authentication status
     */
    public function isAuthenticated() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check if session has expired
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Check if user exists and is active
        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT id, role FROM users WHERE id = ? AND status = 'active'");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->logout();
            return false;
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Authenticate user with email and password
     * 
     * @param string $email User email
     * @param string $password User password
     * @return bool|array Authentication status or user data
     */
    public function login($email, $password) {
        // Check if account is locked
        if ($this->isAccountLocked($email)) {
            $_SESSION['error'] = "Account is temporarily locked due to too many failed login attempts. Please try again later.";
            return false;
        }
        
        // Get user by email
        $stmt = $this->db->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->recordFailedLogin($email);
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            $_SESSION['error'] = "Account is inactive. Please contact administrator.";
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $this->recordFailedLogin($email);
            return false;
        }
        
        // Reset failed login attempts
        $this->resetFailedLogins($email);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Record login
        $this->recordLogin($user['id']);
        
        return $user;
    }
    
    /**
     * Log out user
     */
    public function logout() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Record logout if user was logged in
        if (isset($_SESSION['user_id'])) {
            $this->recordLogout($_SESSION['user_id']);
        }
        
        // Clear session
        $_SESSION = array();
        
        // Destroy session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Check if user has required role
     * 
     * @param string|array $roles Required role(s)
     * @return bool Authorization status
     */
    public function hasRole($roles) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        // Convert single role to array
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        // Check if user role is in required roles
        return in_array($_SESSION['user_role'], $roles);
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT id, name, email, role, last_login FROM users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool Verification status
     */
    public function verifyCSRFToken($token) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists and matches
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate new CSRF token
     * 
     * @return string New CSRF token
     */
    public function refreshCSRFToken() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate new token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Check if account is locked
     * 
     * @param string $email User email
     * @return bool Lock status
     */
    private function isAccountLocked($email) {
        $stmt = $this->db->prepare("SELECT * FROM login_attempts WHERE email = ? AND success = 0 ORDER BY attempt_time DESC LIMIT ?");
        $stmt->bind_param('si', $email, $this->maxLoginAttempts);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows < $this->maxLoginAttempts) {
            return false;
        }
        
        // Get the oldest of the last X failed attempts
        $attempts = [];
        while ($row = $result->fetch_assoc()) {
            $attempts[] = $row;
        }
        
        $oldestAttempt = end($attempts);
        $lockoutTime = strtotime($oldestAttempt['attempt_time']) + $this->lockoutDuration;
        
        return time() < $lockoutTime;
    }
    
    /**
     * Record failed login attempt
     * 
     * @param string $email User email
     */
    private function recordFailedLogin($email) {
        $stmt = $this->db->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (?, ?, 0, NOW())");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt->bind_param('ss', $email, $ipAddress);
        $stmt->execute();
    }
    
    /**
     * Reset failed login attempts
     * 
     * @param string $email User email
     */
    private function resetFailedLogins($email) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE email = ? AND success = 0");
        $stmt->bind_param('s', $email);
        $stmt->execute();
    }
    
    /**
     * Record successful login
     * 
     * @param int $userId User ID
     */
    private function recordLogin($userId) {
        // Update last login time
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        
        // Log login attempt
        $stmt = $this->db->prepare("INSERT INTO login_attempts (email, ip_address, user_id, success, attempt_time) VALUES (?, ?, ?, 1, NOW())");
        $email = $_SESSION['user_email'];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt->bind_param('ssi', $email, $ipAddress, $userId);
        $stmt->execute();
    }
    
    /**
     * Record logout
     * 
     * @param int $userId User ID
     */
    private function recordLogout($userId) {
        $stmt = $this->db->prepare("INSERT INTO user_activity_log (user_id, activity_type, ip_address, activity_time) VALUES (?, 'logout', ?, NOW())");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt->bind_param('is', $userId, $ipAddress);
        $stmt->execute();
    }
    
    /**
     * Create required database tables
     */
    public static function createTables() {
        $db = getDbConnection();
        
        // User activity log table
        $sql = "CREATE TABLE IF NOT EXISTS `user_activity_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `activity_type` varchar(50) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `activity_time` datetime NOT NULL,
            `details` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `activity_time` (`activity_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // Login attempts table
        $sql = "CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email` varchar(255) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `success` tinyint(1) NOT NULL DEFAULT 0,
            `attempt_time` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `email` (`email`),
            KEY `ip_address` (`ip_address`),
            KEY `attempt_time` (`attempt_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // Ensure users table has required fields
        $sql = "SHOW TABLES LIKE 'users'";
        $result = $db->query($sql);
        
        if ($result->num_rows === 0) {
            $sql = "CREATE TABLE `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `password` varchar(255) NOT NULL,
                `role` varchar(20) NOT NULL DEFAULT 'user',
                `status` varchar(20) NOT NULL DEFAULT 'active',
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                `last_login` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $db->query($sql);
        }
    }
}