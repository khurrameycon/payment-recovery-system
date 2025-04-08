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
    
     // File: app/services/NmiService.php
// Update the getFailedTransactions method to improve error handling and logging

public function getFailedTransactions($startDate = null, $endDate = null) {
    // Log the API request
    error_log("NMI API Request: Fetching failed transactions");
    if ($startDate && $endDate) {
        error_log("Date range: $startDate to $endDate");
    }
    
    // Prepare API request with date constraints if provided
    $postData = [
        'security_key' => $this->apiKey,
        'report_type' => 'transaction'
    ];
    
    // Add date range if provided
    if ($startDate && $endDate) {
        $postData['start_date'] = date('Ymd', strtotime($startDate));
        $postData['end_date'] = date('Ymd', strtotime($endDate));
    }
    
    // Add condition for failed transactions
    $postData['condition'] = 'status=failed';
    
    // Make API request using cURL
    $ch = curl_init($this->apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
    
    // Log the raw request
    error_log("NMI API Request Data: " . http_build_query($postData));
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        error_log("NMI API Error: " . $errorMsg);
        curl_close($ch);
        return ['error' => $errorMsg, 'transactions' => []];
    }
    
    curl_close($ch);
    
    // Log a sample of the response (first 200 chars)
    error_log("NMI API Response (first 200 chars): " . substr($response, 0, 200));
    
    // Parse XML response
    $result = $this->parseXmlResponse($response);
    
    // If we get no results, try without the 'condition' param
    if (empty($result['transactions'])) {
        error_log("No failed transactions found with condition param, trying without condition");
        $postData = [
            'security_key' => $this->apiKey,
            'report_type' => 'transaction'
        ];
        
        if ($startDate && $endDate) {
            $postData['start_date'] = date('Ymd', strtotime($startDate));
            $postData['end_date'] = date('Ymd', strtotime($endDate));
        }
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
        
        error_log("Second attempt NMI API Request Data: " . http_build_query($postData));
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            error_log("NMI API Error on second attempt: " . $errorMsg);
            curl_close($ch);
            return ['error' => $errorMsg, 'transactions' => []];
        }
        
        curl_close($ch);
        
        // Log a sample of the response (first 200 chars)
        error_log("NMI API Second Response (first 200 chars): " . substr($response, 0, 200));
        
        // Parse and filter for failed transactions manually
        $result = $this->parseXmlResponse($response);
    }
    
    // If still empty, return empty array instead of null
    if (!isset($result['transactions'])) {
        $result['transactions'] = [];
    }
    
    error_log("Found " . count($result['transactions']) . " failed transactions");
    
    return $result;
}
    
    private function parseXmlResponse($xmlResponse) {
        if (empty($xmlResponse)) {
            return ['transactions' => []];
        }
        
        $transactions = [];
        
        // Create SimpleXML object from response
        try {
            $xml = new SimpleXMLElement($xmlResponse);
            
            // Check if we have transaction data
            if (isset($xml->transaction)) {
                foreach ($xml->transaction as $transaction) {
                    // Get the status/condition
                    $status = isset($transaction->condition) ? (string)$transaction->condition : '';
                    
                    // Only process failed transactions
                    if (strtolower($status) == 'failed') {
                        // Get the amount from the action node
                        $amount = 0;
                        if (isset($transaction->action) && isset($transaction->action->amount)) {
                            $amount = (float)$transaction->action->amount;
                        }
                        
                        // Get email and phone (if available)
                        $email = isset($transaction->email) ? (string)$transaction->email : '';
                        $phone = isset($transaction->phone) ? (string)$transaction->phone : '';
                        
                        // Get the response text (failure reason)
                        $reason = '';
                        if (isset($transaction->action) && isset($transaction->action->response_text)) {
                            $reason = (string)$transaction->action->response_text;
                        }
                        
                        // Get transaction date
                        $date = isset($transaction->action->date) ? (string)$transaction->action->date : '';
                        if ($date) {
                            // Convert YYYYMMDDHHMMSS format to Y-m-d H:i:s
                            $date = DateTime::createFromFormat('YmdHis', $date);
                            $date = $date ? $date->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');
                        } else {
                            $date = date('Y-m-d H:i:s');
                        }
                        
                        // Get all fields from the transaction
                        $transData = [
                            'transaction_id' => isset($transaction->transaction_id) ? (string)$transaction->transaction_id : '',
                            'amount' => $amount,
                            'email' => $email,
                            'phone' => $phone,
                            'date' => $date,
                            'status' => 'failed',
                            'reason' => $reason ?: 'Unknown',
                            'first_name' => isset($transaction->first_name) ? (string)$transaction->first_name : '',
                            'last_name' => isset($transaction->last_name) ? (string)$transaction->last_name : '',
                            'customer' => isset($transaction->customerid) ? (string)$transaction->customerid : ''
                        ];
                        
                        $transactions[] = $transData;
                    }
                }
            }
            
            return ['transactions' => $transactions];
        } catch (Exception $e) {
            error_log("XML Parsing Error: " . $e->getMessage());
            return ['error' => 'Failed to parse XML response: ' . $e->getMessage(), 'transactions' => []];
        }
    }
    // private function parseXmlResponse($xmlResponse) {
    //     $transactions = [];
        
    //     // Create SimpleXML object from response
    //     try {
    //         $xml = new SimpleXMLElement($xmlResponse);
            
    //         // Check if we have transaction data
    //         if (isset($xml->transaction)) {
    //             foreach ($xml->transaction as $transaction) {
    //                 // Get the amount from the action node
    //                 $amount = 0;
    //                 if (isset($transaction->action) && isset($transaction->action->amount)) {
    //                     $amount = (float)$transaction->action->amount;
    //                 }
                    
    //                 // Get all fields from the transaction
    //                 $transData = [
    //                     'transaction_id' => isset($transaction->transaction_id) ? (string)$transaction->transaction_id : '',
    //                     'amount' => $amount, // Use the amount from the action node
    //                     'email' => isset($transaction->email) ? (string)$transaction->email : '',
    //                     'phone' => isset($transaction->phone) ? (string)$transaction->phone : '',
    //                     'date' => isset($transaction->action->date) ? (string)$transaction->action->date : '',
    //                     'status' => isset($transaction->condition) ? (string)$transaction->condition : 'failed',
    //                     'reason' => isset($transaction->action->response_text) ? (string)$transaction->action->response_text : 'failed',
    //                     'first_name' => isset($transaction->first_name) ? (string)$transaction->first_name : '',
    //                     'last_name' => isset($transaction->last_name) ? (string)$transaction->last_name : '',
    //                     'customer' => isset($transaction->customerid) ? (string)$transaction->customerid : ''
    //                 ];
                    
    //                 // Only add failed transactions to our result
    //                 if (strtolower($transData['status']) == 'failed') {
    //                     $transactions[] = $transData;
    //                 }
    //             }
    //         }
            
    //         return ['transactions' => $transactions];
    //     } catch (Exception $e) {
    //         error_log("XML Parsing Error: " . $e->getMessage());
    //         return ['error' => 'Failed to parse XML response: ' . $e->getMessage()];
    //     }
    // }

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

    public function debugTransaction($transactionId) {
        $postData = [
            'security_key' => $this->apiKey,
            'report_type' => 'transaction',
            'transaction_id' => $transactionId
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
        
        // Save the raw XML to a file for inspection
        file_put_contents(BASE_PATH . '/debug_transaction.xml', $response);
        
        // Create a detailed breakdown of the XML structure
        $breakdown = "XML STRUCTURE BREAKDOWN:\n\n";
        
        try {
            $xml = new SimpleXMLElement($response);
            $breakdown .= $this->dumpXmlStructure($xml);
        } catch (Exception $e) {
            $breakdown .= "Error parsing XML: " . $e->getMessage();
        }
        
        // Save the breakdown to a file
        file_put_contents(BASE_PATH . '/debug_structure.txt', $breakdown);
        
        return [
            'message' => 'Debug information has been saved to the files: debug_transaction.xml and debug_structure.txt',
            'raw_sample' => substr($response, 0, 6000) . '...'
        ];
    }
    
    // Helper function to recursively dump XML structure
    private function dumpXmlStructure($node, $path = '', $level = 0) {
        $output = '';
        $indent = str_repeat('  ', $level);
        
        foreach ($node as $name => $element) {
            $currentPath = $path ? $path . '/' . $name : $name;
            $value = trim((string)$element);
            
            $output .= $indent . "Element: {$name}\n";
            $output .= $indent . "  Path: {$currentPath}\n";
            
            if ($value) {
                $output .= $indent . "  Value: {$value}\n";
            }
            
            $output .= $indent . "  Attributes: ";
            
            $attributes = [];
            foreach ($element->attributes() as $attrName => $attrValue) {
                $attributes[] = "{$attrName}=\"{$attrValue}\"";
            }
            
            $output .= $attributes ? implode(', ', $attributes) : 'none';
            $output .= "\n\n";
            
            // Recursively process child elements
            $output .= $this->dumpXmlStructure($element, $currentPath, $level + 1);
        }
        
        return $output;
    }
}
?>