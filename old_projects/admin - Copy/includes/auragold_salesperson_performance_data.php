<?php

/**
 * Salesperson Performance — aggregate POS + standard sale invoice lines by metal/product category.
 */

declare(strict_types=1);

if (!function_exists('auragold_sale_analysis_parse_range')) {
    require_once __DIR__ . '/auragold_sale_analysis_data.php';
}

if (!function_exists('auragold_sp_performance_pos_branch_where_sql')) {
    /**
     * Mirrors {@see auragold_sale_invoices_branch_where_sql} for tbl_pos_sale_invoices alias.
     */
    function auragold_sp_performance_pos_branch_where_sql($conn, string $alias = 'psi'): string {
        if (!$conn instanceof mysqli) {
            return '';
        }
        if (!function_exists('auragold_tbl_has_column') || !auragold_tbl_has_column($conn, 'tbl_pos_sale_invoices', 'branch_id')) {
            return '';
        }
        $eff = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
        if ($eff <= 0) {
            return '';
        }
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        if ($a === '') {
            $a = 'psi';
        }
        $main = function_exists('auragold_settings_main_branch_id') ? (int) auragold_settings_main_branch_id() : 0;
        if ($main > 0 && $eff === $main) {
            return " AND ({$a}.branch_id = {$eff} OR {$a}.branch_id IS NULL OR {$a}.branch_id = 0) ";
        }

        return " AND COALESCE({$a}.branch_id, 0) = {$eff} ";
    }
}

if (!function_exists('auragold_sp_performance_line_outer_sql')) {
    /**
     * Shared SELECT list: category bucket + qty/wts/amounts per invoice line.
     *
     * Expects correlated tables: hdr (sale invoice alias), it (items alias).
     *
     * @param string $invoice_src literal 'si' or 'psi' for DISTINCT bill counts
     * @param string $metal_join fragment joining `mt` to resolved metal id
     */
    function auragold_sp_performance_line_outer_sql(string $invoice_src, string $metal_join, string $carat_decimal_expr): string {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return "
            SELECT
                IF(IFNULL(TRIM(hdr.sales_person), '') = '', '(Unassigned)', TRIM(hdr.sales_person)) AS salesperson_key,
                '{$invoice_src}' AS invoice_src,
                hdr.id AS invoice_id,
                CASE
                    WHEN LOWER(IFNULL(TRIM(it.product_name), '')) REGEXP '(imitation|fashion jewel|artificial jewel|oxidized imitation|american diamond|watch|timepiece)'
                    THEN 'imitation_watches'
                    WHEN LOWER(CONCAT(' ', IFNULL(mt.display_name, ''), ' ', IFNULL(mt.system_name, ''))) LIKE '% silver %'
                         OR LOWER(CONCAT(IFNULL(mt.display_name, ''), IFNULL(mt.system_name, ''))) LIKE 'silver %'
                         OR LOWER(TRIM(CONCAT(IFNULL(mt.display_name, ''), IFNULL(mt.system_name, '')))) LIKE '%silver%'
                    THEN 'silver'
                    WHEN LOWER(CONCAT(IFNULL(mt.display_name, ''), IFNULL(mt.system_name, ''))) LIKE '% platin%' THEN 'platinum'
                    WHEN LOWER(CONCAT(IFNULL(mt.display_name, ''), IFNULL(mt.system_name, ''))) LIKE '%gold%' THEN 'gold'
                    WHEN IFNULL(it.diamond_amount, 0) + IFNULL(it.stone_amount, 0) > 0
                         OR IFNULL(it.stone_weight, 0) > 0
                         OR (IFNULL(it.metal_qty, 0) > 0 AND LOWER(IFNULL(it.calculation_type, '')) LIKE '%carat%')
                         OR LOWER(IFNULL(it.calculation_type, '')) REGEXP '(carat|stone|diamond)'
                         OR (it.carat IS NOT NULL AND TRIM(it.carat) <> ''
                             AND REPLACE(REPLACE(TRIM(it.carat), '+', ''), ' ', '') REGEXP '^[0-9]+(\\\\.[0-9]+)?')
                         OR LOWER(CONCAT(' ', IFNULL(mt.display_name, ''), IFNULL(mt.system_name, ''))) REGEXP '(diamond|gem stone|gemstone)'
                    THEN 'diamond_stones'
                    ELSE 'other_services'
                END AS cat_slug,
                CAST(IFNULL(it.quantity, 0) AS DECIMAL(18, 4)) AS qty_num,
                CAST(IFNULL(it.gross_weight, 0) AS DECIMAL(18, 4)) AS gross_w_num,
                CAST(COALESCE(it.net_weight, it.final_weight, it.gross_weight, 0) AS DECIMAL(18, 4)) AS net_line_num,
                CAST({$carat_decimal_expr} AS DECIMAL(18, 6)) AS d_ct_num,
                CAST(COALESCE(NULLIF(it.net_amt_with_tax, 0), NULLIF(it.net_amount, 0), it.amount, 0) AS DECIMAL(18, 4)) AS sale_line_num,
                CAST(COALESCE(NULLIF(it.net_amt_with_tax, 0), NULLIF(it.net_amount, 0), it.amount, 0)
                    - COALESCE(it.metal_value, 0) - COALESCE(it.making_amount, 0)
                AS DECIMAL(18, 4)) AS gp_line_num
            FROM LINE_ITEMS_TABLE_ALIAS it
            INNER JOIN SALE_HDR_TABLE_ALIAS hdr ON hdr.id = it.invoice_id
                AND IFNULL(hdr.status, '') NOT IN ('cancelled', 'void', 'draft')
                AND DATE(hdr.invoice_date) BETWEEN RANGE_FROM AND RANGE_TO
            {$metal_join}
            WHERE IFNULL(it.status, 1) = 1
              BRANCH_WHERE_ITEMS
        ";
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}

if (!function_exists('auragold_salesperson_performance_fetch_rows')) {
    /**
     * @param array<string, mixed> $sp_groups keys = category slugs in salesperson-performance UI
     * @param array<string, string> $sp_metrics unused but kept for API parity with page
     *
     * @return array<int, array<string, mixed>> rows shaped like sp_sample_row()
     */
    function auragold_salesperson_performance_fetch_rows($conn, string $from_ymd, string $to_ymd, array $sp_groups): array {
        if (!$conn instanceof mysqli || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from_ymd) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to_ymd)) {
            return [];
        }
        $chk_si = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_sale_invoices'");
        if (!$chk_si || mysqli_num_rows($chk_si) === 0) {
            if ($chk_si) {
                mysqli_free_result($chk_si);
            }

            return [];
        }
        mysqli_free_result($chk_si);

        $from_e = esc($from_ymd);
        $to_e = esc($to_ymd);

        $carat_decimal_expr_base = "
            CASE
                WHEN REPLACE(REPLACE(REPLACE(IFNULL(TRIM(it.carat), ''), '+', ''), ' ', ''), ',', '.')
                     REGEXP '^[0-9]+(\\.[0-9]+)?'
                    THEN REPLACE(REPLACE(REPLACE(IFNULL(TRIM(it.carat), ''), '+', ''), ' ', ''), ',', '.')
                ELSE '0'
            END
        ";

        $metal_join_legacy = '
            LEFT JOIN tbl_product_characteristics pc
                ON pc.id = it.product_characteristic_id AND pc.product_id = it.product_id
            LEFT JOIN tbl_metal mt ON mt.id = COALESCE(NULLIF(pc.metal_id, 0),
                IFNULL((SELECT pc2.metal_id FROM tbl_product_characteristics pc2
                    WHERE pc2.product_id = it.product_id AND pc2.status = 1
                    ORDER BY (CASE WHEN pc2.id <=> it.product_characteristic_id THEN 0 ELSE 1 END), pc2.id DESC
                    LIMIT 1), 0))
        ';

        $branch_si = function_exists('auragold_sale_invoices_branch_where_sql')
            ? auragold_sale_invoices_branch_where_sql($conn, 'hdr')
            : '';

        $branch_psi = auragold_sp_performance_pos_branch_where_sql($conn, 'hdr');

        $tpl_si = auragold_sp_performance_line_outer_sql('si', $metal_join_legacy, $carat_decimal_expr_base);
        $sql_si = str_replace(
            ['LINE_ITEMS_TABLE_ALIAS', 'SALE_HDR_TABLE_ALIAS', 'RANGE_FROM', 'RANGE_TO', 'BRANCH_WHERE_ITEMS'],
            ['tbl_sale_invoice_items', 'tbl_sale_invoices', "'{$from_e}'", "'{$to_e}'", $branch_si],
            $tpl_si
        );

        $union_parts = [$sql_si];

        $chk_psi = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_pos_sale_invoices'");
        $has_psi = $chk_psi && mysqli_num_rows($chk_psi) > 0;
        if ($chk_psi) {
            mysqli_free_result($chk_psi);
        }
        if ($has_psi) {
            $tpl_psi = auragold_sp_performance_line_outer_sql('psi', $metal_join_legacy, $carat_decimal_expr_base);
            $sql_psi = str_replace(
                ['LINE_ITEMS_TABLE_ALIAS', 'SALE_HDR_TABLE_ALIAS', 'RANGE_FROM', 'RANGE_TO', 'BRANCH_WHERE_ITEMS'],
                ['tbl_pos_sale_invoice_items', 'tbl_pos_sale_invoices', "'{$from_e}'", "'{$to_e}'", $branch_psi],
                $tpl_psi
            );
            $union_parts[] = $sql_psi;
        }

        $union_sql = implode("\n UNION ALL \n", $union_parts);

        $agg_sql = "
            SELECT
                salesperson_key,
                cat_slug AS bucket,
                SUM(qty_num) AS qty_sum,
                SUM(gross_w_num) AS gw_sum,
                SUM(net_line_num) AS nw_sum,
                SUM(d_ct_num) AS d_ct_sum,
                SUM(sale_line_num) AS sale_sum,
                SUM(gp_line_num) AS gp_sum
            FROM (\n {$union_sql} \n) _lines
            GROUP BY salesperson_key, cat_slug
        ";

        $bills_sql = "
            SELECT salesperson_key, COUNT(DISTINCT CONCAT(invoice_src, ':', invoice_id)) AS bill_cnt
            FROM (\n {$union_sql} \n) _b
            GROUP BY salesperson_key
        ";

        $agg_rows = function_exists('getList') ? getList($agg_sql) : [];
        if (!is_array($agg_rows)) {
            $agg_rows = [];
        }
        $bill_rows = function_exists('getList') ? getList($bills_sql) : [];
        if (!is_array($bill_rows)) {
            $bill_rows = [];
        }
        $bills_by_sp = [];
        foreach ($bill_rows as $br) {
            $k = (string) ($br['salesperson_key'] ?? '');
            $bills_by_sp[$k] = (int) ($br['bill_cnt'] ?? 0);
        }

        $fmt = [
            'qty' => static function ($x): string {
                if (abs((float) $x - round((float) $x)) < 0.000001) {
                    return (string) (int) round((float) $x);
                }

                return number_format((float) $x, 2, '.', '');
            },
            'wt3' => static fn (float $x): string => number_format($x, 3, '.', ''),
            'wt6' => static fn (float $x): string => number_format($x, 6, '.', ''),
            'money' => static fn (float $x): string => number_format($x, 2, '.', ''),
        ];

        $metrics_template = [];
        foreach (array_keys($sp_groups) as $slug) {
            $metrics_template[$slug] = [
                'qty' => $fmt['qty'](0),
                'gross_wt' => $fmt['wt3'](0.0),
                'net_wt' => $fmt['wt3'](0.0),
                'd_ct' => $fmt['wt6'](0.0),
                'sale_amount' => $fmt['money'](0.0),
                'gross_profit' => $fmt['money'](0.0),
            ];
        }

        $clone_metrics_template = static function (array $tpl): array {
            $o = [];
            foreach ($tpl as $slug => $cells) {
                $o[$slug] = $cells;
            }

            return $o;
        };

        $merged = [];

        foreach ($agg_rows as $ar) {
            $spkey = trim((string) ($ar['salesperson_key'] ?? ''));
            if ($spkey === '') {
                $spkey = '(Unassigned)';
            }
            $b = trim((string) ($ar['bucket'] ?? ''));

            if (!isset($merged[$spkey])) {
                $merged[$spkey] = [
                    'name' => $spkey === '(Unassigned)' ? '(Unassigned)' : $spkey,
                    'bills' => (string) ($bills_by_sp[$spkey] ?? 0),
                    'groups' => $clone_metrics_template($metrics_template),
                    'scheme' => ['no_of_scheme' => '0', 'scheme_amount' => $fmt['money'](0.0)],
                ];
            }

            if (!isset($merged[$spkey]['groups'][$b])) {
                continue;
            }
            $merged[$spkey]['groups'][$b] = [
                'qty' => $fmt['qty']((float) ($ar['qty_sum'] ?? 0)),
                'gross_wt' => $fmt['wt3']((float) ($ar['gw_sum'] ?? 0)),
                'net_wt' => $fmt['wt3']((float) ($ar['nw_sum'] ?? 0)),
                'd_ct' => $fmt['wt6']((float) ($ar['d_ct_sum'] ?? 0)),
                'sale_amount' => $fmt['money']((float) ($ar['sale_sum'] ?? 0)),
                'gross_profit' => $fmt['money']((float) ($ar['gp_sum'] ?? 0)),
            ];
        }

        foreach ($bill_rows as $br) {
            $spkey = trim((string) ($br['salesperson_key'] ?? ''));
            if ($spkey === '') {
                $spkey = '(Unassigned)';
            }
            $bcnt = (int) ($br['bill_cnt'] ?? 0);
            if (!isset($merged[$spkey])) {
                $merged[$spkey] = [
                    'name' => $spkey,
                    'bills' => (string) $bcnt,
                    'groups' => $clone_metrics_template($metrics_template),
                    'scheme' => ['no_of_scheme' => '0', 'scheme_amount' => $fmt['money'](0.0)],
                ];
            } else {
                $merged[$spkey]['bills'] = (string) $bcnt;
            }
        }

        $out_rows = [];
        foreach ($merged as $sp_data) {
            $row = ['name' => $sp_data['name'], 'bills' => $sp_data['bills'], 'scheme' => $sp_data['scheme']];
            foreach (array_keys($sp_groups) as $slug) {
                $row[$slug] = $sp_data['groups'][$slug];
            }
            $out_rows[] = $row;
        }

        usort($out_rows, static function ($a, $b): int {
            $an = strtolower((string) ($a['name'] ?? ''));
            $bn = strtolower((string) ($b['name'] ?? ''));

            return $an <=> $bn;
        });

        return $out_rows;
    }
}
