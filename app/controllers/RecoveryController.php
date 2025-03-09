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
    
    public function processPayment() {
        // Get data from POST
        $token = $_POST['token'] ?? '';
        $cardNumber = $_POST['card_number'] ?? '';
        $expiryMonth = $_POST['expiry_month'] ?? '';
        $expiryYear = $_POST['expiry_year'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        
        // Validate token
        $stmt = $this->db->prepare("
            SELECT pr.*, ft.amount, ft.transaction_reference 
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
        
        // In a real implementation, you would process the payment through NMI
        // For now, we'll simulate a successful payment
        
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
        
        // Show success page
        include BASE_PATH . '/app/views/recovery_success.php';
    }
}
?>