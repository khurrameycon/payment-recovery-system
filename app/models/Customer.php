<?php
class Customer {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    /**
     * Find a customer by email or create a new one
     */
    public function findOrCreate($email, $phone = null) {
        // Check if customer exists
        $stmt = $this->db->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Customer exists, return ID
            $row = $result->fetch_assoc();
            return $row['id'];
        }
        
        // Create new customer
        $stmt = $this->db->prepare("INSERT INTO customers (email, phone) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $phone);
        $stmt->execute();
        
        return $this->db->insert_id;
    }
    
    /**
     * Get customer details by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Update customer timezone
     */
    public function updateTimezone($id, $timezone) {
        $stmt = $this->db->prepare("UPDATE customers SET timezone = ? WHERE id = ?");
        $stmt->bind_param("si", $timezone, $id);
        return $stmt->execute();
    }
    
    /**
     * Update customer segment
     */
    public function updateSegment($id, $segment) {
        $stmt = $this->db->prepare("UPDATE customers SET segment = ? WHERE id = ?");
        $stmt->bind_param("si", $segment, $id);
        return $stmt->execute();
    }
}
?>