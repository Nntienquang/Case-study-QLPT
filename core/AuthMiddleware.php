<?php
/**
 * Authentication Middleware
 * Kiểm tra session và timeout
 */

class AuthMiddleware {
    private $authController;
    
    public function __construct($authController) {
        $this->authController = $authController;
    }
    
    /**
     * Check if user is logged in
     * Redirect to login if not
     */
    public function requireLogin() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "login.php");
            exit();
        }
        
        // Check session timeout
        if (!$this->authController->checkSessionTimeout()) {
            header("Location: " . BASE_URL . "login.php?expired=1");
            exit();
        }
    }
    
    /**
     * Check if user is admin
     */
    public function requireAdmin() {
        $this->requireLogin();
        
        if ($_SESSION['role'] !== 'admin') {
            header("Location: " . BASE_URL . "public/index.php");
            exit();
        }
    }
    
    /**
     * Check if user is owner
     */
    public function requireOwner() {
        $this->requireLogin();
        
        if ($_SESSION['role'] !== 'owner') {
            header("Location: " . BASE_URL . "public/index.php");
            exit();
        }
    }
    
    /**
     * Check if user is owner and approved
     */
    public function requireApprovedOwner() {
        $this->requireOwner();
        
        if ($_SESSION['status'] !== 'approved') {
            header("Location: " . BASE_URL . "public/index.php?error=owner_not_approved");
            exit();
        }
    }
    
    /**
     * Check if user is not blocked
     */
    public function requireNotBlocked() {
        $this->requireLogin();
        
        if ($_SESSION['status'] === 'blocked') {
            // Logout if blocked
            $this->authController->logout();
            header("Location: " . BASE_URL . "login.php?blocked=1");
            exit();
        }
    }
    
    /**
     * Check if user is guest (not logged in)
     * Redirect to dashboard if logged in
     */
    public function requireGuest() {
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['role'] === 'admin') {
                header("Location: " . BASE_URL . "public/admin/index.php");
            } else {
                header("Location: " . BASE_URL . "public/index.php");
            }
            exit();
        }
    }
}
?>
