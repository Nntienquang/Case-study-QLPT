<?php
/**
 * Authentication Controller
 * Xử lý tất cả các hoạt động auth: login, register, password reset, etc
 */

class AuthController {
    private $db;
    private $user;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->db = $db;
        $this->user = new User($db);
        $this->activityLog = $activityLog;
    }
    
    /**
     * Register user
     */
    public function register($name, $email, $password, $confirm, $role = 'user') {
        $errors = [];
        
        // Validation
        $name = trim($name ?? "");
        $email = trim($email ?? "");
        $password = $password ?? "";
        $confirm = $confirm ?? "";
        
        if (empty($name)) {
            $errors[] = "Họ tên không được để trống";
        } elseif (strlen($name) < 3) {
            $errors[] = "Họ tên phải có ít nhất 3 ký tự";
        }
        
        if (empty($email)) {
            $errors[] = "Email không được để trống";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ";
        }
        
        if (empty($password)) {
            $errors[] = "Mật khẩu không được để trống";
        } elseif (strlen($password) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
        } elseif ($password !== $confirm) {
            $errors[] = "Mật khẩu không khớp";
        }
        
        // Check if email exists
        if (empty($errors)) {
            $check = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            if ($check) {
                $check->bind_param("s", $email);
                $check->execute();
                $check->store_result();
                
                if ($check->num_rows > 0) {
                    $errors[] = "Email này đã tồn tại";
                }
                $check->close();
            }
        }
        
        // Register if no errors
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $status = 'pending'; // Pending approval
            
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt) {
                $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $status);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    
                    // Log activity
                    if ($this->activityLog) {
                        $this->activityLog->log(
                            0,
                            'register_user',
                            'user',
                            0,
                            [],
                            "Người dùng mới đăng ký: $email"
                        );
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Đăng ký thành công! Vui lòng đăng nhập'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Lỗi hệ thống: ' . htmlspecialchars($stmt->error)
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'message' => implode(', ', $errors)
        ];
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $errors = [];
        
        $email = trim($email ?? "");
        $password = $password ?? "";
        
        if (empty($email) || empty($password)) {
            $errors[] = "Email hoặc mật khẩu không được để trống";
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors)
            ];
        }
        
        // Get user
        $stmt = $this->db->prepare("SELECT id, name, password, role, status FROM users WHERE email = ?");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống'
            ];
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows != 1) {
            // Log failed login attempt
            if ($this->activityLog) {
                $this->activityLog->log(
                    0,
                    'login_failed',
                    'user',
                    0,
                    ['email' => $email],
                    "Thất bại: Email không tồn tại"
                );
            }
            
            return [
                'success' => false,
                'message' => 'Email hoặc mật khẩu không chính xác'
            ];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Check account status
        if ($user['status'] === 'blocked') {
            return [
                'success' => false,
                'message' => 'Tài khoản của bạn bị khóa'
            ];
        }
        
        if ($user['status'] === 'rejected' && $user['role'] === 'owner') {
            return [
                'success' => false,
                'message' => 'Đơn đăng ký owner của bạn bị từ chối'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Log failed login attempt
            if ($this->activityLog) {
                $this->activityLog->log(
                    0,
                    'login_failed',
                    'user',
                    0,
                    ['email' => $email],
                    "Thất bại: Mật khẩu sai"
                );
            }
            
            return [
                'success' => false,
                'message' => 'Email hoặc mật khẩu không chính xác'
            ];
        }
        
        // Login success - set session
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['status'] = $user['status'];
        $_SESSION['login_time'] = time();
        
        // Log successful login
        if ($this->activityLog) {
            $this->activityLog->log(
                $user['id'],
                'login_success',
                'user',
                $user['id'],
                [],
                "Đăng nhập thành công"
            );
        }
        
        return [
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'role' => $user['role']
        ];
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        $email = trim($email ?? "");
        
        if (empty($email)) {
            return [
                'success' => false,
                'message' => 'Email không được để trống'
            ];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email không hợp lệ'
            ];
        }
        
        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống'
            ];
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
            
            // Update user
            $update = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            if ($update) {
                $update->bind_param("sss", $reset_token, $token_expires, $email);
                $update->execute();
                
                // Log activity
                if ($this->activityLog) {
                    $this->activityLog->log(
                        0,
                        'password_reset_request',
                        'user',
                        0,
                        ['email' => $email],
                        "Yêu cầu đặt lại mật khẩu"
                    );
                }
                
                // Return token để gửi email
                $stmt->close();
                $update->close();
                
                return [
                    'success' => true,
                    'message' => 'Nếu email này tồn tại, bạn sẽ nhận được email xác nhận',
                    'token' => $reset_token, // Để gửi email
                    'reset_url' => BASE_URL . 'reset.php?token=' . $reset_token
                ];
            }
        }
        
        $stmt->close();
        
        // Return generic message (don't reveal if email exists)
        return [
            'success' => true,
            'message' => 'Nếu email này tồn tại, bạn sẽ nhận được email xác nhận'
        ];
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword($token, $password, $confirm) {
        $errors = [];
        
        $token = trim($token ?? "");
        $password = $password ?? "";
        $confirm = $confirm ?? "";
        
        if (empty($token)) {
            $errors[] = "Token không hợp lệ";
        }
        
        if (empty($password)) {
            $errors[] = "Mật khẩu không được để trống";
        } elseif (strlen($password) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
        } elseif ($password !== $confirm) {
            $errors[] = "Mật khẩu không khớp";
        }
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(', ', $errors)
            ];
        }
        
        // Validate token
        $stmt = $this->db->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Lỗi hệ thống'
            ];
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows != 1) {
            return [
                'success' => false,
                'message' => 'Liên kết không hợp lệ hoặc đã hết hạn'
            ];
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Update password
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $update = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        
        if ($update) {
            $user_id = $user['id'];
            $update->bind_param("si", $hashed, $user_id);
            
            if ($update->execute()) {
                $update->close();
                
                // Log activity
                if ($this->activityLog) {
                    $this->activityLog->log(
                        $user_id,
                        'password_reset',
                        'user',
                        $user_id,
                        [],
                        "Đặt lại mật khẩu thành công"
                    );
                }
                
                return [
                    'success' => true,
                    'message' => 'Mật khẩu đã được cập nhật. Vui lòng đăng nhập'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Lỗi hệ thống'
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'Lỗi hệ thống'
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $user_id,
                    'logout',
                    'user',
                    $user_id,
                    [],
                    "Đăng xuất"
                );
            }
        }
        
        session_unset();
        session_destroy();
    }
    
    /**
     * Check session timeout
     */
    public function checkSessionTimeout() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $timeout = SESSION_TIMEOUT * 60; // Convert to seconds
        
        if (time() - $_SESSION['login_time'] > $timeout) {
            $this->logout();
            return false;
        }
        
        // Update login time
        $_SESSION['login_time'] = time();
        return true;
    }
}
?>
