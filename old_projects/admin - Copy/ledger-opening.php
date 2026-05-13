<?php 
session_start();
require_once 'config.php';

$edit_customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ledger_opening_branch_from_url = array_key_exists('branch_id', $_GET) ? (int) $_GET['branch_id'] : null;

// Ledger Details modal data
$ledger_groups = [
    ['id' => 1, 'name' => 'Sundry Debtors'],
    ['id' => 2, 'name' => 'Sundry Creditors'],
    ['id' => 3, 'name' => 'Bank Accounts'],
    ['id' => 4, 'name' => 'Cash'],
    ['id' => 5, 'name' => 'Sales'],
    ['id' => 6, 'name' => 'Purchase'],
    ['id' => 7, 'name' => 'Expenses'],
    ['id' => 8, 'name' => 'Income'],
    ['id' => 9, 'name' => 'Capital'],
    ['id' => 10, 'name' => 'Loans & Advances'],
    ['id' => 11, 'name' => 'Fixed Assets'],
    ['id' => 12, 'name' => 'Current Assets'],
    ['id' => 13, 'name' => 'Current Liabilities'],
    ['id' => 14, 'name' => 'Investment'],
];
// Sundry Debtors dropdown options (ledger/account types from reference)
$sundry_options = [
    ['id' => 1,  'name' => 'Primary'],
    ['id' => 2,  'name' => 'Capital Account'],
    ['id' => 3,  'name' => 'Loans (Liability)'],
    ['id' => 4,  'name' => 'Current Liabilities'],
    ['id' => 5,  'name' => 'Fixed Assets'],
    ['id' => 6,  'name' => 'Investments'],
    ['id' => 7,  'name' => 'Current Assets'],
    ['id' => 8,  'name' => 'Branch /Divisions'],
    ['id' => 9,  'name' => 'Misc.Expenses (ASSET)'],
    ['id' => 10, 'name' => 'Suspense A/C'],
    ['id' => 11, 'name' => 'Sales Account'],
    ['id' => 12, 'name' => 'Purchase Account'],
    ['id' => 13, 'name' => 'Direct Income'],
    ['id' => 14, 'name' => 'Direct Expenses'],
    ['id' => 15, 'name' => 'Indirect Income'],
    ['id' => 16, 'name' => 'Indirect Expenses'],
    ['id' => 17, 'name' => 'Reserves & Surplus'],
    ['id' => 18, 'name' => 'Bank OD A/C'],
    ['id' => 19, 'name' => 'Secured Loans'],
    ['id' => 20, 'name' => 'UnSecured Loans'],
    ['id' => 21, 'name' => 'Duties & Taxes'],
    ['id' => 22, 'name' => 'Provisions'],
    ['id' => 23, 'name' => 'Sundry Creditors'],
    ['id' => 24, 'name' => 'Stock-in-Hand'],
    ['id' => 25, 'name' => 'Deposits(Assets)'],
    ['id' => 26, 'name' => 'Loans & Advances(Asset)'],
    ['id' => 27, 'name' => 'Sundry Debtors'],
    ['id' => 28, 'name' => 'Cash-in Hand'],
    ['id' => 29, 'name' => 'Bank Account'],
    ['id' => 30, 'name' => 'Service Account'],
];
$customer_types = getList("SELECT id, name FROM tbl_customer_types WHERE status = 1 ORDER BY name ASC");
$nationalities = getList("SELECT id, name FROM tbl_nationalities WHERE status = 1 ORDER BY name ASC");
$countries = getList("SELECT id, name FROM tbl_countries WHERE status = 1 ORDER BY name ASC");
$countries_ledger = $countries;
require_once __DIR__ . '/includes/international-dial-codes.php';

$ledger_opening_branches = [];
if (function_exists('getListMaster')) {
    $ledger_opening_branches = @getListMaster("SELECT id, name FROM tbl_branches WHERE status = 1 ORDER BY name ASC");
}
if (!is_array($ledger_opening_branches)) {
    $ledger_opening_branches = [];
}

$ledger_opening_default_branch_id = 0;
if ($ledger_opening_branch_from_url !== null && (int) $ledger_opening_branch_from_url > 0) {
    $ledger_opening_default_branch_id = (int) $ledger_opening_branch_from_url;
} elseif ($edit_customer_id <= 0 && function_exists('auragold_effective_branch_id')) {
    $ledger_opening_default_branch_id = (int) auragold_effective_branch_id();
}
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Transaction Report - AuraGold Software</title>
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

/* Ledger form on page - compact layout */
.ledger-form-container { background: #f8fafc; padding: 0 0 80px 0; overflow-y: auto; flex: 1; min-height: 0; }
.ledger-form-container .form-group { margin-bottom: 10px; }
.ledger-form-container .form-group label { font-size: 0.8rem; font-weight: 500; margin-bottom: 4px; display: block; }
.ledger-form-container .form-control { font-size: 0.85rem; padding: 0.35rem 0.6rem; height: 32px; }
.ledger-form-container select.form-control { height: 32px; padding: 0.35rem 0.6rem; }
.ledger-form-container .row { margin-bottom: 2px; }
.ledger-form-container .row > [class*="col-"] { padding-left: 8px; padding-right: 8px; }
.ledger-form-container .nav-tabs .nav-link { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
.ledger-form-container .nav-tabs .nav-link { color: #1e293b !important; }
.ledger-form-container .nav-tabs .nav-link:hover { color: #11294b !important; }
.ledger-form-container .nav-tabs .nav-link.active { color: #11294b !important; font-weight: 600; }
.ledger-form-container .item-tax-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; font-size: 0.85rem; }
.ledger-form-container .item-tax-table thead { background: #11294b; color: #fff; }
.ledger-form-container .item-tax-table thead th { padding: 0.6rem 1rem; text-align: left; font-weight: 600; font-size: 0.85rem; border-right: 1px solid rgba(255, 255, 255, 0.2); }
.ledger-form-container .item-tax-table thead th:last-child { border-right: none; }
.ledger-form-container .item-tax-table tbody tr { border-bottom: 1px solid #e2e8f0; }
.ledger-form-container .item-tax-table tbody tr:last-child { border-bottom: none; }
.ledger-form-container .item-tax-table tbody tr:hover { background: #f8fafc; }
.ledger-form-container .item-tax-table tbody td { padding: 0.6rem 1rem; font-size: 0.85rem; color: #334155; vertical-align: middle; }
.ledger-form-container .item-tax-table tbody td:first-child { font-weight: 500; color: #1e293b; }
.ledger-form-container .item-tax-table tbody td select { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.85rem; background: #fff; cursor: pointer; height: 32px; }
.ledger-form-container .item-tax-table tbody td select:focus { border-color: #c5a864; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); outline: none; }
.ledger-form-container .share-holder-file-item:hover { background: #f1f5f9 !important; }

/* Opening section (right sidebar) */
.ledger-opening-sidebar { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
.ledger-opening-sidebar .opening-title { color: #11294b; font-weight: 600; font-size: 0.95rem; margin: 0 0 0.75rem 0; padding-bottom: 0.4rem; border-bottom: 2px solid #c5a864; }
.ledger-opening-sidebar .form-group { margin-bottom: 0.5rem; }
.ledger-opening-sidebar .form-group label { color: #334155; font-size: 0.8rem; font-weight: 500; margin-bottom: 3px; }
.ledger-opening-sidebar .form-control { border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.9rem; }
.ledger-opening-sidebar .form-control:focus { border-color: #14b8a6; box-shadow: 0 0 0 0.2rem rgba(20, 184, 166, 0.2); outline: none; }
.ledger-opening-sidebar .opening-balance-row { display: flex; align-items: flex-end; gap: 0.5rem; flex-wrap: wrap; }
.ledger-opening-sidebar .opening-balance-row .form-group { flex: 1; min-width: 100px; margin-bottom: 0; }
.ledger-opening-sidebar .opening-credit-debit { display: flex; gap: 0.75rem; margin-top: 0.5rem; }
.ledger-opening-sidebar .form-check-label { color: #334155; font-size: 0.85rem; margin-left: 0.35rem; }
/* Ensure opening branch dropdown stays clickable (some skins mimic disabled styling). */
.ledger-opening-sidebar select#openingBranchId {
    position: relative;
    z-index: 2;
    cursor: pointer;
    pointer-events: auto;
    opacity: 1;
    background-color: #fff;
}
</style>

<body>
<?php include 'sidebar.php'; ?>

<div class="layout-content">
<div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">

<!-- Page Header -->
<div class="page-header-bar">
    <span>Ledger Opening</span>
    <div class="page-header-actions"></div>
</div>

<!-- Ledger Details form (shown on page) -->
<div class="ledger-form-container">
    <form id="customerCreationForm" method="post" enctype="multipart/form-data" data-default-opening-branch="<?php echo (int) $ledger_opening_default_branch_id; ?>">
        <input type="hidden" name="customer_id" id="customerId" value="<?php echo $edit_customer_id; ?>">
        <div style="padding: 1rem 1.25rem; max-width: 1400px; margin: 0 auto;">
            <!-- Top Action Buttons -->
            <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearCustomerForm()" style="margin-right: 0.5rem; padding: 0.4rem 0.75rem; font-size: 0.85rem;">Clear</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveCustomerBtn" onclick="saveCustomer(this)" style="margin-right: 0.5rem; background: #11294b; border: none; padding: 0.4rem 0.75rem; font-size: 0.85rem;">Save</button>
            </div>

                        <!-- Ledger Photo and Basic Info -->
                        <div class="row mb-2">
                            <div class="col-md-2">
                                <div style="text-align: center;">
                                    <div style="width: 100px; height: 100px; border-radius: 50%; background: #f1f5f9; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative; cursor: pointer;" onclick="document.getElementById('ledgerPhotoInput').click();">
                                        <i class="feather icon-camera" style="font-size: 1.25rem; color: #94a3b8;"></i>
                                        <input type="file" id="ledgerPhotoInput" name="ledger_photo" accept="image/*" style="display: none;" onchange="previewLedgerPhoto(this);">
                                    </div>
                                    <div id="ledgerPhotoPreview" style="display: none; width: 100px; height: 100px; border-radius: 50%; margin: 0 auto; overflow: hidden; border: 2px solid #c5a864;">
                                        <img id="ledgerPhotoImg" src="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="form-check mt-2" style="text-align: center;">
                                        <input class="form-check-input" type="checkbox" id="ledgerNameCapital" name="ledger_name_capital" style="width: 0.9rem; height: 0.9rem;">
                                        <label class="form-check-label" for="ledgerNameCapital" style="font-size: 0.75rem;">Ledger Name Capital</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Name *</label>
                                            <input type="text" class="form-control" id="ledgerName" name="name" required oninput="handleNameInput(this)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Alternate Name</label>
                                            <input type="text" class="form-control" id="ledgerAlternateName" name="alternate_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" id="ledgerFirstName" name="first_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" id="ledgerLastName" name="last_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Mobile No</label>
                                            <div class="input-group">
                                                <select class="form-control" id="mobileCountryCode" name="mobile_country_code" style="max-width: 96px; font-size: 0.85rem; padding: 0.4rem 0.5rem; height: 32px;">
                                                    <?php auragold_render_dial_code_select('971'); ?>
                                                </select>
                                                <input type="text" class="form-control" id="ledgerMobileNo" name="mobile_no" placeholder="Mobile No">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Phone No</label>
                                            <input type="text" class="form-control" id="ledgerPhoneNo" name="phone_no" placeholder="Phone No">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Mail ID</label>
                                            <input type="email" class="form-control" id="ledgerMailId" name="mail_id" placeholder="Email">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identity No</label>
                                            <input type="text" class="form-control" id="ledgerIdentityNo" name="identity_no">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>National Id</label>
                                            <input type="text" class="form-control" id="ledgerNationalId" name="national_id">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Trade No</label>
                                            <input type="text" class="form-control" id="ledgerTradeNo" name="trade_no">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identity Issue Date</label>
                                            <input type="date" class="form-control" id="identityIssueDate" name="identity_issue_date">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identity Expiry Date</label>
                                            <input type="date" class="form-control" id="identityExpiryDate" name="identity_expiry_date">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Special Day</label>
                                            <input type="date" class="form-control" id="specialDay" name="special_day">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Customer Type *</label>
                                            <select class="form-control" id="customerType" name="customer_type_id">
                                                <option value="">Select Customer Type</option>
                                                <?php 
                                                foreach($customer_types as $type) {
                                                    echo '<option value="'.$type['id'].'">'.htmlspecialchars($type['name']).'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Registration No</label>
                                            <input type="text" class="form-control" id="registrationNo" name="registration_no">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Registration Date</label>
                                            <input type="date" class="form-control" id="registrationDate" name="registration_date">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Nationality</label>
                                            <select class="form-control" id="nationality" name="nationality_id">
                                                <option value="">Select Nationality</option>
                                                <?php 
                                                foreach($nationalities as $nationality) {
                                                    echo '<option value="'.$nationality['id'].'">'.htmlspecialchars($nationality['name']).'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Country</label>
                                            <select class="form-control" id="country" name="country_id">
                                                <option value="">Select Country</option>
                                                <?php 
                                                foreach($countries as $country) {
                                                    echo '<option value="'.$country['id'].'">'.htmlspecialchars($country['name']).'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Select Group</label>
                                            <select class="form-control" id="ledgerGroup" name="group_id">
                                                <option value="">Select Group</option>
                                                <?php 
                                                foreach($ledger_groups as $group) {
                                                    echo '<option value="'.$group['id'].'">'.htmlspecialchars($group['name']).'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Sundry Debtors *</label>
                                            <select class="form-control" id="ledgerSundryDebtors" name="sundry_debtors_id" required>
                                                <?php 
                                                foreach($sundry_options as $option) {
                                                    echo '<option value="'.$option['id'].'">'.htmlspecialchars($option['name']).'</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ledgerKYC" name="kyc">
                                                <label class="form-check-label" for="ledgerKYC">KYC</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ledgerAML" name="aml">
                                                <label class="form-check-label" for="ledgerAML">AML</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label" style="margin-right: 10px;">Bill to Bill:</label>
                                                <input class="form-check-input" type="radio" id="billToBillYes" name="bill_to_bill" value="1">
                                                <label class="form-check-label" for="billToBillYes" style="margin-right: 15px;">Yes</label>
                                                <input class="form-check-input" type="radio" id="billToBillNo" name="bill_to_bill" value="0" checked>
                                                <label class="form-check-label" for="billToBillNo">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Opening (right sidebar) -->
                            <div class="col-md-3">
                                <div class="ledger-opening-sidebar">
                                    <h6 class="opening-title">Opening</h6>
                                    <div class="form-group">
                                        <label>Opening Balance</label>
                                        <div class="opening-balance-row">
                                            <input type="number" class="form-control" id="openingBalance" name="opening_balance" value="0" step="0.01" min="0" placeholder="0" style="max-width: 140px;">
                                        </div>
                                        <div class="opening-credit-debit">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="openingCredit" name="opening_type" value="credit" checked>
                                                <label class="form-check-label" for="openingCredit">Credit</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="openingDebit" name="opening_type" value="debit">
                                                <label class="form-check-label" for="openingDebit">Debit</label>
                                            </div>
                                        </div>
                                        <div class="form-group mt-2 mb-0">
                                            <label for="openingBranchId">Branch (opening balance)</label>
                                            <select class="form-control form-control-sm" id="openingBranchId" name="opening_branch_id" autocomplete="off" title="Choose which branch this opening balance applies to">
                                                <option value="">— Not set —</option>
                                                <?php foreach ($ledger_opening_branches as $lb): ?>
                                                    <?php
                                                    $lbid = (int) ($lb['id'] ?? 0);
                                                    if ($lbid <= 0) {
                                                        continue;
                                                    }
                                                    $is_def = ($ledger_opening_default_branch_id > 0 && $ledger_opening_default_branch_id === $lbid);
                                                    ?>
                                                    <option value="<?php echo $lbid; ?>"<?php echo $is_def ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($lb['name'] ?? '')); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address and Bank Details Tabs -->
                        <ul class="nav nav-tabs mb-2" id="ledgerTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="billing-tab" data-toggle="tab" href="#billing-address" role="tab">Billing Address</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="shipping-tab" data-toggle="tab" href="#shipping-address" role="tab">Shipping Address</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="item-type-tax-tab" data-toggle="tab" href="#item-type-tax" role="tab">Item Type Tax</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="share-holders-tab" data-toggle="tab" href="#share-holders" role="tab">Share Holders</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="notes-tab" data-toggle="tab" href="#notes" role="tab">Notes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="nominee-tab" data-toggle="tab" href="#nominee" role="tab">Nominee</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="default-settings-tab" data-toggle="tab" href="#default-settings" role="tab">Default Settings</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="ledgerTabContent" style="padding-bottom: 60px;">
                            <!-- Billing Address Tab -->
                            <div class="tab-pane fade show active" id="billing-address" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Address 1</label>
                                            <input type="text" class="form-control" id="billingAddress1" name="billing_address1">
                                        </div>
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" id="billingAddress2" name="billing_address2">
                                        </div>
                                        <div class="form-group">
                                            <label>Country *</label>
                                            <select class="form-control" id="billingCountry" name="billing_country" required>
                                                <option value="">Select Country</option>
                                                <?php foreach ($countries_ledger as $co) {
                                                    $nm = htmlspecialchars($co['name'], ENT_QUOTES, 'UTF-8');
                                                    $cid = (int) $co['id'];
                                                    echo '<option value="' . $nm . '" data-country-id="' . $cid . '">' . $nm . '</option>';
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>State *</label>
                                            <select class="form-control" id="billingState" name="billing_state" required>
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>City</label>
                                            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                                <select class="form-control" id="billingCity" name="billing_city" style="flex: 1; min-width: 0;">
                                                    <option value="">Select City</option>
                                                </select>
                                                <button type="button" class="btn btn-light border rounded-circle city-info-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" title="Cities load for the selected state. Use + to add a new city under this state." tabindex="-1">
                                                    <i class="feather icon-info" style="font-size: 1rem; color: #64748b;"></i>
                                                </button>
                                                <button type="button" class="btn btn-light border rounded-circle city-add-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" data-target="billing" title="Add city under selected state">
                                                    <i class="feather icon-plus" style="font-size: 1rem; color: #11294b;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Zip Code</label>
                                            <input type="text" class="form-control" id="billingZipCode" name="billing_zip_code">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bank Details</label>
                                        </div>
                                        <div class="form-group">
                                            <label>Acc.No.</label>
                                            <input type="text" class="form-control" id="bankAccountNo" name="bank_account_no" placeholder="Account No">
                                        </div>
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" class="form-control" id="bankName" name="bank_name" placeholder="Bank Name">
                                        </div>
                                        <div class="form-group">
                                            <label>IFSC Code</label>
                                            <input type="text" class="form-control" id="bankIfscCode" name="bank_ifsc_code" placeholder="IFSC Code">
                                        </div>
                                        <div class="form-group">
                                            <label>Branch</label>
                                            <input type="text" class="form-control" id="bankBranch" name="bank_branch">
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="shipping-address" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Address 1</label>
                                            <input type="text" class="form-control" id="shippingAddress1" name="shipping_address1">
                                        </div>
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" id="shippingAddress2" name="shipping_address2">
                                        </div>
                                        <div class="form-group">
                                            <label>Country</label>
                                            <select class="form-control" id="shippingCountry" name="shipping_country">
                                                <option value="">Select Country</option>
                                                <?php foreach ($countries_ledger as $co) {
                                                    $nm = htmlspecialchars($co['name'], ENT_QUOTES, 'UTF-8');
                                                    $cid = (int) $co['id'];
                                                    echo '<option value="' . $nm . '" data-country-id="' . $cid . '">' . $nm . '</option>';
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>State</label>
                                            <select class="form-control" id="shippingState" name="shipping_state">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>City</label>
                                            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                                <select class="form-control" id="shippingCity" name="shipping_city" style="flex: 1; min-width: 0;">
                                                    <option value="">Select City</option>
                                                </select>
                                                <button type="button" class="btn btn-light border rounded-circle city-info-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" title="Cities load for the selected state. Use + to add a new city under this state." tabindex="-1">
                                                    <i class="feather icon-info" style="font-size: 1rem; color: #64748b;"></i>
                                                </button>
                                                <button type="button" class="btn btn-light border rounded-circle city-add-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" data-target="shipping" title="Add city under selected state">
                                                    <i class="feather icon-plus" style="font-size: 1rem; color: #11294b;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Zip Code</label>
                                            <input type="text" class="form-control" id="shippingZipCode" name="shipping_zip_code">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="item-type-tax" role="tabpanel">
                                <div style="background: #fff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <table class="item-tax-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%;">Item Name</th>
                                                <th style="width: 30%;">Default Input Type</th>
                                                <th style="width: 30%;">Default Output Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>AMOUNT</td>
                                                <td><select name="item_tax[AMOUNT][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[AMOUNT][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>Gold</td>
                                                <td><select name="item_tax[Gold][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[Gold][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>GOLD - MAKING</td>
                                                <td><select name="item_tax[GOLD_MAKING][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[GOLD_MAKING][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>Silver</td>
                                                <td><select name="item_tax[Silver][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[Silver][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>SILVER - MAKING</td>
                                                <td><select name="item_tax[SILVER_MAKING][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[SILVER_MAKING][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>Diamond & Stones</td>
                                                <td><select name="item_tax[Diamond_Stones][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[Diamond_Stones][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>Imitation Or Watches</td>
                                                <td><select name="item_tax[Imitation_Watches][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[Imitation_Watches][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>LOOSE - DIAMOND</td>
                                                <td><select name="item_tax[LOOSE_DIAMOND][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[LOOSE_DIAMOND][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>CERTIFIED - DIAMOND</td>
                                                <td><select name="item_tax[CERTIFIED_DIAMOND][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[CERTIFIED_DIAMOND][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                            <tr>
                                                <td>Other Or Services</td>
                                                <td><select name="item_tax[Other_Services][input_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                                <td><select name="item_tax[Other_Services][output_type]" class="form-control"><option value="VAT" selected>VAT</option><option value="TAX BAH">TAX BAH</option></select></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="share-holders" role="tabpanel">
                                <div style="background: #fff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <div style="margin-bottom: 1rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h6 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Share Holders</h6>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button type="button" class="btn btn-sm" id="addShareHolderBtn" style="background: #c5a864; color: #fff; border: none; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer;">
                                                    <i class="feather icon-plus" style="font-size: 0.85rem;"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer;">
                                                    <i class="feather icon-settings" style="font-size: 0.85rem;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div style="overflow-x: auto;">
                                            <table class="table" id="shareHoldersTable" style="margin-bottom: 0; font-size: 0.85rem;">
                                                <thead style="background: #11294b; color: #fff;">
                                                    <tr>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; user-select: none;" onclick="sortShareHoldersTable(0)">Name <i class="feather icon-arrow-up" style="font-size: 0.7rem;"></i><i class="feather icon-arrow-down" style="font-size: 0.7rem;"></i></th>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; user-select: none;" onclick="sortShareHoldersTable(1)">Nationality <i class="feather icon-arrow-up" style="font-size: 0.7rem;"></i><i class="feather icon-arrow-down" style="font-size: 0.7rem;"></i></th>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; user-select: none;" onclick="sortShareHoldersTable(2)">Share Per. <i class="feather icon-arrow-up" style="font-size: 0.7rem;"></i><i class="feather icon-arrow-down" style="font-size: 0.7rem;"></i></th>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; width: 60px; text-align: center;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="shareHoldersTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div style="margin-top: 1.5rem;">
                                        <h6 style="margin: 0 0 1rem 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Upload Document</h6>
                                        <div id="shareHolderDocumentUpload" style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 2rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.3s ease;" ondrop="handleShareHolderFileDrop(event)" ondragover="event.preventDefault(); this.style.borderColor = '#c5a864';" ondragleave="this.style.borderColor = '#cbd5e1';" onclick="document.getElementById('shareHolderFileInput').click();">
                                            <input type="file" id="shareHolderFileInput" name="share_holder_documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display: none;" onchange="handleShareHolderFileSelect(this);">
                                            <i class="feather icon-upload-cloud" style="font-size: 2.5rem; color: #c5a864; margin-bottom: 0.5rem;"></i>
                                            <p style="margin: 0.5rem 0 0 0; color: #64748b; font-size: 0.85rem;">Drop files here or click to upload.</p>
                                        </div>
                                        <div id="shareHolderFileList" style="margin-top: 1rem;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="notes" role="tabpanel">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea class="form-control" id="ledgerNotes" name="notes" rows="5"></textarea>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nominee" role="tabpanel">
                                <p>Nominee content goes here</p>
                            </div>

                            <div class="tab-pane fade" id="default-settings" role="tabpanel">
                                <p>Default Settings content goes here</p>
                            </div>
                        </div>
        </div>
    </form>
</div>

</div>
</div>

<!-- Filter Modal -->



<script>
(function() {
    const nationalities = <?php echo json_encode(isset($nationalities) && is_array($nationalities) ? $nationalities : []); ?>;
    let shareHolderRowIndex = 0;
    let shareHoldersData = [];
    let shareHolderFiles = [];

    function previewLedgerPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('ledgerPhotoPreview').style.display = 'block';
                document.getElementById('ledgerPhotoImg').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function handleNameInput(input) {
        const nameValue = input.value;
        const capitalCheckbox = document.getElementById('ledgerNameCapital');
        if (capitalCheckbox && capitalCheckbox.checked) {
            input.value = nameValue.toUpperCase();
        }
        const nameParts = nameValue.trim().split(/\s+/);
        const firstNameField = document.getElementById('ledgerFirstName');
        const lastNameField = document.getElementById('ledgerLastName');
        if (nameParts.length > 0) {
            if (firstNameField) firstNameField.value = nameParts[0];
            if (nameParts.length > 1 && lastNameField) {
                lastNameField.value = nameParts[nameParts.length - 1];
            } else if (nameParts.length === 1 && lastNameField) {
                lastNameField.value = '';
            }
        }
    }

    function addShareHolderRow() {
        shareHolderRowIndex++;
        const tbody = document.getElementById('shareHoldersTableBody');
        if (!tbody) return;
        let nationalityOptions = '<option value="">Select Nationality</option>';
        if (Array.isArray(nationalities)) {
            nationalities.forEach(function(n) {
                nationalityOptions += '<option value="' + n.id + '">' + (n.name || '') + '</option>';
            });
        }
        const row = document.createElement('tr');
        row.id = 'shareHolderRow_' + shareHolderRowIndex;
        row.setAttribute('data-row-index', shareHolderRowIndex);
        row.innerHTML = '<td><input type="text" class="form-control" name="share_holders[' + shareHolderRowIndex + '][name]" placeholder="Enter name" style="font-size: 0.85rem; padding: 0.4rem 0.6rem; height: 32px; border: 1px solid #e2e8f0;"></td>' +
            '<td><select class="form-control" name="share_holders[' + shareHolderRowIndex + '][nationality_id]" style="font-size: 0.85rem; padding: 0.4rem 0.6rem; height: 32px; border: 1px solid #e2e8f0;">' + nationalityOptions + '</select></td>' +
            '<td><input type="number" class="form-control" name="share_holders[' + shareHolderRowIndex + '][share_percentage]" placeholder="0.00" step="0.01" min="0" max="100" style="font-size: 0.85rem; padding: 0.4rem 0.6rem; height: 32px; border: 1px solid #e2e8f0; text-align: right;"></td>' +
            '<td style="text-align: center;"><button type="button" class="btn btn-sm delete-share-holder" onclick="window.deleteShareHolderRow(' + shareHolderRowIndex + ')" style="background: transparent; border: none; color: #ef4444; padding: 0.25rem; cursor: pointer;"><i class="feather icon-trash-2" style="font-size: 0.9rem;"></i></button></td>';
        tbody.appendChild(row);
        shareHoldersData.push({ row_index: shareHolderRowIndex, name: '', nationality_id: '', share_percentage: '' });
    }

    window.deleteShareHolderRow = function(rowIndex) {
        if (confirm('Are you sure you want to delete this share holder?')) {
            const row = document.getElementById('shareHolderRow_' + rowIndex);
            if (row) {
                row.remove();
                shareHoldersData = shareHoldersData.filter(function(item) { return item.row_index !== rowIndex; });
            }
        }
    };

    function sortShareHoldersTable(columnIndex) {
        const tbody = document.getElementById('shareHoldersTableBody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort(function(a, b) {
            var aVal, bVal;
            if (columnIndex === 0) {
                aVal = (a.querySelector('input[type="text"]') && a.querySelector('input[type="text"]').value) || '';
                bVal = (b.querySelector('input[type="text"]') && b.querySelector('input[type="text"]').value) || '';
            } else if (columnIndex === 1) {
                aVal = (a.querySelector('select') && a.querySelector('select').selectedOptions[0] && a.querySelector('select').selectedOptions[0].text) || '';
                bVal = (b.querySelector('select') && b.querySelector('select').selectedOptions[0] && b.querySelector('select').selectedOptions[0].text) || '';
            } else if (columnIndex === 2) {
                aVal = parseFloat((a.querySelector('input[type="number"]') && a.querySelector('input[type="number"]').value) || 0);
                bVal = parseFloat((b.querySelector('input[type="number"]') && b.querySelector('input[type="number"]').value) || 0);
            }
            if (typeof aVal === 'string') return aVal.localeCompare(bVal);
            return aVal - bVal;
        });
        rows.forEach(function(row) { tbody.appendChild(row); });
    }

    function handleShareHolderFiles(files) {
        const fileList = document.getElementById('shareHolderFileList');
        if (!fileList) return;
        Array.from(files).forEach(function(file) {
            shareHolderFiles.push(file);
            const fileItem = document.createElement('div');
            fileItem.className = 'share-holder-file-item';
            fileItem.style.cssText = 'display: flex; align-items: center; justify-content: space-between; padding: 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 0.5rem;';
            fileItem.innerHTML = '<div style="display: flex; align-items: center; gap: 0.5rem;"><i class="feather icon-file" style="color: #c5a864;"></i><span style="font-size: 0.85rem; color: #334155;">' + file.name + '</span><span style="font-size: 0.75rem; color: #94a3b8;">(' + (file.size / 1024).toFixed(2) + ' KB)</span></div><button type="button" onclick="window.removeShareHolderFile(this)" style="background: transparent; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem;"><i class="feather icon-x" style="font-size: 0.9rem;"></i></button>';
            fileList.appendChild(fileItem);
        });
    }

    function handleShareHolderFileDrop(event) {
        event.preventDefault();
        var uploadArea = document.getElementById('shareHolderDocumentUpload');
        if (uploadArea) uploadArea.style.borderColor = '#cbd5e1';
        handleShareHolderFiles(event.dataTransfer.files);
    }

    function handleShareHolderFileSelect(input) {
        handleShareHolderFiles(input.files);
    }

    window.removeShareHolderFile = function(button) {
        var fileItem = button.closest('.share-holder-file-item');
        if (fileItem) {
            var fileName = fileItem.querySelector('span').textContent.trim();
            shareHolderFiles = shareHolderFiles.filter(function(f) { return f.name !== fileName; });
            fileItem.remove();
        }
    };

    function ensureOpeningBranchInteractable() {
        var el = document.getElementById('openingBranchId');
        if (!el) return;
        el.disabled = false;
        el.removeAttribute('disabled');
        el.removeAttribute('readonly');
        el.style.pointerEvents = 'auto';
        el.style.opacity = '1';
    }

    function clearCustomerForm() {
        var form = document.getElementById('customerCreationForm');
        if (form) form.reset();
        var defBr = form && form.getAttribute('data-default-opening-branch');
        var defBid = defBr !== null && defBr !== '' ? parseInt(defBr, 10) : 0;
        if (defBid > 0 && document.getElementById('openingBranchId')) {
            setVal('openingBranchId', String(defBid));
        }
        ensureOpeningBranchInteractable();
        var preview = document.getElementById('ledgerPhotoPreview');
        if (preview) preview.style.display = 'none';
        var photoInput = document.getElementById('ledgerPhotoInput');
        if (photoInput) photoInput.value = '';
        var shareHoldersBody = document.getElementById('shareHoldersTableBody');
        if (shareHoldersBody) shareHoldersBody.innerHTML = '';
        shareHolderRowIndex = 0;
        shareHoldersData = [];
        var fileList = document.getElementById('shareHolderFileList');
        if (fileList) fileList.innerHTML = '';
        shareHolderFiles = [];
    }

    function saveCustomer(saveBtn) {
        var form = document.getElementById('customerCreationForm');
        if (!form || !form.checkValidity()) {
            if (form) form.reportValidity();
            return;
        }
        var ledgerIdEl = document.getElementById('ledgerCustomerId');
        var isNewCustomer = !ledgerIdEl || !String(ledgerIdEl.value || '').trim();
        var customerTypeEl = document.getElementById('customerType');
        if (isNewCustomer && customerTypeEl && !String(customerTypeEl.value || '').trim()) {
            alert('Customer type is required');
            customerTypeEl.focus();
            return;
        }
        var formData = new FormData(form);
        saveBtn = saveBtn || document.getElementById('saveCustomerBtn');
        var originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="feather icon-loader spin"></i> Saving...';
        saveBtn.disabled = true;
        fetch('customer-save.php', { method: 'POST', body: formData })
            .then(function(response) {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text().then(function(text) {
                    try { return JSON.parse(text); } catch (e) { throw new Error('Invalid JSON response'); }
                });
            })
            .then(function(data) {
                if (data.status === 'success' || data.success === true) {
                    alert(data.message || 'Customer created successfully!');
                    window.location.href = 'account-ledger.php';
                } else {
                    alert('Error: ' + (data.message || 'Failed to create customer'));
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Error saving customer: ' + error.message);
            })
            .finally(function() {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
    }

    window.previewLedgerPhoto = previewLedgerPhoto;
    window.handleNameInput = handleNameInput;
    window.clearCustomerForm = clearCustomerForm;
    window.saveCustomer = saveCustomer;
    window.sortShareHoldersTable = sortShareHoldersTable;
    window.handleShareHolderFileDrop = handleShareHolderFileDrop;
    window.handleShareHolderFileSelect = handleShareHolderFileSelect;

    function setVal(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val || '';
    }

    var ledgerOpeningBranchFromUrl = <?php echo json_encode($ledger_opening_branch_from_url); ?>;

    function getCustomerAjaxUrl(customerId, branchOverride) {
        var qs = 'ajax/get-customer.php?customer_id=' + encodeURIComponent(customerId);
        if (branchOverride !== undefined && branchOverride !== null) {
            qs += '&branch_id=' + encodeURIComponent(String(branchOverride));
        } else if (ledgerOpeningBranchFromUrl !== null) {
            qs += '&branch_id=' + encodeURIComponent(String(ledgerOpeningBranchFromUrl));
        }
        return qs;
    }

    function applyOpeningSidebarFromCustomer(c) {
        setVal('openingBalance', c.opening_balance || '0');
        if (c.opening_type === 'Debit') {
            document.getElementById('openingDebit').checked = true;
        } else {
            document.getElementById('openingCredit').checked = true;
        }
        setVal('openingBranchId', c.opening_branch_id != null && c.opening_branch_id !== '' ? String(c.opening_branch_id) : '');
        ensureOpeningBranchInteractable();
    }

    function loadCustomerForEdit(customerId) {
        if (!customerId) return;
        fetch(getCustomerAjaxUrl(customerId))
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.status !== 'success' || !res.customer) return;
                var c = res.customer;
                setVal('customerId', c.id);
                setVal('ledgerName', c.name);
                setVal('ledgerAlternateName', c.alternate_name);
                setVal('ledgerFirstName', c.first_name);
                setVal('ledgerLastName', c.last_name);
                setVal('mobileCountryCode', c.mobile_country_code || '971');
                setVal('ledgerMobileNo', c.mobile_no || '');
                setVal('ledgerPhoneNo', c.phone_no || '');
                setVal('ledgerMailId', c.mail_id || '');
                setVal('ledgerIdentityNo', c.identity_no || '');
                setVal('ledgerNationalId', c.national_id || '');
                setVal('ledgerTradeNo', c.trade_no || '');
                setVal('identityIssueDate', c.identity_issue_date || '');
                setVal('identityExpiryDate', c.identity_expiry_date || '');
                setVal('specialDay', c.special_day || '');
                setVal('customerType', c.customer_type_id || '');
                setVal('registrationNo', c.registration_no || '');
                setVal('registrationDate', c.registration_date || '');
                setVal('nationality', c.nationality_id || '');
                setVal('country', c.country_id || '');
                setVal('ledgerGroup', c.group_id || '');
                setVal('ledgerSundryDebtors', c.sundry_debtors_id || '');
                document.getElementById('ledgerKYC').checked = !!c.kyc;
                document.getElementById('ledgerAML').checked = !!c.aml;
                if (c.bill_to_bill == 1) document.getElementById('billToBillYes').checked = true;
                else document.getElementById('billToBillNo').checked = true;
                document.getElementById('ledgerNameCapital').checked = !!c.ledger_name_capital;
                setVal('billingAddress1', c.billing_address1 || '');
                setVal('billingAddress2', c.billing_address2 || '');
                setVal('billingZipCode', c.billing_zip_code || '');
                setVal('shippingAddress1', c.shipping_address1 || '');
                setVal('shippingAddress2', c.shipping_address2 || '');
                setVal('shippingZipCode', c.shipping_zip_code || '');
                if (typeof window.prefillCustomerLedgerAddressesAsync === 'function') {
                    window.prefillCustomerLedgerAddressesAsync(c);
                }
                setVal('bankAccountNo', c.bank_account_no || '');
                setVal('bankName', c.bank_name || '');
                setVal('bankIfscCode', c.bank_ifsc_code || '');
                setVal('bankBranch', c.bank_branch || '');
                setVal('ledgerNotes', c.notes || '');
                if (c.ledger_photo) {
                    document.getElementById('ledgerPhotoPreview').style.display = 'block';
                    document.getElementById('ledgerPhotoImg').src = c.ledger_photo;
                }
                applyOpeningSidebarFromCustomer(c);
            })
            .catch(function(err) { console.error('Load customer error:', err); });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var addBtn = document.getElementById('addShareHolderBtn');
        if (addBtn) addBtn.addEventListener('click', addShareHolderRow);
        var editId = <?php echo json_encode($edit_customer_id); ?>;
        if (editId) loadCustomerForEdit(editId);
        ensureOpeningBranchInteractable();
        var openingBranchEl = document.getElementById('openingBranchId');
        if (openingBranchEl) {
            openingBranchEl.addEventListener('change', function() {
                var cidEl = document.getElementById('customerId');
                var cid = cidEl && cidEl.value ? parseInt(cidEl.value, 10) : 0;
                if (!cid) return;
                var v = this.value;
                var branchParam = v === '' ? 0 : parseInt(v, 10);
                fetch(getCustomerAjaxUrl(cid, branchParam))
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.status !== 'success' || !res.customer) return;
                        applyOpeningSidebarFromCustomer(res.customer);
                    })
                    .catch(function(err) { console.error('Load opening for branch:', err); });
            });
        }
    });
})();
</script>

<script src="js/customer-ledger-address.js"></script>
<?php include 'footer-script.php'; ?>
</body>
</html>

