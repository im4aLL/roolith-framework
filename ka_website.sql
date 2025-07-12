-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 10:28 AM
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
(4, 'Service', 'service', NULL, '2025-07-06 23:00:29', '2025-07-06 23:00:29');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `hook` varchar(255) NOT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- --------------------------------------------------------

--
-- Table structure for table `module_page`
--

CREATE TABLE `module_page` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
(1, 'Hero', '{\"name\":[\"title\",\"body\",\"image\",\"another\"],\"type\":[\"text\",\"textarea\",\"image\",\"text\"]}', '2025-07-09 06:34:13', '2025-07-12 08:09:49');

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
(6, 'Blog page 3', 'blog-page-3', NULL, 'blog', NULL, 'draft', '2025-06-28 22:20:41', '2025-06-29 20:18:42'),
(12, 'About x', 'about-x', '<p>ffdfdfere&nbsp;x</p>', 'page', 1, 'published', '2025-07-03 06:01:18', '2025-07-06 23:03:02'),
(13, 'Service', 'service', 'asdsdsd', 'page', 1, 'published', '2025-07-03 06:03:55', '2025-07-04 06:24:44'),
(20, 'Contact', 'contact', 'contact&nbsp;page', 'page', 1, 'published', '2025-07-03 06:11:17', '2025-07-04 05:58:55'),
(21, 'Allow html', 'allow-html', '<p>html&nbsp;</p><p>content&nbsp;</p><p></p><p><strong>here</strong></p>', 'page', 1, 'published', '2025-07-04 06:07:34', '2025-07-07 01:55:43'),
(24, 'Another page', 'another-page', '<p>Another&nbsp;page&nbsp;here</p><p></p><p>hola</p><p>hadi</p>', 'page', 1, 'draft', '2025-07-08 06:14:45', '2025-07-08 06:14:45');

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
(11, 12, 3, '2025-07-06 23:02:32', '2025-07-06 23:02:32');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `type` enum('string','int','bool','json','float') NOT NULL DEFAULT 'string',
  `group` varchar(50) DEFAULT NULL,
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
(1, 'Admin', 'admin@website.com', '$2y$10$4KVrVzLxkpTsSCP2O3EZPeBwKUV0dn93ZJvk0tx4oHXaenz2sX1ry', 'admin', '2025-07-07 03:34:51', '2025-06-26 05:01:42', '2025-07-07 01:34:51');

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
  ADD UNIQUE KEY `hook` (`hook`);

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
  ADD PRIMARY KEY (`key`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_data`
--
ALTER TABLE `module_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_page`
--
ALTER TABLE `module_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_settings`
--
ALTER TABLE `module_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `page_category`
--
ALTER TABLE `page_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

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
