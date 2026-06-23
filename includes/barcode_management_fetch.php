<?php

/**
 * Barcode Management — all barcoded stock (present + sold), filterable by metal tab.
 */

require_once __DIR__ . '/gold_silver_stock_list_fetch.php';

if (!function_exists('auragold_barcode_management_metals')) {
    /**
     * @return array<int, array{id:int,name:string,slug:string}>
     */
    function auragold_barcode_management_metals(mysqli $conn): array
    {
        $rows = getList(
            'SELECT id, display_name, system_name FROM tbl_metal WHERE status = 1 ORDER BY display_name ASC, id ASC'
        );
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = trim((string) ($row['display_name'] ?? ''));
            if ($name === '') {
                $name = trim((string) ($row['system_name'] ?? ''));
            }
            if ($name === '') {
                $name = 'Metal ' . $id;
            }
            $out[] = [
                'id' => $id,
                'name' => $name,
                'slug' => auragold_barcode_management_metal_slug($row),
            ];
        }

        return $out;
    }
}

if (!function_exists('auragold_barcode_management_metal_slug')) {
    function auragold_barcode_management_metal_slug(array $row): string
    {
        $sys = strtolower(trim((string) ($row['system_name'] ?? '')));
        if ($sys !== '') {
            $slug = preg_replace('/[^a-z0-9]+/', '_', $sys);
            if ($slug !== '') {
                return $slug;
            }
        }
        $disp = strtolower(trim((string) ($row['display_name'] ?? '')));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $disp);

        return $slug !== '' ? $slug : ('m' . (int) ($row['id'] ?? 0));
    }
}

if (!function_exists('auragold_barcode_management_resolve_branch_id')) {
    function auragold_barcode_management_resolve_branch_id(): int
    {
        $eff = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
        if ($eff > 0) {
            return $eff;
        }
        $wb = isset($_SESSION['working_branch_id']) ? (int) $_SESSION['working_branch_id'] : 0;
        if ($wb <= 0 && !empty($_SESSION['branch_id'])) {
            $wb = (int) $_SESSION['branch_id'];
        }

        return $wb > 0 ? $wb : 0;
    }
}

if (!function_exists('auragold_barcode_management_fetch')) {
    /**
     * @return array{
     *   rows: array<int, array<string, mixed>>,
     *   error: string,
     *   metals: array<int, array{id:int,name:string,slug:string}>,
     *   active_tab: string,
     *   counts: array{present:int,sold:int,total:int}
     * }
     */
    function auragold_barcode_management_fetch(mysqli $conn, string $metalTab = 'all'): array
    {
        $metalTab = strtolower(trim($metalTab));
        $metals = auragold_barcode_management_metals($conn);
        $branch_filter = auragold_barcode_management_resolve_branch_id();

        $all_metal_ids = array_map(static function ($m) {
            return (int) ($m['id'] ?? 0);
        }, $metals);
        $all_metal_ids = array_values(array_filter($all_metal_ids));

        $tab_metal_ids = $all_metal_ids;
        $active_tab = 'all';
        if ($metalTab !== '' && $metalTab !== 'all') {
            $matched = [];
            foreach ($metals as $m) {
                if (($m['slug'] ?? '') === $metalTab || (string) ($m['id'] ?? '') === $metalTab) {
                    $matched[] = (int) $m['id'];
                    $active_tab = (string) $m['slug'];
                }
            }
            if ($matched !== []) {
                $tab_metal_ids = $matched;
            } elseif ($metals !== []) {
                $active_tab = (string) ($metals[0]['slug'] ?? 'all');
                $tab_metal_ids = [(int) ($metals[0]['id'] ?? 0)];
            }
        }

        if (empty($tab_metal_ids)) {
            $tab_metal_ids = [0];
        }
        $scope_ids_sql = implode(',', array_map('intval', $tab_metal_ids));

        $has_stock = gas_tbl_exists($conn, 'tbl_stock');
        $sj_cols = [];
        $has_stock_journal = gas_tbl_exists($conn, 'tbl_stock_journal');
        if ($has_stock_journal) {
            $sj_cols = gas_sj_columns($conn);
            if (!gas_sj_has($sj_cols, 'id') || !gas_sj_has($sj_cols, 'barcode')) {
                $has_stock_journal = false;
            }
        }

        $metal_has_display = false;
        $metal_has_system = false;
        $mh = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_metal');
        if ($mh) {
            while ($mr = mysqli_fetch_assoc($mh)) {
                $fn = strtolower((string) ($mr['Field'] ?? ''));
                if ($fn === 'display_name') {
                    $metal_has_display = true;
                }
                if ($fn === 'system_name') {
                    $metal_has_system = true;
                }
            }
            mysqli_free_result($mh);
        }
        if ($metal_has_display && $metal_has_system) {
            $metal_name_expr = 'COALESCE(NULLIF(TRIM(m.display_name), \'\'), NULLIF(TRIM(m.system_name), \'\'), \'\')';
        } elseif ($metal_has_display) {
            $metal_name_expr = 'COALESCE(NULLIF(TRIM(m.display_name), \'\'), \'\')';
        } elseif ($metal_has_system) {
            $metal_name_expr = 'COALESCE(NULLIF(TRIM(m.system_name), \'\'), \'\')';
        } else {
            $metal_name_expr = '\'\'';
        }

        $inner_where = [
            's.status = 1',
            "(s.barcode IS NOT NULL AND TRIM(COALESCE(s.barcode,'')) <> '')",
            's.metal_id IN (' . $scope_ids_sql . ')',
        ];
        $gas_br_pred = gas_tbl_stock_branch_predicate($conn, $branch_filter, 's');
        if ($gas_br_pred !== '') {
            $inner_where[] = $gas_br_pred;
        }
        $inner_sql = implode(' AND ', $inner_where);

        $gas_stk_in_types_sql = "'opening','purchase','stock_journal','balance','sale_return','inward'";
        $in_qty = gas_stock_inward_qty_expr('s');
        $in_wt = gas_stock_inward_wt_expr('s');

        $agg_subquery = "
    SELECT s.barcode, s.branch_id,
        (SUM(CASE WHEN s.stock_type IN ($gas_stk_in_types_sql) THEN $in_qty ELSE 0 END)
         - SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END)) AS bal_qty,
        (SUM(CASE WHEN s.stock_type IN ($gas_stk_in_types_sql) THEN $in_wt ELSE 0 END)
         - SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END)) AS bal_wt,
        CASE WHEN (
            (SUM(CASE WHEN s.stock_type IN ($gas_stk_in_types_sql) THEN $in_qty ELSE 0 END)
             - SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_qty, 0), s.opening_qty, 0) ELSE 0 END)) > 0.00001
            OR (SUM(CASE WHEN s.stock_type IN ($gas_stk_in_types_sql) THEN $in_wt ELSE 0 END)
             - SUM(CASE WHEN s.stock_type = 'outward' THEN COALESCE(NULLIF(s.current_weight, 0), s.opening_weight, 0) ELSE 0 END)) > 0.00001
        ) THEN 'Present' ELSE 'Sold' END AS barcode_stock_status,
        COALESCE(
            MAX(CASE WHEN s.stock_type IN ($gas_stk_in_types_sql) THEN s.id END),
            MAX(s.id)
        ) AS pick_id
    FROM tbl_stock s
    WHERE $inner_sql
    GROUP BY s.barcode, s.branch_id
";

        $sj_join_sql = '';
        $img_join_sql = '';
        if ($has_stock_journal) {
            $sj_status_sql = gas_sj_active_sql($sj_cols);
            $sj_parts = [
                gas_sj_sel_expr($sj_cols, 'id', 'sj_row_id'),
                gas_sj_sel_expr($sj_cols, 'item_id', 'sj_item_id'),
                gas_sj_sel_expr($sj_cols, 'barcode', 'sj_barcode'),
                gas_sj_sel_expr($sj_cols, 'invoice_id', 'sj_invoice_id'),
                gas_sj_sel_expr($sj_cols, 'huid_no', 'huid_no'),
                gas_sj_sel_expr($sj_cols, 'voucher_type', 'voucher_type'),
                gas_sj_sel_expr($sj_cols, 'invoice_no', 'invoice_no'),
                gas_sj_sel_expr($sj_cols, 'location', 'sj_location'),
                gas_sj_sel_expr($sj_cols, 'gross_weight', 'gross_weight'),
                gas_sj_sel_expr($sj_cols, 'net_weight', 'net_weight'),
                gas_sj_sel_expr($sj_cols, 'purity_weight', 'purity_weight'),
                gas_sj_sel_expr($sj_cols, 'quantity', 'sj_quantity'),
                gas_sj_sel_expr($sj_cols, 'karat', 'sj_karat'),
                gas_sj_sel_expr($sj_cols, 'category', 'sj_category'),
                gas_sj_sel_expr($sj_cols, 'created_at', 'sj_created_at'),
            ];
            $sj_sel_sql = implode(",\n            ", $sj_parts);
            $sj_join_sql = "
    LEFT JOIN (
        SELECT
            $sj_sel_sql
        FROM tbl_stock_journal sj1
        INNER JOIN (
            SELECT barcode, MAX(id) AS max_id
            FROM tbl_stock_journal
            WHERE ($sj_status_sql) AND barcode IS NOT NULL AND TRIM(barcode) <> ''
            GROUP BY barcode
        ) sjmx ON sjmx.max_id = sj1.id
    ) sj ON (sj.sj_barcode COLLATE utf8mb4_general_ci) = (s.barcode COLLATE utf8mb4_general_ci)
        AND s.barcode IS NOT NULL AND TRIM(COALESCE(s.barcode,'')) <> ''
    ";
        }

        $cat_join = '';
        if (gas_tbl_exists($conn, 'tbl_categories')) {
            $cat_join = 'LEFT JOIN tbl_categories cat ON cat.id = p.category_id';
        }

        $select_outer = "
        s.id AS stock_id,
        s.barcode,
        bal.bal_qty AS current_qty,
        bal.bal_wt AS current_weight,
        bal.barcode_stock_status,
        s.opening_weight,
        s.opening_purity,
        s.status AS stock_status,
        s.created_at AS stock_created_at,
        b.name AS branch_name,
        $metal_name_expr AS metal_name,
        p.name AS product_name,
        p.article AS article,
        pc.carat AS pc_carat,
        " . ($cat_join !== '' ? "COALESCE(NULLIF(TRIM(cat.name),''), NULLIF(TRIM(sj.sj_category),''), '')" : "COALESCE(NULLIF(TRIM(sj.sj_category),''), '')") . " AS category_display,
        sj.huid_no,
        sj.voucher_type,
        sj.invoice_no,
        sj.sj_location,
        sj.gross_weight AS sj_gross_weight,
        sj.net_weight AS sj_net_weight,
        sj.purity_weight AS sj_purity_weight,
        sj.sj_quantity,
        sj.sj_karat,
        sj.sj_created_at
";

        $rows = [];
        $load_error = '';
        if (!$has_stock) {
            $load_error = 'Stock table not found.';
        } else {
            if (!$has_stock_journal) {
                $sql = "
    SELECT
        s.id AS stock_id,
        s.barcode,
        bal.bal_qty AS current_qty,
        bal.bal_wt AS current_weight,
        bal.barcode_stock_status,
        s.opening_weight,
        s.opening_purity,
        s.status AS stock_status,
        s.created_at AS stock_created_at,
        b.name AS branch_name,
        $metal_name_expr AS metal_name,
        p.name AS product_name,
        p.article AS article,
        pc.carat AS pc_carat,
        '' AS category_display,
        NULL AS huid_no,
        NULL AS voucher_type,
        NULL AS invoice_no,
        NULL AS sj_location,
        NULL AS sj_gross_weight,
        NULL AS sj_net_weight,
        NULL AS sj_purity_weight,
        NULL AS sj_quantity,
        NULL AS sj_karat,
        NULL AS sj_created_at
    FROM ($agg_subquery) bal
    INNER JOIN tbl_stock s ON s.id = bal.pick_id
    LEFT JOIN tbl_branches b ON s.branch_id = b.id
    LEFT JOIN tbl_metal m ON s.metal_id = m.id
    LEFT JOIN tbl_products p ON s.product_id = p.id
    $cat_join
    LEFT JOIN tbl_product_characteristics pc ON s.product_characteristic_id = pc.id
    ORDER BY bal.barcode_stock_status ASC, s.barcode ASC, s.branch_id ASC
    LIMIT 8000
    ";
            } else {
                $sql = "
    SELECT $select_outer
    FROM ($agg_subquery) bal
    INNER JOIN tbl_stock s ON s.id = bal.pick_id
    LEFT JOIN tbl_branches b ON s.branch_id = b.id
    LEFT JOIN tbl_metal m ON s.metal_id = m.id
    LEFT JOIN tbl_products p ON s.product_id = p.id
    $cat_join
    LEFT JOIN tbl_product_characteristics pc ON s.product_characteristic_id = pc.id
    $sj_join_sql
    ORDER BY bal.barcode_stock_status ASC, s.barcode ASC, s.branch_id ASC
    LIMIT 8000
    ";
            }
            $res = mysqli_query($conn, $sql);
            if (!$res) {
                $load_error = 'Could not load barcodes: ' . htmlspecialchars(mysqli_error($conn), ENT_QUOTES, 'UTF-8');
            } else {
                while ($r = mysqli_fetch_assoc($res)) {
                    $rows[] = $r;
                }
                mysqli_free_result($res);
            }
        }

        $present = 0;
        $sold = 0;
        foreach ($rows as $r) {
            if (($r['barcode_stock_status'] ?? '') === 'Present') {
                $present++;
            } else {
                $sold++;
            }
        }

        return [
            'rows' => $rows,
            'error' => $load_error,
            'metals' => $metals,
            'active_tab' => $active_tab,
            'counts' => [
                'present' => $present,
                'sold' => $sold,
                'total' => count($rows),
            ],
        ];
    }
}
