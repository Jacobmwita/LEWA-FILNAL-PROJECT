-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2025 at 11:55 AM
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
-- Database: `lewa`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL,
  `min_stock_level` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_assignments`
--

CREATE TABLE `job_assignments` (
  `assignment_id` int(11) NOT NULL,
  `job_card_id` int(11) NOT NULL,
  `mechanic_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_cards`
--

CREATE TABLE `job_cards` (
  `job_card_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','assigned','in_progress','completed','on_hold','cancelled') NOT NULL DEFAULT 'pending',
  `urgency` enum('normal','high','emergency') NOT NULL DEFAULT 'normal',
  `assigned_mechanic_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_cards`
--

INSERT INTO `job_cards` (`job_card_id`, `vehicle_id`, `description`, `status`, `urgency`, `assigned_mechanic_id`, `created_at`, `assigned_at`, `completed_at`, `updated_at`, `created_by_user_id`, `driver_id`) VALUES
(1, 1, 'FIX  THE  FRONT  WHEELS  AND  MAKE  THE  BREAKS  TO  FUNCTION  WELL', 'pending', 'high', NULL, '2025-06-16 09:51:16', NULL, NULL, '2025-06-16 09:51:16', NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `job_parts`
--

CREATE TABLE `job_parts` (
  `job_part_id` int(11) NOT NULL,
  `job_card_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL,
  `status` enum('pending','approved','rejected','issued') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `id_number` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `can_view_all_job_cards` tinyint(1) DEFAULT 0,
  `can_create_job_cards` tinyint(1) DEFAULT 0,
  `can_edit_job_cards` tinyint(1) DEFAULT 0,
  `can_request_services` tinyint(1) DEFAULT 0,
  `can_update_job_status` tinyint(1) DEFAULT 0,
  `can_request_parts` tinyint(1) DEFAULT 0,
  `can_manage_inventory` tinyint(1) DEFAULT 0,
  `can_generate_invoices` tinyint(1) DEFAULT 0,
  `can_view_reports` tinyint(1) DEFAULT 0,
  `can_manage_users` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `dashboard_access` varchar(100) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `user_type`, `phone_number`, `id_number`, `department`, `can_view_all_job_cards`, `can_create_job_cards`, `can_edit_job_cards`, `can_request_services`, `can_update_job_status`, `can_request_parts`, `can_manage_inventory`, `can_generate_invoices`, `can_view_reports`, `can_manage_users`, `is_active`, `dashboard_access`, `reset_token`, `reset_token_expiry`, `created_at`, `updated_at`) VALUES
(1, 'JACOB30', '$2y$10$O8Xb5tW4u2Pf8Wi3kkP/Pe4TI74R/M5ovPW1F2u7kUHRNc/zS1V/G', 'JACOB MWITA', 'jacobmwita30@gmail.com', 'workshop_manager', '0707846323', '42353066', 'Logistics', 1, 0, 1, 0, 0, 0, 0, 0, 1, 1, 1, 'admin_dashboard', NULL, NULL, '2025-06-16 09:25:16', '2025-06-16 09:25:16'),
(2, 'JACOB300', '$2y$10$vxuB.pzVgecJWCqWNseS4u9lCKulx6NxjY0n76Yg5adiCEYu7magW', 'JACOB MWITA', 'jacobmwita330@gmail.com', 'driver', '0707846323', '536657757', 'Anti poaching', 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 1, 'driver_portal', NULL, NULL, '2025-06-16 09:48:08', '2025-06-16 09:48:08'),
(3, 'JACOB3000', '$2y$10$OOuvW3mZgm053XFWLCQjq.39ggfY9w8lm6G9afQWqUFK.ozQqSlM.', 'JACOB MWITA', 'jacobmwita390@gmail.com', 'service_advisor', '0707846323', '65345453', 'Anti poaching', 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 1, 'service_dashboard', NULL, NULL, '2025-06-16 09:52:42', '2025-06-16 09:52:42'),
(4, 'JACOB30000', '$2y$10$nEQzEXlooekpojexYeoa4.wxv5k9IgG4iPiGshgS6QAFvqGJwQwEK', 'JACOB MWITA', 'jacobmwita320@gmail.com', 'mechanic', '0707846323', '2334555', 'Finance', 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 1, 'technician_dashboard', NULL, NULL, '2025-06-16 09:54:00', '2025-06-16 09:54:00');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `make` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `registration_number` varchar(50) NOT NULL,
  `year` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `v_notes` text DEFAULT NULL,
  `v_milage` int(11) DEFAULT 0,
  `engine_number` varchar(100) DEFAULT NULL,
  `chassis_number` varchar(100) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `driver_id`, `make`, `model`, `registration_number`, `year`, `color`, `v_notes`, `v_milage`, `engine_number`, `chassis_number`, `fuel_type`, `created_at`, `updated_at`) VALUES
(1, 2, 'BMW', 'TOYOTA', 'KBY 1890Z', 1900, '0', 'ITS  A  FOUR SEATER  CAR', 12234, '123-Z89', '2374684E4383', 'DISEL', '2025-06-16 09:50:13', '2025-06-16 09:50:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `item_name` (`item_name`);

--
-- Indexes for table `job_assignments`
--
ALTER TABLE `job_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `job_card_id` (`job_card_id`),
  ADD KEY `mechanic_id` (`mechanic_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `job_cards`
--
ALTER TABLE `job_cards`
  ADD PRIMARY KEY (`job_card_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `assigned_mechanic_id` (`assigned_mechanic_id`),
  ADD KEY `fk_created_by_user` (`created_by_user_id`),
  ADD KEY `fk_job_card_driver` (`driver_id`);

--
-- Indexes for table `job_parts`
--
ALTER TABLE `job_parts`
  ADD PRIMARY KEY (`job_part_id`),
  ADD KEY `job_card_id` (`job_card_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_user_type` (`user_type`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `driver_id` (`driver_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_assignments`
--
ALTER TABLE `job_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_cards`
--
ALTER TABLE `job_cards`
  MODIFY `job_card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `job_parts`
--
ALTER TABLE `job_parts`
  MODIFY `job_part_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `job_assignments`
--
ALTER TABLE `job_assignments`
  ADD CONSTRAINT `job_assignments_ibfk_1` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`job_card_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_assignments_ibfk_2` FOREIGN KEY (`mechanic_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_cards`
--
ALTER TABLE `job_cards`
  ADD CONSTRAINT `fk_created_by_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_job_card_driver` FOREIGN KEY (`driver_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `job_cards_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_cards_ibfk_2` FOREIGN KEY (`assigned_mechanic_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `job_parts`
--
ALTER TABLE `job_parts`
  ADD CONSTRAINT `job_parts_ibfk_1` FOREIGN KEY (`job_card_id`) REFERENCES `job_cards` (`job_card_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_parts_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`item_id`);

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
