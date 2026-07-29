<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/auragold_metal_exchange_stock.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    require_once __DIR__ . '/../includes/ensure_customer_ledger_branch_column.php';

    $has_pv_branch = auragold_ensure_table_branch_id_column($conn, 'tbl_payment_vouchers');
    $hdr_branch    = auragold_transaction_header_branch_id();
    $eff_branch    = auragold_effective_branch_id();
    $pv_dup_sql    = ($has_pv_branch && $hdr_branch > 0) ? (' AND branch_id = ' . (int) $hdr_branch) : '';

    $voucher_id = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
    $payment_voucher_existed = ($voucher_id > 0);
    $voucher_no = esc($_POST['voucher_no'] ?? '');
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $customer_name = esc($_POST['customer_name'] ?? '');
    $ref_no = esc($_POST['ref_no'] ?? '');
    $receipt_no = esc($_POST['receipt_no'] ?? '');
    $voucher_type = esc($_POST['voucher_type'] ?? '');
    $against = esc($_POST['against'] ?? '');
    $sales_person = esc($_POST['sales_person'] ?? '');
    $against_of = esc($_POST['against_of'] ?? '');
    $currency = esc($_POST['currency'] ?? 'USD');
    $currency_rate = isset($_POST['currency_rate']) ? (float)$_POST['currency_rate'] : 1.0;
    $voucher_date = esc($_POST['voucher_date'] ?? date('Y-m-d'));
    $due_date = esc($_POST['due_date'] ?? null);
    $layaways_id = isset($_POST['layaways_id']) ? (int)$_POST['layaways_id'] : 0;
    $fixing_type = esc($_POST['fixing_type'] ?? 'Standard');
    $previous_balance = isset($_POST['previous_balance']) ? (float)$_POST['previous_balance'] : 0.00;
    $previous_gold = isset($_POST['previous_gold']) ? (float)$_POST['previous_gold'] : 0.000;
    $previous_silver = isset($_POST['previous_silver']) ? (float)$_POST['previous_silver'] : 0.000;
    $total_amount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0.00;
    $total_gold = isset($_POST['total_gold']) ? (float)$_POST['total_gold'] : 0.000;
    $total_silver = isset($_POST['total_silver']) ? (float)$_POST['total_silver'] : 0.000;
    $comment = esc($_POST['comment'] ?? '');
    $items = isset($_POST['items']) ? $_POST['items'] : [];
    $created_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $scrap_invoice_numbers = [];
    $metal_exchange_barcodes_out = [];

    $pv_money_types = ['cash', 'bank', 'cheque', 'upi', 'card'];
    $sum_money_from_items = 0.0;
    $party_against_display = '';
    $party_against_parts = [];
    if (is_array($items)) {
        foreach ($items as $it) {
            $pt = strtolower(trim($it['payment_type'] ?? ''));
            if (!in_array($pt, $pv_money_types, true)) {
                continue;
            }
            $a = (float)($it['amount'] ?? 0);
            $sum_money_from_items += $a;
            if ($a > 0) {
                $d = trim($it['deposit_into'] ?? '');
                if ($d === '' && $pt === 'cash') {
                    $d = 'Cash';
                }
                if ($d !== '') {
                    $party_against_parts[] = $d . '(' . number_format($a, 2) . 'Cr)';
                }
            }
        }
    }
    if (!empty($party_against_parts)) {
        $party_against_display = implode(', ', $party_against_parts);
    }
    if ($total_amount <= 0 && $sum_money_from_items > 0) {
        $total_amount = $sum_money_from_items;
    }

    // Metal id(s) for gold/silver: compute ledger totals from items so deduction is always correct
    $gold_metal_ids = [];
    $silver_metal_ids = [];
    foreach (getList("SELECT id, LOWER(COALESCE(display_name, system_name, '')) as n FROM tbl_metal") as $m) {
        $id = (int)$m['id'];
        $n = $m['n'] ?? '';
        if (strpos($n, 'gold') !== false) {
            $gold_metal_ids[] = $id;
        } elseif (strpos($n, 'silver') !== false) {
            $silver_metal_ids[] = $id;
        }
    }
    $total_gold_pure = 0.000;
    $total_gold_from_items = 0.000;  // gold weight for ledger (from metal exchange items)
    $total_silver_from_items = 0.000;

    // Validation
    if (empty($customer_name)) {
        throw new Exception('Customer name is required');
    }

    if (empty($voucher_no)) {
        throw new Exception('Voucher number is required');
    }

    $pv_row_branch_id = 0;
    if ($voucher_id > 0 && $has_pv_branch) {
        $pv_br = getRecord("SELECT branch_id FROM tbl_payment_vouchers WHERE id = $voucher_id LIMIT 1");
        $pv_row_branch_id = (int) ($pv_br['branch_id'] ?? 0);
        auragold_branch_require_document_access($conn, 'tbl_payment_vouchers', $voucher_id);
    }

    // Check if voucher number already exists (for new vouchers)
    if ($voucher_id == 0) {
        $existing = getRecord("SELECT id FROM tbl_payment_vouchers WHERE voucher_no = '$voucher_no'$pv_dup_sql");
        if ($existing) {
            throw new Exception('Voucher number already exists');
        }
    }

    if ($voucher_id > 0) {
        // Update existing voucher
        $update_query = "
            UPDATE tbl_payment_vouchers SET
                customer_id = " . ($customer_id > 0 ? $customer_id : 'NULL') . ",
                customer_name = '$customer_name',
                ref_no = " . ($ref_no ? "'$ref_no'" : 'NULL') . ",
                voucher_type = " . ($voucher_type ? "'$voucher_type'" : 'NULL') . ",
                against = " . ($against ? "'$against'" : 'NULL') . ",
                sales_person = " . ($sales_person ? "'$sales_person'" : 'NULL') . ",
                against_of = " . ($against_of ? "'$against_of'" : 'NULL') . ",
                currency = '$currency',
                voucher_date = '$voucher_date',
                due_date = " . ($due_date ? "'$due_date'" : 'NULL') . ",
                layaways_id = " . ($layaways_id > 0 ? $layaways_id : 'NULL') . ",
                fixing_type = '$fixing_type',
                previous_balance = $previous_balance,
                previous_gold = $previous_gold,
                previous_silver = $previous_silver,
                total_amount = $total_amount,
                total_gold = $total_gold,
                total_silver = $total_silver,
                comment = " . ($comment ? "'$comment'" : 'NULL') . "
                " . ($has_pv_branch && $eff_branch > 0 && $pv_row_branch_id === 0 ? ', branch_id = ' . (int) $eff_branch : '') . ",
                updated_at = NOW()
            WHERE id = $voucher_id
        ";

        if (!mysqli_query($conn, $update_query)) {
            throw new Exception('Error updating voucher: ' . mysqli_error($conn));
        }

        // Delete existing items
        mysqli_query($conn, "DELETE FROM tbl_stock_journal WHERE comment LIKE 'auragold_doc|src=pv|hid=" . (int) $voucher_id . "|%'");
        mysqli_query($conn, "DELETE FROM tbl_payment_voucher_items WHERE voucher_id = $voucher_id");
    } else {
        // Insert new voucher
        $insert_query = "
            INSERT INTO tbl_payment_vouchers (
                voucher_no, customer_id, customer_name, ref_no, voucher_type, against,
                sales_person, against_of, currency, voucher_date, due_date, layaways_id,
                fixing_type, previous_balance, previous_gold, previous_silver,
                total_amount, total_gold, total_silver, comment, status, created_by,
                " . ($has_pv_branch ? 'branch_id, ' : '') . "created_at
            ) VALUES (
                '$voucher_no',
                " . ($customer_id > 0 ? $customer_id : 'NULL') . ",
                '$customer_name',
                " . ($ref_no ? "'$ref_no'" : 'NULL') . ",
                " . ($voucher_type ? "'$voucher_type'" : 'NULL') . ",
                " . ($against ? "'$against'" : 'NULL') . ",
                " . ($sales_person ? "'$sales_person'" : 'NULL') . ",
                " . ($against_of ? "'$against_of'" : 'NULL') . ",
                '$currency',
                '$voucher_date',
                " . ($due_date ? "'$due_date'" : 'NULL') . ",
                " . ($layaways_id > 0 ? $layaways_id : 'NULL') . ",
                '$fixing_type',
                $previous_balance,
                $previous_gold,
                $previous_silver,
                $total_amount,
                $total_gold,
                $total_silver,
                " . ($comment ? "'$comment'" : 'NULL') . ",
                'draft',
                " . ($created_by ? $created_by : 'NULL') . ",
                " . ($has_pv_branch ? ((int) $hdr_branch > 0 ? (int) $hdr_branch : 'NULL') . ', ' : '') . "
                NOW()
            )
        ";

        if (!mysqli_query($conn, $insert_query)) {
            throw new Exception('Error inserting voucher: ' . mysqli_error($conn));
        }

        $voucher_id = mysqli_insert_id($conn);
    }

    if (is_array($items)) {
        foreach ($items as $__pvi) {
            if (!is_array($__pvi)) {
                continue;
            }
            $__mrg = auragold_payment_merge_stored_details($__pvi);
            if (!auragold_payment_is_metal_exchange_inward($conn, $__mrg)) {
                continue;
            }
            auragold_validate_metal_exchange_for_stock($conn, $__mrg);
        }
    }
    $___pv_me_has_ref = auragold_metal_exchange_document_init($conn, $payment_voucher_existed, (int) $voucher_id, 'payment_voucher_metal_exchange');

    // Insert receipt items
    if (is_array($items) && count($items) > 0) {
        foreach ($items as $pay_seq => $item) {
            $payment_type = esc($item['payment_type'] ?? '');
            $diamond_category = esc($item['diamond_category'] ?? '');
            $transaction_no = esc($item['transaction_no'] ?? '');
            $deposit_into = esc($item['deposit_into'] ?? '');
            $product_id = isset($item['product_id']) ? (int)$item['product_id'] : 0;
            $cheque_date = esc($item['cheque_date'] ?? null);
            $weight = isset($item['weight']) ? (float)$item['weight'] : 0.000;
            $metal_id = isset($item['metal_id']) ? (int)$item['metal_id'] : 0;
            $quantity = isset($item['quantity']) ? (float)$item['quantity'] : 0.00;
            $purity_carat = esc($item['purity_carat'] ?? '');
            $purity_wt = isset($item['purity_wt']) ? (float)$item['purity_wt'] : 0.000;
            $rate = isset($item['rate']) ? (float)$item['rate'] : 0.00;
            $amount = isset($item['amount']) ? (float)$item['amount'] : 0.00;
            $item_code = esc($item['item_code'] ?? '');
            $barcode_no = esc($item['barcode_no'] ?? '');
            $card_no = esc($item['card_no'] ?? '');
            $previous_balance_amount = isset($item['previous_balance_amount']) ? (float)$item['previous_balance_amount'] : 0.00;

            $__pv_keep_line = auragold_should_persist_payment_row_with_metal_exchange($conn, $item)
                || strlen(trim((string) ($item['payment_type'] ?? ''))) > 0;
            if (!$__pv_keep_line) {
                continue;
            }

            // Accumulate metal totals from Metal exchange items for ledger (so gold/silver deduct is always added)
            if ($payment_type === 'Metal' && $metal_id > 0) {
                $wt = $purity_wt > 0 ? $purity_wt : $weight;
                if (in_array($metal_id, $gold_metal_ids, true)) {
                    $total_gold_pure += $wt;
                    $total_gold_from_items += $wt;
                } elseif (in_array($metal_id, $silver_metal_ids, true)) {
                    $total_silver_from_items += $wt;
                }
            }
            
            // Build INSERT query with only existing columns
            // Note: transfer_from, item_code, barcode_no, card_no, rate may not exist in table
            // Adjust based on your actual table structure
            $item_query = "
                INSERT INTO tbl_payment_voucher_items (
                    voucher_id, payment_type, diamond_category, transaction_no, deposit_into,
                    product_id, cheque_date, weight, metal_id, quantity, purity_carat, purity_wt,
                    amount, previous_balance_amount,
                    status, created_at
                ) VALUES (
                    $voucher_id,
                    " . ($payment_type ? "'$payment_type'" : 'NULL') . ",
                    " . ($diamond_category ? "'$diamond_category'" : 'NULL') . ",
                    " . ($transaction_no ? "'$transaction_no'" : 'NULL') . ",
                    " . ($deposit_into ? "'$deposit_into'" : 'NULL') . ",
                    " . ($product_id > 0 ? $product_id : 'NULL') . ",
                    " . ($cheque_date ? "'$cheque_date'" : 'NULL') . ",
                    $weight,
                    " . ($metal_id > 0 ? $metal_id : 'NULL') . ",
                    $quantity,
                    " . ($purity_carat ? "'$purity_carat'" : 'NULL') . ",
                    $purity_wt,
                    $amount,
                    $previous_balance_amount,
                    1,
                    NOW()
                )
            ";

            if (!mysqli_query($conn, $item_query)) {
                throw new Exception('Error inserting voucher item: ' . mysqli_error($conn));
            }
            $pv_line_id = (int) mysqli_insert_id($conn);
            $pv_dt = trim((string) ($_POST['voucher_date'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $pv_dt)) {
                $pv_dt = date('Y-m-d');
            }
            require_once dirname(__DIR__) . '/includes/stock_history_audit_journal.php';
            $pw = $purity_wt > 0 ? $purity_wt : $weight;
            auragold_stock_history_audit_for_document_barcode_line($conn, 'Payment Voucher', trim((string) ($_POST['voucher_no'] ?? '')), $pv_dt, 'PV', (int) $voucher_id, $pv_line_id, 'pv', [
                'barcode' => trim((string) ($item['barcode_no'] ?? $item['barcode'] ?? '')),
                'product_id' => $product_id,
                'metal_id' => $metal_id,
                'quantity' => $quantity,
                'gross_weight' => $weight,
                'less_weight' => 0,
                'net_weight' => $pw,
                'purity_weight' => $purity_wt,
                'pure_weight' => $pw,
                'final_weight' => $pw,
                'purity' => 0,
                'rate' => $rate,
                'amount' => $amount,
                'tax_amount' => 0,
                'net_amount' => $amount,
                'net_amt_with_tax' => $amount,
                'category' => trim((string) ($item['diamond_category'] ?? '')),
            ]);

            $pv_plain_no = trim((string) ($_POST['voucher_no'] ?? ''));
            $pv_me_pm = auragold_payment_merge_stored_details($item);
            auragold_post_metal_exchange_payment_to_stock(
                $conn,
                'payment_voucher_metal_exchange',
                (int) $voucher_id,
                $pv_plain_no,
                substr(trim((string) $voucher_date), 0, 10),
                $pv_me_pm,
                auragold_metal_exchange_default_branch_id(),
                is_int($pay_seq) ? $pay_seq : (int) $pay_seq,
                $___pv_me_has_ref,
                'Payment Voucher — Metal Exchange',
                'pv_me',
                'PV-ME-',
                $metal_exchange_barcodes_out
            );

            // If payment type is Metal Exchange, deduct metal from stock (outward = deduction)
            if ($payment_type === 'Metal' && $metal_id > 0 && $weight > 0) {
                // Get branch_id (default to 1 or get from session/config)
                $branch_id = isset($_SESSION['branch_id']) ? (int)$_SESSION['branch_id'] : 1;
                
                // Calculate stock values - use NEGATIVE so SUM(current_weight) deducts (not adds)
                $stock_weight = $purity_wt > 0 ? $purity_wt : $weight;
                $stock_purity = 0;
                if ($purity_carat) {
                    $purity_numeric = preg_replace('/[^0-9.]/', '', $purity_carat);
                    $stock_purity = (float)$purity_numeric;
                }
                $stock_value = $amount;
                // Outward = deduction: store NEGATIVE weight/value so SUM(current_weight) and SUM(value) deduct (not add)
                // Keep quantity positive so available_qty = (purchase qty - outward qty) still works in reports
                $outward_weight = -abs($stock_weight);
                $outward_value = -abs($stock_value);
                
                $outward_stock_sql = "
                    INSERT INTO tbl_stock (
                        product_id,
                        product_characteristic_id,
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
                    ) VALUES (
                        " . ($product_id > 0 ? $product_id : 'NULL') . ",
                        NULL,
                        $branch_id,
                        $metal_id,
                        $outward_weight,
                        $stock_purity,
                        $quantity,
                        $outward_weight,
                        $rate,
                        $outward_value,
                        $outward_weight,
                        $quantity,
                        'outward',
                        '$voucher_date',
                        NOW()
                    )
                ";
                
                if (!mysqli_query($conn, $outward_stock_sql)) {
                    throw new Exception("Outward stock insert failed: " . mysqli_error($conn));
                }
            }
        }
        // Keep voucher header in sync with items so total_gold/total_silver match ledger
        if ($total_gold_from_items > 0 || $total_silver_from_items > 0) {
            $vg = ($total_gold_from_items > 0) ? $total_gold_from_items : $total_gold;
            $vs = ($total_silver_from_items > 0) ? $total_silver_from_items : $total_silver;
            mysqli_query($conn, "UPDATE tbl_payment_vouchers SET total_gold = " . (float)$vg . ", total_silver = " . (float)$vs . " WHERE id = $voucher_id");
        }
    }

    // Scrap payment lines: create Old Jewelry Scrap invoice (OJB-*) per line; customer ledger against_invoice shows OJB no.
    $t_ojb = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_old_jewelry_scrap_invoices'");
    $t_ojb_i = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_old_jewelry_scrap_invoice_items'");
    $t_ojb_p = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_old_jewelry_scrap_invoice_payments'");
    if ($t_ojb && mysqli_num_rows($t_ojb) > 0 && $t_ojb_i && mysqli_num_rows($t_ojb_i) > 0 && is_array($items) && count($items) > 0) {
        mysqli_query($conn, "DELETE FROM tbl_old_jewelry_scrap_invoices WHERE comment LIKE '%[[PV_LINK_ID:" . (int)$voucher_id . "]]%'");
        $last_ojb = getRecord("SELECT invoice_no FROM tbl_old_jewelry_scrap_invoices ORDER BY id DESC LIMIT 1");
        $next_ojb_num = 1;
        if ($last_ojb && !empty($last_ojb['invoice_no'])) {
            $next_ojb_num = (int)preg_replace('/[^0-9]/', '', $last_ojb['invoice_no']) + 1;
        }
        $cust_sql_id = $customer_id > 0 ? (int)$customer_id : 'NULL';
        $sales_sql = ($sales_person !== '') ? "'" . mysqli_real_escape_string($conn, $sales_person) . "'" : 'NULL';
        $created_by_sql = $created_by ? (int)$created_by : 'NULL';

        foreach ($items as $sitem) {
            if (strcasecmp(trim($sitem['payment_type'] ?? ''), 'Scrap') !== 0) {
                continue;
            }
            $sam = isset($sitem['amount']) ? (float)$sitem['amount'] : 0.00;
            if ($sam <= 0) {
                continue;
            }
            $ojb_no = 'OJB-' . $next_ojb_num;
            $next_ojb_num++;
            while (getRecord("SELECT id FROM tbl_old_jewelry_scrap_invoices WHERE invoice_no = '" . mysqli_real_escape_string($conn, $ojb_no) . "' LIMIT 1")) {
                $ojb_no = 'OJB-' . $next_ojb_num;
                $next_ojb_num++;
            }

            $sw = isset($sitem['weight']) ? (float)$sitem['weight'] : 0.000;
            $snet = $sw;
            $spure = isset($sitem['purity_wt']) ? (float)$sitem['purity_wt'] : (isset($sitem['purity_weight']) ? (float)$sitem['purity_weight'] : 0.000);
            $sqty = isset($sitem['quantity']) ? (float)$sitem['quantity'] : 1.00;
            $srate = isset($sitem['rate']) ? (float)$sitem['rate'] : 0.00;
            $purity_num = 0.00;
            if (!empty($sitem['purity_carat'])) {
                $purity_num = (float)preg_replace('/[^0-9.]/', '', (string)$sitem['purity_carat']);
            }
            $pid = isset($sitem['product_id']) ? (int)$sitem['product_id'] : 0;
            $desc_item = trim((string)($sitem['product'] ?? ''));
            if ($desc_item === '' && $pid > 0) {
                $pr = @getRecord("SELECT name FROM tbl_products WHERE id = $pid LIMIT 1");
                if ($pr && !empty($pr['name'])) {
                    $desc_item = $pr['name'];
                }
            }
            if ($desc_item === '') {
                $desc_item = 'Scrap';
            }
            $desc_item_esc = mysqli_real_escape_string($conn, $desc_item);
            $ojb_comment = mysqli_real_escape_string($conn, 'Auto from Payment Voucher ' . $voucher_no . ' [[PV_LINK_ID:' . (int)$voucher_id . ']]');
            $ojb_no_esc = mysqli_real_escape_string($conn, $ojb_no);

            $ins_h = "
                INSERT INTO tbl_old_jewelry_scrap_invoices (
                    invoice_no, customer_id, customer_name, currency, invoice_date,
                    subtotal, net_total, grand_total, paid_amt, balance_amt,
                    comment, status, created_by, sales_person
                ) VALUES (
                    '$ojb_no_esc', $cust_sql_id, '$customer_name', '$currency', '$voucher_date',
                    $sam, $sam, $sam, $sam, 0.00,
                    '$ojb_comment', 'draft', $created_by_sql, $sales_sql
                )
            ";
            if (!mysqli_query($conn, $ins_h)) {
                throw new Exception('Scrap invoice header failed: ' . mysqli_error($conn));
            }
            $ojb_id = (int)mysqli_insert_id($conn);

            $ins_it = "
                INSERT INTO tbl_old_jewelry_scrap_invoice_items (
                    invoice_id, description, gross_wt, final_wt, net_wt, pure_wt,
                    amount, net_amt, quantity, purity, rate
                ) VALUES (
                    $ojb_id, '$desc_item_esc', $sw, $sw, $snet, $spure,
                    $sam, $sam, $sqty, $purity_num, $srate
                )
            ";
            if (!mysqli_query($conn, $ins_it)) {
                throw new Exception('Scrap invoice item failed: ' . mysqli_error($conn));
            }

            if ($t_ojb_p && mysqli_num_rows($t_ojb_p) > 0) {
                $pv_no_esc = mysqli_real_escape_string($conn, $voucher_no);
                $ins_pm = "
                    INSERT INTO tbl_old_jewelry_scrap_invoice_payments (
                        invoice_id, payment_type, deposit_into, transaction_no, amount
                    ) VALUES (
                        $ojb_id, 'Payment Voucher', NULL, '$pv_no_esc', $sam
                    )
                ";
                if (!mysqli_query($conn, $ins_pm)) {
                    throw new Exception('Scrap invoice payment row failed: ' . mysqli_error($conn));
                }
            }

            $scrap_invoice_numbers[] = $ojb_no;
            $party_against_display .= ($party_against_display !== '' ? ', ' : '') . $ojb_no . '(' . number_format($sam, 2) . 'Dr)';
        }
    }

    $pv_branch_for_ledger = 0;
    if ($has_pv_branch) {
        $pvb = getRecord("SELECT branch_id FROM tbl_payment_vouchers WHERE id = $voucher_id LIMIT 1");
        $pv_branch_for_ledger = (int) ($pvb['branch_id'] ?? 0);
    }
    if ($pv_branch_for_ledger <= 0 && $eff_branch > 0) {
        $pv_branch_for_ledger = (int) $eff_branch;
    }
    auragold_ensure_customer_ledger_branch_column($conn);
    $ledger_has_branch_col = auragold_tbl_has_column($conn, 'tbl_customer_ledger', 'branch_id');
    $ledger_branch_sql_col = $ledger_has_branch_col ? ', branch_id' : '';
    $ledger_branch_sql_val = ($ledger_has_branch_col && $pv_branch_for_ledger > 0)
        ? ', ' . (int) $pv_branch_for_ledger
        : ($ledger_has_branch_col ? ', NULL' : '');
    $ledger_br_scope = auragold_customer_ledger_branch_scope_sql($conn, $pv_branch_for_ledger);

    // Post to customer ledger so payment voucher shows in customer ledger and affects previous balance
    // IMPORTANT: When updating a voucher, delete its old ledger entry FIRST so "last balance" is the
    // balance before this voucher (e.g. 2000). Then new_balance = 2000 - new_total (e.g. 0) = 2000.
    // Otherwise we'd read last_balance = 1500 and compute 1500 - 0 = 1500 (wrong).
    mysqli_query($conn, "
        DELETE FROM tbl_customer_ledger
        WHERE transaction_type = 'payment_voucher' AND transaction_id = $voucher_id AND status = 1
    ");

    $has_gold_pure_cols = false;
    $gpc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_customer_ledger LIKE 'debit_gold_pure'");
    if ($gpc && mysqli_num_rows($gpc) > 0) {
        $has_gold_pure_cols = true;
    }
    $gold_pure_select = $has_gold_pure_cols ? ", balance_gold_pure" : "";

    $ledger_customer_id = $customer_id > 0 ? $customer_id : 0;
    $last_balance = null;
    if ($ledger_customer_id > 0) {
        $last_balance = getRecord("
            SELECT balance_amount, balance_gold, balance_silver $gold_pure_select
            FROM tbl_customer_ledger
            WHERE customer_id = $ledger_customer_id AND status = 1
            $ledger_br_scope
            ORDER BY transaction_date DESC, id DESC
            LIMIT 1
        ");
    }
    if (!$last_balance && !empty($customer_name)) {
        $last_balance = getRecord("
            SELECT balance_amount, balance_gold, balance_silver $gold_pure_select
            FROM tbl_customer_ledger
            WHERE customer_name = '$customer_name' AND status = 1
            $ledger_br_scope
            ORDER BY transaction_date DESC, id DESC
            LIMIT 1
        ");
        if (!$last_balance) {
            $last_balance = getRecord("
                SELECT balance_amount, balance_gold, balance_silver
                FROM tbl_customer_balance
                WHERE customer_name = '$customer_name' LIMIT 1
            ");
        }
    }
    $prev_amt = (float)($last_balance['balance_amount'] ?? 0);
    $prev_gold = (float)($last_balance['balance_gold'] ?? 0);
    $prev_silver = (float)($last_balance['balance_silver'] ?? 0);
    $prev_gold_pure = $has_gold_pure_cols ? (float)($last_balance['balance_gold_pure'] ?? 0) : 0;
    // Metal exchange: post weight on Credit side (gold / silver), same balance math as before
    $ledger_metal_gold = ($total_gold_from_items > 0) ? $total_gold_from_items : $total_gold;
    $ledger_metal_silver = ($total_silver_from_items > 0) ? $total_silver_from_items : $total_silver;
    // Payment Voucher: money on party Debit side; metal exchange on Credit gold/silver columns.
    // Cash/Bank lines below use Credit (money out).
    // balance_amount follows CL = opening + Dr − Cr → Debit increases running balance.
    $new_balance_amt = $prev_amt + $total_amount;
    $new_balance_gold = $prev_gold + $ledger_metal_gold;
    $new_balance_silver = $prev_silver + $ledger_metal_silver;
    $new_balance_gold_pure = $prev_gold_pure + $total_gold_pure;

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : null);
    $ref_sql = $ref_no ? "'$ref_no'" : 'NULL';
    $desc = "Payment Voucher: $voucher_no";
    if ($ledger_metal_gold > 0 || $ledger_metal_silver > 0) {
        $desc .= " (Hedging)";
    }
    $has_against = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_customer_ledger LIKE 'against_ledger'");
    $ledger_has_against_cols = ($has_against && mysqli_num_rows($has_against) > 0);
    $against_cols = $ledger_has_against_cols ? ", against_ledger, against_invoice_no" : "";
    $party_against_inv = !empty($scrap_invoice_numbers)
        ? implode(', ', $scrap_invoice_numbers)
        : ($ref_no !== '' ? $ref_no : $voucher_no);
    if ($ledger_has_against_cols) {
        if ($party_against_display !== '') {
            $against_vals = ", '" . mysqli_real_escape_string($conn, $party_against_display) . "', '" . mysqli_real_escape_string($conn, $party_against_inv) . "'";
        } else {
            $against_vals = ", NULL, NULL";
        }
    } else {
        $against_vals = "";
    }
    $gold_pure_cols = $has_gold_pure_cols ? ", debit_gold_pure, credit_gold_pure, balance_gold_pure" : "";
    $gold_pure_vals = $has_gold_pure_cols ? ", 0, " . (float)$total_gold_pure . ", " . (float)$new_balance_gold_pure : "";

    $desc_esc_led = mysqli_real_escape_string($conn, $desc);
    $ledger_sql = "
        INSERT INTO tbl_customer_ledger (
            customer_id" . $ledger_branch_sql_col . ", customer_name, transaction_type, transaction_id, transaction_no,
            transaction_date, debit_amount, credit_amount,
            debit_gold, credit_gold, debit_silver, credit_silver,
            balance_amount, balance_gold, balance_silver
            $gold_pure_cols
            , description, reference_no, status, created_by, created_at
            $against_cols
        ) VALUES (
            $ledger_customer_id" . $ledger_branch_sql_val . ",
            '$customer_name',
            'payment_voucher',
            $voucher_id,
            '$voucher_no',
            '$voucher_date',
            $total_amount,
            0,
            0,
            " . (float)$ledger_metal_gold . ",
            0,
            " . (float)$ledger_metal_silver . ",
            $new_balance_amt,
            $new_balance_gold,
            $new_balance_silver
            $gold_pure_vals
            , '$desc_esc_led',
            $ref_sql,
            1,
            " . ($user_id ? $user_id : 'NULL') . ",
            NOW()
            $against_vals
        )
    ";
    if (!mysqli_query($conn, $ledger_sql)) {
        throw new Exception('Customer ledger entry failed: ' . mysqli_error($conn));
    }

    // Cash / Bank / etc.: credit payment method (money out)
    $ledger_has_against = $ledger_has_against_cols;
    $against_inv_esc = mysqli_real_escape_string($conn, $ref_no !== '' ? $ref_no : $voucher_no);
    $voucher_no_db = mysqli_real_escape_string($conn, $voucher_no);
    $voucher_date_db = mysqli_real_escape_string($conn, $voucher_date);

    if (is_array($items) && count($items) > 0) {
        foreach ($items as $item) {
            $pt = strtolower(trim($item['payment_type'] ?? ''));
            $line_amt = (float)($item['amount'] ?? 0);
            $dep_raw = trim($item['deposit_into'] ?? '');
            if ($dep_raw === '' && $pt === 'cash') {
                $dep_raw = 'Cash';
            }
            if ($line_amt <= 0 || $dep_raw === '') {
                continue;
            }
            $dep_esc = esc($dep_raw);
            if (!in_array($pt, $pv_money_types, true)) {
                continue;
            }

            $cash_balance_record = getRecord("
                SELECT balance_amount 
                FROM tbl_customer_ledger 
                WHERE customer_name = '$dep_esc' 
                AND status = 1 
                $ledger_br_scope
                ORDER BY transaction_date DESC, id DESC 
                LIMIT 1
            ");
            $cash_prev_balance = (float)($cash_balance_record['balance_amount'] ?? 0);
            $cash_new_balance = $cash_prev_balance - $line_amt;
            $sl_ledger = mysqli_real_escape_string($conn, accountledger_against_party_payment_label($customer_name, $pt, $line_amt, 'Dr'));
            $cash_desc_esc = mysqli_real_escape_string($conn, "Payment to {$customer_name} (Payment Voucher {$voucher_no})");

            if ($ledger_has_against) {
                $cash_ledger_sql = "
                    INSERT INTO tbl_customer_ledger (
                        customer_id" . $ledger_branch_sql_col . ", customer_name, transaction_type, transaction_id, transaction_no,
                        transaction_date, debit_amount, credit_amount,
                        balance_amount, balance_gold, balance_silver,
                        description, reference_no, status, created_by, created_at,
                        against_ledger, against_invoice_no
                    ) VALUES (
                        0" . $ledger_branch_sql_val . ",
                        '$dep_esc',
                        'payment_voucher',
                        $voucher_id,
                        '$voucher_no_db',
                        '$voucher_date_db',
                        0,
                        $line_amt,
                        $cash_new_balance,
                        0,
                        0,
                        '$cash_desc_esc',
                        $ref_sql,
                        1,
                        " . ($user_id ? $user_id : 'NULL') . ",
                        NOW(),
                        '$sl_ledger',
                        '$against_inv_esc'
                    )
                ";
            } else {
                $cash_ledger_sql = "
                    INSERT INTO tbl_customer_ledger (
                        customer_id" . $ledger_branch_sql_col . ", customer_name, transaction_type, transaction_id, transaction_no,
                        transaction_date, debit_amount, credit_amount,
                        balance_amount, balance_gold, balance_silver,
                        description, reference_no, status, created_by, created_at
                    ) VALUES (
                        0" . $ledger_branch_sql_val . ",
                        '$dep_esc',
                        'payment_voucher',
                        $voucher_id,
                        '$voucher_no_db',
                        '$voucher_date_db',
                        0,
                        $line_amt,
                        $cash_new_balance,
                        0,
                        0,
                        '$cash_desc_esc',
                        $ref_sql,
                        1,
                        " . ($user_id ? $user_id : 'NULL') . ",
                        NOW()
                    )
                ";
            }
            if (!mysqli_query($conn, $cash_ledger_sql)) {
                throw new Exception('Cash/Bank ledger entry failed: ' . mysqli_error($conn));
            }
        }
    }

    if ((int) $voucher_id > 0) {
        require_once __DIR__ . '/../includes/auragold_voucher_pending_diamond_stone.php';
        auragold_voucher_apply_pending_diamond_stone_from_post($conn, 'payment_voucher', (int) $voucher_id, $voucher_no_db, $voucher_date_db);
    }

    mysqli_commit($conn);

    require_once __DIR__ . '/../includes/auragold_notifications.php';
    auragold_notify_document_saved($conn, [
        'label' => 'Payment Voucher',
        'verb' => $payment_voucher_existed ? 'updated' : 'created',
        'number' => $voucher_no,
        'party' => $customer_name,
        'doc_date' => $voucher_date,
        'due_date' => $due_date,
        'ref_id' => (int) $voucher_id,
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment voucher saved successfully',
        'voucher_id' => $voucher_id,
        'voucher_no' => $voucher_no,
        'new_barcodes' => $metal_exchange_barcodes_out,
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
