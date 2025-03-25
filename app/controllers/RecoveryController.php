<?php
class RecoveryController {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    public function showPaymentPage() {
        // Get token from request
        $token = $_GET['token'] ?? '';
        $trackingId = $_GET['track'] ?? '';
        
        if (!$token) {
            include BASE_PATH . '/app/views/error.php';
            return;
        }
        
        // Track click if tracking ID is present
        if ($trackingId) {
            $stmt = $this->db->prepare("UPDATE communication_attempts SET status = 'clicked', clicked_at = NOW() WHERE tracking_id = ?");
            $stmt->bind_param("s", $trackingId);
            $stmt->execute();
        }
        
        // Get recovery info
        $stmt = $this->db->prepare("
            SELECT pr.*, ft.amount, ft.transaction_reference, c.email, c.phone 
            FROM payment_recovery pr
            JOIN failed_transactions ft ON pr.transaction_id = ft.id
            JOIN customers c ON ft.customer_id = c.id
            WHERE pr.recovery_token = ? AND pr.status = 'active' AND pr.expiry_date > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "This payment link is invalid or has expired.";
            include BASE_PATH . '/app/views/error.php';
            return;
        }
        
        $recovery = $result->fetch_assoc();
        
        // Show payment form
        include BASE_PATH . '/app/views/recovery_form.php';
    }
    
    

    // Add to app/controllers/RecoveryController.php
    public function processPayment() {
        // Get data from POST
        $token = $_POST['token'] ?? '';
        $cardNumber = $_POST['card_number'] ?? '';
        $expiryMonth = $_POST['expiry_month'] ?? '';
        $expiryYear = $_POST['expiry_year'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        
        // Validate token
        $stmt = $this->db->prepare("
            SELECT pr.*, ft.amount, ft.transaction_reference, ft.customer_id
            FROM payment_recovery pr
            JOIN failed_transactions ft ON pr.transaction_id = ft.id
            WHERE pr.recovery_token = ? AND pr.status = 'active' AND pr.expiry_date > NOW()
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "This payment link is invalid or has expired.";
            include BASE_PATH . '/app/views/error.php';
            return;
        }
        
        $recovery = $result->fetch_assoc();
        
        // Basic validation of card details
        if (empty($cardNumber) || empty($expiryMonth) || empty($expiryYear) || empty($cvv)) {
            $error = "All card details are required.";
            include BASE_PATH . '/app/views/recovery_form.php';
            return;
        }
        
        try {
            // Process payment through NMI
            $paymentSuccess = $this->processPaymentThroughNmi(
                $cardNumber, 
                $expiryMonth, 
                $expiryYear, 
                $cvv, 
                $recovery['amount'],
                $recovery['transaction_reference']
            );
            
            if ($paymentSuccess) {
                // Update recovery status
                $stmt = $this->db->prepare("
                    UPDATE payment_recovery 
                    SET status = 'completed', recovered_amount = ?, recovery_date = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("di", $recovery['amount'], $recovery['id']);
                $stmt->execute();
                
                // Update transaction status
                $stmt = $this->db->prepare("
                    UPDATE failed_transactions 
                    SET recovery_status = 'recovered' 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $recovery['transaction_id']);
                $stmt->execute();
                
                // Log this successful recovery
                $this->logSuccessfulRecovery($recovery['transaction_id'], $recovery['customer_id']);
                
                // Show success page
                include BASE_PATH . '/app/views/recovery_success.php';
            } else {
                // Payment failed
                $error = "Unable to process payment. Please check your card details and try again.";
                include BASE_PATH . '/app/views/recovery_form.php';
            }
        } catch (Exception $e) {
            // Log error
            error_log("Payment processing error: " . $e->getMessage());
            
            // Show error
            $error = "An error occurred while processing your payment. Please try again later.";
            include BASE_PATH . '/app/views/recovery_form.php';
        }
    }
    
    private function processPaymentThroughNmi($cardNumber, $expiryMonth, $expiryYear, $cvv, $amount, $originalTransactionId) {
        // For now we'll simulate success for valid test cards
        // In production, this should make an actual API call to NMI
        
        // Format card number by removing spaces
        $cardNumber = str_replace(' ', '', $cardNumber);
        
        // Sample validation - valid if it's a known test card
        $validTestCards = [
            '4111111111111111', // Visa test card
            '5431111111111111', // MasterCard test card
            '371111111111114',  // Amex test card
            '4242424242424242', // Another common test card
            '6011111111111117'  // Discover test card
        ];
        
        $cardValid = in_array($cardNumber, $validTestCards);
        
        // Log attempted payment
        error_log("Payment attempt: Card: " . substr($cardNumber, 0, 4) . "XXXXXXXXXXXX" . 
                  ", Amount: $amount, Result: " . ($cardValid ? "Success" : "Failed"));
        
        return $cardValid;
    }
    
    private function logSuccessfulRecovery($transactionId, $customerId) {
        // Get the channel that was used successfully for recovery
        $sql = "
            SELECT channel 
            FROM communication_attempts 
            WHERE transaction_id = ? AND status = 'clicked'
            ORDER BY clicked_at DESC 
            LIMIT 1
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $channel = 'direct';
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $channel = $row['channel'];
        }
        
        // Log recovery 
        $sql = "
            INSERT INTO recovery_analytics 
            (transaction_id, customer_id, recovery_date, channel) 
            VALUES (?, ?, NOW(), ?)
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iis", $transactionId, $customerId, $channel);
        $stmt->execute();
    }
}
?>