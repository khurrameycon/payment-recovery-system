// File: app/controllers/TokenController.php
<?php
require_once BASE_PATH . '/app/middleware/Auth.php';
require_once BASE_PATH . '/app/models/Organization.php';

class TokenController {
    private $db;
    private $auth;
    private $organization;
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->auth = new Auth();
        $this->organization = new Organization();
    }
    
    public function createToken() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            $_SESSION['error'] = "You must be logged in to perform this action";
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not associated with an organization";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to create API tokens";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Get form data
        $name = $_POST['name'] ?? '';
        $scopes = isset($_POST['scopes']) && is_array($_POST['scopes']) ? $_POST['scopes'] : ['read'];
        
        // Validate form data
        if (empty($name)) {
            $_SESSION['error'] = "Token name is required";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $scopesJson = json_encode($scopes);
        
        // Save token
        $stmt = $this->db->prepare("
            INSERT INTO api_access_tokens 
            (organization_id, name, token, scopes, created_by, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->bind_param("issis", $organizationId, $name, $token, $scopesJson, $user['id']);
        
        if ($stmt->execute()) {
            // Log audit event
            $this->logAuditEvent($organizationId, $user['id'], 'create_api_token');
            
            $_SESSION['message'] = "API token created successfully";
            $_SESSION['new_token'] = $token;
        } else {
            $_SESSION['error'] = "Failed to create API token";
        }
        
        header('Location: index.php?route=settings/api');
        exit;
    }
    
    public function revokeToken() {
        // Similar authentication and permission checks as above
        
        // Get token ID
        $tokenId = isset($_POST['token_id']) ? (int)$_POST['token_id'] : 0;
        
        // Delete token
        $stmt = $this->db->prepare("
            DELETE FROM api_access_tokens 
            WHERE id = ? AND organization_id = ?
        ");
        
        $stmt->bind_param("ii", $tokenId, $organizationId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['message'] = "API token revoked successfully";
        } else {
            $_SESSION['error'] = "Failed to revoke API token";
        }
        
        header('Location: index.php?route=settings/api');
        exit;
    }
    
    private function logAuditEvent($organizationId, $userId, $action) {
        $stmt = $this->db->prepare("
            INSERT INTO organization_audit_log 
            (organization_id, user_id, action, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iis", $organizationId, $userId, $action);
        $stmt->execute();
    }
}