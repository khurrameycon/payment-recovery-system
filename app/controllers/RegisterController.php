// File: app/controllers/RegisterController.php
<?php
require_once BASE_PATH . '/app/models/Organization.php';

class RegisterController {
    private $db;
    private $organization;
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->organization = new Organization();
    }
    
    public function showRegister() {
        include BASE_PATH . '/app/views/register.php';
    }
    
    public function processRegister() {
        // Get form data
        $name = $_POST['name'] ?? '';
        $subdomain = $_POST['subdomain'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Validate form data
        if (empty($name) || empty($subdomain) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All fields are required";
            header('Location: index.php?route=register');
            exit;
        }
        
        // Validate subdomain
        if (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            $_SESSION['error'] = "Subdomain can only contain lowercase letters, numbers, and hyphens";
            header('Location: index.php?route=register');
            exit;
        }
        
        // Check if subdomain is available
        if ($this->organization->subdomainExists($subdomain)) {
            $_SESSION['error'] = "This subdomain is already taken";
            header('Location: index.php?route=register');
            exit;
        }
        
        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users 
            (name, email, password, role, status, created_at, updated_at) 
            VALUES (?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $stmt->execute();
        
        $userId = $this->db->insert_id;
        
        // Create organization
        $organizationId = $this->organization->create($name, $subdomain, $userId, 'standard');
        
        if (!$organizationId) {
            $_SESSION['error'] = "Failed to create organization";
            header('Location: index.php?route=register');
            exit;
        }
        
        // Set session data and redirect
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['organization_id'] = $organizationId;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        header('Location: index.php?route=dashboard');
        exit;
    }
}