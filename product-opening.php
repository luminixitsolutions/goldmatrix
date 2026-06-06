<?php 
session_start();
require_once 'config.php';
require_once __DIR__ . '/includes/auragold_product_catalog_scope.php';
require_once __DIR__ . '/includes/auragold_product_branch_local_schema.php';
require_once __DIR__ . '/includes/branch_product_delete_permission.php';
if (isset($conn) && $conn instanceof mysqli) {
    auragold_ensure_product_branch_local_schema($conn);
    auragold_ensure_tbl_product_branches_is_active($conn);
}
if (!empty($conn_master)) {
    auragold_ensure_branches_allow_product_delete_column($conn_master);
}
$auragold_show_product_delete = auragold_product_delete_allowed_for_working_context();

// Format decimal for display: preserve decimals (e.g. 0.999), no unnecessary trailing zeros
function format_decimal_display($val) {
    if ($val === '' || $val === null) return '';
    $v = trim((string)$val);
    if ($v === '') return '';
    if (!is_numeric($v)) return htmlspecialchars($v);
    $f = (float)$v;
    if ($f == (int)$f && (string)(int)$f === (string)$f) return (string)(int)$f;
    return rtrim(rtrim(number_format($f, 4, '.', ''), '0'), '.');
}

/** Purity/Carat for opening stock: default 1 for Gold, Silver, Platinum when not stored */
function opening_purity_field_value($metal_display_name, $char_data) {
    if ($char_data && array_key_exists('opening_purity', $char_data) && $char_data['opening_purity'] !== null && $char_data['opening_purity'] !== '') {
        return format_decimal_display($char_data['opening_purity']);
    }
    $n = trim((string)$metal_display_name);
    if (in_array($n, ['Gold', 'Silver', 'Platinum'], true)) {
        return '1';
    }
    return '';
}

/** Barcode prefix default by metal for product characteristics rows */
function opening_barcode_prefix_default($metal_display_name) {
    $n = trim((string)$metal_display_name);
    $defaults = [
        'Gold' => 'GD',
        'Silver' => 'SV',
        'Diamond & Stones' => 'DM',
    ];
    return $defaults[$n] ?? 'RN';
}

/** Resolve barcode prefix for display/edit (keeps custom saved values) */
function opening_barcode_prefix_value($metal_display_name, $char_data) {
    if ($char_data && array_key_exists('barcode_prefix', $char_data)) {
        $saved = trim((string)$char_data['barcode_prefix']);
        if ($saved !== '') {
            return $saved;
        }
    }
    return opening_barcode_prefix_default($metal_display_name);
}

// Load Categories from database
$categories = getList("SELECT id, name FROM tbl_categories WHERE status = 1 ORDER BY name ASC");

// Load Branches from database
$branches = getListMaster("SELECT id, name, code FROM tbl_branches WHERE status = 1 ORDER BY name ASC");

$auragold_working_branch_id = 0;
if (!empty($_SESSION['working_branch_id'])) {
    $auragold_working_branch_id = (int) $_SESSION['working_branch_id'];
} elseif (!empty($_SESSION['branch_id'])) {
    $auragold_working_branch_id = (int) $_SESSION['branch_id'];
}

$auragold_sub_branch_mode = false;
$auragold_main_branch_id_for_catalog = 0;
$auragold_sub_branch_name = '';
if ($auragold_working_branch_id > 0 && !empty($conn_master)) {
    $auragold_branch_ctx = getRecordMaster(
        'SELECT id, name, main_branch_id FROM tbl_branches WHERE id = ' . $auragold_working_branch_id . ' AND status = 1 LIMIT 1'
    );
    if ($auragold_branch_ctx && (int) ($auragold_branch_ctx['main_branch_id'] ?? 0) > 0) {
        $auragold_sub_branch_mode = true;
        $auragold_main_branch_id_for_catalog = (int) $auragold_branch_ctx['main_branch_id'];
        $auragold_sub_branch_name = trim((string) ($auragold_branch_ctx['name'] ?? ''));
    }
}

// Sub-branch: if no master rows yet for this branch, copy Metal / GST / Currency / Location / Unit from main (same DB, branch_id scoping).
if (!empty($conn) && $conn instanceof mysqli && $auragold_sub_branch_mode && $auragold_main_branch_id_for_catalog > 0 && $auragold_working_branch_id > 0
    && function_exists('auragold_seed_subbranch_masters_from_main')) {
    $msc = @getRecord('SELECT COUNT(*) AS c FROM tbl_metal WHERE branch_id = ' . (int) $auragold_working_branch_id);
    if (!$msc || (int) ($msc['c'] ?? 0) === 0) {
        auragold_seed_subbranch_masters_from_main($conn, $auragold_main_branch_id_for_catalog, $auragold_working_branch_id);
    }
}

// Load Calculation Modes from database
$calculation_modes = getList("SELECT id, name, code FROM tbl_calculation_modes WHERE status = 1 ORDER BY sort_order ASC, name ASC");

// Tax Master: taxes shown on product opening (from Masters)
$tax_master_list = [];
$tax_master_table = @mysqli_query($conn, "SELECT 1 FROM tbl_tax_master LIMIT 1");
if ($tax_master_table) {
    $tax_master_list = getList("SELECT id, name, default_value, default_calculation_mode FROM tbl_tax_master WHERE status = 1 " . auragold_master_list_sql_suffix($conn, 'tbl_tax_master') . " ORDER BY sort_order ASC, id ASC");
}

// Load Locations from database (branch-scoped when logged into a branch — same as Masters)
$locations = getList("SELECT id, name FROM tbl_location WHERE status = 1 " . auragold_master_list_sql_suffix($conn, 'tbl_location') . " ORDER BY id ASC");

// Load Units from database
$units = getList("SELECT id, name FROM tbl_unit WHERE status = 1 " . auragold_master_list_sql_suffix($conn, 'tbl_unit') . " ORDER BY id ASC");

// Metals for characteristics: same branch scoping + display_name dedupe as Sale Invoice (same tbl_metal ids per tab).
$metals_sql_suffix = '';
if ($auragold_working_branch_id > 0 && function_exists('auragold_master_list_sql_for_branch_id')) {
    $metals_sql_suffix = auragold_master_list_sql_for_branch_id($conn, 'tbl_metal', $auragold_working_branch_id);
}
if ($metals_sql_suffix === '' && function_exists('auragold_settings_main_branch_id')) {
    $auragold_main_branch_for_metals = (int) auragold_settings_main_branch_id();
    if ($auragold_main_branch_for_metals > 0 && function_exists('auragold_master_list_sql_for_branch_id')) {
        $metals_sql_suffix = auragold_master_list_sql_for_branch_id($conn, 'tbl_metal', $auragold_main_branch_for_metals);
    }
}
if ($metals_sql_suffix === '' && function_exists('auragold_master_list_sql_suffix')) {
    $metals_sql_suffix = auragold_master_list_sql_suffix($conn, 'tbl_metal');
}
$metals_list = getList("SELECT id, display_name, hsn_code FROM tbl_metal WHERE status = 1 " . $metals_sql_suffix . " ORDER BY id ASC");
if (!is_array($metals_list)) {
    $metals_list = [];
}
if (count($metals_list) > 1) {
    $seen_metal_tab = [];
    $metals_dedup = [];
    foreach ($metals_list as $mrow) {
        $dn = strtolower(trim((string) ($mrow['display_name'] ?? '')));
        if ($dn === '') {
            continue;
        }
        if (isset($seen_metal_tab[$dn])) {
            continue;
        }
        $seen_metal_tab[$dn] = true;
        $metals_dedup[] = $mrow;
    }
    $metals_list = $metals_dedup;
}

// Load Products from database
$search_term = isset($_GET['search']) ? esc($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$offset = ($page - 1) * $per_page;

$auragold_sub_branch_list_from_pb = false;
if ($auragold_sub_branch_mode && $auragold_main_branch_id_for_catalog > 0 && $auragold_working_branch_id > 0) {
    $tb_list = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product_branches'");
    if ($tb_list && mysqli_num_rows($tb_list) > 0) {
        mysqli_free_result($tb_list);
        $auragold_sub_branch_list_from_pb = true;
        $sub_b_list = (int) $auragold_working_branch_id;
        $where_clause = "p.status IN (0, 1) AND pb.branch_id = $sub_b_list";
    } else {
        if ($tb_list) {
            mysqli_free_result($tb_list);
        }
        $where_clause = "0=1";
    }
} else {
    $where_clause = "status = 1";
}
if ($search_term != '') {
    if ($auragold_sub_branch_list_from_pb) {
        $where_clause .= " AND (p.name LIKE '%$search_term%' OR p.alternate_name LIKE '%$search_term%' OR p.article LIKE '%$search_term%')";
    } else {
        $where_clause .= " AND (name LIKE '%$search_term%' OR alternate_name LIKE '%$search_term%' OR article LIKE '%$search_term%')";
    }
}

if ($auragold_sub_branch_list_from_pb) {
    $count_row = getRecord(
        "SELECT COUNT(*) AS total FROM tbl_products p INNER JOIN tbl_product_branches pb ON pb.product_id = p.id WHERE $where_clause"
    );
} else {
    $count_row = getRecord("SELECT COUNT(*) AS total FROM tbl_products WHERE $where_clause");
}
$total_products = (int) ($count_row['total'] ?? 0);
$total_pages = $per_page > 0 ? (int) ceil($total_products / $per_page) : 0;

if ($auragold_sub_branch_list_from_pb) {
    $products = getList(
        "SELECT p.id, p.name, p.alternate_name, p.article, p.status, IFNULL(pb.is_active, 1) AS branch_catalog_active "
        . "FROM tbl_products p INNER JOIN tbl_product_branches pb ON pb.product_id = p.id WHERE $where_clause ORDER BY p.id DESC LIMIT $per_page OFFSET $offset"
    );
} else {
    $product_select_cols = 'id, name, alternate_name, article';
    $products = getList("SELECT $product_select_cols FROM tbl_products WHERE $where_clause ORDER BY id DESC LIMIT $per_page OFFSET $offset");
}

// Get current product ID for editing (if any)
$edit_product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_product = null;
$edit_characteristics = [];
$edit_branches = [];
$edit_taxes = [];

if ($edit_product_id > 0) {
    if ($auragold_sub_branch_mode && $auragold_main_branch_id_for_catalog > 0 && $auragold_working_branch_id > 0) {
        $tb_ed = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product_branches'");
        if ($tb_ed && mysqli_num_rows($tb_ed) > 0) {
            mysqli_free_result($tb_ed);
            $sub_b_ed = (int) $auragold_working_branch_id;
            $edit_product = getRecord(
                "SELECT * FROM tbl_products WHERE id = $edit_product_id "
                . "AND id IN (SELECT product_id FROM tbl_product_branches WHERE branch_id = $sub_b_ed)"
            );
        } else {
            if ($tb_ed) {
                mysqli_free_result($tb_ed);
            }
            $edit_product = null;
        }
    } else {
        $edit_product = getRecord("SELECT * FROM tbl_products WHERE id = $edit_product_id AND status = 1");
    }
    if ($edit_product) {
        // Load product branches
        $edit_branches = getList("SELECT branch_id FROM tbl_product_branches WHERE product_id = $edit_product_id");
        if (empty($edit_branches)) {
            // Fallback: get from characteristics
            $edit_branches_raw = getList("SELECT DISTINCT branch_id FROM tbl_product_characteristics WHERE product_id = $edit_product_id");
            $edit_branches = $edit_branches_raw;
        }
        
        // Load product characteristics scoped by branch. Sub-branch: only that branch's rows.
        // Main branch: this branch's rows plus legacy rows (branch_id NULL/0). Without this, multiple rows
        // per metal (main + sub) load together and $char_map overwrites by metal_name — sub-branch prefix can "win".
        $pc_branch_filter = '';
        if (!empty($conn_master)) {
            if ($auragold_working_branch_id > 0) {
                $br_pc = getRecordMaster('SELECT main_branch_id FROM tbl_branches WHERE id = ' . (int) $auragold_working_branch_id . ' LIMIT 1');
                if ($br_pc && (int) ($br_pc['main_branch_id'] ?? 0) > 0) {
                    $pc_branch_filter = ' AND pc.branch_id = ' . (int) $auragold_working_branch_id;
                } elseif ($br_pc) {
                    $mbid = (int) $auragold_working_branch_id;
                    $pc_branch_filter = ' AND (pc.branch_id = ' . $mbid . ' OR pc.branch_id IS NULL OR pc.branch_id = 0)';
                }
            } elseif (function_exists('auragold_settings_main_branch_id')) {
                $mbid = (int) auragold_settings_main_branch_id();
                if ($mbid > 0) {
                    $pc_branch_filter = ' AND (pc.branch_id = ' . $mbid . ' OR pc.branch_id IS NULL OR pc.branch_id = 0)';
                }
            }
        }
        $edit_characteristics = getList("
            SELECT pc.*, m.display_name as metal_name 
            FROM tbl_product_characteristics pc
            LEFT JOIN tbl_metal m ON pc.metal_id = m.id
            WHERE pc.product_id = $edit_product_id AND pc.status = 1 $pc_branch_filter
        ");

        // Opening grid reads pc.opening_*; a fixed bug used to add Purchase Invoice SJ qty into those columns while
        // tbl_stock (stock_type=opening) stayed correct. Prefer the first active opening stock row per characteristic for display.
        if (is_array($edit_characteristics) && !empty($edit_characteristics)) {
            $po_pc_ids = [];
            foreach ($edit_characteristics as $__ec) {
                $__id = (int) ($__ec['id'] ?? 0);
                if ($__id > 0) {
                    $po_pc_ids[$__id] = true;
                }
            }
            if (!empty($po_pc_ids)) {
                $po_idlist = implode(',', array_keys($po_pc_ids));
                $po_stk_open = getList("SELECT id, product_characteristic_id, opening_qty, opening_weight FROM tbl_stock WHERE stock_type = 'opening' AND IFNULL(status, 1) = 1 AND product_characteristic_id IN ($po_idlist) ORDER BY id ASC");
                $po_first_open = [];
                if (is_array($po_stk_open)) {
                    foreach ($po_stk_open as $__sr) {
                        $__pcid = (int) ($__sr['product_characteristic_id'] ?? 0);
                        if ($__pcid > 0 && !isset($po_first_open[$__pcid])) {
                            $po_first_open[$__pcid] = $__sr;
                        }
                    }
                }
                foreach ($edit_characteristics as &$__po_ch) {
                    $__pcid = (int) ($__po_ch['id'] ?? 0);
                    if ($__pcid > 0 && !empty($po_first_open[$__pcid])) {
                        $__rowo = $po_first_open[$__pcid];
                        if (array_key_exists('opening_qty', $__rowo) && $__rowo['opening_qty'] !== null && $__rowo['opening_qty'] !== '') {
                            $__po_ch['opening_qty'] = $__rowo['opening_qty'];
                        }
                        if (array_key_exists('opening_weight', $__rowo) && $__rowo['opening_weight'] !== null && $__rowo['opening_weight'] !== '') {
                            $__po_ch['opening_weight'] = $__rowo['opening_weight'];
                        }
                    }
                }
                unset($__po_ch);
            }
        }

        // Branch-local category / stock (tbl_product_branch_settings); fallback to tbl_products for legacy rows
        $edit_branch_settings = null;
        $po_display_category_id = (int) ($edit_product['category_id'] ?? 0);
        $po_display_is_stock = (int) ($edit_product['is_stock_item'] ?? 0);
        if ($auragold_working_branch_id > 0) {
            $edit_branch_settings = getRecord(
                'SELECT * FROM tbl_product_branch_settings WHERE product_id = ' . (int) $edit_product_id
                . ' AND branch_id = ' . (int) $auragold_working_branch_id . ' LIMIT 1'
            );
            if ($edit_branch_settings) {
                if (isset($edit_branch_settings['category_id']) && $edit_branch_settings['category_id'] !== null && $edit_branch_settings['category_id'] !== '') {
                    $po_display_category_id = (int) $edit_branch_settings['category_id'];
                }
                $po_display_is_stock = (int) ($edit_branch_settings['is_stock_item'] ?? 0);
            }
        }
        // tbl_product_tax.branch_id must match product_opening_save_core.php (session branch, else first main branch)
        $po_tax_branch_id = (int) $auragold_working_branch_id;
        if ($po_tax_branch_id <= 0) {
            $mbr_tax = getRecordMaster('SELECT id FROM tbl_branches WHERE IFNULL(main_branch_id,0)=0 AND status = 1 ORDER BY id ASC LIMIT 1');
            if ($mbr_tax && !empty($mbr_tax['id'])) {
                $po_tax_branch_id = (int) $mbr_tax['id'];
            }
        }
        if ($po_tax_branch_id > 0) {
            $branch_tax_list = getList(
                "SELECT * FROM tbl_product_tax WHERE product_id = $edit_product_id AND branch_id = $po_tax_branch_id AND status = 1"
            );
            if (!empty($branch_tax_list)) {
                $edit_taxes = $branch_tax_list;
            } else {
                $edit_taxes = getList(
                    "SELECT * FROM tbl_product_tax WHERE product_id = $edit_product_id AND status = 1 AND (branch_id IS NULL OR branch_id = 0)"
                );
            }
        } else {
            $edit_taxes = getList(
                "SELECT * FROM tbl_product_tax WHERE product_id = $edit_product_id AND status = 1 AND (branch_id IS NULL OR branch_id = 0)"
            );
        }
    }
}

$auragold_po_sub_branch_edit = !empty($auragold_sub_branch_mode) && $edit_product_id > 0;

// New product: default branch = login branch (working context or session), else legacy "main" name match, else first branch
$auragold_new_product_default_branch_id = 0;
$auragold_new_product_default_branch_name = '';
if (!$edit_product && !empty($branches)) {
    $branch_ids_ok = array_map('intval', array_column($branches, 'id'));
    $bid = 0;
    if (!empty($_SESSION['working_branch_id'])) {
        $bid = (int) $_SESSION['working_branch_id'];
    } elseif (!empty($_SESSION['branch_id'])) {
        $bid = (int) $_SESSION['branch_id'];
    }
    if ($bid > 0 && in_array($bid, $branch_ids_ok, true)) {
        $auragold_new_product_default_branch_id = $bid;
    }
    if ($auragold_new_product_default_branch_id <= 0) {
        foreach ($branches as $branch) {
            $nl = strtolower((string) ($branch['name'] ?? ''));
            if (strpos($nl, 'main') !== false && strpos($nl, 'dubai') === false) {
                $auragold_new_product_default_branch_id = (int) $branch['id'];
                break;
            }
        }
    }
    if ($auragold_new_product_default_branch_id <= 0) {
        $auragold_new_product_default_branch_id = (int) $branches[0]['id'];
    }
    foreach ($branches as $branch) {
        if ((int) $branch['id'] === $auragold_new_product_default_branch_id) {
            $auragold_new_product_default_branch_name = (string) ($branch['name'] ?? '');
            break;
        }
    }
}
?>
<!DOCTYPE html>

<html lang="en" class="default-style">

<head>
    <title>Gold Matrix - Advance Software for Smart Jewellers</title>

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">

<meta name="description" content="Gold Matrix is an advanced jewellery management software designed for smart jewellers. Manage billing, inventory, karigar accounts, gold rates, stock tracking, CRM, reports, and financial operations with precision and ease." />

<meta name="keywords" content="Jewellery Software, Gold Billing Software, Jewellery Management System, Gold Shop Software, Jewellery Inventory Software, Karigar Management, Gold Rate Management, Retail Jewellery Software, Jewellery ERP, Smart Jewellers Software" />

<meta name="author" content="Gold Matrix Software Team" />
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php';?>
</head>

<style>
html, body{
    overflow-x: hidden !important;
    overflow-y: auto;
    height: 100vh;
}

.layout-content {
    min-height: calc(100vh - 60px);
    overflow-y: auto;
    padding-bottom: 8px;
}

.container-fluid {
    min-height: 100%;
    overflow: visible;
    display: flex;
    flex-direction: column;
}

/* Reduce bottom space */
.layout-content .card.mb-4 { margin-bottom: 0.5rem !important; }

.row {
    min-height: 0;
    overflow: visible;
}

.card {
    display: flex;
    flex-direction: column;
    overflow: visible;
}

.card-body {
    flex: 1;
    min-height: 0;
    overflow: visible;
    display: flex;
    flex-direction: column;
}

/* ===== AJAX Loader ===== */
#ajaxLoader {
    position: fixed;
    inset: 0;
    z-index: 9999;
}

.loader-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.25);
}

.loader-spinner {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 45px;
    height: 45px;
    margin: -22px 0 0 -22px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #c5a864;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ===== PRODUCT PAGE STYLING ===== */
.product-wrapper{
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 14px;
    min-height: 0;
    overflow: visible;
}

/* CARD STYLE */
.card-box{
    background: #fff;
    border: 1px solid #e6e6f0;
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

/* LEFT PRODUCT LIST PANEL */
.left-panel{
    height: 100%;
    max-height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.left-panel .btn-sm {
    font-size: 0.8rem;
    padding: 6px 12px;
    white-space: nowrap;
}

.po-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}

.po-toolbar .dropdown,
.po-toolbar > a.btn {
    flex: 0 0 auto;
}

.po-toolbar .btn-sm {
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.left-panel label {
    font-weight: 700;
    font-size: 0.85rem;
    color: #11294b;
    margin-bottom: 6px;
}

.left-panel .form-control-sm {
    font-size: 0.8rem;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

/* LIST TABLE */
.left-list{
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    max-height: 400px;
    margin-top: 8px;
}

.left-list table {
    margin-bottom: 0;
}

.left-list table tbody tr {
    transition: background 0.2s;
}

.left-list table tbody tr.product-row {
    cursor: pointer;
}

.left-list table tbody tr.product-row:hover {
    background: #f8fafc;
}

.left-list table tbody tr.product-row[style*="background"] {
    background: #e0e7ff !important;
    font-weight: 600;
}

.left-list table td {
    font-size: 0.8rem;
    padding: 8px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.left-list table td:last-child {
    text-align: right;
    width: 30px;
}

.left-list table td i {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.left-list table td i:hover {
    opacity: 1;
}

/* PAGINATION */
.pagination-wrapper {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.pagination-info {
    font-size: 0.8rem;
    color: #64748b;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pagination-controls button {
    background: #fff;
    border: 1px solid #e2e8f0;
    padding: 4px 8px;
    font-size: 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    color: #64748b;
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
    min-width: 32px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.pagination-controls .page-number.active {
    background: #11294b;
    color: #fff;
    border-color: #11294b;
}

.show-all-dropdown {
    font-size: 0.75rem;
    padding: 4px 8px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    background: #fff;
}

/* RIGHT CONTENT */
.right-content {
    display: flex;
    flex-direction: column;
    gap: 12px;
    height: 100%;
    overflow: hidden;
    flex: 1;
    min-height: 0;
}

.right-top{
    display: grid;
    grid-template-columns: 1.8fr 1fr;
    gap: 14px;
    align-items: start;
    min-width: 0;
}

.tax-table-wrapper {
    min-width: 0;
    overflow: hidden;
}

@media (min-width: 1400px) {
    .right-top{
        grid-template-columns: 2fr 1fr;
    }
}

/* —— Mobile / tablet: stack list + details (no squeezed side‑by‑side) —— */
@media (max-width: 991.98px) {
    .product-wrapper {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
        min-width: 0;
    }
    .left-panel {
        /* Taller list column on phones/tablets — room for ~5+ product rows */
        max-height: min(75vh, 620px);
        min-height: 0;
    }
    .left-list {
        min-height: 260px;
        max-height: none;
        flex: 1 1 auto;
    }
    .right-content {
        min-height: 0;
        min-width: 0;
        overflow: visible;
    }
    .right-top {
        grid-template-columns: 1fr;
        min-width: 0;
    }
    .form-row-custom {
        grid-template-columns: 1fr;
    }
    .product-details-form {
        min-width: 0;
    }
    .product-details-form .product-details-actions {
        width: 100%;
        margin-bottom: 0.5rem;
        justify-content: flex-start;
    }
    .tax-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .pc-scroll {
        min-height: 200px;
        max-height: min(62vh, calc(100dvh - 11rem));
        overflow-x: auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }

    /* Hide app identity strip (title, FY, DB) — more room for the form */
    body.page-product-opening .company-header .auragold-header-identity__title,
    body.page-product-opening .company-header .auragold-header-identity__pill,
    body.page-product-opening .company-header .user-info .auragold-header-db-name {
        display: none !important;
    }
    body.page-product-opening .company-header {
        row-gap: 0.35rem;
        padding-bottom: 10px;
    }

    /* Product characteristics: full-table horizontal scroll (no frozen columns on mobile) */
    body.page-product-opening .pc-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        min-width: 100%;
    }
    /* Visible compact header (keep in layout — display:none breaks colspan widths) */
    body.page-product-opening .pc-table thead {
        display: table-header-group;
    }
    body.page-product-opening .pc-table thead th {
        position: sticky;
        top: 0;
        left: auto;
        height: auto;
        max-height: none;
        padding: 7px 8px;
        font-size: 0.68rem;
        line-height: 1.2;
        overflow: visible;
        color: #ffffff;
        background: #11294b;
        border-color: #1e3a5f;
        box-shadow: none;
        white-space: nowrap;
        vertical-align: middle;
        cursor: default;
    }
    body.page-product-opening .pc-table thead tr#headerRow2 th {
        top: var(--pc-thead-h1);
        z-index: 8;
        font-size: 0.62rem;
        padding: 5px 6px;
        background: #1a3560;
    }
    body.page-product-opening .pc-table thead tr#headerRow1 > th[rowspan="2"] {
        z-index: 10;
    }
    body.page-product-opening .pc-table thead th .sort-arrows,
    body.page-product-opening .pc-table thead th .pc-col-drag-handle,
    body.page-product-opening .pc-table thead th .add-icon {
        display: none;
    }
    body.page-product-opening .pc-table thead th[data-col="check"],
    body.page-product-opening .pc-table thead th[data-col="metal"],
    body.page-product-opening .pc-table tbody td[data-col="check"],
    body.page-product-opening .pc-table tbody td[data-col="metal"] {
        position: static;
        left: auto;
        box-shadow: none;
        z-index: auto;
    }
    body.page-product-opening .pc-table tbody tr:hover td[data-col="metal"],
    body.page-product-opening .pc-table tbody tr:hover td[data-col="check"] {
        background: #f8fafc;
    }
    body.page-product-opening .pc-wrapper {
        overflow: visible;
        min-width: 0;
        max-width: 100%;
    }
    body.page-product-opening .pc-scroll {
        width: 100%;
        max-width: 100%;
        touch-action: pan-x pan-y;
    }

    /* Page shell: use width on narrow viewports */
    .layout-content > .container-fluid.flex-grow-1:has(.product-wrapper) {
        padding-left: 10px;
        padding-right: 10px;
    }
    .card-body:has(.product-wrapper) {
        padding: 12px;
    }
    .product-wrapper .card-box {
        padding: 12px 14px;
    }
    .left-panel .po-toolbar {
        flex-direction: row;
        align-items: center;
    }
    .left-panel .po-toolbar .btn,
    .left-panel .po-toolbar > a.btn {
        width: auto;
    }
    .pagination-wrapper {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    .pagination-wrapper .d-flex.align-items-center {
        justify-content: center;
        flex-wrap: wrap;
    }
    .checkbox-custom {
        margin-top: 8px;
    }
    .left-list table td.product-name {
        word-break: break-word;
        white-space: normal;
    }
}

@media (max-width: 575.98px) {
    .layout-content > .container-fluid.flex-grow-1:has(.product-wrapper) {
        padding-left: 8px;
        padding-right: 8px;
    }
    .card-body:has(.product-wrapper) {
        padding: 10px;
    }
    .product-wrapper .card-box {
        padding: 10px 12px;
    }
    .product-details-actions {
        flex-direction: column;
        align-items: stretch !important;
    }
    .product-details-actions .btn {
        width: 100%;
    }
    /* Narrower metal label column on very small screens */
    body.page-product-opening .pc-table thead th[data-col="metal"],
    body.page-product-opening .pc-table tbody td[data-col="metal"] {
        min-width: 140px;
        width: 140px;
    }
    body.page-product-opening .pc-scroll {
        max-height: min(58vh, calc(100dvh - 10rem));
    }
}

/* SECTION HEADING */
.sec-title{
    font-weight: 700;
    font-size: 0.9rem;
    color: #11294b;
    margin: 0 0 12px 0;
    padding-bottom: 6px;
}

/* PRODUCT DETAILS FORM */
.product-details-form {
    position: relative;
    min-width: 0;
    overflow: visible;
}

@media (min-width: 992px) {
    .product-details-form {
        overflow: hidden;
    }
    .product-details-form .product-details-actions {
        position: absolute;
        top: 0;
        right: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
}
.product-details-form .product-details-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    /* justify: flex-end on ≥992px in the block above; default start for mobile */
}

.product-details-form .save-btn-top {
    position: static;
}

.form-row-custom {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 12px;
}

.form-row-custom .form-group {
    margin-bottom: 0;
}

.form-row-custom label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #000;
    margin-bottom: 4px;
    display: block;
}

.form-row-custom .form-control,
.form-row-custom select {
    height: 32px;
    font-size: 0.8rem;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 10px;
}

.form-row-custom .form-control:focus,
.form-row-custom select:focus {
    border-color: #11294b;
    box-shadow: 0 0 0 3px rgba(90, 59, 140, 0.1);
}

/* Category and Branch with Plus Icon */
.select-with-add {
    position: relative;
}

.select-with-add .add-icon {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    color: #11294b;
    cursor: pointer;
    font-size: 0.9rem;
    z-index: 2;
}

/* Branch Tags */
.branch-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 6px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    min-height: 32px;
    background: #fff;
}

.branch-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f1f5f9;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #000;
}

.branch-tag .remove-tag {
    cursor: pointer;
    color: #ef4444;
    font-weight: bold;
    margin-left: 4px;
}

.branch-tags .add-branch-btn {
    color: #11294b;
    cursor: pointer;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
}

/* Checkbox Styling */
.checkbox-custom {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 24px;
}

.checkbox-custom input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.checkbox-custom label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #000;
    margin: 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* TAX TABLE */
.tax-table-wrapper {
    height: fit-content;
    width: 100%;
    overflow: hidden;
}

.tax-table-wrapper table {
    width: 100%;
    table-layout: fixed;
    margin-bottom: 0;
}

.tax-table-wrapper table th:nth-child(1),
.tax-table-wrapper table td:nth-child(1) {
    width: 32%;
    min-width: 90px;
}

.tax-table-wrapper table th:nth-child(2),
.tax-table-wrapper table td:nth-child(2) {
    width: 18%;
    min-width: 55px;
}

.tax-table-wrapper table th:nth-child(3),
.tax-table-wrapper table td:nth-child(3) {
    width: 50%;
    min-width: 110px;
}

.tax-table-wrapper table td select {
    width: 100%;
    font-size: 0.7rem;
    padding: 4px 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.tax-table-wrapper table td input[type="number"] {
    width: 100%;
    max-width: 65px;
    font-size: 0.75rem;
    padding: 4px 4px;
    min-width: 0;
}

.tax-table-wrapper .sec-title {
    margin-bottom: 8px;
}

.tax-table-wrapper table th {
    font-size: 0.75rem;
    font-weight: 700;
    color: #ffffff;
    background: #f8fafc;
    padding: 8px 6px;
    border: 1px solid #e2e8f0;
}

.tax-table-wrapper table td {
    padding: 8px 6px;
    border: 1px solid #e2e8f0;
    font-size: 0.8rem;
    vertical-align: middle;
}

.tax-table-wrapper table td:first-child {
    display: flex;
    align-items: center;
    gap: 6px;
}

.tax-table-wrapper table td input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
}

/* PRODUCT CHARACTERISTICS TABLE */
.pc-wrapper{
    border: 1px solid #e4e4f2;
    border-radius: 12px;
    padding: 8px;
    background: #ffffff;
    width: 100%;
}

.pc-scroll{
    max-height: calc(100vh - 280px);
    min-height: 420px;
    overflow-x: auto !important;
    overflow-y: auto !important;
    width: 100%;
    /* First thead row height — updated by syncPcStickyTheadOffset() for two-row sticky header */
    --pc-thead-h1: 42px;
}

.pc-table{
    width: max-content;
    min-width: 100%;
    white-space: nowrap;
    margin-bottom: 0;
}

.pc-table thead th{
    position: sticky;
    top: 0;
    background: #f7f8ff;
    z-index: 5;
    font-size: 0.8rem;
    font-weight: 700;
    color: #ffffff;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    text-align: center;
    vertical-align: middle;
}

/* Two-row header: sub-header row sticks under group row (Purity/Wastage Sale|Purchase, etc.) */
.pc-table thead tr#headerRow2 th {
    top: var(--pc-thead-h1);
    z-index: 8;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.08);
}
/* Single-row group headers (colspan only in row 1) stay in the top band */
.pc-table thead tr#headerRow1 > th[colspan]:not([rowspan="2"]) {
    z-index: 9;
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06);
}
/* Full-height pillar cells (checkbox + Metal + …) stack above sub-headers */
.pc-table thead tr#headerRow1 > th[rowspan="2"] {
    z-index: 10;
}

/* Column drag grip (row1 + row2 group sub-headers) */
.pc-col-drag-handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: middle;
    margin-right: 6px;
    padding: 1px 2px;
    color: #c9a227;
    cursor: grab;
    flex-shrink: 0;
    line-height: 1;
    user-select: none;
    -webkit-user-select: none;
}
.pc-col-drag-handle:active {
    cursor: grabbing;
}
.pc-col-drag-handle .feather,
.pc-col-drag-handle svg {
    width: 14px;
    height: 14px;
}

/* Sticky checkbox column in header (needed before Metal) */
.pc-table thead th[data-col="check"] {
    position: sticky;
    left: 0;
    z-index: 13;
    background: #f7f8ff;
}

/* Sticky Metal column in header */
.pc-table thead th[data-col="metal"] {
    position: sticky;
    left: 40px; /* Position after checkbox column */
    z-index: 12;
    background: #f7f8ff;
    box-shadow: 2px 0 4px rgba(0,0,0,0.1);
}

.pc-table tbody td{
    font-size: 0.85rem;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    vertical-align: middle;
}

/* Sticky checkbox column in body (needed before Metal) */
.pc-table tbody td[data-col="check"] {
    position: sticky;
    left: 0;
    z-index: 7;
    background: #ffffff;
}

/* Sticky Metal column in body */
.pc-table tbody td[data-col="metal"] {
    position: sticky;
    left: 40px; /* Position after checkbox column */
    z-index: 6;
    background: #ffffff;
    box-shadow: 2px 0 4px rgba(0,0,0,0.1);
}

/* Metal column: wide enough for long names (e.g. Imitation Or Watches) without overlapping HSN */
.pc-table thead th[data-col="metal"],
.pc-table tbody td[data-col="metal"] {
    min-width: 220px;
    width: 220px;
    box-sizing: border-box;
    white-space: normal;
}

/* Purity / Wastage sub-columns: wider cells for inputs and group headers */
.pc-table thead th[data-col="purity-sale"],
.pc-table thead th[data-col="purity-purchase"],
.pc-table thead th[data-col="wastage-sale"],
.pc-table thead th[data-col="wastage-purchase"],
.pc-table tbody td[data-col="purity-sale"],
.pc-table tbody td[data-col="purity-purchase"],
.pc-table tbody td[data-col="wastage-sale"],
.pc-table tbody td[data-col="wastage-purchase"] {
    min-width: 112px;
    width: 112px;
    box-sizing: border-box;
}

/* Ensure checkbox column has proper width (for left positioning calculation) */
.pc-table thead th[data-col="check"],
.pc-table tbody td[data-col="check"] {
    min-width: 40px;
    max-width: 40px;
    width: 40px;
}

.pc-table tbody td input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.pc-table tbody td input[type="text"],
.pc-table tbody td select.form-control-sm,
.pc-table tbody td .form-control-sm {
    height: 36px;
    font-size: 0.8rem;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    width: 100%;
    min-width: 96px;
}

.pc-table tbody td input[type="text"]:focus,
.pc-table tbody td .form-control-sm:focus {
    border-color: #11294b;
    outline: none;
    box-shadow: 0 0 0 2px rgba(90, 59, 140, 0.1);
}

.pc-table-header-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.pc-table-header-actions {
    position: relative;
}

.pc-table-header-actions .gear-icon {
    color: #64748b;
    cursor: pointer;
    font-size: 12px;
    transition: color 0.2s;
    position: relative;
}

.pc-table-header-actions .gear-icon:hover {
    color: #11294b;
}

/* Column Settings Dropdown */
.columns-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 1000;
    min-width: 250px;
    max-width: 300px;
    display: none;
    margin-top: 8px;
}

.columns-dropdown.show {
    display: block;
}

.columns-dropdown-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
    font-size: 0.85rem;
    color: #000;
}

.columns-dropdown-search {
    padding: 8px 16px;
    border-bottom: 1px solid #e2e8f0;
}

.columns-dropdown-search input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 0.8rem;
}

.columns-dropdown-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 8px 0;
}

.columns-dropdown-item {
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: background 0.2s;
}

.columns-dropdown-item:hover {
    background: #f8fafc;
}

.columns-dropdown-item input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.columns-dropdown-item label {
    margin: 0;
    cursor: pointer;
    font-size: 0.8rem;
    color: #000;
    flex: 1;
}

/* Draggable Column Headers - keep sticky so header stays fixed when scrolling */
.pc-table thead th {
    cursor: move;
    user-select: none;
    position: sticky;
    top: 0;
}

.pc-table thead th.draggable {
    cursor: grab;
}

.pc-table thead th.draggable:active {
    cursor: grabbing;
}

.pc-table thead th.dragging {
    opacity: 0.5;
    background: #e0e7ff !important;
    cursor: grabbing !important;
}

.pc-table thead th.drag-over {
    border-left: 3px solid #11294b;
}

.pc-table tbody td.dragging-cell {
    opacity: 0.5;
    background: #e0e7ff !important;
}

/* Group headers: drag from grip only (see .pc-col-drag-handle-main) */
.pc-table thead th[colspan].pc-col-drag {
    cursor: default;
}

.pc-table thead th.pc-col-no-drag {
    cursor: default !important;
}

.pc-metal-cell-inner {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.pc-metal-label {
    flex: 1;
    min-width: 0;
    white-space: normal;
    line-height: 1.35;
}

.pc-row-drag-handle {
    cursor: grab;
    color: #94a3b8;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    padding: 2px;
    line-height: 1;
    touch-action: none;
}

.pc-row-drag-handle:hover {
    color: #11294b;
}

.pc-row-drag-handle:active {
    cursor: grabbing;
}

.pc-table tbody tr.pc-row-sortable-ghost {
    opacity: 0.45;
    background: #eef2ff !important;
}

/* Sort arrows */
.sort-arrows {
    display: inline-flex;
    flex-direction: column;
    margin-left: 4px;
    opacity: 0.5;
    font-size: 0.7rem;
}

.pc-table thead th:hover .sort-arrows {
    opacity: 1;
}

.sort-arrows i {
    line-height: 0.6;
    cursor: pointer;
}

.sort-arrows i:hover {
    color: #11294b;
}

/* Karat Column Header with Plus */
.carat-header {
    position: relative;
}

.carat-header .add-icon {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    color: #11294b;
    cursor: pointer;
    font-size: 0.85rem;
}

/* Save Button */
.save-product-btn {
    margin-top: 16px;
    padding: 10px 24px;
    font-size: 0.9rem;
    font-weight: 600;
    background: #11294b;
    border: none;
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
}

.save-product-btn:hover {
    background: #4a2f70;
    box-shadow: 0 4px 12px rgba(90, 59, 140, 0.3);
}

/* Scrollbar Styling */
.left-list::-webkit-scrollbar,
.pc-scroll::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.left-list::-webkit-scrollbar-track,
.pc-scroll::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.left-list::-webkit-scrollbar-thumb,
.pc-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.left-list::-webkit-scrollbar-thumb:hover,
.pc-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Firefox Scrollbar */
.left-list,
.pc-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 #f1f5f9;
}

/* Additional refinements */
.product-wrapper {
    margin-top: 0;
}

.card-body {
    padding: 16px;
}

.form-group {
    margin-bottom: 0;
}

/* Ensure proper spacing in form rows */
.form-row-custom .form-group:first-child {
    margin-left: 0;
}

.form-row-custom .form-group:last-child {
    margin-right: 0;
}

/* Branch tags input styling */
.branch-tags input {
    border: none;
    outline: none;
    flex: 1;
    min-width: 100px;
    font-size: 0.75rem;
}

/* Table input alignment */
.pc-table tbody td {
    text-align: center;
}

.pc-table tbody td:first-child,
.pc-table tbody td:nth-child(2),
.pc-table tbody td[data-col="metal"] {
    text-align: left;
}

/* Hover effect for sticky columns */
.pc-table tbody tr:hover td[data-col="metal"],
.pc-table tbody tr:hover td[data-col="check"] {
    background: #f8fafc;
}

/* Button styling consistency */
.btn-primary {
    background: #11294b;
    border-color: #11294b;
}

.btn-primary:hover {
    background: #4a2f70;
    border-color: #4a2f70;
}

.btn-outline-secondary {
    border-color: #e2e8f0;
    color: #64748b;
}

.btn-outline-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

/* Branch Selection Modal */
.branch-option {
    padding: 10px 15px;
    cursor: pointer;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    margin-bottom: 8px;
    transition: all 0.2s;
    font-size: 0.9rem;
    color: #000;
}

.branch-option:hover {
    background: #f8fafc;
    border-color: #11294b;
    color: #11294b;
}

</style>

</head>

<body class="page-product-opening">
<!-- [ Preloader ] Start -->
<div class="page-loader">
    <div class="bg-primary"></div>
</div>
<!-- [ Preloader ] End -->

<!-- [ Layout wrapper ] Start -->
<div class="layout-wrapper layout-2">
    <div class="layout-inner">
        <!-- [ Layout sidenav ] Start -->
        <div id="layout-sidenav" class="layout-sidenav sidenav sidenav-vertical bg-white logo-dark">
            <!-- Brand demo -->
            <div class="app-brand demo">
                <span class="app-brand-logo demo">
                    <img src="assets/img/logo.png" alt="Brand Logo" class="img-fluid">
                </span>
                <a href="index-2.html" class="app-brand-text demo sidenav-text font-weight-normal ml-2">Empire</a>
                <a href="javascript:" class="layout-sidenav-toggle sidenav-link text-large ml-auto">
                    <i class="ion ion-md-menu align-middle"></i>
                </a>
            </div>
            <div class="sidenav-divider mt-0"></div>

            <!-- Links -->
            <ul class="sidenav-inner py-1">
                <li class="sidenav-item active">
                    <a href="billing-sales-invoice.html" class="sidenav-link">
                        <i class="sidenav-icon feather icon-file-text"></i>
                        <div>Sales Invoice</div>
                    </a>
                </li>
            </ul>
        </div>
        <!-- [ Layout sidenav ] End -->

        <!-- [ Layout container ] Start -->
        <div class="layout-container">
            <!-- [ Layout navbar ( Header ) ] Start -->
            <nav class="layout-navbar navbar navbar-expand-lg align-items-lg-center bg-dark container-p-x" id="layout-navbar">
                <a href="index-2.html" class="navbar-brand app-brand demo d-lg-none py-0 mr-4">
                    <span class="app-brand-logo demo">
                        <img src="assets/img/logo-dark.png" alt="Brand Logo" class="img-fluid">
                    </span>
                    <span class="app-brand-text demo font-weight-normal ml-2">Empire</span>
                </a>

                <div class="layout-sidenav-toggle navbar-nav d-lg-none align-items-lg-center mr-auto">
                    <a class="nav-item nav-link px-0 mr-lg-4" href="javascript:">
                        <i class="ion ion-md-menu text-large align-middle"></i>
                    </a>
                </div>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#layout-navbar-collapse">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="navbar-collapse collapse" id="layout-navbar-collapse">
                    <div class="navbar-nav align-items-lg-center ml-auto">
                        <div class="demo-navbar-notifications nav-item dropdown mr-lg-3">
                            <a class="nav-link dropdown-toggle hide-arrow" href="#" data-toggle="dropdown">
                                <i class="feather icon-bell navbar-icon align-middle"></i>
                                <span class="badge badge-danger badge-dot indicator"></span>
                            </a>
                        </div>
                        <div class="demo-navbar-user nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                                <span class="d-inline-flex flex-lg-row-reverse align-items-center align-middle">
                                    <img src="assets/img/avatars/1.png" alt class="d-block ui-w-30 rounded-circle">
                                    <span class="px-1 mr-lg-2 ml-2 ml-lg-0">SUPER ADMIN</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- [ Layout navbar ( Header ) ] End -->

            <!-- [ Layout content ] Start -->
            <div class="layout-content">
                <!-- [ content ] Start -->
                <div class="container-fluid flex-grow-1" style="padding-top: 0; padding-bottom: 0;">
                    <?php include 'sidebar.php';?>

<div class="row">
<div class="col-lg-12">
<div class="card mb-4">
<div class="card-body">

<div class="product-wrapper">


<!-- ================= LEFT PANEL ================= -->
<div class="card-box left-panel">

<div class="po-toolbar">
    <div class="dropdown po-excel-export-wrap">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="poExcelExportBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Export</button>
        <div class="dropdown-menu">
            <a class="dropdown-item js-po-excel-export-all" href="ajax/export-product-opening-excel.php">Export All Products</a>
        </div>
    </div>
    <div class="dropdown po-excel-import-wrap">
        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="poExcelImportBtn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Import</button>
        <div class="dropdown-menu">
            <a class="dropdown-item js-po-excel-import-trigger" href="#">Import Excel</a>
            <a class="dropdown-item js-po-excel-sample-download" href="ajax/download-product-opening-excel-sample.php">Download Sample</a>
        </div>
    </div>
    <input type="file" id="poExcelImportFile" accept=".xlsx,.xls" style="display:none;">
    <a href="product-opening.php" class="btn btn-primary btn-sm">+New</a>
</div>

<label>Product</label>
<form method="get" action="product-opening.php" id="searchForm">
    <input type="text" name="search" placeholder="Search" class="form-control form-control-sm" value="<?= htmlspecialchars($search_term) ?>" id="productSearch">
    <?php if(isset($_GET['id'])): ?>
    <input type="hidden" name="id" value="<?= (int)$_GET['id'] ?>">
    <?php endif; ?>
</form>

<div class="left-list">
<table class="table table-sm">
<tbody id="productListBody">
<?php 
if (!empty($products)) {
    foreach($products as $product) {
        $product_name = htmlspecialchars($product['name']);
        if (!empty($auragold_sub_branch_mode)) {
            if (isset($product['status']) && (int) $product['status'] === 0) {
                $product_name .= ' <span class="badge badge-secondary" style="font-size:10px;vertical-align:middle;">Inactive</span>';
            }
            if (isset($product['branch_catalog_active']) && (int) $product['branch_catalog_active'] === 0) {
                $product_name .= ' <span class="badge badge-warning" style="font-size:10px;vertical-align:middle;" title="Turn on under Active products for this branch">Pending activation</span>';
            }
        }
        $is_selected = ($edit_product_id == $product['id']) ? 'style="background: #e0e7ff;"' : '';
        echo '<tr class="product-row" data-product-id="'.$product['id'].'" '.$is_selected.'>';
        echo '<td style="cursor: pointer;" class="product-name">'.$product_name.'</td>';
        echo '<td style="text-align: right; width: 30px;">';
        if (!empty($auragold_sub_branch_mode)) {
            $plain_name = htmlspecialchars((string) ($product['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            echo '<i class="feather icon-minus-circle text-warning unassign-product-from-branch" data-product-id="'
                . (int) $product['id'] . '" data-product-name="' . $plain_name
                . '" title="Remove from this branch" style="cursor: pointer;"></i>';
        } elseif (!empty($auragold_show_product_delete)) {
            echo '<i class="feather icon-trash text-danger delete-product" data-product-id="'.$product['id'].'" data-product-name="'.$product_name.'" style="cursor: pointer;"></i>';
        } else {
            echo '<span class="text-muted" style="font-size:12px;" title="Delete is not enabled for this branch">—</span>';
        }
        echo '</td>';
        echo '</tr>';
    }
} else {
    if (!empty($auragold_sub_branch_mode)) {
        echo '<tr><td colspan="2" class="text-center text-muted" style="padding: 20px;">No products allocated to this branch yet. Use <strong>Active products for this branch</strong> to pick items from the main catalog.</td></tr>';
    } else {
        echo '<tr><td colspan="2" class="text-center text-muted" style="padding: 20px;">No products found</td></tr>';
    }
}
?>
</tbody>
</table>
</div>

<div class="pagination-wrapper">
    <div class="pagination-info">
        <?php if ($total_products > 0): ?>
        Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_products) ?> of <?= $total_products ?> entries
        <?php else: ?>
        Showing 0 of 0 entries
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="show-all-dropdown" id="perPageSelect">
            <option value="5" <?= $per_page == 5 ? 'selected' : '' ?>>5</option>
            <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
            <option value="25" <?= $per_page == 25 ? 'selected' : '' ?>>25</option>
            <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
            <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
        </select>
        <div class="pagination-controls">
            <button type="button" title="First" class="page-btn" data-page="1" <?= $page == 1 ? 'disabled' : '' ?>><i class="feather icon-chevrons-left"></i></button>
            <button type="button" title="Previous" class="page-btn" data-page="<?= max(1, $page - 1) ?>" <?= $page == 1 ? 'disabled' : '' ?>><i class="feather icon-chevron-left"></i></button>
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active = ($i == $page) ? 'active' : '';
                echo '<button class="page-number page-btn '.$active.'" data-page="'.$i.'">'.$i.'</button>';
            }
            ?>
            <button type="button" title="Next" class="page-btn" data-page="<?= min($total_pages, $page + 1) ?>" <?= $page >= $total_pages ? 'disabled' : '' ?>><i class="feather icon-chevron-right"></i></button>
            <button type="button" title="Last" class="page-btn" data-page="<?= $total_pages ?>" <?= $page >= $total_pages ? 'disabled' : '' ?>><i class="feather icon-chevrons-right"></i></button>
        </div>
    </div>
</div>

<?php if (!empty($auragold_sub_branch_mode)): ?>
<div class="mt-2">
    <button type="button" class="btn btn-outline-warning btn-sm btn-block text-uppercase font-weight-bold" id="btnSubbranchActivateProducts" data-toggle="modal" data-target="#subbranchMainCatalogModal" style="border-radius: 8px;" title="Pick which main-branch products are active for <?php echo htmlspecialchars($auragold_sub_branch_name !== '' ? $auragold_sub_branch_name : 'this branch', ENT_QUOTES, 'UTF-8'); ?>">
        Active products for this branch
    </button>
</div>
<?php endif; ?>

</div>



<!-- ================= RIGHT PANEL ================= -->
<div class="right-content">

<form method="post" action="product-save.php" id="productSaveForm">

<!-- ======= TOP FORM + TAX BOX ROW ======= -->
<div class="right-top">

<!-- LEFT FORM -->
<div class="card-box product-details-form">

<div class="product-details-actions mb-2">
<?php if ($edit_product_id > 0 && !empty($metals_list)): ?>
<button type="button" class="btn btn-outline-primary btn-sm" id="btnOpeningStockModal" data-toggle="modal" data-target="#openingStockModal">Opening Stock</button>
<?php endif; ?>
<button type="button" class="btn btn-primary btn-sm save-btn-top">Save</button>
</div>

<p class="sec-title">Product Details</p>

<?php if($edit_product_id > 0): ?>
<input type="hidden" name="product_id" value="<?= $edit_product_id ?>">
<?php endif; ?>

<div class="form-row-custom">
<div class="form-group">
<label>Name *</label>
<input name="name" class="form-control" required value="<?= $edit_product ? htmlspecialchars($edit_product['name']) : '' ?>"<?= !empty($auragold_po_sub_branch_edit) ? ' readonly tabindex="-1"' : '' ?>>
</div>

<div class="form-group">
<label>Article</label>
<input name="article" class="form-control" value="<?= $edit_product ? htmlspecialchars($edit_product['article']) : '' ?>"<?= !empty($auragold_po_sub_branch_edit) ? ' readonly tabindex="-1"' : '' ?>>
</div>

<div class="form-group">
<label>Alternate Name</label>
<input name="alternate_name" class="form-control" value="<?= $edit_product ? htmlspecialchars($edit_product['alternate_name']) : '' ?>"<?= !empty($auragold_po_sub_branch_edit) ? ' readonly tabindex="-1"' : '' ?>>
</div>
</div>

<div class="form-row-custom">
<div class="form-group">
<label>Category</label>
<div class="select-with-add">
<select name="category_id" class="form-control">
<?php
if (isset($po_display_category_id)) {
    $sel_cat = (int) $po_display_category_id;
} else {
    $sel_cat = $edit_product ? (int) ($edit_product['category_id'] ?? 0) : 0;
}
?>
<option value=""<?= $sel_cat === 0 ? ' selected' : '' ?>>Select Category</option>
<?php 
foreach ($categories as $cat) {
    $selected = ($sel_cat > 0 && $sel_cat === (int) $cat['id']) ? 'selected' : '';
    echo '<option value="'.$cat['id'].'" '.$selected.'>'.$cat['name'].'</option>';
}
?>
</select>
<?php if (empty($auragold_po_sub_branch_edit)): ?>
<i class="feather icon-plus add-icon" title="Add Category"></i>
<?php endif; ?>
</div>
</div>

<div class="form-group">
<label>Branch <span style="color: red;">*</span></label>
<div class="select-with-add">
<div class="branch-tags" id="branchTagsContainer" data-required="true">
<?php 
if (!empty($auragold_po_sub_branch_edit) && $auragold_working_branch_id > 0) {
    foreach ($branches as $branch) {
        if ((int) $branch['id'] === (int) $auragold_working_branch_id) {
            $branch_name = htmlspecialchars($branch['name']);
            echo '<span class="branch-tag" data-branch-id="' . (int) $branch['id'] . '">' . $branch_name . ' </span>';
            echo '<input type="hidden" name="branch_ids[]" value="' . (int) $branch['id'] . '">';
            break;
        }
    }
} elseif ($edit_product && !empty($edit_branches)) {
    // Show selected branches for edit; allow add/remove when not sub-branch–locked
    $selected_branch_ids = array_column($edit_branches, 'branch_id');
    foreach ($branches as $branch) {
        if (in_array($branch['id'], $selected_branch_ids)) {
            $branch_name = htmlspecialchars($branch['name']);
            echo '<span class="branch-tag" data-branch-id="' . (int) $branch['id'] . '">' . $branch_name;
            if (empty($auragold_po_sub_branch_edit)) {
                echo ' <span class="remove-tag">×</span>';
            }
            echo '</span>';
            echo '<input type="hidden" name="branch_ids[]" value="' . (int) $branch['id'] . '">';
        }
    }
} elseif (!empty($branches) && !$edit_product) {
    // Default: logged-in branch; user can add more via +
    if ($auragold_new_product_default_branch_id > 0) {
        foreach ($branches as $branch) {
            if ((int) $branch['id'] === $auragold_new_product_default_branch_id) {
                $branch_name = htmlspecialchars($branch['name']);
                echo '<span class="branch-tag" data-branch-id="' . (int) $branch['id'] . '">' . $branch_name . ' <span class="remove-tag">×</span></span>';
                echo '<input type="hidden" name="branch_ids[]" value="' . (int) $branch['id'] . '">';
                break;
            }
        }
    } else {
        echo '<span class="text-muted" style="font-size: 0.8rem;">No branches available</span>';
    }
} else {
    echo '<span class="text-muted" style="font-size: 0.8rem;">No branches available</span>';
}
if (empty($auragold_po_sub_branch_edit)) {
    echo '<span class="add-branch-btn" title="Add branch"><i class="feather icon-plus"></i></span>';
}
?>
</div>
</div>
</div>

<div class="form-group checkbox-custom">
<?php
$isi_chk = isset($po_display_is_stock) ? (int) $po_display_is_stock : (($edit_product && (int) ($edit_product['is_stock_item'] ?? 0) === 1) ? 1 : 0);
?>
<label><input type="checkbox" name="is_stock_item" value="1" <?= $isi_chk === 1 ? 'checked' : '' ?>> Show In Stock</label>
</div>
</div>

</div>


<!-- ======= TAX BOX (from Tax Master) ======= -->
<div class="card-box tax-table-wrapper">

<p class="sec-title">Tax</p>

<table class="table table-sm table-bordered mb-0">
<thead>
<tr>
<th>Tax</th>
<th>Value</th>
<th>Calculation Mo...</th>
</tr>
</thead>
<tbody>
<?php
// Edit mode: index by tax_type for lookup
$edit_taxes_by_name = [];
if ($edit_product && !empty($edit_taxes)) {
    foreach ($edit_taxes as $tax) {
        $edit_taxes_by_name[$tax['tax_type']] = $tax;
    }
}

if (!empty($tax_master_list)) {
    // Show one row per tax from Tax Master
    foreach ($tax_master_list as $t) {
        $saved = isset($edit_taxes_by_name[$t['name']]) ? $edit_taxes_by_name[$t['name']] : null;
        $val = $saved ? $saved['tax_value'] : $t['default_value'];
        $mode = $saved ? $saved['calculation_mode'] : $t['default_calculation_mode'];
        ?>
<tr>
<td><input type="checkbox" name="tax_enabled[<?= (int)$t['id'] ?>]" value="1" <?= $saved ? 'checked' : '' ?>> <?= htmlspecialchars($t['name']) ?></td>
<td><input type="number" name="tax_value[<?= (int)$t['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($val) ?>" step="0.01" style="width: 47px;"></td>
<td>
<select name="tax_calculation_mode[<?= (int)$t['id'] ?>]" class="form-control form-control-sm" style="font-size: 0.75rem;">
<?php
foreach ($calculation_modes as $mode_row) {
    $sel = ($mode_row['name'] == $mode) ? 'selected' : '';
    echo '<option value="'.htmlspecialchars($mode_row['name']).'" '.$sel.'>'.htmlspecialchars($mode_row['name']).'</option>';
}
?>
</select>
</td>
</tr>
<?php
    }
} else {
    // Fallback when Tax Master table not used: VAT and TAX BAH (legacy)
    $vat_tax = isset($edit_taxes_by_name['VAT']) ? $edit_taxes_by_name['VAT'] : null;
    $tax_bah_tax = isset($edit_taxes_by_name['TAX BAH']) ? $edit_taxes_by_name['TAX BAH'] : null;
?>
<tr>
<td><input type="checkbox" name="vat" value="1" <?= ($vat_tax) ? 'checked' : '' ?>> VAT</td>
<td><input type="number" name="vat_value" class="form-control form-control-sm" value="<?= $vat_tax ? htmlspecialchars($vat_tax['tax_value']) : '5' ?>" step="0.01" style="width: 47px;"></td>
<td>
<select name="vat_calculation_mode" class="form-control form-control-sm" style="font-size: 0.75rem;">
<?php foreach($calculation_modes as $mode) {
    $selected = ($vat_tax && $vat_tax['calculation_mode'] == $mode['name']) ? 'selected' : (($mode['name'] == 'Product Amount' && !$vat_tax) ? 'selected' : '');
    echo '<option value="'.htmlspecialchars($mode['name']).'" '.$selected.'>'.$mode['name'].'</option>';
} ?>
</select>
</td>
</tr>
<tr>
<td><input type="checkbox" name="tax_bah" value="1" <?= ($tax_bah_tax) ? 'checked' : '' ?>> TAX BAH</td>
<td><input type="number" name="tax_bah_value" class="form-control form-control-sm" value="<?= $tax_bah_tax ? htmlspecialchars($tax_bah_tax['tax_value']) : '10' ?>" step="0.01" style="width: 47px;"></td>
<td>
<select name="tax_bah_calculation_mode" class="form-control form-control-sm" style="font-size: 0.75rem;">
<?php foreach($calculation_modes as $mode) {
    $selected = ($tax_bah_tax && $tax_bah_tax['calculation_mode'] == $mode['name']) ? 'selected' : (($mode['name'] == 'Product Amount' && !$tax_bah_tax) ? 'selected' : '');
    echo '<option value="'.htmlspecialchars($mode['name']).'" '.$selected.'>'.$mode['name'].'</option>';
} ?>
</select>
</td>
</tr>
<?php } ?>
</tbody>
</table>

</div>

</div>




<!-- ================= CHARACTERISTICS TABLE ================= -->
<div class="card-box" style="flex: 1; display: flex; flex-direction: column; min-height: 0; overflow: visible;">

<div class="pc-table-header-actions">
<div>
<p class="sec-title mb-0">Product Characteristics</p>
</div>
<div style="position: relative;">
<i class="feather icon-settings gear-icon" id="columnSettingsBtn" title="Column Settings"></i>
<div class="columns-dropdown" id="columnsDropdown">
<div class="columns-dropdown-header">Columns</div>
<div class="columns-dropdown-search">
<input type="text" id="columnSearch" placeholder="Search columns...">
</div>
<div class="columns-dropdown-list" id="columnsList">
<!-- Will be populated by JavaScript -->
</div>
</div>
</div>
</div>

<div class="pc-wrapper" style="flex: 1; min-height: 0; display: flex; flex-direction: column;">
<div class="pc-scroll" style="flex: 1; min-height: 0;">

<table class="table table-bordered pc-table">

<thead id="pcTableHead">
<tr id="headerRow1">
<th rowspan="2" class="draggable pc-col-no-drag" data-col="check">✔</th>
<th rowspan="2" class="draggable pc-col-drag" data-col="metal">Metal <span class="sort-arrows"><i class="feather icon-chevron-up"></i><i class="feather icon-chevron-down"></i></span></th>
<th rowspan="2" class="draggable pc-col-drag" data-col="hsn">HSN <span class="sort-arrows"><i class="feather icon-chevron-up"></i><i class="feather icon-chevron-down"></i></span></th>
<th rowspan="2" class="draggable pc-col-drag" data-col="unit">Unit</th>
<th rowspan="2" class="draggable pc-col-drag" data-col="sku">SKU <span class="sort-arrows"><i class="feather icon-chevron-up"></i><i class="feather icon-chevron-down"></i></span></th>
<th rowspan="2" class="draggable pc-col-drag" data-col="making">Making On</th>
<th rowspan="2" class="draggable pc-col-drag" data-col="diamond">Diamond Category <span class="sort-arrows"><i class="feather icon-chevron-up"></i><i class="feather icon-chevron-down"></i></span></th>
<th rowspan="2" class="draggable pc-col-drag" data-col="location">Location</th>
<th rowspan="2" class="draggable pc-col-drag carat-header" data-col="carat">Karat <i class="feather icon-plus add-icon"></i> <span class="sort-arrows"><i class="feather icon-chevron-up"></i><i class="feather icon-chevron-down"></i></span></th>
<th rowspan="2" class="draggable pc-col-drag" data-col="discount">Discount</th>
<th colspan="2" class="text-center pc-col-drag" data-col="purity-group">Purity</th>
<th colspan="2" class="text-center pc-col-drag" data-col="wastage-group">Wastage</th>
<th rowspan="2" class="draggable pc-col-drag" data-col="wt-per-piece">Wt / Piece</th>
<th colspan="6" class="text-center d-none pc-col-drag" data-col="opening">Opening</th>
<th colspan="3" class="text-center pc-col-drag" data-col="barcode-group">Barcode</th>
<th rowspan="2" class="draggable pc-col-drag" data-col="serialized">Serialized</th>
<th colspan="7" class="text-center pc-col-drag" data-col="styles">Basic Styles</th>
</tr>

<tr id="headerRow2">
<th data-col="purity-sale">Sale</th>
<th data-col="purity-purchase">Purchase</th>
<th data-col="wastage-sale">Sale</th>
<th data-col="wastage-purchase">Purchase</th>
<th class="d-none" data-col="opening-weight">Weight</th>
<th class="d-none" data-col="opening-purity">Purity</th>
<th class="d-none" data-col="opening-qty">Qty</th>
<th class="d-none" data-col="opening-finalwt">Final Wt</th>
<th class="d-none" data-col="opening-rate">Rate</th>
<th class="d-none" data-col="opening-value">Value</th>
<th data-col="barcode-digits">Digits</th>
<th data-col="barcode-prefix">Prefix</th>
<th data-col="barcode">Barcode</th>
<th data-col="cut">Cut</th>
<th data-col="shape">Shape</th>
<th data-col="color">Color</th>
<th data-col="clarity">Clarity</th>
<th data-col="sieve">Sieve</th>
<th data-col="size">Size</th>
<th data-col="stylecode">Style Code</th>
</tr>
</thead>

<tbody>

<?php
// Create characteristics map for edit mode
$char_map = [];
if ($edit_product && !empty($edit_characteristics)) {
    foreach($edit_characteristics as $char) {
        $char_map[$char['metal_name']] = $char;
    }
}

$i = 0;
foreach ($metals_list as $metal):
    $char_data = isset($char_map[$metal['display_name']]) ? $char_map[$metal['display_name']] : null;
?>
<tr>

<td data-col="check"><input type="checkbox" name="row[<?= $i ?>][is_selected]" <?= ($char_data && (int) ($char_data['is_selected'] ?? 0) === 1) ? 'checked' : '' ?>></td>

<td data-col="metal">
<div class="pc-metal-cell-inner">
<span class="pc-row-drag-handle" title="Drag to reorder row"><i class="feather icon-menu"></i></span>
<input type="hidden" name="row[<?= $i ?>][metal]" value="<?= htmlspecialchars($metal['display_name']) ?>">
<span class="pc-metal-label"><?= htmlspecialchars($metal['display_name']) ?></span>
</div>
</td>

<td data-col="hsn"><input name="row[<?= $i ?>][hsn]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['hsn']) : ($metal['hsn_code'] ?: '7113') ?>"></td>

<td data-col="unit">
<select name="row[<?= $i ?>][unit_id]" class="form-control form-control-sm">
<option value="">Select</option>
<?php foreach($units as $u): ?>
<option value="<?= $u['id'] ?>" <?= ($char_data && isset($char_data['unit_id']) && $char_data['unit_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
<?php endforeach; ?>
</select>
</td>

<td data-col="sku"><input name="row[<?= $i ?>][sku_code]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['sku_code']) : '' ?>"></td>
<td data-col="making"><input name="row[<?= $i ?>][making_on]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['making_on']) : 'Gross Wt' ?>"></td>
<td data-col="diamond"><?php
$is_diamond_stones = ($metal['display_name'] == "Diamond & Stones");
$diamond_val = $char_data ? ($char_data['diamond_category'] ?? '') : '';
if ($is_diamond_stones): ?>
<select name="row[<?= $i ?>][diamond_category]" class="form-control form-control-sm">
<option value="">Select Diamond Category</option>
<option value="Diamonds" <?= ($diamond_val === 'Diamonds') ? 'selected' : '' ?>>Diamonds</option>
<option value="GemStones" <?= ($diamond_val === 'GemStones') ? 'selected' : '' ?>>GemStones</option>
<option value="Jewellery" <?= ($diamond_val === 'Jewellery') ? 'selected' : '' ?>>Jewellery</option>
</select>
<?php else: ?>
<input type="text" name="row[<?= $i ?>][diamond_category]" class="form-control form-control-sm" value="<?= htmlspecialchars($diamond_val) ?>">
<?php endif; ?></td>

<td data-col="location">
<select name="row[<?= $i ?>][location_id]" class="form-control form-control-sm">
<option value="">Select</option>
<?php foreach($locations as $l): ?>
<option value="<?= $l['id'] ?>" <?= ($char_data && isset($char_data['location_id']) && $char_data['location_id'] == $l['id']) ? 'selected' : '' ?>><?= htmlspecialchars($l['name']) ?></option>
<?php endforeach; ?>
</select>
</td>

<td data-col="carat"><input name="row[<?= $i ?>][carat]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['carat']) : '' ?>"></td>
<td data-col="discount"><input name="row[<?= $i ?>][discount]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['discount']) : '' ?>"></td>

<td data-col="purity-sale"><input name="row[<?= $i ?>][purity_sale]" class="form-control form-control-sm" value="<?= $char_data && isset($char_data['purity_sale']) ? htmlspecialchars($char_data['purity_sale']) : '' ?>"></td>
<td data-col="purity-purchase"><input type="checkbox" name="row[<?= $i ?>][purity_purchase]" value="1" <?= ($char_data && isset($char_data['purity_purchase']) && $char_data['purity_purchase'] == 1) ? 'checked' : '' ?>></td>

<td data-col="wastage-sale"><input name="row[<?= $i ?>][wastage_sale]" class="form-control form-control-sm" value="<?= $char_data && isset($char_data['wastage_sale']) ? htmlspecialchars($char_data['wastage_sale']) : '' ?>"></td>
<td data-col="wastage-purchase"><input name="row[<?= $i ?>][wastage_purchase]" class="form-control form-control-sm" value="<?= $char_data && isset($char_data['wastage_purchase']) ? htmlspecialchars($char_data['wastage_purchase']) : '' ?>"></td>

<td data-col="wt-per-piece"><input name="row[<?= $i ?>][wt_per_piece]" class="form-control form-control-sm" value="<?= $char_data && isset($char_data['wt_per_piece']) ? htmlspecialchars($char_data['wt_per_piece']) : '' ?>"></td>

<td class="d-none" data-col="opening-weight"><input name="row[<?= $i ?>][opening_weight]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars(format_decimal_display($char_data['opening_weight'])) : '' ?>"></td>
<td class="d-none" data-col="opening-purity"><input name="row[<?= $i ?>][opening_purity]" class="form-control form-control-sm" value="<?= htmlspecialchars(opening_purity_field_value($metal['display_name'], $char_data)) ?>"></td>
<td class="d-none" data-col="opening-qty"><input name="row[<?= $i ?>][opening_qty]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars(format_decimal_display($char_data['opening_qty'])) : '' ?>"></td>
<td class="d-none" data-col="opening-finalwt"><input name="row[<?= $i ?>][final_weight]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars(format_decimal_display($char_data['final_weight'])) : '' ?>" readonly></td>
<td class="d-none" data-col="opening-rate"><input name="row[<?= $i ?>][rate]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars(format_decimal_display($char_data['rate'])) : '' ?>"></td>
<td class="d-none" data-col="opening-value"><input name="row[<?= $i ?>][value]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars(format_decimal_display($char_data['value'])) : '' ?>" readonly></td>

<td data-col="barcode-digits"><input name="row[<?= $i ?>][barcode_digits]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['barcode_digits']) : '5' ?>"></td>
<td data-col="barcode-prefix"><input name="row[<?= $i ?>][barcode_prefix]" class="form-control form-control-sm" value="<?= htmlspecialchars(opening_barcode_prefix_value($metal['display_name'], $char_data)) ?>"></td>
<td data-col="barcode"><input name="row[<?= $i ?>][barcode]" class="form-control form-control-sm" value="<?= $char_data && isset($char_data['barcode']) ? htmlspecialchars($char_data['barcode']) : '' ?>"></td>

<td data-col="serialized"><input type="checkbox" name="row[<?= $i ?>][serialized_barcode]" value="1" <?= ($char_data && $char_data['serialized_barcode'] == 1) ? 'checked' : '' ?>></td>

<td data-col="cut"><input name="row[<?= $i ?>][cut]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['cut']) : '' ?>"></td>
<td data-col="shape"><input name="row[<?= $i ?>][shape]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['shape']) : '' ?>"></td>
<td data-col="color"><input name="row[<?= $i ?>][color]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['color']) : '' ?>"></td>
<td data-col="clarity"><input name="row[<?= $i ?>][clarity]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['clarity']) : '' ?>"></td>
<td data-col="sieve"><input name="row[<?= $i ?>][sieve]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['sieve']) : '' ?>"></td>
<td data-col="size"><input name="row[<?= $i ?>][size]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['size']) : '' ?>"></td>
<td data-col="stylecode"><input name="row[<?= $i ?>][style_code]" class="form-control form-control-sm" value="<?= $char_data ? htmlspecialchars($char_data['style_code']) : '' ?>"></td>

</tr>
<?php $i++; endforeach; ?>

</tbody>

</table>

</div>
</div>

</div>

</form>

</div>
</div>



</div>
</div>
</div>

</div>
</div>
</div>

<?php if ($edit_product_id > 0 && !empty($metals_list)): ?>
<div class="modal fade" id="openingStockModal" tabindex="-1" role="dialog" aria-labelledby="openingStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 960px;">
        <div class="modal-content">
            <div class="modal-header py-2 align-items-center">
                <h5 class="modal-title w-100 text-center mb-0" id="openingStockModalLabel">
                    <?php if (!empty($auragold_po_sub_branch_edit)): ?>
                    Opening stock — <?= htmlspecialchars($auragold_sub_branch_name !== '' ? $auragold_sub_branch_name : 'this branch', ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                    Opening stock
                    <?php endif; ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2">
                <?php if (!empty($auragold_po_sub_branch_edit)): ?>
                <p class="text-muted small text-center mb-2 px-1">These figures are for <strong><?= htmlspecialchars($auragold_sub_branch_name !== '' ? $auragold_sub_branch_name : 'this branch', ENT_QUOTES, 'UTF-8'); ?></strong> only. Click <strong>Save</strong> in this dialog, then <strong>Save</strong> on the product form to store.</p>
                <?php endif; ?>
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-primary btn-sm" id="openingStockModalSave">Save</button>
                </div>
                <div class="table-responsive opening-stock-modal-table">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="min-width: 160px;">Metal</th>
                                <th>Weight</th>
                                <th>Purity/Carat</th>
                                <th>Final Wt.</th>
                                <th>Quantity</th>
                                <th>Rate</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
$os_i = 0;
foreach ($metals_list as $os_metal):
    $os_char = isset($char_map[$os_metal['display_name']]) ? $char_map[$os_metal['display_name']] : null;
?>
                            <tr data-opening-row="<?= $os_i ?>" data-metal-name="<?= htmlspecialchars($os_metal['display_name']) ?>">
                                <td>
                                    <label class="mb-0 d-flex align-items-center" style="gap:6px;">
                                        <input type="checkbox" class="os-is-selected" <?= ($os_char && !empty($os_char['is_selected']) && (int) $os_char['is_selected'] === 1) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($os_metal['display_name']) ?></span>
                                    </label>
                                </td>
                                <td><input type="text" class="form-control form-control-sm os-opening-weight" inputmode="decimal" value="<?= $os_char ? htmlspecialchars(format_decimal_display($os_char['opening_weight'])) : '' ?>"></td>
                                <td><input type="text" class="form-control form-control-sm os-opening-purity" inputmode="decimal" value="<?= htmlspecialchars(opening_purity_field_value($os_metal['display_name'], $os_char)) ?>"></td>
                                <td><input type="text" class="form-control form-control-sm os-final-weight" readonly tabindex="-1"></td>
                                <td><input type="text" class="form-control form-control-sm os-opening-qty" inputmode="decimal" value="<?= $os_char ? htmlspecialchars(format_decimal_display($os_char['opening_qty'])) : '' ?>"></td>
                                <td><input type="text" class="form-control form-control-sm os-opening-rate" inputmode="decimal" value="<?= $os_char ? htmlspecialchars(format_decimal_display($os_char['rate'])) : '' ?>"></td>
                                <td><input type="text" class="form-control form-control-sm os-opening-value" readonly tabindex="-1"></td>
                            </tr>
<?php
    $os_i++;
endforeach;
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($auragold_sub_branch_mode)): ?>
<div class="modal fade" id="subbranchMainCatalogModal" tabindex="-1" role="dialog" aria-labelledby="subbranchMainCatalogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document" style="max-width: 720px;">
        <div class="modal-content">
            <div class="modal-header py-2 align-items-center">
                <h5 class="modal-title mb-0" id="subbranchMainCatalogModalLabel">Main branch products — active at <?php echo htmlspecialchars($auragold_sub_branch_name !== '' ? $auragold_sub_branch_name : 'this branch', ENT_QUOTES, 'UTF-8'); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-2">All products saved on the main branch are listed (including inactive). Check the products that should be active for your branch.</p>
                <div class="form-group mb-2">
                    <input type="text" class="form-control form-control-sm" id="subbranchCatalogFilter" placeholder="Filter by name or article…" autocomplete="off">
                </div>
                <div id="subbranchCatalogLoading" class="text-center text-muted py-4" style="display:none;">Loading…</div>
                <div id="subbranchCatalogError" class="alert alert-danger py-2 small" style="display:none;"></div>
                <div class="table-responsive" style="max-height: 55vh;">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:42px;" class="text-center">
                                    <label class="mb-0"><input type="checkbox" id="subbranchCatalogCheckAll" title="Select all visible"></label>
                                </th>
                                <th>Product</th>
                                <th style="width:88px;">Master</th>
                            </tr>
                        </thead>
                        <tbody id="subbranchCatalogTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-primary" id="subbranchCatalogSaveBtn">Save</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

                </div>
                <!-- [ content ] End -->
            </div>
            <!-- [ Layout content ] End -->
        </div>
        <!-- [ Layout container ] End -->
    </div>
    <!-- Overlay -->
    <div class="layout-overlay layout-sidenav-toggle"></div>
</div>
<div id="ajaxLoader" style="display:none;">
    <div class="loader-backdrop"></div>
    <div class="loader-spinner"></div>
</div>
<!-- / Layout wrapper -->

<!-- Core scripts -->
<?php include 'footer-script.php';?>
<script src="assets/js/product-opening-excel-import.js?v=<?php echo @filemtime(__DIR__ . '/assets/js/product-opening-excel-import.js'); ?>"></script>

<!-- Sortable.js for column drag and drop -->
<script src="assets/libs/sortablejs/sortable.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>

<script>
// Pass branch data to JavaScript (multi-branch picker + save)
const availableBranches = <?php echo json_encode($branches); ?>;
window.availableBranches = availableBranches;
window.auragoldDefaultBranchId = <?php echo (int) $auragold_new_product_default_branch_id; ?>;
window.auragoldDefaultBranchName = <?php echo json_encode($auragold_new_product_default_branch_name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.auragoldSubBranchMode = <?php echo !empty($auragold_sub_branch_mode) ? 'true' : 'false'; ?>;

$(document).ready(function() {
    // Column definitions with visibility state
    const columnDefinitions = [
        { key: 'check', label: '✔', visible: true, order: 0 },
        { key: 'metal', label: 'Metal', visible: true, order: 1 },
        { key: 'hsn', label: 'HSN', visible: true, order: 2 },
        { key: 'unit', label: 'Unit', visible: true, order: 3 },
        { key: 'sku', label: 'SKU/Product Code', visible: true, order: 4 },
        { key: 'making', label: 'Making on *', visible: true, order: 5 },
        { key: 'diamond', label: 'Diamond Category', visible: true, order: 6 },
        { key: 'location', label: 'Location', visible: true, order: 7 },
        { key: 'carat', label: 'Karat', visible: true, order: 8 },
        { key: 'discount', label: 'Discount', visible: true, order: 9 },
        { key: 'purity-group', label: 'Purity (group)', visible: true, order: 10 },
        { key: 'purity-sale', label: 'Purity (group) - Sale', visible: true, order: 11 },
        { key: 'purity-purchase', label: 'Purity (group) - Purchase', visible: true, order: 12 },
        { key: 'wastage-group', label: 'Wastage (group)', visible: true, order: 13 },
        { key: 'wastage-sale', label: 'Wastage (group) - Sale', visible: true, order: 14 },
        { key: 'wastage-purchase', label: 'Wastage (group) - Purchase', visible: true, order: 15 },
        { key: 'wt-per-piece', label: 'Wt. Per Piece', visible: true, order: 16 },
        { key: 'opening', label: 'Opening (group)', visible: true, order: 17 },
        { key: 'opening-weight', label: 'Opening (group) - Weight', visible: true, order: 18 },
        { key: 'opening-purity', label: 'Opening (group) - Purity/Karat', visible: true, order: 19 },
        { key: 'opening-qty', label: 'Opening (group) - Quantity', visible: true, order: 20 },
        { key: 'opening-finalwt', label: 'Opening (group) - Final Wt.', visible: true, order: 21 },
        { key: 'opening-rate', label: 'Opening (group) - Rate', visible: true, order: 22 },
        { key: 'opening-value', label: 'Opening (group) - Value', visible: true, order: 23 },
        { key: 'barcode-group', label: 'Barcode (group)', visible: true, order: 24 },
        { key: 'barcode-digits', label: 'Barcode (group) - No of Digits', visible: true, order: 25 },
        { key: 'barcode-prefix', label: 'Barcode (group) - Barcode Prefix', visible: true, order: 26 },
        { key: 'barcode', label: 'Barcode', visible: true, order: 27 },
        { key: 'serialized', label: 'Serialized Barcode', visible: true, order: 27 },
        { key: 'styles', label: 'Basic Styles (group)', visible: true, order: 28 },
        { key: 'cut', label: 'Basic Styles (group) - Cut', visible: true, order: 29 },
        { key: 'shape', label: 'Basic Styles (group) - Shape', visible: true, order: 30 },
        { key: 'color', label: 'Basic Styles (group) - Color', visible: true, order: 31 },
        { key: 'clarity', label: 'Basic Styles (group) - Clarity', visible: true, order: 32 },
        { key: 'sieve', label: 'Basic Styles (group) - Sieve Size', visible: true, order: 33 },
        { key: 'size', label: 'Basic Styles (group) - Size', visible: true, order: 34 },
        { key: 'stylecode', label: 'Style Code', visible: true, order: 35 }
    ];

    /** Sub-columns under grouped header row1 cells (data-col must match th) */
    const PC_COL_GROUPS = {
        'purity-group': ['purity-sale', 'purity-purchase'],
        'wastage-group': ['wastage-sale', 'wastage-purchase'],
        'opening': ['opening-weight', 'opening-purity', 'opening-qty', 'opening-finalwt', 'opening-rate', 'opening-value'],
        'barcode-group': ['barcode-digits', 'barcode-prefix', 'barcode'],
        'styles': ['cut', 'shape', 'color', 'clarity', 'sieve', 'size', 'stylecode']
    };

    const PC_SUB_TO_GROUP = {};
    Object.keys(PC_COL_GROUPS).forEach(function (gk) {
        PC_COL_GROUPS[gk].forEach(function (sub) {
            PC_SUB_TO_GROUP[sub] = gk;
        });
    });

    function pcColumnKeysFromHeaderTh($th) {
        const colspan = parseInt($th.attr('colspan'), 10) || 1;
        const rowspan = parseInt($th.attr('rowspan'), 10) || 1;
        const col = $th.data('col');
        if (!col) return [];
        const key = String(col);
        if (rowspan === 2) return [key];
        if (colspan > 1) {
            const g = PC_COL_GROUPS[key];
            return g ? g.slice() : [];
        }
        return [];
    }

    function getPcTableColumnOrderFromDom() {
        const order = [];
        $('#headerRow1').children('th').each(function () {
            const $th = $(this);
            const rowspan = parseInt($th.attr('rowspan'), 10) || 1;
            const colspan = parseInt($th.attr('colspan'), 10) || 1;
            const col = $th.data('col');
            if (!col) return;
            const key = String(col);
            if (rowspan === 2) {
                order.push(key);
            } else if (colspan > 1) {
                const g = PC_COL_GROUPS[key];
                if (g) g.forEach(function (k) { order.push(k); });
            }
        });
        return order;
    }

    function rebuildPcHeaderRow2() {
        const order = getPcTableColumnOrderFromDom();
        const $row2 = $('#headerRow2');
        const map = {};
        $row2.children('th').each(function () {
            const k = $(this).data('col');
            if (k) map[String(k)] = this;
        });
        $row2.empty();
        order.forEach(function (k) {
            if (map[k]) $row2.append(map[k]);
        });
    }

    function applyPcBodyColumnOrder(order) {
        $('.pc-table tbody tr').each(function () {
            const $row = $(this);
            const cellMap = {};
            $row.find('td[data-col]').each(function () {
                cellMap[String($(this).data('col'))] = this;
            });
            order.forEach(function (k) {
                if (cellMap[k]) $row.append(cellMap[k]);
            });
        });
    }

    /** After dragging row2: keep each group's sub-columns contiguous (Sale then Purchase, etc.). */
    function repairRow2GroupPairsAfterSort() {
        const $row2 = $('#headerRow2');
        Object.keys(PC_COL_GROUPS).forEach(function (gk) {
            const keys = PC_COL_GROUPS[gk];
            if (!keys || keys.length < 2) return;
            let $lead = $row2.find('th[data-col="' + keys[0] + '"]');
            if (!$lead.length) return;
            keys.slice(1).forEach(function (k) {
                const $n = $row2.find('th[data-col="' + k + '"]');
                if ($n.length && $n[0] !== $lead[0].nextElementSibling) {
                    $n.insertAfter($lead);
                }
                $lead = $n;
            });
        });
    }

    /** Group keys in left-to-right order from row2 (purity-group, wastage-group, …). */
    function collapsedRow2GroupKeys() {
        const groups = [];
        let lastG = null;
        $('#headerRow2').children('th').each(function () {
            const k = $(this).data('col');
            if (!k) return;
            const g = PC_SUB_TO_GROUP[String(k)];
            if (!g) return;
            if (g !== lastG) {
                groups.push(g);
                lastG = g;
            }
        });
        return groups;
    }

    /** Reorder row1 colspan group headers to match the sub-column order from row2. */
    function syncRow1GroupHeadersFromRow2Collapsed() {
        const collapsed = collapsedRow2GroupKeys();
        if (!collapsed.length) return;
        const $row1 = $('#headerRow1');
        let slots = 0;
        $row1.children('th').each(function () {
            const colspan = parseInt($(this).attr('colspan'), 10) || 1;
            const rowspan = parseInt($(this).attr('rowspan'), 10) || 1;
            const c = $(this).data('col');
            if (rowspan !== 2 && colspan > 1 && PC_COL_GROUPS[String(c)]) slots++;
        });
        if (collapsed.length !== slots) return;
        const groupMap = {};
        $row1.children('th').each(function () {
            const $th = $(this);
            const c = $th.data('col');
            const colspan = parseInt($th.attr('colspan'), 10) || 1;
            const rowspan = parseInt($th.attr('rowspan'), 10) || 1;
            if (rowspan !== 2 && colspan > 1 && PC_COL_GROUPS[String(c)]) {
                groupMap[String(c)] = this;
            }
        });
        let gi = 0;
        const children = $row1.children('th').toArray();
        const newOrder = [];
        children.forEach(function (th) {
            const $th = $(th);
            const c = $th.data('col');
            const colspan = parseInt($th.attr('colspan'), 10) || 1;
            const rowspan = parseInt($th.attr('rowspan'), 10) || 1;
            if (rowspan === 2) {
                newOrder.push(th);
                return;
            }
            if (colspan > 1 && PC_COL_GROUPS[String(c)]) {
                if (gi < collapsed.length) {
                    const gk = collapsed[gi++];
                    const el = groupMap[gk];
                    if (el) newOrder.push(el);
                }
            } else {
                newOrder.push(th);
            }
        });
        newOrder.forEach(function (el) {
            $row1.append(el);
        });
    }

    function onPcHeaderRow2SortEnd() {
        repairRow2GroupPairsAfterSort();
        syncRow1GroupHeadersFromRow2Collapsed();
        updateColumnOrder(false);
    }

    /** Measure first thead row height so row2 sticky `top` matches (group row + sub-header row). */
    function syncPcStickyTheadOffset() {
        const scroll = document.querySelector('.pc-scroll');
        const thead = document.getElementById('pcTableHead');
        const row2 = document.getElementById('headerRow2');
        if (!scroll || !thead || !row2) return;
        let h = row2.offsetTop - thead.offsetTop;
        if (h < 28) {
            h = 42;
        }
        scroll.style.setProperty('--pc-thead-h1', h + 'px');
    }

    const pcDefaultColumnOrder = getPcTableColumnOrderFromDom();

    function mergePcColumnOrder(savedFlat, fallbackFlat) {
        const seen = new Set();
        const out = [];
        (savedFlat || []).forEach(function (k) {
            if (k && !seen.has(k)) { seen.add(k); out.push(k); }
        });
        (fallbackFlat || []).forEach(function (k) {
            if (k && !seen.has(k)) { seen.add(k); out.push(k); }
        });
        return out;
    }

    /** Reorder row1 <th> including colspan group headers, then sync row2 + tbody. skipSave avoids re-POST on load. */
    function applyColumnOrderFromFlatKeys(flatOrder, skipSave) {
        const headerRow = $('#headerRow1');
        const merged = mergePcColumnOrder(flatOrder, pcDefaultColumnOrder);
        const ths = headerRow.children('th').get();
        function rank(th) {
            const keys = pcColumnKeysFromHeaderTh($(th));
            let minIdx = Infinity;
            keys.forEach(function (k) {
                const i = merged.indexOf(k);
                if (i !== -1 && i < minIdx) minIdx = i;
            });
            return minIdx === Infinity ? 999999 : minIdx;
        }
        const wrapped = ths.map(function (th, idx) { return { th: th, idx: idx }; });
        wrapped.sort(function (a, b) {
            const dr = rank(a.th) - rank(b.th);
            if (dr !== 0) return dr;
            return a.idx - b.idx;
        });
        wrapped.forEach(function (x) { headerRow.append(x.th); });
        updateColumnOrder(!!skipSave);
    }

    // Initialize column visibility dropdown
    function initColumnDropdown() {
        const columnsList = $('#columnsList');
        columnsList.empty();
        
        columnDefinitions.forEach(col => {
            if (col.key !== 'check' && col.key !== 'opening' && !col.key.startsWith('opening-') && col.key !== 'barcode-group' && col.key !== 'styles' && col.key !== 'purity-group' && col.key !== 'wastage-group') {
                const item = $(`
                    <div class="columns-dropdown-item">
                        <input type="checkbox" id="col_${col.key}" data-col="${col.key}" ${col.visible ? 'checked' : ''}>
                        <label for="col_${col.key}">${col.label}</label>
                    </div>
                `);
                columnsList.append(item);
            }
        });

        // Column search
        $('#columnSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.columns-dropdown-item').each(function() {
                const label = $(this).find('label').text().toLowerCase();
                $(this).toggle(label.includes(searchTerm));
            });
        });

        // Toggle column visibility
        $('.columns-dropdown-item input[type="checkbox"]').on('change', function() {
            const colKey = $(this).data('col');
            const isVisible = $(this).is(':checked');
            toggleColumnVisibility(colKey, isVisible);
            updateColumnState(colKey, isVisible);
        });
    }

    // Toggle column visibility
    function toggleColumnVisibility(colKey, isVisible) {
        const table = $('.pc-table');
        const headerCells = table.find(`thead th[data-col="${colKey}"]`);
        const bodyCells = table.find(`tbody td[data-col="${colKey}"]`);
        
        if (isVisible) {
            headerCells.show();
            bodyCells.show();
        } else {
            headerCells.hide();
            bodyCells.hide();
        }
        
        // Update checkbox in dropdown
        $(`#col_${colKey}`).prop('checked', isVisible);
    }

    // Update column state
    function updateColumnState(colKey, isVisible) {
        const col = columnDefinitions.find(c => c.key === colKey);
        if (col) {
            col.visible = isVisible;
        }
        // Save to database
        //saveColumnPreferencesToDatabase();
    }

    // Save column preferences to database
    function saveColumnPreferencesToDatabase() {
        const userId = <?= isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : 0 ?>;
        if (userId <= 0) {
            // Fallback to localStorage if no user session
            localStorage.setItem('productColumns', JSON.stringify(columnDefinitions));
            return;
        }

        $.ajax({
            url: 'ajax/save-column-preferences.php',
            type: 'POST',
            data: {
                column_definitions: JSON.stringify(columnDefinitions)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    console.log('Column preferences saved to database');
                }
            },
            error: function() {
                // Fallback to localStorage on error
                localStorage.setItem('productColumns', JSON.stringify(columnDefinitions));
            }
        });
    }

    // Load saved column state from database
    function loadColumnState() {
        const userId = <?= isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : 0 ?>;
        
        if (userId > 0) {
            // Load from database
            $.ajax({
                url: 'ajax/get-column-preferences.php',
                type: 'POST',
                data: { page_name: 'product-opening' },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.preferences && response.preferences.length) {
                        response.preferences.forEach(pref => {
                            const col = columnDefinitions.find(c => c.key === pref.column_key);
                            if (col) {
                                col.visible = pref.is_visible == 1;
                                col.order = pref.column_order;
                                toggleColumnVisibility(col.key, col.visible);
                            }
                        });
                        const sortedPrefs = response.preferences.slice().sort((a, b) => a.column_order - b.column_order);
                        const orderedKeys = sortedPrefs.map(p => p.column_key);
                        applyColumnOrderFromFlatKeys(orderedKeys, true);
                    } else {
                        loadFromLocalStorage();
                    }
                },
                error: function() {
                    // Fallback to localStorage on error
                    loadFromLocalStorage();
                }
            });
        } else {
            // No user session, use localStorage
            loadFromLocalStorage();
        }
    }

    // Load from localStorage (fallback)
    function loadFromLocalStorage() {
        const saved = localStorage.getItem('productColumns');
        if (saved) {
            const savedCols = JSON.parse(saved);
            savedCols.forEach(savedCol => {
                const col = columnDefinitions.find(c => c.key === savedCol.key);
                if (col) {
                    col.visible = savedCol.visible;
                    col.order = savedCol.order;
                    toggleColumnVisibility(col.key, col.visible);
                }
            });
        }
        const flatSaved = localStorage.getItem('productOpeningPcFlatColumnOrder');
        if (flatSaved) {
            try {
                const keys = JSON.parse(flatSaved);
                if (Array.isArray(keys) && keys.length) {
                    applyColumnOrderFromFlatKeys(keys, true);
                }
            } catch (e) { /* ignore */ }
        }
    }

    // Toggle dropdown
    $('#columnSettingsBtn').on('click', function(e) {
        e.stopPropagation();
        $('#columnsDropdown').toggleClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.pc-table-header-actions').length) {
            $('#columnsDropdown').removeClass('show');
        }
    });

    // Initialize column dropdown
    initColumnDropdown();

    function getMainPcRowMetalName($row) {
        if (!$row || !$row.length) return '';
        const $h = $row.find('td[data-col="metal"] input[type="hidden"]');
        if ($h.length) return String($h.val() || '').trim();
        return String($row.find('td[data-col="metal"]').text() || '').trim();
    }

    function reorderOpeningStockModalRowsToMatchMain() {
        const $modalBody = $('#openingStockModal tbody');
        if (!$modalBody.length) return;
        const frag = document.createDocumentFragment();
        $('.pc-table tbody tr').each(function () {
            const metal = getMainPcRowMetalName($(this));
            if (!metal) return;
            const $m = $modalBody.find('tr').filter(function () {
                return $(this).attr('data-metal-name') === metal;
            }).first();
            if ($m.length) frag.appendChild($m[0]);
        });
        $modalBody[0].appendChild(frag);
    }

    // Sync body column order to header (row2 + tbody) from current header row1
    function resetToCorrectColumnOrder() {
        rebuildPcHeaderRow2();
        applyPcBodyColumnOrder(getPcTableColumnOrderFromDom());
        syncPcStickyTheadOffset();
    }
    
    // Reset column order on page load - run before loadColumnState
    setTimeout(function() {
        resetToCorrectColumnOrder();
    }, 50);
    
    loadColumnState();
    
    // Also reset after column state is loaded (in case it reordered incorrectly)
    setTimeout(function() {
        resetToCorrectColumnOrder();
    }, 500);

    $(window).on('resize', syncPcStickyTheadOffset);
    setTimeout(syncPcStickyTheadOffset, 80);

    function initPcColumnDragHandles() {
        $('#headerRow1 th.pc-col-drag').each(function () {
            const $th = $(this);
            if ($th.find('.pc-col-drag-handle-main').length) return;
            $th.prepend('<span class="pc-col-drag-handle pc-col-drag-handle-main" title="Drag to reorder column"><i class="feather icon-move"></i></span>');
        });
        $('#headerRow2 th').each(function () {
            const $th = $(this);
            const col = $th.data('col');
            if (!col || !PC_SUB_TO_GROUP[String(col)]) return;
            if ($th.find('.pc-col-drag-handle-sub').length) return;
            $th.prepend('<span class="pc-col-drag-handle pc-col-drag-handle-sub" title="Drag to move this column group (Sale + Purchase stay together)"><i class="feather icon-move"></i></span>');
        });
        if (window.feather && typeof window.feather.replace === 'function') {
            try { window.feather.replace(); } catch (err) { /* ignore */ }
        }
    }

    initPcColumnDragHandles();

    const headerRow1 = document.getElementById('headerRow1');

    if (headerRow1 && typeof Sortable !== 'undefined') {
        new Sortable(headerRow1, {
            animation: 150,
            draggable: 'th.pc-col-drag',
            handle: '.pc-col-drag-handle-main',
            filter: '.pc-col-no-drag',
            preventOnFilter: true,
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 5,
            onStart(evt) {
                evt.item.classList.add('dragging');
                const keys = pcColumnKeysFromHeaderTh($(evt.item));
                keys.forEach(function (k) {
                    $('.pc-table tbody td[data-col="' + k + '"]').addClass('dragging-cell');
                });
            },
            onEnd(evt) {
                evt.item.classList.remove('dragging');
                $('.pc-table tbody td').removeClass('dragging-cell');
                updateColumnOrder(false);
            }
        });
    }

    const headerRow2 = document.getElementById('headerRow2');
    if (headerRow2 && typeof Sortable !== 'undefined') {
        new Sortable(headerRow2, {
            animation: 150,
            draggable: 'th',
            handle: '.pc-col-drag-handle-sub',
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 5,
            onStart(evt) {
                evt.item.classList.add('dragging');
                const k = $(evt.item).data('col');
                const gk = k ? PC_SUB_TO_GROUP[String(k)] : null;
                const keys = (gk && PC_COL_GROUPS[gk]) ? PC_COL_GROUPS[gk].slice() : (k ? [String(k)] : []);
                keys.forEach(function (colKey) {
                    $('.pc-table tbody td[data-col="' + colKey + '"]').addClass('dragging-cell');
                });
            },
            onEnd(evt) {
                evt.item.classList.remove('dragging');
                $('.pc-table tbody td').removeClass('dragging-cell');
                onPcHeaderRow2SortEnd();
            }
        });
    }

    const pcTbodyEl = document.querySelector('.pc-table tbody');
    if (pcTbodyEl && typeof Sortable !== 'undefined') {
        new Sortable(pcTbodyEl, {
            animation: 150,
            handle: '.pc-row-drag-handle',
            ghostClass: 'pc-row-sortable-ghost',
            onEnd: function () {
                reorderOpeningStockModalRowsToMatchMain();
            }
        });
    }

    function updateColumnOrder(skipSave) {
        rebuildPcHeaderRow2();
        const order = getPcTableColumnOrderFromDom();
        applyPcBodyColumnOrder(order);
        order.forEach(function (colKey, index) {
            const col = columnDefinitions.find(function (c) { return c.key === colKey; });
            if (col) col.order = index;
        });
        syncPcStickyTheadOffset();
        if (!skipSave) {
            saveColumnOrderToDatabase(order);
        }
    }

    // Save column order to database (and always cache flat order in localStorage for reload)
    function saveColumnOrderToDatabase(columnOrder) {
        localStorage.setItem('productOpeningPcFlatColumnOrder', JSON.stringify(columnOrder));
        localStorage.setItem('productColumns', JSON.stringify(columnDefinitions));

        const userId = <?= isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : 0 ?>;
        if (userId <= 0) {
            return;
        }

        $.ajax({
            url: 'ajax/save-column-preferences.php',
            type: 'POST',
            data: {
                column_order: JSON.stringify(columnOrder),
                column_definitions: JSON.stringify(columnDefinitions)
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (typeof showNotification !== 'undefined') {
                        showNotification('Column order saved successfully', 'success');
                    }
                } else {
                    if (typeof showNotification !== 'undefined') {
                        showNotification('Failed to save column order: ' + (response.message || 'Unknown'), 'error');
                    }
                }
            },
            error: function(xhr, status, error) {
                if (typeof showNotification !== 'undefined') {
                    showNotification('Error saving column order. Check console for details.', 'error');
                }
            }
        });
    }

    // Allow page scroll (layout-content has overflow-y: auto in CSS)
    $('html, body').css({
        'overflow-x': 'hidden',
        'overflow-y': 'auto',
        'min-height': '100vh'
    });
    $('.layout-content').css({
        'overflow-y': 'auto',
        'min-height': 'calc(100vh - 60px)'
    });

    // ================== PRODUCT LIST MANAGEMENT ==================
    
    // Product selection - click to load/edit
    $(document).on('click', '.product-name', function() {
        const productId = $(this).closest('.product-row').data('product-id');
        if (productId) {
            window.location.href = 'product-opening.php?id=' + productId + '<?= $search_term ? "&search=".urlencode($search_term) : "" ?><?= $page > 1 ? "&page=".$page : "" ?>';
        }
    });

    // Search functionality
    let searchTimeout;
    $('#productSearch').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            const url = new URL(window.location.href);
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            url.searchParams.delete('page'); // Reset to page 1 on new search
            window.location.href = url.toString();
        }, 500); // Wait 500ms after user stops typing
    });

    // Pagination
    $('.page-btn').on('click', function() {
        if ($(this).prop('disabled')) return;
        const page = $(this).data('page');
        if (page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    });

    // Per page selection
    $('#perPageSelect').on('change', function() {
        const perPage = $(this).val();
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.delete('page'); // Reset to page 1
        window.location.href = url.toString();
    });

    // Delete product (main branch / full delete)
    $(document).on('click', '.delete-product', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        
        if (confirm('Are you sure you want to delete "' + productName + '"? This action cannot be undone.')) {
            $.ajax({
                url: 'ajax/delete-product.php',
                type: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true || response.status === 'success') {
                        showMessage('success', response.message || 'Product deleted successfully');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showMessage('error', response.message || 'Failed to delete product');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while deleting the product');
                }
            });
        }
    });

    // Sub-branch: remove allocation only (does not delete the product master)
    $(document).on('click', '.unassign-product-from-branch', function(e) {
        e.stopPropagation();
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name') || '';
        if (confirm('Remove "' + productName + '" from this branch?\n\nThe product will stay on the main branch and can be added again from "Active products for this branch".')) {
            $.ajax({
                url: 'ajax/unassign-product-from-branch.php',
                type: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === true || response.status === 'success') {
                        showMessage('success', response.message || 'Removed from this branch.');
                        setTimeout(function() {
                            window.location.reload();
                        }, 800);
                    } else {
                        showMessage('error', response.message || 'Could not remove product from branch');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while updating branch assignment');
                }
            });
        }
    });

    // ================== SUB-BRANCH: MAIN CATALOG ACTIVATION MODAL ==================
    if (window.auragoldSubBranchMode) {
        var subbranchCatalogData = [];

        function escSubHtml(s) {
            return $('<div/>').text(s == null ? '' : String(s)).html();
        }

        function renderSubbranchCatalogTable(rows) {
            var $tb = $('#subbranchCatalogTableBody');
            $tb.empty();
            rows.forEach(function (r) {
                var masterLabel = (parseInt(r.status, 10) === 1)
                    ? '<span class="badge badge-info">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
                var searchBlob = ((r.name || '') + ' ' + (r.article || '')).toLowerCase();
                var checked = r.active_here ? ' checked' : '';
                var $tr = $('<tr class="subbranch-cat-row"/>').attr('data-search', searchBlob);
                $tr.append(
                    $('<td class="text-center"/>').html('<input type="checkbox" class="subbranch-cat-chk"' + checked + ' data-product-id="' + parseInt(r.id, 10) + '">'),
                    $('<td/>').html('<div>' + escSubHtml(r.name) + '</div>' + (r.article ? '<div class="text-muted small">' + escSubHtml(r.article) + '</div>' : '')),
                    $('<td/>').html(masterLabel)
                );
                $tb.append($tr);
            });
            applySubbranchCatalogFilter();
        }

        function applySubbranchCatalogFilter() {
            var q = ($('#subbranchCatalogFilter').val() || '').toLowerCase().trim();
            $('.subbranch-cat-row').each(function () {
                var blob = ($(this).attr('data-search') || '');
                var ok = !q || blob.indexOf(q) !== -1;
                $(this).toggle(ok);
            });
        }

        $('#subbranchMainCatalogModal').on('shown.bs.modal', function () {
            $('#subbranchCatalogError').hide().text('');
            $('#subbranchCatalogLoading').show();
            $('#subbranchCatalogTableBody').empty();
            $.getJSON('ajax/subbranch-main-catalog-products.php?_=' + Date.now())
                .done(function (res) {
                    $('#subbranchCatalogLoading').hide();
                    if (res.status === 'success' && res.products) {
                        subbranchCatalogData = res.products;
                        renderSubbranchCatalogTable(subbranchCatalogData);
                    } else {
                        $('#subbranchCatalogError').show().text(res.message || 'Could not load products.');
                    }
                })
                .fail(function (xhr) {
                    $('#subbranchCatalogLoading').hide();
                    var msg = 'Request failed.';
                    try {
                        var r = JSON.parse(xhr.responseText || '{}');
                        if (r.message) {
                            msg = r.message;
                        }
                    } catch (e) {}
                    $('#subbranchCatalogError').show().text(msg);
                });
        });

        $('#subbranchCatalogFilter').on('keyup input', applySubbranchCatalogFilter);

        $('#subbranchCatalogCheckAll').on('change', function () {
            var on = $(this).is(':checked');
            $('.subbranch-cat-row:visible .subbranch-cat-chk').prop('checked', on);
        });

        $('#subbranchCatalogSaveBtn').on('click', function () {
            var ids = [];
            $('.subbranch-cat-chk:checked').each(function () {
                ids.push(parseInt($(this).data('product-id'), 10));
            });
            $('#ajaxLoader').show();
            $.ajax({
                url: 'ajax/save-subbranch-product-activation.php',
                type: 'POST',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({ active_product_ids: ids }),
                dataType: 'json',
                success: function (res) {
                    $('#ajaxLoader').hide();
                    if (res.status === 'success') {
                        showMessage('success', res.message || 'Saved.');
                        $('#subbranchMainCatalogModal').modal('hide');
                        window.location.reload();
                    } else {
                        showMessage('error', res.message || 'Save failed.');
                    }
                },
                error: function (xhr) {
                    $('#ajaxLoader').hide();
                    var msg = 'Save failed.';
                    try {
                        var r = JSON.parse(xhr.responseText || '{}');
                        if (r.message) {
                            msg = r.message;
                        }
                    } catch (e) {}
                    showMessage('error', msg);
                }
            });
        });
    }

    // ================== CALCULATION LOGIC ==================
    
    // Define calculation function - must be accessible to event handlers
    window.calculateRowValues = function($row) {
        if (!$row || $row.length === 0) return;
        
        // Get values from the row using data-col attribute
        const weightInput = $row.find('td[data-col="opening-weight"] input');
        const purityInput = $row.find('td[data-col="opening-purity"] input');
        const qtyInput = $row.find('td[data-col="opening-qty"] input');
        const rateInput = $row.find('td[data-col="opening-rate"] input');
        const finalWeightInput = $row.find('td[data-col="opening-finalwt"] input');
        const valueInput = $row.find('td[data-col="opening-value"] input');
        
        // Get values - allow empty/zero values, default to 0 if not found
        const weight = weightInput.length > 0 ? (parseFloat(weightInput.val()) || 0) : 0;
        const purity = purityInput.length > 0 ? (parseFloat(purityInput.val()) || 0) : 0;
        const qty = qtyInput.length > 0 ? (parseFloat(qtyInput.val()) || 0) : 0;
        const rate = rateInput.length > 0 ? (parseFloat(rateInput.val()) || 0) : 0;

        // Calculate Final Weight = Weight * Purity
        const finalWeight = weight * purity;
        
        // Update Final Weight field - always update if field exists
        if (finalWeightInput.length > 0) {
            finalWeightInput.val(finalWeight.toFixed(3));
        }

        // Calculate Value = Rate * Final Weight
        // Example: 1100 * 3300 = 3630000
        const value = rate * finalWeight;
        
        // Update Value field - always update if field exists
        if (valueInput.length > 0) {
            valueInput.val(value.toFixed(2));
        }
    };

    // ----- Opening Stock modal (edit product): sync with main characteristics grid -----
    var DIAMOND_STONES_METAL = 'Diamond & Stones';
    var DIAMOND_STONES_PURITY_WEIGHT_FACTOR = 5;

    function formatLinkedWeightPurity(n) {
        if (n === '' || n === null || n === undefined || !isFinite(Number(n))) return '';
        var v = Number(n);
        var s = v.toFixed(6).replace(/\.?0+$/, '');
        if (s === '-0') s = '0';
        return s;
    }

    function getPcRowMetalName($row) {
        return getMainPcRowMetalName($row);
    }

    function calculateModalOpeningRow($mtr) {
        if (!$mtr || !$mtr.length) return;
        const weight = parseFloat($mtr.find('.os-opening-weight').val()) || 0;
        const purity = parseFloat($mtr.find('.os-opening-purity').val()) || 0;
        const rate = parseFloat($mtr.find('.os-opening-rate').val()) || 0;
        const finalWeight = weight * purity;
        const value = rate * finalWeight;
        $mtr.find('.os-final-weight').val(finalWeight.toFixed(3));
        $mtr.find('.os-opening-value').val(value.toFixed(2));
    }

    function syncOpeningModalFromMain() {
        reorderOpeningStockModalRowsToMatchMain();
        $('.pc-table tbody tr').each(function () {
            const $main = $(this);
            const metal = getPcRowMetalName($main);
            const $m = $('#openingStockModal tbody tr').filter(function () {
                return $(this).attr('data-metal-name') === metal;
            }).first();
            if (!$m.length) return;
            $m.find('.os-is-selected').prop('checked', $main.find('td[data-col="check"] input[type="checkbox"]').is(':checked'));
            $m.find('.os-opening-weight').val($main.find('td[data-col="opening-weight"] input').val());
            $m.find('.os-opening-purity').val($main.find('td[data-col="opening-purity"] input').val());
            $m.find('.os-opening-qty').val($main.find('td[data-col="opening-qty"] input').val());
            $m.find('.os-opening-rate').val($main.find('td[data-col="opening-rate"] input').val());
            calculateModalOpeningRow($m);
        });
    }

    function syncOpeningModalToMain() {
        $('#openingStockModal tbody tr').each(function () {
            const $m = $(this);
            const metal = $m.attr('data-metal-name') || '';
            const $main = $('.pc-table tbody tr').filter(function () {
                return getPcRowMetalName($(this)) === metal;
            }).first();
            if (!$main.length) return;
            $main.find('td[data-col="check"] input[type="checkbox"]').prop('checked', $m.find('.os-is-selected').is(':checked'));
            $main.find('td[data-col="opening-weight"] input').val($m.find('.os-opening-weight').val());
            $main.find('td[data-col="opening-purity"] input').val($m.find('.os-opening-purity').val());
            $main.find('td[data-col="opening-qty"] input').val($m.find('.os-opening-qty').val());
            $main.find('td[data-col="opening-rate"] input').val($m.find('.os-opening-rate').val());
            if (typeof window.calculateRowValues === 'function') {
                window.calculateRowValues($main);
            }
        });
        $('#openingStockModal').modal('hide');
    }

    $('#openingStockModal').on('shown.bs.modal', function() {
        syncOpeningModalFromMain();
    });

    // Diamond & Stones: purity ↔ weight (weight = purity/5, purity = weight×5)
    $(document).on('input keyup change blur paste', '#openingStockModal .os-opening-purity', function() {
        var $mtr = $(this).closest('tr');
        if ($mtr.attr('data-metal-name') === DIAMOND_STONES_METAL) {
            var p = parseFloat($(this).val()) || 0;
            $mtr.find('.os-opening-weight').val(formatLinkedWeightPurity(p / DIAMOND_STONES_PURITY_WEIGHT_FACTOR));
        }
        calculateModalOpeningRow($mtr);
    });

    $(document).on('input keyup change blur paste', '#openingStockModal .os-opening-weight', function() {
        var $mtr = $(this).closest('tr');
        if ($mtr.attr('data-metal-name') === DIAMOND_STONES_METAL) {
            var w = parseFloat($(this).val()) || 0;
            $mtr.find('.os-opening-purity').val(formatLinkedWeightPurity(w * DIAMOND_STONES_PURITY_WEIGHT_FACTOR));
        }
        calculateModalOpeningRow($mtr);
    });

    $(document).on('input keyup change blur paste', '#openingStockModal .os-opening-qty, #openingStockModal .os-opening-rate', function() {
        calculateModalOpeningRow($(this).closest('tr'));
    });

    $('#openingStockModalSave').on('click', function() {
        syncOpeningModalToMain();
    });

    // Calculate Final Weight, Rate, and Value when Weight, Qty, or Rate changes
    $(document).on('input keyup change blur paste', '.pc-table tbody td[data-col="opening-purity"] input', function() {
        const $row = $(this).closest('tr');
        if ($row.length && getPcRowMetalName($row) === DIAMOND_STONES_METAL) {
            const p = parseFloat($(this).val()) || 0;
            $row.find('td[data-col="opening-weight"] input').val(formatLinkedWeightPurity(p / DIAMOND_STONES_PURITY_WEIGHT_FACTOR));
        }
        if ($row.length > 0 && typeof window.calculateRowValues === 'function') {
            window.calculateRowValues($row);
        }
    });

    $(document).on('input keyup change blur paste', '.pc-table tbody td[data-col="opening-weight"] input', function() {
        const $row = $(this).closest('tr');
        if ($row.length && getPcRowMetalName($row) === DIAMOND_STONES_METAL) {
            const w = parseFloat($(this).val()) || 0;
            $row.find('td[data-col="opening-purity"] input').val(formatLinkedWeightPurity(w * DIAMOND_STONES_PURITY_WEIGHT_FACTOR));
        }
        if ($row.length > 0 && typeof window.calculateRowValues === 'function') {
            window.calculateRowValues($row);
        }
    });

    $(document).on('input keyup change blur paste', '.pc-table tbody td[data-col="opening-qty"] input, .pc-table tbody td[data-col="opening-rate"] input', function() {
        const $row = $(this).closest('tr');
        if ($row.length > 0 && typeof window.calculateRowValues === 'function') {
            window.calculateRowValues($row);
        }
    });

    // Fallback for browsers / dynamic rows (name-based)
    $(document).on('input keyup change blur paste', '.pc-table tbody input[name*="[opening_qty]"]', function() {
        const $row = $(this).closest('tr');
        if ($row.length > 0 && typeof window.calculateRowValues === 'function') {
            window.calculateRowValues($row);
        }
    });

    // Calculate all rows on page load (for edit mode) - ensure DOM is ready
    function initializeCalculations() {
        if (typeof window.calculateRowValues !== 'function') {
            return;
        }
        
        const rows = $('.pc-table tbody tr');
        if (rows.length === 0) {
            return;
        }
        
        rows.each(function() {
            window.calculateRowValues($(this));
        });
    }
    
    // Run calculations after DOM is ready - multiple attempts to ensure it works
    setTimeout(initializeCalculations, 500);
    setTimeout(initializeCalculations, 1000);
    setTimeout(initializeCalculations, 2000);

    // ================== BRANCH TAG MANAGEMENT ==================
    
    // Remove branch tag
    $(document).on('click', '.remove-tag', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $tag = $(this).closest('.branch-tag');
        const branchId = $tag.data('branch-id');
        
        // Remove hidden input
        $(`input[name="branch_ids[]"][value="${branchId}"]`).remove();
        
        // Remove tag
        $tag.remove();
    });

    // Add branch - show dropdown of available branches
    $('.add-branch-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Get already selected branch IDs
        const selectedBranchIds = [];
        $('.branch-tag').each(function() {
            const id = $(this).data('branch-id');
            if (id) selectedBranchIds.push(id);
        });
        
        // Fetch available branches via AJAX
        $.ajax({
            url: 'ajax/get-branches.php',
            type: 'POST',
            data: { exclude_ids: selectedBranchIds },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.branches.length > 0) {
                    showBranchSelectionModal(response.branches);
                } else {
                    showMessage('info', 'No more branches available to add');
                }
            },
            error: function() {
                // Fallback: show all branches from page load
                showBranchSelectionModalFromPage();
            }
        });
    });
    
    // Show branch selection modal
    function showBranchSelectionModal(branches) {
        let optionsHtml = '';
        branches.forEach(function(branch) {
            optionsHtml += `<div class="branch-option" data-branch-id="${branch.id}" data-branch-name="${branch.name}">
                ${branch.name}
            </div>`;
        });
        
        const modalHtml = `
            <div id="branchSelectionModal" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div style="
                    background: white;
                    border-radius: 8px;
                    padding: 20px;
                    max-width: 400px;
                    width: 90%;
                    max-height: 400px;
                    overflow-y: auto;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h5 style="margin: 0;">Select Branch</h5>
                        <button id="closeBranchModal" style="background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                    </div>
                    <div id="branchOptionsList" style="
                        display: flex;
                        flex-direction: column;
                        gap: 8px;
                    ">
                        ${optionsHtml || '<div class="text-muted" style="text-align: center; padding: 20px;">No branches available</div>'}
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Close modal
        $('#closeBranchModal, #branchSelectionModal').on('click', function(e) {
            if (e.target === this) {
                $('#branchSelectionModal').remove();
            }
        });
        
        // Select branch
        $('.branch-option').on('click', function() {
            const branchId = $(this).data('branch-id');
            const branchName = $(this).data('branch-name');
            
            // Check if already added
            if ($(`.branch-tag[data-branch-id="${branchId}"]`).length > 0) {
                showMessage('info', 'This branch is already selected');
                return;
            }
            
            // Add branch tag
            const tagHtml = `<span class="branch-tag" data-branch-id="${branchId}">${branchName} <span class="remove-tag">×</span></span>`;
            const inputHtml = `<input type="hidden" name="branch_ids[]" value="${branchId}">`;
            
            $('.add-branch-btn').before(tagHtml);
            $('.branch-tags').append(inputHtml);
            
            $('#branchSelectionModal').remove();
            showMessage('success', 'Branch added successfully');
        });
    }
    
    // Fallback: show branches from page (if AJAX fails)
    function showBranchSelectionModalFromPage() {
        // Get already selected branch IDs
        const selectedBranchIds = [];
        $('.branch-tag').each(function() {
            const id = $(this).data('branch-id');
            if (id) selectedBranchIds.push(parseInt(id));
        });
        
        // Filter out already selected branches
        const availableBranches = window.availableBranches || [];
        const filteredBranches = availableBranches.filter(function(branch) {
            return selectedBranchIds.indexOf(parseInt(branch.id)) === -1;
        });
        
        if (filteredBranches.length > 0) {
            showBranchSelectionModal(filteredBranches);
        } else {
            showMessage('info', 'No more branches available to add');
        }
    }

    // ================== FORM SUBMISSION ==================
    
    // Handle form submission
    $('#productSaveForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate Name field
        const nameField = $('input[name="name"]');
        if (!nameField.val() || nameField.val().trim() === '') {
            showMessage('error', 'Name field is required');
            nameField.focus();
            return false;
        }
        
        // Validate Branch field - at least one branch must be selected (auto-use login branch if empty)
        let branchTags = $('.branch-tag');
        if (branchTags.length === 0 && window.auragoldDefaultBranchId > 0 && $('#branchTagsContainer').length) {
            const bn = window.auragoldDefaultBranchName || 'Branch';
            const bid = window.auragoldDefaultBranchId;
            $('#branchTagsContainer').append(
                '<span class="branch-tag" data-branch-id="' + bid + '">' + $('<div/>').text(bn).html() + ' <span class="remove-tag">×</span></span>'
            );
            $('#branchTagsContainer').append('<input type="hidden" name="branch_ids[]" value="' + bid + '">');
            branchTags = $('.branch-tag');
        }
        if (branchTags.length === 0) {
            showMessage('error', 'At least one Branch must be selected');
            $('.branch-tags').focus();
            return false;
        }
        
        // Update branch_ids from visible tags
        $('input[name="branch_ids[]"]').remove();
        $('.branch-tag').each(function() {
            const branchId = $(this).data('branch-id');
            if (branchId) {
                $('<input>').attr({
                    type: 'hidden',
                    name: 'branch_ids[]',
                    value: branchId
                }).appendTo($(this).closest('.select-with-add'));
            }
        });
        
        // Show loader
        $('#ajaxLoader').show();
        
        // Get form data
        const formData = $(this).serialize();
        
        // Submit via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#ajaxLoader').hide();
                
                if (response.status === 'success') {
                    let okMsg = response.message || 'Product saved successfully!';
                    if (response.sync_warnings && response.sync_warnings.length) {
                        okMsg += ' Note: ' + response.sync_warnings.join(' ');
                    }
                    showMessage('success', okMsg);
                    
                    // Return to new-product screen after save (create another product)
                    setTimeout(function() {
                        window.location.href = 'product-opening.php';
                    }, 1500);
                } else {
                    showMessage('error', response.message || 'An error occurred');
                }
            },
            error: function(xhr, status, error) {
                $('#ajaxLoader').hide();
                let errorMsg = 'An error occurred while saving the product.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                    errorMsg = xhr.responseText || error;
                }
                
                showMessage('error', errorMsg);
            }
        });
    });

    // Handle save button clicks
    $('.save-btn-top, .save-product-btn').on('click', function(e) {
        e.preventDefault();
        $('#productSaveForm').submit();
    });

    // ================== MESSAGE DISPLAY ==================
    
    function showMessage(type, message) {
        // Remove existing messages
        $('.form-message').remove();
        
        const bgColor = type === 'success' ? '#10b981' : '#ef4444';
        const icon = type === 'success' ? 'check-circle' : 'alert-circle';
        
        const messageHtml = `
            <div class="form-message" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                display: flex;
                align-items: center;
                gap: 12px;
                min-width: 300px;
                animation: slideIn 0.3s ease;
            ">
                <i class="feather icon-${icon}" style="font-size: 20px;"></i>
                <span>${message}</span>
            </div>
        `;
        
        $('body').append(messageHtml);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $('.form-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Add CSS animation
    if (!$('#messageStyles').length) {
        $('<style id="messageStyles">')
            .text(`
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            `)
            .appendTo('head');
    }
    
    // Calculate all rows on page load (for edit mode) - use the initializeCalculations function
    setTimeout(initializeCalculations, 200);
});
</script>

</body>

</html>


