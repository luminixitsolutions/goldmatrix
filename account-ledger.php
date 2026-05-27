<?php
session_start();
require_once 'config.php';
require_once __DIR__ . '/includes/ensure_customer_ledger_branch_column.php';

auragold_ensure_customer_ledger_branch_column($conn);

$ledger_has_branch = false;
$rchk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_customer_ledger LIKE 'branch_id'");
if ($rchk && mysqli_num_rows($rchk) > 0) {
    $ledger_has_branch = true;
}
if ($rchk) {
    mysqli_free_result($rchk);
}

$account_ledger_branches = [];
if (function_exists('getListMaster')) {
    $account_ledger_branches = @getListMaster("SELECT id, name FROM tbl_branches WHERE status = 1 ORDER BY name ASC");
}
if (!is_array($account_ledger_branches)) {
    $account_ledger_branches = [];
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(10, min(100, (int)$_GET['per_page'])) : 25;
$offset = ($page - 1) * $per_page;

// Filters (default list scope to working branch so opening balance matches that branch)
$filter_group = isset($_GET['group']) ? esc($_GET['group']) : '';
$filter_search = isset($_GET['search']) ? esc($_GET['search']) : '';
$branch_filter_explicit = array_key_exists('branch_id', $_GET);
$filter_branch = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
if (!$branch_filter_explicit && function_exists('auragold_effective_branch_id')) {
    $effb = (int) auragold_effective_branch_id();
    if ($effb > 0) {
        $filter_branch = $effb;
    } elseif ($ledger_has_branch && function_exists('auragold_settings_main_branch_id')) {
        // HQ / no working branch: default list to main registry branch so sub-branch ledger rows are not mixed in
        $mid = (int) auragold_settings_main_branch_id();
        if ($mid > 0) {
            $filter_branch = $mid;
        }
    }
}
$pagination_branch_extra = [];
if (!$branch_filter_explicit && $filter_branch > 0) {
    $pagination_branch_extra['branch_id'] = $filter_branch;
}
$active_filters = 0;
if ($filter_group !== '') {
    $active_filters++;
}
if ($filter_search !== '') {
    $active_filters++;
}
if ($filter_branch > 0) {
    $active_filters++;
}

// Ledger name -> group display mapping (for Account Ledger list)
$ledger_group_map = [
    'Cash' => 'Cash-in Hand',
    'Bank Account' => 'Bank Accounts',
    'Sales Account' => 'Sales',
    'Purchase Account' => 'Purchase',
    'Profit And Loss' => 'Primary',
    'Advance Payment' => 'Loans & Advances(Asset)',
    'Salary' => 'Indirect Expenses',
    'Service Account' => 'Service Account',
    'PDC Payable' => 'Current Liabilities',
    'PDC Receivable' => 'Current Assets',
    'Discount Allowed' => 'Indirect Expenses',
    'Discount Received' => 'Indirect Income',
    'Coupon Discount Allowed' => 'Indirect Expenses',
    'Coupon Discount Received' => 'Indirect Income',
    'RoundOFF Allowed' => 'Indirect Expenses',
    'RoundOFF Received' => 'Indirect Income',
    'Adjust Price_Discount' => 'Indirect Expenses',
    'Redeem Allowed' => 'Indirect Expenses',
];

// Fetch ledgers with opening balance (branch-aware when tbl_customer_ledger.branch_id exists)
$where = "l.status = 1";
if ($filter_search !== '') {
    $where .= " AND l.customer_name LIKE '%" . $filter_search . "%'";
}

if ($ledger_has_branch) {
    $main_bid = function_exists('auragold_settings_main_branch_id') ? (int) auragold_settings_main_branch_id() : 0;
    $fb = (int) $filter_branch;
    if ($fb > 0) {
        // All ledgers visible; opening balance for this branch only (0 if none yet). Legacy NULL/0 opening = main branch.
        if ($main_bid > 0 && $fb === $main_bid) {
            $branch_opening_match = '(z.branch_id IS NULL OR z.branch_id = 0 OR z.branch_id = ' . $fb . ')';
        } else {
            $branch_opening_match = 'COALESCE(z.branch_id, 0) = ' . $fb;
        }
        $ledgers_sql = "
            SELECT dn.ledger_name AS ledger_name,
                   COALESCE(ob.balance_amount, 0) AS opening_balance,
                   COALESCE(b.name, '') AS branch_disp_name
            FROM (
                SELECT DISTINCT customer_name AS ledger_name
                FROM tbl_customer_ledger
                WHERE status = 1
            ) dn
            LEFT JOIN tbl_customer_ledger ob ON ob.customer_name = dn.ledger_name
                AND ob.status = 1
                AND ob.transaction_type = 'opening'
                AND ob.id = (
                    SELECT MAX(z.id)
                    FROM tbl_customer_ledger z
                    WHERE z.customer_name = dn.ledger_name
                      AND z.status = 1
                      AND z.transaction_type = 'opening'
                      AND " . $branch_opening_match . "
                )
            LEFT JOIN tbl_branches b ON b.id = ob.branch_id
            WHERE 1=1
        ";
        if ($filter_search !== '') {
            $ledgers_sql .= " AND dn.ledger_name LIKE '%" . $filter_search . "%'";
        }
        $ledgers_sql .= ' ORDER BY dn.ledger_name ASC';
        $all_ledgers = getList($ledgers_sql);
    } else {
        // All branches: one row per ledger per branch that has an opening row
        $w_open = "cl_open.status = 1 AND cl_open.transaction_type = 'opening'";
        if ($filter_search !== '') {
            $w_open .= " AND cl_open.customer_name LIKE '%" . $filter_search . "%'";
        }
        $ledgers_sql = "
            SELECT cl_open.customer_name AS ledger_name,
                   cl_open.balance_amount AS opening_balance,
                   COALESCE(b.name, '') AS branch_disp_name
            FROM tbl_customer_ledger cl_open
            LEFT JOIN tbl_branches b ON b.id = cl_open.branch_id
            INNER JOIN (
                SELECT customer_name, COALESCE(branch_id, 0) AS bid, MAX(id) AS max_id
                FROM tbl_customer_ledger
                WHERE status = 1 AND transaction_type = 'opening'
                GROUP BY customer_name, COALESCE(branch_id, 0)
            ) mx ON cl_open.customer_name = mx.customer_name
                AND COALESCE(cl_open.branch_id, 0) = mx.bid
                AND cl_open.id = mx.max_id
            WHERE $w_open
            ORDER BY cl_open.customer_name ASC
        ";
        $all_ledgers = getList($ledgers_sql);
    }
} else {
    $ledgers_sql = "
        SELECT l.customer_name AS ledger_name,
               (SELECT cl.balance_amount FROM tbl_customer_ledger cl 
                WHERE cl.customer_name = l.customer_name AND cl.status = 1 AND cl.transaction_type = 'opening'
                ORDER BY cl.transaction_date DESC, cl.id DESC LIMIT 1) AS opening_balance
        FROM tbl_customer_ledger l
        WHERE $where
        GROUP BY l.customer_name
        ORDER BY l.customer_name ASC
    ";
    $all_ledgers = getList($ledgers_sql);
}

$branch_id_to_label = [];
foreach ($account_ledger_branches as $abr) {
    $aid = (int) ($abr['id'] ?? 0);
    if ($aid > 0) {
        $branch_id_to_label[$aid] = trim((string) ($abr['name'] ?? ''));
    }
}

// Build rows with group and Cr/Dr; lookup customer_id from tbl_customers for edit link
$ledger_rows = [];
foreach ($all_ledgers as $r) {
    $name = $r['ledger_name'] ?? '';
    $ob = (float)($r['opening_balance'] ?? 0);
    $crdr = $ob >= 0 ? 'Dr' : 'Cr';
    $ob_abs = abs($ob);
    $group_name = isset($ledger_group_map[$name]) ? $ledger_group_map[$name] : 'Primary';
    if ($filter_group !== '' && $group_name !== $filter_group) continue;
    $customer_id = 0;
    $cust = getRecord("SELECT id FROM tbl_customers WHERE name = '" . mysqli_real_escape_string($conn, $name) . "' AND status = 1 LIMIT 1");
    if ($cust) $customer_id = (int)$cust['id'];
    $branch_disp = 'Main Branch';
    if ($ledger_has_branch) {
        $bn = trim((string) ($r['branch_disp_name'] ?? ''));
        if ($filter_branch > 0) {
            $branch_disp = $branch_id_to_label[$filter_branch] ?? ($bn !== '' ? $bn : '—');
        } else {
            $branch_disp = $bn !== '' ? $bn : '—';
        }
    }
    $ledger_rows[] = [
        'ledger_name' => $name,
        'contact' => '',
        'group_name' => $group_name,
        'branch_name' => $branch_disp,
        'opening_balance' => $ob_abs,
        'crdr' => $crdr,
        'customer_id' => $customer_id,
    ];
}

$all_filtered = $ledger_rows;
$total_records = count($all_filtered);
$total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
$ledger_rows = array_slice($all_filtered, $offset, $per_page);

$grand_total = 0;
foreach ($all_filtered as $row) {
    $grand_total += ($row['crdr'] === 'Dr' ? $row['opening_balance'] : -$row['opening_balance']);
}
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Account Ledger - <?php echo htmlspecialchars(auragold_app_name(), ENT_QUOTES, 'UTF-8'); ?> Software</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php';?>
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
    overflow: hidden;
}

.container-fluid {
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    padding: 0;
}

/* Page Header */
.page-header-bar {
    background: #11294b;
    color: #fff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    font-size: 12px;
}

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

.tab-link.active {
    color: #11294b;
    border-bottom-color: #11294b;
    font-weight: 600;
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
.table-container {
    flex: 1;
    overflow: auto;
    background: #fff;
    margin: 4px;
    border-radius: 8px 8px 0 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    margin: 0;
    font-size: 12px;
}

.table thead th {
    background: #f1edff !important;
    font-weight: 600;
    color: #4d5673;
    padding: 12px;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
}

.table tbody td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
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
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow: auto;
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
}

.filter-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
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

.filter-form-group label {
    display: block;
    margin-bottom: 5px;
    color: #ffffff;
    font-weight: 500;
    font-size: 12px;
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

/* Transaction list (Jewelstep-style cards) */
.transaction-list-container {
    margin: 0 20px 0 20px;
    padding: 0;
}
.transaction-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 16px 0;
}
.transaction-card {
    display: flex;
    align-items: stretch;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    transition: box-shadow 0.2s;
}
.transaction-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.transaction-card-left {
    min-width: 180px;
    padding-right: 20px;
    border-right: 1px solid #e2e8f0;
}
.voucher-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin-bottom: 8px;
}
.voucher-purchase_invoice { background: #dbeafe; color: #1e40af; }
.voucher-sale_invoice { background: #d1fae5; color: #065f46; }
.voucher-sale_return { background: #fef3c7; color: #92400e; }
.voucher-purchase_return { background: #fce7f3; color: #9d174d; }
.voucher-sale_quotation { background: #e0e7ff; color: #3730a3; }
.voucher-purchase_quotation { background: #f3e8ff; color: #6b21a8; }
.voucher-sale_fixing_direct { background: #fef9c3; color: #854d0e; }
.transaction-card-left .voucher-no { font-size: 11px; color: #64748b; margin-bottom: 2px; }
.transaction-card-left .voucher-no strong { color: #1e293b; }
.transaction-card-left .branch-name { font-size: 12px; color: #94a3b8; }
.transaction-card-center {
    flex: 1;
    padding: 0 24px;
    min-width: 160px;
}
.transaction-card-center .party-name { font-weight: 600; color: #1e293b; margin-bottom: 6px; font-size: 12px; }
.transaction-card-center .party-meta { font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.transaction-card-center .party-meta i { margin-right: 6px; font-size: 12px; }
.transaction-card-right {
    text-align: right;
    min-width: 200px;
}
.transaction-card-right .company-ref { font-size: 12px; color: #64748b; margin-bottom: 4px; }
.transaction-card-right .trans-date { font-size: 11px; color: #ffffff; margin-bottom: 8px; }
.trans-amount-row { margin-bottom: 10px; }
.transaction-card-right .trans-amount { display: block; font-size: 12px; color: #64748b; }
.transaction-card-right .amount-value { color: #2563eb; font-size: 16px; }
.transaction-card-right .trans-balance { display: block; font-size: 12px; color: #64748b; }
.transaction-card-right .trans-balance strong { color: #1e293b; }
.transaction-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 8px; }
.action-icon {
    width: 32px; height: 32px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid #e2e8f0; border-radius: 6px;
    color: #64748b; text-decoration: none;
    transition: all 0.2s;
}
.action-icon:hover { background: #f1f5f9; color: #11294b; border-color: #c4b5fd; }
.action-icon.btn-delete-transaction { border: none; cursor: pointer; background: transparent; font-size: inherit; }
.action-icon.btn-delete-transaction:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.no-transactions { text-align: center; padding: 48px 20px; color: #64748b; font-size: 15px; }
</style>

<body>
<?php include 'sidebar.php'; ?>

<div class="layout-content">
<div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">

<!-- Page Header -->
<div class="page-header-bar">
    <span>Account Ledger</span>
    <div class="page-header-actions"></div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="addLedgerDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="background:#11294b;border-color:#11294b;">
                <i class="feather icon-plus"></i> Add
            </button>
            <div class="dropdown-menu" aria-labelledby="addLedgerDropdown">
                <a class="dropdown-item" href="ledger-opening.php"><i class="feather icon-user"></i> New Ledger</a>
                <a class="dropdown-item" href="#" onclick="alert('Add Group – integrate with your form'); return false;"><i class="feather icon-folder"></i> New Group</a>
            </div>
        </div>
        <button type="button" class="btn-filter" id="btnFilter" title="Filter">
            <i class="feather icon-filter"></i> Filter
            <?php if ($active_filters > 0): ?>
            <span class="badge"><?php echo $active_filters; ?></span>
            <?php endif; ?>
        </button>
        <button type="button" class="btn-icon" title="Columns / Display" style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">
            <i class="feather icon-sliders"></i>
        </button>
        <div class="dropdown">
            <button class="btn-export dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display:inline-flex;">
                <i class="feather icon-download"></i> Export
            </button>
            <div class="dropdown-menu" aria-labelledby="exportDropdown">
                <a class="dropdown-item" href="#" onclick="exportTable('csv'); return false;">CSV</a>
                <a class="dropdown-item" href="#" onclick="exportTable('excel'); return false;">Excel</a>
                <a class="dropdown-item" href="#" onclick="exportTable('pdf'); return false;">PDF</a>
            </div>
        </div>
        <button type="button" class="btn-icon" title="Settings" style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;">
            <i class="feather icon-settings"></i>
        </button>
    </div>
    <div class="toolbar-right"></div>
</div>

<!-- Table Container -->
<div class="table-container">
    <table class="table" id="accountLedgerTable">
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Ledger</th>
                <th>Contact</th>
                <th>Group</th>
                <th>Branch Name</th>
                <th>Opening Balance</th>
                <th>Cr/Dr</th>
                <th style="width:60px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sr = $offset + 1;
            foreach ($ledger_rows as $row):
                $ob_display = number_format((float)$row['opening_balance'], 3, '.', '');
            ?>
            <tr>
                <td><?php echo $sr++; ?></td>
                <td><?php echo htmlspecialchars($row['ledger_name']); ?></td>
                <td><?php echo htmlspecialchars($row['contact']); ?></td>
                <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                <td><?php echo $ob_display; ?></td>
                <td><span class="crdr-badge <?php echo $row['crdr'] === 'Dr' ? 'dr' : 'cr'; ?>"><?php echo $row['crdr']; ?></span></td>
                <td>
                    <?php if (!empty($row['customer_id'])): ?>
                    <a href="ledger-opening.php?id=<?php echo (int)$row['customer_id']; ?><?php echo $filter_branch > 0 ? '&branch_id=' . (int) $filter_branch : ''; ?>" class="btn btn-sm p-0 border-0 text-primary mr-1" title="Edit">
                        <i class="feather icon-edit-2" style="font-size:14px;"></i>
                    </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm p-0 border-0 text-danger btn-delete-ledger" title="Delete" data-ledger="<?php echo htmlspecialchars($row['ledger_name']); ?>">
                        <i class="feather icon-trash-2" style="font-size:14px;"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ledger_rows)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No ledger accounts found.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-footer-total total-row">
                <td colspan="5" class="text-right font-weight-bold">Grand Total</td>
                <td class="font-weight-bold"><?php echo number_format(abs($grand_total), 3, '.', ''); ?></td>
                <td><span class="crdr-badge <?php echo $grand_total >= 0 ? 'dr' : 'cr'; ?>"><?php echo $grand_total >= 0 ? 'Dr' : 'Cr'; ?></span></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Pagination -->
<div class="pagination-container">
    <div class="pagination-info">
        Showing <?php echo $total_records === 0 ? 0 : $offset + 1; ?> to <?php echo min($offset + count($ledger_rows), $total_records); ?> of <?php echo $total_records; ?> entries
    </div>
    <div class="pagination-right">
        <div class="per-page-dropdown">
            <select id="perPageSelect" onchange="changePerPage(this.value)">
                <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100</option>
            </select>
            <span style="margin-left:6px;font-size:12px;color:#64748b;">Items</span>
        </div>
        <nav class="pagination">
            <?php
            $q = $_GET;
            unset($q['page']);
            $q = array_merge($q, $pagination_branch_extra);
            $base_q = http_build_query($q);
            $base_url = 'account-ledger.php' . ($base_q ? '?' . $base_q . '&' : '?');
            $page_param = 'page=';
            ?>
            <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page <= 1 ? '#' : $base_url . $page_param . '1'; ?>">&laquo;</a>
            <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page <= 1 ? '#' : $base_url . $page_param . ($page - 1); ?>">&lsaquo;</a>
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a class="page-link <?php echo $i === $page ? 'active' : ''; ?>" href="<?php echo $base_url . $page_param . $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" href="<?php echo $page >= $total_pages ? '#' : $base_url . $page_param . ($page + 1); ?>">&rsaquo;</a>
            <a class="page-link <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" href="<?php echo $page >= $total_pages ? '#' : $base_url . $page_param . $total_pages; ?>">&raquo;</a>
        </nav>
    </div>
</div>

</div>
</div>

<!-- Filter Modal -->
<div class="filter-modal" id="filterModal">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <h5>Filter Ledgers</h5>
            <button type="button" class="filter-modal-close" id="filterModalClose">&times;</button>
        </div>
        <div class="filter-modal-body">
            <form method="get" action="account-ledger.php" id="filterForm">
                <input type="hidden" name="per_page" value="<?php echo (int)$per_page; ?>">
                <div class="filter-form-row">
                    <div class="filter-form-group">
                        <label style="color:#1e293b;">Search Ledger</label>
                        <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($filter_search); ?>" placeholder="Ledger name">
                    </div>
                    <div class="filter-form-group">
                        <label style="color:#1e293b;">Group</label>
                        <select name="group" class="form-control">
                            <option value="">All Groups</option>
                            <?php
                            $filter_groups = ['Cash-in Hand', 'Primary', 'Loans & Advances(Asset)', 'Indirect Expenses', 'Service Account', 'Current Liabilities', 'Current Assets', 'Indirect Income', 'Bank Accounts', 'Sales', 'Purchase'];
                            foreach ($filter_groups as $gname):
                            ?>
                            <option value="<?php echo htmlspecialchars($gname); ?>" <?php echo $filter_group === $gname ? 'selected' : ''; ?>><?php echo htmlspecialchars($gname); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-form-group">
                        <label style="color:#1e293b;">Branch (opening)</label>
                        <select name="branch_id" class="form-control">
                            <option value="0" <?php echo $filter_branch === 0 ? 'selected' : ''; ?>>All branches</option>
                            <?php foreach ($account_ledger_branches as $abr): ?>
                                <?php $abid = (int) ($abr['id'] ?? 0); ?>
                                <option value="<?php echo $abid; ?>" <?php echo ($filter_branch === $abid) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) ($abr['name'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-modal-footer">
                    <button type="button" class="btn-cancel" id="filterCancel">Cancel</button>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn-clear" onclick="window.location.href='account-ledger.php';">Clear</button>
                        <button type="submit" class="btn-apply">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btnFilter').onclick = function() { document.getElementById('filterModal').classList.add('active'); };
document.getElementById('filterModalClose').onclick = function() { document.getElementById('filterModal').classList.remove('active'); };
document.getElementById('filterCancel').onclick = function() { document.getElementById('filterModal').classList.remove('active'); };
document.getElementById('filterModal').onclick = function(e) { if (e.target === this) this.classList.remove('active'); };

function changePerPage(val) {
    var u = new URL(window.location.href);
    u.searchParams.set('per_page', val);
    u.searchParams.set('page', '1');
    window.location.href = u.toString();
}

function exportTable(format) {
    alert('Export as ' + format + ' – integrate with your export script.');
}

document.querySelectorAll('.btn-delete-ledger').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var name = this.getAttribute('data-ledger');
        if (confirm('Delete ledger “‘ + name + ’”? This may affect existing transactions.')) {
            alert('Delete – integrate with your delete API for: ' + name);
        }
    });
});
</script>

<?php include 'footer-script.php'; ?>
</body>
</html>

