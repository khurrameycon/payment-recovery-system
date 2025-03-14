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
    public function getFailedTransactions($startDate = null, $endDate = null) {
        // Prepare API request without date constraints
        $postData = [
            'security_key' => $this->apiKey,
            'report_type' => 'transaction',
            'condition' => 'status=failed'
        ];
        
        // Make API request using cURL
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        
        // Parse and filter for failed transactions only
        $result = $this->parseXmlResponse($response);
        
        // If we get no results with condition, try without it
        if (empty($result['transactions'])) {
            $postData = [
                'security_key' => $this->apiKey,
                'report_type' => 'transaction'
            ];
            
            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                return ['error' => curl_error($ch)];
            }
            
            curl_close($ch);
            
            // Parse and filter for failed transactions only
            $result = $this->parseXmlResponse($response);
        }
        
        return $result;
    }
    
    private function parseXmlResponse($xmlResponse) {
        $transactions = [];
        
        // Create SimpleXML object from response
        try {
            $xml = new SimpleXMLElement($xmlResponse);
            
            // Check if we have transaction data
            if (isset($xml->transaction)) {
                foreach ($xml->transaction as $transaction) {
                    // Get all fields from the transaction
                    $transData = [
                        'transaction_id' => isset($transaction->transaction_id) ? (string)$transaction->transaction_id : '',
                        'amount' => isset($transaction->amount) ? (float)$transaction->amount : 0,
                        'email' => isset($transaction->email) ? (string)$transaction->email : '',
                        'phone' => isset($transaction->phone) ? (string)$transaction->phone : '',
                        'date' => isset($transaction->date) ? (string)$transaction->date : '',
                        'status' => isset($transaction->status) ? (string)$transaction->status : 'failed',
                        'reason' => isset($transaction->condition) ? (string)$transaction->condition : 'failed',
                        'first_name' => isset($transaction->first_name) ? (string)$transaction->first_name : '',
                        'last_name' => isset($transaction->last_name) ? (string)$transaction->last_name : '',
                        'customer' => isset($transaction->customer) ? (string)$transaction->customer : ''
                    ];
                    
                    // Only add failed transactions to our result
                    if (strtolower($transData['status']) == 'failed') {
                        $transactions[] = $transData;
                    }
                }
            }
            
            return ['transactions' => $transactions];
        } catch (Exception $e) {
            error_log("XML Parsing Error: " . $e->getMessage());
            return ['error' => 'Failed to parse XML response: ' . $e->getMessage()];
        }
    }

    public function getTransactionById($transactionId) {
        $postData = [
            'security_key' => $this->apiKey,
            'report_type' => 'transaction',
            'transaction_id' => $transactionId
        ];
        
        error_log("NMI API Request for transaction " . $transactionId . ": " . json_encode($postData));
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        
        error_log("NMI API Response for transaction " . $transactionId . " (first 100 chars): " . substr($response, 0, 100));
        
        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        
        return $this->parseXmlResponse($response);
    }

    public function getSpecificTransaction($transactionId) {
        $postData = [
            'security_key' => $this->apiKey,
            'report_type' => 'transaction',
            'transaction_id' => $transactionId
        ];
        
        error_log("NMI API Request for Specific Transaction: " . json_encode($postData));
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        error_log("NMI API Response for Specific Transaction: " . $response);
        
        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        
        return $this->parseXmlResponse($response);
    }
}
?>