-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2026 at 09:57 AM
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
-- Database: `pms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `patient_id`, `appointment_date`, `appointment_time`, `reason`, `status`, `created_at`, `admin_note`) VALUES
(1, 5, '2026-04-25', '11:16:00', 'may sakit', 'approved', '2026-04-23 14:16:08', NULL),
(3, 5, '2026-04-30', '12:22:00', 'walang', 'approved', '2026-04-23 15:25:54', NULL),
(4, 5, '2026-04-30', '12:22:00', 'walang', 'rejected', '2026-04-23 15:26:17', 'wala na pong vacant'),
(5, 5, '2026-04-30', '12:21:00', 'fvbhsvfhsvhfabshfhbfhsvhfsvfsvfs', 'rejected', '2026-04-23 16:01:19', 'nag uli na si doc'),
(6, 5, '2026-04-25', '10:00:00', 'pa check up man doc', 'rejected', '2026-04-23 16:11:18', ''),
(7, 5, '2026-04-25', '10:00:00', 'pa check up man doc', 'pending', '2026-04-23 16:31:11', NULL),
(8, 5, '2026-04-25', '12:00:00', 'may sakit', 'approved', '2026-04-24 03:54:27', 'sige'),
(10, 12, '2026-04-30', '21:21:00', 'tcccgc', 'pending', '2026-04-28 13:21:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` int(11) NOT NULL,
  `medicine_name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `medicine_name`) VALUES
(1, 'Amoxicillin'),
(4, 'Antibiotic'),
(5, 'Antihistamine'),
(6, 'Atorvastatin'),
(3, 'Losartan'),
(2, 'Mefenamic'),
(7, 'Oxymetazoline');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_details`
--

CREATE TABLE `medicine_details` (
  `id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `packing` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `medicine_details`
--

INSERT INTO `medicine_details` (`id`, `medicine_id`, `packing`) VALUES
(1, 5, '50'),
(4, 6, '25'),
(5, 3, '80'),
(6, 2, '100'),
(7, 7, '25'),
(8, 1, '50');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `patient_name` varchar(60) NOT NULL,
  `address` varchar(100) NOT NULL,
  `cnic` varchar(17) NOT NULL,
  `date_of_birth` date NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `gender` varchar(6) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `age` int(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `patient_name`, `address`, `cnic`, `date_of_birth`, `phone_number`, `gender`, `password`, `age`) VALUES
(2, 'Johnsil', 'Poblacion', 'okay', '2000-02-02', '0909090909', 'Male', '4bdc705346b9627e5891ebaf58417da5', 26),
(3, 'Patient', 'Sample Address 101 - Updated', '123', '2026-04-03', '091235649879', 'Male', 'e50082b397eae6781d6809ee6841efcc', NULL),
(4, 'Mark Cooper', 'Sample Address 101 - Updated', '0009', '2005-12-21', '0009', 'Male', '29549a71a57f587d88209b9c1f1b7999', 20),
(5, 'Name', 'Sample Address 101 - Updated', '1232', '2026-04-22', '09123', 'Male', '5f4dcc3b5aa765d61d8327deb882cf99', 0),
(6, 'Mark Cooper', 'Sample Address 101 - Updated', '1211', '2021-03-04', '1234', 'Male', '81dc9bdb52d04dc20036dbd8313ed055', NULL),
(7, 'Hakw', 'Sample Address 101 - Updated', '1212', '2014-04-24', '1267', 'Male', 'e53a0a2978c28872a4505bdb51db06dc', 12),
(8, 'Ikw Lang', 'Sample Address 101 - Updated', '1111', '2014-04-24', '2222', 'Female', '934b535800b1cba8f96a5d72f72f1611', NULL),
(9, 'Sige', 'Sample Address 101 - Updated', '1555', '2004-12-17', '1555', 'Male', 'b2dd140336c9df867c087a29b2e66034', NULL),
(10, 'Juan Dela Cruz', 'Sample Address 101 - Updated', '12-12-12', '2000-12-17', '09123456', 'Male', 'eade867f85c5921700e20e028ee1539c', NULL),
(11, 'Juan Cruz', 'Sample Address 101 - Updated', '12-12', '2003-02-11', '091234', 'Male', '32250170a0dca92d53ec9624f336ca24', NULL),
(12, 'Juan', 'Sample Address 101 - Updated', '12-12', '2000-12-17', '0912345', 'Male', '0d1abff041b893bec699a2d9990381c5', 25);

-- --------------------------------------------------------

--
-- Table structure for table `patient_medication_history`
--

CREATE TABLE `patient_medication_history` (
  `id` int(11) NOT NULL,
  `patient_visit_id` int(11) NOT NULL,
  `medicine_details_id` int(11) NOT NULL,
  `quantity` tinyint(4) NOT NULL,
  `dosage` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `patient_medication_history`
--

INSERT INTO `patient_medication_history` (`id`, `patient_visit_id`, `medicine_details_id`, `quantity`, `dosage`) VALUES
(1, 1, 1, 5, '250'),
(2, 1, 6, 2, '500'),
(3, 2, 2, 2, '250'),
(4, 2, 7, 2, '250'),
(5, 3, 2, 123, '122'),
(6, 10, 4, 3, '3'),
(7, 11, 6, 7, '1'),
(8, 12, 5, 2, '1'),
(9, 14, 8, 10, '1'),
(10, 15, 8, 2, '1x per day'),
(11, 16, 8, 2, '1x a day'),
(12, 17, 8, 5, '3x per day');

-- --------------------------------------------------------

--
-- Table structure for table `patient_visits`
--

CREATE TABLE `patient_visits` (
  `id` int(11) NOT NULL,
  `visit_date` date NOT NULL,
  `next_visit_date` date DEFAULT NULL,
  `bp` varchar(23) NOT NULL,
  `weight` varchar(12) NOT NULL,
  `disease` varchar(30) NOT NULL,
  `patient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `patient_visits`
--

INSERT INTO `patient_visits` (`id`, `visit_date`, `next_visit_date`, `bp`, `weight`, `disease`, `patient_id`) VALUES
(3, '2026-03-31', '2026-03-31', '1213', '1221', 'cancer', 2),
(6, '2026-03-31', '2026-03-31', '120', '53', 'itchi', 2),
(7, '2026-03-31', '2026-03-31', '120', '53', 'itchi', 2),
(8, '2026-03-31', '2026-03-31', '120', '53', 'itchi', 2),
(9, '2026-03-31', '2026-03-31', '120', '53', 'itchi', 2),
(10, '2026-04-03', '2026-04-03', '120', '175', 'okay', 2),
(11, '2026-04-03', '2026-04-03', '90/120', '53', 'wala', 2),
(12, '2026-04-03', '2026-04-03', '1213', '175', 'itchi', 2),
(14, '2026-04-03', '2026-04-03', '90/120', '53', 'limot', 3),
(15, '2026-04-07', '2026-04-07', '90/120', '53', 'limot', 2),
(16, '2026-04-24', '2026-04-24', '90/120', '53', 'kupal', 7),
(17, '2026-04-28', '2026-04-30', '90/120', '65', 'limot', 12);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `display_name` varchar(30) NOT NULL,
  `user_name` varchar(30) NOT NULL,
  `password` varchar(100) NOT NULL,
  `profile_picture` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `display_name`, `user_name`, `password`, `profile_picture`) VALUES
(1, 'Administrator', 'admin', '0192023a7bbd73250516f069df18b500', '1656551981avatar.png '),
(2, 'admin2.0', 'AdminYarn', '5f4dcc3b5aa765d61d8327deb882cf99', '1774959312hanapinMO.jpeg '),
(3, 'user', 'user', 'c7365852071f25d05683cb192ddce212', '1775228138hanapinMO.jpeg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointments_patient_id` (`patient_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_name` (`medicine_name`);

--
-- Indexes for table `medicine_details`
--
ALTER TABLE `medicine_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_medicine_details_medicine_id` (`medicine_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patient_medication_history`
--
ALTER TABLE `patient_medication_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_patient_medication_history_patients_visits_id` (`patient_visit_id`),
  ADD KEY `fk_patient_medication_history_medicine_details_id` (`medicine_details_id`);

--
-- Indexes for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_patients_visit_patient_id` (`patient_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medicine_details`
--
ALTER TABLE `medicine_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `patient_medication_history`
--
ALTER TABLE `patient_medication_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `patient_visits`
--
ALTER TABLE `patient_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_details`
--
ALTER TABLE `medicine_details`
  ADD CONSTRAINT `fk_medicine_details_medicine_id` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`);

--
-- Constraints for table `patient_medication_history`
--
ALTER TABLE `patient_medication_history`
  ADD CONSTRAINT `fk_patient_medication_history_medicine_details_id` FOREIGN KEY (`medicine_details_id`) REFERENCES `medicine_details` (`id`),
  ADD CONSTRAINT `fk_patient_medication_history_patients_visits_id` FOREIGN KEY (`patient_visit_id`) REFERENCES `patient_visits` (`id`);

--
-- Constraints for table `patient_visits`
--
ALTER TABLE `patient_visits`
  ADD CONSTRAINT `fk_patients_visit_patient_id` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
