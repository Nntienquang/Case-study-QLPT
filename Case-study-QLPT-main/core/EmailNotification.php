<?php
/**
 * Email Notification System
 * 
 * Gửi email thông báo về các sự kiện trong hệ thống
 */

class EmailNotification {
    private $db;
    private $smtp_enabled;
    private $from_email;
    private $from_name;
    
    public function __construct($db, $smtp_enabled = false) {
        $this->db = $db;
        $this->smtp_enabled = $smtp_enabled; // Bật khi có SMTP configuration
        $this->from_email = 'noreply@phongtro.com';
        $this->from_name = 'Hệ Thống Quản Lý Phòng Trọ';
    }
    
    /**
     * Gửi email thông báo duyệt tài khoản chủ trọ
     */
    public function sendOwnerApprovalNotification($user_id) {
        $user_sql = "SELECT * FROM users WHERE id = " . (int)$user_id;
        $user = $this->db->getRow($user_sql);
        
        if (!$user || !$user['email']) {
            return false;
        }
        
        $subject = "✅ Tài khoản của bạn đã được duyệt";
        
        $body = "
        <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #28a745; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                    a { color: #007bff; text-decoration: none; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Tài khoản được duyệt ✅</h2>
                    </div>
                    <div class='content'>
                        <p>Xin chào <strong>{$user['name']}</strong>,</p>
                        <p>Tài khoản chủ trọ của bạn đã được hệ thống duyệt thành công!</p>
                        <p>Bạn giờ có thể:</p>
                        <ul>
                            <li>Đăng tin phòng trọ mới</li>
                            <li>Quản lý danh sách phòng trọ</li>
                            <li>Xem đơn đặt phòng từ khách hàng</li>
                            <li>Quản lý thanh toán</li>
                        </ul>
                        <p><a href='" . BASE_URL . "' style='background-color: #28a745; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block;'>Đăng nhập ngay</a></p>
                        <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
                        <p>Trân trọng,<br>Hệ thống Quản Lý Phòng Trọ</p>
                    </div>
                    <div class='footer'>
                        <p>Email tự động từ hệ thống. Vui lòng không trả lời email này.</p>
                    </div>
                </div>
            </body>
        </html>";
        
        return $this->sendEmail($user['email'], $user['name'], $subject, $body);
    }
    
    /**
     * Gửi email thông báo từ chối tài khoản chủ trọ
     */
    public function sendOwnerRejectionNotification($user_id, $rejection_reason = '') {
        $user_sql = "SELECT * FROM users WHERE id = " . (int)$user_id;
        $user = $this->db->getRow($user_sql);
        
        if (!$user || !$user['email']) {
            return false;
        }
        
        $subject = "❌ Tài khoản chủ trọ của bạn cần thêm thông tin";
        
        $reason_html = '';
        if ($rejection_reason) {
            $reason_html = "<div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                <strong>Lý do từ chối:</strong><br>
                {$rejection_reason}
            </div>";
        }
        
        $body = "
        <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #dc3545; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                    a { color: #007bff; text-decoration: none; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Tài khoản cần bổ sung thông tin</h2>
                    </div>
                    <div class='content'>
                        <p>Xin chào <strong>{$user['name']}</strong>,</p>
                        <p>Tài khoản chủ trọ của bạn cần bổ sung hoặc sửa lại một số thông tin.</p>
                        {$reason_html}
                        <p>Vui lòng đăng nhập vào tài khoản và cập nhật thông tin theo yêu cầu.</p>
                        <p><a href='" . BASE_URL . "' style='background-color: #007bff; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block;'>Cập nhật tài khoản</a></p>
                        <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ với chúng tôi.</p>
                        <p>Trân trọng,<br>Hệ thống Quản Lý Phòng Trọ</p>
                    </div>
                    <div class='footer'>
                        <p>Email tự động từ hệ thống. Vui lòng không trả lời email này.</p>
                    </div>
                </div>
            </body>
        </html>";
        
        return $this->sendEmail($user['email'], $user['name'], $subject, $body);
    }
    
    /**
     * Gửi email thông báo block tài khoản
     */
    public function sendAccountBlockedNotification($user_id, $reason = '') {
        $user_sql = "SELECT * FROM users WHERE id = " . (int)$user_id;
        $user = $this->db->getRow($user_sql);
        
        if (!$user || !$user['email']) {
            return false;
        }
        
        $subject = "🔒 Tài khoản của bạn đã bị khóa";
        
        $reason_html = '';
        if ($reason) {
            $reason_html = "<div style='background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0;'>
                <strong>Lý do khóa:</strong><br>
                {$reason}
            </div>";
        }
        
        $body = "
        <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #dc3545; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Tài khoản bị khóa 🔒</h2>
                    </div>
                    <div class='content'>
                        <p>Xin chào <strong>{$user['name']}</strong>,</p>
                        <p>Tài khoản của bạn đã bị khóa do vi phạm quy tắc hệ thống.</p>
                        {$reason_html}
                        <p>Bạn không thể đăng nhập hoặc sử dụng các dịch vụ cho đến khi tài khoản được mở khóa.</p>
                        <p>Nếu bạn cho rằng điều này là lỗi, vui lòng liên hệ với hỗ trợ.</p>
                        <p>Trân trọng,<br>Hệ thống Quản Lý Phòng Trọ</p>
                    </div>
                    <div class='footer'>
                        <p>Email tự động từ hệ thống. Vui lòng không trả lời email này.</p>
                    </div>
                </div>
            </body>
        </html>";
        
        return $this->sendEmail($user['email'], $user['name'], $subject, $body);
    }
    
    /**
     * Gửi email generic
     */
    private function sendEmail($to_email, $to_name, $subject, $body) {
        // Nếu SMTP chưa cấu hình, lưu email vào database để xem sau (fallback)
        if (!$this->smtp_enabled) {
            return $this->saveEmailToLog($to_email, $to_name, $subject, $body);
        }
        
        // TODO: Thêm cấu hình SMTP thực sự
        // Hiện tại sử dụng mail() function của PHP (require mailserver trên server)
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
        $headers .= "Reply-To: {$this->from_email}" . "\r\n";
        
        $sent = mail($to_email, $subject, $body, $headers);
        
        if ($sent) {
            $this->saveEmailToLog($to_email, $to_name, $subject, $body, 'sent');
        }
        
        return $sent;
    }
    
    /**
     * Lưu email vào log (fallback khi SMTP chưa cấu hình)
     */
    private function saveEmailToLog($to_email, $to_name, $subject, $body, $status = 'pending') {
        $conn = $this->db->getConnection();
        $to_email_esc = $conn->real_escape_string($to_email);
        $to_name_esc = $conn->real_escape_string($to_name);
        $subject_esc = $conn->real_escape_string($subject);
        $body_esc = $conn->real_escape_string($body);
        $status_esc = $conn->real_escape_string($status);
        
        $sql = "INSERT INTO email_logs 
                (to_email, to_name, subject, body, status, created_at) 
                VALUES 
                ('{$to_email_esc}', '{$to_name_esc}', '{$subject_esc}', '{$body_esc}', '{$status_esc}', NOW())";
        
        return $this->db->query($sql);
    }
    
    /**
     * Lấy danh sách email chưa gửi
     */
    public function getPendingEmails($limit = 50) {
        $sql = "SELECT * FROM email_logs WHERE status = 'pending' LIMIT " . (int)$limit;
        return $this->db->getRows($sql);
    }
    
    /**
     * Đánh dấu email đã gửi
     */
    public function markEmailAsSent($email_id) {
        $sql = "UPDATE email_logs SET status = 'sent', sent_at = NOW() WHERE id = " . (int)$email_id;
        return $this->db->query($sql);
    }
}
?>
