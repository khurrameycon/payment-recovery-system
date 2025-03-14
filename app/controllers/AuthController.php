<?php
require_once BASE_PATH . '/app/middleware/Auth.php';

class AuthController {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    public function showLogin() {
        include BASE_PATH . '/app/views/login.php';
    }
    
    public function processLogin() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($this->auth->login($email, $password)) {
            header('Location: index.php?route=dashboard');
            exit;
        }
        
        $_SESSION['error'] = "Invalid email or password";
        header('Location: index.php?route=login');
        exit;
    }
    
    public function logout() {
        $this->auth->logout();
        header('Location: index.php?route=login');
        exit;
    }
}
?>