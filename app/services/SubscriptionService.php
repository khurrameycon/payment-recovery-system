<?php
// File: app/services/SubscriptionService.php

class SubscriptionService {
    private $db;
    private $apiUrl;
    private $apiKey;
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->apiUrl = PAYMENT_API_URL ?? '';
        $this->apiKey = PAYMENT_API_KEY ?? '';
    }
    
    /**
     * Get subscription plans
     * 
     * @return array Available subscription plans
     */
    public function getPlans() {
        return [
            'standard' => [
                'name' => 'Standard',
                'price' => 49,
                'price_yearly' => 490,
                'description' => 'For small businesses',
                'features' => [
                    'Up to 1,000 recovery attempts per month',
                    'Email channel',
                    'SMS channel',
                    'Basic white-labeling',
                    'Up to 5 users',
                    'Email support'
                ],
                'limits' => [
                    'recovery_attempts' => 1000,
                    'users' => 5,
                    'channels' => ['email', 'sms'],
                    'whitelabel' => false,
                    'custom_domain' => false,
                    'api_access' => false
                ]
            ],
            'premium' => [
                'name' => 'Premium',
                'price' => 149,
                'price_yearly' => 1490,
                'description' => 'For growing businesses',
                'features' => [
                    'Up to 5,000 recovery attempts per month',
                    'Email channel',
                    'SMS channel',
                    'WhatsApp channel',
                    'Advanced white-labeling',
                    'Custom domain',
                    'Up to 15 users',
                    'API access',
                    'Priority email support',
                    'Phone support'
                ],
                'limits' => [
                    'recovery_attempts' => 5000,
                    'users' => 15,
                    'channels' => ['email', 'sms', 'whatsapp'],
                    'whitelabel' => true,
                    'custom_domain' => true,
                    'api_access' => true
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 499,
                'price_yearly' => 4990,
                'description' => 'For large businesses',
                'features' => [
                    'Unlimited recovery attempts',
                    'All channels',
                    'Complete white-labeling',
                    'Multiple custom domains',
                    'Unlimited users',
                    'Advanced API access',
                    'Dedicated support',
                    'Custom integration'
                ],
                'limits' => [
                    'recovery_attempts' => 999999,
                    'users' => 999999,
                    'channels' => ['email', 'sms', 'whatsapp', 'custom'],
                    'whitelabel' => true,
                    'custom_domain' => true,
                    'api_access' => true
                ]
            ]
        ];
    }
    
    /**
     * Get organization's current subscription
     * 
     * @param int $organizationId Organization ID
     * @return array Subscription data
     */
    public function getSubscription($organizationId) {
        $stmt = $this->db->prepare("
            SELECT ob.*, o.plan
            FROM organization_billing ob
            JOIN organizations o ON ob.organization_id = o.id
            WHERE ob.organization_id = ?
            ORDER BY ob.id DESC
            LIMIT 1
        ");
        
        $stmt->bind_param('i', $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // No subscription record found, get organization plan
            $stmt = $this->db->prepare("SELECT plan FROM organizations WHERE id = ?");
            $stmt->bind_param('i', $organizationId);
            $stmt->execute();
            $orgResult = $stmt->get_result();
            
            if ($orgResult->num_rows === 0) {
                return null;
            }
            
            $org = $orgResult->fetch_assoc();
            
            // Create a default subscription record
            $plan = $org['plan'];
            $plans = $this->getPlans();
            
            $nextBillingDate = date('Y-m-d', strtotime('+30 days'));
            
            return [
                'organization_id' => $organizationId,
                'plan' => $plan,
                'status' => 'active',
                'next_billing_date' => $nextBillingDate,
                'last_billing_date' => date('Y-m-d'),
                'payment_method' => null,
                'payment_details' => null,
                'subscription_id' => null,
                'price' => $plans[$plan]['price'] ?? 0,
                'billing_period' => 'monthly',
                'trial_ends_at' => null,
                'features' => $plans[$plan]['features'] ?? [],
                'limits' => $plans[$plan]['limits'] ?? []
            ];
        }
        
        $subscription = $result->fetch_assoc();
        $plan = $subscription['plan'];
        $plans = $this->getPlans();
        
        // Add plan details
        $subscription['features'] = $plans[$plan]['features'] ?? [];
        $subscription['limits'] = $plans[$plan]['limits'] ?? [];
        $subscription['price'] = $plans[$plan]['price'] ?? 0;
        
        // Default to monthly billing
        $subscription['billing_period'] = 'monthly';
        
        return $subscription;
    }
    
    /**
     * Check if organization is within subscription limits
     * 
     * @param int $organizationId Organization ID
     * @param string $limitKey Limit key to check
     * @return bool True if within limits
     */
    public function checkLimit($organizationId, $limitKey) {
        $subscription = $this->getSubscription($organizationId);
        
        if (!$subscription) {
            return false;
        }
        
        // Check for specific limits
        switch ($limitKey) {
            case 'users':
                $userCount = $this->countOrganizationUsers($organizationId);
                return $userCount < $subscription['limits']['users'];
                
            case 'recovery_attempts':
                $currentMonth = date('Y-m');
                $attemptCount = $this->countMonthlyAttempts($organizationId, $currentMonth);
                return $attemptCount < $subscription['limits']['recovery_attempts'];
                
            case 'channel':
                $channel = func_get_arg(2);
                return in_array($channel, $subscription['limits']['channels']);
                
            case 'whitelabel':
                return $subscription['limits']['whitelabel'];
                
            case 'custom_domain':
                return $subscription['limits']['custom_domain'];
                
            case 'api_access':
                return $subscription['limits']['api_access'];
                
            default:
                return false;
        }
    }
    
    /**
     * Count organization users
     * 
     * @param int $organizationId Organization ID
     * @return int User count
     */
    private function countOrganizationUsers($organizationId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM users 
            WHERE organization_id = ?
        ");
        
        $stmt->bind_param('i', $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['count'];
    }
    
    /**
     * Count monthly recovery attempts
     * 
     * @param int $organizationId Organization ID
     * @param string $yearMonth Year and month (YYYY-MM)
     * @return int Attempt count
     */
    private function countMonthlyAttempts($organizationId, $yearMonth) {
        // Check if we have usage record for this month
        $stmt = $this->db->prepare("
            SELECT messages_sent 
            FROM organization_usage 
            WHERE organization_id = ? AND year_month = ?
        ");
        
        $stmt->bind_param('is', $organizationId, $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['messages_sent'];
        }
        
        // If no usage record, count from communication attempts
        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM communication_attempts 
            WHERE organization_id = ? AND scheduled_at BETWEEN ? AND ?
        ");
        
        $stmt->bind_param('iss', $organizationId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return (int)$row['count'];
    }
    
    /**
     * Change organization subscription plan
     * 
     * @param int $organizationId Organization ID
     * @param string $newPlan New plan
     * @param string $billingPeriod Billing period (monthly/yearly)
     * @return bool Success status
     */
    public function changePlan($organizationId, $newPlan, $billingPeriod = 'monthly') {
        // Validate plan
        $plans = $this->getPlans();
        
        if (!isset($plans[$newPlan])) {
            return false;
        }
        
        try {
            // Begin transaction
            $this->db->begin_transaction();
            
            // Update organization plan
            $stmt = $this->db->prepare("
                UPDATE organizations 
                SET plan = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->bind_param('si', $newPlan, $organizationId);
            $stmt->execute();
            
            // Get price based on billing period
            $price = ($billingPeriod === 'yearly') 
                ? $plans[$newPlan]['price_yearly']
                : $plans[$newPlan]['price'];
            
            // Calculate next billing date
            $nextBillingDate = ($billingPeriod === 'yearly')
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+1 month'));
            
            // Update or create billing record
            $stmt = $this->db->prepare("
                INSERT INTO organization_billing 
                (organization_id, plan, status, next_billing_date, last_billing_date, created_at, updated_at) 
                VALUES (?, ?, 'active', ?, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                plan = ?, status = 'active', next_billing_date = ?, updated_at = NOW()
            ");
            
            $stmt->bind_param('issss', $organizationId, $newPlan, $nextBillingDate, $newPlan, $nextBillingDate);
            $stmt->execute();
            
            // Commit transaction
            $this->db->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            error_log("Error changing plan: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process subscription payment
     * 
     * @param int $organizationId Organization ID
     * @param array $paymentData Payment data
     * @return array Result data
     */
    public function processPayment($organizationId, $paymentData) {
        $subscription = $this->getSubscription($organizationId);
        
        if (!$subscription) {
            return [
                'success' => false,
                'message' => 'Subscription not found'
            ];
        }
        
        try {
            // In a real implementation, this would call a payment gateway API
            // For now, we'll simulate a successful payment
            
            // Generate a fake transaction ID
            $transactionId = 'tr_' . uniqid();
            
            // Update subscription payment details
            $paymentMethod = $paymentData['payment_method'];
            $paymentDetails = json_encode([
                'last4' => substr($paymentData['card_number'], -4),
                'exp_month' => $paymentData['exp_month'],
                'exp_year' => $paymentData['exp_year'],
                'brand' => $this->getCardBrand($paymentData['card_number'])
            ]);
            
            $stmt = $this->db->prepare("
                UPDATE organization_billing 
                SET payment_method = ?, payment_details = ?, updated_at = NOW() 
                WHERE organization_id = ?
            ");
            
            $stmt->bind_param('ssi', $paymentMethod, $paymentDetails, $organizationId);
            $stmt->execute();
            
            // Log payment
            $this->logPayment($organizationId, $subscription['plan'], $subscription['price'], $transactionId);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction_id' => $transactionId
            ];
        } catch (Exception $e) {
            error_log("Payment processing error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get credit card brand from number
     * 
     * @param string $cardNumber Card number
     * @return string Card brand
     */
    private function getCardBrand($cardNumber) {
        $cardNumber = preg_replace('/[^\d]/', '', $cardNumber);
        
        if (preg_match('/^4/', $cardNumber)) {
            return 'Visa';
        } else if (preg_match('/^5[1-5]/', $cardNumber)) {
            return 'Mastercard';
        } else if (preg_match('/^3[47]/', $cardNumber)) {
            return 'American Express';
        } else if (preg_match('/^6(?:011|5)/', $cardNumber)) {
            return 'Discover';
        } else {
            return 'Unknown';
        }
    }
    
    /**
     * Log subscription payment
     * 
     * @param int $organizationId Organization ID
     * @param string $plan Subscription plan
     * @param float $amount Payment amount
     * @param string $transactionId Transaction ID
     * @return bool Success status
     */
    private function logPayment($organizationId, $plan, $amount, $transactionId) {
        $stmt = $this->db->prepare("
            INSERT INTO subscription_payments 
            (organization_id, plan, amount, transaction_id, payment_date) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('isds', $organizationId, $plan, $amount, $transactionId);
        
        return $stmt->execute();
    }
    
    /**
     * Cancel subscription
     * 
     * @param int $organizationId Organization ID
     * @return bool Success status
     */
    public function cancelSubscription($organizationId) {
        try {
            // Update subscription status
            $stmt = $this->db->prepare("
                UPDATE organization_billing 
                SET status = 'canceled', updated_at = NOW() 
                WHERE organization_id = ?
            ");
            
            $stmt->bind_param('i', $organizationId);
            $result = $stmt->execute();
            
            if ($result) {
                // Downgrade organization to free plan
                $stmt = $this->db->prepare("
                    UPDATE organizations 
                    SET plan = 'free', updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $stmt->bind_param('i', $organizationId);
                $stmt->execute();
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error canceling subscription: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create database tables for subscription management
     */
    public static function createTables() {
        $db = getDbConnection();
        
        // Create subscription payments table
        $sql = "CREATE TABLE IF NOT EXISTS `subscription_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `organization_id` int(11) NOT NULL,
            `plan` varchar(20) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `transaction_id` varchar(100) DEFAULT NULL,
            `payment_date` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `organization_id` (`organization_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // Create subscription features table
        $sql = "CREATE TABLE IF NOT EXISTS `subscription_features` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `plan` varchar(20) NOT NULL,
            `feature_key` varchar(50) NOT NULL,
            `feature_value` text NOT NULL,
            `feature_type` varchar(20) DEFAULT 'string',
            PRIMARY KEY (`id`),
            UNIQUE KEY `plan_feature` (`plan`, `feature_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // Check if organization_billing table exists
        $result = $db->query("SHOW TABLES LIKE 'organization_billing'");
        
        if ($result->num_rows === 0) {
            // Create organization_billing table
            $sql = "CREATE TABLE IF NOT EXISTS `organization_billing` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `organization_id` int(11) NOT NULL,
                `plan` varchar(20) NOT NULL DEFAULT 'standard',
                `status` varchar(20) NOT NULL DEFAULT 'active',
                `next_billing_date` date DEFAULT NULL,
                `last_billing_date` date DEFAULT NULL,
                `payment_method` varchar(50) DEFAULT NULL,
                `payment_details` text DEFAULT NULL,
                `subscription_id` varchar(100) DEFAULT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `organization_id` (`organization_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $db->query($sql);
        }
        
        // Insert default subscription features
        $plans = ['standard', 'premium', 'enterprise'];
        $features = [
            'max_users' => ['standard' => 5, 'premium' => 15, 'enterprise' => 999],
            'max_recovery_attempts' => ['standard' => 1000, 'premium' => 5000, 'enterprise' => 999999],
            'channels' => [
                'standard' => json_encode(['email', 'sms']),
                'premium' => json_encode(['email', 'sms', 'whatsapp']),
                'enterprise' => json_encode(['email', 'sms', 'whatsapp', 'custom'])
            ],
            'whitelabel' => ['standard' => 0, 'premium' => 1, 'enterprise' => 1],
            'custom_domain' => ['standard' => 0, 'premium' => 1, 'enterprise' => 1],
            'api_access' => ['standard' => 0, 'premium' => 1, 'enterprise' => 1],
            'support_level' => ['standard' => 'email', 'premium' => 'priority', 'enterprise' => 'dedicated']
        ];
        
        foreach ($plans as $plan) {
            foreach ($features as $feature => $values) {
                $value = $values[$plan];
                $type = is_string($value) && $feature === 'channels' ? 'json' : (is_numeric($value) ? 'int' : 'string');
                
                // Check if feature exists
                $stmt = $db->prepare("
                    SELECT id FROM subscription_features 
                    WHERE plan = ? AND feature_key = ?
                ");
                
                $stmt->bind_param('ss', $plan, $feature);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // Insert feature
                    $stmt = $db->prepare("
                        INSERT INTO subscription_features 
                        (plan, feature_key, feature_value, feature_type) 
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    if (is_bool($value)) {
                        $value = $value ? '1' : '0';
                    }
                    
                    $stmt->bind_param('ssss', $plan, $feature, $value, $type);
                    $stmt->execute();
                }
            }
        }
    }
    
    /**
     * Track usage metrics for billing
     * 
     * @param int $organizationId Organization ID
     * @param string $metric Metric to track
     * @param float $value Value to add
     * @return bool Success status
     */
    public function trackUsage($organizationId, $metric, $value = 1) {
        // Get current month
        $yearMonth = date('Y-m');
        
        // Check if we have a record for this month
        $stmt = $this->db->prepare("
            SELECT id FROM organization_usage 
            WHERE organization_id = ? AND year_month = ?
        ");
        
        $stmt->bind_param('is', $organizationId, $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Valid metrics
        $validMetrics = [
            'transactions_count',
            'messages_sent',
            'sms_count',
            'whatsapp_count',
            'recovered_amount',
            'recovered_count',
            'api_calls'
        ];
        
        if (!in_array($metric, $validMetrics)) {
            return false;
        }
        
        if ($result->num_rows === 0) {
            // Create new record
            $sql = "INSERT INTO organization_usage 
                   (organization_id, year_month, {$metric}) 
                   VALUES (?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('isd', $organizationId, $yearMonth, $value);
        } else {
            // Update existing record
            $row = $result->fetch_assoc();
            $sql = "UPDATE organization_usage 
                   SET {$metric} = {$metric} + ? 
                   WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('di', $value, $row['id']);
        }
        
        return $stmt->execute();
    }
    
    /**
     * Check if organization needs to be billed
     * 
     * @param int $organizationId Organization ID
     * @return bool True if billing is due
     */
    public function isBillingDue($organizationId) {
        $stmt = $this->db->prepare("
            SELECT * FROM organization_billing 
            WHERE organization_id = ? AND status = 'active'
        ");
        
        $stmt->bind_param('i', $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $billing = $result->fetch_assoc();
        
        // Check if next billing date is today or in the past
        $nextBillingDate = strtotime($billing['next_billing_date']);
        $today = strtotime(date('Y-m-d'));
        
        return $nextBillingDate <= $today;
    }
    
    /**
     * Process automatic billing for all due organizations
     * 
     * @return array Result with count of successful and failed billings
     */
    public function processBilling() {
        $stmt = $this->db->prepare("
            SELECT ob.*, o.name as organization_name, o.id as organization_id
            FROM organization_billing ob
            JOIN organizations o ON ob.organization_id = o.id
            WHERE ob.status = 'active' AND ob.next_billing_date <= CURDATE()
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $successful = 0;
        $failed = 0;
        
        while ($billing = $result->fetch_assoc()) {
            $organizationId = $billing['organization_id'];
            $plan = $billing['plan'];
            
            // Get plan details
            $plans = $this->getPlans();
            $price = $plans[$plan]['price'] ?? 0;
            
            try {
                // In a real implementation, this would charge the customer's card
                // For now, simulate a successful payment
                
                // Calculate next billing date
                $nextBillingDate = date('Y-m-d', strtotime('+1 month', strtotime($billing['next_billing_date'])));
                
                // Update billing record
                $stmt = $this->db->prepare("
                    UPDATE organization_billing 
                    SET last_billing_date = CURDATE(), next_billing_date = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                
                $stmt->bind_param('si', $nextBillingDate, $billing['id']);
                $stmt->execute();
                
                // Log payment
                $transactionId = 'tr_' . uniqid();
                $this->logPayment($organizationId, $plan, $price, $transactionId);
                
                // Log billing event
                error_log("Billed organization {$billing['organization_name']} (ID: {$organizationId}) for {$price} on plan {$plan}");
                
                $successful++;
            } catch (Exception $e) {
                error_log("Billing failed for organization {$billing['organization_name']} (ID: {$organizationId}): " . $e->getMessage());
                $failed++;
            }
        }
        
        return [
            'successful' => $successful,
            'failed' => $failed
        ];
    }
    
    /**
     * Get billing history for an organization
     * 
     * @param int $organizationId Organization ID
     * @return array Billing history
     */
    public function getBillingHistory($organizationId) {
        $stmt = $this->db->prepare("
            SELECT * FROM subscription_payments 
            WHERE organization_id = ? 
            ORDER BY payment_date DESC
        ");
        
        $stmt->bind_param('i', $organizationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
 * Get usage report for an organization
 * 
 * @param int $organizationId Organization ID
 * @param int $months Number of months to include
 * @return array Usage report
 */
// public function getUsageReport($organizationId, $months = 6) {
//     $usageData = [];
    
//     // Get current month and previous months
//     for ($i = 0; $i < $months; $i++) {
//         $yearMonth = date('Y-m', strtotime("-{$i} months"));
//         $stmt = $this->db->prepare("
//             SELECT * FROM organization_usage 
//             WHERE organization_id = ? AND year_month = ?
//         ");
        
//         $stmt->bind_param('is', $organizationId, $yearMonth);
//         $stmt->execute();
//         $result = $stmt->get_result();
        
//         if ($result->num_rows > 0) {
//             $usage = $result->fetch_assoc();
//         } else {
//             // No usage data for this month
//             $usage = [
//                 'year_month' => $yearMonth,
//                 'transactions_count' => 0,
//                 'messages_sent' => 0,
//                 'sms_count' => 0,
//                 'whatsapp_count' => 0,
//                 'recovered_amount' => 0,
//                 'recovered_count' => 0,
//                 'api_calls' => 0
//             ];
//         }
        
//         $usageData[] = $usage;
//     }
    
//     // Get subscription limits
//     $subscription = $this->getSubscription($organizationId);
//     $limits = $subscription['limits'] ?? [];
    
//     return [
//         'usage' => $usageData,
//         'limits' => $limits
//     ];
// }

/**
 * Get usage report for an organization - simplified version
 */
public function getUsageReport($organizationId, $months = 6) {
    // Create default usage data structure
    $usageData = [
        [
            'year_month' => date('Y-m'),
            'transactions_count' => 0,
            'messages_sent' => 0,
            'sms_count' => 0,
            'whatsapp_count' => 0,
            'recovered_amount' => 0,
            'recovered_count' => 0,
            'api_calls' => 0
        ]
    ];
    
    // Get subscription
    $subscription = $this->getSubscription($organizationId);
    $limits = $subscription['limits'] ?? [];
    
    return [
        'usage' => $usageData,
        'limits' => $limits
    ];
}
}