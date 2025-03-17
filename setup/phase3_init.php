<?php
// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Execute SQL script for multi-tenant tables
$db = getDbConnection();

// Create multi-tenant tables
echo "Creating multi-tenant tables...\n";
$multiTenantSQL = file_get_contents(__DIR__ . '/sql/multi_tenant.sql');
if ($multiTenantSQL) {
    $statements = explode(';', $multiTenantSQL);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->query($statement);
        }
    }
}

// Create subscription tables
echo "Creating subscription tables...\n";
require_once BASE_PATH . '/app/services/SubscriptionService.php';
SubscriptionService::createTables();

echo "Phase 3 setup completed.\n";