<?php
// File: app/services/ErrorHandler.php

class ErrorHandler {
    private static $instance;
    private $logDir;
    private $logFile;
    private $db;
    private $isProduction;
    private $contextData;
    
    /**
     * Initialize error handler
     * 
     * @param string $logDir Directory for log files
     * @param bool $isProduction Whether we're in production mode
     */
    private function __construct($logDir = null, $isProduction = false) {
        // Set log directory
        $this->logDir = $logDir ?: BASE_PATH . '/logs';
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Set log file for today
        $this->logFile = $this->logDir . '/error-' . date('Y-m-d') . '.log';
        
        // Set production flag
        $this->isProduction = $isProduction;
        
        // Initialize database connection
        try {
            $this->db = getDbConnection();
        } catch (Exception $e) {
            // Log to file if DB connection fails
            $this->logToFile('CRITICAL', 'Failed to connect to database: ' . $e->getMessage());
        }
        
        // Initialize context data
        $this->contextData = [
            'request_id' => uniqid(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Add user ID to context if logged in
        if (isset($_SESSION['user_id'])) {
            $this->contextData['user_id'] = $_SESSION['user_id'];
        }
    }
    
    /**
     * Get singleton instance
     * 
     * @param string $logDir Directory for log files
     * @param bool $isProduction Whether we're in production mode
     * @return ErrorHandler Instance
     */
    public static function getInstance($logDir = null, $isProduction = false) {
        if (self::$instance === null) {
            self::$instance = new self($logDir, $isProduction);
        }
        
        return self::$instance;
    }
    
    /**
     * Register error handler
     */
    public function register() {
        // Set error handler
        set_error_handler([$this, 'handleError']);
        
        // Set exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Register shutdown function
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Handle PHP errors
     * 
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile File where error occurred
     * @param int $errline Line where error occurred
     * @return bool Whether error was handled
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        // Don't handle errors if they're suppressed with @
        if (error_reporting() === 0) {
            return false;
        }
        
        // Map error level to severity
        $severity = $this->mapErrorLevelToSeverity($errno);
        
        // Format message
        $message = sprintf('%s in %s on line %d', $errstr, $errfile, $errline);
        
        // Log error
        $this->log($severity, $message, [
            'error_type' => 'php_error',
            'error_code' => $errno,
            'file' => $errfile,
            'line' => $errline
        ]);
        
        // Don't execute PHP's internal error handler
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     * 
     * @param Throwable $exception The exception
     */
    public function handleException($exception) {
        // Format message
        $message = sprintf(
            'Uncaught %s: %s in %s on line %d',
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
        
        // Log exception
        $this->log('ERROR', $message, [
            'error_type' => 'exception',
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
        
        // Display error page in production, or exception details in development
        $this->displayError($exception);
    }
    
    /**
     * Handle fatal errors
     */
    public function handleShutdown() {
        // Get last error
        $error = error_get_last();
        
        // Only handle fatal errors
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Format message
            $message = sprintf(
                'Fatal error: %s in %s on line %d',
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            // Log error
            $this->log('CRITICAL', $message, [
                'error_type' => 'fatal_error',
                'error_code' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);
            
            // Display error page
            if ($this->isProduction) {
                $this->displayErrorPage(500, 'Internal Server Error');
            } else {
                // Display detailed error in development
                $this->displayDetailedError($message, $error['file'], $error['line']);
            }
        }
    }
    
    /**
     * Log message with context
     * 
     * @param string $severity Error severity
     * @param string $message Error message
     * @param array $context Additional context
     */
    public function log($severity, $message, array $context = []) {
        // Merge with global context
        $context = array_merge($this->contextData, $context);
        
        // Log to database
        $this->logToDatabase($severity, $message, $context);
        
        // Log to file
        $this->logToFile($severity, $message, $context);
    }
    
    /**
     * Log message to database
     * 
     * @param string $severity Error severity
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function logToDatabase($severity, $message, array $context = []) {
        // Skip if no database connection
        if (!$this->db) {
            return;
        }
        
        try {
            // Prepare statement
            $stmt = $this->db->prepare("
                INSERT INTO error_logs 
                (severity, message, context_data, user_id, ip_address, url, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            // Get context values
            $userId = $context['user_id'] ?? null;
            $ip = $context['ip'] ?? null;
            $url = $context['url'] ?? null;
            $userAgent = $context['user_agent'] ?? null;
            
            // Convert context to JSON
            $contextJson = json_encode($context);
            
            // Bind parameters and execute
            $stmt->bind_param(
                'sssisss',
                $severity,
                $message,
                $contextJson,
                $userId,
                $ip,
                $url,
                $userAgent
            );
            
            $stmt->execute();
        } catch (Exception $e) {
            // Log to file if database logging fails
            $this->logToFile(
                'WARNING',
                'Failed to log to database: ' . $e->getMessage(),
                ['original_message' => $message]
            );
        }
    }
    
    /**
     * Log message to file
     * 
     * @param string $severity Error severity
     * @param string $message Error message
     * @param array $context Additional context
     */
    private function logToFile($severity, $message, array $context = []) {
        // Format log entry
        $logEntry = sprintf(
            "[%s] [%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $severity,
            $context['request_id'] ?? 'unknown',
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        // Append to log file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Display error to user
     * 
     * @param Throwable $exception The exception
     */
    private function displayError($exception) {
        if ($this->isProduction) {
            // In production, show generic error page
            $this->displayErrorPage(500, 'Internal Server Error');
        } else {
            // In development, show detailed error
            $this->displayDetailedError(
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            );
        }
    }
    
    /**
     * Display generic error page
     * 
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     */
    private function displayErrorPage($statusCode, $message) {
        // Set HTTP status code
        http_response_code($statusCode);
        
        // Include error template if exists
        $errorTemplate = BASE_PATH . '/app/views/errors/' . $statusCode . '.php';
        
        if (file_exists($errorTemplate)) {
            include $errorTemplate;
        } else {
            // Default error output
            echo '<!DOCTYPE html>
                <html>
                <head>
                    <title>Error</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                        h1 { color: #e74c3c; }
                        .error-container { max-width: 500px; margin: 0 auto; }
                    </style>
                </head>
                <body>
                    <div class="error-container">
                        <h1>' . $statusCode . ' - ' . $message . '</h1>
                        <p>Sorry, an error has occurred. Please try again later.</p>
                        <p>Reference ID: ' . $this->contextData['request_id'] . '</p>
                    </div>
                </body>
                </html>';
        }
        
        // Stop script execution
        exit;
    }
    
    /**
     * Display detailed error information for development
     * 
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line where error occurred
     * @param string $trace Stack trace
     */
    private function displayDetailedError($message, $file, $line, $trace = null) {
        // Set HTTP status code
        http_response_code(500);
        
        // Output detailed error
        echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Error Details</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { color: #e74c3c; }
                    .error-container { background: #f8f9fa; padding: 20px; border-radius: 5px; }
                    .error-message { color: #e74c3c; font-weight: bold; }
                    .error-location { color: #3498db; }
                    .error-trace { background: #f1f1f1; padding: 10px; overflow-x: auto; font-family: monospace; white-space: pre; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <h1>Error Details</h1>
                    <p class="error-message">' . htmlspecialchars($message) . '</p>
                    <p class="error-location">File: ' . htmlspecialchars($file) . ' on line ' . $line . '</p>';
        
        // Add stack trace if available
        if ($trace) {
            echo '<h3>Stack Trace:</h3>
                  <div class="error-trace">' . htmlspecialchars($trace) . '</div>';
        }
        
        echo '  <p>Reference ID: ' . $this->contextData['request_id'] . '</p>
                </div>
            </body>
            </html>';
        
        // Stop script execution
        exit;
    }
    
    /**
     * Map PHP error level to severity string
     * 
     * @param int $level PHP error level
     * @return string Severity string
     */
    private function mapErrorLevelToSeverity($level) {
        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                return 'CRITICAL';
            
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                return 'ERROR';
            
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                return 'WARNING';
            
            case E_NOTICE:
            case E_USER_NOTICE:
                return 'NOTICE';
            
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                return 'INFO';
            
            default:
                return 'DEBUG';
        }
    }
    
    /**
     * Add extra context data
     * 
     * @param array $data Context data to add
     */
    public function addContext(array $data) {
        $this->contextData = array_merge($this->contextData, $data);
    }
    
    /**
     * Create error log table in database
     */
    public static function createTables() {
        $db = getDbConnection();
        
        // Error logs table
        $sql = "CREATE TABLE IF NOT EXISTS `error_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `severity` varchar(20) NOT NULL,
            `message` text NOT NULL,
            `context_data` json DEFAULT NULL,
            `user_id` int(11) DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `url` varchar(255) DEFAULT NULL,
            `user_agent` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`id`),
            KEY `severity` (`severity`),
            KEY `user_id` (`user_id`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $db->query($sql);
    }
    
    /**
     * Generate a secure token
     * 
     * @param int $length Token length
     * @return string Secure token
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Create a secure hash for payment recovery links
     * 
     * @param int $transactionId Transaction ID
     * @param string $email Customer email
     * @param float $amount Transaction amount
     * @return string Secure hash
     */
    public static function createRecoveryHash($transactionId, $email, $amount) {
        // Get salt from configuration
        $salt = defined('RECOVERY_SALT') ? RECOVERY_SALT : 'default-salt-change-me';
        
        // Create data string
        $data = $transactionId . '|' . $email . '|' . $amount . '|' . $salt;
        
        // Return hash
        return hash('sha256', $data);
    }
    
    /**
     * Validate input data
     * 
     * @param mixed $data Data to validate
     * @param string $type Validation type
     * @param array $options Validation options
     * @return mixed Validated data or null if invalid
     */
    public static function validateInput($data, $type, array $options = []) {
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL) ? $data : null;
            
            case 'int':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                
                $options = [];
                if ($min !== null) $options['min_range'] = $min;
                if ($max !== null) $options['max_range'] = $max;
                
                return filter_var($data, FILTER_VALIDATE_INT, ['options' => $options]) !== false ? (int)$data : null;
            
            case 'float':
                $min = $options['min'] ?? null;
                $max = $options['max'] ?? null;
                
                $validated = filter_var($data, FILTER_VALIDATE_FLOAT);
                
                if ($validated === false) {
                    return null;
                }
                
                $validated = (float)$validated;
                
                if ($min !== null && $validated < $min) {
                    return null;
                }
                
                if ($max !== null && $validated > $max) {
                    return null;
                }
                
                return $validated;
            
            case 'url':
                return filter_var($data, FILTER_VALIDATE_URL) ? $data : null;
            
            case 'date':
                $format = $options['format'] ?? 'Y-m-d';
                
                $date = DateTime::createFromFormat($format, $data);
                return ($date && $date->format($format) === $data) ? $data : null;
            
            case 'phone':
                // Basic phone validation - at least 10 digits
                return preg_match('/^\+?[0-9]{10,15}$/', $data) ? $data : null;
            
            case 'alphanum':
                return preg_match('/^[a-zA-Z0-9]+$/', $data) ? $data : null;
            
            case 'text':
                $minLength = $options['min_length'] ?? 0;
                $maxLength = $options['max_length'] ?? null;
                
                $length = strlen($data);
                
                if ($length < $minLength) {
                    return null;
                }
                
                if ($maxLength !== null && $length > $maxLength) {
                    return null;
                }
                
                // Strip potentially dangerous tags if requested
                if (!empty($options['strip_tags'])) {
                    $data = strip_tags($data);
                }
                
                return $data;
                
            default:
                return null;
        }
    }
}