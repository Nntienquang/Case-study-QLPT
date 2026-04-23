-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2026 at 10:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phongtro`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
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
-- Table structure for table `motels`
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
  `status` enum('pending','approved','hidden') DEFAULT 'pending',
  `count_view` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
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
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `motel_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('user','owner','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilities`
--

CREATE TABLE `utilities` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL
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
-- Indexes for table `bookings`
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
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `motel_id` (`motel_id`);

--
-- Indexes for table `motels`
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
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_id` (`booking_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`motel_id`),
  ADD KEY `motel_id` (`motel_id`);

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
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `utilities`
--
ALTER TABLE `utilities`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motels`
--
ALTER TABLE `motels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `motel_images`
--
ALTER TABLE `motel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilities`
--
ALTER TABLE `utilities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `withdraw_requests`
--
ALTER TABLE `withdraw_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`motel_id`) REFERENCES `motels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `motels`
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

-- ================= USERS =================
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'User 1', 'user1@gmail.com', '123', 'user'),
(2, 'User 2', 'user2@gmail.com', '123', 'user'),
(3, 'Owner 1', 'owner1@gmail.com', '123', 'owner'),
(4, 'Admin', 'admin@gmail.com', '123', 'admin');

-- ================= WALLETS =================
INSERT INTO wallets (user_id, balance) VALUES
(1, 5000000),
(2, 3000000),
(3, 0),
(4, 0);

-- ================= DISTRICTS =================
INSERT INTO districts (id, name) VALUES
(1, 'Quận 1'),
(2, 'Quận 3'),
(3, 'Quận 7');

-- ================= CATEGORIES =================
INSERT INTO categories (id, name) VALUES
(1, 'Phòng trọ'),
(2, 'Chung cư mini'),
(3, 'Căn hộ');

-- ================= MOTELS =================
INSERT INTO motels (id, title, price, area, address, lat, lng, user_id, category_id, district_id, status) VALUES
(1, 'Phòng Q1 giá rẻ', 2000000, 20, 'Q1 HCM', 10.7769, 106.7009, 3, 1, 1, 'approved'),
(2, 'Phòng full nội thất', 3500000, 30, 'Q3 HCM', 10.7820, 106.6910, 3, 2, 2, 'approved'),
(3, 'Căn hộ mini Q7', 5000000, 40, 'Q7 HCM', 10.7379, 106.7310, 3, 3, 3, 'approved');

-- ================= MOTEL IMAGES =================
INSERT INTO motel_images (motel_id, image_url) VALUES
(1, 'img1.jpg'),
(1, 'img2.jpg'),
(2, 'img3.jpg'),
(3, 'img4.jpg');

-- ================= UTILITIES =================
INSERT INTO utilities (id, name) VALUES
(1, 'Wifi'),
(2, 'Điều hòa'),
(3, 'Máy giặt');

-- ================= MOTEL UTILITIES =================
INSERT INTO motel_utilities (motel_id, utility_id) VALUES
(1, 1),
(1, 2),
(2, 1),
(3, 1),
(3, 2),
(3, 3);

-- ================= BOOKINGS =================
INSERT INTO bookings (id, user_id, motel_id, deposit_amount, checkin_date, status) VALUES
(1, 1, 1, 1000000, '2026-05-01', 'completed'),
(2, 2, 2, 1500000, '2026-05-02', 'accepted'),
(3, 1, 3, 2000000, '2026-05-03', 'pending');

-- ================= PAYMENTS =================
INSERT INTO payments (booking_id, amount, fee, method, transaction_code, status) VALUES
(1, 1000000, 10000, 'vnpay', 'TXN001', 'released'),
(2, 1500000, 15000, 'momo', 'TXN002', 'held'),
(3, 2000000, 20000, 'vnpay', 'TXN003', 'pending');

-- ================= TRANSACTIONS =================
INSERT INTO transactions (from_user, to_user, amount, fee, type, booking_id) VALUES
(1, NULL, 1000000, 0, 'deposit', 1),
(NULL, 3, 990000, 10000, 'release', 1),
(NULL, 4, 10000, 0, 'fee', 1);

-- ================= WITHDRAW REQUEST =================
INSERT INTO withdraw_requests (user_id, amount, status) VALUES
(3, 500000, 'pending'),
(3, 1000000, 'approved');

-- ================= FAVORITES =================
INSERT INTO favorites (user_id, motel_id) VALUES
(1, 1),
(1, 2),
(2, 3);

-- ================= REVIEWS =================
INSERT INTO reviews (user_id, motel_id, rating, comment) VALUES
(1, 1, 5, 'Phòng rất tốt'),
(2, 2, 4, 'Ổn áp'),
(1, 3, 3, 'Tạm được');
