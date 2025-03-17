<?php
// File: app/models/Organization.php

class Organization {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Create a new organization
     * 
     * @param string $name Organization name
     * @param string $subdomain Organization subdomain
     * @param int $ownerId User ID of organization owner
     * @param string $plan Subscription plan
     * @return int|bool Organization ID or false on failure
     */
    public function create($name, $subdomain, $ownerId, $plan = 'standard') {
        // Validate subdomain (only alphanumeric and hyphens)
        if (!preg_match('/^[a-z0-9\-]+$/', $subdomain)) {
            return false;
        }
        
        // Check if subdomain already exists
        if ($this->subdomainExists($subdomain)) {
            return false;
        }
        
        try {
            // Begin transaction
            $this->db->begin_transaction();
            
            // Create organization
            $stmt = $this->db->prepare("
                INSERT INTO organizations 
                (name, subdomain, plan, owner_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param("sssi", $name, $subdomain, $plan, $ownerId);
            $stmt->execute();
            
            $organizationId = $this->db->insert_id;
            
            // Create default branding
            $stmt = $this->db->prepare("
                INSERT INTO organization_branding 
                (organization_id, company_name, created_at, updated_at) 
                VALUES (?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param("is", $organizationId, $name);
            $stmt->execute();
            
            // Update owner's organization
            $stmt = $this->db->prepare("
                UPDATE users 
                SET organization_id = ?, organization_role = 'owner' 
                WHERE id = ?
            ");
            
            $stmt->bind_param("ii", $organizationId, $ownerId);
            $stmt->execute();
            
            // Create API key
            $apiKey = $this->generateApiKey();
            $stmt = $this->db->prepare("
                UPDATE organizations 
                SET api_key = ? 
                WHERE id = ?
            ");
            
            $stmt->bind_param("si", $apiKey, $organizationId);
            $stmt->execute();
            
            // Commit transaction
            $this->db->commit();
            
            return $organizationId;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            error_log("Error creating organization: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get organization by ID
     * 
     * @param int $id Organization ID
     * @return array|null Organization data
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM organizations WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $organization = $result->fetch_assoc();
        
        // Load settings
        $organization['settings'] = $this->getSettings($id);
        
        // Load branding
        $organization['branding'] = $this->getBranding($id);
        
        return $organization;
    }
    
    /**
     * Get organization by subdomain
     * 
     * @param string $subdomain Organization subdomain
     * @return array|null Organization data
     */
    public function getBySubdomain($subdomain) {
        $stmt = $this->db->prepare("SELECT * FROM organizations WHERE subdomain = ?");
        $stmt->bind_param("s", $subdomain);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $organization = $result->fetch_assoc();
        
        // Load settings
        $organization['settings'] = $this->getSettings($organization['id']);
        
        // Load branding
        $organization['branding'] = $this->getBranding($organization['id']);
        
        return $organization;
    }
    
    /**
     * Check if subdomain already exists
     * 
     * @param string $subdomain Subdomain to check
     * @return bool True if subdomain exists
     */
    public function subdomainExists($subdomain) {
        $stmt = $this->db->prepare("SELECT id FROM organizations WHERE subdomain = ?");
        $stmt->bind_param("s", $subdomain);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Get organization settings
     * 
     * @param int $organizationId Organization ID
     * @return array Settings
     */
    public function getSettings($organizationId) {
        $stmt = $this->db->prepare("
            SELECT setting_key, setting_value, setting_type 
            FROM organization_settings 
            WHERE organization_id = ?
        ");
        
        $stmt->bind_param("i", $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $settings = [];
        
        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            $type = $row['setting_type'];
            
            // Convert value based on type
            switch ($type) {
                case 'boolean':
                    $value = (bool)$value;
                    break;
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'float':
                    $value = (float)$value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $settings[$key] = $value;
        }
        
        return $settings;
    }
    
    /**
     * Update organization setting
     * 
     * @param int $organizationId Organization ID
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Setting type
     * @return bool Success status
     */
    public function updateSetting($organizationId, $key, $value, $type = 'string') {
        // Prepare value based on type
        switch ($type) {
            case 'json':
                $value = json_encode($value);
                break;
            case 'boolean':
                $value = $value ? '1' : '0';
                break;
        }
        
        // Check if setting exists
        $stmt = $this->db->prepare("
            SELECT id FROM organization_settings 
            WHERE organization_id = ? AND setting_key = ?
        ");
        
        $stmt->bind_param("is", $organizationId, $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing setting
            $stmt = $this->db->prepare("
                UPDATE organization_settings 
                SET setting_value = ?, setting_type = ?, updated_at = NOW() 
                WHERE organization_id = ? AND setting_key = ?
            ");
            
            $stmt->bind_param("ssis", $value, $type, $organizationId, $key);
        } else {
            // Insert new setting
            $stmt = $this->db->prepare("
                INSERT INTO organization_settings 
                (organization_id, setting_key, setting_value, setting_type, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param("isss", $organizationId, $key, $value, $type);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Get organization branding
     * 
     * @param int $organizationId Organization ID
     * @return array|null Branding data
     */
    public function getBranding($organizationId) {
        $stmt = $this->db->prepare("SELECT * FROM organization_branding WHERE organization_id = ?");
        $stmt->bind_param("i", $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Update organization branding
     * 
     * @param int $organizationId Organization ID
     * @param array $branding Branding data
     * @return bool Success status
     */
    public function updateBranding($organizationId, $branding) {
        // Check if branding exists
        $stmt = $this->db->prepare("SELECT id FROM organization_branding WHERE organization_id = ?");
        $stmt->bind_param("i", $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing branding
            $sql = "UPDATE organization_branding SET ";
            $types = "";
            $params = [];
            
            foreach ($branding as $key => $value) {
                if ($key !== 'id' && $key !== 'organization_id' && $key !== 'created_at' && $key !== 'updated_at') {
                    $sql .= "{$key} = ?, ";
                    $types .= "s";
                    $params[] = $value;
                }
            }
            
            $sql .= "updated_at = NOW() WHERE organization_id = ?";
            $types .= "i";
            $params[] = $organizationId;
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters dynamically
            $bindParams = array_merge([$types], $params);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
            
            return $stmt->execute();
        } else {
            // Insert new branding
            $branding['organization_id'] = $organizationId;
            $branding['created_at'] = date('Y-m-d H:i:s');
            $branding['updated_at'] = date('Y-m-d H:i:s');
            
            $keys = implode(', ', array_keys($branding));
            $placeholders = implode(', ', array_fill(0, count($branding), '?'));
            
            $stmt = $this->db->prepare("INSERT INTO organization_branding ({$keys}) VALUES ({$placeholders})");
            
            $types = str_repeat('s', count($branding));
            
            // Bind parameters dynamically
            $bindParams = array_merge([$types], array_values($branding));
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
            
            return $stmt->execute();
        }
    }
    
    /**
     * Helper function for dynamic parameter binding
     */
    private function refValues($arr) {
        $refs = [];
        
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        
        return $refs;
    }
    
    /**
     * Get users in organization
     * 
     * @param int $organizationId Organization ID
     * @return array Users
     */
    public function getUsers($organizationId) {
        $stmt = $this->db->prepare("
            SELECT id, name, email, role, organization_role, status, created_at, last_login 
            FROM users 
            WHERE organization_id = ?
        ");
        
        $stmt->bind_param("i", $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    /**
     * Add user to organization
     * 
     * @param int $organizationId Organization ID
     * @param string $email User email
     * @param string $name User name
     * @param string $role Organization role
     * @return int|bool User ID or false on failure
     */
    public function addUser($organizationId, $email, $name, $role = 'member') {
        // Check if user with this email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Update user's organization
            $stmt = $this->db->prepare("
                UPDATE users 
                SET organization_id = ?, organization_role = ? 
                WHERE id = ?
            ");
            
            $stmt->bind_param("isi", $organizationId, $role, $user['id']);
            
            if ($stmt->execute()) {
                return $user['id'];
            }
            
            return false;
        }
        
        // Create new user
        $password = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO users 
            (name, email, password, role, organization_id, organization_role, status, created_at, updated_at) 
            VALUES (?, ?, ?, 'user', ?, ?, 'active', NOW(), NOW())
        ");
        
        $stmt->bind_param("sssis", $name, $email, $hashedPassword, $organizationId, $role);
        
        if ($stmt->execute()) {
            $userId = $this->db->insert_id;
            
            // TODO: Send invitation email with temporary password
            
            return $userId;
        }
        
        return false;
    }
    
    /**
     * Remove user from organization
     * 
     * @param int $organizationId Organization ID
     * @param int $userId User ID
     * @return bool Success status
     */
    public function removeUser($organizationId, $userId) {
        // Don't allow removing the owner
        $stmt = $this->db->prepare("
            SELECT organization_role FROM users 
            WHERE id = ? AND organization_id = ?
        ");
        
        $stmt->bind_param("ii", $userId, $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $user = $result->fetch_assoc();
        
        if ($user['organization_role'] === 'owner') {
            return false;
        }
        
        // Remove user from organization
        $stmt = $this->db->prepare("
            UPDATE users 
            SET organization_id = NULL, organization_role = NULL 
            WHERE id = ? AND organization_id = ?
        ");
        
        $stmt->bind_param("ii", $userId, $organizationId);
        
        return $stmt->execute();
    }
    
    /**
     * Generate API key
     * 
     * @return string API key
     */
    private function generateApiKey() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Track organization usage
     * 
     * @param int $organizationId Organization ID
     * @param string $metric Metric to increment
     * @param int $value Value to add
     * @return bool Success status
     */
    public function trackUsage($organizationId, $metric, $value = 1) {
        $yearMonth = date('Y-m');
        
        // Check if record exists for this month
        $stmt = $this->db->prepare("
            SELECT id FROM organization_usage 
            WHERE organization_id = ? AND year_month = ?
        ");
        
        $stmt->bind_param("is", $organizationId, $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $stmt = $this->db->prepare("
                UPDATE organization_usage 
                SET {$metric} = {$metric} + ? 
                WHERE organization_id = ? AND year_month = ?
            ");
            
            $stmt->bind_param("iis", $value, $organizationId, $yearMonth);
        } else {
            // Create new record
            $stmt = $this->db->prepare("
                INSERT INTO organization_usage 
                (organization_id, year_month, {$metric}) 
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param("isi", $organizationId, $yearMonth, $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Get organization usage
     * 
     * @param int $organizationId Organization ID
     * @param string $yearMonth Year and month (YYYY-MM)
     * @return array|null Usage data
     */
    public function getUsage($organizationId, $yearMonth = null) {
        if ($yearMonth === null) {
            $yearMonth = date('Y-m');
        }
        
        $stmt = $this->db->prepare("
            SELECT * FROM organization_usage 
            WHERE organization_id = ? AND year_month = ?
        ");
        
        $stmt->bind_param("is", $organizationId, $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Log organization audit event
     * 
     * @param int $organizationId Organization ID
     * @param int $userId User ID
     * @param string $action Action performed
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param array $details Additional details
     * @return bool Success status
     */
    public function logAudit($organizationId, $userId, $action, $entityType = null, $entityId = null, $details = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $detailsJson = $details ? json_encode($details) : null;
        
        $stmt = $this->db->prepare("
            INSERT INTO organization_audit_log 
            (organization_id, user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iississs", $organizationId, $userId, $action, $entityType, $entityId, $detailsJson, $ipAddress, $userAgent);
        
        return $stmt->execute();
    }
}