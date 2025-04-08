<?php
// File: test-communication.php
// This is a standalone test script to test email and SMS sending

// Define base path
define('BASE_PATH', __DIR__);


$logsDir = BASE_PATH . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "Created logs directory for storing test emails\n";
}
// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Include Communication service
require_once BASE_PATH . '/app/services/CommunicationService.php';

// Create Communication service instance (true = test mode)
$communicationService = new CommunicationService(true);

echo "Testing Communication Service...\n\n";

// Test sending email
echo "Testing email sending...\n";
$testEmailTo = "customer@example.com"; // This will be overridden by the test email in CommunicationService
$testEmailSubject = "Payment Recovery Test Email";
$testEmailBody = "
<html>
<body>
    <h1>Test Payment Recovery Email</h1>
    <p>This is a test email from the Payment Recovery System.</p>
    <p>If you are seeing this, email sending is working correctly.</p>
    <p><a href='http://example.com/test-link'>Click here to test tracking</a></p>
</body>
</html>
";

try {
    $emailResult = $communicationService->sendEmail($testEmailTo, $testEmailSubject, $testEmailBody);
    
    if ($emailResult) {
        echo "Email sent successfully!\n";
        echo "Check your email at: " . $communicationService->testEmail . "\n";
    } else {
        echo "Failed to send email. Check logs for details.\n";
    }
} catch (Exception $e) {
    echo "Exception caught while sending email: " . $e->getMessage() . "\n";
}

// Test sending SMS
echo "\nTesting SMS sending...\n";
$testSmsTo = "+16479795947"; // This will be overridden by the test phone in CommunicationService
$testSmsMessage = "This is a test SMS from the Payment Recovery System. If you are seeing this, SMS sending is working correctly.";

try {
    $smsResult = $communicationService->sendSMS($testSmsTo, $testSmsMessage);
    
    if ($smsResult) {
        echo "SMS sent successfully!\n";
        echo "Check your phone at: " . $communicationService->testPhone . "\n";
    } else {
        echo "Failed to send SMS. Check logs for details.\n";
    }
} catch (Exception $e) {
    echo "Exception caught while sending SMS: " . $e->getMessage() . "\n";
}

echo "\nCommunication tests completed.\n";
?>