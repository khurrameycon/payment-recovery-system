<?php
// Start session
session_start();

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Basic routing
$route = $_GET['route'] ?? 'home';

switch ($route) {
    case 'login':
        require_once BASE_PATH . '/app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->showLogin();
        break;
    
    case 'process-login':
        require_once BASE_PATH . '/app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->processLogin();
        break;
    
    case 'logout':
        require_once BASE_PATH . '/app/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case 'home':
        require_once BASE_PATH . '/app/controllers/HomeController.php';
        $controller = new HomeController();
        $controller->index();
        break;
    
    case 'failed-transactions':
        require_once BASE_PATH . '/app/controllers/TransactionController.php';
        $controller = new TransactionController();
        $controller->listFailed();
        break;
    
    case 'fetch-from-nmi':
        require_once BASE_PATH . '/app/controllers/TransactionController.php';
        $controller = new TransactionController();
        $controller->fetchFromNmi();
        break;
    
    case 'create-recovery-links':
        require_once BASE_PATH . '/app/controllers/TransactionController.php';
        $controller = new TransactionController();
        $controller->createRecoveryLinks();
        break;
    
    case 'send-reminder':
        require_once BASE_PATH . '/app/controllers/ReminderController.php';
        $controller = new ReminderController();
        $controller->sendReminder();
        break;
    
    case 'process-reminders':
        require_once BASE_PATH . '/app/controllers/ReminderController.php';
        $controller = new ReminderController();
        $controller->processScheduled();
        break;
    
    case 'track-open':
        require_once BASE_PATH . '/app/controllers/ReminderController.php';
        $controller = new ReminderController();
        $controller->trackOpen();
        break;
    
    case 'track-click':
        require_once BASE_PATH . '/app/controllers/ReminderController.php';
        $controller = new ReminderController();
        $controller->trackClick();
        break;
    
    case 'recover':
        require_once BASE_PATH . '/app/controllers/RecoveryController.php';
        $controller = new RecoveryController();
        $controller->showPaymentPage();
        break;
    
    case 'process-payment':
        require_once BASE_PATH . '/app/controllers/RecoveryController.php';
        $controller = new RecoveryController();
        $controller->processPayment();
        break;
    
    // Add these cases to your switch statement
    case 'dashboard':
        require_once BASE_PATH . '/app/controllers/ReportController.php';
        $controller = new ReportController();
        $controller->dashboard();
        break;

    case 'reminder-report':
        require_once BASE_PATH . '/app/controllers/ReportController.php';
        $controller = new ReportController();
        $controller->reminderReport();
        break;
    
    case 'view-transaction':
        require_once BASE_PATH . '/app/controllers/TransactionController.php';
        $controller = new TransactionController();
        $controller->viewTransaction();
        break;
    
    case 'advanced-analytics':
        require_once BASE_PATH . '/app/controllers/ReportController.php';
        $controller = new ReportController();
        $controller->advancedAnalytics();
        break;
    

    case 'test-transaction':
        require_once BASE_PATH . '/app/controllers/TransactionController.php';
        $controller = new TransactionController();
        $controller->testSpecificTransaction();
        break;
    
        
    // Default route
    default:
        echo "404 - Not Found";
        break;
}
?>