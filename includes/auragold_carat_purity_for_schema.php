<?php
/**
 * Carat master: separate purity % for Sales, Purchase, and Common on one row.
 */
if (!function_exists('auragold_ensure_tbl_carat_purity_split')) {
    function auragold_ensure_tbl_carat_purity_split($conn): void
    {
        if (!$conn || !function_exists('auragold_tbl_has_column')) {
            return;
        }
        $t = 'tbl_carat';
        foreach (
            [
                'purity_sales'    => "DECIMAL(12,3) NULL DEFAULT NULL COMMENT 'Purity % for sales vouchers' AFTER `purity`",
                'purity_purchase' => "DECIMAL(12,3) NULL DEFAULT NULL COMMENT 'Purity % for purchase vouchers' AFTER `purity_sales`",
                'purity_common'   => "DECIMAL(12,3) NULL DEFAULT NULL COMMENT 'Purity % for common/stock' AFTER `purity_purchase`",
            ] as $col => $def
        ) {
            if (!auragold_tbl_has_column($conn, $t, $col)) {
                @mysqli_query($conn, "ALTER TABLE `{$t}` ADD COLUMN `{$col}` {$def}");
            }
        }
        if (auragold_tbl_has_column($conn, $t, 'purity_common') && auragold_tbl_has_column($conn, $t, 'purity')) {
            @mysqli_query(
                $conn,
                "UPDATE `{$t}` SET
                    purity_common = purity
                 WHERE status = 1
                   AND purity IS NOT NULL AND TRIM(CAST(purity AS CHAR)) != ''
                   AND (purity_common IS NULL OR TRIM(CAST(purity_common AS CHAR)) = '')"
            );
            @mysqli_query(
                $conn,
                "UPDATE `{$t}` SET
                    purity_sales = purity
                 WHERE status = 1
                   AND purity IS NOT NULL AND TRIM(CAST(purity AS CHAR)) != ''
                   AND (purity_sales IS NULL OR TRIM(CAST(purity_sales AS CHAR)) = '')"
            );
            @mysqli_query(
                $conn,
                "UPDATE `{$t}` SET
                    purity_purchase = purity
                 WHERE status = 1
                   AND purity IS NOT NULL AND TRIM(CAST(purity AS CHAR)) != ''
                   AND (purity_purchase IS NULL OR TRIM(CAST(purity_purchase AS CHAR)) = '')"
            );
        }
    }
}

/** @deprecated use auragold_ensure_tbl_carat_purity_split */
if (!function_exists('auragold_ensure_tbl_carat_purity_for')) {
    function auragold_ensure_tbl_carat_purity_for($conn): void
    {
        auragold_ensure_tbl_carat_purity_split($conn);
    }
}

if (!function_exists('auragold_carat_has_split_purity')) {
    function auragold_carat_has_split_purity($conn): bool
    {
        return $conn instanceof mysqli
            && function_exists('auragold_tbl_has_column')
            && auragold_tbl_has_column($conn, 'tbl_carat', 'purity_sales');
    }
}

/** @deprecated */
if (!function_exists('auragold_carat_has_purity_for')) {
    function auragold_carat_has_purity_for($conn): bool
    {
        return auragold_carat_has_split_purity($conn);
    }
}

if (!function_exists('auragold_carat_format_purity_display')) {
    function auragold_carat_format_purity_display($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $s = trim((string) $value);
        return $s === '' ? '-' : $s;
    }
}

if (!function_exists('auragold_carat_normalize_purity_input')) {
    function auragold_carat_normalize_purity_input($value): string
    {
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (!is_numeric($s)) {
            return '';
        }
        return $s;
    }
}

if (!function_exists('auragold_carat_resolve_purity_for_context')) {
    /**
     * @param array<string,mixed> $row
     * @param string              $context sales|purchase|common|all
     */
    function auragold_carat_resolve_purity_for_context(array $row, string $context = 'common'): string
    {
        $ctx = strtolower(trim($context));
        if ($ctx === 'sale') {
            $ctx = 'sales';
        }
        if ($ctx === 'pur') {
            $ctx = 'purchase';
        }
        $legacy = trim((string) ($row['purity'] ?? ''));
        $sales = trim((string) ($row['purity_sales'] ?? ''));
        $purchase = trim((string) ($row['purity_purchase'] ?? ''));
        $common = trim((string) ($row['purity_common'] ?? ''));

        if ($ctx === 'sales') {
            return $sales !== '' ? $sales : ($common !== '' ? $common : $legacy);
        }
        if ($ctx === 'purchase') {
            return $purchase !== '' ? $purchase : ($common !== '' ? $common : $legacy);
        }
        if ($ctx === 'common') {
            return $common !== '' ? $common : $legacy;
        }
        return $common !== '' ? $common : ($sales !== '' ? $sales : ($purchase !== '' ? $purchase : $legacy));
    }
}

if (!function_exists('auragold_carat_apply_context_purity')) {
    /** @param array<string,mixed> $row */
    function auragold_carat_apply_context_purity(array $row, string $context = 'all'): array
    {
        $row['purity'] = auragold_carat_resolve_purity_for_context($row, $context);
        return $row;
    }
}

if (!function_exists('auragold_carat_purity_for_sql_filter')) {
    /** No row filter — each carat row holds all three purity values. */
    function auragold_carat_purity_for_sql_filter($conn, string $context, string $alias = ''): string
    {
        unset($conn, $context, $alias);
        return '';
    }
}

if (!function_exists('auragold_get_carat_list')) {
    /**
     * @param string $context sales|purchase|common|all — sets resolved `purity` for dropdowns
     */
    function auragold_get_carat_list($conn, string $context = 'all'): array
    {
        if (!$conn instanceof mysqli || !function_exists('getList')) {
            return [];
        }
        auragold_ensure_tbl_carat_purity_split($conn);
        $suffix = function_exists('auragold_master_list_sql_suffix')
            ? auragold_master_list_sql_suffix($conn, 'tbl_carat')
            : '';
        $has_metal = function_exists('auragold_tbl_has_column') && auragold_tbl_has_column($conn, 'tbl_carat', 'metal_id');
        $has_split = auragold_carat_has_split_purity($conn);
        $extra = $has_split ? ', purity_sales, purity_purchase, purity_common' : '';
        if ($has_metal) {
            $sql = 'SELECT id, name, purity, description, metal_id' . $extra
                . ' FROM tbl_carat WHERE status = 1 ' . $suffix
                . ' ORDER BY metal_id IS NULL, metal_id ASC, id ASC';
        } else {
            $sql = 'SELECT id, name, purity, description' . $extra
                . ' FROM tbl_carat WHERE status = 1 ' . $suffix . ' ORDER BY id ASC';
        }
        $list = getList($sql);
        if (!is_array($list)) {
            return [];
        }
        $ctx = $context === '' ? 'all' : $context;
        foreach ($list as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $list[$i] = auragold_carat_apply_context_purity($row, $ctx);
        }
        return $list;
    }
}
