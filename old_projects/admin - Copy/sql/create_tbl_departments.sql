-- Department master table
CREATE TABLE IF NOT EXISTS `tbl_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dept_name` varchar(120) NOT NULL,
  `short_code` varchar(40) NOT NULL,
  `department_type` varchar(40) NOT NULL DEFAULT 'Wt. Wise',
  `process_type` varchar(40) NOT NULL DEFAULT 'Manufacturing',
  `auto_loss` tinyint(1) NOT NULL DEFAULT 1,
  `auto_profit` tinyint(1) NOT NULL DEFAULT 1,
  `calculate_stock` tinyint(1) NOT NULL DEFAULT 0,
  `progress_percent` decimal(8,2) DEFAULT NULL,
  `exclude_jobcard_summary` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_department_short_code` (`short_code`),
  KEY `idx_department_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
