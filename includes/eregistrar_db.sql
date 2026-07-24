-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 24, 2026 at 05:09 AM
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
-- Database: `eregistrar`
--

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `document_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `processing_days` int(11) DEFAULT 0,
  `requirements` text DEFAULT NULL,
  `status` enum('Available','Unavailable') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`document_id`, `document_name`, `description`, `fee`, `processing_days`, `requirements`, `status`, `created_at`) VALUES
(1, 'Form 137', 'Student Permanent Record ', 200.00, 5, 'School ID,\r\nName of the school where the Form 137 will be requested\r\n\r\n', 'Available', '2026-07-19 07:19:05'),
(2, 'Transcript of Records', 'Official record of student grades and academic history.', 100.00, 3, 'Valid School ID, Request Form', 'Available', '2026-07-19 07:21:14'),
(3, 'Certificate of Enrollment', 'Certification that the student is currently enrolled.', 50.00, 1, 'Valid School ID', 'Available', '2026-07-19 07:21:14'),
(4, 'Certificate of Grades', 'Official certification of student grades.', 50.00, 2, 'Valid School ID, Request Form', 'Available', '2026-07-19 07:21:14'),
(5, 'Good Moral Certificate', 'Certificate of good moral character.', 50.00, 2, 'Valid School ID', 'Available', '2026-07-19 07:21:14'),
(6, 'Diploma', 'Official diploma document.', 200.00, 5, 'Clearance, Valid School ID', 'Available', '2026-07-19 07:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `document_requirements`
--

CREATE TABLE `document_requirements` (
  `requirement_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `requirement_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_requirements`
--

INSERT INTO `document_requirements` (`requirement_id`, `document_id`, `requirement_name`) VALUES
(1, 1, 'Valid School ID'),
(2, 1, 'Payment Receipt'),
(3, 1, 'Authorization Letter'),
(4, 5, 'Valid School ID'),
(5, 5, 'Clearance'),
(6, 1, 'Valid School ID'),
(7, 1, 'Official Receipt / Payment Receipt'),
(8, 1, 'Clearance from School'),
(9, 1, 'Authorization Letter (if claiming through representative)'),
(10, 2, 'Valid School ID'),
(11, 2, 'Official Receipt / Payment Receipt'),
(12, 2, 'School Clearance'),
(13, 2, 'Authorization Letter (if representative)'),
(14, 3, 'Valid School ID'),
(15, 3, 'Official Receipt / Payment Receipt'),
(16, 4, 'Valid School ID'),
(17, 4, 'Official Receipt / Payment Receipt'),
(18, 5, 'Valid School ID'),
(19, 5, 'Official Receipt / Payment Receipt'),
(20, 5, 'Department Clearance'),
(21, 6, 'Valid School ID'),
(22, 6, 'Official Receipt / Payment Receipt'),
(23, 6, 'School Clearance'),
(24, 6, 'Authorization Letter (if representative)');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`message_id`, `sender_id`, `receiver_id`, `message`, `status`, `created_at`) VALUES
(1, 4, 1, 'Hello, how much is TOR?\r\n', 'Unread', '2026-07-19 16:14:13'),
(13, 6, 1, 'Are you available on weekends?', 'Unread', '2026-07-20 16:25:32'),
(14, 1, 3, 'yes po?', 'Unread', '2026-07-21 00:17:23'),
(15, 3, 1, 'What time the office close?', 'Unread', '2026-07-21 00:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `message`, `status`, `created_at`) VALUES
(2, 1, 'Your document request has been submitted successfully.', 'Unread', '2026-07-19 15:44:57'),
(22, 4, 'Your request REQ20260722142326 has been approved.', 'Unread', '2026-07-22 12:34:42'),
(23, 4, 'Your request REQ20260722142326 is now being processed.', 'Unread', '2026-07-24 02:42:06'),
(24, 4, 'Your request REQ20260722142326 is ready for claim.', 'Unread', '2026-07-24 02:42:10'),
(25, 4, 'Your request REQ20260722142326 is ready for claim.', 'Unread', '2026-07-24 02:42:13'),
(26, 4, 'Your request REQ20260722142326 has been successfully claimed.', 'Unread', '2026-07-24 02:42:15');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_file` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `request_id`, `payment_method`, `reference_number`, `proof_file`, `status`, `created_at`) VALUES
(1, 1, 1, 'GCash', '123456789', 'receipt.png', 'Pending', '2026-07-19 15:55:05'),
(2, 4, 0, 'GCash', '46468413535', '1784476699_undraw_certificate_cqps.png', 'Pending', '2026-07-19 15:58:19'),
(3, 4, 0, 'GCash', '542512154541', '1784476715_undraw_certificate_cqps.png', 'Pending', '2026-07-19 15:58:35'),
(4, 4, 0, 'GCash', '16516454513', '1784539669_recaptcha.png', 'Pending', '2026-07-20 09:27:49'),
(5, 4, 0, 'GCash', '16516454513', '1784549111_undraw_nice-to-meet-you_sqin.png', 'Pending', '2026-07-20 12:05:12'),
(6, 4, 0, 'GCash', '35454584512', '1784550076_undraw_writing-online_x665.png', 'Pending', '2026-07-20 12:21:16'),
(7, 4, 0, 'GCash', '35454584512', '1784555798_cat.jpg', 'Pending', '2026-07-20 13:56:38');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `tracking_no` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `remarks` text DEFAULT NULL,
  `uploaded_file` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `claim_date` date DEFAULT NULL,
  `payment_method` enum('E-Payment','On the Counter') NOT NULL DEFAULT 'On the Counter',
  `payment_status` enum('Pending','Paid','Rejected') NOT NULL DEFAULT 'Pending',
  `fullname` varchar(100) DEFAULT NULL,
  `student_no` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`request_id`, `tracking_no`, `user_id`, `document_id`, `purpose`, `quantity`, `remarks`, `uploaded_file`, `status`, `request_date`, `claim_date`, `payment_method`, `payment_status`, `fullname`, `student_no`, `course`, `year_level`, `email`) VALUES
(20, 'REQ20260722142326', 4, 4, 'For Scholarship', 1, '', NULL, 'Claimed', '2026-07-22 12:23:26', '2026-07-24', 'E-Payment', 'Pending', 'Juliefel Malusay', '20232159', 'Bachelor of Science in Criminology Education', '4th Year', 'juliefelmalusay884@gmail.com'),
(21, 'REQ20260724044746', 4, 1, 'For Enrollment', 1, '', NULL, 'Pending', '2026-07-24 02:47:46', NULL, 'On the Counter', 'Pending', 'Juliefel Malusay', '20232159', 'Bachelor of Science in Criminology Education', '4th Year', 'juliefelmalusay884@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `student_id`, `request_id`, `document_id`, `file_name`, `file_path`, `status`, `remarks`, `created_at`) VALUES
(1, 4, 0, 3, '1784524260_4_recaptcha.png', 'uploads/documents/1784524260_4_recaptcha.png', 'Pending', 'Waiting for admin approval', '2026-07-20 05:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `student_no` varchar(50) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'Student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `student_no`, `fullname`, `course`, `year_level`, `contact_no`, `email`, `password`, `role`, `created_at`, `profile_image`) VALUES
(1, 'ADMIN001', 'Registrar Administrator', 'BSIT', '4th Year', '09123456789', 'admin@consolatrix.edu', '$2y$10$I4rEQ.W4fu4op7e7clmKW.Y4e7Skd5EXzLFiS2yZzspYLN9WSfUGa', 'Admin', '2026-07-19 06:54:43', NULL),
(3, '20258971', 'Kim Cyril Gargar', 'Bachelor of Science in Criminology Education', '4th Year', '09755062837', 'cyrilgargar123@gmail.com', '$2y$10$7XtIMLDF34SbFgP2oaSE2eEhOkXPYVhOC/ptyo2G5bfFScuufYNpG', 'Student', '2026-07-19 07:31:39', 'profile_3_1784564435.jpg'),
(4, '20232159', 'Juliefel Malusay', 'Bachelor of Science in Criminology Education', '4th Year', '09755066673', 'juliefelmalusay884@gmail.com', '$2y$10$65yH/6XMzr2b1mvJDsxCEuvwHxn17iKjOnycXmHcPSFwIbJNbLW6S', 'Student', '2026-07-19 09:44:28', 'profile_4_1784646329.jpg'),
(6, '202354689', 'Anjenneth Alcontin', 'Bachelor of Science in Information Technology', '4th Year', '09755045684', 'anjennethalcontin13@gmail.com', '$2y$10$sqZLkuxWRx4OQaOW10S64uGVeTD9FbPQwiykmBIP1EHq2VcdBYbPq', 'Student', '2026-07-20 16:22:30', 'profile_6_1784564633.png'),
(7, '20232063', 'Ibon Pono', 'Bachelor of Science in Information Technology', '4th Year', '09266578020', 'ibonpono123@gmail.com', '$2y$10$dL2oLPz4L1EEtQYU6nNUzeG4xbS5g2qJp8D/HS18WSac08l2a9GX.', 'Student', '2026-07-21 10:28:10', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`);

--
-- Indexes for table `document_requirements`
--
ALTER TABLE `document_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `document_requirements`
--
ALTER TABLE `document_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `document_requirements`
--
ALTER TABLE `document_requirements`
  ADD CONSTRAINT `document_requirements_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
