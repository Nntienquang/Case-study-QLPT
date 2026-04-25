-- Email Logs Table
-- Lưu trữ lịch sử gửi email để kiểm soát và debug

CREATE TABLE IF NOT EXISTS email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body LONGTEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_to_email (to_email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
