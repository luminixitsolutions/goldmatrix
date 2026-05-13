-- Voucher Setting: one row per metal (Gold, Silver, Platinum, etc.). Used by voucher-setting.php.
-- Run once to create the table.

CREATE TABLE IF NOT EXISTS `tbl_voucher_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `metal_wise` varchar(80) NOT NULL COMMENT 'Gold, Silver, Platinum, Diamond & Stones, Imitation Or Watches, Other Or Services',
  `minimum_amount_column` varchar(50) NOT NULL DEFAULT 'Amount',
  `reverse_calculation_result_column` varchar(50) NOT NULL DEFAULT 'MakingRate',
  `default_discount_type` varchar(50) NOT NULL DEFAULT 'Fix',
  `default_calculation_type` varchar(50) NOT NULL DEFAULT 'Fix',
  `stock_availability_check_by` varchar(50) NOT NULL DEFAULT 'Carat',
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_metal_wise` (`metal_wise`),
  KEY `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per metal (run once)
INSERT IGNORE INTO `tbl_voucher_settings` (`metal_wise`, `minimum_amount_column`, `reverse_calculation_result_column`, `default_discount_type`, `default_calculation_type`, `stock_availability_check_by`, `updated_at`) VALUES
('Gold', 'Amount', 'MakingRate', 'Fix', 'Fix', 'Carat', NOW()),
('Silver', 'Amount', 'MakingRate', 'Fix', 'Fix', 'Carat', NOW()),
('Platinum', 'Amount', 'MakingRate', 'Fix', 'Fix', 'Carat', NOW()),
('Diamond & Stones', 'Amount', 'MakingRate', 'Fix', 'Fix', 'Carat', NOW()),
('Imitation Or Watches', 'Amount', 'MakingRate', 'Fix', 'Fix', 'Carat', NOW()),
('Other Or Services', 'Amount', 'MakingRate', 'Fix', 'Fix', 'Carat', NOW());
