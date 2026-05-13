-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 23, 2026 at 10:38 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `auragold`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_advance_payments`
--

CREATE TABLE `tbl_advance_payments` (
  `id` int NOT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `voucher_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `total_gold` decimal(10,3) DEFAULT '0.000',
  `total_silver` decimal(10,3) DEFAULT '0.000',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_advance_payment_items`
--

CREATE TABLE `tbl_advance_payment_items` (
  `id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal_id` int DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purity_wt` decimal(10,3) DEFAULT '0.000',
  `amount` decimal(15,2) DEFAULT '0.00',
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_article`
--

CREATE TABLE `tbl_article` (
  `id` int NOT NULL,
  `article_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_article`
--

INSERT INTO `tbl_article` (`id`, `article_code`, `name`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'ds', 'ds', 'dds', 1, 0, NULL, '2025-12-19 11:26:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_barcode_settings`
--

CREATE TABLE `tbl_barcode_settings` (
  `id` int UNSIGNED NOT NULL,
  `label_size_preset` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '100x18' COMMENT 'e.g. 100x18, 100x25, custom',
  `label_width_mm` decimal(10,2) NOT NULL DEFAULT '100.00',
  `label_height_mm` decimal(10,2) NOT NULL DEFAULT '18.00',
  `font_size` int NOT NULL DEFAULT '12' COMMENT 'Label text font size in px',
  `show_product_name` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=show, 0=hide',
  `show_price` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=show, 0=hide',
  `show_barcode_number` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=show, 0=hide',
  `print_copies` int NOT NULL DEFAULT '1' COMMENT 'Number of copies per label',
  `metal_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g. Gold, Silver, Platinum',
  `design_layout` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON: barcode label design (field, left, top, prefix, suffix, font, font_size)',
  `preview_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to saved preview image e.g. uploads/barcode_settings/preview_1234567890.png',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_barcode_settings`
--

INSERT INTO `tbl_barcode_settings` (`id`, `label_size_preset`, `label_width_mm`, `label_height_mm`, `font_size`, `show_product_name`, `show_price`, `show_barcode_number`, `print_copies`, `metal_type`, `design_layout`, `preview_image`, `created_at`, `updated_at`) VALUES
(1, '100x18', 100.00, 18.00, 10, 1, 1, 1, 1, 'Gold', '{\"items\":[{\"type\":\"barcode_image\",\"left\":0,\"top\":0.22,\"width\":47.81,\"height\":4.05},{\"type\":\"text\",\"field\":\"Barcode\",\"left\":67.11,\"top\":0.45,\"prefix\":\"Barcode\",\"suffix\":\"\",\"font\":\"Arial\",\"font_size\":\"10\"},{\"type\":\"text\",\"field\":\"GrossWt\",\"left\":71.93,\"top\":1.35,\"prefix\":\"GrossWt\",\"suffix\":\"\",\"font\":\"Arial\",\"font_size\":\"10\"},{\"type\":\"text\",\"field\":\"CompanyName\",\"left\":100,\"top\":0.23,\"prefix\":\"roade jwellers\",\"suffix\":\"\",\"font\":\"Arial\",\"font_size\":\"10\"},{\"type\":\"text\",\"field\":\"Carat\",\"left\":100,\"top\":1.35,\"prefix\":\"Carat\",\"suffix\":\"\",\"font\":\"Arial\",\"font_size\":\"10\"},{\"type\":\"text\",\"field\":\"NetWt\",\"left\":52.63,\"top\":0.9,\"prefix\":\"NetWt\",\"suffix\":\"\",\"font\":\"Arial\",\"font_size\":\"10\"},{\"type\":\"text\",\"field\":\"ProductName\",\"left\":96.05,\"top\":1.35,\"prefix\":\"ProductName\",\"suffix\":\"\",\"font\":\"Arial\",\"font_size\":\"10\"}],\"items2\":[{\"type\":\"barcode_image\",\"left\":0,\"top\":0,\"width\":39.47,\"height\":4.05}],\"barcode1_top\":0.22,\"barcode1_left\":0,\"barcode2_top\":0,\"barcode2_left\":0,\"barcode_bar_width\":2,\"barcode_bar_height\":20,\"qr_width\":60,\"qr_height\":60}', 'uploads/barcode_settings/preview_1773839754.png', '2026-02-25 16:25:12', '2026-03-18 18:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bill_series`
--

CREATE TABLE `tbl_bill_series` (
  `id` int UNSIGNED NOT NULL,
  `voucher_type_id` int NOT NULL COMMENT 'FK to tbl_voucher_types.id',
  `branch_id` int DEFAULT NULL COMMENT 'Optional branch',
  `prefix` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `suffix` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `start_count` int NOT NULL DEFAULT '0' COMMENT 'Bill series count from',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_bill_series`
--

INSERT INTO `tbl_bill_series` (`id`, `voucher_type_id`, `branch_id`, `prefix`, `suffix`, `start_count`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 33, NULL, 'SPK', '', 11, 1, 1, '2026-03-18 16:12:57', '2026-03-19 14:50:24'),
(2, 22, NULL, 'PRI', '', 1, 1, 1, '2026-03-20 11:11:59', NULL),
(3, 35, NULL, 'SQT', '', 2, 1, 1, '2026-03-20 12:09:29', NULL),
(4, 36, NULL, 'SR', '', 1, 1, NULL, '2026-03-20 13:20:09', NULL),
(5, 25, NULL, 'PR', '', 1, 1, NULL, '2026-03-20 14:33:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_branches`
--

CREATE TABLE `tbl_branches` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `db_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `db_users` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `db_password` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `main_branch_id` int NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `username` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `allow_product_delete` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_branches`
--

INSERT INTO `tbl_branches` (`id`, `name`, `code`, `db_name`, `db_users`, `db_password`, `main_branch_id`, `status`, `created_at`, `username`, `password`, `allow_product_delete`) VALUES
(1, 'ROKDE BRANCH', 'MAIN', 'auragold', 'root', NULL, 0, 1, '2025-12-26 16:05:20', 'admin', '12345', 0),
(2, 'Branch 1', NULL, 'auragold_branch1', 'root', NULL, 1, 1, '2025-12-26 16:05:20', NULL, NULL, 0),
(3, 'Branch 2', '', NULL, NULL, NULL, 1, 0, '2025-12-26 16:05:20', NULL, NULL, 0),
(4, 'Branch 3', '', NULL, NULL, NULL, 1, 0, '2025-12-26 16:05:20', NULL, NULL, 0),
(9, 'Branch 3', '', NULL, NULL, NULL, 1, 0, '2025-12-26 16:05:20', NULL, NULL, 0),
(10, 'Branch 4', '', NULL, NULL, NULL, 1, 0, '2025-12-26 16:05:20', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_break_type`
--

CREATE TABLE `tbl_break_type` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_calculation_modes`
--

CREATE TABLE `tbl_calculation_modes` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_calculation_modes`
--

INSERT INTO `tbl_calculation_modes` (`id`, `name`, `code`, `status`, `sort_order`, `created_at`) VALUES
(1, 'Product Amount', 'product_amount', 1, 1, '2025-12-26 16:34:46'),
(2, 'Invoice Amount', 'invoice_amount', 1, 2, '2025-12-26 16:34:46'),
(3, 'Hallmark Amount', 'hallmark_amount', 1, 3, '2025-12-26 16:34:46'),
(4, 'Making Charge Amount', 'making_charge_amount', 1, 4, '2025-12-26 16:34:46'),
(5, 'Metal Exchange Amount', 'metal_exchange_amount', 1, 5, '2025-12-26 16:34:46'),
(6, 'Gold Amount', 'gold_amount', 1, 6, '2025-12-26 16:34:46');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_campaign_group`
--

CREATE TABLE `tbl_campaign_group` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_campaign_group`
--

INSERT INTO `tbl_campaign_group` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'dd', 1, 0, NULL, '2025-12-19 11:21:50', NULL),
(2, 'tttttt', 0, 0, 0, '2025-12-22 09:38:41', '2025-12-22 09:38:45');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_carat`
--

CREATE TABLE `tbl_carat` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `purity` decimal(6,3) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_carat`
--

INSERT INTO `tbl_carat` (`id`, `name`, `purity`, `description`, `status`, `created_by`, `modified_by`, `created_at`) VALUES
(3, '24k', 0.999, 'Pure goldd', 1, 0, 0, '2025-12-18 12:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cash_denomination`
--

CREATE TABLE `tbl_cash_denomination` (
  `id` int NOT NULL,
  `type` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_cash_denomination`
--

INSERT INTO `tbl_cash_denomination` (`id`, `type`, `amount`, `currency`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Note', 11.00, 'AED', 1, 0, 0, '2025-12-19 11:30:50', '2025-12-19 11:32:13');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_categories`
--

CREATE TABLE `tbl_categories` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_categories`
--

INSERT INTO `tbl_categories` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'BOMBAY', 1, '2025-12-26 16:10:53'),
(2, 'BOMBAY >> CARTIER', 1, '2025-12-26 16:10:53'),
(3, 'Bracelet', 1, '2025-12-26 16:10:53'),
(4, 'Earring', 1, '2025-12-26 16:10:53'),
(5, 'NLR HARAM', 1, '2025-12-26 16:10:53'),
(6, 'PACHI WORK', 1, '2025-12-26 16:10:53'),
(7, 'Ring', 1, '2025-12-26 16:10:53'),
(8, 'VENKATEWSARA RINGS', 1, '2025-12-26 16:10:53');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_clarity`
--

CREATE TABLE `tbl_clarity` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_clarity`
--

INSERT INTO `tbl_clarity` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'hhh', 0, 0, NULL, '2025-12-18 12:38:12', '2025-12-18 12:38:27'),
(2, 'Pure', 0, 0, NULL, '2025-12-18 16:35:12', '2025-12-18 16:35:15');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_collection`
--

CREATE TABLE `tbl_collection` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '1=Active,0=Deleted',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_collection`
--

INSERT INTO `tbl_collection` (`id`, `name`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Test Old', 'Test New', 1, 0, 0, '2025-12-18 12:01:58', '2025-12-18 16:36:40');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_color`
--

CREATE TABLE `tbl_color` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_color`
--

INSERT INTO `tbl_color` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'red', 1, 0, NULL, '2025-12-18 13:21:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_consignment_in`
--

CREATE TABLE `tbl_consignment_in` (
  `id` int NOT NULL,
  `consignment_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CI-1, CI-2, etc.',
  `consignment_out_id` int DEFAULT NULL COMMENT 'Reference to original consignment out record',
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consignment_date` date NOT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `gross_total` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `total_quantity` int DEFAULT '0',
  `total_gross_weight` decimal(10,3) DEFAULT '0.000',
  `total_net_weight` decimal(10,3) DEFAULT '0.000',
  `total_pure_weight` decimal(10,3) DEFAULT '0.000',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, cancelled',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_consignment_in_items`
--

CREATE TABLE `tbl_consignment_in_items` (
  `id` int NOT NULL,
  `consignment_id` int NOT NULL,
  `consignment_out_item_id` int DEFAULT NULL COMMENT 'Reference to original consignment out item',
  `product_id` int DEFAULT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `huid_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation_mode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metal_id` int DEFAULT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,4) DEFAULT '0.0000',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `wastage_percent` decimal(10,2) DEFAULT '0.00',
  `wastage_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `metal_value` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `making_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `making_rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `stone_weight` decimal(10,3) DEFAULT '0.000',
  `stone_rate` decimal(15,2) DEFAULT '0.00',
  `stone_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_amount` decimal(15,2) DEFAULT '0.00',
  `other_amount` decimal(15,2) DEFAULT '0.00',
  `discount_percent` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `tax_percent` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_consignment_out`
--

CREATE TABLE `tbl_consignment_out` (
  `id` int NOT NULL,
  `consignment_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'CO-1, CO-2, etc.',
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consignment_date` date NOT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `gross_total` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `total_quantity` int DEFAULT '0',
  `total_gross_weight` decimal(10,3) DEFAULT '0.000',
  `total_net_weight` decimal(10,3) DEFAULT '0.000',
  `total_pure_weight` decimal(10,3) DEFAULT '0.000',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, cancelled, returned',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_consignment_out`
--

INSERT INTO `tbl_consignment_out` (`id`, `consignment_no`, `customer_id`, `customer_name`, `consignment_date`, `ref_no`, `against_of`, `currency`, `fixing_type`, `sales_person`, `previous_balance`, `previous_gold`, `previous_silver`, `gross_total`, `discount_amount`, `tax_amount`, `grand_total`, `total_quantity`, `total_gross_weight`, `total_net_weight`, `total_pure_weight`, `comment`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'CO-1', 1, 'Rajat Dhanwalkar', '2026-02-26', NULL, NULL, 'AED', 'Standard', NULL, -2000.00, 0.000, 0.000, 1140.00, 0.00, 0.00, 1140.00, 1, 10.000, 10.000, 0.000, NULL, 'active', 1, '2026-02-26 16:00:10', NULL),
(3, 'CO-2', 2, 'nilesh', '2026-02-26', NULL, NULL, 'AED', 'Standard', NULL, 2000.00, 0.000, 0.000, 1140.00, 0.00, 0.00, 1140.00, 1, 10.000, 10.000, 0.000, NULL, 'active', 1, '2026-02-26 16:04:09', NULL),
(4, 'CO-3', 1, 'Rajat Dhanwalkar', '2026-02-27', NULL, NULL, 'AED', 'Standard', NULL, -2000.00, 0.000, 0.000, 1140.00, 0.00, 0.00, 1140.00, 1, 10.000, 10.000, 0.000, NULL, 'active', 1, '2026-02-27 14:25:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_consignment_out_items`
--

CREATE TABLE `tbl_consignment_out_items` (
  `id` int NOT NULL,
  `consignment_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `huid_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation_mode` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metal_id` int DEFAULT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,4) DEFAULT '0.0000',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `wastage_percent` decimal(10,2) DEFAULT '0.00',
  `wastage_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `metal_value` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `making_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `making_rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `stone_weight` decimal(10,3) DEFAULT '0.000',
  `stone_rate` decimal(15,2) DEFAULT '0.00',
  `stone_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_amount` decimal(15,2) DEFAULT '0.00',
  `other_amount` decimal(15,2) DEFAULT '0.00',
  `discount_percent` decimal(10,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `tax_percent` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_consignment_out_items`
--

INSERT INTO `tbl_consignment_out_items` (`id`, `consignment_id`, `product_id`, `product_characteristic_id`, `barcode`, `product_name`, `design_no`, `huid_no`, `category`, `calculation_mode`, `location`, `metal_id`, `carat`, `quantity`, `gross_weight`, `less_weight`, `net_weight`, `purity`, `purity_weight`, `wastage_percent`, `wastage_weight`, `final_weight`, `pure_weight`, `rate`, `metal_value`, `amount`, `making_type`, `making_rate`, `making_amount`, `stone_weight`, `stone_rate`, `stone_amount`, `diamond_amount`, `other_amount`, `discount_percent`, `discount_amount`, `tax_percent`, `tax_amount`, `net_amount`, `net_amt_with_tax`, `status`, `created_at`) VALUES
(2, 2, 1, 1, 'RN00001', 'Gold Bar - Gold', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 10.000, 0.000, 10.000, 0.9500, 0.000, 0.00, 0.000, 9.500, 0.000, 120.00, 1140.00, 1140.00, NULL, 0.00, 0.00, 0.000, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1140.00, 1140.00, 1, '2026-02-26 16:00:10'),
(3, 3, 1, 1, 'RN00001', 'Gold Bar - Gold', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 10.000, 0.000, 10.000, 0.9500, 0.000, 0.00, 0.000, 9.500, 0.000, 120.00, 1140.00, 1140.00, NULL, 0.00, 0.00, 0.000, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1140.00, 1140.00, 1, '2026-02-26 16:04:09'),
(4, 4, 1, 1, 'RN00001', 'Gold Bar - Gold', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 10.000, 0.000, 10.000, 0.9500, 0.000, 0.00, 0.000, 9.500, 0.000, 120.00, 1140.00, 1140.00, NULL, 0.00, 0.00, 0.000, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1140.00, 1140.00, 1, '2026-02-27 14:25:31');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contra_vouchers`
--

CREATE TABLE `tbl_contra_vouchers` (
  `id` int NOT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_date` date NOT NULL,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_contra_voucher_items`
--

CREATE TABLE `tbl_contra_voucher_items` (
  `id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `bank_cash_ac` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Bank or Cash account name',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_date` date DEFAULT NULL,
  `transaction_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'withdrawal' COMMENT 'deposit or withdrawal',
  `amount` decimal(15,2) DEFAULT '0.00',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_counter`
--

CREATE TABLE `tbl_counter` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` varchar(150) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_counter`
--

INSERT INTO `tbl_counter` (`id`, `name`, `location`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'ds', 'ds', 'ds', 1, 0, NULL, '2025-12-18 13:59:26', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_countries`
--

CREATE TABLE `tbl_countries` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `code3` varchar(3) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_countries`
--

INSERT INTO `tbl_countries` (`id`, `name`, `code`, `code3`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Afghanistan', 'AF', 'AFG', 1, 1, '2025-12-30 15:50:03', NULL),
(2, 'Albania', 'AL', 'ALB', 1, 2, '2025-12-30 15:50:03', NULL),
(3, 'Algeria', 'DZ', 'DZA', 1, 3, '2025-12-30 15:50:03', NULL),
(4, 'Angola', 'AO', 'AGO', 1, 4, '2025-12-30 15:50:03', NULL),
(5, 'Argentina', 'AR', 'ARG', 1, 5, '2025-12-30 15:50:03', NULL),
(6, 'Australia', 'AU', 'AUS', 1, 6, '2025-12-30 15:50:03', NULL),
(7, 'Austria', 'AT', 'AUT', 1, 7, '2025-12-30 15:50:03', NULL),
(8, 'Bahrain', 'BH', 'BHR', 1, 8, '2025-12-30 15:50:03', NULL),
(9, 'Bangladesh', 'BD', 'BGD', 1, 9, '2025-12-30 15:50:03', NULL),
(10, 'Belgium', 'BE', 'BEL', 1, 10, '2025-12-30 15:50:03', NULL),
(11, 'Brazil', 'BR', 'BRA', 1, 11, '2025-12-30 15:50:03', NULL),
(12, 'Canada', 'CA', 'CAN', 1, 12, '2025-12-30 15:50:03', NULL),
(13, 'China', 'CN', 'CHN', 1, 13, '2025-12-30 15:50:03', NULL),
(14, 'Egypt', 'EG', 'EGY', 1, 14, '2025-12-30 15:50:03', NULL),
(15, 'France', 'FR', 'FRA', 1, 15, '2025-12-30 15:50:03', NULL),
(16, 'Germany', 'DE', 'DEU', 1, 16, '2025-12-30 15:50:03', NULL),
(17, 'Ghana', 'GH', 'GHA', 1, 17, '2025-12-30 15:50:03', NULL),
(18, 'Greece', 'GR', 'GRC', 1, 18, '2025-12-30 15:50:03', NULL),
(19, 'Guinea-Bissau', 'GW', 'GNB', 1, 19, '2025-12-30 15:50:03', NULL),
(20, 'Guyana', 'GY', 'GUY', 1, 20, '2025-12-30 15:50:03', NULL),
(21, 'Haiti', 'HT', 'HTI', 1, 21, '2025-12-30 15:50:03', NULL),
(22, 'Heard Island and McDonald Islands', 'HM', 'HMD', 1, 22, '2025-12-30 15:50:03', NULL),
(23, 'Honduras', 'HN', 'HND', 1, 23, '2025-12-30 15:50:03', NULL),
(24, 'Hong Kong S.A.R.', 'HK', 'HKG', 1, 24, '2025-12-30 15:50:03', NULL),
(25, 'Hungary', 'HU', 'HUN', 1, 25, '2025-12-30 15:50:03', NULL),
(26, 'India', 'IN', 'IND', 1, 26, '2025-12-30 15:50:03', NULL),
(27, 'Indonesia', 'ID', 'IDN', 1, 27, '2025-12-30 15:50:03', NULL),
(28, 'Iran', 'IR', 'IRN', 1, 28, '2025-12-30 15:50:03', NULL),
(29, 'Iraq', 'IQ', 'IRQ', 1, 29, '2025-12-30 15:50:03', NULL),
(30, 'Ireland', 'IE', 'IRL', 1, 30, '2025-12-30 15:50:03', NULL),
(31, 'Italy', 'IT', 'ITA', 1, 31, '2025-12-30 15:50:03', NULL),
(32, 'Japan', 'JP', 'JPN', 1, 32, '2025-12-30 15:50:03', NULL),
(33, 'Jersey', 'JE', 'JEY', 1, 33, '2025-12-30 15:50:03', NULL),
(34, 'Jordan', 'JO', 'JOR', 1, 34, '2025-12-30 15:50:03', NULL),
(35, 'Kenya', 'KE', 'KEN', 1, 35, '2025-12-30 15:50:03', NULL),
(36, 'Kuwait', 'KW', 'KWT', 1, 36, '2025-12-30 15:50:03', NULL),
(37, 'Lebanon', 'LB', 'LBN', 1, 37, '2025-12-30 15:50:03', NULL),
(38, 'Libya', 'LY', 'LBY', 1, 38, '2025-12-30 15:50:03', NULL),
(39, 'Malaysia', 'MY', 'MYS', 1, 39, '2025-12-30 15:50:03', NULL),
(40, 'Mexico', 'MX', 'MEX', 1, 40, '2025-12-30 15:50:03', NULL),
(41, 'Morocco', 'MA', 'MAR', 1, 41, '2025-12-30 15:50:03', NULL),
(42, 'Nepal', 'NP', 'NPL', 1, 42, '2025-12-30 15:50:03', NULL),
(43, 'Netherlands', 'NL', 'NLD', 1, 43, '2025-12-30 15:50:03', NULL),
(44, 'New Zealand', 'NZ', 'NZL', 1, 44, '2025-12-30 15:50:03', NULL),
(45, 'Nigeria', 'NG', 'NGA', 1, 45, '2025-12-30 15:50:03', NULL),
(46, 'Oman', 'OM', 'OMN', 1, 46, '2025-12-30 15:50:03', NULL),
(47, 'Pakistan', 'PK', 'PAK', 1, 47, '2025-12-30 15:50:03', NULL),
(48, 'Palestine', 'PS', 'PSE', 1, 48, '2025-12-30 15:50:03', NULL),
(49, 'Philippines', 'PH', 'PHL', 1, 49, '2025-12-30 15:50:03', NULL),
(50, 'Qatar', 'QA', 'QAT', 1, 50, '2025-12-30 15:50:03', NULL),
(51, 'Russia', 'RU', 'RUS', 1, 51, '2025-12-30 15:50:03', NULL),
(52, 'Saudi Arabia', 'SA', 'SAU', 1, 52, '2025-12-30 15:50:03', NULL),
(53, 'Singapore', 'SG', 'SGP', 1, 53, '2025-12-30 15:50:03', NULL),
(54, 'South Africa', 'ZA', 'ZAF', 1, 54, '2025-12-30 15:50:03', NULL),
(55, 'South Korea', 'KR', 'KOR', 1, 55, '2025-12-30 15:50:03', NULL),
(56, 'Spain', 'ES', 'ESP', 1, 56, '2025-12-30 15:50:03', NULL),
(57, 'Sri Lanka', 'LK', 'LKA', 1, 57, '2025-12-30 15:50:03', NULL),
(58, 'Sudan', 'SD', 'SDN', 1, 58, '2025-12-30 15:50:03', NULL),
(59, 'Switzerland', 'CH', 'CHE', 1, 59, '2025-12-30 15:50:03', NULL),
(60, 'Syria', 'SY', 'SYR', 1, 60, '2025-12-30 15:50:03', NULL),
(61, 'Thailand', 'TH', 'THA', 1, 61, '2025-12-30 15:50:03', NULL),
(62, 'Tunisia', 'TN', 'TUN', 1, 62, '2025-12-30 15:50:03', NULL),
(63, 'Turkey', 'TR', 'TUR', 1, 63, '2025-12-30 15:50:03', NULL),
(64, 'Ukraine', 'UA', 'UKR', 1, 64, '2025-12-30 15:50:03', NULL),
(65, 'United Arab Emirates', 'AE', 'ARE', 1, 65, '2025-12-30 15:50:03', NULL),
(66, 'United Kingdom', 'GB', 'GBR', 1, 66, '2025-12-30 15:50:03', NULL),
(67, 'United States', 'US', 'USA', 1, 67, '2025-12-30 15:50:03', NULL),
(68, 'Yemen', 'YE', 'YEM', 1, 68, '2025-12-30 15:50:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_credit_notes`
--

CREATE TABLE `tbl_credit_notes` (
  `id` int NOT NULL,
  `credit_note_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credit_note_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_credit_note_items`
--

CREATE TABLE `tbl_credit_note_items` (
  `id` int NOT NULL,
  `credit_note_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_credit_note_payments`
--

CREATE TABLE `tbl_credit_note_payments` (
  `id` int NOT NULL,
  `credit_note_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `current_order_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_currency`
--

CREATE TABLE `tbl_currency` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `decimal_places` int DEFAULT '2',
  `symbol` varchar(20) DEFAULT NULL,
  `description` varchar(150) DEFAULT NULL,
  `is_base` tinyint(1) DEFAULT '0',
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_currency`
--

INSERT INTO `tbl_currency` (`id`, `name`, `decimal_places`, `symbol`, `description`, `is_base`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'ds', 2, 'ds', 'ds', 1, 1, 0, 0, '2025-12-18 13:40:37', '2025-12-18 13:50:18'),
(2, 'ds', 2, 'ds', 'ds', 0, 1, 0, NULL, '2025-12-18 13:49:43', '2025-12-18 13:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_currency_exchange_rate`
--

CREATE TABLE `tbl_currency_exchange_rate` (
  `id` int NOT NULL,
  `currency_id` int NOT NULL,
  `rate` decimal(12,6) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_currency_exchange_rate`
--

INSERT INTO `tbl_currency_exchange_rate` (`id`, `currency_id`, `rate`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 1, 112.000000, '121', 1, 0, NULL, '2025-12-18 13:45:50', NULL),
(2, 1, 12.000000, 'sdds', 1, 0, NULL, '2025-12-18 13:49:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customers`
--

CREATE TABLE `tbl_customers` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `alternate_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile_country_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '971',
  `mobile_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mail_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `national_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `trade_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identity_issue_date` date DEFAULT NULL,
  `identity_expiry_date` date DEFAULT NULL,
  `special_day` date DEFAULT NULL,
  `customer_type_id` int DEFAULT '0',
  `registration_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `nationality_id` int DEFAULT '0',
  `country_id` int DEFAULT '0',
  `date1` date DEFAULT NULL,
  `date2` date DEFAULT NULL,
  `group_id` int DEFAULT '0',
  `sundry_debtors_id` int DEFAULT '0',
  `ledger_name_capital` tinyint(1) DEFAULT '0',
  `kyc` tinyint(1) DEFAULT '0',
  `aml` tinyint(1) DEFAULT '0',
  `bill_to_bill` tinyint(1) DEFAULT '0',
  `billing_address1` text COLLATE utf8mb4_general_ci,
  `billing_address2` text COLLATE utf8mb4_general_ci,
  `billing_country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billing_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `billing_zip_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_address1` text COLLATE utf8mb4_general_ci,
  `shipping_address2` text COLLATE utf8mb4_general_ci,
  `shipping_country` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_state` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shipping_zip_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_ifsc_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_branch` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `item_tax_data` text COLLATE utf8mb4_general_ci,
  `ledger_photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `share_holders_data` text COLLATE utf8mb4_general_ci,
  `share_holder_documents` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_advance_policy`
--

CREATE TABLE `tbl_customer_advance_policy` (
  `id` int NOT NULL,
  `policy_name` varchar(150) NOT NULL,
  `days_duration` int NOT NULL,
  `min_gold_percent` decimal(5,2) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_advance_vouchers`
--

CREATE TABLE `tbl_customer_advance_vouchers` (
  `id` int NOT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `voucher_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `total_gold` decimal(10,3) DEFAULT '0.000',
  `total_silver` decimal(10,3) DEFAULT '0.000',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_advance_voucher_items`
--

CREATE TABLE `tbl_customer_advance_voucher_items` (
  `id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal_id` int DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purity_wt` decimal(10,3) DEFAULT '0.000',
  `amount` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_balance`
--

CREATE TABLE `tbl_customer_balance` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance_amount` decimal(15,2) DEFAULT '0.00',
  `balance_gold` decimal(10,3) DEFAULT '0.000',
  `balance_silver` decimal(10,3) DEFAULT '0.000',
  `last_transaction_date` date DEFAULT NULL,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_ledger`
--

CREATE TABLE `tbl_customer_ledger` (
  `id` int NOT NULL,
  `customer_id` int NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sale_order, purchase_invoice, payment, receipt, advance, return',
  `transaction_id` int DEFAULT NULL COMMENT 'ID of related transaction (order_id, invoice_id, etc.)',
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Order/Invoice number',
  `transaction_date` date NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount customer owes (sale orders, purchases)',
  `credit_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount customer paid (payments, receipts)',
  `debit_gold` decimal(10,3) DEFAULT '0.000' COMMENT 'Gold weight customer owes',
  `credit_gold` decimal(10,3) DEFAULT '0.000' COMMENT 'Gold weight customer paid',
  `debit_gold_pure` decimal(10,3) DEFAULT '0.000' COMMENT 'Gold pure weight (debit)',
  `credit_gold_pure` decimal(10,3) DEFAULT '0.000' COMMENT 'Gold pure weight (credit)',
  `debit_silver` decimal(10,3) DEFAULT '0.000' COMMENT 'Silver weight customer owes',
  `credit_silver` decimal(10,3) DEFAULT '0.000' COMMENT 'Silver weight customer paid',
  `balance_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Running balance amount',
  `balance_gold` decimal(10,3) DEFAULT '0.000' COMMENT 'Running balance gold',
  `balance_gold_pure` decimal(10,3) DEFAULT '0.000' COMMENT 'Running balance gold pure',
  `balance_silver` decimal(10,3) DEFAULT '0.000' COMMENT 'Running balance silver',
  `description` text COLLATE utf8mb4_unicode_ci,
  `reference_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_ledger` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Against Ledger name with balance (e.g., ABC(640.00Dr))',
  `against_invoice_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Against Invoice/Order number',
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer_types`
--

CREATE TABLE `tbl_customer_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_customer_types`
--

INSERT INTO `tbl_customer_types` (`id`, `name`, `code`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Customer', 'CUSTOMER', 1, 1, '2025-12-30 15:50:03', NULL),
(2, 'WholeSaler', 'WHOLESALER', 1, 2, '2025-12-30 15:50:03', NULL),
(3, 'Job Worker', 'JOB_WORKER', 1, 3, '2025-12-30 15:50:03', NULL),
(4, 'Employee', 'EMPLOYEE', 1, 4, '2025-12-30 15:50:03', NULL),
(5, 'Sales Person', 'SALES_PERSON', 1, 5, '2025-12-30 15:50:03', NULL),
(6, 'Supplier', 'SUPPLIER', 1, 6, '2025-12-30 15:50:03', NULL),
(7, 'Qbo Account', 'QBO_ACCOUNT', 1, 7, '2025-12-30 15:50:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cut`
--

CREATE TABLE `tbl_cut` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_cut`
--

INSERT INTO `tbl_cut` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Cut New', 1, 0, 0, '2025-12-18 12:44:55', '2025-12-18 16:37:02');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_day_reports`
--

CREATE TABLE `tbl_day_reports` (
  `id` int NOT NULL,
  `report_date` date NOT NULL,
  `opening_amount` decimal(15,2) DEFAULT '0.00',
  `expected_amount` decimal(15,2) DEFAULT '0.00',
  `online_cheque_payment` decimal(15,2) DEFAULT '0.00',
  `closing_cash` decimal(15,2) DEFAULT '0.00',
  `cash_denomination` decimal(15,3) DEFAULT '0.000',
  `difference` decimal(15,2) DEFAULT '0.00',
  `report_data` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_debit_notes`
--

CREATE TABLE `tbl_debit_notes` (
  `id` int NOT NULL,
  `debit_note_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit_note_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_debit_note_items`
--

CREATE TABLE `tbl_debit_note_items` (
  `id` int NOT NULL,
  `debit_note_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_debit_note_payments`
--

CREATE TABLE `tbl_debit_note_payments` (
  `id` int NOT NULL,
  `debit_note_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `current_order_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_departments`
--

CREATE TABLE `tbl_departments` (
  `id` int NOT NULL,
  `dept_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `short_code` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `department_type` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Wt. Wise',
  `process_type` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Manufacturing',
  `auto_loss` tinyint(1) NOT NULL DEFAULT '1',
  `auto_profit` tinyint(1) NOT NULL DEFAULT '1',
  `calculate_stock` tinyint(1) NOT NULL DEFAULT '0',
  `progress_percent` decimal(8,2) DEFAULT NULL,
  `exclude_jobcard_summary` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_departments`
--

INSERT INTO `tbl_departments` (`id`, `dept_name`, `short_code`, `department_type`, `process_type`, `auto_loss`, `auto_profit`, `calculate_stock`, `progress_percent`, `exclude_jobcard_summary`, `status`, `created_at`, `updated_at`) VALUES
(1, 'polish department', 'PD', 'Wt. Wise', 'Melting', 0, 1, 1, NULL, 0, 1, '2026-02-27 15:53:09', '2026-02-27 15:53:09'),
(2, 'Casting', 'CS', 'Wt. Wise', 'Manufacturing', 1, 1, 0, NULL, 0, 1, '2026-02-27 16:09:43', '2026-02-27 16:09:43'),
(3, 'Filling', 'FL', 'Wt. Wise', 'Manufacturing', 1, 1, 0, NULL, 0, 1, '2026-02-27 16:12:51', '2026-02-27 16:12:51'),
(4, 'WAX', 'WX', 'Wt. Wise', 'Manufacturing', 1, 1, 0, NULL, 0, 1, '2026-02-27 16:14:09', '2026-02-27 16:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_department_users`
--

CREATE TABLE `tbl_department_users` (
  `id` int NOT NULL,
  `user_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_department_user_map`
--

CREATE TABLE `tbl_department_user_map` (
  `id` int NOT NULL,
  `department_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_department_user_map`
--

INSERT INTO `tbl_department_user_map` (`id`, `department_id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 0, '2026-02-28 18:00:35', '2026-02-28 18:00:47'),
(2, 2, 2, 1, '2026-02-28 18:00:43', '2026-02-28 18:00:43');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_document_type`
--

CREATE TABLE `tbl_document_type` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_document_type`
--

INSERT INTO `tbl_document_type` (`id`, `name`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'ds', 'ds', 1, 0, NULL, '2025-12-18 13:55:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expenses`
--

CREATE TABLE `tbl_expenses` (
  `id` int NOT NULL,
  `expense_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `with_tax` tinyint(1) DEFAULT '1',
  `ledger_id` int DEFAULT NULL,
  `ledger_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'INR',
  `exchange_rate` decimal(15,6) DEFAULT '1.000000',
  `expense_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `layaways` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `discount_percent` decimal(10,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expense_categories`
--

CREATE TABLE `tbl_expense_categories` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Display name (e.g. Inter Branch Account ABU DHABI)',
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type in parentheses (e.g. Branch /Divisions)',
  `sort_order` int DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expense_items`
--

CREATE TABLE `tbl_expense_items` (
  `id` int NOT NULL,
  `expense_id` int NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_rate` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `tax_with_amount` decimal(15,2) DEFAULT '0.00',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_expense_payments`
--

CREATE TABLE `tbl_expense_payments` (
  `id` int NOT NULL,
  `expense_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_from` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `card_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_invoice_print_settings`
--

CREATE TABLE `tbl_invoice_print_settings` (
  `id` int UNSIGNED NOT NULL,
  `setting_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default' COMMENT 'default, sale_invoice, purchase_invoice, ...',
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. sale_invoice_columns, header_company_logo, layout_type',
  `setting_value` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON or 1/0 for toggles',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_invoice_print_settings`
--

INSERT INTO `tbl_invoice_print_settings` (`id`, `setting_type`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'default', 'sale_invoice_columns', '[\"making_charge\",\"item_name\",\"net_weight\",\"category\",\"diamond_amount\",\"rate\",\"stone_amount\",\"discount\",\"amount\"]', '2026-03-18 01:31:51'),
(2, 'default', 'header_company_logo', '1', '2026-03-18 01:31:51'),
(3, 'default', 'header_company_name', '1', '2026-03-18 01:31:51'),
(4, 'default', 'header_gst_number', '1', '2026-03-18 01:31:51'),
(5, 'default', 'header_phone', '1', '2026-03-18 01:31:51'),
(6, 'default', 'header_invoice_title', '1', '2026-03-18 01:31:51'),
(7, 'default', 'footer_terms_conditions', '1', '2026-03-18 01:31:51'),
(8, 'default', 'footer_authorized_signature', '1', '2026-03-18 01:31:51'),
(9, 'default', 'footer_thank_you_message', '1', '2026-03-18 01:31:51'),
(10, 'default', 'layout_type', 'A4', '2026-03-18 01:31:51'),
(11, 'default', 'company_logo_path', 'uploads/invoice_print/logo.png', '2026-03-18 01:31:51'),
(12, 'default', 'company_name', 'Gold Matrix', '2026-03-18 01:31:51'),
(13, 'default', 'company_address', 'Dubai', '2026-03-18 01:31:51'),
(14, 'default', 'company_gst', '1000222222', '2026-03-18 01:31:51'),
(15, 'default', 'company_phone', '9977665544', '2026-03-18 01:31:51'),
(16, 'default', 'company_email', 'rajatdh07@gmail.com', '2026-03-18 01:31:51'),
(17, 'default', 'invoice_title', 'Tax Invoice', '2026-03-18 01:31:51'),
(18, 'default', 'terms_conditions', 'Terms & Conditions', '2026-03-18 01:31:51'),
(19, 'default', 'authorized_signature', 'Authorized Signature', '2026-03-18 01:31:51'),
(20, 'default', 'thank_you_message', 'Thank You Message', '2026-03-18 01:31:51'),
(21, 'default', 'invoice_secondary_language', 'ar', '2026-03-18 01:31:51'),
(22, 'default', 'advertise_banner_path', 'uploads/invoice_print/advertise_banner.jpg', '2026-03-18 01:31:51'),
(23, 'default', 'footer_show_banner', '1', '2026-03-18 01:31:51'),
(35, 'purchase_invoice', 'sale_invoice_columns', '[\"item_name\",\"making_charge\",\"diamond_amount\",\"net_weight\",\"category\",\"rate\",\"stone_amount\",\"discount\",\"amount\"]', '2026-03-12 19:26:23'),
(36, 'purchase_invoice', 'header_company_logo', '1', '2026-03-12 19:26:23'),
(37, 'purchase_invoice', 'header_company_name', '1', '2026-03-12 19:26:23'),
(38, 'purchase_invoice', 'header_gst_number', '1', '2026-03-12 19:26:23'),
(39, 'purchase_invoice', 'header_phone', '1', '2026-03-12 19:26:23'),
(40, 'purchase_invoice', 'header_invoice_title', '1', '2026-03-12 19:26:23'),
(41, 'purchase_invoice', 'footer_terms_conditions', '1', '2026-03-12 19:26:23'),
(42, 'purchase_invoice', 'footer_authorized_signature', '1', '2026-03-12 19:26:23'),
(43, 'purchase_invoice', 'footer_thank_you_message', '1', '2026-03-12 19:26:23'),
(44, 'purchase_invoice', 'layout_type', 'A4', '2026-03-12 19:26:23'),
(45, 'purchase_invoice', 'company_logo_path', 'uploads/invoice_print/logo.png', '2026-03-12 19:26:23'),
(46, 'purchase_invoice', 'company_name', 'Gold Matrix', '2026-03-12 19:26:23'),
(47, 'purchase_invoice', 'company_address', 'Dubai', '2026-03-12 19:26:23'),
(48, 'purchase_invoice', 'company_gst', '1000222222', '2026-03-12 19:26:23'),
(49, 'purchase_invoice', 'company_phone', '9977665544', '2026-03-12 19:26:23'),
(50, 'purchase_invoice', 'company_email', 'rajatdh07@gmail.com', '2026-03-12 19:26:23'),
(51, 'purchase_invoice', 'invoice_title', 'Tax Invoice', '2026-03-12 19:26:23'),
(52, 'purchase_invoice', 'terms_conditions', 'Terms & Conditions', '2026-03-12 19:26:23'),
(53, 'purchase_invoice', 'authorized_signature', 'Authorized Signature', '2026-03-12 19:26:23'),
(54, 'purchase_invoice', 'thank_you_message', 'Thank You Message', '2026-03-12 19:26:23'),
(55, 'purchase_invoice', 'invoice_secondary_language', 'ar', '2026-03-12 19:26:23'),
(56, 'purchase_invoice', 'footer_show_banner', '1', '2026-03-12 19:26:23'),
(57, 'purchase_invoice', 'advertise_banner_path', 'uploads/invoice_print/advertise_banner.jpg', '2026-03-12 19:26:23'),
(58, 'sale_order', 'sale_invoice_columns', '[\"item_name\",\"gross_weight\",\"making_charge\",\"net_weight\",\"rate\",\"stone_amount\",\"discount\",\"amount\"]', '2026-03-18 01:34:57'),
(59, 'sale_order', 'header_company_logo', '1', '2026-03-18 01:34:57'),
(60, 'sale_order', 'header_company_name', '1', '2026-03-18 01:34:57'),
(61, 'sale_order', 'header_gst_number', '1', '2026-03-18 01:34:57'),
(62, 'sale_order', 'header_phone', '1', '2026-03-18 01:34:57'),
(63, 'sale_order', 'header_invoice_title', '1', '2026-03-18 01:34:57'),
(64, 'sale_order', 'footer_terms_conditions', '1', '2026-03-18 01:34:57'),
(65, 'sale_order', 'footer_authorized_signature', '1', '2026-03-18 01:34:57'),
(66, 'sale_order', 'footer_thank_you_message', '1', '2026-03-18 01:34:57'),
(67, 'sale_order', 'layout_type', 'Thermal 80mm', '2026-03-18 01:34:57'),
(68, 'sale_order', 'design_template', 'template_4', '2026-03-18 01:34:57'),
(69, 'sale_order', 'invoice_template', 'template_thermal', '2026-03-18 01:34:57'),
(70, 'sale_order', 'company_logo_path', 'uploads/invoice_print/logo.png', '2026-03-18 01:34:57'),
(71, 'sale_order', 'company_name', 'Gold Matrix', '2026-03-18 01:34:57'),
(72, 'sale_order', 'company_address', 'Dubai', '2026-03-18 01:34:57'),
(73, 'sale_order', 'company_gst', '1000222222', '2026-03-18 01:34:57'),
(74, 'sale_order', 'company_phone', '9977665544', '2026-03-18 01:34:57'),
(75, 'sale_order', 'company_email', 'rajatdh07@gmail.com', '2026-03-18 01:34:57'),
(76, 'sale_order', 'invoice_title', 'Tax Invoice', '2026-03-18 01:34:57'),
(77, 'sale_order', 'terms_conditions', 'Terms & ConditionsDSVBASKHDBV;KASBKCVS', '2026-03-18 01:34:57'),
(78, 'sale_order', 'authorized_signature', 'Authorized Signature', '2026-03-18 01:34:57'),
(79, 'sale_order', 'thank_you_message', 'Thank You Message', '2026-03-18 01:34:57'),
(80, 'sale_order', 'invoice_secondary_language', '', '2026-03-18 01:34:57'),
(81, 'sale_order', 'footer_show_banner', '1', '2026-03-18 01:34:57'),
(82, 'sale_order', 'advertise_banner_path', 'uploads/invoice_print/advertise_banner.jpg', '2026-03-18 01:34:57'),
(83, 'sale_invoice', 'sale_invoice_columns', '[\"category\",\"net_weight\",\"making_charge\",\"diamond_amount\",\"amount\",\"rate\",\"stone_amount\",\"discount\"]', '2026-03-18 01:35:48'),
(84, 'sale_invoice', 'header_company_logo', '0', '2026-03-18 01:35:48'),
(85, 'sale_invoice', 'header_company_name', '0', '2026-03-18 01:35:48'),
(86, 'sale_invoice', 'header_gst_number', '0', '2026-03-18 01:35:48'),
(87, 'sale_invoice', 'header_phone', '0', '2026-03-18 01:35:48'),
(88, 'sale_invoice', 'header_invoice_title', '0', '2026-03-18 01:35:48'),
(89, 'sale_invoice', 'footer_terms_conditions', '1', '2026-03-18 01:35:48'),
(90, 'sale_invoice', 'footer_authorized_signature', '1', '2026-03-18 01:35:48'),
(91, 'sale_invoice', 'footer_thank_you_message', '1', '2026-03-18 01:35:48'),
(92, 'sale_invoice', 'layout_type', 'Thermal 80mm', '2026-03-18 01:35:48'),
(93, 'sale_invoice', 'design_template', 'template_1', '2026-03-18 01:35:48'),
(94, 'sale_invoice', 'invoice_template', 'template_classic', '2026-03-18 01:35:48'),
(95, 'sale_invoice', 'company_logo_path', 'uploads/invoice_print/logo.png', '2026-03-18 01:35:48'),
(96, 'sale_invoice', 'company_name', 'Gold Matrix', '2026-03-18 01:35:48'),
(97, 'sale_invoice', 'company_address', 'Dubai', '2026-03-18 01:35:48'),
(98, 'sale_invoice', 'company_gst', '1000222222', '2026-03-18 01:35:48'),
(99, 'sale_invoice', 'company_phone', '9977665544', '2026-03-18 01:35:48'),
(100, 'sale_invoice', 'company_email', 'rajatdh07@gmail.com', '2026-03-18 01:35:48'),
(101, 'sale_invoice', 'invoice_title', 'Tax Invoice', '2026-03-18 01:35:48'),
(102, 'sale_invoice', 'terms_conditions', 'Terms & Conditions DFAFCAWCASD', '2026-03-18 01:35:48'),
(103, 'sale_invoice', 'authorized_signature', 'Authorized Signature', '2026-03-18 01:35:48'),
(104, 'sale_invoice', 'thank_you_message', 'Thank You Message', '2026-03-18 01:35:48'),
(105, 'sale_invoice', 'invoice_secondary_language', '', '2026-03-18 01:35:48'),
(106, 'sale_invoice', 'footer_show_banner', '0', '2026-03-18 01:35:48'),
(107, 'sale_invoice', 'advertise_banner_path', 'uploads/invoice_print/advertise_banner.jpg', '2026-03-18 01:35:48'),
(108, 'purchase_invoice', 'design_template', 'template_1', '2026-03-12 19:26:23'),
(109, 'purchase_invoice', 'invoice_template', 'template_classic', '2026-03-12 19:26:23'),
(110, 'default', 'design_template', 'template_2', '2026-03-18 01:31:51'),
(111, 'default', 'invoice_template', 'template_classic', '2026-03-18 01:31:51');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_jobwork_orders`
--

CREATE TABLE `tbl_jobwork_orders` (
  `id` int NOT NULL,
  `jobwork_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `sale_order_id` int NOT NULL,
  `sale_order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `priority` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_jobwork_order_items`
--

CREATE TABLE `tbl_jobwork_order_items` (
  `id` int NOT NULL,
  `jobwork_order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_job_work_orders`
--

CREATE TABLE `tbl_job_work_orders` (
  `id` int NOT NULL,
  `job_work_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sale_order_id` int NOT NULL,
  `sale_order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_journal_vouchers`
--

CREATE TABLE `tbl_journal_vouchers` (
  `id` int NOT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_date` date NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `credit_wt` decimal(15,4) DEFAULT '0.0000',
  `debit_wt` decimal(15,4) DEFAULT '0.0000',
  `debit_total` decimal(15,2) DEFAULT '0.00',
  `credit_total` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_journal_voucher_items`
--

CREATE TABLE `tbl_journal_voucher_items` (
  `id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `branch_id` int DEFAULT NULL COMMENT 'FK to tbl_branches',
  `branch_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Denormalized branch name',
  `account_ledger` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Account ledger name',
  `cr_dr` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Dr' COMMENT 'Cr or Dr',
  `against` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT '0.00',
  `metal` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purity_wt` decimal(15,4) DEFAULT '0.0000',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_loan_product_type`
--

CREATE TABLE `tbl_loan_product_type` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_loan_product_type`
--

INSERT INTO `tbl_loan_product_type` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'dsds', 1, 0, NULL, '2025-12-19 11:03:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_loan_reason`
--

CREATE TABLE `tbl_loan_reason` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_loan_reason`
--

INSERT INTO `tbl_loan_reason` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'ddd', 0, 0, 0, '2025-12-19 11:09:28', '2025-12-19 11:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_location`
--

CREATE TABLE `tbl_location` (
  `id` int NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_location`
--

INSERT INTO `tbl_location` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Nagpur', 0, NULL, NULL, '2025-12-18 11:26:05', NULL),
(2, 'Nagpur', 0, NULL, 0, '2025-12-18 11:27:22', NULL),
(3, 'Nagpur', 0, NULL, NULL, '2025-12-18 11:28:47', NULL),
(4, 'Nagpurdddd', 0, NULL, 0, '2025-12-18 11:29:00', NULL),
(5, 'ddd', 0, NULL, NULL, '2025-12-18 11:35:27', NULL),
(6, 'Amravati', 1, 0, 0, '2025-12-18 12:00:07', '2025-12-18 16:36:22'),
(7, 'Mumbai', 0, 0, 0, '2025-12-18 12:00:21', '2025-12-18 16:36:30');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_metal`
--

CREATE TABLE `tbl_metal` (
  `id` int NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `hsn_code` varchar(20) DEFAULT NULL,
  `system_name` varchar(100) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_metal`
--

INSERT INTO `tbl_metal` (`id`, `display_name`, `hsn_code`, `system_name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Gold', '12345', 'ewew', 1, 0, NULL, '2025-12-18 12:41:42', '2025-12-26 12:04:01'),
(2, 'Silver', NULL, NULL, 1, NULL, NULL, '2025-12-26 12:04:11', NULL),
(3, 'Platinum', NULL, NULL, 1, NULL, NULL, '2025-12-26 12:04:19', NULL),
(4, 'Diamond & Stones', NULL, NULL, 1, NULL, NULL, '2025-12-26 12:04:19', NULL),
(5, 'Imitation Or Watches', NULL, NULL, 1, NULL, NULL, '2025-12-26 12:04:29', NULL),
(6, 'Other Or Services', NULL, NULL, 1, NULL, NULL, '2025-12-26 12:04:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_nationalities`
--

CREATE TABLE `tbl_nationalities` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_nationalities`
--

INSERT INTO `tbl_nationalities` (`id`, `name`, `code`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Afghan', 'AF', 1, 1, '2025-12-30 15:50:03', NULL),
(2, 'Albanian', 'AL', 1, 2, '2025-12-30 15:50:03', NULL),
(3, 'Algerian', 'DZ', 1, 3, '2025-12-30 15:50:03', NULL),
(4, 'American', 'US', 1, 4, '2025-12-30 15:50:03', NULL),
(5, 'Argentine', 'AR', 1, 5, '2025-12-30 15:50:03', NULL),
(6, 'Australian', 'AU', 1, 6, '2025-12-30 15:50:03', NULL),
(7, 'Austrian', 'AT', 1, 7, '2025-12-30 15:50:03', NULL),
(8, 'Bangladeshi', 'BD', 1, 8, '2025-12-30 15:50:03', NULL),
(9, 'Belgian', 'BE', 1, 9, '2025-12-30 15:50:03', NULL),
(10, 'Brazilian', 'BR', 1, 10, '2025-12-30 15:50:03', NULL),
(11, 'British', 'GB', 1, 11, '2025-12-30 15:50:03', NULL),
(12, 'Canadian', 'CA', 1, 12, '2025-12-30 15:50:03', NULL),
(13, 'Chinese', 'CN', 1, 13, '2025-12-30 15:50:03', NULL),
(14, 'Egyptian', 'EG', 1, 14, '2025-12-30 15:50:03', NULL),
(15, 'Emirati', 'AE', 1, 15, '2025-12-30 15:50:03', NULL),
(16, 'Filipino', 'PH', 1, 16, '2025-12-30 15:50:03', NULL),
(17, 'French', 'FR', 1, 17, '2025-12-30 15:50:03', NULL),
(18, 'German', 'DE', 1, 18, '2025-12-30 15:50:03', NULL),
(19, 'Indian', 'IN', 1, 19, '2025-12-30 15:50:03', NULL),
(20, 'Indonesian', 'ID', 1, 20, '2025-12-30 15:50:03', NULL),
(21, 'Iranian', 'IR', 1, 21, '2025-12-30 15:50:03', NULL),
(22, 'Iraqi', 'IQ', 1, 22, '2025-12-30 15:50:03', NULL),
(23, 'Irish', 'IE', 1, 23, '2025-12-30 15:50:03', NULL),
(24, 'Italian', 'IT', 1, 24, '2025-12-30 15:50:03', NULL),
(25, 'Japanese', 'JP', 1, 25, '2025-12-30 15:50:03', NULL),
(26, 'Jordanian', 'JO', 1, 26, '2025-12-30 15:50:03', NULL),
(27, 'Kenyan', 'KE', 1, 27, '2025-12-30 15:50:03', NULL),
(28, 'Kuwaiti', 'KW', 1, 28, '2025-12-30 15:50:03', NULL),
(29, 'Lebanese', 'LB', 1, 29, '2025-12-30 15:50:03', NULL),
(30, 'Malaysian', 'MY', 1, 30, '2025-12-30 15:50:03', NULL),
(31, 'Mexican', 'MX', 1, 31, '2025-12-30 15:50:03', NULL),
(32, 'Moroccan', 'MA', 1, 32, '2025-12-30 15:50:03', NULL),
(33, 'Nepalese', 'NP', 1, 33, '2025-12-30 15:50:03', NULL),
(34, 'Nigerian', 'NG', 1, 34, '2025-12-30 15:50:03', NULL),
(35, 'Omani', 'OM', 1, 35, '2025-12-30 15:50:03', NULL),
(36, 'Pakistani', 'PK', 1, 36, '2025-12-30 15:50:03', NULL),
(37, 'Palestinian', 'PS', 1, 37, '2025-12-30 15:50:03', NULL),
(38, 'Qatari', 'QA', 1, 38, '2025-12-30 15:50:03', NULL),
(39, 'Russian', 'RU', 1, 39, '2025-12-30 15:50:03', NULL),
(40, 'Saudi Arabian', 'SA', 1, 40, '2025-12-30 15:50:03', NULL),
(41, 'Singaporean', 'SG', 1, 41, '2025-12-30 15:50:03', NULL),
(42, 'South African', 'ZA', 1, 42, '2025-12-30 15:50:03', NULL),
(43, 'South Korean', 'KR', 1, 43, '2025-12-30 15:50:03', NULL),
(44, 'Spanish', 'ES', 1, 44, '2025-12-30 15:50:03', NULL),
(45, 'Sri Lankan', 'LK', 1, 45, '2025-12-30 15:50:03', NULL),
(46, 'Sudanese', 'SD', 1, 46, '2025-12-30 15:50:03', NULL),
(47, 'Swiss', 'CH', 1, 47, '2025-12-30 15:50:03', NULL),
(48, 'Syrian', 'SY', 1, 48, '2025-12-30 15:50:03', NULL),
(49, 'Thai', 'TH', 1, 49, '2025-12-30 15:50:03', NULL),
(50, 'Tunisian', 'TN', 1, 50, '2025-12-30 15:50:03', NULL),
(51, 'Turkish', 'TR', 1, 51, '2025-12-30 15:50:03', NULL),
(52, 'Ukrainian', 'UA', 1, 52, '2025-12-30 15:50:03', NULL),
(53, 'Yemeni', 'YE', 1, 53, '2025-12-30 15:50:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_old_jewelry_scrap_invoices`
--

CREATE TABLE `tbl_old_jewelry_scrap_invoices` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `currency_rate` decimal(18,6) DEFAULT '1.000000',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ounce_rate` decimal(15,4) DEFAULT '0.0000',
  `previous_balance_amt` decimal(15,2) DEFAULT '0.00',
  `previous_balance_gold` decimal(15,4) DEFAULT '0.0000',
  `previous_balance_silver` decimal(15,4) DEFAULT '0.0000',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `round_off_apply` tinyint(1) DEFAULT '0',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_old_jewelry_scrap_invoice_items`
--

CREATE TABLE `tbl_old_jewelry_scrap_invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metal_id` int DEFAULT NULL,
  `gross_wt` decimal(15,4) DEFAULT '0.0000',
  `less_wt` decimal(15,4) DEFAULT '0.0000',
  `final_wt` decimal(15,4) DEFAULT '0.0000',
  `net_wt` decimal(15,4) DEFAULT '0.0000',
  `pure_wt` decimal(15,4) DEFAULT '0.0000',
  `making` decimal(15,2) DEFAULT '0.00',
  `tax` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `net_amt` decimal(15,2) DEFAULT '0.00',
  `quantity` decimal(10,2) DEFAULT '1.00',
  `net_amt_wt` decimal(15,4) DEFAULT '0.0000',
  `diamond_wt` decimal(15,4) DEFAULT '0.0000',
  `gemstone_wt` decimal(15,4) DEFAULT '0.0000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `rate` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_stocked` tinyint(1) DEFAULT '0' COMMENT '1=stocked in',
  `stocked_at` datetime DEFAULT NULL,
  `stocked_branch_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_old_jewelry_scrap_invoice_payments`
--

CREATE TABLE `tbl_old_jewelry_scrap_invoice_payments` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `card_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_old_jewelry_stock`
--

CREATE TABLE `tbl_old_jewelry_stock` (
  `id` int NOT NULL,
  `source_invoice_id` int NOT NULL,
  `source_item_id` int NOT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Old Jewelry - Scrap',
  `metal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `final_wt` decimal(15,4) DEFAULT '0.0000',
  `gross_wt` decimal(15,4) DEFAULT '0.0000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `branch_id` int DEFAULT NULL,
  `less_wt` decimal(15,4) DEFAULT '0.0000',
  `net_wt` decimal(15,4) DEFAULT '0.0000',
  `amount` decimal(15,2) DEFAULT '0.00',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_invoice_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_voucher` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `rate` decimal(15,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_packet_type`
--

CREATE TABLE `tbl_packet_type` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_vouchers`
--

CREATE TABLE `tbl_payment_vouchers` (
  `id` int NOT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `currency_rate` decimal(15,6) DEFAULT '1.000000',
  `voucher_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `total_gold` decimal(10,3) DEFAULT '0.000',
  `total_silver` decimal(10,3) DEFAULT '0.000',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_voucher_items`
--

CREATE TABLE `tbl_payment_voucher_items` (
  `id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal_id` int DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purity_wt` decimal(10,3) DEFAULT '0.000',
  `amount` decimal(15,2) DEFAULT '0.00',
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_products`
--

CREATE TABLE `tbl_products` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `alternate_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `article` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category_id` int NOT NULL,
  `is_stock_item` tinyint(1) DEFAULT '1',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_products`
--

INSERT INTO `tbl_products` (`id`, `name`, `alternate_name`, `article`, `category_id`, `is_stock_item`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ring', '', '', 7, 0, 1, '2026-03-21 23:20:21', NULL),
(3, 'Ring 2', '', '', 1, 0, 1, '2026-03-22 11:16:37', NULL),
(5, 'Ring 3', '', '', 0, 0, 1, '2026-03-23 12:12:26', NULL),
(6, 'Gold RIng', '', '', 0, 0, 1, '2026-03-23 15:02:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_branches`
--

CREATE TABLE `tbl_product_branches` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_characteristics`
--

CREATE TABLE `tbl_product_characteristics` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `branch_id` int NOT NULL,
  `metal_id` int NOT NULL,
  `is_selected` tinyint(1) DEFAULT '0',
  `serialized_barcode` tinyint(1) DEFAULT '0',
  `hsn` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sku_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `making_on` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit_id` int DEFAULT NULL COMMENT 'Reference to tbl_unit.id',
  `location_id` int DEFAULT NULL COMMENT 'Reference to tbl_location.id',
  `purity_sale` decimal(10,2) DEFAULT NULL COMMENT 'Purity percentage for sale',
  `purity_purchase` tinyint(1) DEFAULT '0' COMMENT 'Purchase purity enabled (1) or not (0)',
  `wastage_sale` decimal(10,2) DEFAULT NULL COMMENT 'Wastage percentage for sale',
  `wastage_purchase` decimal(10,2) DEFAULT NULL COMMENT 'Wastage percentage for purchase',
  `wt_per_piece` decimal(10,3) DEFAULT NULL COMMENT 'Weight per piece',
  `carat` decimal(10,2) DEFAULT '0.00',
  `discount` decimal(10,2) DEFAULT '0.00',
  `opening_weight` decimal(15,4) DEFAULT NULL,
  `opening_purity` decimal(10,4) DEFAULT NULL COMMENT 'Purity e.g. 0.999 for 99.9%',
  `opening_qty` decimal(15,4) DEFAULT NULL,
  `final_weight` decimal(15,4) DEFAULT NULL,
  `rate` decimal(15,4) DEFAULT NULL,
  `value` decimal(15,4) DEFAULT NULL,
  `barcode_digits` int DEFAULT '0',
  `barcode_prefix` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cut` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `shape` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `clarity` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sieve` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `size` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `style_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `barcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_product_characteristics`
--

INSERT INTO `tbl_product_characteristics` (`id`, `product_id`, `branch_id`, `metal_id`, `is_selected`, `serialized_barcode`, `hsn`, `sku_code`, `making_on`, `diamond_category`, `unit_id`, `location_id`, `purity_sale`, `purity_purchase`, `wastage_sale`, `wastage_purchase`, `wt_per_piece`, `carat`, `discount`, `opening_weight`, `opening_purity`, `opening_qty`, `final_weight`, `rate`, `value`, `barcode_digits`, `barcode_prefix`, `cut`, `shape`, `color`, `clarity`, `sieve`, `size`, `style_code`, `status`, `created_at`, `updated_at`, `barcode`) VALUES
(1, 1, 1, 1, 1, 0, '12345', '', 'Gross Wt', '', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, 0.00, 66.0000, 0.9000, 43.0000, 90.0000, 120.0000, 10800.0000, 5, 'RN', '', '', '', '', '', '', '', 1, '2026-03-21 23:20:21', '2026-03-23 12:01:46', 'RN00001'),
(2, 3, 1, 1, 1, 0, '12345', '', 'Gross Wt', '', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, 0.00, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 5, 'RNN', '', '', '', '', '', '', '', 1, '2026-03-22 11:16:37', NULL, 'RNN00001'),
(3, 5, 2, 1, 1, 0, '12345', '', 'Gross Wt', '', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, 0.00, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 5, 'RNTH', '', '', '', '', '', '', '', 1, '2026-03-23 12:12:26', NULL, 'RNTH00001'),
(4, 6, 1, 1, 1, 0, '12345', '', 'Gross Wt', '', NULL, NULL, NULL, 0, NULL, NULL, NULL, 0.00, 0.00, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 5, 'GRN', '', '', '', '', '', '', '', 1, '2026-03-23 15:02:21', NULL, 'GRN00001');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product_tax`
--

CREATE TABLE `tbl_product_tax` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `tax_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tax_value` decimal(10,2) DEFAULT '0.00',
  `calculation_mode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Product Amount',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_invoices`
--

CREATE TABLE `tbl_purchase_invoices` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `discount_percent` decimal(10,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `payment_comments` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of comments: [{text, added_by, added_at}]',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `use_previous_balance` tinyint(1) DEFAULT '0' COMMENT '1=used previous balance on this invoice',
  `previous_balance_used_amt` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount used from previous balance (e.g. 500.00)',
  `hedge_contract_ref` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hedge contract reference when fixing_type = Hedging',
  `hedge_date` date DEFAULT NULL COMMENT 'Hedge / locked rate date when fixing_type = Hedging'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_invoice_items`
--

CREATE TABLE `tbl_purchase_invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `active` tinyint(1) DEFAULT '1' COMMENT 'Active status (1=active, 0=inactive)',
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `rfid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'RFID Code',
  `voucher_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Voucher Type ID',
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_id` int DEFAULT NULL COMMENT 'Location ID',
  `images` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON: primary path + array of image paths',
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pkt_wt` decimal(10,3) DEFAULT '0.000' COMMENT 'Pkt. Wt.',
  `pkt_less_wt` decimal(10,3) DEFAULT '0.000' COMMENT 'Pkt. Less Wt.',
  `requested_purity` decimal(10,2) DEFAULT '0.00' COMMENT 'Requested Purity',
  `requested_wt` decimal(10,3) DEFAULT '0.000' COMMENT 'Requested Wt.',
  `quantity` decimal(10,2) DEFAULT '1.00',
  `metal_qty` decimal(12,2) DEFAULT '1.00',
  `metal_weight` decimal(12,4) DEFAULT '0.0000',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `gold_loss_wt` decimal(10,3) DEFAULT '0.000' COMMENT 'Gold Loss Wt.',
  `gold_loss_value` decimal(10,2) DEFAULT '0.00' COMMENT 'Gold Loss Value',
  `setting_charge` decimal(10,2) DEFAULT '0.00' COMMENT 'Setting Charge',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `wastage_per` decimal(10,2) DEFAULT '0.00' COMMENT 'Wastage Per.',
  `wastage_wt` decimal(10,3) DEFAULT '0.000' COMMENT 'Wastage Wt.',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `alloy_wt` decimal(10,3) DEFAULT '0.000' COMMENT 'Alloy Wt.',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `metal_rate` decimal(15,2) DEFAULT NULL,
  `metal_value` decimal(10,2) DEFAULT '0.00' COMMENT 'Metal Value',
  `metal_cost` decimal(10,2) DEFAULT '0.00' COMMENT 'Metal Cost',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `stone_cost` decimal(10,2) DEFAULT '0.00' COMMENT 'Stone Cost',
  `making_actual_value` decimal(10,2) DEFAULT '0.00' COMMENT 'Making Actual Value',
  `making_cost` decimal(10,2) DEFAULT '0.00' COMMENT 'Making Cost',
  `min_price` decimal(10,2) DEFAULT '0.00' COMMENT 'Minimum Price',
  `minimum` decimal(10,2) DEFAULT '0.00' COMMENT 'Minimum Price Code',
  `stone_charge_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Stone Charge Type',
  `stone_weight` decimal(10,3) DEFAULT '0.000' COMMENT 'Stone Weight',
  `stone_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Stone Rate',
  `stone_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Stone Amount',
  `diamond_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Diamond Amount',
  `purchase_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Purchase Amount',
  `sale_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Sale Amount',
  `sale_amount_with` decimal(10,2) DEFAULT '0.00' COMMENT 'Sale Amount With Tax',
  `amount` decimal(15,2) DEFAULT '0.00',
  `discount_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Discount Type',
  `discount_per` decimal(10,2) DEFAULT '0.00' COMMENT 'Discount Per.',
  `discount_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Discount Amount',
  `discount` decimal(10,2) DEFAULT '0.00' COMMENT 'Discount',
  `discount_type2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Discount Type 2',
  `discount_per2` decimal(10,2) DEFAULT '0.00' COMMENT 'Discount Per. 2',
  `discount_amount2` decimal(10,2) DEFAULT '0.00' COMMENT 'Discount Amount 2',
  `discounted_amt` decimal(10,2) DEFAULT '0.00' COMMENT 'Discounted Amt.',
  `discounted_per` decimal(10,2) DEFAULT '0.00' COMMENT 'Discounted Per.',
  `making_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Making Type',
  `making_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Making Rate',
  `making_discount_amt` decimal(10,2) DEFAULT '0.00' COMMENT 'Making Discount Amount',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `other_charge_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Other Charge Type',
  `other_weight` decimal(10,3) DEFAULT '0.000' COMMENT 'Other Weight',
  `other_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'Other Rate',
  `other_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Other Info',
  `other_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Other Amount',
  `hallmark_amount` decimal(10,2) DEFAULT '0.00' COMMENT 'Hallmark Amount',
  `hallmark_rate` decimal(10,2) DEFAULT '0.00' COMMENT 'HallMark Rate',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00' COMMENT 'Tax',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `reverse` decimal(10,2) DEFAULT '0.00' COMMENT 'Reverse',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `huid` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HUID No.',
  `category_id` int DEFAULT NULL COMMENT 'Category ID',
  `calculation_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Calculation Type',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_invoice_payments`
--

CREATE TABLE `tbl_purchase_invoice_payments` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount paid towards previous balance',
  `current_order_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount paid towards current order',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_orders`
--

CREATE TABLE `tbl_purchase_orders` (
  `id` int NOT NULL,
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_items`
--

CREATE TABLE `tbl_purchase_order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_order_payments`
--

CREATE TABLE `tbl_purchase_order_payments` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT '0.00',
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_quotations`
--

CREATE TABLE `tbl_purchase_quotations` (
  `id` int NOT NULL,
  `quotation_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `rate` decimal(15,6) DEFAULT '1.000000',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `ounce_rate` decimal(15,2) DEFAULT '0.00',
  `unfix_dmd_gms` tinyint(1) DEFAULT '0',
  `unfix_metal` tinyint(1) DEFAULT '0',
  `unfix` tinyint(1) DEFAULT '0',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `return_invoice` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `against_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_quotation_items`
--

CREATE TABLE `tbl_purchase_quotation_items` (
  `id` int NOT NULL,
  `quotation_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stone_weight` decimal(10,3) DEFAULT '0.000',
  `stone_rate` decimal(15,2) DEFAULT '0.00',
  `stone_amount` decimal(15,2) DEFAULT '0.00',
  `other_amount` decimal(15,2) DEFAULT '0.00',
  `quantity` decimal(10,2) DEFAULT '1.00',
  `metal_qty` decimal(12,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `metal_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,6) DEFAULT '0.000000',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `making_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `making_rate` decimal(15,2) DEFAULT '0.00',
  `metal_rate` decimal(15,2) DEFAULT NULL,
  `metal_value` decimal(15,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `tax_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'no_tax',
  `amount` decimal(15,2) DEFAULT '0.00',
  `rate` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `purchase_amount` decimal(15,2) DEFAULT '0.00',
  `sale_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_weight` decimal(15,2) DEFAULT '0.00',
  `diamond_weight` decimal(10,3) DEFAULT '0.000',
  `gemstone_weight` decimal(10,3) DEFAULT '0.000',
  `diamond_amount` decimal(15,2) DEFAULT '0.00',
  `discount_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_per` decimal(15,2) DEFAULT '0.00',
  `discount_amount` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_quotation_payments`
--

CREATE TABLE `tbl_purchase_quotation_payments` (
  `id` int NOT NULL,
  `quotation_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_from` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_returns`
--

CREATE TABLE `tbl_purchase_returns` (
  `id` int NOT NULL,
  `return_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `supplier_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_id` int DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `ounce_rate` decimal(15,2) DEFAULT '0.00',
  `unfix_dmd_gms` tinyint(1) DEFAULT '0',
  `unfix_metal` tinyint(1) DEFAULT '0',
  `unfix` tinyint(1) DEFAULT '0',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `credit_note` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_return_items`
--

CREATE TABLE `tbl_purchase_return_items` (
  `id` int NOT NULL,
  `return_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `net_amt_weight` decimal(15,2) DEFAULT '0.00',
  `diamond_weight` decimal(10,3) DEFAULT '0.000',
  `gemstone_weight` decimal(10,3) DEFAULT '0.000',
  `diamond_amount` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_purchase_return_payments`
--

CREATE TABLE `tbl_purchase_return_payments` (
  `id` int NOT NULL,
  `return_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_from` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_receipt_vouchers`
--

CREATE TABLE `tbl_receipt_vouchers` (
  `id` int NOT NULL,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `voucher_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `total_gold` decimal(10,3) DEFAULT '0.000',
  `total_silver` decimal(10,3) DEFAULT '0.000',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_receipt_voucher_items`
--

CREATE TABLE `tbl_receipt_voucher_items` (
  `id` int NOT NULL,
  `voucher_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal_id` int DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purity_wt` decimal(10,3) DEFAULT '0.000',
  `amount` decimal(15,2) DEFAULT '0.00',
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_remark`
--

CREATE TABLE `tbl_remark` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_invoices`
--

CREATE TABLE `tbl_repair_invoices` (
  `id` int NOT NULL,
  `repair_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `repair_invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_invoice_items`
--

CREATE TABLE `tbl_repair_invoice_items` (
  `id` int NOT NULL,
  `repair_invoice_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_invoice_payments`
--

CREATE TABLE `tbl_repair_invoice_payments` (
  `id` int NOT NULL,
  `repair_invoice_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `current_order_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_jobwork_orders`
--

CREATE TABLE `tbl_repair_jobwork_orders` (
  `id` int NOT NULL,
  `jobwork_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `repair_order_id` int NOT NULL,
  `repair_order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_jobwork_order_items`
--

CREATE TABLE `tbl_repair_jobwork_order_items` (
  `id` int NOT NULL,
  `repair_jobwork_order_id` int NOT NULL,
  `product_id` int DEFAULT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_orders`
--

CREATE TABLE `tbl_repair_orders` (
  `id` int NOT NULL,
  `order_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_order_items`
--

CREATE TABLE `tbl_repair_order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_repair_order_payments`
--

CREATE TABLE `tbl_repair_order_payments` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `current_order_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_fixing_direct`
--

CREATE TABLE `tbl_sale_fixing_direct` (
  `id` int NOT NULL,
  `ref_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `layaways` tinyint(1) DEFAULT '0',
  `against` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `against_of` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'USD',
  `currency_rate` decimal(10,2) DEFAULT '1.00',
  `goz` decimal(10,2) DEFAULT '0.00',
  `fixing_date` date DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `total_gross_wt` decimal(10,3) DEFAULT '0.000',
  `total_purity_wt` decimal(10,3) DEFAULT '0.000',
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `comment` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_fixing_direct_items`
--

CREATE TABLE `tbl_sale_fixing_direct_items` (
  `id` int NOT NULL,
  `fixing_id` int NOT NULL,
  `metal_id` int NOT NULL,
  `gross_wt` decimal(10,3) DEFAULT '0.000',
  `purity_wt` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `purity` decimal(10,2) DEFAULT '1.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_invoices`
--

CREATE TABLE `tbl_sale_invoices` (
  `id` int NOT NULL,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `previous_diamond` decimal(12,3) DEFAULT '0.000',
  `previous_gemstone` decimal(12,3) DEFAULT '0.000',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `discount_percent` decimal(10,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `adjusted_balance_used` decimal(14,2) DEFAULT '0.00' COMMENT 'Amount of adjusted balance used in this invoice',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `payment_comments` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array of comments: [{text, added_by, added_at}]',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `use_previous_balance` tinyint(1) DEFAULT '0' COMMENT '1=used previous balance on this invoice',
  `previous_balance_used_amt` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount used from previous balance (e.g. 500.00)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_invoice_items`
--

CREATE TABLE `tbl_sale_invoice_items` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0' COMMENT 'Display order (drag-and-drop)',
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stone_weight` decimal(10,3) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `metal_qty` decimal(12,2) DEFAULT '1.00',
  `metal_weight` decimal(12,4) DEFAULT '0.0000',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `metal_value` decimal(15,2) DEFAULT NULL,
  `metal_rate` decimal(15,2) DEFAULT NULL,
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `stone_amount` decimal(15,2) DEFAULT NULL,
  `diamond_amount` decimal(15,2) DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_invoice_payments`
--

CREATE TABLE `tbl_sale_invoice_payments` (
  `id` int NOT NULL,
  `invoice_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `current_order_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_orders`
--

CREATE TABLE `tbl_sale_orders` (
  `id` int NOT NULL,
  `order_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'AED',
  `currency_rate` decimal(10,4) DEFAULT '1.0000',
  `ref_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Standard',
  `previous_balance` decimal(10,2) DEFAULT '0.00',
  `previous_gold` decimal(10,3) DEFAULT '0.000',
  `previous_silver` decimal(10,3) DEFAULT '0.000',
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `additional_amt` decimal(10,2) DEFAULT '0.00',
  `net_total` decimal(10,2) DEFAULT '0.00',
  `reward_points` decimal(10,2) DEFAULT '0.00',
  `coupon_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coupon_discount` decimal(10,2) DEFAULT '0.00',
  `discount_amt` decimal(10,2) DEFAULT '0.00',
  `redeem_points` decimal(10,2) DEFAULT '0.00',
  `grand_total` decimal(10,2) DEFAULT '0.00',
  `advance_payment` decimal(10,2) DEFAULT '0.00',
  `metal_amt` decimal(10,2) DEFAULT '0.00',
  `round_off` decimal(10,2) DEFAULT '0.00',
  `paid_amt` decimal(10,2) DEFAULT '0.00',
  `balance_amt` decimal(10,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_order_items`
--

CREATE TABLE `tbl_sale_order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` decimal(10,3) DEFAULT '1.000',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(10,2) DEFAULT '0.00',
  `stone_charges` decimal(10,2) DEFAULT '0.00',
  `stone_amount` decimal(10,2) DEFAULT '0.00',
  `other_charges` decimal(10,2) DEFAULT '0.00',
  `other_amount` decimal(10,2) DEFAULT '0.00',
  `diamond_value` decimal(10,2) DEFAULT '0.00',
  `diamond_amount` decimal(10,2) DEFAULT '0.00',
  `gemstone_value` decimal(10,2) DEFAULT '0.00',
  `discount` decimal(10,2) DEFAULT '0.00',
  `metal_value` decimal(10,2) DEFAULT '0.00',
  `making_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Fix',
  `making_rate` decimal(10,2) DEFAULT '0.00',
  `making_amount` decimal(10,2) DEFAULT '0.00',
  `making_cost` decimal(10,2) DEFAULT '0.00',
  `amount` decimal(10,2) DEFAULT '0.00',
  `tax_percent` decimal(10,2) DEFAULT '0.00',
  `tax_amount` decimal(10,2) DEFAULT '0.00',
  `net_amount` decimal(10,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(10,2) DEFAULT '0.00',
  `purchase_amount` decimal(10,2) DEFAULT '0.00',
  `sale_amount` decimal(10,2) DEFAULT '0.00',
  `sale_amount_with` decimal(10,2) DEFAULT '0.00',
  `reverse` decimal(10,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `metal_unfix` tinyint(1) DEFAULT '0',
  `unfix` tinyint(1) DEFAULT '0',
  `ounce_rate` tinyint(1) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_order_payments`
--

CREATE TABLE `tbl_sale_order_payments` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `deposit_into` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00' COMMENT 'Amount paid towards previous balance',
  `diamond_category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` decimal(10,3) DEFAULT '0.000',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_quotations`
--

CREATE TABLE `tbl_sale_quotations` (
  `id` int NOT NULL,
  `quotation_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'AED',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quotation_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `additional_amt` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `reward_points` decimal(15,2) DEFAULT '0.00',
  `coupon_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coupon_discount` decimal(15,2) DEFAULT '0.00',
  `discount_amt` decimal(15,2) DEFAULT '0.00',
  `redeem_points` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `advance_payment` decimal(15,2) DEFAULT '0.00',
  `metal_amt` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `paid_amt` decimal(15,2) DEFAULT '0.00',
  `balance_amt` decimal(15,2) DEFAULT '0.00',
  `adjusted_balance_used` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `payment_comments` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `validity_days` int DEFAULT '30',
  `expiry_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `against_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_quotation_items`
--

CREATE TABLE `tbl_sale_quotation_items` (
  `id` int NOT NULL,
  `quotation_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `metal_qty` decimal(12,2) DEFAULT '1.00',
  `metal_weight` decimal(12,4) DEFAULT '0.0000',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `metal_rate` decimal(15,2) DEFAULT NULL,
  `metal_value` decimal(15,2) DEFAULT NULL,
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diamond_category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` int DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `diamond_amount` decimal(15,2) DEFAULT NULL,
  `stone_amount` decimal(15,2) DEFAULT NULL,
  `stone_weight` decimal(10,3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_quotation_payments`
--

CREATE TABLE `tbl_sale_quotation_payments` (
  `id` int NOT NULL,
  `quotation_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `previous_balance_amount` decimal(15,2) DEFAULT '0.00',
  `current_order_amount` decimal(15,2) DEFAULT '0.00',
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_returns`
--

CREATE TABLE `tbl_sale_returns` (
  `id` int NOT NULL,
  `return_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int DEFAULT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `against_of` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `against_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Direct, Sale Invoice, Sale Quotation',
  `against_id` int DEFAULT NULL COMMENT 'ID of selected Sale Invoice or Sale Quotation',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  `rate` decimal(15,6) DEFAULT '1.000000',
  `ref_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `layaways_id` int DEFAULT NULL,
  `fixing_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Standard',
  `ounce_rate` decimal(15,2) DEFAULT '0.00',
  `unfix_dmd_gms` tinyint(1) DEFAULT '0',
  `unfix_metal` tinyint(1) DEFAULT '0',
  `unfix` tinyint(1) DEFAULT '0',
  `previous_balance` decimal(15,2) DEFAULT '0.00',
  `previous_gold` decimal(15,2) DEFAULT '0.00',
  `previous_silver` decimal(15,2) DEFAULT '0.00',
  `subtotal` decimal(15,2) DEFAULT '0.00',
  `net_total` decimal(15,2) DEFAULT '0.00',
  `grand_total` decimal(15,2) DEFAULT '0.00',
  `round_off` decimal(15,2) DEFAULT '0.00',
  `credit_note` decimal(15,2) DEFAULT '0.00',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `payment_comments` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON: [{text, added_by, added_at}]',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_return_items`
--

CREATE TABLE `tbl_sale_return_items` (
  `id` int NOT NULL,
  `return_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Diamonds, GemStones, Jewellery',
  `carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '1.00',
  `metal_qty` decimal(12,2) DEFAULT '1.00',
  `metal_weight` decimal(12,4) DEFAULT '0.0000',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_weight` decimal(15,2) DEFAULT '0.00',
  `diamond_weight` decimal(10,3) DEFAULT '0.000',
  `gemstone_weight` decimal(10,3) DEFAULT '0.000',
  `diamond_amount` decimal(15,2) DEFAULT '0.00',
  `calculation_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rate X Gross Wt, Carat X Rate, Fix, etc.',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sale_return_payments`
--

CREATE TABLE `tbl_sale_return_payments` (
  `id` int NOT NULL,
  `return_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diamond_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_from` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deposit_into` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `weight` decimal(10,3) DEFAULT '0.000',
  `metal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `purity_carat` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_shape`
--

CREATE TABLE `tbl_shape` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_shape`
--

INSERT INTO `tbl_shape` (`id`, `name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'dsds', 1, 0, NULL, '2025-12-18 13:24:07', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sieve_size`
--

CREATE TABLE `tbl_sieve_size` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_size`
--

CREATE TABLE `tbl_size` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(150) DEFAULT NULL,
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_size`
--

INSERT INTO `tbl_size` (`id`, `name`, `description`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'ds', 'dsds', 1, 0, NULL, '2025-12-18 13:52:36', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock`
--

CREATE TABLE `tbl_stock` (
  `id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Barcode number for the stock entry',
  `branch_id` int NOT NULL,
  `metal_id` int DEFAULT NULL,
  `opening_weight` decimal(15,4) DEFAULT NULL,
  `opening_purity` decimal(10,4) DEFAULT NULL,
  `opening_qty` decimal(15,4) DEFAULT NULL,
  `final_weight` decimal(15,4) DEFAULT NULL,
  `rate` decimal(15,4) DEFAULT NULL,
  `value` decimal(15,4) DEFAULT NULL,
  `current_weight` decimal(15,4) DEFAULT NULL,
  `current_qty` decimal(15,4) DEFAULT NULL,
  `stock_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'opening',
  `transaction_date` date DEFAULT NULL,
  `stock_journal_id` int DEFAULT NULL COMMENT 'Purchase invoice item_id (journal batch)',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_stock`
--

INSERT INTO `tbl_stock` (`id`, `product_id`, `product_characteristic_id`, `barcode`, `branch_id`, `metal_id`, `opening_weight`, `opening_purity`, `opening_qty`, `final_weight`, `rate`, `value`, `current_weight`, `current_qty`, `stock_type`, `transaction_date`, `stock_journal_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'RN00002', 1, 1, 0.0000, 0.9000, 0.0000, 0.0000, 120.0000, 0.0000, 0.0000, 0.0000, 'purchase', '2026-03-21', NULL, 1, '2026-03-21 23:36:07', '2026-03-23 10:22:02'),
(2, 1, 1, 'RN00003', 1, 1, 0.0000, 0.9000, 0.0000, 0.0000, 120.0000, 0.0000, 0.0000, 0.0000, 'purchase', '2026-03-21', NULL, 1, '2026-03-21 23:36:07', '2026-03-21 23:40:37'),
(3, 1, 1, 'RN00004', 1, 1, 0.0000, 0.9000, 0.0000, 0.0000, 120.0000, 0.0000, 0.0000, 0.0000, 'purchase', '2026-03-21', NULL, 1, '2026-03-21 23:36:07', '2026-03-23 10:44:13'),
(4, 1, 1, NULL, 1, 1, 15.0000, 0.9000, 3.0000, 15.0000, 120.0000, 1701.0000, 15.0000, 3.0000, 'outward', '2026-03-21', NULL, 1, '2026-03-21 23:36:07', NULL),
(5, 1, 1, 'RN00003', 1, 1, 5.0000, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, 5.0000, 1.0000, 'outward', '2026-03-21', NULL, 1, '2026-03-21 23:40:37', NULL),
(6, 1, 1, 'RN00002', 1, 1, 5.0000, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, 5.0000, 1.0000, 'outward', '2026-03-23', NULL, 1, '2026-03-23 10:22:02', NULL),
(7, 1, 1, 'RN00004', 1, 1, 5.0000, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, 5.0000, 1.0000, 'outward', '2026-03-23', NULL, 1, '2026-03-23 10:44:13', NULL),
(8, 1, 1, 'RN00005', 1, 1, 0.0000, 0.9000, 0.0000, 0.0000, 120.0000, 0.0000, 0.0000, 0.0000, 'purchase', '2026-03-23', NULL, 1, '2026-03-23 12:01:46', '2026-03-23 12:09:01'),
(9, 1, 1, 'RN00006', 1, 1, 0.0000, 0.9000, 0.0000, 0.0000, 120.0000, 0.0000, 0.0000, 0.0000, 'purchase', '2026-03-23', NULL, 1, '2026-03-23 12:01:46', '2026-03-23 12:10:29'),
(10, 1, 1, 'RN00007', 1, 1, 0.0000, 0.9000, 0.0000, 0.0000, 120.0000, 0.0000, 0.0000, 0.0000, 'purchase', '2026-03-23', NULL, 1, '2026-03-23 12:01:46', '2026-03-23 15:08:04'),
(11, 1, 1, 'RN00008', 1, 1, 2.0000, 0.9000, 1.0000, 1.8000, 120.0000, 226.8000, 0.0000, 0.0000, 'purchase', '2026-03-23', NULL, 1, '2026-03-23 12:01:46', '2026-03-23 12:01:46'),
(12, 1, 1, NULL, 1, 1, 19.0000, 0.9000, 4.0000, 19.0000, 120.0000, 2154.6000, 19.0000, 4.0000, 'outward', '2026-03-23', NULL, 1, '2026-03-23 12:01:46', NULL),
(13, 1, 1, 'RN00005', 1, 1, 10.0000, 0.9000, 1.0000, 10.0000, 120.0000, 1134.0000, 10.0000, 1.0000, 'outward', '2026-03-23', NULL, 1, '2026-03-23 12:09:01', NULL),
(14, 1, 1, 'RN00006', 1, 1, 5.0000, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, 5.0000, 1.0000, 'outward', '2026-03-23', NULL, 1, '2026-03-23 12:10:29', NULL),
(15, 1, 1, 'RN00007', 1, 1, 2.0000, 0.9000, 1.0000, 2.0000, 120.0000, 226.8000, 2.0000, 1.0000, 'outward', '2026-03-23', NULL, 1, '2026-03-23 15:08:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_journal`
--

CREATE TABLE `tbl_stock_journal` (
  `id` int NOT NULL,
  `sj_invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Stock Journal invoice number (SJ-1, SJ-2, etc.)',
  `item_id` int DEFAULT NULL COMMENT 'Reference to tbl_purchase_invoice_items.id (NULL for product opening)',
  `invoice_id` int DEFAULT NULL COMMENT 'Reference to tbl_purchase_invoices.id (NULL for product opening)',
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Purchase invoice number for reference',
  `sj_date` date NOT NULL COMMENT 'Stock journal date',
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metal_id` int DEFAULT NULL,
  `metal_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'gold, silver, diamond, loose',
  `quantity` decimal(10,2) DEFAULT '1.00',
  `gross_weight` decimal(10,3) DEFAULT '0.000',
  `less_weight` decimal(10,3) DEFAULT '0.000',
  `net_weight` decimal(10,3) DEFAULT '0.000',
  `purity` decimal(10,2) DEFAULT '0.00',
  `purity_weight` decimal(10,3) DEFAULT '0.000',
  `pure_weight` decimal(10,3) DEFAULT '0.000',
  `final_weight` decimal(10,3) DEFAULT '0.000',
  `rate` decimal(15,2) DEFAULT '0.00',
  `amount` decimal(15,2) DEFAULT '0.00',
  `making_amount` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `net_amount` decimal(15,2) DEFAULT '0.00',
  `net_amt_with_tax` decimal(15,2) DEFAULT '0.00',
  `group_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active' COMMENT 'active, completed, cancelled',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `rfid_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `voucher_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `design_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `huid_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calculation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `karat` decimal(10,2) DEFAULT NULL,
  `pkt_wt` decimal(10,3) DEFAULT NULL,
  `pkt_less_wt` decimal(10,3) DEFAULT NULL,
  `requested_purity` decimal(10,2) DEFAULT NULL,
  `requested` decimal(10,3) DEFAULT NULL,
  `gold_loss_1` decimal(10,3) DEFAULT NULL,
  `gold_loss_2` decimal(10,3) DEFAULT NULL,
  `setting_charge` decimal(15,2) DEFAULT NULL,
  `wastage_per` decimal(10,2) DEFAULT NULL,
  `wastage_wt` decimal(10,3) DEFAULT NULL,
  `alloy_wt` decimal(10,3) DEFAULT NULL,
  `metal_value` decimal(15,2) DEFAULT NULL,
  `metal_cost` decimal(15,2) DEFAULT NULL,
  `discount_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_per` decimal(10,2) DEFAULT NULL,
  `discount_amount` decimal(15,2) DEFAULT NULL,
  `discount` decimal(15,2) DEFAULT NULL,
  `making_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `making_rate` decimal(10,2) DEFAULT NULL,
  `making_cost` decimal(15,2) DEFAULT NULL,
  `minimum_price` decimal(15,2) DEFAULT NULL,
  `stone_charge_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stone_weight` decimal(10,3) DEFAULT NULL,
  `stone_rate` decimal(10,2) DEFAULT NULL,
  `stone_amount` decimal(15,2) DEFAULT NULL,
  `stone_cost` decimal(15,2) DEFAULT NULL,
  `diamond_amount` decimal(15,2) DEFAULT NULL,
  `purchase_amount` decimal(15,2) DEFAULT NULL,
  `sale_amount` decimal(15,2) DEFAULT NULL,
  `other_charge_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_weight` decimal(10,3) DEFAULT NULL,
  `other_rate` decimal(10,2) DEFAULT NULL,
  `other_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `other_amount` decimal(15,2) DEFAULT NULL,
  `hallmark_amount` decimal(15,2) DEFAULT NULL,
  `hallmark_rate` decimal(10,2) DEFAULT NULL,
  `reverse` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_stock_journal`
--

INSERT INTO `tbl_stock_journal` (`id`, `sj_invoice_no`, `item_id`, `invoice_id`, `invoice_no`, `sj_date`, `barcode`, `code`, `product_id`, `product_characteristic_id`, `product_name`, `metal_id`, `metal_type`, `quantity`, `gross_weight`, `less_weight`, `net_weight`, `purity`, `purity_weight`, `pure_weight`, `final_weight`, `rate`, `amount`, `making_amount`, `tax_amount`, `net_amount`, `net_amt_with_tax`, `group_name`, `comment`, `status`, `created_by`, `created_at`, `updated_at`, `rfid_code`, `voucher_type`, `design_no`, `huid_no`, `category`, `calculation`, `location`, `karat`, `pkt_wt`, `pkt_less_wt`, `requested_purity`, `requested`, `gold_loss_1`, `gold_loss_2`, `setting_charge`, `wastage_per`, `wastage_wt`, `alloy_wt`, `metal_value`, `metal_cost`, `discount_type`, `discount_per`, `discount_amount`, `discount`, `making_type`, `making_rate`, `making_cost`, `minimum_price`, `stone_charge_type`, `stone_weight`, `stone_rate`, `stone_amount`, `stone_cost`, `diamond_amount`, `purchase_amount`, `sale_amount`, `other_charge_type`, `other_weight`, `other_rate`, `other_info`, `other_amount`, `hallmark_amount`, `hallmark_rate`, `reverse`) VALUES
(1, 'SJ-1-1', NULL, NULL, NULL, '2026-03-21', 'RN00002', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 5.000, 0.000, 5.000, 0.90, 0.045, 4.500, 4.500, 120.00, 540.00, 0.00, 27.00, 540.00, 567.00, NULL, NULL, 'active', 1, '2026-03-21 23:36:07', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 540.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 540.00, 540.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00),
(2, 'SJ-1-2', NULL, NULL, NULL, '2026-03-21', 'RN00003', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 5.000, 0.000, 5.000, 0.90, 0.045, 4.500, 4.500, 120.00, 540.00, 0.00, 27.00, 540.00, 567.00, NULL, NULL, 'active', 1, '2026-03-21 23:36:07', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 540.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 540.00, 540.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00),
(3, 'SJ-1-3', NULL, NULL, NULL, '2026-03-21', 'RN00004', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 5.000, 0.000, 5.000, 0.90, 0.045, 4.500, 4.500, 120.00, 540.00, 0.00, 27.00, 540.00, 567.00, NULL, NULL, 'active', 1, '2026-03-21 23:36:07', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 540.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 540.00, 540.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00),
(4, 'SJ-2-1', NULL, NULL, NULL, '2026-03-23', 'RN00005', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 10.000, 0.000, 10.000, 0.90, 0.090, 9.000, 9.000, 120.00, 1080.00, 0.00, 54.00, 1080.00, 1134.00, NULL, NULL, 'active', 1, '2026-03-23 12:01:46', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1080.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 1080.00, 1080.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00),
(5, 'SJ-2-2', NULL, NULL, NULL, '2026-03-23', 'RN00006', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 5.000, 0.000, 5.000, 0.90, 0.045, 4.500, 4.500, 120.00, 540.00, 0.00, 27.00, 540.00, 567.00, NULL, NULL, 'active', 1, '2026-03-23 12:01:46', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 540.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 540.00, 540.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00),
(6, 'SJ-2-3', NULL, NULL, NULL, '2026-03-23', 'RN00007', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 2.000, 0.000, 2.000, 0.90, 0.018, 1.800, 1.800, 120.00, 216.00, 0.00, 10.80, 216.00, 226.80, NULL, NULL, 'active', 1, '2026-03-23 12:01:46', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 216.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 216.00, 216.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00),
(7, 'SJ-2-4', NULL, NULL, NULL, '2026-03-23', 'RN00008', NULL, 1, 1, 'Ring - Gold', 1, NULL, 1.00, 2.000, 0.000, 2.000, 0.90, 0.018, 1.800, 1.800, 120.00, 216.00, 0.00, 10.80, 216.00, 226.80, NULL, NULL, 'active', 1, '2026-03-23 12:01:46', NULL, NULL, 'product_opening', NULL, NULL, NULL, NULL, NULL, 0.00, 0.000, 0.000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 216.00, NULL, NULL, NULL, NULL, 0.00, 'Fix', 0.00, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, 0.00, 216.00, 216.00, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_journal_images`
--

CREATE TABLE `tbl_stock_journal_images` (
  `id` int NOT NULL,
  `item_id` int NOT NULL DEFAULT '0',
  `barcode_no` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `image_path` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_stock_journal_images`
--

INSERT INTO `tbl_stock_journal_images` (`id`, `item_id`, `barcode_no`, `image_path`, `created_at`) VALUES
(1, 0, 'RN00002', 'uploads/stock_journal/20260318173417_49056a44_1.jpg', '2026-03-18 17:34:17'),
(2, 0, 'RN00002', 'uploads/stock_journal/20260318173417_9a461c14_2.jpg', '2026-03-18 17:34:17');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_stock_transfer_pending`
--

CREATE TABLE `tbl_stock_transfer_pending` (
  `id` int NOT NULL,
  `from_branch_id` int NOT NULL,
  `to_branch_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_characteristic_id` int DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metal_id` int DEFAULT NULL,
  `opening_purity` decimal(15,4) DEFAULT NULL,
  `move_qty` decimal(15,4) NOT NULL,
  `move_wt` decimal(15,4) NOT NULL,
  `rate` decimal(15,4) DEFAULT NULL,
  `value` decimal(15,4) DEFAULT NULL,
  `transfer_date` date DEFAULT NULL,
  `source_stock_id` int DEFAULT NULL,
  `outward_stock_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `received_stock_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `received_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_stock_transfer_pending`
--

INSERT INTO `tbl_stock_transfer_pending` (`id`, `from_branch_id`, `to_branch_id`, `product_id`, `product_characteristic_id`, `barcode`, `metal_id`, `opening_purity`, `move_qty`, `move_wt`, `rate`, `value`, `transfer_date`, `source_stock_id`, `outward_stock_id`, `status`, `received_stock_id`, `created_at`, `received_at`) VALUES
(1, 1, 2, 1, 1, 'RN00003', 1, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, '2026-03-21', 2, 5, 'received', 5, '2026-03-21 23:40:37', '2026-03-23 10:51:10'),
(2, 1, 2, 1, 1, 'RN00002', 1, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, '2026-03-23', 1, 6, 'pending', NULL, '2026-03-23 10:22:02', NULL),
(3, 1, 2, 1, 1, 'RN00004', 1, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, '2026-03-23', 3, 7, 'pending', NULL, '2026-03-23 10:44:13', NULL),
(4, 1, 2, 1, 1, 'RN00005', 1, 0.9000, 1.0000, 10.0000, 120.0000, 1134.0000, '2026-03-23', 8, 13, 'received', 7, '2026-03-23 12:09:01', '2026-03-23 12:09:50'),
(5, 1, 2, 1, 1, 'RN00006', 1, 0.9000, 1.0000, 5.0000, 120.0000, 567.0000, '2026-03-23', 9, 14, 'received', 9, '2026-03-23 12:10:29', '2026-03-23 12:10:37'),
(6, 1, 2, 1, 1, 'RN00007', 1, 0.9000, 1.0000, 2.0000, 120.0000, 226.8000, '2026-03-23', 10, 15, 'received', 11, '2026-03-23 15:08:04', '2026-03-23 15:08:44');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_task_type`
--

CREATE TABLE `tbl_task_type` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `used_in` enum('Sales','Purchase','Both') DEFAULT 'Both',
  `status` tinyint DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_task_type`
--

INSERT INTO `tbl_task_type` (`id`, `name`, `used_in`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'dsds', 'Both', 1, 0, NULL, '2025-12-19 10:55:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_taxes`
--

CREATE TABLE `tbl_taxes` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `applicable_for` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Product',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_taxes`
--

INSERT INTO `tbl_taxes` (`id`, `name`, `applicable_for`, `status`, `created_at`) VALUES
(1, 'VAT', 'Product', 1, '2025-12-29 16:00:12');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_tax_master`
--

CREATE TABLE `tbl_tax_master` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Tax name (e.g. VAT, TAX BAH)',
  `default_value` decimal(10,2) DEFAULT '0.00' COMMENT 'Default % or value shown on product opening',
  `default_calculation_mode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Product Amount' COMMENT 'Default calculation mode name',
  `sort_order` int DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_tax_master`
--

INSERT INTO `tbl_tax_master` (`id`, `name`, `default_value`, `default_calculation_mode`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'VAT', 5.00, 'Product Amount', 1, 1, '2026-03-11 16:31:00', NULL),
(2, 'TAX BAH', 10.00, 'Product Amount', 2, 0, '2026-03-11 16:31:00', '2026-03-11 23:07:05'),
(3, 'SGST', 9.00, 'Product Amount', 0, 0, '2026-03-11 16:31:28', '2026-03-15 23:56:00'),
(4, 'CGST', 9.00, 'Product Amount', 0, 0, '2026-03-11 16:31:35', '2026-03-15 23:56:04'),
(5, 'IGST', 18.00, 'Product Amount', 0, 0, '2026-03-11 16:31:46', '2026-03-15 23:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_unit`
--

CREATE TABLE `tbl_unit` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `formal_name` varchar(100) NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_unit`
--

INSERT INTO `tbl_unit` (`id`, `name`, `formal_name`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Test', 'uuuuudsds', 1, 0, 0, '2025-12-18 12:06:38', '2025-12-18 12:06:44'),
(2, 'dddd', 'ddd', 0, 0, 0, '2025-12-22 09:32:56', '2025-12-22 09:33:01');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_unit_conversion`
--

CREATE TABLE `tbl_unit_conversion` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `unit_id` int NOT NULL,
  `conversion_rate` decimal(10,4) NOT NULL,
  `quantity` decimal(10,4) DEFAULT '1.0000',
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_unit_conversion`
--

INSERT INTO `tbl_unit_conversion` (`id`, `name`, `unit_id`, `conversion_rate`, `quantity`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Test name', 1, 111.0000, 1.0000, 1, 0, 0, '2025-12-18 12:18:41', '2025-12-18 16:37:15'),
(2, 'dasdada', 1, 1121.0000, 12.0000, 1, 0, NULL, '2025-12-18 12:21:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_users`
--

CREATE TABLE `tbl_users` (
  `id` int NOT NULL,
  `Fname` varchar(100) DEFAULT NULL,
  `Lname` varchar(100) DEFAULT NULL,
  `Username` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `EmailId` varchar(100) DEFAULT NULL,
  `Password` varchar(50) DEFAULT NULL,
  `Status` enum('1','0') NOT NULL,
  `CreatedBy` int NOT NULL,
  `CreatedDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ModifiedBy` int NOT NULL,
  `ModifiedDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `tbl_users`
--

INSERT INTO `tbl_users` (`id`, `Fname`, `Lname`, `Username`, `Phone`, `EmailId`, `Password`, `Status`, `CreatedBy`, `CreatedDate`, `ModifiedBy`, `ModifiedDate`) VALUES
(1, 'Rajat', 'Dhanwalkar', 'admin', '9595454907', 'rajatdh07@gmail.com', '12345', '1', 0, '2025-12-26 08:07:50', 0, '2025-12-26 08:13:52');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_column_preferences`
--

CREATE TABLE `tbl_user_column_preferences` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `page_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'product-opening',
  `tab_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'Tab: 1=Gold, 2=Silver, 3=Platinum (metal_id)',
  `column_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `column_order` int NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user_column_preferences`
--

INSERT INTO `tbl_user_column_preferences` (`id`, `user_id`, `page_name`, `tab_key`, `column_key`, `column_order`, `is_visible`, `created_at`, `updated_at`) VALUES
(1, 1, 'sale-invoice-product-modal', 'main', 'purchase-amount', 27, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(2, 1, 'sale-invoice-product-modal', 'main', 'sale-amount', 28, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(3, 1, 'sale-invoice-product-modal', 'main', 'sale-amount-with', 29, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(4, 1, 'sale-invoice-product-modal', 'main', 'net-amt', 30, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(5, 1, 'sale-invoice-product-modal', 'main', 'tax-type', 31, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(6, 1, 'sale-invoice-product-modal', 'main', 'tax-percent', 32, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(7, 1, 'sale-invoice-product-modal', 'main', 'tax', 33, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(8, 1, 'sale-invoice-product-modal', 'main', 'id', 0, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(9, 1, 'sale-invoice-product-modal', 'main', 'rfid', 1, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(10, 1, 'sale-invoice-product-modal', 'main', 'voucher-type', 2, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(11, 1, 'sale-invoice-product-modal', 'main', 'photo', 3, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(12, 1, 'sale-invoice-product-modal', 'main', 'barcode', 4, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(13, 1, 'sale-invoice-product-modal', 'main', 'design-no', 5, 1, '2026-03-23 12:07:03', '2026-03-23 15:06:08'),
(14, 1, 'sale-invoice-product-modal', 'main', 'huid', 6, 1, '2026-03-23 12:07:03', '2026-03-23 15:06:09'),
(15, 1, 'sale-invoice-product-modal', 'main', 'category', 9, 1, '2026-03-23 12:07:03', '2026-03-23 15:06:13'),
(16, 1, 'sale-invoice-product-modal', 'main', 'calculation', 7, 1, '2026-03-23 12:07:03', '2026-03-23 15:06:13'),
(17, 1, 'sale-invoice-product-modal', 'main', 'product', 10, 1, '2026-03-23 12:07:03', '2026-03-23 15:06:13'),
(18, 1, 'sale-invoice-product-modal', 'main', 'location', 8, 1, '2026-03-23 12:07:03', '2026-03-23 15:06:13'),
(19, 1, 'sale-invoice-product-modal', 'main', 'pkt-wt', 16, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(20, 1, 'sale-invoice-product-modal', 'main', 'pkt-less-wt', 17, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(21, 1, 'sale-invoice-product-modal', 'main', 'gross-wt', 18, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(22, 1, 'sale-invoice-product-modal', 'main', 'stone-weight', 19, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(23, 1, 'sale-invoice-product-modal', 'main', 'less-wt', 20, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(24, 1, 'sale-invoice-product-modal', 'main', 'net-wt', 21, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(25, 1, 'sale-invoice-product-modal', 'main', 'quantity', 22, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(26, 1, 'sale-invoice-product-modal', 'main', 'rate', 23, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(27, 1, 'sale-invoice-product-modal', 'main', 'amount', 24, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(28, 1, 'sale-invoice-product-modal', 'main', 'metal-qty', 34, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(29, 1, 'sale-invoice-product-modal', 'main', 'metal-weight', 35, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(30, 1, 'sale-invoice-product-modal', 'main', 'carat', 36, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(31, 1, 'sale-invoice-product-modal', 'main', 'purity', 37, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(32, 1, 'sale-invoice-product-modal', 'main', 'purity-wt', 38, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(33, 1, 'sale-invoice-product-modal', 'main', 'gold-loss1', 39, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(34, 1, 'sale-invoice-product-modal', 'main', 'gold-loss2', 40, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(35, 1, 'sale-invoice-product-modal', 'main', 'metal-loss-value', 41, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(36, 1, 'sale-invoice-product-modal', 'main', 'wastage-per', 42, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(37, 1, 'sale-invoice-product-modal', 'main', 'wastage-wt', 43, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(38, 1, 'sale-invoice-product-modal', 'main', 'metal-rate', 44, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(39, 1, 'sale-invoice-product-modal', 'main', 'metal-value', 45, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(40, 1, 'sale-invoice-product-modal', 'main', 'metal-cost', 46, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(41, 1, 'sale-invoice-product-modal', 'main', 'requested-purity', 47, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(42, 1, 'sale-invoice-product-modal', 'main', 'requested', 48, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(43, 1, 'sale-invoice-product-modal', 'main', 'setting-charge', 49, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(44, 1, 'sale-invoice-product-modal', 'main', 'final-wt', 50, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(45, 1, 'sale-invoice-product-modal', 'main', 'alloy-wt', 51, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(46, 1, 'sale-invoice-product-modal', 'main', 'discount-type', 52, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(47, 1, 'sale-invoice-product-modal', 'main', 'discount-per', 53, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(48, 1, 'sale-invoice-product-modal', 'main', 'discount-amount', 54, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(49, 1, 'sale-invoice-product-modal', 'main', 'discount', 55, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(50, 1, 'sale-invoice-product-modal', 'main', 'making-type', 56, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(51, 1, 'sale-invoice-product-modal', 'main', 'making-rate', 57, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(52, 1, 'sale-invoice-product-modal', 'main', 'making-discount-amt', 58, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(53, 1, 'sale-invoice-product-modal', 'main', 'making-amount', 59, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(54, 1, 'sale-invoice-product-modal', 'main', 'making-actual-value', 60, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(55, 1, 'sale-invoice-product-modal', 'main', 'making-cost', 61, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(56, 1, 'sale-invoice-product-modal', 'main', 'min-price', 62, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(57, 1, 'sale-invoice-product-modal', 'main', 'minimum', 63, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(58, 1, 'sale-invoice-product-modal', 'main', 'stone-charge-type', 64, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(59, 1, 'sale-invoice-product-modal', 'main', 'stone-rate', 65, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(60, 1, 'sale-invoice-product-modal', 'main', 'stone-amount', 66, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(61, 1, 'sale-invoice-product-modal', 'main', 'stone-cost', 67, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(62, 1, 'sale-invoice-product-modal', 'main', 'diamond-amount', 68, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:51'),
(63, 1, 'sale-invoice-product-modal', 'main', 'other-charge-type', 11, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(64, 1, 'sale-invoice-product-modal', 'main', 'other-weight', 12, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(65, 1, 'sale-invoice-product-modal', 'main', 'other-rate', 13, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(66, 1, 'sale-invoice-product-modal', 'main', 'other-info', 14, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(67, 1, 'sale-invoice-product-modal', 'main', 'other-amount', 15, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(68, 1, 'sale-invoice-product-modal', 'main', 'hallmark-amount', 25, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(69, 1, 'sale-invoice-product-modal', 'main', 'hallmark-rate', 26, 1, '2026-03-23 12:07:03', '2026-03-23 15:05:59'),
(70, 1, 'sale-invoice-product-modal', 'main', 'net-amt-tax', 69, 1, '2026-03-23 12:07:03', NULL),
(71, 1, 'sale-invoice-product-modal', 'main', 'reverse', 70, 1, '2026-03-23 12:07:03', NULL),
(214, 1, 'sale-invoice-product-modal', '4', 'checkbox', 0, 1, '2026-03-23 12:07:52', NULL),
(215, 1, 'sale-invoice-product-modal', '4', 'id', 1, 0, '2026-03-23 12:07:52', NULL),
(216, 1, 'sale-invoice-product-modal', '4', 'rfid', 2, 0, '2026-03-23 12:07:52', NULL),
(217, 1, 'sale-invoice-product-modal', '4', 'voucher-type', 3, 0, '2026-03-23 12:07:52', NULL),
(218, 1, 'sale-invoice-product-modal', '4', 'photo', 4, 0, '2026-03-23 12:07:52', NULL),
(219, 1, 'sale-invoice-product-modal', '4', 'barcode', 5, 0, '2026-03-23 12:07:52', NULL),
(220, 1, 'sale-invoice-product-modal', '4', 'design-no', 6, 0, '2026-03-23 12:07:52', NULL),
(221, 1, 'sale-invoice-product-modal', '4', 'huid', 7, 0, '2026-03-23 12:07:52', NULL),
(222, 1, 'sale-invoice-product-modal', '4', 'category', 8, 0, '2026-03-23 12:07:52', NULL),
(223, 1, 'sale-invoice-product-modal', '4', 'calculation', 9, 0, '2026-03-23 12:07:52', NULL),
(224, 1, 'sale-invoice-product-modal', '4', 'product', 10, 0, '2026-03-23 12:07:52', NULL),
(225, 1, 'sale-invoice-product-modal', '4', 'location', 11, 0, '2026-03-23 12:07:52', NULL),
(226, 1, 'sale-invoice-product-modal', '4', 'pkt-wt', 12, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(227, 1, 'sale-invoice-product-modal', '4', 'pkt-less-wt', 13, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(228, 1, 'sale-invoice-product-modal', '4', 'gross-wt', 14, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(229, 1, 'sale-invoice-product-modal', '4', 'stone-weight', 15, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(230, 1, 'sale-invoice-product-modal', '4', 'less-wt', 16, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(231, 1, 'sale-invoice-product-modal', '4', 'net-wt', 17, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(232, 1, 'sale-invoice-product-modal', '4', 'quantity', 18, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(233, 1, 'sale-invoice-product-modal', '4', 'rate', 19, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(234, 1, 'sale-invoice-product-modal', '4', 'amount', 20, 0, '2026-03-23 12:07:52', '2026-03-23 12:07:58'),
(235, 1, 'sale-invoice-product-modal', '4', 'metal-qty', 21, 1, '2026-03-23 12:07:52', NULL),
(236, 1, 'sale-invoice-product-modal', '4', 'metal-weight', 22, 1, '2026-03-23 12:07:52', NULL),
(237, 1, 'sale-invoice-product-modal', '4', 'carat', 23, 1, '2026-03-23 12:07:52', NULL),
(238, 1, 'sale-invoice-product-modal', '4', 'purity', 24, 1, '2026-03-23 12:07:52', NULL),
(239, 1, 'sale-invoice-product-modal', '4', 'purity-wt', 25, 1, '2026-03-23 12:07:52', NULL),
(240, 1, 'sale-invoice-product-modal', '4', 'gold-loss1', 26, 1, '2026-03-23 12:07:52', NULL),
(241, 1, 'sale-invoice-product-modal', '4', 'gold-loss2', 27, 1, '2026-03-23 12:07:52', NULL),
(242, 1, 'sale-invoice-product-modal', '4', 'metal-loss-value', 28, 1, '2026-03-23 12:07:52', NULL),
(243, 1, 'sale-invoice-product-modal', '4', 'wastage-per', 29, 1, '2026-03-23 12:07:52', NULL),
(244, 1, 'sale-invoice-product-modal', '4', 'wastage-wt', 30, 1, '2026-03-23 12:07:52', NULL),
(245, 1, 'sale-invoice-product-modal', '4', 'metal-rate', 31, 1, '2026-03-23 12:07:52', NULL),
(246, 1, 'sale-invoice-product-modal', '4', 'metal-value', 32, 1, '2026-03-23 12:07:52', NULL),
(247, 1, 'sale-invoice-product-modal', '4', 'metal-cost', 33, 1, '2026-03-23 12:07:52', NULL),
(248, 1, 'sale-invoice-product-modal', '4', 'requested-purity', 34, 1, '2026-03-23 12:07:52', NULL),
(249, 1, 'sale-invoice-product-modal', '4', 'requested', 35, 1, '2026-03-23 12:07:52', NULL),
(250, 1, 'sale-invoice-product-modal', '4', 'setting-charge', 36, 1, '2026-03-23 12:07:52', NULL),
(251, 1, 'sale-invoice-product-modal', '4', 'final-wt', 37, 1, '2026-03-23 12:07:52', NULL),
(252, 1, 'sale-invoice-product-modal', '4', 'alloy-wt', 38, 1, '2026-03-23 12:07:52', NULL),
(253, 1, 'sale-invoice-product-modal', '4', 'discount-type', 39, 1, '2026-03-23 12:07:52', NULL),
(254, 1, 'sale-invoice-product-modal', '4', 'discount-per', 40, 1, '2026-03-23 12:07:52', NULL),
(255, 1, 'sale-invoice-product-modal', '4', 'discount-amount', 41, 1, '2026-03-23 12:07:52', NULL),
(256, 1, 'sale-invoice-product-modal', '4', 'discount', 42, 1, '2026-03-23 12:07:52', NULL),
(257, 1, 'sale-invoice-product-modal', '4', 'making-type', 43, 1, '2026-03-23 12:07:52', NULL),
(258, 1, 'sale-invoice-product-modal', '4', 'making-rate', 44, 1, '2026-03-23 12:07:52', NULL),
(259, 1, 'sale-invoice-product-modal', '4', 'making-discount-amt', 45, 1, '2026-03-23 12:07:52', NULL),
(260, 1, 'sale-invoice-product-modal', '4', 'making-amount', 46, 1, '2026-03-23 12:07:52', NULL),
(261, 1, 'sale-invoice-product-modal', '4', 'making-actual-value', 47, 1, '2026-03-23 12:07:52', NULL),
(262, 1, 'sale-invoice-product-modal', '4', 'making-cost', 48, 1, '2026-03-23 12:07:52', NULL),
(263, 1, 'sale-invoice-product-modal', '4', 'min-price', 49, 1, '2026-03-23 12:07:52', NULL),
(264, 1, 'sale-invoice-product-modal', '4', 'minimum', 50, 1, '2026-03-23 12:07:52', NULL),
(265, 1, 'sale-invoice-product-modal', '4', 'stone-charge-type', 51, 1, '2026-03-23 12:07:52', NULL),
(266, 1, 'sale-invoice-product-modal', '4', 'stone-rate', 52, 1, '2026-03-23 12:07:52', NULL),
(267, 1, 'sale-invoice-product-modal', '4', 'stone-amount', 53, 1, '2026-03-23 12:07:52', NULL),
(268, 1, 'sale-invoice-product-modal', '4', 'stone-cost', 54, 1, '2026-03-23 12:07:52', NULL),
(269, 1, 'sale-invoice-product-modal', '4', 'diamond-amount', 55, 1, '2026-03-23 12:07:52', NULL),
(270, 1, 'sale-invoice-product-modal', '4', 'purchase-amount', 56, 1, '2026-03-23 12:07:52', NULL),
(271, 1, 'sale-invoice-product-modal', '4', 'sale-amount', 57, 1, '2026-03-23 12:07:52', NULL),
(272, 1, 'sale-invoice-product-modal', '4', 'sale-amount-with', 58, 1, '2026-03-23 12:07:52', NULL),
(273, 1, 'sale-invoice-product-modal', '4', 'net-amt', 59, 1, '2026-03-23 12:07:52', NULL),
(274, 1, 'sale-invoice-product-modal', '4', 'tax-type', 60, 1, '2026-03-23 12:07:52', NULL),
(275, 1, 'sale-invoice-product-modal', '4', 'tax-percent', 61, 1, '2026-03-23 12:07:52', NULL),
(276, 1, 'sale-invoice-product-modal', '4', 'tax', 62, 1, '2026-03-23 12:07:52', NULL),
(277, 1, 'sale-invoice-product-modal', '4', 'other-charge-type', 63, 1, '2026-03-23 12:07:52', NULL),
(278, 1, 'sale-invoice-product-modal', '4', 'other-weight', 64, 1, '2026-03-23 12:07:52', NULL),
(279, 1, 'sale-invoice-product-modal', '4', 'other-rate', 65, 1, '2026-03-23 12:07:52', NULL),
(280, 1, 'sale-invoice-product-modal', '4', 'other-info', 66, 1, '2026-03-23 12:07:52', NULL),
(281, 1, 'sale-invoice-product-modal', '4', 'other-amount', 67, 1, '2026-03-23 12:07:52', NULL),
(282, 1, 'sale-invoice-product-modal', '4', 'hallmark-amount', 68, 1, '2026-03-23 12:07:52', NULL),
(283, 1, 'sale-invoice-product-modal', '4', 'hallmark-rate', 69, 1, '2026-03-23 12:07:52', NULL),
(284, 1, 'sale-invoice-product-modal', '4', 'net-amt-tax', 70, 1, '2026-03-23 12:07:52', NULL),
(285, 1, 'sale-invoice-product-modal', '4', 'reverse', 71, 1, '2026-03-23 12:07:52', NULL),
(1210, 1, 'sale-invoice-product-modal', '1', 'checkbox', 0, 1, '2026-03-23 15:06:27', NULL),
(1211, 1, 'sale-invoice-product-modal', '1', 'id', 1, 0, '2026-03-23 15:06:27', NULL),
(1212, 1, 'sale-invoice-product-modal', '1', 'rfid', 2, 0, '2026-03-23 15:06:27', NULL),
(1213, 1, 'sale-invoice-product-modal', '1', 'voucher-type', 3, 0, '2026-03-23 15:06:27', NULL),
(1214, 1, 'sale-invoice-product-modal', '1', 'photo', 4, 0, '2026-03-23 15:06:27', NULL),
(1215, 1, 'sale-invoice-product-modal', '1', 'barcode', 5, 0, '2026-03-23 15:06:27', NULL),
(1216, 1, 'sale-invoice-product-modal', '1', 'design-no', 6, 0, '2026-03-23 15:06:27', NULL),
(1217, 1, 'sale-invoice-product-modal', '1', 'huid', 7, 0, '2026-03-23 15:06:27', NULL),
(1218, 1, 'sale-invoice-product-modal', '1', 'category', 8, 0, '2026-03-23 15:06:27', NULL),
(1219, 1, 'sale-invoice-product-modal', '1', 'calculation', 9, 0, '2026-03-23 15:06:27', NULL),
(1220, 1, 'sale-invoice-product-modal', '1', 'product', 10, 0, '2026-03-23 15:06:27', NULL),
(1221, 1, 'sale-invoice-product-modal', '1', 'location', 11, 0, '2026-03-23 15:06:27', NULL),
(1222, 1, 'sale-invoice-product-modal', '1', 'pkt-wt', 12, 0, '2026-03-23 15:06:27', NULL),
(1223, 1, 'sale-invoice-product-modal', '1', 'pkt-less-wt', 13, 0, '2026-03-23 15:06:27', NULL),
(1224, 1, 'sale-invoice-product-modal', '1', 'gross-wt', 14, 0, '2026-03-23 15:06:27', NULL),
(1225, 1, 'sale-invoice-product-modal', '1', 'stone-weight', 15, 0, '2026-03-23 15:06:27', NULL),
(1226, 1, 'sale-invoice-product-modal', '1', 'less-wt', 16, 0, '2026-03-23 15:06:27', NULL),
(1227, 1, 'sale-invoice-product-modal', '1', 'net-wt', 17, 0, '2026-03-23 15:06:27', NULL),
(1228, 1, 'sale-invoice-product-modal', '1', 'quantity', 18, 0, '2026-03-23 15:06:27', NULL),
(1229, 1, 'sale-invoice-product-modal', '1', 'rate', 19, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:31'),
(1230, 1, 'sale-invoice-product-modal', '1', 'amount', 20, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:31'),
(1231, 1, 'sale-invoice-product-modal', '1', 'metal-qty', 21, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1232, 1, 'sale-invoice-product-modal', '1', 'metal-weight', 22, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1233, 1, 'sale-invoice-product-modal', '1', 'carat', 23, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1234, 1, 'sale-invoice-product-modal', '1', 'purity', 24, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1235, 1, 'sale-invoice-product-modal', '1', 'purity-wt', 25, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1236, 1, 'sale-invoice-product-modal', '1', 'gold-loss1', 26, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1237, 1, 'sale-invoice-product-modal', '1', 'gold-loss2', 27, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1238, 1, 'sale-invoice-product-modal', '1', 'metal-loss-value', 28, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1239, 1, 'sale-invoice-product-modal', '1', 'wastage-per', 29, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1240, 1, 'sale-invoice-product-modal', '1', 'wastage-wt', 30, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1241, 1, 'sale-invoice-product-modal', '1', 'metal-rate', 31, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1242, 1, 'sale-invoice-product-modal', '1', 'metal-value', 32, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1243, 1, 'sale-invoice-product-modal', '1', 'metal-cost', 33, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:33'),
(1244, 1, 'sale-invoice-product-modal', '1', 'requested-purity', 34, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1245, 1, 'sale-invoice-product-modal', '1', 'requested', 35, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1246, 1, 'sale-invoice-product-modal', '1', 'setting-charge', 36, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1247, 1, 'sale-invoice-product-modal', '1', 'final-wt', 37, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1248, 1, 'sale-invoice-product-modal', '1', 'alloy-wt', 38, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1249, 1, 'sale-invoice-product-modal', '1', 'discount-type', 39, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1250, 1, 'sale-invoice-product-modal', '1', 'discount-per', 40, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1251, 1, 'sale-invoice-product-modal', '1', 'discount-amount', 41, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1252, 1, 'sale-invoice-product-modal', '1', 'discount', 42, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:35'),
(1253, 1, 'sale-invoice-product-modal', '1', 'making-type', 43, 1, '2026-03-23 15:06:27', NULL),
(1254, 1, 'sale-invoice-product-modal', '1', 'making-rate', 44, 1, '2026-03-23 15:06:27', NULL),
(1255, 1, 'sale-invoice-product-modal', '1', 'making-discount-amt', 45, 1, '2026-03-23 15:06:27', NULL),
(1256, 1, 'sale-invoice-product-modal', '1', 'making-amount', 46, 1, '2026-03-23 15:06:27', NULL),
(1257, 1, 'sale-invoice-product-modal', '1', 'making-actual-value', 47, 1, '2026-03-23 15:06:27', NULL),
(1258, 1, 'sale-invoice-product-modal', '1', 'making-cost', 48, 1, '2026-03-23 15:06:27', NULL),
(1259, 1, 'sale-invoice-product-modal', '1', 'min-price', 49, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:36'),
(1260, 1, 'sale-invoice-product-modal', '1', 'minimum', 50, 0, '2026-03-23 15:06:27', '2026-03-23 15:06:36'),
(1261, 1, 'sale-invoice-product-modal', '1', 'stone-charge-type', 51, 1, '2026-03-23 15:06:27', NULL),
(1262, 1, 'sale-invoice-product-modal', '1', 'stone-rate', 52, 1, '2026-03-23 15:06:27', NULL),
(1263, 1, 'sale-invoice-product-modal', '1', 'stone-amount', 53, 1, '2026-03-23 15:06:27', NULL),
(1264, 1, 'sale-invoice-product-modal', '1', 'stone-cost', 54, 1, '2026-03-23 15:06:27', NULL),
(1265, 1, 'sale-invoice-product-modal', '1', 'diamond-amount', 55, 1, '2026-03-23 15:06:27', NULL),
(1266, 1, 'sale-invoice-product-modal', '1', 'purchase-amount', 56, 1, '2026-03-23 15:06:27', NULL),
(1267, 1, 'sale-invoice-product-modal', '1', 'sale-amount', 57, 1, '2026-03-23 15:06:27', NULL),
(1268, 1, 'sale-invoice-product-modal', '1', 'sale-amount-with', 58, 1, '2026-03-23 15:06:27', NULL),
(1269, 1, 'sale-invoice-product-modal', '1', 'net-amt', 59, 1, '2026-03-23 15:06:27', NULL),
(1270, 1, 'sale-invoice-product-modal', '1', 'tax-type', 60, 1, '2026-03-23 15:06:27', NULL),
(1271, 1, 'sale-invoice-product-modal', '1', 'tax-percent', 61, 1, '2026-03-23 15:06:27', NULL),
(1272, 1, 'sale-invoice-product-modal', '1', 'tax', 62, 1, '2026-03-23 15:06:27', NULL),
(1273, 1, 'sale-invoice-product-modal', '1', 'other-charge-type', 63, 1, '2026-03-23 15:06:27', NULL),
(1274, 1, 'sale-invoice-product-modal', '1', 'other-weight', 64, 1, '2026-03-23 15:06:27', NULL),
(1275, 1, 'sale-invoice-product-modal', '1', 'other-rate', 65, 1, '2026-03-23 15:06:27', NULL),
(1276, 1, 'sale-invoice-product-modal', '1', 'other-info', 66, 1, '2026-03-23 15:06:27', NULL),
(1277, 1, 'sale-invoice-product-modal', '1', 'other-amount', 67, 1, '2026-03-23 15:06:27', NULL),
(1278, 1, 'sale-invoice-product-modal', '1', 'hallmark-amount', 68, 1, '2026-03-23 15:06:27', NULL),
(1279, 1, 'sale-invoice-product-modal', '1', 'hallmark-rate', 69, 1, '2026-03-23 15:06:27', NULL),
(1280, 1, 'sale-invoice-product-modal', '1', 'net-amt-tax', 70, 1, '2026-03-23 15:06:27', NULL),
(1281, 1, 'sale-invoice-product-modal', '1', 'reverse', 71, 1, '2026-03-23 15:06:27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voucher_field_visibility`
--

CREATE TABLE `tbl_voucher_field_visibility` (
  `id` int NOT NULL,
  `voucher_type_id` int NOT NULL,
  `reference_no` tinyint(1) DEFAULT '0',
  `sales_person` tinyint(1) DEFAULT '0',
  `currency` tinyint(1) DEFAULT '0',
  `against_of` tinyint(1) DEFAULT '0',
  `layaways` tinyint(1) DEFAULT '0',
  `due_date` tinyint(1) DEFAULT '0',
  `fixing_type` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voucher_metal_allocations`
--

CREATE TABLE `tbl_voucher_metal_allocations` (
  `id` int NOT NULL,
  `voucher_type_id` int NOT NULL,
  `metal_id` int NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voucher_settings`
--

CREATE TABLE `tbl_voucher_settings` (
  `id` int UNSIGNED NOT NULL,
  `metal_wise` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Gold, Silver, Platinum, Diamond & Stones, Imitation Or Watches, Other Or Services',
  `minimum_amount_column` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Amount',
  `reverse_calculation_result_column` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MakingRate',
  `default_discount_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'On Amount',
  `default_calculation_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Fix',
  `stock_availability_check_by` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Carat',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbl_voucher_settings`
--

INSERT INTO `tbl_voucher_settings` (`id`, `metal_wise`, `minimum_amount_column`, `reverse_calculation_result_column`, `default_discount_type`, `default_calculation_type`, `stock_availability_check_by`, `updated_at`) VALUES
(1, 'Gold', 'Amount', 'MakingRate', 'On Amount', 'Fix', 'Carat', '2026-03-18 15:59:48'),
(2, 'Silver', 'Amount', 'MakingRate', 'On Amount', 'Fix', 'Carat', '2026-03-18 15:59:48'),
(3, 'Platinum', 'Amount', 'MakingRate', 'On Amount', 'Fix', 'Carat', '2026-03-18 15:59:48'),
(4, 'Diamond & Stones', 'Amount', 'MakingRate', 'On Amount', 'Fix', 'Carat', '2026-03-18 15:59:48'),
(5, 'Imitation Or Watches', 'Amount', 'MakingRate', 'On Amount', 'Fix', 'Carat', '2026-03-18 15:59:48'),
(6, 'Other Or Services', 'Amount', 'MakingRate', 'On Amount', 'Fix', 'Carat', '2026-03-18 15:59:48');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voucher_tax_allocations`
--

CREATE TABLE `tbl_voucher_tax_allocations` (
  `id` int NOT NULL,
  `voucher_type_id` int NOT NULL,
  `tax_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_voucher_types`
--

CREATE TABLE `tbl_voucher_types` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `method_of_numbering` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type_of_voucher` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `calculate_amount_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Rate X Gross Wt',
  `calculate_wastage_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Net Wt',
  `fixing_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Standard',
  `calculate_loss_by` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Net Wt',
  `do_not_apply_on_stock` tinyint(1) DEFAULT '0',
  `sales_persons_mandatory` tinyint(1) DEFAULT '0',
  `create_auto_journal_voucher` tinyint(1) DEFAULT '0',
  `metal_unfix` tinyint(1) DEFAULT '0',
  `internal_unfix` tinyint(1) DEFAULT '0',
  `do_not_allow_0_amount` tinyint(1) DEFAULT '0',
  `payment_mandatory` tinyint(1) DEFAULT '0',
  `calculate_markup_on_sale` tinyint(1) DEFAULT '0',
  `status` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `modified_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_voucher_types`
--

INSERT INTO `tbl_voucher_types` (`id`, `name`, `method_of_numbering`, `type_of_voucher`, `calculate_amount_by`, `calculate_wastage_by`, `fixing_type`, `calculate_loss_by`, `do_not_apply_on_stock`, `sales_persons_mandatory`, `create_auto_journal_voucher`, `metal_unfix`, `internal_unfix`, `do_not_allow_0_amount`, `payment_mandatory`, `calculate_markup_on_sale`, `status`, `created_by`, `modified_by`, `created_at`, `updated_at`) VALUES
(1, 'Advance Payment', '1', 'Advance Payment', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 1, 1, 0, 1, 1, 0, 0, 0, 1, NULL, 1, '2025-12-29 17:34:11', '2025-12-29 18:07:13'),
(2, 'Appraisal', '2', 'Appraisal', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, 1, '2025-12-29 17:34:11', '2026-01-04 22:08:43'),
(3, 'Assign Inventory', NULL, 'Assign Inventory', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(4, 'Bill Of Material', NULL, 'Bill Of Material', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(5, 'Broken Entry', NULL, 'Broken Entry', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(6, 'Expense Invoice', NULL, 'Expense Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(7, 'Fund Transfer', NULL, 'Fund Transfer', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(8, 'Fund Withdraw', NULL, 'Fund Withdraw', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(9, 'Income Invoice', NULL, 'Income Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(10, 'Investment Fund', NULL, 'Investment Fund', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(11, 'Jewelry Catalogue', NULL, 'Jewelry Catalogue', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(12, 'Jobwork Invoice', NULL, 'Jobwork Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(13, 'Jobwork Order', NULL, 'Jobwork Order', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(14, 'Jobwork Queue', NULL, 'Jobwork Queue', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(15, 'JobworkQueue Master', NULL, 'JobworkQueue Master', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(16, 'Journal Voucher', NULL, 'Journal Voucher', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(17, 'Loan', NULL, 'Loan', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(18, 'Loan Release', NULL, 'Loan Release', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(19, 'Material In', NULL, 'Material In', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(20, 'Purchase Fixing', NULL, 'Purchase Fixing', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(21, 'Purchase Fixing Direct Invoice', NULL, 'Purchase Fixing Direct Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(22, 'Purchase Invoice', NULL, 'Purchase Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(23, 'Purchase Order', NULL, 'Purchase Order', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(24, 'Purchase Quotation', NULL, 'Purchase Quotation', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(25, 'Purchase Return', NULL, 'Purchase Return', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(26, 'Receipt Voucher', NULL, 'Receipt Voucher', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(27, 'Rejection In', NULL, 'Rejection In', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(28, 'Rejection Out', NULL, 'Rejection Out', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(29, 'Repair Invoice', NULL, 'Repair Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(30, 'Repair Order', NULL, 'Repair Order', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(31, 'Sale Fixing', NULL, 'Sale Fixing', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(32, 'Sale Fixing Direct Invoice', NULL, 'Sale Fixing Direct Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(33, 'Sales Invoice', NULL, 'Sales Invoice', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(34, 'Sales Order', '3', 'Sales Order', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, 1, '2025-12-29 17:34:11', '2026-01-06 16:17:46'),
(35, 'Sales Quotation', NULL, 'Sales Quotation', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(36, 'Sales Return', NULL, 'Sales Return', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(37, 'Service Voucher', NULL, 'Service Voucher', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(38, 'Stock Journal', NULL, 'Stock Journal', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(39, 'Stock Transfer In', NULL, 'Stock Transfer In', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(40, 'Stock Transfer Out', NULL, 'Stock Transfer Out', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL),
(41, 'UnAssign Inventory', NULL, 'UnAssign Inventory', 'Rate X Gross Wt', 'Net Wt', 'Standard', 'Net Wt', 0, 0, 0, 0, 0, 0, 0, 0, 1, NULL, NULL, '2025-12-29 17:34:11', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_advance_payments`
--
ALTER TABLE `tbl_advance_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `voucher_date` (`voucher_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_advance_payment_items`
--
ALTER TABLE `tbl_advance_payment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `metal_id` (`metal_id`);

--
-- Indexes for table `tbl_article`
--
ALTER TABLE `tbl_article`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_barcode_settings`
--
ALTER TABLE `tbl_barcode_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_updated` (`updated_at`);

--
-- Indexes for table `tbl_bill_series`
--
ALTER TABLE `tbl_bill_series`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_voucher_type_id` (`voucher_type_id`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_branches`
--
ALTER TABLE `tbl_branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_break_type`
--
ALTER TABLE `tbl_break_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_calculation_modes`
--
ALTER TABLE `tbl_calculation_modes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tbl_campaign_group`
--
ALTER TABLE `tbl_campaign_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_carat`
--
ALTER TABLE `tbl_carat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_carat_status` (`status`);

--
-- Indexes for table `tbl_cash_denomination`
--
ALTER TABLE `tbl_cash_denomination`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_categories`
--
ALTER TABLE `tbl_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_clarity`
--
ALTER TABLE `tbl_clarity`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_collection`
--
ALTER TABLE `tbl_collection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_collection_status` (`status`);

--
-- Indexes for table `tbl_color`
--
ALTER TABLE `tbl_color`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_consignment_in`
--
ALTER TABLE `tbl_consignment_in`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consignment_no` (`consignment_no`),
  ADD KEY `consignment_out_id` (`consignment_out_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `customer_name` (`customer_name`),
  ADD KEY `consignment_date` (`consignment_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_consignment_in_items`
--
ALTER TABLE `tbl_consignment_in_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consignment_id` (`consignment_id`),
  ADD KEY `consignment_out_item_id` (`consignment_out_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `barcode` (`barcode`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`);

--
-- Indexes for table `tbl_consignment_out`
--
ALTER TABLE `tbl_consignment_out`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `consignment_no` (`consignment_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `customer_name` (`customer_name`),
  ADD KEY `consignment_date` (`consignment_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_consignment_out_items`
--
ALTER TABLE `tbl_consignment_out_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consignment_id` (`consignment_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `barcode` (`barcode`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`);

--
-- Indexes for table `tbl_contra_vouchers`
--
ALTER TABLE `tbl_contra_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `voucher_date` (`voucher_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_contra_voucher_items`
--
ALTER TABLE `tbl_contra_voucher_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `tbl_counter`
--
ALTER TABLE `tbl_counter`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_countries`
--
ALTER TABLE `tbl_countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `code` (`code`);

--
-- Indexes for table `tbl_credit_notes`
--
ALTER TABLE `tbl_credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `credit_note_no` (`credit_note_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `credit_note_date` (`credit_note_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_credit_note_items`
--
ALTER TABLE `tbl_credit_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credit_note_id` (`credit_note_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_credit_note_payments`
--
ALTER TABLE `tbl_credit_note_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `credit_note_id` (`credit_note_id`);

--
-- Indexes for table `tbl_currency`
--
ALTER TABLE `tbl_currency`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_currency_exchange_rate`
--
ALTER TABLE `tbl_currency_exchange_rate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_customers`
--
ALTER TABLE `tbl_customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `mobile_no` (`mobile_no`),
  ADD KEY `mail_id` (`mail_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `customer_type_id` (`customer_type_id`),
  ADD KEY `nationality_id` (`nationality_id`),
  ADD KEY `country_id` (`country_id`);

--
-- Indexes for table `tbl_customer_advance_policy`
--
ALTER TABLE `tbl_customer_advance_policy`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_customer_advance_vouchers`
--
ALTER TABLE `tbl_customer_advance_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `voucher_date` (`voucher_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_customer_advance_voucher_items`
--
ALTER TABLE `tbl_customer_advance_voucher_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `metal_id` (`metal_id`);

--
-- Indexes for table `tbl_customer_balance`
--
ALTER TABLE `tbl_customer_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD KEY `balance_amount` (`balance_amount`);

--
-- Indexes for table `tbl_customer_ledger`
--
ALTER TABLE `tbl_customer_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `transaction_type` (`transaction_type`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `transaction_date` (`transaction_date`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_against_invoice_no` (`against_invoice_no`);

--
-- Indexes for table `tbl_customer_types`
--
ALTER TABLE `tbl_customer_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tbl_cut`
--
ALTER TABLE `tbl_cut`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_day_reports`
--
ALTER TABLE `tbl_day_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_date` (`report_date`),
  ADD KEY `idx_report_date` (`report_date`);

--
-- Indexes for table `tbl_debit_notes`
--
ALTER TABLE `tbl_debit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `debit_note_no` (`debit_note_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `debit_note_date` (`debit_note_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_debit_note_items`
--
ALTER TABLE `tbl_debit_note_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debit_note_id` (`debit_note_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_debit_note_payments`
--
ALTER TABLE `tbl_debit_note_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `debit_note_id` (`debit_note_id`);

--
-- Indexes for table `tbl_departments`
--
ALTER TABLE `tbl_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_department_short_code` (`short_code`),
  ADD KEY `idx_department_status` (`status`);

--
-- Indexes for table `tbl_department_users`
--
ALTER TABLE `tbl_department_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_department_user_name` (`user_name`),
  ADD KEY `idx_department_user_status` (`status`);

--
-- Indexes for table `tbl_department_user_map`
--
ALTER TABLE `tbl_department_user_map`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_department_user_map` (`department_id`,`user_id`),
  ADD KEY `idx_department_map_department` (`department_id`),
  ADD KEY `idx_department_map_user` (`user_id`),
  ADD KEY `idx_department_map_status` (`status`);

--
-- Indexes for table `tbl_document_type`
--
ALTER TABLE `tbl_document_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_expenses`
--
ALTER TABLE `tbl_expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_no` (`expense_no`),
  ADD KEY `ledger_id` (`ledger_id`),
  ADD KEY `expense_date` (`expense_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_expense_categories`
--
ALTER TABLE `tbl_expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_expense_items`
--
ALTER TABLE `tbl_expense_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`);

--
-- Indexes for table `tbl_expense_payments`
--
ALTER TABLE `tbl_expense_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_id` (`expense_id`);

--
-- Indexes for table `tbl_invoice_print_settings`
--
ALTER TABLE `tbl_invoice_print_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_type_key` (`setting_type`,`setting_key`),
  ADD KEY `idx_updated` (`updated_at`),
  ADD KEY `idx_setting_type` (`setting_type`);

--
-- Indexes for table `tbl_jobwork_orders`
--
ALTER TABLE `tbl_jobwork_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_order_id` (`sale_order_id`),
  ADD KEY `jobwork_no` (`jobwork_no`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_jobwork_order_items`
--
ALTER TABLE `tbl_jobwork_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobwork_order_id` (`jobwork_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_job_work_orders`
--
ALTER TABLE `tbl_job_work_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_work_no` (`job_work_no`),
  ADD KEY `sale_order_id` (`sale_order_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_journal_vouchers`
--
ALTER TABLE `tbl_journal_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `voucher_date` (`voucher_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_journal_voucher_items`
--
ALTER TABLE `tbl_journal_voucher_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `tbl_loan_product_type`
--
ALTER TABLE `tbl_loan_product_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_loan_reason`
--
ALTER TABLE `tbl_loan_reason`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_location`
--
ALTER TABLE `tbl_location`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_location_status` (`status`);

--
-- Indexes for table `tbl_metal`
--
ALTER TABLE `tbl_metal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_nationalities`
--
ALTER TABLE `tbl_nationalities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tbl_old_jewelry_scrap_invoices`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `invoice_date` (`invoice_date`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_old_jewelry_scrap_invoice_items`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbl_old_jewelry_scrap_invoice_payments`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbl_old_jewelry_stock`
--
ALTER TABLE `tbl_old_jewelry_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `source_invoice_id` (`source_invoice_id`),
  ADD KEY `source_item_id` (`source_item_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `tbl_packet_type`
--
ALTER TABLE `tbl_packet_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_payment_vouchers`
--
ALTER TABLE `tbl_payment_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `voucher_date` (`voucher_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_payment_voucher_items`
--
ALTER TABLE `tbl_payment_voucher_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `metal_id` (`metal_id`);

--
-- Indexes for table `tbl_products`
--
ALTER TABLE `tbl_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `tbl_product_branches`
--
ALTER TABLE `tbl_product_branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_branch` (`product_id`,`branch_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `tbl_product_characteristics`
--
ALTER TABLE `tbl_product_characteristics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `metal_id` (`metal_id`),
  ADD KEY `idx_unit_id` (`unit_id`),
  ADD KEY `idx_location_id` (`location_id`);

--
-- Indexes for table `tbl_product_tax`
--
ALTER TABLE `tbl_product_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_purchase_invoices`
--
ALTER TABLE `tbl_purchase_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `invoice_date` (`invoice_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_purchase_invoice_items`
--
ALTER TABLE `tbl_purchase_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`);

--
-- Indexes for table `tbl_purchase_invoice_payments`
--
ALTER TABLE `tbl_purchase_invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbl_purchase_orders`
--
ALTER TABLE `tbl_purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_no` (`order_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_date` (`order_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_purchase_order_items`
--
ALTER TABLE `tbl_purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_purchase_order_payments`
--
ALTER TABLE `tbl_purchase_order_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `tbl_purchase_quotations`
--
ALTER TABLE `tbl_purchase_quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_no` (`quotation_no`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `quotation_date` (`quotation_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_purchase_quotation_items`
--
ALTER TABLE `tbl_purchase_quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_purchase_quotation_payments`
--
ALTER TABLE `tbl_purchase_quotation_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `tbl_purchase_returns`
--
ALTER TABLE `tbl_purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `return_date` (`return_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_purchase_return_items`
--
ALTER TABLE `tbl_purchase_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_purchase_return_payments`
--
ALTER TABLE `tbl_purchase_return_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`);

--
-- Indexes for table `tbl_receipt_vouchers`
--
ALTER TABLE `tbl_receipt_vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_no` (`voucher_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `voucher_date` (`voucher_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_receipt_voucher_items`
--
ALTER TABLE `tbl_receipt_voucher_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_id` (`voucher_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `metal_id` (`metal_id`);

--
-- Indexes for table `tbl_remark`
--
ALTER TABLE `tbl_remark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_repair_invoices`
--
ALTER TABLE `tbl_repair_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `repair_invoice_no` (`repair_invoice_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `repair_invoice_date` (`repair_invoice_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_repair_invoice_items`
--
ALTER TABLE `tbl_repair_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repair_invoice_id` (`repair_invoice_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_repair_invoice_payments`
--
ALTER TABLE `tbl_repair_invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repair_invoice_id` (`repair_invoice_id`);

--
-- Indexes for table `tbl_repair_jobwork_orders`
--
ALTER TABLE `tbl_repair_jobwork_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `repair_order_id` (`repair_order_id`),
  ADD KEY `jobwork_no` (`jobwork_no`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_repair_jobwork_order_items`
--
ALTER TABLE `tbl_repair_jobwork_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `repair_jobwork_order_id` (`repair_jobwork_order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `tbl_repair_orders`
--
ALTER TABLE `tbl_repair_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_date` (`order_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_repair_order_items`
--
ALTER TABLE `tbl_repair_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_repair_order_payments`
--
ALTER TABLE `tbl_repair_order_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `tbl_sale_fixing_direct`
--
ALTER TABLE `tbl_sale_fixing_direct`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ref_no` (`ref_no`),
  ADD KEY `idx_fixing_date` (`fixing_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tbl_sale_fixing_direct_items`
--
ALTER TABLE `tbl_sale_fixing_direct_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fixing_id` (`fixing_id`),
  ADD KEY `idx_metal_id` (`metal_id`);

--
-- Indexes for table `tbl_sale_invoices`
--
ALTER TABLE `tbl_sale_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `invoice_date` (`invoice_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_sale_invoice_items`
--
ALTER TABLE `tbl_sale_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_sale_invoice_payments`
--
ALTER TABLE `tbl_sale_invoice_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `tbl_sale_orders`
--
ALTER TABLE `tbl_sale_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_date` (`order_date`),
  ADD KEY `order_no` (`order_no`);

--
-- Indexes for table `tbl_sale_order_items`
--
ALTER TABLE `tbl_sale_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`);

--
-- Indexes for table `tbl_sale_order_payments`
--
ALTER TABLE `tbl_sale_order_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `tbl_sale_quotations`
--
ALTER TABLE `tbl_sale_quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_no` (`quotation_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `quotation_date` (`quotation_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_sale_quotation_items`
--
ALTER TABLE `tbl_sale_quotation_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_sale_quotation_payments`
--
ALTER TABLE `tbl_sale_quotation_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quotation_id` (`quotation_id`);

--
-- Indexes for table `tbl_sale_returns`
--
ALTER TABLE `tbl_sale_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `return_date` (`return_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_sale_return_items`
--
ALTER TABLE `tbl_sale_return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `barcode` (`barcode`);

--
-- Indexes for table `tbl_sale_return_payments`
--
ALTER TABLE `tbl_sale_return_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `return_id` (`return_id`);

--
-- Indexes for table `tbl_shape`
--
ALTER TABLE `tbl_shape`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_sieve_size`
--
ALTER TABLE `tbl_sieve_size`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_size`
--
ALTER TABLE `tbl_size`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_stock`
--
ALTER TABLE `tbl_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `metal_id` (`metal_id`),
  ADD KEY `product_characteristic_id` (`product_characteristic_id`),
  ADD KEY `idx_barcode` (`barcode`);

--
-- Indexes for table `tbl_stock_journal`
--
ALTER TABLE `tbl_stock_journal`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sj_invoice_no` (`sj_invoice_no`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `metal_id` (`metal_id`),
  ADD KEY `sj_date` (`sj_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbl_stock_journal_images`
--
ALTER TABLE `tbl_stock_journal_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_barcode` (`item_id`,`barcode_no`);

--
-- Indexes for table `tbl_stock_transfer_pending`
--
ALTER TABLE `tbl_stock_transfer_pending`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_to` (`status`,`to_branch_id`),
  ADD KEY `idx_outward` (`outward_stock_id`);

--
-- Indexes for table `tbl_task_type`
--
ALTER TABLE `tbl_task_type`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_taxes`
--
ALTER TABLE `tbl_taxes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_tax_master`
--
ALTER TABLE `tbl_tax_master`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `tbl_unit`
--
ALTER TABLE `tbl_unit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_unit_conversion`
--
ALTER TABLE `tbl_unit_conversion`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_users`
--
ALTER TABLE `tbl_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_user_column_preferences`
--
ALTER TABLE `tbl_user_column_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_page_tab_column` (`user_id`,`page_name`,`tab_key`,`column_key`),
  ADD KEY `idx_user_page` (`user_id`,`page_name`);

--
-- Indexes for table `tbl_voucher_field_visibility`
--
ALTER TABLE `tbl_voucher_field_visibility`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_type_id` (`voucher_type_id`);

--
-- Indexes for table `tbl_voucher_metal_allocations`
--
ALTER TABLE `tbl_voucher_metal_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_type_id` (`voucher_type_id`),
  ADD KEY `metal_id` (`metal_id`);

--
-- Indexes for table `tbl_voucher_settings`
--
ALTER TABLE `tbl_voucher_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_metal_wise` (`metal_wise`),
  ADD KEY `idx_updated` (`updated_at`);

--
-- Indexes for table `tbl_voucher_tax_allocations`
--
ALTER TABLE `tbl_voucher_tax_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voucher_type_id` (`voucher_type_id`),
  ADD KEY `tax_id` (`tax_id`);

--
-- Indexes for table `tbl_voucher_types`
--
ALTER TABLE `tbl_voucher_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_advance_payments`
--
ALTER TABLE `tbl_advance_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_advance_payment_items`
--
ALTER TABLE `tbl_advance_payment_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_article`
--
ALTER TABLE `tbl_article`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_barcode_settings`
--
ALTER TABLE `tbl_barcode_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_bill_series`
--
ALTER TABLE `tbl_bill_series`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_branches`
--
ALTER TABLE `tbl_branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_break_type`
--
ALTER TABLE `tbl_break_type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_calculation_modes`
--
ALTER TABLE `tbl_calculation_modes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_campaign_group`
--
ALTER TABLE `tbl_campaign_group`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_carat`
--
ALTER TABLE `tbl_carat`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_cash_denomination`
--
ALTER TABLE `tbl_cash_denomination`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_categories`
--
ALTER TABLE `tbl_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_clarity`
--
ALTER TABLE `tbl_clarity`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_collection`
--
ALTER TABLE `tbl_collection`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_color`
--
ALTER TABLE `tbl_color`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_consignment_in`
--
ALTER TABLE `tbl_consignment_in`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_consignment_in_items`
--
ALTER TABLE `tbl_consignment_in_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_consignment_out`
--
ALTER TABLE `tbl_consignment_out`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_consignment_out_items`
--
ALTER TABLE `tbl_consignment_out_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_contra_vouchers`
--
ALTER TABLE `tbl_contra_vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_contra_voucher_items`
--
ALTER TABLE `tbl_contra_voucher_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_counter`
--
ALTER TABLE `tbl_counter`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_countries`
--
ALTER TABLE `tbl_countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `tbl_credit_notes`
--
ALTER TABLE `tbl_credit_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_credit_note_items`
--
ALTER TABLE `tbl_credit_note_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_credit_note_payments`
--
ALTER TABLE `tbl_credit_note_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_currency`
--
ALTER TABLE `tbl_currency`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_currency_exchange_rate`
--
ALTER TABLE `tbl_currency_exchange_rate`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_customers`
--
ALTER TABLE `tbl_customers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_advance_policy`
--
ALTER TABLE `tbl_customer_advance_policy`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_advance_vouchers`
--
ALTER TABLE `tbl_customer_advance_vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_advance_voucher_items`
--
ALTER TABLE `tbl_customer_advance_voucher_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_balance`
--
ALTER TABLE `tbl_customer_balance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_ledger`
--
ALTER TABLE `tbl_customer_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_customer_types`
--
ALTER TABLE `tbl_customer_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_cut`
--
ALTER TABLE `tbl_cut`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_day_reports`
--
ALTER TABLE `tbl_day_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_debit_notes`
--
ALTER TABLE `tbl_debit_notes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_debit_note_items`
--
ALTER TABLE `tbl_debit_note_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_debit_note_payments`
--
ALTER TABLE `tbl_debit_note_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_departments`
--
ALTER TABLE `tbl_departments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_department_users`
--
ALTER TABLE `tbl_department_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_department_user_map`
--
ALTER TABLE `tbl_department_user_map`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_document_type`
--
ALTER TABLE `tbl_document_type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_expenses`
--
ALTER TABLE `tbl_expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_expense_categories`
--
ALTER TABLE `tbl_expense_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_expense_items`
--
ALTER TABLE `tbl_expense_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_expense_payments`
--
ALTER TABLE `tbl_expense_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_invoice_print_settings`
--
ALTER TABLE `tbl_invoice_print_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `tbl_jobwork_orders`
--
ALTER TABLE `tbl_jobwork_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_jobwork_order_items`
--
ALTER TABLE `tbl_jobwork_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_job_work_orders`
--
ALTER TABLE `tbl_job_work_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_journal_vouchers`
--
ALTER TABLE `tbl_journal_vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_journal_voucher_items`
--
ALTER TABLE `tbl_journal_voucher_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_loan_product_type`
--
ALTER TABLE `tbl_loan_product_type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_loan_reason`
--
ALTER TABLE `tbl_loan_reason`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_location`
--
ALTER TABLE `tbl_location`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_metal`
--
ALTER TABLE `tbl_metal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_nationalities`
--
ALTER TABLE `tbl_nationalities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `tbl_old_jewelry_scrap_invoices`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_old_jewelry_scrap_invoice_items`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_old_jewelry_scrap_invoice_payments`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoice_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_old_jewelry_stock`
--
ALTER TABLE `tbl_old_jewelry_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_packet_type`
--
ALTER TABLE `tbl_packet_type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_payment_vouchers`
--
ALTER TABLE `tbl_payment_vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_payment_voucher_items`
--
ALTER TABLE `tbl_payment_voucher_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_products`
--
ALTER TABLE `tbl_products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_product_branches`
--
ALTER TABLE `tbl_product_branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_product_characteristics`
--
ALTER TABLE `tbl_product_characteristics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_product_tax`
--
ALTER TABLE `tbl_product_tax`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_invoices`
--
ALTER TABLE `tbl_purchase_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_invoice_items`
--
ALTER TABLE `tbl_purchase_invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_invoice_payments`
--
ALTER TABLE `tbl_purchase_invoice_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_orders`
--
ALTER TABLE `tbl_purchase_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_items`
--
ALTER TABLE `tbl_purchase_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_order_payments`
--
ALTER TABLE `tbl_purchase_order_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_quotations`
--
ALTER TABLE `tbl_purchase_quotations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_quotation_items`
--
ALTER TABLE `tbl_purchase_quotation_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_quotation_payments`
--
ALTER TABLE `tbl_purchase_quotation_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_returns`
--
ALTER TABLE `tbl_purchase_returns`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_return_items`
--
ALTER TABLE `tbl_purchase_return_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_purchase_return_payments`
--
ALTER TABLE `tbl_purchase_return_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_receipt_vouchers`
--
ALTER TABLE `tbl_receipt_vouchers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_receipt_voucher_items`
--
ALTER TABLE `tbl_receipt_voucher_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_remark`
--
ALTER TABLE `tbl_remark`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_invoices`
--
ALTER TABLE `tbl_repair_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_invoice_items`
--
ALTER TABLE `tbl_repair_invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_invoice_payments`
--
ALTER TABLE `tbl_repair_invoice_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_jobwork_orders`
--
ALTER TABLE `tbl_repair_jobwork_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_jobwork_order_items`
--
ALTER TABLE `tbl_repair_jobwork_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_orders`
--
ALTER TABLE `tbl_repair_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_order_items`
--
ALTER TABLE `tbl_repair_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_repair_order_payments`
--
ALTER TABLE `tbl_repair_order_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_fixing_direct`
--
ALTER TABLE `tbl_sale_fixing_direct`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_fixing_direct_items`
--
ALTER TABLE `tbl_sale_fixing_direct_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_invoices`
--
ALTER TABLE `tbl_sale_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_invoice_items`
--
ALTER TABLE `tbl_sale_invoice_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_invoice_payments`
--
ALTER TABLE `tbl_sale_invoice_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_orders`
--
ALTER TABLE `tbl_sale_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_order_items`
--
ALTER TABLE `tbl_sale_order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_order_payments`
--
ALTER TABLE `tbl_sale_order_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_quotations`
--
ALTER TABLE `tbl_sale_quotations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_quotation_items`
--
ALTER TABLE `tbl_sale_quotation_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_quotation_payments`
--
ALTER TABLE `tbl_sale_quotation_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_returns`
--
ALTER TABLE `tbl_sale_returns`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_return_items`
--
ALTER TABLE `tbl_sale_return_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_sale_return_payments`
--
ALTER TABLE `tbl_sale_return_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_shape`
--
ALTER TABLE `tbl_shape`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_sieve_size`
--
ALTER TABLE `tbl_sieve_size`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_size`
--
ALTER TABLE `tbl_size`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_stock`
--
ALTER TABLE `tbl_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_stock_journal`
--
ALTER TABLE `tbl_stock_journal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_stock_journal_images`
--
ALTER TABLE `tbl_stock_journal_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_stock_transfer_pending`
--
ALTER TABLE `tbl_stock_transfer_pending`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_task_type`
--
ALTER TABLE `tbl_task_type`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_taxes`
--
ALTER TABLE `tbl_taxes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_tax_master`
--
ALTER TABLE `tbl_tax_master`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_unit`
--
ALTER TABLE `tbl_unit`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_unit_conversion`
--
ALTER TABLE `tbl_unit_conversion`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_users`
--
ALTER TABLE `tbl_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_user_column_preferences`
--
ALTER TABLE `tbl_user_column_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1642;

--
-- AUTO_INCREMENT for table `tbl_voucher_field_visibility`
--
ALTER TABLE `tbl_voucher_field_visibility`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_voucher_metal_allocations`
--
ALTER TABLE `tbl_voucher_metal_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_voucher_settings`
--
ALTER TABLE `tbl_voucher_settings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_voucher_tax_allocations`
--
ALTER TABLE `tbl_voucher_tax_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_voucher_types`
--
ALTER TABLE `tbl_voucher_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_advance_payment_items`
--
ALTER TABLE `tbl_advance_payment_items`
  ADD CONSTRAINT `fk_advance_payment_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_advance_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_consignment_in_items`
--
ALTER TABLE `tbl_consignment_in_items`
  ADD CONSTRAINT `fk_consignment_in_items_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `tbl_consignment_in` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_consignment_out_items`
--
ALTER TABLE `tbl_consignment_out_items`
  ADD CONSTRAINT `fk_consignment_out_items_consignment` FOREIGN KEY (`consignment_id`) REFERENCES `tbl_consignment_out` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_contra_voucher_items`
--
ALTER TABLE `tbl_contra_voucher_items`
  ADD CONSTRAINT `fk_contra_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_contra_vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_credit_note_items`
--
ALTER TABLE `tbl_credit_note_items`
  ADD CONSTRAINT `fk_credit_note_items_note` FOREIGN KEY (`credit_note_id`) REFERENCES `tbl_credit_notes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_credit_note_payments`
--
ALTER TABLE `tbl_credit_note_payments`
  ADD CONSTRAINT `fk_credit_note_payments_note` FOREIGN KEY (`credit_note_id`) REFERENCES `tbl_credit_notes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_customer_advance_voucher_items`
--
ALTER TABLE `tbl_customer_advance_voucher_items`
  ADD CONSTRAINT `fk_customer_advance_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_customer_advance_vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_debit_note_items`
--
ALTER TABLE `tbl_debit_note_items`
  ADD CONSTRAINT `fk_debit_note_items_note` FOREIGN KEY (`debit_note_id`) REFERENCES `tbl_debit_notes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_debit_note_payments`
--
ALTER TABLE `tbl_debit_note_payments`
  ADD CONSTRAINT `fk_debit_note_payments_note` FOREIGN KEY (`debit_note_id`) REFERENCES `tbl_debit_notes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_expense_items`
--
ALTER TABLE `tbl_expense_items`
  ADD CONSTRAINT `fk_expense_items_expense` FOREIGN KEY (`expense_id`) REFERENCES `tbl_expenses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_expense_payments`
--
ALTER TABLE `tbl_expense_payments`
  ADD CONSTRAINT `fk_expense_payments_expense` FOREIGN KEY (`expense_id`) REFERENCES `tbl_expenses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_journal_voucher_items`
--
ALTER TABLE `tbl_journal_voucher_items`
  ADD CONSTRAINT `fk_journal_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_journal_vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_old_jewelry_scrap_invoice_items`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoice_items`
  ADD CONSTRAINT `fk_scrap_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_old_jewelry_scrap_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_old_jewelry_scrap_invoice_payments`
--
ALTER TABLE `tbl_old_jewelry_scrap_invoice_payments`
  ADD CONSTRAINT `fk_scrap_invoice_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_old_jewelry_scrap_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_payment_voucher_items`
--
ALTER TABLE `tbl_payment_voucher_items`
  ADD CONSTRAINT `fk_payment_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_payment_vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_purchase_invoice_items`
--
ALTER TABLE `tbl_purchase_invoice_items`
  ADD CONSTRAINT `fk_purchase_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_purchase_invoice_payments`
--
ALTER TABLE `tbl_purchase_invoice_payments`
  ADD CONSTRAINT `fk_purchase_invoice_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_purchase_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_purchase_quotation_items`
--
ALTER TABLE `tbl_purchase_quotation_items`
  ADD CONSTRAINT `fk_purchase_quotation_items_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `tbl_purchase_quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_purchase_quotation_payments`
--
ALTER TABLE `tbl_purchase_quotation_payments`
  ADD CONSTRAINT `fk_purchase_quotation_payments_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `tbl_purchase_quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_purchase_return_items`
--
ALTER TABLE `tbl_purchase_return_items`
  ADD CONSTRAINT `fk_purchase_return_items_return` FOREIGN KEY (`return_id`) REFERENCES `tbl_purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_purchase_return_payments`
--
ALTER TABLE `tbl_purchase_return_payments`
  ADD CONSTRAINT `fk_purchase_return_payments_return` FOREIGN KEY (`return_id`) REFERENCES `tbl_purchase_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_receipt_voucher_items`
--
ALTER TABLE `tbl_receipt_voucher_items`
  ADD CONSTRAINT `fk_receipt_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_receipt_vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_repair_invoice_items`
--
ALTER TABLE `tbl_repair_invoice_items`
  ADD CONSTRAINT `fk_repair_invoice_items_invoice` FOREIGN KEY (`repair_invoice_id`) REFERENCES `tbl_repair_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_repair_invoice_payments`
--
ALTER TABLE `tbl_repair_invoice_payments`
  ADD CONSTRAINT `fk_repair_invoice_payments_invoice` FOREIGN KEY (`repair_invoice_id`) REFERENCES `tbl_repair_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_repair_jobwork_orders`
--
ALTER TABLE `tbl_repair_jobwork_orders`
  ADD CONSTRAINT `fk_repair_jobwork_orders_repair` FOREIGN KEY (`repair_order_id`) REFERENCES `tbl_repair_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_repair_jobwork_order_items`
--
ALTER TABLE `tbl_repair_jobwork_order_items`
  ADD CONSTRAINT `fk_repair_jobwork_order_items_order` FOREIGN KEY (`repair_jobwork_order_id`) REFERENCES `tbl_repair_jobwork_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_repair_order_items`
--
ALTER TABLE `tbl_repair_order_items`
  ADD CONSTRAINT `fk_repair_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `tbl_repair_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_repair_order_payments`
--
ALTER TABLE `tbl_repair_order_payments`
  ADD CONSTRAINT `fk_repair_order_payments_order` FOREIGN KEY (`order_id`) REFERENCES `tbl_repair_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_fixing_direct_items`
--
ALTER TABLE `tbl_sale_fixing_direct_items`
  ADD CONSTRAINT `fk_fixing_items_fixing` FOREIGN KEY (`fixing_id`) REFERENCES `tbl_sale_fixing_direct` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_invoice_items`
--
ALTER TABLE `tbl_sale_invoice_items`
  ADD CONSTRAINT `fk_sale_invoice_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_sale_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_invoice_payments`
--
ALTER TABLE `tbl_sale_invoice_payments`
  ADD CONSTRAINT `fk_sale_invoice_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_sale_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_quotation_items`
--
ALTER TABLE `tbl_sale_quotation_items`
  ADD CONSTRAINT `fk_sale_quotation_items_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `tbl_sale_quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_quotation_payments`
--
ALTER TABLE `tbl_sale_quotation_payments`
  ADD CONSTRAINT `fk_sale_quotation_payments_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `tbl_sale_quotations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_return_items`
--
ALTER TABLE `tbl_sale_return_items`
  ADD CONSTRAINT `fk_sale_return_items_return` FOREIGN KEY (`return_id`) REFERENCES `tbl_sale_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_sale_return_payments`
--
ALTER TABLE `tbl_sale_return_payments`
  ADD CONSTRAINT `fk_sale_return_payments_return` FOREIGN KEY (`return_id`) REFERENCES `tbl_sale_returns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_stock_journal`
--
ALTER TABLE `tbl_stock_journal`
  ADD CONSTRAINT `fk_stock_journal_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `tbl_purchase_invoices` (`id`),
  ADD CONSTRAINT `fk_stock_journal_item` FOREIGN KEY (`item_id`) REFERENCES `tbl_purchase_invoice_items` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
