-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2025 at 05:48 PM
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
-- Database: `hirayafitdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `fullname`, `email`, `role`, `is_active`, `last_login`, `remember_token`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'HirayaFit Admin', 'leiumarsayco@gmail.com', 'super_admin', 1, '2025-05-24 15:46:03', NULL, 'admin_1_1748076724.jpeg', '2025-05-24 08:23:38', '2025-05-24 15:46:03');

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `category` enum('general','support','order_inquiry','complaint','feedback') DEFAULT 'general',
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `user_id`, `admin_id`, `subject`, `status`, `priority`, `category`, `last_message_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'ayos ang products nyo', 'open', 'normal', 'general', '2025-05-24 15:08:39', '2025-05-24 14:18:47', '2025-05-24 15:08:39');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_type` enum('user','admin') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `message_type` enum('general','support','order_inquiry','complaint') DEFAULT 'general',
  `parent_message_id` int(11) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('active','archived','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_type`, `sender_id`, `receiver_type`, `receiver_id`, `subject`, `message`, `is_read`, `message_type`, `parent_message_id`, `attachment_path`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(1, 'user', 1, 'admin', 1, 'ayos ang products nyo', 'ang ganda ng bra nyp', 0, 'general', NULL, NULL, 'normal', 'active', '2025-05-24 14:18:47', '2025-05-24 14:18:47'),
(2, 'user', 1, 'admin', 1, '', 'ang  ganda ng products nyo boss', 0, 'general', NULL, NULL, 'normal', 'active', '2025-05-24 15:08:39', '2025-05-24 15:08:39');

-- --------------------------------------------------------

--
-- Table structure for table `message_participants`
--

CREATE TABLE `message_participants` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `participant_type` enum('user','admin') NOT NULL,
  `participant_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_read_status`
--

CREATE TABLE `message_read_status` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `reader_type` enum('user','admin') NOT NULL,
  `reader_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_read_status`
--

INSERT INTO `message_read_status` (`id`, `message_id`, `reader_type`, `reader_id`, `read_at`) VALUES
(1, 1, 'user', 1, '2025-05-24 14:19:12'),
(4, 2, 'user', 1, '2025-05-24 15:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_type` enum('user','admin') NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `push_notifications` tinyint(1) DEFAULT 1,
  `sms_notifications` tinyint(1) DEFAULT 0,
  `notification_frequency` enum('immediate','hourly','daily') DEFAULT 'immediate',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_preferences`
--

INSERT INTO `notification_preferences` (`id`, `user_type`, `user_id`, `email_notifications`, `push_notifications`, `sms_notifications`, `notification_frequency`, `created_at`, `updated_at`) VALUES
(1, 'user', 1, 1, 1, 0, 'immediate', '2025-05-24 14:12:51', '2025-05-24 14:12:51'),
(2, 'admin', 1, 1, 1, 0, 'immediate', '2025-05-24 14:12:51', '2025-05-24 14:12:51');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `remember_token` varchar(64) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_purpose` enum('PASSWORD_RESET','EMAIL_VERIFICATION','LOGIN') DEFAULT NULL,
  `otp_created_at` timestamp NULL DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `otp_is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `password`, `address`, `phone`, `profile_image`, `is_active`, `remember_token`, `last_login`, `otp_code`, `otp_purpose`, `otp_created_at`, `otp_expires_at`, `otp_is_used`, `created_at`, `updated_at`) VALUES
(1, 'Jhun Sayco', 'jhunsayco70@gmail.com', 'hapon', '$2y$10$LGIUFmxHN1sQ4cmmxAxVLej1uCFudaPIoBcVwRHg39H43057oDKqi', 'Talacsan San Rafael Bulacan', '9633945919', '683183796d1a3_received_338513232261148.jpeg', 1, NULL, '2025-05-24 14:51:40', '880592', 'EMAIL_VERIFICATION', '2025-05-24 02:29:45', '2025-05-24 02:44:45', 1, '2025-05-24 08:29:45', '2025-05-24 14:51:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_last_message` (`last_message_at`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_type`,`sender_id`),
  ADD KEY `idx_receiver` (`receiver_type`,`receiver_id`),
  ADD KEY `idx_conversation` (`sender_type`,`sender_id`,`receiver_type`,`receiver_id`),
  ADD KEY `idx_unread` (`is_read`,`receiver_type`,`receiver_id`),
  ADD KEY `idx_parent` (`parent_message_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `message_participants`
--
ALTER TABLE `message_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_participant` (`conversation_id`,`participant_type`,`participant_id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_participant` (`participant_type`,`participant_id`);

--
-- Indexes for table `message_read_status`
--
ALTER TABLE `message_read_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_read` (`message_id`,`reader_type`,`reader_id`),
  ADD KEY `idx_message` (`message_id`),
  ADD KEY `idx_reader` (`reader_type`,`reader_id`);

--
-- Indexes for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_prefs` (`user_type`,`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `email_2` (`email`,`otp_code`,`otp_is_used`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `message_participants`
--
ALTER TABLE `message_participants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_read_status`
--
ALTER TABLE `message_read_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`parent_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `message_participants`
--
ALTER TABLE `message_participants`
  ADD CONSTRAINT `message_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_read_status`
--
ALTER TABLE `message_read_status`
  ADD CONSTRAINT `message_read_status_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
