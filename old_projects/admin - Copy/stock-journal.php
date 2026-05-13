<?php 
session_start();
require_once 'config.php';
require_once __DIR__ . '/includes/branch_profile_schema.php';

$stock_journal_effective_branch_id = function_exists('auragold_effective_branch_id') ? auragold_effective_branch_id() : 0;
if (isset($conn) && $conn instanceof mysqli && function_exists('auragold_ensure_table_branch_id_column')) {
    auragold_ensure_table_branch_id_column($conn, 'tbl_purchase_invoices', 'id');
}

$sj_branch_label = '';
if ($stock_journal_effective_branch_id > 0 && !empty($conn_master) && function_exists('getRecordMaster')) {
    $sjbr = getRecordMaster('SELECT name FROM tbl_branches WHERE id = ' . (int) $stock_journal_effective_branch_id . ' LIMIT 1');
    if ($sjbr && !empty($sjbr['name'])) {
        $sj_branch_label = trim((string) $sjbr['name']);
    }
}

require_once __DIR__ . '/includes/dashboard_currency_display.php';
$sj_currency_sql_fallback = 'AED';
if (isset($conn) && $conn instanceof mysqli) {
    $sj_currency_sql_fallback = mysqli_real_escape_string(
        $conn,
        auragold_branch_profile_currency_display_label($conn, (!empty($conn_master) && $conn_master instanceof mysqli) ? $conn_master : null)
    );
}

$purchase_branch_sql = '';
$purchase_branch_sql_pi = '';
if ($stock_journal_effective_branch_id > 0 && isset($conn) && $conn instanceof mysqli && function_exists('auragold_tbl_has_column')
    && auragold_tbl_has_column($conn, 'tbl_purchase_invoices', 'branch_id')) {
    $b = (int) $stock_journal_effective_branch_id;
    $purchase_branch_sql = ' AND branch_id = ' . $b;
    $purchase_branch_sql_pi = ' AND pi.branch_id = ' . $b;
}

$po_branch_sql = '';
if ($stock_journal_effective_branch_id > 0 && !empty($conn_master) && function_exists('getRecordMaster')) {
    $sb = getRecordMaster('SELECT main_branch_id FROM tbl_branches WHERE id = ' . (int) $stock_journal_effective_branch_id . ' LIMIT 1');
    if ($sb) {
        if ((int) ($sb['main_branch_id'] ?? 0) > 0) {
            $po_branch_sql = ' AND pc.branch_id = ' . (int) $stock_journal_effective_branch_id;
        } else {
            $bidm = (int) $stock_journal_effective_branch_id;
            $po_branch_sql = ' AND (pc.branch_id = ' . $bidm . ' OR pc.branch_id IS NULL OR pc.branch_id = 0)';
        }
    }
}

// Get filters
$search = isset($_GET['search']) ? esc($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? esc($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? esc($_GET['to_date']) : '';
$customer_filter = isset($_GET['customer']) ? esc($_GET['customer']) : '';
// Voucher type: purchase_invoice (default), product_opening, jobwork_invoice, purchase_quotation, broken_entry
$voucher = isset($_GET['voucher']) ? esc($_GET['voucher']) : 'purchase_invoice';
if (!in_array($voucher, ['purchase_invoice', 'product_opening', 'jobwork_invoice', 'purchase_quotation', 'broken_entry'])) {
    $voucher = 'purchase_invoice';
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $per_page;

// Initialize data array
$data = [];
$total_records = 0;
$total_pages = 1;
$customers = [];

// Build WHERE clause for invoices table
$where_clause = "1=1";
if (!empty($search)) {
    $where_clause .= " AND (invoice_no LIKE '%" . esc($search) . "%' OR supplier_name LIKE '%" . esc($search) . "%')";
}
if (!empty($from_date)) {
    $where_clause .= " AND invoice_date >= '" . esc($from_date) . "'";
}
if (!empty($to_date)) {
    $where_clause .= " AND invoice_date <= '" . esc($to_date) . "'";
}
if (!empty($customer_filter)) {
    $where_clause .= " AND supplier_name = '" . esc($customer_filter) . "'";
}

try {
    if ($voucher === 'product_opening') {
        // Product Opening: rows from tbl_product_characteristics with opening data (branch-scoped)
        $po_where = "pc.status = 1 AND p.status = 1 AND (COALESCE(pc.opening_weight, 0) > 0 OR COALESCE(pc.final_weight, 0) > 0 OR COALESCE(pc.value, 0) > 0)";
        if (!empty($search)) {
            $po_where .= " AND (p.name LIKE '%" . esc($search) . "%' OR p.alternate_name LIKE '%" . esc($search) . "%' OR pc.barcode LIKE '%" . esc($search) . "%')";
        }
        $po_where .= $po_branch_sql;
        $po_count_sql = "SELECT COUNT(*) as total FROM tbl_product_characteristics pc INNER JOIN tbl_products p ON pc.product_id = p.id WHERE $po_where";
        $po_total = getRecord($po_count_sql);
        $total_records = $po_total ? (int)$po_total['total'] : 0;
        $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
        $data = [];
        if ($total_records > 0) {
            $po_query = "
                SELECT 
                    0 as invoice_id,
                    '' as invoice_no,
                    '' as supplier_name,
                    NULL as invoice_date,
                    1 as status,
                    '$sj_currency_sql_fallback' as currency,
                    pc.id as item_id,
                    p.id as product_id,
                    p.name as product_name,
                    pc.barcode,
                    COALESCE(pc.opening_qty, 0) + (
                        SELECT COALESCE(SUM(sj_po.quantity), 0)
                        FROM tbl_stock_journal sj_po
                        WHERE sj_po.product_characteristic_id = pc.id
                          AND sj_po.status = 'active'
                          AND (sj_po.item_id IS NULL OR sj_po.item_id = 0)
                          AND (sj_po.comment IS NULL OR sj_po.comment NOT LIKE 'auragold_doc|src=pi|%')
                    ) as quantity,
                    p.name as full_product_name,
                    COALESCE(pc.metal_id, 0) as metal_id,
                    COALESCE(m.display_name, 'N/A') as metal_name,
                    'N/A' as location_name,
                    COALESCE(pc.branch_id, 0) as branch_id,
                    '' as branch_name,
                    COALESCE(pc.opening_weight, 0) as gross_weight,
                    COALESCE(pc.final_weight, 0) as net_weight,
                    COALESCE(pc.opening_purity, 0) as purity,
                    COALESCE(pc.rate, 0) as rate,
                    COALESCE(pc.value, 0) as net_amount,
                    COALESCE(pc.value, 0) as purchase_amount,
                    0.00 as stockjournal_amount,
                    (
                        SELECT COALESCE(SUM(sj_po.quantity), 0)
                        FROM tbl_stock_journal sj_po
                        WHERE sj_po.product_characteristic_id = pc.id
                          AND sj_po.status = 'active'
                          AND (sj_po.item_id IS NULL OR sj_po.item_id = 0)
                          AND (sj_po.comment IS NULL OR sj_po.comment NOT LIKE 'auragold_doc|src=pi|%')
                    ) as production_qty,
                    COALESCE(pc.opening_qty, 0) as available_qty,
                    COALESCE(pc.opening_qty, 0) + (
                        SELECT COALESCE(SUM(sj_po.quantity), 0)
                        FROM tbl_stock_journal sj_po
                        WHERE sj_po.product_characteristic_id = pc.id
                          AND sj_po.status = 'active'
                          AND (sj_po.item_id IS NULL OR sj_po.item_id = 0)
                          AND (sj_po.comment IS NULL OR sj_po.comment NOT LIKE 'auragold_doc|src=pi|%')
                    ) as total_qty,
                    0.00 as stone_wt,
                    (CASE WHEN EXISTS (SELECT 1 FROM tbl_stock_journal sj WHERE sj.product_characteristic_id = pc.id AND (sj.item_id IS NULL OR sj.item_id = 0) AND sj.status = 'active' AND (sj.comment IS NULL OR sj.comment NOT LIKE 'auragold_doc|src=pi|%')) THEN 1 ELSE 0 END) as has_stock_journal,
                    'product_opening' as voucher_type,
                    pc.id as characteristic_id
                FROM tbl_product_characteristics pc
                INNER JOIN tbl_products p ON pc.product_id = p.id
                LEFT JOIN tbl_metal m ON pc.metal_id = m.id
                WHERE $po_where
                ORDER BY p.name ASC, pc.id ASC
                LIMIT $per_page OFFSET $offset
            ";
            $data = getList($po_query);
        }
        $customers = [];
    } elseif ($voucher === 'jobwork_invoice' || $voucher === 'purchase_quotation' || $voucher === 'broken_entry') {
        // Placeholder: no tables yet, show empty
        $data = [];
        $total_records = 0;
        $total_pages = 1;
        $customers = [];
    } else {
        // purchase_invoice (default)
        // Get unique customers for filter
        $customers = getList("SELECT DISTINCT supplier_name FROM tbl_purchase_invoices WHERE supplier_name IS NOT NULL AND supplier_name != ''" . $purchase_branch_sql . " ORDER BY supplier_name ASC");

        // Simplified query - get invoices first, join items if they exist
    // This ensures invoices always show even if they have no items
     $query = "
        SELECT 
            pi.id as invoice_id,
            pi.invoice_no,
            pi.supplier_name,
            pi.invoice_date,
            pi.status,
            CASE
                WHEN pi.currency IS NOT NULL AND TRIM(pi.currency) <> '' THEN TRIM(pi.currency)
                ELSE '$sj_currency_sql_fallback'
            END as currency,
            pii.id as item_id,
            pii.product_id,
            pii.product_name,
            pii.barcode,
            COALESCE(pii.metal_qty, pii.quantity, 0) as quantity,
            COALESCE(p.name, pii.product_name, 'N/A') as full_product_name,
            COALESCE(pc.metal_id, 0) as metal_id,
            COALESCE(m.display_name, 'N/A') as metal_name,
            'N/A' as location_name,
            COALESCE(pi.branch_id, 0) as branch_id,
            '' as branch_name,
            COALESCE(pii.gross_weight, 0) as gross_weight,
            COALESCE(pii.net_weight, 0) as net_weight,
            COALESCE(pii.purity, 0) as purity,
            COALESCE(pii.rate, 0) as rate,
            COALESCE(pii.net_amount, 0) as net_amount,
            COALESCE(pii.net_amount, pii.amount, 0) as purchase_amount,
            0.00 as stockjournal_amount,
            COALESCE(SUM(sj.quantity), 0) as production_qty,
            GREATEST(COALESCE(pii.metal_qty, pii.quantity, 0) - COALESCE(SUM(sj.quantity), 0), 0) as available_qty,
            COALESCE(pii.metal_qty, pii.quantity, 0) as total_qty,
            0.00 as stone_wt,
            CASE WHEN COUNT(DISTINCT sj.id) > 0 THEN 1 ELSE 0 END as has_stock_journal,
            'purchase_invoice' as voucher_type,
            COALESCE(pii.product_characteristic_id, 0) as characteristic_id
        FROM tbl_purchase_invoices pi
        LEFT JOIN tbl_purchase_invoice_items pii ON pi.id = pii.invoice_id
        LEFT JOIN tbl_products p ON pii.product_id = p.id
        LEFT JOIN tbl_product_characteristics pc ON pii.product_characteristic_id = pc.id
        LEFT JOIN tbl_metal m ON pc.metal_id = m.id
        LEFT JOIN tbl_stock_journal sj ON sj.item_id = pii.id AND sj.status = 'active'
            AND (sj.comment IS NULL OR sj.comment NOT LIKE 'auragold_doc|src=pi|%')
        WHERE $where_clause$purchase_branch_sql_pi
        GROUP BY pi.id, pii.id
        ORDER BY pi.invoice_date DESC, pi.id DESC, pii.id ASC
    ";

    // Get total count - count all invoices matching the criteria
    $count_query = "SELECT COUNT(*) as total FROM tbl_purchase_invoices WHERE $where_clause$purchase_branch_sql";
    
    $total_record = getRecord($count_query);
    $total_records = $total_record ? (int)$total_record['total'] : 0;
    $total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;

    // Get paginated data
    if ($total_records > 0) {
        $data = getList($query . " LIMIT $per_page OFFSET $offset");
        // If getList returns false or empty, try simple invoice query
        if ($data === false || empty($data)) {
            $simple_query = "
                SELECT 
                    pi.id as invoice_id,
                    pi.invoice_no,
                    pi.supplier_name,
                    pi.invoice_date,
                    pi.status,
                    CASE
                        WHEN pi.currency IS NOT NULL AND TRIM(pi.currency) <> '' THEN TRIM(pi.currency)
                        ELSE '$sj_currency_sql_fallback'
                    END as currency,
                    NULL as item_id,
                    NULL as product_id,
                    NULL as product_name,
                    NULL as barcode,
                    NULL as quantity,
                    'N/A' as full_product_name,
                    0 as metal_id,
                    'N/A' as metal_name,
                    'N/A' as location_name,
                    COALESCE(pi.branch_id, 0) as branch_id,
                    '' as branch_name,
                    0 as gross_weight,
                    0 as net_weight,
                    0 as purity,
                    0 as rate,
                    0 as net_amount,
                    0 as purchase_amount,
                    0.00 as stockjournal_amount,
                    0.00 as production_qty,
                    0.00 as available_qty,
                    0.00 as total_qty,
                    0.00 as stone_wt,
                    0 as has_stock_journal,
                    'purchase_invoice' as voucher_type,
                    0 as characteristic_id
                FROM tbl_purchase_invoices pi
                WHERE $where_clause$purchase_branch_sql
                ORDER BY pi.invoice_date DESC, pi.id DESC
                LIMIT $per_page OFFSET $offset
            ";
            $data = getList($simple_query);
        }
    } else {
        $data = [];
    }
    } // end else purchase_invoice
    if (!empty($data) && function_exists('auragold_enrich_rows_branch_name_from_registry')) {
        auragold_enrich_rows_branch_name_from_registry($data);
    }
} catch (Exception $e) {
    // Log error and show empty data
    error_log("Stock Journal Error: " . $e->getMessage());
    $data = [];
    $total_records = 0;
}

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Stock Journal - AuraGold Software</title>
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

/* Toolbar */
.toolbar {
    background: #fff;
    padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.toolbar-left {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.toolbar-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-box {
    position: relative;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 8px 35px 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12px;
}

.search-box i {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.filter-btn, .export-btn {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.filter-btn:hover, .export-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* Table Container */
.table-container {
    flex: 1;
    overflow: auto;
    background: #fff;
    margin: 4px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.table {
    width: 100%;
    margin: 0;
    font-size: 12px;
    border-collapse: collapse;
}

.table thead th {
    background: #11294b !important;
    font-weight: 600;
    color: #fff;
    padding: 6px;
    border-bottom: 2px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 10;
    text-align: left;
}

/* Stock journal: reorder + resize — auto layout so full header labels show; horizontal scroll in .table-container */
#ledgerTable {
    table-layout: auto;
    width: max-content;
    min-width: 100%;
    border-collapse: collapse;
}
#ledgerTable thead th {
    position: relative;
    vertical-align: middle;
    user-select: none;
    box-sizing: border-box;
    overflow: visible;
    white-space: nowrap;
    text-overflow: clip;
    padding: 7px 14px 7px 8px;
    line-height: 1.3;
    font-size: 12px;
    hyphens: none;
}
#ledgerTable thead th.sj-th-reorder {
    /* room for resizer on the right */
    padding-right: 12px;
}
#ledgerTable thead th .sj-th-drag {
    display: inline-flex;
    align-items: center;
    margin-left: 6px;
    cursor: grab;
    color: rgba(255, 255, 255, 0.65);
    line-height: 0;
    vertical-align: middle;
    flex-shrink: 0;
}
#ledgerTable thead th .sj-th-drag .feather {
    width: 15px;
    height: 15px;
    stroke: currentColor;
}
#ledgerTable thead th .sj-th-drag:hover {
    color: #fff;
}
#ledgerTable thead th .sj-th-drag:active {
    cursor: grabbing;
}
#ledgerTable thead th .sj-th-label {
    display: inline;
    vertical-align: middle;
    margin-right: 2px;
}
#ledgerTable thead th .sj-col-resizer {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 6px;
    cursor: col-resize;
    z-index: 3;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12));
}
#ledgerTable thead th .sj-col-resizer:hover {
    background: rgba(255, 255, 255, 0.2);
}
#ledgerTable thead th.sortable-ghost,
#ledgerTable thead th.sj-sortable-ghost {
    opacity: 0.4;
    background: #1e3a5f !important;
}
#ledgerTable thead th.sj-sortable-chosen {
    background: #16305a !important;
}

.table tbody td {
    padding: 4px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f8fafc;
}

.btn-view {
    background: #11294b;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    font-weight: 500;
}

.btn-view:hover {
    background: #4a2b7c;
}

.btn-create, .btn-update, .btn-delete, .btn-add-items {
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    font-weight: 500;
    margin: 0 2px;
}

.btn-create {
    background: #11294b;
    color: #fff;
}

.btn-create:hover {
    background: #4a2b7c;
}

.btn-add-items {
    background: #10b981;
    color: #fff;
}

.btn-add-items:hover {
    background: #059669;
}

.btn-update {
    background: #a78bfa;
    color: #fff;
}

.btn-update:hover {
    background: #8b5cf6;
}

/* Action buttons in table: ensure always clickable, no lock/overlay issues */
.table-container .btn-create,
.table-container .btn-update,
.table-container .btn-delete,
.table-container .btn-add-items {
    pointer-events: auto;
    cursor: pointer;
}

.btn-update:disabled {
    background: #cbd5e1;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-delete {
    background: #ef4444;
    color: #fff;
}

.btn-delete:hover {
    background: #dc2626;
}

.btn-delete:disabled {
    background: #cbd5e1;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.6;
}

.invoice-link {
    color: #3b82f6;
    text-decoration: underline;
    cursor: pointer;
}

.invoice-link:hover {
    color: #2563eb;
}

/* Pagination */
.pagination-container {
    background: #fff;
    padding: 12px 20px;
    margin: 0 20px 20px 20px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.pagination-info {
    color: #64748b;
    font-size: 12px;
}

.pagination-right {
    display: flex;
    gap: 10px;
    align-items: center;
}

.per-page-dropdown select {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 12px;
}

.pagination-controls {
    display: flex;
    gap: 5px;
    align-items: center;
}

.pagination-controls button {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    min-width: 36px;
}

.pagination-controls button:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.pagination-controls button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-controls .page-number {
    background: #11294b;
    color: #fff;
    border-color: #11294b;
}

.pagination-controls .page-number:hover {
    background: #4a2b7c;
}
</style>

<body>
    <?php include 'sidebar.php';?>
    
    <div class="layout-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header-bar">
                <div>Stock Journal<?php if ($sj_branch_label !== ''): ?> <span style="opacity:0.9;font-weight:500;">— <?= htmlspecialchars($sj_branch_label, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></div>
                <div class="page-header-actions">
                    <button class="btn-icon" title="Filter" onclick="openFilterModal()">
                        <i class="feather icon-filter"></i>
                    </button>
                    <button class="btn-icon" title="Refresh" onclick="location.reload()">
                        <i class="feather icon-refresh-cw"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn-icon" title="Export" data-toggle="dropdown">
                            <i class="feather icon-download"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" onclick="exportToExcel()">Export to Excel</a>
                            <a class="dropdown-item" href="#" onclick="exportToPDF()">Export to PDF</a>
                        </div>
                    </div>
                    <button class="btn-icon" title="Settings">
                        <i class="feather icon-settings"></i>
                    </button>
                </div>
            </div>

            <!-- Toolbar -->
            <!-- <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search by Invoice No or Customer..." value="<?php echo htmlspecialchars($search); ?>" onkeypress="handleSearchEnter(event)">
                        <i class="feather icon-search"></i>
                    </div>
                    <button class="filter-btn" onclick="openFilterModal()">
                        <i class="feather icon-filter"></i> Filter
                    </button>
                </div>
                <div class="toolbar-right">
                    <button class="export-btn" onclick="exportToExcel()">
                        <i class="feather icon-download"></i> Export
                    </button>
                </div>
            </div> -->

            <!-- DataTable Controls Bar -->
            <div class="datatable-controls-bar" style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background: #f8f9fa; border-bottom: 1px solid #e2e8f0; border-radius: 8px 8px 0 0;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="voucherType" style="margin: 0; font-weight: 600; font-size: 13px; color: #374151;">Voucher</label>
                        <select id="voucherType" class="form-control" onchange="changeVoucher(this.value)" style="min-width: 180px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
                            <option value="purchase_invoice" <?php echo $voucher === 'purchase_invoice' ? 'selected' : ''; ?>>Purchase Invoice</option>
                            <option value="product_opening" <?php echo $voucher === 'product_opening' ? 'selected' : ''; ?>>Product Opening</option>
                            <option value="jobwork_invoice" <?php echo $voucher === 'jobwork_invoice' ? 'selected' : ''; ?>>Jobwork Invoice</option>
                            <option value="purchase_quotation" <?php echo $voucher === 'purchase_quotation' ? 'selected' : ''; ?>>Purchase Quotation</option>
                            <option value="broken_entry" <?php echo $voucher === 'broken_entry' ? 'selected' : ''; ?>>Broken Entry</option>
                        </select>
                    </div>
                    <div class="datatable-search" style="flex: 1;">
                        <input type="text" id="customSearch" class="form-control" placeholder="Search in table..." style="max-width: 300px; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
                    </div>
                </div>
                <div class="datatable-buttons" style="display: flex; gap: 10px;">
                    <button id="exportExcelBtn" class="btn" style="background: #11294b; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                        <i class="feather icon-download"></i> Export Excel
                    </button>
                    <button id="printBtn" class="btn" style="background: #11294b; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                        <i class="feather icon-printer"></i> Print
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container" style="border-radius: 0 0 8px 8px;">
                <table class="table" id="ledgerTable" data-sj-voucher="<?php echo htmlspecialchars($voucher, ENT_QUOTES, 'UTF-8'); ?>">
                    <thead>
                        <tr>
                            <th class="sj-th-fixed" data-col="view" data-sj-title="View" data-sj-min="76" style="min-width: 76px; width: 84px;"><span class="sj-th-label">View</span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="sr_no" data-sj-title="Sr No." data-sj-min="100" style="min-width: 100px;"><span class="sj-th-label">Sr No.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="customer" data-sj-title="Customer" data-sj-min="120" style="min-width: 120px;"><span class="sj-th-label">Customer</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="date" data-sj-title="Date" data-sj-min="96" style="min-width: 96px;"><span class="sj-th-label">Date</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="source" data-sj-title="Source" data-sj-min="100" style="min-width: 100px;"><span class="sj-th-label">Source</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="sj_invoice_no" data-sj-title="SJ Invoice No" data-sj-min="160" style="min-width: 160px;"><span class="sj-th-label">SJ Invoice No</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="metal" data-sj-title="Metal" data-sj-min="96" style="min-width: 96px;"><span class="sj-th-label">Metal</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="product" data-sj-title="Product" data-sj-min="150" style="min-width: 150px;"><span class="sj-th-label">Product</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="currency" data-sj-title="Currency" data-sj-min="100" style="min-width: 100px;"><span class="sj-th-label">Currency</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="location" data-sj-title="Location" data-sj-min="100" style="min-width: 100px;"><span class="sj-th-label">Location</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="gross_wt" data-sj-title="Gross Wt." data-sj-min="110" style="min-width: 110px;"><span class="sj-th-label">Gross Wt.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="purchase_amount" data-sj-title="Purchase Amount." data-sj-min="180" style="min-width: 180px;"><span class="sj-th-label">Purchase Amount.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="stockjournal_amount" data-sj-title="StockJournal Amount" data-sj-min="210" style="min-width: 210px;"><span class="sj-th-label">StockJournal Amount</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="production_qty" data-sj-title="Production Qty." data-sj-min="160" style="min-width: 160px;" title="<?php echo $voucher === 'product_opening'
                                ? 'Sum of quantities on Product Opening stock journal lines (item not linked to a purchase invoice line).'
                                : 'Sum of quantities on Stock Journal lines (one line can have qty &gt; 1).'; ?>"><span class="sj-th-label">Production Qty.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="available_qty" data-sj-title="Available Qty." data-sj-min="155" style="min-width: 155px;" title="<?php echo $voucher === 'product_opening'
                                ? 'Remaining opening quantity on the characteristic (after stock journal consumption).'
                                : 'Purchase line qty minus sum of SJ line quantities — not yet assigned in Stock Journal.'; ?>"><span class="sj-th-label">Available Qty.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="total_qty" data-sj-title="Total Qty." data-sj-min="110" style="min-width: 110px;" title="<?php echo $voucher === 'product_opening'
                                ? 'Original opening bucket: remaining opening qty plus quantities already moved via Product Opening stock journal.'
                                : 'Qty on the purchase invoice line (metal qty or quantity).'; ?>"><span class="sj-th-label">Total Qty.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="branch_name" data-sj-title="Branch Name" data-sj-min="130" style="min-width: 130px;"><span class="sj-th-label">Branch Name</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sortable sj-th-reorder" data-col="stone_wt" data-sj-title="Stone Wt." data-sj-min="100" style="min-width: 100px;"><span class="sj-th-label">Stone Wt.</span><span class="sj-th-drag" title="Drag to reorder column"><i class="feather icon-move" aria-hidden="true"></i></span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                            <th class="sj-th-fixed" data-col="action" data-sj-title="Action" data-sj-min="260" style="min-width: 260px; width: 280px;"><span class="sj-th-label">Action</span><span class="sj-col-resizer" aria-hidden="true"></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data)): ?>
                            <?php 
                            $sr_no = $offset + 1;
                            $is_opening = ($voucher === 'product_opening');
                            foreach ($data as $row): 
                                $row_voucher = $row['voucher_type'] ?? '';
                                $is_row_opening = ($row_voucher === 'product_opening' || $is_opening);
                            ?>
                            <tr data-voucher="<?php echo $is_row_opening ? 'product_opening' : 'purchase_invoice'; ?>">
                                <td data-col="view">
                                    <?php if ($is_row_opening): ?>
                                        <a href="product-opening.php?id=<?php echo (int)($row['product_id'] ?? 0); ?>" class="btn-view" style="display: inline-block; padding: 4px 12px; background: #11294b; color: #fff; border-radius: 4px; text-decoration: none; font-size: 12px;">View</a>
                                    <?php else: ?>
                                        <button type="button" class="btn-view" onclick="viewInvoice(<?php echo (int)($row['invoice_id'] ?? 0); ?>)">View</button>
                                    <?php endif; ?>
                                </td>
                                <td data-col="sr_no"><?php echo $sr_no++; ?></td>
                                <td data-col="customer"><?php echo htmlspecialchars($row['supplier_name'] ?? ''); ?></td>
                                <td data-col="date"><?php echo !empty($row['invoice_date']) ? date('d/m/Y', strtotime($row['invoice_date'])) : ''; ?></td>
                                <td data-col="source">
                                    <?php if ($is_row_opening): ?>
                                        <span style="color: #11294b; font-weight: 500;">Opening</span>
                                    <?php else: ?>
                                        <a href="purchase-invoice.php?id=<?php echo (int)($row['invoice_id'] ?? 0); ?>" class="invoice-link">
                                            <?php echo htmlspecialchars($row['invoice_no'] ?? ''); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td data-col="sj_invoice_no"></td>
                                <td data-col="metal"><?php echo htmlspecialchars($row['metal_name'] ?? 'N/A'); ?></td>
                                <td data-col="product"><?php echo htmlspecialchars($row['product_name'] ?? ($row['full_product_name'] ?? 'N/A')); ?></td>
                                <td data-col="currency"><?php echo htmlspecialchars($row['currency'] ?? 'N/A'); ?></td>
                                <td data-col="location"><?php echo htmlspecialchars($row['location_name'] ?? 'N/A'); ?></td>
                                <td data-col="gross_wt" style="color: #a78bfa;"><?php echo number_format($row['gross_weight'] ?? 0, 3); ?></td>
                                <td data-col="purchase_amount" style="color: #a78bfa;"><?php echo number_format($row['purchase_amount'] ?? 0, 2); ?></td>
                                <td data-col="stockjournal_amount" style="color: #a78bfa;"><?php echo number_format($row['stockjournal_amount'] ?? 0, 2); ?></td>
                                <td data-col="production_qty" style="color: #a78bfa;"><?php echo number_format($row['production_qty'] ?? 0, 2); ?></td>
                                <td data-col="available_qty" style="color: #a78bfa;"><?php echo number_format($row['available_qty'] ?? 0, 2); ?></td>
                                <td data-col="total_qty" style="color: #a78bfa;"><?php echo number_format($row['total_qty'] ?? 0, 2); ?></td>
                                <td data-col="branch_name" style="color: #3b82f6;"><?php echo htmlspecialchars($row['branch_name'] ?? 'Main Branch'); ?></td>
                                <td data-col="stone_wt" style="color: #a78bfa;"><?php echo number_format($row['stone_wt'] ?? 0, 2); ?></td>
                                <td data-col="action">
                                    <?php 
                                    $is_row_opening = ($row_voucher === 'product_opening' || $is_opening);
                                    if ($is_row_opening): 
                                        $char_id = isset($row['characteristic_id']) ? (int)$row['characteristic_id'] : (int)($row['item_id'] ?? 0);
                                        $product_id = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                                    ?>
                                        <?php $po_has_stock = (int)($row['has_stock_journal'] ?? 0) > 0; ?>
                                        <button type="button" class="btn-create" data-action="create" data-item-id="<?php echo $char_id; ?>" data-product-id="<?php echo $product_id; ?>" data-voucher-type="product_opening">Create</button>
                                        <button type="button" class="btn-update" data-action="update" data-item-id="<?php echo $char_id; ?>" data-product-id="<?php echo $product_id; ?>" data-voucher-type="product_opening" <?php echo $po_has_stock ? '' : 'disabled'; ?>>Update Items</button>
                                        <button type="button" class="btn-delete" data-action="delete" data-item-id="<?php echo $char_id; ?>" data-voucher-type="product_opening" <?php echo $po_has_stock ? '' : 'disabled'; ?>>Delete</button>
                                    <?php else: ?>
                                        <?php 
                                        $item_id = isset($row['item_id']) && $row['item_id'] ? $row['item_id'] : 0;
                                        $has_stock_journal = (int)($row['has_stock_journal'] ?? 0) > 0;
                                        $pi_product_id = isset($row['product_id']) ? (int)$row['product_id'] : 0;
                                        $pi_char_id = isset($row['characteristic_id']) ? (int)$row['characteristic_id'] : 0;
                                        ?>
                                        <?php if ($item_id > 0): ?>
                                            <?php if ($has_stock_journal): ?>
                                                <button type="button" class="btn-add-items" data-action="add" data-voucher-type="purchase_invoice" data-item-id="<?php echo (int)$item_id; ?>" data-product-id="<?php echo (int)$pi_product_id; ?>" data-characteristic-id="<?php echo (int)$pi_char_id; ?>">Add Items</button>
                                                <button type="button" class="btn-update" data-action="update" data-voucher-type="purchase_invoice" data-item-id="<?php echo (int)$item_id; ?>" data-product-id="<?php echo (int)$pi_product_id; ?>" data-characteristic-id="<?php echo (int)$pi_char_id; ?>">Update Items</button>
                                                <button type="button" class="btn-delete" data-action="delete" data-voucher-type="purchase_invoice" data-item-id="<?php echo (int)$item_id; ?>">Delete</button>
                                            <?php else: ?>
                                                <button type="button" class="btn-create" data-action="create" data-voucher-type="purchase_invoice" data-item-id="<?php echo (int)$item_id; ?>" data-product-id="<?php echo (int)$pi_product_id; ?>" data-characteristic-id="<?php echo (int)$pi_char_id; ?>">Create</button>
                                                <button type="button" class="btn-update" data-action="update" data-voucher-type="purchase_invoice" data-item-id="<?php echo (int)$item_id; ?>" data-product-id="<?php echo (int)$pi_product_id; ?>" data-characteristic-id="<?php echo (int)$pi_char_id; ?>" disabled>Update Items</button>
                                                <button type="button" class="btn-delete" data-action="delete" data-voucher-type="purchase_invoice" data-item-id="<?php echo (int)$item_id; ?>" disabled>Delete</button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button type="button" class="btn-create" disabled>Create</button>
                                            <button type="button" class="btn-update" disabled>Update Items</button>
                                            <button type="button" class="btn-delete" disabled>Delete</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="19" style="text-align: center; padding: 40px; color: #64748b;">
                                    <?php
                                    if ($voucher === 'product_opening') echo 'No product opening items found.';
                                    elseif (in_array($voucher, ['jobwork_invoice', 'purchase_quotation', 'broken_entry'])) echo 'No records for this voucher type.';
                                    else echo 'No purchase invoice records found';
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

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
                            <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>Show 100 Items</option>
                        </select>
                    </div>
                    <div class="pagination-controls">
                        <button onclick="goToPage(1)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>&lt;&lt;</button>
                        <button onclick="goToPage(<?php echo $page - 1; ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>&lt;</button>
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <button class="<?php echo $i == $page ? 'page-number' : ''; ?>" onclick="goToPage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                        <?php endfor; ?>
                        <button onclick="goToPage(<?php echo $page + 1; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>&gt;</button>
                        <button onclick="goToPage(<?php echo $total_pages; ?>)" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>&gt;&gt;</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
function viewInvoice(invoiceId) {
    window.location.href = 'purchase-invoice.php?id=' + invoiceId;
}

function changeVoucher(value) {
    var url = new URL(window.location.href);
    url.searchParams.set('voucher', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Build create URL: product opening = characteristic + voucher + product; purchase invoice = item + voucher + optional product/characteristic (same shape as opening list).
function auragoldStockJournalCreateUrl(itemId, voucherType, productId, characteristicId, mode) {
    var productIdN = parseInt(productId, 10) || 0;
    var charIdN = parseInt(characteristicId, 10) || 0;
    if (voucherType === 'product_opening') {
        var q = 'stock-journal-create.php?characteristic_id=' + encodeURIComponent(itemId) + '&voucher=product_opening';
        if (productIdN > 0) q += '&product_id=' + encodeURIComponent(productIdN);
        if (mode === 'add') q += '&mode=add';
        return q;
    }
    var qp = 'stock-journal-create.php?item_id=' + encodeURIComponent(itemId) + '&voucher=purchase_invoice';
    if (productIdN > 0) qp += '&product_id=' + encodeURIComponent(productIdN);
    if (charIdN > 0) qp += '&characteristic_id=' + encodeURIComponent(charIdN);
    if (mode === 'add') qp += '&mode=add';
    return qp;
}
function auragoldStockJournalUpdateUrl(itemId, voucherType, productId, characteristicId) {
    var productIdN = parseInt(productId, 10) || 0;
    var charIdN = parseInt(characteristicId, 10) || 0;
    if (voucherType === 'product_opening') {
        var u = 'stock-journal-update.php?characteristic_id=' + encodeURIComponent(itemId) + '&voucher=product_opening';
        if (productIdN > 0) u += '&product_id=' + encodeURIComponent(productIdN);
        return u;
    }
    var up = 'stock-journal-update.php?item_id=' + encodeURIComponent(itemId) + '&voucher=purchase_invoice';
    if (productIdN > 0) up += '&product_id=' + encodeURIComponent(productIdN);
    if (charIdN > 0) up += '&characteristic_id=' + encodeURIComponent(charIdN);
    return up;
}

// Single delegated handler: Create / Add / Update / Delete for all voucher types
document.addEventListener('DOMContentLoaded', function() {
    var container = document.querySelector('.table-container');
    if (!container) return;
    container.addEventListener('click', function(e) {
        var btn = e.target && e.target.closest && e.target.closest('[data-action]');
        if (!btn) return;
        var action = btn.getAttribute('data-action');
        var itemId = parseInt(btn.getAttribute('data-item-id'), 10);
        var voucherType = btn.getAttribute('data-voucher-type') || '';
        if (!itemId) return;
        e.preventDefault();
        e.stopPropagation();
        var productId = parseInt(btn.getAttribute('data-product-id'), 10) || 0;
        var characteristicId = parseInt(btn.getAttribute('data-characteristic-id'), 10) || 0;
        if (action === 'create') {
            window.location.href = auragoldStockJournalCreateUrl(itemId, voucherType, productId, characteristicId, '');
        } else if (action === 'add') {
            window.location.href = auragoldStockJournalCreateUrl(itemId, voucherType, productId, characteristicId, 'add');
        } else if (action === 'update') {
            window.location.href = auragoldStockJournalUpdateUrl(itemId, voucherType, productId, characteristicId);
        } else if (action === 'delete') {
            deleteItem(itemId, btn, voucherType);
        }
    });
});

function createStockJournal(itemId) {
    if (itemId && itemId > 0) {
        window.location.href = 'stock-journal-create.php?item_id=' + itemId + '&voucher=purchase_invoice';
    } else {
        alert('Invalid item ID');
    }
}

function addItems(itemId) {
    if (itemId && itemId > 0) {
        window.location.href = 'stock-journal-create.php?item_id=' + itemId + '&voucher=purchase_invoice&mode=add';
    } else {
        alert('Invalid item ID');
    }
}

function updateItems(itemId) {
    if (itemId && itemId > 0) {
        window.location.href = 'stock-journal-create.php?item_id=' + itemId + '&voucher=purchase_invoice&edit=true';
    } else {
        alert('Invalid item ID');
    }
}

function deleteItem(itemId, buttonElement, voucherType) {
    if (!itemId || itemId <= 0) {
        alert('Invalid item ID');
        return;
    }
    
    if (!confirm('Are you sure you want to delete all stock journal entries for this item? This action cannot be undone.')) {
        return;
    }
    
    var deleteBtn = buttonElement || (event && event.target);
    var originalText = '';
    if (deleteBtn) {
        originalText = deleteBtn.textContent;
        deleteBtn.disabled = true;
        deleteBtn.textContent = 'Deleting...';
    }
    
    var payload = voucherType === 'product_opening' ? { characteristic_id: itemId, voucher: 'product_opening' } : { item_id: itemId };
    
    fetch('ajax/delete-stock-journal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message || 'Stock journal entries deleted successfully');
            // Reload the page to refresh the table
            window.location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete stock journal entries'));
            if (deleteBtn && originalText) {
                deleteBtn.disabled = false;
                deleteBtn.textContent = originalText;
            }
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Error deleting stock journal entries: ' + error.message);
        if (deleteBtn && originalText) {
            deleteBtn.disabled = false;
            deleteBtn.textContent = originalText;
        }
    });
}

function handleSearchEnter(event) {
    if (event.key === 'Enter') {
        performSearch();
    }
}

function performSearch() {
    const search = document.getElementById('searchInput').value;
    const url = new URL(window.location.href);
    url.searchParams.set('search', search);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function openFilterModal() {
    alert('Filter modal - TODO: Implement filter functionality');
    // TODO: Implement filter modal
}

function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function goToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

function exportToExcel() {
    alert('Export to Excel functionality will be implemented');
}

function exportToPDF() {
    alert('Export to PDF functionality will be implemented');
}
</script>

<?php include 'footer-script.php'; ?>
<script src="assets/libs/sortablejs/sortable.js"></script>
<!-- DataTable Functionality for Stock Journal -->
<script>
$(document).ready(function() {
    setTimeout(function() {
        initStockJournalTable();
    }, 100);
});

var SJ_TABLE_COL_KEYS = ['view', 'sr_no', 'customer', 'date', 'source', 'sj_invoice_no', 'metal', 'product', 'currency', 'location', 'gross_wt', 'purchase_amount', 'stockjournal_amount', 'production_qty', 'available_qty', 'total_qty', 'branch_name', 'stone_wt', 'action'];
var sjLedgerSortableInstance = null;

function initSJTableColumnsAndResize() {
    var table = document.getElementById('ledgerTable');
    if (!table || typeof Sortable === 'undefined') return;
    var voucher = table.getAttribute('data-sj-voucher') || 'purchase_invoice';
    var orderKey = 'auragold_stock_journal_col_order_v2_' + voucher;
    var widthsKey = 'auragold_stock_journal_col_widths_v2_' + voucher;
    var theadRow = table.querySelector('thead tr');
    if (!theadRow) return;

    function getOrderFromThead() {
        return [].map.call(table.querySelectorAll('thead th[data-col]'), function (th) {
            return th.getAttribute('data-col');
        });
    }
    function syncBodyColumnOrder(order) {
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            if (tr.cells.length === 1) return;
            var byCol = {};
            tr.querySelectorAll('td[data-col]').forEach(function (td) {
                byCol[td.getAttribute('data-col')] = td;
            });
            order.forEach(function (k) {
                if (byCol[k]) tr.appendChild(byCol[k]);
            });
        });
    }
    function applyOrderArray(order) {
        if (!order || order.length !== SJ_TABLE_COL_KEYS.length) return;
        var need = {};
        SJ_TABLE_COL_KEYS.forEach(function (k) { need[k] = 0; });
        order.forEach(function (k) {
            if (Object.prototype.hasOwnProperty.call(need, k)) need[k]++;
        });
        if (!SJ_TABLE_COL_KEYS.every(function (k) { return need[k] === 1; })) return;
        if (order[0] !== 'view' || order[order.length - 1] !== 'action') return;
        var thByCol = {};
        theadRow.querySelectorAll('th[data-col]').forEach(function (th) {
            thByCol[th.getAttribute('data-col')] = th;
        });
        order.forEach(function (k) {
            if (thByCol[k]) theadRow.appendChild(thByCol[k]);
        });
        syncBodyColumnOrder(order);
    }
    function tryLoadOrder() {
        try {
            var j = localStorage.getItem(orderKey);
            if (!j) return;
            applyOrderArray(JSON.parse(j));
        } catch (e) {}
    }
    function saveOrder() {
        try {
            localStorage.setItem(orderKey, JSON.stringify(getOrderFromThead()));
        } catch (e) {}
    }
    function thMinWidthFloor(th) {
        var a = th.getAttribute('data-sj-min');
        if (a != null && a !== '') {
            var f = parseInt(a, 10);
            if (!isNaN(f) && f >= 40) return f;
        }
        var sw = th.style && th.style.minWidth;
        if (sw && /^\d+px$/.test(sw)) {
            f = parseInt(sw, 10);
            if (!isNaN(f) && f >= 40) return f;
        }
        return 40;
    }
    function applyWidths(w) {
        if (!w || typeof w !== 'object') return;
        table.querySelectorAll('thead th[data-col]').forEach(function (th) {
            var k = th.getAttribute('data-col');
            if (k && w[k] != null) {
                var floor = thMinWidthFloor(th);
                var px = Math.max(floor, parseInt(w[k], 10) || 0);
                th.style.width = px + 'px';
                th.style.minWidth = px + 'px';
            }
        });
    }
    function tryLoadWidths() {
        try {
            var j = localStorage.getItem(widthsKey);
            if (j) applyWidths(JSON.parse(j));
        } catch (e) {}
    }
    function saveWidths() {
        var w = {};
        table.querySelectorAll('thead th[data-col]').forEach(function (th) {
            var k = th.getAttribute('data-col');
            if (k) w[k] = Math.round(th.getBoundingClientRect().width);
        });
        try {
            localStorage.setItem(widthsKey, JSON.stringify(w));
        } catch (e) {}
    }
    if (sjLedgerSortableInstance && typeof sjLedgerSortableInstance.destroy === 'function') {
        try { sjLedgerSortableInstance.destroy(); } catch (e) {}
        sjLedgerSortableInstance = null;
    }
    tryLoadOrder();
    tryLoadWidths();
    var lastGoodOrder = getOrderFromThead().slice();
    sjLedgerSortableInstance = Sortable.create(theadRow, {
        animation: 150,
        handle: '.sj-th-drag',
        draggable: 'th.sj-th-reorder',
        filter: '.sj-th-fixed',
        preventOnFilter: true,
        ghostClass: 'sj-sortable-ghost',
        chosenClass: 'sj-sortable-chosen',
        onEnd: function () {
            var ord = getOrderFromThead();
            if (ord[0] !== 'view' || ord[ord.length - 1] !== 'action') {
                applyOrderArray(lastGoodOrder);
                return;
            }
            syncBodyColumnOrder(ord);
            saveOrder();
            lastGoodOrder = ord.slice();
        }
    });
    table.querySelectorAll('thead th .sj-col-resizer').forEach(function (handle) {
        handle.addEventListener('mousedown', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var th = handle.closest('th');
            if (!th) return;
            var startX = e.clientX;
            var startW = th.getBoundingClientRect().width;
            var minW = thMinWidthFloor(th);
            function onMove(e2) {
                var dx = e2.clientX - startX;
                var w = Math.max(minW, Math.round(startW + dx));
                th.style.width = w + 'px';
                th.style.minWidth = w + 'px';
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                document.body.style.cursor = '';
                th.classList.remove('sj-col-resizing');
                saveWidths();
            }
            th.classList.add('sj-col-resizing');
            document.body.style.cursor = 'col-resize';
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    });
}

function initStockJournalTable() {
    var $table = $('#ledgerTable');
    if ($table.length === 0) {
        console.log('Table not found');
        return;
    }
    
    initSJTableColumnsAndResize();
    
    // Initialize search
    $('#customSearch').off('keyup change').on('keyup change', function() {
        var searchVal = $(this).val().toLowerCase();
        $table.find('tbody tr').each(function() {
            var rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchVal) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // Initialize sorting
    initTableSorting();
    
    // Connect export button
    $('#exportExcelBtn').off('click').on('click', function() {
        exportTableToExcel();
    });
    
    // Connect print button
    $('#printBtn').off('click').on('click', function() {
        printTable();
    });
    
    console.log('Stock Journal table initialized');
}

// Table sorting functionality
function initTableSorting() {
    var $table = $('#ledgerTable');
    var $headers = $table.find('thead th');
    var sortOrder = {};
    
    // Sortable column headers: pointer cursor, no ↕/↑/↓ icons
    $headers.each(function(index) {
        if (index > 0 && index < $headers.length - 1) { // Skip first (View) and last (Action) columns
            $(this).css('cursor', 'pointer');
            $(this).attr('data-sort-col', index);
        }
    });
    
    // Click handler for sorting
    $headers.on('click', function(e) {
        if ($(e.target).closest('.sj-th-drag, .sj-col-resizer').length) return;
        var colIndex = $(this).index();
        var totalCols = $headers.length;
        if (colIndex === 0 || colIndex === totalCols - 1) return; // Don't sort first and last columns
        
        var $tbody = $table.find('tbody');
        var rows = $tbody.find('tr').get();
        
        // Toggle sort order
        sortOrder[colIndex] = sortOrder[colIndex] === 'asc' ? 'desc' : 'asc';
        var isAsc = sortOrder[colIndex] === 'asc';
        
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
    var $headerCells = $('#ledgerTable thead th');
    var totalCols = $headerCells.length;
    
    // Get headers (skip first View column and last Action column)
    $headerCells.each(function(i) {
        if (i > 0 && i < totalCols - 1) {
            var title = $(this).attr('data-sj-title') || $(this).text();
            headers.push('"' + String(title).replace(/"/g, '""').replace(/↕|↑|↓/g, '').trim() + '"');
        }
    });
    csv.push(headers.join(','));
    
    // Get visible rows data
    $('#ledgerTable tbody tr:visible').each(function() {
        var rowData = [];
        var $cells = $(this).find('td');
        var cellCount = $cells.length;
        $cells.each(function(i) {
            if (i > 0 && i < cellCount - 1) {
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
    link.setAttribute('download', 'Stock_Journal_' + new Date().toISOString().slice(0,10) + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    alert('Excel/CSV file exported successfully!');
}

function printTable() {
    var printContents = '<html><head><title>Stock Journal Report</title>';
    printContents += '<style>';
    printContents += 'body { font-family: Arial, sans-serif; font-size: 11px; }';
    printContents += 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    printContents += 'th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }';
    printContents += 'th { background-color: #11294b; color: white; }';
    printContents += 'tr:nth-child(even) { background-color: #f2f2f2; }';
    printContents += 'h1 { text-align: center; color: #11294b; }';
    printContents += '@media print { th { background-color: #11294b !important; -webkit-print-color-adjust: exact; } }';
    printContents += '</style></head><body>';
    printContents += '<h1>Stock Journal Report</h1>';
    printContents += '<p>Generated on: ' + new Date().toLocaleString() + '</p>';
    printContents += '<table>';
    
    // Header (skip first View and last Action columns)
    var $headerCells = $('#ledgerTable thead th');
    var totalCols = $headerCells.length;
    printContents += '<thead><tr>';
    $headerCells.each(function(i) {
        if (i > 0 && i < totalCols - 1) {
            var title = $(this).attr('data-sj-title') || $(this).text();
            printContents += '<th>' + String(title).replace(/↕|↑|↓/g, '').trim() + '</th>';
        }
    });
    printContents += '</tr></thead>';
    
    // Body
    printContents += '<tbody>';
    $('#ledgerTable tbody tr:visible').each(function() {
        printContents += '<tr>';
        var $cells = $(this).find('td');
        var cellCount = $cells.length;
        $cells.each(function(i) {
            if (i > 0 && i < cellCount - 1) {
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

