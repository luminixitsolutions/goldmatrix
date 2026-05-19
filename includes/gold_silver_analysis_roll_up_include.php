<?php

/**
 * Gold/Silver analysis — shared roll-up SQL.
 * Included by gold-silver-analysis.php and stock-details export endpoints.
 */

// Search and filters
$search_raw = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$search_term = $search_raw !== '' ? esc($search_raw) : '';
$branch_ids = [];
if (isset($_GET['branch'])) {
    if (is_array($_GET['branch'])) {
        $branch_ids = array_values(array_unique(array_filter(array_map('intval', $_GET['branch']))));
    } else {
        $b = (int) $_GET['branch'];
        if ($b > 0) {
            $branch_ids = [$b];
        }
    }
}

$gsa_effective_branch_id = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
// Scope to login branch when no branch filter in URL; use ?branch=0 (All Branches) to show every branch.
if (empty($branch_ids) && $gsa_effective_branch_id > 0 && !isset($_GET['branch'])) {
    $branch_ids = [$gsa_effective_branch_id];
}

$metal_filter_ids = [];
if (isset($_GET['metal'])) {
    if (is_array($_GET['metal'])) {
        $metal_filter_ids = array_values(array_unique(array_filter(array_map('intval', $_GET['metal']))));
    } else {
        $m = (int) $_GET['metal'];
        if ($m > 0) {
            $metal_filter_ids = [$m];
        }
    }
}

// Advance filter (modal) — same GET keys on apply
$adv_to_raw = isset($_GET['adv_to']) ? trim((string) $_GET['adv_to']) : '';
$adv_to_sql = '';
if ($adv_to_raw !== '') {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $adv_to_raw)) {
        $adv_to_sql = $adv_to_raw;
    } elseif (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $adv_to_raw, $m)) {
        $adv_to_sql = $m[3] . '-' . $m[2] . '-' . $m[1];
    }
}
$adv_serial = isset($_GET['adv_serial']) ? strtolower(trim((string) $_GET['adv_serial'])) : 'both';
if (!in_array($adv_serial, ['both', 'yes', 'no'], true)) {
    $adv_serial = 'both';
}
$adv_product_ids = [];
if (isset($_GET['adv_product'])) {
    if (is_array($_GET['adv_product'])) {
        $adv_product_ids = array_values(array_unique(array_filter(array_map('intval', $_GET['adv_product']))));
    } else {
        $p = (int) $_GET['adv_product'];
        if ($p > 0) {
            $adv_product_ids = [$p];
        }
    }
}

$adv_articles = [];
if (isset($_GET['adv_article'])) {
    if (is_array($_GET['adv_article'])) {
        foreach ($_GET['adv_article'] as $a) {
            $a = trim((string) $a);
            if ($a !== '') {
                $adv_articles[] = $a;
            }
        }
    } else {
        $a = trim((string) $_GET['adv_article']);
        if ($a !== '') {
            $adv_articles[] = $a;
        }
    }
}
$adv_articles = array_values(array_unique($adv_articles));

$adv_karat_ids = [];
if (isset($_GET['adv_karat'])) {
    if (is_array($_GET['adv_karat'])) {
        $adv_karat_ids = array_values(array_unique(array_filter(array_map('intval', $_GET['adv_karat']))));
    } else {
        $k = (int) $_GET['adv_karat'];
        if ($k > 0) {
            $adv_karat_ids = [$k];
        }
    }
}

$adv_category_ids = [];
if (isset($_GET['adv_category'])) {
    if (is_array($_GET['adv_category'])) {
        $adv_category_ids = array_values(array_unique(array_filter(array_map('intval', $_GET['adv_category']))));
    } else {
        $c = (int) $_GET['adv_category'];
        if ($c > 0) {
            $adv_category_ids = [$c];
        }
    }
}

$adv_group = isset($_GET['adv_group']) ? trim((string) $_GET['adv_group']) : '';
$adv_gross_wt = isset($_GET['adv_gross']) ? trim((string) $_GET['adv_gross']) : '';

$gsa_stock_date_field = '';
$sdq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_stock');
if ($sdq) {
    $date_candidates = ['created_at', 'created_on', 'updated_at', 'stock_date', 'transaction_date', 'entry_date'];
    while ($r = mysqli_fetch_assoc($sdq)) {
        $f = strtolower((string) ($r['Field'] ?? ''));
        if (in_array($f, $date_candidates, true)) {
            $gsa_stock_date_field = $r['Field'];
            break;
        }
    }
    mysqli_free_result($sdq);
}

$gsa_sj_has_group_name = false;
$gj = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock_journal LIKE 'group_name'");
if ($gj && mysqli_num_rows($gj) > 0) {
    $gsa_sj_has_group_name = true;
}
if ($gj) {
    mysqli_free_result($gj);
}

// This page: Gold and Silver stock only (tbl_metal.display_name)
$scope_metals = getList("SELECT id, display_name AS name FROM tbl_metal WHERE status = 1 AND display_name IN ('Gold','Silver') ORDER BY display_name ASC");
$scope_metal_ids = array_map('intval', array_column($scope_metals ?: [], 'id'));
if (empty($scope_metal_ids)) {
    $scope_metal_ids = [1, 2];
}
if (empty($scope_metals) && !empty($scope_metal_ids)) {
    $scope_metals = getList("SELECT id, display_name AS name FROM tbl_metal WHERE status = 1 AND id IN (" . implode(',', $scope_metal_ids) . ") ORDER BY display_name ASC");
}

$metal_filter_ids = array_values(array_intersect($metal_filter_ids, $scope_metal_ids));
$branch_filter = count($branch_ids) === 1 ? (int) $branch_ids[0] : 0;
$metal_filter = count($metal_filter_ids) === 1 ? (int) $metal_filter_ids[0] : 0;

// Build WHERE clause
// Current Stock tab: display_qty/weight = receipt totals (opening + purchase + stock_journal + balance lots) minus outward — matches Stock Details pcs closing when the same tbl_stock rows are used.
$where_clause = "s.status = 1 AND s.stock_type IN ('opening', 'purchase', 'stock_journal', 'outward', 'balance', 'inward', 'sale_return')";
if ($search_term != '') {
    $where_clause .= " AND (p.name LIKE '%$search_term%' OR p.article LIKE '%$search_term%' OR p.alternate_name LIKE '%$search_term%')";
}
if (!empty($branch_ids)) {
    $where_clause .= ' AND s.branch_id IN (' . implode(',', array_map('intval', $branch_ids)) . ')';
}
$effective_metal_ids = !empty($metal_filter_ids) ? $metal_filter_ids : $scope_metal_ids;
$where_clause .= ' AND s.metal_id IN (' . implode(',', array_map('intval', $effective_metal_ids)) . ')';
// "Show In Stock" on Product Opening: tbl_product_branch_settings per (product, branch)
$where_clause .= ' AND ' . auragold_sql_show_in_stock_for_stock_table('s', 'p');

if ($adv_to_sql !== '' && $gsa_stock_date_field !== '') {
    $df = preg_replace('/[^a-zA-Z0-9_]/', '', $gsa_stock_date_field);
    if ($df !== '') {
        $adv_to_esc = esc($adv_to_sql);
        $where_clause .= " AND DATE(s.`$df`) <= '$adv_to_esc'";
    }
}
if ($adv_serial === 'yes') {
    $where_clause .= " AND s.barcode IS NOT NULL AND TRIM(s.barcode) != ''";
} elseif ($adv_serial === 'no') {
    $where_clause .= " AND (s.barcode IS NULL OR TRIM(s.barcode) = '')";
}
if (!empty($adv_product_ids)) {
    $where_clause .= ' AND s.product_id IN (' . implode(',', array_map('intval', $adv_product_ids)) . ')';
}
if (!empty($adv_articles)) {
    $parts = [];
    foreach ($adv_articles as $a) {
        $parts[] = "'" . esc($a) . "'";
    }
    $where_clause .= ' AND p.article IN (' . implode(',', $parts) . ')';
}
if (!empty($adv_karat_ids)) {
    $kn_parts = [];
    foreach ($adv_karat_ids as $kid) {
        $kr = getRecord('SELECT name FROM tbl_carat WHERE id = ' . (int) $kid . ' AND status = 1 LIMIT 1');
        if (!empty($kr['name'])) {
            $kn_parts[] = "'" . esc(trim((string) $kr['name'])) . "'";
        }
    }
    $kn_parts = array_unique($kn_parts);
    if (!empty($kn_parts)) {
        $where_clause .= ' AND pc.carat IN (' . implode(',', $kn_parts) . ')';
    }
}
if (!empty($adv_category_ids)) {
    $where_clause .= ' AND p.category_id IN (' . implode(',', array_map('intval', $adv_category_ids)) . ')';
}
if ($adv_group !== '' && $gsa_sj_has_group_name) {
    $g_like = '%' . esc(str_replace(['%', '_'], ['\\%', '\\_'], $adv_group)) . '%';
    $where_clause .= " AND EXISTS (
        SELECT 1 FROM tbl_stock_journal sj_grp
        WHERE sj_grp.status = 'active'
        AND sj_grp.group_name LIKE '$g_like'
        AND (
            sj_grp.product_id = s.product_id
            OR EXISTS (
                SELECT 1 FROM tbl_product_characteristics pcg
                WHERE pcg.id = sj_grp.product_characteristic_id
                AND pcg.product_id = s.product_id AND pcg.branch_id = s.branch_id AND pcg.metal_id = s.metal_id AND pcg.status = 1
            )
        )
    )";
}
if ($adv_gross_wt !== '' && is_numeric($adv_gross_wt)) {
    $gw = (float) $adv_gross_wt;
    $where_clause .= ' AND ABS(COALESCE(s.opening_weight, s.current_weight, 0) - ' . $gw . ') < 0.02';
}

$adv_filter_count = ($adv_to_sql !== '' ? 1 : 0)
    + ($adv_serial !== 'both' ? 1 : 0)
    + (!empty($branch_ids) ? 1 : 0)
    + (!empty($metal_filter_ids) ? 1 : 0)
    + (!empty($adv_product_ids) ? 1 : 0)
    + (!empty($adv_articles) ? 1 : 0)
    + (!empty($adv_karat_ids) ? 1 : 0)
    + (!empty($adv_category_ids) ? 1 : 0)
    + ($adv_group !== '' ? 1 : 0)
    + ($adv_gross_wt !== '' ? 1 : 0)
    + ($search_raw !== '' ? 1 : 0);

// Shared inner query: group by product + branch + metal + product_characteristic_id (used by Current Stock and Stock Details tabs).
// Aggregate from tbl_stock only (same scope as Stock Availability Wt). Do not filter purchase rows by
// tbl_purchase_invoice_items time-match — that hid valid lines (e.g. journal-linked purchase) and broke totals.
// Per-row qty/weight use COALESCE(NULLIF(current_* ,0), opening_*) so current_qty/current_weight = 0 still picks up opening_* (sold-down lots stay visible in totals).
// Inner subquery: $stock_inner_sql = $stock_inner_select . $stock_inner_from
//   SELECT ... FROM tbl_stock s LEFT JOIN ... WHERE ... GROUP BY s.product_id, s.branch_id, s.metal_id, s.product_characteristic_id
$gsa_join_location = '';
$gsa_loc_sql = "MAX('') AS location_name";
$gsa_loc_chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_product_characteristics LIKE 'location_id'");
$gsa_loc_tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_location'");
$gsa_has_loc_table = ($gsa_loc_tbl && mysqli_num_rows($gsa_loc_tbl) > 0);
if ($gsa_loc_tbl) {
    mysqli_free_result($gsa_loc_tbl);
}
if ($gsa_loc_chk && mysqli_num_rows($gsa_loc_chk) > 0 && $gsa_has_loc_table) {
    $gsa_join_location = "\n    LEFT JOIN tbl_location loc_pc ON loc_pc.id = pc.location_id";
    $gsa_loc_sql = 'MAX(loc_pc.name) AS location_name';
}
if ($gsa_loc_chk) {
    mysqli_free_result($gsa_loc_chk);
}

$stock_inner_from = "
    FROM tbl_stock s
    LEFT JOIN tbl_products p ON s.product_id = p.id
    LEFT JOIN tbl_metal m ON s.metal_id = m.id
    LEFT JOIN tbl_branches b ON s.branch_id = b.id
    LEFT JOIN tbl_product_characteristics pc
        ON s.product_characteristic_id = pc.id
    $gsa_join_location
    WHERE $where_clause
    GROUP BY s.product_id, s.branch_id, s.metal_id, s.product_characteristic_id
";
$stock_inner_select = "
    SELECT 
        s.product_id,
        s.product_characteristic_id,
        s.branch_id,
        s.metal_id,
        MAX(p.name) as product_name,
        MAX(p.article) as article,
        MAX(p.alternate_name) as alternate_name,
        MAX(m.display_name) as metal_name,
        MAX(b.name) as branch_name,
        $gsa_loc_sql,
        MAX(pc.hsn) as hsn,
        MAX(pc.sku_code) as sku_code,
        MAX(pc.making_on) as making_on,
        MAX(pc.diamond_category) as diamond_category,
        MAX(pc.carat) as carat,
        SUM(
            CASE 
                WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return')
                THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0)
                ELSE 0
            END
        ) as purchase_qty,
        COALESCE((
            SELECT SUM(COALESCE(pii3.metal_weight, pii3.gross_weight, 0))
            FROM tbl_purchase_invoice_items pii3
            INNER JOIN tbl_product_characteristics pc3 ON pc3.id = pii3.product_characteristic_id AND pc3.product_id = s.product_id AND pc3.branch_id = s.branch_id AND pc3.metal_id = s.metal_id AND pc3.status = 1
            WHERE pii3.product_id = s.product_id AND pii3.status = 1
        ), 0) as purchase_metal_weight,
        COALESCE((
            SELECT SUM(sj.quantity)
            FROM tbl_stock_journal sj
            WHERE sj.status = 'active'
            AND (
                EXISTS (
                    SELECT 1 FROM tbl_purchase_invoice_items pii2
                    INNER JOIN tbl_product_characteristics pc2 ON pc2.id = pii2.product_characteristic_id
                    WHERE pii2.id = sj.item_id AND pii2.status = 1
                    AND pc2.product_id = s.product_id AND pc2.branch_id = s.branch_id AND pc2.metal_id = s.metal_id AND pc2.status = 1
                )
                OR EXISTS (
                    SELECT 1 FROM tbl_product_characteristics pc2
                    WHERE pc2.id = sj.product_characteristic_id
                    AND pc2.product_id = s.product_id AND pc2.branch_id = s.branch_id AND pc2.metal_id = s.metal_id AND pc2.status = 1
                )
            )
        ), 0) as production_qty,
        COALESCE((
            SELECT SUM(COALESCE(sj.gross_weight, sj.net_weight, 0))
            FROM tbl_stock_journal sj
            WHERE sj.status = 'active'
            AND (
                EXISTS (
                    SELECT 1 FROM tbl_purchase_invoice_items pii2
                    INNER JOIN tbl_product_characteristics pc2 ON pc2.id = pii2.product_characteristic_id
                    WHERE pii2.id = sj.item_id AND pii2.status = 1
                    AND pc2.product_id = s.product_id AND pc2.branch_id = s.branch_id AND pc2.metal_id = s.metal_id AND pc2.status = 1
                )
                OR EXISTS (
                    SELECT 1 FROM tbl_product_characteristics pc2
                    WHERE pc2.id = sj.product_characteristic_id
                    AND pc2.product_id = s.product_id AND pc2.branch_id = s.branch_id AND pc2.metal_id = s.metal_id AND pc2.status = 1
                )
            )
        ), 0) as production_weight,
        COALESCE((
            SELECT SUM(sii.quantity)
            FROM tbl_sale_invoice_items sii
            INNER JOIN tbl_sale_invoices si ON sii.invoice_id = si.id
            INNER JOIN tbl_product_characteristics pc4 ON pc4.id = sii.product_characteristic_id AND pc4.product_id = s.product_id AND pc4.branch_id = s.branch_id AND pc4.metal_id = s.metal_id AND pc4.status = 1
            WHERE sii.product_id = s.product_id AND sii.status = 1 AND si.status != 'cancelled'
        ), 0) as sale_invoice_qty,
        SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) as inward_gross_sum,
        SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * (CASE WHEN COALESCE(s.opening_purity, 0) <= 1 THEN COALESCE(s.opening_purity, 0) ELSE COALESCE(s.opening_purity, 0) / 100 END) ELSE 0 END) as inward_pure_sum,
        SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * (CASE WHEN COALESCE(s.opening_purity, 0) <= 1 THEN COALESCE(s.opening_purity, 0) ELSE COALESCE(s.opening_purity, 0) / 100 END) ELSE 0 END) as outward_pure_sum,
        SUM(
            CASE 
                WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return')
                THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0)
                ELSE 0
            END
        ) as available_qty,
        (SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) - SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END)) as stock_net_weight,
        SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) as outward_weight_sum,
        SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END) as inward_receipt_qty_sum,
        SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) as inward_receipt_weight_sum,
        SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * (CASE WHEN COALESCE(s.opening_purity, 0) <= 1 THEN COALESCE(s.opening_purity, 0) ELSE COALESCE(s.opening_purity, 0) / 100 END) ELSE 0 END) as inward_receipt_pure_sum,
        SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END) as outward_qty_sum,
        SUM(s.opening_weight) as opening_weight,
        CASE WHEN SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) > 0 THEN SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * COALESCE(s.opening_purity, 0) ELSE 0 END) / SUM(CASE WHEN s.stock_type IN ('opening','purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) ELSE MAX(s.opening_purity) END as opening_purity,
        SUM(CASE WHEN s.stock_type = 'opening' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) AS sd_gross_opening,
        SUM(CASE WHEN s.stock_type IN ('purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) AS sd_gross_in,
        SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END) AS sd_gross_out,
        SUM(CASE WHEN s.stock_type = 'opening' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * (CASE WHEN COALESCE(s.opening_purity, 0) <= 1 THEN COALESCE(s.opening_purity, 0) ELSE COALESCE(s.opening_purity, 0) / 100 END) ELSE 0 END) AS sd_pure_opening,
        SUM(CASE WHEN s.stock_type IN ('purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * (CASE WHEN COALESCE(s.opening_purity, 0) <= 1 THEN COALESCE(s.opening_purity, 0) ELSE COALESCE(s.opening_purity, 0) / 100 END) ELSE 0 END) AS sd_pure_in,
        SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) * (CASE WHEN COALESCE(s.opening_purity, 0) <= 1 THEN COALESCE(s.opening_purity, 0) ELSE COALESCE(s.opening_purity, 0) / 100 END) ELSE 0 END) AS sd_pure_out,
        SUM(CASE WHEN s.stock_type = 'opening' THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END) AS sd_pcs_opening,
        SUM(CASE WHEN s.stock_type IN ('purchase','stock_journal','balance','inward','sale_return') THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END) AS sd_pcs_in,
        SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END) AS sd_pcs_out,
        SUM(s.value) as value,
        MAX(s.final_weight) as final_weight,
        MAX(s.rate) as rate
";
$stock_inner_sql = $stock_inner_select . $stock_inner_from;

// Roll characteristic-level rows up to product + branch + metal (matches list/report grain).
$stock_roll_up_sql = "
    SELECT 
        product_id,
        branch_id,
        metal_id,
        MAX(product_characteristic_id) as product_characteristic_id,
        MAX(product_name) as product_name,
        MAX(article) as article,
        MAX(alternate_name) as alternate_name,
        MAX(metal_name) as metal_name,
        MAX(branch_name) as branch_name,
        MAX(location_name) as location_name,
        MAX(hsn) as hsn,
        MAX(sku_code) as sku_code,
        MAX(making_on) as making_on,
        MAX(diamond_category) as diamond_category,
        MAX(carat) as carat,
        SUM(purchase_qty) as purchase_qty,
        MAX(purchase_metal_weight) as purchase_metal_weight,
        MAX(production_qty) as production_qty,
        MAX(production_weight) as production_weight,
        MAX(sale_invoice_qty) as sale_invoice_qty,
        SUM(inward_gross_sum) as inward_gross_sum,
        SUM(inward_pure_sum) as inward_pure_sum,
        SUM(outward_pure_sum) as outward_pure_sum,
        SUM(available_qty) as available_qty,
        SUM(stock_net_weight) as stock_net_weight,
        SUM(outward_weight_sum) as outward_weight_sum,
        SUM(inward_receipt_qty_sum) as inward_receipt_qty_sum,
        SUM(inward_receipt_weight_sum) as inward_receipt_weight_sum,
        SUM(inward_receipt_pure_sum) as inward_receipt_pure_sum,
        SUM(outward_qty_sum) as outward_qty_sum,
        SUM(sd_gross_opening) AS sd_gross_opening,
        SUM(sd_gross_in) AS sd_gross_in,
        SUM(sd_gross_out) AS sd_gross_out,
        SUM(sd_pure_opening) AS sd_pure_opening,
        SUM(sd_pure_in) AS sd_pure_in,
        SUM(sd_pure_out) AS sd_pure_out,
        SUM(sd_pcs_opening) AS sd_pcs_opening,
        SUM(sd_pcs_in) AS sd_pcs_in,
        SUM(sd_pcs_out) AS sd_pcs_out,
        SUM(opening_weight) as opening_weight,
        MAX(opening_purity) as opening_purity,
        SUM(value) as value,
        MAX(final_weight) as final_weight,
        MAX(rate) as rate
    FROM (
        $stock_inner_sql
    ) tmp
    GROUP BY product_id, branch_id, metal_id
";
