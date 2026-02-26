-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 26, 2026 at 10:20 AM
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
-- Database: `samadhan_erp_db_2026`
--

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

CREATE TABLE `credit_notes` (
  `credit_note_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_id` bigint(20) NOT NULL,
  `invoice_id` bigint(20) NOT NULL,
  `credit_note_number` varchar(50) NOT NULL,
  `credit_note_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `sub_total` decimal(15,2) DEFAULT 0.00,
  `total_discount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','approved','refunded','adjusted','cancelled') DEFAULT 'draft',
  `gst_type` enum('CGST_SGST','IGST') DEFAULT 'CGST_SGST',
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `adjustment` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_notes`
--

INSERT INTO `credit_notes` (`credit_note_id`, `organization_id`, `customer_id`, `invoice_id`, `credit_note_number`, `credit_note_date`, `reference_no`, `reason`, `sub_total`, `total_discount`, `total_amount`, `status`, `gst_type`, `cgst_amount`, `sgst_amount`, `igst_amount`, `adjustment`, `notes`, `terms_conditions`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 30, 4, 'CN-SAM-0001', '2026-02-18', NULL, '', 36100.00, 1840.00, 39144.00, 'refunded', 'CGST_SGST', 0.00, 0.00, 0.00, 0.00, '', NULL, 13, '2026-02-18 06:08:33', '2026-02-18 06:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `credit_note_history`
--

CREATE TABLE `credit_note_history` (
  `history_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `credit_note_id` bigint(20) NOT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_note_history`
--

INSERT INTO `credit_note_history` (`history_id`, `organization_id`, `credit_note_id`, `action`, `description`, `performed_by`, `created_at`) VALUES
(1, 1, 1, 'created', 'Credit Note Generated', 13, '2026-02-18 06:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `credit_note_items`
--

CREATE TABLE `credit_note_items` (
  `id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `credit_note_id` bigint(20) NOT NULL,
  `item_id` bigint(20) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `hsn_code` varchar(50) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('amount','percentage') DEFAULT 'amount',
  `amount` decimal(15,2) DEFAULT 0.00,
  `gst_rate` decimal(5,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `credit_note_items`
--

INSERT INTO `credit_note_items` (`id`, `organization_id`, `credit_note_id`, `item_id`, `item_name`, `hsn_code`, `unit_id`, `quantity`, `rate`, `discount`, `discount_type`, `amount`, `gst_rate`, `total_amount`, `created_at`) VALUES
(1, 1, 1, 1, 'Commercial Plywood 19mm', '3004', 1, 6.00, 2950.00, 0.00, 'amount', 17700.00, 12.00, 19824.00, '2026-02-18 06:08:33'),
(2, 1, 1, 2, 'BWP Marine Plywood 18mm', '1701', 1, 4.00, 4600.00, 10.00, 'percentage', 18400.00, 5.00, 19320.00, '2026-02-18 06:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `customers_commissions_ledger`
--

CREATE TABLE `customers_commissions_ledger` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `commission_amount` decimal(15,2) DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers_commissions_ledger`
--

INSERT INTO `customers_commissions_ledger` (`id`, `organization_id`, `customer_id`, `invoice_id`, `commission_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 30, 1, 6900.00, NULL, '2026-02-09 17:08:41', '2026-02-09 17:08:41'),
(2, 1, 30, 2, 6900.00, NULL, '2026-02-09 17:12:06', '2026-02-09 17:12:06'),
(3, 1, 36, 4, 5520.00, NULL, '2026-02-16 11:59:44', '2026-02-16 11:59:44'),
(4, 1, 37, 2, 33120.00, NULL, '2026-02-20 12:20:10', '2026-02-20 12:20:10');

-- --------------------------------------------------------

--
-- Table structure for table `customers_commissions_payouts`
--

CREATE TABLE `customers_commissions_payouts` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` varchar(50) DEFAULT 'Cash',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers_ledger`
--

CREATE TABLE `customers_ledger` (
  `ledger_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `particulars` varchar(255) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'invoice, payment, adjustment, opening',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers_ledger`
--

INSERT INTO `customers_ledger` (`ledger_id`, `organization_id`, `customer_id`, `transaction_date`, `particulars`, `debit`, `credit`, `balance`, `reference_id`, `reference_type`, `created_at`) VALUES
(1, 1, 36, '2026-02-09', 'Invoice #INV-SAM-0001 (Ref: 458772)', 48247.50, 0.00, 48247.50, 1, 'invoice', '2026-02-09 17:08:51'),
(2, 1, 36, '2026-02-09', 'Invoice #INV-SAM-0003', 48247.50, 0.00, 96495.00, 2, 'invoice', '2026-02-09 17:12:18'),
(3, 1, 30, '2026-02-09', 'Invoice #INV-SAM-0004 (Ref: 458772)', 33757.50, 0.00, 33757.50, 3, 'invoice', '2026-02-09 17:15:42'),
(4, 1, 30, '2026-02-12', 'Payment Received #PAY-SAM-0001 (Ref: 458772)', 0.00, 10000.00, 23757.50, 1, 'payment', '2026-02-12 15:42:41'),
(5, 1, 30, '2026-02-12', 'Payment Received #PAY-SAM-0002', 0.00, 10000.00, 13757.50, 2, 'payment', '2026-02-12 16:09:17'),
(6, 1, 30, '2026-02-12', 'Payment Received #PAY-PR-SAM-0001 (Ref: 458772)', 0.00, 4000.00, 29757.50, 3, 'payment', '2026-02-12 16:28:22'),
(7, 1, 30, '2026-02-16', 'Invoice #INV-SAM-0005', 76510.00, 0.00, 106267.50, 4, 'invoice', '2026-02-16 11:59:54'),
(8, 1, 30, '2026-02-18', 'Credit Note #CN-SAM-0001 (Against Invoice #4)', 0.00, 39144.00, 67123.50, 1, 'credit_note', '2026-02-18 11:38:33'),
(9, 1, 37, '2026-02-20', 'Invoice #INV-SAM-0001 (Ref: HGHFH)', 3304.00, 0.00, 3304.00, 1, 'invoice', '2026-02-20 11:04:41'),
(10, 1, 38, '2026-02-20', 'Invoice #INV-SAM-0001 (Ref: HGHFH)', 437206.00, 0.00, 437206.00, 2, 'invoice', '2026-02-20 12:20:19'),
(11, 1, 38, '2026-02-21', 'Payment Received #PAY-PR-SAM-0002', 0.00, 437206.00, -437206.00, 4, 'payment', '2026-02-21 10:43:25'),
(12, 1, 38, '2026-02-21', 'Invoice #INV-SAM-0002 (Ref: HGHFH)', 425005.00, 0.00, -12201.00, 3, 'invoice', '2026-02-21 11:55:07'),
(13, 1, 38, '2026-02-23', 'Invoice #INV-SAM-0003 (Ref: HGHFH)', 437206.00, 0.00, 425005.00, 4, 'invoice', '2026-02-23 16:24:40');

-- --------------------------------------------------------

--
-- Table structure for table `customers_listing`
--

CREATE TABLE `customers_listing` (
  `customer_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `customers_type_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `anniversary_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `state_code` varchar(10) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_state` varchar(100) DEFAULT NULL,
  `shipping_state_code` varchar(10) DEFAULT NULL,
  `shipping_pincode` varchar(20) DEFAULT NULL,
  `current_balance_due` decimal(10,2) NOT NULL,
  `loyalty_point_balance` decimal(10,2) NOT NULL,
  `commissions_amount` decimal(15,2) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers_listing`
--

INSERT INTO `customers_listing` (`customer_id`, `organization_id`, `customer_code`, `customers_type_id`, `customer_name`, `company_name`, `email`, `anniversary_date`, `date_of_birth`, `phone`, `gst_number`, `address`, `city`, `state`, `state_code`, `pincode`, `shipping_address`, `shipping_city`, `shipping_state`, `shipping_state_code`, `shipping_pincode`, `current_balance_due`, `loyalty_point_balance`, `commissions_amount`, `avatar`, `created_at`) VALUES
(37, 1, 'CUS-SAM-0001', 1, 'Sunil Kumar', 'WYD', 'soumodeep.official20@gmail.com', '2026-02-20', '2026-02-20', '7059411929', 'CCDR45555', 'BARASAT', 'Kolkata', 'West Bengal', '19', '700124', 'BARASAT', 'Kolkata', 'West Bengal', '19', '700124', 0.00, 500000.00, 0.00, '', '2026-02-20 05:19:59'),
(38, 1, 'CUS-SAM-0002', 2, 'Sandip', 'SKC INFOTECH', 'info.skc@gmail.com', '2026-02-21', '2026-02-20', '7558965255', 'DFDFDFRER', '', '', '', '', '', '', '', '', '', '', 425005.00, 487799.00, 0.00, 'uploads/ORGSAM20260006/customer_avatars/cust_699805078b74c.jpg', '2026-02-20 06:48:57');

-- --------------------------------------------------------

--
-- Table structure for table `customers_type_listing`
--

CREATE TABLE `customers_type_listing` (
  `customers_type_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customers_type_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers_type_listing`
--

INSERT INTO `customers_type_listing` (`customers_type_id`, `organization_id`, `customers_type_name`) VALUES
(1, 1, 'Interior'),
(2, 1, 'Architecture'),
(3, 1, 'Carpenter'),
(8, 1, 'Retail');

-- --------------------------------------------------------

--
-- Table structure for table `debit_notes`
--

CREATE TABLE `debit_notes` (
  `debit_note_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `debit_note_number` varchar(50) NOT NULL,
  `debit_note_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `debit_notes`
--

INSERT INTO `debit_notes` (`debit_note_id`, `organization_id`, `po_id`, `vendor_id`, `debit_note_number`, `debit_note_date`, `remarks`, `created_at`) VALUES
(2, 1, 8, 27, 'DN-SAM-0001', '2026-02-07', 'nngnghfghfgfg', '2026-02-07 10:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `debit_note_items`
--

CREATE TABLE `debit_note_items` (
  `debit_note_item_id` int(11) NOT NULL,
  `debit_note_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `return_qty` decimal(10,2) NOT NULL,
  `return_reason` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `debit_note_items`
--

INSERT INTO `debit_note_items` (`debit_note_item_id`, `debit_note_id`, `po_item_id`, `item_id`, `return_qty`, `return_reason`, `remarks`) VALUES
(10, 2, 27, 1, 12.00, 'Damaged', 'OK'),
(11, 2, 28, 2, 13.00, 'Damaged', 'OK'),
(12, 2, 29, 3, 14.00, 'Damaged', 'OK');

-- --------------------------------------------------------

--
-- Table structure for table `department_listing`
--

CREATE TABLE `department_listing` (
  `department_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `department_slug` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_listing`
--

INSERT INTO `department_listing` (`department_id`, `organization_id`, `department_name`, `department_slug`) VALUES
(1, 1, 'DECORATIVE CATEGORY', 'decorative-category'),
(2, 1, 'VENEER CATEGORY', 'veneer-category'),
(3, 1, 'HARDWAE CATEGORY', 'hardwae-category'),
(4, 1, 'EXTERIOR CATEGORY', 'exterior-category');

-- --------------------------------------------------------

--
-- Table structure for table `department_targets`
--

CREATE TABLE `department_targets` (
  `id` int(11) NOT NULL,
  `monthly_target_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `target_amount` decimal(15,2) DEFAULT NULL,
  `team_member_incentive` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_targets`
--

INSERT INTO `department_targets` (`id`, `monthly_target_id`, `department_id`, `target_amount`, `team_member_incentive`, `created_at`) VALUES
(1, 1, 1, 1641136.25, 26258.18, '2026-02-26 06:30:23'),
(2, 1, 2, 1641136.25, 26258.18, '2026-02-26 06:30:23'),
(3, 1, 3, 1641136.25, 26258.18, '2026-02-26 06:30:23'),
(4, 1, 4, 1641136.25, 26258.18, '2026-02-26 06:30:23');

-- --------------------------------------------------------

--
-- Table structure for table `designation_listing`
--

CREATE TABLE `designation_listing` (
  `designation_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `designation_name` varchar(50) NOT NULL,
  `designation_slug` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designation_listing`
--

INSERT INTO `designation_listing` (`designation_id`, `organization_id`, `designation_name`, `designation_slug`) VALUES
(1, 1, 'Accountant', 'accountant'),
(2, 1, 'App Developer', 'app-developer'),
(4, 1, 'Programmer', 'programmer'),
(5, 1, 'Frontend Developer', 'frontend-developer');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `designation_id` int(11) NOT NULL,
  `employee_code` varchar(50) NOT NULL,
  `salutation` varchar(10) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `primary_email` varchar(150) DEFAULT NULL,
  `alternate_email` varchar(150) DEFAULT NULL,
  `primary_phone` varchar(20) DEFAULT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `joined_on` date DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `aadhar` varchar(20) DEFAULT NULL,
  `voter_id` varchar(20) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `enrollment_type` enum('Intern','Trainee','Probational','Permanent','Associate') NOT NULL,
  `employment_status` varchar(50) DEFAULT 'Joined',
  `notes` text DEFAULT NULL,
  `employee_image` varchar(255) DEFAULT NULL,
  `document_attachment` text DEFAULT NULL,
  `ref_phone_no` varchar(20) DEFAULT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `password_view` varchar(50) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `redirect_url` varchar(255) DEFAULT 'dashboard',
  `total_incentive_earned` decimal(15,2) DEFAULT 0.00,
  `current_incentive_balance` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `organization_id`, `department_id`, `role_id`, `designation_id`, `employee_code`, `salutation`, `first_name`, `last_name`, `gender`, `primary_email`, `alternate_email`, `primary_phone`, `alternate_phone`, `date_of_birth`, `joined_on`, `pan`, `aadhar`, `voter_id`, `father_name`, `mother_name`, `emergency_contact_name`, `emergency_contact_phone`, `enrollment_type`, `employment_status`, `notes`, `employee_image`, `document_attachment`, `ref_phone_no`, `blood_group`, `password`, `password_view`, `remember_token`, `is_active`, `created_at`, `updated_at`, `redirect_url`, `total_incentive_earned`, `current_incentive_balance`) VALUES
(1, 1, 2, 1, 4, 'EMP-SAM-0001', 'Mr.', 'Soumodeep', 'Mondal', 'Male', 'soumodeep.official20@gmail.com', 'skc@gmail.com', '785988474', '785988474', '2019-01-28', '2026-01-12', 'AADCS7369C', '3434343466', '23323233', 'Michael Doe', 'JANE DOE', '232244', '545454545', 'Permanent', 'Hired', 'yrdgdgddffefsef', 'EMPSAM001_1768827581.jpg', NULL, '', '', '$2y$10$YrFrne5TDP7lbddkESbce.ruEESxY52xFV9PZ1tcWtj48QYcKP5i6', 'superadmin', NULL, 1, '2026-01-12 07:01:36', '2026-02-21 10:59:11', 'dashboard', 0.00, 0.00),
(2, 1, 2, 2, 2, 'EMP-SAM-0002', 'Mr.', 'Prabir Kumar', 'Jana', 'Male', 'prabir@gmail.com', 'prabir_alt@gmail.com', '785988474', '34456666', '2026-01-12', '2026-01-13', 'AADCS7369C', '3434343466', '23323233', 'MICHAEL DOE', 'JANE DOE', '232244', '5454545666', 'Intern', 'Hired', 'yrthfhbfbnf', 'EMPSAM002_1768827551.jpg', NULL, '', '', '$2y$10$sG/LNXzV5IajRz4hVQFm0.SAnRL2lFb.S/qmFlxwfqtMAUUn3qFvi', 'admin', NULL, 1, '2026-01-12 08:59:05', '2026-02-21 10:59:14', 'vendors', 0.00, 0.00),
(3, 1, 2, 4, 2, 'EMP-SAM-0003', 'Mr.', 'Soumojit', 'Khan', 'Male', 'soumojit@gmail.com', 'soumojit_alt@gmail.com', '785988474', '5454677888', '2026-01-14', '2026-01-14', 'AADCS7369C', '3434343466', '23323233', 'MICHAEL DOE', ' Rita Kumar Shaw', '2322445555', '77656454545', 'Permanent', 'Hired', '', 'EMPSAM003_1768827591.jpg', NULL, '', '', '$2y$10$qMyEP0Tv7KHOafJePUO3j.K0cGvfiunZ144g4bwrgKcsTqWfHXZPu', 'admin', NULL, 1, '2026-01-14 11:06:02', '2026-02-21 10:59:16', 'dashboard', 0.00, 0.00),
(4, 1, 2, 3, 4, 'EMP-SAM-0004', 'Mr.', 'Sandip', 'Dalui', 'Male', 'sandip@gmail.com', '', '785988474', '785988474', '2026-01-20', NULL, '', '', '', '', '', '', '', 'Permanent', 'Hired', '', 'EMPSAM004_1768827570.jpg', NULL, '', '', '$2y$10$Bb2onZhal8R7bjiNZ9AO5OpKSc8yRLuPSEAircDgRAtP8zX/08qgC', 'admin', NULL, 1, '2026-01-14 11:28:20', '2026-02-21 10:59:18', 'dashboard', 0.00, 0.00),
(13, 1, 1, 1, 2, 'EMP-SAM-0005', 'Mr.', 'Manish', 'Gupta', '', 'manishsbpl.ods@gmail.com', '', '09999999999', '', '2026-01-16', NULL, '', '', '', '', '', '', '', '', 'Hired', '', 'EMPSAM005_1768827498.webp', 'EMPSAM005_1768827530.pdf', '', '', '$2y$10$YrFrne5TDP7lbddkESbce.ruEESxY52xFV9PZ1tcWtj48QYcKP5i6', 'superadmin', NULL, 1, '2026-01-16 04:54:59', '2026-02-21 10:59:21', 'dashboard', 0.00, 0.00),
(24, 1, 2, 4, 2, 'EMP-SAM-0006', 'Mr.', 'SKC', 'info', 'Male', 'soumodeep20@gmail.com', '', '', '', '2026-02-14', NULL, '', '', '', '', '', '', '', 'Permanent', 'Hired', '', '', '', '', '', '$2y$10$R4gqbz/o8M5sThmc9XiYjumt5kH9YGthtpZD3gpyTYDmuW42HDkY6', '14022026', NULL, 1, '2026-02-16 06:45:25', '2026-02-21 10:59:23', 'dashboard', 0.00, 0.00),
(25, 1, 2, 4, 2, 'EMP-SAM-0007', 'Mr.', 'ASISH', 'MONDAL', 'Male', 'asish@gmail.com', '', '', '', '2026-02-21', NULL, '', '', '', '', '', '', '', 'Permanent', 'Hired', '', '', '', '', '', '$2y$10$gTvL70RQiihBx63J0Y92/ujJKPuSQLykPJdUwH4uit8cNsfsApgsS', '21022026', NULL, 1, '2026-02-21 04:52:23', '2026-02-21 07:39:10', 'dashboard', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `employees_temp`
--

CREATE TABLE `employees_temp` (
  `employees_temp_id` bigint(20) NOT NULL,
  `salutation` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `primary_email` varchar(150) DEFAULT NULL,
  `primary_phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_view` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_addresses`
--

CREATE TABLE `employee_addresses` (
  `employee_addresses_id` bigint(20) NOT NULL,
  `employee_id` bigint(20) NOT NULL,
  `current_street` text DEFAULT NULL,
  `current_city` varchar(100) DEFAULT NULL,
  `current_district` varchar(100) DEFAULT NULL,
  `current_state` varchar(100) DEFAULT NULL,
  `current_country` varchar(100) DEFAULT NULL,
  `current_pincode` varchar(10) DEFAULT NULL,
  `permanent_street` text DEFAULT NULL,
  `permanent_city` varchar(100) DEFAULT NULL,
  `permanent_district` varchar(100) DEFAULT NULL,
  `permanent_state` varchar(100) DEFAULT NULL,
  `permanent_country` varchar(100) DEFAULT NULL,
  `permanent_pincode` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_addresses`
--

INSERT INTO `employee_addresses` (`employee_addresses_id`, `employee_id`, `current_street`, `current_city`, `current_district`, `current_state`, `current_country`, `current_pincode`, `permanent_street`, `permanent_city`, `permanent_district`, `permanent_state`, `permanent_country`, `permanent_pincode`) VALUES
(1, 1, 'Chapadali', 'Barasat', 'S4P', 'WB', 'India', '700124', 'Chapadali', 'Barasat', 'S4P', 'WB', 'India', '700124'),
(2, 2, 'Chapadali', 'Barasat', 'S4P', 'WB', 'India', '700124', 'Chapadali', 'Barasat', 'S4P', 'WB', 'India', '700124'),
(3, 4, '', '', '', '', 'India', '', '', '', '', '', 'India', ''),
(4, 3, '', '', '', '', 'India', '', '', '', '', '', 'India', ''),
(7, 10, 'Chapadali', 'Barasat', 'S4P', 'WB', 'India', '700124', 'Chapadali', 'Barasat', 'S4P', 'WB', 'India', '700124'),
(9, 13, '', '', '', '', 'India', '', '', '', '', '', 'India', ''),
(13, 25, '', '', '', '', 'India', '', '', '', '', '', 'India', ''),
(14, 24, '', '', '', '', 'India', '', '', '', '', '', 'India', '');

-- --------------------------------------------------------

--
-- Table structure for table `employee_bank_details`
--

CREATE TABLE `employee_bank_details` (
  `id` bigint(20) NOT NULL,
  `employee_id` bigint(20) NOT NULL,
  `bank_name` varchar(150) DEFAULT NULL,
  `branch_name` varchar(150) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_type` enum('Savings','Current') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_bank_details`
--

INSERT INTO `employee_bank_details` (`id`, `employee_id`, `bank_name`, `branch_name`, `ifsc_code`, `account_number`, `account_type`) VALUES
(1, 1, 'STATE BANK OF INDIA', 'DURGAPUR', 'SBIN56589', '149895623233', 'Savings'),
(2, 2, 'BANK OF BARODA', 'Salt Lake Sector 1 Branch', 'SBIN56589', '1234567890', 'Savings'),
(3, 4, '', '', '', '', 'Savings'),
(4, 3, '', '', '', '', 'Savings'),
(7, 10, 'STATE BANK OF INDIA', 'Salt Lake Sector 1 Branch', 'SBIN56589', '12365690', 'Savings'),
(9, 13, '', '', '', '', 'Savings'),
(13, 25, '', '', '', '', 'Savings'),
(14, 24, '', '', '', '', 'Savings');

-- --------------------------------------------------------

--
-- Table structure for table `employee_permissions`
--

CREATE TABLE `employee_permissions` (
  `permission_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `module_slug` varchar(255) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_permissions`
--

INSERT INTO `employee_permissions` (`permission_id`, `employee_id`, `module_slug`, `can_view`, `can_add`, `can_edit`, `can_delete`) VALUES
(210, 2, 'grn_backdate', 1, 1, 1, 1),
(211, 2, 'goods_received_notes', 1, 1, 1, 1),
(216, 1, 'access_control', 1, 1, 1, 1),
(217, 1, 'add_targets', 1, 1, 1, 1),
(218, 1, 'credit_note', 1, 1, 0, 0),
(219, 1, 'payment_received', 1, 1, 1, 1),
(448, 25, 'designation_listing', 1, 1, 1, 1),
(449, 25, 'discount_report', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `goods_received_notes`
--

CREATE TABLE `goods_received_notes` (
  `grn_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `po_id` bigint(20) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `grn_number` varchar(50) NOT NULL,
  `grn_date` date NOT NULL,
  `challan_no` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_received_notes`
--

INSERT INTO `goods_received_notes` (`grn_id`, `organization_id`, `po_id`, `vendor_id`, `grn_number`, `grn_date`, `challan_no`, `remarks`, `created_at`) VALUES
(5, 1, 8, 33, 'GRN-SAM-0001', '2026-02-20', '', '', '2026-02-20 07:37:30'),
(6, 1, 10, 33, 'GRN-SAM-0002', '2026-02-23', '', '', '2026-02-23 09:03:44');

-- --------------------------------------------------------

--
-- Table structure for table `goods_received_note_items`
--

CREATE TABLE `goods_received_note_items` (
  `grn_item_id` bigint(20) NOT NULL,
  `grn_id` bigint(20) NOT NULL,
  `po_item_id` bigint(20) NOT NULL,
  `item_id` bigint(20) DEFAULT NULL,
  `ordered_qty` decimal(10,2) DEFAULT 0.00,
  `received_qty` decimal(10,2) DEFAULT 0.00,
  `condition_status` varchar(50) DEFAULT 'Good',
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `goods_received_note_items`
--

INSERT INTO `goods_received_note_items` (`grn_item_id`, `grn_id`, `po_item_id`, `item_id`, `ordered_qty`, `received_qty`, `condition_status`, `remarks`) VALUES
(8, 5, 26, 1, 10.00, 5.00, 'Good', ''),
(9, 6, 29, 4, 30.00, 30.00, 'Good', '');

-- --------------------------------------------------------

--
-- Table structure for table `hsn_listing`
--

CREATE TABLE `hsn_listing` (
  `hsn_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `hsn_code` varchar(10) NOT NULL,
  `description` text DEFAULT NULL,
  `gst_rate` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hsn_listing`
--

INSERT INTO `hsn_listing` (`hsn_id`, `organization_id`, `hsn_code`, `description`, `gst_rate`, `created_at`, `updated_at`) VALUES
(1, 1, '1001', 'Wheat and meslin', 28.00, '2026-01-19 08:51:10', '2026-01-20 12:09:05'),
(2, 1, '1006', 'Rice (non-branded)', 0.00, '2026-01-19 08:51:29', '2026-01-20 12:09:07'),
(3, 1, '2106', 'Ready-to-eat packaged foods', 12.00, '2026-01-19 08:51:29', '2026-01-20 12:09:08'),
(4, 1, '0402', 'Milk powder', 5.00, '2026-01-19 08:51:29', '2026-01-20 12:09:09'),
(5, 1, '0709', 'Fresh vegetables', 0.00, '2026-01-19 08:51:29', '2026-01-20 12:09:11'),
(6, 1, '2202', 'Soft drinks', 28.00, '2026-01-19 08:51:29', '2026-01-20 12:09:12'),
(7, 1, '1701', 'Sugar', 5.00, '2026-01-19 08:51:29', '2026-01-20 12:09:13'),
(8, 1, '3401', 'Soap and detergents', 18.00, '2026-01-19 08:51:29', '2026-01-20 12:09:15'),
(9, 1, '3004', 'Medicines', 12.00, '2026-01-19 08:51:29', '2026-01-20 12:17:05'),
(10, 1, '8528', 'Televisions', 28.00, '2026-01-19 08:51:29', '2026-01-20 12:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `incentive_ledger`
--

CREATE TABLE `incentive_ledger` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `monthly_target_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `distribution_type` enum('manager','team','manual','payout') NOT NULL,
  `distribution_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items_listing`
--

CREATE TABLE `items_listing` (
  `item_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `item_name` varchar(200) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `hsn_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `stock_keeping_unit` varchar(100) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `mrp` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `current_stock` decimal(15,2) DEFAULT 0.00,
  `opening_stock` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items_listing`
--

INSERT INTO `items_listing` (`item_id`, `organization_id`, `item_name`, `brand`, `hsn_id`, `description`, `stock_keeping_unit`, `unit_id`, `mrp`, `selling_price`, `create_at`, `update_at`, `current_stock`, `opening_stock`) VALUES
(1, 1, 'Commercial Plywood 19mm', 'Greenply', 9, '19mm commercial plywood sheet 8x4', 'PLY-GP-19-001', 1, 3200.00, 2950.00, '2026-01-15 05:26:47', '2026-02-23 10:54:30', -136.00, 0.00),
(2, 1, 'BWP Marine Plywood 18mm', 'Century Ply', 7, 'Waterproof marine plywood 8x4', 'PLY-CP-18-002', 1, 4800.00, 4600.00, '2026-01-15 05:26:47', '2026-02-23 10:54:30', -160.00, 0.00),
(3, 1, 'Flush Door 30mm', 'Greenpanel', 9, '30mm flush door waterproof', 'DOOR-GP-30-003', 1, 5200.00, 4900.00, '2026-01-15 05:26:47', '2026-02-10 10:08:38', 0.00, 0.00),
(4, 1, 'Decorative Laminate Sheet', 'Sunmica', 8, '1mm decorative laminate glossy finish', 'LAM-SUN-01', 1, 1350.00, 1250.00, '2026-01-15 05:26:47', '2026-02-23 09:03:44', 30.00, 0.00),
(5, 1, 'Acrylic Laminate Sheet', 'Merino', 8, 'High gloss acrylic laminate', 'LAM-MER-02', 1, 4200.00, 3950.00, '2026-01-15 05:26:47', '2026-02-17 07:18:36', 50.00, 50.00),
(6, 1, 'Block Board 19mm', 'Kitply', 1, '19mm block board sheet 8x4', 'BB-KIT-19-004', 1, 3600.00, 3350.00, '2026-01-15 05:26:47', '2026-02-07 04:41:10', 0.00, 0.00),
(7, 1, 'PVC Laminate Sheet', 'Royal Touch', 9, 'PVC laminate waterproof sheet', 'LAM-PVC-05', 1, 2200.00, 2050.00, '2026-01-15 05:26:47', '2026-02-07 04:41:10', 0.00, 0.00),
(8, 1, 'Wooden Louver Panel', 'Local', 10, 'Decorative wooden louver panel', 'LOU-WOOD-01', 1, 2800.00, 2600.00, '2026-01-15 05:26:47', '2026-02-07 04:41:10', 0.00, 0.00),
(15, 1, 'Edge Band Tape 22mm', 'Local', 4, 'r45454', 'LOU-WOOD-0147', 1, 55.00, 285.00, '2026-02-05 09:44:09', '2026-02-07 04:41:10', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `item_commissions`
--

CREATE TABLE `item_commissions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `customers_type_id` int(11) NOT NULL,
  `commission_percentage` decimal(5,2) DEFAULT 0.00,
  `organization_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_commissions`
--

INSERT INTO `item_commissions` (`id`, `item_id`, `customers_type_id`, `commission_percentage`, `organization_id`) VALUES
(46, 6, 1, 5.00, 1),
(47, 6, 2, 15.00, 1),
(48, 6, 3, 20.00, 1),
(49, 6, 8, 0.00, 1),
(50, 2, 1, 12.00, 1),
(51, 2, 2, 15.00, 1),
(52, 2, 3, 0.00, 1),
(53, 2, 8, 0.00, 1),
(58, 4, 1, 0.00, 1),
(59, 4, 2, 0.00, 1),
(60, 4, 3, 0.00, 1),
(61, 4, 8, 0.00, 1),
(62, 15, 1, 5.00, 1),
(63, 15, 2, 0.00, 1),
(64, 15, 3, 0.00, 1),
(65, 15, 8, 0.00, 1),
(66, 3, 1, 0.00, 1),
(67, 3, 2, 0.00, 1),
(68, 3, 3, 0.00, 1),
(69, 3, 8, 0.00, 1),
(70, 7, 1, 0.00, 1),
(71, 7, 2, 0.00, 1),
(72, 7, 3, 0.00, 1),
(73, 7, 8, 0.00, 1),
(74, 8, 1, 0.00, 1),
(75, 8, 2, 0.00, 1),
(76, 8, 3, 0.00, 1),
(77, 8, 8, 0.00, 1),
(78, 1, 1, 0.00, 1),
(79, 1, 2, 2.00, 1),
(80, 1, 3, 0.00, 1),
(81, 1, 8, 0.00, 1),
(82, 5, 1, 5.00, 1),
(83, 5, 2, 6.00, 1),
(84, 5, 3, 7.00, 1),
(85, 5, 8, 8.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_points_earned`
--

CREATE TABLE `loyalty_points_earned` (
  `loyalty_point_id` bigint(20) NOT NULL,
  `organization_id` bigint(20) NOT NULL,
  `customer_id` bigint(20) NOT NULL,
  `slab_id` bigint(20) NOT NULL,
  `invoice_id` bigint(20) NOT NULL,
  `bill_amount` decimal(10,2) DEFAULT NULL,
  `points_earned` int(11) NOT NULL,
  `points_remaining` int(11) DEFAULT 0,
  `valid_till` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_points_earned`
--

INSERT INTO `loyalty_points_earned` (`loyalty_point_id`, `organization_id`, `customer_id`, `slab_id`, `invoice_id`, `bill_amount`, `points_earned`, `points_remaining`, `valid_till`, `created_at`) VALUES
(8, 1, 38, 4, 4, 437206.00, 12201, 0, '2026-02-22', '2026-02-23 10:54:30');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_point_slabs`
--

CREATE TABLE `loyalty_point_slabs` (
  `slab_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `slab_no` varchar(50) NOT NULL,
  `from_sale_amount` decimal(15,2) NOT NULL,
  `to_sale_amount` decimal(15,2) NOT NULL,
  `points_per_100_rupees` int(11) NOT NULL DEFAULT 0,
  `valid_for_days` int(11) NOT NULL DEFAULT 365,
  `applicable_from_date` date NOT NULL,
  `applicable_to_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_point_slabs`
--

INSERT INTO `loyalty_point_slabs` (`slab_id`, `organization_id`, `slab_no`, `from_sale_amount`, `to_sale_amount`, `points_per_100_rupees`, `valid_for_days`, `applicable_from_date`, `applicable_to_date`, `created_at`, `updated_at`) VALUES
(2, 1, 'SLAB-SAM-001', 1.00, 10000.00, 1, 334, '2026-01-01', '2026-12-01', '2026-01-21 11:23:20', '2026-01-22 10:38:57'),
(3, 1, 'SLAB-SAM-002', 10000.00, 50000.00, 2, 37, '2026-01-22', '2026-02-28', '2026-01-22 10:39:26', '2026-02-05 05:06:55'),
(4, 1, 'SLAB-SAM-003', 50000.00, 100000.00, 3, 37, '2026-01-22', '2026-02-28', '2026-01-22 10:39:41', '2026-02-05 05:07:13');

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_point_transactions`
--

CREATE TABLE `loyalty_point_transactions` (
  `transaction_id` bigint(20) NOT NULL,
  `organization_id` bigint(20) NOT NULL,
  `customer_id` bigint(20) NOT NULL,
  `invoice_id` bigint(20) DEFAULT NULL,
  `transaction_type` enum('EARN','REDEEM','ADJUST','EXPIRED') NOT NULL,
  `points` int(11) NOT NULL,
  `balance_after_transaction` int(11) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loyalty_point_transactions`
--

INSERT INTO `loyalty_point_transactions` (`transaction_id`, `organization_id`, `customer_id`, `invoice_id`, `transaction_type`, `points`, `balance_after_transaction`, `expiry_date`, `note`, `created_at`) VALUES
(18, 1, 38, NULL, 'EXPIRED', 12201, 487799, '2026-02-23', 'Points Expired', '2026-02-23 11:14:26');

-- --------------------------------------------------------

--
-- Table structure for table `monthly_targets`
--

CREATE TABLE `monthly_targets` (
  `id` int(11) NOT NULL,
  `month` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `total_target` decimal(15,2) DEFAULT NULL,
  `incentive_percent` decimal(5,2) DEFAULT NULL,
  `manager_share_percent` decimal(5,2) DEFAULT 20.00,
  `manager_roles` text DEFAULT NULL,
  `team_share_percent` decimal(5,2) DEFAULT 80.00,
  `distributed` enum('0','1') NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `monthly_targets`
--

INSERT INTO `monthly_targets` (`id`, `month`, `year`, `total_target`, `incentive_percent`, `manager_share_percent`, `manager_roles`, `team_share_percent`, `distributed`, `created_at`) VALUES
(1, 'February', 2026, 6564545.00, 2.00, 20.00, '[\"2\",\"3\"]', 80.00, '0', '2026-02-26 06:30:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error','reminder','system') DEFAULT 'info',
  `icon` varchar(200) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  `send_by` bigint(20) UNSIGNED DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'low'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `organization_id` bigint(20) NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `organizations_code` varchar(25) DEFAULT NULL,
  `organization_short_code` varchar(10) NOT NULL,
  `organization_logo` varchar(255) NOT NULL,
  `industry` varchar(150) DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `state_code` varchar(10) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `currency_code` varchar(10) NOT NULL DEFAULT 'INR',
  `language` varchar(50) NOT NULL DEFAULT 'English',
  `timezone` varchar(100) NOT NULL DEFAULT 'Asia/Kolkata',
  `gst_registered` tinyint(1) DEFAULT 0,
  `gst_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `account_holder_name` varchar(100) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `qr_code` varchar(100) NOT NULL,
  `upi_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`organization_id`, `organization_name`, `organizations_code`, `organization_short_code`, `organization_logo`, `industry`, `country`, `state`, `state_code`, `city`, `address`, `pincode`, `currency_code`, `language`, `timezone`, `gst_registered`, `gst_number`, `email`, `phone`, `bank_name`, `account_number`, `ifsc_code`, `account_holder_name`, `branch_name`, `qr_code`, `upi_id`, `created_at`, `updated_at`) VALUES
(1, 'SAMADHAN', 'ORGSAM20260006', 'SAM', 'logo_696ddccf4787a9.59884566.jpg', 'Manufacturing', 'India', 'West Bengal', '19', 'BARASAT', '21/4 K.N.C. ROAD ,NABAPALLI PARA, P.O.BARASAT, KOLKATA 124, WEST BENGAL', '700124', 'INR', 'en', 'Asia/Kolkata', 1, 'DDEF456565GBFGF', 'info@samadhan@gmail.com', '033 544782', 'ICICI Bank', '192905002326', 'ICIC0001929', 'SAMADHAN', 'S. F. ROAD', 'qrcode_samadhan.png', 'MSSAMADHAN.eazypay1@icici', '2026-01-19 07:27:11', '2026-02-21 09:31:18');

-- --------------------------------------------------------

--
-- Table structure for table `payment_made`
--

CREATE TABLE `payment_made` (
  `payment_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_made`
--

INSERT INTO `payment_made` (`payment_id`, `organization_id`, `vendor_id`, `payment_number`, `payment_date`, `payment_mode`, `reference_no`, `amount`, `notes`, `created_by`, `created_at`) VALUES
(7, 1, 34, 'PAY-PM-SAM-0001', '2026-02-24', 'Cheque', 'HGHFH', 10200.00, '5454', 13, '2026-02-24 05:10:48');

-- --------------------------------------------------------

--
-- Table structure for table `payment_received`
--

CREATE TABLE `payment_received` (
  `payment_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_id` bigint(20) NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` varchar(50) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `item_type` enum('invoice','advance') NOT NULL DEFAULT 'advance',
  `invoice_id` bigint(20) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_received`
--

INSERT INTO `payment_received` (`payment_id`, `organization_id`, `customer_id`, `payment_number`, `payment_date`, `payment_mode`, `reference_no`, `amount`, `item_type`, `invoice_id`, `notes`, `created_by`, `created_at`) VALUES
(3, 1, 30, 'PAY-PR-SAM-0001', '2026-02-12', 'Cash', '458772', 4000.00, 'invoice', 3, '', 13, '2026-02-12 10:58:22'),
(4, 1, 38, 'PAY-PR-SAM-0002', '2026-02-21', 'Cash', '', 437206.00, 'invoice', 2, '', 1, '2026-02-21 05:13:25');

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoices`
--

CREATE TABLE `proforma_invoices` (
  `proforma_invoice_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `make_employee_id` int(11) NOT NULL,
  `sales_employee_id` int(11) NOT NULL,
  `delivery_mode` varchar(50) NOT NULL,
  `proforma_invoice_number` varchar(50) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `sub_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'sent',
  `notes` text DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `adjustment` decimal(10,2) DEFAULT 0.00,
  `gst_type` enum('CGST_SGST','IGST') DEFAULT 'CGST_SGST',
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proforma_invoices`
--

INSERT INTO `proforma_invoices` (`proforma_invoice_id`, `organization_id`, `customer_id`, `make_employee_id`, `sales_employee_id`, `delivery_mode`, `proforma_invoice_number`, `reference_no`, `invoice_date`, `payment_terms`, `sub_total`, `total_amount`, `status`, `notes`, `terms_conditions`, `adjustment`, `gst_type`, `cgst_amount`, `sgst_amount`, `igst_amount`, `created_at`, `updated_at`) VALUES
(2, 1, 38, 13, 2, 'road', 'PRO-SAM-0001', 'HGHFH', '2026-02-20', 'Due on Receipt', 423500.00, 437206.00, 'sent', '', '', 0.00, 'CGST_SGST', 15228.00, 15228.00, 0.00, '2026-02-20 12:19:22', '2026-02-20 12:19:22'),
(3, 1, 37, 1, 2, 'road', 'PRO-SAM-0002', 'HGHFH', '2026-02-21', 'Net 15', 29500.00, 33040.00, 'sent', '', '', 0.00, 'CGST_SGST', 1770.00, 1770.00, 0.00, '2026-02-21 14:23:34', '2026-02-21 14:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoice_items`
--

CREATE TABLE `proforma_invoice_items` (
  `item_row_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `proforma_invoice_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `hsn_code` varchar(50) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `rate` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('amount','percentage') DEFAULT 'amount',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `gst_rate` decimal(5,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `proforma_invoice_items`
--

INSERT INTO `proforma_invoice_items` (`item_row_id`, `organization_id`, `proforma_invoice_id`, `item_id`, `item_name`, `hsn_code`, `unit_id`, `quantity`, `rate`, `discount`, `discount_type`, `amount`, `gst_rate`, `total_amount`, `created_at`) VALUES
(7, 1, 2, 1, 'Commercial Plywood 19mm', '3004', 1, 50.00, 2950.00, 2.00, 'percentage', 144550.00, 12.00, 161896.00, '2026-02-20 06:49:22'),
(8, 1, 2, 2, 'BWP Marine Plywood 18mm', '1701', 1, 60.00, 4600.00, 5.00, 'percentage', 262200.00, 5.00, 275310.00, '2026-02-20 06:49:22'),
(9, 1, 3, 1, 'Commercial Plywood 19mm', '3004', 1, 10.00, 2950.00, 0.00, 'amount', 29500.00, 12.00, 33040.00, '2026-02-21 08:53:34');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `purchase_orders_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `delivery_address_type` enum('organization','customer') DEFAULT 'organization',
  `delivery_address_text` text DEFAULT NULL,
  `po_number` varchar(50) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `order_date` date NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `shipment_preference` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `sub_total` decimal(15,2) DEFAULT 0.00,
  `discount_type` enum('percentage','amount') DEFAULT 'percentage',
  `discount_value` decimal(10,2) DEFAULT 0.00,
  `adjustment` decimal(10,2) DEFAULT 0.00,
  `gst_type` varchar(50) DEFAULT NULL,
  `gst_rate` decimal(10,2) DEFAULT 0.00,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('draft','sent','confirmed','cancelled','partially_received','received') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`purchase_orders_id`, `organization_id`, `vendor_id`, `delivery_address_type`, `delivery_address_text`, `po_number`, `reference_no`, `order_date`, `delivery_date`, `payment_terms`, `payment_date`, `shipment_preference`, `notes`, `terms_conditions`, `sub_total`, `discount_type`, `discount_value`, `adjustment`, `gst_type`, `gst_rate`, `cgst_amount`, `sgst_amount`, `igst_amount`, `total_amount`, `status`, `created_at`, `updated_at`) VALUES
(8, 1, 33, 'organization', NULL, 'PO-SAM-0001', 'HGHFH', '2026-02-20', '2026-02-28', 'Due on Receipt', NULL, NULL, '', '', 28025.00, 'amount', 0.00, 0.00, '', 0.00, 0.00, 0.00, 0.00, 28025.00, 'partially_received', '2026-02-20 07:36:46', '2026-02-20 07:37:30'),
(9, 1, 33, 'organization', NULL, 'PO-SAM-0002', 'HGHFH', '2026-02-23', '2026-02-19', 'Net 15', '2026-02-24', NULL, '', '', 12500.00, 'amount', 0.00, 0.00, '', 0.00, 0.00, 0.00, 0.00, 12500.00, 'cancelled', '2026-02-23 09:01:07', '2026-02-23 09:52:25'),
(10, 1, 33, 'organization', NULL, 'PO-SAM-0003', '', '2026-02-23', NULL, 'Due on Receipt', NULL, NULL, '', '', 45000.00, 'amount', 0.00, 0.00, '', 0.00, 0.00, 0.00, 0.00, 45000.00, 'received', '2026-02-23 09:01:53', '2026-02-23 09:03:44'),
(11, 1, 33, 'organization', NULL, 'PO-SAM-0004', 'HGHFH', '2026-02-01', NULL, 'Net 30', '2026-02-23', NULL, '', '', 2600.00, 'amount', 0.00, 0.00, '', 0.00, 0.00, 0.00, 0.00, 2600.00, 'sent', '2026-02-23 09:22:32', '2026-02-23 09:55:11');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_activity_logs`
--

CREATE TABLE `purchase_order_activity_logs` (
  `po_logs_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `purchase_order_id` bigint(20) NOT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `performed_by` bigint(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_activity_logs`
--

INSERT INTO `purchase_order_activity_logs` (`po_logs_id`, `organization_id`, `purchase_order_id`, `action`, `description`, `performed_by`, `created_at`) VALUES
(25, 1, 8, 'created', 'Purchase Order Created', 1, '2026-02-20 07:36:46'),
(26, 0, 8, 'status_update', 'Status updated to Confirmed', 1, '2026-02-20 07:37:04'),
(27, 1, 8, 'updated', 'Purchase Order Updated', 1, '2026-02-20 07:37:15'),
(28, 1, 9, 'created', 'Purchase Order Created', 1, '2026-02-23 09:01:07'),
(29, 1, 10, 'created', 'Purchase Order Created', 1, '2026-02-23 09:01:53'),
(30, 0, 10, 'status_update', 'Status updated to Confirmed', 1, '2026-02-23 09:03:22'),
(31, 1, 10, 'updated', 'Purchase Order Updated', 1, '2026-02-23 09:03:38'),
(32, 0, 9, 'status_update', 'Status updated to Cancelled', 1, '2026-02-23 09:17:43'),
(33, 1, 9, 'updated', 'Purchase Order Updated', 1, '2026-02-23 09:17:43'),
(34, 1, 9, 'updated', 'Purchase Order Updated', 1, '2026-02-23 09:17:59'),
(35, 1, 11, 'created', 'Purchase Order Created', 1, '2026-02-23 09:22:32');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_files`
--

CREATE TABLE `purchase_order_files` (
  `files_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `purchase_order_id` bigint(20) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `purchase_order_id` bigint(20) NOT NULL,
  `item_id` bigint(20) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `unit_id` varchar(20) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL,
  `discount_type` varchar(20) DEFAULT 'amount',
  `amount` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `organization_id`, `purchase_order_id`, `item_id`, `item_name`, `quantity`, `unit_id`, `rate`, `discount`, `discount_type`, `amount`, `created_at`) VALUES
(26, 1, 8, 1, 'Commercial Plywood 19mm', 10.00, '1', 2950.00, 5.00, 'percentage', 28025.00, '2026-02-20 07:37:15'),
(29, 1, 10, 4, 'Decorative Laminate Sheet', 30.00, '1', 1500.00, 0.00, 'amount', 45000.00, '2026-02-23 09:03:38'),
(31, 1, 9, 4, 'Decorative Laminate Sheet', 10.00, '1', 1250.00, 0.00, 'amount', 12500.00, '2026-02-23 09:17:59'),
(32, 1, 11, 8, 'Wooden Louver Panel', 1.00, '1', 2600.00, 0.00, 'amount', 2600.00, '2026-02-23 09:22:32');

-- --------------------------------------------------------

--
-- Table structure for table `roles_listing`
--

CREATE TABLE `roles_listing` (
  `role_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_slug` varchar(50) NOT NULL,
  `is_active` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles_listing`
--

INSERT INTO `roles_listing` (`role_id`, `organization_id`, `role_name`, `role_slug`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'SUPER ADMIN', 'super-admin', 1, '2026-01-08 09:29:23', '2026-01-20 08:59:42'),
(2, 1, 'ADMIN', 'admin', 1, '2026-01-08 09:29:27', '2026-01-20 09:07:18'),
(3, 1, 'MANAGER', 'manager', 1, '2026-01-08 09:29:33', '2026-01-20 09:07:20'),
(4, 1, 'EMPLOYEE', 'employee', 1, '2026-01-08 09:30:05', '2026-01-20 09:07:22');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `invoice_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `proforma_invoice_id` int(11) DEFAULT 0,
  `customer_id` bigint(20) NOT NULL,
  `make_employee_id` int(11) NOT NULL,
  `sales_employee_id` int(11) DEFAULT 0,
  `delivery_mode` varchar(255) DEFAULT NULL,
  `reference_customer_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `payment_terms` varchar(50) DEFAULT NULL,
  `discount_type` enum('amount','percentage') DEFAULT 'amount',
  `discount_value` decimal(15,2) DEFAULT 0.00,
  `sub_total` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `balance_due` decimal(15,2) NOT NULL,
  `status` enum('draft','sent','paid','overdue','cancelled','refunded','approved') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `adjustment` decimal(10,2) DEFAULT 0.00,
  `gst_type` enum('CGST_SGST','IGST') DEFAULT NULL,
  `cgst_amount` decimal(10,2) DEFAULT 0.00,
  `sgst_amount` decimal(10,2) DEFAULT 0.00,
  `igst_amount` decimal(10,2) DEFAULT 0.00,
  `reward_points_earned` decimal(10,2) DEFAULT 0.00,
  `reward_points_redeemed` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_invoices`
--

INSERT INTO `sales_invoices` (`invoice_id`, `organization_id`, `proforma_invoice_id`, `customer_id`, `make_employee_id`, `sales_employee_id`, `delivery_mode`, `reference_customer_id`, `invoice_number`, `invoice_date`, `due_date`, `reference_no`, `payment_terms`, `discount_type`, `discount_value`, `sub_total`, `total_amount`, `balance_due`, `status`, `notes`, `terms_conditions`, `adjustment`, `gst_type`, `cgst_amount`, `sgst_amount`, `igst_amount`, `reward_points_earned`, `reward_points_redeemed`, `created_at`, `updated_at`) VALUES
(2, 1, 2, 38, 13, 2, 'road', 37, 'INV-SAM-0001', '2026-02-20', NULL, 'HGHFH', 'Due on Receipt', 'amount', 16750.00, 423500.00, 437206.00, 0.00, 'paid', '', '', 0.00, 'CGST_SGST', 15228.00, 15228.00, 0.00, 12201.00, 0.00, '2026-02-20 06:50:10', '2026-02-21 05:13:25'),
(3, 1, 2, 38, 1, 2, 'road', NULL, 'INV-SAM-0002', '2026-02-21', NULL, 'HGHFH', 'Due on Receipt', 'amount', 16750.00, 423500.00, 425005.00, 425005.00, 'sent', '', '', 0.00, 'CGST_SGST', 15228.00, 15228.00, 0.00, 11835.00, 12201.00, '2026-02-21 06:24:57', '2026-02-21 06:24:57'),
(4, 1, 2, 38, 13, 2, 'road', NULL, 'INV-SAM-0003', '2026-02-23', NULL, 'HGHFH', 'Due on Receipt', 'amount', 16750.00, 423500.00, 437206.00, 437206.00, 'sent', '', '', 0.00, 'CGST_SGST', 15228.00, 15228.00, 0.00, 12201.00, 0.00, '2026-02-23 10:54:30', '2026-02-23 10:54:30');

-- --------------------------------------------------------

--
-- Table structure for table `sales_invoice_items`
--

CREATE TABLE `sales_invoice_items` (
  `id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `invoice_id` bigint(20) NOT NULL,
  `item_id` bigint(20) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `hsn_code` varchar(50) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `discount_type` enum('amount','percentage') DEFAULT 'amount',
  `amount` decimal(15,2) DEFAULT NULL,
  `gst_rate` decimal(5,2) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_invoice_items`
--

INSERT INTO `sales_invoice_items` (`id`, `organization_id`, `invoice_id`, `item_id`, `item_name`, `hsn_code`, `unit_id`, `quantity`, `rate`, `discount`, `discount_type`, `amount`, `gst_rate`, `total_amount`, `created_at`) VALUES
(2, 1, 2, 1, 'Commercial Plywood 19mm', '3004', 1, 50.00, 2950.00, 2.00, 'percentage', 144550.00, 12.00, 161896.00, '2026-02-20 06:50:10'),
(3, 1, 2, 2, 'BWP Marine Plywood 18mm', '1701', 1, 60.00, 4600.00, 5.00, 'percentage', 262200.00, 5.00, 275310.00, '2026-02-20 06:50:10'),
(4, 1, 3, 1, 'Commercial Plywood 19mm', '3004', 1, 50.00, 2950.00, 2.00, 'percentage', 144550.00, 12.00, 161896.00, '2026-02-21 06:24:57'),
(5, 1, 3, 2, 'BWP Marine Plywood 18mm', '1701', 1, 60.00, 4600.00, 5.00, 'percentage', 262200.00, 5.00, 275310.00, '2026-02-21 06:24:57'),
(6, 1, 4, 1, 'Commercial Plywood 19mm', '3004', 1, 50.00, 2950.00, 2.00, 'percentage', 144550.00, 12.00, 161896.00, '2026-02-23 10:54:30'),
(7, 1, 4, 2, 'BWP Marine Plywood 18mm', '1701', 1, 60.00, 4600.00, 5.00, 'percentage', 262200.00, 5.00, 275310.00, '2026-02-23 10:54:30');

-- --------------------------------------------------------

--
-- Table structure for table `units_listing`
--

CREATE TABLE `units_listing` (
  `unit_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `unit_name` varchar(100) NOT NULL,
  `unit_slug` varchar(100) NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `update_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units_listing`
--

INSERT INTO `units_listing` (`unit_id`, `organization_id`, `unit_name`, `unit_slug`, `create_date`, `update_at`) VALUES
(1, 1, 'PCS', 'pcs', '2026-01-08 09:56:01', '2026-01-20 12:22:55'),
(2, 1, 'PANEL', 'panel', '2026-01-08 10:05:06', '2026-01-20 12:22:58'),
(4, 1, 'SHEET', 'sheet', '2026-01-20 12:24:36', '2026-01-20 12:25:48');

-- --------------------------------------------------------

--
-- Table structure for table `vendors_addresses`
--

CREATE TABLE `vendors_addresses` (
  `vendor_addresses_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `address_type` enum('billing','shipping') NOT NULL,
  `attention` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `address_line1` varchar(255) DEFAULT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pin_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors_addresses`
--

INSERT INTO `vendors_addresses` (`vendor_addresses_id`, `organization_id`, `vendor_id`, `address_type`, `attention`, `country`, `address_line1`, `address_line2`, `city`, `state`, `pin_code`, `phone`, `fax`, `created_at`, `updated_at`) VALUES
(37, 0, 4, 'billing', 'Bill', 'India', 'Barasat', 'Chapaali', 'Kolkata', 'West Bengal', '700125', '7059411929', '5556781929', '2026-01-15 08:00:36', '2026-01-15 08:00:36'),
(38, 0, 4, 'shipping', 'Bill', 'India', 'Barasat', 'Chapaali', 'Kolkata', 'West Bengal', '700125', '7059411929', '5556781929', '2026-01-15 08:00:36', '2026-01-15 08:00:36'),
(72, 1, 27, 'billing', 'Bill', 'India', 'Barasat', 'Chapaali', 'Kolkata', 'West Bengal', '700125', '7059411929', '5556781929', '2026-01-21 07:03:44', '2026-01-21 07:03:44'),
(73, 1, 27, 'shipping', 'Bill', 'India', 'Barasat', 'Chapaali', 'Kolkata', 'West Bengal', '700125', '7059411929', '5556781929', '2026-01-21 07:03:44', '2026-01-21 07:03:44'),
(84, 1, 30, 'billing', '', 'india', 'chapadali', 'station', 'barasat', 'wb', '700124', '', '', '2026-02-19 11:22:19', '2026-02-19 11:22:19'),
(85, 1, 30, 'shipping', '', 'india', 'chapadali', 'station', 'barasat', 'wb', '700124', '', '', '2026-02-19 11:22:19', '2026-02-19 11:22:19'),
(86, 1, 31, 'billing', '', 'india', 'chapadali', 'station', 'barasat', 'wb', '700124', '', '', '2026-02-19 11:26:57', '2026-02-19 11:26:57'),
(87, 1, 31, 'shipping', '', 'india', 'chapadali', 'station', 'barasat', 'wb', '700124', '', '', '2026-02-19 11:26:57', '2026-02-19 11:26:57'),
(88, 1, 32, 'billing', 'billing', 'India', '12 Market Road', 'Near City Mall', '12 Market Road', 'New Delhi', '110001', '011-4567891', '011-4567890', '2026-02-19 11:53:44', '2026-02-19 11:53:44'),
(89, 1, 32, 'shipping', 'billing', 'India', '12 Market Road', 'Near City Mall', '12 Market Road', 'New Delhi', '110001', '011-4567891', '011-4567890', '2026-02-19 11:53:44', '2026-02-19 11:53:44'),
(90, 1, 33, 'billing', 'billing', 'india', 'chapadali', 'station', 'barasat', 'wb', '700124', '011-4567891', '011-4567890', '2026-02-20 07:36:17', '2026-02-20 07:36:17'),
(91, 1, 33, 'shipping', 'billing', 'india', 'chapadali', 'station', 'barasat', 'wb', '700124', '011-4567891', '011-4567890', '2026-02-20 07:36:17', '2026-02-20 07:36:17'),
(92, 1, 34, 'billing', 'Billing', 'india', 'chapadali', 'Near City Mall', '12 Market Road', 'New Delhi', '700124', '011-4567891', '011-4567890', '2026-02-23 12:15:43', '2026-02-23 12:15:43'),
(93, 1, 34, 'shipping', 'Billing', 'india', 'chapadali', 'Near City Mall', '12 Market Road', 'New Delhi', '700124', '011-4567891', '011-4567890', '2026-02-23 12:15:43', '2026-02-23 12:15:43');

-- --------------------------------------------------------

--
-- Table structure for table `vendors_bank_accounts`
--

CREATE TABLE `vendors_bank_accounts` (
  `vendor_bank_accounts_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `account_holder_name` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors_contacts`
--

CREATE TABLE `vendors_contacts` (
  `vendor_contacts_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `salutation` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `work_phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors_listing`
--

CREATE TABLE `vendors_listing` (
  `vendor_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_code` varchar(100) NOT NULL,
  `salutation` varchar(10) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `display_name` varchar(255) NOT NULL,
  `vendor_type` varchar(100) NOT NULL,
  `vendor_account_type` varchar(50) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `work_phone` varchar(20) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `vendor_language` varchar(50) DEFAULT 'English',
  `pan` varchar(20) DEFAULT NULL,
  `gst_no` varchar(50) NOT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `opening_balance_type` enum('DR','CR') DEFAULT NULL,
  `current_balance_due` decimal(15,2) NOT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors_listing`
--

INSERT INTO `vendors_listing` (`vendor_id`, `organization_id`, `vendor_code`, `salutation`, `first_name`, `last_name`, `company_name`, `display_name`, `vendor_type`, `vendor_account_type`, `email`, `work_phone`, `mobile`, `vendor_language`, `pan`, `gst_no`, `currency`, `opening_balance`, `opening_balance_type`, `current_balance_due`, `payment_terms`, `status`, `avatar`, `created_at`, `updated_at`) VALUES
(33, 1, 'VEN-SAM-001', 'Mr.', 'SOUMODEEP', 'MONDAL', 'SKC INFOTECH', 'SKC INFOTECH', 'Goods Supplier', 'Sundry Creditors', 'soumodeep.official20@gmail.com', '011-4567890', '7059411929', 'en', 'AADCS7369C', '21AABCA4455D1ZC', '0', 50000.00, 'CR', 59750.00, 'Due on Receipt', 'active', '', '2026-02-20 07:36:17', '2026-02-23 09:03:44'),
(34, 1, 'VEN-SAM-002', 'Dr.', 'Avik', 'Mondal', 'ORIX-SRC', 'ORIX-SRC', '', '', 'avik@gmail.com', '43435466633444', '878787996565', 'en', '', '', '0', 0.00, 'DR', -10200.00, 'Due on Receipt', 'active', '', '2026-02-23 12:15:43', '2026-02-24 05:10:48');

-- --------------------------------------------------------

--
-- Table structure for table `vendors_remarks`
--

CREATE TABLE `vendors_remarks` (
  `vendor_remarks_id` bigint(20) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `vendor_id` bigint(20) NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD PRIMARY KEY (`credit_note_id`),
  ADD UNIQUE KEY `credit_note_number` (`credit_note_number`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `credit_note_history`
--
ALTER TABLE `credit_note_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `credit_note_id` (`credit_note_id`);

--
-- Indexes for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credit_note_id` (`credit_note_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `customers_commissions_ledger`
--
ALTER TABLE `customers_commissions_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `customers_commissions_payouts`
--
ALTER TABLE `customers_commissions_payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_org` (`organization_id`),
  ADD KEY `idx_cust` (`customer_id`);

--
-- Indexes for table `customers_ledger`
--
ALTER TABLE `customers_ledger`
  ADD PRIMARY KEY (`ledger_id`),
  ADD KEY `idx_cust_org` (`customer_id`,`organization_id`);

--
-- Indexes for table `customers_listing`
--
ALTER TABLE `customers_listing`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customers_type_listing`
--
ALTER TABLE `customers_type_listing`
  ADD PRIMARY KEY (`customers_type_id`);

--
-- Indexes for table `debit_notes`
--
ALTER TABLE `debit_notes`
  ADD PRIMARY KEY (`debit_note_id`);

--
-- Indexes for table `debit_note_items`
--
ALTER TABLE `debit_note_items`
  ADD PRIMARY KEY (`debit_note_item_id`);

--
-- Indexes for table `department_listing`
--
ALTER TABLE `department_listing`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `department_targets`
--
ALTER TABLE `department_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `monthly_target_id` (`monthly_target_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `designation_listing`
--
ALTER TABLE `designation_listing`
  ADD PRIMARY KEY (`designation_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_code` (`employee_code`);

--
-- Indexes for table `employees_temp`
--
ALTER TABLE `employees_temp`
  ADD PRIMARY KEY (`employees_temp_id`),
  ADD UNIQUE KEY `primary_email` (`primary_email`);

--
-- Indexes for table `employee_addresses`
--
ALTER TABLE `employee_addresses`
  ADD PRIMARY KEY (`employee_addresses_id`);

--
-- Indexes for table `employee_bank_details`
--
ALTER TABLE `employee_bank_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `goods_received_notes`
--
ALTER TABLE `goods_received_notes`
  ADD PRIMARY KEY (`grn_id`),
  ADD UNIQUE KEY `grn_number` (`grn_number`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `goods_received_note_items`
--
ALTER TABLE `goods_received_note_items`
  ADD PRIMARY KEY (`grn_item_id`),
  ADD KEY `grn_id` (`grn_id`),
  ADD KEY `po_item_id` (`po_item_id`);

--
-- Indexes for table `hsn_listing`
--
ALTER TABLE `hsn_listing`
  ADD PRIMARY KEY (`hsn_id`);

--
-- Indexes for table `incentive_ledger`
--
ALTER TABLE `incentive_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `monthly_target_id` (`monthly_target_id`);

--
-- Indexes for table `items_listing`
--
ALTER TABLE `items_listing`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `item_commissions`
--
ALTER TABLE `item_commissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `customers_type_id` (`customers_type_id`);

--
-- Indexes for table `loyalty_points_earned`
--
ALTER TABLE `loyalty_points_earned`
  ADD PRIMARY KEY (`loyalty_point_id`);

--
-- Indexes for table `loyalty_point_slabs`
--
ALTER TABLE `loyalty_point_slabs`
  ADD PRIMARY KEY (`slab_id`);

--
-- Indexes for table `loyalty_point_transactions`
--
ALTER TABLE `loyalty_point_transactions`
  ADD PRIMARY KEY (`transaction_id`);

--
-- Indexes for table `monthly_targets`
--
ALTER TABLE `monthly_targets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`organization_id`);

--
-- Indexes for table `payment_made`
--
ALTER TABLE `payment_made`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `payment_received`
--
ALTER TABLE `payment_received`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  ADD PRIMARY KEY (`proforma_invoice_id`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `proforma_invoice_items`
--
ALTER TABLE `proforma_invoice_items`
  ADD PRIMARY KEY (`item_row_id`),
  ADD KEY `proforma_invoice_id` (`proforma_invoice_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`purchase_orders_id`),
  ADD UNIQUE KEY `po_number` (`po_number`);

--
-- Indexes for table `purchase_order_activity_logs`
--
ALTER TABLE `purchase_order_activity_logs`
  ADD PRIMARY KEY (`po_logs_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `purchase_order_files`
--
ALTER TABLE `purchase_order_files`
  ADD PRIMARY KEY (`files_id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_id` (`purchase_order_id`);

--
-- Indexes for table `roles_listing`
--
ALTER TABLE `roles_listing`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`invoice_id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `units_listing`
--
ALTER TABLE `units_listing`
  ADD PRIMARY KEY (`unit_id`);

--
-- Indexes for table `vendors_addresses`
--
ALTER TABLE `vendors_addresses`
  ADD PRIMARY KEY (`vendor_addresses_id`);

--
-- Indexes for table `vendors_bank_accounts`
--
ALTER TABLE `vendors_bank_accounts`
  ADD PRIMARY KEY (`vendor_bank_accounts_id`);

--
-- Indexes for table `vendors_contacts`
--
ALTER TABLE `vendors_contacts`
  ADD PRIMARY KEY (`vendor_contacts_id`);

--
-- Indexes for table `vendors_listing`
--
ALTER TABLE `vendors_listing`
  ADD PRIMARY KEY (`vendor_id`);

--
-- Indexes for table `vendors_remarks`
--
ALTER TABLE `vendors_remarks`
  ADD PRIMARY KEY (`vendor_remarks_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `credit_notes`
--
ALTER TABLE `credit_notes`
  MODIFY `credit_note_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `credit_note_history`
--
ALTER TABLE `credit_note_history`
  MODIFY `history_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `credit_note_items`
--
ALTER TABLE `credit_note_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers_commissions_ledger`
--
ALTER TABLE `customers_commissions_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers_commissions_payouts`
--
ALTER TABLE `customers_commissions_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers_ledger`
--
ALTER TABLE `customers_ledger`
  MODIFY `ledger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `customers_listing`
--
ALTER TABLE `customers_listing`
  MODIFY `customer_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `customers_type_listing`
--
ALTER TABLE `customers_type_listing`
  MODIFY `customers_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `debit_notes`
--
ALTER TABLE `debit_notes`
  MODIFY `debit_note_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `debit_note_items`
--
ALTER TABLE `debit_note_items`
  MODIFY `debit_note_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `department_listing`
--
ALTER TABLE `department_listing`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `department_targets`
--
ALTER TABLE `department_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `designation_listing`
--
ALTER TABLE `designation_listing`
  MODIFY `designation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `employees_temp`
--
ALTER TABLE `employees_temp`
  MODIFY `employees_temp_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_addresses`
--
ALTER TABLE `employee_addresses`
  MODIFY `employee_addresses_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employee_bank_details`
--
ALTER TABLE `employee_bank_details`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=450;

--
-- AUTO_INCREMENT for table `goods_received_notes`
--
ALTER TABLE `goods_received_notes`
  MODIFY `grn_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `goods_received_note_items`
--
ALTER TABLE `goods_received_note_items`
  MODIFY `grn_item_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `hsn_listing`
--
ALTER TABLE `hsn_listing`
  MODIFY `hsn_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `incentive_ledger`
--
ALTER TABLE `incentive_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items_listing`
--
ALTER TABLE `items_listing`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `item_commissions`
--
ALTER TABLE `item_commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `loyalty_points_earned`
--
ALTER TABLE `loyalty_points_earned`
  MODIFY `loyalty_point_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `loyalty_point_slabs`
--
ALTER TABLE `loyalty_point_slabs`
  MODIFY `slab_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `loyalty_point_transactions`
--
ALTER TABLE `loyalty_point_transactions`
  MODIFY `transaction_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `monthly_targets`
--
ALTER TABLE `monthly_targets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `organization_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_made`
--
ALTER TABLE `payment_made`
  MODIFY `payment_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_received`
--
ALTER TABLE `payment_received`
  MODIFY `payment_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  MODIFY `proforma_invoice_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `proforma_invoice_items`
--
ALTER TABLE `proforma_invoice_items`
  MODIFY `item_row_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `purchase_orders_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `purchase_order_activity_logs`
--
ALTER TABLE `purchase_order_activity_logs`
  MODIFY `po_logs_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `purchase_order_files`
--
ALTER TABLE `purchase_order_files`
  MODIFY `files_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `roles_listing`
--
ALTER TABLE `roles_listing`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `invoice_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_invoice_items`
--
ALTER TABLE `sales_invoice_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `units_listing`
--
ALTER TABLE `units_listing`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vendors_addresses`
--
ALTER TABLE `vendors_addresses`
  MODIFY `vendor_addresses_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `vendors_bank_accounts`
--
ALTER TABLE `vendors_bank_accounts`
  MODIFY `vendor_bank_accounts_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `vendors_contacts`
--
ALTER TABLE `vendors_contacts`
  MODIFY `vendor_contacts_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `vendors_listing`
--
ALTER TABLE `vendors_listing`
  MODIFY `vendor_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `vendors_remarks`
--
ALTER TABLE `vendors_remarks`
  MODIFY `vendor_remarks_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `department_targets`
--
ALTER TABLE `department_targets`
  ADD CONSTRAINT `department_targets_ibfk_1` FOREIGN KEY (`monthly_target_id`) REFERENCES `monthly_targets` (`id`),
  ADD CONSTRAINT `department_targets_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department_listing` (`department_id`);

--
-- Constraints for table `purchase_order_activity_logs`
--
ALTER TABLE `purchase_order_activity_logs`
  ADD CONSTRAINT `purchase_order_activity_logs_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_orders_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_files`
--
ALTER TABLE `purchase_order_files`
  ADD CONSTRAINT `purchase_order_files_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_orders_id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`purchase_orders_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
