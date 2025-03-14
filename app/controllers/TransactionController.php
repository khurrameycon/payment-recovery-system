<?php
require_once BASE_PATH . '/app/services/NmiService.php';
require_once BASE_PATH . '/app/models/Customer.php';
require_once BASE_PATH . '/app/models/Transaction.php';

class TransactionController {
    private $nmiService;
    private $customerModel;
    private $transactionModel;
    
    public function __construct() {
        $this->nmiService = new NmiService();
        $this->customerModel = new Customer();
        $this->transactionModel = new Transaction();
    }
    
    public function listFailed() {
        // Get transactions from database
        $transactions = $this->transactionModel->getFailedTransactions();
        
        // Load view
        include BASE_PATH . '/app/views/failed-transactions.php';
    }
    
    public function fetchFromNmi() {
        // Fetch failed transactions from NMI without date constraints
        $result = $this->nmiService->getFailedTransactions();
        
        if (isset($result['error'])) {
            // Handle error
            $error = $result['error'];
            include BASE_PATH . '/app/views/error.php';
            return;
        }
        
        // Process and save transactions
        $savedCount = 0;
        foreach ($result['transactions'] as $transaction) {
            // Skip transactions with no email
            if (empty($transaction['email'])) {
                error_log("Skipping transaction {$transaction['transaction_id']} due to missing email");
                continue;
            }
            
            // Find or create customer
            $customerId = $this->customerModel->findOrCreate(
                $transaction['email'], 
                $transaction['phone'] ?? null
            );
            
            // Use a default date if missing
            $date = !empty($transaction['date']) ? $transaction['date'] : date('Y-m-d H:i:s');
            
            // Save transaction
            if ($this->transactionModel->saveFailedTransaction(
                $customerId,
                $transaction['transaction_id'],
                $transaction['amount'],
                $transaction['reason'] ?: 'Unknown',
                $date
            )) {
                $savedCount++;
            }
        }
        
        // Set success message
        $_SESSION['message'] = "Successfully imported {$savedCount} failed transactions.";
        
        // Redirect to transaction list
        header('Location: index.php?route=failed-transactions');
        exit;
    }
    
    public function createRecoveryLinks() {
        // Get transactions without recovery links
        $transactions = $this->transactionModel->getTransactionsWithoutRecovery();
        
        $createdCount = 0;
        foreach ($transactions as $transaction) {
            // Generate unique token
            $token = bin2hex(random_bytes(16));
            
            // Create recovery link
            $link = 'http://localhost/payment-recovery/public/index.php?route=recover&token=' . $token;
            
            // Set expiry date (3 days from now)
            $expiryDate = date('Y-m-d H:i:s', strtotime('+3 days'));
            
            // Save recovery link
            if ($this->transactionModel->createRecoveryLink($transaction['id'], $link, $token, $expiryDate)) {
                $createdCount++;
            }
        }
        
        // Set success message
        $_SESSION['message'] = "Created {$createdCount} recovery links.";
        
        // Redirect to transaction list
        header('Location: index.php?route=failed-transactions');
        exit;
    }

    public function viewTransaction() {
        // Get transaction ID from request
        $transactionId = $_GET['id'] ?? 0;
        
        if (!$transactionId) {
            $_SESSION['error'] = "No transaction specified";
            header('Location: index.php?route=failed-transactions');
            exit;
        }
        
        // Get transaction details
        $transaction = $this->transactionModel->getTransactionById($transactionId);
        
        if (!$transaction) {
            $_SESSION['error'] = "Transaction not found";
            header('Location: index.php?route=failed-transactions');
            exit;
        }
        
        // Get customer details
        $customer = $this->customerModel->getById($transaction['customer_id']);
        
        // Get recovery links
        $recoveryLink = $this->transactionModel->getRecoveryLink($transactionId);
        
        // Get communication history
        $communications = $this->transactionModel->getCommunicationHistory($transactionId);
        
        // Load view
        include BASE_PATH . '/app/views/view_transaction.php';
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

    public function testSpecificTransaction() {
        $transactionId = '10508482027'; // Use a real failed transaction ID from your NMI dashboard
        $result = $this->nmiService->getSpecificTransaction($transactionId);
        
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }


    public function testTransaction() {
        // Test with a known failed transaction ID from your screenshot
        $transactionId = '10508510583'; // One of your failed transactions
        
        $result = $this->nmiService->getTransactionById($transactionId);
        
        echo "<h1>Test Transaction Fetch</h1>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        echo "<h1>Get All Failed Transactions</h1>";
        // Try with a date range
        $startDate = '2025-03-14';
        $endDate = '2025-03-15';
        $allResults = $this->nmiService->getFailedTransactions($startDate, $endDate);
        echo "<pre>";
        print_r($allResults);
        echo "</pre>";
    }
}
?>