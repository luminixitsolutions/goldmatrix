<?php
/**
 * SQL fragments for "products assigned to a branch" (tbl_product_branches or tbl_product_characteristics).
 */

if (!function_exists('auragold_sql_products_scope_for_branch')) {
    /**
     * Correlated predicate for tbl_products: product is linked to the given branch.
     *
     * @param int $branch_id Branch registry id (e.g. main branch id)
     */
    function auragold_sql_products_scope_for_branch($branch_id) {
        $branch_id = (int) $branch_id;
        if ($branch_id <= 0) {
            return '0';
        }
        return "(EXISTS (SELECT 1 FROM tbl_product_branches pb WHERE pb.product_id = tbl_products.id AND pb.branch_id = $branch_id AND IFNULL(pb.is_active, 1) = 1) "
            . "OR EXISTS (SELECT 1 FROM tbl_product_characteristics pc WHERE pc.product_id = tbl_products.id AND pc.status = 1 AND pc.branch_id = $branch_id))";
    }
}
