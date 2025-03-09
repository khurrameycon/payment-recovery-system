<?php
class NmiService {
    private $apiUrl;
    private $apiKey;
    
    public function __construct() {
        $this->apiUrl = NMI_API_URL;
        $this->apiKey = NMI_API_KEY;
    }
    
    /**
     * Fetch failed transactions from NMI
     */
    public function getFailedTransactions($startDate, $endDate) {
        // Prepare API request
        $postData = [
            'security_key' => $this->apiKey,
            'condition' => 'status=declined',
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        
        // Make API request using cURL
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            // Handle error
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        
        // Parse and return response
        // This is a simplified example - actual NMI response would need proper parsing
        return $this->parseResponse($response);
    }
    
    /**
     * Parse NMI API response
     */
    private function parseResponse($response) {
        // In a real implementation, this would properly parse the XML or JSON response
        // For now, we'll return a dummy structure
        return [
            'transactions' => [
                [
                    'transaction_id' => 'nmi_123456',
                    'amount' => 99.99,
                    'email' => 'customer@example.com',
                    'phone' => '1234567890',
                    'date' => '2023-01-15 14:30:00',
                    'reason' => 'insufficient_funds'
                ],
                [
                    'transaction_id' => 'nmi_123457',
                    'amount' => 149.99,
                    'email' => 'another@example.com',
                    'phone' => '9876543210',
                    'date' => '2023-01-15 15:45:00',
                    'reason' => 'card_declined'
                ]
            ]
        ];
    }
}
?>