<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';
require_once BASE_PATH . '/app/services/ReminderService.php';

// Create reminder service
$reminderService = new ReminderService();

// Send scheduled reminders
$sent = $reminderService->sendScheduledReminders();

echo "Sent {$sent} reminders.\n";
?>