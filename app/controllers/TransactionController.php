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
        // Set default date range to last 7 days
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-7 days'));
        
        // Allow custom date range from POST
        if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
        }
        
        // Fetch failed transactions from NMI
        $result = $this->nmiService->getFailedTransactions($startDate, $endDate);
        
        if (isset($result['error'])) {
            // Handle error
            $error = $result['error'];
            include BASE_PATH . '/app/views/error.php';
            return;
        }
        
        // Process and save transactions
        $savedCount = 0;
        foreach ($result['transactions'] as $transaction) {
            // Find or create customer
            $customerId = $this->customerModel->findOrCreate(
                $transaction['email'], 
                $transaction['phone'] ?? null
            );
            
            // Save transaction
            if ($this->transactionModel->saveFailedTransaction(
                $customerId,
                $transaction['transaction_id'],
                $transaction['amount'],
                $transaction['reason'],
                $transaction['date']
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
}
?>