<?php
// File: app/services/WhatsAppService.php

class WhatsAppService {
    private $apiUrl;
    private $accessToken;
    private $phoneNumberId;
    private $businessAccountId;
    private $db;
    
    /**
     * Initialize WhatsApp Business API client
     */
    public function __construct() {
        $this->db = getDbConnection();
        $this->apiUrl = WHATSAPP_API_URL ?? 'https://graph.facebook.com/v17.0';
        $this->accessToken = WHATSAPP_ACCESS_TOKEN ?? '';
        $this->phoneNumberId = WHATSAPP_PHONE_NUMBER_ID ?? '';
        $this->businessAccountId = WHATSAPP_BUSINESS_ACCOUNT_ID ?? '';
    }
    
    /**
     * Send a message to a customer using a template
     * 
     * @param string $phoneNumber Customer's phone number with country code
     * @param string $templateName Template name
     * @param array $templateParams Template parameters
     * @param string $languageCode Language code (default: en_US)
     * @return array Response data
     */
    public function sendTemplateMessage($phoneNumber, $templateName, $templateParams = [], $languageCode = 'en_US') {
        // Clean phone number to ensure it has proper format
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        // Prepare API endpoint URL
        $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";
        
        // Prepare template components
        $components = [];
        
        // Add parameters to components array if they exist
        if (!empty($templateParams)) {
            $parameters = [];
            
            foreach ($templateParams as $param) {
                $parameters[] = [
                    'type' => 'text',
                    'text' => $param
                ];
            }
            
            if (!empty($parameters)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => $parameters
                ];
            }
        }
        
        // Prepare request data
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode
                ]
            ]
        ];
        
        // Add components if they exist
        if (!empty($components)) {
            $data['template']['components'] = $components;
        }
        
        // Make API request
        $response = $this->makeApiRequest($endpoint, $data);
        
        // Log the attempt in database
        $this->logMessageAttempt($phoneNumber, $templateName, $response);
        
        return $response;
    }
    
    /**
     * Send a text message to a customer
     * 
     * @param string $phoneNumber Customer's phone number with country code
     * @param string $message Text message to send
     * @return array Response data
     */
    public function sendTextMessage($phoneNumber, $message) {
        // Clean phone number to ensure it has proper format
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        // Prepare API endpoint URL
        $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";
        
        // Prepare request data
        $data = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message
            ]
        ];
        
        // Make API request
        $response = $this->makeApiRequest($endpoint, $data);
        
        // Log the attempt in database
        $this->logMessageAttempt($phoneNumber, 'text_message', $response);
        
        return $response;
    }
    
    /**
     * Send a recovery payment link via WhatsApp
     * 
     * @param int $transactionId The transaction ID
     * @param string $templateName Template name to use
     * @return array Result data
     */
    public function sendRecoveryLink($transactionId, $templateName = 'payment_recovery') {
        // Get transaction details
        $transaction = $this->getTransactionDetails($transactionId);
        
        if (!$transaction) {
            return [
                'success' => false,
                'error' => 'Transaction not found'
            ];
        }
        
        // Get customer details
        $customer = $this->getCustomerDetails($transaction['customer_id']);
        
        if (!$customer || empty($customer['phone'])) {
            return [
                'success' => false,
                'error' => 'Customer phone number not found'
            ];
        }
        
        // Get recovery link
        $recoveryLink = $this->getRecoveryLink($transactionId);
        
        if (!$recoveryLink) {
            return [
                'success' => false,
                'error' => 'Recovery link not found'
            ];
        }
        
        // Generate tracking ID for this attempt
        $trackingId = uniqid() . bin2hex(random_bytes(4));
        
        // Add tracking parameter to recovery link
        $trackingUrl = $recoveryLink['recovery_link'] . '&track=' . $trackingId;
        
        // Create template parameters
        $templateParams = [
            $customer['first_name'] ?? 'Customer',
            number_format($transaction['amount'], 2),
            $trackingUrl
        ];
        
        // Send the message
        $response = $this->sendTemplateMessage(
            $customer['phone'],
            $templateName,
            $templateParams
        );
        
        // Record the communication attempt
        if ($response['success']) {
            $whatsappMessageId = $response['messages'][0]['id'] ?? null;
            
            $stmt = $this->db->prepare("INSERT INTO communication_attempts 
                                       (transaction_id, channel, status, sent_at, message_template, tracking_id, external_id) 
                                       VALUES (?, 'whatsapp', 'sent', NOW(), ?, ?, ?)");
            $stmt->bind_param("isss", $transactionId, $templateName, $trackingId, $whatsappMessageId);
            $stmt->execute();
        }
        
        return $response;
    }
    
    /**
     * Process webhook events from WhatsApp Business API
     * 
     * @param array $payload Webhook payload
     * @return bool Success flag
     */
    public function processWebhook($payload) {
        // Validate webhook signature if needed
        if (!$this->validateWebhook($payload)) {
            return false;
        }
        
        // Get entry array from payload
        $entries = $payload['entry'] ?? [];
        
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            
            foreach ($changes as $change) {
                if ($change['field'] != 'messages') {
                    continue;
                }
                
                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];
                
                foreach ($messages as $message) {
                    $this->processMessage($message, $value);
                }
                
                $statuses = $value['statuses'] ?? [];
                
                foreach ($statuses as $status) {
                    $this->processStatus($status);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Make an API request to WhatsApp Business API
     * 
     * @param string $endpoint API endpoint URL
     * @param array $data Request data
     * @return array Response data
     */
    private function makeApiRequest($endpoint, $data) {
        // Initialize cURL session
        $ch = curl_init($endpoint);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_errno($ch)) {
            curl_close($ch);
            return [
                'success' => false,
                'error' => 'cURL Error: ' . curl_error($ch),
                'http_code' => $httpCode
            ];
        }
        
        // Close cURL session
        curl_close($ch);
        
        // Decode JSON response
        $responseData = json_decode($response, true);
        
        // Check if request was successful
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        return [
            'success' => $success,
            'http_code' => $httpCode,
            'messages' => $responseData['messages'] ?? [],
            'error' => $success ? null : ($responseData['error']['message'] ?? 'Unknown error')
        ];
    }
    
    /**
     * Format phone number to ensure it has proper international format
     * 
     * @param string $phoneNumber Phone number to format
     * @return string Formatted phone number
     */
    private function formatPhoneNumber($phoneNumber) {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phoneNumber);
        
        // Ensure it starts with a plus sign
        if (substr($digits, 0, 1) !== '+') {
            // Add country code if it doesn't have one
            if (strlen($digits) <= 10) {
                // Default to US (+1) if no country code
                $digits = '1' . $digits;
            }
            
            $digits = '+' . $digits;
        }
        
        return $digits;
    }
    
    /**
     * Log message attempt in database
     * 
     * @param string $phoneNumber Recipient phone number
     * @param string $template Template used
     * @param array $response API response
     */
    private function logMessageAttempt($phoneNumber, $template, $response) {
        // Prepare data for logging
        $success = $response['success'] ? 1 : 0;
        $messageId = $response['success'] ? ($response['messages'][0]['id'] ?? null) : null;
        $errorMessage = $response['success'] ? null : ($response['error'] ?? 'Unknown error');
        
        // Insert log entry
        $stmt = $this->db->prepare("INSERT INTO whatsapp_message_logs 
                                   (phone_number, template_name, success, message_id, error_message, created_at) 
                                   VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssiss", $phoneNumber, $template, $success, $messageId, $errorMessage);
        $stmt->execute();
    }
    
    /**
     * Process incoming message from webhook
     * 
     * @param array $message Message data
     * @param array $value Additional webhook data
     */
    private function processMessage($message, $value) {
        $messageId = $message['id'] ?? null;
        $from = $message['from'] ?? null;
        $timestamp = $message['timestamp'] ?? null;
        $text = $message['text']['body'] ?? null;
        
        if (!$messageId || !$from) {
            return;
        }
        
        // Convert timestamp to datetime
        $datetime = date('Y-m-d H:i:s', $timestamp);
        
        // Log the incoming message
        $stmt = $this->db->prepare("INSERT INTO whatsapp_incoming_messages 
                                   (message_id, phone_number, message_text, timestamp, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $messageId, $from, $text, $datetime);
        $stmt->execute();
        
        // Check if this is a response to a recovery message
        $this->handleRecoveryResponse($from, $text);
    }
    
    /**
     * Process message status update from webhook
     * 
     * @param array $status Status data
     */
    private function processStatus($status) {
        $messageId = $status['id'] ?? null;
        $status = $status['status'] ?? null;
        $timestamp = $status['timestamp'] ?? null;
        
        if (!$messageId || !$status) {
            return;
        }
        
        // Convert timestamp to datetime
        $datetime = date('Y-m-d H:i:s', $timestamp);
        
        // Update communication attempt status if this is a message we sent
        if (in_array($status, ['sent', 'delivered', 'read'])) {
            $dbStatus = 'sent';
            
            if ($status == 'read') {
                $dbStatus = 'opened';
            }
            
            $stmt = $this->db->prepare("UPDATE communication_attempts 
                                       SET status = ?, updated_at = NOW() 
                                       WHERE external_id = ?");
            $stmt->bind_param("ss", $dbStatus, $messageId);
            $stmt->execute();
        }
    }
    
    /**
     * Get transaction details
     * 
     * @param int $transactionId Transaction ID
     * @return array|null Transaction data
     */
    private function getTransactionDetails($transactionId) {
        $stmt = $this->db->prepare("SELECT * FROM failed_transactions WHERE id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get customer details
     * 
     * @param int $customerId Customer ID
     * @return array|null Customer data
     */
    private function getCustomerDetails($customerId) {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Get recovery link for transaction
     * 
     * @param int $transactionId Transaction ID
     * @return array|null Recovery link data
     */
    private function getRecoveryLink($transactionId) {
        $stmt = $this->db->prepare("SELECT * FROM payment_recovery WHERE transaction_id = ? AND status = 'active'");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        return $result->fetch_assoc();
    }
    
    /**
     * Validate webhook signature
     * 
     * @param array $payload Webhook payload
     * @return bool Validation result
     */
    private function validateWebhook($payload) {
        // In a real implementation, validate the signature
        // using app secret from Meta for security
        return true;
    }
    
    /**
     * Handle potential response to a recovery message
     * 
     * @param string $phoneNumber Customer phone number
     * @param string $messageText Message text
     */
    private function handleRecoveryResponse($phoneNumber, $messageText) {
        // Check if this is asking for help with payment
        $helpPhrases = [
            'help', 'can\'t pay', 'cannot pay', 'having trouble', 'payment issue',
            'link not working', 'problem with payment', 'need assistance', 'support'
        ];
        
        $needsHelp = false;
        foreach ($helpPhrases as $phrase) {
            if (stripos($messageText, $phrase) !== false) {
                $needsHelp = true;
                break;
            }
        }
        
        if ($needsHelp) {
            // Find the customer
            $stmt = $this->db->prepare("SELECT id FROM customers WHERE phone = ? LIMIT 1");
            $stmt->bind_param("s", $phoneNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                
                // Send help message with support contact
                $this->sendTextMessage(
                    $phoneNumber,
                    "We're sorry you're having trouble with your payment. Please contact our support team at support@example.com or call us at +1-800-123-4567 for immediate assistance."
                );
                
                // Log this in the system
                $sql = "INSERT INTO customer_support_requests 
                       (customer_id, channel, message, status, created_at) 
                       VALUES (?, 'whatsapp', ?, 'pending', NOW())";
                
                $stmt = $this->db->prepare($sql);
                $message = "Customer requested payment help via WhatsApp: " . $messageText;
                $stmt->bind_param("is", $customer['id'], $message);
                $stmt->execute();
            }
        }
    }
    
    /**
     * Get available message templates
     * 
     * @return array Template list
     */
    public function getAvailableTemplates() {
        // In production, this would call the WhatsApp Business API
        // to get the list of approved templates
        
        // For now, return predefined templates
        return [
            [
                'name' => 'payment_recovery',
                'language' => 'en_US',
                'category' => 'PAYMENT_UPDATE',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Hello {{1}}, your payment of ${{2}} was declined. Please use this link to complete your payment: {{3}}',
                        'example' => [
                            'body_text' => [
                                ['text' => 'John'],
                                ['text' => '100.00'],
                                ['text' => 'https://example.com/pay/123456']
                            ]
                        ]
                    ]
                ],
                'status' => 'APPROVED'
            ],
            [
                'name' => 'payment_reminder',
                'language' => 'en_US',
                'category' => 'PAYMENT_UPDATE',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Hello {{1}}, this is a reminder about your pending payment of ${{2}}. Click here to complete your payment: {{3}}',
                        'example' => [
                            'body_text' => [
                                ['text' => 'John'],
                                ['text' => '100.00'],
                                ['text' => 'https://example.com/pay/123456']
                            ]
                        ]
                    ]
                ],
                'status' => 'APPROVED'
            ],
            [
                'name' => 'final_reminder',
                'language' => 'en_US',
                'category' => 'PAYMENT_UPDATE',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Hello {{1}}, this is your final reminder for your pending payment of ${{2}}. Please complete your payment here: {{3}}',
                        'example' => [
                            'body_text' => [
                                ['text' => 'John'],
                                ['text' => '100.00'],
                                ['text' => 'https://example.com/pay/123456']
                            ]
                        ]
                    ]
                ],
                'status' => 'APPROVED'
            ]
        ];
    }
    
    /**
     * Create and submit a new template for approval
     * 
     * @param string $name Template name
     * @param string $category Template category
     * @param string $bodyText Body text with {{parameters}}
     * @param array $headerParams Header parameters
     * @param array $exampleParams Example parameters
     * @return array Result data
     */
    public function createTemplate($name, $category, $bodyText, $headerParams = [], $exampleParams = []) {
        // This would call the WhatsApp Business API to submit a template for approval
        // In a real implementation, this would use the Create Message Template endpoint
        
        // For now, just log the template creation request
        $templateData = [
            'name' => $name,
            'category' => $category,
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => $bodyText,
                    'example' => [
                        'body_text' => $exampleParams
                    ]
                ]
            ]
        ];
        
        // Add header if provided
        if (!empty($headerParams)) {
            $templateData['components'][] = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $headerParams['text'],
                'example' => [
                    'header_text' => $headerParams['example']
                ]
            ];
        }
        
        // Log template creation
        error_log('WhatsApp template creation request: ' . json_encode($templateData));
        
        return [
            'success' => true,
            'template_name' => $name,
            'status' => 'PENDING',
            'id' => uniqid('template_')
        ];
    }
    
    /**
     * Create SQL tables for WhatsApp integration
     */
    public static function createTables() {
        $db = getDbConnection();
        
        // WhatsApp message logs table
        $sql = "CREATE TABLE IF NOT EXISTS `whatsapp_message_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `phone_number` varchar(20) NOT NULL,
            `template_name` varchar(100) NOT NULL,
            `success` tinyint(1) NOT NULL DEFAULT 0,
            `message_id` varchar(100) DEFAULT NULL,
            `error_message` text DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `phone_number` (`phone_number`),
            KEY `message_id` (`message_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // WhatsApp incoming messages table
        $sql = "CREATE TABLE IF NOT EXISTS `whatsapp_incoming_messages` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `message_id` varchar(100) NOT NULL,
            `phone_number` varchar(20) NOT NULL,
            `message_text` text DEFAULT NULL,
            `timestamp` datetime NOT NULL,
            `created_at` datetime NOT NULL,
            `processed` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `message_id` (`message_id`),
            KEY `phone_number` (`phone_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // Customer support requests table
        $sql = "CREATE TABLE IF NOT EXISTS `customer_support_requests` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `channel` varchar(20) NOT NULL,
            `message` text NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'pending',
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            `assigned_to` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
        
        // Add external_id field to communication_attempts table if it doesn't exist
        $sql = "SHOW COLUMNS FROM `communication_attempts` LIKE 'external_id'";
        $result = $db->query($sql);
        
        if ($result->num_rows === 0) {
            $sql = "ALTER TABLE `communication_attempts` 
                    ADD COLUMN `external_id` varchar(100) DEFAULT NULL,
                    ADD INDEX `external_id` (`external_id`)";
            $db->query($sql);
        }
    }

    /**
     * Send payment recovery reminder via WhatsApp
     * 
     * @param int $transactionId Transaction to recover
     * @param array $options Additional options
     * @return array Result with status and details
     */
    public function sendRecoveryReminder($transactionId, $options = []) {
        // Get transaction details
        $transaction = $this->getTransactionDetails($transactionId);
        if (!$transaction) {
            return [
                'success' => false,
                'message' => 'Transaction not found'
            ];
        }
        
        // Get customer details
        $customer = $this->getCustomerDetails($transaction['customer_id']);
        if (!$customer || empty($customer['phone'])) {
            return [
                'success' => false,
                'message' => 'Customer phone number not found'
            ];
        }
        
        // Get recovery link
        $recoveryLink = $this->getRecoveryLink($transactionId);
        if (!$recoveryLink) {
            return [
                'success' => false,
                'message' => 'Recovery link not found'
            ];
        }
        
        // Generate tracking ID for this attempt
        $trackingId = uniqid() . bin2hex(random_bytes(4));
        
        // Add tracking parameter to recovery link
        $trackingUrl = $recoveryLink['recovery_link'] . '&track=' . $trackingId;
        
        // Determine template based on segment and attempt number
        $template = $options['template'] ?? 'payment_recovery';
        $customerName = $customer['first_name'] ?? 'Customer';
        
        // Format amount with proper currency symbol
        $amount = number_format($transaction['amount'], 2);
        
        // Send the WhatsApp message
        $result = $this->sendTemplateMessage(
            $customer['phone'],
            $template,
            [$customerName, $amount, $trackingUrl],
            $options['language'] ?? 'en_US'
        );
        
        // Record the communication attempt if message sending was successful
        if ($result['success']) {
            $whatsappMessageId = $result['messages'][0]['id'] ?? null;
            $templateName = $template;
            
            $stmt = $this->db->prepare("INSERT INTO communication_attempts 
                                    (transaction_id, channel, status, scheduled_at, sent_at, message_template, tracking_id, external_id) 
                                    VALUES (?, 'whatsapp', 'sent', NOW(), NOW(), ?, ?, ?)");
            $stmt->bind_param("isss", $transactionId, $templateName, $trackingId, $whatsappMessageId);
            $stmt->execute();
            
            // Update organization usage statistics if this is a multi-tenant system
            if (isset($transaction['organization_id'])) {
                $this->updateUsageStats($transaction['organization_id'], 'whatsapp');
            }
            
            return [
                'success' => true,
                'message' => 'WhatsApp reminder sent successfully',
                'tracking_id' => $trackingId
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['error'] ?? 'Failed to send WhatsApp message',
            'details' => $result
        ];
    }

    /**
     * Update organization usage statistics
     */
    private function updateUsageStats($organizationId, $channel) {
        $yearMonth = date('Y-m');
        
        // Check if we have a record for this month
        $stmt = $this->db->prepare("
            SELECT id FROM organization_usage 
            WHERE organization_id = ? AND year_month = ?
        ");
        
        $stmt->bind_param('is', $organizationId, $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Create new record
            $sql = "INSERT INTO organization_usage 
                (organization_id, year_month, messages_sent, {$channel}_count) 
                VALUES (?, ?, 1, 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('is', $organizationId, $yearMonth);
        } else {
            // Update existing record
            $row = $result->fetch_assoc();
            $sql = "UPDATE organization_usage 
                SET messages_sent = messages_sent + 1, {$channel}_count = {$channel}_count + 1 
                WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $row['id']);
        }
        
        $stmt->execute();
    }
}