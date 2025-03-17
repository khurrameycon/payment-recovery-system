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
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Update last login time
            $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
    }
}
?>