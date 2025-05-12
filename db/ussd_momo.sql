-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 02:30 PM
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
-- Database: `ussd_momo`
--

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `agent_code` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `agent_code`, `full_name`, `phone_number`, `pin`, `status`, `created_at`) VALUES
(3, '2004', 'aimable', '+250735073602', '$2y$10$HauJmAWxSHs87QW/IIhtOOCSsPUeC6k7fA/PiQeTYqLQRkFoe7gj6', 'ACTIVE', '2025-05-12 11:46:34'),
(4, '10109', 'shyaka', '0785073602', '$2y$10$NvV0ZqsmoAn/k0QQ59BITeknjgwDlTHWIinprj6gWN0p6Rnq0FDsu', 'ACTIVE', '2025-05-12 12:20:09');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `sender_phone` varchar(15) DEFAULT NULL,
  `recipient_phone` varchar(15) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('SEND','WITHDRAW','DEPOSIT') NOT NULL,
  `agent_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `sender_phone`, `recipient_phone`, `amount`, `transaction_type`, `agent_code`, `created_at`) VALUES
(1, NULL, '0728087192', 9000.00, 'DEPOSIT', '2004', '2025-05-12 11:50:42'),
(2, NULL, '+250728087192', 3000.00, 'DEPOSIT', '2004', '2025-05-12 11:57:58'),
(3, NULL, '+250789446505', 8000.00, 'DEPOSIT', '2004', '2025-05-12 11:59:43'),
(4, NULL, '+250789446505', 900.00, 'DEPOSIT', '2004', '2025-05-12 12:00:56'),
(5, '+250789446505', '+250728087192', 200.00, 'SEND', NULL, '2025-05-12 12:21:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `phone_number`, `full_name`, `pin`, `balance`, `created_at`) VALUES
(1, '0728087192', 'Emmy', '$2y$10$qBttxbgM3T/aNKpRGnbUS.6jJKsJyF5TugqJdp4SjcTP/uorX7aT6', 9000.00, '2025-05-12 10:44:06'),
(2, '+250728087192', 'emmy samvura', '$2y$10$p2Gvarns4uofydfy8aX9ZO.oWTKgjXgoiO/X70NYxDHU0X9oatFL.', 3200.00, '2025-05-12 11:14:28'),
(3, '+250789446505', 'niyonkuru', '$2y$10$aApUuKwwx61NRU3BBtq8Zeri2E8L/bimJdvUp.wYZbjuiSQdBIsEG', 8700.00, '2025-05-12 11:42:53'),
(4, '+250798596695', 'sabato', '$2y$10$oALPNfUJQ.mS/o17CieYaOfKiUI5QlvdpxI4cYiZp4tAvwsVa4i9m', 0.00, '2025-05-12 12:03:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agent_code` (`agent_code`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_phone` (`sender_phone`),
  ADD KEY `recipient_phone` (`recipient_phone`),
  ADD KEY `agent_code` (`agent_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`sender_phone`) REFERENCES `users` (`phone_number`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`recipient_phone`) REFERENCES `users` (`phone_number`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`agent_code`) REFERENCES `agents` (`agent_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
