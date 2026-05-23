-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 17, 2026 at 12:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 5, 'approve_user', 'user', 6, NULL, NULL, 'Duyệt tài khoản owner: Chủ trọ 1 (owner@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-05-14 06:05:10'),
(2, 5, 'approve_user', 'user', 8, NULL, NULL, 'Duyệt tài khoản owner: Bảo Phan (admin1234@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-05-14 06:07:58'),
(3, 5, 'approve_user', 'user', 8, NULL, NULL, 'Duyệt tài khoản owner: Owner (owner123@gmail.com)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-15 13:08:08'),
(4, 5, 'login_success', 'user', 5, NULL, NULL, 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 08:49:25'),
(5, 5, 'delete_user', 'user', 4, NULL, NULL, 'Xóa tài khoản admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-16 08:54:30'),
(6, 5, 'login_success', 'user', 5, NULL, NULL, 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 05:38:04'),
(7, 11, 'login_success', 'user', 11, NULL, NULL, 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 06:28:05'),
(8, 11, 'login_success', 'user', 11, NULL, NULL, 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 06:29:27'),
(9, 7, 'login_success', 'user', 7, NULL, NULL, 'Đăng nhập thành công', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-17 09:50:20');

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
-- Table structure for table `articles`
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
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_code` varchar(30) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `expected_move_in_date` date DEFAULT NULL,
  `rental_duration_months` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `contact_name` varchar(120) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `deposit_amount` int(11) DEFAULT NULL,
  `total_amount` int(11) NOT NULL DEFAULT 0,
  `payment_status` enum('pending','processing','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `booking_status` enum('pending','waiting_payment','paid','confirmed','cancelled','rejected','expired','completed') NOT NULL DEFAULT 'pending',
  `checkin_date` date DEFAULT NULL,
  `status` enum('pending','paid','accepted','completed','rejected','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `owner_id`, `motel_id`, `check_in_date`, `check_out_date`, `expected_move_in_date`, `rental_duration_months`, `note`, `contact_name`, `contact_phone`, `contact_email`, `deposit_amount`, `total_amount`, `payment_status`, `booking_status`, `checkin_date`, `status`, `created_at`, `cancelled_at`, `cancelled_by`, `cancellation_reason`, `rejected_at`, `rejected_by`, `rejection_reason`, `expires_at`, `updated_at`) VALUES
(1, 'BK260517000001', 14, 12, 1, '2026-05-20', NULL, '2026-05-20', NULL, 'Muốn xem phòng buổi chiều.', 'Demo Người Thuê A', '0902000001', 'demo.user@qlpt.test', 1800000, 1800000, 'pending', 'pending', '2026-05-20', 'pending', '2026-05-17 09:43:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'BK260517000002', 15, 12, 2, '2026-05-24', NULL, '2026-05-24', NULL, 'Đã đặt cọc, chờ chủ xác nhận.', 'Demo Người Thuê B', '0902000002', 'demo.user2@qlpt.test', 3200000, 3200000, 'paid', 'paid', '2026-05-24', 'paid', '2026-05-17 09:43:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'BK260512000003', 14, 12, 3, '2026-04-27', NULL, '2026-04-27', NULL, 'Đã được nhận phòng.', 'Demo Người Thuê A', '0902000001', 'demo.user@qlpt.test', 4500000, 4500000, 'paid', 'confirmed', '2026-04-27', 'accepted', '2026-05-12 09:43:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'BK260417000004', 15, 12, 1, '2026-03-18', NULL, '2026-03-18', NULL, 'Đơn đã hoàn tất để test review.', 'Demo Người Thuê B', '0902000002', 'demo.user2@qlpt.test', 1800000, 1800000, 'paid', 'completed', '2026-03-18', 'completed', '2026-04-17 09:43:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `booking_room_holds`
--

CREATE TABLE `booking_room_holds` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hold_status` enum('active','released','expired','converted') NOT NULL DEFAULT 'active',
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
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
-- Table structure for table `contracts`
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
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user_id`, `owner_id`, `motel_id`, `last_message_at`, `created_at`) VALUES
(1, 14, 12, 1, '2026-05-17 16:43:14', '2026-05-17 09:43:14');

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `province_code` varchar(20) DEFAULT NULL,
  `district_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `name`, `province_code`, `district_code`) VALUES
(1, 'Quận 1', NULL, NULL),
(2, 'Quận 3', NULL, NULL),
(3, 'Quận 7', NULL, NULL);

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

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `motel_id`) VALUES
(1, 14, 1),
(2, 14, 2),
(3, 15, 3);

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

--
-- Dumping data for table `maintenance_requests`
--

INSERT INTO `maintenance_requests` (`id`, `user_id`, `owner_id`, `motel_id`, `booking_id`, `title`, `description`, `image_url`, `priority`, `status`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 14, 12, 3, 3, 'Vòi nước bị rò', 'Vòi nước trong phòng tắm bị rò nhẹ, cần kiểm tra.', NULL, 'normal', 'open', NULL, '2026-05-17 09:43:14', NULL),
(2, 15, 12, 1, 4, 'Bóng đèn hỏng', 'Bóng đèn khu vực bàn học bị hỏng.', NULL, 'low', 'resolved', NULL, '2026-05-05 09:43:14', '2026-05-07 16:43:14');

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

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `conversation_id`, `sender_id`, `body`, `read_at`, `created_at`) VALUES
(1, 1, 14, 'Chào anh/chị, phòng này còn trống không ạ?', NULL, '2026-05-17 09:13:14'),
(2, 1, 12, 'Phòng còn trống, bạn có thể đặt lịch xem phòng nhé.', NULL, '2026-05-17 09:23:14');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_bills`
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

--
-- Dumping data for table `monthly_bills`
--

INSERT INTO `monthly_bills` (`id`, `motel_id`, `user_id`, `billing_month`, `billing_year`, `elec_old`, `elec_new`, `elec_price`, `water_old`, `water_new`, `water_price`, `trash_fee`, `internet_fee`, `total_amount`, `status`, `created_at`) VALUES
(1, 3, 14, 5, 2026, 120, 160, 3500, 30, 38, 20000, 50000, 100000, 450000, 'unpaid', '2026-05-17 09:43:14'),
(2, 1, 15, 4, 2026, 80, 110, 3500, 20, 25, 20000, 50000, 100000, 355000, 'paid', '2026-04-27 09:43:14');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_invoices`
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
-- Table structure for table `motels`
--

CREATE TABLE `motels` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL,
  `deposit_amount` int(11) NOT NULL DEFAULT 0,
  `area` int(11) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `province_code` varchar(20) DEFAULT NULL,
  `province_name` varchar(120) DEFAULT NULL,
  `district_code` varchar(20) DEFAULT NULL,
  `district_name` varchar(120) DEFAULT NULL,
  `ward_code` varchar(20) DEFAULT NULL,
  `ward_name` varchar(120) DEFAULT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `address_api_source` varchar(80) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `district_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','hidden','rejected') DEFAULT 'pending',
  `room_status` enum('available','reserved','rented','unavailable') NOT NULL DEFAULT 'available',
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `count_view` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `max_people` int(11) DEFAULT NULL,
  `utilities` text DEFAULT NULL,
  `furniture` text DEFAULT NULL,
  `verification_status` varchar(30) NOT NULL DEFAULT 'unverified',
  `health_score` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `available_from` date DEFAULT NULL,
  `service_fee` int(11) NOT NULL DEFAULT 0,
  `electricity_unit_price` int(11) NOT NULL DEFAULT 0,
  `water_fee_per_person` int(11) NOT NULL DEFAULT 0,
  `internet_fee` int(11) NOT NULL DEFAULT 0,
  `parking_fee` int(11) NOT NULL DEFAULT 0,
  `other_fee` int(11) NOT NULL DEFAULT 0,
  `service_note` varchar(255) DEFAULT NULL,
  `deposit_months` decimal(3,1) NOT NULL DEFAULT 1.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motels`
--

INSERT INTO `motels` (`id`, `title`, `description`, `price`, `deposit_amount`, `area`, `address`, `province_code`, `province_name`, `district_code`, `district_name`, `ward_code`, `ward_name`, `street_address`, `address_api_source`, `lat`, `lng`, `phone`, `user_id`, `category_id`, `district_id`, `status`, `room_status`, `rejection_reason`, `rejected_by`, `rejected_at`, `count_view`, `created_at`, `updated_at`, `bedrooms`, `bathrooms`, `max_people`, `utilities`, `furniture`, `verification_status`, `health_score`, `is_featured`, `available_from`, `service_fee`, `electricity_unit_price`, `water_fee_per_person`, `internet_fee`, `parking_fee`, `other_fee`, `service_note`, `deposit_months`) VALUES
(1, 'DEMO - Phòng gần Đại học Vinh đầy đủ nội thất', 'Phòng sạch, có cửa sổ, wifi, máy giặt, phù hợp sinh viên và người đi làm.', 1800000, 1800000, 25, '182 Lê Duẩn, Phường Trường Thi, Thành phố Vinh, Nghệ An', '40', 'Nghệ An', '412', 'Thành phố Vinh', '16666', 'Trường Thi', '182 Lê Duẩn', 'seed', 18.67910000, 105.68190000, '0901000001', 12, 1, 1, 'approved', 'available', NULL, NULL, NULL, 35, '2026-05-17 09:43:14', NULL, 1, 1, NULL, 'Wifi,Điều hòa,Máy giặt', NULL, 'verified', 92, 1, '2026-05-17', 100000, 3500, 100000, 0, 0, 0, NULL, 1.0),
(2, 'DEMO - Chung cư mini yên tĩnh có ban công', 'Căn hộ mini riêng tư, an ninh, có khu để xe và ban công thoáng.', 3200000, 4800000, 38, '15 Nguyễn Văn Cừ, Thành phố Vinh, Nghệ An', '40', 'Nghệ An', '412', 'Thành phố Vinh', '16668', 'Bến Thủy', '15 Nguyễn Văn Cừ', 'seed', 18.66850000, 105.69210000, '0901000001', 12, 2, 2, 'approved', 'available', NULL, NULL, NULL, 18, '2026-05-17 09:43:14', NULL, 1, 1, NULL, 'Wifi,Điều hòa', NULL, 'verified', 85, 0, '2026-05-22', 150000, 3500, 100000, 0, 0, 0, NULL, 1.5),
(3, 'DEMO - Căn hộ studio cao cấp trung tâm', 'Studio mới, nội thất đẹp, phù hợp người đi làm cần không gian riêng.', 4500000, 9000000, 45, '88 Lê Mao, Thành phố Vinh, Nghệ An', '40', 'Nghệ An', '412', 'Thành phố Vinh', '16670', 'Lê Mao', '88 Lê Mao', 'seed', 18.67490000, 105.67630000, '0901000001', 12, 3, 3, 'approved', 'available', NULL, NULL, NULL, 53, '2026-05-17 09:43:14', NULL, 1, 1, NULL, 'Wifi,Điều hòa,Máy giặt', NULL, 'verified', 96, 1, '2026-05-17', 200000, 3500, 100000, 0, 0, 0, NULL, 2.0),
(4, 'DEMO - Phòng owner mới chờ admin duyệt', 'Tin đang chờ admin kiểm duyệt để test luồng duyệt phòng.', 1500000, 1500000, 20, 'Phường Hưng Dũng, Thành phố Vinh, Nghệ An', '40', 'Nghệ An', '412', 'Thành phố Vinh', '16672', 'Hưng Dũng', 'Ngõ 12', 'seed', 18.69000000, 105.70000000, '0901000002', 13, 1, 1, 'pending', 'available', NULL, NULL, NULL, 0, '2026-05-17 09:43:14', NULL, 1, 1, NULL, 'Wifi', NULL, 'unverified', 70, 0, '2026-05-17', 50000, 3500, 100000, 0, 0, 0, NULL, 1.0),
(5, 'DEMO - Phòng bị ẩn để test admin/owner', 'Tin đã bị ẩn khỏi public.', 2100000, 2100000, 26, 'Đường Nguyễn Du, TP Vinh', '40', 'Nghệ An', '412', 'Thành phố Vinh', NULL, NULL, 'Nguyễn Du', 'seed', NULL, NULL, '0901000001', 12, 1, 1, 'hidden', 'available', NULL, NULL, NULL, 7, '2026-05-17 09:43:14', NULL, 1, 1, NULL, 'Wifi', NULL, 'verified', 60, 0, '2026-05-17', 80000, 3500, 100000, 0, 0, 0, NULL, 1.0),
(6, 'DEMO - Phòng bị từ chối thiếu thông tin', 'Tin bị từ chối để test luồng rejection.', 1200000, 1200000, 16, 'Địa chỉ chưa rõ', '40', 'Nghệ An', '412', 'Thành phố Vinh', NULL, NULL, 'Địa chỉ chưa rõ', 'seed', NULL, NULL, '0901000001', 12, 1, 1, 'rejected', 'available', 'Thiếu ảnh rõ ràng và địa chỉ chưa đầy đủ.', 5, '2026-05-17 16:43:14', 2, '2026-05-17 09:43:14', NULL, 1, 1, NULL, '', NULL, 'unverified', 35, 0, '2026-05-17', 0, 3500, 100000, 0, 0, 0, NULL, 1.0);

-- --------------------------------------------------------

--
-- Table structure for table `motel_images`
--

CREATE TABLE `motel_images` (
  `id` int(11) NOT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motel_images`
--

INSERT INTO `motel_images` (`id`, `motel_id`, `image_url`) VALUES
(1, 1, 'uploads/motels/motel_6a09869feab09.jpg'),
(2, 1, 'uploads/motels/motel_6a096b186de22.jpeg'),
(3, 2, 'uploads/motels/motel_6a096b186de22.jpeg'),
(4, 3, 'uploads/motels/motel_6a09869feab09.jpg'),
(5, 4, 'uploads/motels/motel_6a096b186de22.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `motel_utilities`
--

CREATE TABLE `motel_utilities` (
  `motel_id` int(11) NOT NULL,
  `utility_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `motel_utilities`
--

INSERT INTO `motel_utilities` (`motel_id`, `utility_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(2, 2),
(3, 1),
(3, 2),
(3, 3),
(4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `news_categories`
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
-- Table structure for table `notifications`
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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `body`, `link`, `read_at`, `created_at`) VALUES
(1, 12, 'booking_status', 'Có booking mới', 'Demo Người Thuê A vừa gửi yêu cầu đặt phòng.', 'owner/bookings.php', NULL, '2026-05-17 09:43:14'),
(2, 14, 'viewing_status', 'Lịch xem phòng đang chờ xác nhận', 'Chủ phòng sẽ phản hồi lịch xem của bạn.', 'user/dashboard.php', NULL, '2026-05-17 09:43:14'),
(3, 15, 'payment', 'Thanh toán đã được ghi nhận', 'Khoản đặt cọc của bạn đang được giữ an toàn.', 'user/my-bookings.php', '2026-05-17 16:43:14', '2026-05-16 09:43:14');

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
  `payment_code` varchar(30) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `amount` int(11) DEFAULT NULL,
  `fee` int(11) DEFAULT 0,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','processing','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
  `method` varchar(50) DEFAULT NULL,
  `transaction_code` varchar(255) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `gateway_response` longtext DEFAULT NULL,
  `status` enum('pending','held','released','refunded') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_code`, `booking_id`, `amount`, `fee`, `payment_method`, `payment_status`, `method`, `transaction_code`, `paid_at`, `gateway_response`, `status`, `created_at`, `updated_at`) VALUES
(1, 'PAY260517000001', 2, 3200000, 32000, 'bank_transfer', 'paid', 'bank_transfer', 'DEMO-PAY-PAID-001', '2026-05-17 16:43:14', NULL, 'held', '2026-05-17 09:43:14', NULL),
(2, 'PAY260513000002', 3, 4500000, 45000, 'momo', 'paid', 'momo', 'DEMO-PAY-ACC-001', '2026-05-13 16:43:14', NULL, 'released', '2026-05-13 09:43:14', NULL),
(3, 'PAY260422000003', 4, 1800000, 18000, 'cash', 'paid', 'cash', 'DEMO-PAY-COMP-001', '2026-04-22 16:43:14', NULL, 'released', '2026-04-22 09:43:14', NULL);

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

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `reporter_id`, `reported_user_id`, `motel_id`, `report_type`, `reason`, `status`, `admin_note`, `handled_by`, `handled_at`, `created_at`) VALUES
(1, 14, 12, 2, 'price_mismatch', 'Giá phòng trên tin khác với giá chủ báo khi nhắn tin.', 'pending', NULL, NULL, NULL, '2026-05-17 09:43:14'),
(2, 15, 12, 1, 'content', 'Tin đã được kiểm tra lại, không phát hiện vi phạm.', 'resolved', 'Đã kiểm tra nội dung và ảnh.', 5, '2026-05-17 16:43:14', '2026-05-10 09:43:14');

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

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `motel_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 15, 1, 5, 'Phòng sạch, chủ hỗ trợ nhanh, đúng như mô tả.', 'visible', '2026-05-02 09:43:14'),
(2, 14, 3, 4, 'Vị trí tốt, nội thất ổn, giá hơi cao nhưng đáng cân nhắc.', 'visible', '2026-05-14 09:43:14');

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

--
-- Dumping data for table `saved_searches`
--

INSERT INTO `saved_searches` (`id`, `user_id`, `name`, `keyword`, `district_id`, `category_id`, `price_min`, `price_max`, `area_min`, `alert_enabled`, `last_notified_at`, `created_at`) VALUES
(1, 14, 'Phòng gần trường dưới 2 triệu', 'gần đại học', 1, 1, 1000000, 2000000, 18, 1, NULL, '2026-05-17 09:43:14'),
(2, 15, 'Studio trung tâm', 'studio', 3, 3, 3000000, 5000000, 30, 1, NULL, '2026-05-17 09:43:14');

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
(3, NULL, 4, 10000, 0, 'fee', NULL, '2026-04-23 08:39:47'),
(4, 15, 12, 3200000, 32000, 'deposit', 2, '2026-05-17 09:43:14'),
(5, 14, 12, 4455000, 45000, 'release', 3, '2026-05-13 09:43:14'),
(6, 15, 12, 1782000, 18000, 'release', 4, '2026-04-22 09:43:14'),
(7, NULL, 5, 95000, 0, 'fee', 4, '2026-04-22 09:43:14');

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
  `owner_verification_status` enum('not_required','pending_verification','submitted','approved','rejected') NOT NULL DEFAULT 'not_required',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `idcard_number` varchar(50) DEFAULT NULL,
  `trust_score` int(11) NOT NULL DEFAULT 0,
  `admin_note` text DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_submitted_at` datetime DEFAULT NULL,
  `verification_reviewed_by` int(11) DEFAULT NULL,
  `verification_reviewed_at` datetime DEFAULT NULL,
  `verification_rejection_reason` text DEFAULT NULL,
  `id_card_front` varchar(255) DEFAULT NULL,
  `id_card_back` varchar(255) DEFAULT NULL,
  `selfie_image` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `bank_verified_at` datetime DEFAULT NULL,
  `notify_email` tinyint(1) DEFAULT 1,
  `notify_booking` tinyint(1) DEFAULT 1,
  `show_phone` tinyint(1) DEFAULT 1,
  `dark_mode` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `reset_token`, `reset_expires`, `force_password_change`, `phone`, `avatar`, `role`, `created_at`, `status`, `owner_verification_status`, `approved_by`, `approved_at`, `rejection_reason`, `address`, `idcard_number`, `trust_score`, `admin_note`, `verified_at`, `verification_submitted_at`, `verification_reviewed_by`, `verification_reviewed_at`, `verification_rejection_reason`, `id_card_front`, `id_card_back`, `selfie_image`, `bank_name`, `bank_account_no`, `bank_account_name`, `bank_verified_at`, `notify_email`, `notify_booking`, `show_phone`, `dark_mode`) VALUES
(2, 'User 2', 'user2@gmail.com', '123', NULL, NULL, 0, NULL, NULL, 'user', '2026-04-23 08:39:47', 'approved', 'not_required', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(5, 'Admin', 'admin123@gmail.com', '$2y$10$o5dhV8yry9Mmv7Cgdq6ZjuWCGYRSNLrReh5G4DTh4eN/xFYhvTNCy', NULL, NULL, 0, NULL, NULL, 'admin', '2026-04-25 10:29:43', 'approved', 'not_required', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(6, 'Chủ trọ 1', 'owner@gmail.com', '$2y$10$gU1LggXMYCfg4nEh8NZIv.hcB1L1kTxSJMsiMuwvy2mCzRdact4i2', NULL, NULL, 0, NULL, NULL, 'owner', '2026-04-29 09:27:23', 'approved', 'approved', 5, '2026-05-13 16:15:55', NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, '2026-05-13 16:15:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(7, 'User', 'user123@gmail.com', '$2y$10$udXXi/ARGfseLfIHPa0GE..0qplAgd75dGcDCNt1toASvuJiRJhTa', NULL, NULL, 0, NULL, NULL, 'user', '2026-05-15 13:07:14', 'approved', 'not_required', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(8, 'Owner', 'owner123@gmail.com', '$2y$10$a/REVjGxzRqT3BcRRu.I5.FOtLbx2ORNEKRL.jAp4Lg31RXsvqTFC', NULL, NULL, 0, '0193839338', NULL, 'owner', '2026-05-15 13:07:39', 'approved', 'approved', 5, '2026-05-15 20:08:08', NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, '2026-05-15 20:08:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(9, 'Bảo Phan', 'baopdq1@qlpt.com', '$2y$10$KN3kCa1p67bAncjLp3AZkuvKk6P6idJdAMcMCufdZxayyFKaSkcxu', NULL, NULL, 0, '0123456789', NULL, 'user', '2026-05-14 05:06:30', 'approved', 'not_required', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(10, 'Bảo Phan', 'admin1234@gmail.com', '$2y$10$I6QhngehzijRnpltd0cdXeRE0Zq5Ne2hsCYWRXpyEqFwDT.PewGTW', NULL, NULL, 0, '0123456790', NULL, 'owner', '2026-05-14 06:06:46', 'approved', 'approved', 5, '2026-05-14 13:07:58', NULL, NULL, NULL, 0, NULL, NULL, NULL, 5, '2026-05-14 13:07:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(11, 'Nguyễn Văn Doanh', 'user5@gmail.com', '$2y$10$hle/h5oru2xTUeU5YMm3MusPsJ4btCuACrm7Csm4dPKVF4olPCGBy', NULL, NULL, 0, '0393833839', NULL, 'user', '2026-05-17 06:27:51', 'approved', 'not_required', NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(12, 'Demo Owner Đã Duyệt', 'demo.owner@qlpt.test', '$2y$10$ojFGZKViCYMmt4eQRunqKu/G72PEpVRbPAdsC90d64NBjRRpZMIwq', NULL, NULL, 0, '0901000001', NULL, 'owner', '2026-05-17 09:43:14', 'approved', 'approved', 5, '2026-05-17 16:43:14', NULL, '182 Lê Duẩn, TP Vinh', '186000000001', 86, NULL, '2026-05-17 16:43:14', NULL, 5, '2026-05-17 16:43:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(13, 'Demo Owner Chờ Duyệt', 'demo.owner.pending@qlpt.test', '$2y$10$ojFGZKViCYMmt4eQRunqKu/G72PEpVRbPAdsC90d64NBjRRpZMIwq', NULL, NULL, 0, '0901000002', NULL, 'owner', '2026-05-17 09:43:14', 'pending', 'submitted', NULL, NULL, NULL, 'Trường Thi, TP Vinh', '186000000002', 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(14, 'Demo Người Thuê A', 'demo.user@qlpt.test', '$2y$10$ojFGZKViCYMmt4eQRunqKu/G72PEpVRbPAdsC90d64NBjRRpZMIwq', NULL, NULL, 0, '0902000001', NULL, 'user', '2026-05-17 09:43:14', 'approved', 'not_required', NULL, NULL, NULL, 'Đại học Vinh', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(15, 'Demo Người Thuê B', 'demo.user2@qlpt.test', '$2y$10$ojFGZKViCYMmt4eQRunqKu/G72PEpVRbPAdsC90d64NBjRRpZMIwq', NULL, NULL, 0, '0902000002', NULL, 'user', '2026-05-17 09:43:14', 'approved', 'not_required', NULL, NULL, NULL, 'Bến Thủy, TP Vinh', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0);

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

--
-- Dumping data for table `viewing_appointments`
--

INSERT INTO `viewing_appointments` (`id`, `user_id`, `motel_id`, `owner_id`, `preferred_time`, `note`, `status`, `created_at`, `updated_at`) VALUES
(1, 14, 1, 12, '2026-05-18 16:43:14', 'Em muốn xem phòng sau 17h.', 'pending', '2026-05-17 09:43:14', NULL),
(2, 15, 2, 12, '2026-05-19 16:43:14', 'Có thể xem cuối tuần.', 'accepted', '2026-05-17 09:43:14', '2026-05-17 16:43:14'),
(3, 14, 3, 12, '2026-05-14 16:43:14', 'Không còn nhu cầu.', 'cancelled', '2026-05-12 09:43:14', '2026-05-17 16:43:14');

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
(4, 4, 0),
(5, 12, 2500000),
(6, 14, 1500000),
(7, 15, 700000);

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
-- Dumping data for table `withdraw_requests`
--

INSERT INTO `withdraw_requests` (`id`, `user_id`, `amount`, `status`, `created_at`) VALUES
(1, 12, 500000, 'pending', '2026-05-17 09:43:14'),
(2, 12, 300000, 'approved', '2026-05-07 09:43:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_admin_id` (`admin_id`),
  ADD KEY `idx_activity_logs_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_activity_logs_created_at` (`created_at`);

--
-- Indexes for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_articles_slug` (`slug`),
  ADD KEY `idx_articles_category_id` (`category_id`),
  ADD KEY `idx_articles_author_id` (`author_id`),
  ADD KEY `idx_articles_status` (`status`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bookings_booking_code` (`booking_code`),
  ADD KEY `idx_bookings_user_id` (`user_id`),
  ADD KEY `idx_bookings_motel_id` (`motel_id`),
  ADD KEY `idx_bookings_status` (`status`),
  ADD KEY `idx_bookings_owner_id` (`owner_id`),
  ADD KEY `idx_bookings_booking_status` (`booking_status`),
  ADD KEY `idx_bookings_payment_status` (`payment_status`),
  ADD KEY `idx_bookings_motel_status` (`motel_id`,`booking_status`);

--
-- Indexes for table `booking_room_holds`
--
ALTER TABLE `booking_room_holds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_booking_room_holds_booking` (`booking_id`),
  ADD KEY `idx_booking_room_holds_motel_status` (`motel_id`,`hold_status`,`expires_at`),
  ADD KEY `idx_booking_room_holds_user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_contracts_motel_id` (`motel_id`),
  ADD KEY `idx_contracts_owner_id` (`owner_id`),
  ADD KEY `idx_contracts_user_id` (`user_id`),
  ADD KEY `idx_contracts_status` (`status`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversations_user_id` (`user_id`),
  ADD KEY `idx_conversations_owner_id` (`owner_id`),
  ADD KEY `idx_conversations_motel_id` (`motel_id`),
  ADD KEY `idx_conversations_last_message_at` (`last_message_at`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_districts_codes` (`province_code`,`district_code`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_logs_user_id` (`user_id`),
  ADD KEY `idx_email_logs_status` (`status`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_favorites_user_motel` (`user_id`,`motel_id`),
  ADD KEY `idx_favorites_motel_id` (`motel_id`);

--
-- Indexes for table `listing_quality_checks`
--
ALTER TABLE `listing_quality_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_listing_quality_checks_motel_id` (`motel_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_attempts_identity` (`email`,`ip_address`),
  ADD KEY `idx_login_attempts_created_at` (`created_at`);

--
-- Indexes for table `login_security_state`
--
ALTER TABLE `login_security_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_login_security_state_identity_hash` (`identity_hash`),
  ADD KEY `idx_login_security_state_locked_until` (`locked_until`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_maintenance_requests_user_id` (`user_id`),
  ADD KEY `idx_maintenance_requests_owner_id` (`owner_id`),
  ADD KEY `idx_maintenance_requests_motel_id` (`motel_id`),
  ADD KEY `idx_maintenance_requests_status` (`status`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_conversation_id` (`conversation_id`),
  ADD KEY `idx_messages_sender_id` (`sender_id`),
  ADD KEY `idx_messages_created_at` (`created_at`);

--
-- Indexes for table `monthly_bills`
--
ALTER TABLE `monthly_bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_monthly_bills_period` (`motel_id`,`user_id`,`billing_month`,`billing_year`),
  ADD KEY `idx_monthly_bills_user_id` (`user_id`),
  ADD KEY `idx_monthly_bills_status` (`status`);

--
-- Indexes for table `monthly_invoices`
--
ALTER TABLE `monthly_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_monthly_invoices_period` (`owner_id`,`tenant_user_id`,`motel_id`,`period_month`,`period_year`),
  ADD KEY `idx_monthly_invoices_tenant_user_id` (`tenant_user_id`),
  ADD KEY `idx_monthly_invoices_motel_id` (`motel_id`);

--
-- Indexes for table `motels`
--
ALTER TABLE `motels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_motels_user_id` (`user_id`),
  ADD KEY `idx_motels_category_id` (`category_id`),
  ADD KEY `idx_motels_district_id` (`district_id`),
  ADD KEY `idx_motels_status` (`status`),
  ADD KEY `idx_motels_location_codes` (`province_code`,`district_code`,`ward_code`),
  ADD KEY `idx_motels_room_status` (`room_status`),
  ADD KEY `idx_motels_price_area` (`price`,`area`);

--
-- Indexes for table `motel_images`
--
ALTER TABLE `motel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_motel_images_motel_id` (`motel_id`);

--
-- Indexes for table `motel_utilities`
--
ALTER TABLE `motel_utilities`
  ADD PRIMARY KEY (`motel_id`,`utility_id`),
  ADD KEY `idx_motel_utilities_utility_id` (`utility_id`);

--
-- Indexes for table `news_categories`
--
ALTER TABLE `news_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_news_categories_slug` (`slug`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_id` (`user_id`),
  ADD KEY `idx_notifications_read_at` (`read_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_password_resets_token_hash` (`token_hash`),
  ADD KEY `idx_password_resets_user_id` (`user_id`),
  ADD KEY `idx_password_resets_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payments_payment_code` (`payment_code`),
  ADD KEY `idx_payments_booking_id` (`booking_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_payment_status` (`payment_status`),
  ADD KEY `idx_payments_transaction_code` (`transaction_code`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reports_reporter_id` (`reporter_id`),
  ADD KEY `idx_reports_reported_user_id` (`reported_user_id`),
  ADD KEY `idx_reports_motel_id` (`motel_id`),
  ADD KEY `idx_reports_status` (`status`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_user_id` (`user_id`),
  ADD KEY `idx_reviews_motel_id` (`motel_id`),
  ADD KEY `idx_reviews_status` (`status`);

--
-- Indexes for table `saved_searches`
--
ALTER TABLE `saved_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_saved_searches_user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transactions_from_user` (`from_user`),
  ADD KEY `idx_transactions_to_user` (`to_user`),
  ADD KEY `idx_transactions_booking_id` (`booking_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_phone` (`phone`),
  ADD KEY `idx_users_role_status` (`role`,`status`),
  ADD KEY `idx_users_owner_verification_status` (`owner_verification_status`);

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
  ADD KEY `idx_viewing_appointments_user_id` (`user_id`),
  ADD KEY `idx_viewing_appointments_motel_id` (`motel_id`),
  ADD KEY `idx_viewing_appointments_owner_id` (`owner_id`),
  ADD KEY `idx_viewing_appointments_status` (`status`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wallets_user_id` (`user_id`);

--
-- Indexes for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_withdraw_requests_user_id` (`user_id`),
  ADD KEY `idx_withdraw_requests_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admin_notes`
--
ALTER TABLE `admin_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking_room_holds`
--
ALTER TABLE `booking_room_holds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `monthly_bills`
--
ALTER TABLE `monthly_bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `monthly_invoices`
--
ALTER TABLE `monthly_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motels`
--
ALTER TABLE `motels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `motel_images`
--
ALTER TABLE `motel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `news_categories`
--
ALTER TABLE `news_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `saved_searches`
--
ALTER TABLE `saved_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `viewing_appointments`
--
ALTER TABLE `viewing_appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- Table structure for table `wishlists`
--
CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `motel_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`, `motel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `vouchers`
--
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `discount_amount` int(11) DEFAULT 0,
  `discount_percent` int(11) DEFAULT 0,
  `min_spend` int(11) DEFAULT 0,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `usage_limit` int(11) DEFAULT 100,
  `used_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bổ sung cột cho motels
ALTER TABLE `motels`
ADD COLUMN `old_price` int(11) DEFAULT NULL AFTER `price`,
ADD COLUMN `is_flash_sale` tinyint(1) NOT NULL DEFAULT 0 AFTER `old_price`,
ADD COLUMN `badge_label` varchar(50) DEFAULT NULL AFTER `is_flash_sale`,
ADD COLUMN `amenities_json` text DEFAULT NULL AFTER `badge_label`;

-- Bổ sung cột cho bookings
ALTER TABLE `bookings`
ADD COLUMN `voucher_id` int(11) DEFAULT NULL AFTER `total_amount`,
ADD COLUMN `discount_applied` int(11) DEFAULT 0 AFTER `voucher_id`,
ADD COLUMN `final_amount` int(11) NOT NULL DEFAULT 0 AFTER `discount_applied`,
ADD COLUMN `payment_method` enum('cod','banking','momo','vnpay') DEFAULT 'cod' AFTER `payment_status`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
