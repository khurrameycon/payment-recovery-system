<?php
// File: setup/phase2_init.php

define('BASE_PATH', dirname(__DIR__));

// Include configuration
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Include required services
require_once BASE_PATH . '/app/services/ErrorHandler.php';
require_once BASE_PATH . '/app/services/TokenManager.php';
require_once BASE_PATH . '/app/services/SegmentationEngine.php';
require_once BASE_PATH . '/app/services/WhatsAppService.php';
require_once BASE_PATH . '/app/middleware/AuthMiddleware.php';

echo "=== Payment Recovery System - Phase 2 Setup ===\n";

try {
    // Create database tables
    echo "Creating database tables...\n";
    $db = getDbConnection();
    
    // Create authentication tables
    echo "  - Setting up authentication tables...\n";
    AuthMiddleware::createTables();
    
    // Create token tables
    echo "  - Setting up token management tables...\n";
    TokenManager::createTables();
    
    // Create error logs table
    echo "  - Setting up error logging tables...\n";
    ErrorHandler::createTables();
    
    // Create customer segmentation tables
    echo "  - Setting up customer segmentation tables...\n";
    
    // Create sql directory if it doesn't exist
    $sqlDir = BASE_PATH . '/setup/sql';
    if (!is_dir($sqlDir)) {
        mkdir($sqlDir, 0755, true);
        echo "    Created SQL directory at {$sqlDir}\n";
    }
    
    // Write segmentation SQL file
    $segmentationSqlFile = $sqlDir . '/segmentation.sql';
    $segmentationSql = "
-- Create customer_segmentation table if it doesn't exist
CREATE TABLE IF NOT EXISTS `customer_segmentation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `value_segment` varchar(50) NOT NULL DEFAULT 'unknown',
  `loyalty_segment` varchar(50) NOT NULL DEFAULT 'new',
  `behavior_segment` varchar(50) NOT NULL DEFAULT 'unknown',
  `combined_segment` varchar(50) NOT NULL DEFAULT 'standard',
  `metrics` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  KEY `combined_segment` (`combined_segment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add holidays table for better timezone intelligence
CREATE TABLE IF NOT EXISTS `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'US',
  PRIMARY KEY (`id`),
  KEY `holiday_date` (`holiday_date`),
  KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add segment_strategies table for customizable communication rules
CREATE TABLE IF NOT EXISTS `segment_strategies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segment` varchar(50) NOT NULL,
  `primary_channel` varchar(50) NOT NULL DEFAULT 'email',
  `fallback_channel` varchar(50) DEFAULT NULL,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `min_hours_between` int(11) NOT NULL DEFAULT 24,
  `preferred_time` varchar(50) DEFAULT NULL,
  `template_set` varchar(50) DEFAULT 'standard',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segment` (`segment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default segment strategies
INSERT INTO `segment_strategies` 
(`segment`, `primary_channel`, `fallback_channel`, `max_attempts`, `min_hours_between`, `preferred_time`, `template_set`, `active`)
VALUES
('vip', 'email', 'sms', 5, 24, 'business_hours', 'premium', 1),
('high_priority', 'email', 'sms', 4, 24, 'business_hours', 'personalized', 1),
('standard', 'email', NULL, 3, 24, 'business_hours', 'standard', 1),
('nurture', 'email', NULL, 4, 48, 'business_hours', 'educational', 1),
('low_priority', 'email', NULL, 2, 48, 'business_hours', 'basic', 1);";

    file_put_contents($segmentationSqlFile, $segmentationSql);
    
    // Execute segmentation SQL
    $statements = explode(';', $segmentationSql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->query($statement);
            if ($db->error) {
                throw new Exception("SQL Error: " . $db->error);
            }
        }
    }
    
    // Update customers table
    echo "  - Updating customers table...\n";
    // Test if columns exist before adding them
    $result = $db->query("SHOW COLUMNS FROM `customers` LIKE 'timezone'");
    if ($result->num_rows === 0) {
        $db->query("ALTER TABLE `customers` ADD COLUMN `timezone` varchar(50) DEFAULT 'UTC'");
    }
    
    $result = $db->query("SHOW COLUMNS FROM `customers` LIKE 'country'");
    if ($result->num_rows === 0) {
        $db->query("ALTER TABLE `customers` ADD COLUMN `country` char(2) DEFAULT 'US'");
    }
    
    $result = $db->query("SHOW COLUMNS FROM `customers` LIKE 'first_name'");
    if ($result->num_rows === 0) {
        $db->query("ALTER TABLE `customers` ADD COLUMN `first_name` varchar(100) DEFAULT NULL");
    }
    
    $result = $db->query("SHOW COLUMNS FROM `customers` LIKE 'last_name'");
    if ($result->num_rows === 0) {
        $db->query("ALTER TABLE `customers` ADD COLUMN `last_name` varchar(100) DEFAULT NULL");
    }
    
    $result = $db->query("SHOW COLUMNS FROM `customers` LIKE 'segment'");
    if ($result->num_rows === 0) {
        $db->query("ALTER TABLE `customers` ADD COLUMN `segment` varchar(50) DEFAULT 'standard'");
    } else {
        $db->query("ALTER TABLE `customers` MODIFY COLUMN `segment` varchar(50) DEFAULT 'standard'");
    }
    
    // Add indexes
    echo "  - Adding indexes for performance...\n";
    $db->query("CREATE INDEX IF NOT EXISTS `idx_customers_segment` ON `customers` (`segment`)");
    $db->query("CREATE INDEX IF NOT EXISTS `idx_failed_transactions_customer_id` ON `failed_transactions` (`customer_id`)");
    $db->query("CREATE INDEX IF NOT EXISTS `idx_communication_attempts_transaction_id` ON `communication_attempts` (`transaction_id`)");
    
    // Create WhatsApp tables
    echo "  - Setting up WhatsApp integration tables...\n";
    WhatsAppService::createTables();
    
    echo "Database tables created successfully.\n";
    
    // Create admin user if it doesn't exist
    echo "Checking for admin user...\n";
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if (!$result) {
        $db->query("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role` varchar(20) NOT NULL DEFAULT 'user',
            `status` varchar(20) NOT NULL DEFAULT 'active',
            `created_at` datetime NOT NULL,
            `updated_at` datetime DEFAULT NULL,
            `last_login` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        
        $result = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    }
    
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        echo "No admin user found. Creating default admin user...\n";
        
        // Generate password
        $password = bin2hex(random_bytes(8)); // 16 character random password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Create admin user
        $stmt = $db->prepare("
            INSERT INTO users 
            (name, email, password, role, status, created_at, updated_at) 
            VALUES (?, ?, ?, 'admin', 'active', NOW(), NOW())
        ");
        
        $name = "Admin User";
        $email = "admin@example.com";
        
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $stmt->execute();
        
        echo "Admin user created successfully.\n";
        echo "Email: {$email}\n";
        echo "Password: {$password}\n";
        echo "IMPORTANT: Please change this password after logging in.\n";
    } else {
        echo "Admin user already exists.\n";
    }
    
    // Ensure SegmentationEngine class can be accessed
    if (class_exists('SegmentationEngine')) {
        // Initialize segmentation engine
        echo "Initializing customer segmentation engine...\n";
        $segmentationEngine = new SegmentationEngine();
        $updatedCount = $segmentationEngine->reanalyzeAllCustomers();
        echo "Analyzed and segmented {$updatedCount} customer(s).\n";
    } else {
        echo "Warning: SegmentationEngine class not found. Skipping customer segmentation.\n";
    }
    
    // Create log directory if it doesn't exist
    echo "Setting up log directory...\n";
    $logDir = BASE_PATH . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
        echo "Log directory created at {$logDir}\n";
    } else {
        echo "Log directory already exists.\n";
    }
    
    // Update configuration file with security settings
    echo "Updating security configuration...\n";
    $securityConfig = <<<EOT
<?php
// Security configuration for Payment Recovery System

// CSRF Protection
define('CSRF_PROTECTION_ENABLED', true);

// Recovery link protection
define('RECOVERY_SALT', '{{SALT}}');

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Password policies
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Error reporting in production
define('PRODUCTION_MODE', false); // Set to true in production

// Rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_MAX_REQUESTS', 100);
define('RATE_LIMIT_PERIOD', 60); // 1 minute
EOT;

    // Generate unique salt
    $salt = bin2hex(random_bytes(16));
    $securityConfig = str_replace('{{SALT}}', $salt, $securityConfig);
    
    // Create config directory if it doesn't exist
    $configDir = BASE_PATH . '/config';
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
        echo "Config directory created at {$configDir}\n";
    }
    
    // Save to config file
    file_put_contents($configDir . '/security.php', $securityConfig);
    echo "Security configuration updated.\n";
    
    echo "\nPhase 2 setup completed successfully! ✓\n";
    echo "Your payment recovery system now has:\n";
    echo "  ✓ Enhanced security with proper authentication\n";
    echo "  ✓ Customer segmentation engine\n";
    echo "  ✓ Multi-channel communication\n";
    echo "  ✓ WhatsApp integration\n";
    echo "  ✓ Comprehensive error handling\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Setup failed. Please check the error and try again.\n";
    exit(1);
}