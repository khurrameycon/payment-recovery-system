<?php
class Transaction {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Get all failed transactions
     */
    public function getFailedTransactions() {
        $sql = "SELECT ft.*, c.email, c.phone 
                FROM failed_transactions ft
                JOIN customers c ON ft.customer_id = c.id
                ORDER BY ft.transaction_date DESC";
        
        $result = $this->db->query($sql);
        $transactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Save a failed transaction
     */
    public function saveFailedTransaction($customerId, $reference, $amount, $reason, $transactionDate) {
        // Check if transaction already exists
        $stmt = $this->db->prepare("SELECT id FROM failed_transactions WHERE transaction_reference = ?");
        $stmt->bind_param("s", $reference);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Transaction already exists
            return false;
        }
        
        // Insert new transaction
        $stmt = $this->db->prepare("INSERT INTO failed_transactions 
                                    (customer_id, transaction_reference, amount, failure_reason, transaction_date) 
                                    VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $customerId, $reference, $amount, $reason, $transactionDate);
        
        return $stmt->execute();
    }
    
    /**
     * Get transactions without recovery links
     */
    public function getTransactionsWithoutRecovery() {
        $sql = "SELECT ft.* 
                FROM failed_transactions ft
                LEFT JOIN payment_recovery pr ON ft.id = pr.transaction_id
                WHERE pr.id IS NULL
                ORDER BY ft.transaction_date DESC";
        
        $result = $this->db->query($sql);
        $transactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
    
    /**
     * Create recovery link for a transaction
     */
    public function createRecoveryLink($transactionId, $link, $token, $expiryDate) {
        $stmt = $this->db->prepare("INSERT INTO payment_recovery 
                                   (transaction_id, recovery_link, recovery_token, expiry_date) 
                                   VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $transactionId, $link, $token, $expiryDate);
        
        return $stmt->execute();
    }
}
?>