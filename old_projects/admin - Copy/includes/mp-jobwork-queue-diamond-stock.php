<?php
if (!function_exists('mp_jwq_diamond_issue_table_name')) {
    function mp_jwq_diamond_issue_table_name(): string
    {
        return 'tbl_jobwork_queue_diamond_stock_issue';
    }
}

if (!function_exists('mp_jwq_ensure_diamond_issue_table')) {
    function mp_jwq_ensure_diamond_issue_table(mysqli $conn): void
    {
        $tbl = mp_jwq_diamond_issue_table_name();
        @mysqli_query(
            $conn,
            'CREATE TABLE IF NOT EXISTS `' . $tbl . '` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `jobwork_order_id` int(11) NOT NULL,
              `jobwork_order_item_id` int(11) DEFAULT NULL,
              `stock_id` int(11) NOT NULL,
              `barcode` varchar(100) DEFAULT NULL,
              `product_name` varchar(255) DEFAULT NULL,
              `diamond_category` varchar(100) DEFAULT NULL,
              `weight` decimal(14,4) NOT NULL DEFAULT 0,
              `qty` decimal(14,4) NOT NULL DEFAULT 0,
              `from_dept_id` int(11) DEFAULT NULL,
              `to_dept_id` int(11) DEFAULT NULL,
              `from_user_id` int(11) DEFAULT NULL,
              `to_user_id` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_jwo_item_stock` (`jobwork_order_id`,`jobwork_order_item_id`,`stock_id`),
              KEY `idx_jwo` (`jobwork_order_id`),
              KEY `idx_item` (`jobwork_order_item_id`),
              KEY `idx_stock` (`stock_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $has_col = static function (mysqli $conn, string $table, string $col): bool {
            $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '" . mysqli_real_escape_string($conn, $col) . "'");
            $ok = ($q && mysqli_num_rows($q) > 0);
            if ($q) {
                mysqli_free_result($q);
            }
            return $ok;
        };
        $has_key = static function (mysqli $conn, string $table, string $key): bool {
            $q = @mysqli_query($conn, "SHOW INDEX FROM `$table` WHERE Key_name = '" . mysqli_real_escape_string($conn, $key) . "'");
            $ok = ($q && mysqli_num_rows($q) > 0);
            if ($q) {
                mysqli_free_result($q);
            }
            return $ok;
        };
        $safe_sql = static function (mysqli $conn, string $sql): void {
            try {
                @mysqli_query($conn, $sql);
            } catch (Throwable $e) {
                // Ignore migration races/duplicates; table already exists in desired shape.
            }
        };

        if (!$has_col($conn, $tbl, 'diamond_category')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `diamond_category` varchar(100) DEFAULT NULL AFTER `product_name`");
        }
        if (!$has_col($conn, $tbl, 'weight')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `weight` decimal(14,4) NOT NULL DEFAULT 0 AFTER `diamond_category`");
        }
        if (!$has_col($conn, $tbl, 'qty')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `qty` decimal(14,4) NOT NULL DEFAULT 0 AFTER `weight`");
        }
        if (!$has_col($conn, $tbl, 'from_dept_id')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `from_dept_id` int(11) DEFAULT NULL AFTER `qty`");
        }
        if (!$has_col($conn, $tbl, 'to_dept_id')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `to_dept_id` int(11) DEFAULT NULL AFTER `from_dept_id`");
        }
        if (!$has_col($conn, $tbl, 'from_user_id')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `from_user_id` int(11) DEFAULT NULL AFTER `to_dept_id`");
        }
        if (!$has_col($conn, $tbl, 'to_user_id')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `to_user_id` int(11) DEFAULT NULL AFTER `from_user_id`");
        }
        if (!$has_col($conn, $tbl, 'added_by_dept_id')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `added_by_dept_id` int(11) DEFAULT NULL AFTER `to_user_id`");
        }
        if (!$has_col($conn, $tbl, 'added_by_user_id')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `added_by_user_id` int(11) DEFAULT NULL AFTER `added_by_dept_id`");
        }
        if (!$has_col($conn, $tbl, 'weight_out')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `weight_out` decimal(14,4) NOT NULL DEFAULT 0 AFTER `weight`");
        }
        if (!$has_col($conn, $tbl, 'qty_out')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD COLUMN `qty_out` decimal(14,4) NOT NULL DEFAULT 0 AFTER `weight_out`");
        }
        if ($has_key($conn, $tbl, 'uniq_issue')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` DROP INDEX `uniq_issue`");
        }
        if (!$has_key($conn, $tbl, 'idx_jwo_item_stock')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD INDEX `idx_jwo_item_stock` (`jobwork_order_id`,`jobwork_order_item_id`,`stock_id`)");
        }
        if ($has_key($conn, $tbl, 'uniq_issue_stock')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` DROP INDEX `uniq_issue_stock`");
        }
        if (!$has_key($conn, $tbl, 'uniq_jwq_stock')) {
            $safe_sql($conn, "ALTER TABLE `$tbl` ADD UNIQUE KEY `uniq_jwq_stock` (`jobwork_order_id`,`stock_id`)");
        }

        // Backward compatibility: move legacy rows from old table once.
        $old_chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_diamond_stock'");
        if ($old_chk && mysqli_num_rows($old_chk) > 0) {
            @mysqli_query(
                $conn,
                "INSERT IGNORE INTO `$tbl` (`jobwork_order_id`,`jobwork_order_item_id`,`stock_id`,`barcode`,`product_name`,`weight`,`qty`,`weight_out`,`qty_out`,`created_at`)
                 SELECT `jobwork_order_id`, COALESCE(`jobwork_order_item_id`,0), `stock_id`, `barcode`, `product_name`,
                        COALESCE(`weight_out`,0), COALESCE(`qty_out`,0), COALESCE(`weight_out`,0), COALESCE(`qty_out`,0), `created_at`
                 FROM `tbl_jobwork_queue_diamond_stock`"
            );
            mysqli_free_result($old_chk);
        } elseif ($old_chk) {
            mysqli_free_result($old_chk);
        }
    }
}

if (!function_exists('mp_jwq_clear_last_db_error')) {
    function mp_jwq_clear_last_db_error(): void
    {
        $GLOBALS['_mp_jwq_last_db_error'] = '';
    }
}
if (!function_exists('mp_jwq_set_last_db_error')) {
    function mp_jwq_set_last_db_error(mysqli $conn): void
    {
        $GLOBALS['_mp_jwq_last_db_error'] = trim((string) mysqli_error($conn));
    }
}
if (!function_exists('mp_jwq_get_last_db_error')) {
    function mp_jwq_get_last_db_error(): string
    {
        return trim((string) ($GLOBALS['_mp_jwq_last_db_error'] ?? ''));
    }
}

if (!function_exists('mp_jwq_apply_diamond_stock_consumption')) {
    /**
     * @param array<int, array<string, mixed>>|null $rows
     */
    function mp_jwq_apply_diamond_stock_consumption(mysqli $conn, int $jobwork_order_id, $rows, bool &$tx_ok, string &$tx_err, int $from_dept_id = 0, int $to_dept_id = 0, int $from_user_id = 0, int $to_user_id = 0, array &$stats = []): void
    {
        if (!$tx_ok || !is_array($rows) || empty($rows)) {
            return;
        }
        mp_jwq_clear_last_db_error();
        mp_jwq_ensure_diamond_issue_table($conn);
        $tbl = mp_jwq_diamond_issue_table_name();
        $first_item_id = 0;
        if (function_exists('getRecord')) {
            $fi = getRecord('SELECT id FROM tbl_jobwork_order_items WHERE jobwork_order_id = ' . (int) $jobwork_order_id . ' ORDER BY id ASC LIMIT 1');
            if ($fi && isset($fi['id'])) {
                $first_item_id = (int) $fi['id'];
            }
        }
        $stats['saved_rows'] = isset($stats['saved_rows']) ? (int) $stats['saved_rows'] : 0;
        $stats['excluded_stock_ids'] = isset($stats['excluded_stock_ids']) && is_array($stats['excluded_stock_ids']) ? $stats['excluded_stock_ids'] : [];

        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $req_bc = trim((string) ($r['barcode'] ?? ''));
            if ($req_bc === '') {
                continue;
            }
            $sid = (int) ($r['stock_id'] ?? 0);
            if ($sid < 1) {
                $bc_esc_try = mysqli_real_escape_string($conn, $req_bc);
                $st_by_bc = function_exists('getRecord')
                    ? getRecord(
                        "SELECT * FROM tbl_stock WHERE status = 1"
                        . " AND (stock_type IS NULL OR LOWER(TRIM(stock_type)) <> 'outward')"
                        . " AND barcode = '" . $bc_esc_try . "'"
                        . ' AND COALESCE(current_weight,0) > 0'
                        . ' ORDER BY current_weight DESC, id DESC LIMIT 1'
                    )
                    : null;
                if ($st_by_bc && isset($st_by_bc['id'])) {
                    $sid = (int) $st_by_bc['id'];
                }
            }
            if ($sid < 1) {
                continue;
            }
            $item_id = isset($r['jobwork_order_item_id']) ? (int) $r['jobwork_order_item_id'] : 0;
            if ($item_id < 1) {
                $item_id = $first_item_id > 0 ? $first_item_id : 0;
            }
            $w_out = isset($r['weight']) ? (float) $r['weight'] : 0.0;
            $q_out = isset($r['qty']) ? (float) $r['qty'] : 0.0;
            if ($w_out <= 0.0000001) {
                continue;
            }

            $st = function_exists('getRecord')
                ? getRecord('SELECT * FROM tbl_stock WHERE id = ' . $sid . ' AND status = 1 LIMIT 1')
                : null;
            if (!$st) {
                continue;
            }
            $stbc = trim((string) ($st['barcode'] ?? ''));
            if ($req_bc !== '' && ($stbc === '' || strcasecmp($stbc, $req_bc) !== 0)) {
                $bc_esc_try = mysqli_real_escape_string($conn, $req_bc);
                $st2 = function_exists('getRecord')
                    ? getRecord(
                        "SELECT * FROM tbl_stock WHERE status = 1"
                        . " AND (stock_type IS NULL OR LOWER(TRIM(stock_type)) <> 'outward')"
                        . " AND barcode = '" . $bc_esc_try . "'"
                        . ' AND COALESCE(current_weight,0) > 0'
                        . ' ORDER BY current_weight DESC, id DESC LIMIT 1'
                    )
                    : null;
                if ($st2 && isset($st2['id'])) {
                    $st = $st2;
                    $sid = (int) $st2['id'];
                } else {
                    continue;
                }
            }

            $avail = (float) ($st['current_weight'] ?? 0);
            if ($avail <= 0.0000001) {
                $bc_try = trim((string) ($r['barcode'] ?? $st['barcode'] ?? ''));
                if ($bc_try !== '') {
                    $bc_esc_try = mysqli_real_escape_string($conn, $bc_try);
                    $fallback = function_exists('getRecord')
                        ? getRecord(
                            "SELECT * FROM tbl_stock WHERE status = 1"
                            . " AND (stock_type IS NULL OR LOWER(TRIM(stock_type)) <> 'outward')"
                            . " AND barcode = '" . $bc_esc_try . "'"
                            . ' AND COALESCE(current_weight,0) > 0'
                            . ' ORDER BY current_weight DESC, id DESC LIMIT 1'
                        )
                        : null;
                    if ($fallback) {
                        $st = $fallback;
                        $avail = (float) ($st['current_weight'] ?? 0);
                    }
                }
            }
            $take = $w_out;
            if ($take > $avail + 0.0001) {
                $take = $avail;
            }
            if ($take <= 0.0000001) {
                continue;
            }

            $src_id = (int) $st['id'];
            if ($src_id < 1) {
                continue;
            }
            $already = function_exists('getRecord')
                ? getRecord('SELECT id FROM `' . $tbl . '` WHERE jobwork_order_id = ' . (int) $jobwork_order_id . ' AND stock_id = ' . $src_id . ' LIMIT 1')
                : null;
            if ($already && isset($already['id'])) {
                continue;
            }
            $balance_wt = max(0.0, $avail - $take);
            $prev_cq = (float) ($st['current_qty'] ?? 0);
            $sold_q = $q_out > 0.0000001 ? min($q_out, $prev_cq) : ($avail > 0.0000001 ? $prev_cq * ($take / $avail) : 0.0);
            $new_cq = max(0.0, $prev_cq - $sold_q);
            $rate = (float) ($st['rate'] ?? 0);
            $new_val = round($rate * $balance_wt, 2);

            $bal_wt_sql = round($balance_wt, 4);
            $new_cq_sql = round($new_cq, 4);
            $upd = 'UPDATE tbl_stock SET current_weight = ' . $bal_wt_sql . ', current_qty = ' . $new_cq_sql
                . ', final_weight = ' . $bal_wt_sql . ', value = ' . $new_val . ' WHERE id = ' . $src_id . ' LIMIT 1';
            if (!@mysqli_query($conn, $upd)) {
                $tx_ok = false;
                mp_jwq_set_last_db_error($conn);
                $tx_err = 'Could not update stock for diamond consumption. DB: ' . mp_jwq_get_last_db_error();

                return;
            }

            $pid = (int) ($st['product_id'] ?? 0);
            $pcid = isset($st['product_characteristic_id']) ? (int) $st['product_characteristic_id'] : 0;
            $branch_id = (int) ($st['branch_id'] ?? 0);
            $metal_id = (int) ($st['metal_id'] ?? 0);
            $purity = (float) ($st['opening_purity'] ?? 0);
            $rate_sql = (float) ($st['rate'] ?? 0);
            $out_val = round($rate_sql * $take, 2);
            $barcode_sql = 'NULL';
            $bc_src = trim((string)($st['barcode'] ?? ''));
            if ($bc_src !== '') {
                $barcode_sql = "'" . mysqli_real_escape_string($conn, $bc_src) . "'";
            }
            $pcid_sql = $pcid > 0 ? (string) $pcid : 'NULL';
            $out_sql = "INSERT INTO tbl_stock (product_id, product_characteristic_id, barcode, branch_id, metal_id, opening_weight, opening_purity, opening_qty, final_weight, rate, value, current_weight, current_qty, stock_type, transaction_date, created_at) VALUES ("
                . $pid . ', ' . $pcid_sql . ', ' . $barcode_sql . ', ' . $branch_id . ', ' . $metal_id . ', '
                . round($take, 4) . ', ' . round($purity, 4) . ', ' . round($sold_q, 4) . ', ' . round($take, 4) . ', '
                . $rate_sql . ', ' . $out_val . ', ' . round($take, 4) . ', ' . round($sold_q, 4) . ", 'outward', CURDATE(), NOW())";
            if (!@mysqli_query($conn, $out_sql)) {
                $tx_ok = false;
                mp_jwq_set_last_db_error($conn);
                $tx_err = 'Could not insert outward stock for diamond consumption. DB: ' . mp_jwq_get_last_db_error();

                return;
            }

            $bc_esc = mysqli_real_escape_string($conn, $req_bc);
            $pn_esc = mysqli_real_escape_string($conn, (string) ($r['product_name'] ?? ''));
            $cat_esc = mysqli_real_escape_string($conn, (string) ($r['diamond_category'] ?? 'Diamond'));
            $w_log = round($take, 4);
            $q_log = round($sold_q, 4);
            $item_sql = $item_id > 0 ? (string) $item_id : '0';
            $fd_sql = $from_dept_id > 0 ? (string) $from_dept_id : 'NULL';
            $td_sql = $to_dept_id > 0 ? (string) $to_dept_id : 'NULL';
            $fu_sql = $from_user_id > 0 ? (string) $from_user_id : 'NULL';
            $tu_sql = $to_user_id > 0 ? (string) $to_user_id : 'NULL';
            $add_d_dept = (int) ($r['added_by_dept_id'] ?? 0);
            $add_d_user = (int) ($r['added_by_user_id'] ?? 0);
            $add_dd_sql = $add_d_dept > 0 ? (string) $add_d_dept : 'NULL';
            $add_du_sql = $add_d_user > 0 ? (string) $add_d_user : 'NULL';

            $ins = "INSERT INTO `$tbl` (jobwork_order_id, jobwork_order_item_id, stock_id, barcode, product_name, diamond_category, weight, qty, weight_out, qty_out, from_dept_id, to_dept_id, from_user_id, to_user_id, added_by_dept_id, added_by_user_id, created_at) VALUES ("
                . (int) $jobwork_order_id . ', ' . $item_sql . ', ' . $src_id . ", '" . $bc_esc . "', '" . $pn_esc . "', '" . $cat_esc . "', "
                . $w_log . ', ' . $q_log . ', ' . $w_log . ', ' . $q_log . ', ' . $fd_sql . ', ' . $td_sql . ', ' . $fu_sql . ', ' . $tu_sql . ', ' . $add_dd_sql . ', ' . $add_du_sql . ', NOW())';
            if (!@mysqli_query($conn, $ins)) {
                $tx_ok = false;
                mp_jwq_set_last_db_error($conn);
                $tx_err = 'Could not log diamond stock issue. DB: ' . mp_jwq_get_last_db_error();

                return;
            }
            $stats['saved_rows'] = (int) $stats['saved_rows'] + 1;
            $stats['excluded_stock_ids'][] = $src_id;
        }
        $stats['excluded_stock_ids'] = array_values(array_unique(array_map('intval', $stats['excluded_stock_ids'])));
    }
}

if (!function_exists('mp_jwq_upsert_diamond_issue_rows_from_payload')) {
    /**
     * Ensure tbl_jobwork_queue_diamond_stock_issue has one row per payload stock_id for this job work order.
     * Uses INSERT … ON DUPLICATE KEY UPDATE (uniq on jobwork_order_id + stock_id) so pending / modal rows persist
     * even when mp_jwq_apply_diamond_stock_consumption skips insert (e.g. stock already consumed, $already, or take=0).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    function mp_jwq_upsert_diamond_issue_rows_from_payload(
        mysqli $conn,
        int $jobwork_order_id,
        array $rows,
        int $first_item_id_fallback,
        int $from_dept_id,
        int $to_dept_id,
        int $from_user_id,
        int $to_user_id,
        bool &$tx_ok,
        string &$tx_err,
        string &$last_insert_error
    ): int {
        if (!$tx_ok || empty($rows)) {
            return 0;
        }
        mp_jwq_ensure_diamond_issue_table($conn);
        $tbl = mp_jwq_diamond_issue_table_name();
        $jwo = (int) $jobwork_order_id;
        if ($jwo < 1) {
            return 0;
        }
        $last_insert_error = '';
        $n = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $stock_id = (int) ($row['stock_id'] ?? 0);
            $barcode = trim((string) ($row['barcode'] ?? ''));
            $product_name = trim((string) ($row['product_name'] ?? ''));
            $weight = (float) ($row['weight'] ?? 0);
            $qty = (float) ($row['qty'] ?? 0);
            $jobwork_order_item_id = (int) ($row['jobwork_order_item_id'] ?? 0);
            if ($jobwork_order_item_id < 1 && $first_item_id_fallback > 0) {
                $jobwork_order_item_id = $first_item_id_fallback;
            }
            $diamond_category = trim((string) ($row['diamond_category'] ?? 'Diamond'));
            if ($diamond_category === '') {
                $diamond_category = 'Diamond';
            }
            if ($stock_id < 1 || $barcode === '') {
                continue;
            }
            $w_log = round(max(0.0, $weight), 4);
            $q_log = round($qty, 4);
            $bc_esc = mysqli_real_escape_string($conn, $barcode);
            $pn_esc = mysqli_real_escape_string($conn, $product_name);
            $cat_esc = mysqli_real_escape_string($conn, $diamond_category);
            $item_sql = $jobwork_order_item_id > 0 ? (string) $jobwork_order_item_id : '0';
            $fd_sql = $from_dept_id > 0 ? (string) $from_dept_id : 'NULL';
            $td_sql = $to_dept_id > 0 ? (string) $to_dept_id : 'NULL';
            $fu_sql = $from_user_id > 0 ? (string) $from_user_id : 'NULL';
            $tu_sql = $to_user_id > 0 ? (string) $to_user_id : 'NULL';
            $add_d_dept = (int) ($row['added_by_dept_id'] ?? 0);
            $add_d_user = (int) ($row['added_by_user_id'] ?? 0);
            $add_dd_sql = $add_d_dept > 0 ? (string) $add_d_dept : 'NULL';
            $add_du_sql = $add_d_user > 0 ? (string) $add_d_user : 'NULL';

            $sql = 'INSERT INTO `' . $tbl . '` (jobwork_order_id, jobwork_order_item_id, stock_id, barcode, product_name, diamond_category, weight, qty, weight_out, qty_out, from_dept_id, to_dept_id, from_user_id, to_user_id, added_by_dept_id, added_by_user_id, created_at) VALUES ('
                . $jwo . ', ' . $item_sql . ', ' . $stock_id . ", '" . $bc_esc . "', '" . $pn_esc . "', '" . $cat_esc . "', "
                . $w_log . ', ' . $q_log . ', ' . $w_log . ', ' . $q_log . ', ' . $fd_sql . ', ' . $td_sql . ', ' . $fu_sql . ', ' . $tu_sql . ', ' . $add_dd_sql . ', ' . $add_du_sql . ', NOW())'
                . ' ON DUPLICATE KEY UPDATE '
                . 'jobwork_order_item_id = VALUES(jobwork_order_item_id),'
                . "barcode = VALUES(barcode),"
                . 'product_name = VALUES(product_name),'
                . 'diamond_category = VALUES(diamond_category),'
                . 'weight = VALUES(weight),'
                . 'qty = VALUES(qty),'
                . 'weight_out = VALUES(weight_out),'
                . 'qty_out = VALUES(qty_out),'
                . 'from_dept_id = VALUES(from_dept_id),'
                . 'to_dept_id = VALUES(to_dept_id),'
                . 'from_user_id = VALUES(from_user_id),'
                . 'to_user_id = VALUES(to_user_id),'
                . 'added_by_dept_id = COALESCE(NULLIF(VALUES(added_by_dept_id), 0), added_by_dept_id),'
                . 'added_by_user_id = COALESCE(NULLIF(VALUES(added_by_user_id), 0), added_by_user_id)';
            if (!@mysqli_query($conn, $sql)) {
                $tx_ok = false;
                mp_jwq_set_last_db_error($conn);
                $last_insert_error = 'Could not upsert diamond issue row (stock_id=' . $stock_id . ', barcode=' . $barcode . '): ' . mp_jwq_get_last_db_error();
                $tx_err = $last_insert_error;

                return $n;
            }
            ++$n;
        }

        return $n;
    }
}

if (!function_exists('mp_jwq_recalculate_line_diamond_weights')) {
    function mp_jwq_recalculate_line_diamond_weights(mysqli $conn, int $jobwork_order_id): array
    {
        $tbl = mp_jwq_diamond_issue_table_name();
        $jwo = (int) $jobwork_order_id;
        if ($jwo < 1) {
            return [];
        }
        $icq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_jobwork_order_items');
        if (!$icq) {
            return [];
        }
        $ji_cols = [];
        while ($r = mysqli_fetch_assoc($icq)) {
            $ji_cols[(string) ($r['Field'] ?? '')] = true;
        }
        mysqli_free_result($icq);
        $col = !empty($ji_cols['diamond_weight']) ? 'diamond_weight' : (!empty($ji_cols['diamond_wt']) ? 'diamond_wt' : '');
        if ($col === '') {
            return [];
        }
        $sql = "UPDATE tbl_jobwork_order_items ji
                SET ji.`$col` = (
                    SELECT COALESCE(SUM(ds.weight),0)
                    FROM `$tbl` ds
                    WHERE ds.jobwork_order_id = ji.jobwork_order_id
                      AND ds.jobwork_order_item_id = ji.id
                      AND ds.stock_id > 0
                      AND TRIM(IFNULL(ds.barcode,'')) <> ''
                )
                WHERE ji.jobwork_order_id = " . $jwo;
        @mysqli_query($conn, $sql);
        $rows = function_exists('getList')
            ? getList("SELECT id AS item_id, COALESCE(`$col`,0) AS diamond_weight FROM tbl_jobwork_order_items WHERE jobwork_order_id = $jwo ORDER BY id ASC")
            : [];
        return is_array($rows) ? $rows : [];
    }
}

/**
 * When no rows exist in tbl_jobwork_queue_diamond_stock_issue, build display rows from line diamond_weight / diamond_wt.
 *
 * @return list<array<string, mixed>>
 */
if (!function_exists('mp_jwq_fallback_line_diamond_rows_from_jobwork')) {
    function mp_jwq_fallback_line_diamond_rows_from_jobwork(mysqli $conn, int $jobwork_order_id): array
    {
        $jwo = (int) $jobwork_order_id;
        if ($jwo < 1) {
            return [];
        }
        $icq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_jobwork_order_items');
        if (!$icq) {
            return [];
        }
        $ji_cols = [];
        while ($r = mysqli_fetch_assoc($icq)) {
            $ji_cols[(string) ($r['Field'] ?? '')] = true;
        }
        mysqli_free_result($icq);
        $dw = !empty($ji_cols['diamond_weight']) ? 'diamond_weight' : (!empty($ji_cols['diamond_wt']) ? 'diamond_wt' : '');
        if ($dw === '') {
            return [];
        }
        $tagCol = !empty($ji_cols['tag_no']) ? 'tag_no' : (!empty($ji_cols['barcode']) ? 'barcode' : '');
        $descCol = !empty($ji_cols['product_name']) ? 'product_name' : (!empty($ji_cols['description']) ? 'description' : '');
        $sql = 'SELECT id, `' . $dw . '` AS dw';
        if ($tagCol !== '') {
            $sql .= ', `' . $tagCol . '` AS tag_hint';
        }
        if ($descCol !== '') {
            $sql .= ', `' . $descCol . '` AS desc_hint';
        }
        $sql .= ' FROM tbl_jobwork_order_items WHERE jobwork_order_id = ' . $jwo
            . ' AND COALESCE(`' . $dw . '`,0) > 0.0000001 ORDER BY id ASC';
        $rows = function_exists('getList') ? getList($sql) : [];
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $w = (float) ($row['dw'] ?? 0);
            if ($w <= 0.0000001) {
                continue;
            }
            $tag = trim((string) ($row['tag_hint'] ?? ''));
            $desc = trim((string) ($row['desc_hint'] ?? ''));
            $label = trim($tag . ' ' . $desc);
            if ($label === '') {
                $label = 'Item #' . (int) ($row['id'] ?? 0);
            }
            $out[] = [
                'id' => 0,
                'jobwork_order_id' => $jwo,
                'jobwork_order_item_id' => (int) ($row['id'] ?? 0),
                'stock_id' => 0,
                'barcode' => '',
                'product_name' => 'Diamond on line (no barcoded issues saved yet): ' . $label,
                'diamond_category' => 'Diamond',
                'weight' => $w,
                'qty' => 0.0,
                'weight_out' => $w,
                'qty_out' => 0.0,
                'from_dept_name' => '',
                'to_dept_name' => '',
                'from_user_name' => '',
                'to_user_name' => '',
                'created_at' => '',
                '_line_fallback' => true,
            ];
        }

        return $out;
    }
}

if (!function_exists('mp_jwq_list_diamond_stock_issues')) {
    /**
     * Prefer saved issue rows (one row per issued stock line). If none, optional line-total fallback from job items.
     *
     * @return list<array<string, mixed>>
     */
    function mp_jwq_list_diamond_stock_issues(mysqli $conn, int $jobwork_order_id, int $item_id = 0): array
    {
        $jwo = (int) $jobwork_order_id;
        if ($jwo < 1) {
            return [];
        }
        mp_jwq_ensure_diamond_issue_table($conn);
        $tbl = mp_jwq_diamond_issue_table_name();
        $chk = @mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $tbl) . "'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            if ($chk) {
                mysqli_free_result($chk);
            }

            return [];
        }
        mysqli_free_result($chk);

        $selectCols = 'SELECT ds.id, ds.jobwork_order_id, ds.jobwork_order_item_id, ds.stock_id,'
            . ' TRIM(IFNULL(ds.barcode, \'\')) AS barcode,'
            . ' ds.product_name, ds.diamond_category,'
            . ' ds.weight, ds.qty, ds.weight_out, ds.qty_out, ds.created_at,'
            . ' COALESCE(ds.added_by_dept_id,0) AS added_by_dept_id, COALESCE(ds.added_by_user_id,0) AS added_by_user_id,'
            . " IFNULL(fd.dept_name,'') AS from_dept_name, IFNULL(td.dept_name,'') AS to_dept_name,"
            . " IFNULL(fu.name,'') AS from_user_name, IFNULL(tu.name,'') AS to_user_name,"
            . " IFNULL(ad_dep.dept_name,'') AS added_by_dept_name, IFNULL(ad_usr.name,'') AS added_by_user_name"
            . " FROM `$tbl` ds"
            . ' LEFT JOIN tbl_departments fd ON fd.id = ds.from_dept_id'
            . ' LEFT JOIN tbl_departments td ON td.id = ds.to_dept_id'
            . ' LEFT JOIN tbl_customers fu ON fu.id = ds.from_user_id'
            . ' LEFT JOIN tbl_customers tu ON tu.id = ds.to_user_id'
            . ' LEFT JOIN tbl_departments ad_dep ON ad_dep.id = ds.added_by_dept_id'
            . ' LEFT JOIN tbl_customers ad_usr ON ad_usr.id = ds.added_by_user_id';

        $whereBase = ' WHERE ds.jobwork_order_id = ' . $jwo;
        if ($item_id > 0) {
            $whereBase .= ' AND ds.jobwork_order_item_id = ' . (int) $item_id;
        }
        $orderBy = ' ORDER BY ds.id ASC';

        $runList = static function (mysqli $conn, string $sql): array {
            $list = [];
            if (function_exists('getList')) {
                $tmp = getList($sql);
                if (is_array($tmp)) {
                    $list = $tmp;
                }
            }
            if (count($list) === 0) {
                $res = @mysqli_query($conn, $sql);
                if ($res) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $list[] = $row;
                    }
                    mysqli_free_result($res);
                }
            }

            return $list;
        };

        $sqlReal = $selectCols . $whereBase
            . " AND ds.stock_id > 0 AND TRIM(IFNULL(ds.barcode,'')) <> ''"
            . $orderBy;
        $realList = $runList($conn, $sqlReal);
        if (count($realList) > 0) {
            return $realList;
        }

        return mp_jwq_fallback_line_diamond_rows_from_jobwork($conn, $jwo);
    }
}

if (!function_exists('mp_jwq_consumed_stock_ids')) {
    function mp_jwq_consumed_stock_ids(mysqli $conn, int $jobwork_order_id = 0): array
    {
        mp_jwq_ensure_diamond_issue_table($conn);
        $tbl = mp_jwq_diamond_issue_table_name();
        $sql = "SELECT DISTINCT stock_id FROM `$tbl` WHERE stock_id > 0";
        if ($jobwork_order_id > 0) {
            $sql .= ' AND jobwork_order_id = ' . (int) $jobwork_order_id;
        }
        $rows = function_exists('getList') ? getList($sql) : [];
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $sid = (int) ($r['stock_id'] ?? 0);
            if ($sid > 0) {
                $out[] = $sid;
            }
        }
        return array_values(array_unique($out));
    }
}
