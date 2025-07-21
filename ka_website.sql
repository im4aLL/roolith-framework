-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 08:05 AM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ka_website`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `body` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `body`, `created_at`, `updated_at`) VALUES
(1, 'Test', 'test', NULL, '2025-06-28 22:19:28', '2025-07-02 04:09:01'),
(2, 'Sample', 'sample', NULL, '2025-07-02 04:08:36', '2025-07-02 04:08:36'),
(3, 'News', 'news', NULL, '2025-07-02 04:08:49', '2025-07-02 04:08:49'),
(4, 'Service', 'service', NULL, '2025-07-06 23:00:29', '2025-07-06 23:00:29'),
(5, 'Movies', 'movies', '<p>body&nbsp;data&nbsp;</p>', '2025-07-20 06:12:33', '2025-07-20 06:27:32');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `module_setting_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `hook` varchar(255) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `module_setting_id`, `title`, `hook`, `group_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 'Module 1', 'mod-6878650db82b4', '', 'published', '2025-07-17 02:51:37', '2025-07-17 02:51:37'),
(3, 3, 'Module 2', 'mod-68786c26cde95', 'test-group', 'draft', '2025-07-17 03:21:19', '2025-07-20 01:36:12'),
(4, 1, 'mega', 'mod-687893ab7694b', '', 'published', '2025-07-17 06:10:58', '2025-07-17 06:10:58'),
(5, 1, 'mega 2', 'mod-687895e2d2626', '', 'draft', '2025-07-17 06:19:56', '2025-07-17 06:19:56'),
(6, 1, 'aaa', 'mod-6878979287eab', '', 'draft', '2025-07-17 06:27:46', '2025-07-17 06:27:46'),
(7, 4, 'Some text xyy', 'mod-687bd28f190a4', '', 'draft', '2025-07-19 17:15:14', '2025-07-19 17:45:58'),
(8, 3, 'video item one', 'mod-687c4925f3a08', '', 'published', '2025-07-20 01:41:52', '2025-07-20 01:41:52'),
(14, 3, 'something', 'mod-687c4eb8cd3aa', 'youtube', 'draft', '2025-07-20 02:04:53', '2025-07-20 02:04:53');

-- --------------------------------------------------------

--
-- Table structure for table `module_data`
--

CREATE TABLE `module_data` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `module_data`
--

INSERT INTO `module_data` (`id`, `module_id`, `field_name`, `field_data`) VALUES
(1, 1, 'label', 'haha'),
(2, 1, 'buttonText', 'submit'),
(3, 1, 'image', '1752720697_625643_plan.jpg'),
(5, 3, 'label', 'b'),
(6, 3, 'buttonText', 'aa'),
(7, 4, 'header', 'something'),
(8, 4, 'body', 'body text'),
(9, 4, 'another', 'another text'),
(10, 4, 'rich_text', 'rich&amp;nbsp;text&amp;nbsp;1'),
(11, 4, 'another_rich_text', 'may&amp;nbsp;be&amp;nbsp;another&amp;nbsp;rich&amp;nbsp;text&amp;nbsp;'),
(13, 4, 'image_multiple', '[\"1752947284_861288_Screenshot_1.jpg\",\"1752948284_616163_Screenshot_1.png\"]'),
(14, 4, 'attachment', '1752732658_940412_test.pdf'),
(15, 4, 'multiple_attachment', '[\"1752732658_957560_Resumedark.pdf\"]'),
(16, 5, 'header', 'wtf'),
(17, 5, 'body', 'ssdsd'),
(18, 5, 'another', 'fdfdf'),
(19, 5, 'rich_text', 'ewrqwerqeqweq'),
(20, 5, 'another_rich_text', 'ggjfgjgfhj'),
(21, 5, 'image', '1752733196_259636_IMG_3415_result.jpg'),
(22, 5, 'image_multiple', '[\"1752733196_293306_hadi_fairytale.jpg\",\"1752733196_342723_hadi-pp.jpg\"]'),
(23, 5, 'attachment', '1752733196_722713_Presidents_Honour_List_winter_2023.pdf'),
(24, 5, 'multiple_attachment', '[\"1752733196_841280_final-exam-answer-md-habibullah-al-hadi.pdf\"]'),
(25, 6, 'header', ''),
(26, 6, 'body', ''),
(27, 6, 'another', ''),
(28, 6, 'rich_text', 'asdasdsadadsdasdsdasdsadasdsada'),
(29, 6, 'another_rich_text', ''),
(30, 7, 'something_long', 'xii'),
(31, 7, 'another_long', 'yyu'),
(32, 4, 'image', '1752949155_780446_canada-post.jpg'),
(33, 6, 'image', '1752949406_390705_abworkout.jpg'),
(34, 6, 'image_multiple', '[\"1752949406_257310_Screenshot_3.png\",\"1752949406_377288_Screenshot_4.png\"]'),
(35, 6, 'attachment', '1752949424_958946_unofficial-transcript.pdf'),
(36, 6, 'multiple_attachment', '[\"1752949406_571987_Presidents_Honour_List_winter_2023.pdf\",\"1752949406_479553_Resumedark.pdf\"]'),
(37, 8, 'group', 'youtube'),
(38, 8, 'label', 'test'),
(39, 8, 'buttonText', 'Join Event'),
(40, 14, 'label', 'a'),
(41, 14, 'buttonText', 'b');

-- --------------------------------------------------------

--
-- Table structure for table `module_page`
--

CREATE TABLE `module_page` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `position` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `module_page`
--

INSERT INTO `module_page` (`id`, `page_id`, `module_id`, `position`, `created_at`, `updated_at`) VALUES
(7, 25, 14, 1, '2025-07-21 05:05:25', '2025-07-21 05:05:25'),
(8, 25, 1, 2, '2025-07-21 05:05:25', '2025-07-21 05:05:25'),
(9, 25, 3, 3, '2025-07-21 05:05:25', '2025-07-21 05:05:25');

-- --------------------------------------------------------

--
-- Table structure for table `module_settings`
--

CREATE TABLE `module_settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `module_settings`
--

INSERT INTO `module_settings` (`id`, `name`, `settings`, `created_at`, `updated_at`) VALUES
(1, 'Hero', '{\"name\":[\"header\",\"body\",\"image\",\"another\",\"rich_text\",\"image_multiple\",\"attachment\",\"multiple_attachment\",\"another_rich_text\"],\"type\":[\"text\",\"textarea\",\"image\",\"text\",\"rich-text\",\"image-multiple\",\"file\",\"file-multiple\",\"rich-text\"]}', '2025-07-09 06:34:13', '2025-07-17 06:41:12'),
(3, 'Test Module', '{\"name\":[\"label\",\"buttonText\",\"image\"],\"type\":[\"text\",\"text\",\"image\"]}', '2025-07-17 02:50:47', '2025-07-17 02:50:47'),
(4, 'Final settings', '{\"name\":[\"something_long\",\"another_long\"],\"type\":[\"text\",\"text\"]}', '2025-07-17 07:02:59', '2025-07-17 07:02:59'),
(5, 'content', '{\"name\":[\"headline\",\"subtext\",\"section_one_hl\",\"section_one_body\",\"section_one_link\",\"section_two_hl\",\"section_two_body\",\"section_two_link\",\"image\"],\"type\":[\"text\",\"textarea\",\"text\",\"rich-text\",\"text\",\"text\",\"rich-text\",\"text\",\"image\"]}', '2025-07-19 22:35:39', '2025-07-19 22:36:16');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `type` enum('page','blog') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `body`, `type`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Sample page 1', 'sample-page-1', 'test', 'page', NULL, 'draft', '2025-06-28 22:19:56', '2025-06-29 19:59:45'),
(2, 'Sample page 2', 'sample-page-2', NULL, 'page', 1, 'draft', '2025-06-28 22:20:05', '2025-06-28 23:08:20'),
(3, 'Sample page 3', 'sample-page-3', NULL, 'page', 1, 'draft', '2025-06-28 22:20:13', '2025-06-28 23:08:22'),
(4, 'Blog page 1', 'blog-page-1', NULL, 'blog', NULL, 'draft', '2025-06-28 22:20:28', '2025-07-02 04:45:49'),
(5, 'Blog page 2', 'blog-page-2', NULL, 'blog', NULL, 'draft', '2025-06-28 22:20:34', '2025-07-02 04:45:52'),
(6, 'Blog page 3', 'blog-page-3', '<p>asdsdd</p>', 'blog', NULL, 'draft', '2025-06-28 22:20:41', '2025-07-21 05:32:22'),
(12, 'About x', 'about-x', '<p>ffdfdfere&nbsp;x</p>', 'page', 1, 'published', '2025-07-03 06:01:18', '2025-07-06 23:03:02'),
(13, 'Service', 'service', 'asdsdsd', 'page', 1, 'published', '2025-07-03 06:03:55', '2025-07-04 06:24:44'),
(20, 'Contact', 'contact', 'contact&nbsp;page', 'page', 1, 'published', '2025-07-03 06:11:17', '2025-07-04 05:58:55'),
(21, 'Allow html', 'allow-html', '<p>html&nbsp;</p><p>content&nbsp;</p><p></p><p><strong>here</strong></p>', 'page', 1, 'published', '2025-07-04 06:07:34', '2025-07-07 01:55:43'),
(24, 'Another page', 'another-page', '<p>Another&nbsp;page&nbsp;here</p><p></p><p>hola</p><p>hadi</p>', 'page', 1, 'draft', '2025-07-08 06:14:45', '2025-07-08 06:14:45'),
(25, 'Page with modules', 'page-with-modules', '<p>page&nbsp;</p><p>description</p><p>here&nbsp;</p>', 'page', 1, 'published', '2025-07-20 23:05:53', '2025-07-20 23:05:53');

-- --------------------------------------------------------

--
-- Table structure for table `page_category`
--

CREATE TABLE `page_category` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `page_category`
--

INSERT INTO `page_category` (`id`, `page_id`, `category_id`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2025-07-02 04:53:17', '2025-07-02 04:53:17'),
(2, 2, 1, '2025-07-02 04:53:30', '2025-07-02 04:53:30'),
(3, 1, 1, '2025-07-02 04:53:38', '2025-07-02 04:53:38'),
(7, 13, 1, '2025-07-03 06:03:55', '2025-07-03 06:03:55'),
(10, 12, 4, '2025-07-06 23:01:11', '2025-07-06 23:01:11'),
(11, 12, 3, '2025-07-06 23:02:32', '2025-07-06 23:02:32'),
(16, 25, 1, '2025-07-21 04:45:18', '2025-07-21 04:45:18');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `last_logged_in` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `last_logged_in`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@website.com', '$2y$10$4KVrVzLxkpTsSCP2O3EZPeBwKUV0dn93ZJvk0tx4oHXaenz2sX1ry', 'admin', '2025-07-20 00:32:01', '2025-06-26 05:01:42', '2025-07-19 22:32:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hook` (`hook`),
  ADD KEY `module_setting_id` (`module_setting_id`);

--
-- Indexes for table `module_data`
--
ALTER TABLE `module_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `module_page`
--
ALTER TABLE `module_page`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `module_settings`
--
ALTER TABLE `module_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `page_category`
--
ALTER TABLE `page_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_category_ibfk_1` (`category_id`),
  ADD KEY `page_category_ibfk_2` (`page_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `module_data`
--
ALTER TABLE `module_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `module_page`
--
ALTER TABLE `module_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `module_settings`
--
ALTER TABLE `module_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `page_category`
--
ALTER TABLE `page_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`module_setting_id`) REFERENCES `module_settings` (`id`);

--
-- Constraints for table `module_data`
--
ALTER TABLE `module_data`
  ADD CONSTRAINT `module_data_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `module_page`
--
ALTER TABLE `module_page`
  ADD CONSTRAINT `module_page_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_page_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `page_category`
--
ALTER TABLE `page_category`
  ADD CONSTRAINT `page_category_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_category_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
