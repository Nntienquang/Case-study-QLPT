<?php

/**
 * Đảm bảo bảng hóa đơn tiền điện/nước tồn tại (tự tạo nếu chưa có).
 */
function qlpt_ensure_monthly_invoices_table(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $sql = <<<SQL
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
    $conn->query($sql);
    $done = true;
}
