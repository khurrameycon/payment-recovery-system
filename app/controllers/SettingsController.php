<?php
// File: app/controllers/SettingsController.php

require_once BASE_PATH . '/app/middleware/Auth.php';
require_once BASE_PATH . '/app/models/Organization.php';
require_once BASE_PATH . '/app/services/ErrorHandler.php';

class SettingsController {
    private $auth;
    private $organization;
    private $errorHandler;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->organization = new Organization();
        $this->errorHandler = ErrorHandler::getInstance();
    }
    
    /**
     * Display general settings page
     */
    public function general() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Get organization details
        $organization = $this->organization->getById($organizationId);
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access settings.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Load settings view
        include BASE_PATH . '/app/views/settings/general.php';
    }
    
    /**
     * Update general settings
     */
    public function updateGeneral() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to update settings.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token. Please try again.";
            header('Location: index.php?route=settings/general');
            exit;
        }
        
        // Get and validate form data
        $name = $this->errorHandler->validateInput($_POST['name'] ?? '', 'text', ['min_length' => 2, 'max_length' => 100]);
        $email = $this->errorHandler->validateInput($_POST['support_email'] ?? '', 'email');
        $phone = $this->errorHandler->validateInput($_POST['support_phone'] ?? '', 'text');
        $timezone = $this->errorHandler->validateInput($_POST['timezone'] ?? '', 'text');
        $dateFormat = $this->errorHandler->validateInput($_POST['date_format'] ?? '', 'text');
        
        // Check for validation errors
        if (!$name) {
            $_SESSION['error'] = "Organization name is required and must be between 2-100 characters.";
            header('Location: index.php?route=settings/general');
            exit;
        }
        
        // Update organization name
        $stmt = $this->db->prepare("UPDATE organizations SET name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $name, $organizationId);
        $stmt->execute();
        
        // Update settings
        if ($email) {
            $this->organization->updateSetting($organizationId, 'support_email', $email);
        }
        
        if ($phone) {
            $this->organization->updateSetting($organizationId, 'support_phone', $phone);
        }
        
        if ($timezone) {
            $this->organization->updateSetting($organizationId, 'timezone', $timezone);
        }
        
        if ($dateFormat) {
            $this->organization->updateSetting($organizationId, 'date_format', $dateFormat);
        }
        
        // Log audit event
        $this->organization->logAudit(
            $organizationId, 
            $user['id'], 
            'update_settings',
            'organization',
            $organizationId,
            ['settings' => 'general']
        );
        
        $_SESSION['message'] = "General settings updated successfully.";
        header('Location: index.php?route=settings/general');
        exit;
    }
    
    /**
     * Display branding settings page
     */
    public function branding() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Get organization details
        $organization = $this->organization->getById($organizationId);
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access settings.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Load branding settings view
        include BASE_PATH . '/app/views/settings/branding.php';
    }
    
    /**
     * Update branding settings
     */
    public function updateBranding() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to update settings.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token. Please try again.";
            header('Location: index.php?route=settings/branding');
            exit;
        }
        
        // Get current branding
        $currentBranding = $this->organization->getBranding($organizationId);
        
        // Prepare branding data
        $branding = [
            'company_name' => $this->errorHandler->validateInput($_POST['company_name'] ?? '', 'text', ['max_length' => 100]) ?: $currentBranding['company_name'],
            'primary_color' => $this->errorHandler->validateInput($_POST['primary_color'] ?? '', 'text') ?: $currentBranding['primary_color'],
            'secondary_color' => $this->errorHandler->validateInput($_POST['secondary_color'] ?? '', 'text') ?: $currentBranding['secondary_color'],
            'accent_color' => $this->errorHandler->validateInput($_POST['accent_color'] ?? '', 'text') ?: $currentBranding['accent_color'],
            'email_header' => $this->errorHandler->validateInput($_POST['email_header'] ?? '', 'text') ?: $currentBranding['email_header'],
            'email_footer' => $this->errorHandler->validateInput($_POST['email_footer'] ?? '', 'text') ?: $currentBranding['email_footer'],
            'support_email' => $this->errorHandler->validateInput($_POST['support_email'] ?? '', 'email') ?: $currentBranding['support_email'],
            'support_phone' => $this->errorHandler->validateInput($_POST['support_phone'] ?? '', 'text') ?: $currentBranding['support_phone']
        ];
        
        // Handle logo upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoPath = $this->uploadLogo($organizationId, $_FILES['logo']);
            
            if ($logoPath) {
                $branding['logo_url'] = $logoPath;
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $faviconPath = $this->uploadFavicon($organizationId, $_FILES['favicon']);
            
            if ($faviconPath) {
                $branding['favicon_url'] = $faviconPath;
            }
        }
        
        // Update branding
        if ($this->organization->updateBranding($organizationId, $branding)) {
            // Log audit event
            $this->organization->logAudit(
                $organizationId, 
                $user['id'], 
                'update_branding',
                'organization',
                $organizationId
            );
            
            $_SESSION['message'] = "Branding settings updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update branding settings.";
        }
        
        header('Location: index.php?route=settings/branding');
        exit;
    }
    
    /**
     * Display users & permissions page
     */
    public function users() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Get organization details
        $organization = $this->organization->getById($organizationId);
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access user settings.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Get organization users
        $users = $this->organization->getUsers($organizationId);
        
        // Load users view
        include BASE_PATH . '/app/views/settings/users.php';
    }
    
    /**
     * Add new user to organization
     */
    public function addUser() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to add users.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token. Please try again.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Get and validate form data
        $name = $this->errorHandler->validateInput($_POST['name'] ?? '', 'text', ['min_length' => 2, 'max_length' => 100]);
        $email = $this->errorHandler->validateInput($_POST['email'] ?? '', 'email');
        $role = $_POST['role'] ?? 'member';
        
        // Validate role
        if (!in_array($role, ['member', 'admin'])) {
            $role = 'member';
        }
        
        // Check for validation errors
        if (!$name || !$email) {
            $_SESSION['error'] = "Name and email are required.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Check if organization has reached user limit
        $organization = $this->organization->getById($organizationId);
        $users = $this->organization->getUsers($organizationId);
        
        if (count($users) >= $organization['max_users']) {
            $_SESSION['error'] = "You have reached the maximum number of users for your plan.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Add user to organization
        $userId = $this->organization->addUser($organizationId, $email, $name, $role);
        
        if ($userId) {
            // Log audit event
            $this->organization->logAudit(
                $organizationId, 
                $user['id'], 
                'add_user',
                'user',
                $userId,
                ['role' => $role]
            );
            
            $_SESSION['message'] = "User {$name} ({$email}) has been added to your organization.";
        } else {
            $_SESSION['error'] = "Failed to add user.";
        }
        
        header('Location: index.php?route=settings/users');
        exit;
    }
    
    /**
     * Remove user from organization
     */
    public function removeUser() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to remove users.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token. Please try again.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Get user ID
        $userId = $this->errorHandler->validateInput($_POST['user_id'] ?? 0, 'int');
        
        if (!$userId) {
            $_SESSION['error'] = "Invalid user ID.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Prevent removing yourself
        if ($userId == $user['id']) {
            $_SESSION['error'] = "You cannot remove yourself from the organization.";
            header('Location: index.php?route=settings/users');
            exit;
        }
        
        // Remove user from organization
        if ($this->organization->removeUser($organizationId, $userId)) {
            // Log audit event
            $this->organization->logAudit(
                $organizationId, 
                $user['id'], 
                'remove_user',
                'user',
                $userId
            );
            
            $_SESSION['message'] = "User has been removed from your organization.";
        } else {
            $_SESSION['error'] = "Failed to remove user.";
        }
        
        header('Location: index.php?route=settings/users');
        exit;
    }
    
    /**
     * Display API settings page
     */
    public function api() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Get organization details
        $organization = $this->organization->getById($organizationId);
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to access API settings.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Get API keys
        $apiTokens = $this->getApiTokens($organizationId);
        
        // Get webhooks
        $webhooks = $this->getWebhooks($organizationId);
        
        // Load API settings view
        include BASE_PATH . '/app/views/settings/api.php';
    }
    
    /**
     * Create new API token
     */
    public function createApiToken() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to create API tokens.";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token. Please try again.";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Get and validate form data
        $name = $this->errorHandler->validateInput($_POST['name'] ?? '', 'text', ['min_length' => 2, 'max_length' => 100]);
        $scopes = isset($_POST['scopes']) && is_array($_POST['scopes']) ? $_POST['scopes'] : ['read'];
        
        // Validate scopes
        $allowedScopes = ['read', 'write', 'recover'];
        $scopes = array_intersect($scopes, $allowedScopes);
        
        if (empty($scopes)) {
            $scopes = ['read'];
        }
        
        // Check for validation errors
        if (!$name) {
            $_SESSION['error'] = "Token name is required.";
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
        
        $stmt->bind_param("isssi", $organizationId, $name, $token, $scopesJson, $user['id']);
        
        if ($stmt->execute()) {
            // Log audit event
            $this->organization->logAudit(
                $organizationId, 
                $user['id'], 
                'create_api_token',
                'api_token',
                $this->db->insert_id,
                ['scopes' => $scopes]
            );
            
            $_SESSION['message'] = "API token created successfully. Token: {$token}";
            $_SESSION['new_token'] = $token;
        } else {
            $_SESSION['error'] = "Failed to create API token.";
        }
        
        header('Location: index.php?route=settings/api');
        exit;
    }
    
    /**
     * Revoke API token
     */
    public function revokeApiToken() {
        // Check if user is authenticated
        if (!$this->auth->isAuthenticated()) {
            header('Location: index.php?route=login');
            exit;
        }
        
        // Get current user and organization
        $user = $this->auth->getCurrentUser();
        $organizationId = $user['organization_id'];
        
        if (!$organizationId) {
            $_SESSION['error'] = "You are not part of an organization.";
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        // Check if user has permission
        if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
            $_SESSION['error'] = "You don't have permission to revoke API tokens.";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !$this->auth->verifyCSRFToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Invalid security token. Please try again.";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Get token ID
        $tokenId = $this->errorHandler->validateInput($_POST['token_id'] ?? 0, 'int');
        
        if (!$tokenId) {
            $_SESSION['error'] = "Invalid token ID.";
            header('Location: index.php?route=settings/api');
            exit;
        }
        
        // Revoke token
        $stmt = $this->db->prepare("
            DELETE FROM api_access_tokens
            WHERE id = ? AND organization_id = ?
        ");
        
        $stmt->bind_param("ii", $tokenId, $organizationId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Log audit event
            $this->organization->logAudit(
                $organizationId, 
                $user['id'], 
                'revoke_api_token',
                'api_token',
                $tokenId
            );
            
            $_SESSION['message'] = "API token revoked successfully.";
        } else {
            $_SESSION['error'] = "Failed to revoke API token.";
        }
        
        header('Location: index.php?route=settings/api');
        exit;
    }
    
    /**
     * Get API tokens for organization
     * 
     * @param int $organizationId Organization ID
     * @return array API tokens
     */
    private function getApiTokens($organizationId) {
        $stmt = $this->db->prepare("
            SELECT t.*, u.name as created_by_name 
            FROM api_access_tokens t
            JOIN users u ON t.created_by = u.id
            WHERE t.organization_id = ?
            ORDER BY t.created_at DESC
        ");
        
        $stmt->bind_param("i", $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['scopes'] = json_decode($row['scopes'], true);
            $tokens[] = $row;
        }
        
        return $tokens;
    }
    
    /**
     * Get webhooks for organization
     * 
     * @param int $organizationId Organization ID
     * @return array Webhooks
     */
    private function getWebhooks($organizationId) {
        $stmt = $this->db->prepare("
            SELECT w.*, u.name as created_by_name 
            FROM organization_webhooks w
            JOIN users u ON w.created_by = u.id
            WHERE w.organization_id = ?
            ORDER BY w.created_at DESC
        ");
        
        $stmt->bind_param("i", $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $webhooks = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['events'] = json_decode($row['events'], true);
            $webhooks[] = $row;
        }
        
        return $webhooks;
    }
    
    /**
     * Upload logo
     * 
     * @param int $organizationId Organization ID
     * @param array $file File data
     * @return string|false Path to uploaded file or false on failure
     */
    private function uploadLogo($organizationId, $file) {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error'] = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
            return false;
        }
        
        // Check file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "File too large. Maximum size is 2MB.";
            return false;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadsDir = BASE_PATH . '/public/uploads/logos';
        
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = $organizationId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
        $path = $uploadsDir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return 'uploads/logos/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Upload favicon
     * 
     * @param int $organizationId Organization ID
     * @param array $file File data
     * @return string|false Path to uploaded file or false on failure
     */
    private function uploadFavicon($organizationId, $file) {
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/x-icon'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            $_SESSION['error'] = "Invalid file type. Only JPEG, PNG, GIF, and ICO are allowed.";
            return false;
        }
        
        // Check file size (max 1MB)
        if ($file['size'] > 1 * 1024 * 1024) {
            $_SESSION['error'] = "File too large. Maximum size is 1MB.";
            return false;
        }
        
        // Create uploads directory if it doesn't exist
        $uploadsDir = BASE_PATH . '/public/uploads/favicons';
        
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = $organizationId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.ico';
        $path = $uploadsDir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return 'uploads/favicons/' . $filename;
        }
        
        return false;
    }
    /**
 * Display billing settings page
 */
public function billing() {
    // Check if user is authenticated
    if (!$this->auth->isAuthenticated()) {
        header('Location: index.php?route=login');
        exit;
    }
    
    // Get current user and organization
    $user = $this->auth->getCurrentUser();
    $organizationId = $user['organization_id'];
    
    if (!$organizationId) {
        $_SESSION['error'] = "You are not part of an organization.";
        header('Location: index.php?route=dashboard');
        exit;
    }
    
    // Check if user has permission
    if ($user['organization_role'] !== 'owner' && $user['organization_role'] !== 'admin') {
        $_SESSION['error'] = "You don't have permission to access billing settings.";
        header('Location: index.php?route=dashboard');
        exit;
    }
    
    // Include subscription service
    require_once BASE_PATH . '/app/services/SubscriptionService.php';
    $subscriptionService = new SubscriptionService();
    
    // Get subscription data
    $subscription = $subscriptionService->getSubscription($organizationId);
    $plans = $subscriptionService->getPlans();
    $billingHistory = $subscriptionService->getBillingHistory($organizationId);
    $usageReport = $subscriptionService->getUsageReport($organizationId);
    
    // Get organization users for usage display
    $users = $this->organization->getUsers($organizationId);
    
    // Load billing settings view
    include BASE_PATH . '/app/views/settings/billing.php';
}
}