<?php
class ReportController {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    public function dashboard() {
        // Get summary statistics
        $stats = $this->getStats();
        
        // Get recent reminders
        $reminders = $this->getRecentReminders();
        
        // Get recovery rates by channel
        $recoveryRates = $this->getRecoveryRatesByChannel();
        
        include BASE_PATH . '/app/views/dashboard.php';
    }
    
    public function reminderReport() {
        // Get start and end dates from request
        $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Get reminder stats
        $reminderStats = $this->getReminderStats($startDate, $endDate);
        
        include BASE_PATH . '/app/views/reminder_report.php';
    }
    
    private function getStats() {
        // Total failed transactions
        $result = $this->db->query("SELECT COUNT(*) as count FROM failed_transactions");
        $stats['total_failed'] = $result->fetch_assoc()['count'];
        
        // Total recovered transactions
        $result = $this->db->query("SELECT COUNT(*) as count FROM failed_transactions WHERE recovery_status = 'recovered'");
        $stats['total_recovered'] = $result->fetch_assoc()['count'];
        
        // Recovery rate
        $stats['recovery_rate'] = $stats['total_failed'] > 0 ? 
            round(($stats['total_recovered'] / $stats['total_failed']) * 100, 2) : 0;
        
        // Total amount recovered
        $result = $this->db->query("SELECT SUM(recovered_amount) as total FROM payment_recovery WHERE status = 'completed'");
        $stats['total_amount_recovered'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total reminders sent
        $result = $this->db->query("SELECT COUNT(*) as count FROM communication_attempts WHERE status != 'scheduled'");
        $stats['total_reminders_sent'] = $result->fetch_assoc()['count'];
        
        // Email open rate
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'opened' OR status = 'clicked' THEN 1 ELSE 0 END) as opened
            FROM communication_attempts 
            WHERE channel = 'email' AND status != 'scheduled'
        ");
        $emailStats = $result->fetch_assoc();
        $stats['email_open_rate'] = $emailStats['total'] > 0 ? 
            round(($emailStats['opened'] / $emailStats['total']) * 100, 2) : 0;
        
        return $stats;
    }
    
    private function getRecentReminders() {
        $sql = "
            SELECT ca.*, ft.amount, c.email, c.phone 
            FROM communication_attempts ca
            JOIN failed_transactions ft ON ca.transaction_id = ft.id
            JOIN customers c ON ft.customer_id = c.id
            WHERE ca.status != 'scheduled'
            ORDER BY ca.sent_at DESC
            LIMIT 10
        ";
        
        $result = $this->db->query($sql);
        $reminders = [];
        
        while ($row = $result->fetch_assoc()) {
            $reminders[] = $row;
        }
        
        return $reminders;
    }
    
    private function getRecoveryRatesByChannel() {
        $sql = "
            SELECT 
                ca.channel,
                COUNT(DISTINCT ca.transaction_id) as total_transactions,
                SUM(CASE WHEN ft.recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered_transactions
            FROM communication_attempts ca
            JOIN failed_transactions ft ON ca.transaction_id = ft.id
            WHERE ca.status != 'scheduled'
            GROUP BY ca.channel
        ";
        
        $result = $this->db->query($sql);
        $rates = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['recovery_rate'] = $row['total_transactions'] > 0 ? 
                round(($row['recovered_transactions'] / $row['total_transactions']) * 100, 2) : 0;
            $rates[] = $row;
        }
        
        return $rates;
    }
    
    private function getReminderStats($startDate, $endDate) {
        $sql = "
            SELECT 
                DATE(ca.sent_at) as date,
                ca.channel,
                COUNT(*) as total_sent,
                SUM(CASE WHEN ca.status = 'opened' OR ca.status = 'clicked' THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN ca.status = 'clicked' THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN ft.recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered
            FROM communication_attempts ca
            JOIN failed_transactions ft ON ca.transaction_id = ft.id
            WHERE ca.status != 'scheduled'
                AND ca.sent_at BETWEEN ? AND ?
            GROUP BY DATE(ca.sent_at), ca.channel
            ORDER BY DATE(ca.sent_at) DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['open_rate'] = $row['total_sent'] > 0 ? 
                round(($row['opened'] / $row['total_sent']) * 100, 2) : 0;
            $row['click_rate'] = $row['total_sent'] > 0 ? 
                round(($row['clicked'] / $row['total_sent']) * 100, 2) : 0;
            $row['recovery_rate'] = $row['total_sent'] > 0 ? 
                round(($row['recovered'] / $row['total_sent']) * 100, 2) : 0;
            $stats[] = $row;
        }
        
        return $stats;
    }



    // Add to app/controllers/ReportController.php
public function advancedAnalytics() {
    // Get recovery rate by channel
    $channelRecoveryRates = $this->getRecoveryRatesByChannel();
    
    // Get recovery rate by time of day
    $timeOfDayStats = $this->getRecoveryRatesByTimeOfDay();
    
    // Get recovery rate by day of week
    $dayOfWeekStats = $this->getRecoveryRatesByDayOfWeek();
    
    // Get average time to recovery
    $avgTimeToRecovery = $this->getAverageTimeToRecovery();
    
    // Get recovery rate by customer segment
    $segmentStats = $this->getRecoveryRatesBySegment();
    
    include BASE_PATH . '/app/views/advanced_analytics.php';
}


private function getRecoveryRatesByDayOfWeek() {
    $sql = "
        SELECT 
            DAYOFWEEK(ca.sent_at) as day_of_week,
            COUNT(DISTINCT ca.transaction_id) as total_sent,
            SUM(CASE WHEN ft.recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered
        FROM communication_attempts ca
        JOIN failed_transactions ft ON ca.transaction_id = ft.id
        WHERE ca.status != 'scheduled'
        GROUP BY DAYOFWEEK(ca.sent_at)
        ORDER BY DAYOFWEEK(ca.sent_at)
    ";
    
    $result = $this->db->query($sql);
    $stats = [];
    
    $dayNames = [
        1 => 'Sunday',
        2 => 'Monday',
        3 => 'Tuesday',
        4 => 'Wednesday',
        5 => 'Thursday',
        6 => 'Friday',
        7 => 'Saturday'
    ];
    
    while ($row = $result->fetch_assoc()) {
        $dayNumber = $row['day_of_week'];
        $dayName = $dayNames[$dayNumber];
        $totalSent = $row['total_sent'];
        $recovered = $row['recovered'];
        
        $recoveryRate = $totalSent > 0 ? 
            round(($recovered / $totalSent) * 100, 2) : 0;
        
        $stats[] = [
            'day_of_week' => $dayNumber,
            'day_name' => $dayName,
            'total_sent' => $totalSent,
            'recovered' => $recovered,
            'recovery_rate' => $recoveryRate
        ];
    }
    
    return $stats;
}

/**
 * Get recovery rate by time of day
 * 
 * @return array Time of day stats
 */
private function getRecoveryRatesByTimeOfDay() {
    $sql = "
        SELECT 
            HOUR(ca.sent_at) as hour_of_day,
            COUNT(DISTINCT ca.transaction_id) as total_sent,
            SUM(CASE WHEN ft.recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered
        FROM communication_attempts ca
        JOIN failed_transactions ft ON ca.transaction_id = ft.id
        WHERE ca.status != 'scheduled'
        GROUP BY HOUR(ca.sent_at)
        ORDER BY HOUR(ca.sent_at)
    ";
    
    $result = $this->db->query($sql);
    $stats = [];
    
    while ($row = $result->fetch_assoc()) {
        $hourOfDay = $row['hour_of_day'];
        $totalSent = $row['total_sent'];
        $recovered = $row['recovered'];
        
        $recoveryRate = $totalSent > 0 ? 
            round(($recovered / $totalSent) * 100, 2) : 0;
        
        $stats[] = [
            'hour_of_day' => $hourOfDay,
            'total_sent' => $totalSent,
            'recovered' => $recovered,
            'recovery_rate' => $recoveryRate
        ];
    }
    
    return $stats;
}

/**
 * Get average time to recovery
 * 
 * @return float Average hours to recovery
 */
private function getAverageTimeToRecovery() {
    $sql = "
        SELECT 
            AVG(TIMESTAMPDIFF(HOUR, ft.transaction_date, pr.recovery_date)) as avg_hours
        FROM failed_transactions ft
        JOIN payment_recovery pr ON ft.id = pr.transaction_id
        WHERE ft.recovery_status = 'recovered'
            AND pr.status = 'completed'
    ";
    
    $result = $this->db->query($sql);
    $row = $result->fetch_assoc();
    
    return round($row['avg_hours'] ?? 0, 1);
}

/**
 * Get recovery rate by customer segment
 * 
 * @return array Segment stats
 */
private function getRecoveryRatesBySegment() {
    $sql = "
        SELECT 
            COALESCE(c.segment, 'standard') as segment,
            COUNT(DISTINCT ft.id) as total_transactions,
            SUM(CASE WHEN ft.recovery_status = 'recovered' THEN 1 ELSE 0 END) as recovered
        FROM failed_transactions ft
        JOIN customers c ON ft.customer_id = c.id
        GROUP BY COALESCE(c.segment, 'standard')
    ";
    
    $result = $this->db->query($sql);
    $stats = [];
    
    while ($row = $result->fetch_assoc()) {
        $segment = $row['segment'] ?: 'standard';
        $totalTransactions = $row['total_transactions'];
        $recovered = $row['recovered'];
        
        $recoveryRate = $totalTransactions > 0 ? 
            round(($recovered / $totalTransactions) * 100, 2) : 0;
        
        $stats[] = [
            'segment' => $segment,
            'total_transactions' => $totalTransactions,
            'recovered' => $recovered,
            'recovery_rate' => $recoveryRate
        ];
    }
    
    return $stats;
}


}


?>