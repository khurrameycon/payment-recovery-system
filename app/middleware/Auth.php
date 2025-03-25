// Create app/middleware/Auth.php
<?php
class Auth {
    private $db;
    

    public function __construct() {
        $this->db = getDbConnection();
    }
    
/**
 * Check if user is authenticated
 * 
 * @return bool Authentication status
 */

   public function isAuthenticated() {
       if (!isset($_SESSION['user_id'])) {
           return false;
       }
       
       // Check if user exists and is active
       $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
       $stmt->bind_param('i', $_SESSION['user_id']);
       $stmt->execute();
       $result = $stmt->get_result();
       
       if ($result->num_rows === 0) {
           $this->logout();
           return false;
       }
       
       return true;
   }
   
   /**
    * Verify CSRF token
    * 
    * @param string $token Token to verify
    * @return bool Verification status
    */
   public function verifyCSRFToken($token) {
       return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
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
       $stmt = $this->db->prepare("SELECT id, name, email, role, organization_id, organization_role FROM users WHERE id = ?");
       $stmt->bind_param('i', $userId);
       $stmt->execute();
       $result = $stmt->get_result();
       
       if ($result->num_rows === 0) {
           return null;
       }
       
       return $result->fetch_assoc();
   }
    public function checkLogin() {
        if (!isset($_SESSION['user_id'])) {
            // Not logged in, redirect to login page
            header('Location: index.php?route=login');
            exit;
        }
        
        // Check if session is still valid
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Invalid user, log out
            $this->logout();
            header('Location: index.php?route=login');
            exit;
        }
        
        return true;
    }
    
    public function login($email, $password) {
        // Check if account is locked
        if ($this->isAccountLocked($email)) {
            $_SESSION['error'] = "Account is temporarily locked due to too many failed login attempts. Please try again later.";
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $this->recordFailedLogin($email);
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Reset failed login attempts
            $this->resetFailedLogins($email);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['organization_id'] = $user['organization_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['last_activity'] = time();
            
            // Update last login time
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            return true;
        }
        
        $this->recordFailedLogin($email);
        return false;
    }
    
    private function isAccountLocked($email) {
        $stmt = $this->db->prepare("SELECT * FROM login_attempts WHERE email = ? AND success = 0 ORDER BY attempt_time DESC LIMIT 5");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows < 5) {
            return false;
        }
        
        // Get the oldest of the last 5 failed attempts
        $attempts = [];
        while ($row = $result->fetch_assoc()) {
            $attempts[] = $row;
        }
        
        $oldestAttempt = end($attempts);
        $lockoutTime = strtotime($oldestAttempt['attempt_time']) + 900; // 15 minutes
        
        return time() < $lockoutTime;
    }
    
    private function recordFailedLogin($email) {
        $stmt = $this->db->prepare("INSERT INTO login_attempts (email, ip_address, success, attempt_time) VALUES (?, ?, 0, NOW())");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt->bind_param('ss', $email, $ipAddress);
        $stmt->execute();
    }
    
    private function resetFailedLogins($email) {
        $stmt = $this->db->prepare("DELETE FROM login_attempts WHERE email = ? AND success = 0");
        $stmt->bind_param('s', $email);
        $stmt->execute();
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
    }
}
?>