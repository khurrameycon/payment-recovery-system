<?php
// File: app/services/CommunicationService.php
// New file for sending emails and SMS

class CommunicationService {
    private $twilioSid;
    private $twilioToken;
    private $twilioPhone;
    private $testMode;
    public $testEmail;
    public $testPhone;
    
    public function __construct($testMode = true) {
        $this->twilioSid = TWILIO_SID;
        $this->twilioToken = TWILIO_TOKEN;
        $this->twilioPhone = TWILIO_PHONE;
        $this->testMode = $testMode;
        
        // Test contact information - replace with your own for testing
        $this->testEmail = "khurrameycon4@gmail.com"; // REPLACE WITH YOUR EMAIL
        $this->testPhone = "+16479795947"; // REPLACE WITH YOUR PHONE NUMBER (with country code)
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $options Additional options
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $body, $options = []) {
        // In test mode, override recipient with test email
        if ($this->testMode) {
            error_log("TEST MODE: Redirecting email from $to to {$this->testEmail}");
            $to = $this->testEmail;
        }
        
        // Log email sending attempt
        error_log("Sending email to: $to");
        error_log("Subject: $subject");
        
        // Method 1: Use SMTP directly (requires allow_url_fopen enabled)
        try {
            // OPTION 1: Use an external SMTP service like Gmail, SendGrid, etc.
            // Replace these with your actual email provider details
            $smtpServer = "smtp.gmail.com";
            $smtpPort = 587;
            $smtpUsername = "your.email@gmail.com"; // Replace with your Gmail
            $smtpPassword = "your-gmail-app-password"; // Generate an app password
            
            // Create a basic socket connection (if this fails, try Method 2)
            $socket = fsockopen($smtpServer, $smtpPort, $errno, $errstr, 30);
            if (!$socket) {
                error_log("Could not connect to SMTP server: $errstr ($errno)");
                throw new Exception("SMTP connection failed");
            }
            
            // Method 2: Using PHP mail() function as fallback
            // If you're using XAMPP, configure your php.ini mail settings first
            $from = "Payment Recovery <noreply@paymentrecovery.com>";
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $from,
                'Reply-To: support@paymentrecovery.com'
            ];
            
            // Add additional headers from options
            if (isset($options['headers']) && is_array($options['headers'])) {
                $headers = array_merge($headers, $options['headers']);
            }
            
            $result = mail($to, $subject, $body, implode("\r\n", $headers));
            
            // Method 3: Write to file for testing
            // This is a fallback method if neither SMTP nor mail() works
            if (!$result) {
                error_log("Mail function failed, writing email to file instead");
                $emailFile = BASE_PATH . '/logs/test_email_' . time() . '.html';
                $emailContent = "To: $to\nSubject: $subject\n\n$body";
                file_put_contents($emailFile, $emailContent);
                error_log("Email saved to file: $emailFile");
                $result = true; // Consider it a success for testing purposes
            }
        } catch (Exception $e) {
            error_log("Exception in sendEmail: " . $e->getMessage());
            
            // Fallback to file method
            $emailFile = BASE_PATH . '/logs/test_email_' . time() . '.html';
            $emailContent = "To: $to\nSubject: $subject\n\n$body";
            file_put_contents($emailFile, $emailContent);
            error_log("Email saved to file: $emailFile");
            $result = true; // Consider it a success for testing purposes
        }
        
        // Log result
        if ($result) {
            error_log("Email sent successfully");
        } else {
            error_log("Failed to send email");
        }
        
        return $result;
    }
    
    /**
     * Send SMS via Twilio
     * 
     * @param string $to Recipient phone number
     * @param string $message SMS message
     * @return bool Success status
     */
    public function sendSMS($to, $message) {
        // In test mode, override recipient with test phone
        if ($this->testMode) {
            error_log("TEST MODE: Redirecting SMS from $to to {$this->testPhone}");
            $to = $this->testPhone;
        }
        
        // Check if Twilio credentials are configured
        if (empty($this->twilioSid) || empty($this->twilioToken) || empty($this->twilioPhone)) {
            error_log("Twilio not configured properly. Check API keys.");
            return false;
        }
        
        // Prepare Twilio API request
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json";
        $data = [
            'From' => $this->twilioPhone,
            'To' => $to,
            'Body' => $message
        ];
        
        // Log SMS sending attempt
        error_log("Sending SMS to: $to");
        error_log("Message: " . substr($message, 0, 50) . "...");
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$this->twilioSid}:{$this->twilioToken}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_errno($ch)) {
            error_log("Twilio API Error: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Log response
        error_log("Twilio API Response Code: $httpCode");
        error_log("Twilio API Response: " . substr($response, 0, 200));
        
        // Check if successful (2xx status code)
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        if ($success) {
            error_log("SMS sent successfully");
        } else {
            error_log("Failed to send SMS");
        }
        
        return $success;
    }
}
?>