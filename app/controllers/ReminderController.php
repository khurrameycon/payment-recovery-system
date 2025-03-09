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
        // Send scheduled reminders
        $sent = $this->reminderService->sendScheduledReminders();
        
        echo "Sent {$sent} reminders.";
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
}
?>