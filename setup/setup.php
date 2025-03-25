<?php
// File: setup/setup.php

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration files
require_once BASE_PATH . '/config/database.php';

// Check if tables already exist
function tablesExist($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    return $result->num_rows > 0;
}

// Initialize database
function initializeDatabase() {
    $conn = getDbConnection();
    
    // Check if database already initialized
    if (tablesExist($conn)) {
        echo "Database is already initialized.\n";
        echo "To reinitialize, please drop all tables first.\n";
        return false;
    }
    
    // Read SQL from install.sql file
    $sqlFile = BASE_PATH . '/setup/sql/install.sql';
    
    if (!file_exists($sqlFile)) {
        echo "SQL file not found: $sqlFile\n";
        
        // Create the directory if it doesn't exist
        if (!is_dir(dirname($sqlFile))) {
            mkdir(dirname($sqlFile), 0755, true);
        }
        
        // Create the SQL file with the content from the detailed fix recommendations
        $sqlContent = "-- Install script for Payment Recovery System\n";
        // Add all the CREATE TABLE statements here from the first fix recommendation
        
        file_put_contents($sqlFile, $sqlContent);
        echo "Created SQL file: $sqlFile\n";
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute each SQL statement
    $statements = explode(';', $sql);
    $success = true;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                echo "Error executing SQL: " . $conn->error . "\n";
                $success = false;
            }
        }
    }
    
    if ($success) {
        echo "Database initialized successfully.\n";
        
        // Create default admin user
        createDefaultAdmin($conn);
        
        return true;
    } else {
        echo "Database initialization failed.\n";
        return false;
    }
}

// Create default admin user
function createDefaultAdmin($conn) {
    $name = "Admin User";
    $email = "admin@example.com";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO users 
        (name, email, password, role, status, created_at, updated_at) 
        VALUES (?, ?, ?, 'admin', 'active', ?, ?)
    ");
    
    $stmt->bind_param("sssss", $name, $email, $password, $now, $now);
    
    if ($stmt->execute()) {
        echo "Default admin user created: $email (password: admin123)\n";
        return true;
    } else {
        echo "Failed to create default admin user: " . $stmt->error . "\n";
        return false;
    }
}

// Run the initialization
if (php_sapi_name() === 'cli') {
    // Running from command line
    initializeDatabase();
} else {
    // Running from browser
    echo "<html><body><pre>";
    $result = initializeDatabase();
    echo "</pre>";
    
    if ($result) {
        echo "<p>Setup completed successfully. <a href='../public/index.php'>Go to application</a></p>";
    } else {
        echo "<p>Setup failed. Please check the error messages above.</p>";
    }
    echo "</body></html>";
}