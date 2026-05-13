<?php
/**
 * SQL for stock analysis: include rows only when Product Opening "Show In Stock" is on
 * (tbl_product_branch_settings.is_stock_item = 1 for product_id + stock.branch_id).
 * If no row exists in that table for the pair, fall back to legacy tbl_products.is_stock_item
 * (defaults to 1 for NULL so old data still appears until saved from Product Opening).
 */
if (!function_exists('auragold_sql_show_in_stock_for_stock_table')) {
    function auragold_sql_show_in_stock_for_stock_table(string $stockAlias = 's', string $productAlias = 'p'): string {
        $stockAlias   = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $stockAlias) ? $stockAlias : 's';
        $productAlias = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $productAlias) ? $productAlias : 'p';
        return '('
            . 'EXISTS(SELECT 1 FROM tbl_product_branch_settings auragold_pbs_si '
            . "WHERE auragold_pbs_si.product_id = {$stockAlias}.product_id AND auragold_pbs_si.branch_id = {$stockAlias}.branch_id AND auragold_pbs_si.is_stock_item = 1) "
            . "OR (NOT EXISTS(SELECT 1 FROM tbl_product_branch_settings auragold_pbs_si0 WHERE auragold_pbs_si0.product_id = {$stockAlias}.product_id AND auragold_pbs_si0.branch_id = {$stockAlias}.branch_id) "
            . "AND COALESCE({$productAlias}.is_stock_item, 1) = 1)"
        . ')';
    }
}
if (!function_exists('auragold_sql_show_in_stock_for_product_and_stock_subquery')) {
    function auragold_sql_show_in_stock_for_product_and_stock_subquery(string $stockAlias = 's0', string $productAlias = 'p'): string {
        $stockAlias   = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $stockAlias) ? $stockAlias : 's0';
        $productAlias = preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $productAlias) ? $productAlias : 'p';
        return '('
            . 'EXISTS(SELECT 1 FROM tbl_product_branch_settings pbs_s '
            . "WHERE pbs_s.product_id = {$stockAlias}.product_id AND pbs_s.branch_id = {$stockAlias}.branch_id AND pbs_s.is_stock_item = 1) "
            . "OR (NOT EXISTS(SELECT 1 FROM tbl_product_branch_settings pbs_s0 WHERE pbs_s0.product_id = {$stockAlias}.product_id AND pbs_s0.branch_id = {$stockAlias}.branch_id) "
            . "AND COALESCE({$productAlias}.is_stock_item, 1) = 1)"
        . ')';
    }
}
