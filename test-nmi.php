<?php
// File: test-nmi.php
// This is a standalone test script to test NMI API integration

// Define base path
define('BASE_PATH', __DIR__);

// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Include NMI service
require_once BASE_PATH . '/app/services/NmiService.php';

// Create NMI service instance
$nmiService = new NmiService();

// Define date range for testing (adjust as needed)
$startDate = date('Y-m-d', strtotime('-7 days'));
$endDate = date('Y-m-d');

echo "Testing NMI API connection...\n";
echo "Date range: $startDate to $endDate\n\n";

// Test fetching transactions
try {
    $result = $nmiService->getFailedTransactions($startDate, $endDate);
    
    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        $transactions = $result['transactions'];
        $count = count($transactions);
        
        echo "Successfully fetched $count failed transactions.\n\n";
        
        if ($count > 0) {
            echo "First transaction details:\n";
            $firstTx = $transactions[0];
            echo "Transaction ID: " . ($firstTx['transaction_id'] ?? 'Unknown') . "\n";
            echo "Amount: $" . ($firstTx['amount'] ?? 'Unknown') . "\n";
            echo "Email: " . ($firstTx['email'] ?? 'Unknown') . "\n";
            echo "Date: " . ($firstTx['date'] ?? 'Unknown') . "\n";
            echo "Status: " . ($firstTx['status'] ?? 'Unknown') . "\n";
            echo "Reason: " . ($firstTx['reason'] ?? 'Unknown') . "\n";
        }
    }
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
}

// Test fetching a specific transaction (use a real transaction ID from your NMI account)
echo "\nTesting fetch specific transaction...\n";
$transactionId = '10508482027'; // Replace with a real transaction ID from your NMI account

try {
    $result = $nmiService->getSpecificTransaction($transactionId);
    
    if (isset($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        $transactions = $result['transactions'];
        
        if (!empty($transactions)) {
            echo "Successfully fetched specific transaction:\n";
            $tx = $transactions[0];
            echo "Transaction ID: " . ($tx['transaction_id'] ?? 'Unknown') . "\n";
            echo "Amount: $" . ($tx['amount'] ?? 'Unknown') . "\n";
            echo "Email: " . ($tx['email'] ?? 'Unknown') . "\n";
            echo "Date: " . ($tx['date'] ?? 'Unknown') . "\n";
            echo "Status: " . ($tx['status'] ?? 'Unknown') . "\n";
            echo "Reason: " . ($tx['reason'] ?? 'Unknown') . "\n";
        } else {
            echo "No transaction found with ID: $transactionId\n";
        }
    }
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
}

echo "\nNMI API tests completed.\n";
?>