-- Hóa đơn tiền điện / nước / phí khác theo tháng (chủ trọ -> người thuê)
-- Có thể chạy thủ công; ứng dụng cũng tự tạo bảng qua core/MonthlyInvoiceSchema.php nếu chưa có.

CREATE TABLE IF NOT EXISTS `monthly_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `tenant_user_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `period_year` smallint(6) NOT NULL,
  `electricity_fee` int(11) NOT NULL DEFAULT 0,
  `water_fee` int(11) NOT NULL DEFAULT 0,
  `other_fee` int(11) NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_period` (`owner_id`,`tenant_user_id`,`motel_id`,`period_year`,`period_month`),
  KEY `idx_tenant` (`tenant_user_id`),
  KEY `idx_owner` (`owner_id`),
  KEY `idx_motel` (`motel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
