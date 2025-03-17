<?php
// File: app/services/SegmentationEngine.php

class SegmentationEngine {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Analyze and segment a customer based on their transaction history and behavior
     * 
     * @param int $customerId The customer ID to analyze
     * @return array Segmentation data and insights
     */
    public function analyzeCustomer($customerId) {
        // Get customer transaction history
        $transactions = $this->getCustomerTransactions($customerId);
        
        if (empty($transactions)) {
            return $this->createDefaultSegment($customerId);
        }
        
        // Calculate key metrics
        $metrics = $this->calculateCustomerMetrics($transactions);
        
        // Determine value segment based on average transaction amount
        $valueSegment = $this->determineValueSegment($metrics['avg_amount']);
        
        // Determine loyalty segment based on transaction count and history
        $loyaltySegment = $this->determineLoyaltySegment($metrics['total_count'], $metrics['first_transaction_date']);
        
        // Determine recovery behavior segment based on past responses
        $behaviorSegment = $this->determineBehaviorSegment($customerId);
        
        // Combine segments into a customer profile
        $segmentProfile = [
            'customer_id' => $customerId,
            'value_segment' => $valueSegment,
            'loyalty_segment' => $loyaltySegment,
            'behavior_segment' => $behaviorSegment,
            'combined_segment' => $this->determineCombinedSegment($valueSegment, $loyaltySegment, $behaviorSegment),
            'metrics' => $metrics,
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        // Store segmentation in database
        $this->saveSegmentation($customerId, $segmentProfile);
        
        return $segmentProfile;
    }
    
    /**
     * Get customer's transaction history
     */
    private function getCustomerTransactions($customerId) {
        $sql = "SELECT * FROM failed_transactions 
                WHERE customer_id = ? 
                ORDER BY transaction_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Calculate key customer metrics from transaction history
     */
    private function calculateCustomerMetrics($transactions) {
        $totalAmount = 0;
        $recoveredAmount = 0;
        $recoveredCount = 0;
        $firstDate = null;
        $lastDate = null;
        
        foreach ($transactions as $transaction) {
            $totalAmount += $transaction['amount'];
            
            if ($transaction['recovery_status'] == 'recovered') {
                $recoveredCount++;
                // Assuming there's a field for the recovered amount
                $recoveredAmount += $transaction['amount']; 
            }
            
            $transactionDate = strtotime($transaction['transaction_date']);
            
            if ($firstDate === null || $transactionDate < $firstDate) {
                $firstDate = $transactionDate;
            }
            
            if ($lastDate === null || $transactionDate > $lastDate) {
                $lastDate = $transactionDate;
            }
        }
        
        $count = count($transactions);
        
        return [
            'total_count' => $count,
            'total_amount' => $totalAmount,
            'avg_amount' => $count > 0 ? $totalAmount / $count : 0,
            'recovered_count' => $recoveredCount,
            'recovered_amount' => $recoveredAmount,
            'recovery_rate' => $count > 0 ? ($recoveredCount / $count) * 100 : 0,
            'first_transaction_date' => $firstDate ? date('Y-m-d', $firstDate) : null,
            'last_transaction_date' => $lastDate ? date('Y-m-d', $lastDate) : null,
            'days_as_customer' => $firstDate ? ceil((time() - $firstDate) / (60 * 60 * 24)) : 0
        ];
    }
    
    /**
     * Determine value segment based on average transaction amount
     */
    private function determineValueSegment($avgAmount) {
        if ($avgAmount >= 1000) {
            return 'high_value';
        } else if ($avgAmount >= 250) {
            return 'medium_value';
        } else {
            return 'low_value';
        }
    }
    
    /**
     * Determine loyalty segment based on transaction count and history
     */
    private function determineLoyaltySegment($transactionCount, $firstTransactionDate) {
        if (!$firstTransactionDate) {
            return 'new';
        }
        
        $daysSinceFirstTransaction = ceil((time() - strtotime($firstTransactionDate)) / (60 * 60 * 24));
        
        if ($transactionCount >= 10 || $daysSinceFirstTransaction > 365) {
            return 'loyal';
        } else if ($transactionCount >= 3 || $daysSinceFirstTransaction > 90) {
            return 'returning';
        } else {
            return 'new';
        }
    }
    
    /**
     * Determine behavior segment based on past responses to recovery attempts
     */
    private function determineBehaviorSegment($customerId) {
        // Get communication history
        $sql = "SELECT ca.*, ft.recovery_status 
                FROM communication_attempts ca 
                JOIN failed_transactions ft ON ca.transaction_id = ft.id 
                WHERE ft.customer_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $totalAttempts = 0;
        $openedCount = 0;
        $clickedCount = 0;
        $recoveredCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $totalAttempts++;
            
            if ($row['status'] == 'opened' || $row['status'] == 'clicked') {
                $openedCount++;
            }
            
            if ($row['status'] == 'clicked') {
                $clickedCount++;
            }
            
            if ($row['recovery_status'] == 'recovered') {
                $recoveredCount++;
            }
        }
        
        // Calculate response rates
        $openRate = $totalAttempts > 0 ? ($openedCount / $totalAttempts) * 100 : 0;
        $clickRate = $totalAttempts > 0 ? ($clickedCount / $totalAttempts) * 100 : 0;
        $recoveryRate = $totalAttempts > 0 ? ($recoveredCount / $totalAttempts) * 100 : 0;
        
        // Determine segment based on response rates
        if ($recoveryRate >= 50) {
            return 'responsive';
        } else if ($openRate >= 30 || $clickRate >= 15) {
            return 'engaged';
        } else if ($totalAttempts > 0) {
            return 'inactive';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Determine combined segment from individual segments
     */
    private function determineCombinedSegment($valueSegment, $loyaltySegment, $behaviorSegment) {
        // Priority matrix for combined segment
        $priorities = [
            // High value customers
            'high_value_loyal_responsive' => 'vip',
            'high_value_loyal_engaged' => 'high_priority',
            'high_value_loyal_inactive' => 'nurture',
            'high_value_returning_responsive' => 'high_priority',
            'high_value_returning_engaged' => 'high_priority',
            'high_value_returning_inactive' => 'nurture',
            'high_value_new_responsive' => 'high_priority',
            'high_value_new_engaged' => 'high_priority',
            'high_value_new_inactive' => 'standard',
            'high_value_new_unknown' => 'standard',
            
            // Medium value customers
            'medium_value_loyal_responsive' => 'high_priority',
            'medium_value_loyal_engaged' => 'high_priority',
            'medium_value_loyal_inactive' => 'nurture',
            'medium_value_returning_responsive' => 'standard',
            'medium_value_returning_engaged' => 'standard',
            'medium_value_returning_inactive' => 'nurture',
            'medium_value_new_responsive' => 'standard',
            'medium_value_new_engaged' => 'standard',
            'medium_value_new_inactive' => 'standard',
            'medium_value_new_unknown' => 'standard',
            
            // Low value customers
            'low_value_loyal_responsive' => 'standard',
            'low_value_loyal_engaged' => 'standard',
            'low_value_loyal_inactive' => 'low_priority',
            'low_value_returning_responsive' => 'standard',
            'low_value_returning_engaged' => 'standard',
            'low_value_returning_inactive' => 'low_priority',
            'low_value_new_responsive' => 'standard',
            'low_value_new_engaged' => 'standard',
            'low_value_new_inactive' => 'low_priority',
            'low_value_new_unknown' => 'low_priority'
        ];
        
        $key = "{$valueSegment}_{$loyaltySegment}_{$behaviorSegment}";
        
        return $priorities[$key] ?? 'standard';
    }
    
    /**
     * Save segmentation data to database
     */
    private function saveSegmentation($customerId, $segmentProfile) {
        // First, check if segmentation exists
        $checkSql = "SELECT id FROM customer_segmentation WHERE customer_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param("i", $customerId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        $valueSegment = $segmentProfile['value_segment'];
        $loyaltySegment = $segmentProfile['loyalty_segment'];
        $behaviorSegment = $segmentProfile['behavior_segment'];
        $combinedSegment = $segmentProfile['combined_segment'];
        $metrics = json_encode($segmentProfile['metrics']);
        $now = date('Y-m-d H:i:s');
        
        if ($result->num_rows > 0) {
            // Update existing segmentation
            $row = $result->fetch_assoc();
            $updateSql = "UPDATE customer_segmentation 
                          SET value_segment = ?, 
                              loyalty_segment = ?, 
                              behavior_segment = ?,
                              combined_segment = ?, 
                              metrics = ?,
                              updated_at = ?
                          WHERE id = ?";
            
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param("ssssssi", 
                $valueSegment, 
                $loyaltySegment,
                $behaviorSegment,
                $combinedSegment,
                $metrics,
                $now,
                $row['id']
            );
            $updateStmt->execute();
        } else {
            // Insert new segmentation
            $insertSql = "INSERT INTO customer_segmentation 
                          (customer_id, value_segment, loyalty_segment, behavior_segment, 
                           combined_segment, metrics, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->bind_param("isssssss", 
                $customerId,
                $valueSegment, 
                $loyaltySegment,
                $behaviorSegment,
                $combinedSegment,
                $metrics,
                $now,
                $now
            );
            $insertStmt->execute();
        }
        
        // Update the customer table's segment field with the combined segment
        $this->updateCustomerSegment($customerId, $combinedSegment);
    }
    
    /**
     * Update customer's segment in the customers table
     */
    private function updateCustomerSegment($customerId, $segment) {
        $sql = "UPDATE customers SET segment = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $segment, $customerId);
        $stmt->execute();
    }
    
    /**
     * Create default segment for customers with no transaction history
     */
    private function createDefaultSegment($customerId) {
        $segmentProfile = [
            'customer_id' => $customerId,
            'value_segment' => 'unknown',
            'loyalty_segment' => 'new',
            'behavior_segment' => 'unknown',
            'combined_segment' => 'standard',
            'metrics' => [
                'total_count' => 0,
                'total_amount' => 0,
                'avg_amount' => 0,
                'recovered_count' => 0,
                'recovered_amount' => 0,
                'recovery_rate' => 0,
                'first_transaction_date' => null,
                'last_transaction_date' => null,
                'days_as_customer' => 0
            ],
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        $this->saveSegmentation($customerId, $segmentProfile);
        
        return $segmentProfile;
    }
    
    /**
     * Re-analyze and segment all customers
     */
    public function reanalyzeAllCustomers() {
        $sql = "SELECT id FROM customers";
        $result = $this->db->query($sql);
        
        $updatedCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $this->analyzeCustomer($row['id']);
            $updatedCount++;
        }
        
        return $updatedCount;
    }
    
    /**
     * Get recommended communication strategies based on segment
     */
    public function getSegmentStrategies($segment) {
        $strategies = [
            'vip' => [
                'channels' => ['email', 'sms', 'whatsapp'],
                'timing' => 'priority',
                'max_attempts' => 5,
                'tone' => 'personalized',
                'offer' => 'special_support'
            ],
            'high_priority' => [
                'channels' => ['email', 'sms'],
                'timing' => 'priority',
                'max_attempts' => 4,
                'tone' => 'personalized',
                'offer' => 'expedited'
            ],
            'standard' => [
                'channels' => ['email'],
                'timing' => 'standard',
                'max_attempts' => 3,
                'tone' => 'professional',
                'offer' => 'standard'
            ],
            'nurture' => [
                'channels' => ['email'],
                'timing' => 'extended',
                'max_attempts' => 4,
                'tone' => 'educational',
                'offer' => 'assistance'
            ],
            'low_priority' => [
                'channels' => ['email'],
                'timing' => 'standard',
                'max_attempts' => 2,
                'tone' => 'simple',
                'offer' => 'basic'
            ]
        ];
        
        return $strategies[$segment] ?? $strategies['standard'];
    }
}