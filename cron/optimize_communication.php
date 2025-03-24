<?php
// File: cron/optimize_communication.php

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration files
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/api.php';

// Include required services
require_once BASE_PATH . '/app/services/SegmentationEngine.php';
require_once BASE_PATH . '/app/services/TimeOptimizationService.php';

// Get command line options
$options = getopt("f:t:s:h", ["force", "task:", "segment:", "help"]);

// Show help if requested
if (isset($options['h']) || isset($options['help'])) {
    echo "Usage: php optimize_communication.php [options]\n";
    echo "Options:\n";
    echo "  -f, --force       Force update even if already run today\n";
    echo "  -t, --task=TASK   Task to run (all, segment, timezone, holidays)\n";
    echo "  -s, --segment=SEG Segment to optimize (all, vip, high_priority, standard, etc.)\n";
    echo "  -h, --help        Show this help\n";
    exit(0);
}

// Set default task
$task = $options['t'] ?? $options['task'] ?? 'all';
$segment = $options['s'] ?? $options['segment'] ?? 'all';
$force = isset($options['f']) || isset($options['force']);

echo "=== Payment Recovery System - Communication Optimization ===\n";
echo "Running task: $task\n";
if ($segment !== 'all') {
    echo "For segment: $segment\n";
}

// Check if task was already run today (unless forced)
if (!$force) {
    $db = getDbConnection();
    $result = $db->query("SELECT COUNT(*) as count FROM system_tasks WHERE task_name = '{$task}' AND DATE(run_date) = CURDATE()");
    if ($result && $result->fetch_assoc()['count'] > 0) {
        echo "Task already run today. Use -f to force re-run.\n";
        exit(0);
    }
}

$startTime = microtime(true);

// Run tasks based on selection
try {
    if ($task === 'all' || $task === 'segment') {
        echo "Running customer segmentation...\n";
        $segmentationEngine = new SegmentationEngine();
        
        if ($segment === 'all') {
            $result = $segmentationEngine->performBulkSegmentation();
            echo "Analyzed and segmented {$result['updated']} of {$result['total']} customers.\n";
        } else {
            // Future implementation: segment-specific optimization
            echo "Segment-specific optimization not implemented yet.\n";
        }
    }
    
    if ($task === 'all' || $task === 'timezone') {
        echo "Optimizing timezone settings...\n";
        // This is where you would add code to update timezone settings
        echo "Timezone optimization is part of the segmentation process.\n";
    }
    
    if ($task === 'all' || $task === 'holidays') {
        echo "Updating holiday database...\n";
        $timeOptimizer = new TimeOptimizationService();
        $count = $timeOptimizer->updateHolidayDatabase();
        echo "Added {$count} holidays to the database.\n";
    }
    
    // Log successful completion
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO system_tasks (task_name, status, duration, details) VALUES (?, 'completed', ?, ?)");
    $duration = round(microtime(true) - $startTime, 2);
    $details = json_encode(['task' => $task, 'segment' => $segment, 'forced' => $force]);
    $stmt->bind_param("sds", $task, $duration, $details);
    $stmt->execute();
    
    echo "Optimization completed successfully in {$duration} seconds.\n";
    
} catch (Exception $e) {
    // Log error
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO system_tasks (task_name, status, error_message, details) VALUES (?, 'failed', ?, ?)");
    $errorMsg = $e->getMessage();
    $details = json_encode(['task' => $task, 'segment' => $segment, 'forced' => $force, 'error' => $errorMsg]);
    $stmt->bind_param("sss", $task, $errorMsg, $details);
    $stmt->execute();
    
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}