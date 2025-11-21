-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 21, 2025 at 04:58 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u967494580_pbl`
--

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL,
  `points_threshold` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `description`, `icon_url`, `points_threshold`) VALUES
(1, 'Mentor Eligible', 'Met all requirements to become a mentor', '🎓', NULL),
(2, 'Mentor', 'Promoted to mentor status', '👨‍🏫', NULL),
(3, 'Helping Hand', 'Reviewed 10 peer submissions', '🤝', NULL),
(4, 'Community Leader', 'Active mentor for 30 days', '⭐', NULL),
(5, 'Top Mentor', 'Helped 50+ students successfully', '🏆', NULL),
(6, 'First Steps', 'Enrolled in your first course', '🎓', NULL),
(7, 'Code Warrior', 'Submitted your first code', '💻', NULL),
(8, 'Problem Solver', 'Passed your first quest', '✅', NULL),
(9, 'Course Champion', 'Completed your first course', '🏆', NULL),
(10, 'Tower Defender', 'Played the tower defense game', '🎮', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `description`, `created_by`, `created_at`) VALUES
(1, 'Intro to Coding (ICT)', 'Foundations of programming for SHS ICT strand', 1, '2025-05-21 08:38:33'),
(2, 'Intro to Coding (ICT)', 'Foundations of programming for SHS ICT strand', 1, '2025-10-27 12:53:21'),
(4, 'CSS Flexbox & Grid Mastery', 'Master modern CSS layout techniques! Learn how to create responsive, flexible layouts using CSS Flexbox and Grid. This course is specifically designed for ICT students and directly supports the concepts in our Tower Defense game. Perfect for understanding how to position elements on a webpage.', 20, '2025-10-28 20:53:14');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`user_id`, `course_id`, `enrolled_at`) VALUES
(7, 4, '2025-10-28 23:02:02'),
(11, 1, '2025-10-28 03:27:25'),
(11, 2, '2025-10-28 03:27:14'),
(11, 4, '2025-10-28 20:53:21'),
(12, 2, '2025-10-28 03:24:14'),
(20, 4, '2025-10-28 20:53:14'),
(22, 4, '2025-10-29 00:34:49'),
(23, 1, '2025-11-03 09:45:33'),
(23, 4, '2025-11-03 09:45:42');

-- --------------------------------------------------------

--
-- Table structure for table `game_scores`
--

CREATE TABLE `game_scores` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `score` int(11) NOT NULL,
  `wave_reached` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_scores`
--

INSERT INTO `game_scores` (`id`, `user_id`, `score`, `wave_reached`, `created_at`) VALUES
(1, 11, 70, 2, '2025-10-28 17:57:13'),
(2, 22, 250, 3, '2025-10-29 00:36:45'),
(3, 23, 460, 4, '2025-11-03 09:47:57'),
(4, 12, 1100, 5, '2025-11-21 03:49:13');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_criteria`
--

CREATE TABLE `mentor_criteria` (
  `id` int(10) UNSIGNED NOT NULL,
  `criteria_key` varchar(50) NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `threshold_value` int(10) UNSIGNED NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentor_criteria`
--

INSERT INTO `mentor_criteria` (`id`, `criteria_key`, `name`, `description`, `threshold_value`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'min_completed_quests', 'Minimum Completed Quests', 'Number of quests student must complete', 15, 1, '2025-10-27 12:53:32', '2025-10-27 12:53:32'),
(2, 'min_game_score', 'Minimum Game Score', 'Minimum score in tower defense game', 15000, 1, '2025-10-27 12:53:32', '2025-10-27 12:53:32'),
(3, 'min_courses_completed', 'Minimum Courses Completed', 'Number of courses student must complete', 2, 1, '2025-10-27 12:53:32', '2025-10-27 12:53:32'),
(4, 'min_badges_earned', 'Minimum Badges Earned', 'Number of badges student must earn', 5, 1, '2025-10-27 12:53:32', '2025-10-27 12:53:32'),
(5, 'min_perfect_submissions', 'Minimum Perfect Submissions', 'Number of perfect score submissions', 3, 1, '2025-10-27 12:53:32', '2025-10-27 12:53:32');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_promotions`
--

CREATE TABLE `mentor_promotions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `promoted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `promoted_by` int(10) UNSIGNED DEFAULT NULL,
  `promotion_type` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` enum('info','success','warning','error','welcome') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `is_read`, `created_at`) VALUES
(2, 11, 'Welcome to Aprender! Start your learning journey by playing the tower defense game.', 'welcome', 1, '2025-10-27 13:49:36'),
(3, 12, 'Welcome to Aprender! Start your learning journey by playing the tower defense game.', 'welcome', 0, '2025-10-28 03:23:25'),
(4, 12, 'You have successfully enrolled in a new course!', 'success', 0, '2025-10-28 03:24:14'),
(5, 11, 'You have successfully enrolled in a new course!', 'success', 1, '2025-10-28 03:27:14'),
(6, 11, 'You have successfully enrolled in a new course!', 'success', 1, '2025-10-28 03:27:25'),
(7, 11, 'Your code submission for \"fjsadjfsdaj\" is under review.', 'info', 1, '2025-10-28 03:27:32'),
(8, 11, 'Your submission for \'fjsadjfsdaj\' needs revision. Check the feedback.', 'warning', 1, '2025-10-28 03:29:01'),
(9, 11, 'Your submission for \'fjsadjfsdaj\' was approved! You earned 0 points.', 'success', 1, '2025-10-28 03:29:21'),
(11, 11, 'You have successfully enrolled in a new course!', 'success', 0, '2025-10-28 20:11:58'),
(16, 20, '🎉 Welcome, Cheska! Your mentor account has been created successfully!', 'success', 1, '2025-10-28 20:39:43'),
(17, 21, '🎉 Welcome, Kyle! Your mentor account has been created successfully!', 'success', 0, '2025-10-28 20:39:43'),
(18, 20, '🎉 Your CSS Flexbox & Grid Mastery course has been restored successfully!', 'success', 1, '2025-10-28 20:53:14'),
(19, 11, 'You have successfully enrolled in a new course!', 'success', 0, '2025-10-28 20:53:21'),
(20, 7, 'You have successfully enrolled in a new course!', 'success', 0, '2025-10-28 23:02:02'),
(21, 7, 'Your code submission for \"Flexbox Direction & Wrapping\" is under review.', 'info', 0, '2025-10-28 23:02:15'),
(22, 22, 'Welcome to Aprender! Start your learning journey by playing the tower defense game.', 'welcome', 0, '2025-10-29 00:34:15'),
(23, 22, 'You have successfully enrolled in a new course!', 'success', 0, '2025-10-29 00:34:49'),
(24, 23, 'Welcome to Aprnder! Start your learning journey by playing the tower defense game.', 'welcome', 0, '2025-11-03 09:44:49'),
(25, 23, 'You have successfully enrolled in a new course!', 'success', 0, '2025-11-03 09:45:33'),
(26, 23, 'You have successfully enrolled in a new course!', 'success', 0, '2025-11-03 09:45:42'),
(27, 23, 'Your code submission for \"Flexbox Direction & Wrapping\" is under review.', 'info', 0, '2025-11-03 09:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `quests`
--

CREATE TABLE `quests` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL DEFAULT 'easy',
  `max_points` int(10) UNSIGNED NOT NULL DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quests`
--

INSERT INTO `quests` (`id`, `course_id`, `title`, `description`, `difficulty`, `max_points`, `created_at`) VALUES
(9, 4, 'Introduction to Flexbox Basics', '# Flexbox Fundamentals\r\n\r\nLearn the basics of CSS Flexbox! Your task is to create a simple horizontal navigation menu using flexbox.\r\n\r\n## Requirements:\r\n1. Use `display: flex;` on the container\r\n2. Use `justify-content: space-around;` to distribute items\r\n3. Center items vertically with `align-items: center;`\r\n\r\n## Code Template:\r\n```css\r\n.nav-container {\r\n  display: flex;\r\n  /* Add your properties here */\r\n}\r\n```\r\n\r\n## Expected Result:\r\nA horizontally distributed navigation menu with evenly spaced items.', 'easy', 50, '2025-10-28 20:53:14'),
(10, 4, 'Flexbox Direction & Wrapping', '# Flexbox Direction & Wrapping\r\n\r\nMaster flex-direction and flex-wrap properties to control layout flow!\r\n\r\n## Requirements:\r\n1. Create a card grid that wraps to multiple lines\r\n2. Use `flex-direction: row;`\r\n3. Use `flex-wrap: wrap;` to allow wrapping\r\n4. Add spacing between items using `gap: 20px;`\r\n\r\n## Real-World Application:\r\nThis is exactly how the turrets are positioned in our Tower Defense game!\r\n\r\n## Code Template:\r\n```css\r\n.card-container {\r\n  display: flex;\r\n  /* Add your direction and wrapping properties */\r\n}\r\n```', 'easy', 75, '2025-10-28 20:53:14'),
(11, 4, 'Advanced Flexbox Alignment', '# Master Flexbox Alignment\r\n\r\nLearn advanced alignment techniques with justify-content and align-items!\r\n\r\n## Requirements:\r\n1. Create a centered card layout\r\n2. Use `justify-content: center;` for horizontal centering\r\n3. Use `align-items: center;` for vertical centering\r\n4. Experiment with different values: flex-start, flex-end, space-between, space-around\r\n\r\n## Challenge:\r\nPosition 6 turrets in a 2x3 grid formation, just like in Wave 1 of the game!\r\n\r\n```css\r\n.turret-container {\r\n  display: flex;\r\n  /* Apply optimal positioning */\r\n}\r\n```', 'medium', 100, '2025-10-28 20:53:14'),
(12, 4, 'Introduction to CSS Grid', '# CSS Grid Fundamentals\r\n\r\nLearn how to create powerful 2D layouts with CSS Grid!\r\n\r\n## Requirements:\r\n1. Use `display: grid;`\r\n2. Define columns with `grid-template-columns: repeat(3, 1fr);`\r\n3. Define rows with `grid-template-rows: repeat(2, 200px);`\r\n4. Add spacing with `gap: 15px;`\r\n\r\n## Grid Basics:\r\n- **Columns:** Vertical divisions\r\n- **Rows:** Horizontal divisions\r\n- **fr unit:** Flexible fraction of available space\r\n\r\n```css\r\n.grid-container {\r\n  display: grid;\r\n  /* Add your grid properties */\r\n}\r\n```', 'medium', 100, '2025-10-28 20:53:14'),
(13, 4, 'Grid Areas & Placement', '# Advanced Grid Placement\r\n\r\nMaster grid-template-areas for creating complex layouts!\r\n\r\n## Requirements:\r\n1. Create a webpage layout with header, sidebar, main content, and footer\r\n2. Use `grid-template-areas` to define named regions\r\n3. Place items using `grid-area` property\r\n\r\n## Template:\r\n```css\r\n.page-layout {\r\n  display: grid;\r\n  grid-template-areas:\r\n    \"header header header\"\r\n    \"sidebar main main\"\r\n    \"footer footer footer\";\r\n  grid-template-columns: 200px 1fr 1fr;\r\n  grid-template-rows: auto 1fr auto;\r\n  gap: 10px;\r\n}\r\n\r\n.header { grid-area: header; }\r\n.sidebar { grid-area: sidebar; }\r\n.main { grid-area: main; }\r\n.footer { grid-area: footer; }\r\n```', 'medium', 125, '2025-10-28 20:53:14'),
(14, 4, 'Responsive Layouts with Flexbox & Grid', '# Create Responsive Layouts\r\n\r\nCombine Flexbox and Grid to build fully responsive web layouts!\r\n\r\n## Requirements:\r\n1. Create a responsive gallery that uses Grid on desktop\r\n2. Switches to Flexbox column on mobile\r\n3. Uses media queries: `@media (max-width: 768px)`\r\n4. Implements `auto-fit` and `minmax()` for automatic responsiveness\r\n\r\n## Advanced Technique:\r\n```css\r\n.responsive-grid {\r\n  display: grid;\r\n  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));\r\n  gap: 20px;\r\n}\r\n```\r\n\r\nThis automatically adjusts columns based on available space!', 'hard', 150, '2025-10-28 20:53:14'),
(15, 4, 'Tower Defense Positioning Challenge', '# Final Challenge: Game Layout Optimization\r\n\r\nApply everything you\'ve learned to optimize turret positioning in our Tower Defense game!\r\n\r\n## Objective:\r\nPosition 6 turrets to defend against enemies using ONLY CSS Flexbox properties.\r\n\r\n## Wave Requirements:\r\nEach wave has different enemy paths. Position turrets to maximize coverage!\r\n\r\n### Wave 1 - Straight Path:\r\n```css\r\ndisplay: flex;\r\njustify-content: space-around;\r\nalign-items: center;\r\ngap: 80px;\r\n```\r\n\r\n### Wave 2 - L-Shaped Path:\r\n```css\r\ndisplay: flex;\r\njustify-content: space-between;\r\nalign-items: flex-start;\r\ngap: 60px;\r\nflex-wrap: wrap;\r\n```\r\n\r\n### Wave 3 - Zigzag Path:\r\n```css\r\ndisplay: flex;\r\njustify-content: center;\r\nalign-items: space-evenly;\r\ngap: 100px;\r\n```\r\n\r\n## Success Criteria:\r\n- All turrets positioned away from enemy path\r\n- Maximum coverage area\r\n- No turret overlap\r\n- Strategic spacing using gap property\r\n\r\n**This quest directly applies to the actual game - practice here, win there!**', 'hard', 200, '2025-10-28 20:53:14');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `quest_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `code` text NOT NULL,
  `status` enum('pending','passed','failed','resubmitted') NOT NULL DEFAULT 'pending',
  `points_awarded` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `quest_id`, `user_id`, `code`, `status`, `points_awarded`, `feedback`, `submitted_at`) VALUES
(2, 10, 7, 'hwhwkwwkwkwksjn', 'pending', NULL, NULL, '2025-10-28 23:02:15'),
(3, 10, 23, 'fjfknc', 'pending', NULL, NULL, '2025-11-03 09:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','mentor','admin','user') NOT NULL DEFAULT 'student',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `role`, `active`, `email_verified`, `verification_token`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin@example.com', '$2y$10$Vi2SSNe1XHnMSOfDWkW.2.2AIxlDFLH/11p7iD2ODoWQ0kZWlsRzG', 'admin', 1, 0, NULL, '2025-10-28 19:02:05', '2025-05-21 08:38:33', '2025-10-28 19:02:05'),
(6, 'aquino2@gmail.com', '$2y$10$hjZAGnSA53aw1PFqIrn5tO11pkXFGR7OTx1LBG3OgOcIoRZ.geVHy', 'student', 1, 0, '580c22e9d4df3ee46061ad8cc6663a220586b6d23e61f17420496a1b452b3e4f', '2025-05-21 10:19:32', '2025-05-21 10:18:49', '2025-10-27 12:53:42'),
(7, 'chesskaeunice@gmail.com', '$2y$10$tsj49kkPXe9HNj8v9ergAO9tGjzzMhF0gL1rHFrA0Omn4EjmwwUsa', 'student', 1, 0, '8c4b47088ec7b8882a2656996943892d3c619db88f653a7a1825e7921f7fc0fc', '2025-10-29 00:51:07', '2025-10-01 01:57:38', '2025-10-29 00:51:07'),
(8, 'makcataga@gmail.com', '$2y$10$ZcILm8TsfE8LRxwVkoQYleR/gYRp.wvuv2EdcuOTK/DDoaOHuzsGC', 'student', 1, 0, '0199f71f32b0ee326454623221b612f60dcc9d3f93c4bf10510d6e51ea2c5850', '2025-10-01 02:11:03', '2025-10-01 02:10:35', '2025-10-27 12:53:42'),
(11, 'beefyspud@gmail.com', '$2y$10$Zy2sziZEjvo4dP82ybQ69.1n6tJ3uqAK3ZuFm1UWHP.1GJeJSdR4.', 'student', 1, 0, '42c4279878aed5fcb6dc6ee98b359f7154ab618cef3cc265d0f7c7156bc0c116', '2025-11-03 09:11:07', '2025-10-27 13:49:36', '2025-11-03 09:11:07'),
(12, 'yugotrenth@gmail.com', '$2y$10$odC/xQfqLdsyBwLk7Vy7euHd2WHrxBjQN1u5XQsy.MI5pJm.MGbC2', 'student', 1, 0, '8a993eb702180a28a4085dde9679241552494a6d391824584aa8cacb9e4d68a8', '2025-11-21 03:46:25', '2025-10-28 03:23:25', '2025-11-21 03:46:25'),
(20, 'cheska.diaz@aprnder.com', '$2y$10$P2jYC6bhWvi.IU1OYskGtuoLqn.fOC/Ebnulm4vnJvD1O53ahLo5G', 'mentor', 1, 1, NULL, '2025-10-29 00:31:07', '2025-10-28 20:39:43', '2025-10-29 00:31:07'),
(21, 'kyle.cataga@aprnder.com', '$2y$10$P2jYC6bhWvi.IU1OYskGtuoLqn.fOC/Ebnulm4vnJvD1O53ahLo5G', 'mentor', 1, 1, NULL, NULL, '2025-10-28 20:39:43', '2025-10-28 20:39:43'),
(22, 'annepatricearbolente@gmail.com', '$2y$10$L2kj3Lu3mJ7cm0sne435qOSck29mcqtit/kj/hOWzHDk2n1ZMOqja', 'student', 1, 0, '4aec7ca99552eb562f6bc1b41cf0d72f2cb9463fce1d2aa174c80457578c0622', '2025-10-29 00:34:35', '2025-10-29 00:34:15', '2025-10-29 00:34:35'),
(23, 'luisayvonnebagtas@gmail.com', '$2y$10$WTuJsnXX1D.Rzssv5utiqe5vACNN3fXubt.qdkrQRtfGum.I5bmOa', 'student', 1, 0, '9977fd77e1fd3e44188c21b867d506169023f3d9c5461cf1f32d33a7a6b68f13', '2025-11-03 09:45:11', '2025-11-03 09:44:49', '2025-11-03 09:45:11');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `badge_id` int(10) UNSIGNED NOT NULL,
  `awarded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`user_id`, `full_name`, `avatar_url`, `bio`, `preferences`) VALUES
(1, 'Administrator', NULL, NULL, NULL),
(11, 'James Romel Aquino', NULL, NULL, NULL),
(12, 'loysa', NULL, NULL, NULL),
(20, 'Cheska Diaz', NULL, 'Data Protection Officer and experienced web development mentor.', NULL),
(21, 'Kyle Cataga', NULL, 'Experienced CSS developer and educator.', NULL),
(22, 'Anne Nyenye', NULL, NULL, NULL),
(23, 'Luisa Yvonne Bagtas', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_tokens`
--

CREATE TABLE `user_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `type` enum('remember','reset','verify') NOT NULL DEFAULT 'remember',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_courses_created_by` (`created_by`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`user_id`,`course_id`),
  ADD KEY `fk_enrollments_course` (`course_id`);

--
-- Indexes for table `game_scores`
--
ALTER TABLE `game_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_score` (`user_id`,`score`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `mentor_criteria`
--
ALTER TABLE `mentor_criteria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `criteria_key` (`criteria_key`),
  ADD KEY `idx_criteria_active` (`is_active`);

--
-- Indexes for table `mentor_promotions`
--
ALTER TABLE `mentor_promotions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mentor_promotions_admin` (`promoted_by`),
  ADD KEY `idx_mentor_promotions_user` (`user_id`),
  ADD KEY `idx_mentor_promotions_date` (`promoted_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Indexes for table `quests`
--
ALTER TABLE `quests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quests_course` (`course_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submissions_user` (`user_id`),
  ADD KEY `idx_submissions_quest` (`quest_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_active` (`active`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`user_id`,`badge_id`),
  ADD KEY `fk_user_badges_badge` (`badge_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_tokens_token` (`token`),
  ADD KEY `idx_user_tokens_user` (`user_id`),
  ADD KEY `idx_user_tokens_expires` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `game_scores`
--
ALTER TABLE `game_scores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mentor_criteria`
--
ALTER TABLE `mentor_criteria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mentor_promotions`
--
ALTER TABLE `mentor_promotions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `quests`
--
ALTER TABLE `quests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_tokens`
--
ALTER TABLE `user_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `fk_enrollments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `game_scores`
--
ALTER TABLE `game_scores`
  ADD CONSTRAINT `fk_game_scores_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mentor_promotions`
--
ALTER TABLE `mentor_promotions`
  ADD CONSTRAINT `fk_mentor_promotions_admin` FOREIGN KEY (`promoted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mentor_promotions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quests`
--
ALTER TABLE `quests`
  ADD CONSTRAINT `fk_quests_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_submissions_quest` FOREIGN KEY (`quest_id`) REFERENCES `quests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_submissions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `fk_user_badges_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_tokens`
--
ALTER TABLE `user_tokens`
  ADD CONSTRAINT `fk_user_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
