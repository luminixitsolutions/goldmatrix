<?php 
session_start();
require_once 'config.php';

/** Negative numeric display: red text, value in parentheses with minus inside e.g. (-1,234.56). */
function accountledger_fmt_red_paren(float $value, int $decimals = 2): string {
    if ($value < 0) {
        return '<span style="color: #dc2626;">(-' . number_format(abs($value), $decimals) . ')</span>';
    }
    return number_format($value, $decimals);
}

/**
 * Branch name from JOIN can be empty when working DB tbl_branches is missing rows or branch_id is legacy NULL/0.
 * Resolve from registry map (same list as filter); NULL/0 branch_id uses main branch label when set.
 *
 * @param array<int, string> $idToName
 */
function accountledger_branch_display_name(array &$rows, array $idToName, int $mainBranchId): void {
    foreach ($rows as &$row) {
        $bn = trim((string) ($row['branch_name'] ?? ''));
        if ($bn !== '' && $bn !== '—') {
            continue;
        }
        $bid = array_key_exists('branch_id', $row) ? (int) ($row['branch_id'] ?? 0) : 0;
        if ($bid > 0 && !empty($idToName[$bid])) {
            $row['branch_name'] = $idToName[$bid];
            continue;
        }
        if ($bid <= 0 && $mainBranchId > 0 && !empty($idToName[$mainBranchId])) {
            $row['branch_name'] = $idToName[$mainBranchId];
        }
    }
    unset($row);
}

// Get filters (multi-select via [] supported; legacy single values still work)
$date_range = isset($_GET['date_range']) ? esc($_GET['date_range']) : '';

$branch_ids = [];
if (isset($_GET['branch_id'])) {
    if (is_array($_GET['branch_id'])) {
        $branch_ids = array_values(array_unique(array_filter(array_map('intval', $_GET['branch_id']))));
    } else {
        $b = (int) $_GET['branch_id'];
        if ($b > 0) {
            $branch_ids = [$b];
        }
    }
}
$branch_id = count($branch_ids) === 1 ? (int) $branch_ids[0] : 0;

/**
 * Branch scope for ledger queries (same idea as transaction-report.php):
 * - Effective login/working branch &gt; 0: that branch only.
 * - Else explicit ?branch_id[]= from Advance Filter (e.g. Select All = all branches).
 * - Else default to registry main branch so main/HQ sessions do not list every sub-branch in one report.
 */
$al_main_branch_id = function_exists('auragold_settings_main_branch_id') ? (int) auragold_settings_main_branch_id() : 0;
$tr_effective_branch_id = function_exists('auragold_effective_branch_id') ? auragold_effective_branch_id() : 0;
if ($tr_effective_branch_id > 0) {
    $tr_resolved_branch_ids = [$tr_effective_branch_id];
} elseif (!empty($branch_ids)) {
    $tr_resolved_branch_ids = $branch_ids;
} elseif ($al_main_branch_id > 0) {
    $tr_resolved_branch_ids = [$al_main_branch_id];
} else {
    $tr_resolved_branch_ids = [];
}

$invoice_no_raw = isset($_GET['invoice_no']) ? trim((string) $_GET['invoice_no']) : '';
$invoice_no = $invoice_no_raw !== '' ? esc($invoice_no_raw) : '';

$bill_to_bill_raw = isset($_GET['bill_to_bill']) ? trim((string) $_GET['bill_to_bill']) : '';
$bill_to_bill = $bill_to_bill_raw !== '' ? esc($bill_to_bill_raw) : '';

$ledger_names_sel = [];
if (isset($_GET['ledger_name'])) {
    if (is_array($_GET['ledger_name'])) {
        foreach ($_GET['ledger_name'] as $n) {
            $n = trim((string) $n);
            if ($n !== '') {
                $ledger_names_sel[] = $n;
            }
        }
    } else {
        $n = trim((string) $_GET['ledger_name']);
        if ($n !== '') {
            $ledger_names_sel[] = $n;
        }
    }
}
$ledger_names_sel = array_values(array_unique($ledger_names_sel));

$group_ids = [];
if (isset($_GET['group'])) {
    if (is_array($_GET['group'])) {
        foreach ($_GET['group'] as $g) {
            $g = trim((string) $g);
            if ($g !== '') {
                $group_ids[] = $g;
            }
        }
    } else {
        $g = trim((string) $_GET['group']);
        if ($g !== '') {
            $group_ids[] = $g;
        }
    }
}
$group_ids = array_values(array_unique($group_ids));
$group = count($group_ids) === 1 ? esc($group_ids[0]) : '';

$ledger_types_sel = [];
$allowed_ledger_types = ['Customer', 'Supplier', 'Account'];
if (isset($_GET['ledger_type'])) {
    if (is_array($_GET['ledger_type'])) {
        foreach ($_GET['ledger_type'] as $lt) {
            $lt = trim((string) $lt);
            if (in_array($lt, $allowed_ledger_types, true)) {
                $ledger_types_sel[] = $lt;
            }
        }
    } else {
        $lt = trim((string) $_GET['ledger_type']);
        if (in_array($lt, $allowed_ledger_types, true)) {
            $ledger_types_sel[] = $lt;
        }
    }
}
$ledger_types_sel = array_values(array_unique($ledger_types_sel));
$ledger_type = count($ledger_types_sel) === 1 ? esc($ledger_types_sel[0]) : '';

$against_inv_no_raw = isset($_GET['against_inv_no']) ? trim((string) $_GET['against_inv_no']) : '';
$against_inv_no = $against_inv_no_raw !== '' ? esc($against_inv_no_raw) : '';

$only_balance = isset($_GET['only_balance']) ? (int) $_GET['only_balance'] : 0;

$ledger_account_raw = isset($_GET['ledger_account']) ? trim((string) $_GET['ledger_account']) : '';

// Parse date range if provided
$from_date = '';
$to_date = '';
if (!empty($date_range)) {
    $dates = explode(' - ', $date_range);
    if (count($dates) == 2) {
        $from_date = trim($dates[0]);
        $to_date = trim($dates[1]);
    }
} else {
    $from_date = isset($_GET['from_date']) ? esc($_GET['from_date']) : '';
    $to_date = isset($_GET['to_date']) ? esc($_GET['to_date']) : '';
}

$search_raw = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$search = $search_raw !== '' ? esc($search_raw) : '';

if ($ledger_account_raw !== '' && empty($ledger_names_sel)) {
    $ledger_names_sel = [$ledger_account_raw];
}
$ledger_account = $ledger_account_raw !== '' ? esc($ledger_account_raw) : '';
$ledger_name = count($ledger_names_sel) === 1 ? esc($ledger_names_sel[0]) : '';

// ========== FIXING TYPE RULES (Metal weight in Account Ledger) ==========
// Fixing Type is stored in source voucher tables per module:
//   Sale Invoice      -> tbl_sale_invoice.fixing_type
//   Purchase Invoice  -> tbl_purchase_invoice.fixing_type
//   Sale Return       -> tbl_sale_return.fixing_type
//   Purchase Return   -> tbl_purchase_return.fixing_type
//   Sale Quotation    -> tbl_sale_quotation.fixing_type
//   Purchase Quotation-> tbl_purchase_quotation.fixing_type
//   Sale Order        -> tbl_sale_order.fixing_type
//   Purchase Order    -> tbl_purchase_order.fixing_type
//   Repair Order      -> tbl_repair_order.fixing_type
// When each module inserts into tbl_customer_ledger, it must append " (Hedging)" to description
// when Fixing Type = 'Hedging'; Standard entries must NOT include that text.
// In this report we do not JOIN to voucher tables; we use the ledger's description to decide:
//   - If description contains '(Hedging)' -> treat as Hedging -> show and sum metal weight.
//   - Otherwise -> treat as Standard -> metal weight is 0, not shown, not in totals.
// Rule: Standard = amount only in ledger. Hedging = amount + metal weight in ledger.
// ========================================================================================
$is_hedging_ledger = false; // set for View All Ledger tab when result set has any Hedging entry

// Active tab
$active_tab = isset($_GET['tab']) ? esc($_GET['tab']) : 'balance';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$offset = ($page - 1) * $per_page;

// Ledgers hidden from this report (dropdown exclusion + WHERE NOT IN below)
$accountledger_report_hidden_ledgers = ['Purchase Fixing Account'];
$accountledger_hidden_in_sql = '';
if (!empty($accountledger_report_hidden_ledgers)) {
    $___h = [];
    foreach ($accountledger_report_hidden_ledgers as $___hn) {
        $___h[] = "'" . mysqli_real_escape_string($conn, $___hn) . "'";
    }
    $accountledger_hidden_in_sql = implode(',', $___h);
}

// Get branches
$branches = getListMaster("SELECT id, name FROM tbl_branches WHERE status = 1 ORDER BY name ASC");
$al_branch_id_to_name = [];
foreach ($branches as $_abr) {
    $aid = (int) ($_abr['id'] ?? 0);
    if ($aid > 0) {
        $al_branch_id_to_name[$aid] = trim((string) ($_abr['name'] ?? ''));
    }
}
$ledger_has_branch_id = isset($conn) && $conn instanceof mysqli && function_exists('auragold_tbl_has_column')
    ? auragold_tbl_has_column($conn, 'tbl_customer_ledger', 'branch_id') : false;

// Legacy rows: branch_id NULL/0 = created before per-branch tagging; treat as main branch when scope includes main.
$al_scope_includes_main_legacy = ($al_main_branch_id > 0 && !empty($tr_resolved_branch_ids) && in_array($al_main_branch_id, $tr_resolved_branch_ids, true));
$al_branch_scope_sql_l = '';
$al_branch_scope_sql_tbl = '';
if ($ledger_has_branch_id && !empty($tr_resolved_branch_ids)) {
    $ids = implode(',', array_map('intval', $tr_resolved_branch_ids));
    if ($al_scope_includes_main_legacy) {
        $al_branch_scope_sql_l = " AND (l.branch_id IN ($ids) OR l.branch_id IS NULL OR l.branch_id = 0)";
        $al_branch_scope_sql_tbl = " AND (branch_id IN ($ids) OR branch_id IS NULL OR branch_id = 0)";
    } else {
        $al_branch_scope_sql_l = " AND l.branch_id IN ($ids)";
        $al_branch_scope_sql_tbl = " AND branch_id IN ($ids)";
    }
}

// Get unique ledger names from customer ledger; common accounts (Cash, Purchase, Sales, etc.) first
$ledger_names_branch_sql = $al_branch_scope_sql_tbl;
$ledger_names_raw = getList("SELECT DISTINCT customer_name FROM tbl_customer_ledger WHERE status = 1" . $ledger_names_branch_sql . ($accountledger_hidden_in_sql !== '' ? " AND customer_name NOT IN ($accountledger_hidden_in_sql)" : '') . " ORDER BY customer_name ASC");
$ledger_priority = ['Cash','Purchase Account','Sales Account','Bank Account','Hedging Account','Discount Received','Manufacturing Account','Making Purchase Account','Making Sale Account','Layaways Fund'];
$ledger_names = [];
foreach ($ledger_priority as $pn) {
    foreach ($ledger_names_raw as $r) {
        if (isset($r['customer_name']) && $r['customer_name'] === $pn) {
            $ledger_names[] = $r;
            break;
        }
    }
}
$added = array_column($ledger_names, 'customer_name');
foreach ($ledger_names_raw as $r) {
    $cn = $r['customer_name'] ?? '';
    if ($cn !== '' && !in_array($cn, $added, true) && !in_array($cn, $accountledger_report_hidden_ledgers, true)) {
        $ledger_names[] = $r;
        $added[] = $cn;
    }
}

// Ledger groups
$ledger_groups = [
    ['id' => 1, 'name' => 'Sundry Debtors'],
    ['id' => 2, 'name' => 'Sundry Creditors'],
    ['id' => 3, 'name' => 'Bank Accounts'],
    ['id' => 4, 'name' => 'Cash'],
    ['id' => 5, 'name' => 'Sales'],
    ['id' => 6, 'name' => 'Purchase'],
];

// Quick filter for common ledger/account types (show first, filter by ledger name)
$ledger_account_options = [
    '' => 'All Ledgers',
    'Cash' => 'Cash',
    'Purchase Account' => 'Purchase Account',
    'Sales Account' => 'Sales Account',
    'Making Sale Account' => 'Making Sale Account',
    'Making Sales Account' => 'Making Sales Account',
    'Tax Ledger' => 'Tax Ledger',
    'Bank Account' => 'Bank Account',
    'Hedging Account' => 'Hedging Account',
    'Discount Received' => 'Discount Received',
    'Manufacturing Account' => 'Manufacturing Account',
    'Making Purchase Account' => 'Making Purchase Account',
];

// Voucher types
$voucher_types = [
    'purchase_invoice' => 'Purchase Invoice',
    'purchase_quotation' => 'Purchase Quotation',
    'purchase_quotation_revenue' => 'Purchase Quotation',
    'sale_order' => 'Sale Order',
    'sale_invoice' => 'Sale Invoice',
    'sale_return' => 'Sale Return',
    'sale_revenue' => 'Sale Revenue',
    'payment' => 'Payment',
    'payment_voucher' => 'Payment Voucher',
    'receipt_voucher' => 'Receipt Voucher',
    'receipt' => 'Receipt',
    'advance' => 'Advance',
    'return' => 'Return',
    'metal_to_amount' => 'Metal To Amount',
    'amount_to_metal' => 'Amount To Metal',
    'investment_fund_transfer' => 'Investment Fund Transfer',
];

$filter_voucher_keys = [];
if (isset($_GET['voucher_type'])) {
    if (is_array($_GET['voucher_type'])) {
        foreach ($_GET['voucher_type'] as $vk) {
            $vk = trim((string) $vk);
            if ($vk !== '' && isset($voucher_types[$vk])) {
                $filter_voucher_keys[] = $vk;
            }
        }
    } else {
        $vk = trim((string) $_GET['voucher_type']);
        if ($vk !== '' && isset($voucher_types[$vk])) {
            $filter_voucher_keys[] = $vk;
        }
    }
}
$filter_voucher_keys = array_values(array_unique($filter_voucher_keys));
$voucher_type = count($filter_voucher_keys) === 1 ? esc($filter_voucher_keys[0]) : '';

$filter_against_voucher_keys = [];
if (isset($_GET['against_voucher_type'])) {
    if (is_array($_GET['against_voucher_type'])) {
        foreach ($_GET['against_voucher_type'] as $vk) {
            $vk = trim((string) $vk);
            if ($vk !== '' && isset($voucher_types[$vk])) {
                $filter_against_voucher_keys[] = $vk;
            }
        }
    } else {
        $vk = trim((string) $_GET['against_voucher_type']);
        if ($vk !== '' && isset($voucher_types[$vk])) {
            $filter_against_voucher_keys[] = $vk;
        }
    }
}
$filter_against_voucher_keys = array_values(array_unique($filter_against_voucher_keys));
$against_voucher_type = count($filter_against_voucher_keys) === 1 ? esc($filter_against_voucher_keys[0]) : '';

// Build WHERE clause (customer ledger)
$where_clause = "l.status = 1";
$tt_conds = [];
if (!empty($ledger_types_sel)) {
    $parts = [];
    foreach ($ledger_types_sel as $lt) {
        $parts[] = "'" . esc($lt) . "'";
    }
    $tt_conds[] = 'l.transaction_type IN (' . implode(',', $parts) . ')';
}
if (!empty($filter_voucher_keys)) {
    $parts = [];
    foreach ($filter_voucher_keys as $vk) {
        $parts[] = "'" . esc($vk) . "'";
    }
    $tt_conds[] = 'l.transaction_type IN (' . implode(',', $parts) . ')';
}
if (count($tt_conds) === 1) {
    $where_clause .= ' AND ' . $tt_conds[0];
} elseif (count($tt_conds) === 2) {
    $where_clause .= ' AND (' . $tt_conds[0] . ' OR ' . $tt_conds[1] . ')';
}
if (!empty($from_date)) {
    $where_clause .= " AND l.transaction_date >= '$from_date'";
}
if (!empty($to_date)) {
    $where_clause .= " AND l.transaction_date <= '$to_date'";
}
if (!empty($invoice_no)) {
    $where_clause .= " AND l.transaction_no LIKE '%$invoice_no%'";
}
if (!empty($ledger_names_sel)) {
    $parts = [];
    foreach ($ledger_names_sel as $ln) {
        $parts[] = "'" . esc($ln) . "'";
    }
    $where_clause .= ' AND l.customer_name IN (' . implode(',', $parts) . ')';
}
if (!empty($against_inv_no)) {
    $where_clause .= " AND l.reference_no LIKE '%$against_inv_no%'";
}
if (!empty($search)) {
    $where_clause .= " AND (l.customer_name LIKE '%$search%' OR l.transaction_no LIKE '%$search%')";
}
if (!empty($accountledger_hidden_in_sql)) {
    $where_clause .= ' AND l.customer_name NOT IN (' . $accountledger_hidden_in_sql . ')';
}
// Hide previous_balance_payment lines: amounts are merged into sale_invoice in save-sale-invoice; legacy rows would double-count in the report.
$where_clause .= " AND COALESCE(l.transaction_type,'') <> 'previous_balance_payment'";
if ($al_branch_scope_sql_l !== '') {
    $where_clause .= $al_branch_scope_sql_l;
}
$where_clause_l2 = str_replace('l.', 'l2.', $where_clause);

// Initialize totals variables
$total_opening = 0;
$total_debit = 0;
$total_credit = 0;
$total_closing = 0;
$all_ledger_data = [];

// Gold pure columns: available for both Balance and View All Ledger tabs
$has_gold_pure = false;
$gc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_customer_ledger LIKE 'debit_gold_pure'");
if ($gc && mysqli_num_rows($gc) > 0) { $has_gold_pure = true; }
if ($gc) mysqli_free_result($gc);

// Central condition: entry is Hedging (Fixing Type = Hedging) when description contains "(Hedging)" (case-insensitive).
// Used everywhere we fetch, display, or total metal weight. Standard entries do not match; their metal is 0.
$hedging_desc_condition = "LOWER(COALESCE(l.description,'')) LIKE '%(hedging)%'";
// View All Ledger: also show metal on payment rows (e.g. Metal Exchange on PI) and RV/PV party rows (sale auto RV with metal exchange).
$payment_metal_condition = "(COALESCE(l.transaction_type,'') = 'payment' AND (ABS(COALESCE(l.debit_gold,0)) + ABS(COALESCE(l.credit_gold,0)) + ABS(COALESCE(l.debit_silver,0)) + ABS(COALESCE(l.credit_silver,0)) > 0.00001))";
$rv_pv_metal_condition = "(COALESCE(l.transaction_type,'') IN ('receipt_voucher','payment_voucher') AND (ABS(COALESCE(l.debit_gold,0)) + ABS(COALESCE(l.credit_gold,0)) + ABS(COALESCE(l.debit_silver,0)) + ABS(COALESCE(l.credit_silver,0)) > 0.00001))";
$ledger_metal_view_condition = "($hedging_desc_condition OR $payment_metal_condition OR $rv_pv_metal_condition)";
// For Balance tab: exclude 'opening' from metal sum; only Hedging entries contribute to metal.
$hedging_case = "COALESCE(l.transaction_type,'') != 'opening' AND ($hedging_desc_condition)";

// For Balance Amounts tab - one row per ledger account (not per branch): group by customer_id + name
// so mixed NULL/0/branch_id for the same party does not duplicate (legacy rows vs per-branch tag).
// Metal weight: only from Hedging entries. Standard = amount only, metal columns show 0.
if ($active_tab == 'balance') {
    // Ledger key: (customer_id > 0) ? one row per id; else nominal accounts (customer_id=0) per name
    $al_ledger_group_expr = '(CASE WHEN l.customer_id > 0 THEN l.customer_id ELSE 0 END), l.customer_name';
    $gold_pure_sql = $has_gold_pure ? "
            COALESCE(SUM(CASE WHEN $hedging_case THEN l.debit_gold_pure ELSE 0 END), 0) as total_debit_gold_pure,
            COALESCE(SUM(CASE WHEN $hedging_case THEN l.credit_gold_pure ELSE 0 END), 0) as total_credit_gold_pure,
    " : "
            0 as total_debit_gold_pure,
            0 as total_credit_gold_pure,
    ";
    if ($ledger_has_branch_id) {
        $al_bal_main_bid = (int) $al_main_branch_id;
        $al_bal_join_main = ($al_bal_main_bid > 0) ? 'LEFT JOIN tbl_branches b_main_lbl ON b_main_lbl.id = ' . $al_bal_main_bid : '';
        $al_bal_branch_name = ($al_bal_main_bid > 0)
            ? 'MAX(COALESCE(b.name, b_main_lbl.name, \'—\')) AS branch_name'
            : 'MAX(COALESCE(b.name, \'—\')) AS branch_name';
        $ledger_query = "
            SELECT 
                l.customer_name as ledger_name,
                MAX(l.customer_id) as customer_id,
                MAX(l.branch_id) as branch_id,
                $al_bal_branch_name,
                COALESCE(SUM(CASE WHEN COALESCE(l.transaction_type,'') != 'opening' THEN l.debit_amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN COALESCE(l.transaction_type,'') != 'opening' THEN l.credit_amount ELSE 0 END), 0) as total_credit,
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.debit_gold ELSE 0 END), 0) as total_debit_gold,
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.credit_gold ELSE 0 END), 0) as total_credit_gold,
                $gold_pure_sql
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.debit_silver ELSE 0 END), 0) as total_debit_silver,
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.credit_silver ELSE 0 END), 0) as total_credit_silver,
                CASE 
                    WHEN MAX(l.customer_id) > 0 THEN 'Customer'
                    ELSE 'Account'
                END as ledger_type,
                MAX(CASE WHEN ($hedging_desc_condition) THEN 1 ELSE 0 END) as has_hedging
            FROM tbl_customer_ledger l
            LEFT JOIN tbl_branches b ON b.id = l.branch_id
            $al_bal_join_main
            WHERE $where_clause
            GROUP BY $al_ledger_group_expr
            ORDER BY l.customer_name ASC
        ";
    } else {
        $ledger_query = "
            SELECT 
                l.customer_name as ledger_name,
                MAX(l.customer_id) as customer_id,
                '—' as branch_name,
                COALESCE(SUM(CASE WHEN COALESCE(l.transaction_type,'') != 'opening' THEN l.debit_amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN COALESCE(l.transaction_type,'') != 'opening' THEN l.credit_amount ELSE 0 END), 0) as total_credit,
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.debit_gold ELSE 0 END), 0) as total_debit_gold,
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.credit_gold ELSE 0 END), 0) as total_credit_gold,
                $gold_pure_sql
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.debit_silver ELSE 0 END), 0) as total_debit_silver,
                COALESCE(SUM(CASE WHEN $hedging_case THEN l.credit_silver ELSE 0 END), 0) as total_credit_silver,
                CASE 
                    WHEN MAX(l.customer_id) > 0 THEN 'Customer'
                    ELSE 'Account'
                END as ledger_type,
                MAX(CASE WHEN ($hedging_desc_condition) THEN 1 ELSE 0 END) as has_hedging
            FROM tbl_customer_ledger l
            WHERE $where_clause
            GROUP BY $al_ledger_group_expr
            ORDER BY l.customer_name ASC
        ";
    }
    
    $ledger_data = getList($ledger_query);
    if ($ledger_has_branch_id && !empty($al_branch_id_to_name)) {
        accountledger_branch_display_name($ledger_data, $al_branch_id_to_name, $al_main_branch_id);
    }

    // Calculate opening and closing balances (one per ledger: same branch scope as main sum — no per-row branch split)
    foreach ($ledger_data as &$ledger) {
        $customer_name = $ledger['ledger_name'];
        $cid_open = (int) ($ledger['customer_id'] ?? 0);
        $ledger_cust_id_sql = ($cid_open > 0) ? ' AND customer_id = ' . $cid_open : '';
        // Match report WHERE on tbl_customer_ledger (incl. legacy NULL/0 when main branch is in scope)
        $ledger_branch_open_sql = $ledger_has_branch_id ? $al_branch_scope_sql_tbl : '';

        // Opening balance: from 'opening' transaction so ledger shows "Opening" column correctly after adding opening amount.
        // When date range is set, use last balance before from_date; when no date range, use opening transaction only.
        // Metal opening: only from Hedging entries (Standard fixing type does not contribute to metal balance).
        $opening_amt = 0;
        $opening_gold = 0;
        $opening_gold_pure = 0;
        $opening_silver = 0;
        $opening_select = "balance_amount, balance_gold, balance_silver";
        if ($has_gold_pure) $opening_select .= ", balance_gold_pure";
        if (!empty($from_date)) {
            $opening_query = "
                SELECT $opening_select
                FROM tbl_customer_ledger
                WHERE customer_name = '" . mysqli_real_escape_string($conn, $customer_name) . "'
                AND status = 1
                $ledger_cust_id_sql
                $ledger_branch_open_sql
                AND transaction_date < '$from_date'
                ORDER BY transaction_date DESC, id DESC
                LIMIT 1
            ";
            $opening_balance = getRecord($opening_query);
            if ($opening_balance) {
                $opening_amt = (float)$opening_balance['balance_amount'];
                $opening_gold = (float)($opening_balance['balance_gold'] ?? 0);
                $opening_gold_pure = $has_gold_pure ? (float)($opening_balance['balance_gold_pure'] ?? 0) : 0;
                $opening_silver = (float)($opening_balance['balance_silver'] ?? 0);
            }
            // Metal opening: sum only Hedging entries before from_date (balance_gold in DB mixes Standard+Hedging)
            $cust_esc = mysqli_real_escape_string($conn, $customer_name);
            // Opening metal: only from Hedging entries (Standard fixing type does not contribute)
            $opening_metal_row = getRecord("
                SELECT
                    COALESCE(SUM(debit_gold - credit_gold), 0) as opening_gold,
                    " . ($has_gold_pure ? "COALESCE(SUM(debit_gold_pure - credit_gold_pure), 0) as opening_gold_pure," : "0 as opening_gold_pure,") . "
                    COALESCE(SUM(debit_silver - credit_silver), 0) as opening_silver
                FROM tbl_customer_ledger
                WHERE customer_name = '$cust_esc' AND status = 1 $ledger_cust_id_sql $ledger_branch_open_sql AND transaction_date < '$from_date'
                AND LOWER(COALESCE(description,'')) LIKE '%(hedging)%'
            ");
            if ($opening_metal_row) {
                $opening_gold = (float)($opening_metal_row['opening_gold'] ?? 0);
                $opening_gold_pure = $has_gold_pure ? (float)($opening_metal_row['opening_gold_pure'] ?? 0) : 0;
                $opening_silver = (float)($opening_metal_row['opening_silver'] ?? 0);
            }
        } else {
            $opening_row_select = "balance_amount, balance_gold, balance_silver, debit_amount, credit_amount, debit_gold, credit_gold, debit_silver, credit_silver, description";
            if ($has_gold_pure) $opening_row_select .= ", balance_gold_pure, debit_gold_pure, credit_gold_pure";
            $opening_row = getRecord("
                SELECT $opening_row_select
                FROM tbl_customer_ledger
                WHERE customer_name = '" . mysqli_real_escape_string($conn, $customer_name) . "'
                AND status = 1
                $ledger_cust_id_sql
                $ledger_branch_open_sql
                AND transaction_type = 'opening'
                ORDER BY transaction_date DESC, id DESC
                LIMIT 1
            ");
            if ($opening_row) {
                $ob = (float)($opening_row['balance_amount'] ?? 0);
                if ($ob != 0) {
                    $opening_amt = $ob;
                } else {
                    $dr = (float)($opening_row['debit_amount'] ?? 0);
                    $cr = (float)($opening_row['credit_amount'] ?? 0);
                    $opening_amt = $dr > 0 ? $dr : -$cr;
                }
                // Metal opening: only if opening entry is Hedging (description contains '(Hedging)')
                $opening_desc = $opening_row['description'] ?? '';
                if (stripos($opening_desc, '(Hedging)') !== false) {
                    $opening_gold = (float)($opening_row['balance_gold'] ?? 0);
                    $opening_gold_pure = $has_gold_pure ? (float)($opening_row['balance_gold_pure'] ?? 0) : 0;
                    $opening_silver = (float)($opening_row['balance_silver'] ?? 0);
                    if ($ob == 0) {
                        $opening_gold = (float)($opening_row['debit_gold'] ?? 0) - (float)($opening_row['credit_gold'] ?? 0);
                        if ($has_gold_pure) $opening_gold_pure = (float)($opening_row['debit_gold_pure'] ?? 0) - (float)($opening_row['credit_gold_pure'] ?? 0);
                        $opening_silver = (float)($opening_row['debit_silver'] ?? 0) - (float)($opening_row['credit_silver'] ?? 0);
                    }
                }
            }
        }
        
        // Closing amount (money): opening + debit - credit.
        $closing_amt = $opening_amt + $ledger['total_debit'] - $ledger['total_credit'];
        // Gold / silver closing columns: period net = debit - credit (same rule as View All Ledger).
        $closing_gold = (float)$ledger['total_debit_gold'] - (float)$ledger['total_credit_gold'];
        $closing_gold_pure = (float)($ledger['total_debit_gold_pure'] ?? 0) - (float)($ledger['total_credit_gold_pure'] ?? 0);
        $closing_silver = (float)$ledger['total_debit_silver'] - (float)$ledger['total_credit_silver'];
        
        // Determine Cr/Dr (when opening is 0 show "0" for CrOrDr)
        $opening_crdr = ($opening_amt == 0) ? '0' : ($opening_amt > 0 ? 'Dr' : 'Cr');
        $closing_crdr = ($closing_amt == 0) ? '0' : ($closing_amt > 0 ? 'Dr' : 'Cr');
        
        $ledger['opening_amt'] = abs($opening_amt);
        $ledger['opening_crdr'] = $opening_crdr;
        $ledger['opening_gold'] = $opening_gold;
        $ledger['opening_gold_pure'] = $opening_gold_pure;
        $ledger['opening_silver'] = $opening_silver;
        $ledger['closing_amt'] = abs($closing_amt);
        $ledger['closing_amt_signed'] = $closing_amt; // signed: debit - credit (for display: negative in red)
        $ledger['closing_crdr'] = $closing_crdr;
        $ledger['closing_gold'] = $closing_gold;
        $ledger['closing_gold_pure'] = $closing_gold_pure;
        $ledger['closing_silver'] = $closing_silver;
        // When gold_pure columns don't exist in DB, use gross values for Pure row (same as purity wt stored in main gold cols)
        if (!$has_gold_pure) {
            $ledger['opening_gold_pure'] = $ledger['opening_gold'];
            $ledger['total_credit_gold_pure'] = $ledger['total_credit_gold'];
            $ledger['total_debit_gold_pure'] = $ledger['total_debit_gold'];
            $ledger['closing_gold_pure'] = $ledger['closing_gold'];
        }
    }
    unset($ledger);
    
    // Calculate totals (before pagination, from all data)
    $all_ledger_data = $ledger_data; // Keep full dataset for totals
    foreach ($all_ledger_data as $ledger) {
        $total_opening += ($ledger['opening_crdr'] === '0' ? 0 : ($ledger['opening_crdr'] == 'Dr' ? $ledger['opening_amt'] : -$ledger['opening_amt']));
        $total_debit += $ledger['total_debit'];
        $total_credit += $ledger['total_credit'];
        $closing_signed = isset($ledger['closing_amt_signed']) ? $ledger['closing_amt_signed'] : ($ledger['closing_crdr'] == 'Dr' ? $ledger['closing_amt'] : -$ledger['closing_amt']);
        $total_closing += $closing_signed;
    }
    
    // Pagination
    $total_records = count($all_ledger_data);
    $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
    $ledger_data = array_slice($all_ledger_data, $offset, $per_page);
    
} else {
    // View All Ledger tab - Transaction-level entries only, no JOINs (avoids row multiplication)
    // Show only entries where selected ledger (customer_name) is directly involved
    $gold_pure_select = $has_gold_pure ? "l.debit_gold_pure as gold_debit_pure, l.credit_gold_pure as gold_credit_pure, l.balance_gold_pure as gold_cl_pure," : "";
    $al_va_main_bid = (int) $al_main_branch_id;
    if ($ledger_has_branch_id) {
        $al_branch_name_expr = ($al_va_main_bid > 0) ? "COALESCE(b.name, b_main_lbl.name, '—')" : "COALESCE(b.name, '—')";
        $al_ledger_from = ($al_va_main_bid > 0)
            ? 'FROM tbl_customer_ledger l LEFT JOIN tbl_branches b ON b.id = l.branch_id LEFT JOIN tbl_branches b_main_lbl ON b_main_lbl.id = ' . $al_va_main_bid
            : 'FROM tbl_customer_ledger l LEFT JOIN tbl_branches b ON b.id = l.branch_id';
    } else {
        $al_branch_name_expr = "'—'";
        $al_ledger_from = 'FROM tbl_customer_ledger l';
    }
    $al_sel_branch_id = $ledger_has_branch_id ? "l.branch_id,\n            " : '';
    $ledger_query = "
        SELECT 
            l.id,
            l.customer_id,
            $al_sel_branch_id
            l.customer_name as ledger_name,
            l.transaction_type,
            l.transaction_id,
            l.transaction_date as date,
            CASE
                WHEN l.transaction_type = 'payment' THEN COALESCE(
                    (SELECT pv.voucher_no FROM tbl_payment_vouchers pv WHERE pv.ref_no = l.transaction_no ORDER BY pv.id DESC LIMIT 1),
                    l.transaction_no
                )
                WHEN l.transaction_type = 'receipt_voucher' THEN COALESCE(
                    (SELECT rv.voucher_no FROM tbl_receipt_vouchers rv WHERE rv.id = l.transaction_id LIMIT 1),
                    l.transaction_no
                )
                WHEN l.transaction_type = 'payment_voucher' THEN COALESCE(
                    (SELECT pv2.voucher_no FROM tbl_payment_vouchers pv2 WHERE pv2.id = l.transaction_id LIMIT 1),
                    l.transaction_no
                )
                ELSE l.transaction_no
            END as invoice_no,
            COALESCE(l.against_ledger, '') as against_ledger,
            COALESCE(l.against_invoice_no, l.reference_no, '') as against_invoice_no,
            COALESCE(l.description, '') as description,
            CASE 
                WHEN l.transaction_type = 'purchase_invoice' THEN 'Purchase Invoice'
                WHEN l.transaction_type = 'purchase_return' THEN 'Purchase Return'
                WHEN l.transaction_type = 'sale_order' THEN 'Sale Order'
                WHEN l.transaction_type = 'sale_invoice' THEN 'Sale Invoice'
                WHEN l.transaction_type = 'sale_return' THEN 'Sale Return'
                WHEN l.transaction_type = 'sale_quotation' THEN 'Sales Quotation'
                WHEN l.transaction_type = 'sale_quotation_revenue' THEN 'Sales Quotation'
                WHEN l.transaction_type = 'purchase_quotation' THEN 'Purchase Quotation'
                WHEN l.transaction_type = 'purchase_quotation_revenue' THEN 'Purchase Quotation'
                WHEN l.transaction_type = 'quotation_payment' THEN 'Receipt Voucher'
                WHEN l.transaction_type = 'sale_revenue' THEN 'Sale Revenue'
                WHEN l.transaction_type = 'payment' THEN CASE
                    WHEN l.description LIKE '%Receipt from%' THEN 'Sale Receipt'
                    WHEN l.description LIKE '%Payment to %' OR l.description LIKE '%(Payment Voucher%' THEN 'Payment Voucher'
                    WHEN l.description LIKE '%Payment from %' THEN 'Receipt Voucher'
                    ELSE 'Payment'
                END
                WHEN l.transaction_type = 'payment_voucher' THEN 'Payment Voucher'
                WHEN l.transaction_type = 'receipt_voucher' THEN 'Receipt Voucher'
                WHEN l.transaction_type = 'receipt' THEN 'Receipt Voucher'
                WHEN l.transaction_type = 'advance' THEN 'Advance'
                WHEN l.transaction_type = 'return' THEN 'Return'
                WHEN l.transaction_type = 'opening' THEN 'OPENING'
                WHEN l.transaction_type = 'old_jewelry_scrap_invoice' THEN 'Old Jewelry - Scrap Invoice'
                WHEN l.transaction_type = 'old_jewelry_scrap_contra' THEN 'Old Jewelry - Scrap Invoice'
                WHEN l.transaction_type = 'metal_to_amount' THEN 'Metal To Amount'
                WHEN l.transaction_type = 'amount_to_metal' THEN 'Amount To Metal'
                WHEN l.transaction_type = 'investment_fund_transfer' THEN 'Investment Fund Transfer'
                ELSE l.transaction_type
            END as type_of_voucher,
            l.debit_amount,
            l.credit_amount,
            l.balance_amount as cl_amount,
            l.debit_gold as gold_debit_wt,
            l.credit_gold as gold_credit_wt,
            l.balance_gold as gold_cl_wt,
            $gold_pure_select
            l.debit_silver as silver_debit_wt,
            l.credit_silver as silver_credit_wt,
            CASE WHEN LOWER(COALESCE(l.description,'')) LIKE '%(hedging)%' THEN 'Hedging' ELSE 'Standard' END as fixing_type_display,
            $al_branch_name_expr as branch_name,
            CASE 
                WHEN l.customer_id > 0 THEN 'Customer'
                ELSE 'Account'
            END as ledger_type
        $al_ledger_from
        WHERE $where_clause
        ORDER BY l.transaction_date ASC, l.created_at ASC, l.id ASC
    ";
    
    $total_record = getRecord("SELECT COUNT(*) as total FROM tbl_customer_ledger l WHERE $where_clause");
    $total_records = $total_record ? (int)$total_record['total'] : 0;
    $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
    
    $ledger_data = getList($ledger_query . " LIMIT $per_page OFFSET $offset");
    if ($ledger_has_branch_id && !empty($al_branch_id_to_name)) {
        accountledger_branch_display_name($ledger_data, $al_branch_id_to_name, $al_main_branch_id);
    }

    // Running balance before current page (for correct CL. Amount = previous + debit - credit)
    $running_balance = 0;
    if ($offset > 0) {
        $bal_before = getRecord("
            SELECT COALESCE(SUM(sub.debit_amount - sub.credit_amount), 0) as bal
            FROM (SELECT l2.debit_amount, l2.credit_amount FROM tbl_customer_ledger l2
                  WHERE $where_clause_l2 ORDER BY l2.transaction_date ASC, l2.created_at ASC, l2.id ASC LIMIT $offset) sub
        ");
        $running_balance = round((float)($bal_before['bal'] ?? 0), 2);
        if (abs($running_balance) < 0.01) {
            $running_balance = 0;
        }
    }
    // Pre-calculate running CL ($running_balance may already reflect rows before this page when $offset > 0)
    foreach ($ledger_data as &$entry) {
        $debit = (float)($entry['debit_amount'] ?? 0);
        $credit = (float)($entry['credit_amount'] ?? 0);

        $transaction_type = strtolower((string)($entry['transaction_type'] ?? ''));
        $against = strtolower((string)($entry['against_ledger'] ?? ''));
        $desc_lc = strtolower((string)($entry['description'] ?? ''));

        $scrap_hint = (strpos($against, 'scrap') !== false || strpos($desc_lc, 'scrap') !== false);

        // Scrap payment: party line may be debit-only (or mirrored Dr/Cr); running CL must treat it as receipt (credit reduces balance)
        if ($transaction_type === 'payment' && $scrap_hint) {
            if ($debit > 0.00001 && $credit < 0.00001) {
                $credit = $debit;
                $debit = 0.0;
            } elseif ($debit > 0.00001 && $credit > 0.00001 && abs($debit - $credit) < 0.01) {
                $credit = max($debit, $credit);
                $debit = 0.0;
            }
        }

        // previous_balance_payment (sale paid from PB): sometimes posted debit-only; for running CL use credit effect
        if ($transaction_type === 'previous_balance_payment' && $debit > 0.00001 && $credit < 0.00001) {
            $credit = $debit;
            $debit = 0.0;
        }

        $running_balance = round($running_balance + $debit - $credit, 2);
        if (abs($running_balance) < 0.01) {
            $running_balance = 0;
        }
        $entry['running_cl'] = $running_balance;
    }
    unset($entry);

    // Sale invoice (party row): legacy rows used Cash in against_ledger — rebuild from sale_revenue lines on same transaction_id.
    $si_against_by_tid = [];
    $si_tid_need = [];
    foreach ($ledger_data as $entry) {
        $tt = (string) ($entry['transaction_type'] ?? '');
        $da = (float) ($entry['debit_amount'] ?? 0);
        $ca = (float) ($entry['credit_amount'] ?? 0);
        $al = trim((string) ($entry['against_ledger'] ?? ''));
        $ledger_nm = (string) ($entry['ledger_name'] ?? '');
        if ($tt === 'sale_invoice' && $ledger_nm !== 'Hedging Account' && $da > 0.00001 && $ca < 0.00001
            && ($al === '' || preg_match('/^cash\(/i', $al))) {
            $tid = (int) ($entry['transaction_id'] ?? 0);
            if ($tid > 0) {
                $si_tid_need[$tid] = true;
            }
        }
    }
    if (!empty($si_tid_need)) {
        $in_list = implode(',', array_map('intval', array_keys($si_tid_need)));
        $al_rev_branch = $al_branch_scope_sql_tbl;
        $rev_rows = getList("SELECT transaction_id, customer_name, credit_amount, id FROM tbl_customer_ledger WHERE status = 1 AND transaction_type = 'sale_revenue' AND credit_amount > 0.00001 AND transaction_id IN ($in_list)$al_rev_branch ORDER BY transaction_id ASC, id ASC");
        if (is_array($rev_rows)) {
            foreach ($rev_rows as $rr) {
                $tid = (int) ($rr['transaction_id'] ?? 0);
                $name = trim((string) ($rr['customer_name'] ?? ''));
                $amt = (float) ($rr['credit_amount'] ?? 0);
                if ($tid <= 0 || $name === '' || $amt <= 0) {
                    continue;
                }
                $piece = $name . '(' . number_format($amt, 2) . 'Cr)';
                $si_against_by_tid[$tid] = isset($si_against_by_tid[$tid]) && $si_against_by_tid[$tid] !== ''
                    ? $si_against_by_tid[$tid] . ', ' . $piece
                    : $piece;
            }
        }
    }

    // PI scrap settlement: stored as party Debit for correct CL math; user-facing report shows amount under Credit (same net effect as other payments).
    foreach ($ledger_data as &$entry) {
        $da = (float)($entry['debit_amount'] ?? 0);
        $ca = (float)($entry['credit_amount'] ?? 0);
        $al = (string)($entry['against_ledger'] ?? '');
        $desc = (string)($entry['description'] ?? '');
        $tt = (string)($entry['transaction_type'] ?? '');
        $ledger_nm = (string) ($entry['ledger_name'] ?? '');
        $is_pi_scrap_party = ($tt === 'payment')
            && stripos($desc, 'Purchase Invoice') !== false
            && $da > 0.00001
            && $ca < 0.00001
            && preg_match('/^\s*scrap\s*\(/i', $al);
        $is_si_scrap_party = ($tt === 'payment')
            && stripos($desc, 'Sale Invoice') !== false
            && $ca > 0.00001
            && $da < 0.00001
            && preg_match('/^\s*scrap\s*\(/i', $al);
        if ($is_pi_scrap_party) {
            $entry['display_debit_amount'] = 0.0;
            $entry['display_credit_amount'] = $da;
            $entry['against_ledger_display'] = preg_replace('/Dr\)/i', 'Cr)', $al);
        } elseif ($is_si_scrap_party) {
            // Mirror of PI scrap: party stored on opposite column; show scrap under Debit on sale ledger.
            $entry['display_debit_amount'] = $ca;
            $entry['display_credit_amount'] = 0.0;
            $entry['against_ledger_display'] = preg_replace('/Cr\)/i', 'Dr)', $al);
        } else {
            $entry['display_debit_amount'] = $da;
            $entry['display_credit_amount'] = $ca;
            $entry['against_ledger_display'] = $al;
            if ($tt === 'sale_invoice' && $ledger_nm !== 'Hedging Account' && $da > 0.00001 && $ca < 0.00001) {
                $al_trim = trim($al);
                if ($al_trim === '' || preg_match('/^cash\(/i', $al_trim)) {
                    $tid = (int) ($entry['transaction_id'] ?? 0);
                    if ($tid > 0 && !empty($si_against_by_tid[$tid])) {
                        $entry['against_ledger_display'] = $si_against_by_tid[$tid];
                    }
                    if (trim((string) ($entry['against_invoice_no'] ?? '')) === '' && trim((string) ($entry['invoice_no'] ?? '')) !== '') {
                        $entry['against_invoice_no'] = $entry['invoice_no'];
                    }
                }
            }
        }
    }
    unset($entry);
    
    // Calculate totals for View All Ledger tab
    $total_debit_all = 0;
    $total_credit_all = 0;
    $total_cl_amount_all = 0;
    $total_gold_debit_wt_all = 0;
    $total_gold_credit_wt_all = 0;
    $total_gold_cl_wt_all = 0;
    $total_gold_debit_pure_all = 0;
    $total_gold_credit_pure_all = 0;
    $total_gold_cl_pure_all = 0;
    $total_silver_debit_wt_all = 0;
    $total_silver_credit_wt_all = 0;
    $total_silver_cl_wt_all = 0;
    
    // Metal totals: Hedging lines + payment lines with stored weight (Metal Exchange, etc.).
    $hedging_metal_sql = "
        SUM(CASE WHEN ($ledger_metal_view_condition) THEN l.debit_gold ELSE 0 END) as total_gold_debit_hedging,
        SUM(CASE WHEN ($ledger_metal_view_condition) THEN l.credit_gold ELSE 0 END) as total_gold_credit_hedging,
        " . ($has_gold_pure ? "
        SUM(CASE WHEN ($ledger_metal_view_condition) THEN l.debit_gold_pure ELSE 0 END) as total_gold_debit_pure_hedging,
        SUM(CASE WHEN ($ledger_metal_view_condition) THEN l.credit_gold_pure ELSE 0 END) as total_gold_credit_pure_hedging,
        " : "0 as total_gold_debit_pure_hedging, 0 as total_gold_credit_pure_hedging, ") . "
        SUM(CASE WHEN ($ledger_metal_view_condition) THEN l.debit_silver ELSE 0 END) as total_silver_debit_hedging,
        SUM(CASE WHEN ($ledger_metal_view_condition) THEN l.credit_silver ELSE 0 END) as total_silver_credit_hedging,
        MAX(CASE WHEN ($ledger_metal_view_condition) THEN 1 ELSE 0 END) as has_hedging
    ";
    if ($has_gold_pure) {
        $totals_query = "
            SELECT 
                SUM(l.debit_amount) as total_debit,
                SUM(l.credit_amount) as total_credit,
                MAX(l.balance_amount) as max_balance,
                SUM(l.debit_gold) as total_gold_debit,
                SUM(l.credit_gold) as total_gold_credit,
                MAX(l.balance_gold) as max_gold_balance,
                SUM(l.debit_gold_pure) as total_gold_debit_pure,
                SUM(l.credit_gold_pure) as total_gold_credit_pure,
                $hedging_metal_sql
            FROM tbl_customer_ledger l
            WHERE $where_clause
        ";
    } else {
        $totals_query = "
            SELECT 
                SUM(l.debit_amount) as total_debit,
                SUM(l.credit_amount) as total_credit,
                MAX(l.balance_amount) as max_balance,
                SUM(l.debit_gold) as total_gold_debit,
                SUM(l.credit_gold) as total_gold_credit,
                MAX(l.balance_gold) as max_gold_balance,
                0 as total_gold_debit_pure,
                0 as total_gold_credit_pure,
                $hedging_metal_sql
            FROM tbl_customer_ledger l
            WHERE $where_clause
        ";
    }
    $totals_result = getRecord($totals_query);
    if ($totals_result) {
        $total_debit_all = (float)($totals_result['total_debit'] ?? 0);
        $total_credit_all = (float)($totals_result['total_credit'] ?? 0);
        $total_cl_amount_all = $total_debit_all - $total_credit_all;
        $is_hedging_ledger = !empty($totals_result['has_hedging']);
        // Metal totals: hedging and/or payment rows with weight (e.g. Metal Exchange).
        $total_gold_debit_wt_all = $is_hedging_ledger ? (float)($totals_result['total_gold_debit_hedging'] ?? 0) : 0;
        $total_gold_credit_wt_all = $is_hedging_ledger ? (float)($totals_result['total_gold_credit_hedging'] ?? 0) : 0;
        $total_gold_debit_pure_all = $is_hedging_ledger ? (float)($totals_result['total_gold_debit_pure_hedging'] ?? 0) : 0;
        $total_gold_credit_pure_all = $is_hedging_ledger ? (float)($totals_result['total_gold_credit_pure_hedging'] ?? 0) : 0;
        // Gold CL footer: total debit - total credit (hedging lines only), same as per-row CL = debit - credit.
        $total_gold_cl_wt_all = $is_hedging_ledger ? ($total_gold_debit_wt_all - $total_gold_credit_wt_all) : 0;
        $total_gold_cl_pure_all = $is_hedging_ledger ? ($total_gold_debit_pure_all - $total_gold_credit_pure_all) : 0;
        $total_silver_debit_wt_all = $is_hedging_ledger ? (float)($totals_result['total_silver_debit_hedging'] ?? 0) : 0;
        $total_silver_credit_wt_all = $is_hedging_ledger ? (float)($totals_result['total_silver_credit_hedging'] ?? 0) : 0;
        $total_silver_cl_wt_all = $is_hedging_ledger ? ($total_silver_debit_wt_all - $total_silver_credit_wt_all) : 0;
        // Totals row: align with displayed columns (PI scrap party lines shown under Credit, not Debit).
        $pi_scrap_party_sum_row = getRecord("
            SELECT COALESCE(SUM(l.debit_amount), 0) AS s
            FROM tbl_customer_ledger l
            WHERE $where_clause
            AND l.transaction_type = 'payment'
            AND l.debit_amount > 0
            AND ABS(l.credit_amount) < 0.00001
            AND LOWER(l.description) LIKE '%purchase invoice%'
            AND LOWER(TRIM(l.against_ledger)) LIKE 'scrap(%'
        ");
        $pi_scrap_party_total = (float)($pi_scrap_party_sum_row['s'] ?? 0);
        $total_debit_display_all = $total_debit_all - $pi_scrap_party_total;
        $total_credit_display_all = $total_credit_all + $pi_scrap_party_total;
        // Footer CL must match visible Debit/Credit totals (debit − credit), not raw DB sums when display is adjusted.
        $total_cl_footer_all = $total_debit_display_all - $total_credit_display_all;
    } else {
        $total_debit_display_all = $total_debit_all;
        $total_credit_display_all = $total_credit_all;
        $total_cl_footer_all = $total_debit_display_all - $total_credit_display_all;
    }
}

// Ensure total_records is always set
if (!isset($total_records)) {
    $total_records = 0;
}
if (!isset($total_pages)) {
    $total_pages = 1;
}

/**
 * Build URL query for pagination and tab links (supports array filter params).
 */
function al_build_query(array $overrides = []) {
    global $active_tab, $per_page, $page, $date_range, $from_date, $to_date,
        $invoice_no_raw, $tr_resolved_branch_ids, $bill_to_bill_raw, $ledger_names_sel, $group_ids,
        $ledger_types_sel, $filter_voucher_keys, $filter_against_voucher_keys,
        $against_inv_no_raw, $only_balance, $search_raw, $ledger_account_raw;
    $q = [];
    if (!empty($date_range)) {
        $q['date_range'] = $date_range;
    } else {
        if (!empty($from_date)) {
            $q['from_date'] = $from_date;
        }
        if (!empty($to_date)) {
            $q['to_date'] = $to_date;
        }
    }
    if ($invoice_no_raw !== '') {
        $q['invoice_no'] = $invoice_no_raw;
    }
    if (!empty($tr_resolved_branch_ids)) {
        $q['branch_id'] = $tr_resolved_branch_ids;
    }
    if ($bill_to_bill_raw !== '') {
        $q['bill_to_bill'] = $bill_to_bill_raw;
    }
    if (!empty($ledger_names_sel)) {
        $q['ledger_name'] = $ledger_names_sel;
    }
    if (!empty($group_ids)) {
        $q['group'] = $group_ids;
    }
    if (!empty($ledger_types_sel)) {
        $q['ledger_type'] = $ledger_types_sel;
    }
    if (!empty($filter_voucher_keys)) {
        $q['voucher_type'] = $filter_voucher_keys;
    }
    if (!empty($filter_against_voucher_keys)) {
        $q['against_voucher_type'] = $filter_against_voucher_keys;
    }
    if ($against_inv_no_raw !== '') {
        $q['against_inv_no'] = $against_inv_no_raw;
    }
    if ($only_balance) {
        $q['only_balance'] = 1;
    }
    if ($search_raw !== '') {
        $q['search'] = $search_raw;
    }
    if ($ledger_account_raw !== '') {
        $q['ledger_account'] = $ledger_account_raw;
    }
    $q['per_page'] = $per_page;
    $q = array_merge($q, $overrides);
    foreach ($q as $qk => $qv) {
        if ($qv === null) {
            unset($q[$qk]);
        }
    }
    if (!isset($q['tab'])) {
        $q['tab'] = $active_tab;
    }
    if (!isset($q['page'])) {
        $q['page'] = max(1, (int) $page);
    }
    return '?' . http_build_query($q);
}

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Account Ledger Report - AuraGold Software</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php';?>
    <link rel="stylesheet" href="style.css">
</head>

<style>
html, body {
    overflow-x: hidden !important;
    height: 100vh;
    background: #f4f6fb;
    /* font-family: 'Segoe UI', Arial, sans-serif; */
}

.layout-content {
    height: calc(100vh - 60px);
    overflow-y: auto;
    overflow-x: hidden;
}

.container-fluid {
    height: 100%;
    overflow: visible;
    display: flex;
    flex-direction: column;
    padding: 0;
}

/* Page Header */


.page-header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.page-header-actions .btn-icon {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.page-header-actions .btn-icon:hover {
    background: rgba(255,255,255,0.3);
}

.page-header-actions .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc2626;
    color: #fff;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Tabs */
.tabs-container {
    background: #fff;
    border-bottom: 2px solid #e2e8f0;
    padding: 0 20px;
}

.tabs-list {
    display: flex;
    gap: 0;
    margin: 0;
    padding: 0;
    list-style: none;
}

.tabs-list li {
    margin: 0;
}

.tab-link {
    display: block;
    padding: 4px 10px;
    color: #64748b;
    text-decoration: none;
    border-bottom: 2px solid #c5a864;
    transition: all 0.2s;
    font-weight: 500;
}

.tab-link:hover {
    color: #11294b;
    background: #f8fafc;
}



.tabs-ledger-filter {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px;
    flex-wrap: wrap;
}
.tabs-ledger-filter label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}
.tabs-ledger-filter .ledger-account-select {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12px;
    min-width: 180px;
}
.tabs-ledger-filter .ledger-filter-hint {
    font-size: 12px;
    color: #94a3b8;
}

/* Toolbar */
.toolbar {
    background: #fff;
    padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.toolbar-left {
    display: flex;
    gap: 10px;
    align-items: center;
}

.toolbar-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.btn-filter {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.btn-filter:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.btn-export {
    background: #11294b;
    border: none;
    color: #fff;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.btn-export:hover {
    background: #4a2b7c;
}

/* Table Container */


/* DataTables Controls Visibility */
.dataTables_wrapper {
    width: 100%;
    overflow: visible !important;
}
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dt-buttons,
.dataTables_wrapper .dataTables_length {
    display: flex !important;
    visibility: visible !important;
    padding: 10px 0;
}
.dataTables_wrapper .dataTables_filter input {
    min-width: 200px;
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
}
.dataTables_wrapper .dataTables_info {
    padding: 10px 0;
}

/* Fixed/sticky table header row */
.table-container {
    position: relative;
}
#ledgerTable thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #11294b;
    color: #fff;
    box-shadow: 0 1px 0 0 rgba(0,0,0,0.1);
    white-space: nowrap;
}
#ledgerTable thead th:first-child {
    z-index: 3;
}

/* Vertical lines between columns (View | Date | Ledger | …) */
#ledgerTable {
    border-collapse: collapse;
}
#ledgerTable thead th:not(:first-child) {
    border-left: 1px solid rgba(255, 255, 255, 0.25);
}
#ledgerTable tbody td:not(:first-child),
#ledgerTable tfoot td:not(:first-child) {
    border-left: 1px solid #cbd5e1;
}

#ledgerTable thead th {
    padding: 2px 5px;
}

#ledgerTable thead th.alr-th-reorder .alr-th-drag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    margin-left: 0.35rem;
    margin-right: 0.1rem;
    cursor: grab;
    color: #c9a962;
    line-height: 1;
    flex-shrink: 0;
}

#ledgerTable thead th.alr-th-reorder .alr-th-drag .feather {
    width: 0.95rem;
    height: 0.95rem;
}

#ledgerTable thead th.alr-th-reorder .alr-th-drag:active {
    cursor: grabbing;
}

.alr-sortable-ghost {
    opacity: 0.45;
}

.alr-sortable-chosen {
    opacity: 0.9;
}

.table {
    width: 100%;
    margin: 0;
    font-size: 12px;
}
.table tbody td {
    padding: 7px;
}


.table th[data-col].col-hidden,
.table td[data-col].col-hidden {
    display: none !important;
}

/* Light green / pista background from Gold Open. Gross (or first gold column) to end */
#ledgerTable .col-pista {
    background-color: #e2f0e0 !important;
}
#ledgerTable thead th.col-pista {
    background-color: #c5dfc5 !important;
    color: #1a2e1a;
}
#ledgerTable tbody tr:hover td.col-pista {
    background-color: #d4e9d2 !important;
}
#ledgerTable tbody tr.table-footer-total td.col-pista {
    background-color: #d4e9d2 !important;
}



.table tbody tr:hover {
    background: #f8fafc;
}

.table tbody tr.total-row {
    background: #f1edff;
    font-weight: 600;
}

.table tbody tr.total-row td {
    border-top: 2px solid #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
}

.btn-view-all {
    background: #11294b;
    color: #fff;
    border: none;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    font-weight: 500;
}

.btn-view-all:hover {
    background: #4a2b7c;
}

.crdr-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.crdr-badge.dr {
    background: #fee2e2;
    color: #dc2626;
}

.crdr-badge.cr {
    background: #dbeafe;
    color: #2563eb;
}

/* Total Row in Footer */
.table-footer-total {
    background: #f1edff;
    font-weight: 600;
    border-top: 2px solid #e2e8f0;
}

.table-footer-total td {
    padding: 12px;
    border-bottom: 2px solid #e2e8f0;
}

/* Pagination */
.pagination-container {
    background: #fff;
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0 20px 20px 20px;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.pagination-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.per-page-dropdown {
    position: relative;
}

.per-page-dropdown select {
    padding: 6px 30px 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 12px;
    color: #64748b;
    background: #fff;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 35px;
}

.per-page-dropdown select:hover {
    border-color: #cbd5e1;
}

.pagination-info {
    color: #64748b;
    font-size: 12px;
}

.pagination {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination .page-link {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    color: #64748b;
    text-decoration: none;
    border-radius: 4px;
    font-size: 12px;
}

.pagination .page-link:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.pagination .page-link.active {
    background: #11294b;
    color: #fff;
    border-color: #11294b;
}

.pagination .page-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Filter Modal */
.filter-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.filter-modal.active {
    display: flex;
}

.filter-modal-content {
    background: #fff;
    border-radius: 8px;
    padding: 0;
    width: min(960px, calc(100vw - 32px));
    max-width: 960px;
    max-height: 90vh;
    overflow: visible;
}

.filter-modal-header {
    background: #11294b;
    color: #fff;
    padding: 15px 20px;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
    border-bottom: none;
}

.filter-modal-header h5 {
    margin: 0;
    color: #fff;
    font-weight: 600;
}

.filter-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #fff;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.filter-modal-close:hover {
    color: #f0f0f0;
}

.filter-modal-body {
    padding: 20px;
    max-height: calc(90vh - 120px);
    overflow-x: visible;
    overflow-y: auto;
}

#filterModal .mp-ms-panel {
    z-index: 1100;
}

#filterModal .filter-grid .al-adv-section.filter-field-full {
    grid-column: 1 / -1;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    margin: 12px 0 6px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}

#filterModal .filter-grid .al-adv-section.filter-field-full:first-child {
    margin-top: 0;
}

#filterModal .filter-field > .filter-field-label {
    margin: 0;
    color: #435474;
    font-weight: 600;
    font-size: 13px;
}

#filterModal .al-date-to-clear-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    min-width: 0;
}

#filterModal .al-date-to-clear-wrap input[type="date"] {
    flex: 1;
    min-width: 0;
}

#filterModal .al-date-to-clear-wrap .btn {
    flex-shrink: 0;
    height: 34px;
    white-space: nowrap;
}

.filter-form-group.full-width {
    grid-column: 1 / -1;
}

.date-range-input {
    position: relative;
}

.date-range-input input {
    padding-right: 60px;
}

.date-range-icons {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 5px;
}

.date-range-icons i {
    color: #64748b;
    cursor: pointer;
    font-size: 16px;
}

.date-range-icons i:hover {
    color: #11294b;
}


.filter-form-group {
    margin-bottom: 15px;
}



.filter-form-group input,
.filter-form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12px;
}

.filter-modal-footer {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}

.btn-cancel {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-apply {
    background: linear-gradient(135deg, #11294b 0%, #7c5ba8 100%);
    border: none;
    color: #fff;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.btn-apply:hover {
    background: linear-gradient(135deg, #4a2b7c 0%, #6c4b98 100%);
}

.btn-clear {
    background: #fff;
    border: 1px solid #ec4899;
    color: #ec4899;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.btn-clear:hover {
    background: #fdf2f8;
}

.dropdown-columns { position: relative; }
.dropdown-columns-menu {
    position: fixed;
    min-width: 260px;
    max-height: 400px;
    overflow-y: auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    border: 1px solid #e2e8f0;
    z-index: 9999;
    padding: 0;
}
.dropdown-columns-menu.show { display: block !important; }
.dropdown-columns-header {
    padding: 12px 14px;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12px;
}
.dropdown-columns-body {
    padding: 10px 14px;
    max-height: 320px;
    overflow-y: auto;
}
.dropdown-columns-body label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 0;
    cursor: pointer;
    font-size: 13px;
    color: #ffffff;
    white-space: nowrap;
}
.dropdown-columns-body label:hover { color: #1e293b; }
.dropdown-columns-footer {
    padding: 10px 14px;
    border-top: 1px solid #e2e8f0;
}
.btn-reset-cols {
    font-size: 12px;
    color: #11294b;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 0;
}
.btn-reset-cols:hover { text-decoration: underline; }
</style>

<body>
<?php include 'sidebar.php'; ?>

<div class="layout-content">
<div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">

<!-- Page Header -->
<div class="page-header-bar">
    <div>Account Ledger Report</div>
    <div class="page-header-actions">
        <button class="btn-icon" onclick="openFilterModal()" title="Filter">
            <i class="feather icon-filter"></i>
            <?php 
            $filter_count = 0;
            if (!empty($date_range) || !empty($from_date) || !empty($to_date)) $filter_count++;
            if (!empty($invoice_no)) $filter_count++;
            if (!empty($tr_resolved_branch_ids)) $filter_count++;
            if (!empty($bill_to_bill)) $filter_count++;
            if (!empty($ledger_names_sel)) $filter_count++;
            if (!empty($group_ids)) $filter_count++;
            if (!empty($ledger_types_sel)) $filter_count++;
            if (!empty($filter_voucher_keys)) $filter_count++;
            if (!empty($filter_against_voucher_keys)) $filter_count++;
            if (!empty($against_inv_no)) $filter_count++;
            if (!empty($only_balance)) $filter_count++;
            if (!empty($search)) $filter_count++;
            if ($filter_count > 0): ?>
            <span class="badge"><?php echo $filter_count; ?></span>
            <?php endif; ?>
        </button>
        <button class="btn-icon" onclick="location.reload()" title="Refresh">
            <i class="feather icon-refresh-cw"></i>
        </button>
        <div class="dropdown">
            <button class="btn-icon" onclick="event.stopPropagation(); this.nextElementSibling.classList.toggle('show')" title="Export">
                <i class="feather icon-download"></i>
            </button>
            <div class="dropdown-menu" style="display: none;">
                <a class="dropdown-item" href="#" onclick="exportToExcel()">Export to Excel</a>
                <a class="dropdown-item" href="#" onclick="exportToPDF()">Export to PDF</a>
            </div>
        </div>
        <div class="dropdown dropdown-columns">
            <button class="btn-icon" id="columnSettingsBtn" onclick="toggleColumnDropdown(event)" title="Show/Hide Columns">
                <i class="feather icon-settings"></i>
            </button>
            <div class="dropdown-menu dropdown-columns-menu" id="columnSettingsDropdown" style="display: none;">
                <div class="dropdown-columns-header">Show / Hide Columns</div>
                <div class="dropdown-columns-body" id="columnCheckboxesContainer">
                    <!-- Populated by JS -->
                </div>
                <div class="dropdown-columns-footer">
                    <button type="button" class="btn-reset-cols" onclick="resetColumnVisibility()">Show All</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs + Ledger Account Quick Filter -->
<div class="tabs-container">
    <ul class="tabs-list">
        <li>
            <a href="<?php echo htmlspecialchars(al_build_query(['tab' => 'balance', 'page' => 1])); ?>" 
               class="tab-link <?php echo $active_tab == 'balance' ? 'active' : ''; ?>">
                Balance Amounts
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars(al_build_query(['tab' => 'all', 'page' => 1])); ?>" 
               class="tab-link <?php echo $active_tab == 'all' ? 'active' : ''; ?>">
                View All Ledger
            </a>
        </li>
    </ul>
    <div class="tabs-ledger-filter">
        <label>Ledger:</label>
        <select class="ledger-account-select" onchange="alLedgerQuickChange(this)">
            <?php foreach ($ledger_account_options as $opt_val => $opt_label): ?>
            <option value="<?php echo htmlspecialchars($opt_val); ?>" <?php echo $ledger_name == $opt_val ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($opt_label); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <span class="ledger-filter-hint">Or choose from Advance Filter</span>
        <div class="datatable-controls-bar" style="background: #fff; padding: 3px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #e2e8f0;">
    <div class="datatable-search-box">
        <input type="text" id="customSearch" class="form-control" placeholder="Search records..." style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 4px; min-width: 250px; font-size: 13px;">
    </div>
    <div class="datatable-action-buttons" style="display: flex; gap: 8px;">
        <button type="button" id="exportExcelBtn" class="btn btn-success btn-sm" style="padding: 8px 16px; background: #11294b; color: #fff; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
            <i class="feather icon-download"></i> Export Excel
        </button>
        <button type="button" id="printBtn" class="btn btn-secondary btn-sm" style="padding: 8px 16px; background: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
            <i class="feather icon-printer"></i> Print
        </button>
    </div>
</div>
    </div>
</div>



<!-- Table Container -->
<div class="table-container">
<table id="ledgerTable" class="table table-striped" data-pista-from="<?php echo $active_tab == 'balance' ? '9' : '10'; ?>" data-al-tab="<?php echo htmlspecialchars($active_tab, ENT_QUOTES, 'UTF-8'); ?>">
        <thead>
            <tr>
                <?php if ($active_tab == 'balance'): ?>
                    <th data-col="0" style="width: 100px;"></th>
                    <th data-col="1">Ledger</th>
                    <th data-col="2">Branch Name</th>
                    <th data-col="3">Opening Amt CrOrDr</th>
                    <th data-col="4">Opening Amount</th>
                    <th data-col="5">Debit Amount</th>
                    <th data-col="6">Credit Amount</th>
                    <th data-col="7">Closing Amt CrOrDr</th>
                    <th data-col="8">Closing Amount</th>
                    <th data-col="9">Gold Open. Gross</th><th data-col="10">Gold Credit Gross</th><th data-col="11">Gold Debit Gross</th><th data-col="12">Gold Closing Gross</th>
                    <th data-col="13">Gold Open. Pure</th><th data-col="14">Gold Credit Pure</th><th data-col="15">Gold Debit Pure</th><th data-col="16">Gold Closing Pure</th>
                    <th data-col="17">Silver Open. Gross</th><th data-col="18">Silver Credit Gross</th><th data-col="19">Silver Debit Gross</th><th data-col="20">Silver Closing Gross</th>
                    <th data-col="21">Silver Open. Pure</th><th data-col="22">Silver Credit Pure</th><th data-col="23">Silver Debit Pure</th><th data-col="24">Silver Closing Pure</th>
                    <th data-col="25">Plat. Open. Gross</th><th data-col="26">Plat. Credit Gross</th><th data-col="27">Plat. Debit Gross</th><th data-col="28">Plat. Closing Gross</th>
                    <th data-col="29">Plat. Open. Pure</th><th data-col="30">Plat. Credit Pure</th><th data-col="31">Plat. Debit Pure</th><th data-col="32">Plat. Closing Pure</th>
                    <th data-col="33">Imit. Open. Gross</th><th data-col="34">Imit. Credit Gross</th><th data-col="35">Imit. Debit Gross</th><th data-col="36">Imit. Closing Gross</th>
                    <th data-col="37">Imit. Open. Pure</th><th data-col="38">Imit. Credit Pure</th><th data-col="39">Imit. Debit Pure</th><th data-col="40">Imit. Closing Pure</th>
                    <th data-col="41">Other Open. Gross</th><th data-col="42">Other Credit Gross</th><th data-col="43">Other Debit Gross</th><th data-col="44">Other Closing Gross</th>
                    <th data-col="45">Other Open. Pure</th><th data-col="46">Other Credit Pure</th><th data-col="47">Other Debit Pure</th><th data-col="48">Other Closing Pure</th>
                    <th data-col="49">Diamond Open Ct</th><th data-col="50">Diamond Debit Ct</th><th data-col="51">Diamond Credit Ct</th><th data-col="52">Diamond Closing Ct</th>
                    <th data-col="53">Gemstone Open Ct</th><th data-col="54">Gemstone Debit Ct</th><th data-col="55">Gemstone Credit Ct</th><th data-col="56">Gemstone Closing Ct</th>
                    <th data-col="57">Ledger Type</th>
                <?php else: ?>
                    <th data-col="0" style="width: 80px;"></th>
                    <th data-col="1">Date</th>
                    <th data-col="2">Ledger</th>
                    <th data-col="3">Invoice No</th>
                    <th data-col="4">Against Ledger</th>
                    <th data-col="5">Against Invoice No</th>
                    <th data-col="6">Type Of Voucher</th>
                    <th data-col="6b">Fixing Type</th>
                    <th data-col="7">Debit</th>
                    <th data-col="8">Credit</th>
                    <th data-col="9">CL. Amount</th>
                    <th data-col="10">Gold Debit Gross</th>
                    <th data-col="11">Gold Credit Gross</th>
                    <th data-col="12">Gold CL. Gross</th>
                    <?php if ($has_gold_pure): ?>
                    <th data-col="13">Gold Debit Pure</th>
                    <th data-col="14">Gold Credit Pure</th>
                    <th data-col="15">Gold CL. Pure</th>
                    <?php endif; ?>
                    <th data-col="16">Silver Debit Wt</th>
                    <th data-col="17">Silver Credit Wt</th>
                    <th data-col="18">Silver CL. Wt</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($active_tab == 'balance'): ?>
                <?php if (!empty($ledger_data)): ?>
                    <?php foreach ($ledger_data as $ledger): ?>
                    <tr>
                        <td data-col="0"><button class="btn-view-all" onclick="viewLedgerDetails('<?php echo htmlspecialchars($ledger['ledger_name']); ?>', <?php echo $ledger['customer_id']; ?>)">View All</button></td>
                        <td data-col="1"><?php echo htmlspecialchars($ledger['ledger_name']); ?></td>
                        <td data-col="2"><?php echo htmlspecialchars($ledger['branch_name']); ?></td>
                        <td data-col="3"><?php if ($ledger['opening_crdr'] === '0') { echo '0'; } else { ?><span class="crdr-badge <?php echo strtolower($ledger['opening_crdr']); ?>"><?php echo number_format($ledger['opening_amt'], 2) . ' ' . $ledger['opening_crdr']; ?></span><?php } ?></td>
                        <td data-col="4"><?php
                        if ($ledger['opening_crdr'] === '0') {
                            echo number_format(0, 2);
                        } elseif ($ledger['opening_crdr'] === 'Cr') {
                            echo '<span style="color: #dc2626;">(-' . number_format($ledger['opening_amt'], 2) . ')</span>';
                        } else {
                            echo number_format($ledger['opening_amt'], 2);
                        }
                        ?></td>
                        <td data-col="5"><?php echo number_format($ledger['total_debit'], 2); ?></td>
                        <td data-col="6"><?php echo number_format($ledger['total_credit'], 2); ?></td>
                        <td data-col="7"><?php if ($ledger['closing_crdr'] === '0') { echo '0'; } else { ?><span class="crdr-badge <?php echo strtolower($ledger['closing_crdr']); ?>"><?php echo number_format($ledger['closing_amt'], 2) . ' ' . $ledger['closing_crdr']; ?></span><?php } ?></td>
                        <td data-col="8"><?php
                        $closing_signed = isset($ledger['closing_amt_signed']) ? (float)$ledger['closing_amt_signed'] : ($ledger['closing_crdr'] === 'Cr' ? -$ledger['closing_amt'] : $ledger['closing_amt']);
                        echo accountledger_fmt_red_paren($closing_signed, 2);
                        ?></td>
                        <?php
                        // Fixing Type: show metal only when this ledger has at least one Hedging entry. Standard-only ledgers: metal columns = 0.
                        $show_metal = !empty($ledger['has_hedging']);
                        ?>
                        <td data-col="9"><?php echo number_format($show_metal ? $ledger['opening_gold'] : 0, 3); ?></td><td data-col="10"><?php echo number_format($show_metal ? $ledger['total_credit_gold'] : 0, 3); ?></td><td data-col="11"><?php echo number_format($show_metal ? $ledger['total_debit_gold'] : 0, 3); ?></td><td data-col="12"><?php
                        $cg = $show_metal ? (float)$ledger['closing_gold'] : 0;
                        echo accountledger_fmt_red_paren($cg, 3);
                        ?></td>
                        <td data-col="13"><?php echo number_format($show_metal ? (isset($ledger['opening_gold_pure']) ? $ledger['opening_gold_pure'] : $ledger['opening_gold']) : 0, 3); ?></td><td data-col="14"><?php echo number_format($show_metal ? (isset($ledger['total_credit_gold_pure']) ? $ledger['total_credit_gold_pure'] : $ledger['total_credit_gold']) : 0, 3); ?></td><td data-col="15"><?php echo number_format($show_metal ? (isset($ledger['total_debit_gold_pure']) ? $ledger['total_debit_gold_pure'] : $ledger['total_debit_gold']) : 0, 3); ?></td><td data-col="16"><?php
                        $cl_gold_pure = $show_metal ? (isset($ledger['closing_gold_pure']) ? (float)$ledger['closing_gold_pure'] : (float)$ledger['closing_gold']) : 0;
                        echo accountledger_fmt_red_paren($cl_gold_pure, 3);
                        ?></td>
                        <td data-col="17"><?php echo number_format($show_metal ? $ledger['opening_silver'] : 0, 3); ?></td><td data-col="18"><?php echo number_format($show_metal ? $ledger['total_credit_silver'] : 0, 3); ?></td><td data-col="19"><?php echo number_format($show_metal ? $ledger['total_debit_silver'] : 0, 3); ?></td><td data-col="20"><?php
                        $cs = $show_metal ? (float)$ledger['closing_silver'] : 0;
                        echo accountledger_fmt_red_paren($cs, 3);
                        ?></td>
                        <td data-col="21"><?php echo number_format($show_metal ? $ledger['opening_silver'] : 0, 3); ?></td><td data-col="22"><?php echo number_format($show_metal ? $ledger['total_credit_silver'] : 0, 3); ?></td><td data-col="23"><?php echo number_format($show_metal ? $ledger['total_debit_silver'] : 0, 3); ?></td><td data-col="24"><?php
                        echo accountledger_fmt_red_paren($cs, 3);
                        ?></td>
                        <td data-col="25">0.000</td><td data-col="26">0.000</td><td data-col="27">0.000</td><td data-col="28">0.000</td><td data-col="29">0.000</td><td data-col="30">0.000</td><td data-col="31">0.000</td><td data-col="32">0.000</td>
                        <td data-col="33">0.000</td><td data-col="34">0.000</td><td data-col="35">0.000</td><td data-col="36">0.000</td><td data-col="37">0.000</td><td data-col="38">0.000</td><td data-col="39">0.000</td><td data-col="40">0.000</td>
                        <td data-col="41">0.000</td><td data-col="42">0.000</td><td data-col="43">0.000</td><td data-col="44">0.000</td><td data-col="45">0.000</td><td data-col="46">0.000</td><td data-col="47">0.000</td><td data-col="48">0.000</td>
                        <td data-col="49">0.000</td><td data-col="50">0.000</td><td data-col="51">0.000</td><td data-col="52">0.000</td>
                        <td data-col="53">0.000</td><td data-col="54">0.000</td><td data-col="55">0.000</td><td data-col="56">0.000</td>
                        <td data-col="57"><?php echo htmlspecialchars($ledger['ledger_type']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="59" style="text-align: center; padding: 40px; color: #64748b;">
                            No ledger data found
                        </td>
                    </tr>
                <?php endif; ?>
            <?php else: ?>
                <!-- View All Ledger Tab -->
                <?php if (!empty($ledger_data)): ?>
                    <?php foreach ($ledger_data as $entry): ?>
                    <tr>
                        <td data-col="0">
                            <?php 
                            $invoice_no_raw = $entry['invoice_no'] ?? '';
                            $transaction_type_raw = $entry['transaction_type'] ?? '';
                            $transaction_id = isset($entry['transaction_id']) ? (int)$entry['transaction_id'] : 0;
                            $invoice_no_attr = htmlspecialchars($invoice_no_raw, ENT_QUOTES, 'UTF-8');
                            $transaction_type_attr = htmlspecialchars($transaction_type_raw, ENT_QUOTES, 'UTF-8');
                            ?>
                            <button class="btn-view-all view-transaction-btn" data-invoice-no="<?php echo $invoice_no_attr; ?>" data-transaction-type="<?php echo $transaction_type_attr; ?>" data-transaction-id="<?php echo $transaction_id; ?>">View</button>
                        </td>
                        <td data-col="1"><?php echo $entry['date'] ? date('d/m/Y', strtotime($entry['date'])) : ''; ?></td>
                        <td data-col="2"><?php echo htmlspecialchars($entry['ledger_name']); ?></td>
                        <td data-col="3"><?php echo htmlspecialchars($invoice_no_raw); ?></td>
                        <td data-col="4"><?php echo htmlspecialchars($entry['against_ledger_display'] ?? $entry['against_ledger'] ?? ''); ?></td>
                        <td data-col="5"><?php echo htmlspecialchars($entry['against_invoice_no'] ?? ''); ?></td>
                        <td data-col="6"><?php echo htmlspecialchars($entry['type_of_voucher'] ?? ''); ?></td>
                        <td data-col="6b"><?php echo htmlspecialchars($entry['fixing_type_display'] ?? 'Standard'); ?></td>
                        <td data-col="7" style="color: #11294b;"><?php echo number_format((float)($entry['display_debit_amount'] ?? $entry['debit_amount'] ?? 0), 2); ?></td>
                        <td data-col="8" style="color: #11294b;"><?php echo number_format((float)($entry['display_credit_amount'] ?? $entry['credit_amount'] ?? 0), 2); ?></td>
                        <?php 
                        $cl_val = (float)($entry['running_cl'] ?? $entry['cl_amount'] ?? 0);
                        $cl_style = $cl_val < 0 ? 'background: #f1edff; color: #dc2626;' : 'background: #f1edff;';
                        $cl_display = $cl_val < 0 ? '(-' . number_format(abs($cl_val), 2) . ')' : number_format($cl_val, 2);
                        ?>
                        <td data-col="9" style="<?php echo $cl_style; ?>"><?php echo $cl_display; ?></td>
                        <?php 
                        // Hedging; payment rows with weight; receipt/payment voucher party rows (e.g. sale RV + Metal Exchange).
                        $entry_is_hedging = (stripos($entry['description'] ?? '', '(Hedging)') !== false);
                        $entry_metal_wts = abs((float)($entry['gold_debit_wt'] ?? 0)) + abs((float)($entry['gold_credit_wt'] ?? 0))
                            + abs((float)($entry['silver_debit_wt'] ?? 0)) + abs((float)($entry['silver_credit_wt'] ?? 0));
                        $entry_payment_metal = (($entry['transaction_type'] ?? '') === 'payment') && ($entry_metal_wts > 0.00001);
                        $entry_rv_pv_metal = in_array(($entry['transaction_type'] ?? ''), ['receipt_voucher', 'payment_voucher'], true)
                            && ($entry_metal_wts > 0.00001);
                        $show_metal_for_row = $entry_is_hedging || $entry_payment_metal || $entry_rv_pv_metal;
                        ?>
                        <td data-col="10"><?php echo number_format($show_metal_for_row ? $entry['gold_debit_wt'] : 0, 3); ?></td>
                        <td data-col="11"><?php echo number_format($show_metal_for_row ? $entry['gold_credit_wt'] : 0, 3); ?></td>
                        <td data-col="12" style="background: #d1fae5;"><?php
                        $gdw = $show_metal_for_row ? (float)($entry['gold_debit_wt'] ?? 0) : 0;
                        $gcw = $show_metal_for_row ? (float)($entry['gold_credit_wt'] ?? 0) : 0;
                        $gcl_wt_row = $gdw - $gcw;
                        echo accountledger_fmt_red_paren($gcl_wt_row, 3);
                        ?></td>
                        <?php if ($has_gold_pure): 
                            $gdp = $show_metal_for_row ? (float)($entry['gold_debit_pure'] ?? 0) : 0;
                            $gcp = $show_metal_for_row ? (float)($entry['gold_credit_pure'] ?? 0) : 0;
                            $gcl = $gdp - $gcp;
                        ?>
                        <td data-col="13"><?php echo number_format($gdp, 3); ?></td>
                        <td data-col="14"><?php echo number_format($gcp, 3); ?></td>
                        <td data-col="15" style="background: #d1fae5;"><?php echo accountledger_fmt_red_paren($gcl, 3); ?></td>
                        <?php endif; ?>
                        <?php
                        $sdw = $show_metal_for_row ? (float)($entry['silver_debit_wt'] ?? 0) : 0;
                        $scw = $show_metal_for_row ? (float)($entry['silver_credit_wt'] ?? 0) : 0;
                        $scl = $sdw - $scw;
                        ?>
                        <td data-col="16"><?php echo number_format($sdw, 3); ?></td>
                        <td data-col="17"><?php echo number_format($scw, 3); ?></td>
                        <td data-col="18" style="background: #d1fae5;"><?php echo accountledger_fmt_red_paren($scl, 3); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-footer-total">
                        <td data-col="0" colspan="8"><strong>Total</strong></td>
                        <td data-col="7"><strong><?php echo number_format($total_debit_display_all ?? $total_debit_all, 2); ?></strong></td>
                        <td data-col="8"><strong><?php echo number_format($total_credit_display_all ?? $total_credit_all, 2); ?></strong></td>
                        <?php 
                        $total_cl_footer = isset($total_cl_footer_all) ? (float) $total_cl_footer_all : (float) ($total_cl_amount_all ?? 0);
                        $total_cl_style = $total_cl_footer < 0 ? 'color: #dc2626;' : '';
                        $total_cl_display = $total_cl_footer < 0 ? '(-' . number_format(abs($total_cl_footer), 2) . ')' : number_format($total_cl_footer, 2);
                        ?>
                        <td data-col="9" style="<?php echo $total_cl_style; ?>"><strong><?php echo $total_cl_display; ?></strong></td>
                        <?php $show_metal_totals = (!empty($ledger_name) || $is_hedging_ledger); ?>
                        <td data-col="10"><strong><?php echo number_format($show_metal_totals ? $total_gold_debit_wt_all : 0, 3); ?></strong></td>
                        <td data-col="11"><strong><?php echo number_format($show_metal_totals ? $total_gold_credit_wt_all : 0, 3); ?></strong></td>
                        <td data-col="12"><strong><?php echo accountledger_fmt_red_paren($show_metal_totals ? (float)$total_gold_cl_wt_all : 0, 3); ?></strong></td>
                        <?php if ($has_gold_pure): 
                            $tot_cl_pure = $show_metal_totals ? (float)$total_gold_cl_pure_all : 0;
                        ?>
                        <td data-col="13"><strong><?php echo number_format($show_metal_totals ? $total_gold_debit_pure_all : 0, 3); ?></strong></td>
                        <td data-col="14"><strong><?php echo number_format($show_metal_totals ? $total_gold_credit_pure_all : 0, 3); ?></strong></td>
                        <td data-col="15"><strong><?php echo accountledger_fmt_red_paren($tot_cl_pure, 3); ?></strong></td>
                        <?php endif; ?>
                        <td data-col="16"><strong><?php echo number_format($show_metal_totals ? $total_silver_debit_wt_all : 0, 3); ?></strong></td>
                        <td data-col="17"><strong><?php echo number_format($show_metal_totals ? $total_silver_credit_wt_all : 0, 3); ?></strong></td>
                        <td data-col="18"><strong><?php echo accountledger_fmt_red_paren($show_metal_totals ? (float)$total_silver_cl_wt_all : 0, 3); ?></strong></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $has_gold_pure ? 20 : 17; ?>" style="text-align: center; padding: 40px; color: #64748b;">
                            No ledger entries found
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($active_tab == 'balance'): ?>
<!-- Total Row in Footer (Outside table-container) -->
<div style="background: #fff; margin: 0 20px; border-radius: 0 0 8px 8px; border-top: 2px solid #e2e8f0;">
<table id="transactionTable" class="table table-striped">
        <tbody>
            <tr class="table-footer-total">
                <td colspan="3" style="padding: 12px;"><strong>Total</strong></td>
                <td style="padding: 12px;">
                    <?php if ($total_opening == 0) { echo '0'; } else { ?><span class="crdr-badge <?php echo $total_opening >= 0 ? 'dr' : 'cr'; ?>"><?php echo $total_opening >= 0 ? 'Dr' : 'Cr'; ?></span><?php } ?>
                </td>
                <td style="padding: 12px;"><strong><?php echo number_format(abs($total_opening), 2); ?></strong></td>
                <td style="padding: 12px;"><strong><?php echo number_format($total_debit, 2); ?></strong></td>
                <td style="padding: 12px;"><strong><?php echo number_format($total_credit, 2); ?></strong></td>
                <td style="padding: 12px;">0</td>
                <td style="padding: 12px;"><strong><?php
                echo accountledger_fmt_red_paren((float)$total_closing, 2);
                ?></strong></td>
                <td colspan="49" style="padding: 12px;"></td>
            </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Pagination -->
<div class="pagination-container">
    <div class="pagination-info">
        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries
    </div>
    <div class="pagination-right">
        <div class="per-page-dropdown">
            <select onchange="changePerPage(this.value)">
                <option value="5" <?php echo $per_page == 5 ? 'selected' : ''; ?>>Show 5 Items</option>
                <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>Show 10 Items</option>
                <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>Show 25 Items</option>
                <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>Show 50 Items</option>
                <option value="75" <?php echo $per_page == 75 ? 'selected' : ''; ?>>Show 75 Items</option>
                <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>Show 100 Items</option>
                <option value="999999" <?php echo $per_page >= 999999 ? 'selected' : ''; ?>>Show All Items</option>
            </select>
        </div>
        <div class="pagination">
            <?php if ($total_pages > 0): ?>
            <a href="<?php echo htmlspecialchars(al_build_query(['page' => 1])); ?>" 
               class="page-link <?php echo $page == 1 ? 'disabled' : ''; ?>">&lt;&lt;</a>
            <a href="<?php echo htmlspecialchars(al_build_query(['page' => max(1, $page - 1)])); ?>" 
               class="page-link <?php echo $page == 1 ? 'disabled' : ''; ?>">&lt;</a>
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
            <a href="<?php echo htmlspecialchars(al_build_query(['page' => $i])); ?>" 
               class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <a href="<?php echo htmlspecialchars(al_build_query(['page' => min($total_pages, $page + 1)])); ?>" 
               class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">&gt;</a>
            <a href="<?php echo htmlspecialchars(al_build_query(['page' => $total_pages])); ?>" 
               class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">&gt;&gt;</a>
            <?php endif; ?>
        </div>
</div>

</div>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="filter-modal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h5>Advance Filter</h5>
            <button class="filter-modal-close" onclick="closeFilterModal()">&times;</button>
        </div>
        <div class="filter-modal-body">
            <form method="GET" action="" id="filterForm">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                
                <div class="filter-grid">
                    <div class="al-adv-section filter-field-full">Date range</div>
                    <div class="filter-field">
                        <label for="alFromDate">From Date</label>
                        <input type="date" name="from_date" id="alFromDate" value="<?php echo htmlspecialchars($from_date); ?>" class="form-control" title="Click to open calendar">
                    </div>
                    <div class="filter-field">
                        <label for="alToDate">To Date</label>
                        <div class="al-date-to-clear-wrap">
                            <input type="date" name="to_date" id="alToDate" value="<?php echo htmlspecialchars($to_date); ?>" class="form-control" title="Click to open calendar">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDateRange()" title="Clear dates"><i class="feather icon-refresh-cw"></i> Clear</button>
                        </div>
                    </div>

                    <div class="al-adv-section filter-field-full">Filters</div>
                    <div class="filter-field">
                        <label for="al_inv_no">Invoice No.</label>
                        <input type="text" name="invoice_no" id="al_inv_no" value="<?php echo htmlspecialchars($invoice_no_raw); ?>" class="form-control" placeholder="Enter invoice number" autocomplete="off">
                    </div>
                    <div class="filter-field">
                        <label class="filter-field-label">Branch</label>
                        <div class="mp-ms" data-mp-ms data-mp-label="Branches">
                            <button type="button" class="mp-ms-btn" aria-expanded="false">Branches</button>
                            <div class="mp-ms-panel">
                                <label class="mp-ms-all"<?php echo $tr_effective_branch_id > 0 ? ' style="display:none"' : ''; ?>><input type="checkbox" class="mp-ms-check-all"> Select All</label>
                                <input type="search" class="mp-ms-search" placeholder="Search" autocomplete="off">
                                <div class="mp-ms-list">
                                    <?php foreach ($branches as $branch): ?>
                                    <label class="mp-ms-opt"><input type="checkbox" name="branch_id[]" value="<?php echo (int) $branch['id']; ?>" <?php echo in_array((int) $branch['id'], $tr_resolved_branch_ids, true) ? 'checked' : ''; ?> <?php echo ($tr_effective_branch_id > 0 && (int) $branch['id'] !== $tr_effective_branch_id) ? 'disabled' : ''; ?>><span><?php echo htmlspecialchars($branch['name'] ?? ''); ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="al_bill_bill">Bill To Bill</label>
                        <select name="bill_to_bill" id="al_bill_bill" class="form-control">
                            <option value="">Both</option>
                            <option value="yes" <?php echo $bill_to_bill_raw === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="no" <?php echo $bill_to_bill_raw === 'no' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-field-label">Ledger</label>
                        <div class="mp-ms" data-mp-ms data-mp-label="Ledgers">
                            <button type="button" class="mp-ms-btn" aria-expanded="false">Ledgers</button>
                            <div class="mp-ms-panel">
                                <label class="mp-ms-all"><input type="checkbox" class="mp-ms-check-all"> Select All</label>
                                <input type="search" class="mp-ms-search" placeholder="Search" autocomplete="off">
                                <div class="mp-ms-list">
                                    <?php foreach ($ledger_names as $name): ?>
                                    <?php $cn = isset($name['customer_name']) ? (string) $name['customer_name'] : ''; if ($cn === '') { continue; } ?>
                                    <label class="mp-ms-opt"><input type="checkbox" name="ledger_name[]" value="<?php echo htmlspecialchars($cn, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($cn, $ledger_names_sel, true) ? 'checked' : ''; ?>><span><?php echo htmlspecialchars($cn); ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-field-label">Group</label>
                        <div class="mp-ms" data-mp-ms data-mp-label="Groups">
                            <button type="button" class="mp-ms-btn" aria-expanded="false">Groups</button>
                            <div class="mp-ms-panel">
                                <label class="mp-ms-all"><input type="checkbox" class="mp-ms-check-all"> Select All</label>
                                <input type="search" class="mp-ms-search" placeholder="Search" autocomplete="off">
                                <div class="mp-ms-list">
                                    <?php foreach ($ledger_groups as $grp): ?>
                                    <label class="mp-ms-opt"><input type="checkbox" name="group[]" value="<?php echo (int) $grp['id']; ?>" <?php echo in_array((string) $grp['id'], $group_ids, true) ? 'checked' : ''; ?>><span><?php echo htmlspecialchars($grp['name'] ?? ''); ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="filter-field">
                        <label class="filter-field-label">Ledger Type</label>
                        <div class="mp-ms" data-mp-ms data-mp-label="Ledger types">
                            <button type="button" class="mp-ms-btn" aria-expanded="false">Ledger types</button>
                            <div class="mp-ms-panel">
                                <label class="mp-ms-all"><input type="checkbox" class="mp-ms-check-all"> Select All</label>
                                <div class="mp-ms-list">
                                    <label class="mp-ms-opt"><input type="checkbox" name="ledger_type[]" value="Customer" <?php echo in_array('Customer', $ledger_types_sel, true) ? 'checked' : ''; ?>><span>Customer</span></label>
                                    <label class="mp-ms-opt"><input type="checkbox" name="ledger_type[]" value="Supplier" <?php echo in_array('Supplier', $ledger_types_sel, true) ? 'checked' : ''; ?>><span>Supplier</span></label>
                                    <label class="mp-ms-opt"><input type="checkbox" name="ledger_type[]" value="Account" <?php echo in_array('Account', $ledger_types_sel, true) ? 'checked' : ''; ?>><span>Account</span></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label class="filter-field-label">Type of Voucher</label>
                        <div class="mp-ms" data-mp-ms data-mp-label="Voucher types">
                            <button type="button" class="mp-ms-btn" aria-expanded="false">Voucher types</button>
                            <div class="mp-ms-panel">
                                <label class="mp-ms-all"><input type="checkbox" class="mp-ms-check-all"> Select All</label>
                                <input type="search" class="mp-ms-search" placeholder="Search" autocomplete="off">
                                <div class="mp-ms-list">
                                    <?php foreach ($voucher_types as $key => $label): ?>
                                    <label class="mp-ms-opt"><input type="checkbox" name="voucher_type[]" value="<?php echo htmlspecialchars($key); ?>" <?php echo in_array($key, $filter_voucher_keys, true) ? 'checked' : ''; ?>><span><?php echo htmlspecialchars($label); ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="filter-field">
                        <label class="filter-field-label">Against Voucher</label>
                        <div class="mp-ms" data-mp-ms data-mp-label="Against voucher">
                            <button type="button" class="mp-ms-btn" aria-expanded="false">Against voucher</button>
                            <div class="mp-ms-panel">
                                <label class="mp-ms-all"><input type="checkbox" class="mp-ms-check-all"> Select All</label>
                                <input type="search" class="mp-ms-search" placeholder="Search" autocomplete="off">
                                <div class="mp-ms-list">
                                    <?php foreach ($voucher_types as $key => $label): ?>
                                    <label class="mp-ms-opt"><input type="checkbox" name="against_voucher_type[]" value="<?php echo htmlspecialchars($key); ?>" <?php echo in_array($key, $filter_against_voucher_keys, true) ? 'checked' : ''; ?>><span><?php echo htmlspecialchars($label); ?></span></label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="filter-field">
                        <label for="al_against_inv">Against Inv. No</label>
                        <input type="text" name="against_inv_no" id="al_against_inv" value="<?php echo htmlspecialchars($against_inv_no_raw); ?>" class="form-control" placeholder="Enter invoice number" autocomplete="off">
                    </div>
                    <div class="filter-field">
                        <label class="filter-field-label">Options</label>
                        <label class="filter-field-checkbox-row">
                            <input type="checkbox" name="only_balance" value="1" <?php echo $only_balance ? 'checked' : ''; ?>>
                            Only Balance
                        </label>
                    </div>
                </div>
                
                <div class="filter-modal-footer">
                    <button type="submit" class="btn-apply">Apply Filter</button>
                    <button type="button" class="btn-clear" onclick="clearFilters()">Clear Filter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var STORAGE_KEY = 'accountledger_columns_<?php echo $active_tab; ?>';
    var balanceCols = [
        {col:'0',label:'Action'},{col:'1',label:'Ledger'},{col:'2',label:'Branch Name'},{col:'3',label:'Opening CrOrDr'},{col:'4',label:'Opening Amount'},
        {col:'5',label:'Debit Amount'},{col:'6',label:'Credit Amount'},{col:'7',label:'Closing CrOrDr'},{col:'8',label:'Closing Amount'},
        {col:'9',label:'Gold Open Gross'},{col:'10',label:'Gold Credit Gross'},{col:'11',label:'Gold Debit Gross'},{col:'12',label:'Gold Closing Gross'},
        {col:'13',label:'Gold Open Pure'},{col:'14',label:'Gold Credit Pure'},{col:'15',label:'Gold Debit Pure'},{col:'16',label:'Gold Closing Pure'},
        {col:'17',label:'Silver Open Gross'},{col:'18',label:'Silver Credit Gross'},{col:'19',label:'Silver Debit Gross'},{col:'20',label:'Silver Closing Gross'},
        {col:'21',label:'Silver Open Pure'},{col:'22',label:'Silver Credit Pure'},{col:'23',label:'Silver Debit Pure'},{col:'24',label:'Silver Closing Pure'},
        {col:'25',label:'Plat Open Gross'},{col:'26',label:'Plat Credit Gross'},{col:'27',label:'Plat Debit Gross'},{col:'28',label:'Plat Closing Gross'},
        {col:'29',label:'Plat Open Pure'},{col:'30',label:'Plat Credit Pure'},{col:'31',label:'Plat Debit Pure'},{col:'32',label:'Plat Closing Pure'},
        {col:'33',label:'Imit Open Gross'},{col:'34',label:'Imit Credit Gross'},{col:'35',label:'Imit Debit Gross'},{col:'36',label:'Imit Closing Gross'},
        {col:'37',label:'Imit Open Pure'},{col:'38',label:'Imit Credit Pure'},{col:'39',label:'Imit Debit Pure'},{col:'40',label:'Imit Closing Pure'},
        {col:'41',label:'Other Open Gross'},{col:'42',label:'Other Credit Gross'},{col:'43',label:'Other Debit Gross'},{col:'44',label:'Other Closing Gross'},
        {col:'45',label:'Other Open Pure'},{col:'46',label:'Other Credit Pure'},{col:'47',label:'Other Debit Pure'},{col:'48',label:'Other Closing Pure'},
        {col:'49',label:'Diamond Open Ct'},{col:'50',label:'Diamond Debit Ct'},{col:'51',label:'Diamond Credit Ct'},{col:'52',label:'Diamond Closing Ct'},
        {col:'53',label:'Gemstone Open Ct'},{col:'54',label:'Gemstone Debit Ct'},{col:'55',label:'Gemstone Credit Ct'},{col:'56',label:'Gemstone Closing Ct'},
        {col:'57',label:'Ledger Type'}
    ];
    var allCols = [
        {col:'0',label:'Action'},{col:'1',label:'Date'},{col:'2',label:'Ledger'},{col:'3',label:'Invoice No'},{col:'4',label:'Against Ledger'},
        {col:'5',label:'Against Inv No'},{col:'6',label:'Type Of Voucher'},{col:'7',label:'Debit'},{col:'8',label:'Credit'},{col:'9',label:'CL Amount'},
        {col:'10',label:'Gold Debit Gross'},{col:'11',label:'Gold Credit Gross'},{col:'12',label:'Gold CL Gross'}
        <?php if ($has_gold_pure): ?>,{col:'13',label:'Gold Debit Pure'},{col:'14',label:'Gold Credit Pure'},{col:'15',label:'Gold CL Pure'}<?php endif; ?>
    ];
    var cols = '<?php echo $active_tab; ?>' === 'balance' ? balanceCols : allCols;

    function loadColumnPrefs() {
        try {
            var s = localStorage.getItem(STORAGE_KEY);
            return s ? JSON.parse(s) : null;
        } catch(e) { return null; }
    }
    function saveColumnPrefs(hidden) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(hidden)); } catch(e) {}
    }
    function applyColumnVisibility(hidden) {
        document.querySelectorAll('.table th[data-col], .table td[data-col]').forEach(function(el) {
            var c = el.getAttribute('data-col');
            if (hidden.indexOf(c) >= 0) el.classList.add('col-hidden');
            else el.classList.remove('col-hidden');
        });
    }
    function buildCheckboxes() {
        var hidden = loadColumnPrefs() || [];
        var html = '';
        cols.forEach(function(c) {
            var checked = hidden.indexOf(c.col) < 0;
            html += '<label><input type="checkbox" data-col="'+c.col+'" '+(checked?'checked':'')+'> '+c.label+'</label>';
        });
        var cont = document.getElementById('columnCheckboxesContainer');
        if (cont) cont.innerHTML = html;
        cont.querySelectorAll('input').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var h = [];
                cont.querySelectorAll('input:not(:checked)').forEach(function(x) { h.push(x.getAttribute('data-col')); });
                saveColumnPrefs(h);
                applyColumnVisibility(h);
            });
        });
        applyColumnVisibility(hidden);
    }
    window.resetColumnVisibility = function() {
        saveColumnPrefs([]);
        document.querySelectorAll('#columnCheckboxesContainer input').forEach(function(cb) { cb.checked = true; });
        applyColumnVisibility([]);
    };
    buildCheckboxes();
    function applyPistaColumnBackground() {
        var table = document.getElementById('ledgerTable');
        if (!table) return;
        var from = parseInt(table.getAttribute('data-pista-from') || '9', 10);
        table.querySelectorAll('th[data-col], td[data-col]').forEach(function(el) {
            var c = parseInt(el.getAttribute('data-col'), 10);
            if (!isNaN(c) && c >= from) el.classList.add('col-pista');
            else el.classList.remove('col-pista');
        });
    }
    applyPistaColumnBackground();
    window.applyPistaColumnBackground = applyPistaColumnBackground;
    window.toggleColumnDropdown = function(e) {
        e.stopPropagation();
        var btn = document.getElementById('columnSettingsBtn');
        var dd = document.getElementById('columnSettingsDropdown');
        if (!btn || !dd) return;
        if (dd.classList.contains('show')) {
            dd.classList.remove('show');
            return;
        }
        var r = btn.getBoundingClientRect();
        dd.style.top = (r.bottom + 6) + 'px';
        dd.style.right = (window.innerWidth - r.right) + 'px';
        dd.style.left = 'auto';
        dd.classList.add('show');
    };
    document.addEventListener('click', function(e) {
        var dd = document.getElementById('columnSettingsDropdown');
        if (dd && !e.target.closest('.dropdown-columns')) dd.classList.remove('show');
    });
})();

function alLedgerQuickChange(sel) {
    var u = <?php echo json_encode(al_build_query(['ledger_account' => null, 'ledger_name' => null, 'page' => 1])); ?>;
    if (sel && sel.value) {
        u += '&ledger_account=' + encodeURIComponent(sel.value) + '&ledger_name=' + encodeURIComponent(sel.value);
    }
    location.href = u;
}

function openFilterModal() {
    document.getElementById('filterModal').classList.add('active');
}

function closeFilterModal() {
    document.getElementById('filterModal').classList.remove('active');
}

function trMpMsUpdateLabel(wrap) {
    var btn = wrap.querySelector('.mp-ms-btn');
    var list = wrap.querySelector('.mp-ms-list');
    var ph = wrap.getAttribute('data-mp-label') || 'Select';
    if (!btn || !list) return;
    var opts = list.querySelectorAll('input[type="checkbox"]');
    var checked = list.querySelectorAll('input[type="checkbox"]:checked');
    var n = checked.length;
    var total = opts.length;
    if (n === 0) {
        btn.textContent = ph;
    } else if (total && n === total) {
        btn.textContent = ph + ' (all)';
    } else {
        btn.textContent = ph + ' (' + n + ')';
    }
}

function initTrMpMultiSelectDropdowns(root) {
    root = root || document;
    root.querySelectorAll('[data-mp-ms]').forEach(function (wrap) {
        if (wrap._trMpMsInit) return;
        wrap._trMpMsInit = true;
        var btn = wrap.querySelector('.mp-ms-btn');
        var panel = wrap.querySelector('.mp-ms-panel');
        var search = wrap.querySelector('.mp-ms-search');
        var list = wrap.querySelector('.mp-ms-list');
        var allCb = wrap.querySelector('.mp-ms-check-all');

        function syncAll() {
            var opts = list.querySelectorAll('input[type="checkbox"]');
            var checked = list.querySelectorAll('input[type="checkbox"]:checked');
            if (allCb) {
                allCb.indeterminate = checked.length > 0 && checked.length < opts.length;
                allCb.checked = opts.length > 0 && checked.length === opts.length;
            }
            trMpMsUpdateLabel(wrap);
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = panel.classList.contains('is-open');
            document.querySelectorAll('#filterModal .mp-ms-panel.is-open').forEach(function (p) {
                p.classList.remove('is-open');
            });
            document.querySelectorAll('#filterModal .mp-ms-btn').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
            });
            if (!wasOpen) {
                panel.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });

        if (allCb) {
            allCb.addEventListener('change', function () {
                var v = allCb.checked;
                list.querySelectorAll('.mp-ms-opt').forEach(function (lab) {
                    if (lab.style.display === 'none') return;
                    var cb = lab.querySelector('input[type="checkbox"]');
                    if (cb) cb.checked = v;
                });
                syncAll();
            });
        }
        list.addEventListener('change', function (e) {
            if (e.target && e.target.type === 'checkbox' && e.target !== allCb) syncAll();
        });

        if (search) {
            search.addEventListener('input', function () {
                var q = (search.value || '').toLowerCase().trim();
                list.querySelectorAll('.mp-ms-opt').forEach(function (lab) {
                    var t = (lab.textContent || '').toLowerCase();
                    lab.style.display = !q || t.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        }
        syncAll();
    });

    if (!document._trMpMsDocClick) {
        document._trMpMsDocClick = true;
        document.addEventListener('click', function (e) {
            if (e.target.closest && e.target.closest('#filterModal .mp-ms')) return;
            document.querySelectorAll('#filterModal .mp-ms-panel.is-open').forEach(function (p) {
                p.classList.remove('is-open');
            });
            document.querySelectorAll('#filterModal .mp-ms-btn').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
            });
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        var ff = document.getElementById('filterForm');
        if (ff) initTrMpMultiSelectDropdowns(ff);
    });
} else {
    var ff = document.getElementById('filterForm');
    if (ff) initTrMpMultiSelectDropdowns(ff);
}

// Close modal when clicking outside
document.getElementById('filterModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeFilterModal();
    }
});

function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    // Preserve all filter parameters
    window.location.href = url.toString();
}

function resetDateRange() {
    var a = document.getElementById('alFromDate');
    var b = document.getElementById('alToDate');
    if (a) a.value = '';
    if (b) b.value = '';
}

function clearFilters() {
    var tab = '<?php echo htmlspecialchars($active_tab, ENT_QUOTES, 'UTF-8'); ?>';
    var per = '<?php echo (int) $per_page; ?>';
    window.location.href = '?tab=' + encodeURIComponent(tab) + '&per_page=' + per;
}

function viewLedgerDetails(ledgerName, customerId) {
    // Redirect to detailed ledger view
    window.location.href = 'accountledger-report.php?tab=all&ledger_name=' + encodeURIComponent(ledgerName);
}

function viewTransactionDetails(invoiceNo, transactionType, transactionId) {
    // Convert to proper types and handle empty strings
    invoiceNo = (invoiceNo || '').toString().trim();
    transactionType = (transactionType || '').toString().trim();
    transactionId = parseInt(transactionId) || 0;
    
    console.log('viewTransactionDetails called:', {invoiceNo: invoiceNo, transactionType: transactionType, transactionId: transactionId});
    
    if (transactionType === 'payment_voucher' && transactionId > 0) {
        window.location.href = 'payment-voucher.php?id=' + transactionId;
        return;
    }

    if (transactionType === 'receipt_voucher' && transactionId > 0) {
        window.location.href = 'receipt-voucher.php?id=' + transactionId;
        return;
    }
    
    // Standalone / linked payment voucher number (PV-1, PV1, etc.)
    if (transactionType === 'payment' && invoiceNo && /^PV[\-\s]?/i.test(String(invoiceNo).trim())) {
        var pvNo = String(invoiceNo).trim();
        var pvUrl = 'ajax/get-payment-voucher-id.php?voucher_no=' + encodeURIComponent(pvNo);
        var openPv = function(id) {
            if (id) {
                window.location.href = 'payment-voucher.php?id=' + id;
            } else {
                alert('Payment voucher not found: ' + pvNo);
            }
        };
        if (typeof jQuery !== 'undefined' && jQuery.ajax) {
            jQuery.ajax({
                url: pvUrl,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.voucher_id) {
                        openPv(response.voucher_id);
                    } else {
                        openPv(null);
                    }
                },
                error: function() { openPv(null); }
            });
        } else if (typeof fetch !== 'undefined') {
            fetch(pvUrl).then(function(r) { return r.json(); }).then(function(data) {
                if (data.status === 'success' && data.voucher_id) {
                    openPv(data.voucher_id);
                } else {
                    openPv(null);
                }
            }).catch(function() { openPv(null); });
        } else {
            alert('Open Payment Voucher from the menu and search for: ' + pvNo);
        }
        return;
    }
    
    // Payment lines tied to a purchase/sale invoice (transaction_id = invoice id)
    if (transactionType === 'payment' && transactionId > 0) {
        var invPay = (invoiceNo || '').trim();
        if (/^(SI-|SRI)/i.test(invPay)) {
            window.location.href = 'sale-order.php?id=' + transactionId;
            return;
        }
        window.location.href = 'purchase-invoice.php?id=' + transactionId;
        return;
    }
    
    // Determine type from invoice number prefix first (most reliable)
    var isPurchase = false;
    var isSale = false;
    
    if (invoiceNo) {
        var upperInvoice = invoiceNo.toUpperCase();
        if (upperInvoice.startsWith('PI-')) {
            isPurchase = true;
        } else if (upperInvoice.startsWith('SO-')) {
            isSale = true;
        }
    }
    
    // If we couldn't determine from invoice number, try transaction type
    if (!isPurchase && !isSale) {
        if (transactionType === 'purchase_invoice') {
            isPurchase = true;
        } else if (transactionType === 'sale_order') {
            isSale = true;
        }
    }
    
    // If transaction_id is available and valid, and we know the type, use it directly
    if (transactionId && transactionId > 0 && (isPurchase || isSale)) {
        if (isPurchase) {
            window.location.href = 'purchase-invoice.php?id=' + transactionId;
            return;
        } else if (isSale) {
            window.location.href = 'sale-order.php?id=' + transactionId;
            return;
        }
    }
    
    // If we don't have invoice number, show error
    if (!invoiceNo) {
        alert('Invoice number is required to view details');
        return;
    }
    
    // Purchase Invoice - need to look up ID
    if (isPurchase) {
        // Make AJAX call to get invoice ID
        var url = 'ajax/get-invoice-id.php?invoice_no=' + encodeURIComponent(invoiceNo) + '&type=purchase';
        
        if (typeof jQuery !== 'undefined' && jQuery.ajax) {
            jQuery.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.invoice_id) {
                        window.location.href = 'purchase-invoice.php?id=' + response.invoice_id;
                    } else {
                        alert('Purchase invoice not found: ' + invoiceNo);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('Error looking up invoice: ' + invoiceNo);
                }
            });
        } else if (typeof fetch !== 'undefined') {
            // Use fetch API as fallback
            fetch(url)
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.status === 'success' && data.invoice_id) {
                        window.location.href = 'purchase-invoice.php?id=' + data.invoice_id;
                    } else {
                        alert('Purchase invoice not found: ' + invoiceNo);
                    }
                })
                .catch(function(error) {
                    console.error('Fetch error:', error);
                    alert('Error looking up invoice: ' + invoiceNo);
                });
        } else {
            // Final fallback: redirect with invoice number (page should handle lookup)
            window.location.href = 'purchase-invoice.php?invoice_no=' + encodeURIComponent(invoiceNo);
        }
    }
    // Sale Order - need to look up ID
    else if (isSale) {
        // Make AJAX call to get order ID
        var url = 'ajax/get-invoice-id.php?invoice_no=' + encodeURIComponent(invoiceNo) + '&type=sale';
        
        if (typeof jQuery !== 'undefined' && jQuery.ajax) {
            jQuery.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.order_id) {
                        window.location.href = 'sale-order.php?id=' + response.order_id;
                    } else {
                        alert('Sale order not found: ' + invoiceNo);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    alert('Error looking up order: ' + invoiceNo);
                }
            });
        } else if (typeof fetch !== 'undefined') {
            // Use fetch API as fallback
            fetch(url)
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.status === 'success' && data.order_id) {
                        window.location.href = 'sale-order.php?id=' + data.order_id;
                    } else {
                        alert('Sale order not found: ' + invoiceNo);
                    }
                })
                .catch(function(error) {
                    console.error('Fetch error:', error);
                    alert('Error looking up order: ' + invoiceNo);
                });
        } else {
            // Final fallback: redirect with invoice number (page should handle lookup)
            window.location.href = 'sale-order.php?invoice_no=' + encodeURIComponent(invoiceNo);
        }
    } else {
        alert('Cannot determine invoice type. Invoice: ' + (invoiceNo || 'N/A') + ', Type: ' + (transactionType || 'N/A'));
    }
}

function exportToExcel() {
    // Trigger DataTables Excel export button
    if ($.fn.DataTable.isDataTable('#ledgerTable')) {
        $('#ledgerTable').DataTable().button('.buttons-excel').trigger();
    } else {
        alert('Table not initialized for export');
    }
}

function exportToPDF() {
    // Trigger DataTables print button (can be saved as PDF from print dialog)
    if ($.fn.DataTable.isDataTable('#ledgerTable')) {
        $('#ledgerTable').DataTable().button('.buttons-print').trigger();
    } else {
        window.print();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for view transaction buttons using event delegation
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('view-transaction-btn')) {
            var btn = e.target;
            var invoiceNo = btn.getAttribute('data-invoice-no') || '';
            var transactionType = btn.getAttribute('data-transaction-type') || '';
            var transactionId = parseInt(btn.getAttribute('data-transaction-id')) || 0;
            
            viewTransactionDetails(invoiceNo, transactionType, transactionId);
        }
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        if (!dropdown.previousElementSibling.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
});


</script>

<?php include 'footer-script.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
// Initialize DataTables for ledgerTable - MUST be after footer-script.php
var ledgerTable = null;
window.lastGoodLedgerOrder = null;

function ledgerOrderStorageKey() {
    var t = document.getElementById('ledgerTable');
    var tab = t && t.getAttribute('data-al-tab') ? t.getAttribute('data-al-tab') : 'balance';
    return 'auragold_accountledger_col_order_' + tab;
}

function getLedgerHeaderOrder(table) {
    var tr = table.querySelector('thead tr');
    if (!tr) return [];
    return Array.prototype.map.call(tr.querySelectorAll('th[data-col]'), function (th) {
        return th.getAttribute('data-col');
    });
}

function loadLedgerColumnOrder() {
    try {
        var raw = localStorage.getItem(ledgerOrderStorageKey());
        if (!raw) return null;
        var o = JSON.parse(raw);
        return Array.isArray(o) ? o : null;
    } catch (e) {
        return null;
    }
}

function saveLedgerColumnOrder(order) {
    try {
        localStorage.setItem(ledgerOrderStorageKey(), JSON.stringify(order));
    } catch (e) {}
}

function normalizeLedgerColumnOrder(saved, currentKeys) {
    if (!saved || !saved.length) return currentKeys.slice();
    var set = {};
    currentKeys.forEach(function (k) { set[k] = true; });
    var out = [];
    saved.forEach(function (k) {
        if (set[k]) {
            out.push(k);
            delete set[k];
        }
    });
    currentKeys.forEach(function (k) {
        if (set[k]) out.push(k);
    });
    return out;
}

function enhanceLedgerHeadersForReorder() {
    var table = document.getElementById('ledgerTable');
    if (!table) return;
    table.querySelectorAll('thead th[data-col]').forEach(function (th) {
        var c = th.getAttribute('data-col');
        if (c === '0') {
            th.classList.add('alr-th-fixed');
            return;
        }
        th.classList.add('alr-th-reorder');
        if (th.querySelector('.alr-th-drag')) return;
        var drag = document.createElement('span');
        drag.className = 'alr-th-drag';
        drag.title = 'Drag to reorder columns';
        drag.innerHTML = '<i class="feather icon-move" aria-hidden="true"></i>';
        th.appendChild(drag);
    });
}

function syncLedgerFooterRow(tr, order) {
    var table = document.getElementById('ledgerTable');
    if (!table || table.getAttribute('data-al-tab') !== 'all') return;
    var tds = Array.from(tr.querySelectorAll('td'));
    if (tds.length < 2) return;
    var first = tds[0];
    if (!first.hasAttribute('colspan') || first.getAttribute('data-col') !== '0') return;
    var anchorKey = '7';
    var idx = order.indexOf(anchorKey);
    if (idx < 0) idx = Math.min(9, order.length);
    first.colSpan = idx;
    var map = {};
    tds.slice(1).forEach(function (td) {
        var k = td.getAttribute('data-col');
        if (k) map[k] = td;
    });
    while (tr.firstChild) tr.removeChild(tr.firstChild);
    tr.appendChild(first);
    for (var i = idx; i < order.length; i++) {
        var k = order[i];
        if (map[k]) tr.appendChild(map[k]);
    }
}

function syncLedgerTableBodyToHeaderOrder(table, order) {
    var tb = table.querySelector('tbody');
    if (!tb) return;
    tb.querySelectorAll('tr').forEach(function (tr) {
        if (tr.cells.length === 1 && tr.cells[0].colSpan > 1) return;
        if (tr.classList.contains('table-footer-total')) {
            syncLedgerFooterRow(tr, order);
            return;
        }
        var byCol = {};
        tr.querySelectorAll('td[data-col]').forEach(function (td) {
            var k = td.getAttribute('data-col');
            if (k && !byCol[k]) byCol[k] = td;
        });
        order.forEach(function (k) {
            if (byCol[k]) tr.appendChild(byCol[k]);
        });
    });
}

function applyLedgerColumnOrder(table, order) {
    var tr = table.querySelector('thead tr');
    if (!tr) return;
    var map = {};
    tr.querySelectorAll('th[data-col]').forEach(function (th) {
        map[th.getAttribute('data-col')] = th;
    });
    order.forEach(function (k) {
        if (map[k]) tr.appendChild(map[k]);
    });
    syncLedgerTableBodyToHeaderOrder(table, order);
}

function prepareLedgerTableDom() {
    var table = document.getElementById('ledgerTable');
    if (!table) return;
    enhanceLedgerHeadersForReorder();
    var defaultOrder = getLedgerHeaderOrder(table);
    var saved = loadLedgerColumnOrder();
    var order = normalizeLedgerColumnOrder(saved, defaultOrder);
    applyLedgerColumnOrder(table, order);
    window.lastGoodLedgerOrder = order.slice();
    if (window.applyPistaColumnBackground) window.applyPistaColumnBackground();
}

function initLedgerColumnSortable() {
    var tr = document.querySelector('#ledgerTable thead tr');
    if (!tr || typeof Sortable === 'undefined') return;
    if (tr._alrSortable) {
        tr._alrSortable.destroy();
        tr._alrSortable = null;
    }
    tr._alrSortable = Sortable.create(tr, {
        animation: 150,
        handle: '.alr-th-drag',
        draggable: 'th.alr-th-reorder',
        filter: '.alr-th-fixed',
        preventOnFilter: false,
        ghostClass: 'alr-sortable-ghost',
        chosenClass: 'alr-sortable-chosen',
        onEnd: function () {
            finalizeLedgerColumnReorder();
        }
    });
}

function finalizeLedgerColumnReorder() {
    var table = document.getElementById('ledgerTable');
    if (!table) return;
    var order = getLedgerHeaderOrder(table);
    if (order[0] !== '0') {
        applyLedgerColumnOrder(table, window.lastGoodLedgerOrder || order);
        return;
    }
    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#ledgerTable')) {
        $('#ledgerTable').DataTable().destroy();
        ledgerTable = null;
    }
    syncLedgerTableBodyToHeaderOrder(table, order);
    saveLedgerColumnOrder(order);
    window.lastGoodLedgerOrder = order.slice();
    if (window.applyPistaColumnBackground) window.applyPistaColumnBackground();
    initLedgerTable({ skipPrepare: true });
}

$(document).ready(function() {
    setTimeout(function() {
        initLedgerTable();
    }, 100);
});

function initLedgerTable(opts) {
    opts = opts || {};
    var $table = $('#ledgerTable');
    if (!$table.length) {
        console.log('Table #ledgerTable not found');
        return;
    }

    if (!opts.skipPrepare) {
        prepareLedgerTableDom();
    } else {
        enhanceLedgerHeadersForReorder();
    }

    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#ledgerTable')) {
        $('#ledgerTable').DataTable().destroy();
    }

    if (!$.fn.DataTable) {
        console.log('DataTables not loaded, using fallback');
        initFallbackSearch();
        initLedgerColumnSortable();
        return;
    }

    try {
        ledgerTable = $table.DataTable({
            ordering: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            lengthChange: true,
            info: true,
            searching: true,
            paging: true,
            autoWidth: false,
            dom: 'lrtip',
            columnDefs: [
                { orderable: false, targets: 0 }
            ],
            language: {
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            initComplete: function() {
                console.log('DataTables initialized successfully');
                initLedgerColumnSortable();
            }
        });

        $('#customSearch').off('keyup change').on('keyup change', function() {
            var searchVal = $(this).val();
            ledgerTable.search(searchVal).draw();
        });

    } catch (e) {
        console.log('DataTables error:', e);
        initFallbackSearch();
        initLedgerColumnSortable();
    }

    $('#exportExcelBtn').off('click').on('click', function() {
        exportTableToExcel();
    });

    $('#printBtn').off('click').on('click', function() {
        printTable();
    });

    initTableSorting();
}

// Fallback search function if DataTables doesn't work
function initFallbackSearch() {
    $('#customSearch').off('keyup change').on('keyup change', function() {
        var searchVal = $(this).val().toLowerCase();
        $('#ledgerTable tbody tr').each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchVal) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    initTableSorting();
    console.log('Fallback search initialized');
}

// Table sorting functionality
function initTableSorting() {
    var $table = $('#ledgerTable');
    var $headers = $table.children('thead').find('> tr > th');
    var sortOrder = {};
    
    // Add sorting styles to headers
    $headers.each(function(index) {
        if (index > 0) { // Skip first column (View button)
            $(this).css('cursor', 'pointer');
            $(this).attr('data-sort-col', index);
            $(this).find('.sort-icon').remove();
            $(this).append(' <span class="sort-icon" style="font-size:10px;opacity:0.5;">↕</span>');
        }
    });
    
    $headers.off('click.alrSort').on('click.alrSort', function(e) {
        if ($(e.target).closest('.alr-th-drag').length) return;
        var colIndex = $(this).index();
        if (colIndex === 0) return;

        var $tbody = $table.find('tbody');
        var rows = $tbody.find('tr').get();
        
        // Toggle sort order
        sortOrder[colIndex] = sortOrder[colIndex] === 'asc' ? 'desc' : 'asc';
        var isAsc = sortOrder[colIndex] === 'asc';
        
        // Update sort icons
        $headers.find('.sort-icon').text('↕').css('opacity', '0.5');
        $(this).find('.sort-icon').text(isAsc ? '↑' : '↓').css('opacity', '1');
        
        // Sort rows
        rows.sort(function(a, b) {
            var aVal = $(a).find('td').eq(colIndex).text().trim();
            var bVal = $(b).find('td').eq(colIndex).text().trim();
            
            // Try to parse as number
            var aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
            var bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return isAsc ? aNum - bNum : bNum - aNum;
            }
            
            // String comparison
            return isAsc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        
        // Reattach sorted rows
        $.each(rows, function(index, row) {
            $tbody.append(row);
        });
    });

    console.log('Table sorting initialized');
}

function exportTableToExcel() {
    var csv = [];
    var headers = [];
    
    // Get headers (skip first column which is View button)
    $('#ledgerTable thead th').each(function(i) {
        if (i > 0) {
            headers.push('"' + $(this).text().trim().replace(/"/g, '""') + '"');
        }
    });
    csv.push(headers.join(','));
    
    // Get visible rows data
    $('#ledgerTable tbody tr:visible').each(function() {
        var rowData = [];
        $(this).find('td').each(function(i) {
            if (i > 0) {
                var cellText = $(this).text().trim().replace(/"/g, '""');
                rowData.push('"' + cellText + '"');
            }
        });
        if (rowData.length > 0) {
            csv.push(rowData.join(','));
        }
    });
    
    // Create and download file
    var csvContent = csv.join('\n');
    var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'Account_Ledger_Report_' + new Date().toISOString().slice(0,10) + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert('Excel/CSV file exported successfully!');
}

function printTable() {
    var printContents = '<html><head><title>Account Ledger Report</title>';
    printContents += '<style>';
    printContents += 'body { font-family: Arial, sans-serif; font-size: 12px; }';
    printContents += 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    printContents += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    printContents += 'th { background-color: #11294b; color: white; }';
    printContents += 'tr:nth-child(even) { background-color: #f2f2f2; }';
    printContents += 'h1 { text-align: center; color: #11294b; }';
    printContents += '@media print { th { background-color: #11294b !important; -webkit-print-color-adjust: exact; } }';
    printContents += '</style></head><body>';
    printContents += '<h1>Account Ledger Report</h1>';
    printContents += '<p>Generated on: ' + new Date().toLocaleString() + '</p>';
    printContents += '<table>';
    
    // Header
    printContents += '<thead><tr>';
    $('#ledgerTable thead th').each(function(i) {
        if (i > 0) {
            printContents += '<th>' + $(this).text() + '</th>';
        }
    });
    printContents += '</tr></thead>';
    
    // Body
    printContents += '<tbody>';
    $('#ledgerTable tbody tr:visible').each(function() {
        printContents += '<tr>';
        $(this).find('td').each(function(i) {
            if (i > 0) {
                printContents += '<td>' + $(this).text() + '</td>';
            }
        });
        printContents += '</tr>';
    });
    printContents += '</tbody></table></body></html>';
    
    var printWindow = window.open('', '_blank');
    printWindow.document.write(printContents);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}
</script>
</body>
</html>

