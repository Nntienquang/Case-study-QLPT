<?php
/**
 * Authentication Class
 */

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $conn = $this->db->getConnection();
        $email_escaped = $conn->real_escape_string($email);
        $sql = "SELECT * FROM users WHERE email = '$email_escaped' AND role = 'admin'";
        $user = $this->db->getRow($sql);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN;
    }
    
    /**
     * Check session timeout
     */
    public function checkTimeout() {
        if (isset($_SESSION['login_time'])) {
            if ((time() - $_SESSION['login_time']) > (SESSION_TIMEOUT * 60)) {
                $this->logout();
                return false;
            }
            $_SESSION['login_time'] = time();
        }
        return true;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    /**
     * Register admin user
     */
    public function registerAdmin($name, $email, $password, $phone = '') {
        $conn = $this->db->getConnection();
        $email_escaped = $conn->real_escape_string($email);
        
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = '$email_escaped'";
        if ($this->db->getRow($sql)) {
            return false;
        }
        
        $data = [
            'name' => $name,
            'email' => $email_escaped,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'phone' => $phone,
            'role' => ROLE_ADMIN
        ];
        
        return $this->db->insert('users', $data);
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }
}

?>
