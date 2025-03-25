<?php
require_once BASE_PATH . '/app/services/ReminderService.php';
require_once BASE_PATH . '/app/models/Transaction.php';

class ReminderController {
    private $reminderService;
    private $transactionModel;
    
    public function __construct() {
        $this->reminderService = new ReminderService();
        $this->transactionModel = new Transaction();
    }
    
    public function sendReminder() {
        // Get transaction ID from request
        $transactionId = $_GET['id'] ?? 0;
        
        if (!$transactionId) {
            $_SESSION['error'] = "No transaction specified";
            header('Location: index.php?route=failed-transactions');
            exit;
        }
        
        // Get channel from request (default to email)
        $channel = $_GET['channel'] ?? 'email';
        
        // Schedule reminder
        $result = $this->reminderService->scheduleReminder($transactionId, $channel);
        
        if ($result) {
            $_SESSION['message'] = "Reminder scheduled successfully";
        } else {
            $_SESSION['error'] = "Failed to schedule reminder. Maximum reminders might have been reached.";
        }
        
        // Redirect back to transaction list
        header('Location: index.php?route=failed-transactions');
        exit;
    }
    
    public function processScheduled() {
        // Force one reminder to be due now for testing
        $db = getDbConnection(); // Get the database connection
        
        $updateSql = "UPDATE communication_attempts 
                     SET scheduled_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) 
                     WHERE status = 'scheduled' 
                     LIMIT 5";
                     
        $db->query($updateSql);
        error_log("Updated one reminder to be due now for testing");
        
        // Log the start time
        $startTime = microtime(true);
        
        // Send scheduled reminders
        $sent = $this->reminderService->sendScheduledReminders();
        
        // Calculate execution time
        $executionTime = round(microtime(true) - $startTime, 2);
        
        // Output results
        echo "<h1>Reminder Processing Results</h1>";
        echo "<p>Processed at: " . date('Y-m-d H:i:s') . "</p>";
        echo "<p>Sent {$sent} reminders.</p>";
        echo "<p>Execution time: {$executionTime} seconds</p>";
        echo "<p><a href='index.php?route=dashboard'>Back to Dashboard</a></p>";
    }
    
    public function trackOpen() {
        // Get tracking ID from request
        $trackingId = $_GET['id'] ?? '';
        
        if ($trackingId) {
            // Update status to opened
            $this->updateReminderStatus($trackingId, 'opened');
        }
        
        // Return a 1x1 transparent GIF
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
    
    public function trackClick() {
        // Get tracking ID and redirect URL from request
        $trackingId = $_GET['id'] ?? '';
        $redirectUrl = $_GET['url'] ?? '';
        
        if ($trackingId) {
            // Update status to clicked
            $this->updateReminderStatus($trackingId, 'clicked');
        }
        
        // Redirect to the original URL
        if ($redirectUrl) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        // If no redirect URL, go to home
        header('Location: index.php');
        exit;
    }
    
    private function updateReminderStatus($trackingId, $status) {
        $conn = getDbConnection();
        $stmt = $conn->prepare("UPDATE communication_attempts SET status = ?, {$status}_at = NOW() WHERE tracking_id = ?");
        $stmt->bind_param("ss", $status, $trackingId);
        $stmt->execute();
    }


    // Add to app/controllers/ReminderController.php
public function scheduleSmartReminder() {
    // Get transaction ID from request
    $transactionId = $_GET['id'] ?? 0;
    
    if (!$transactionId) {
        $_SESSION['error'] = "No transaction specified";
        header('Location: index.php?route=failed-transactions');
        exit;
    }
    
    // Get channel from request (default to smart)
    $channel = $_GET['channel'] ?? 'smart';
    
    // Create options array
    $options = [
        'channel' => $channel,
        'send_now' => isset($_GET['send_now']) && $_GET['send_now'] == 1
    ];
    
    // Schedule the reminder
    $result = $this->reminderService->scheduleSmartReminder($transactionId, $options);
    
    if ($result['success']) {
        $_SESSION['message'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    // Redirect back to transaction details or list
    if (isset($_GET['return']) && $_GET['return'] == 'details') {
        header('Location: index.php?route=view-transaction&id=' . $transactionId);
    } else {
        header('Location: index.php?route=failed-transactions');
    }
    exit;
}
}
?>