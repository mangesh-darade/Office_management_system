-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 14, 2025 at 10:45 AM
-- Server version: 5.7.26
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `employmanagement`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entity_type` varchar(100) NOT NULL,
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_actlog_actor` (`actor_id`),
  KEY `idx_actlog_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `actor_id`, `entity_type`, `entity_id`, `action`, `changes`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 5, 'projects', 1, 'created', NULL, '58.84.62.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 17:31:52'),
(2, 5, 'tasks', 1, 'created', NULL, '58.84.62.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 17:41:00'),
(3, 5, 'employees', 1, 'created', NULL, '58.84.62.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 17:41:33'),
(4, 5, 'designations', 1, 'created', NULL, '58.84.62.201', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 17:41:51'),
(5, 6, 'employees', 3, 'created', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 12:25:01'),
(6, 6, 'employees', 3, 'updated', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 12:26:07'),
(7, 7, 'employees', 5, 'created', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 03:20:29'),
(8, 7, 'employees', 5, 'deleted', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 11:10:37'),
(9, 7, 'employees', 6, 'created', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 11:11:55'),
(10, 8, 'employees', 6, 'updated', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 11:14:43'),
(11, 7, 'employees', 7, 'created', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 11:17:01'),
(12, 7, 'projects', 2, 'created', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:23:18'),
(13, 7, 'tasks', 2, 'created', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 12:24:14');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `posted_by` int(11) NOT NULL,
  `target_roles` varchar(100) DEFAULT 'all',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE IF NOT EXISTS `attendance` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `att_date` date NOT NULL,
  `punch_in` datetime DEFAULT NULL,
  `punch_out` datetime DEFAULT NULL,
  `notes` text,
  `attachment_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `source` enum('manual','auto') NOT NULL DEFAULT 'manual',
  `total_hours` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','half_day','work_from_home') NOT NULL DEFAULT 'present',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance` (`user_id`,`att_date`),
  KEY `idx_att_user_date` (`user_id`,`att_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `att_date`, `punch_in`, `punch_out`, `notes`, `attachment_path`, `latitude`, `longitude`, `ip_address`, `source`, `total_hours`, `status`, `created_at`, `updated_at`) VALUES
(5, 8, '2025-11-14', '2025-11-14 11:14:00', '2025-11-14 11:15:00', '', NULL, '18.5097370', '73.7992520', '::1', 'manual', NULL, 'present', '2025-11-14 11:14:59', '2025-11-14 11:15:07');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE IF NOT EXISTS `attendance_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `event` enum('punch_in','punch_out','auto_login','auto_logout') NOT NULL,
  `event_time` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_user_time` (`user_id`,`event_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `calls`
--

DROP TABLE IF EXISTS `calls`;
CREATE TABLE IF NOT EXISTS `calls` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `initiator_id` int(10) UNSIGNED NOT NULL,
  `status` enum('initiated','ringing','connected','ended','failed') NOT NULL DEFAULT 'initiated',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `initiator_id` (`initiator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `calls`
--

INSERT INTO `calls` (`id`, `conversation_id`, `initiator_id`, `status`, `created_at`, `ended_at`) VALUES
(1, 1, 7, 'initiated', '2025-11-14 14:41:24', NULL),
(2, 1, 7, 'ended', '2025-11-14 14:47:45', '2025-11-14 14:48:20'),
(3, 1, 7, 'initiated', '2025-11-14 14:48:31', NULL),
(4, 1, 7, 'ended', '2025-11-14 14:48:57', '2025-11-14 14:49:20'),
(5, 1, 7, 'ended', '2025-11-14 15:29:33', '2025-11-14 15:30:29'),
(6, 1, 7, 'initiated', '2025-11-14 15:41:33', NULL),
(7, 1, 7, 'ended', '2025-11-14 15:41:55', '2025-11-14 15:42:13'),
(8, 1, 7, 'ended', '2025-11-14 15:51:23', '2025-11-14 15:51:53'),
(9, 1, 7, 'ended', '2025-11-14 16:00:46', '2025-11-14 16:01:59'),
(10, 1, 7, 'ended', '2025-11-14 16:11:48', '2025-11-14 16:11:58'),
(11, 1, 7, 'ended', '2025-11-14 16:12:06', '2025-11-14 16:12:22'),
(12, 1, 7, 'ended', '2025-11-14 16:12:30', '2025-11-14 16:12:59');

-- --------------------------------------------------------

--
-- Table structure for table `call_participants`
--

DROP TABLE IF EXISTS `call_participants`;
CREATE TABLE IF NOT EXISTS `call_participants` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_call_user` (`call_id`,`user_id`),
  KEY `call_id` (`call_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `call_participants`
--

INSERT INTO `call_participants` (`id`, `call_id`, `user_id`, `joined_at`, `left_at`) VALUES
(1, 1, 5, NULL, NULL),
(2, 1, 6, NULL, NULL),
(3, 1, 7, NULL, NULL),
(4, 1, 8, NULL, NULL),
(5, 2, 5, NULL, NULL),
(6, 2, 6, NULL, NULL),
(7, 2, 7, NULL, NULL),
(8, 2, 8, NULL, NULL),
(9, 3, 5, NULL, NULL),
(10, 3, 6, NULL, NULL),
(11, 3, 7, NULL, NULL),
(12, 3, 8, NULL, NULL),
(13, 4, 5, NULL, NULL),
(14, 4, 6, NULL, NULL),
(15, 4, 7, NULL, NULL),
(16, 4, 8, NULL, NULL),
(17, 5, 5, NULL, NULL),
(18, 5, 6, NULL, NULL),
(19, 5, 7, NULL, NULL),
(20, 5, 8, NULL, NULL),
(21, 6, 5, NULL, NULL),
(22, 6, 6, NULL, NULL),
(23, 6, 7, NULL, NULL),
(24, 6, 8, NULL, NULL),
(25, 7, 5, NULL, NULL),
(26, 7, 6, NULL, NULL),
(27, 7, 7, NULL, NULL),
(28, 7, 8, NULL, NULL),
(29, 8, 5, NULL, NULL),
(30, 8, 6, NULL, NULL),
(31, 8, 7, NULL, NULL),
(32, 8, 8, NULL, NULL),
(33, 9, 5, NULL, NULL),
(34, 9, 6, NULL, NULL),
(35, 9, 7, NULL, NULL),
(36, 9, 8, NULL, NULL),
(37, 10, 5, NULL, NULL),
(38, 10, 6, NULL, NULL),
(39, 10, 7, NULL, NULL),
(40, 10, 8, NULL, NULL),
(41, 11, 5, NULL, NULL),
(42, 11, 6, NULL, NULL),
(43, 11, 7, NULL, NULL),
(44, 11, 8, NULL, NULL),
(45, 12, 5, NULL, NULL),
(46, 12, 6, NULL, NULL),
(47, 12, 7, NULL, NULL),
(48, 12, 8, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_code` varchar(50) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `zip_code` varchar(20) DEFAULT NULL,
  `gstin` varchar(50) DEFAULT NULL,
  `pan_number` varchar(20) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `client_type` varchar(30) DEFAULT 'company',
  `account_manager_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `notes` text,
  `logo` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_client_code` (`client_code`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `client_code`, `company_name`, `contact_person`, `email`, `phone`, `alternate_phone`, `website`, `address`, `city`, `state`, `country`, `zip_code`, `gstin`, `pan_number`, `industry`, `client_type`, `account_manager_id`, `status`, `notes`, `logo`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CLI-2025-00001', 'Magic Wear', 'unkonw', 'magicwear@gmail.com', '7656556576', '', '', '', '', '', 'India', '', '', '', '', 'company', 5, 'active', '', NULL, 5, '2025-11-13 17:33:42', '2025-11-13 17:33:42');

-- --------------------------------------------------------

--
-- Table structure for table `client_contacts`
--

DROP TABLE IF EXISTS `client_contacts`;
CREATE TABLE IF NOT EXISTS `client_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `contact_name` varchar(200) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `department` varchar(100) DEFAULT NULL,
  `notes` text,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_primary` (`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('dm','group') NOT NULL DEFAULT 'dm',
  `title` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `conversations`
--

INSERT INTO `conversations` (`id`, `type`, `title`, `created_by`, `created_at`) VALUES
(1, 'group', 'wdasd', 7, '2025-11-14 14:41:18');

-- --------------------------------------------------------

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
CREATE TABLE IF NOT EXISTS `conversation_participants` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('member','admin') NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_conv_user` (`conversation_id`,`user_id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `conversation_participants`
--

INSERT INTO `conversation_participants` (`id`, `conversation_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 7, 'admin', '2025-11-14 14:41:18'),
(2, 1, 5, 'member', '2025-11-14 14:41:18'),
(3, 1, 8, 'member', '2025-11-14 14:41:19'),
(4, 1, 6, 'member', '2025-11-14 14:41:19');

-- --------------------------------------------------------

--
-- Table structure for table `daily_work_logs`
--

DROP TABLE IF EXISTS `daily_work_logs`;
CREATE TABLE IF NOT EXISTS `daily_work_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `notes` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_worklog` (`user_id`,`task_id`,`work_date`),
  KEY `fk_dwl_task` (`task_id`),
  KEY `idx_dwl_user_date` (`user_id`,`work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_code` varchar(20) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `description` text,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dept_code` (`dept_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_code`, `dept_name`, `description`, `manager_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'D001', 'Devlopment', '', 5, 'active', '2025-11-13 17:41:33', '2025-11-13 17:41:33');

-- --------------------------------------------------------

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
CREATE TABLE IF NOT EXISTS `designations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `designation_code` varchar(20) NOT NULL,
  `designation_name` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT '1',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `designation_code` (`designation_code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `designations`
--

INSERT INTO `designations` (`id`, `designation_code`, `designation_name`, `department_id`, `level`, `status`, `created_at`, `updated_at`) VALUES
(1, 'D001', 'Lead', 1, 1, 'active', '2025-11-13 17:41:51', '2025-11-13 17:41:51');

-- --------------------------------------------------------

--
-- Table structure for table `dm_manager`
--

DROP TABLE IF EXISTS `dm_manager`;
CREATE TABLE IF NOT EXISTS `dm_manager` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `assign_id` int(11) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `title` varchar(191) DEFAULT NULL,
  `squary` longtext NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `backup_path` varchar(500) DEFAULT NULL,
  `database_name` varchar(191) DEFAULT NULL,
  `table_name` varchar(191) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `dm_manager`
--

INSERT INTO `dm_manager` (`id`, `project_id`, `assign_id`, `version`, `title`, `squary`, `file_path`, `backup_path`, `database_name`, `table_name`, `created_at`) VALUES
(1, 1, 5, '1602', 'Job Works', '  employee_id INT PRIMARY KEY,          -- Unique ID for each employee,\n  first_name VARCHAR(50) NOT NULL,      -- Employee\'s first name (up to 50 characters),\n  last_name VARCHAR(50) NOT NULL,       -- Employee\'s last name (up to 50 characters),\n  email VARCHAR(100) UNIQUE NOT NULL,   -- Employee\'s email (up to 100 characters), must be unique,\n  hire_date DATE NOT NULL,              -- Date of hiring (must be a valid date),\n  salary DECIMAL(10, 2) NOT NULL,       -- Employee\'s salary, can have two decimal places,\n  department VARCHAR(50),               -- Department the employee belongs to,\n  job_title VARCHAR(100),               -- Job title/position of the employee,\n  manager_id INT,                       -- Manager\'s employee ID (foreign key to the employee),\n', 'C:\\wamp\\www\\Office_management_system\\sitadmin_clothingdev1602_new (2).sql', 'C:\\wamp\\www\\Office_management_system\\sitadmin_clothingdev1602_new (2).sql.bak_20251114_105624', 'sitadmin_clothingdev1602_new', 'sma_addresses', '2025-11-14 10:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `emp_code` varchar(50) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `last_name` varchar(80) DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `personal_email` varchar(190) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `probation_end` date DEFAULT NULL,
  `department` varchar(120) DEFAULT NULL,
  `designation` varchar(120) DEFAULT NULL,
  `reporting_to` bigint(20) UNSIGNED DEFAULT NULL,
  `employment_type` enum('full_time','part_time','contract','intern') DEFAULT 'full_time',
  `salary_ctc` decimal(12,2) DEFAULT NULL,
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `emp_code` (`emp_code`),
  KEY `idx_employees_reporting_to` (`reporting_to`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `emp_code`, `first_name`, `last_name`, `gender`, `dob`, `personal_email`, `phone`, `address`, `city`, `state`, `country`, `zipcode`, `join_date`, `probation_end`, `department`, `designation`, `reporting_to`, `employment_type`, `salary_ctc`, `emergency_contact_name`, `emergency_contact_phone`, `created_at`, `updated_at`) VALUES
(3, 5, 'D_001', 'Mangesh', 'Darade', NULL, NULL, NULL, '7744010738', NULL, NULL, NULL, NULL, NULL, '2025-11-13', NULL, 'Devlopment', 'Lead', 6, 'full_time', NULL, NULL, NULL, '2025-11-13 17:55:01', '2025-11-13 17:56:07'),
(6, 8, 'Ep_8', 'Mangesh', 'Darade', NULL, NULL, NULL, '07744010738', NULL, NULL, NULL, NULL, NULL, '2025-11-14', NULL, 'Devlopment', 'Software', 5, 'full_time', NULL, NULL, NULL, '2025-11-14 11:11:55', '2025-11-14 11:11:55'),
(7, 6, '342', 'VISHAL', 'UNIFORMS', NULL, NULL, NULL, '09780091008', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, 'full_time', NULL, NULL, NULL, '2025-11-14 11:17:01', '2025-11-14 11:17:01');

-- --------------------------------------------------------

--
-- Table structure for table `leave_approvals`
--

DROP TABLE IF EXISTS `leave_approvals`;
CREATE TABLE IF NOT EXISTS `leave_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `leave_id` bigint(20) UNSIGNED NOT NULL,
  `approver_id` bigint(20) UNSIGNED NOT NULL,
  `level` enum('lead','hr') NOT NULL,
  `decision` enum('approved','rejected') NOT NULL,
  `remarks` text,
  `decided_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_la_approver` (`approver_id`),
  KEY `idx_la_leave` (`leave_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type_id` bigint(20) UNSIGNED NOT NULL,
  `year` int(11) NOT NULL,
  `opening_balance` decimal(5,2) NOT NULL DEFAULT '0.00',
  `accrued` decimal(5,2) NOT NULL DEFAULT '0.00',
  `used` decimal(5,2) NOT NULL DEFAULT '0.00',
  `closing_balance` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leave_balance` (`user_id`,`type_id`,`year`),
  KEY `fk_lb_type` (`type_id`),
  KEY `idx_lb_user_year` (`user_id`,`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type_id` bigint(20) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days` decimal(5,2) NOT NULL,
  `reason` text,
  `status` enum('pending','lead_approved','hr_approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `current_approver_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_lr_type` (`type_id`),
  KEY `fk_lr_approver` (`current_approver_id`),
  KEY `idx_lr_user_status` (`user_id`,`status`),
  KEY `idx_lr_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `annual_quota` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_paid` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `body` mediumtext,
  `attachment_path` varchar(512) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `message_reads`
--

DROP TABLE IF EXISTS `message_reads`;
CREATE TABLE IF NOT EXISTS `message_reads` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `read_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_msg_user` (`message_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('task_assigned','leave_request','leave_status','deadline_reminder','system') NOT NULL,
  `title` varchar(190) NOT NULL,
  `body` text,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `channel` enum('in_app','email') NOT NULL DEFAULT 'in_app',
  `read_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `module` varchar(64) NOT NULL,
  `can_access` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_module` (`role_id`,`module`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `role_id`, `module`, `can_access`) VALUES
(1, 1, '', 1),
(2, 2, '', 1),
(3, 3, '', 1),
(4, 4, '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
CREATE TABLE IF NOT EXISTS `projects` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(190) NOT NULL,
  `db_name` varchar(191) DEFAULT NULL,
  `description` text,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planned','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planned',
  `manager_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_projects_manager` (`manager_id`),
  KEY `idx_projects_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `code`, `name`, `db_name`, `description`, `start_date`, `end_date`, `status`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'Pr_01', 'MagicWear', NULL, NULL, '2025-11-13', '2025-11-29', '', NULL, '2025-11-13 17:31:52', '2025-11-13 17:31:52'),
(2, 'BNL', 'Kitchen', NULL, NULL, '2025-11-14', '2025-11-14', 'planned', NULL, '2025-11-14 12:23:18', '2025-11-14 12:23:18');

-- --------------------------------------------------------

--
-- Table structure for table `project_members`
--

DROP TABLE IF EXISTS `project_members`;
CREATE TABLE IF NOT EXISTS `project_members` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('member','lead','viewer') NOT NULL DEFAULT 'member',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_user` (`project_id`,`user_id`),
  KEY `idx_pm_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `project_status_history`
--

DROP TABLE IF EXISTS `project_status_history`;
CREATE TABLE IF NOT EXISTS `project_status_history` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `old_status` enum('planned','active','on_hold','completed','cancelled') DEFAULT NULL,
  `new_status` enum('planned','active','on_hold','completed','cancelled') NOT NULL,
  `changed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_psh_user` (`changed_by`),
  KEY `idx_psh_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text,
  `send_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'queued',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_send_at` (`send_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`id`, `user_id`, `email`, `from_email`, `from_name`, `type`, `subject`, `body`, `send_at`, `sent_at`, `status`, `created_at`) VALUES
(1, 5, 'darade@gmail.com', NULL, NULL, 'task_assigned', 'Task assigned: Jobworks', 'You have been assigned a task: Jobworks\\n\\nOpen: https://internalportal.elintpos.in/tasks/1', '2025-11-13 17:41:00', NULL, 'queued', '2025-11-13 17:41:00'),
(2, 5, 'darade@gmail.com', NULL, NULL, 'task_assigned', 'Task assigned: sfsfsdf', 'You have been assigned a task: sfsfsdf\\n\\nOpen: http://localhost/Office_management_system/tasks/2', '2025-11-14 12:24:00', NULL, 'queued', '2025-11-14 12:24:14');

-- --------------------------------------------------------

--
-- Table structure for table `reminder_schedules`
--

DROP TABLE IF EXISTS `reminder_schedules`;
CREATE TABLE IF NOT EXISTS `reminder_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `audience` varchar(20) DEFAULT 'user',
  `user_id` int(11) DEFAULT NULL,
  `weekdays` varchar(50) NOT NULL,
  `send_time` char(5) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text,
  `active` tinyint(1) DEFAULT '1',
  `last_run_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `reminder_templates`
--

DROP TABLE IF EXISTS `reminder_templates`;
CREATE TABLE IF NOT EXISTS `reminder_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `requirements`
--

DROP TABLE IF EXISTS `requirements`;
CREATE TABLE IF NOT EXISTS `requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `req_number` varchar(50) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `title` varchar(500) NOT NULL,
  `description` text,
  `requirement_type` varchar(50) DEFAULT 'new_feature',
  `priority` varchar(20) DEFAULT 'medium',
  `status` varchar(50) DEFAULT 'received',
  `budget_estimate` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `expected_delivery_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_req_number` (`req_number`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `requirements`
--

INSERT INTO `requirements` (`id`, `req_number`, `client_id`, `project_id`, `title`, `description`, `requirement_type`, `priority`, `status`, `budget_estimate`, `currency`, `expected_delivery_date`, `received_date`, `owner_id`, `guide_id`, `assigned_to`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'REQ-2025-00001', 1, 1, 'Job Works', '<table border=\"1\" cellpadding=\"1\" cellspacing=\"1\" style=\"width:500px\">\r\n	<thead>\r\n		<tr>\r\n			<td>Release</td>\r\n			<td>Screen</td>\r\n			<td>Menu</td>\r\n			<td>Details</td>\r\n			<td>Owner</td>\r\n		</tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Variant BOM</td>\r\n			<td>Production Unit</td>\r\n			<td>BOM with Variants</td>\r\n			<td>Ayush</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>RM Calculator</td>\r\n			<td>Production Unit</td>\r\n			<td>RM Calulatror + Generate PO Navigation</td>\r\n			<td>Bhavana</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Generate Variant PO</td>\r\n			<td>Production Unit</td>\r\n			<td>Generate PO with request + xfers</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Transfer RM</td>\r\n			<td>Production Unit</td>\r\n			<td>Only mapped Purchase related records</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Add/Edit Supplier</td>\r\n			<td>Purchase</td>\r\n			<td>add location field and column in DB</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Add / Edit User</td>\r\n			<td>&nbsp;</td>\r\n			<td>For &#39;Sales User&#39; Vendor locations are not<br />\r\n			displayed.</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 2</td>\r\n			<td>Genrate Variant PO</td>\r\n			<td>&nbsp;</td>\r\n			<td>Multiple PO Products.</td>\r\n			<td>&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 2</td>\r\n			<td>Genrate Variant PO</td>\r\n			<td>&nbsp;</td>\r\n			<td>Handling for Variant with Recipe 0</td>\r\n			<td>&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 2</td>\r\n			<td>Genrate Variant PO</td>\r\n			<td>&nbsp;</td>\r\n			<td>Handling supplier and request qty for multiple locations.</td>\r\n			<td>&nbsp;</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n\r\n<p>&nbsp;\r\n<p>&nbsp;</p>\r\n</p>\r\n', 'new_feature', 'high', 'received', NULL, 'INR', '2025-11-20', '2025-11-01', NULL, NULL, 5, 5, '2025-11-13 17:36:28', '2025-11-13 17:36:28'),
(2, 'REQ-2025-00002', 1, 1, '453', '', 'enhancement', 'low', 'received', NULL, 'INR', '2025-11-14', '2025-11-14', NULL, NULL, NULL, 7, '2025-11-14 11:38:38', '2025-11-14 11:38:38'),
(3, 'REQ-2025-00003', 1, 1, '5234', '<p>34324234</p>\r\n', 'enhancement', 'low', 'received', NULL, 'INR', '2025-11-14', '2025-11-14', 8, NULL, 5, 7, '2025-11-14 11:45:50', '2025-11-14 11:45:50'),
(4, 'REQ-2025-00004', 1, 2, 'sfsfsdf', '<p>sdfsdf</p>\r\n', 'bug_fix', 'low', 'received', NULL, 'INR', NULL, '2025-11-14', 7, NULL, 0, 7, '2025-11-14 12:23:56', '2025-11-14 12:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `requirement_attachments`
--

DROP TABLE IF EXISTS `requirement_attachments`;
CREATE TABLE IF NOT EXISTS `requirement_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requirement_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_requirement` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_versions`
--

DROP TABLE IF EXISTS `requirement_versions`;
CREATE TABLE IF NOT EXISTS `requirement_versions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requirement_id` int(11) NOT NULL,
  `version_no` int(11) NOT NULL,
  `title` varchar(500) NOT NULL,
  `description` text,
  `requirement_type` varchar(50) DEFAULT NULL,
  `priority` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `budget_estimate` decimal(15,2) DEFAULT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_req` (`requirement_id`),
  KEY `idx_version` (`version_no`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `requirement_versions`
--

INSERT INTO `requirement_versions` (`id`, `requirement_id`, `version_no`, `title`, `description`, `requirement_type`, `priority`, `status`, `budget_estimate`, `expected_delivery_date`, `received_date`, `owner_id`, `guide_id`, `assigned_to`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'Job Works', '<table border=\"1\" cellpadding=\"1\" cellspacing=\"1\" style=\"width:500px\">\r\n	<thead>\r\n		<tr>\r\n			<td>Release</td>\r\n			<td>Screen</td>\r\n			<td>Menu</td>\r\n			<td>Details</td>\r\n			<td>Owner</td>\r\n		</tr>\r\n	</thead>\r\n	<tbody>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Variant BOM</td>\r\n			<td>Production Unit</td>\r\n			<td>BOM with Variants</td>\r\n			<td>Ayush</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>RM Calculator</td>\r\n			<td>Production Unit</td>\r\n			<td>RM Calulatror + Generate PO Navigation</td>\r\n			<td>Bhavana</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Generate Variant PO</td>\r\n			<td>Production Unit</td>\r\n			<td>Generate PO with request + xfers</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Transfer RM</td>\r\n			<td>Production Unit</td>\r\n			<td>Only mapped Purchase related records</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Add/Edit Supplier</td>\r\n			<td>Purchase</td>\r\n			<td>add location field and column in DB</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 1</td>\r\n			<td>Add / Edit User</td>\r\n			<td>&nbsp;</td>\r\n			<td>For &#39;Sales User&#39; Vendor locations are not<br />\r\n			displayed.</td>\r\n			<td>Mangesh</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 2</td>\r\n			<td>Genrate Variant PO</td>\r\n			<td>&nbsp;</td>\r\n			<td>Multiple PO Products.</td>\r\n			<td>&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 2</td>\r\n			<td>Genrate Variant PO</td>\r\n			<td>&nbsp;</td>\r\n			<td>Handling for Variant with Recipe 0</td>\r\n			<td>&nbsp;</td>\r\n		</tr>\r\n		<tr>\r\n			<td>Release 2</td>\r\n			<td>Genrate Variant PO</td>\r\n			<td>&nbsp;</td>\r\n			<td>Handling supplier and request qty for multiple locations.</td>\r\n			<td>&nbsp;</td>\r\n		</tr>\r\n	</tbody>\r\n</table>\r\n\r\n<p>&nbsp;\r\n<p>&nbsp;</p>\r\n</p>\r\n', 'new_feature', 'high', 'received', NULL, '2025-11-20', '2025-11-01', NULL, NULL, 5, 5, '2025-11-13 17:36:28'),
(2, 2, 1, '453', '', 'enhancement', 'low', 'received', NULL, '2025-11-14', '2025-11-14', NULL, NULL, NULL, 7, '2025-11-14 11:38:38'),
(3, 3, 1, '5234', '<p>34324234</p>\r\n', 'enhancement', 'low', 'received', NULL, '2025-11-14', '2025-11-14', NULL, NULL, 5, 7, '2025-11-14 11:45:50'),
(4, 4, 1, 'sfsfsdf', '<p>sdfsdf</p>\r\n', 'bug_fix', 'low', 'received', NULL, NULL, '2025-11-14', NULL, NULL, 0, 7, '2025-11-14 12:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `rm_daily_tasks`
--

DROP TABLE IF EXISTS `rm_daily_tasks`;
CREATE TABLE IF NOT EXISTS `rm_daily_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED DEFAULT NULL,
  `release_id` bigint(20) UNSIGNED DEFAULT NULL,
  `task_description` text NOT NULL,
  `task_type` enum('development','testing','review','deployment','documentation','other') NOT NULL DEFAULT 'development',
  `status` enum('pending','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `estimated_hours` decimal(6,2) DEFAULT NULL,
  `actual_hours` decimal(6,2) DEFAULT NULL,
  `work_date` date NOT NULL,
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_daily_tasks_employee` (`employee_id`),
  KEY `idx_rm_daily_tasks_project` (`project_id`),
  KEY `idx_rm_daily_tasks_release` (`release_id`),
  KEY `idx_rm_daily_tasks_status` (`status`),
  KEY `idx_rm_daily_tasks_work_date` (`work_date`),
  KEY `idx_rm_daily_tasks_type` (`task_type`),
  KEY `idx_rm_daily_tasks_created_at` (`created_at`),
  KEY `idx_rm_daily_tasks_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rm_modules`
--

DROP TABLE IF EXISTS `rm_modules`;
CREATE TABLE IF NOT EXISTS `rm_modules` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_modules_project` (`project_id`),
  KEY `idx_rm_modules_created_by` (`created_by`),
  KEY `idx_rm_modules_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rm_projects`
--

DROP TABLE IF EXISTS `rm_projects`;
CREATE TABLE IF NOT EXISTS `rm_projects` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` enum('active','inactive','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_projects_created_by` (`created_by`),
  KEY `idx_rm_projects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rm_releases`
--

DROP TABLE IF EXISTS `rm_releases`;
CREATE TABLE IF NOT EXISTS `rm_releases` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `screen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `release_name` varchar(255) NOT NULL,
  `version` varchar(50) NOT NULL,
  `functionality_name` varchar(255) NOT NULL,
  `description` text,
  `environment` enum('dev','staging','prod','test') NOT NULL DEFAULT 'dev',
  `status` enum('pending','in_progress','testing','completed','cancelled','deployed') NOT NULL DEFAULT 'pending',
  `assigned_by` bigint(20) UNSIGNED NOT NULL,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `tested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `testing_pending_with` bigint(20) UNSIGNED DEFAULT NULL,
  `code_overview_by` bigint(20) UNSIGNED DEFAULT NULL,
  `doc_url` varchar(500) DEFAULT NULL,
  `km_note` text,
  `kt_note` text,
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `estimated_hours` decimal(6,2) DEFAULT NULL,
  `actual_hours` decimal(6,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `deployment_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_releases_project` (`project_id`),
  KEY `idx_rm_releases_module` (`module_id`),
  KEY `idx_rm_releases_screen` (`screen_id`),
  KEY `idx_rm_releases_assigned_by` (`assigned_by`),
  KEY `idx_rm_releases_assigned_to` (`assigned_to`),
  KEY `idx_rm_releases_status` (`status`),
  KEY `idx_rm_releases_environment` (`environment`),
  KEY `idx_rm_releases_priority` (`priority`),
  KEY `fk_rm_releases_tested_by` (`tested_by`),
  KEY `fk_rm_releases_testing_pending` (`testing_pending_with`),
  KEY `fk_rm_releases_code_overview` (`code_overview_by`),
  KEY `idx_rm_releases_created_at` (`created_at`),
  KEY `idx_rm_releases_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `rm_screens`
--

DROP TABLE IF EXISTS `rm_screens`;
CREATE TABLE IF NOT EXISTS `rm_screens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `screen_type` enum('web','mobile','desktop','api') NOT NULL DEFAULT 'web',
  `status` enum('active','inactive','completed','cancelled') NOT NULL DEFAULT 'active',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_screens_module` (`module_id`),
  KEY `idx_rm_screens_created_by` (`created_by`),
  KEY `idx_rm_screens_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, '2025-11-13 17:26:48', '2025-11-13 17:26:48'),
(2, 'hr', NULL, '2025-11-13 17:26:48', '2025-11-13 17:26:48'),
(3, 'manager', NULL, '2025-11-13 17:26:48', '2025-11-13 17:26:48'),
(4, 'employee', NULL, '2025-11-13 17:26:48', '2025-11-13 17:26:48');

-- --------------------------------------------------------

--
-- Table structure for table `saved_queries`
--

DROP TABLE IF EXISTS `saved_queries`;
CREATE TABLE IF NOT EXISTS `saved_queries` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `version` varchar(50) DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `sql_text` longtext NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(120) NOT NULL,
  `value` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'attendance_start_time', '09:30', '2025-11-13 18:01:41', '2025-11-13 18:01:41'),
(2, 'attendance_end_time', '18:30', '2025-11-13 18:01:41', '2025-11-13 18:01:41'),
(3, 'attendance_grace_minutes', '15', '2025-11-13 18:01:41', '2025-11-13 18:01:41'),
(4, 'attendance_weekends', '0,6', '2025-11-13 18:01:41', '2025-11-13 18:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `signaling_messages`
--

DROP TABLE IF EXISTS `signaling_messages`;
CREATE TABLE IF NOT EXISTS `signaling_messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `call_id` bigint(20) UNSIGNED NOT NULL,
  `from_user_id` int(10) UNSIGNED NOT NULL,
  `to_user_id` int(10) UNSIGNED DEFAULT NULL,
  `type` enum('offer','answer','ice') NOT NULL,
  `payload` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `call_id` (`call_id`),
  KEY `to_user_id` (`to_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `signaling_messages`
--

INSERT INTO `signaling_messages` (`id`, `call_id`, `from_user_id`, `to_user_id`, `type`, `payload`, `created_at`) VALUES
(1, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1362020224 1 udp 2122129151 192.168.1.7 57892 typ host generation 0 ufrag Okae network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(2, 1, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 9213807953250913101 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Okae\\r\\na=ice-pwd:qb4uNemRU8drc/tRg7Yf+xNO\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 71:4F:33:4A:DA:43:A2:C2:7A:EB:46:2B:A1:FF:F8:78:30:94:78:2B:BE:47:3D:F9:A8:16:C9:49:51:F6:22:08\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 e54815e3-8e31-44ff-a07c-d77670ea8f7f\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2575560954 cname:z3Bd/GIJic56bK9b\\r\\na=ssrc:2575560954 msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 e54815e3-8e31-44ff-a07c-d77670ea8f7f\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Okae\\r\\na=ice-pwd:qb4uNemRU8drc/tRg7Yf+xNO\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 71:4F:33:4A:DA:43:A2:C2:7A:EB:46:2B:A1:FF:F8:78:30:94:78:2B:BE:47:3D:F9:A8:16:C9:49:51:F6:22:08\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 defc89c2-9420-45ca-8bca-db336b2f3959\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 1377563623 4060905322\\r\\na=ssrc:1377563623 cname:z3Bd/GIJic56bK9b\\r\\na=ssrc:1377563623 msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 defc89c2-9420-45ca-8bca-db336b2f3959\\r\\na=ssrc:4060905322 cname:z3Bd/GIJic56bK9b\\r\\na=ssrc:4060905322 msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 defc89c2-9420-45ca-8bca-db336b2f3959\\r\\n\",\"type\":\"offer\"}', '2025-11-14 14:41:26'),
(3, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:3036316228 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 57893 typ host generation 0 ufrag Okae network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(4, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1362020224 1 udp 2122129151 192.168.1.7 57895 typ host generation 0 ufrag Okae network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(5, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:3036316228 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 57896 typ host generation 0 ufrag Okae network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(6, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:2551623071 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 57894 typ host generation 0 ufrag Okae network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(7, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:2551623071 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 57897 typ host generation 0 ufrag Okae network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(8, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:2944668436 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Okae network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(9, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1723627787 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Okae network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(10, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1246779088 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Okae network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:26'),
(11, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:2944668436 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Okae network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:27'),
(12, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1246779088 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Okae network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:27'),
(13, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1723627787 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Okae network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:27'),
(14, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:299152681 1 udp 1685921535 58.84.62.152 8010 typ srflx raddr 192.168.1.7 rport 57892 generation 0 ufrag Okae network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:27'),
(15, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:299152681 1 udp 1685921535 58.84.62.152 10209 typ srflx raddr 192.168.1.7 rport 57895 generation 0 ufrag Okae network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Okae\"}', '2025-11-14 14:41:27'),
(16, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:2551623071 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 62829 typ host generation 0 ufrag v6y2 network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:27'),
(17, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:3036316228 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 62828 typ host generation 0 ufrag v6y2 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:27'),
(18, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1362020224 1 udp 2122129151 192.168.1.7 62827 typ host generation 0 ufrag v6y2 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:27'),
(19, 1, 7, NULL, 'answer', '{\"sdp\":\"v=0\\r\\no=- 9213807953250913101 3 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:v6y2\\r\\na=ice-pwd:GkJ/ou7IdnSlapXNVjKTfOJC\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 71:4F:33:4A:DA:43:A2:C2:7A:EB:46:2B:A1:FF:F8:78:30:94:78:2B:BE:47:3D:F9:A8:16:C9:49:51:F6:22:08\\r\\na=setup:active\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 e54815e3-8e31-44ff-a07c-d77670ea8f7f\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2871415555 cname:z3Bd/GIJic56bK9b\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:v6y2\\r\\na=ice-pwd:GkJ/ou7IdnSlapXNVjKTfOJC\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 71:4F:33:4A:DA:43:A2:C2:7A:EB:46:2B:A1:FF:F8:78:30:94:78:2B:BE:47:3D:F9:A8:16:C9:49:51:F6:22:08\\r\\na=setup:active\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:daf7e06e-fa92-41b2-ae6c-9963eeb0f7e8 defc89c2-9420-45ca-8bca-db336b2f3959\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 3223616746 2545985504\\r\\na=ssrc:3223616746 cname:z3Bd/GIJic56bK9b\\r\\na=ssrc:2545985504 cname:z3Bd/GIJic56bK9b\\r\\n\",\"type\":\"answer\"}', '2025-11-14 14:41:28'),
(20, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:299152681 1 udp 1685921535 58.84.62.152 7608 typ srflx raddr 192.168.1.7 rport 62827 generation 0 ufrag v6y2 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:28'),
(21, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1246779088 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag v6y2 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:28'),
(22, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:2944668436 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag v6y2 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:28'),
(23, 1, 7, NULL, 'ice', '{\"candidate\":\"candidate:1723627787 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag v6y2 network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"v6y2\"}', '2025-11-14 14:41:28'),
(24, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:627582403 1 udp 2122129151 192.168.1.7 54970 typ host generation 0 ufrag zRLY network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(25, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:627582403 1 udp 2122129151 192.168.1.7 54973 typ host generation 0 ufrag zRLY network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(26, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:613673998 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 54971 typ host generation 0 ufrag zRLY network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(27, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:287489200 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 54972 typ host generation 0 ufrag zRLY network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(28, 2, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 7294186585928087705 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS a0bcd4cf-fac7-4938-8e76-a2e849581058\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:zRLY\\r\\na=ice-pwd:gFcPCn5RpynrU+Q0p0qRQGhe\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 08:84:3A:E0:0B:5C:13:4E:83:D6:FE:F2:82:8F:8F:88:60:E9:59:25:72:31:87:E5:78:15:BD:46:96:49:4A:F0\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 6e23b323-c4c8-49fb-82a3-64c66d9ff025\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2388674717 cname:e0fsyhGeliiT0EQe\\r\\na=ssrc:2388674717 msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 6e23b323-c4c8-49fb-82a3-64c66d9ff025\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:zRLY\\r\\na=ice-pwd:gFcPCn5RpynrU+Q0p0qRQGhe\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 08:84:3A:E0:0B:5C:13:4E:83:D6:FE:F2:82:8F:8F:88:60:E9:59:25:72:31:87:E5:78:15:BD:46:96:49:4A:F0\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 53408ba1-9fbc-4dc6-94aa-0abe65eb5ec7\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 32998030 1862073515\\r\\na=ssrc:32998030 cname:e0fsyhGeliiT0EQe\\r\\na=ssrc:32998030 msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 53408ba1-9fbc-4dc6-94aa-0abe65eb5ec7\\r\\na=ssrc:1862073515 cname:e0fsyhGeliiT0EQe\\r\\na=ssrc:1862073515 msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 53408ba1-9fbc-4dc6-94aa-0abe65eb5ec7\\r\\n\",\"type\":\"offer\"}', '2025-11-14 14:47:46'),
(29, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:613673998 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 54974 typ host generation 0 ufrag zRLY network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(30, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:287489200 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 54975 typ host generation 0 ufrag zRLY network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(31, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1537722203 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag zRLY network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:46'),
(32, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1515982486 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag zRLY network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(33, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1877827112 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag zRLY network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(34, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1537722203 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag zRLY network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(35, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1515982486 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag zRLY network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(36, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1877827112 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag zRLY network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(37, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1174057 1 udp 1685921535 58.84.62.152 24648 typ srflx raddr 192.168.1.7 rport 54970 generation 0 ufrag zRLY network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(38, 2, 7, NULL, 'ice', '{\"candidate\":\"candidate:1174057 1 udp 1685921535 58.84.62.152 20454 typ srflx raddr 192.168.1.7 rport 54973 generation 0 ufrag zRLY network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"zRLY\"}', '2025-11-14 14:47:47'),
(39, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:1570082266 1 udp 2122129151 192.168.1.7 52375 typ host generation 0 ufrag Jp8Q network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(40, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:3091318814 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 52379 typ host generation 0 ufrag Jp8Q network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(41, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:3091318814 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 52376 typ host generation 0 ufrag Jp8Q network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(42, 3, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 8376262442646096816 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS a0bcd4cf-fac7-4938-8e76-a2e849581058\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Jp8Q\\r\\na=ice-pwd:2iJSkccRuz9N4dkD/8MlwOVY\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 FC:33:35:74:EA:C5:89:4C:90:18:93:2B:25:68:E9:44:7A:E9:79:E1:5F:A5:D7:79:27:16:84:38:88:38:48:EB\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 6e23b323-c4c8-49fb-82a3-64c66d9ff025\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:3878917356 cname:EcQpTzDBmyH5/535\\r\\na=ssrc:3878917356 msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 6e23b323-c4c8-49fb-82a3-64c66d9ff025\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Jp8Q\\r\\na=ice-pwd:2iJSkccRuz9N4dkD/8MlwOVY\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 FC:33:35:74:EA:C5:89:4C:90:18:93:2B:25:68:E9:44:7A:E9:79:E1:5F:A5:D7:79:27:16:84:38:88:38:48:EB\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 53408ba1-9fbc-4dc6-94aa-0abe65eb5ec7\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 1079800004 1782659672\\r\\na=ssrc:1079800004 cname:EcQpTzDBmyH5/535\\r\\na=ssrc:1079800004 msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 53408ba1-9fbc-4dc6-94aa-0abe65eb5ec7\\r\\na=ssrc:1782659672 cname:EcQpTzDBmyH5/535\\r\\na=ssrc:1782659672 msid:a0bcd4cf-fac7-4938-8e76-a2e849581058 53408ba1-9fbc-4dc6-94aa-0abe65eb5ec7\\r\\n\",\"type\":\"offer\"}', '2025-11-14 14:48:31'),
(43, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:2494425029 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 52377 typ host generation 0 ufrag Jp8Q network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(44, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:1570082266 1 udp 2122129151 192.168.1.7 52378 typ host generation 0 ufrag Jp8Q network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(45, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:2494425029 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 52380 typ host generation 0 ufrag Jp8Q network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(46, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:493879155 1 udp 1685921535 58.84.62.152 9662 typ srflx raddr 192.168.1.7 rport 52375 generation 0 ufrag Jp8Q network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(47, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:493879155 1 udp 1685921535 58.84.62.152 22620 typ srflx raddr 192.168.1.7 rport 52378 generation 0 ufrag Jp8Q network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(48, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:2738837838 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Jp8Q network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(49, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:1189813386 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Jp8Q network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(50, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:1778856785 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Jp8Q network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(51, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:2738837838 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Jp8Q network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(52, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:1189813386 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Jp8Q network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(53, 3, 7, NULL, 'ice', '{\"candidate\":\"candidate:1778856785 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Jp8Q network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Jp8Q\"}', '2025-11-14 14:48:31'),
(54, 4, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 4200356733860162869 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 7a7796cd-9bc3-40bf-9576-0db695d4159a\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:oZd6\\r\\na=ice-pwd:yjEx/QGozbVihoemhJZ6XSYQ\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 AC:E1:49:C6:10:55:F3:87:5C:A6:70:E4:C8:E2:FA:19:9E:D8:4A:45:75:7E:F6:C4:19:24:E1:D9:08:F6:92:39\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:7a7796cd-9bc3-40bf-9576-0db695d4159a 60aadb1c-1b81-4bcc-a31e-a9c29b35dba7\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:1991723866 cname:HY70TmKcY34VPOC5\\r\\na=ssrc:1991723866 msid:7a7796cd-9bc3-40bf-9576-0db695d4159a 60aadb1c-1b81-4bcc-a31e-a9c29b35dba7\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:oZd6\\r\\na=ice-pwd:yjEx/QGozbVihoemhJZ6XSYQ\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 AC:E1:49:C6:10:55:F3:87:5C:A6:70:E4:C8:E2:FA:19:9E:D8:4A:45:75:7E:F6:C4:19:24:E1:D9:08:F6:92:39\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:7a7796cd-9bc3-40bf-9576-0db695d4159a 13fd6a05-483e-474d-b667-beffc552f83f\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 1493989565 1940441612\\r\\na=ssrc:1493989565 cname:HY70TmKcY34VPOC5\\r\\na=ssrc:1493989565 msid:7a7796cd-9bc3-40bf-9576-0db695d4159a 13fd6a05-483e-474d-b667-beffc552f83f\\r\\na=ssrc:1940441612 cname:HY70TmKcY34VPOC5\\r\\na=ssrc:1940441612 msid:7a7796cd-9bc3-40bf-9576-0db695d4159a 13fd6a05-483e-474d-b667-beffc552f83f\\r\\n\",\"type\":\"offer\"}', '2025-11-14 14:48:58'),
(55, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:2092097225 1 udp 2122129151 192.168.1.7 62029 typ host generation 0 ufrag oZd6 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(56, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:2573638413 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 62030 typ host generation 0 ufrag oZd6 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(57, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:2092097225 1 udp 2122129151 192.168.1.7 62032 typ host generation 0 ufrag oZd6 network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(58, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:3045766358 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 62031 typ host generation 0 ufrag oZd6 network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(59, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:2573638413 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 62033 typ host generation 0 ufrag oZd6 network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(60, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:3045766358 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 62034 typ host generation 0 ufrag oZd6 network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(61, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:1011395680 1 udp 1685921535 58.84.62.152 3500 typ srflx raddr 192.168.1.7 rport 62032 generation 0 ufrag oZd6 network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(62, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:1011395680 1 udp 1685921535 58.84.62.152 22692 typ srflx raddr 192.168.1.7 rport 62029 generation 0 ufrag oZd6 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(63, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:2182613597 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag oZd6 network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(64, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:1741442969 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag oZd6 network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(65, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:1260412994 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag oZd6 network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58');
INSERT INTO `signaling_messages` (`id`, `call_id`, `from_user_id`, `to_user_id`, `type`, `payload`, `created_at`) VALUES
(66, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:2182613597 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag oZd6 network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(67, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:1260412994 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag oZd6 network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(68, 4, 7, NULL, 'ice', '{\"candidate\":\"candidate:1741442969 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag oZd6 network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"oZd6\"}', '2025-11-14 14:48:58'),
(69, 5, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 3214379935196223226 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 09c23a62-ee07-4abb-b4e6-6894e55dfcbe\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Zcfv\\r\\na=ice-pwd:fkI+WgfQ4gJs+LPUCPkcbITN\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 1B:19:AF:5C:23:FE:D5:70:F3:78:47:27:A3:38:5C:86:A9:75:4E:AF:30:75:EB:C9:16:13:C3:19:EF:6F:45:6E\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:09c23a62-ee07-4abb-b4e6-6894e55dfcbe c5d6df86-dedc-418d-af9d-e44b52f99744\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:989651549 cname:cZQo5Cl6QsDa76Yh\\r\\na=ssrc:989651549 msid:09c23a62-ee07-4abb-b4e6-6894e55dfcbe c5d6df86-dedc-418d-af9d-e44b52f99744\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Zcfv\\r\\na=ice-pwd:fkI+WgfQ4gJs+LPUCPkcbITN\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 1B:19:AF:5C:23:FE:D5:70:F3:78:47:27:A3:38:5C:86:A9:75:4E:AF:30:75:EB:C9:16:13:C3:19:EF:6F:45:6E\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:09c23a62-ee07-4abb-b4e6-6894e55dfcbe 00281d25-ca4a-4fde-9344-e8a4e8fa4696\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 439366331 864634434\\r\\na=ssrc:439366331 cname:cZQo5Cl6QsDa76Yh\\r\\na=ssrc:439366331 msid:09c23a62-ee07-4abb-b4e6-6894e55dfcbe 00281d25-ca4a-4fde-9344-e8a4e8fa4696\\r\\na=ssrc:864634434 cname:cZQo5Cl6QsDa76Yh\\r\\na=ssrc:864634434 msid:09c23a62-ee07-4abb-b4e6-6894e55dfcbe 00281d25-ca4a-4fde-9344-e8a4e8fa4696\\r\\n\",\"type\":\"offer\"}', '2025-11-14 15:29:35'),
(70, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1399270999 1 udp 2122129151 192.168.1.7 61090 typ host generation 0 ufrag Zcfv network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(71, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1386014618 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 61091 typ host generation 0 ufrag Zcfv network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(72, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1731049252 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 61092 typ host generation 0 ufrag Zcfv network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(73, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1386014618 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 61094 typ host generation 0 ufrag Zcfv network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(74, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1399270999 1 udp 2122129151 192.168.1.7 61093 typ host generation 0 ufrag Zcfv network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(75, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1731049252 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 61095 typ host generation 0 ufrag Zcfv network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(76, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:766037199 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Zcfv network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(77, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:743638274 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Zcfv network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(78, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:434263484 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Zcfv network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(79, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:766037199 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Zcfv network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(80, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:743638274 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Zcfv network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(81, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:434263484 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Zcfv network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(82, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1981735357 1 udp 1685921535 58.84.62.152 17709 typ srflx raddr 192.168.1.7 rport 61090 generation 0 ufrag Zcfv network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(83, 5, 7, NULL, 'ice', '{\"candidate\":\"candidate:1981735357 1 udp 1685921535 58.84.62.152 29929 typ srflx raddr 192.168.1.7 rport 61093 generation 0 ufrag Zcfv network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Zcfv\"}', '2025-11-14 15:29:35'),
(84, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:2419340513 1 udp 2122129151 192.168.1.7 55260 typ host generation 0 ufrag BY5C network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(85, 6, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 3662140566585401802 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 458bdd8b-e044-4044-bf54-60779538b5c9\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:BY5C\\r\\na=ice-pwd:Jrmt7uOCWmz/utv3TBU0S+5J\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CA:4C:4E:AF:CC:AF:E8:03:08:FE:45:D2:F2:86:D6:5D:8B:5C:E8:04:21:17:30:29:09:92:8A:ED:63:71:48:E8\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:458bdd8b-e044-4044-bf54-60779538b5c9 c4601a90-3ae4-40c4-bf1c-c849d32ea991\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:3633578484 cname:KRSpA+faOSo6t47G\\r\\na=ssrc:3633578484 msid:458bdd8b-e044-4044-bf54-60779538b5c9 c4601a90-3ae4-40c4-bf1c-c849d32ea991\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:BY5C\\r\\na=ice-pwd:Jrmt7uOCWmz/utv3TBU0S+5J\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CA:4C:4E:AF:CC:AF:E8:03:08:FE:45:D2:F2:86:D6:5D:8B:5C:E8:04:21:17:30:29:09:92:8A:ED:63:71:48:E8\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:458bdd8b-e044-4044-bf54-60779538b5c9 2d015c82-34ee-47a8-8eb3-271c0616a3a4\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 2095863035 850984818\\r\\na=ssrc:2095863035 cname:KRSpA+faOSo6t47G\\r\\na=ssrc:2095863035 msid:458bdd8b-e044-4044-bf54-60779538b5c9 2d015c82-34ee-47a8-8eb3-271c0616a3a4\\r\\na=ssrc:850984818 cname:KRSpA+faOSo6t47G\\r\\na=ssrc:850984818 msid:458bdd8b-e044-4044-bf54-60779538b5c9 2d015c82-34ee-47a8-8eb3-271c0616a3a4\\r\\n\",\"type\":\"offer\"}', '2025-11-14 15:41:34'),
(86, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:1977643301 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 55261 typ host generation 0 ufrag BY5C network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(87, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:1977643301 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 55264 typ host generation 0 ufrag BY5C network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(88, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:1493982974 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 55262 typ host generation 0 ufrag BY5C network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(89, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:2419340513 1 udp 2122129151 192.168.1.7 55263 typ host generation 0 ufrag BY5C network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(90, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:1493982974 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 55265 typ host generation 0 ufrag BY5C network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(91, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:3503179336 1 udp 1685921535 58.84.62.152 29907 typ srflx raddr 192.168.1.7 rport 55260 generation 0 ufrag BY5C network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(92, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:3503179336 1 udp 1685921535 58.84.62.152 4784 typ srflx raddr 192.168.1.7 rport 55263 generation 0 ufrag BY5C network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(93, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:2336925105 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag BY5C network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(94, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:1855905909 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag BY5C network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(95, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:2812709482 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag BY5C network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(96, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:1855905909 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag BY5C network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(97, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:2336925105 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag BY5C network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(98, 6, 7, NULL, 'ice', '{\"candidate\":\"candidate:2812709482 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag BY5C network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"BY5C\"}', '2025-11-14 15:41:34'),
(99, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:311697648 1 udp 2122129151 192.168.1.7 50573 typ host generation 0 ufrag C9qz network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(100, 7, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 7650731357704301914 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 8754cf55-77f7-46d2-aa1b-772ccd12a24e\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:C9qz\\r\\na=ice-pwd:uASH08BJ13Ogyy2rkaOPG+OA\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CE:9E:C9:56:9B:AD:76:38:EF:F3:02:3F:A5:DE:46:D9:26:F7:2A:AB:CD:5A:49:91:59:6F:B4:F4:82:81:4F:FC\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:8754cf55-77f7-46d2-aa1b-772ccd12a24e 6427f0f5-6d23-4af3-9bec-03eddb174aed\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:3370729535 cname:XpXWAzlYL42DKv87\\r\\na=ssrc:3370729535 msid:8754cf55-77f7-46d2-aa1b-772ccd12a24e 6427f0f5-6d23-4af3-9bec-03eddb174aed\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:C9qz\\r\\na=ice-pwd:uASH08BJ13Ogyy2rkaOPG+OA\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CE:9E:C9:56:9B:AD:76:38:EF:F3:02:3F:A5:DE:46:D9:26:F7:2A:AB:CD:5A:49:91:59:6F:B4:F4:82:81:4F:FC\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:8754cf55-77f7-46d2-aa1b-772ccd12a24e 4b26d624-cf81-4f49-94ed-c6306e099c5d\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 3857222225 3137027518\\r\\na=ssrc:3857222225 cname:XpXWAzlYL42DKv87\\r\\na=ssrc:3857222225 msid:8754cf55-77f7-46d2-aa1b-772ccd12a24e 4b26d624-cf81-4f49-94ed-c6306e099c5d\\r\\na=ssrc:3137027518 cname:XpXWAzlYL42DKv87\\r\\na=ssrc:3137027518 msid:8754cf55-77f7-46d2-aa1b-772ccd12a24e 4b26d624-cf81-4f49-94ed-c6306e099c5d\\r\\n\",\"type\":\"offer\"}', '2025-11-14 15:41:56'),
(101, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:326101309 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 50574 typ host generation 0 ufrag C9qz network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(102, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:652130691 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 50575 typ host generation 0 ufrag C9qz network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(103, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:326101309 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 50577 typ host generation 0 ufrag C9qz network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(104, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:311697648 1 udp 2122129151 192.168.1.7 50576 typ host generation 0 ufrag C9qz network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(105, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:652130691 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 50578 typ host generation 0 ufrag C9qz network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(106, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:1817952872 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag C9qz network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(107, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:938340122 1 udp 1685921535 58.84.62.152 18232 typ srflx raddr 192.168.1.7 rport 50576 generation 0 ufrag C9qz network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(108, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:1839205285 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag C9qz network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(109, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:1477532443 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag C9qz network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(110, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:938340122 1 udp 1685921535 58.84.62.152 15057 typ srflx raddr 192.168.1.7 rport 50573 generation 0 ufrag C9qz network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(111, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:1817952872 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag C9qz network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(112, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:1839205285 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag C9qz network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(113, 7, 7, NULL, 'ice', '{\"candidate\":\"candidate:1477532443 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag C9qz network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"C9qz\"}', '2025-11-14 15:41:56'),
(114, 8, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 6032323574308649611 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 92e25574-2e00-4711-b8b8-c7175be5ea0a\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:3LWf\\r\\na=ice-pwd:aDI54TNyZVTxtIicYosLz4vO\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 FC:A2:51:2D:10:8E:EF:49:29:48:F0:34:EF:2C:6C:A2:32:28:85:DF:91:D7:4B:57:80:96:5B:17:C5:2E:D0:2B\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:92e25574-2e00-4711-b8b8-c7175be5ea0a 94146817-a373-433e-85e8-e079b2b05c43\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2950624344 cname:Cj/TGChomfYpMn9y\\r\\na=ssrc:2950624344 msid:92e25574-2e00-4711-b8b8-c7175be5ea0a 94146817-a373-433e-85e8-e079b2b05c43\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:3LWf\\r\\na=ice-pwd:aDI54TNyZVTxtIicYosLz4vO\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 FC:A2:51:2D:10:8E:EF:49:29:48:F0:34:EF:2C:6C:A2:32:28:85:DF:91:D7:4B:57:80:96:5B:17:C5:2E:D0:2B\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:92e25574-2e00-4711-b8b8-c7175be5ea0a 7b508092-5f8d-4b5e-b1e8-51721442a62a\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 1892073328 3809973954\\r\\na=ssrc:1892073328 cname:Cj/TGChomfYpMn9y\\r\\na=ssrc:1892073328 msid:92e25574-2e00-4711-b8b8-c7175be5ea0a 7b508092-5f8d-4b5e-b1e8-51721442a62a\\r\\na=ssrc:3809973954 cname:Cj/TGChomfYpMn9y\\r\\na=ssrc:3809973954 msid:92e25574-2e00-4711-b8b8-c7175be5ea0a 7b508092-5f8d-4b5e-b1e8-51721442a62a\\r\\n\",\"type\":\"offer\"}', '2025-11-14 15:51:24'),
(115, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:500484134 1 udp 2122129151 192.168.1.7 63230 typ host generation 0 ufrag 3LWf network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(116, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:472842731 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 63231 typ host generation 0 ufrag 3LWf network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(117, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:500484134 1 udp 2122129151 192.168.1.7 63233 typ host generation 0 ufrag 3LWf network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(118, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:472842731 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 63234 typ host generation 0 ufrag 3LWf network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(119, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:698241365 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 63232 typ host generation 0 ufrag 3LWf network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(120, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:698241365 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 63235 typ host generation 0 ufrag 3LWf network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(121, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:1662724798 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag 3LWf network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(122, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:1464972237 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag 3LWf network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(123, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:1658905459 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag 3LWf network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(124, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:1662724798 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag 3LWf network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(125, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:1658905459 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag 3LWf network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(126, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:1464972237 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag 3LWf network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(127, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:950863820 1 udp 1685921535 58.84.62.152 16757 typ srflx raddr 192.168.1.7 rport 63230 generation 0 ufrag 3LWf network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(128, 8, 7, NULL, 'ice', '{\"candidate\":\"candidate:950863820 1 udp 1685921535 58.84.62.152 27482 typ srflx raddr 192.168.1.7 rport 63233 generation 0 ufrag 3LWf network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"3LWf\"}', '2025-11-14 15:51:24'),
(129, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:2366225630 1 udp 2122129151 192.168.1.7 52461 typ host generation 0 ufrag 6QrX network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47');
INSERT INTO `signaling_messages` (`id`, `call_id`, `from_user_id`, `to_user_id`, `type`, `payload`, `created_at`) VALUES
(130, 9, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 9206136711772917197 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS 4d1bf8ac-c73e-4723-8d5b-34cd6f7172dd\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:6QrX\\r\\na=ice-pwd:31bOFdyrFLyzU8rcdlWBh91s\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 88:8E:D7:72:C5:0F:EA:B7:73:22:EE:EB:B9:13:DF:36:2D:63:19:1B:9B:83:EF:B5:DC:2E:39:A8:41:6F:68:FA\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:4d1bf8ac-c73e-4723-8d5b-34cd6f7172dd b49d1f35-2747-4603-a8b6-93c22108ac82\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2043305101 cname:6neK32skVAIIz/iN\\r\\na=ssrc:2043305101 msid:4d1bf8ac-c73e-4723-8d5b-34cd6f7172dd b49d1f35-2747-4603-a8b6-93c22108ac82\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:6QrX\\r\\na=ice-pwd:31bOFdyrFLyzU8rcdlWBh91s\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 88:8E:D7:72:C5:0F:EA:B7:73:22:EE:EB:B9:13:DF:36:2D:63:19:1B:9B:83:EF:B5:DC:2E:39:A8:41:6F:68:FA\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:4d1bf8ac-c73e-4723-8d5b-34cd6f7172dd 69e96dde-217b-417e-8cb8-81250ead9cf1\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 1500874977 195102834\\r\\na=ssrc:1500874977 cname:6neK32skVAIIz/iN\\r\\na=ssrc:1500874977 msid:4d1bf8ac-c73e-4723-8d5b-34cd6f7172dd 69e96dde-217b-417e-8cb8-81250ead9cf1\\r\\na=ssrc:195102834 cname:6neK32skVAIIz/iN\\r\\na=ssrc:195102834 msid:4d1bf8ac-c73e-4723-8d5b-34cd6f7172dd 69e96dde-217b-417e-8cb8-81250ead9cf1\\r\\n\",\"type\":\"offer\"}', '2025-11-14 16:00:47'),
(131, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:1759377690 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 52465 typ host generation 0 ufrag 6QrX network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(132, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:1759377690 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 52462 typ host generation 0 ufrag 6QrX network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(133, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:1144116929 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 52463 typ host generation 0 ufrag 6QrX network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(134, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:2366225630 1 udp 2122129151 192.168.1.7 52464 typ host generation 0 ufrag 6QrX network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(135, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:1144116929 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 52466 typ host generation 0 ufrag 6QrX network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(136, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:2524392846 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag 6QrX network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(137, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:1940088906 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag 6QrX network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(138, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:3130720853 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag 6QrX network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(139, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:2524392846 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag 6QrX network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(140, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:1940088906 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag 6QrX network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(141, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:3130720853 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag 6QrX network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(142, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:3455307383 1 udp 1685921535 58.84.62.152 7959 typ srflx raddr 192.168.1.7 rport 52464 generation 0 ufrag 6QrX network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(143, 9, 7, NULL, 'ice', '{\"candidate\":\"candidate:3455307383 1 udp 1685921535 58.84.62.152 23627 typ srflx raddr 192.168.1.7 rport 52461 generation 0 ufrag 6QrX network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"6QrX\"}', '2025-11-14 16:00:47'),
(144, 10, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 2522062115029203681 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS ff39c36b-3963-4d9c-8433-78702b597523\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:wW6D\\r\\na=ice-pwd:Pw2LfjBpzWnivx6YxejbcEmg\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 DD:0C:6A:1D:44:F0:76:9D:6D:EF:89:2A:26:63:81:52:8E:C1:33:CE:BF:1A:52:7F:5C:06:2C:E3:0F:D3:34:D5\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:ff39c36b-3963-4d9c-8433-78702b597523 36af2dfa-f094-4f6f-8653-2ef87a13094d\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2046699253 cname:1djLLbVLTKt2JMY/\\r\\na=ssrc:2046699253 msid:ff39c36b-3963-4d9c-8433-78702b597523 36af2dfa-f094-4f6f-8653-2ef87a13094d\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:wW6D\\r\\na=ice-pwd:Pw2LfjBpzWnivx6YxejbcEmg\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 DD:0C:6A:1D:44:F0:76:9D:6D:EF:89:2A:26:63:81:52:8E:C1:33:CE:BF:1A:52:7F:5C:06:2C:E3:0F:D3:34:D5\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 4112883804 3181392197\\r\\na=ssrc:4112883804 cname:1djLLbVLTKt2JMY/\\r\\na=ssrc:4112883804 msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\na=ssrc:3181392197 cname:1djLLbVLTKt2JMY/\\r\\na=ssrc:3181392197 msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\n\",\"type\":\"offer\"}', '2025-11-14 16:11:50'),
(145, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3129927360 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 60593 typ host generation 0 ufrag wW6D network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(146, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:2587241857 1 udp 2122129151 192.168.1.7 60592 typ host generation 0 ufrag wW6D network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(147, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3129927360 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 60596 typ host generation 0 ufrag wW6D network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(148, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3195445992 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 60594 typ host generation 0 ufrag wW6D network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(149, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:2587241857 1 udp 2122129151 192.168.1.7 60595 typ host generation 0 ufrag wW6D network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(150, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3195445992 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 60597 typ host generation 0 ufrag wW6D network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(151, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3236464613 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag wW6D network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(152, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3289433037 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag wW6D network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(153, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3836217484 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag wW6D network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(154, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3836217484 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag wW6D network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(155, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3236464613 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag wW6D network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(156, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:3289433037 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag wW6D network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(157, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:2351654707 1 udp 1685921535 58.84.62.152 1728 typ srflx raddr 192.168.1.7 rport 60592 generation 0 ufrag wW6D network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(158, 10, 7, NULL, 'ice', '{\"candidate\":\"candidate:2351654707 1 udp 1685921535 58.84.62.152 3698 typ srflx raddr 192.168.1.7 rport 60595 generation 0 ufrag wW6D network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"wW6D\"}', '2025-11-14 16:11:50'),
(159, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:3160771457 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 63273 typ host generation 0 ufrag Qcrr network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(160, 11, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 2937436090605196025 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS ff39c36b-3963-4d9c-8433-78702b597523\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Qcrr\\r\\na=ice-pwd:3IHOZ3dWxUL/Z8q83GNf9CbN\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 BA:A6:B1:FA:B3:55:2F:73:0D:84:79:22:4F:52:E3:FF:5D:8A:52:3D:B7:EA:CC:3D:A1:43:5E:17:FA:57:1D:7C\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:ff39c36b-3963-4d9c-8433-78702b597523 36af2dfa-f094-4f6f-8653-2ef87a13094d\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:3301564712 cname:WkRnkRWaSy6TDBHP\\r\\na=ssrc:3301564712 msid:ff39c36b-3963-4d9c-8433-78702b597523 36af2dfa-f094-4f6f-8653-2ef87a13094d\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:Qcrr\\r\\na=ice-pwd:3IHOZ3dWxUL/Z8q83GNf9CbN\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 BA:A6:B1:FA:B3:55:2F:73:0D:84:79:22:4F:52:E3:FF:5D:8A:52:3D:B7:EA:CC:3D:A1:43:5E:17:FA:57:1D:7C\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 2035528072 1803616346\\r\\na=ssrc:2035528072 cname:WkRnkRWaSy6TDBHP\\r\\na=ssrc:2035528072 msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\na=ssrc:1803616346 cname:WkRnkRWaSy6TDBHP\\r\\na=ssrc:1803616346 msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\n\",\"type\":\"offer\"}', '2025-11-14 16:12:06'),
(161, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:2424939610 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 63274 typ host generation 0 ufrag Qcrr network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(162, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:3160771457 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 63276 typ host generation 0 ufrag Qcrr network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(163, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:1504824901 1 udp 2122129151 192.168.1.7 63272 typ host generation 0 ufrag Qcrr network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(164, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:1504824901 1 udp 2122129151 192.168.1.7 63275 typ host generation 0 ufrag Qcrr network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(165, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:2424939610 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 63277 typ host generation 0 ufrag Qcrr network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(166, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:424393964 1 udp 1685921535 58.84.62.152 15091 typ srflx raddr 192.168.1.7 rport 63272 generation 0 ufrag Qcrr network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(167, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:424393964 1 udp 1685921535 58.84.62.152 24732 typ srflx raddr 192.168.1.7 rport 63275 generation 0 ufrag Qcrr network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(168, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:1120886549 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Qcrr network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(169, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:2803571409 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Qcrr network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(170, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:1847816398 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Qcrr network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(171, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:2803571409 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag Qcrr network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(172, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:1120886549 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag Qcrr network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(173, 11, 7, NULL, 'ice', '{\"candidate\":\"candidate:1847816398 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag Qcrr network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"Qcrr\"}', '2025-11-14 16:12:06'),
(174, 12, 7, NULL, 'offer', '{\"sdp\":\"v=0\\r\\no=- 8230406999459266434 2 IN IP4 127.0.0.1\\r\\ns=-\\r\\nt=0 0\\r\\na=group:BUNDLE 0 1\\r\\na=extmap-allow-mixed\\r\\na=msid-semantic: WMS ff39c36b-3963-4d9c-8433-78702b597523\\r\\nm=audio 9 UDP/TLS/RTP/SAVPF 111 63 9 0 8 13 110 126\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:qRhT\\r\\na=ice-pwd:ofZuonmAeG3C1ZUrLRR/t6u+\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CC:DA:3D:93:B8:71:95:7A:25:B8:D0:9F:42:11:E6:92:76:17:C1:97:E2:4C:79:06:8F:41:F3:43:F4:79:0F:15\\r\\na=setup:actpass\\r\\na=mid:0\\r\\na=extmap:1 urn:ietf:params:rtp-hdrext:ssrc-audio-level\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=sendrecv\\r\\na=msid:ff39c36b-3963-4d9c-8433-78702b597523 36af2dfa-f094-4f6f-8653-2ef87a13094d\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:111 opus/48000/2\\r\\na=rtcp-fb:111 transport-cc\\r\\na=fmtp:111 minptime=10;useinbandfec=1\\r\\na=rtpmap:63 red/48000/2\\r\\na=fmtp:63 111/111\\r\\na=rtpmap:9 G722/8000\\r\\na=rtpmap:0 PCMU/8000\\r\\na=rtpmap:8 PCMA/8000\\r\\na=rtpmap:13 CN/8000\\r\\na=rtpmap:110 telephone-event/48000\\r\\na=rtpmap:126 telephone-event/8000\\r\\na=ssrc:2025178812 cname:HvixegzNvJG+LdVG\\r\\na=ssrc:2025178812 msid:ff39c36b-3963-4d9c-8433-78702b597523 36af2dfa-f094-4f6f-8653-2ef87a13094d\\r\\nm=video 9 UDP/TLS/RTP/SAVPF 96 97 103 104 107 108 109 114 115 116 117 118 39 40 45 46 98 99 100 101 119 120 49 50 123 124 125\\r\\nc=IN IP4 0.0.0.0\\r\\na=rtcp:9 IN IP4 0.0.0.0\\r\\na=ice-ufrag:qRhT\\r\\na=ice-pwd:ofZuonmAeG3C1ZUrLRR/t6u+\\r\\na=ice-options:trickle\\r\\na=fingerprint:sha-256 CC:DA:3D:93:B8:71:95:7A:25:B8:D0:9F:42:11:E6:92:76:17:C1:97:E2:4C:79:06:8F:41:F3:43:F4:79:0F:15\\r\\na=setup:actpass\\r\\na=mid:1\\r\\na=extmap:14 urn:ietf:params:rtp-hdrext:toffset\\r\\na=extmap:2 http://www.webrtc.org/experiments/rtp-hdrext/abs-send-time\\r\\na=extmap:13 urn:3gpp:video-orientation\\r\\na=extmap:3 http://www.ietf.org/id/draft-holmer-rmcat-transport-wide-cc-extensions-01\\r\\na=extmap:5 http://www.webrtc.org/experiments/rtp-hdrext/playout-delay\\r\\na=extmap:6 http://www.webrtc.org/experiments/rtp-hdrext/video-content-type\\r\\na=extmap:7 http://www.webrtc.org/experiments/rtp-hdrext/video-timing\\r\\na=extmap:8 http://www.webrtc.org/experiments/rtp-hdrext/color-space\\r\\na=extmap:4 urn:ietf:params:rtp-hdrext:sdes:mid\\r\\na=extmap:10 urn:ietf:params:rtp-hdrext:sdes:rtp-stream-id\\r\\na=extmap:11 urn:ietf:params:rtp-hdrext:sdes:repaired-rtp-stream-id\\r\\na=sendrecv\\r\\na=msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\na=rtcp-mux\\r\\na=rtcp-rsize\\r\\na=rtpmap:96 VP8/90000\\r\\na=rtcp-fb:96 goog-remb\\r\\na=rtcp-fb:96 transport-cc\\r\\na=rtcp-fb:96 ccm fir\\r\\na=rtcp-fb:96 nack\\r\\na=rtcp-fb:96 nack pli\\r\\na=rtpmap:97 rtx/90000\\r\\na=fmtp:97 apt=96\\r\\na=rtpmap:103 H264/90000\\r\\na=rtcp-fb:103 goog-remb\\r\\na=rtcp-fb:103 transport-cc\\r\\na=rtcp-fb:103 ccm fir\\r\\na=rtcp-fb:103 nack\\r\\na=rtcp-fb:103 nack pli\\r\\na=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\\r\\na=rtpmap:104 rtx/90000\\r\\na=fmtp:104 apt=103\\r\\na=rtpmap:107 H264/90000\\r\\na=rtcp-fb:107 goog-remb\\r\\na=rtcp-fb:107 transport-cc\\r\\na=rtcp-fb:107 ccm fir\\r\\na=rtcp-fb:107 nack\\r\\na=rtcp-fb:107 nack pli\\r\\na=fmtp:107 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42001f\\r\\na=rtpmap:108 rtx/90000\\r\\na=fmtp:108 apt=107\\r\\na=rtpmap:109 H264/90000\\r\\na=rtcp-fb:109 goog-remb\\r\\na=rtcp-fb:109 transport-cc\\r\\na=rtcp-fb:109 ccm fir\\r\\na=rtcp-fb:109 nack\\r\\na=rtcp-fb:109 nack pli\\r\\na=fmtp:109 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\\r\\na=rtpmap:114 rtx/90000\\r\\na=fmtp:114 apt=109\\r\\na=rtpmap:115 H264/90000\\r\\na=rtcp-fb:115 goog-remb\\r\\na=rtcp-fb:115 transport-cc\\r\\na=rtcp-fb:115 ccm fir\\r\\na=rtcp-fb:115 nack\\r\\na=rtcp-fb:115 nack pli\\r\\na=fmtp:115 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=42e01f\\r\\na=rtpmap:116 rtx/90000\\r\\na=fmtp:116 apt=115\\r\\na=rtpmap:117 H264/90000\\r\\na=rtcp-fb:117 goog-remb\\r\\na=rtcp-fb:117 transport-cc\\r\\na=rtcp-fb:117 ccm fir\\r\\na=rtcp-fb:117 nack\\r\\na=rtcp-fb:117 nack pli\\r\\na=fmtp:117 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=4d001f\\r\\na=rtpmap:118 rtx/90000\\r\\na=fmtp:118 apt=117\\r\\na=rtpmap:39 H264/90000\\r\\na=rtcp-fb:39 goog-remb\\r\\na=rtcp-fb:39 transport-cc\\r\\na=rtcp-fb:39 ccm fir\\r\\na=rtcp-fb:39 nack\\r\\na=rtcp-fb:39 nack pli\\r\\na=fmtp:39 level-asymmetry-allowed=1;packetization-mode=0;profile-level-id=4d001f\\r\\na=rtpmap:40 rtx/90000\\r\\na=fmtp:40 apt=39\\r\\na=rtpmap:45 AV1/90000\\r\\na=rtcp-fb:45 goog-remb\\r\\na=rtcp-fb:45 transport-cc\\r\\na=rtcp-fb:45 ccm fir\\r\\na=rtcp-fb:45 nack\\r\\na=rtcp-fb:45 nack pli\\r\\na=fmtp:45 level-idx=5;profile=0;tier=0\\r\\na=rtpmap:46 rtx/90000\\r\\na=fmtp:46 apt=45\\r\\na=rtpmap:98 VP9/90000\\r\\na=rtcp-fb:98 goog-remb\\r\\na=rtcp-fb:98 transport-cc\\r\\na=rtcp-fb:98 ccm fir\\r\\na=rtcp-fb:98 nack\\r\\na=rtcp-fb:98 nack pli\\r\\na=fmtp:98 profile-id=0\\r\\na=rtpmap:99 rtx/90000\\r\\na=fmtp:99 apt=98\\r\\na=rtpmap:100 VP9/90000\\r\\na=rtcp-fb:100 goog-remb\\r\\na=rtcp-fb:100 transport-cc\\r\\na=rtcp-fb:100 ccm fir\\r\\na=rtcp-fb:100 nack\\r\\na=rtcp-fb:100 nack pli\\r\\na=fmtp:100 profile-id=2\\r\\na=rtpmap:101 rtx/90000\\r\\na=fmtp:101 apt=100\\r\\na=rtpmap:119 H264/90000\\r\\na=rtcp-fb:119 goog-remb\\r\\na=rtcp-fb:119 transport-cc\\r\\na=rtcp-fb:119 ccm fir\\r\\na=rtcp-fb:119 nack\\r\\na=rtcp-fb:119 nack pli\\r\\na=fmtp:119 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=64001f\\r\\na=rtpmap:120 rtx/90000\\r\\na=fmtp:120 apt=119\\r\\na=rtpmap:49 H265/90000\\r\\na=rtcp-fb:49 goog-remb\\r\\na=rtcp-fb:49 transport-cc\\r\\na=rtcp-fb:49 ccm fir\\r\\na=rtcp-fb:49 nack\\r\\na=rtcp-fb:49 nack pli\\r\\na=fmtp:49 level-id=93;profile-id=1;tier-flag=0;tx-mode=SRST\\r\\na=rtpmap:50 rtx/90000\\r\\na=fmtp:50 apt=49\\r\\na=rtpmap:123 red/90000\\r\\na=rtpmap:124 rtx/90000\\r\\na=fmtp:124 apt=123\\r\\na=rtpmap:125 ulpfec/90000\\r\\na=ssrc-group:FID 1372306192 2632674070\\r\\na=ssrc:1372306192 cname:HvixegzNvJG+LdVG\\r\\na=ssrc:1372306192 msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\na=ssrc:2632674070 cname:HvixegzNvJG+LdVG\\r\\na=ssrc:2632674070 msid:ff39c36b-3963-4d9c-8433-78702b597523 d2f78b5c-74d5-47a1-a4d0-af21793518ec\\r\\n\",\"type\":\"offer\"}', '2025-11-14 16:12:31'),
(175, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:59504702 1 udp 2122129151 192.168.1.7 56916 typ host generation 0 ufrag qRhT network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(176, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:59504702 1 udp 2122129151 192.168.1.7 56919 typ host generation 0 ufrag qRhT network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(177, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:40907251 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 56920 typ host generation 0 ufrag qRhT network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(178, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:935420237 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 56918 typ host generation 0 ufrag qRhT network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(179, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:40907251 1 udp 2122262783 2402:e280:3e7c:13b:4013:2434:2882:a3c6 56917 typ host generation 0 ufrag qRhT network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(180, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:935420237 1 udp 2122197247 2402:e280:3e7c:13b:e9d6:108e:944a:41db 56921 typ host generation 0 ufrag qRhT network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(181, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:653408212 1 udp 1685921535 58.84.62.152 23060 typ srflx raddr 192.168.1.7 rport 56916 generation 0 ufrag qRhT network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(182, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:653408212 1 udp 1685921535 58.84.62.152 4255 typ srflx raddr 192.168.1.7 rport 56919 generation 0 ufrag qRhT network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(183, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:2101615270 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag qRhT network-id 1 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(184, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:1225688021 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag qRhT network-id 3 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(185, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:2092946283 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag qRhT network-id 2 network-cost 10\",\"sdpMid\":\"0\",\"sdpMLineIndex\":0,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(186, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:2101615270 1 tcp 1518149375 192.168.1.7 9 typ host tcptype active generation 0 ufrag qRhT network-id 1 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(187, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:1225688021 1 tcp 1518217471 2402:e280:3e7c:13b:e9d6:108e:944a:41db 9 typ host tcptype active generation 0 ufrag qRhT network-id 3 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31'),
(188, 12, 7, NULL, 'ice', '{\"candidate\":\"candidate:2092946283 1 tcp 1518283007 2402:e280:3e7c:13b:4013:2434:2882:a3c6 9 typ host tcptype active generation 0 ufrag qRhT network-id 2 network-cost 10\",\"sdpMid\":\"1\",\"sdpMLineIndex\":1,\"usernameFragment\":\"qRhT\"}', '2025-11-14 16:12:31');

-- --------------------------------------------------------

--
-- Table structure for table `sma_commentmeta`
--

DROP TABLE IF EXISTS `sma_commentmeta`;
CREATE TABLE IF NOT EXISTS `sma_commentmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `comment_id` (`comment_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_comments`
--

DROP TABLE IF EXISTS `sma_comments`;
CREATE TABLE IF NOT EXISTS `sma_comments` (
  `comment_ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comment_post_ID` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `comment_author` tinytext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_author_email` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_url` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_author_IP` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `comment_content` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `comment_karma` int(11) NOT NULL DEFAULT '0',
  `comment_approved` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '1',
  `comment_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'comment',
  `comment_parent` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`comment_ID`),
  KEY `comment_post_ID` (`comment_post_ID`),
  KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
  KEY `comment_date_gmt` (`comment_date_gmt`),
  KEY `comment_parent` (`comment_parent`),
  KEY `comment_author_email` (`comment_author_email`(10))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_links`
--

DROP TABLE IF EXISTS `sma_links`;
CREATE TABLE IF NOT EXISTS `sma_links` (
  `link_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `link_url` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_image` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_target` varchar(25) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_description` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_visible` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'Y',
  `link_owner` bigint(20) UNSIGNED NOT NULL DEFAULT '1',
  `link_rating` int(11) NOT NULL DEFAULT '0',
  `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `link_rel` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `link_notes` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `link_rss` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`link_id`),
  KEY `link_visible` (`link_visible`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_options`
--

DROP TABLE IF EXISTS `sma_options`;
CREATE TABLE IF NOT EXISTS `sma_options` (
  `option_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `option_name` varchar(191) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `option_value` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `autoload` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`option_id`),
  UNIQUE KEY `option_name` (`option_name`),
  KEY `autoload` (`autoload`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_postmeta`
--

DROP TABLE IF EXISTS `sma_postmeta`;
CREATE TABLE IF NOT EXISTS `sma_postmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `post_id` (`post_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_posts`
--

DROP TABLE IF EXISTS `sma_posts`;
CREATE TABLE IF NOT EXISTS `sma_posts` (
  `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_author` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_title` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_excerpt` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'publish',
  `comment_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `ping_status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `post_password` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `post_name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `to_ping` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `pinged` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `post_content_filtered` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `post_parent` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `guid` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `menu_order` int(11) NOT NULL DEFAULT '0',
  `post_type` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'post',
  `post_mime_type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `comment_count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `post_name` (`post_name`(191)),
  KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
  KEY `post_parent` (`post_parent`),
  KEY `post_author` (`post_author`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_termmeta`
--

DROP TABLE IF EXISTS `sma_termmeta`;
CREATE TABLE IF NOT EXISTS `sma_termmeta` (
  `meta_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `meta_key` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `meta_value` longtext COLLATE utf8mb4_unicode_520_ci,
  PRIMARY KEY (`meta_id`),
  KEY `term_id` (`term_id`),
  KEY `meta_key` (`meta_key`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_terms`
--

DROP TABLE IF EXISTS `sma_terms`;
CREATE TABLE IF NOT EXISTS `sma_terms` (
  `term_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `slug` varchar(200) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `term_group` bigint(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_id`),
  KEY `slug` (`slug`(191)),
  KEY `name` (`name`(191))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_term_relationships`
--

DROP TABLE IF EXISTS `sma_term_relationships`;
CREATE TABLE IF NOT EXISTS `sma_term_relationships` (
  `object_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `term_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_id`,`term_taxonomy_id`),
  KEY `term_taxonomy_id` (`term_taxonomy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sma_term_taxonomy`
--

DROP TABLE IF EXISTS `sma_term_taxonomy`;
CREATE TABLE IF NOT EXISTS `sma_term_taxonomy` (
  `term_taxonomy_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `term_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `taxonomy` varchar(32) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT '',
  `description` longtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `parent` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `count` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`term_taxonomy_id`),
  UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
  KEY `taxonomy` (`taxonomy`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(190) NOT NULL,
  `description` text,
  `assigned_to` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','in_progress','completed','blocked') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `estimate_hours` decimal(6,2) DEFAULT NULL,
  `actual_hours` decimal(6,2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tasks_creator` (`created_by`),
  KEY `idx_tasks_project` (`project_id`),
  KEY `idx_tasks_assigned` (`assigned_to`),
  KEY `idx_tasks_status` (`status`),
  KEY `idx_tasks_due` (`due_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `title`, `description`, `assigned_to`, `created_by`, `status`, `priority`, `start_date`, `due_date`, `completed_at`, `estimate_hours`, `actual_hours`, `created_at`, `updated_at`) VALUES
(1, 1, 'Jobworks', '', 5, 5, 'in_progress', 'medium', NULL, NULL, NULL, NULL, NULL, '2025-11-13 17:41:00', '2025-11-13 17:41:00'),
(2, 2, 'sfsfsdf', '', 5, 7, 'pending', 'medium', NULL, NULL, NULL, NULL, NULL, '2025-11-14 12:24:14', '2025-11-14 12:24:14');

-- --------------------------------------------------------

--
-- Table structure for table `task_activity`
--

DROP TABLE IF EXISTS `task_activity`;
CREATE TABLE IF NOT EXISTS `task_activity` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` enum('created','updated','status_changed','assigned','commented','attachment_added') NOT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tact_user` (`user_id`),
  KEY `idx_tact_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

DROP TABLE IF EXISTS `task_attachments`;
CREATE TABLE IF NOT EXISTS `task_attachments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `file_name` varchar(190) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `size_bytes` bigint(20) DEFAULT NULL,
  `uploaded_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ta_user` (`uploaded_by`),
  KEY `idx_ta_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `task_comments`
--

DROP TABLE IF EXISTS `task_comments`;
CREATE TABLE IF NOT EXISTS `task_comments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_tc_user` (`user_id`),
  KEY `idx_tc_task` (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

DROP TABLE IF EXISTS `timesheets`;
CREATE TABLE IF NOT EXISTS `timesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `total_hours` decimal(5,2) DEFAULT '0.00',
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_week` (`user_id`,`week_start_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `timesheets`
--

INSERT INTO `timesheets` (`id`, `user_id`, `week_start_date`, `week_end_date`, `total_hours`, `status`, `submitted_at`, `approved_by`, `approved_at`, `comments`, `created_at`, `updated_at`) VALUES
(1, 6, '2025-11-10', '2025-11-16', '0.00', 'submitted', '2025-11-13 12:27:13', NULL, NULL, NULL, '2025-11-13 12:27:08', '2025-11-13 17:57:13'),
(2, 8, '2025-11-10', '2025-11-16', '0.00', 'submitted', '2025-11-14 11:15:36', NULL, NULL, NULL, '2025-11-14 11:15:33', '2025-11-14 11:15:36'),
(3, 7, '2025-11-10', '2025-11-16', '0.00', 'submitted', '2025-11-14 11:38:06', NULL, NULL, NULL, '2025-11-14 11:38:04', '2025-11-14 11:38:06');

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_entries`
--

DROP TABLE IF EXISTS `timesheet_entries`;
CREATE TABLE IF NOT EXISTS `timesheet_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timesheet_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `work_date` date NOT NULL,
  `hours` decimal(5,2) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `timesheet_id` (`timesheet_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `timesheet_entries`
--

INSERT INTO `timesheet_entries` (`id`, `timesheet_id`, `task_id`, `project_id`, `work_date`, `hours`, `description`, `created_at`) VALUES
(1, 1, 1, 1, '2025-11-13', '34.00', '', '2025-11-13 12:27:08'),
(2, 2, 1, 1, '2025-11-14', '9.00', '', '2025-11-14 11:15:33'),
(3, 3, 1, 1, '2025-11-14', '324.00', '324', '2025-11-14 11:38:04'),
(4, 3, 1, 2, '2025-11-14', '23.00', '', '2025-11-14 14:40:27');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `password_hash` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_at` datetime DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `role`, `password_hash`, `is_verified`, `last_login_at`, `status`, `role_id`, `name`, `phone`, `avatar`, `created_at`, `updated_at`) VALUES
(5, 'darade@gmail.com', 'user', '$2y$10$X3mX0hrYCyltJAL2hjq8J.1ljES5tgG5OYaSVjZdJbkhGQ6I8wgrS', 0, NULL, 'active', 1, 'Darade', NULL, NULL, '2025-11-13 17:31:01', '2025-11-14 11:19:14'),
(6, 'mangeshdarade@gmail.com', 'user', '$2y$10$2pQ36SGbW5YcTFEB12n4JOb4Zy1vTOLh.GyAS5crZbkU5WhNClUYK', 0, NULL, 'active', 1, 'Darde', NULL, NULL, '2025-11-13 12:22:30', '2025-11-13 17:52:30'),
(7, 'md@gmail.com', 'user', '$2y$10$hdAPeB/DQZHUFfC3ERsuXOW.9PrQ8ti03YJBu4LtsTK/zjrzjZd6W', 0, '2025-11-14 11:23:15', 'active', 1, 'mangesh', NULL, NULL, '2025-11-14 03:19:06', '2025-11-14 11:23:15'),
(8, 'employe@gmail.com', 'user', '$2y$10$cdjOwMWJ7vCZKcDcjSBT.uvBy/QzPZuFAw2Y6BkoHt./8Mj/WhaaO', 1, NULL, 'active', 4, 'employee', NULL, NULL, '2025-11-14 03:26:56', '2025-11-14 11:14:01');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `fk_actlog_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `daily_work_logs`
--
ALTER TABLE `daily_work_logs`
  ADD CONSTRAINT `fk_dwl_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dwl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_emp_reporting` FOREIGN KEY (`reporting_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leave_approvals`
--
ALTER TABLE `leave_approvals`
  ADD CONSTRAINT `fk_la_approver` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_la_leave` FOREIGN KEY (`leave_id`) REFERENCES `leave_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `fk_lb_type` FOREIGN KEY (`type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lb_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_lr_approver` FOREIGN KEY (`current_approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lr_type` FOREIGN KEY (`type_id`) REFERENCES `leave_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_lr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `project_members`
--
ALTER TABLE `project_members`
  ADD CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `project_status_history`
--
ALTER TABLE `project_status_history`
  ADD CONSTRAINT `fk_psh_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_psh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `rm_daily_tasks`
--
ALTER TABLE `rm_daily_tasks`
  ADD CONSTRAINT `fk_rm_daily_tasks_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_daily_tasks_project` FOREIGN KEY (`project_id`) REFERENCES `rm_projects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rm_daily_tasks_release` FOREIGN KEY (`release_id`) REFERENCES `rm_releases` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rm_modules`
--
ALTER TABLE `rm_modules`
  ADD CONSTRAINT `fk_rm_modules_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_modules_project` FOREIGN KEY (`project_id`) REFERENCES `rm_projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rm_projects`
--
ALTER TABLE `rm_projects`
  ADD CONSTRAINT `fk_rm_projects_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rm_releases`
--
ALTER TABLE `rm_releases`
  ADD CONSTRAINT `fk_rm_releases_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_releases_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rm_releases_code_overview` FOREIGN KEY (`code_overview_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rm_releases_module` FOREIGN KEY (`module_id`) REFERENCES `rm_modules` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rm_releases_project` FOREIGN KEY (`project_id`) REFERENCES `rm_projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_releases_screen` FOREIGN KEY (`screen_id`) REFERENCES `rm_screens` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rm_releases_tested_by` FOREIGN KEY (`tested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rm_releases_testing_pending` FOREIGN KEY (`testing_pending_with`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `rm_screens`
--
ALTER TABLE `rm_screens`
  ADD CONSTRAINT `fk_rm_screens_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rm_screens_module` FOREIGN KEY (`module_id`) REFERENCES `rm_modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tasks_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `task_activity`
--
ALTER TABLE `task_activity`
  ADD CONSTRAINT `fk_tact_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tact_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD CONSTRAINT `fk_ta_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ta_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
