<?php
require_once BASE_PATH . '/app/models/Transaction.php';
require_once BASE_PATH . '/app/models/Customer.php';

class ReminderService {
    private $db;
    private $transactionModel;
    private $customerModel;
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->transactionModel = new Transaction();
        $this->customerModel = new Customer();
    }
    
    /**
     * Schedule a reminder for a transaction
     */
    public function scheduleReminder($transactionId, $channel = 'email') {
        // Get transaction and customer info
        $transaction = $this->transactionModel->getTransactionById($transactionId);
        
        if (!$transaction) {
            return false;
        }
        
        $customer = $this->customerModel->getById($transaction['customer_id']);
        
        if (!$customer) {
            return false;
        }
        
        // Check if this would be the first, second, or third reminder
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM communication_attempts 
                                     WHERE transaction_id = ? AND channel = ?");
        $stmt->bind_param("is", $transactionId, $channel);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $reminderCount = $row['count'];
        
        // Only allow up to 3 reminders
        if ($reminderCount >= 3) {
            return false;
        }
        
        // Determine template based on reminder count
        $template = 'reminder_' . ($reminderCount + 1);
        
        // Calculate when to send based on timezone
        $timezone = $customer['timezone'] ?: 'UTC';
        $now = new DateTime('now', new DateTimeZone($timezone));
        
        // If it's after 8pm or before 8am, schedule for 10am
        $hour = (int)$now->format('H');
        if ($hour >= 20 || $hour < 8) {
            $now->setTime(10, 0); // Set to 10am
            // If it's after 8pm, schedule for next day
            if ($hour >= 20) {
                $now->modify('+1 day');
            }
        }
        
        $scheduledAt = $now->format('Y-m-d H:i:s');
        
        // Generate tracking ID
        $trackingId = uniqid() . bin2hex(random_bytes(4));
        
        // Insert into communication_attempts
        $stmt = $this->db->prepare("INSERT INTO communication_attempts 
                                    (transaction_id, channel, status, scheduled_at, message_template, tracking_id) 
                                    VALUES (?, ?, 'scheduled', ?, ?, ?)");
        $stmt->bind_param("issss", $transactionId, $channel, $scheduledAt, $template, $trackingId);
        
        return $stmt->execute();
    }
    
    /**
     * Send scheduled reminders
     */
    public function sendScheduledReminders() {
        // Get all scheduled reminders that are due
        $sql = "SELECT ca.*, ft.amount, ft.transaction_reference, c.email, c.phone, pr.recovery_link 
                FROM communication_attempts ca
                JOIN failed_transactions ft ON ca.transaction_id = ft.id
                JOIN customers c ON ft.customer_id = c.id
                JOIN payment_recovery pr ON ft.id = pr.transaction_id
                WHERE ca.status = 'scheduled' 
                AND ca.scheduled_at <= NOW()
                AND pr.status = 'active'";
        
        $result = $this->db->query($sql);
        $sent = 0;
        
        while ($reminder = $result->fetch_assoc()) {
            $success = false;
            
            if ($reminder['channel'] == 'email') {
                $success = $this->sendEmailReminder($reminder);
            } elseif ($reminder['channel'] == 'sms') {
                $success = $this->sendSmsReminder($reminder);
            }
            
            if ($success) {
                // Update status to sent
                $stmt = $this->db->prepare("UPDATE communication_attempts 
                                           SET status = 'sent', sent_at = NOW() 
                                           WHERE id = ?");
                $stmt->bind_param("i", $reminder['id']);
                $stmt->execute();
                $sent++;
            }
        }
        
        return $sent;
    }
    
    /**
     * Send email reminder
     */
    private function sendEmailReminder($reminder) {
        // In a real implementation, this would use PHPMailer or another email library
        // For now, we'll just simulate sending
        
        // Get email template
        $template = $this->getEmailTemplate($reminder['message_template']);
        
        // Replace placeholders
        $amount = number_format($reminder['amount'], 2);
        $body = str_replace(
            ['{{AMOUNT}}', '{{PAYMENT_LINK}}', '{{TRACKING_ID}}'],
            [$amount, $reminder['recovery_link'] . '&track=' . $reminder['tracking_id'], $reminder['tracking_id']],
            $template['body']
        );
        
        $subject = str_replace(
            ['{{AMOUNT}}'],
            [$amount],
            $template['subject']
        );
        
        // Log email for development
        error_log("EMAIL TO: {$reminder['email']}, SUBJECT: {$subject}");
        error_log("BODY: {$body}");
        
        // In production, you would send the actual email here
        
        return true;
    }
    
    /**
     * Send SMS reminder
     */
    private function sendSmsReminder($reminder) {
        // In a real implementation, this would use Twilio
        // For now, we'll just simulate sending
        
        // Get SMS template
        $template = $this->getSmsTemplate($reminder['message_template']);
        
        // Replace placeholders
        $amount = number_format($reminder['amount'], 2);
        $message = str_replace(
            ['{{AMOUNT}}', '{{PAYMENT_LINK}}', '{{TRACKING_ID}}'],
            [$amount, $this->getShortenedUrl($reminder['recovery_link'] . '&track=' . $reminder['tracking_id']), $reminder['tracking_id']],
            $template
        );
        
        // Log SMS for development
        error_log("SMS TO: {$reminder['phone']}, MESSAGE: {$message}");
        
        // In production, you would send the actual SMS here
        
        return true;
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($templateName) {
        // In a real implementation, these would be in the database or template files
        $templates = [
            'reminder_1' => [
                'subject' => 'Your payment of ${{AMOUNT}} was declined',
                'body' => '<p>We noticed your recent payment was declined. Please click <a href="{{PAYMENT_LINK}}">here</a> to complete your payment.</p>'
            ],
            'reminder_2' => [
                'subject' => 'Second Reminder: Your payment of ${{AMOUNT}} is still pending',
                'body' => '<p>This is a friendly reminder that your payment is still pending. Please click <a href="{{PAYMENT_LINK}}">here</a> to complete your payment.</p>'
            ],
            'reminder_3' => [
                'subject' => 'Final Reminder: Your payment of ${{AMOUNT}}',
                'body' => '<p>This is your final reminder to complete your payment. Please click <a href="{{PAYMENT_LINK}}">here</a> to proceed.</p>'
            ]
        ];
        
        return $templates[$templateName] ?? $templates['reminder_1'];
    }
    
    /**
     * Get SMS template
     */
    private function getSmsTemplate($templateName) {
        // In a real implementation, these would be in the database
        $templates = [
            'reminder_1' => 'Your payment of ${{AMOUNT}} was declined. Complete your payment here: {{PAYMENT_LINK}}',
            'reminder_2' => 'Reminder: Your payment of ${{AMOUNT}} is still pending. Pay here: {{PAYMENT_LINK}}',
            'reminder_3' => 'Final notice: Please complete your payment of ${{AMOUNT}} here: {{PAYMENT_LINK}}'
        ];
        
        return $templates[$templateName] ?? $templates['reminder_1'];
    }
    
    /**
     * Get shortened URL (simulated)
     */
    private function getShortenedUrl($url) {
        // In a real implementation, this would use Bitly API
        // For now, we'll just return a fake shortened URL
        return 'https://bit.ly/' . substr(md5($url), 0, 7);
    }
}
?>