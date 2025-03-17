<?php
// File: app/services/TokenManager.php

class TokenManager {
    private $db;
    private $tokenLifespan = 86400; // 24 hours default token lifespan
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Generate a new token for a specific purpose
     * 
     * @param string $type Token type (recovery, api, etc.)
     * @param int $userId Associated user ID
     * @param array $data Additional data to store with token
     * @param int $expiry Token expiration time (seconds from now)
     * @return string Generated token
     */
    public function createToken($type, $userId = null, array $data = [], $expiry = null) {
        // Generate token value
        $token = bin2hex(random_bytes(32));
        
        // Set expiration time
        $expiryTime = time() + ($expiry ?: $this->tokenLifespan);
        $expiryDate = date('Y-m-d H:i:s', $expiryTime);
        
        // Prepare data
        $jsonData = !empty($data) ? json_encode($data) : null;
        
        // Store token in database
        $stmt = $this->db->prepare("
            INSERT INTO tokens 
            (token, token_type, user_id, data, expires_at, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('ssiss', $token, $type, $userId, $jsonData, $expiryDate);
        $stmt->execute();
        
        return $token;
    }
    
    /**
     * Verify and retrieve token data
     * 
     * @param string $token Token to verify
     * @param string $type Token type to check
     * @return array|false Token data or false if invalid
     */
    public function verifyToken($token, $type) {
        // Get token from database
        $stmt = $this->db->prepare("
            SELECT * FROM tokens 
            WHERE token = ? AND token_type = ? AND expires_at > NOW() AND consumed_at IS NULL
        ");
        
        $stmt->bind_param('ss', $token, $type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $tokenData = $result->fetch_assoc();
        
        // Parse stored JSON data
        if ($tokenData['data']) {
            $tokenData['data'] = json_decode($tokenData['data'], true);
        } else {
            $tokenData['data'] = [];
        }
        
        return $tokenData;
    }
    
    /**
     * Mark token as consumed (used)
     * 
     * @param string $token Token to consume
     * @param string $type Token type
     * @return bool Success status
     */
    public function consumeToken($token, $type) {
        $stmt = $this->db->prepare("
            UPDATE tokens 
            SET consumed_at = NOW() 
            WHERE token = ? AND token_type = ? AND consumed_at IS NULL
        ");
        
        $stmt->bind_param('ss', $token, $type);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Refresh token expiration time
     * 
     * @param string $token Token to refresh
     * @param string $type Token type
     * @param int $expiry New expiration time (seconds from now)
     * @return bool Success status
     */
    public function refreshToken($token, $type, $expiry = null) {
        $expiryTime = time() + ($expiry ?: $this->tokenLifespan);
        $expiryDate = date('Y-m-d H:i:s', $expiryTime);
        
        $stmt = $this->db->prepare("
            UPDATE tokens 
            SET expires_at = ? 
            WHERE token = ? AND token_type = ? AND consumed_at IS NULL
        ");
        
        $stmt->bind_param('sss', $expiryDate, $token, $type);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Get all tokens for a user
     * 
     * @param int $userId User ID
     * @param string $type Optional token type filter
     * @return array Array of token data
     */
    public function getUserTokens($userId, $type = null) {
        if ($type) {
            $stmt = $this->db->prepare("
                SELECT * FROM tokens 
                WHERE user_id = ? AND token_type = ? 
                ORDER BY created_at DESC
            ");
            $stmt->bind_param('is', $userId, $type);
        } else {
            $stmt = $this->db->prepare("
                SELECT * FROM tokens 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->bind_param('i', $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            // Parse stored JSON data
            if ($row['data']) {
                $row['data'] = json_decode($row['data'], true);
            } else {
                $row['data'] = [];
            }
            
            $tokens[] = $row;
        }
        
        return $tokens;
    }
    
    /**
     * Delete expired or consumed tokens
     * 
     * @param int $olderThan Delete tokens older than this many seconds
     * @return int Number of deleted tokens
     */
    public function purgeOldTokens($olderThan = 86400) {
        // Delete tokens that are expired or consumed and older than the specified time
        $expiryDate = date('Y-m-d H:i:s', time() - $olderThan);
        
        $stmt = $this->db->prepare("
            DELETE FROM tokens 
            WHERE (expires_at < NOW() OR consumed_at IS NOT NULL) 
            AND created_at < ?
        ");
        
        $stmt->bind_param('s', $expiryDate);
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    /**
     * Revoke a specific token
     * 
     * @param string $token Token to revoke
     * @return bool Success status
     */
    public function revokeToken($token) {
        $stmt = $this->db->prepare("DELETE FROM tokens WHERE token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Revoke all tokens for a user
     * 
     * @param int $userId User ID
     * @param string $type Optional token type filter
     * @return int Number of revoked tokens
     */
    public function revokeUserTokens($userId, $type = null) {
        if ($type) {
            $stmt = $this->db->prepare("DELETE FROM tokens WHERE user_id = ? AND token_type = ?");
            $stmt->bind_param('is', $userId, $type);
        } else {
            $stmt = $this->db->prepare("DELETE FROM tokens WHERE user_id = ?");
            $stmt->bind_param('i', $userId);
        }
        
        $stmt->execute();
        
        return $stmt->affected_rows;
    }
    
    /**
     * Create secure payment recovery token
     * 
     * @param int $transactionId Transaction ID
     * @param float $amount Transaction amount
     * @param int $customerId Customer ID
     * @param int $expiry Token expiration time (seconds)
     * @return string Recovery token
     */
    public function createRecoveryToken($transactionId, $amount, $customerId, $expiry = 259200) { // 3 days default
        return $this->createToken('recovery', $customerId, [
            'transaction_id' => $transactionId,
            'amount' => $amount
        ], $expiry);
    }
    
    /**
     * Verify payment recovery token
     * 
     * @param string $token Recovery token
     * @param int $transactionId Transaction ID to validate against
     * @return array|false Token data or false if invalid
     */
    public function verifyRecoveryToken($token, $transactionId) {
        $tokenData = $this->verifyToken($token, 'recovery');
        
        if (!$tokenData) {
            return false;
        }
        
        // Verify transaction ID
        if ($tokenData['data']['transaction_id'] != $transactionId) {
            return false;
        }
        
        return $tokenData;
    }
    
    /**
     * Create database tables for token management
     */
    public static function createTables() {
        $db = getDbConnection();
        
        // Create tokens table
        $sql = "CREATE TABLE IF NOT EXISTS `tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `token` varchar(255) NOT NULL,
            `token_type` varchar(50) NOT NULL,
            `user_id` int(11) DEFAULT NULL,
            `data` text DEFAULT NULL,
            `expires_at` datetime NOT NULL,
            `created_at` datetime NOT NULL,
            `consumed_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token` (`token`),
            KEY `token_type` (`token_type`),
            KEY `user_id` (`user_id`),
            KEY `expires_at` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
    }
}