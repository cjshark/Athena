-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 12:57 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tutor_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `status` enum('pending','accepted','declined','completed','confirmed') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `proof_file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rating` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `student_id`, `tutor_id`, `session_date`, `session_time`, `status`, `payment_method`, `proof_file_path`, `created_at`, `notes`, `updated_at`, `rating`, `feedback`) VALUES
(34, 1, 2, '2025-05-10', '22:00:00', 'completed', 'Maya', 'uploads/proofs/booking_34_681f5354339e2.jpg', '2025-05-10 13:23:14', 'I want to be fluent in English', '2025-05-10 13:24:39', 4, 'Impressive, Thank you Ma\'am Lyca'),
(35, 1, 1, '2025-05-10', '22:00:00', 'completed', 'GCash', 'uploads/proofs/booking_35_681f53bfdd2c7.jpg', '2025-05-10 13:24:00', 'I want to be good at singing', '2025-05-10 13:25:48', 5, 'Thank Teacher!!'),
(36, 1, 1, '2025-05-10', '22:00:00', 'completed', 'GCash', 'uploads/proofs/booking_36_681f54b992d0d.jpg', '2025-05-10 13:28:58', 'hello', '2025-05-10 13:30:37', 4, 'Amazing!!!'),
(37, 1, 2, '2025-05-10', '23:00:00', 'confirmed', 'Maya', 'uploads/proofs/booking_37_681f615455a8c.jpg', '2025-05-10 14:21:43', 'hello', '2025-05-10 14:23:16', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rates`
--

CREATE TABLE `rates` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `student_id`, `subject`, `session_date`, `session_time`, `notes`, `status`, `created_at`) VALUES
(1, 3, 'Mathematics', '2025-06-12', '23:27:00', 'Hello i want to book', 'pending', '2025-05-04 03:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `email`, `phone`, `password`, `status`, `created_at`, `phone_number`, `profile_image`) VALUES
(1, 'Christian', '23@gmail.com', '9308120938', '$2y$10$W0xKRqF5vmXc3TJwUBvh3uRizVmdukWq3IeuaHDxT2/AoCXtvKII2', 'active', '2025-05-04 04:05:52', '09346712611', 'uploads/profile_images/student_1_681f3219caba2.jpeg'),
(2, 'Christian', 'chris@gmail.com', '09312312311', '$2y$10$Trs3Al6idzr5owD9fhUSpuT54yvhQoI86vwGAjrZjpPOEcwHrAmiO', 'active', '2025-05-10 11:17:43', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tutor`
--

CREATE TABLE `tutor` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tutor`
--

INSERT INTO `tutor` (`id`, `name`, `email`, `password`, `created_at`, `phone`, `profile_image`) VALUES
(1, 'Christian Jake Catipay', 'cjshark321@gmail.com', '$2y$10$aoY9OpwK1UgP1EyZhVQPxuNa4W8S6T/ECYE9Uijn1cmTkid2gIZ5.', '2025-05-03 12:35:55', NULL, NULL),
(2, 'ewqeqwe', 'cj@gmail.com', '$2y$10$CngP3qmUvV0RPZzMqeoQ.unyMMv8UblUvzR8e2gQt2zYnUHfume.2', '2025-05-03 12:37:14', NULL, NULL),
(3, 'Imelda J Catipay', 'cjshark3221@gmail.com', '$2y$10$O051C0TqTAUXDJnMH4rRxelFnecSIy03u04HqLfj9MOIb4hdMz7OO', '2025-05-03 12:44:06', '09778298234', NULL),
(4, 'Andrew Garfield', 'Andrew@gmail.com', '$2y$10$/a6gGm5GvvRzYNS2al8Uuupkh3fZ8jGD4U/s.WJpz9.46iBwpMbkG', '2025-05-04 03:49:04', '09879138921', NULL),
(5, 'Cjshark', 'q@gmail.com', '$2y$10$xdesEg75zvjzljdFIHGs2OUYn0a8.qdTHDF3WBMgygrWvIgVnRlCW', '2025-05-04 03:57:10', '12314252', NULL),
(6, 'cj', '1@gmail.com', '$2y$10$X4RCMqsQ9Z/CkWtc1DLVHe8YVkYXeM2ru51S0pV11SG7incX/A09u', '2025-05-04 04:01:50', '48453154', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tutors`
--

CREATE TABLE `tutors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `specialty` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `experience` int(11) DEFAULT 0 COMMENT 'Years of teaching experience',
  `bio` text DEFAULT NULL COMMENT 'Tutor biography/profile description',
  `phone_number` varchar(20) DEFAULT NULL COMMENT 'Tutors contact phone number'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tutors`
--

INSERT INTO `tutors` (`id`, `name`, `email`, `phone`, `password`, `status`, `profile_image`, `created_at`, `specialty`, `description`, `rating`, `hourly_rate`, `experience`, `bio`, `phone_number`) VALUES
(1, 'Teacher', 'teach@gmail.com', '1231434', '$2y$10$oHE0HbTSsIabghSe5gCHx.PyDrbGWZGD4tEcoKdz/01/DCJOiPo7q', 'active', 'uploads/profile_images/tutor_1_681f31fe3176e.jpg', '2025-05-04 04:06:28', 'Music', 'Hello! I’m Teacher, a dedicated music tutor and performing artist with over 6 years of experience inspiring students of all ages and skill levels to develop their musical abilities. I hold a degree in [Music Education / Performance / Music Theory, etc.] from [University Name] and have trained in [specific styles, instruments, or certifications if applicable].\r\n\r\nMy teaching covers a range of subjects including:\r\n\r\nInstrumental instruction (e.g., piano, guitar, violin, drums)\r\n\r\nVoice training and vocal technique\r\n\r\nMusic theory and ear training\r\n\r\nSongwriting and composition\r\n\r\nSight-reading and improvisation\r\n\r\nPerformance preparation and stage confidence\r\n\r\nI take a personalized, student-centered approach—whether you\'re learning for fun, preparing for exams (like ABRSM, Trinity, or school recitals), or working toward a professional music career. My lessons are structured yet flexible, blending technique with creativity, and always adapted to your individual learning style and goals.\r\n\r\nMusic is not just about playing notes—it’s about expression, discipline, and joy. My goal is to create a supportive and inspiring environment where students can explore their creativity, master their instrument, and develop a lifelong love for music.\r\n\r\nLet’s make music learning a rewarding and exciting journey!\r\n\r\n', '4.50', '25.40', 6, 'Hi! I’m Teacher, a passionate music tutor with 6 years of teaching experience and a deep love for helping students grow through music. I specialize in [instrument(s) – e.g., piano, guitar, voice], music theory, and performance coaching. Whether you\'re a beginner or looking to refine your skills, I offer fun, personalized lessons designed to nurture talent and build confidence.', '09778298234'),
(2, 'Lyca', 'and@gmail.com', '321432525', '$2y$10$PaoOB.k4O2cXJ3sIhNZd0OQblpX1YgyG1gIjoHsRP32Ck2UtqCx5G', 'active', 'uploads/profile_images/tutor_2_681ecb78091ba.jpg', '2025-05-04 04:37:50', 'English', 'Hello! I’m Lyca, a dedicated and enthusiastic English tutor with over 5 years of experience helping learners of all ages and backgrounds achieve their language goals. I hold a degree in [English / English Literature / Education / Linguistics] from [University Name], and I’ve spent my career specializing in personalized English instruction—ranging from foundational grammar and vocabulary to advanced academic writing, literary analysis, and conversational fluency.\r\n\r\nMy teaching philosophy centers on patience, adaptability, and encouragement. I believe that every student learns differently, so I design custom lesson plans that suit each learner’s pace, interests, and objectives. Whether you’re a student preparing for exams (like IELTS, TOEFL, SAT, or school assessments), a professional aiming to improve communication in the workplace, or an ESL learner building everyday fluency, I am here to guide you every step of the way.\r\n\r\nI incorporate modern teaching tools, interactive activities, and real-world content to make lessons engaging and relevant. More than just improving skills, my goal is to build confidence and foster a genuine appreciation for the English language. Over the years, I’ve helped students boost their grades, pass language tests, sharpen their writing, and express themselves more clearly and confidently in both academic and real-life situations.\r\n\r\nLet’s work together to make your English learning journey effective, enjoyable, and empowering!', '4.00', '20.00', 5, 'Hi! I’m Lyca, a passionate and experienced English tutor dedicated to helping students build strong language skills and confidence. With 5 years of teaching experience and a background in English literature and education, I specialize in grammar, writing, reading comprehension, and conversational fluency. Whether you\'re preparing for exams, improving academic performance, or learning English as a second language, I tailor my lessons to suit your goals and learning style. My approach is patient, engaging, and results-driven — let\'s make English enjoyable and empowering together!', '09321816951');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `rates`
--
ALTER TABLE `rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tutor`
--
ALTER TABLE `tutor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tutors`
--
ALTER TABLE `tutors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rates`
--
ALTER TABLE `rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tutor`
--
ALTER TABLE `tutor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tutors`
--
ALTER TABLE `tutors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tutor` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`);

--
-- Constraints for table `rates`
--
ALTER TABLE `rates`
  ADD CONSTRAINT `rates_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `tutor` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
