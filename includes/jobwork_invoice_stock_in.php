<?php
/**
 * After a jobwork invoice is saved: post finished barcodes from tbl_jobwork_order_items
 * into tbl_stock_journal + tbl_stock (purchase) so they appear in stock / stock history.
 *
 * Rows are tagged with comment: auragold_jwi|jwi_id={invoice_id}|joi_id={item_id}|
 * Re-save removes prior rows for that invoice and re-inserts.
 */

if (!function_exists('auragold_jobwork_invoice_remove_stock_for_invoice')) {

    function auragold_jobwork_invoice_remove_stock_for_invoice(mysqli $conn, int $jobwork_invoice_id): void
    {
        $jid = (int) $jobwork_invoice_id;
        if ($jid <= 0) {
            return;
        }
        $like = 'auragold_jwi|jwi_id=' . $jid . '|%';
        $like_esc = mysqli_real_escape_string($conn, $like);
        $q = @mysqli_query($conn, "SELECT id FROM tbl_stock_journal WHERE status = 'active' AND comment LIKE '$like_esc'");
        $ids = [];
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $ids[] = (int) ($r['id'] ?? 0);
            }
            mysqli_free_result($q);
        }
        if (empty($ids)) {
            return;
        }
        $in = implode(',', array_map('intval', $ids));

        $has_ref = false;
        $rc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock WHERE Field IN ('reference_id','reference_type')");
        if ($rc && mysqli_num_rows($rc) >= 2) {
            $has_ref = true;
        }
        if ($rc) {
            mysqli_free_result($rc);
        }

        if ($has_ref) {
            @mysqli_query($conn, "DELETE FROM tbl_stock WHERE reference_type = 'stock_journal' AND reference_id IN ($in)");
        }

        $t_inward = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_inward_stock'");
        if ($t_inward && mysqli_num_rows($t_inward) > 0) {
            mysqli_free_result($t_inward);
            @mysqli_query($conn, "DELETE FROM tbl_inward_stock WHERE stock_journal_id IN ($in)");
        } elseif ($t_inward) {
            mysqli_free_result($t_inward);
        }

        @mysqli_query($conn, "DELETE FROM tbl_stock_journal WHERE id IN ($in)");
    }

    /**
     * @throws RuntimeException
     */
    function auragold_jobwork_invoice_apply_stock_in(mysqli $conn, int $jobwork_invoice_id, int $jobwork_order_id, string $invoice_no): void
    {
        $jwi_id = (int) $jobwork_invoice_id;
        $jwo_id = (int) $jobwork_order_id;
        if ($jwi_id <= 0 || $jwo_id <= 0) {
            return;
        }

        $invoice_no_esc = mysqli_real_escape_string($conn, $invoice_no);
        $journal_date_esc = mysqli_real_escape_string($conn, date('Y-m-d'));

        $has_reference_cols = false;
        $ref_cols = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock WHERE Field IN ('reference_id','reference_type')");
        if ($ref_cols && mysqli_num_rows($ref_cols) >= 2) {
            $has_reference_cols = true;
        }
        if ($ref_cols) {
            mysqli_free_result($ref_cols);
        }

        $has_stock_journal_id = false;
        $sjid_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'stock_journal_id'");
        if ($sjid_col && mysqli_num_rows($sjid_col) > 0) {
            $has_stock_journal_id = true;
        }
        if ($sjid_col) {
            mysqli_free_result($sjid_col);
        }

        auragold_jobwork_invoice_remove_stock_for_invoice($conn, $jwi_id);

        $jwo = getRecord("SELECT id, sale_order_id, sale_order_no, jobwork_no FROM tbl_jobwork_orders WHERE id = $jwo_id LIMIT 1");
        if (!$jwo) {
            throw new RuntimeException('Job work order not found for stock posting');
        }

        $items = getList("SELECT * FROM tbl_jobwork_order_items WHERE jobwork_order_id = $jwo_id ORDER BY id ASC");
        if (!is_array($items) || empty($items)) {
            return;
        }

        $user_id = 0;
        if (isset($_SESSION['Admin']['id'])) {
            $user_id = (int) $_SESSION['Admin']['id'];
        } elseif (isset($_SESSION['user_id'])) {
            $user_id = (int) $_SESSION['user_id'];
        }

        foreach ($items as $item) {
            $joi_id = (int) ($item['id'] ?? 0);
            $product_id = (int) ($item['product_id'] ?? 0);
            $barcode = isset($item['barcode']) ? trim((string) $item['barcode']) : '';
            if ($product_id <= 0 || $barcode === '') {
                continue;
            }

            $characteristic_id = isset($item['product_characteristic_id']) ? (int) $item['product_characteristic_id'] : 0;
            $product_name = isset($item['product_name']) ? trim((string) $item['product_name']) : '';
            if ($product_name === '' && $product_id > 0) {
                $pn = getRecord("SELECT name FROM tbl_products WHERE id = $product_id AND status = 1 LIMIT 1");
                if ($pn && !empty($pn['name'])) {
                    $product_name = trim((string) $pn['name']);
                }
            }
            $product_name_esc = mysqli_real_escape_string($conn, $product_name);

            $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;
            $gross_weight = isset($item['gross_weight']) ? (float) $item['gross_weight'] : 0;
            $less_weight = isset($item['less_weight']) ? (float) $item['less_weight'] : 0;
            $net_weight = isset($item['net_weight']) ? (float) $item['net_weight'] : 0;
            $final_weight = isset($item['final_weight']) ? (float) $item['final_weight'] : 0;
            $purity = isset($item['purity']) ? (float) $item['purity'] : 0;
            $pure_weight = isset($item['pure_weight']) ? (float) $item['pure_weight'] : 0;
            $rate = isset($item['rate']) ? (float) $item['rate'] : 0;
            $amount = isset($item['amount']) ? (float) $item['amount'] : 0;
            $making_amount = isset($item['making_amount']) ? (float) $item['making_amount'] : 0;
            $tax_amount = isset($item['tax_amount']) ? (float) $item['tax_amount'] : 0;
            $net_amount = isset($item['net_amount']) ? (float) $item['net_amount'] : 0;
            $net_amt_with_tax = isset($item['net_amt_with_tax']) ? (float) $item['net_amt_with_tax'] : 0;
            $design_no = isset($item['design_no']) ? trim((string) $item['design_no']) : '';

            if ($quantity <= 0) {
                $quantity = 1;
            }
            $stock_weight = $gross_weight > 0 ? $gross_weight : ($final_weight > 0 ? $final_weight : ($net_weight > 0 ? $net_weight : 0));
            if ($stock_weight <= 0 && $pure_weight > 0) {
                $stock_weight = $pure_weight;
            }
            if ($stock_weight <= 0) {
                continue;
            }
            if ($net_weight <= 0) {
                $net_weight = $stock_weight - $less_weight;
            }
            $purity_weight = ($net_weight > 0 && $purity > 0) ? ($net_weight * $purity / 100) : 0;
            if ($pure_weight <= 0 && $purity_weight > 0) {
                $pure_weight = $purity_weight;
            }
            if ($final_weight <= 0) {
                $final_weight = $net_weight > 0 ? $net_weight : $stock_weight;
            }

            $metal_id = 0;
            $branch_id = 0;
            $stock_purity = $purity;
            if ($characteristic_id > 0) {
                $char_details = getRecord("SELECT branch_id, metal_id, opening_purity FROM tbl_product_characteristics WHERE id = $characteristic_id AND status = 1");
                if ($char_details) {
                    $branch_id = (int) $char_details['branch_id'];
                    $metal_id = (int) $char_details['metal_id'];
                    if ($stock_purity <= 0) {
                        $stock_purity = (float) ($char_details['opening_purity'] ?? 0);
                    }
                }
            }
            if ($metal_id <= 0 && $product_id > 0) {
                $default_metal = getRecord("SELECT metal_id FROM tbl_product_characteristics WHERE product_id = $product_id AND status = 1 ORDER BY id DESC LIMIT 1");
                $metal_id = $default_metal ? (int) $default_metal['metal_id'] : 0;
            }
            if ($branch_id <= 0) {
                $branch_id = 1;
            }
            if ($metal_id <= 0) {
                $metal_id = 1;
            }
            if ($stock_purity <= 0) {
                $stock_purity = 100.0;
            }

            $metal_type = '';
            if ($metal_id > 0) {
                $metal_info = getRecord("SELECT system_name, display_name FROM tbl_metal WHERE id = $metal_id");
                if ($metal_info) {
                    $raw = $metal_info['system_name'] ?? strtolower((string) ($metal_info['display_name'] ?? ''));
                    $raw = trim((string) $raw);
                    if ($raw !== '') {
                        $metal_type = $raw;
                    }
                }
            }
            $metal_type_esc = $metal_type !== '' ? "'" . mysqli_real_escape_string($conn, $metal_type) . "'" : 'NULL';

            $category_esc = 'NULL';
            if ($product_id > 0) {
                $pcat = getRecord("SELECT c.name FROM tbl_products p LEFT JOIN tbl_categories c ON c.id = p.category_id WHERE p.id = $product_id LIMIT 1");
                if ($pcat && !empty($pcat['name'])) {
                    $category_esc = "'" . mysqli_real_escape_string($conn, (string) $pcat['name']) . "'";
                }
            }

            $sj_no = 'JWI' . $jwi_id . 'I' . $joi_id;
            if (strlen($sj_no) > 45) {
                $sj_no = 'J' . $jwi_id . 'x' . $joi_id;
            }
            $sj_no_esc = mysqli_real_escape_string($conn, $sj_no);

            $comment_raw = 'auragold_jwi|jwi_id=' . $jwi_id . '|joi_id=' . $joi_id . '|';
            $comment_esc = mysqli_real_escape_string($conn, $comment_raw);

            $barcode_esc = mysqli_real_escape_string($conn, $barcode);
            $design_esc = $design_no !== '' ? "'" . mysqli_real_escape_string($conn, $design_no) . "'" : 'NULL';

            $voucher_esc = mysqli_real_escape_string($conn, 'Jobwork Invoice');

            $stock_value = $net_amt_with_tax > 0 ? $net_amt_with_tax : ($net_amount > 0 ? $net_amount : $amount);

            $journal_sql = "
            INSERT INTO tbl_stock_journal (
                sj_invoice_no,
                item_id,
                invoice_id,
                invoice_no,
                sj_date,
                barcode,
                code,
                product_id,
                product_characteristic_id,
                product_name,
                metal_id,
                metal_type,
                quantity,
                karat,
                gross_weight,
                less_weight,
                net_weight,
                purity,
                purity_weight,
                pure_weight,
                final_weight,
                rate,
                amount,
                making_amount,
                tax_amount,
                net_amount,
                net_amt_with_tax,
                rfid_code,
                voucher_type,
                design_no,
                huid_no,
                category,
                calculation,
                location,
                pkt_wt,
                pkt_less_wt,
                requested_purity,
                requested,
                gold_loss_1,
                gold_loss_2,
                setting_charge,
                wastage_per,
                wastage_wt,
                alloy_wt,
                metal_value,
                metal_cost,
                discount_type,
                discount_per,
                discount_amount,
                discount,
                making_type,
                making_rate,
                making_cost,
                minimum_price,
                stone_charge_type,
                stone_weight,
                stone_rate,
                stone_amount,
                stone_cost,
                diamond_amount,
                purchase_amount,
                sale_amount,
                other_charge_type,
                other_weight,
                other_rate,
                other_info,
                other_amount,
                hallmark_amount,
                hallmark_rate,
                reverse,
                group_name,
                comment,
                status,
                created_by,
                created_at
            ) VALUES (
                '$sj_no_esc',
                NULL,
                NULL,
                '$invoice_no_esc',
                '$journal_date_esc',
                '$barcode_esc',
                NULL,
                $product_id,
                " . ($characteristic_id > 0 ? $characteristic_id : 'NULL') . ",
                '$product_name_esc',
                " . ($metal_id ? $metal_id : 'NULL') . ",
                $metal_type_esc,
                $quantity,
                NULL,
                $gross_weight,
                $less_weight,
                $net_weight,
                $purity,
                $purity_weight,
                $pure_weight,
                $final_weight,
                $rate,
                $amount,
                $making_amount,
                $tax_amount,
                $net_amount,
                $net_amt_with_tax,
                NULL,
                '$voucher_esc',
                $design_esc,
                NULL,
                $category_esc,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                '$comment_esc',
                'active',
                $user_id,
                NOW()
            )
            ";

            if (!mysqli_query($conn, $journal_sql)) {
                throw new RuntimeException('Stock journal insert failed: ' . mysqli_error($conn));
            }
            $journal_id = (int) mysqli_insert_id($conn);

            $ref_cols_sql = $has_reference_cols ? ', reference_id, reference_type' : '';
            $ref_vals_sql = $has_reference_cols ? ", $journal_id, 'stock_journal'" : '';
            $sjid_col_sql = $has_stock_journal_id ? ', stock_journal_id' : '';
            $sjid_val_sql = $has_stock_journal_id ? ', NULL' : '';

            $barcode_sql = "'" . $barcode_esc . "'";
            $inward_stock_sql = "
                INSERT INTO tbl_stock (
                    product_id,
                    product_characteristic_id,
                    barcode,
                    branch_id,
                    metal_id,
                    opening_weight,
                    opening_purity,
                    opening_qty,
                    final_weight,
                    rate,
                    value,
                    current_weight,
                    current_qty,
                    stock_type,
                    transaction_date,
                    created_at
                    $ref_cols_sql
                    $sjid_col_sql
                ) VALUES (
                    $product_id,
                    " . ($characteristic_id ? $characteristic_id : 'NULL') . ",
                    $barcode_sql,
                    $branch_id,
                    $metal_id,
                    $stock_weight,
                    $stock_purity,
                    $quantity,
                    " . ($final_weight > 0 ? $final_weight : $stock_weight) . ",
                    $rate,
                    $stock_value,
                    $stock_weight,
                    $quantity,
                    'purchase',
                    '$journal_date_esc',
                    NOW()
                    $ref_vals_sql
                    $sjid_val_sql
                )
            ";

            if (!mysqli_query($conn, $inward_stock_sql)) {
                throw new RuntimeException('Stock insert failed: ' . mysqli_error($conn));
            }

            $t_inward = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_inward_stock'");
            if ($t_inward && mysqli_num_rows($t_inward) > 0) {
                mysqli_free_result($t_inward);
                $inward_val_sql = "
                    INSERT INTO tbl_inward_stock (
                        stock_journal_id, product_id, product_characteristic_id, barcode_no,
                        branch_id, metal_id, qty, weight, rate, value, stock_type, transaction_date, created_at
                    ) VALUES (
                        $journal_id,
                        $product_id,
                        " . ($characteristic_id ? $characteristic_id : 'NULL') . ",
                        $barcode_sql,
                        $branch_id,
                        " . ($metal_id ? $metal_id : 'NULL') . ",
                        $quantity,
                        $stock_weight,
                        $rate,
                        $stock_value,
                        'purchase',
                        '$journal_date_esc',
                        NOW()
                    )
                ";
                if (!mysqli_query($conn, $inward_val_sql)) {
                    throw new RuntimeException('tbl_inward_stock insert failed: ' . mysqli_error($conn));
                }
            } elseif ($t_inward) {
                mysqli_free_result($t_inward);
            }

            if ($characteristic_id > 0) {
                $has_qty = false;
                $has_wt = false;
                $upd_cols = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_product_characteristics WHERE Field IN ('opening_qty','opening_weight')");
                if ($upd_cols) {
                    while ($c = mysqli_fetch_assoc($upd_cols)) {
                        if (($c['Field'] ?? '') === 'opening_qty') {
                            $has_qty = true;
                        }
                        if (($c['Field'] ?? '') === 'opening_weight') {
                            $has_wt = true;
                        }
                    }
                    mysqli_free_result($upd_cols);
                }
                if ($has_qty || $has_wt) {
                    $set_parts = [];
                    if ($has_qty) {
                        $set_parts[] = 'opening_qty = COALESCE(opening_qty, 0) + ' . $quantity;
                    }
                    if ($has_wt) {
                        $set_parts[] = 'opening_weight = COALESCE(opening_weight, 0) + ' . $stock_weight;
                    }
                    $upd_sql = 'UPDATE tbl_product_characteristics SET ' . implode(', ', $set_parts) . " WHERE id = $characteristic_id";
                    if (!mysqli_query($conn, $upd_sql)) {
                        throw new RuntimeException('Product characteristic update failed: ' . mysqli_error($conn));
                    }
                }
            }
        }
    }
}
