<?php

/**
 * Shared Metal Exchange → tbl_stock (new barcode) + stock history audit.
 * Used by sale order, sale invoice, purchase vouchers, etc.
 */

if (!function_exists('auragold_payment_merge_stored_details')) {
    /**
     * Merge JSON from payment_details column into the payment row (edit/save round-trip).
     *
     * @param array<string, mixed> $payment
     *
     * @return array<string, mixed>
     */
    function auragold_payment_merge_stored_details(array $payment): array
    {
        $pd_raw = $payment['payment_details'] ?? '';
        if (is_string($pd_raw) && $pd_raw !== '') {
            $j = json_decode($pd_raw, true);
            if (is_array($j)) {
                return array_merge($payment, $j);
            }
        }

        return $payment;
    }
}

if (!function_exists('auragold_metal_exchange_resolve')) {
    /**
     * Detect metal-exchange payment line and resolved gross/pure weights.
     *
     * @return array{is_me: bool, gross: float, pure: float, metal_id: int, qty: float, is_silver: bool}
     */
    function auragold_metal_exchange_resolve($conn, array $payment): array
    {
        $payment = auragold_payment_merge_stored_details($payment);
        $dep = strtolower(trim((string) ($payment['deposit_into'] ?? '')));
        $pt = strtolower(trim((string) ($payment['payment_type'] ?? '')));
        $is_me = ($dep === 'metal exchange')
            || (strpos($pt, 'm. exch') !== false)
            || (strpos($pt, 'metal') !== false && strpos($pt, 'exch') !== false)
            || ($pt === 'metal_exchange')
            || (strpos($pt, 'metal-exchange') !== false);
        $empty = ['is_me' => false, 'gross' => 0.0, 'pure' => 0.0, 'metal_id' => 0, 'qty' => 1.0, 'is_silver' => false];
        if (!$is_me) {
            return $empty;
        }
        $qty = (float) ($payment['quantity'] ?? 1);
        if ($qty < 1e-8) {
            $qty = 1.0;
        }
        $gross = (float) ($payment['metal_exchange_gross_wt'] ?? 0) * $qty;
        if ($gross <= 1e-8) {
            $gross = (float) ($payment['gross_weight'] ?? $payment['gross_wt'] ?? $payment['net_weight'] ?? $payment['weight'] ?? 0) * $qty;
        }
        if ($gross <= 1e-8 && $qty > 0) {
            $gross = $qty;
        }
        $pure = (float) ($payment['metal_exchange_purity_wt'] ?? 0) * $qty;
        if ($pure <= 1e-8) {
            $pure = (float) ($payment['purity_weight'] ?? $payment['pure_wt'] ?? $payment['purity_wt'] ?? 0) * $qty;
        }
        $pur_num = (float) ($payment['purity_carat'] ?? $payment['purity'] ?? 0);
        if ($pure <= 1e-8 && $gross > 1e-8 && $pur_num > 0) {
            if ($pur_num <= 1) {
                $pure = $gross * $pur_num;
            } elseif ($pur_num <= 100) {
                $pure = $gross * ($pur_num / 100);
            } else {
                $pure = $gross * ($pur_num / 1000);
            }
        }
        if ($pure <= 1e-8 && $gross > 1e-8) {
            $pure = $gross;
        }
        $mid = (int) ($payment['metal_exchange_metal_id'] ?? $payment['metal_id'] ?? 0);
        $nm = '';
        if ($mid > 0) {
            $mr = getRecord("SELECT LOWER(TRIM(COALESCE(display_name, system_name, ''))) AS n FROM tbl_metal WHERE id = $mid LIMIT 1");
            $nm = strtolower(trim((string) ($mr['n'] ?? '')));
        }
        $is_silver = strpos($nm, 'silver') !== false;

        return [
            'is_me' => true,
            'gross' => $gross,
            'pure' => $pure,
            'metal_id' => $mid,
            'qty' => $qty,
            'is_silver' => $is_silver,
        ];
    }
}

if (!function_exists('auragold_prepare_tbl_stock_reference_columns')) {
    /** Ensure tbl_stock.reference_id / reference_type exist (for tagging metal-exchange inward rows). */
    function auragold_prepare_tbl_stock_reference_columns($conn): bool
    {
        $t_stock = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_stock'");
        if (!$t_stock || mysqli_num_rows($t_stock) === 0) {
            if ($t_stock) {
                mysqli_free_result($t_stock);
            }

            return false;
        }
        mysqli_free_result($t_stock);

        $tbl_stock_has_reference = false;
        $__stk_ref = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock WHERE Field IN ('reference_id','reference_type')");
        if ($__stk_ref && mysqli_num_rows($__stk_ref) >= 2) {
            $tbl_stock_has_reference = true;
        }
        if ($__stk_ref) {
            mysqli_free_result($__stk_ref);
        }
        if (!$tbl_stock_has_reference) {
            @mysqli_query($conn, "ALTER TABLE `tbl_stock` ADD COLUMN `reference_id` INT NULL DEFAULT NULL AFTER `transaction_date`");
            @mysqli_query($conn, "ALTER TABLE `tbl_stock` ADD COLUMN `reference_type` VARCHAR(50) NULL DEFAULT NULL AFTER `reference_id`");
            $__stk_ref2 = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock WHERE Field IN ('reference_id','reference_type')");
            if ($__stk_ref2 && mysqli_num_rows($__stk_ref2) >= 2) {
                $tbl_stock_has_reference = true;
            }
            if ($__stk_ref2) {
                mysqli_free_result($__stk_ref2);
            }
        }

        return $tbl_stock_has_reference;
    }
}

if (!function_exists('auragold_metal_exchange_reference_types_sql_list_safe')) {
    /** Same list without relying on $conn global — escape via mysqli when provided */
    function auragold_metal_exchange_reference_types_sql_list_safe(?mysqli $conn): string
    {
        $types = [
            'sale_order_metal_exchange',
            'sale_invoice_metal_exchange',
            'sale_quotation_metal_exchange',
            'sale_return_metal_exchange',
            'purchase_invoice_metal_exchange',
            'purchase_quotation_metal_exchange',
            'purchase_return_metal_exchange',
            'payment_voucher_metal_exchange',
            'receipt_voucher_metal_exchange',
            'advance_payment_metal_exchange',
            'old_jewelry_scrap_invoice_metal_exchange',
            'material_issue_metal_exchange',
            'material_receive_metal_exchange',
            'jobwork_order_metal_exchange',
            'jobwork_invoice_metal_exchange',
            'old_jewellery_scrap_stock_in_metal_exchange',
            'consignment_in_metal_exchange',
            'consignment_out_metal_exchange',
            'pos_sale_invoice_metal_exchange',
        ];
        $parts = [];
        foreach ($types as $t) {
            $parts[] = $conn ? ("'" . mysqli_real_escape_string($conn, $t) . "'") : ("'" . str_replace("'", "''", $t) . "'");
        }

        return implode(',', $parts);
    }
}

if (!function_exists('auragold_payment_is_metal_exchange_inward')) {
    function auragold_payment_is_metal_exchange_inward($conn, array $payment): bool
    {
        $p = auragold_payment_merge_stored_details($payment);
        $r = auragold_metal_exchange_resolve($conn, $p);
        if (!$r['is_me'] || $r['gross'] <= 1e-8) {
            return false;
        }
        $pcid = (int) ($p['metal_exchange_product_id'] ?? $p['product_id'] ?? 0);
        $mid = (int) ($p['metal_exchange_metal_id'] ?? $p['metal_id'] ?? 0);

        return $pcid > 0 && $mid > 0;
    }
}

if (!function_exists('auragold_should_persist_payment_row_with_metal_exchange')) {
    /** Persist payment row when amount > 0, valid metal exchange inward, or any explicit UI payment line (incl. zero amount). */
    function auragold_should_persist_payment_row_with_metal_exchange($conn, array $payment): bool
    {
        $p = auragold_payment_merge_stored_details($payment);
        $amt = (float) ($p['amount'] ?? 0);
        if ($amt > 0.00001) {
            return true;
        }
        if (auragold_payment_is_metal_exchange_inward($conn, $p)) {
            return true;
        }

        $type = strtolower(trim((string) ($p['type'] ?? '')));
        $pt = strtolower(trim((string) ($p['payment_type'] ?? '')));
        $dep = strtolower(trim((string) ($p['deposit_into'] ?? '')));
        if ($type !== '' || $pt !== '' || $dep !== '') {
            return true;
        }

        return false;
    }
}

if (!function_exists('auragold_validate_metal_exchange_for_stock')) {
    function auragold_validate_metal_exchange_for_stock($conn, array $payment): void
    {
        if (!auragold_payment_is_metal_exchange_inward($conn, $payment)) {
            return;
        }
        $p = auragold_payment_merge_stored_details($payment);
        $mid = (int) ($p['metal_exchange_metal_id'] ?? $p['metal_id'] ?? 0);
        $pcid = (int) ($p['metal_exchange_product_id'] ?? $p['product_id'] ?? 0);
        $row = getRecord(
            'SELECT pc.id FROM tbl_product_characteristics pc '
            . 'INNER JOIN tbl_products p ON p.id = pc.product_id '
            . "WHERE pc.id = $pcid AND pc.metal_id = $mid AND p.status = 1 AND pc.status = 1 LIMIT 1"
        );
        if (!$row) {
            throw new Exception('Metal exchange: choose a valid product for the selected metal (from the search list).');
        }
    }
}

if (!function_exists('auragold_metal_exchange_sj_prefix_and_audit_src')) {
    /**
     * @return array{0: string, 1: string}|null [sj_invoice_prefix, audit_src comment tag]
     */
    function auragold_metal_exchange_sj_prefix_and_audit_src(string $reference_type): ?array
    {
        static $map = [
            'sale_order_metal_exchange' => ['SO-ME-', 'so_me'],
            'sale_invoice_metal_exchange' => ['SI-ME-', 'si_me'],
            'sale_quotation_metal_exchange' => ['SQ-ME-', 'sq_me'],
            'sale_return_metal_exchange' => ['SR-ME-', 'sr_me'],
            'pos_sale_invoice_metal_exchange' => ['POSI-ME-', 'posi_me'],
            'purchase_invoice_metal_exchange' => ['PI-ME-', 'pi_me'],
            'purchase_quotation_metal_exchange' => ['PQ-ME-', 'pq_me'],
            'purchase_return_metal_exchange' => ['PR-ME-', 'pr_me'],
            'payment_voucher_metal_exchange' => ['PV-ME-', 'pv_me'],
            'receipt_voucher_metal_exchange' => ['RV-ME-', 'rv_me'],
            'advance_payment_metal_exchange' => ['AP-ME-', 'ap_me'],
            'old_jewelry_scrap_invoice_metal_exchange' => ['OJSI-ME-', 'ojsi_me'],
            'old_jewellery_scrap_stock_in_metal_exchange' => ['OJSTK-ME-', 'ojstk_me'],
            'material_issue_metal_exchange' => ['MI-ME-', 'mi_me'],
            'material_receive_metal_exchange' => ['MR-ME-', 'mr_me'],
            'jobwork_order_metal_exchange' => ['JWO-ME-', 'jwo_me'],
            'jobwork_invoice_metal_exchange' => ['JWI-ME-', 'jwi_me'],
            'consignment_in_metal_exchange' => ['CIN-ME-', 'cin_me'],
            'consignment_out_metal_exchange' => ['COU-ME-', 'cou_me'],
        ];

        return $map[$reference_type] ?? null;
    }
}

if (!function_exists('auragold_metal_exchange_delete_journal_for_reference')) {
    /** Remove prior metal-exchange stock journal rows before re-posting (sj_invoice_no is UNIQUE). */
    function auragold_metal_exchange_delete_journal_for_reference(mysqli $conn, string $reference_type, int $reference_id): void
    {
        if ($reference_id < 1 || trim($reference_type) === '') {
            return;
        }
        $meta = auragold_metal_exchange_sj_prefix_and_audit_src($reference_type);
        if ($meta === null) {
            return;
        }
        $t = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_stock_journal'");
        if (!$t || mysqli_num_rows($t) === 0) {
            if ($t) {
                mysqli_free_result($t);
            }

            return;
        }
        mysqli_free_result($t);

        [$prefix, $audit_src] = $meta;
        $rid = (int) $reference_id;
        $pfx_esc = mysqli_real_escape_string($conn, $prefix . $rid . '-');
        mysqli_query($conn, "DELETE FROM tbl_stock_journal WHERE sj_invoice_no LIKE '{$pfx_esc}%'");

        $src_esc = mysqli_real_escape_string($conn, $audit_src);
        mysqli_query($conn, "DELETE FROM tbl_stock_journal WHERE comment LIKE 'auragold_doc|src={$src_esc}|rid={$rid}|%'");
    }
}

if (!function_exists('auragold_metal_exchange_delete_stock_for_reference')) {
    function auragold_metal_exchange_delete_stock_for_reference(mysqli $conn, string $reference_type, int $reference_id): void
    {
        if ($reference_id < 1 || trim($reference_type) === '') {
            return;
        }
        $rt = mysqli_real_escape_string($conn, $reference_type);
        mysqli_query($conn, "DELETE FROM tbl_stock WHERE reference_type = '$rt' AND reference_id = " . (int) $reference_id);
    }
}

if (!function_exists('auragold_metal_exchange_document_init')) {
    /**
     * Call before re-inserting payments on document save (creates reference columns if needed; deletes prior ME stock on edit).
     */
    function auragold_metal_exchange_document_init(mysqli $conn, bool $is_update, int $doc_id, string $reference_type): bool
    {
        $has_ref = auragold_prepare_tbl_stock_reference_columns($conn);
        if ($is_update && $doc_id > 0) {
            auragold_metal_exchange_delete_journal_for_reference($conn, $reference_type, $doc_id);
            if ($has_ref) {
                auragold_metal_exchange_delete_stock_for_reference($conn, $reference_type, $doc_id);
            }
        }

        return $has_ref;
    }
}

if (!function_exists('auragold_metal_exchange_default_branch_id')) {
    function auragold_metal_exchange_default_branch_id(): int
    {
        $bid = (int) ($_SESSION['working_branch_id'] ?? $_SESSION['branch_id'] ?? 0);

        return $bid > 0 ? $bid : 1;
    }
}

if (!function_exists('auragold_post_metal_exchange_payment_to_stock')) {
    /**
     * New inward tbl_stock row + stock history for one metal-exchange payment line.
     *
     * @param array<string, mixed> $payment merged or raw (merged inside)
     * @param array<int, array{barcode: string, product_name: string}> $created_barcodes_out
     */
    function auragold_post_metal_exchange_payment_to_stock(
        mysqli $conn,
        string $reference_type,
        int $reference_id,
        string $doc_no_plain,
        string $doc_date_ymd,
        array $payment,
        int $branch_id,
        int $pay_seq,
        bool $tbl_stock_has_reference,
        string $history_voucher_label,
        string $audit_src,
        string $sj_invoice_prefix,
        array &$created_barcodes_out
    ): void {
        if (!auragold_payment_is_metal_exchange_inward($conn, $payment)) {
            return;
        }
        $p = auragold_payment_merge_stored_details($payment);
        auragold_validate_metal_exchange_for_stock($conn, $p);
        $r = auragold_metal_exchange_resolve($conn, $p);
        $pcid = (int) ($p['metal_exchange_product_id'] ?? $p['product_id'] ?? 0);
        $mid = (int) ($p['metal_exchange_metal_id'] ?? $p['metal_id'] ?? 0);
        $pcrow = getRecord("SELECT product_id FROM tbl_product_characteristics WHERE id = $pcid AND status = 1 LIMIT 1");
        $pid = (int) ($pcrow['product_id'] ?? 0);
        if ($pid <= 0) {
            throw new Exception('Metal exchange: invalid product characteristic.');
        }
        $barcode_plain = '';
        if (!function_exists('auragold_next_product_stock_barcode')) {
            $nb = __DIR__ . '/next_product_stock_barcode.php';
            if (is_file($nb)) {
                require_once $nb;
            }
        }
        for ($bc_attempt = 0; $bc_attempt < 12; $bc_attempt++) {
            $nb = auragold_next_product_stock_barcode($conn, $pid, $pcid, $mid, $branch_id);
            $try_bc = trim((string) ($nb['barcode'] ?? ''));
            if ($try_bc === '') {
                break;
            }
            $try_esc = mysqli_real_escape_string($conn, $try_bc);
            $dup = getRecord("SELECT id FROM tbl_stock WHERE barcode = '$try_esc' AND status = 1 LIMIT 1");
            if (!$dup) {
                $barcode_plain = $try_bc;
                break;
            }
        }
        if ($barcode_plain === '') {
            throw new Exception('Metal exchange: could not allocate a unique barcode.');
        }

        error_log('[' . $audit_src . '] metal_exchange stock ref=' . $reference_type . ':' . $reference_id . ' pid=' . $pid . ' bc=' . $barcode_plain);
        $gross = (float) $r['gross'];
        $pure = (float) $r['pure'];
        $qty = (float) $r['qty'];
        if ($qty <= 1e-8) {
            $qty = 1.0;
        }
        $opening_purity = ($gross > 1e-8) ? min(1.0, max(0.0001, $pure / $gross)) : 0.916;
        $final_w = ($pure > 1e-8) ? $pure : $gross;
        $rate = (float) ($p['metal_exchange_rate'] ?? $p['rate'] ?? 0);
        $line_amt = (float) ($p['amount'] ?? 0);
        if ($line_amt <= 1e-8 && $rate > 1e-8 && $pure > 1e-8) {
            $line_amt = $rate * $pure;
        } elseif ($line_amt <= 1e-8 && $rate > 1e-8) {
            $line_amt = $rate * $gross;
        }
        $product_name_plain = trim((string) ($p['metal_exchange_product_name'] ?? $p['product_name'] ?? ''));

        $t_stock = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_stock'");
        if (!$t_stock || mysqli_num_rows($t_stock) === 0) {
            if ($t_stock) {
                mysqli_free_result($t_stock);
            }
            throw new Exception('Metal exchange: inventory table not available.');
        }
        mysqli_free_result($t_stock);

        $tbl_stock_has_barcode = false;
        $bc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'barcode'");
        if ($bc && mysqli_num_rows($bc) > 0) {
            $tbl_stock_has_barcode = true;
        }
        if ($bc) {
            mysqli_free_result($bc);
        }
        if (!$tbl_stock_has_barcode) {
            throw new Exception('Metal exchange: tbl_stock has no barcode column.');
        }

        $has_sj = false;
        $sj_check = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'stock_journal_id'");
        if ($sj_check && mysqli_num_rows($sj_check) > 0) {
            $has_sj = true;
        }
        if ($sj_check) {
            mysqli_free_result($sj_check);
        }

        $has_status = false;
        $stc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'status'");
        if ($stc && mysqli_num_rows($stc) > 0) {
            $has_status = true;
        }
        if ($stc) {
            mysqli_free_result($stc);
        }

        $bc_sql = "'" . mysqli_real_escape_string($conn, $barcode_plain) . "'";
        $txn_esc = mysqli_real_escape_string($conn, $doc_date_ymd);
        $bid = $branch_id > 0 ? $branch_id : 1;
        $ref_id = (int) $reference_id;
        $rt_esc = mysqli_real_escape_string($conn, $reference_type);

        $stock_cols = 'product_id, product_characteristic_id, barcode, branch_id, metal_id, opening_weight, opening_purity, opening_qty, final_weight, rate, value, current_weight, current_qty, stock_type, transaction_date';
        $stock_vals = "$pid, $pcid, $bc_sql, $bid, $mid, $gross, $opening_purity, $qty, $final_w, $rate, $line_amt, $gross, $qty, 'purchase', '$txn_esc'";
        if ($tbl_stock_has_reference) {
            $stock_cols .= ', reference_id, reference_type';
            $stock_vals .= ", $ref_id, '$rt_esc'";
        }
        if ($has_sj) {
            $stock_cols .= ', stock_journal_id';
            $stock_vals .= ', NULL';
        }
        if ($has_status) {
            $stock_cols .= ', status, created_at';
            $stock_vals .= ', 1, NOW()';
        } else {
            $stock_cols .= ', created_at';
            $stock_vals .= ', NOW()';
        }

        $stock_insert_sql = "INSERT INTO tbl_stock ($stock_cols) VALUES ($stock_vals)";
        if (!mysqli_query($conn, $stock_insert_sql)) {
            $err = mysqli_error($conn);
            error_log('[' . $audit_src . '] STOCK INSERT FAILED ref=' . $reference_type . ':' . $reference_id . ' err=' . $err);
            throw new Exception('Metal exchange: stock insert failed: ' . $err);
        }

        require_once __DIR__ . '/stock_history_audit_journal.php';
        $sj_no = $sj_invoice_prefix . (int) $reference_id . '-' . (int) $pay_seq;
        if (strlen($sj_no) > 48) {
            $sj_no = preg_replace('/[^A-Za-z0-9\\-]/', '', substr($sj_invoice_prefix, 0, 4)) . (int) $reference_id . 'x' . (int) $pay_seq;
        }
        auragold_stock_history_audit_insert_row($conn, [
            'sj_invoice_no' => $sj_no,
            'item_id' => 0,
            'invoice_id' => 0,
            'invoice_no' => $doc_no_plain,
            'sj_date' => $doc_date_ymd,
            'barcode' => $barcode_plain,
            'product_id' => $pid,
            'product_characteristic_id' => $pcid,
            'product_name' => $product_name_plain,
            'metal_id' => $mid,
            'metal_type' => function_exists('auragold_stock_history_metal_type') ? auragold_stock_history_metal_type($conn, $mid) : '',
            'quantity' => $qty,
            'gross_weight' => $gross,
            'less_weight' => 0,
            'net_weight' => $gross,
            'purity' => $opening_purity * 100,
            'purity_weight' => $pure,
            'pure_weight' => $pure,
            'final_weight' => $final_w,
            'rate' => $rate,
            'amount' => $line_amt,
            'making_amount' => 0,
            'tax_amount' => 0,
            'net_amount' => $line_amt,
            'net_amt_with_tax' => $line_amt,
            'rfid_code' => '',
            'voucher_type' => $history_voucher_label,
            'design_no' => '',
            'category' => '',
            'comment' => 'auragold_doc|src=' . $audit_src . '|rid=' . (int) $reference_id . '|seq=' . (int) $pay_seq . '|',
        ]);

        $created_barcodes_out[] = [
            'barcode' => $barcode_plain,
            'product_name' => $product_name_plain,
        ];
    }
}
