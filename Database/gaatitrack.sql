-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 15, 2023 at 05:36 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gaatitrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(30) NOT NULL,
  `branch_code` varchar(50) NOT NULL,
  `street` text NOT NULL,
  `city` text NOT NULL,
  `state` text NOT NULL,
  `zip_code` varchar(50) NOT NULL,
  `country` text NOT NULL,
  `contact` varchar(100) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_code`, `street`, `city`, `state`, `zip_code`, `country`, `contact`, `date_created`) VALUES
(4, 'dIbUK5mEh96f0Zc', '100 S Biscayne Blvd, Suite 2100', 'Miami', 'FL', '33131', 'United States', '+1 (305) 555-0142', '2023-11-27 13:31:49'),
(5, 'ugjU3qv4QOVd5J8', '350 5th Avenue, Suite 4800', 'New York', 'NY', '10118', 'United States', '+1 (212) 555-0190', '2023-07-31 07:30:11'),
(6, 'SouWaBUl38xMpIK', '233 S Wacker Dr, Loop Station', 'Chicago', 'IL', '60606', 'United States', '+1 (312) 555-0185', '2023-08-02 18:33:35'),
(9, 'PRmKESVF4tWCzl2', '600 Montgomery St, Financial Hub', 'San Francisco', 'CA', '94111', 'United States', '+1 (415) 555-0128', '2023-08-03 13:04:18'),
(10, 'FDB7N4z0CnQr2Ky', '1717 McKinney Ave, Downtown Hub', 'Dallas', 'TX', '75202', 'United States', '+1 (214) 555-0170', '2023-08-03 13:07:25'),
(11, 'Fe8czxW94PY2lwq', '1000 4th Ave, Central Station', 'Seattle', 'WA', '98104', 'United States', '+1 (206) 555-0160', '2023-08-03 13:08:24'),
(12, 'SVYGbLwdW7em3zh', '800 Wilshire Blvd, Metro Station', 'Los Angeles', 'CA', '90017', 'United States', '+1 (213) 555-0199', '2023-08-03 14:40:41');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

CREATE TABLE `parcels` (
  `id` int(30) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `sender_name` text NOT NULL,
  `sender_address` text NOT NULL,
  `sender_contact` text DEFAULT NULL,
  `sender_email` varchar(200) NOT NULL DEFAULT '',
  `recipient_name` text NOT NULL,
  `recipient_address` text NOT NULL,
  `recipient_contact` text DEFAULT NULL,
  `recipient_email` varchar(200) NOT NULL DEFAULT '',
  `type` int(1) NOT NULL COMMENT '1 = Deliver, 2=Pickup',
  `from_branch_id` varchar(30) NOT NULL,
  `to_branch_id` varchar(30) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `height` varchar(100) NOT NULL,
  `width` varchar(100) NOT NULL,
  `length` varchar(100) NOT NULL,
  `price` float NOT NULL,
  `status` int(2) NOT NULL DEFAULT 0,
  `parcel_image` varchar(255) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `reference_number`, `sender_name`, `sender_address`, `sender_contact`, `recipient_name`, `recipient_address`, `recipient_contact`, `type`, `from_branch_id`, `to_branch_id`, `weight`, `height`, `width`, `length`, `price`, `status`, `date_created`) VALUES
(1, '201406231415', 'John Smith', '350 5th Avenue, Suite 1200, New York, NY 10118', '+1 (212) 555-0143', 'Claire Blake', '233 S Wacker Dr, Floor 44, Chicago, IL 60606', '+1 (312) 555-0182', 1, '1', '0', '30kg', '12in', '12in', '15in', 45, 7, '2023-11-26 16:15:46'),
(3, '983186540795', 'Michael Anderson', '1000 4th Ave, Suite 300, Seattle, WA 98104', '+1 (206) 555-0165', 'Emily Davis', '600 Montgomery St, Floor 18, San Francisco, CA 94111', '+1 (415) 555-0129', 2, '1', '3', '20Kg', '10in', '10in', '10in', 35, 2, '2023-11-26 16:46:03'),
(10, '798438738398', 'Sarah Jenkins', '120 Wall Street, 22nd Floor, New York, NY 10005', '+1 (212) 555-0177', 'Kevin Brooks', '233 S Wacker Dr, Loop Station, Chicago, IL 60606', '+1 (312) 555-0185', 2, '5', '9', '45', '120', '677', '670', 65, 0, '2023-08-03 13:14:13'),
(11, '701589720901', 'David Wilson', '555 California St, Suite 3100, San Francisco, CA 94104', '+1 (415) 555-0188', 'Amanda Clark', '100 Pine St, Suite 1250, San Francisco, CA 94111', '+1 (415) 555-0166', 2, '5', '11', '55', '160', '455', '555', 55, 0, '2023-08-03 13:18:45'),
(12, '618331985221', 'Robert Miller', '120 Wall Street, 22nd Floor, New York, NY 10005', '+1 (212) 555-0177', 'Jessica Taylor', '401 N Michigan Ave, Suite 1500, Chicago, IL 60611', '+1 (312) 555-0133', 2, '5', '', '45', '160', '455', '670', 65, 0, '2023-08-03 13:20:55'),
(13, '275728717086', 'David Wilson', '555 California St, Suite 3100, San Francisco, CA 94104', '+1 (415) 555-0188', 'Amanda Clark', '100 Pine St, Suite 1250, San Francisco, CA 94111', '+1 (415) 555-0166', 2, '9', '9', '45', '120', '677', '67', 55, 0, '2023-08-03 13:22:09'),
(14, '753697280349', 'William Brown', '200 E Randolph St, Suite 5100, Chicago, IL 60601', '+1 (312) 555-0199', 'Olivia Martinez', '2100 Ross Ave, Suite 2800, Dallas, TX 75201', '+1 (214) 555-0144', 2, '6', '10', '45', '160', '677', '670', 75, 0, '2023-08-03 13:27:19'),
(15, '667392903463', 'James Thomas', '111 W Washington St, Chicago, IL 60602', '+1 (312) 555-0112', 'Sophia Hernandez', '200 S Biscayne Blvd, Suite 4000, Miami, FL 33131', '+1 (305) 555-0198', 2, '6', '4', '45', '160', '455', '555', 70, 0, '2023-08-03 13:33:33'),
(16, '429522865395', 'Daniel White', '300 N LaSalle St, Chicago, IL 60654', '+1 (312) 555-0155', 'Emma Harris', '500 W Madison St, Suite 2000, Chicago, IL 60661', '+1 (312) 555-0174', 2, '6', '6', '45', '120', '677', '67', 40, 0, '2023-08-03 13:34:34'),
(17, '726627862410', 'Christopher Martin', '150 N Riverside Plaza, Chicago, IL 60606', '+1 (312) 555-0136', 'Ava Thompson', '767 5th Ave, New York, NY 10153', '+1 (212) 555-0161', 2, '6', '5', '45', '120', '677', '67', 60, 0, '2023-08-03 14:27:17'),
(18, '026242450983', 'Matthew Jackson', '110 N Wacker Dr, Suite 3500, Chicago, IL 60606', '+1 (312) 555-0148', 'Isabella Garcia', '101 California St, Suite 2500, San Francisco, CA 94111', '+1 (415) 555-0152', 2, '6', '9', '45', '120', '677', '670', 50, 5, '2023-08-03 14:29:54'),
(19, '923246250964', 'Anthony Lewis', '181 W Madison St, Chicago, IL 60602', '+1 (312) 555-0189', 'Mia Robinson', '345 California St, San Francisco, CA 94104', '+1 (415) 555-0173', 2, '6', '9', '45', '160', '455', '670', 65, 0, '2023-08-03 14:33:19'),
(20, '199897593227', 'Joshua Walker', '30 Rockefeller Plaza, New York, NY 10112', '+1 (212) 555-0125', 'Charlotte Hall', '50 Fremont St, San Francisco, CA 94105', '+1 (415) 555-0149', 2, '5', '9', '45', '160', '455', '555', 80, 4, '2023-08-03 14:45:56');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_tracks`
--

CREATE TABLE `parcel_tracks` (
  `id` int(30) NOT NULL,
  `parcel_id` int(30) NOT NULL,
  `status` int(2) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parcel_tracks`
--

INSERT INTO `parcel_tracks` (`id`, `parcel_id`, `status`, `date_created`) VALUES
(1, 2, 1, '2023-11-27 09:53:27'),
(2, 3, 1, '2023-11-27 09:55:17'),
(3, 1, 1, '2023-11-27 10:28:01'),
(4, 1, 2, '2023-11-27 10:28:10'),
(5, 1, 3, '2023-11-27 10:28:16'),
(6, 1, 4, '2023-11-27 11:05:03'),
(7, 1, 5, '2023-11-27 11:05:17'),
(8, 1, 7, '2023-11-27 11:05:26'),
(9, 3, 2, '2023-11-27 11:05:41'),
(10, 6, 1, '2023-11-27 14:06:57'),
(11, 20, 4, '2023-08-11 21:36:28'),
(12, 18, 5, '2023-08-11 21:36:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `cover_img` text NOT NULL,
  `api_key` varchar(512) NOT NULL DEFAULT '',
  `smtp_host` varchar(255) NOT NULL DEFAULT 'sandbox.smtp.mailtrap.io',
  `smtp_port` varchar(10) NOT NULL DEFAULT '2525',
  `smtp_user` varchar(255) NOT NULL DEFAULT '',
  `smtp_pass` varchar(255) NOT NULL DEFAULT '',
  `smtp_security` varchar(10) NOT NULL DEFAULT 'tls',
  `mail_from_address` varchar(255) NOT NULL DEFAULT 'no-reply@gaatitrack.com',
  `mail_from_name` varchar(255) NOT NULL DEFAULT 'GaaTiTrack Logistics',
  `mail_driver` varchar(20) NOT NULL DEFAULT 'smtp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `name`, `email`, `contact`, `address`, `cover_img`, `api_key`) VALUES
(1, 'GaaTiTrack Logistics USA', 'support@gaatitrack.com', '+1 (800) 555-0199', '1250 Broadway, Suite 3200, New York, NY 10001, United States', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(30) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1 = admin, 2 = staff',
  `branch_id` int(30) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `type`, `branch_id`, `date_created`) VALUES
(1, 'Mayuri K', 'K', 'mayuri.infospace@gmail.com', '21232f297a57a5a743894a0e4a801fc3', 1, 0, '2023-11-26 10:57:04'),
(4, 'Suhit', 'Chavan', 'suhit@gmail.com', 'e35b98bdf7f7b2ec50eaab211dd950de', 2, 5, '2023-07-31 07:30:37'),
(5, 'mayuri', 'Kale', 'mayuri@admin.com', '21232f297a57a5a743894a0e4a801fc3', 2, 5, '2023-08-02 18:34:44'),
(6, 'Nilesh', 'Chaure', 'n@admin.com', '0192023a7bbd73250516f069df18b500', 2, 6, '2023-08-02 19:47:31'),
(7, 'Mayuri', 'K', 'mayuri1@gmail.com', 'cd92a26534dba48cd785cdcc0b3e6bd1', 2, 9, '2023-08-03 13:09:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parcels`
--
ALTER TABLE `parcels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parcel_tracks`
--
ALTER TABLE `parcel_tracks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `parcels`
--
ALTER TABLE `parcels`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `parcel_tracks`
--
ALTER TABLE `parcel_tracks`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
