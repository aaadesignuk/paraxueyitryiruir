-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 24, 2026 at 06:08 PM
-- Server version: 10.6.23-MariaDB-0ubuntu0.22.04.1
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `paralete_apz_009`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `audit_id` bigint(20) UNSIGNED NOT NULL,
  `entity_type` varchar(30) NOT NULL,
  `entity_id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(60) NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `billing_records`
--

CREATE TABLE `billing_records` (
  `billing_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `total_hours` decimal(10,2) DEFAULT NULL,
  `gross_amount` decimal(10,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `net_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Invoiced','Paid') DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commission_invoices`
--

CREATE TABLE `commission_invoices` (
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `gross_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 20.00,
  `commission_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Unpaid','Paid','Draft') NOT NULL DEFAULT 'Unpaid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `commission_invoice_items`
--

CREATE TABLE `commission_invoice_items` (
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `hours_worked` decimal(10,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `line_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employer_profiles`
--

CREATE TABLE `employer_profiles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `firm_name` varchar(160) NOT NULL,
  `area_of_law` varchar(100) DEFAULT NULL,
  `sub_specialism` varchar(255) DEFAULT NULL,
  `location` varchar(120) DEFAULT NULL,
  `tasks_required` text DEFAULT NULL,
  `telephone` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `employer_client_ref` varchar(120) DEFAULT NULL,
  `title` varchar(160) NOT NULL,
  `client_ref` varchar(100) DEFAULT NULL,
  `specialism` varchar(100) DEFAULT NULL,
  `sub_specialism` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `job_type` enum('Hours','Days') DEFAULT 'Hours',
  `hours_required` decimal(10,2) DEFAULT NULL,
  `on_site` tinyint(1) NOT NULL DEFAULT 0,
  `urgent_work` tinyint(1) NOT NULL DEFAULT 0,
  `work_247` tinyint(1) NOT NULL DEFAULT 0,
  `languages_required` varchar(255) DEFAULT NULL,
  `urgent_travel_required` tinyint(1) NOT NULL DEFAULT 0,
  `dual_qualifications` varchar(255) DEFAULT NULL,
  `max_rate` decimal(10,2) DEFAULT NULL,
  `rate_type` varchar(20) DEFAULT NULL,
  `rate_amount` decimal(10,2) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Open',
  `created_at` datetime DEFAULT current_timestamp(),
  `work_mode` enum('Remote','On-site','Hybrid') DEFAULT NULL,
  `job_country` varchar(100) DEFAULT NULL,
  `job_city` varchar(100) DEFAULT NULL,
  `travel_required` tinyint(1) DEFAULT 0,
  `travel_country` varchar(100) DEFAULT NULL,
  `travel_city` varchar(100) DEFAULT NULL,
  `travel_days` int(11) DEFAULT NULL,
  `travel_budget` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Triggers `jobs`
--
DELIMITER $$
CREATE TRIGGER `trg_jobs_min_rate_ins` BEFORE INSERT ON `jobs` FOR EACH ROW BEGIN
  DECLARE minrate DECIMAL(10,2);
  SELECT CAST(setting_value AS DECIMAL(10,2))
    INTO minrate
  FROM settings
  WHERE setting_key='MIN_HOURLY_RATE'
  LIMIT 1;

  IF minrate IS NOT NULL AND NEW.max_rate IS NOT NULL AND NEW.max_rate < minrate THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'max_rate below MIN_HOURLY_RATE';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_jobs_min_rate_upd` BEFORE UPDATE ON `jobs` FOR EACH ROW BEGIN
  DECLARE minrate DECIMAL(10,2);
  SELECT CAST(setting_value AS DECIMAL(10,2))
    INTO minrate
  FROM settings
  WHERE setting_key='MIN_HOURLY_RATE'
  LIMIT 1;

  IF minrate IS NOT NULL AND NEW.max_rate IS NOT NULL AND NEW.max_rate < minrate THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'max_rate below MIN_HOURLY_RATE';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `job_assignments`
--

CREATE TABLE `job_assignments` (
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `agreed_rate` decimal(10,2) DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `started_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_handover`
--

CREATE TABLE `job_handover` (
  `handover_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `method` enum('LINK','FIRM_EMAIL','PERSONAL_EMAIL','NOT_REQUIRED') NOT NULL DEFAULT 'LINK',
  `shared_link` varchar(1024) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `added_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `added_at` datetime DEFAULT NULL,
  `updated_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `access_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `access_confirmed_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `access_confirmed_at` datetime DEFAULT NULL,
  `access_issue_flag` tinyint(1) NOT NULL DEFAULT 0,
  `access_issue_note` text DEFAULT NULL,
  `access_issue_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_invitations`
--

CREATE TABLE `job_invitations` (
  `invitation_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('Invited','Accepted','Declined') DEFAULT 'Invited',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_task_requirements`
--

CREATE TABLE `job_task_requirements` (
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paralegal_category_experience`
--

CREATE TABLE `paralegal_category_experience` (
  `id` int(11) NOT NULL,
  `paralegal_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `experience_type` enum('Hours','Years') NOT NULL DEFAULT 'Years',
  `experience_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paralegal_invoices`
--

CREATE TABLE `paralegal_invoices` (
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `total_hours` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gross_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Draft','Submitted','Paid') NOT NULL DEFAULT 'Draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `submitted_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paralegal_invoice_items`
--

CREATE TABLE `paralegal_invoice_items` (
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `job_title_snapshot` varchar(160) NOT NULL DEFAULT '',
  `client_ref_snapshot` varchar(120) NOT NULL DEFAULT '',
  `hours` decimal(10,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paralegal_profiles`
--

CREATE TABLE `paralegal_profiles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `specialism` varchar(255) DEFAULT NULL,
  `experience_type` enum('None','Days','Months','Hours','Years') DEFAULT 'None',
  `experience_value` decimal(10,2) DEFAULT NULL,
  `preferred_rate` decimal(10,2) DEFAULT NULL,
  `preferred_rate_urgent` decimal(10,2) DEFAULT NULL,
  `preferred_rate_overnight` decimal(10,2) DEFAULT NULL,
  `preferred_rate_specialist` decimal(10,2) DEFAULT NULL,
  `location_preference` varchar(120) DEFAULT NULL,
  `base_country` varchar(120) DEFAULT NULL,
  `base_state` varchar(120) DEFAULT NULL,
  `base_city` varchar(120) DEFAULT NULL,
  `base_postcode` varchar(40) DEFAULT NULL,
  `base_address1` varchar(255) DEFAULT NULL,
  `base_address2` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `can_travel_abroad` tinyint(1) NOT NULL DEFAULT 0,
  `night_work` tinyint(1) NOT NULL DEFAULT 0,
  `languages` varchar(255) DEFAULT NULL,
  `profile_summary` text DEFAULT NULL,
  `availability_start` date DEFAULT NULL,
  `availability_end` date DEFAULT NULL,
  `weekly_availability` text DEFAULT NULL,
  `night_available` tinyint(1) NOT NULL DEFAULT 0,
  `weekend_available` tinyint(1) NOT NULL DEFAULT 0,
  `available_from` date DEFAULT NULL,
  `available_to` date DEFAULT NULL,
  `cv_path` varchar(255) DEFAULT NULL,
  `id_doc_path` varchar(255) DEFAULT NULL,
  `visa_path` varchar(255) DEFAULT NULL,
  `utility_bill_path` varchar(255) DEFAULT NULL,
  `language_skills` text DEFAULT NULL,
  `sub_specialism` varchar(255) DEFAULT NULL,
  `specialism2` varchar(255) DEFAULT NULL,
  `sub_specialism2` varchar(255) DEFAULT NULL,
  `travel_anywhere` tinyint(1) NOT NULL DEFAULT 0,
  `travel_countries` text DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `account_name` varchar(120) DEFAULT NULL,
  `account_no` varchar(40) DEFAULT NULL,
  `sort_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paralegal_skills`
--

CREATE TABLE `paralegal_skills` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `skill_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paralegal_task_skills`
--

CREATE TABLE `paralegal_task_skills` (
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED NOT NULL,
  `rater_user_id` bigint(20) UNSIGNED NOT NULL,
  `ratee_user_id` bigint(20) UNSIGNED NOT NULL,
  `stars` tinyint(3) UNSIGNED NOT NULL,
  `private_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `skill_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specialisms`
--

CREATE TABLE `specialisms` (
  `id` int(11) NOT NULL,
  `specialism` varchar(255) NOT NULL,
  `sub_specialism` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_activities`
--

CREATE TABLE `task_activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_categories`
--

CREATE TABLE `task_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheets`
--

CREATE TABLE `timesheets` (
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `hours_worked` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `work_type` enum('Work','Travel') NOT NULL DEFAULT 'Work',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `reviewed_at` datetime DEFAULT NULL,
  `paralegal_invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `paralegal_invoiced_at` datetime DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_disputes`
--

CREATE TABLE `timesheet_disputes` (
  `dispute_id` bigint(20) UNSIGNED NOT NULL,
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `assignment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `dispute_text` text DEFAULT NULL,
  `employer_reply_text` text DEFAULT NULL,
  `status` enum('Open','Resolved') NOT NULL DEFAULT 'Open',
  `resolved_action` enum('Approved','Rejected') DEFAULT NULL,
  `resolved_note` text DEFAULT NULL,
  `payable_hours` decimal(10,2) DEFAULT NULL,
  `payable_percent` decimal(6,2) DEFAULT NULL,
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_disputes_spare`
--

CREATE TABLE `timesheet_disputes_spare` (
  `dispute_id` bigint(20) UNSIGNED NOT NULL,
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `paralegal_id` bigint(20) UNSIGNED NOT NULL,
  `job_id` bigint(20) UNSIGNED DEFAULT NULL,
  `raised_by` enum('E','P') NOT NULL,
  `dispute_reason` text DEFAULT NULL,
  `status` enum('Open','Resolved') NOT NULL DEFAULT 'Open',
  `resolved_action` enum('Approved','Rejected') DEFAULT NULL,
  `resolved_note` text DEFAULT NULL,
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_entries`
--

CREATE TABLE `timesheet_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `work_date` date NOT NULL,
  `activity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `minutes` int(11) NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_queries`
--

CREATE TABLE `timesheet_queries` (
  `query_id` bigint(20) UNSIGNED NOT NULL,
  `timesheet_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `reason` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `para_response` text DEFAULT NULL,
  `para_responded_at` datetime DEFAULT NULL,
  `employer_reply` text DEFAULT NULL,
  `employer_replied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('A','E','P') NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `terms_accepted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`,`created_at`),
  ADD KEY `idx_audit_action` (`action`,`created_at`);

--
-- Indexes for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD PRIMARY KEY (`billing_id`),
  ADD KEY `idx_billing_assignment` (`assignment_id`);

--
-- Indexes for table `commission_invoices`
--
ALTER TABLE `commission_invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD KEY `idx_ci_employer_period` (`employer_id`,`period_start`,`period_end`);

--
-- Indexes for table `commission_invoice_items`
--
ALTER TABLE `commission_invoice_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uq_cii_invoice_timesheet` (`invoice_id`,`timesheet_id`),
  ADD KEY `idx_cii_invoice` (`invoice_id`);

--
-- Indexes for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `idx_jobs_employer` (`employer_id`);

--
-- Indexes for table `job_assignments`
--
ALTER TABLE `job_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_ja_job` (`job_id`),
  ADD KEY `idx_ja_employer` (`employer_id`),
  ADD KEY `idx_ja_paralegal` (`paralegal_id`);

--
-- Indexes for table `job_handover`
--
ALTER TABLE `job_handover`
  ADD PRIMARY KEY (`handover_id`),
  ADD UNIQUE KEY `uq_job_handover_job` (`job_id`),
  ADD KEY `idx_job_handover_job_confirmed` (`job_id`,`access_confirmed`),
  ADD KEY `idx_job_handover_confirmed_at` (`access_confirmed_at`);

--
-- Indexes for table `job_invitations`
--
ALTER TABLE `job_invitations`
  ADD PRIMARY KEY (`invitation_id`),
  ADD KEY `idx_ji_job` (`job_id`),
  ADD KEY `idx_ji_employer` (`employer_id`),
  ADD KEY `idx_ji_paralegal` (`paralegal_id`);

--
-- Indexes for table `job_task_requirements`
--
ALTER TABLE `job_task_requirements`
  ADD PRIMARY KEY (`job_id`,`activity_id`),
  ADD KEY `idx_jtr_activity` (`activity_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_notifications_user` (`user_id`);

--
-- Indexes for table `paralegal_category_experience`
--
ALTER TABLE `paralegal_category_experience`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_paralegal_category` (`paralegal_id`,`category_id`),
  ADD KEY `idx_paralegal` (`paralegal_id`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `paralegal_invoices`
--
ALTER TABLE `paralegal_invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `uq_pi_emp_para_job_period` (`employer_id`,`paralegal_id`,`job_id`,`period_start`,`period_end`),
  ADD KEY `idx_pi_employer_period` (`employer_id`,`period_start`,`period_end`),
  ADD KEY `idx_pi_paralegal_period` (`paralegal_id`,`period_start`,`period_end`),
  ADD KEY `idx_pi_para_period_job` (`paralegal_id`,`employer_id`,`period_start`,`period_end`,`job_id`);

--
-- Indexes for table `paralegal_invoice_items`
--
ALTER TABLE `paralegal_invoice_items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `uniq_invoice_timesheet` (`invoice_id`,`timesheet_id`),
  ADD KEY `idx_pii_invoice` (`invoice_id`),
  ADD KEY `idx_pii_assignment` (`assignment_id`);

--
-- Indexes for table `paralegal_profiles`
--
ALTER TABLE `paralegal_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `paralegal_skills`
--
ALTER TABLE `paralegal_skills`
  ADD PRIMARY KEY (`user_id`,`skill_id`),
  ADD KEY `idx_ps_skill` (`skill_id`),
  ADD KEY `idx_paralegal_skills_skill_id` (`skill_id`);

--
-- Indexes for table `paralegal_task_skills`
--
ALTER TABLE `paralegal_task_skills`
  ADD PRIMARY KEY (`paralegal_id`,`activity_id`),
  ADD KEY `idx_pts_activity` (`activity_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `uq_rating_unique` (`job_id`,`rater_user_id`,`ratee_user_id`),
  ADD KEY `idx_ratings_ratee` (`ratee_user_id`,`created_at`),
  ADD KEY `fk_ratings_rater` (`rater_user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`skill_id`),
  ADD UNIQUE KEY `uq_skills_name` (`name`);

--
-- Indexes for table `specialisms`
--
ALTER TABLE `specialisms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_activities`
--
ALTER TABLE `task_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task_activities_category` (`category_id`);

--
-- Indexes for table `task_categories`
--
ALTER TABLE `task_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD PRIMARY KEY (`timesheet_id`),
  ADD KEY `idx_timesheets_invoice` (`paralegal_invoice_id`),
  ADD KEY `idx_timesheets_assignment_id` (`assignment_id`);

--
-- Indexes for table `timesheet_disputes`
--
ALTER TABLE `timesheet_disputes`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `timesheet_id` (`timesheet_id`),
  ADD KEY `status` (`status`),
  ADD KEY `paralegal_id` (`paralegal_id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indexes for table `timesheet_disputes_spare`
--
ALTER TABLE `timesheet_disputes_spare`
  ADD PRIMARY KEY (`dispute_id`),
  ADD KEY `timesheet_id` (`timesheet_id`),
  ADD KEY `status` (`status`),
  ADD KEY `employer_id` (`employer_id`),
  ADD KEY `paralegal_id` (`paralegal_id`);

--
-- Indexes for table `timesheet_entries`
--
ALTER TABLE `timesheet_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tse_timesheet` (`timesheet_id`),
  ADD KEY `idx_tse_activity` (`activity_id`);

--
-- Indexes for table `timesheet_queries`
--
ALTER TABLE `timesheet_queries`
  ADD PRIMARY KEY (`query_id`),
  ADD KEY `idx_tq_timesheet` (`timesheet_id`,`created_at`),
  ADD KEY `fk_tq_employer` (`employer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `audit_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billing_records`
--
ALTER TABLE `billing_records`
  MODIFY `billing_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commission_invoices`
--
ALTER TABLE `commission_invoices`
  MODIFY `invoice_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `commission_invoice_items`
--
ALTER TABLE `commission_invoice_items`
  MODIFY `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `job_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_assignments`
--
ALTER TABLE `job_assignments`
  MODIFY `assignment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_handover`
--
ALTER TABLE `job_handover`
  MODIFY `handover_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_invitations`
--
ALTER TABLE `job_invitations`
  MODIFY `invitation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paralegal_category_experience`
--
ALTER TABLE `paralegal_category_experience`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paralegal_invoices`
--
ALTER TABLE `paralegal_invoices`
  MODIFY `invoice_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paralegal_invoice_items`
--
ALTER TABLE `paralegal_invoice_items`
  MODIFY `item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `skill_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specialisms`
--
ALTER TABLE `specialisms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_activities`
--
ALTER TABLE `task_activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheets`
--
ALTER TABLE `timesheets`
  MODIFY `timesheet_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_disputes`
--
ALTER TABLE `timesheet_disputes`
  MODIFY `dispute_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_disputes_spare`
--
ALTER TABLE `timesheet_disputes_spare`
  MODIFY `dispute_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_entries`
--
ALTER TABLE `timesheet_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_queries`
--
ALTER TABLE `timesheet_queries`
  MODIFY `query_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing_records`
--
ALTER TABLE `billing_records`
  ADD CONSTRAINT `fk_billing_records_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `job_assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `commission_invoices`
--
ALTER TABLE `commission_invoices`
  ADD CONSTRAINT `fk_commission_invoices_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `commission_invoice_items`
--
ALTER TABLE `commission_invoice_items`
  ADD CONSTRAINT `fk_cii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `commission_invoices` (`invoice_id`) ON DELETE CASCADE;

--
-- Constraints for table `employer_profiles`
--
ALTER TABLE `employer_profiles`
  ADD CONSTRAINT `fk_employer_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_jobs_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_assignments`
--
ALTER TABLE `job_assignments`
  ADD CONSTRAINT `fk_job_assignments_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_assignments_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_assignments_paralegal` FOREIGN KEY (`paralegal_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_handover`
--
ALTER TABLE `job_handover`
  ADD CONSTRAINT `fk_job_handover_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_invitations`
--
ALTER TABLE `job_invitations`
  ADD CONSTRAINT `fk_job_invitations_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_invitations_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_invitations_paralegal` FOREIGN KEY (`paralegal_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_task_requirements`
--
ALTER TABLE `job_task_requirements`
  ADD CONSTRAINT `fk_jtr_activity` FOREIGN KEY (`activity_id`) REFERENCES `task_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jtr_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `paralegal_invoices`
--
ALTER TABLE `paralegal_invoices`
  ADD CONSTRAINT `fk_paralegal_invoices_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_paralegal_invoices_paralegal` FOREIGN KEY (`paralegal_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `paralegal_invoice_items`
--
ALTER TABLE `paralegal_invoice_items`
  ADD CONSTRAINT `fk_pii_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `job_assignments` (`assignment_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `paralegal_invoices` (`invoice_id`) ON DELETE CASCADE;

--
-- Constraints for table `paralegal_profiles`
--
ALTER TABLE `paralegal_profiles`
  ADD CONSTRAINT `fk_paralegal_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `paralegal_skills`
--
ALTER TABLE `paralegal_skills`
  ADD CONSTRAINT `fk_paralegal_skills_activity` FOREIGN KEY (`skill_id`) REFERENCES `task_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_paralegal_skills_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `paralegal_task_skills`
--
ALTER TABLE `paralegal_task_skills`
  ADD CONSTRAINT `fk_pts_activity` FOREIGN KEY (`activity_id`) REFERENCES `task_activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pts_paralegal` FOREIGN KEY (`paralegal_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `fk_ratings_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_ratee` FOREIGN KEY (`ratee_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_rater` FOREIGN KEY (`rater_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `task_activities`
--
ALTER TABLE `task_activities`
  ADD CONSTRAINT `fk_task_activities_category` FOREIGN KEY (`category_id`) REFERENCES `task_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timesheets`
--
ALTER TABLE `timesheets`
  ADD CONSTRAINT `fk_timesheets_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `job_assignments` (`assignment_id`) ON DELETE CASCADE;

--
-- Constraints for table `timesheet_entries`
--
ALTER TABLE `timesheet_entries`
  ADD CONSTRAINT `fk_tse_activity` FOREIGN KEY (`activity_id`) REFERENCES `task_activities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tse_timesheet` FOREIGN KEY (`timesheet_id`) REFERENCES `timesheets` (`timesheet_id`) ON DELETE CASCADE;

--
-- Constraints for table `timesheet_queries`
--
ALTER TABLE `timesheet_queries`
  ADD CONSTRAINT `fk_tq_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tq_timesheet` FOREIGN KEY (`timesheet_id`) REFERENCES `timesheets` (`timesheet_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
