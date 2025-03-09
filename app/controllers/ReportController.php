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
}
?>