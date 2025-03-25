<?php
// File: public/system-check.php

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Check PHP version
$phpVersionOk = version_compare(PHP_VERSION, '7.4.0', '>=');

// Check database connection
try {
    $db = getDbConnection();
    $dbConnectionOk = true;
    
    // Check if tables exist
    $tableResults = [];
    $requiredTables = [
        'users', 'customers', 'failed_transactions', 'payment_recovery',
        'communication_attempts', 'organizations', 'segment_strategies',
        'customer_segmentation', 'organization_branding', 'api_access_tokens'
    ];
    
    foreach ($requiredTables as $table) {
        $result = $db->query("SHOW TABLES LIKE '{$table}'");
        $tableResults[$table] = $result->num_rows > 0;
    }
    
} catch (Exception $e) {
    $dbConnectionOk = false;
    $dbError = $e->getMessage();
}

// Check directory permissions
$directoryChecks = [
    'logs' => is_writable(BASE_PATH . '/logs'),
    'public/uploads' => is_writable(BASE_PATH . '/public/uploads'),
    'public/uploads/logos' => is_dir(BASE_PATH . '/public/uploads/logos') ? 
        is_writable(BASE_PATH . '/public/uploads/logos') : 
        is_writable(BASE_PATH . '/public/uploads'),
    'public/uploads/favicons' => is_dir(BASE_PATH . '/public/uploads/favicons') ? 
        is_writable(BASE_PATH . '/public/uploads/favicons') : 
        is_writable(BASE_PATH . '/public/uploads')
];

// Create directories if they don't exist
foreach ($directoryChecks as $dir => $status) {
    if (!$status) {
        $fullPath = BASE_PATH . '/' . $dir;
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
            $directoryChecks[$dir] = is_writable($fullPath);
        }
    }
}

// Check required PHP extensions
$requiredExtensions = ['mysqli', 'curl', 'json', 'mbstring', 'xml'];
$extensionChecks = [];

foreach ($requiredExtensions as $ext) {
    $extensionChecks[$ext] = extension_loaded($ext);
}

// Check API configurations
$apiChecks = [
    'NMI API URL' => !empty(NMI_API_URL),
    'NMI API Key' => !empty(NMI_API_KEY)
];

// Output results as HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check | Payment Recovery System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .check-item {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
        }
        
        .check-success {
            background-color: #d1e7dd;
            border-left: 4px solid #198754;
        }
        
        .check-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .check-error {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <h1>Payment Recovery System - System Check</h1>
        <p class="lead">This page checks if your system meets all requirements for running the application.</p>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">PHP Environment</h5>
            </div>
            <div class="card-body">
                <div class="check-item <?php echo $phpVersionOk ? 'check-success' : 'check-error'; ?>">
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                    <?php if (!$phpVersionOk): ?>
                        <span class="text-danger">PHP 7.4.0 or higher is required.</span>
                    <?php endif; ?>
                </div>
                
                <h6 class="mt-3">Required Extensions:</h6>
                <?php foreach ($extensionChecks as $ext => $loaded): ?>
                    <div class="check-item <?php echo $loaded ? 'check-success' : 'check-error'; ?>">
                        <strong><?php echo $ext; ?>:</strong>
                        <?php echo $loaded ? 'Loaded' : 'Not loaded'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Database Connection</h5>
            </div>
            <div class="card-body">
                <div class="check-item <?php echo $dbConnectionOk ? 'check-success' : 'check-error'; ?>">
                    <strong>Connection:</strong>
                    <?php echo $dbConnectionOk ? 'Connected successfully' : 'Connection failed: ' . $dbError; ?>
                </div>
                
                <?php if ($dbConnectionOk): ?>
                    <h6 class="mt-3">Required Tables:</h6>
                    <?php foreach ($tableResults as $table => $exists): ?>
                        <div class="check-item <?php echo $exists ? 'check-success' : 'check-error'; ?>">
                            <strong><?php echo $table; ?>:</strong>
                            <?php echo $exists ? 'Exists' : 'Missing'; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Directory Permissions</h5>
            </div>
            <div class="card-body">
                <?php foreach ($directoryChecks as $dir => $writable): ?>
                    <div class="check-item <?php echo $writable ? 'check-success' : 'check-error'; ?>">
                        <strong><?php echo $dir; ?>:</strong>
                        <?php echo $writable ? 'Writable' : 'Not writable'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">API Configuration</h5>
            </div>
            <div class="card-body">
                <?php foreach ($apiChecks as $api => $configured): ?>
                    <div class="check-item <?php echo $configured ? 'check-success' : 'check-warning'; ?>">
                        <strong><?php echo $api; ?>:</strong>
                        <?php echo $configured ? 'Configured' : 'Not configured'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <?php if ($phpVersionOk && $dbConnectionOk && !in_array(false, $extensionChecks) && !in_array(false, $directoryChecks)): ?>
                <div class="alert alert-success">
                    <h4 class="alert-heading">All checks passed!</h4>
                    <p>Your system meets all requirements to run the Payment Recovery System.</p>
                </div>
                <a href="index.php" class="btn btn-primary">Go to Application</a>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h4 class="alert-heading">Some checks failed</h4>
                    <p>Please fix the issues highlighted above before running the application.</p>
                </div>
                <a href="system-check.php" class="btn btn-primary">Re-run Checks</a>
            <?php endif; ?>
            
            <?php if ($dbConnectionOk && in_array(false, $tableResults)): ?>
                <a href="../setup/setup.php" class="btn btn-warning ms-2">Run Database Setup</a>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>