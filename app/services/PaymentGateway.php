// Create app/services/PaymentGateway.php
<?php
interface PaymentGatewayInterface {
    public function processPayment($amount, $cardData, $transactionReference);
    public function getTransactionStatus($transactionId);
    public function getFailedTransactions($startDate, $endDate);
}

class NmiGateway implements PaymentGatewayInterface {
    private $apiUrl;
    private $apiKey;
    
    public function __construct() {
        $this->apiUrl = NMI_API_URL;
        $this->apiKey = NMI_API_KEY;
    }
    
    public function processPayment($amount, $cardData, $transactionReference) {
        // Implementation for NMI
    }
    
    public function getTransactionStatus($transactionId) {
        // Implementation for NMI
    }
    
    public function getFailedTransactions($startDate, $endDate) {
        // Implementation for NMI
    }
}

class StripeGateway implements PaymentGatewayInterface {
    private $apiKey;
    
    public function __construct() {
        $this->apiKey = STRIPE_API_KEY;
    }
    
    public function processPayment($amount, $cardData, $transactionReference) {
        // Implementation for Stripe
    }
    
    public function getTransactionStatus($transactionId) {
        // Implementation for Stripe
    }
    
    public function getFailedTransactions($startDate, $endDate) {
        // Implementation for Stripe
    }
}

class PaymentGatewayFactory {
    public static function create($gateway) {
        switch($gateway) {
            case 'nmi':
                return new NmiGateway();
            case 'stripe':
                return new StripeGateway();
            default:
                throw new Exception("Unsupported payment gateway: {$gateway}");
        }
    }
}
?>