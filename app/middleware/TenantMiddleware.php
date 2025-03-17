<?php
// File: app/middleware/TenantMiddleware.php

require_once BASE_PATH . '/app/models/Organization.php';

class TenantMiddleware {
    private $db;
    private $organization;
    private $organizationId;
    private $subdomain;
    private $customDomain;
    private $branding;
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->organization = new Organization();
        $this->parseHost();
        $this->loadOrganization();
    }
    
    /**
     * Parse host to determine subdomain or custom domain
     */
    private function parseHost() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Handle app.paymentrecovery.com (main app)
        if ($host === 'app.paymentrecovery.com' || $host === 'localhost' || $host === '') {
            $this->subdomain = '';
            $this->customDomain = '';
            return;
        }
        
        // Check if this is a subdomain of paymentrecovery.com
        if (preg_match('/^([a-z0-9\-]+)\.paymentrecovery\.com$/i', $host, $matches)) {
            $this->subdomain = $matches[1];
            $this->customDomain = '';
            return;
        }
        
        // Otherwise, treat as custom domain
        $this->subdomain = '';
        $this->customDomain = $host;
    }
    
    /**
     * Load organization data based on subdomain or custom domain
     */
    private function loadOrganization() {
        $organization = null;
        
        if ($this->subdomain) {
            $organization = $this->organization->getBySubdomain($this->subdomain);
        } else if ($this->customDomain) {
            // Look up by custom domain
            $stmt = $this->db->prepare("SELECT * FROM organizations WHERE custom_domain = ? AND status = 'active'");
            $stmt->bind_param("s", $this->customDomain);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $orgData = $result->fetch_assoc();
                $organization = $this->organization->getById($orgData['id']);
            }
        }
        
        if ($organization) {
            $this->organizationId = $organization['id'];
            $this->branding = $organization['branding'];
        } else {
            $this->organizationId = null;
            $this->branding = null;
        }
    }
    
    /**
     * Get current organization ID
     * 
     * @return int|null Organization ID
     */
    public function getOrganizationId() {
        return $this->organizationId;
    }
    
    /**
     * Get current branding settings
     * 
     * @return array|null Branding settings
     */
    public function getBranding() {
        return $this->branding;
    }
    
    /**
     * Check if current request is for a specific tenant
     * 
     * @return bool True if tenant-specific
     */
    public function isTenantRequest() {
        return $this->organizationId !== null;
    }
    
    /**
     * Set the organization context for the current request
     * 
     * @param int $organizationId Organization ID
     */
    public function setOrganizationContext($organizationId) {
        if ($organizationId) {
            $organization = $this->organization->getById($organizationId);
            
            if ($organization) {
                $this->organizationId = $organization['id'];
                $this->branding = $organization['branding'];
            }
        }
    }
    
    /**
     * Apply tenant context to database queries
     * 
     * This function modifies SQL queries to include organization_id filter
     * 
     * @param string $sql Original SQL query
     * @return string Modified SQL query
     */
    public function applyTenantScope($sql) {
        // Only apply tenant context if we have an organization ID
        if (!$this->organizationId) {
            return $sql;
        }
        
        // Skip queries that don't need tenant scoping
        $skipPatterns = [
            '/^SELECT.*FROM\s+organizations/i',
            '/^SELECT.*FROM\s+users/i',
        ];
        
        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return $sql;
            }
        }
        
        // Add organization_id to WHERE clause for SELECT queries
        if (preg_match('/^SELECT.*FROM\s+(\w+)/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            // Check if table has organization_id column
            $result = $this->db->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'organization_id'");
            
            if ($result && $result->num_rows > 0) {
                // Add organization_id filter
                if (stripos($sql, 'WHERE') !== false) {
                    // Add to existing WHERE clause
                    $sql = preg_replace('/WHERE/i', "WHERE {$tableName}.organization_id = {$this->organizationId} AND ", $sql, 1);
                } else if (stripos($sql, 'GROUP BY') !== false) {
                    // Add before GROUP BY
                    $sql = preg_replace('/GROUP BY/i', "WHERE {$tableName}.organization_id = {$this->organizationId} GROUP BY", $sql, 1);
                } else if (stripos($sql, 'ORDER BY') !== false) {
                    // Add before ORDER BY
                    $sql = preg_replace('/ORDER BY/i', "WHERE {$tableName}.organization_id = {$this->organizationId} ORDER BY", $sql, 1);
                } else if (stripos($sql, 'LIMIT') !== false) {
                    // Add before LIMIT
                    $sql = preg_replace('/LIMIT/i', "WHERE {$tableName}.organization_id = {$this->organizationId} LIMIT", $sql, 1);
                } else {
                    // Add at the end
                    $sql .= " WHERE {$tableName}.organization_id = {$this->organizationId}";
                }
            }
        }
        
        // Add organization_id to INSERT statements
        if (preg_match('/^INSERT\s+INTO\s+(\w+)\s+\((.+?)\)\s+VALUES/i', $sql, $matches)) {
            $tableName = $matches[1];
            $columns = $matches[2];
            
            // Check if table has organization_id column and it's not already in the column list
            $result = $this->db->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'organization_id'");
            
            if ($result && $result->num_rows > 0 && stripos($columns, 'organization_id') === false) {
                // Add organization_id to column list
                $newColumns = $columns . ', organization_id';
                $sql = str_replace("({$columns})", "({$newColumns})", $sql);
                
                // Add organization_id to values
                $sql = preg_replace('/VALUES\s+\(/', "VALUES (", $sql);
                $sql = preg_replace('/\)(\s*,\s*\(|\s*$)/', ", {$this->organizationId})$1", $sql);
            }
        }
        
        // Add organization_id to UPDATE statements
        if (preg_match('/^UPDATE\s+(\w+)\s+SET/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            // Check if table has organization_id column
            $result = $this->db->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'organization_id'");
            
            if ($result && $result->num_rows > 0) {
                // Add organization_id filter
                if (stripos($sql, 'WHERE') !== false) {
                    // Add to existing WHERE clause
                    $sql = preg_replace('/WHERE/i', "WHERE {$tableName}.organization_id = {$this->organizationId} AND ", $sql, 1);
                } else {
                    // Add at the end
                    $sql .= " WHERE {$tableName}.organization_id = {$this->organizationId}";
                }
            }
        }
        
        // Add organization_id to DELETE statements
        if (preg_match('/^DELETE\s+FROM\s+(\w+)/i', $sql, $matches)) {
            $tableName = $matches[1];
            
            // Check if table has organization_id column
            $result = $this->db->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'organization_id'");
            
            if ($result && $result->num_rows > 0) {
                // Add organization_id filter
                if (stripos($sql, 'WHERE') !== false) {
                    // Add to existing WHERE clause
                    $sql = preg_replace('/WHERE/i', "WHERE {$tableName}.organization_id = {$this->organizationId} AND ", $sql, 1);
                } else {
                    // Add at the end
                    $sql .= " WHERE {$tableName}.organization_id = {$this->organizationId}";
                }
            }
        }
        
        return $sql;
    }
    
    /**
     * Apply branding to the current request/response
     * 
     * @param string $content HTML content
     * @return string Modified HTML content
     */
    public function applyBranding($content) {
        // Skip if no branding or no content
        if (!$this->branding || !$content) {
            return $content;
        }
        
        // Replace favicon
        if (!empty($this->branding['favicon_url'])) {
            $content = preg_replace(
                '/<link[^>]+rel=["\'](?:shortcut )?icon["\']/i',
                '<link rel="icon" href="' . htmlspecialchars($this->branding['favicon_url']) . '"',
                $content
            );
        }
        
        // Replace logo
        if (!empty($this->branding['logo_url'])) {
            $content = preg_replace(
                '/<img[^>]+class=["\']logo["\']/i',
                '<img class="logo" src="' . htmlspecialchars($this->branding['logo_url']) . '" alt="' . htmlspecialchars($this->branding['company_name']) . '"',
                $content
            );
        }
        
        // Replace company name
        if (!empty($this->branding['company_name'])) {
            $content = preg_replace(
                '/<title>(.*?)<\/title>/i',
                '<title>' . htmlspecialchars($this->branding['company_name']) . ' - $1</title>',
                $content
            );
        }
        
        // Replace colors
        if (!empty($this->branding['primary_color'])) {
            $content = str_replace('#2563eb', $this->branding['primary_color'], $content);
        }
        
        if (!empty($this->branding['secondary_color'])) {
            $content = str_replace('#4f46e5', $this->branding['secondary_color'], $content);
        }
        
        if (!empty($this->branding['accent_color'])) {
            $content = str_replace('#16a34a', $this->branding['accent_color'], $content);
        }
        
        return $content;
    }
    
    /**
     * Apply email branding to email templates
     * 
     * @param string $template Email template
     * @param array $data Template data
     * @return string Branded email template
     */
    public function applyEmailBranding($template, $data = []) {
        // Skip if no branding or no template
        if (!$this->branding || !$template) {
            return $template;
        }
        
        // Add email header
        if (!empty($this->branding['email_header'])) {
            $template = str_replace('{{EMAIL_HEADER}}', $this->branding['email_header'], $template);
        } else {
            $template = str_replace('{{EMAIL_HEADER}}', '', $template);
        }
        
        // Add email footer
        if (!empty($this->branding['email_footer'])) {
            $template = str_replace('{{EMAIL_FOOTER}}', $this->branding['email_footer'], $template);
        } else {
            $template = str_replace('{{EMAIL_FOOTER}}', '', $template);
        }
        
        // Add company name
        if (!empty($this->branding['company_name'])) {
            $template = str_replace('{{COMPANY_NAME}}', htmlspecialchars($this->branding['company_name']), $template);
        } else {
            $template = str_replace('{{COMPANY_NAME}}', 'Payment Recovery', $template);
        }
        
        // Add support info
        if (!empty($this->branding['support_email'])) {
            $template = str_replace('{{SUPPORT_EMAIL}}', htmlspecialchars($this->branding['support_email']), $template);
        } else {
            $template = str_replace('{{SUPPORT_EMAIL}}', 'support@paymentrecovery.com', $template);
        }
        
        if (!empty($this->branding['support_phone'])) {
            $template = str_replace('{{SUPPORT_PHONE}}', htmlspecialchars($this->branding['support_phone']), $template);
        } else {
            $template = str_replace('{{SUPPORT_PHONE}}', '', $template);
        }
        
        // Add colors
        if (!empty($this->branding['primary_color'])) {
            $template = str_replace('{{PRIMARY_COLOR}}', $this->branding['primary_color'], $template);
        } else {
            $template = str_replace('{{PRIMARY_COLOR}}', '#2563eb', $template);
        }
        
        // Add logo
        if (!empty($this->branding['logo_url'])) {
            $logoHtml = '<img src="' . htmlspecialchars($this->branding['logo_url']) . '" alt="' . htmlspecialchars($this->branding['company_name']) . '" style="max-height: 50px; max-width: 200px;">';
            $template = str_replace('{{LOGO}}', $logoHtml, $template);
        } else {
            $template = str_replace('{{LOGO}}', '{{COMPANY_NAME}}', $template);
        }
        
        // Replace any remaining data placeholders
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . strtoupper($key) . '}}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Get organization subdomain for URL generation
     * 
     * @return string Subdomain or custom domain
     */
    public function getDomain() {
        if ($this->customDomain) {
            return $this->customDomain;
        }
        
        if ($this->subdomain) {
            return $this->subdomain . '.paymentrecovery.com';
        }
        
        return 'app.paymentrecovery.com';
    }
    
    /**
     * Generate a URL with tenant context
     * 
     * @param string $route Route name
     * @param array $params URL parameters
     * @return string Full URL with tenant context
     */
    public function url($route, $params = []) {
        $domain = $this->getDomain();
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        
        $url = "{$protocol}://{$domain}/index.php?route={$route}";
        
        // Add parameters
        foreach ($params as $key => $value) {
            $url .= "&{$key}=" . urlencode($value);
        }
        
        return $url;
    }
}