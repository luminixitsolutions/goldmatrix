-- Separate saved layout for QR vs Barcode designer + default print preference
-- Run once on existing databases.

ALTER TABLE `tbl_barcode_settings`
  ADD COLUMN `design_layout_qr` LONGTEXT NULL COMMENT 'JSON label design for QR mode' AFTER `design_layout`,
  ADD COLUMN `default_print_code_type` VARCHAR(10) NOT NULL DEFAULT 'barcode' COMMENT 'barcode|qr — used when print URL has no code= param' AFTER `design_layout_qr`;

-- Seed QR layout from existing barcode layout (optional one-time copy)
UPDATE `tbl_barcode_settings`
SET `design_layout_qr` = `design_layout`
WHERE (`design_layout_qr` IS NULL OR `design_layout_qr` = '')
  AND `design_layout` IS NOT NULL AND `design_layout` != '';
