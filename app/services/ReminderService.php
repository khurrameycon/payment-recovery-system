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

    public function determineOptimalSendTime($customerId) {
        // Get customer timezone
        $customer = $this->customerModel->getById($customerId);
        $timezone = $customer['timezone'] ?: 'UTC';
        
        // Create datetime object in customer's timezone
        $now = new DateTime('now', new DateTimeZone($timezone));
        $hour = (int)$now->format('H');
        $dayOfWeek = (int)$now->format('N'); // 1 (Monday) to 7 (Sunday)
        
        // Check if current time is within business hours (9am-5pm, Monday-Friday)
        $isBusinessHours = ($hour >= 9 && $hour < 17) && ($dayOfWeek <= 5);
        
        // If outside business hours, schedule for next business hour
        if (!$isBusinessHours) {
            if ($hour >= 17 || $dayOfWeek > 5) {
                // After business hours or weekend - schedule for 10am next business day
                $now->setTime(10, 0, 0);
                
                if ($dayOfWeek == 6) { // Saturday
                    $now->modify('+2 days'); // Schedule for Monday
                } else if ($dayOfWeek == 7) { // Sunday
                    $now->modify('+1 day'); // Schedule for Monday
                } else if ($hour >= 17) { // After hours on weekday
                    $now->modify('+1 day'); // Schedule for tomorrow
                }
            } else if ($hour < 9) {
                // Early morning - schedule for 10am same day
                $now->setTime(10, 0, 0);
            }
        }
        
        return $now;
    }


    // Add to app/services/ReminderService.php
    public function determineOptimalChannel($customerId, $transactionId) {
        // Get customer info
        $customer = $this->customerModel->getById($customerId);
        
        // Get previous communication history
        $stmt = $this->db->prepare("
            SELECT channel, status, COUNT(*) as count
            FROM communication_attempts
            WHERE transaction_id IN (
                SELECT id FROM failed_transactions WHERE customer_id = ?
            )
            GROUP BY channel, status
        ");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $channelStats = [
            'email' => ['sent' => 0, 'opened' => 0, 'clicked' => 0],
            'sms' => ['sent' => 0, 'opened' => 0, 'clicked' => 0]
        ];
        
        while ($row = $result->fetch_assoc()) {
            $channelStats[$row['channel']][$row['status']] = $row['count'];
        }
        
        // Calculate success rates
        $emailSuccess = 0;
        $smsSuccess = 0;
        
        if ($channelStats['email']['sent'] > 0) {
            $emailSuccess = ($channelStats['email']['opened'] + $channelStats['email']['clicked']) / $channelStats['email']['sent'];
        }
        
        if ($channelStats['sms']['sent'] > 0) {
            $smsSuccess = ($channelStats['sms']['opened'] + $channelStats['sms']['clicked']) / $channelStats['sms']['sent'];
        }
        
        // Get customer segment
        $segment = $customer['segment'] ?: 'standard';
        
        // Choose channel based on success rate, segment and availability
        if ($segment == 'premium') {
            // Premium customers get both channels
            return ['email', 'sms'];
        } else if (!empty($customer['email']) && !empty($customer['phone'])) {
            // We have both, choose the more successful one
            return $emailSuccess >= $smsSuccess ? 'email' : 'sms';
        } else if (!empty($customer['email'])) {
            return 'email';
        } else if (!empty($customer['phone'])) {
            return 'sms';
        }
        
        // Default fallback
        return 'email';
    }

    private function sendEmailReminder($reminder) {
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
        
        // Set headers
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: Payment Recovery <recovery@yourdomain.com>' . "\r\n";
        
        // Add tracking pixel for opens
        $trackingPixel = '<img src="http://localhost/payment-recovery/public/index.php?route=track-open&id=' . 
                         $reminder['tracking_id'] . '" width="1" height="1" alt="">';
        $body .= $trackingPixel;
        
        // Send email
        $success = mail($reminder['email'], $subject, $body, $headers);
        
        // Log sending attempt
        error_log("Email to {$reminder['email']}, Subject: {$subject}, Success: " . ($success ? 'Yes' : 'No'));
        
        return $success;
    }
}
?>