-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 07:17 AM
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
-- Database: `junkgenie`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_id` int(11) NOT NULL,
  `pickup_date` date NOT NULL,
  `pickup_status` enum('Pending','Accepted','Rejected','Completed','Cancelled') DEFAULT 'Pending',
  `assigned_employee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `address_id`, `pickup_date`, `pickup_status`, `assigned_employee_id`, `created_at`, `updated_at`) VALUES
(1, 34, 3, '2025-03-24', 'Completed', 5, '2025-03-22 08:27:51', '2025-03-24 09:29:21'),
(2, 40, 4, '2025-03-24', 'Completed', 4, '2025-03-23 09:04:51', '2025-03-24 05:07:16'),
(3, 34, 3, '2025-03-25', 'Completed', 6, '2025-03-25 06:16:41', '2025-03-25 06:30:17'),
(4, 27, 5, '2025-03-25', 'Rejected', 7, '2025-03-25 07:51:29', '2025-03-25 07:53:50'),
(5, 27, 5, '2025-03-25', 'Completed', 7, '2025-03-25 08:00:05', '2025-03-25 10:06:59'),
(6, 34, 3, '2025-03-25', 'Completed', 5, '2025-03-25 10:18:03', '2025-03-25 10:21:48'),
(8, 4, 8, '2025-03-27', 'Cancelled', NULL, '2025-03-27 09:01:15', '2025-03-27 09:10:50'),
(9, 4, 8, '2025-03-27', 'Pending', NULL, '2025-03-27 09:17:06', '2025-03-27 09:17:06');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `image`, `description`) VALUES
(1, 1, 1, 'uploads/cart/1742632071_fridge.png', 'This 15-year-old, non-functional, single-door refrigerator is ready for scrapping due to its outdated condition'),
(2, 2, 2, 'uploads/cart/1742720691_comp.jpeg', 'The old computer is partially functional, slightly damaged, and ready to be scrapped.'),
(3, 3, 5, 'uploads/cart/1742883401_r.png', 'A double-door refrigerator that is completely non-functional and not working at all.'),
(4, 3, 6, 'uploads/cart/1742883401_oven1.png', 'A 3-year-old oven that is not working and has malfunctioned'),
(5, 4, 4, 'uploads/cart/1742889089_B.PNG', 'mobile phone old'),
(6, 5, 4, 'uploads/cart/1742889605_mobile.jpg', 'old mobile phone'),
(7, 6, 1, 'uploads/cart/1742897883_r.png', 'bla'),
(9, 8, 10, 'uploads/cart/1743066075_wallpaper.jpg', 'an old cpu'),
(10, 9, 10, 'uploads/cart/1743067026_cpu1.jpg', 'Old non-functional CPU.'),
(11, 9, 9, 'uploads/cart/1743067026_lcdmonitor1.jpg', 'Old non-functional Monitor');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Large Household appliances', 'electrical or electronic devices used in households for various purposes, characterized by their substantial size and weight', 1, '2025-03-12 08:26:45', '2025-03-25 05:30:40'),
(2, 'Small household appliances', 'electrical or electronic devices used in households for various purposes, characterized by their compact size and portability.', 1, '2025-03-12 08:45:26', '2025-03-12 08:49:01'),
(3, 'IT & Telecommunication equipment', 'electronic devices and tools used for information processing, transmission, and communications.', 1, '2025-03-12 09:08:52', '2025-03-25 04:02:35'),
(4, 'Consumer Electronics', 'TVs, audio systems, cameras.', 1, '2025-03-25 05:30:36', '2025-03-25 06:47:33'),
(5, 'Lighting Devices', 'Lighting devices like fluorescent lamps and LEDs can release toxic substances into the environment if not properly recycled as e-waste.', 1, '2025-03-27 06:26:31', '2025-03-27 06:50:10');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `city_id` int(11) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`city_id`, `city_name`, `is_active`, `created_at`, `user_id`) VALUES
(1, 'Kochi', 1, '2025-02-17 15:25:53', NULL),
(3, 'Thiruvananthapuram', 1, '2025-02-17 15:26:07', NULL),
(9, 'Thrissur', 1, '2025-02-17 15:57:32', NULL),
(10, 'Kollam', 1, '2025-02-17 17:09:48', NULL),
(12, 'Kottayam', 1, '2025-02-25 04:20:12', NULL),
(15, 'Kanjirappally', 1, '2025-03-02 12:35:01', NULL),
(16, 'Pala', 1, '2025-03-02 19:08:06', NULL),
(17, 'Kozhikode', 1, '2025-03-02 19:12:57', NULL),
(18, 'Malappuram', 1, '2025-03-02 19:13:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `availability` enum('Available','Unavailable') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `user_id`, `availability`) VALUES
(4, 36, 'Available'),
(5, 38, 'Available'),
(6, 39, 'Available'),
(7, 41, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `employee_activity`
--

CREATE TABLE `employee_activity` (
  `activity_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(50) DEFAULT 'fa-info-circle',
  `activity_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_activity`
--

INSERT INTO `employee_activity` (`activity_id`, `employee_id`, `description`, `icon`, `activity_time`) VALUES
(1, 4, 'Logged into the system', 'fa-sign-in-alt', '2025-03-12 11:02:06'),
(2, 4, 'Updated availability status to Available', 'fa-user-clock', '2025-03-12 11:02:06'),
(3, 4, 'Completed pickup request #1234', 'fa-check-circle', '2025-03-12 11:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price_per_pc` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price_per_pc`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Refrigerator(single door)', 'Refrigerators are a type of hazardous e-waste due to the presence of toxic materials like refrigerants, mercury, lead, and cadmium.', 1050.00, 1, '2025-03-12 08:47:18', '2025-03-25 04:11:39'),
(2, 3, 'Computers', 'Computers both old and current version is acceptable.', 500.00, 1, '2025-03-14 08:10:29', '2025-03-27 06:52:13'),
(3, 1, 'washing machine', 'A washing machine as e-waste contains recyclable materials and requires proper disposal to avoid environmental harm.', 1500.00, 1, '2025-03-17 09:25:33', '2025-03-25 04:07:13'),
(4, 4, 'Mobile Phones(Smarthphones)', 'Discarded mobile phones become e-waste, contributing to environmental issues due to their toxic components like heavy metals and non-biodegradable materials.', 10.00, 1, '2025-03-25 05:44:57', '2025-03-25 05:44:57'),
(5, 1, 'Refrigerator(double door)', 'A double-door refrigerator ready for scrap is typically non-functional, heavily damaged, or beyond repair, making it unsuitable for reuse or resale.', 1450.00, 1, '2025-03-25 05:50:54', '2025-03-25 05:50:54'),
(6, 4, 'Oven', 'An oven becomes e-waste when discarded after being damaged, non-functional, or obsolete, and should be recycled responsibly to minimize environmental impact.', 350.00, 1, '2025-03-25 05:58:13', '2025-03-25 05:58:13'),
(7, 4, 'CRT TV', 'Proper recycling is crucial to prevent harmful substances like lead from impacting the environment.', 200.00, 1, '2025-03-27 06:36:18', '2025-03-27 06:36:18'),
(8, 3, 'CRT Monitor', 'Safe recycling is essential to manage the hazardous materials, like lead and phosphorus, it contains.', 150.00, 1, '2025-03-27 06:38:51', '2025-03-27 06:38:51'),
(9, 3, 'LCD Monitor', 'These monitors often contain components like mercury, which require careful recycling to prevent environmental harm.', 100.00, 1, '2025-03-27 06:40:54', '2025-03-27 06:40:54'),
(10, 3, 'Computer CPU', 'Proper recycling ensures that valuable materials like metals are recovered and harmful substances are safely managed.', 220.00, 1, '2025-03-27 06:43:07', '2025-03-27 06:43:07');

-- --------------------------------------------------------

--
-- Table structure for table `rejections`
--

CREATE TABLE `rejections` (
  `rejection_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rejections`
--

INSERT INTO `rejections` (`rejection_id`, `cart_id`, `reason`, `created_at`) VALUES
(2, 4, 'image is not provided', '2025-03-25 07:53:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Employee','End User') NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` enum('Yes','No') DEFAULT 'No',
  `email_verification_token` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `reset_otp` varchar(6) DEFAULT NULL,
  `reset_otp_expiry` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `city_id` int(11) DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `fullname`, `email`, `phone`, `password`, `role`, `status`, `address`, `created_at`, `reset_token`, `reset_token_expiry`, `zip_code`, `verification_token`, `is_verified`, `email_verification_token`, `email_verified`, `reset_otp`, `reset_otp_expiry`, `is_active`, `city_id`, `google_id`) VALUES
(3, 'Arya Dileep', 'ayoradileep@gmail.com', '8281812694', '$2y$10$Qu7.ULbp5wdpsV.7.9BRiuDQPeMiB2zFniQ1gJaq0HAhxM0WcmmL6', 'Admin', 'Active', '', '2025-02-17 14:30:01', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, NULL, NULL),
(4, 'Ameya S', 'aaryadilieep@gmail.com', '8877799009', '$2y$10$1CBXryrfV6s0gS9Hxn537.lekrrQo9DXkWHCUXCRk1gZEWnMNmNsO', 'End User', 'Active', 'Kottayam', '2025-02-18 15:21:04', 'bc11336a9d51250f49fcc0b77c5c46966d725610220270bd0db15f1a0b77c4ebb4ddba32209b30c454b8575017cd16de5836', '2025-02-23 07:10:58', NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 3, NULL),
(26, 'Keerthana Thomas', 'keerthanathomas097@gmail.com', '8075643884', '$2y$10$yKAl4.TAkQvhFGh7ueZKoedtDiRBCY7ZR0TWcS2Cxv1BKEvXUXyTm', 'End User', 'Active', NULL, '2025-02-25 06:46:48', NULL, NULL, NULL, NULL, 'No', 'fc54b1c85d476f44a7b8d117ac4e4e1088a24986aeec76826c26f8399135d17a6280487f8964f1ea4dfcbede3eb848d527bd', 0, NULL, NULL, 1, 12, NULL),
(27, 'Clara', 'clara@gmail.com', '8987654678', '$2y$10$F2FrTPTuFjUTc5fORhUg5uXLeEpufcX2dfZQU4qfOlRNrT/ee1Yaq', 'End User', 'Active', NULL, '2025-02-25 07:38:30', NULL, NULL, NULL, NULL, 'No', 'c70b353a0f5a9db6c20a6689d37cf5fbe577bab6d328156d711842575a57494cb3d73d525f553b5bfe5dd582c06ad189960e', 0, NULL, NULL, 1, 10, NULL),
(28, 'Johny', 'john@gmail.com', '7853567897', '$2y$10$OoNa25OJNVs2dIBlzv/mGeR/c04jCTUXMExFIZWFj2TrSP.vmUApa', 'End User', 'Active', NULL, '2025-02-25 07:42:43', NULL, NULL, NULL, NULL, 'No', 'a997e91462f19060277907aec91b85c0c06ffcd3069d18d03e0b76fdbf282813e520d7c6d9da34966afec0cd504ed148543b', 0, NULL, NULL, 1, 1, NULL),
(34, 'Jimin', 'bangtanstory07@gmail.com', '9646890534', '$2y$10$t60HnZuMpEIxSfjCjTnNC.I.zNXnNoVEEvO3q1cQRNfSzDFqPFx8O', 'End User', 'Active', NULL, '2025-03-02 11:34:31', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 15, NULL),
(36, 'Arjun', 'arjun@gmail.com', '6753686575', '$2y$10$PtTXxs5/8FqxOQph63MeDOK46g.r4qC5PDyhS8PIcQirOxGp6C6/K', 'Employee', 'Active', NULL, '2025-03-11 16:54:52', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 18, NULL),
(38, 'Annu Thomas', 'keerthanathomas@gmail.com', '8901245678', '$2y$10$7Y8jakgBPqbAnAhjcSpeDe0qNYk7fN8yM5udsetOWUf/xrZOFPRGS', 'Employee', 'Active', NULL, '2025-03-12 09:22:57', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 15, NULL),
(39, 'Vijay J', 'vj@gmail.com', '7895621589', '$2y$10$8KWJzd/Kgl6T9S80R6e4/ObKlv4xAWc75iwCUS6gy255ivZSnzwfW', 'Employee', 'Active', NULL, '2025-03-22 13:28:17', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 15, NULL),
(40, 'Adwaid', 'sreejadileep2020@gmail.com', '8563890324', '$2y$10$9PX7P6o0yrQsv53sCvi0O.W.HcxlcrBBN5syEvD5BA8cAxCIpSXPy', 'End User', 'Active', NULL, '2025-03-23 08:58:57', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 18, NULL),
(41, 'Milan', 'milan04@gmail.com', '8964576890', '$2y$10$4RZRm/d4.9A/DZljU72fX.L4ZmB6pnAmURLtGKXXy6maCQGRtVz7.', 'Employee', 'Active', NULL, '2025-03-25 07:00:27', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 10, NULL),
(42, 'Geetha Lakshmi', 'goldenrankedgirls@gmail.com', '7689065467', '$2y$10$cP1uPxNSYmm43OhNkdoU2uFUpE1eMcC3vLdoDIGUvKvgXK40QSOI.', 'End User', 'Active', NULL, '2025-04-08 21:29:26', NULL, NULL, NULL, NULL, 'No', NULL, 0, NULL, NULL, 1, 12, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `address_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address_type` enum('Home','Work','Other') NOT NULL DEFAULT 'Home',
  `city_id` int(11) NOT NULL,
  `address_line` text NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `pincode` varchar(6) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`address_id`, `user_id`, `address_type`, `city_id`, `address_line`, `landmark`, `pincode`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 34, 'Home', 15, 'rosevilla(H)', 'next to JK stores', '686506', 0, 1, '2025-03-19 09:05:53', '2025-03-19 09:06:20'),
(4, 40, 'Home', 18, 'Vettickal(H)\r\nKodur', 'Opposite to BCozy tea enterprises', '676506', 0, 1, '2025-03-23 09:01:57', '2025-03-23 09:01:57'),
(5, 27, 'Home', 10, 'Thakadiyel(H)\r\nKarbala', 'Behind Karbala Ground', '691001', 0, 1, '2025-03-25 06:59:16', '2025-03-25 06:59:16'),
(8, 4, 'Home', 3, 'Mulakkupaadam House,\r\nPvn 364 Building,\r\n Aiswarya Nagar,Kesavadasapuram', 'Aiswarya Home Vista', '691004', 0, 1, '2025-03-27 09:00:19', '2025-03-27 09:00:19'),
(9, 42, 'Home', 12, 'Prasanasadhanam House\r\nKottayam', 'yamuna lodge', '686001', 0, 1, '2025-04-08 22:14:26', '2025-04-08 22:14:26');

--
-- Triggers `user_addresses`
--
DELIMITER $$
CREATE TRIGGER `before_address_update` BEFORE UPDATE ON `user_addresses` FOR EACH ROW BEGIN
                    IF NEW.is_default = TRUE THEN
                        UPDATE user_addresses 
                        SET is_default = FALSE 
                        WHERE user_id = NEW.user_id 
                        AND address_id != NEW.address_id;
                    END IF;
                END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `address_id` (`address_id`),
  ADD KEY `assigned_employee_id` (`assigned_employee_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`city_id`),
  ADD UNIQUE KEY `city_name` (`city_name`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `employee_activity`
--
ALTER TABLE `employee_activity`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `rejections`
--
ALTER TABLE `rejections`
  ADD PRIMARY KEY (`rejection_id`),
  ADD KEY `cart_id` (`cart_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `fk_city` (`city_id`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `idx_user_addresses_user_id` (`user_id`),
  ADD KEY `idx_user_addresses_city_id` (`city_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `city_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employee_activity`
--
ALTER TABLE `employee_activity`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `rejections`
--
ALTER TABLE `rejections`
  MODIFY `rejection_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`address_id`) REFERENCES `user_addresses` (`address_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`assigned_employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_activity`
--
ALTER TABLE `employee_activity`
  ADD CONSTRAINT `employee_activity_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `rejections`
--
ALTER TABLE `rejections`
  ADD CONSTRAINT `rejections_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_city` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`);

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_addresses_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
