<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

$st_tree_root_id = function_exists('auragold_branch_stock_transfer_tree_root_id')
    ? (int) auragold_branch_stock_transfer_tree_root_id()
    : (function_exists('auragold_settings_main_branch_id') ? (int) auragold_settings_main_branch_id() : 0);
if ($st_tree_root_id > 0) {
    $branches = getListMaster(
        'SELECT id, name, code FROM tbl_branches WHERE status = 1 AND (id = ' . $st_tree_root_id
        . ' OR IFNULL(main_branch_id, 0) = ' . $st_tree_root_id . ') ORDER BY name ASC'
    );
} else {
    $branches = getListMaster("SELECT id, name, code FROM tbl_branches WHERE status = 1 ORDER BY name ASC");
}
if (!is_array($branches)) {
    $branches = [];
}

$default_branch_id = 0;
if (!empty($_SESSION['working_branch_id'])) {
    $default_branch_id = (int) $_SESSION['working_branch_id'];
} elseif (!empty($_SESSION['branch_id'])) {
    $default_branch_id = (int) $_SESSION['branch_id'];
}
if ($default_branch_id > 0 && !empty($branches)) {
    $in_branch_list = false;
    foreach ($branches as $b) {
        if ((int) ($b['id'] ?? 0) === $default_branch_id) {
            $in_branch_list = true;
            break;
        }
    }
    if (!$in_branch_list) {
        $default_branch_id = 0;
        if ($st_tree_root_id > 0) {
            foreach ($branches as $b) {
                if ((int) ($b['id'] ?? 0) === $st_tree_root_id) {
                    $default_branch_id = $st_tree_root_id;
                    break;
                }
            }
        }
        if ($default_branch_id <= 0 && isset($branches[0]['id'])) {
            $default_branch_id = (int) $branches[0]['id'];
        }
    }
}

$today_ymd = date('Y-m-d');
$active_mysql_db = defined('DB_NAME') ? (string) DB_NAME : '';
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Stock Transfer — AuraGold</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include __DIR__ . '/header-script.php'; ?>
<style>
    .st-wrap {
        --st-accent: #7c6fd6;
        --st-accent-soft: #ece9ff;
        --st-border: #e8e6f2;
    }
    .st-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .st-toolbar-left, .st-toolbar-right {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
    }
    .st-date-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .st-date-wrap label {
        margin: 0;
        font-size: 13px;
        color: #4a5568;
    }
    .st-panel {
        background: #fff;
        border: 1px solid var(--st-border);
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(80, 72, 140, 0.06);
        min-height: 420px;
        display: flex;
        flex-direction: column;
    }
    .st-panel-head {
        padding: 12px 14px;
        border-bottom: 1px solid var(--st-border);
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px;
    }
    .st-panel-head .form-group {
        margin-bottom: 0;
    }
    .st-panel-title {
        font-weight: 650;
        font-size: 14px;
        color: #1d2c4f;
        margin-right: 8px;
    }
    .st-table-wrap {
        flex: 1;
        overflow: auto;
        max-height: calc(100vh - 280px);
    }
    .st-table {
        font-size: 13px;
        margin-bottom: 0;
    }
    /* Navy header + white text (overrides theme / Bootstrap .table rules) */
    .st-wrap .st-table thead th {
        background: #1a2d4a !important;
        color: #fff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-top: none !important;
        white-space: nowrap;
        font-weight: 600;
        vertical-align: middle;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .st-wrap .st-table thead th,
    .st-wrap .st-table thead th a {
        color: #fff !important;
    }
    .st-wrap .st-table thead th input[type="checkbox"] {
        filter: brightness(0) invert(1);
        cursor: pointer;
    }
    .st-wrap .st-table {
        border-collapse: collapse;
    }
    .st-wrap .st-table th,
    .st-wrap .st-table td {
        border: 1px solid #b8c0cc;
        vertical-align: middle;
    }
    .st-wrap .st-table thead th {
        border-color: rgba(255, 255, 255, 0.35);
    }
    .st-wrap .st-table tbody tr:hover td {
        background: #faf9ff;
    }
    .st-thumb {
        width: 36px;
        height: 36px;
        border-radius: 6px;
        background: var(--st-accent-soft);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--st-accent);
        font-size: 16px;
    }
    .st-footer-total {
        display: flex;
        justify-content: flex-end;
        padding: 10px 14px;
        border-top: 1px solid var(--st-border);
        font-weight: 600;
        background: #faf9ff;
    }
    .st-btn-outline-accent {
        border-color: var(--st-accent);
        color: var(--st-accent);
        background: #fff;
    }
    .st-btn-outline-accent:hover {
        background: var(--st-accent-soft);
        color: #5a4fc4;
    }
    .st-empty {
        text-align: center;
        padding: 48px 16px;
        color: #8892a6;
        font-size: 14px;
    }
    .st-filter-badge {
        position: relative;
    }
    .st-filter-badge .badge {
        position: absolute;
        top: -6px;
        right: -6px;
        font-size: 10px;
    }
    .st-filter-bar {
        padding: 8px 12px 10px;
        background: #fff;
    }
    .st-filter-input {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding-left: 2px;
        padding-right: 2px;
        background: transparent;
        border-bottom: 2px solid #ff9800 !important;
    }
    .st-filter-input:focus {
        outline: none;
        box-shadow: none !important;
        border-bottom-color: #f57c00 !important;
        background: transparent;
    }
    .st-filter-input::placeholder {
        color: #9ca3af;
    }
    .st-filter-bar.st-filter-bar-with-cols {
        display: flex;
        align-items: stretch;
        flex-wrap: wrap;
        gap: 4px;
    }
    .st-filter-bar.st-filter-bar-with-cols .st-filter-input {
        flex: 1 1 120px;
        min-width: 120px;
    }
    .st-col-settings-wrap {
        position: relative;
        flex-shrink: 0;
        align-self: center;
    }
    .st-col-settings-btn {
        color: #64748b !important;
        padding: 6px 8px !important;
        line-height: 1;
    }
    .st-col-settings-btn:hover {
        color: var(--st-accent) !important;
        background: var(--st-accent-soft) !important;
        border-radius: 6px;
    }
    .st-wrap .columns-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1050;
        min-width: 260px;
        max-width: 320px;
        display: none;
        margin-top: 6px;
    }
    .st-wrap .columns-dropdown.show {
        display: block;
    }
    .st-wrap .columns-dropdown-header {
        padding: 10px 14px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        font-size: 0.85rem;
        color: #1d2c4f;
    }
    .st-wrap .columns-dropdown-search {
        padding: 8px 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .st-wrap .columns-dropdown-search input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.8rem;
    }
    .st-wrap .columns-dropdown-list {
        max-height: 280px;
        overflow-y: auto;
        padding: 6px 0;
    }
    .st-wrap .columns-dropdown-item {
        padding: 6px 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .st-wrap .columns-dropdown-item:hover {
        background: #f8fafc;
    }
    .st-wrap .columns-dropdown-item input[type="checkbox"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    .st-wrap .columns-dropdown-item label {
        margin: 0;
        cursor: pointer;
        font-size: 0.8rem;
        color: #334155;
        flex: 1;
    }
    .st-col-order-wrap {
        padding: 8px 12px 10px;
        border-bottom: 1px solid #e2e8f0;
        max-height: 200px;
        overflow-y: auto;
    }
    .st-col-order-wrap .st-col-order-title {
        font-size: 0.72rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 6px;
    }
    .st-col-order-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .st-col-order-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        background: #f8fafc;
        font-size: 0.78rem;
        color: #334155;
        cursor: grab;
        user-select: none;
    }
    .st-col-order-item:active {
        cursor: grabbing;
    }
    .st-col-order-item.st-col-order-dragging {
        opacity: 0.55;
    }
    .st-col-order-item .feather {
        flex-shrink: 0;
        color: #94a3b8;
    }
    .layout-content .container-fluid { padding-bottom: 12px; }
    /* Two blocks side by side (source | destination), like reference UI */
    .st-stock-transfer-row {
        align-items: stretch;
    }
    .st-stock-transfer-row > [class*="col-"] {
        display: flex;
        flex-direction: column;
    }
    .st-stock-transfer-row .st-panel {
        flex: 1 1 auto;
        width: 100%;
        min-height: min(520px, 70vh);
    }
    .st-table-wrap .st-table-wide {
        min-width: 2800px;
    }
    .st-img-cell img {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid var(--st-border);
    }
    .st-text-clip {
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .st-cell-num {
        font-variant-numeric: tabular-nums;
    }
    .st-source-row {
        cursor: grab;
    }
    .st-source-row:active {
        cursor: grabbing;
    }
    .st-source-row.st-dragging {
        opacity: 0.45;
    }
    .st-drag-handle {
        cursor: grab;
        user-select: none;
        width: 40px;
        text-align: center;
    }
    .st-drag-handle:active {
        cursor: grabbing;
    }
    #stDestDropZone.st-drop-active {
        outline: 2px dashed var(--st-accent);
        outline-offset: -2px;
        background: var(--st-accent-soft);
        border-radius: 8px;
    }
</style>
</head>
<body>
<div class="layout-wrapper layout-2">
    <div class="layout-inner">
        <div id="layout-sidenav" class="layout-sidenav sidenav sidenav-vertical bg-white logo-dark" aria-hidden="true"></div>
        <div class="layout-container">
            <nav class="layout-navbar navbar navbar-expand-lg align-items-lg-center bg-dark container-p-x" id="layout-navbar" aria-hidden="true"></nav>
            <div class="layout-content">
                <div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="st-wrap p-3">
    <div class="st-toolbar">
        <div class="st-toolbar-left">
            <div class="st-date-wrap">
                <label for="stTransferDate">Date</label>
                <input type="date" class="form-control form-control-sm" id="stTransferDate" value="<?php echo htmlspecialchars($today_ymd); ?>" style="width:160px;">
                <button type="button" class="btn btn-sm btn-light border" id="stDateRefresh" title="Today"><i class="feather icon-refresh-cw"></i></button>
            </div>
        </div>
        <div class="st-toolbar-right">
            <button type="button" class="btn btn-sm st-btn-outline-accent" id="stBtnLoose" title="Reserved for future use">Transfer Loose Items</button>
            <button type="button" class="btn btn-sm btn-light border st-filter-badge" id="stBtnFilter" title="Filter"><i class="feather icon-filter"></i><span class="badge badge-danger" id="stFilterCount" style="display:none;">0</span></button>
            <button type="button" class="btn btn-sm btn-light border" id="stBtnRefreshAll" title="Reload source list"><i class="feather icon-refresh-cw"></i></button>
            <button type="button" class="btn btn-sm btn-secondary" id="stBtnSave" disabled>Save</button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="stBtnPrintBc" title="Print barcode for items in transfer list">Print Barcode</button>
            <a href="stock-transfer-history.php" class="btn btn-sm btn-light border" title="View past transfers"><i class="feather icon-list"></i> History</a>
            <a href="stock-receive-history.php" class="btn btn-sm btn-light border" title="Stock received at branch after transfer"><i class="feather icon-download"></i> Receive</a>
        </div>
    </div>

    <div class="row st-stock-transfer-row">
        <div class="col-12 col-lg-6 mb-3">
            <div class="st-panel">
                <div class="st-panel-head">
                    <span class="st-panel-title">Source branch</span>
                    <div class="form-group">
                        <label class="small text-muted mb-0">Branch</label>
                        <select class="form-control form-control-sm" id="stFromBranch" style="min-width:180px;">
                            <option value="">— Select —</option>
                            <?php foreach ($branches as $b): ?>
                                <?php $bid = (int) ($b['id'] ?? 0); ?>
                                <option value="<?php echo $bid; ?>"<?php echo ($default_branch_id > 0 && $default_branch_id === $bid) ? ' selected' : ''; ?>><?php echo htmlspecialchars($b['name'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" id="stApplySource" title="Loads lines with on-hand qty. or weight &gt; 0 at the selected branch (non-outward)">Apply</button>
                    <div class="flex-grow-1"></div>
                    <div class="form-group" style="min-width:200px;">
                        <label class="small text-muted mb-0">Barcode</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="stBarcodeIn" placeholder="Scan or enter" autocomplete="off">
                            <div class="input-group-append">
                                <span class="input-group-text bg-white"><i class="feather icon-maximize-2" style="font-size:14px;"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="st-filter-bar border-bottom st-filter-bar-with-cols">
                    <input type="text" class="form-control form-control-sm st-filter-input" id="stSourceFilter" placeholder="Filter by name / barcode / article…" autocomplete="off">
                    <div class="st-col-settings-wrap">
                        <button type="button" class="btn btn-sm btn-link st-col-settings-btn" id="stSourceColBtn" title="Show / hide columns" aria-haspopup="true" aria-expanded="false"><i class="feather icon-settings" style="font-size:18px;"></i></button>
                        <div class="columns-dropdown" id="stSourceColDropdown" aria-hidden="true">
                            <div class="columns-dropdown-header">Columns</div>
                            <div class="columns-dropdown-search">
                                <input type="text" id="stSourceColSearch" placeholder="Search columns…" autocomplete="off">
                            </div>
                            <div class="st-col-order-wrap">
                                <div class="st-col-order-title">Column order (drag)</div>
                                <div class="st-col-order-list" id="stSourceColOrderList"></div>
                            </div>
                            <div class="columns-dropdown-list" id="stSourceColList"></div>
                        </div>
                    </div>
                </div>
                <div class="st-table-wrap">
                    <table class="table table-sm table-bordered st-table st-table-wide" id="stTableSource">
                        <thead>
                            <tr>
                                <th data-col="st_cb" style="width:36px;"><input type="checkbox" id="stSourceSelectAll" title="Select all"></th>
                                <th data-col="st_drag" style="width:40px;"></th>
                                <th data-col="st_img" style="width:48px;">Img</th>
                                <th data-col="net_amt" class="text-right">Net Amt</th>
                                <th data-col="date">Date</th>
                                <th data-col="view" class="text-center">View</th>
                                <th data-col="barcode">Barcode...</th>
                                <th data-col="product_name" style="min-width:140px;">Product Name</th>
                                <th data-col="rfid">RFID</th>
                                <th data-col="location">Location</th>
                                <th data-col="against_invoice">Against Invoice No</th>
                                <th data-col="type_of_voucher">Type Of Voucher</th>
                                <th data-col="voucher_type">Voucher Type</th>
                                <th data-col="invoice">Invoice</th>
                                <th data-col="branch">Branch</th>
                                <th data-col="qty" class="text-right">Qty.</th>
                                <th data-col="gross_wt" class="text-right">Gross Wt</th>
                                <th data-col="purity" class="text-right">Pu</th>
                                <th data-col="pure_wt" class="text-right">Pure Wt.</th>
                                <th data-col="requested_qty" class="text-right">Requested Qty</th>
                                <th data-col="requested_wt" class="text-right">Requested Wt</th>
                                <th data-col="stone_wt" class="text-right">Stone Wt</th>
                                <th data-col="diamond_wt" class="text-right">Diamond Wt</th>
                                <th data-col="less_wt" class="text-right">Less Wt.</th>
                                <th data-col="purity_wt" class="text-right">Purity Wt</th>
                                <th data-col="wastage_per" class="text-right">Wastage Per.</th>
                                <th data-col="wastage_wt" class="text-right">Wastage Wt.</th>
                                <th data-col="net_wt" class="text-right">Net Wt</th>
                                <th data-col="alloy_wt" class="text-right">Alloy Wt.</th>
                                <th data-col="final_wt" class="text-right">Final Wt</th>
                                <th data-col="standard_wt" class="text-right">Standard Wt</th>
                                <th data-col="actual_wt" class="text-right">Actual Wt</th>
                                <th data-col="national_wt" class="text-right">National Wt</th>
                                <th data-col="name">Name</th>
                                <th data-col="making_rate" class="text-right">Making Rate</th>
                                <th data-col="amt" class="text-right">Amt</th>
                                <th data-col="making_amt" class="text-right">Making Amt</th>
                                <th data-col="amount" class="text-right">Amount</th>
                                <th data-col="hui_code">HUI Code</th>
                                <th data-col="packet_wt" class="text-right">Packet Wt</th>
                                <th data-col="packet_length" class="text-right">Packet L.</th>
                                <th data-col="rate" class="text-right">Rate</th>
                                <th data-col="hallmark1">Hallmark 1</th>
                                <th data-col="hallmark2">Hallmark 2</th>
                                <th data-col="net_amt_with_tax" class="text-right">Net Amt W/Tax</th>
                                <th data-col="tax_amt" class="text-right">Tax Amt</th>
                                <th data-col="discount_per" class="text-right">Discount %</th>
                                <th data-col="discount_amt" class="text-right">Discount Amt</th>
                                <th data-col="metal_value" class="text-right">Metal Val.</th>
                                <th data-col="purchase" class="text-right">Purchase</th>
                            </tr>
                        </thead>
                        <tbody id="stTableSourceBody">
                            <tr><td colspan="50" class="st-empty">Select branch and Apply to load stock.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="st-footer-total">
                    <span>Amount: <span id="stSourceTotal">0.00</span></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6 mb-3">
            <div class="st-panel">
                <div class="st-panel-head">
                    <span class="st-panel-title">Destination branch</span>
                    <div class="form-group">
                        <label class="small text-muted mb-0">Branch</label>
                        <select class="form-control form-control-sm" id="stToBranch" style="min-width:200px;">
                            <option value="">— Select —</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo (int) $b['id']; ?>"><?php echo htmlspecialchars($b['name'] ?? ''); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="stAddSelected" title="Move selected rows here, or drag rows from the left table into the grid below"><i class="feather icon-arrow-right"></i> Add selected</button>
                    <button type="button" class="btn btn-sm btn-light border" id="stClearDest" title="Clear transfer list">Clear</button>
                </div>
                <div class="st-filter-bar border-bottom st-filter-bar-with-cols">
                    <input type="text" class="form-control form-control-sm st-filter-input" id="stDestFilter" placeholder="Filter by name / barcode / article…" autocomplete="off">
                    <div class="st-col-settings-wrap">
                        <button type="button" class="btn btn-sm btn-link st-col-settings-btn" id="stDestColBtn" title="Show / hide columns" aria-haspopup="true" aria-expanded="false"><i class="feather icon-settings" style="font-size:18px;"></i></button>
                        <div class="columns-dropdown" id="stDestColDropdown" aria-hidden="true">
                            <div class="columns-dropdown-header">Columns</div>
                            <div class="columns-dropdown-search">
                                <input type="text" id="stDestColSearch" placeholder="Search columns…" autocomplete="off">
                            </div>
                            <div class="st-col-order-wrap">
                                <div class="st-col-order-title">Column order (drag)</div>
                                <div class="st-col-order-list" id="stDestColOrderList"></div>
                            </div>
                            <div class="columns-dropdown-list" id="stDestColList"></div>
                        </div>
                    </div>
                </div>
                <div class="st-table-wrap" id="stDestDropZone" title="Drop rows here to add to transfer list">
                    <table class="table table-sm table-bordered st-table st-table-wide" id="stTableDest">
                        <thead>
                            <tr>
                                <th data-col="st_cb" style="width:36px;"><input type="checkbox" id="stDestSelectAll"></th>
                                <th data-col="net_amt" class="text-right">Net Amt</th>
                                <th data-col="date">Date</th>
                                <th data-col="view" class="text-center">View</th>
                                <th data-col="barcode">Barcode...</th>
                                <th data-col="product_name" style="min-width:140px;">Product Name</th>
                                <th data-col="rfid">RFID</th>
                                <th data-col="location">Location</th>
                                <th data-col="against_invoice">Against Invoice No</th>
                                <th data-col="type_of_voucher">Type Of Voucher</th>
                                <th data-col="voucher_type">Voucher Type</th>
                                <th data-col="invoice">Invoice</th>
                                <th data-col="branch">Branch</th>
                                <th data-col="qty" class="text-right">Qty.</th>
                                <th data-col="gross_wt" class="text-right">Gross Wt</th>
                                <th data-col="purity" class="text-right">Pu</th>
                                <th data-col="pure_wt" class="text-right">Pure Wt.</th>
                                <th data-col="requested_qty" class="text-right">Requested Qty</th>
                                <th data-col="requested_wt" class="text-right">Requested Wt</th>
                                <th data-col="stone_wt" class="text-right">Stone Wt</th>
                                <th data-col="diamond_wt" class="text-right">Diamond Wt</th>
                                <th data-col="less_wt" class="text-right">Less Wt.</th>
                                <th data-col="purity_wt" class="text-right">Purity Wt</th>
                                <th data-col="wastage_per" class="text-right">Wastage Per.</th>
                                <th data-col="wastage_wt" class="text-right">Wastage Wt.</th>
                                <th data-col="net_wt" class="text-right">Net Wt</th>
                                <th data-col="alloy_wt" class="text-right">Alloy Wt.</th>
                                <th data-col="final_wt" class="text-right">Final Wt</th>
                                <th data-col="standard_wt" class="text-right">Standard Wt</th>
                                <th data-col="actual_wt" class="text-right">Actual Wt</th>
                                <th data-col="national_wt" class="text-right">National Wt</th>
                                <th data-col="name">Name</th>
                                <th data-col="making_rate" class="text-right">Making Rate</th>
                                <th data-col="amt" class="text-right">Amt</th>
                                <th data-col="making_amt" class="text-right">Making Amt</th>
                                <th data-col="amount" class="text-right">Amount</th>
                                <th data-col="hui_code">HUI Code</th>
                                <th data-col="packet_wt" class="text-right">Packet Wt</th>
                                <th data-col="packet_length" class="text-right">Packet L.</th>
                                <th data-col="rate" class="text-right">Rate</th>
                                <th data-col="hallmark1">Hallmark 1</th>
                                <th data-col="hallmark2">Hallmark 2</th>
                                <th data-col="net_amt_with_tax" class="text-right">Net Amt W/Tax</th>
                                <th data-col="tax_amt" class="text-right">Tax Amt</th>
                                <th data-col="discount_per" class="text-right">Discount %</th>
                                <th data-col="discount_amt" class="text-right">Discount Amt</th>
                                <th data-col="metal_value" class="text-right">Metal Val.</th>
                                <th data-col="purchase" class="text-right">Purchase</th>
                                <th data-col="st_remove" style="width:72px;"></th>
                            </tr>
                        </thead>
                        <tbody id="stTableDestBody">
                            <tr><td colspan="49" class="st-empty">No Rows To Show</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="st-footer-total">
                    <span>Amount: <span id="stDestTotal">0.00</span></span>
                </div>
            </div>
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/libs/bootstrap-sweetalert/bootstrap-sweetalert.js"></script>
<script>
(function () {
    var todayYmd = <?php echo json_encode($today_ymd); ?>;
    var defaultBranchId = <?php echo (int) $default_branch_id; ?>;

    function stSwalOrAlert(opts) {
        if (typeof swal === 'function') {
            swal(opts);
        } else {
            alert((opts.title ? opts.title + '\n\n' : '') + (opts.text || ''));
        }
    }

    function stShowMsg(title, text, type) {
        stSwalOrAlert({
            title: title || (type === 'error' ? 'Error' : 'Notice'),
            text: text || '',
            type: type || 'warning',
            confirmButtonText: 'OK'
        });
    }

    var sourceRows = [];
    var destRows = [];
    var filterText = '';
    var filterTextDest = '';

    function fmtMoney(n) {
        var x = parseFloat(n) || 0;
        return x.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtOptNum(n) {
        if (n === null || n === undefined || n === '') return '—';
        var x = parseFloat(n);
        if (isNaN(x)) return '—';
        return String(x);
    }

    function fmtMoneyDash(v) {
        if (v === null || v === undefined || v === '') return '—';
        return fmtMoney(v);
    }

    function fmtTextDash(s) {
        if (s === null || s === undefined || String(s).trim() === '') return '—';
        return String(s);
    }

    function firstImageSrc(raw) {
        if (!raw) return '';
        var s = String(raw).trim();
        if (!s) return '';
        if (s.charAt(0) === '[') {
            try {
                var arr = JSON.parse(s);
                if (Array.isArray(arr) && arr[0]) return String(arr[0]).trim();
            } catch (e) {}
        }
        var part = s.split(',')[0].trim().replace(/^["']|["']$/g, '');
        if (part.indexOf('http') === 0 || part.indexOf('/') === 0) return part;
        if (part) return part;
        return '';
    }

    function thumbHtml(imageUrls) {
        var src = firstImageSrc(imageUrls);
        if (src) {
            return '<div class="st-img-cell"><img src="' + escapeHtml(src) + '" alt=""></div>';
        }
        return '<div class="st-thumb"><i class="feather icon-image"></i></div>';
    }

    function fmtDateDmY(d) {
        if (d === null || d === undefined || d === '') return '—';
        var s = String(d);
        if (s.indexOf(' ') >= 0) s = s.split(' ')[0];
        var p = s.split('-');
        if (p.length === 3) return p[2] + '/' + p[1] + '/' + p[0];
        return s;
    }

    function fmtNumFixed(n, minF, maxF) {
        if (n === null || n === undefined || n === '') {
            n = 0;
        }
        var x = parseFloat(n);
        if (isNaN(x)) {
            return '—';
        }
        return x.toLocaleString(undefined, { minimumFractionDigits: minF, maximumFractionDigits: maxF });
    }

    function viewCellInner(r) {
        var iid = parseInt(r.invoice_id, 10) || 0;
        if (iid > 0) {
            return '<a href="purchase-invoice.php?id=' + iid + '" class="st-view-link" target="_blank" rel="noopener" draggable="false">View</a>';
        }
        return '<span class="text-muted">-</span>';
    }

    /** Each data column td; order is applied by rowDataCellsHtml(which). */
    function stBuildDataCellsParts(r) {
        var invNo = r.against_invoice_no != null ? String(r.against_invoice_no) : '';
        var vt = r.type_of_voucher != null ? String(r.type_of_voucher) : '';
        var vtype = r.voucher_type != null ? String(r.voucher_type) : '';
        return {
            net_amt: '<td data-col="net_amt" class="text-right st-cell-num">' + fmtMoney(r.net_amt != null ? r.net_amt : 0) + '</td>',
            date: '<td data-col="date">' + escapeHtml(fmtDateDmY(r.transaction_date)) + '</td>',
            view: '<td data-col="view" class="text-center">' + viewCellInner(r) + '</td>',
            barcode: '<td data-col="barcode">' + escapeHtml(fmtTextDash(r.barcode)) + '</td>',
            product_name: '<td data-col="product_name" class="st-text-clip" style="max-width:220px;" title="' + escapeHtml(String(r.product_name != null ? r.product_name : '')) + '">' + escapeHtml(fmtTextDash(r.product_name)) + '</td>',
            rfid: '<td data-col="rfid">' + escapeHtml(fmtTextDash(r.rfid)) + '</td>',
            location: '<td data-col="location">' + escapeHtml(fmtTextDash(r.location)) + '</td>',
            against_invoice: '<td data-col="against_invoice">' + escapeHtml(fmtTextDash(r.against_invoice_no)) + '</td>',
            type_of_voucher: '<td data-col="type_of_voucher">' + escapeHtml(fmtTextDash(vt)) + '</td>',
            voucher_type: '<td data-col="voucher_type">' + escapeHtml(fmtTextDash(vtype)) + '</td>',
            invoice: '<td data-col="invoice">' + escapeHtml(fmtTextDash(invNo)) + '</td>',
            branch: '<td data-col="branch">' + escapeHtml(fmtTextDash(r.branch_name)) + '</td>',
            qty: '<td data-col="qty" class="text-right st-cell-num">' + fmtNumFixed(r.qty, 0, 0) + '</td>',
            gross_wt: '<td data-col="gross_wt" class="text-right st-cell-num">' + fmtNumFixed(r.gross_wt, 3, 3) + '</td>',
            purity: '<td data-col="purity" class="text-right st-cell-num">' + fmtNumFixed(r.purity, 2, 2) + '</td>',
            pure_wt: '<td data-col="pure_wt" class="text-right st-cell-num">' + fmtNumFixed(r.pure_wt, 3, 3) + '</td>',
            requested_qty: '<td data-col="requested_qty" class="text-right st-cell-num">' + fmtNumFixed(r.requested_qty, 3, 3) + '</td>',
            requested_wt: '<td data-col="requested_wt" class="text-right st-cell-num">' + fmtNumFixed(r.requested_wt, 2, 2) + '</td>',
            stone_wt: '<td data-col="stone_wt" class="text-right st-cell-num">' + fmtNumFixed(r.stone_wt, 2, 2) + '</td>',
            diamond_wt: '<td data-col="diamond_wt" class="text-right st-cell-num">' + fmtNumFixed(r.diamond_wt, 2, 2) + '</td>',
            less_wt: '<td data-col="less_wt" class="text-right st-cell-num">' + fmtNumFixed(r.less_wt, 3, 3) + '</td>',
            purity_wt: '<td data-col="purity_wt" class="text-right st-cell-num">' + fmtNumFixed(r.purity_wt, 3, 3) + '</td>',
            wastage_per: '<td data-col="wastage_per" class="text-right st-cell-num">' + fmtNumFixed(r.wastage_per, 2, 2) + '</td>',
            wastage_wt: '<td data-col="wastage_wt" class="text-right st-cell-num">' + fmtNumFixed(r.wastage_wt, 3, 3) + '</td>',
            net_wt: '<td data-col="net_wt" class="text-right st-cell-num">' + fmtNumFixed(r.net_wt, 3, 3) + '</td>',
            alloy_wt: '<td data-col="alloy_wt" class="text-right st-cell-num">' + fmtNumFixed(r.alloy_wt, 3, 3) + '</td>',
            final_wt: '<td data-col="final_wt" class="text-right st-cell-num">' + fmtNumFixed(r.final_wt, 3, 3) + '</td>',
            standard_wt: '<td data-col="standard_wt" class="text-right st-cell-num">' + fmtNumFixed(r.standard_wt, 3, 3) + '</td>',
            actual_wt: '<td data-col="actual_wt" class="text-right st-cell-num">' + fmtNumFixed(r.actual_wt, 3, 3) + '</td>',
            national_wt: '<td data-col="national_wt" class="text-right st-cell-num">' + fmtNumFixed(r.national_wt, 3, 3) + '</td>',
            name: '<td data-col="name">' + escapeHtml(fmtTextDash(r.name)) + '</td>',
            making_rate: '<td data-col="making_rate" class="text-right st-cell-num">' + fmtNumFixed(r.making_rate, 2, 2) + '</td>',
            amt: '<td data-col="amt" class="text-right st-cell-num">' + fmtNumFixed(r.amt, 2, 2) + '</td>',
            making_amt: '<td data-col="making_amt" class="text-right st-cell-num">' + fmtNumFixed(r.making_amt, 2, 2) + '</td>',
            amount: '<td data-col="amount" class="text-right st-cell-num">' + fmtNumFixed(r.amount, 2, 2) + '</td>',
            hui_code: '<td data-col="hui_code">' + escapeHtml(fmtTextDash(r.hui_code)) + '</td>',
            packet_wt: '<td data-col="packet_wt" class="text-right st-cell-num">' + fmtNumFixed(r.packet_wt, 3, 3) + '</td>',
            packet_length: '<td data-col="packet_length" class="text-right st-cell-num">' + fmtNumFixed(r.packet_length, 3, 3) + '</td>',
            rate: '<td data-col="rate" class="text-right st-cell-num">' + fmtNumFixed(r.rate, 2, 2) + '</td>',
            hallmark1: '<td data-col="hallmark1">' + escapeHtml(fmtTextDash(r.hallmark1)) + '</td>',
            hallmark2: '<td data-col="hallmark2">' + escapeHtml(fmtTextDash(r.hallmark2)) + '</td>',
            net_amt_with_tax: '<td data-col="net_amt_with_tax" class="text-right st-cell-num">' + fmtNumFixed(r.net_amt_with_tax, 2, 2) + '</td>',
            tax_amt: '<td data-col="tax_amt" class="text-right st-cell-num">' + fmtNumFixed(r.tax_amt, 2, 2) + '</td>',
            discount_per: '<td data-col="discount_per" class="text-right st-cell-num">' + fmtNumFixed(r.discount_per, 2, 2) + '</td>',
            discount_amt: '<td data-col="discount_amt" class="text-right st-cell-num">' + fmtNumFixed(r.discount_amt, 2, 2) + '</td>',
            metal_value: '<td data-col="metal_value" class="text-right st-cell-num">' + fmtNumFixed(r.metal_value, 2, 2) + '</td>',
            purchase: '<td data-col="purchase" class="text-right st-cell-num">' + fmtNumFixed(r.purchase, 2, 2) + '</td>'
        };
    }

    function rowDataCellsHtml(r, which) {
        var parts = stBuildDataCellsParts(r);
        var order = stGetDataColOrder(which);
        var h = '';
        order.forEach(function (key) {
            if (parts[key]) {
                h += parts[key];
            }
        });
        return h;
    }

    function rowMatchesFilter(r) {
        if (!filterText) return true;
        var t = filterText.toLowerCase();
        try {
            return JSON.stringify(r).toLowerCase().indexOf(t) >= 0;
        } catch (e) {
            return true;
        }
    }

    function rowMatchesDestFilter(r) {
        if (!filterTextDest) return true;
        var t = filterTextDest.toLowerCase();
        try {
            return JSON.stringify(r).toLowerCase().indexOf(t) >= 0;
        } catch (e) {
            return true;
        }
    }

    var stDataColDefs = [
        ['net_amt', 'Net Amt'], ['date', 'Date'], ['view', 'View'], ['barcode', 'Barcode'], ['product_name', 'Product Name'],
        ['rfid', 'RFID'], ['location', 'Location'], ['against_invoice', 'Against Invoice No'], ['type_of_voucher', 'Type Of Voucher'],
        ['voucher_type', 'Voucher Type'], ['invoice', 'Invoice'], ['branch', 'Branch'], ['qty', 'Qty.'], ['gross_wt', 'Gross Wt'],
        ['purity', 'Pu'], ['pure_wt', 'Pure Wt.'], ['requested_qty', 'Requested Qty'], ['requested_wt', 'Requested Wt'],
        ['stone_wt', 'Stone Wt'], ['diamond_wt', 'Diamond Wt'], ['less_wt', 'Less Wt.'], ['purity_wt', 'Purity Wt'],
        ['wastage_per', 'Wastage Per.'], ['wastage_wt', 'Wastage Wt.'], ['net_wt', 'Net Wt'], ['alloy_wt', 'Alloy Wt.'],
        ['final_wt', 'Final Wt'], ['standard_wt', 'Standard Wt'], ['actual_wt', 'Actual Wt'], ['national_wt', 'National Wt'],
        ['name', 'Name'], ['making_rate', 'Making Rate'], ['amt', 'Amt'], ['making_amt', 'Making Amt'], ['amount', 'Amount'],
        ['hui_code', 'HUI Code'], ['packet_wt', 'Packet Wt'], ['packet_length', 'Packet L.'], ['rate', 'Rate'],
        ['hallmark1', 'Hallmark 1'], ['hallmark2', 'Hallmark 2'], ['net_amt_with_tax', 'Net Amt W/Tax'], ['tax_amt', 'Tax Amt'],
        ['discount_per', 'Discount %'], ['discount_amt', 'Discount Amt'], ['metal_value', 'Metal Val.'], ['purchase', 'Purchase']
    ];

    function stDefaultDataColKeys() {
        return stDataColDefs.map(function (x) {
            return x[0];
        });
    }

    function stLabelForDataCol(key) {
        for (var i = 0; i < stDataColDefs.length; i++) {
            if (stDataColDefs[i][0] === key) {
                return stDataColDefs[i][1];
            }
        }
        return key;
    }

    function stGetDataColOrder(which) {
        var def = stDefaultDataColKeys();
        var lsKey = which === 'source' ? 'auragold_st_transfer_col_order_src' : 'auragold_st_transfer_col_order_dest';
        try {
            var raw = localStorage.getItem(lsKey);
            if (raw) {
                var arr = JSON.parse(raw);
                if (Array.isArray(arr)) {
                    var seen = {};
                    var out = [];
                    arr.forEach(function (k) {
                        if (def.indexOf(k) >= 0 && !seen[k]) {
                            seen[k] = true;
                            out.push(k);
                        }
                    });
                    def.forEach(function (k) {
                        if (!seen[k]) {
                            seen[k] = true;
                            out.push(k);
                        }
                    });
                    return out;
                }
            }
        } catch (e) {}
        return def.slice();
    }

    function stSaveDataColOrder(which, orderArr) {
        try {
            var lsKey = which === 'source' ? 'auragold_st_transfer_col_order_src' : 'auragold_st_transfer_col_order_dest';
            localStorage.setItem(lsKey, JSON.stringify(orderArr));
        } catch (e) {}
    }

    function stRefreshColOrderList(which) {
        var id = which === 'source' ? 'stSourceColOrderList' : 'stDestColOrderList';
        var el = document.getElementById(id);
        if (!el) {
            return;
        }
        var order = stGetDataColOrder(which);
        var html = '';
        order.forEach(function (key) {
            html += '<div class="st-col-order-item" draggable="true" data-st-order-key="' + escapeHtml(key) + '" title="' + escapeHtml(stLabelForDataCol(key)) + '">' +
                '<i class="feather icon-menu" aria-hidden="true"></i><span>' + escapeHtml(stLabelForDataCol(key)) + '</span></div>';
        });
        el.innerHTML = html;
    }

    function stApplyColumnOrder(which) {
        var tableId = which === 'source' ? 'stTableSource' : 'stTableDest';
        var table = document.getElementById(tableId);
        if (!table) {
            return;
        }
        var theadRow = table.querySelector('thead tr');
        if (!theadRow) {
            return;
        }
        var prefix = which === 'source' ? ['st_cb', 'st_drag', 'st_img'] : ['st_cb'];
        var suffix = which === 'source' ? [] : ['st_remove'];
        var order = stGetDataColOrder(which);
        var byCol = {};
        theadRow.querySelectorAll('th[data-col]').forEach(function (th) {
            byCol[th.getAttribute('data-col')] = th;
        });
        prefix.concat(order).concat(suffix).forEach(function (k) {
            var th = byCol[k];
            if (th) {
                theadRow.appendChild(th);
            }
        });
        stApplyColumnVisibility(which);
    }

    function stBindColOrderDnD(which) {
        var id = which === 'source' ? 'stSourceColOrderList' : 'stDestColOrderList';
        var listEl = document.getElementById(id);
        if (!listEl || listEl.getAttribute('data-st-dnd-bound') === '1') {
            return;
        }
        listEl.setAttribute('data-st-dnd-bound', '1');
        var dragKey = null;
        listEl.addEventListener('dragstart', function (e) {
            var item = e.target.closest('.st-col-order-item');
            if (!item || !listEl.contains(item)) {
                return;
            }
            dragKey = item.getAttribute('data-st-order-key');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', dragKey || '');
            item.classList.add('st-col-order-dragging');
        });
        listEl.addEventListener('dragend', function () {
            listEl.querySelectorAll('.st-col-order-dragging').forEach(function (el) {
                el.classList.remove('st-col-order-dragging');
            });
            dragKey = null;
        });
        listEl.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });
        listEl.addEventListener('drop', function (e) {
            e.preventDefault();
            var target = e.target.closest('.st-col-order-item');
            var fromKey = e.dataTransfer.getData('text/plain') || dragKey;
            if (!target || !fromKey || !listEl.contains(target)) {
                return;
            }
            var toKey = target.getAttribute('data-st-order-key');
            if (!toKey || fromKey === toKey) {
                return;
            }
            var order = stGetDataColOrder(which).slice();
            var fi = order.indexOf(fromKey);
            var ti = order.indexOf(toKey);
            if (fi < 0 || ti < 0) {
                return;
            }
            order.splice(fi, 1);
            if (fi < ti) {
                ti--;
            }
            order.splice(ti, 0, fromKey);
            stSaveDataColOrder(which, order);
            stRefreshColOrderList(which);
            stApplyColumnOrder(which);
            renderSource();
            renderDest();
        });
    }

    var stColStateSource = {};
    var stColStateDest = {};

    function stMergeColState(which, keys) {
        var out = {};
        keys.forEach(function (k) { out[k] = true; });
        try {
            var raw = localStorage.getItem(which === 'source' ? 'auragold_st_transfer_cols_src' : 'auragold_st_transfer_cols_dest');
            if (raw) {
                var o = JSON.parse(raw);
                if (o && typeof o === 'object') {
                    keys.forEach(function (k) {
                        if (Object.prototype.hasOwnProperty.call(o, k)) {
                            out[k] = !!o[k];
                        }
                    });
                }
            }
        } catch (e) {}
        return out;
    }

    function stSaveColState(which) {
        try {
            var obj = which === 'source' ? stColStateSource : stColStateDest;
            localStorage.setItem(
                which === 'source' ? 'auragold_st_transfer_cols_src' : 'auragold_st_transfer_cols_dest',
                JSON.stringify(obj)
            );
        } catch (e) {}
    }

    function stApplyColumnVisibility(which) {
        var tableId = which === 'source' ? 'stTableSource' : 'stTableDest';
        var table = document.getElementById(tableId);
        if (!table) return;
        var state = which === 'source' ? stColStateSource : stColStateDest;
        Object.keys(state).forEach(function (key) {
            var visible = state[key] !== false;
            var dis = visible ? '' : 'none';
            table.querySelectorAll('thead th[data-col="' + key + '"], tbody td[data-col="' + key + '"]').forEach(function (el) {
                el.style.display = dis;
            });
        });
    }

    function stInitColumnSettings() {
        var srcKeys = ['st_cb', 'st_drag', 'st_img'].concat(stDataColDefs.map(function (x) { return x[0]; }));
        var destKeys = ['st_cb'].concat(stDataColDefs.map(function (x) { return x[0]; })).concat(['st_remove']);

        stColStateSource = stMergeColState('source', srcKeys);
        stColStateDest = stMergeColState('dest', destKeys);

        function buildList(listEl, which, defs) {
            listEl.innerHTML = '';
            defs.forEach(function (d) {
                var key = d[0];
                var label = d[1];
                var id = 'st_col_' + which + '_' + key;
                var checked = (which === 'source' ? stColStateSource : stColStateDest)[key] !== false;
                var div = document.createElement('div');
                div.className = 'columns-dropdown-item';
                div.innerHTML = '<input type="checkbox" id="' + id + '" data-st-col="' + key + '" data-st-which="' + which + '"' + (checked ? ' checked' : '') + '>' +
                    '<label for="' + id + '">' + label + '</label>';
                listEl.appendChild(div);
            });
        }

        var srcDefs = [['st_cb', 'Select all'], ['st_drag', 'Drag handle'], ['st_img', 'Image']].concat(stDataColDefs);
        buildList(document.getElementById('stSourceColList'), 'source', srcDefs);

        var destDefs = [['st_cb', 'Select all']].concat(stDataColDefs).concat([['st_remove', 'Remove']]);
        buildList(document.getElementById('stDestColList'), 'dest', destDefs);

        function bindSearch(inputId, listId) {
            document.getElementById(inputId).addEventListener('input', function () {
                var term = (this.value || '').toLowerCase();
                document.querySelectorAll('#' + listId + ' .columns-dropdown-item').forEach(function (row) {
                    var lab = row.querySelector('label');
                    var t = lab ? lab.textContent.toLowerCase() : '';
                    row.style.display = t.indexOf(term) >= 0 ? '' : 'none';
                });
            });
        }
        bindSearch('stSourceColSearch', 'stSourceColList');
        bindSearch('stDestColSearch', 'stDestColList');

        document.getElementById('stSourceColList').addEventListener('change', function (e) {
            var inp = e.target.closest('input[data-st-col]');
            if (!inp) return;
            var key = inp.getAttribute('data-st-col');
            stColStateSource[key] = inp.checked;
            stSaveColState('source');
            stApplyColumnVisibility('source');
        });
        document.getElementById('stDestColList').addEventListener('change', function (e) {
            var inp = e.target.closest('input[data-st-col]');
            if (!inp) return;
            var key = inp.getAttribute('data-st-col');
            stColStateDest[key] = inp.checked;
            stSaveColState('dest');
            stApplyColumnVisibility('dest');
        });

        function toggleDropdown(btnId, dropId, otherDropId) {
            document.getElementById(btnId).addEventListener('click', function (e) {
                e.stopPropagation();
                var d = document.getElementById(dropId);
                var od = document.getElementById(otherDropId);
                var open = !d.classList.contains('show');
                d.classList.toggle('show', open);
                if (od) od.classList.remove('show');
                this.setAttribute('aria-expanded', open ? 'true' : 'false');
                d.setAttribute('aria-hidden', open ? 'false' : 'true');
            });
        }
        toggleDropdown('stSourceColBtn', 'stSourceColDropdown', 'stDestColDropdown');
        toggleDropdown('stDestColBtn', 'stDestColDropdown', 'stSourceColDropdown');

        document.addEventListener('click', function (e) {
            if (e.target.closest('.st-col-settings-wrap')) return;
            document.getElementById('stSourceColDropdown').classList.remove('show');
            document.getElementById('stDestColDropdown').classList.remove('show');
            document.getElementById('stSourceColBtn').setAttribute('aria-expanded', 'false');
            document.getElementById('stDestColBtn').setAttribute('aria-expanded', 'false');
        });

        stApplyColumnVisibility('source');
        stApplyColumnVisibility('dest');

        stRefreshColOrderList('source');
        stRefreshColOrderList('dest');
        stBindColOrderDnD('source');
        stBindColOrderDnD('dest');
        stApplyColumnOrder('source');
        stApplyColumnOrder('dest');
    }

    function renderSource() {
        var tb = document.getElementById('stTableSourceBody');
        var total = 0;
        var html = '';
        sourceRows.forEach(function (r) {
            if (!rowMatchesFilter(r)) return;
            total += parseFloat(r.net_amt != null ? r.net_amt : r.amount) || 0;
            html += '<tr draggable="true" class="st-source-row" data-id="' + r.id + '">';
            html += '<td data-col="st_cb"><input type="checkbox" class="st-source-cb" value="' + r.id + '" draggable="false"></td>';
            html += '<td data-col="st_drag" class="st-drag-handle text-muted" title="Drag row to destination"><i class="feather icon-menu" style="font-size:14px;" aria-hidden="true"></i></td>';
            html += '<td data-col="st_img">' + thumbHtml(r.image_urls) + '</td>';
            html += rowDataCellsHtml(r, 'source');
            html += '</tr>';
        });
        if (!html) {
            html = '<tr><td colspan="50" class="st-empty">' +
                (sourceRows.length ? 'No rows match filter.' : 'No stock for this branch with available inward quantity (opening / purchase). Use the source branch that matches Stock History → Inward for that barcode.') + '</td></tr>';
        }
        tb.innerHTML = html;
        document.getElementById('stSourceTotal').textContent = fmtMoney(total);
        stApplyColumnVisibility('source');

        var fc = document.getElementById('stFilterCount');
        if (filterText) {
            fc.style.display = 'inline';
            fc.textContent = '1';
        } else {
            fc.style.display = 'none';
        }
    }

    function renderDest() {
        var tb = document.getElementById('stTableDestBody');
        var total = 0;
        var html = '';
        destRows.forEach(function (r, idx) {
            if (!rowMatchesDestFilter(r)) return;
            total += parseFloat(r.net_amt != null ? r.net_amt : r.amount) || 0;
            html += '<tr data-id="' + r.id + '">';
            html += '<td data-col="st_cb"><input type="checkbox" class="st-dest-cb" value="' + r.id + '"></td>';
            html += rowDataCellsHtml(r, 'dest');
            html += '<td data-col="st_remove" class="text-center"><button type="button" class="btn btn-xs btn-link text-danger p-1 st-remove" data-idx="' + idx + '" title="Remove" aria-label="Remove"><i class="feather icon-trash-2" style="font-size:16px;"></i></button></td>';
            html += '</tr>';
        });
        if (!html) {
            html = '<tr><td colspan="49" class="st-empty">' +
                (destRows.length ? 'No rows match filter.' : 'No Rows To Show') + '</td></tr>';
        }
        tb.innerHTML = html;
        document.getElementById('stDestTotal').textContent = fmtMoney(total);
        stApplyColumnVisibility('dest');

        var saveBtn = document.getElementById('stBtnSave');
        var toBr = document.getElementById('stToBranch').value;
        saveBtn.disabled = !(destRows.length && toBr);
    }

    function escapeHtml(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function destHas(id) {
        return destRows.some(function (r) { return String(r.id) === String(id); });
    }

    function sourceById(id) {
        for (var i = 0; i < sourceRows.length; i++) {
            if (String(sourceRows[i].id) === String(id)) return sourceRows[i];
        }
        return null;
    }

    function removeSourceRowById(stockId) {
        var sid = String(stockId);
        for (var i = 0; i < sourceRows.length; i++) {
            if (String(sourceRows[i].id) === sid) {
                sourceRows.splice(i, 1);
                return true;
            }
        }
        return false;
    }

    function loadSourceList() {
        var bid = document.getElementById('stFromBranch').value;
        if (!bid) {
            stShowMsg('Source branch', 'Select a source branch from the list, then click Apply.', 'warning');
            return;
        }
        var url = new URL('ajax/stock-transfer-list.php', window.location.href);
        url.searchParams.set('branch_id', bid);
        fetch(url.toString(), { credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    var data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error(
                            (r.status !== 200 ? 'HTTP ' + r.status + '. ' : '') +
                            (text ? text.slice(0, 400) : 'Empty response from server.')
                        );
                    }
                    if (!r.ok) {
                        throw new Error(data && data.message ? data.message : ('HTTP ' + r.status));
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.success) {
                    stShowMsg('Could not load stock', data.message || 'Failed to load stock.', 'error');
                    return;
                }
                sourceRows = data.rows || [];
                destRows = [];
                filterTextDest = '';
                var destF = document.getElementById('stDestFilter');
                if (destF) destF.value = '';
                renderSource();
                renderDest();
            })
            .catch(function (err) {
                stShowMsg('Could not load stock', err && err.message ? err.message : 'Network error loading stock.', 'error');
            });
    }

    function addToDestByStockId(stockId) {
        if (destHas(stockId)) return;
        var row = sourceById(stockId);
        if (!row) return;
        removeSourceRowById(stockId);
        destRows.push(row);
        renderSource();
        renderDest();
    }

    function initStockDragDrop() {
        var srcBody = document.getElementById('stTableSourceBody');
        var destZone = document.getElementById('stDestDropZone');
        if (!srcBody || !destZone) return;

        srcBody.addEventListener('dragstart', function (e) {
            var tr = e.target.closest('tr.st-source-row[data-id]');
            if (!tr) return;
            var id = tr.getAttribute('data-id');
            if (!id) return;
            e.dataTransfer.setData('application/x-auragold-stock-id', id);
            e.dataTransfer.setData('text/plain', id);
            e.dataTransfer.effectAllowed = 'move';
            tr.classList.add('st-dragging');
        });

        srcBody.addEventListener('dragend', function (e) {
            var tr = e.target.closest('tr.st-source-row');
            if (tr) tr.classList.remove('st-dragging');
        });

        destZone.addEventListener('dragenter', function (e) {
            e.preventDefault();
            destZone.classList.add('st-drop-active');
        });

        destZone.addEventListener('dragleave', function (e) {
            var rel = e.relatedTarget;
            if (!rel || !destZone.contains(rel)) {
                destZone.classList.remove('st-drop-active');
            }
        });

        destZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        destZone.addEventListener('drop', function (e) {
            e.preventDefault();
            destZone.classList.remove('st-drop-active');
            var id = e.dataTransfer.getData('application/x-auragold-stock-id') || e.dataTransfer.getData('text/plain');
            if (!id) return;
            addToDestByStockId(String(id).trim());
        });

        document.addEventListener('dragend', function () {
            destZone.classList.remove('st-drop-active');
        });
    }

    document.getElementById('stApplySource').addEventListener('click', loadSourceList);
    document.getElementById('stBtnRefreshAll').addEventListener('click', loadSourceList);

    document.getElementById('stDateRefresh').addEventListener('click', function () {
        document.getElementById('stTransferDate').value = todayYmd;
    });

    document.getElementById('stSourceFilter').addEventListener('input', function () {
        filterText = (this.value || '').trim();
        renderSource();
    });

    document.getElementById('stDestFilter').addEventListener('input', function () {
        filterTextDest = (this.value || '').trim();
        renderDest();
    });

    document.getElementById('stSourceSelectAll').addEventListener('change', function () {
        var on = this.checked;
        document.querySelectorAll('.st-source-cb').forEach(function (cb) { cb.checked = on; });
    });

    document.getElementById('stDestSelectAll').addEventListener('change', function () {
        var on = this.checked;
        document.querySelectorAll('.st-dest-cb').forEach(function (cb) { cb.checked = on; });
    });

    document.getElementById('stAddSelected').addEventListener('click', function () {
        document.querySelectorAll('.st-source-cb:checked').forEach(function (cb) {
            addToDestByStockId(cb.value);
        });
        document.querySelectorAll('.st-source-cb').forEach(function (cb) { cb.checked = false; });
        document.getElementById('stSourceSelectAll').checked = false;
    });

    document.getElementById('stTableDestBody').addEventListener('click', function (e) {
        var btn = e.target.closest('.st-remove');
        if (!btn) return;
        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        if (!isNaN(idx)) {
            var back = destRows[idx];
            destRows.splice(idx, 1);
            if (back) {
                sourceRows.push(back);
            }
            renderSource();
            renderDest();
        }
    });

    document.getElementById('stClearDest').addEventListener('click', function () {
        destRows.forEach(function (r) {
            sourceRows.push(r);
        });
        destRows = [];
        filterTextDest = '';
        var destF = document.getElementById('stDestFilter');
        if (destF) destF.value = '';
        renderSource();
        renderDest();
    });

    document.getElementById('stToBranch').addEventListener('change', renderDest);

    document.getElementById('stBarcodeIn').addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var bc = (this.value || '').trim();
        if (!bc) return;
        var bid = document.getElementById('stFromBranch').value;
        if (!bid) {
            stShowMsg('Source branch', 'Select a source branch before scanning a barcode.', 'warning');
            return;
        }
        var burl = new URL('ajax/stock-transfer-barcode.php', window.location.href);
        burl.searchParams.set('branch_id', bid);
        burl.searchParams.set('barcode', bc);
        fetch(burl.toString(), { credentials: 'same-origin' })
            .then(function (r) {
                return r.text().then(function (text) {
                    var data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        throw new Error(text ? text.slice(0, 400) : 'Bad response');
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (!data.success) {
                    stShowMsg('Barcode', data.message || 'Barcode not found.', 'error');
                    return;
                }
                var row = data.row;
                var found = false;
                for (var i = 0; i < sourceRows.length; i++) {
                    if (String(sourceRows[i].id) === String(row.id)) { found = true; break; }
                }
                if (!found) {
                    sourceRows.unshift(row);
                    renderSource();
                }
                addToDestByStockId(row.id);
                document.getElementById('stBarcodeIn').value = '';
            })
            .catch(function (err) {
                stShowMsg('Barcode', err && err.message ? err.message : 'Network error.', 'error');
            });
    });

    document.getElementById('stBtnSave').addEventListener('click', function () {
        var fromB = document.getElementById('stFromBranch').value;
        var toB = document.getElementById('stToBranch').value;
        var dt = document.getElementById('stTransferDate').value;
        if (!fromB || !toB) {
            stShowMsg('Branches', 'Select both source and destination branch.', 'warning');
            return;
        }
        if (fromB === toB) {
            stShowMsg('Branches', 'Source and destination must be different branches.', 'warning');
            return;
        }
        if (!destRows.length) {
            stShowMsg('Transfer list', 'Add one or more items to the transfer list before saving.', 'warning');
            return;
        }
        if (!confirm('Transfer ' + destRows.length + ' item(s) to the destination branch?')) return;

        var ids = destRows.map(function (r) { return r.id; });
        fetch('ajax/stock-transfer-save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                from_branch_id: parseInt(fromB, 10),
                to_branch_id: parseInt(toB, 10),
                transfer_date: dt || todayYmd,
                stock_ids: ids
            })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    stSwalOrAlert({
                        title: 'Transfer failed',
                        text: data.message || 'Save failed.',
                        type: 'error',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                var msg = data.message || 'Saved.';
                if (data.database) {
                    msg += '\n\nPrimary database: ' + data.database + ' (tbl_stock)';
                }
                if (data.mirror_database && data.mirror_attempted) {
                    msg += '\n\nAlso copied destination lines to: ' + data.mirror_database;
                    if (data.mirror_warning) {
                        msg += '\nWarning: ' + data.mirror_warning;
                    }
                }
                stSwalOrAlert({
                    title: 'Transfer saved',
                    text: msg,
                    type: 'success',
                    confirmButtonText: 'OK'
                });
                destRows = [];
                renderDest();
                loadSourceList();
            })
            .catch(function () {
                stSwalOrAlert({
                    title: 'Network error',
                    text: 'Could not reach the server. Try again.',
                    type: 'error',
                    confirmButtonText: 'OK'
                });
            });
    });

    document.getElementById('stBtnLoose').addEventListener('click', function () {
        stShowMsg('Transfer loose items', 'This option is not configured yet. Use barcode scan for tagged stock.', 'info');
    });

    document.getElementById('stBtnFilter').addEventListener('click', function () {
        document.getElementById('stSourceFilter').focus();
    });

    document.getElementById('stBtnPrintBc').addEventListener('click', function () {
        if (!destRows.length) {
            stShowMsg('Print barcode', 'Add items to the transfer list first.', 'warning');
            return;
        }
        window.print();
    });

    document.addEventListener('DOMContentLoaded', function () {
        stInitColumnSettings();
        initStockDragDrop();
        if (defaultBranchId > 0) {
            loadSourceList();
        }
    });
})();
</script>
</body>
</html>
