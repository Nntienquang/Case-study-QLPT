CREATE TABLE IF NOT EXISTS `owner_warnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `warning_level` enum('reminder','warning','severe_warning','posting_suspended') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_owner_warnings_owner_id_created_at` (`owner_id`,`created_at`),
  KEY `idx_owner_warnings_admin_id` (`admin_id`),
  KEY `idx_owner_warnings_warning_level` (`warning_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
