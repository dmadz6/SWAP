-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2025 at 07:49 AM
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
-- Database: `xyz polytechnic`
--

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_code` varchar(4) NOT NULL,
  `course_code` varchar(3) NOT NULL,
  `class_type` enum('Semester','Term') NOT NULL,
  `faculty_identification_code` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`class_code`, `course_code`, `class_type`, `faculty_identification_code`) VALUES
('BU01', 'B01', 'Semester', 'F102'),
('BU02', 'B02', 'Semester', 'F102'),
('BU03', 'F21', 'Semester', 'F102'),
('BU04', 'F22', 'Semester', 'F102'),
('BU05', 'B01', 'Semester', 'F105'),
('DS01', 'D01', 'Semester', 'F104'),
('DS02', 'D02', 'Semester', 'F104'),
('DS03', 'M21', 'Semester', 'F104'),
('DS04', 'M22', 'Semester', 'F104'),
('EG01', 'M01', 'Semester', 'F103'),
('EG02', 'M02', 'Semester', 'F103'),
('EG03', 'E21', 'Semester', 'F103'),
('EG04', 'E22', 'Semester', 'F103'),
('IT01', 'I01', 'Semester', 'F101'),
('IT02', 'I02', 'Semester', 'F101'),
('IT03', 'I21', 'Semester', 'F101'),
('IT04', 'I22', 'Semester', 'F101');

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_code` varchar(3) NOT NULL,
  `course_name` varchar(50) NOT NULL,
  `diploma_code` varchar(5) NOT NULL,
  `course_start_date` date DEFAULT NULL,
  `course_end_date` date DEFAULT NULL,
  `status` enum('To start','No Status','In-progress','Ended') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_code`, `course_name`, `diploma_code`, `course_start_date`, `course_end_date`, `status`) VALUES
('B01', 'Business Administration', 'DPBM', '2222-05-06', '4444-04-04', 'In-progress'),
('B02', 'Marketing Principles', 'DPBM', '2025-01-15', '2025-05-15', 'In-progress'),
('B03', 'test', 'DPBM', '0000-00-00', '0000-00-00', 'No Status'),
('B04', 'test2', 'DPBM', NULL, NULL, 'No Status'),
('B05', 'test3', 'DPBM', NULL, NULL, 'No Status'),
('D01', 'Industrial Design Basics', 'DPID', '2025-01-15', '2025-05-15', 'In-progress'),
('D02', 'Product Design', 'DPID', '2025-01-15', '2025-05-15', 'In-progress'),
('E21', 'Circuit Theory', 'DPEE', '2025-01-15', '2025-05-15', 'In-progress'),
('E22', 'Power Systems', 'DPEE', '2025-01-15', '2025-05-15', 'In-progress'),
('F21', 'Financial Accounting', 'DPFN', '2025-01-15', '2025-05-15', 'In-progress'),
('F22', 'Investment Banking', 'DPFN', '2025-01-15', '2025-05-15', 'In-progress'),
('I01', 'Programming Fundamentals', 'DPIT', '2025-01-15', '2025-05-15', 'In-progress'),
('I02', 'Database Systems', 'DPIT', '2025-01-15', '2025-05-15', 'In-progress'),
('I21', 'Network Security', 'DPIS', '2025-01-15', '2025-05-15', 'In-progress'),
('I22', 'Cybersecurity Basics', 'DPIS', '2025-01-15', '2025-05-15', 'In-progress'),
('M01', 'Mechanics', 'DPME', '2025-01-15', '2025-05-15', 'In-progress'),
('M02', 'Thermodynamics', 'DPME', '2025-01-15', '2025-05-15', 'In-progress'),
('M21', 'Digital Media', 'DPMD', '2025-01-15', '2025-05-15', 'In-progress'),
('M22', 'Visual Communication', 'DPMD', '2025-01-15', '2025-05-15', 'In-progress');

-- --------------------------------------------------------

--
-- Table structure for table `diploma`
--

CREATE TABLE `diploma` (
  `diploma_code` varchar(5) NOT NULL,
  `diploma_name` varchar(50) NOT NULL,
  `school_code` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diploma`
--

INSERT INTO `diploma` (`diploma_code`, `diploma_name`, `school_code`) VALUES
('DPBM', 'Diploma in Business Management', 'BUS'),
('DPEE', 'Diploma in Electrical Engineering', 'ENG'),
('DPFN', 'Diploma in Finance', 'BUS'),
('DPID', 'Diploma in Industrial Design', 'DES'),
('DPIS', 'Diploma in Information Security', 'IIT'),
('DPIT', 'Diploma in Information Technology', 'IIT'),
('DPMD', 'Diploma in Media Design', 'DES'),
('DPME', 'Diploma in Mechanical Engineering', 'ENG');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_identification_code` varchar(4) NOT NULL,
  `school_code` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_identification_code`, `school_code`) VALUES
('F101', 'IIT'),
('F102', 'BUS'),
('F103', 'ENG'),
('F104', 'DES'),
('F105', 'IIT'),
('F106', 'BUS'),
('F107', 'ENG'),
('F108', 'DES');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset`
--

INSERT INTO `password_reset` (`id`, `email`, `token`, `created_at`) VALUES
(1, 'hamizvn@gmail.com', 'a62cec0382b401e3c9cdc16a0f7a7860f01f5f84286a69f55f9f4ec547bc0829e6fd3feac246e05bbdc3bcc7862879c54d9f', '2025-01-30 14:38:48'),
(2, 'hamizvn@gmail.com', 'e60f769744a4ceddaa960b40cd5f9446cafcbd014a1ae04c8eb64c5bdc33b0eec67cc8e8293aab310829683d5c97c35838e7', '2025-01-30 14:38:51'),
(3, 'hamizvn@gmail.com', '45734aeabadf21d2ede9e9d5427fc37bbd5143a16f637af6a60e541b05297f7eb9dea5d8d8ba1a1b07ff0dbb80d130edae0c', '2025-01-30 14:39:54'),
(4, 'hamizvn@gmail.com', '31bd37094063ff678541273b5eb283a11430fc888f867e49d6d5e753ab77c722c55a559a93a9b6a335befe279271439e9221', '2025-01-30 14:40:58'),
(5, 'hamizvn@gmail.com', '8351dae793959fd8ee1b5a697bce1a40b9b9aa6c723faa5feb564afcd65803edcfb51c517344b185b1c01706d51616d68680', '2025-01-30 14:42:23'),
(6, 'hamizvn@gmail.com', '50a2e36b8a698286bbd726eb5ae9fb086e6d63039cd96cb95372df53760cd43b8bbae64779f6a0ef454394b6269e0571f0f3', '2025-01-30 15:03:37'),
(7, 's102@gmail.com', '7663e2327903bb9ae3a26d26fb854f31e3b760554589f8ecd06b6855abb82d8744200e7c37a97ad4c4473dd105ec1d694f9c', '2025-01-30 15:28:15'),
(8, 'hamizvn@gmail.com', 'c84ecdb3a063db7c46dc631b15b88038ee7ddbb592b26a46c9b61b4aca52b51ef7527a44be4d61089bcb121c2e7c1996dc2d', '2025-01-31 13:24:58');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` enum('1','2','3') NOT NULL,
  `role_name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`) VALUES
('1', 'admin'),
('2', 'faculty'),
('3', 'student');

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `school_code` varchar(3) NOT NULL,
  `school_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school`
--

INSERT INTO `school` (`school_code`, `school_name`) VALUES
('BUS', 'School of Business'),
('DES', 'School of Design'),
('ENG', 'School of Engineering'),
('IIT', 'School of Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `semester_gpa_to_course_code`
--

CREATE TABLE `semester_gpa_to_course_code` (
  `grade_id` int(4) NOT NULL,
  `identification_code` varchar(4) NOT NULL,
  `course_code` varchar(3) NOT NULL,
  `course_score` decimal(2,1) NOT NULL,
  `grade` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semester_gpa_to_course_code`
--

INSERT INTO `semester_gpa_to_course_code` (`grade_id`, `identification_code`, `course_code`, `course_score`, `grade`) VALUES
(2, 'S101', 'B01', 3.8, 'B+'),
(3, 'S101', 'B02', 3.7, 'B+');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `identification_code` varchar(4) NOT NULL,
  `diploma_code` varchar(5) NOT NULL,
  `class_code` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`identification_code`, `diploma_code`, `class_code`) VALUES
('S202', 'DPBM', 'BU02'),
('S202', 'DPBM', NULL),
('S202', 'DPBM', NULL),
('S304', 'DPEE', 'EG04'),
('S304', 'DPEE', NULL),
('S304', 'DPEE', NULL),
('S101', 'DPIT', 'IT01'),
('S101', 'DPIT', NULL),
('S101', 'DPIT', NULL),
('S102', 'DPIT', 'IT02'),
('S102', 'DPIT', NULL),
('S102', 'DPIT', NULL),
('S103', 'DPIS', 'IT03'),
('S103', 'DPIS', NULL),
('S103', 'DPIS', NULL),
('S104', 'DPIS', 'IT04'),
('S104', 'DPIS', NULL),
('S104', 'DPIS', NULL),
('S203', 'DPFN', 'BU03'),
('S203', 'DPFN', NULL),
('S203', 'DPFN', NULL),
('S204', 'DPFN', 'BU04'),
('S204', 'DPFN', NULL),
('S204', 'DPFN', NULL),
('S302', 'DPME', 'EG02'),
('S302', 'DPME', NULL),
('S302', 'DPME', NULL),
('S404', 'DPMD', 'DS04'),
('S404', 'DPMD', NULL),
('S404', 'DPMD', NULL),
('S403', 'DPMD', 'DS03'),
('S403', 'DPMD', NULL),
('S403', 'DPMD', NULL),
('S401', 'DPID', 'DS01'),
('S401', 'DPID', NULL),
('S401', 'DPID', NULL),
('S402', 'DPID', 'DS02'),
('S402', 'DPID', NULL),
('S402', 'DPID', NULL),
('S201', 'DPBM', 'BU01'),
('S201', 'DPBM', 'BU02'),
('S201', 'DPBM', 'BU03'),
('S301', 'DPME', 'EG01'),
('S301', 'DPME', NULL),
('S301', 'DPME', NULL),
('S303', 'DPEE', 'EG03'),
('S303', 'DPEE', NULL),
('S303', 'DPEE', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_score`
--

CREATE TABLE `student_score` (
  `identification_code` varchar(4) NOT NULL,
  `semester_gpa` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_score`
--

INSERT INTO `student_score` (`identification_code`, `semester_gpa`) VALUES
('S101', 3.75);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `identification_code` varchar(4) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(3) NOT NULL,
  `phone_number` int(8) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `login_tracker` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`identification_code`, `email`, `password`, `role_id`, `phone_number`, `full_name`, `login_tracker`) VALUES
('A001', 'a001@gmail.com', '$2y$10$IUohb6hCzOa18DuRUOJaL.y8TOb/hqn2RvYZzlgh2G9U7ouYSexi6', 1, 82424436, 'ADMIN1', 1),
('A002', 'a002@gmail.com', '$2y$10$m1XiNf.Saa7tMOMqcHYQo.cNiHI1AGZPA1yDYq9lEq7xM/AGqdtoK', 1, 82424437, 'ADMIN2', 1),
('A003', 'a003@gmail.com', '$2y$10$zW0F0xxOoW2yM8EvNX94ROT75rE7kaGgFnAwMTlx4MJ03dHB9d5Hy', 1, 82424438, 'ADMIN3', 1),
('F101', 'jiun110506@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654321, 'LIN ZHAO', 1),
('F102', 'f102@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654322, 'AHMAD RAZAK', 1),
('F103', 'f103@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654323, 'KIM MING', 1),
('F104', 'f104@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654324, 'SITI AMINAH', 1),
('F105', 'f105@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654324, 'SITI DELISHA', 1),
('F106', 'f106@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654325, 'SITI ALYA', 1),
('F107', 'f107@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654326, 'NUR AMANDA', 1),
('F108', 'f108@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654327, 'ILHAN JIUN', 1),
('F109', 'f109@gmail.com', '$2y$10$twztr.y8nqA4yoE28ZmA8umvMKZXk/8GYCZBMw3HadTyBv6qRT.Oe', 2, 93893583, 'TEST FACULTY', 1),
('S101', 'S101@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234561, 'LEE WEI', 1),
('S102', 'S102@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234562, 'TAN MEI', 0),
('S103', 'S103@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234563, 'WONG HAO', 0),
('S104', 'S104@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234564, 'CHEN XIONG', 0),
('S201', 'S201@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234569, 'NURUL IMAN', 0),
('S202', 'S202@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234562, 'RAHMAN SHAH', 0),
('S203', 'S203@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234563, 'SITI ZUBAIDAH', 0),
('S204', 'S204@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234564, 'MUHAMAD RIZAL', 0),
('S301', 'S301@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234565, 'LIM HONG', 0),
('S302', 'S302@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234566, 'NG CHENG', 0),
('S303', 'S311@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234569, 'KOH YUAN', 0),
('S304', 'S304@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234568, 'ZHANG WEI', 0),
('S401', 'S401@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234565, 'DEWI PUTRI', 0),
('S402', 'S402@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234566, 'RAVI KUMAR', 0),
('S403', 'S403@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234567, 'MEI LING', 1),
('S404', 'S404@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234568, 'PARK MIN', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_code`),
  ADD KEY `course_code_idx` (`course_code`),
  ADD KEY `faculty_identification_code_idx` (`faculty_identification_code`) USING BTREE;

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_code`),
  ADD KEY `diploma_code_idx` (`diploma_code`);

--
-- Indexes for table `diploma`
--
ALTER TABLE `diploma`
  ADD PRIMARY KEY (`diploma_code`),
  ADD KEY `school_code_idx` (`school_code`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD KEY `identification_code_idx` (`faculty_identification_code`) USING BTREE,
  ADD KEY `faculty_school_code_idx` (`school_code`) USING BTREE;

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`school_code`);

--
-- Indexes for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `course_code_idx` (`course_code`),
  ADD KEY `identification_code_idx` (`identification_code`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD KEY `identification_code_idx` (`identification_code`),
  ADD KEY `diploma_code_idx` (`diploma_code`),
  ADD KEY `class_code_idx` (`class_code`) USING BTREE;

--
-- Indexes for table `student_score`
--
ALTER TABLE `student_score`
  ADD KEY `identification_code_idx` (`identification_code`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`identification_code`),
  ADD KEY `role_id_idx` (`role_id`),
  ADD KEY `email_idx` (`email`) USING BTREE,
  ADD KEY `full_name_idx` (`full_name`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  MODIFY `grade_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `course_code_fk` FOREIGN KEY (`course_code`) REFERENCES `course` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_faculty` FOREIGN KEY (`faculty_identification_code`) REFERENCES `faculty` (`faculty_identification_code`) ON UPDATE CASCADE;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `diploma_code_fk2` FOREIGN KEY (`diploma_code`) REFERENCES `diploma` (`diploma_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `diploma`
--
ALTER TABLE `diploma`
  ADD CONSTRAINT `diploma_to_school_fk` FOREIGN KEY (`school_code`) REFERENCES `school` (`school_code`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`faculty_identification_code`) REFERENCES `user` (`identification_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `faculty_school_code_fk` FOREIGN KEY (`school_code`) REFERENCES `school` (`school_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  ADD CONSTRAINT `semester_course_code_fk` FOREIGN KEY (`course_code`) REFERENCES `course` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `semester_gpa_identification_code_fk` FOREIGN KEY (`identification_code`) REFERENCES `user` (`identification_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_class_code_fk` FOREIGN KEY (`class_code`) REFERENCES `class` (`class_code`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `student_identification_code_fk` FOREIGN KEY (`identification_code`) REFERENCES `user` (`identification_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_to_diploma_code_fk` FOREIGN KEY (`diploma_code`) REFERENCES `diploma` (`diploma_code`) ON UPDATE CASCADE;

--
-- Constraints for table `student_score`
--
ALTER TABLE `student_score`
  ADD CONSTRAINT `student_score_identification_code_fk` FOREIGN KEY (`identification_code`) REFERENCES `user` (`identification_code`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
