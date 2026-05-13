<?php
/**
 * Shared queries for role / segment dashboards (retailer, wholesaler, manufacturing, etc.).
 * Uses tbl_customer_types.code (CUSTOMER, WHOLESALER, JOB_WORKER, …).
 */

if (!function_exists('auragold_dashboard_normalize_fy_date')) {
    function auragold_dashboard_normalize_fy_date($raw): string {
        $d = trim((string) $raw);
        if ($d !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return $d;
        }

        return '';
    }

    /**
     * Filter a date column to the logged-in financial year (session), when set.
     */
    function auragold_dashboard_fy_date_sql(string $alias, string $dateColumn): string {
        if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['financial_year']) || !is_array($_SESSION['financial_year'])) {
            return '';
        }
        $start = auragold_dashboard_normalize_fy_date($_SESSION['financial_year']['start_date'] ?? '');
        $end   = auragold_dashboard_normalize_fy_date($_SESSION['financial_year']['end_date'] ?? '');
        if ($start === '' || $end === '') {
            return '';
        }
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $dateColumn);
        if ($a === '' || $c === '') {
            return '';
        }

        return " AND {$a}.{$c} >= '" . $start . "' AND {$a}.{$c} <= '" . $end . "' ";
    }

    function auragold_dashboard_mysqli(): ?mysqli {
        global $conn;
        $dbc = $conn ?? $GLOBALS['conn'] ?? null;

        return ($dbc instanceof mysqli) ? $dbc : null;
    }

    /** Branch + FY on tbl_sale_invoices (alias si). */
    function auragold_dashboard_si_extra_sql(string $alias = 'si'): string {
        $dbc = auragold_dashboard_mysqli();
        if (!$dbc) {
            return '';
        }
        $br = function_exists('auragold_sale_invoices_branch_where_sql') ? auragold_sale_invoices_branch_where_sql($dbc, $alias) : '';

        return $br . auragold_dashboard_fy_date_sql($alias, 'invoice_date');
    }

    /**
     * Branch + invoice_date range intersected with logged-in FY (when set). Use when the page already applies a date range (e.g. salesperson period).
     *
     * @param string $rangeStart Y-m-d
     * @param string $rangeEnd   Y-m-d
     */
    function auragold_dashboard_si_scope_for_range_sql(string $alias, string $rangeStart, string $rangeEnd): string {
        $dbc = auragold_dashboard_mysqli();
        if (!$dbc) {
            return '';
        }
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        if ($a === '') {
            $a = 'si';
        }
        $br = function_exists('auragold_sale_invoices_branch_where_sql') ? auragold_sale_invoices_branch_where_sql($dbc, $a) : '';
        $rs = auragold_dashboard_normalize_fy_date($rangeStart);
        $re = auragold_dashboard_normalize_fy_date($rangeEnd);
        if ($rs === '' || $re === '') {
            return $br;
        }
        if ($rs > $re) {
            return $br . ' AND 1=0 ';
        }
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['financial_year']) && is_array($_SESSION['financial_year'])) {
            $fs = auragold_dashboard_normalize_fy_date($_SESSION['financial_year']['start_date'] ?? '');
            $fe = auragold_dashboard_normalize_fy_date($_SESSION['financial_year']['end_date'] ?? '');
            if ($fs !== '' && $fe !== '') {
                if ($re < $fs || $rs > $fe) {
                    return $br . ' AND 1=0 ';
                }
                $rs = max($rs, $fs);
                $re = min($re, $fe);
            }
        }

        return $br . " AND {$a}.invoice_date >= '" . $rs . "' AND {$a}.invoice_date <= '" . $re . "' ";
    }

    function auragold_dashboard_pi_extra_sql(string $alias = 'pi'): string {
        $dbc = auragold_dashboard_mysqli();
        if (!$dbc) {
            return '';
        }
        $br = function_exists('auragold_sql_and_branch_scope') ? auragold_sql_and_branch_scope($dbc, 'tbl_purchase_invoices', $alias) : '';

        return $br . auragold_dashboard_fy_date_sql($alias, 'invoice_date');
    }

    function auragold_dashboard_so_extra_sql(string $alias = 'so'): string {
        $dbc = auragold_dashboard_mysqli();
        if (!$dbc) {
            return '';
        }
        $br = function_exists('auragold_sql_and_branch_scope') ? auragold_sql_and_branch_scope($dbc, 'tbl_sale_orders', $alias) : '';

        return $br . auragold_dashboard_fy_date_sql($alias, 'order_date');
    }

    function auragold_dashboard_jwo_extra_sql(string $alias = 'j'): string {
        $dbc = auragold_dashboard_mysqli();
        if (!$dbc) {
            return '';
        }
        $br = function_exists('auragold_sql_and_branch_scope') ? auragold_sql_and_branch_scope($dbc, 'tbl_jobwork_orders', $alias) : '';

        return $br . auragold_dashboard_fy_date_sql($alias, 'order_date');
    }

    /** Branch filter for tbl_stock snapshot queries (no FY). */
    function auragold_dashboard_stock_branch_sql(string $alias = 's'): string {
        $dbc = auragold_dashboard_mysqli();
        if (!$dbc) {
            return '';
        }

        return function_exists('auragold_sql_and_branch_scope') ? auragold_sql_and_branch_scope($dbc, 'tbl_stock', $alias) : '';
    }
}

if (!function_exists('auragold_dashboard_sale_status_where')) {
    /**
     * Exclude cancelled/void sale invoices from aggregates.
     */
    function auragold_dashboard_sale_status_condition($siAlias = 'si') {
        return "({$siAlias}.status IS NULL OR LOWER(TRIM({$siAlias}.status)) NOT IN ('cancelled','void','canceled'))";
    }

    function auragold_dashboard_sale_status_where($siAlias = 'si') {
        return ' AND ' . auragold_dashboard_sale_status_condition($siAlias) . ' ';
    }

    /**
     * Resolve customer type id by code (case-insensitive), e.g. CUSTOMER, WHOLESALER, JOB_WORKER.
     *
     * @return int 0 if not found
     */
    function auragold_customer_type_id_by_code($code) {
        $code = trim((string) $code);
        if ($code === '' || !function_exists('getRecord')) {
            return 0;
        }
        global $conn;
        $dbc = $conn ?? $GLOBALS['conn'] ?? null;
        if (!$dbc) {
            return 0;
        }
        $esc = mysqli_real_escape_string($dbc, $code);
        $r = getRecord(
            "SELECT id FROM tbl_customer_types WHERE status = 1 AND LOWER(TRIM(code)) = LOWER('$esc') LIMIT 1"
        );
        return $r && isset($r['id']) ? (int) $r['id'] : 0;
    }

    /**
     * @param int $customerTypeId
     * @return array{
     *   customer_count:int,
     *   invoice_count:int,
     *   sale_total:float,
     *   customers_with_sales:int
     * }
     */
    function auragold_dashboard_segment_summary($customerTypeId) {
        $out = [
            'customer_count' => 0,
            'invoice_count' => 0,
            'sale_total' => 0.0,
            'customers_with_sales' => 0,
        ];
        $tid = (int) $customerTypeId;
        if ($tid <= 0 || !function_exists('getRecord')) {
            return $out;
        }
        $st  = auragold_dashboard_sale_status_where('si');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        $r1  = getRecord(
            "SELECT COUNT(DISTINCT c.id) AS c
             FROM tbl_customers c
             INNER JOIN tbl_sale_invoices si ON si.customer_id = c.id
             WHERE c.status = 1 AND c.customer_type_id = $tid
             $st $siX"
        );
        if ($r1) {
            $out['customer_count'] = (int) ($r1['c'] ?? 0);
        }
        $r2 = getRecord(
            "SELECT COUNT(DISTINCT si.customer_id) AS c,
                    COUNT(DISTINCT si.id) AS inv,
                    COALESCE(SUM(si.grand_total),0) AS gtot
             FROM tbl_sale_invoices si
             INNER JOIN tbl_customers c ON si.customer_id = c.id
             WHERE c.customer_type_id = $tid
             $st $siX"
        );
        if ($r2) {
            $out['customers_with_sales'] = (int) ($r2['c'] ?? 0);
            $out['invoice_count'] = (int) ($r2['inv'] ?? 0);
            $out['sale_total'] = (float) ($r2['gtot'] ?? 0);
        }
        return $out;
    }

    /**
     * Recent sale invoices for customers of a given type.
     *
     * @return list<array<string,mixed>>
     */
    function auragold_dashboard_segment_recent_invoices($customerTypeId, $limit = 15) {
        $tid = (int) $customerTypeId;
        $lim = max(1, min(100, (int) $limit));
        if ($tid <= 0 || !function_exists('getList')) {
            return [];
        }
        $st  = auragold_dashboard_sale_status_where('si');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        return getList(
            "SELECT si.id, si.invoice_no, si.invoice_date, si.customer_name, si.grand_total,
                    si.sales_person, si.status, c.customer_type_id
             FROM tbl_sale_invoices si
             INNER JOIN tbl_customers c ON si.customer_id = c.id
             WHERE c.customer_type_id = $tid
             $st $siX
             ORDER BY si.invoice_date DESC, si.id DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * Top customers by sale total for a customer type.
     *
     * @return list<array<string,mixed>>
     */
    function auragold_dashboard_segment_top_customers($customerTypeId, $limit = 10) {
        $tid = (int) $customerTypeId;
        $lim = max(1, min(50, (int) $limit));
        if ($tid <= 0 || !function_exists('getList')) {
            return [];
        }
        $cond = auragold_dashboard_sale_status_condition('si');
        $siX  = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        return getList(
            "SELECT c.id, c.name,
                    (SELECT COUNT(*) FROM tbl_sale_invoices si
                     WHERE si.customer_id = c.id AND $cond $siX) AS inv_count,
                    COALESCE((SELECT SUM(si.grand_total) FROM tbl_sale_invoices si
                     WHERE si.customer_id = c.id AND $cond $siX),0) AS sale_total
             FROM tbl_customers c
             WHERE c.status = 1 AND c.customer_type_id = $tid
             ORDER BY sale_total DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * Sales grouped by sales_person (tbl_sale_invoices.sales_person).
     *
     * @return list<array<string,mixed>>
     */
    function auragold_dashboard_sales_by_salesperson($limit = 25) {
        $lim = max(1, min(100, (int) $limit));
        if (!function_exists('getList')) {
            return [];
        }
        $st  = auragold_dashboard_sale_status_where('si');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        return getList(
            "SELECT TRIM(si.sales_person) AS sp,
                    COUNT(si.id) AS inv_count,
                    COALESCE(SUM(si.grand_total),0) AS sale_total
             FROM tbl_sale_invoices si
             WHERE si.sales_person IS NOT NULL AND TRIM(si.sales_person) <> ''
             $st $siX
             GROUP BY TRIM(si.sales_person)
             ORDER BY sale_total DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * Recent invoices that have a sales person set.
     *
     * @return list<array<string,mixed>>
     */
    function auragold_dashboard_recent_invoices_with_salesperson($limit = 20) {
        $lim = max(1, min(100, (int) $limit));
        if (!function_exists('getList')) {
            return [];
        }
        $st  = auragold_dashboard_sale_status_where('si');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        return getList(
            "SELECT si.invoice_no, si.invoice_date, si.customer_name, si.grand_total,
                    TRIM(si.sales_person) AS sales_person, si.status
             FROM tbl_sale_invoices si
             WHERE si.sales_person IS NOT NULL AND TRIM(si.sales_person) <> ''
             $st $siX
             ORDER BY si.invoice_date DESC, si.id DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * Gold jewellery lines: latest / average metal_rate by carat (from sale invoice lines, Gold metal).
     *
     * @return list<array<string,mixed>>
     */
    function auragold_dashboard_gold_metal_rates_from_sales($limitCarats = 20) {
        $lim = max(1, min(50, (int) $limitCarats));
        if (!function_exists('getList')) {
            return [];
        }
        $st  = auragold_dashboard_sale_status_where('si');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        return getList(
            "SELECT TRIM(sii.carat) AS carat,
                    AVG(sii.metal_rate) AS avg_metal_rate,
                    MAX(sii.metal_rate) AS max_metal_rate,
                    MIN(sii.metal_rate) AS min_metal_rate,
                    MAX(si.invoice_date) AS last_invoice_date,
                    COUNT(*) AS line_count
             FROM tbl_sale_invoice_items sii
             INNER JOIN tbl_sale_invoices si ON sii.invoice_id = si.id
             LEFT JOIN tbl_product_characteristics pc ON sii.product_characteristic_id = pc.id
             WHERE sii.status = 1
             AND sii.metal_rate IS NOT NULL AND sii.metal_rate > 0
             AND pc.metal_id = 1
             $st $siX
             GROUP BY TRIM(sii.carat)
             HAVING carat IS NOT NULL AND TRIM(carat) <> ''
             ORDER BY last_invoice_date DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * Carat master rows (reference).
     *
     * @return list<array<string,mixed>>
     */
    function auragold_dashboard_carat_master() {
        if (!function_exists('getList')) {
            return [];
        }
        return getList(
            "SELECT id, name, purity, description FROM tbl_carat WHERE status = 1 ORDER BY name ASC"
        ) ?: [];
    }

    /**
     * Stock overview: by metal and branch.
     *
     * @return array{by_metal:list,by_branch:list,totals:array}
     */
    function auragold_dashboard_stock_overview() {
        $empty = ['by_metal' => [], 'by_branch' => [], 'totals' => []];
        if (!function_exists('getList') || !function_exists('getRecord')) {
            return $empty;
        }
        $stkBr = function_exists('auragold_dashboard_stock_branch_sql') ? auragold_dashboard_stock_branch_sql('s') : '';
        $byMetal = getList(
            "SELECT m.id, m.display_name AS metal_name,
                    COUNT(s.id) AS row_count,
                    COALESCE(SUM(s.current_weight),0) AS sum_current_weight,
                    COALESCE(SUM(s.current_qty),0) AS sum_current_qty,
                    COALESCE(SUM(s.value),0) AS sum_value
             FROM tbl_stock s
             LEFT JOIN tbl_metal m ON s.metal_id = m.id
             WHERE s.status = 1 $stkBr
             GROUP BY m.id, m.display_name
             ORDER BY m.display_name ASC"
        ) ?: [];

        $byBranch = getList(
            "SELECT b.id, b.name AS branch_name,
                    COUNT(s.id) AS row_count,
                    COALESCE(SUM(s.current_weight),0) AS sum_current_weight,
                    COALESCE(SUM(s.value),0) AS sum_value
             FROM tbl_stock s
             LEFT JOIN tbl_branches b ON s.branch_id = b.id
             WHERE s.status = 1 $stkBr
             GROUP BY b.id, b.name
             ORDER BY b.name ASC"
        ) ?: [];

        $tot = getRecord(
            "SELECT COUNT(*) AS rows_n,
                    COALESCE(SUM(s.current_weight),0) AS w,
                    COALESCE(SUM(s.current_qty),0) AS q,
                    COALESCE(SUM(s.value),0) AS v
             FROM tbl_stock s WHERE s.status = 1 $stkBr"
        );

        return [
            'by_metal' => $byMetal,
            'by_branch' => $byBranch,
            'totals' => [
                'rows' => $tot ? (int) ($tot['rows_n'] ?? 0) : 0,
                'weight' => $tot ? (float) ($tot['w'] ?? 0) : 0,
                'qty' => $tot ? (float) ($tot['q'] ?? 0) : 0,
                'value' => $tot ? (float) ($tot['v'] ?? 0) : 0,
            ],
        ];
    }

    /**
     * JewelSteps-style stock dashboard: KPI grid, metal chart series, karat bars, low stock list.
     *
     * @return array<string,mixed>
     */
    function auragold_stock_dashboard_jewelsteps() {
        $base = auragold_dashboard_stock_overview();
        $out = array_merge($base, [
            'kpi' => [
                'total_products' => 0,
                'total_products_qty' => 0.0,
                'zero_stock_lines' => 0,
                'zero_stock_qty' => 0.0,
                'inward_weight' => 0.0,
                'inward_qty' => 0.0,
                'outward_weight' => 0.0,
                'outward_qty' => 0.0,
                'metals' => [],
            ],
            'metal_chart' => [],
            'metal_chart_branchwise' => [
                'branch_labels' => [],
                'datasets' => [],
                'table_rows' => [],
            ],
            'karatwise' => [],
            'low_stock' => [],
        ]);
        if (!function_exists('getRecord') || !function_exists('getList')) {
            return $out;
        }

        global $conn;
        $dbc = $conn ?? $GLOBALS['conn'] ?? null;

        $stkBr = function_exists('auragold_dashboard_stock_branch_sql') ? auragold_dashboard_stock_branch_sql('s') : '';

        $rProd = getRecord(
            'SELECT COUNT(DISTINCT s.product_id) AS c FROM tbl_stock s WHERE s.status = 1' . $stkBr
        );
        $out['kpi']['total_products'] = $rProd ? (int) ($rProd['c'] ?? 0) : 0;
        $out['kpi']['total_products_qty'] = (float) ($out['totals']['qty'] ?? 0);

        $rZero = getRecord(
            "SELECT COUNT(*) AS c, COALESCE(SUM(s.current_qty),0) AS q FROM tbl_stock s
             WHERE s.status = 1 $stkBr
             AND COALESCE(s.current_weight,0) <= 0
             AND COALESCE(s.current_qty,0) <= 0"
        );
        if ($rZero) {
            $out['kpi']['zero_stock_lines'] = (int) ($rZero['c'] ?? 0);
            $out['kpi']['zero_stock_qty'] = (float) ($rZero['q'] ?? 0);
        }

        $rIn = getRecord(
            "SELECT COALESCE(SUM(s.current_weight),0) AS w, COALESCE(SUM(s.current_qty),0) AS q
             FROM tbl_stock s
             WHERE s.status = 1 $stkBr AND s.stock_type IN ('opening','purchase','sale_return')"
        );
        if ($rIn) {
            $out['kpi']['inward_weight'] = (float) ($rIn['w'] ?? 0);
            $out['kpi']['inward_qty'] = (float) ($rIn['q'] ?? 0);
        }

        $rOut = getRecord(
            "SELECT COALESCE(SUM(s.current_weight),0) AS w, COALESCE(SUM(s.current_qty),0) AS q
             FROM tbl_stock s
             WHERE s.status = 1 $stkBr AND s.stock_type = 'outward'"
        );
        if ($rOut) {
            $out['kpi']['outward_weight'] = (float) ($rOut['w'] ?? 0);
            $out['kpi']['outward_qty'] = (float) ($rOut['q'] ?? 0);
        }

        $mMetalWhere = '';
        if ($dbc instanceof mysqli && function_exists('auragold_tbl_has_column') && function_exists('auragold_master_list_sql_suffix')
            && auragold_tbl_has_column($dbc, 'tbl_metal', 'branch_id')) {
            $mMetalWhere = auragold_master_list_sql_suffix($dbc, 'tbl_metal', 'm.branch_id');
        }
        $metals = getList(
            "SELECT m.id, m.display_name AS name,
                    COALESCE(SUM(s.current_weight),0) AS w,
                    COALESCE(SUM(s.current_qty),0) AS q
             FROM tbl_metal m
             LEFT JOIN tbl_stock s ON s.metal_id = m.id AND s.status = 1 $stkBr
             WHERE m.status = 1 $mMetalWhere
             GROUP BY m.id, m.display_name
             ORDER BY m.id ASC"
        ) ?: [];
        $out['kpi']['metals'] = $metals;

        $chart = [];
        foreach ($metals as $m) {
            $chart[] = [
                'label' => (string) ($m['name'] ?? ''),
                'weight' => round((float) ($m['w'] ?? 0), 3),
            ];
        }
        $out['metal_chart'] = $chart;

        // Branch × metal master: show every active tbl_metal row for each branch (zeros when no stock).
        $stkBrS2 = function_exists('auragold_dashboard_stock_branch_sql') ? auragold_dashboard_stock_branch_sql('s2') : '';
        $hasMetalBranchCol = $dbc instanceof mysqli && function_exists('auragold_tbl_has_column')
            && auragold_tbl_has_column($dbc, 'tbl_metal', 'branch_id');
        $metalJoinOn = $hasMetalBranchCol
            ? 'm.status = 1 AND (m.branch_id = b.id OR m.branch_id IS NULL OR m.branch_id = 0)'
            : 'm.status = 1';
        $bwRows = getList(
            "SELECT b.id AS branch_id,
                    COALESCE(b.name, '—') AS branch_name,
                    m.id AS metal_id,
                    COALESCE(NULLIF(TRIM(m.display_name), ''), 'Unknown') AS metal_display,
                    COALESCE(SUM(s.current_weight), 0) AS w
             FROM tbl_branches b
             INNER JOIN tbl_metal m ON $metalJoinOn
             LEFT JOIN tbl_stock s
                ON s.branch_id = b.id
                AND s.metal_id = m.id
                AND s.status = 1
                $stkBr
             WHERE b.status = 1
               AND b.id IN (
                   SELECT DISTINCT s2.branch_id
                   FROM tbl_stock s2
                   WHERE s2.status = 1
                     AND s2.branch_id IS NOT NULL
                     $stkBrS2
               )
             GROUP BY b.id, b.name, m.id, m.display_name
             ORDER BY branch_name ASC, metal_display ASC, m.id ASC"
        ) ?: [];

        $branchOrder = [];
        $branchSeen = [];
        foreach ($bwRows as $br) {
            $bid = (int) ($br['branch_id'] ?? 0);
            if (!isset($branchSeen[$bid])) {
                $branchSeen[$bid] = true;
                $branchOrder[] = [
                    'id' => $bid,
                    'name' => (string) ($br['branch_name'] ?? '—'),
                ];
            }
        }
        $metalNames = [];
        foreach ($bwRows as $br) {
            $metalNames[(string) ($br['metal_display'] ?? 'Unknown')] = true;
        }
        $metalLabels = array_keys($metalNames);
        sort($metalLabels, SORT_NATURAL | SORT_FLAG_CASE);

        $stacked = [];
        foreach ($metalLabels as $ml) {
            $stacked[$ml] = array_fill(0, count($branchOrder), 0.0);
        }
        foreach ($bwRows as $br) {
            $bid = (int) ($br['branch_id'] ?? 0);
            $idx = null;
            foreach ($branchOrder as $i => $bo) {
                if ((int) ($bo['id'] ?? 0) === $bid) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx === null) {
                continue;
            }
            $mn = (string) ($br['metal_display'] ?? 'Unknown');
            if (!isset($stacked[$mn])) {
                continue;
            }
            $stacked[$mn][$idx] += round((float) ($br['w'] ?? 0), 3);
        }

        $palette = ['#eab308', '#38bdf8', '#a78bfa', '#94a3b8', '#f472b6', '#c084fc', '#34d399', '#fb923c', '#f87171', '#818cf8'];
        $bwDatasets = [];
        foreach ($metalLabels as $mi => $ml) {
            $bwDatasets[] = [
                'label' => $ml,
                'data' => array_values($stacked[$ml]),
                'backgroundColor' => $palette[$mi % count($palette)],
            ];
        }
        $tableRows = [];
        foreach ($bwRows as $br) {
            $tableRows[] = [
                'branch_name' => (string) ($br['branch_name'] ?? '—'),
                'metal_display' => (string) ($br['metal_display'] ?? 'Unknown'),
                'weight' => round((float) ($br['w'] ?? 0), 3),
            ];
        }
        $out['metal_chart_branchwise'] = [
            'branch_labels' => array_map(static function ($b) {
                return (string) ($b['name'] ?? '—');
            }, $branchOrder),
            'datasets' => $bwDatasets,
            'table_rows' => $tableRows,
        ];

        $karatRows = getList(
            "SELECT
                TRIM(CAST(pc.carat AS CHAR)) AS carat_label,
                COALESCE(SUM(s.current_weight),0) AS w,
                COALESCE(SUM(s.current_qty),0) AS q
             FROM tbl_stock s
             INNER JOIN tbl_product_characteristics pc ON s.product_characteristic_id = pc.id
             WHERE s.status = 1 AND s.metal_id = 1 $stkBr
             GROUP BY TRIM(CAST(pc.carat AS CHAR))
             HAVING carat_label IS NOT NULL AND carat_label <> ''
             ORDER BY carat_label ASC"
        ) ?: [];
        foreach ($karatRows as &$kr) {
            $lab = (string) ($kr['carat_label'] ?? '');
            $kr['title'] = $lab !== '' ? $lab . ' (Gold)' : '—';
            $kr['weight'] = (float) ($kr['w'] ?? 0);
            $kr['qty'] = (float) ($kr['q'] ?? 0);
        }
        unset($kr);
        $out['karatwise'] = $karatRows;

        $hasImg = false;
        if ($dbc) {
            $imgCol = @mysqli_query($dbc, "SHOW COLUMNS FROM tbl_product_characteristics LIKE 'images'");
            if ($imgCol && mysqli_num_rows($imgCol) > 0) {
                $hasImg = true;
                mysqli_free_result($imgCol);
            } elseif ($imgCol) {
                mysqli_free_result($imgCol);
            }
        }
        // One row per branch + product. Weight/Qty = totals for that item at that branch (all lines).
        // Low-line count = how many lines match the threshold (qty ≤ 1 or weight ≤ 0); those lines can be 0/0 while other lines hold stock.
        if ($hasImg) {
            $low = getList(
                "SELECT p.id AS product_id,
                        p.name AS product_name,
                        s.branch_id,
                        COALESCE(MAX(b.name), '—') AS branch_name,
                        COUNT(s.id) AS low_line_count,
                        COALESCE(MAX(st.total_weight), 0) AS total_weight,
                        COALESCE(MAX(st.total_qty), 0) AS total_qty,
                        MAX(pc.images) AS images
                 FROM tbl_stock s
                 INNER JOIN tbl_products p ON s.product_id = p.id
                 LEFT JOIN tbl_branches b ON s.branch_id = b.id
                 LEFT JOIN tbl_product_characteristics pc ON s.product_characteristic_id = pc.id
                 LEFT JOIN (
                     SELECT s2.product_id,
                            s2.branch_id,
                            COALESCE(SUM(s2.current_weight), 0) AS total_weight,
                            COALESCE(SUM(s2.current_qty), 0) AS total_qty
                     FROM tbl_stock s2
                     WHERE s2.status = 1 $stkBrS2
                     GROUP BY s2.product_id, s2.branch_id
                 ) st ON st.product_id = p.id AND (st.branch_id <=> s.branch_id)
                 WHERE s.status = 1 $stkBr
                 AND (COALESCE(s.current_qty,0) <= 1 OR COALESCE(s.current_weight,0) <= 0)
                 GROUP BY s.branch_id, p.id, p.name
                 ORDER BY COALESCE(MAX(st.total_qty), 0) ASC, COALESCE(MAX(st.total_weight), 0) ASC
                 LIMIT 20"
            ) ?: [];
        } else {
            $low = getList(
                "SELECT p.id AS product_id,
                        p.name AS product_name,
                        s.branch_id,
                        COALESCE(MAX(b.name), '—') AS branch_name,
                        COUNT(s.id) AS low_line_count,
                        COALESCE(MAX(st.total_weight), 0) AS total_weight,
                        COALESCE(MAX(st.total_qty), 0) AS total_qty,
                        NULL AS images
                 FROM tbl_stock s
                 INNER JOIN tbl_products p ON s.product_id = p.id
                 LEFT JOIN tbl_branches b ON s.branch_id = b.id
                 LEFT JOIN tbl_product_characteristics pc ON s.product_characteristic_id = pc.id
                 LEFT JOIN (
                     SELECT s2.product_id,
                            s2.branch_id,
                            COALESCE(SUM(s2.current_weight), 0) AS total_weight,
                            COALESCE(SUM(s2.current_qty), 0) AS total_qty
                     FROM tbl_stock s2
                     WHERE s2.status = 1 $stkBrS2
                     GROUP BY s2.product_id, s2.branch_id
                 ) st ON st.product_id = p.id AND (st.branch_id <=> s.branch_id)
                 WHERE s.status = 1 $stkBr
                 AND (COALESCE(s.current_qty,0) <= 1 OR COALESCE(s.current_weight,0) <= 0)
                 GROUP BY s.branch_id, p.id, p.name
                 ORDER BY COALESCE(MAX(st.total_qty), 0) ASC, COALESCE(MAX(st.total_weight), 0) ASC
                 LIMIT 20"
            ) ?: [];
        }
        $out['low_stock'] = $low;

        return $out;
    }

    /**
     * Human label for customer type code (for page titles).
     */
    function auragold_customer_type_label($code) {
        $map = [
            'CUSTOMER' => 'Retailer (Customer)',
            'WHOLESALER' => 'Wholesaler',
            'JOB_WORKER' => 'Manufacturing / Job worker',
        ];
        $k = strtoupper(trim((string) $code));
        return $map[$k] ?? $k;
    }

    function auragold_dashboard_purchase_status_where($alias = 'pi') {
        return ' AND (' . $alias . '.status IS NULL OR LOWER(TRIM(' . $alias . '.status)) NOT IN (\'cancelled\',\'void\',\'canceled\')) ';
    }

    function auragold_dashboard_order_status_where($alias = 'so') {
        return ' AND (' . $alias . '.status IS NULL OR LOWER(TRIM(' . $alias . '.status)) NOT IN (\'cancelled\',\'void\',\'canceled\')) ';
    }

    /**
     * Latest running balance for a system ledger row (customer_id = 0) in tbl_customer_ledger.
     */
    function auragold_ledger_system_balance($ledgerName) {
        global $conn;
        $dbc = $conn ?? $GLOBALS['conn'] ?? null;
        if (!$dbc || !function_exists('getRecord')) {
            return 0.0;
        }
        $n = mysqli_real_escape_string($dbc, trim((string) $ledgerName));
        if ($n === '') {
            return 0.0;
        }
        $r = getRecord(
            "SELECT balance_amount FROM tbl_customer_ledger
             WHERE customer_id = 0 AND customer_name = '$n'
             ORDER BY id DESC LIMIT 1"
        );
        return $r ? (float) ($r['balance_amount'] ?? 0) : 0.0;
    }

    /**
     * Sum latest system-ledger balances for multiple account names (e.g. Bank + Bank Account).
     */
    function auragold_ledger_system_balance_sum(array $ledgerNames) {
        $sum = 0.0;
        foreach ($ledgerNames as $name) {
            $sum += auragold_ledger_system_balance((string) $name);
        }
        return $sum;
    }

    /**
     * JewelSteps-style home KPIs for a customer segment (CUSTOMER / WHOLESALER): today’s figures,
     * 7-day sales series, gold market lines. Purchase totals are global (not filtered by segment).
     *
     * @param string $customerTypeCode tbl_customer_types.code e.g. CUSTOMER, WHOLESALER
     * @return array<string,mixed>
     */
    function auragold_segment_retail_dashboard_kpis($customerTypeCode) {
        $empty = [
            'sales_today' => 0.0,
            'purchase_today' => 0.0,
            'orders_today' => 0,
            'cash_today' => 0.0,
            'bank_today' => 0.0,
            'card_today' => 0.0,
            'balance_cash' => 0.0,
            'balance_bank' => 0.0,
            'balance_card' => 0.0,
            'chart_labels' => [],
            'chart_values' => [],
            'market' => ['18k' => null, '21k' => null, '22k' => null, '24k' => null],
            'customer_type_id' => 0,
        ];
        if (!function_exists('getRecord') || !function_exists('getList')) {
            return $empty;
        }

        $today = date('Y-m-d');
        $code = strtoupper(trim((string) $customerTypeCode));
        $tid = $code !== '' ? auragold_customer_type_id_by_code($code) : 0;
        $st  = auragold_dashboard_sale_status_where('si');
        $pt  = auragold_dashboard_purchase_status_where('pi');
        $ot  = auragold_dashboard_order_status_where('so');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        $piX = function_exists('auragold_dashboard_pi_extra_sql') ? auragold_dashboard_pi_extra_sql('pi') : '';
        $soX = function_exists('auragold_dashboard_so_extra_sql') ? auragold_dashboard_so_extra_sql('so') : '';

        $saleJoin = '';
        $saleWhereExtra = '';
        if ($tid > 0) {
            $saleJoin = ' INNER JOIN tbl_customers c ON si.customer_id = c.id ';
            $saleWhereExtra = " AND c.customer_type_id = $tid ";
        }

        $rSales = getRecord(
            "SELECT COALESCE(SUM(si.grand_total),0) AS t FROM tbl_sale_invoices si
             $saleJoin
             WHERE si.invoice_date = '$today' $saleWhereExtra $st $siX"
        );
        $salesToday = $rSales ? (float) ($rSales['t'] ?? 0) : 0.0;

        $rPur = getRecord(
            "SELECT COALESCE(SUM(pi.grand_total),0) AS t FROM tbl_purchase_invoices pi
             WHERE pi.invoice_date = '$today' $pt $piX"
        );
        $purchaseToday = $rPur ? (float) ($rPur['t'] ?? 0) : 0.0;

        $orderExtra = '';
        if ($tid > 0) {
            $orderExtra = " AND (so.customer_id IS NULL OR EXISTS (SELECT 1 FROM tbl_customers cx WHERE cx.id = so.customer_id AND cx.customer_type_id = $tid))";
        }
        $rOrd = getRecord(
            "SELECT COUNT(*) AS c FROM tbl_sale_orders so
             WHERE so.order_date = '$today' $ot $orderExtra $soX"
        );
        $ordersToday = $rOrd ? (int) ($rOrd['c'] ?? 0) : 0;

        $payJoin = ' INNER JOIN tbl_sale_invoices si ON sip.invoice_id = si.id ';
        $payExtra = '';
        if ($tid > 0) {
            $payJoin .= ' INNER JOIN tbl_customers c2 ON si.customer_id = c2.id ';
            $payExtra = " AND c2.customer_type_id = $tid ";
        }
        $payWhere = " sip.status = 1 AND si.invoice_date = '$today' $payExtra $st $siX ";
        // Match UI labels from sale invoice (Cash, Bank, UPI, Cheque, Card) case-insensitively.
        $ptCash = "LOWER(TRIM(COALESCE(sip.payment_type,''))) = 'cash'";
        $ptBank = "LOWER(TRIM(COALESCE(sip.payment_type,''))) IN ('bank','upi','cheque')";
        $ptCard = "LOWER(TRIM(COALESCE(sip.payment_type,''))) = 'card'";

        $rCash = getRecord(
            "SELECT COALESCE(SUM(sip.amount),0) AS t FROM tbl_sale_invoice_payments sip
             $payJoin WHERE $payWhere AND $ptCash"
        );
        $rBank = getRecord(
            "SELECT COALESCE(SUM(sip.amount),0) AS t FROM tbl_sale_invoice_payments sip
             $payJoin WHERE $payWhere AND $ptBank"
        );
        $rCard = getRecord(
            "SELECT COALESCE(SUM(sip.amount),0) AS t FROM tbl_sale_invoice_payments sip
             $payJoin WHERE $payWhere AND $ptCard"
        );

        $chartLabels = [];
        $chartValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime('-' . $i . ' days'));
            $chartLabels[] = date('D j', strtotime($d));
            $rDay = getRecord(
                "SELECT COALESCE(SUM(si.grand_total),0) AS t FROM tbl_sale_invoices si
                 $saleJoin
                 WHERE si.invoice_date = '$d' $saleWhereExtra $st $siX"
            );
            $chartValues[] = $rDay ? round((float) ($rDay['t'] ?? 0), 2) : 0.0;
        }

        $rates = auragold_dashboard_gold_metal_rates_from_sales(40);
        $market = ['18k' => null, '21k' => null, '22k' => null, '24k' => null];
        foreach ($rates as $row) {
            $k = strtolower(str_replace(' ', '', (string) ($row['carat'] ?? '')));
            if (preg_match('/^18/', $k)) {
                $market['18k'] = $row;
            } elseif (preg_match('/^21/', $k)) {
                $market['21k'] = $row;
            } elseif (preg_match('/^22/', $k)) {
                $market['22k'] = $row;
            } elseif (preg_match('/^24/', $k)) {
                $market['24k'] = $row;
            }
        }

        return [
            'sales_today' => $salesToday,
            'purchase_today' => $purchaseToday,
            'orders_today' => $ordersToday,
            'cash_today' => $rCash ? (float) ($rCash['t'] ?? 0) : 0.0,
            'bank_today' => $rBank ? (float) ($rBank['t'] ?? 0) : 0.0,
            'card_today' => $rCard ? (float) ($rCard['t'] ?? 0) : 0.0,
            'balance_cash' => auragold_ledger_system_balance('Cash'),
            'balance_bank' => auragold_ledger_system_balance_sum(['Bank Account', 'Bank']),
            'balance_card' => auragold_ledger_system_balance('Card'),
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'market' => $market,
            'customer_type_id' => $tid,
        ];
    }

    /**
     * Retailer (CUSTOMER type) — same data as {@see auragold_segment_retail_dashboard_kpis}('CUSTOMER').
     *
     * @return array<string,mixed>
     */
    function auragold_retailer_dashboard_kpis() {
        $r = auragold_segment_retail_dashboard_kpis('CUSTOMER');
        $r['retailer_type_id'] = (int) ($r['customer_type_id'] ?? 0);

        return $r;
    }

    /**
     * Wholesaler (WHOLESALER type) — same layout/KPI logic as retailer, filtered by wholesaler customers.
     *
     * @return array<string,mixed>
     */
    function auragold_wholesaler_dashboard_kpis() {
        return auragold_segment_retail_dashboard_kpis('WHOLESALER');
    }

    /**
     * @param string $tableName
     */
    function auragold_table_exists($tableName) {
        global $conn;
        $dbc = $conn ?? $GLOBALS['conn'] ?? null;
        if (!$dbc) {
            return false;
        }
        $t = mysqli_real_escape_string($dbc, $tableName);
        $r = @mysqli_query($dbc, "SHOW TABLES LIKE '$t'");
        $ok = $r && mysqli_num_rows($r) > 0;
        if ($r) {
            mysqli_free_result($r);
        }
        return $ok;
    }

    /**
     * Manufacturing / jobwork dashboard: KPIs from tbl_jobwork_orders + sale order lists.
     *
     * @return array<string,mixed>
     */
    function auragold_manufacturing_dashboard() {
        $out = [
            'has_jobwork' => false,
            'kpi' => [
                'in_progress' => 0,
                'delayed' => 0,
                'on_hold' => 0,
                'not_initiate' => 0,
            ],
            'list_in_progress' => [],
            'workstation_rows' => [],
            'list_on_hold' => [],
            'list_delayed' => [],
            'recent_sale_orders' => [],
            'completed_orders' => [],
            'total_jobwork' => 0,
            'total_sale_orders' => 0,
        ];
        if (!function_exists('getRecord') || !function_exists('getList')) {
            return $out;
        }

        $jw  = 'tbl_jobwork_orders';
        $jwoX = function_exists('auragold_dashboard_jwo_extra_sql') ? auragold_dashboard_jwo_extra_sql('j') : '';
        $soX  = function_exists('auragold_dashboard_so_extra_sql') ? auragold_dashboard_so_extra_sql('so') : '';
        if (!auragold_table_exists($jw)) {
            $out['recent_sale_orders'] = auragold_mfg_recent_sale_orders(12);
            $out['completed_orders'] = auragold_mfg_completed_sale_orders(12);
            if (auragold_table_exists('tbl_sale_orders')) {
                $rso = getRecord('SELECT COUNT(*) AS c FROM tbl_sale_orders so WHERE 1=1 ' . $soX);
                $out['total_sale_orders'] = $rso ? (int) ($rso['c'] ?? 0) : 0;
            }
            return $out;
        }

        $out['has_jobwork'] = true;
        $stDone = " LOWER(TRIM(IFNULL(j.status,''))) IN ('cancelled','void','canceled','completed','done','closed') ";
        $stOpen = " NOT ($stDone) ";

        $rTot = getRecord("SELECT COUNT(*) AS c FROM $jw j WHERE 1=1 $jwoX");
        $out['total_jobwork'] = $rTot ? (int) ($rTot['c'] ?? 0) : 0;

        $rHold = getRecord(
            "SELECT COUNT(*) AS c FROM $jw j WHERE 1=1 $jwoX AND (
             LOWER(TRIM(IFNULL(j.status,''))) LIKE '%hold%'
             OR LOWER(TRIM(IFNULL(j.status,''))) IN ('on hold','on_hold','hold'))"
        );
        $out['kpi']['on_hold'] = $rHold ? (int) ($rHold['c'] ?? 0) : 0;

        $rNi = getRecord(
            "SELECT COUNT(*) AS c FROM $jw j WHERE 1=1 $jwoX AND (
             LOWER(TRIM(IFNULL(j.status,''))) IN ('draft','pending','not_initiated','not initiate')
             OR TRIM(IFNULL(j.status,'')) = '')"
        );
        $out['kpi']['not_initiate'] = $rNi ? (int) ($rNi['c'] ?? 0) : 0;

        $rDel = getRecord(
            "SELECT COUNT(*) AS c FROM $jw j WHERE 1=1 $jwoX AND $stOpen
             AND j.due_date IS NOT NULL AND j.due_date < CURDATE()"
        );
        $out['kpi']['delayed'] = $rDel ? (int) ($rDel['c'] ?? 0) : 0;

        $rIp = getRecord(
            "SELECT COUNT(*) AS c FROM $jw j WHERE 1=1 $jwoX AND $stOpen
             AND LOWER(TRIM(IFNULL(j.status,''))) NOT IN ('draft','pending')
             AND TRIM(IFNULL(j.status,'')) <> ''
             AND LOWER(TRIM(IFNULL(j.status,''))) NOT LIKE '%hold%'
             AND NOT (j.due_date IS NOT NULL AND j.due_date < CURDATE())"
        );
        $out['kpi']['in_progress'] = $rIp ? (int) ($rIp['c'] ?? 0) : 0;

        $out['list_in_progress'] = getList(
            "SELECT j.id, j.jobwork_no, j.customer_name, j.sale_order_no, j.order_date, j.due_date, j.status, j.department_id
             FROM $jw j WHERE 1=1 $jwoX AND $stOpen
             AND LOWER(TRIM(IFNULL(j.status,''))) NOT IN ('draft','pending')
             AND TRIM(IFNULL(j.status,'')) <> ''
             AND LOWER(TRIM(IFNULL(j.status,''))) NOT LIKE '%hold%'
             AND NOT (j.due_date IS NOT NULL AND j.due_date < CURDATE())
             ORDER BY j.due_date ASC, j.id DESC
             LIMIT 20"
        ) ?: [];

        $out['list_on_hold'] = getList(
            "SELECT j.id, j.jobwork_no, j.customer_name, j.sale_order_no, j.order_date, j.due_date, j.status, j.department_id
             FROM $jw j WHERE 1=1 $jwoX AND
             (LOWER(TRIM(IFNULL(j.status,''))) LIKE '%hold%' OR LOWER(TRIM(IFNULL(j.status,''))) IN ('on hold','on_hold','hold'))
             ORDER BY j.order_date DESC
             LIMIT 20"
        ) ?: [];

        $out['list_delayed'] = getList(
            "SELECT j.id, j.jobwork_no, j.customer_name, j.sale_order_no, j.order_date, j.due_date, j.status, j.department_id
             FROM $jw j WHERE 1=1 $jwoX AND $stOpen
             AND j.due_date IS NOT NULL AND j.due_date < CURDATE()
             ORDER BY j.due_date ASC
             LIMIT 20"
        ) ?: [];

        $out['workstation_rows'] = auragold_mfg_workstation_summary();

        $out['recent_sale_orders'] = auragold_mfg_recent_sale_orders(12);
        $out['completed_orders'] = auragold_mfg_completed_sale_orders(12);

        if (auragold_table_exists('tbl_sale_orders')) {
            $rso = getRecord('SELECT COUNT(*) AS c FROM tbl_sale_orders so WHERE 1=1 ' . $soX);
            $out['total_sale_orders'] = $rso ? (int) ($rso['c'] ?? 0) : 0;
        }

        return $out;
    }

    /**
     * @return list<array<string,mixed>>
     */
    function auragold_mfg_workstation_summary() {
        if (!function_exists('getList') || !auragold_table_exists('tbl_jobwork_orders')) {
            return [];
        }
        $jwoX = function_exists('auragold_dashboard_jwo_extra_sql') ? auragold_dashboard_jwo_extra_sql('j') : '';
        $rows = getList(
            "SELECT TRIM(j.department_id) AS dept_key, COUNT(*) AS order_count
             FROM tbl_jobwork_orders j
             WHERE 1=1 $jwoX AND j.department_id IS NOT NULL AND TRIM(j.department_id) <> ''
             GROUP BY TRIM(j.department_id)
             ORDER BY order_count DESC
             LIMIT 25"
        ) ?: [];
        $deptNames = [];
        if (auragold_table_exists('tbl_departments')) {
            $dl = getList('SELECT id, dept_name FROM tbl_departments WHERE status = 1');
            foreach ($dl ?: [] as $d) {
                $deptNames[(string) ($d['id'] ?? '')] = (string) ($d['dept_name'] ?? '');
            }
        }
        foreach ($rows as &$r) {
            $k = (string) ($r['dept_key'] ?? '');
            $r['dept_label'] = $deptNames[$k] ?? $k;
        }
        unset($r);
        return $rows;
    }

    /**
     * @return list<array<string,mixed>>
     */
    function auragold_mfg_recent_sale_orders($limit = 12) {
        if (!function_exists('getList') || !auragold_table_exists('tbl_sale_orders')) {
            return [];
        }
        $lim = max(1, min(50, (int) $limit));
        $soX = function_exists('auragold_dashboard_so_extra_sql') ? auragold_dashboard_so_extra_sql('so') : '';
        return getList(
            "SELECT so.id, so.customer_name, so.order_no, so.order_date, so.status,
                    (SELECT MIN(soi.barcode) FROM tbl_sale_order_items soi WHERE soi.order_id = so.id AND soi.barcode IS NOT NULL AND TRIM(soi.barcode) <> '') AS tag_no
             FROM tbl_sale_orders so
             WHERE 1=1 $soX
             ORDER BY so.order_date DESC, so.id DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * @return list<array<string,mixed>>
     */
    function auragold_mfg_completed_sale_orders($limit = 12) {
        if (!function_exists('getList') || !auragold_table_exists('tbl_sale_orders')) {
            return [];
        }
        $lim = max(1, min(50, (int) $limit));
        $ot  = auragold_dashboard_order_status_where('so');
        $soX = function_exists('auragold_dashboard_so_extra_sql') ? auragold_dashboard_so_extra_sql('so') : '';
        return getList(
            "SELECT so.id, so.customer_name, so.order_no, so.order_date, so.status,
                    (SELECT MIN(soi.barcode) FROM tbl_sale_order_items soi WHERE soi.order_id = so.id AND soi.barcode IS NOT NULL AND TRIM(soi.barcode) <> '') AS tag_no
             FROM tbl_sale_orders so
             WHERE LOWER(TRIM(IFNULL(so.status,''))) IN ('completed','done','closed','delivered','fulfilled')
             $ot $soX
             ORDER BY so.order_date DESC, so.id DESC
             LIMIT $lim"
        ) ?: [];
    }

    /**
     * Sales person filter for tbl_sale_invoices (alias si). Empty or ALL = no filter.
     */
    function auragold_salesperson_sql_filter($selectedSp, $alias = 'si') {
        global $conn;
        $dbc = $conn ?? $GLOBALS['conn'] ?? null;
        $sp = trim((string) $selectedSp);
        if ($sp === '' || strtoupper($sp) === 'ALL') {
            return '';
        }
        if (!$dbc) {
            return '';
        }
        $esc = mysqli_real_escape_string($dbc, $sp);
        return " AND LOWER(TRIM($alias.sales_person)) = LOWER('$esc') ";
    }

    /**
     * @return list<string>
     */
    function auragold_salesperson_distinct_names() {
        if (!function_exists('getList')) {
            return [];
        }
        $st  = auragold_dashboard_sale_status_where('si');
        $siX = function_exists('auragold_dashboard_si_extra_sql') ? auragold_dashboard_si_extra_sql('si') : '';
        $rows = getList(
            "SELECT DISTINCT TRIM(si.sales_person) AS sp FROM tbl_sale_invoices si
             WHERE si.sales_person IS NOT NULL AND TRIM(si.sales_person) <> ''
             $st $siX
             ORDER BY sp ASC"
        ) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $s = trim((string) ($r['sp'] ?? ''));
            if ($s !== '') {
                $out[] = $s;
            }
        }
        return $out;
    }

    /**
     * @return array{start:string,end:string,kpi_end:string,period_key:string}
     */
    function auragold_salesperson_period_bounds($period) {
        $today = new \DateTime('today');
        $p = strtolower(trim((string) $period));

        if ($p === 'today') {
            $d = $today->format('Y-m-d');
            return ['start' => $d, 'end' => $d, 'kpi_end' => $d, 'period_key' => 'today'];
        }

        if ($p === 'this_week') {
            $mon = clone $today;
            $mon->modify('monday this week');
            $sun = clone $mon;
            $sun->modify('+6 days');
            $kpiEnd = $today <= $sun ? $today : $sun;
            return [
                'start' => $mon->format('Y-m-d'),
                'end' => $sun->format('Y-m-d'),
                'kpi_end' => $kpiEnd->format('Y-m-d'),
                'period_key' => 'week',
            ];
        }

        if ($p === 'last_month') {
            $first = new \DateTime('first day of last month');
            $last = new \DateTime('last day of last month');
            $ds = $first->format('Y-m-d');
            $de = $last->format('Y-m-d');
            return ['start' => $ds, 'end' => $de, 'kpi_end' => $de, 'period_key' => 'month'];
        }

        // this_month (default)
        $first = new \DateTime($today->format('Y-m-01'));
        $last = new \DateTime($today->format('Y-m-t'));
        $kpiEnd = $today <= $last ? $today : $last;
        return [
            'start' => $first->format('Y-m-d'),
            'end' => $last->format('Y-m-d'),
            'kpi_end' => $kpiEnd->format('Y-m-d'),
            'period_key' => 'month',
        ];
    }

    /**
     * JewelSteps-style salesperson dashboard: KPIs, daily chart, leaderboards.
     *
     * @return array<string,mixed>
     */
    function auragold_salesperson_dashboard_data($selectedSp, $period) {
        $empty = [
            'kpi' => [
                'total_sales' => 0.0,
                'total_making' => 0.0,
                'total_invoices' => 0,
                'today_sales' => 0.0,
                'today_making' => 0.0,
            ],
            'chart_labels' => [],
            'chart_values' => [],
            'top_performers' => [],
            'weak_performers' => [],
            'salesperson_options' => [],
        ];
        if (!function_exists('getRecord') || !function_exists('getList')) {
            $empty['bounds'] = auragold_salesperson_period_bounds($period);
            return $empty;
        }

        $st  = auragold_dashboard_sale_status_where('si');
        $spf = auragold_salesperson_sql_filter($selectedSp, 'si');
        $bounds = auragold_salesperson_period_bounds($period);
        $start = $bounds['start'];
        $end = $bounds['end'];
        $kpiEnd = $bounds['kpi_end'];
        $today = date('Y-m-d');

        $rngKpi = function_exists('auragold_dashboard_si_scope_for_range_sql')
            ? auragold_dashboard_si_scope_for_range_sql('si', $start, $kpiEnd)
            : '';
        $rngToday = function_exists('auragold_dashboard_si_scope_for_range_sql')
            ? auragold_dashboard_si_scope_for_range_sql('si', $today, $today)
            : '';

        $empty['salesperson_options'] = auragold_salesperson_distinct_names();

        $rTot = getRecord(
            "SELECT COALESCE(SUM(si.grand_total),0) AS t, COUNT(DISTINCT si.id) AS c
             FROM tbl_sale_invoices si
             WHERE 1=1 $spf $st $rngKpi"
        );
        $rMk = getRecord(
            "SELECT COALESCE(SUM(sii.making_amount),0) AS m
             FROM tbl_sale_invoice_items sii
             INNER JOIN tbl_sale_invoices si ON sii.invoice_id = si.id
             WHERE sii.status = 1 $spf $st $rngKpi"
        );
        $rTd = getRecord(
            "SELECT COALESCE(SUM(si.grand_total),0) AS t
             FROM tbl_sale_invoices si
             WHERE 1=1 $spf $st $rngToday"
        );
        $rTm = getRecord(
            "SELECT COALESCE(SUM(sii.making_amount),0) AS m
             FROM tbl_sale_invoice_items sii
             INNER JOIN tbl_sale_invoices si ON sii.invoice_id = si.id
             WHERE sii.status = 1 $spf $st $rngToday"
        );

        $chartLabels = [];
        $chartValues = [];
        $chartStart = new \DateTime($start);
        $chartEnd = new \DateTime($end);
        $iter = clone $chartStart;
        while ($iter <= $chartEnd) {
            $ds = $iter->format('Y-m-d');
            $chartLabels[] = $iter->format('j');
            $rngDs = function_exists('auragold_dashboard_si_scope_for_range_sql')
                ? auragold_dashboard_si_scope_for_range_sql('si', $ds, $ds)
                : '';
            $rDay = getRecord(
                "SELECT COALESCE(SUM(si.grand_total),0) AS t FROM tbl_sale_invoices si
                 WHERE 1=1 $spf $st $rngDs"
            );
            $chartValues[] = $rDay ? round((float) ($rDay['t'] ?? 0), 2) : 0.0;
            $iter->modify('+1 day');
        }

        $top = getList(
            "SELECT TRIM(si.sales_person) AS name, COALESCE(SUM(si.grand_total),0) AS amount
             FROM tbl_sale_invoices si
             WHERE TRIM(IFNULL(si.sales_person,'')) <> ''
             $st $rngKpi
             GROUP BY TRIM(si.sales_person)
             ORDER BY COALESCE(SUM(si.grand_total),0) DESC
             LIMIT 10"
        ) ?: [];

        $weak = getList(
            "SELECT TRIM(si.sales_person) AS name, COALESCE(SUM(si.grand_total),0) AS amount
             FROM tbl_sale_invoices si
             WHERE TRIM(IFNULL(si.sales_person,'')) <> ''
             $st $rngKpi
             GROUP BY TRIM(si.sales_person)
             HAVING COALESCE(SUM(si.grand_total),0) > 0
             ORDER BY COALESCE(SUM(si.grand_total),0) ASC
             LIMIT 10"
        ) ?: [];

        return [
            'kpi' => [
                'total_sales' => $rTot ? (float) ($rTot['t'] ?? 0) : 0.0,
                'total_making' => $rMk ? (float) ($rMk['m'] ?? 0) : 0.0,
                'total_invoices' => $rTot ? (int) ($rTot['c'] ?? 0) : 0,
                'today_sales' => $rTd ? (float) ($rTd['t'] ?? 0) : 0.0,
                'today_making' => $rTm ? (float) ($rTm['m'] ?? 0) : 0.0,
            ],
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'top_performers' => $top,
            'weak_performers' => $weak,
            'salesperson_options' => $empty['salesperson_options'],
            'bounds' => $bounds,
        ];
    }
}
