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

    /**
 * Get transaction by ID
 */
    public function getTransactionById($id) {
        $stmt = $this->db->prepare("SELECT ft.*, c.email, c.phone 
                                FROM failed_transactions ft
                                JOIN customers c ON ft.customer_id = c.id
                                WHERE ft.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    /**
 * Get recovery link for a transaction
 */
    public function getRecoveryLink($transactionId) {
        $stmt = $this->db->prepare("SELECT * FROM payment_recovery WHERE transaction_id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    /**
 * Get communication history for a transaction
 */
    public function getCommunicationHistory($transactionId) {
        $stmt = $this->db->prepare("SELECT * FROM communication_attempts WHERE transaction_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }


    public function determineCustomerSegment($customerId) {
        // Get average transaction amount for this customer
        $stmt = $this->db->prepare("
            SELECT AVG(amount) as avg_amount, COUNT(*) as transaction_count 
            FROM failed_transactions 
            WHERE customer_id = ?
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $avgAmount = $data['avg_amount'] ?: 0;
        $count = $data['transaction_count'] ?: 0;
        
        // Determine segment based on transaction history
        if ($avgAmount > 500) {
            $segment = 'premium';
        } else if ($avgAmount > 100) {
            $segment = 'standard';
        } else {
            $segment = 'basic';
        }
        
        // If they have many transactions, upgrade segment
        if ($count > 5) {
            if ($segment == 'basic') $segment = 'standard';
            else if ($segment == 'standard') $segment = 'premium';
        }
        
        // Update customer segment
        $this->db->prepare("UPDATE customers SET segment = ? WHERE id = ?")->execute([$segment, $customerId]);
        
        return $segment;
    }
}
?>