-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
<<<<<<< HEAD
-- Host: 127.0.0.1
-- Generation Time: May 15, 2026 at 04:04 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
=======
-- Máy chủ: 127.0.0.1
<<<<<<< HEAD
-- Thời gian đã tạo: Th5 15, 2026 lúc 11:33 AM
=======
-- Thời gian đã tạo: Th5 15, 2026 lúc 11:43 AM
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quanlyphongtro`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
<<<<<<< HEAD
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(2, 5, 'approve_user', 'user', 8, NULL, NULL, 'Duyệt tài khoản owner: Owner (owner123@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 13:08:08');
=======
-- Đang đổ dữ liệu cho bảng `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 5, 'approve_user', 'user', 6, NULL, NULL, 'Duyệt tài khoản owner: Chủ trọ 1 (owner@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-05-14 06:05:10'),
(2, 5, 'approve_user', 'user', 8, NULL, NULL, 'Duyệt tài khoản owner: Bảo Phan (admin1234@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-05-14 06:07:58');
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE `admin_notes` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
<<<<<<< HEAD
-- Table structure for table `articles`
=======
<<<<<<< HEAD
=======
-- Cấu trúc bảng cho bảng `articles`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('draft','published','hidden') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
<<<<<<< HEAD
-- Table structure for table `bookings`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Cấu trúc bảng cho bảng `bookings`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `deposit_amount` int(11) DEFAULT NULL,
  `checkin_date` date DEFAULT NULL,
  `status` enum('pending','paid','accepted','completed','rejected','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Phòng trọ'),
(2, 'Chung cư mini'),
(3, 'Căn hộ');

-- --------------------------------------------------------

--
<<<<<<< HEAD
-- Table structure for table `conversations`
=======
<<<<<<< HEAD
-- Cấu trúc bảng cho bảng `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `deposit_amount` int(11) NOT NULL,
  `monthly_price` int(11) NOT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `status` enum('pending_signature','active','expiring_soon','expired','terminated') DEFAULT 'pending_signature',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Cấu trúc bảng cho bảng `conversations`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `name`) VALUES
(1, 'Quận 1'),
(2, 'Quận 3'),
(3, 'Quận 7');

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `listing_quality_checks`
--

CREATE TABLE `listing_quality_checks` (
  `id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `missing_fields` text DEFAULT NULL,
  `suggestions` text DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `failure_reason` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_security_state`
--

CREATE TABLE `login_security_state` (
  `id` int(11) NOT NULL,
  `identity_hash` char(64) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `failures` int(11) NOT NULL DEFAULT 0,
  `captcha_required` tinyint(1) NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `lock_level` tinyint(1) NOT NULL DEFAULT 0,
  `last_failure_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `title` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'normal',
  `status` varchar(30) NOT NULL DEFAULT 'open',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
<<<<<<< HEAD
-- Table structure for table `motels`
=======
<<<<<<< HEAD
-- Cấu trúc bảng cho bảng `monthly_bills`
--

CREATE TABLE `monthly_bills` (
  `id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `billing_month` int(2) NOT NULL,
  `billing_year` int(4) NOT NULL,
  `elec_old` int(11) DEFAULT 0,
  `elec_new` int(11) DEFAULT 0,
  `elec_price` int(11) DEFAULT 3500,
  `water_old` int(11) DEFAULT 0,
  `water_new` int(11) DEFAULT 0,
  `water_price` int(11) DEFAULT 20000,
  `trash_fee` int(11) DEFAULT 50000,
  `internet_fee` int(11) DEFAULT 100000,
  `total_amount` int(11) NOT NULL,
  `status` enum('unpaid','paid','overdue') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `monthly_invoices`
--

CREATE TABLE `monthly_invoices` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `tenant_user_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `period_year` smallint(6) NOT NULL,
  `electricity_fee` int(11) NOT NULL DEFAULT 0,
  `water_fee` int(11) NOT NULL DEFAULT 0,
  `other_fee` int(11) NOT NULL DEFAULT 0,
  `note` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Cấu trúc bảng cho bảng `motels`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--

CREATE TABLE `motels` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL,
  `area` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','hidden','rejected') DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `count_view` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `utilities` text DEFAULT NULL,
  `verification_status` varchar(30) NOT NULL DEFAULT 'unverified',
  `health_score` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `available_from` date DEFAULT NULL,
  `service_fee` int(11) NOT NULL DEFAULT 0,
  `deposit_months` decimal(3,1) NOT NULL DEFAULT 1.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `motel_images`
--

CREATE TABLE `motel_images` (
  `id` int(11) NOT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `motel_utilities`
--

CREATE TABLE `motel_utilities` (
  `motel_id` int(11) NOT NULL,
  `utility_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
<<<<<<< HEAD
-- Table structure for table `news_categories`
=======
<<<<<<< HEAD
=======
-- Cấu trúc bảng cho bảng `news_categories`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--

CREATE TABLE `news_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_categories`
--

INSERT INTO `news_categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Kinh nghiệm thuê trọ', 'kinh-nghiem-thue-tro', 'Các mẹo hay giúp sinh viên và người đi làm tìm phòng trọ ưng ý, tránh lừa đảo.', '2026-05-15 13:02:47'),
(2, 'Cẩm nang pháp lý', 'cam-nang-phap-ly', 'Kiến thức về hợp đồng, luật cư trú, đăng ký tạm trú tạm vắng.', '2026-05-15 13:02:47'),
(3, 'Thông báo từ hệ thống', 'thong-bao', 'Các cập nhật tính năng mới hoặc quy định từ Ban quản trị QuanLyPhongTro.', '2026-05-15 13:02:47');

-- --------------------------------------------------------

--
<<<<<<< HEAD
-- Table structure for table `notifications`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Cấu trúc bảng cho bảng `notifications`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(80) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `fee` int(11) DEFAULT 0,
  `method` varchar(50) DEFAULT NULL,
  `transaction_code` varchar(255) DEFAULT NULL,
  `status` enum('pending','held','released','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `reporter_id` int(11) DEFAULT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `report_type` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `handled_by` int(11) DEFAULT NULL,
  `handled_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_searches`
--

CREATE TABLE `saved_searches` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price_min` int(11) DEFAULT NULL,
  `price_max` int(11) DEFAULT NULL,
  `area_min` int(11) DEFAULT NULL,
  `alert_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_notified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `from_user` int(11) DEFAULT NULL,
  `to_user` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `fee` int(11) DEFAULT 0,
  `type` enum('deposit','release','refund','withdraw','fee') DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `from_user`, `to_user`, `amount`, `fee`, `type`, `booking_id`, `created_at`) VALUES
(1, NULL, NULL, 1000000, 0, 'deposit', NULL, '2026-04-23 08:39:47'),
(2, NULL, NULL, 990000, 10000, 'release', NULL, '2026-04-23 08:39:47'),
(3, NULL, 4, 10000, 0, 'fee', NULL, '2026-04-23 08:39:47');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('user','owner','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `idcard_number` varchar(50) DEFAULT NULL,
  `trust_score` int(11) NOT NULL DEFAULT 0,
  `admin_note` text DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `id_card_front` varchar(255) DEFAULT NULL,
  `id_card_back` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `notify_email` tinyint(1) DEFAULT 1,
  `notify_booking` tinyint(1) DEFAULT 1,
  `show_phone` tinyint(1) DEFAULT 1,
  `dark_mode` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

<<<<<<< HEAD
INSERT INTO `users` (`id`, `name`, `email`, `password`, `reset_token`, `reset_expires`, `force_password_change`, `phone`, `avatar`, `role`, `created_at`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `address`, `idcard_number`, `trust_score`, `admin_note`, `verified_at`) VALUES
(2, 'User 2', 'user2@gmail.com', '123', NULL, NULL, 0, NULL, NULL, 'user', '2026-04-23 08:39:47', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, 'Admin', 'admin@gmail.com', '123', NULL, NULL, 0, NULL, NULL, 'admin', '2026-04-23 08:39:47', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(5, 'Admin', 'admin123@gmail.com', '$2y$10$o5dhV8yry9Mmv7Cgdq6ZjuWCGYRSNLrReh5G4DTh4eN/xFYhvTNCy', NULL, NULL, 0, '', NULL, 'admin', '2026-04-25 10:29:43', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(6, 'Chủ trọ 1', 'owner@gmail.com', '$2y$10$gU1LggXMYCfg4nEh8NZIv.hcB1L1kTxSJMsiMuwvy2mCzRdact4i2', NULL, NULL, 0, NULL, NULL, 'owner', '2026-04-29 09:27:23', 'approved', 5, '2026-05-13 16:15:55', NULL, NULL, NULL, 0, NULL, NULL),
(7, 'User', 'user123@gmail.com', '$2y$10$udXXi/ARGfseLfIHPa0GE..0qplAgd75dGcDCNt1toASvuJiRJhTa', NULL, NULL, 0, '', NULL, 'user', '2026-05-15 13:07:14', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(8, 'Owner', 'owner123@gmail.com', '$2y$10$a/REVjGxzRqT3BcRRu.I5.FOtLbx2ORNEKRL.jAp4Lg31RXsvqTFC', NULL, NULL, 0, '0193839338', NULL, 'owner', '2026-05-15 13:07:39', 'approved', 5, '2026-05-15 20:08:08', NULL, NULL, NULL, 0, NULL, NULL);
=======
INSERT INTO `users` (`id`, `name`, `email`, `password`, `reset_token`, `reset_expires`, `phone`, `avatar`, `role`, `created_at`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `address`, `idcard_number`, `trust_score`, `admin_note`, `verified_at`, `id_card_front`, `id_card_back`, `bank_name`, `bank_account_no`, `bank_account_name`, `notify_email`, `notify_booking`, `show_phone`, `dark_mode`) VALUES
(2, 'User 2', 'user2@gmail.com', '123', NULL, NULL, NULL, NULL, 'user', '2026-04-23 08:39:47', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(4, 'Admin', 'admin@gmail.com', '123', NULL, NULL, NULL, NULL, 'admin', '2026-04-23 08:39:47', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(5, 'Admin', 'admin123@gmail.com', '$2y$10$o5dhV8yry9Mmv7Cgdq6ZjuWCGYRSNLrReh5G4DTh4eN/xFYhvTNCy', NULL, NULL, '', NULL, 'admin', '2026-04-25 10:29:43', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(6, 'Chủ trọ 1', 'owner@gmail.com', '$2y$10$gU1LggXMYCfg4nEh8NZIv.hcB1L1kTxSJMsiMuwvy2mCzRdact4i2', NULL, NULL, NULL, NULL, 'owner', '2026-04-29 09:27:23', 'approved', 5, '2026-05-14 13:05:10', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(7, 'Bảo Phan', 'baopdq1@qlpt.com', '$2y$10$KN3kCa1p67bAncjLp3AZkuvKk6P6idJdAMcMCufdZxayyFKaSkcxu', NULL, NULL, '0123456789', NULL, 'user', '2026-05-14 05:06:30', 'approved', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(8, 'Bảo Phan', 'admin1234@gmail.com', '$2y$10$I6QhngehzijRnpltd0cdXeRE0Zq5Ne2hsCYWRXpyEqFwDT.PewGTW', NULL, NULL, '0123456789', NULL, 'owner', '2026-05-14 06:06:46', 'approved', 5, '2026-05-14 13:07:58', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0);
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8

-- --------------------------------------------------------

--
-- Table structure for table `utilities`
--

CREATE TABLE `utilities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilities`
--

INSERT INTO `utilities` (`id`, `name`) VALUES
(1, 'Wifi'),
(2, 'Điều hòa'),
(3, 'Máy giặt');

-- --------------------------------------------------------

--
-- Table structure for table `viewing_appointments`
--

CREATE TABLE `viewing_appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `preferred_time` datetime NOT NULL,
  `note` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `balance` bigint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `balance`) VALUES
(2, 2, 3000000),
(4, 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `withdraw_requests`
--

CREATE TABLE `withdraw_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
<<<<<<< HEAD
-- Indexes for table `articles`
=======
<<<<<<< HEAD
=======
-- Chỉ mục cho bảng `articles`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `author_id` (`author_id`);

--
<<<<<<< HEAD
-- Indexes for table `bookings`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Chỉ mục cho bảng `bookings`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `motel_id` (`motel_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
<<<<<<< HEAD
-- Indexes for table `conversations`
=======
<<<<<<< HEAD
-- Chỉ mục cho bảng `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motel_id` (`motel_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `user_id` (`user_id`);

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Chỉ mục cho bảng `conversations`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_conversation` (`user_id`,`owner_id`,`motel_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_last_message_at` (`last_message_at`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `motel_id` (`motel_id`);

--
-- Indexes for table `listing_quality_checks`
--
ALTER TABLE `listing_quality_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_motel_id` (`motel_id`),
  ADD KEY `idx_score` (`score`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_email_ip` (`email`,`ip_address`),
  ADD KEY `idx_login_attempts_created_at` (`created_at`);

--
-- Indexes for table `login_security_state`
--
ALTER TABLE `login_security_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_login_security_identity` (`identity_hash`),
  ADD KEY `idx_login_security_email_ip` (`email`,`ip_address`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_motel_id` (`motel_id`),
  ADD KEY `idx_booking_id` (`booking_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
<<<<<<< HEAD
-- Indexes for table `motels`
=======
<<<<<<< HEAD
-- Chỉ mục cho bảng `monthly_bills`
--
ALTER TABLE `monthly_bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motel_id` (`motel_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `monthly_invoices`
--
ALTER TABLE `monthly_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_period` (`owner_id`,`tenant_user_id`,`motel_id`,`period_year`,`period_month`),
  ADD KEY `idx_tenant` (`tenant_user_id`),
  ADD KEY `idx_owner` (`owner_id`),
  ADD KEY `idx_motel` (`motel_id`);

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Chỉ mục cho bảng `motels`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `motels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `district_id` (`district_id`);

--
-- Indexes for table `motel_images`
--
ALTER TABLE `motel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `motel_id` (`motel_id`);

--
-- Indexes for table `motel_utilities`
--
ALTER TABLE `motel_utilities`
  ADD PRIMARY KEY (`motel_id`,`utility_id`),
  ADD KEY `utility_id` (`utility_id`);

--
<<<<<<< HEAD
-- Indexes for table `news_categories`
=======
<<<<<<< HEAD
=======
-- Chỉ mục cho bảng `news_categories`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `news_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
<<<<<<< HEAD
-- Indexes for table `notifications`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Chỉ mục cho bảng `notifications`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read_at` (`read_at`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_password_resets_token_hash` (`token_hash`),
  ADD KEY `idx_password_resets_user_id` (`user_id`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reporter_id` (`reporter_id`),
  ADD KEY `idx_reported_user_id` (`reported_user_id`),
  ADD KEY `idx_motel_id` (`motel_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`motel_id`),
  ADD KEY `motel_id` (`motel_id`),
  ADD KEY `idx_reviews_status` (`status`);

--
-- Indexes for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_alert_enabled` (`alert_enabled`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_user` (`from_user`),
  ADD KEY `to_user` (`to_user`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `utilities`
--
ALTER TABLE `utilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `viewing_appointments`
--
ALTER TABLE `viewing_appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_motel_id` (`motel_id`),
  ADD KEY `idx_owner_id` (`owner_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_preferred_time` (`preferred_time`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_notes`
--
ALTER TABLE `admin_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `articles`
=======
<<<<<<< HEAD
=======
-- AUTO_INCREMENT cho bảng `articles`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `bookings`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- AUTO_INCREMENT cho bảng `bookings`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `conversations`
=======
<<<<<<< HEAD
-- AUTO_INCREMENT cho bảng `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- AUTO_INCREMENT cho bảng `conversations`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `listing_quality_checks`
--
ALTER TABLE `listing_quality_checks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_security_state`
--
ALTER TABLE `login_security_state`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `motels`
=======
<<<<<<< HEAD
-- AUTO_INCREMENT cho bảng `monthly_bills`
--
ALTER TABLE `monthly_bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `monthly_invoices`
--
ALTER TABLE `monthly_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- AUTO_INCREMENT cho bảng `motels`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `motels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `motel_images`
--
ALTER TABLE `motel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `news_categories`
=======
<<<<<<< HEAD
=======
-- AUTO_INCREMENT cho bảng `news_categories`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `news_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
<<<<<<< HEAD
-- AUTO_INCREMENT for table `notifications`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- AUTO_INCREMENT cho bảng `notifications`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `viewing_appointments`
--
ALTER TABLE `viewing_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
<<<<<<< HEAD
-- Constraints for table `articles`
=======
<<<<<<< HEAD
=======
-- Các ràng buộc cho bảng `articles`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `news_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
<<<<<<< HEAD
-- Constraints for table `bookings`
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Các ràng buộc cho bảng `bookings`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE;

--
<<<<<<< HEAD
-- Constraints for table `favorites`
=======
<<<<<<< HEAD
-- Các ràng buộc cho bảng `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Các ràng buộc cho bảng `favorites`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE;

--
<<<<<<< HEAD
-- Constraints for table `motels`
=======
<<<<<<< HEAD
-- Các ràng buộc cho bảng `monthly_bills`
--
ALTER TABLE `monthly_bills`
  ADD CONSTRAINT `monthly_bills_ibfk_1` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `monthly_bills_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
=======
>>>>>>> f2bc8b2589e58240d3ef382c248c93db5bcc39e6
-- Các ràng buộc cho bảng `motels`
>>>>>>> 86bd390fd85842e98e5636e226783ec4c03994b8
--
ALTER TABLE `motels`
  ADD CONSTRAINT `motels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `motels_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `motels_ibfk_3` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `motel_images`
--
ALTER TABLE `motel_images`
  ADD CONSTRAINT `motel_images_ibfk_1` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `motel_utilities`
--
ALTER TABLE `motel_utilities`
  ADD CONSTRAINT `motel_utilities_ibfk_1` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `motel_utilities_ibfk_2` FOREIGN KEY (`utility_id`) REFERENCES `utilities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`from_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`to_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  ADD CONSTRAINT `withdraw_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
