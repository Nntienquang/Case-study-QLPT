CREATE TABLE IF NOT EXISTS `page_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `host` varchar(255) NOT NULL,
  `page_url` varchar(500) NOT NULL,
  `page_type` varchar(80) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_page_views_viewed_at` (`viewed_at`),
  KEY `idx_page_views_page_url` (`page_url`(191)),
  KEY `idx_page_views_page_type` (`page_type`),
  KEY `idx_page_views_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
