-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2025 at 01:48 PM
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
-- Database: `systems`
--

-- --------------------------------------------------------

--
-- Table structure for table `allowances`
--

CREATE TABLE `allowances` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_name` varchar(50) NOT NULL,
  `transport` decimal(10,2) DEFAULT 0.00,
  `meal` decimal(10,2) DEFAULT 0.00,
  `housing` decimal(10,2) DEFAULT 0.00,
  `communication` decimal(10,2) DEFAULT 0.00,
  `other_benefits` decimal(10,2) DEFAULT 0.00,
  `benefits_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allowances`
--

INSERT INTO `allowances` (`id`, `employee_name`, `transport`, `meal`, `housing`, `communication`, `other_benefits`, `benefits_description`, `created_at`) VALUES
(1, 'admin admin', 100.00, 50.00, 50.00, 50.00, 100.00, 'dasasd', '2025-08-23 12:16:23'),
(2, 'admin admin', 100.00, 50.00, 50.00, 50.00, 100.00, 'dasasd', '2025-08-23 12:23:10');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(6) UNSIGNED NOT NULL,
  `job_requisition_id` int(6) UNSIGNED DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Applied',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `application_date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `time_in` timestamp NULL DEFAULT NULL,
  `time_out` timestamp NULL DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `status` enum('present','absent','late','half-day','leave','holiday') NOT NULL,
  `leave_type` enum('sick','vacation','personal','maternity','paternity','other') DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `benefits`
--

CREATE TABLE `benefits` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` int(11) NOT NULL,
  `benefit_type` varchar(100) NOT NULL,
  `coverage_level` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `premium` decimal(10,2) DEFAULT NULL,
  `employer_contribution` decimal(10,2) DEFAULT NULL,
  `status` enum('active','pending','terminated') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `benefits_usage`
--

CREATE TABLE `benefits_usage` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `benefit_type` enum('Consultation','Hospitalization','Reimbursement') NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date_used` date NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `benefits_usage`
--

INSERT INTO `benefits_usage` (`id`, `employee_id`, `benefit_type`, `description`, `amount`, `date_used`, `status`, `reg_date`) VALUES
(1, '4', 'Consultation', 'sdadas', 500.00, '2025-08-25', 'Approved', '2025-08-25 05:19:53');

-- --------------------------------------------------------

--
-- Table structure for table `benefit_plans`
--

CREATE TABLE `benefit_plans` (
  `id` int(6) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('health','dental','vision','retirement','other') NOT NULL,
  `cost_employee` decimal(10,2) DEFAULT NULL,
  `cost_employer` decimal(10,2) DEFAULT NULL,
  `eligibility_conditions` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_allocation`
--

CREATE TABLE `budget_allocation` (
  `id` int(6) UNSIGNED NOT NULL,
  `year` int(4) NOT NULL,
  `department` varchar(50) NOT NULL,
  `total_budget` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `claim_type` enum('medical','dental','vision','wellness','other') NOT NULL,
  `provider_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','under_review','approved','rejected','paid') DEFAULT 'submitted',
  `reviewer_notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`id`, `employee_id`, `claim_type`, `provider_id`, `amount`, `description`, `document_path`, `status`, `reviewer_notes`, `submitted_at`, `reviewed_at`, `paid_at`, `submission_date`) VALUES
(1, 2, 'medical', 1, 10000.00, 'dasad', 'uploads/claims/1755701561_ece5d31a-a788-43f0-b884-98e186500b26.pdf', 'submitted', NULL, '2025-08-20 14:52:41', NULL, NULL, '2025-08-20 16:43:33'),
(2, 2, 'medical', 1, 10000.00, 'dasad', 'uploads/claims/1755703365_ece5d31a-a788-43f0-b884-98e186500b26.pdf', 'submitted', NULL, '2025-08-20 15:22:45', NULL, NULL, '2025-08-20 16:43:33');

-- --------------------------------------------------------

--
-- Table structure for table `compensation`
--

CREATE TABLE `compensation` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `base_salary` decimal(10,2) NOT NULL,
  `bonuses` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `adjustments` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensation_calculations`
--

CREATE TABLE `compensation_calculations` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` int(6) UNSIGNED NOT NULL,
  `sales_amount` decimal(10,2) NOT NULL,
  `commission` decimal(10,2) NOT NULL,
  `total_compensation` decimal(10,2) NOT NULL,
  `calculation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `compensation_calculations`
--

INSERT INTO `compensation_calculations` (`id`, `employee_id`, `sales_amount`, `commission`, `total_compensation`, `calculation_date`) VALUES
(1, 1, 100000.00, 5000.00, 57000.00, '2025-08-21 13:44:04'),
(2, 2, 150000.00, 11250.00, 74250.00, '2025-08-21 13:44:04'),
(3, 3, 80000.00, 8000.00, 54500.00, '2025-08-21 13:44:04'),
(4, 1, 75000.00, 3750.00, 55750.00, '2025-08-21 13:44:04'),
(5, 4, 120000.00, 7800.00, 65300.00, '2025-08-21 13:44:04'),
(6, 5, 95000.00, 7600.00, 61400.00, '2025-08-21 13:44:04'),
(7, 2, 180000.00, 13500.00, 76500.00, '2025-08-21 13:44:04'),
(8, 3, 60000.00, 6000.00, 52500.00, '2025-08-21 13:44:04'),
(9, 1, 100000.00, 5000.00, 57000.00, '2025-08-21 13:55:14'),
(10, 2, 150000.00, 11250.00, 74250.00, '2025-08-21 13:55:14'),
(11, 3, 80000.00, 8000.00, 54500.00, '2025-08-21 13:55:14'),
(12, 1, 75000.00, 3750.00, 55750.00, '2025-08-21 13:55:14'),
(13, 4, 120000.00, 7800.00, 65300.00, '2025-08-21 13:55:14'),
(14, 5, 95000.00, 7600.00, 61400.00, '2025-08-21 13:55:14'),
(15, 2, 180000.00, 13500.00, 76500.00, '2025-08-21 13:55:14'),
(16, 3, 60000.00, 6000.00, 52500.00, '2025-08-21 13:55:14');

-- --------------------------------------------------------

--
-- Table structure for table `compensation_records`
--

CREATE TABLE `compensation_records` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `compensation_type` enum('salary','bonus','increase') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `effective_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compensation_requests`
--

CREATE TABLE `compensation_requests` (
  `id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `compensation_type` enum('overtime','bonus','travel_expense','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `approval_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` int(11) NOT NULL,
  `sss` decimal(10,2) DEFAULT 0.00,
  `philhealth` decimal(10,2) DEFAULT 0.00,
  `pagibig` decimal(10,2) DEFAULT 0.00,
  `withholding_tax` decimal(10,2) DEFAULT 0.00,
  `company_loans` decimal(10,2) DEFAULT 0.00,
  `salary_advances` decimal(10,2) DEFAULT 0.00,
  `absences` int(3) DEFAULT 0,
  `lateness` int(3) DEFAULT 0,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deduction_rules`
--

CREATE TABLE `deduction_rules` (
  `id` int(6) UNSIGNED NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `deduction_type` enum('fixed','percentage') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `applies_to_all` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deduction_rules`
--

INSERT INTO `deduction_rules` (`id`, `rule_name`, `deduction_type`, `amount`, `description`, `applies_to_all`, `is_active`, `created_at`) VALUES
(1, 'Standard Deduction Application', 'fixed', 500.00, 'Conditions:\r\n\r\nEmployee must have an active payroll record.\r\n\r\nDeductions are applied only if salary > Minimum Wage (to comply with labor law).\r\n\r\nEach deduction is calculated based on either:\r\n\r\nFixed amount (e.g., ₱500), or\r\n\r\nPercentage of Basic Salary (e.g., 5%).', 1, 1, '2025-08-25 15:53:10'),
(3, 'Company Loan Deduction', 'percentage', 20.00, 'Conditions:\r\n\r\nApplies if employee has an outstanding loan balance in company records.\r\n\r\nDeduction may be fixed amount or percentage of monthly salary, depending on agreement.', 0, 1, '2025-08-25 15:57:39'),
(4, 'Health Insurance Contribution', 'fixed', 500.00, 'per month deduction for health insurance of the employee', 1, 1, '2025-08-25 15:58:50');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Operations / Transport Management', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(2, 'Warehousing & Inventory', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(3, 'Dispatch & Fleet Management', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(4, 'Customs & Compliance', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(5, 'Sales & Marketing', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(6, 'Customer Service', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(7, 'Billing & Invoicing', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(8, 'Accounts & Finance', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(9, 'HR & Administration', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(10, 'IT & System Administration', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41'),
(11, 'Data Analytics & Reporting', NULL, '2025-08-20 11:38:41', '2025-08-20 11:38:41');

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('bank','check','cash') NOT NULL,
  `disbursement_date` date NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `pay_period_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `disbursements`
--

INSERT INTO `disbursements` (`id`, `employee_id`, `amount`, `payment_method`, `disbursement_date`, `status`, `reference`, `pay_period_id`, `created_at`) VALUES
(1, 4, 26787.50, 'cash', '2025-08-24', 'completed', NULL, 1, '2025-08-24 09:57:49'),
(2, 4, 26787.50, 'cash', '2025-08-24', 'completed', NULL, 1, '2025-08-24 10:04:24'),
(3, 4, 26787.50, 'bank', '2025-08-24', 'completed', NULL, 1, '2025-08-24 10:04:51');

-- --------------------------------------------------------

--
-- Table structure for table `earnings_calculations`
--

CREATE TABLE `earnings_calculations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_id` int(11) NOT NULL,
  `base_pay` decimal(12,2) NOT NULL,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `holiday_pay` decimal(12,2) DEFAULT 0.00,
  `night_differential` decimal(12,2) DEFAULT 0.00,
  `incentives` decimal(12,2) DEFAULT 0.00,
  `bonuses` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) NOT NULL,
  `calculation_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `earnings_calculations`
--

INSERT INTO `earnings_calculations` (`id`, `employee_id`, `pay_period_id`, `base_pay`, `overtime_pay`, `holiday_pay`, `night_differential`, `incentives`, `bonuses`, `allowances`, `gross_pay`, `calculation_date`, `created_at`) VALUES
(1, 4, 1, 26400.00, 187.50, 0.00, 0.00, 100.00, 100.00, 0.00, 26787.50, '2025-08-24', '2025-08-24 07:36:49'),
(2, 4, 1, 26400.00, 187.50, 0.00, 0.00, 100.00, 100.00, 0.00, 26787.50, '2025-08-24', '2025-08-24 07:36:59'),
(3, 4, 1, 26400.00, 187.50, 0.00, 0.00, 100.00, 100.00, 0.00, 26787.50, '2025-08-24', '2025-08-24 07:37:32');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `hire_date` date NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `payment_method` enum('bank','check','cash') DEFAULT 'bank',
  `has_system_access` tinyint(1) DEFAULT 0,
  `role` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `department_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `email`, `phone`, `hire_date`, `department`, `position`, `bank_account`, `payment_method`, `has_system_access`, `role`, `address`, `date_of_birth`, `gender`, `marital_status`, `emergency_contact_name`, `emergency_contact_phone`, `document_path`, `status`, `department_id`, `role_id`, `created_at`, `updated_at`) VALUES
(1, 'than', 'peros', 'than@gmail.com', '098312313213', '2025-08-20', NULL, NULL, NULL, 'bank', 1, NULL, '187 AREA 5 SITO MENDEZ BAESA', '2004-05-10', 'Male', 'Single', '', '', 'uploads/68a5aeb9bc1fc.docx', '', NULL, NULL, '2025-08-20 11:17:13', '2025-08-20 13:00:11'),
(2, 'COOKIES', 'cookie', 'cookies@gmail.com', '0912412312332', '2025-08-20', 'Accounts & Finance', 'Analyst', NULL, 'bank', 1, NULL, '41 dimas street arty subdivision lawang bato', '2025-08-20', 'Female', 'Single', '', '', 'uploads/68a5cba91c6f3.docx', 'approved', NULL, NULL, '2025-08-20 13:20:41', '2025-08-22 18:32:54'),
(3, 'dogie', 'dogie', 'dogie@gmail.com', '09213142131', '2025-08-23', NULL, NULL, NULL, 'bank', 1, NULL, 'adaadad', '2025-08-23', 'Male', 'Single', '091214123', '0912414123', 'uploads/68a8bab447bee.docx', 'approved', NULL, NULL, '2025-08-22 18:45:08', '2025-08-22 18:45:34'),
(4, 'admin', 'admin', 'admin@gmail.com', '0912141241313', '2025-08-23', 'HR & Administration', 'Manager', NULL, 'bank', 1, NULL, 'asdaasda', '2025-08-23', 'Male', 'Single', '0912413132', '09124123', 'uploads/68a8bd3cb8637.docx', 'approved', NULL, NULL, '2025-08-22 18:55:56', '2025-08-23 11:51:48');

-- --------------------------------------------------------

--
-- Table structure for table `employee_assignment_history`
--

CREATE TABLE `employee_assignment_history` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_coverage`
--

CREATE TABLE `employee_coverage` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` int(6) NOT NULL,
  `policy_id` int(6) NOT NULL,
  `enrollment_date` date NOT NULL,
  `coverage_status` enum('Active','Suspended','Terminated') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_deductions`
--

CREATE TABLE `employee_deductions` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` int(6) UNSIGNED NOT NULL,
  `rule_id` int(6) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deduction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_deductions`
--

INSERT INTO `employee_deductions` (`id`, `employee_id`, `rule_id`, `amount`, `deduction_date`, `description`, `created_at`) VALUES
(1, 4, 1, 500.00, '2025-08-25', 'Conditions:\r\n\r\nEmployee must have an active payroll record.\r\n\r\nDeductions are applied only if salary > Minimum Wage (to comply with labor law).\r\n\r\nEach deduction is calculated based on either:\r\n\r\nFixed amount (e.g., ₱500), or\r\n\r\nPercentage of Basic Salary (e.g., 5%).', '2025-08-25 15:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `employee_performance`
--

CREATE TABLE `employee_performance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `review_date` date NOT NULL,
  `performance_score` decimal(4,2) NOT NULL,
  `review_period` enum('Monthly','Quarterly','Semi-Annual','Annual') NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hmo_policies`
--

CREATE TABLE `hmo_policies` (
  `id` int(6) UNSIGNED NOT NULL,
  `policy_name` varchar(100) NOT NULL,
  `provider` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `coverage_details` text DEFAULT NULL,
  `annual_limit` decimal(12,2) NOT NULL,
  `consultation_limit` int(6) NOT NULL,
  `hospitalization_limit` int(6) NOT NULL,
  `reimbursement_limit` decimal(10,2) NOT NULL,
  `monthly_premium` decimal(8,2) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `effective_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holiday_settings`
--

CREATE TABLE `holiday_settings` (
  `id` int(11) NOT NULL,
  `holiday_name` varchar(100) NOT NULL,
  `holiday_date` date NOT NULL,
  `multiplier` decimal(4,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holiday_settings`
--

INSERT INTO `holiday_settings` (`id`, `holiday_name`, `holiday_date`, `multiplier`, `description`, `is_recurring`, `created_at`) VALUES
(1, 'New Year\'s Day', '2025-01-01', 2.00, 'Regular holiday', 1, '2025-08-24 07:35:28'),
(2, 'Independence Day', '2025-06-12', 2.00, 'Regular holiday', 1, '2025-08-24 07:35:28'),
(3, 'Christmas Day', '2025-12-25', 2.00, 'Regular holiday', 1, '2025-08-24 07:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `incentives`
--

CREATE TABLE `incentives` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `incentive_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `date_given` date NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `incentives`
--

INSERT INTO `incentives` (`id`, `employee_id`, `incentive_type`, `amount`, `date_given`, `description`, `created_at`) VALUES
(6, 1, '13th Month Pay', 16000.00, '2025-08-23', '', '2025-08-23 10:05:09'),
(7, 1, '13th Month Pay', 160000.00, '2025-08-23', 'adsdsa', '2025-08-23 10:13:47'),
(8, 4, 'Performance Bonus', 1231.00, '2025-08-23', '', '2025-08-23 10:50:16');

-- --------------------------------------------------------

--
-- Table structure for table `incentive_rules`
--

CREATE TABLE `incentive_rules` (
  `id` int(11) NOT NULL,
  `rule_name` varchar(255) NOT NULL,
  `incentive_type` enum('Performance Bonus','13th Month Pay','Attendance Incentive','Commission','Other') NOT NULL,
  `calculation_method` enum('Fixed Amount','Percentage of Salary','Tiered Calculation','Custom Formula') NOT NULL,
  `calculation_parameters` text DEFAULT NULL,
  `eligibility_criteria` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` int(6) UNSIGNED NOT NULL,
  `applicant_id` int(6) UNSIGNED DEFAULT NULL,
  `interview_date` datetime DEFAULT NULL,
  `interviewer` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_requisitions`
--

CREATE TABLE `job_requisitions` (
  `id` int(6) UNSIGNED NOT NULL,
  `position_title` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `job_description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpis`
--

CREATE TABLE `kpis` (
  `kpi_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `kpi_date` date NOT NULL,
  `kpi_name` varchar(255) NOT NULL,
  `target` decimal(10,2) NOT NULL,
  `actual` decimal(10,2) NOT NULL,
  `achievement` decimal(5,2) DEFAULT NULL,
  `weightage` decimal(5,2) DEFAULT 100.00,
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans_deductions`
--

CREATE TABLE `loans_deductions` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `sss_loans` decimal(10,2) DEFAULT 0.00,
  `company_loans` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `schedule_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `night_differential_settings`
--

CREATE TABLE `night_differential_settings` (
  `id` int(11) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `multiplier` decimal(4,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `night_differential_settings`
--

INSERT INTO `night_differential_settings` (`id`, `start_time`, `end_time`, `multiplier`, `description`, `is_active`, `created_at`) VALUES
(1, '22:00:00', '06:00:00', 1.10, 'Night differential rate', 1, '2025-08-24 07:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_process`
--

CREATE TABLE `onboarding_process` (
  `id` int(6) UNSIGNED NOT NULL,
  `applicant_id` int(6) UNSIGNED DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `orientation_date` date DEFAULT NULL,
  `paperwork_status` varchar(20) DEFAULT 'Pending',
  `training_status` varchar(20) DEFAULT 'Pending',
  `equipment_status` varchar(20) DEFAULT 'Pending',
  `status` varchar(20) DEFAULT 'In Progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_schedules`
--

CREATE TABLE `onboarding_schedules` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `session_type` enum('orientation','onboarding','training') NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `location` varchar(100) NOT NULL,
  `facilitator` varchar(100) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `onboarding_schedules`
--

INSERT INTO `onboarding_schedules` (`id`, `employee_id`, `session_type`, `session_date`, `session_time`, `location`, `facilitator`, `status`, `notes`, `created_at`) VALUES
(1, 1, 'training', '2025-08-20', '08:08:00', 'head quarters', 'asdasd', 'completed', 'asdasd', '2025-08-20 12:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `overtime_rates`
--

CREATE TABLE `overtime_rates` (
  `id` int(11) NOT NULL,
  `rate_name` varchar(100) NOT NULL,
  `multiplier` decimal(4,2) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_rates`
--

INSERT INTO `overtime_rates` (`id`, `rate_name`, `multiplier`, `description`, `is_active`, `created_at`) VALUES
(1, 'Regular Overtime', 1.25, 'Standard overtime rate', 1, '2025-08-24 07:35:28'),
(2, 'Rest Day Overtime', 1.69, 'Overtime on rest days', 1, '2025-08-24 07:35:28'),
(3, 'Holiday Overtime', 2.60, 'Overtime on holidays', 1, '2025-08-24 07:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `gross_pay` decimal(10,2) NOT NULL,
  `sss_contribution` decimal(10,2) DEFAULT 0.00,
  `philhealth_contribution` decimal(10,2) DEFAULT 0.00,
  `pagibig_contribution` decimal(10,2) DEFAULT 0.00,
  `withholding_tax` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `date_processed` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pay_periods`
--

CREATE TABLE `pay_periods` (
  `id` int(11) NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pay_periods`
--

INSERT INTO `pay_periods` (`id`, `period_name`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(1, 'Current Period', '2025-08-01', '2025-08-31', 1, '2025-08-24 07:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `pay_rates`
--

CREATE TABLE `pay_rates` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_type` enum('monthly','daily','hourly') NOT NULL,
  `rate` decimal(12,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pay_rates`
--

INSERT INTO `pay_rates` (`id`, `employee_id`, `pay_type`, `rate`, `effective_date`, `created_at`) VALUES
(1, 1, 'hourly', 180.00, '2025-08-24', '2025-08-24 07:35:28'),
(2, 2, 'daily', 1983.00, '2025-08-24', '2025-08-24 07:35:28'),
(3, 3, 'hourly', 248.00, '2025-08-24', '2025-08-24 07:35:28'),
(4, 4, 'hourly', 150.00, '2025-08-24', '2025-08-24 07:35:28');

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--

CREATE TABLE `performance_reviews` (
  `review_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `review_date` date NOT NULL,
  `review_period_start` date NOT NULL,
  `review_period_end` date NOT NULL,
  `overall_rating` decimal(3,1) NOT NULL,
  `job_knowledge_rating` int(11) DEFAULT NULL CHECK (`job_knowledge_rating` between 1 and 5),
  `productivity_rating` int(11) DEFAULT NULL CHECK (`productivity_rating` between 1 and 5),
  `quality_work_rating` int(11) DEFAULT NULL CHECK (`quality_work_rating` between 1 and 5),
  `teamwork_rating` int(11) DEFAULT NULL CHECK (`teamwork_rating` between 1 and 5),
  `communication_rating` int(11) DEFAULT NULL CHECK (`communication_rating` between 1 and 5),
  `initiative_rating` int(11) DEFAULT NULL CHECK (`initiative_rating` between 1 and 5),
  `strengths` text DEFAULT NULL,
  `areas_improvement` text DEFAULT NULL,
  `goals` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `employee_comments` text DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') DEFAULT 'draft',
  `next_review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`id`, `title`, `department_id`, `description`, `created_at`) VALUES
(1, 'Driver', NULL, NULL, '2025-08-20 11:50:38'),
(2, 'Dock Worker', NULL, NULL, '2025-08-20 11:50:38'),
(3, 'Supervisor', NULL, NULL, '2025-08-20 11:50:38'),
(4, 'Manager', NULL, NULL, '2025-08-20 11:50:38'),
(5, 'Coordinator', NULL, NULL, '2025-08-20 11:50:38'),
(6, 'Analyst', NULL, NULL, '2025-08-20 11:50:38'),
(7, 'Specialist', NULL, NULL, '2025-08-20 11:50:38');

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('hospital','clinic','dental','pharmacy','other') NOT NULL,
  `address` text DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--

INSERT INTO `providers` (`id`, `name`, `type`, `address`, `contact_phone`, `contact_email`, `status`, `created_at`) VALUES
(1, 'City General Hospital', 'hospital', '123 Medical Ave, Cityville', '555-1234', 'info@citygeneral.com', 'active', '2025-08-20 14:52:24'),
(2, 'Bright Smile Dental', 'dental', '456 Dental St, Townsville', '555-5678', 'contact@brightsmile.com', 'active', '2025-08-20 14:52:24'),
(3, 'Clear Vision Center', 'other', '789 Optical Rd, Villageton', '555-9012', 'hello@clearvision.com', 'active', '2025-08-20 14:52:24'),
(4, 'Wellness Pharmacy', 'pharmacy', '321 Health Blvd, Hamletown', '555-3456', 'orders@wellnesspharmacy.com', 'active', '2025-08-20 14:52:24');

-- --------------------------------------------------------

--
-- Table structure for table `required_documents`
--

CREATE TABLE `required_documents` (
  `id` int(6) UNSIGNED NOT NULL,
  `compensation_type` enum('all','overtime','bonus','travel_expense','other') DEFAULT 'all',
  `document_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `required_documents`
--

INSERT INTO `required_documents` (`id`, `compensation_type`, `document_name`, `description`, `is_required`, `created_at`) VALUES
(1, 'overtime', 'Timesheet', 'Signed timesheet showing overtime hours', 1, '2025-08-21 12:52:14'),
(2, 'bonus', 'Performance Review', 'Recent performance review documentation', 1, '2025-08-21 12:52:14'),
(3, 'travel_expense', 'Receipts', 'All expense receipts', 1, '2025-08-21 12:52:14'),
(4, 'travel_expense', 'Travel Itinerary', 'Detailed travel itinerary', 1, '2025-08-21 12:52:14'),
(5, 'other', 'Explanation Letter', 'Detailed explanation of the compensation request', 1, '2025-08-21 12:52:14');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_adjustments`
--

CREATE TABLE `salary_adjustments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `adjustment_type` enum('COLA','Reclassification','Promotion','Merit Increase','Other') NOT NULL,
  `adjustment_amount` decimal(12,2) NOT NULL,
  `description` text DEFAULT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_adjustments`
--

INSERT INTO `salary_adjustments` (`id`, `employee_id`, `adjustment_type`, `adjustment_amount`, `description`, `effective_date`, `created_at`, `amount`) VALUES
(1, 2, 'Promotion', 0.00, 'dasd', '2025-08-25', '2025-08-25 04:53:23', 10000.00),
(2, 2, 'Promotion', 0.00, 'dasd', '2025-08-25', '2025-08-25 04:55:05', 10000.00),
(3, 2, 'Promotion', 0.00, 'dasd', '2025-08-25', '2025-08-25 04:58:25', 10000.00),
(4, 2, 'Promotion', 0.00, 'dasd', '2025-08-25', '2025-08-25 05:04:02', 10000.00);

-- --------------------------------------------------------

--
-- Table structure for table `salary_assignments`
--

CREATE TABLE `salary_assignments` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `salary_grade` varchar(100) DEFAULT NULL,
  `job_level` int(11) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_assignments`
--

INSERT INTO `salary_assignments` (`id`, `employee_id`, `base_salary`, `salary_grade`, `job_level`, `effective_date`, `created_at`) VALUES
(1, 1, 18000.00, 'SG-3', NULL, '2025-08-23', '2025-08-23 08:39:20');

-- --------------------------------------------------------

--
-- Table structure for table `salary_grades`
--

CREATE TABLE `salary_grades` (
  `id` int(11) NOT NULL,
  `grade_name` varchar(100) NOT NULL,
  `min_salary` decimal(12,2) NOT NULL,
  `max_salary` decimal(12,2) NOT NULL,
  `job_level` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_grades`
--

INSERT INTO `salary_grades` (`id`, `grade_name`, `min_salary`, `max_salary`, `job_level`, `created_at`) VALUES
(1, 'paldo ', 1000.00, 50000.00, 3, '2025-08-23 08:12:52'),
(3, 'sakto lang', 800.00, 22991.00, 2, '2025-08-23 08:31:08'),
(4, 'pwede na', 690.00, 20001.00, 1, '2025-08-23 08:31:46');

-- --------------------------------------------------------

--
-- Table structure for table `salary_structures`
--

CREATE TABLE `salary_structures` (
  `id` int(11) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `level_name` varchar(100) NOT NULL,
  `min_salary` decimal(12,2) NOT NULL,
  `max_salary` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salary_structures`
--

INSERT INTO `salary_structures` (`id`, `grade`, `level_name`, `min_salary`, `max_salary`, `created_at`) VALUES
(1, 'SG-1', 'Entry Level', 12000.00, 18000.00, '2025-08-23 08:35:44'),
(2, 'SG-2', 'Junior Staff', 15000.00, 22000.00, '2025-08-23 08:35:44'),
(3, 'SG-3', 'Staff', 18000.00, 26000.00, '2025-08-23 08:35:44'),
(4, 'SG-4', 'Senior Staff', 22000.00, 32000.00, '2025-08-23 08:35:44'),
(5, 'SG-5', 'Supervisor', 28000.00, 40000.00, '2025-08-23 08:35:44'),
(6, 'SG-6', 'Assistant Manager', 35000.00, 50000.00, '2025-08-23 08:35:44'),
(7, 'SG-7', 'Manager', 45000.00, 65000.00, '2025-08-23 08:35:44'),
(8, 'SG-8', 'Senior Manager', 60000.00, 85000.00, '2025-08-23 08:35:44'),
(9, 'SG-9', 'Director', 80000.00, 120000.00, '2025-08-23 08:35:44'),
(10, 'SG-10', 'Executive', 100000.00, 150000.00, '2025-08-23 08:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`id`, `employee_id`, `status`, `changed_at`, `notes`, `changed_by`) VALUES
(1, 1, 'inactive', '2025-08-20 13:07:09', NULL, 'Admin'),
(2, 1, 'inactive', '2025-08-20 13:07:48', NULL, 'Admin'),
(3, 1, 'inactive', '2025-08-20 13:08:50', NULL, 'Admin'),
(4, 1, 'inactive', '2025-08-20 13:09:00', NULL, 'Admin'),
(5, 1, 'active', '2025-08-20 13:11:47', NULL, 'Admin'),
(6, 4, 'active', '2025-08-23 07:34:54', NULL, 'Admin'),
(7, 2, 'active', '2025-08-23 07:34:56', NULL, 'Admin'),
(8, 3, 'active', '2025-08-23 07:34:57', NULL, 'Admin'),
(9, 4, 'inactive', '2025-08-25 07:41:27', NULL, 'Admin'),
(10, 2, 'active', '2025-08-25 07:41:28', NULL, 'Admin'),
(11, 2, 'active', '2025-08-25 07:41:29', NULL, 'Admin'),
(12, 2, 'inactive', '2025-08-25 07:41:33', NULL, 'Admin'),
(13, 3, 'inactive', '2025-08-25 07:41:37', NULL, 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `system_profiles`
--

CREATE TABLE `system_profiles` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `access_level` enum('basic','manager','admin') DEFAULT 'basic',
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_profiles`
--

INSERT INTO `system_profiles` (`id`, `employee_id`, `username`, `password`, `password_hash`, `email`, `access_level`, `department`, `position`, `last_login`, `is_active`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'user194b3', '', '$2y$10$xrKoLuex.0FLFfjfsxEDZOV.FM5PT9cNe8xiWUnLpvvMnuzLeNg7S', 'than@gmail.com', 'manager', NULL, NULL, NULL, 1, 'active', '2025-08-20 12:07:00', '2025-08-20 12:07:00'),
(2, 1, 'user1943f', '', '$2y$10$iJCNVmRQNK1hPC4adzlBgOrqVYYfeXuwuNMmalgcNjYNkdIXzycm.', '', 'manager', NULL, NULL, NULL, 1, 'active', '2025-08-20 12:07:36', '2025-08-20 12:07:36'),
(3, 2, 'user27200', '', '$2y$10$NWd7Vsw62G.1DACRpdNDwOitRlU.NaqRoCuHFI9krUCHXnCROcaIC', 'cookies@gmail.com', 'basic', NULL, NULL, NULL, 1, 'active', '2025-08-22 18:32:54', '2025-08-22 18:32:54'),
(4, 3, 'user33faf', '', '$2y$10$qxmFQWvDvnKxCIbTMdT0x.0jRUQH/j7qvujRs26LDYBVSTASJ2/3y', 'dogie@gmail.com', 'basic', NULL, NULL, NULL, 1, 'active', '2025-08-22 18:45:34', '2025-08-22 18:45:34'),
(5, 4, 'user45568', '', '$2y$10$r8Njoeg0mm8ddjAPSBvmUOPCVTDuWM7kru5P38Yvz5G.1nVswrJXa', 'admin@gmail.com', 'admin', NULL, NULL, NULL, 1, 'active', '2025-08-22 18:56:21', '2025-08-22 19:02:49');

-- --------------------------------------------------------

--
-- Table structure for table `time_attendance`
--

CREATE TABLE `time_attendance` (
  `id` int(6) UNSIGNED NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `undertime_hours` decimal(4,2) DEFAULT 0.00,
  `leaves` int(3) DEFAULT 0,
  `absences` int(3) DEFAULT 0,
  `record_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_records`
--

CREATE TABLE `training_records` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `training_name` varchar(100) NOT NULL,
  `training_date` date NOT NULL,
  `completion_date` date NOT NULL,
  `trainer` varchar(100) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `status` enum('completed','in-progress','failed') DEFAULT 'completed',
  `certificate_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `validation_rules`
--

CREATE TABLE `validation_rules` (
  `id` int(6) UNSIGNED NOT NULL,
  `rule_name` varchar(100) NOT NULL,
  `rule_description` text DEFAULT NULL,
  `rule_type` enum('eligibility','document','compliance') NOT NULL,
  `compensation_type` enum('all','overtime','bonus','travel_expense','other') DEFAULT 'all',
  `condition_field` varchar(100) DEFAULT NULL,
  `condition_operator` enum('>','<','>=','<=','=','!=','in','not_in') DEFAULT NULL,
  `condition_value` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `validation_rules`
--

INSERT INTO `validation_rules` (`id`, `rule_name`, `rule_description`, `rule_type`, `compensation_type`, `condition_field`, `condition_operator`, `condition_value`, `error_message`, `is_active`, `created_at`) VALUES
(1, 'min_employment_period', 'Employee must be employed for at least 90 days', 'eligibility', 'all', 'hire_date', '<=', 'DATE_SUB(CURDATE(), INTERVAL 90 DAY)', 'Employee has not completed the minimum employment period of 90 days.', 1, '2025-08-21 12:52:14'),
(2, 'max_overtime_amount', 'Overtime compensation cannot exceed $2000 per request', 'compliance', 'overtime', 'amount', '<=', '2000', 'Overtime amount cannot exceed $2000 per request.', 1, '2025-08-21 12:52:14'),
(3, 'max_bonus_amount', 'Bonus compensation cannot exceed 20% of salary', 'compliance', 'bonus', 'amount', '<=', 'salary * 0.2', 'Bonus amount cannot exceed 20% of employee\'s salary.', 1, '2025-08-21 12:52:14'),
(4, 'manager_approval_required', 'Requests over $5000 require special approval', 'compliance', 'all', 'amount', '>', '5000', 'Requests over $5000 require special approval process.', 1, '2025-08-21 12:52:14'),
(5, 'travel_expense_docs', 'Travel expenses require receipts and itinerary', 'document', 'travel_expense', NULL, NULL, NULL, 'Travel expense claims require receipts and travel itinerary documents.', 1, '2025-08-21 12:52:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowances`
--
ALTER TABLE `allowances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_requisition_id` (`job_requisition_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `benefits`
--
ALTER TABLE `benefits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `benefits_usage`
--
ALTER TABLE `benefits_usage`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `benefit_plans`
--
ALTER TABLE `benefit_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_allocation`
--
ALTER TABLE `budget_allocation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `provider_id` (`provider_id`);

--
-- Indexes for table `compensation`
--
ALTER TABLE `compensation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compensation_calculations`
--
ALTER TABLE `compensation_calculations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compensation_records`
--
ALTER TABLE `compensation_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compensation_requests`
--
ALTER TABLE `compensation_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `deduction_rules`
--
ALTER TABLE `deduction_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `pay_period_id` (`pay_period_id`);

--
-- Indexes for table `earnings_calculations`
--
ALTER TABLE `earnings_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `pay_period_id` (`pay_period_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_name` (`first_name`,`last_name`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `employee_assignment_history`
--
ALTER TABLE `employee_assignment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `employee_coverage`
--
ALTER TABLE `employee_coverage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_performance`
--
ALTER TABLE `employee_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `reviewer_id` (`reviewer_id`);

--
-- Indexes for table `hmo_policies`
--
ALTER TABLE `hmo_policies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `holiday_settings`
--
ALTER TABLE `holiday_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incentives`
--
ALTER TABLE `incentives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `incentive_rules`
--
ALTER TABLE `incentive_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `job_requisitions`
--
ALTER TABLE `job_requisitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kpis`
--
ALTER TABLE `kpis`
  ADD PRIMARY KEY (`kpi_id`);

--
-- Indexes for table `loans_deductions`
--
ALTER TABLE `loans_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `night_differential_settings`
--
ALTER TABLE `night_differential_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `onboarding_process`
--
ALTER TABLE `onboarding_process`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `onboarding_schedules`
--
ALTER TABLE `onboarding_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `overtime_rates`
--
ALTER TABLE `overtime_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `pay_periods`
--
ALTER TABLE `pay_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pay_rates`
--
ALTER TABLE `pay_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`review_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `required_documents`
--
ALTER TABLE `required_documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `salary_adjustments`
--
ALTER TABLE `salary_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `salary_assignments`
--
ALTER TABLE `salary_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `salary_grades`
--
ALTER TABLE `salary_grades`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salary_structures`
--
ALTER TABLE `salary_structures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade` (`grade`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `system_profiles`
--
ALTER TABLE `system_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `time_attendance`
--
ALTER TABLE `time_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `training_records`
--
ALTER TABLE `training_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `validation_rules`
--
ALTER TABLE `validation_rules`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allowances`
--
ALTER TABLE `allowances`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benefits`
--
ALTER TABLE `benefits`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `benefits_usage`
--
ALTER TABLE `benefits_usage`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `benefit_plans`
--
ALTER TABLE `benefit_plans`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_allocation`
--
ALTER TABLE `budget_allocation`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `compensation`
--
ALTER TABLE `compensation`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensation_calculations`
--
ALTER TABLE `compensation_calculations`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `compensation_records`
--
ALTER TABLE `compensation_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compensation_requests`
--
ALTER TABLE `compensation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deduction_rules`
--
ALTER TABLE `deduction_rules`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `disbursements`
--
ALTER TABLE `disbursements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `earnings_calculations`
--
ALTER TABLE `earnings_calculations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_assignment_history`
--
ALTER TABLE `employee_assignment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_coverage`
--
ALTER TABLE `employee_coverage`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_performance`
--
ALTER TABLE `employee_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hmo_policies`
--
ALTER TABLE `hmo_policies`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holiday_settings`
--
ALTER TABLE `holiday_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `incentives`
--
ALTER TABLE `incentives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `incentive_rules`
--
ALTER TABLE `incentive_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_requisitions`
--
ALTER TABLE `job_requisitions`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpis`
--
ALTER TABLE `kpis`
  MODIFY `kpi_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans_deductions`
--
ALTER TABLE `loans_deductions`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `night_differential_settings`
--
ALTER TABLE `night_differential_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `onboarding_process`
--
ALTER TABLE `onboarding_process`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_schedules`
--
ALTER TABLE `onboarding_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `overtime_rates`
--
ALTER TABLE `overtime_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pay_periods`
--
ALTER TABLE `pay_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pay_rates`
--
ALTER TABLE `pay_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `required_documents`
--
ALTER TABLE `required_documents`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_adjustments`
--
ALTER TABLE `salary_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `salary_assignments`
--
ALTER TABLE `salary_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `salary_grades`
--
ALTER TABLE `salary_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `salary_structures`
--
ALTER TABLE `salary_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `system_profiles`
--
ALTER TABLE `system_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `time_attendance`
--
ALTER TABLE `time_attendance`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_records`
--
ALTER TABLE `training_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `validation_rules`
--
ALTER TABLE `validation_rules`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`job_requisition_id`) REFERENCES `job_requisitions` (`id`);

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`provider_id`) REFERENCES `providers` (`id`);

--
-- Constraints for table `deductions`
--
ALTER TABLE `deductions`
  ADD CONSTRAINT `deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `disbursements`
--
ALTER TABLE `disbursements`
  ADD CONSTRAINT `disbursements_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disbursements_ibfk_2` FOREIGN KEY (`pay_period_id`) REFERENCES `pay_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `earnings_calculations`
--
ALTER TABLE `earnings_calculations`
  ADD CONSTRAINT `earnings_calculations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `earnings_calculations_ibfk_2` FOREIGN KEY (`pay_period_id`) REFERENCES `pay_periods` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_assignment_history`
--
ALTER TABLE `employee_assignment_history`
  ADD CONSTRAINT `employee_assignment_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_assignment_history_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_assignment_history_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employee_assignment_history_ibfk_4` FOREIGN KEY (`assigned_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_coverage`
--
ALTER TABLE `employee_coverage`
  ADD CONSTRAINT `employee_coverage_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_performance`
--
ALTER TABLE `employee_performance`
  ADD CONSTRAINT `employee_performance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_performance_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`);

--
-- Constraints for table `onboarding_process`
--
ALTER TABLE `onboarding_process`
  ADD CONSTRAINT `onboarding_process_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`);

--
-- Constraints for table `onboarding_schedules`
--
ALTER TABLE `onboarding_schedules`
  ADD CONSTRAINT `onboarding_schedules_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `pay_rates`
--
ALTER TABLE `pay_rates`
  ADD CONSTRAINT `pay_rates_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `salary_adjustments`
--
ALTER TABLE `salary_adjustments`
  ADD CONSTRAINT `salary_adjustments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `salary_assignments`
--
ALTER TABLE `salary_assignments`
  ADD CONSTRAINT `salary_assignments_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `status`
--
ALTER TABLE `status`
  ADD CONSTRAINT `status_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_profiles`
--
ALTER TABLE `system_profiles`
  ADD CONSTRAINT `system_profiles_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_records`
--
ALTER TABLE `training_records`
  ADD CONSTRAINT `training_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
