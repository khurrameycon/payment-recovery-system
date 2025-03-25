<?php
// File: app/services/TimeOptimizationService.php

class TimeOptimizationService {
    private $db;
    private $holidayCache = [];
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->loadHolidays();
    }
    /**
     * Get customer's timezone from database or detect it based on location
     */
    public function getCustomerTimezone($customerId) {
        // Get customer details
        $customer = $this->getCustomerDetails($customerId);
        
        // If customer has timezone set, use it
        if (!empty($customer['timezone'])) {
            return $customer['timezone'];
        }
        
        // Otherwise, try to detect based on customer's country
        $country = $customer['country'] ?? 'US';
        
        // Default timezone map for major countries
        $countryTimezones = [
            'US' => 'America/New_York',
            'CA' => 'America/Toronto',
            'GB' => 'Europe/London',
            'AU' => 'Australia/Sydney',
            'DE' => 'Europe/Berlin',
            'FR' => 'Europe/Paris',
            'JP' => 'Asia/Tokyo',
            'CN' => 'Asia/Shanghai',
            'IN' => 'Asia/Kolkata',
            'BR' => 'America/Sao_Paulo',
            'RU' => 'Europe/Moscow',
            'ZA' => 'Africa/Johannesburg',
            'MX' => 'America/Mexico_City'
        ];
        
        $timezone = $countryTimezones[$country] ?? 'UTC';
        
        // Save detected timezone to customer record for future use
        $this->updateCustomerTimezone($customerId, $timezone);
        
        return $timezone;
    }
    
    /**
     * Update customer's timezone
     */
    private function updateCustomerTimezone($customerId, $timezone) {
        $stmt = $this->db->prepare("UPDATE customers SET timezone = ? WHERE id = ?");
        $stmt->bind_param("si", $timezone, $customerId);
        $stmt->execute();
    }
    
    /**
     * Load more holidays from external API or database
     * Can be expanded to include more countries and holidays
     */
    public function updateHolidayDatabase() {
        // For demonstration, adding some US and UK holidays for 2023
        $holidays = [
            // US Holidays
            ['US', '2025-01-01', 'New Year\'s Day'],
            ['US', '2025-01-20', 'Martin Luther King Jr. Day'],
            ['US', '2025-02-17', 'Presidents Day'],
            ['US', '2025-05-26', 'Memorial Day'],
            ['US', '2025-07-04', 'Independence Day'],
            ['US', '2025-09-01', 'Labor Day'],
            ['US', '2025-10-13', 'Columbus Day'],
            ['US', '2025-11-11', 'Veterans Day'],
            ['US', '2025-11-27', 'Thanksgiving'],
            ['US', '2025-12-25', 'Christmas Day'],
            
            // UK Holidays
            ['GB', '2025-01-01', 'New Year\'s Day'],
            ['GB', '2025-04-18', 'Good Friday'],
            ['GB', '2025-04-21', 'Easter Monday'],
            ['GB', '2025-05-05', 'Early May Bank Holiday'],
            ['GB', '2025-05-26', 'Spring Bank Holiday'],
            ['GB', '2025-08-25', 'Summer Bank Holiday'],
            ['GB', '2025-12-25', 'Christmas Day'],
            ['GB', '2025-12-26', 'Boxing Day']
        ];
        
        // Clear existing holidays for these dates (to avoid duplicates)
        $this->db->query("DELETE FROM holidays WHERE holiday_date >= '2025-01-01' AND holiday_date <= '2025-12-31'");
        
        // Add new holidays
        $stmt = $this->db->prepare("INSERT INTO holidays (country, holiday_date, name) VALUES (?, ?, ?)");
        
        foreach ($holidays as $holiday) {
            $stmt->bind_param("sss", $holiday[0], $holiday[1], $holiday[2]);
            $stmt->execute();
        }
        
        // Refresh the holiday cache
        $this->loadHolidays();
        
        return count($holidays);
    }
    
    /**
     * Determine the optimal send time based on customer timezone and preferences
     * 
     * @param int $customerId Customer ID
     * @param string $transactionType Type of transaction (high_value, medium_value, low_value)
     * @return DateTime Optimal send time
     */
    public function getOptimalSendTime($customerId, $transactionType = 'standard') {
        // Get customer timezone and preferences
        $customer = $this->getCustomerDetails($customerId);
        $timezone = $customer['timezone'] ?: 'UTC';
        
        try {
            // Create datetime in customer's timezone
            $now = new DateTime('now', new DateTimeZone($timezone));
            $hour = (int)$now->format('H');
            $dayOfWeek = (int)$now->format('N'); // 1 (Monday) to 7 (Sunday)
            $dateStr = $now->format('Y-m-d');
            
            // Check if today is a holiday in customer's country
            $isHoliday = $this->isHoliday($dateStr, $customer['country'] ?? 'US');
            
            // Define business hours based on segment/type
            $businessHours = $this->getBusinessHours($transactionType);
            
            // Check if current time is within business hours
            $isBusinessHours = $this->isWithinBusinessHours($hour, $dayOfWeek, $businessHours, $isHoliday);
            
            // Get quiet hours based on country/region
            $quietHours = $this->getQuietHours($customer['country'] ?? 'US');
            
            // Check if current time is within quiet hours
            $isQuietHours = $this->isWithinQuietHours($hour, $quietHours);
            
            // If outside business hours or in quiet hours, schedule for next business hour
            if (!$isBusinessHours || $isQuietHours) {
                return $this->getNextBusinessHour($now, $businessHours, $quietHours, $isHoliday);
            }
            
            // Current time is acceptable
            return $now;
        } catch (Exception $e) {
            // In case of any timezone errors, default to current time
            error_log("Error in getOptimalSendTime: " . $e->getMessage());
            return new DateTime();
        }
    }
    
    /**
     * Check if given hour is within business hours
     */
    private function isWithinBusinessHours($hour, $dayOfWeek, $businessHours, $isHoliday) {
        // Never send on holidays
        if ($isHoliday) {
            return false;
        }
        
        // Check if it's a weekend
        $isWeekend = ($dayOfWeek == 6 || $dayOfWeek == 7); // Saturday or Sunday
        
        // If weekend and weekend sending is not allowed
        if ($isWeekend && !$businessHours['send_on_weekend']) {
            return false;
        }
        
        // Check hour range
        return ($hour >= $businessHours['start_hour'] && $hour < $businessHours['end_hour']);
    }
    
    /**
     * Check if given hour is within quiet hours
     */
    private function isWithinQuietHours($hour, $quietHours) {
        // If hour is between quiet start and end
        if ($quietHours['start_hour'] <= $quietHours['end_hour']) {
            // Simple case: quiet hours are in same day (e.g., 22:00 to 06:00)
            return ($hour >= $quietHours['start_hour'] || $hour < $quietHours['end_hour']);
        } else {
            // Overnight case: quiet hours span midnight (e.g., 22:00 to 06:00)
            return ($hour >= $quietHours['start_hour'] || $hour < $quietHours['end_hour']);
        }
    }
    
    /**
     * Get the next available business hour
     */
    private function getNextBusinessHour($datetime, $businessHours, $quietHours, $isHoliday) {
        $targetDateTime = clone $datetime;
        $dayOfWeek = (int)$targetDateTime->format('N'); // 1 (Monday) to 7 (Sunday)
        $hour = (int)$targetDateTime->format('H');
        
        // If it's a holiday, move to next day
        if ($isHoliday) {
            $targetDateTime->modify('+1 day');
            $targetDateTime->setTime($businessHours['start_hour'], 0, 0);
            
            // Recursively check next day (in case it's also a holiday or weekend)
            return $this->getNextBusinessHour(
                $targetDateTime, 
                $businessHours, 
                $quietHours, 
                $this->isHoliday($targetDateTime->format('Y-m-d'), $targetDateTime->getTimezone()->getName())
            );
        }
        
        // If it's weekend and weekend sending is not allowed
        $isWeekend = ($dayOfWeek == 6 || $dayOfWeek == 7); // Saturday or Sunday
        if ($isWeekend && !$businessHours['send_on_weekend']) {
            // Calculate days to add to get to Monday
            $daysToAdd = 8 - $dayOfWeek; // 8-6=2 for Saturday, 8-7=1 for Sunday
            $targetDateTime->modify("+{$daysToAdd} days");
            $targetDateTime->setTime($businessHours['start_hour'], 0, 0);
            return $targetDateTime;
        }
        
        // If after business hours, schedule for next day's start
        if ($hour >= $businessHours['end_hour']) {
            $targetDateTime->modify('+1 day');
            $targetDateTime->setTime($businessHours['start_hour'], 0, 0);
            return $targetDateTime;
        }
        
        // If before business hours, schedule for today's start
        if ($hour < $businessHours['start_hour']) {
            $targetDateTime->setTime($businessHours['start_hour'], 0, 0);
            return $targetDateTime;
        }
        
        // If within quiet hours but also business hours, move to after quiet hours
        if ($this->isWithinQuietHours($hour, $quietHours)) {
            if ($quietHours['end_hour'] < $businessHours['end_hour']) {
                // If quiet hours end before business hours end, schedule after quiet hours
                $targetDateTime->setTime($quietHours['end_hour'], 0, 0);
            } else {
                // If quiet hours extend beyond business hours, schedule for next business day
                $targetDateTime->modify('+1 day');
                $targetDateTime->setTime($businessHours['start_hour'], 0, 0);
            }
            return $targetDateTime;
        }
        
        // Current time is acceptable
        return $targetDateTime;
    }
    
    /**
     * Check if a given date is a holiday in the specified country
     */
    private function isHoliday($date, $country = 'US') {
        // If we don't have holidays for this country, assume it's not a holiday
        if (!isset($this->holidayCache[$country])) {
            return false;
        }
        
        // Check if date exists in the holiday array
        return in_array($date, $this->holidayCache[$country]);
    }
    
    /**
     * Load holidays from database into cache
     */
    private function loadHolidays() {
        $sql = "SELECT holiday_date, country FROM holidays WHERE holiday_date >= CURDATE()";
        $result = $this->db->query($sql);
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $country = $row['country'];
                $date = $row['holiday_date'];
                
                if (!isset($this->holidayCache[$country])) {
                    $this->holidayCache[$country] = [];
                }
                
                $this->holidayCache[$country][] = $date;
            }
        }
    }
    
    /**
     * Get business hours configuration based on transaction type
     */
    private function getBusinessHours($transactionType) {
        // Default business hours
        $default = [
            'start_hour' => 9,  // 9 AM
            'end_hour' => 17,   // 5 PM
            'send_on_weekend' => false
        ];
        
        // Adjust based on transaction type
        switch ($transactionType) {
            case 'high_value':
                // For high value transactions, extend hours and allow weekend
                return [
                    'start_hour' => 8,  // 8 AM
                    'end_hour' => 20,   // 8 PM
                    'send_on_weekend' => true
                ];
                
            case 'medium_value':
                // For medium value, standard hours but allow weekend
                return [
                    'start_hour' => 9,  // 9 AM
                    'end_hour' => 18,   // 6 PM
                    'send_on_weekend' => true
                ];
                
            default:
                return $default;
        }
    }
    
    /**
     * Get quiet hours configuration based on country
     */
    private function getQuietHours($country = 'US') {
        // Default quiet hours (10 PM to 7 AM)
        $default = [
            'start_hour' => 22, // 10 PM
            'end_hour' => 7     // 7 AM
        ];
        
        // Country-specific quiet hours
        $countryQuietHours = [
            'US' => [
                'start_hour' => 21, // 9 PM
                'end_hour' => 8     // 8 AM
            ],
            'UK' => [
                'start_hour' => 20, // 8 PM
                'end_hour' => 8     // 8 AM
            ],
            'DE' => [
                'start_hour' => 20, // 8 PM 
                'end_hour' => 8     // 8 AM
            ],
            // Add more countries as needed
        ];
        
        return $countryQuietHours[$country] ?? $default;
    }
    
    /**
     * Get customer details from database
     */
    private function getCustomerDetails($customerId) {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Default values if customer not found
        return [
            'timezone' => 'UTC',
            'country' => 'US',
            'segment' => 'standard'
        ];
    }
}