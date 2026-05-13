<?php
session_start();
require_once 'config.php';

// Filters
$search = isset($_GET['search']) ? esc($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? esc($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? esc($_GET['to_date']) : '';
$order_no = isset($_GET['order_no']) ? esc($_GET['order_no']) : '';
$customer = isset($_GET['customer']) ? esc($_GET['customer']) : '';
$status_filter = isset($_GET['status']) ? esc($_GET['status']) : '';
$customer_names_raw = isset($_GET['customer_names']) ? trim((string)$_GET['customer_names']) : '';
$status_list_raw = isset($_GET['status_list']) ? trim((string)$_GET['status_list']) : '';
$department_ids_raw = isset($_GET['department_ids']) ? trim((string)$_GET['department_ids']) : '';
$product_ids_raw = isset($_GET['product_ids']) ? trim((string)$_GET['product_ids']) : '';
$source_list_raw = isset($_GET['source_list']) ? trim((string)$_GET['source_list']) : '';

$selected_customer_names = array_values(array_filter(array_map('trim', explode(',', $customer_names_raw))));
$selected_status_list = array_values(array_filter(array_map('trim', explode(',', $status_list_raw))));
$selected_department_ids = array_values(array_filter(array_map('intval', explode(',', $department_ids_raw))));
$selected_product_ids = array_values(array_filter(array_map('intval', explode(',', $product_ids_raw))));
$selected_source_list = array_values(array_filter(array_map('trim', explode(',', $source_list_raw))));

$has_sale_order_department_col = false;
$has_sale_order_source_col = false;
$col_chk_department = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'department_id'");
if ($col_chk_department && mysqli_num_rows($col_chk_department) > 0) $has_sale_order_department_col = true;
if ($col_chk_department) mysqli_free_result($col_chk_department);
$col_chk_source = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'source'");
if ($col_chk_source && mysqli_num_rows($col_chk_source) > 0) $has_sale_order_source_col = true;
if ($col_chk_source) mysqli_free_result($col_chk_source);

$has_so_item_images_col = false;
$col_chk_soimg = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_order_items LIKE 'images'");
if ($col_chk_soimg && mysqli_num_rows($col_chk_soimg) > 0) {
    $has_so_item_images_col = true;
}
if ($col_chk_soimg) {
    mysqli_free_result($col_chk_soimg);
}

$has_repair_order_tables = false;
$tbl_ro_chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_repair_orders'");
$tbl_roi_chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_repair_order_items'");
if ($tbl_ro_chk && mysqli_num_rows($tbl_ro_chk) > 0 && $tbl_roi_chk && mysqli_num_rows($tbl_roi_chk) > 0) {
    $has_repair_order_tables = true;
}
if ($tbl_ro_chk) {
    mysqli_free_result($tbl_ro_chk);
}
if ($tbl_roi_chk) {
    mysqli_free_result($tbl_roi_chk);
}

$has_roi_item_images_col = false;
if ($has_repair_order_tables) {
    $col_chk_roiimg = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_repair_order_items LIKE 'images'");
    if ($col_chk_roiimg && mysqli_num_rows($col_chk_roiimg) > 0) {
        $has_roi_item_images_col = true;
    }
    if ($col_chk_roiimg) {
        mysqli_free_result($col_chk_roiimg);
    }
}

// When department or source filters are used (sale-order columns), omit repair rows — tbl_repair_orders has no matching columns in base schema.
$repair_filters_compatible = $has_repair_order_tables
    && !( !empty($selected_department_ids) && $has_sale_order_department_col )
    && !( !empty($selected_source_list) && $has_sale_order_source_col );

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
$per_page = max(10, min(100, $per_page));
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = "1=1";
if (!empty($from_date)) $where .= " AND so.order_date >= '$from_date'";
if (!empty($to_date)) $where .= " AND so.order_date <= '$to_date'";
if (!empty($order_no)) $where .= " AND so.order_no LIKE '%$order_no%'";
if (!empty($customer)) $where .= " AND so.customer_name LIKE '%$customer%'";
if (!empty($status_filter)) $where .= " AND so.status = '$status_filter'";
if (!empty($selected_customer_names)) {
    $customer_clauses = [];
    foreach ($selected_customer_names as $cust_name) {
        $esc_name = esc($cust_name);
        if ($esc_name !== '') $customer_clauses[] = "so.customer_name = '$esc_name'";
    }
    if (!empty($customer_clauses)) $where .= " AND (" . implode(' OR ', $customer_clauses) . ")";
}
if (!empty($selected_status_list)) {
    $status_clauses = [];
    foreach ($selected_status_list as $sts_name) {
        $esc_sts = esc($sts_name);
        if ($esc_sts !== '') $status_clauses[] = "so.status = '$esc_sts'";
    }
    if (!empty($status_clauses)) $where .= " AND (" . implode(' OR ', $status_clauses) . ")";
}
if (!empty($selected_department_ids) && $has_sale_order_department_col) {
    $dep_ids_sql = implode(',', array_map('intval', $selected_department_ids));
    if ($dep_ids_sql !== '') $where .= " AND IFNULL(so.department_id, 0) IN ($dep_ids_sql)";
}
if (!empty($selected_product_ids)) {
    $prod_ids_sql = implode(',', array_map('intval', $selected_product_ids));
    if ($prod_ids_sql !== '') $where .= " AND IFNULL(soi.product_id, 0) IN ($prod_ids_sql)";
}
if (!empty($selected_source_list) && $has_sale_order_source_col) {
    $source_clauses = [];
    foreach ($selected_source_list as $src_name) {
        $esc_src = esc($src_name);
        if ($esc_src !== '') $source_clauses[] = "IFNULL(so.source, '') = '$esc_src'";
    }
    if (!empty($source_clauses)) $where .= " AND (" . implode(' OR ', $source_clauses) . ")";
}
if (!empty($search)) {
    $where .= " AND (so.order_no LIKE '%$search%' OR so.customer_name LIKE '%$search%' OR soi.product_name LIKE '%$search%' OR soi.design_no LIKE '%$search%' OR soi.barcode LIKE '%$search%')";
}

// Get sale order items with order and product data
$items_query = "
    SELECT 
        soi.id as item_id,
        soi.order_id,
        soi.product_id,
        soi.product_characteristic_id,
        soi.barcode,
        soi.product_name,
        soi.design_no,
        soi.quantity,
        soi.gross_weight,
        soi.final_weight,
        soi.net_weight,
        soi.rate,
        soi.amount,
        soi.status as item_status,
        so.order_no,
        so.customer_name,
        so.order_date,
        so.due_date,
        so.status as order_status,
        so.sales_person,
        p.name as product_name_from_product,
        p.article,
        pc.sku_code as rfid_code
        " . ($has_so_item_images_col ? ", soi.images" : "") . "
    FROM tbl_sale_order_items soi
    INNER JOIN tbl_sale_orders so ON soi.order_id = so.id
    LEFT JOIN tbl_products p ON soi.product_id = p.id
    LEFT JOIN tbl_product_characteristics pc ON soi.product_characteristic_id = pc.id
    WHERE $where
    ORDER BY so.order_date DESC, so.id DESC, soi.id ASC
";

$sale_rows = getList($items_query);

$sale_processed = [];
foreach ($sale_rows as $row) {
    $product_name = !empty($row['product_name']) ? $row['product_name'] : ($row['product_name_from_product'] ?? $row['article'] ?? 'N/A');
    $row['product_name_display'] = $product_name;
    $row['rfid_code'] = $row['rfid_code'] ?? '';
    $row['product_photo'] = '';
    $row['order_kind'] = 'sale';
    if ($has_so_item_images_col && !empty($row['images'])) {
        $dec = @json_decode($row['images'], true);
        if ($dec && !empty($dec['images']) && is_array($dec['images'])) {
            $primary = isset($dec['primary']) ? $dec['primary'] : $dec['images'][0];
            if ($primary !== '' && $primary !== null) {
                $base_admin = (isset($SiteUrl) ? rtrim($SiteUrl, '/') . '/' : '') . 'admin/';
                $row['product_photo'] = $base_admin . ltrim((string)$primary, '/');
            }
        }
    }
    $sale_processed[] = $row;
}

$repair_processed = [];
if ($repair_filters_compatible) {
    $where_repair = "1=1";
    if (!empty($from_date)) {
        $where_repair .= " AND ro.order_date >= '$from_date'";
    }
    if (!empty($to_date)) {
        $where_repair .= " AND ro.order_date <= '$to_date'";
    }
    if (!empty($order_no)) {
        $where_repair .= " AND ro.order_no LIKE '%$order_no%'";
    }
    if (!empty($customer)) {
        $where_repair .= " AND ro.customer_name LIKE '%$customer%'";
    }
    if (!empty($status_filter)) {
        $where_repair .= " AND ro.status = '$status_filter'";
    }
    if (!empty($selected_customer_names)) {
        $customer_clauses_r = [];
        foreach ($selected_customer_names as $cust_name) {
            $esc_name = esc($cust_name);
            if ($esc_name !== '') {
                $customer_clauses_r[] = "ro.customer_name = '$esc_name'";
            }
        }
        if (!empty($customer_clauses_r)) {
            $where_repair .= " AND (" . implode(' OR ', $customer_clauses_r) . ")";
        }
    }
    if (!empty($selected_status_list)) {
        $status_clauses_r = [];
        foreach ($selected_status_list as $sts_name) {
            $esc_sts = esc($sts_name);
            if ($esc_sts !== '') {
                $status_clauses_r[] = "ro.status = '$esc_sts'";
            }
        }
        if (!empty($status_clauses_r)) {
            $where_repair .= " AND (" . implode(' OR ', $status_clauses_r) . ")";
        }
    }
    if (!empty($selected_product_ids)) {
        $prod_ids_sql = implode(',', array_map('intval', $selected_product_ids));
        if ($prod_ids_sql !== '') {
            $where_repair .= " AND IFNULL(roi.product_id, 0) IN ($prod_ids_sql)";
        }
    }
    if (!empty($search)) {
        $where_repair .= " AND (ro.order_no LIKE '%$search%' OR ro.customer_name LIKE '%$search%' OR roi.product_name LIKE '%$search%' OR roi.design_no LIKE '%$search%' OR roi.barcode LIKE '%$search%')";
    }

    $repair_items_query = "
        SELECT 
            roi.id as item_id,
            roi.order_id,
            roi.product_id,
            roi.product_characteristic_id,
            roi.barcode,
            roi.product_name,
            roi.design_no,
            roi.quantity,
            roi.gross_weight,
            roi.final_weight,
            roi.net_weight,
            roi.rate,
            roi.amount,
            roi.status as item_status,
            ro.order_no,
            ro.customer_name,
            ro.order_date,
            ro.due_date,
            ro.status as order_status,
            ro.sales_person,
            p.name as product_name_from_product,
            p.article,
            pc.sku_code as rfid_code
            " . ($has_roi_item_images_col ? ", roi.images" : "") . "
        FROM tbl_repair_order_items roi
        INNER JOIN tbl_repair_orders ro ON roi.order_id = ro.id
        LEFT JOIN tbl_products p ON roi.product_id = p.id
        LEFT JOIN tbl_product_characteristics pc ON roi.product_characteristic_id = pc.id
        WHERE $where_repair
    ";
    $repair_rows = getList($repair_items_query);
    foreach ($repair_rows as $row) {
        $product_name = !empty($row['product_name']) ? $row['product_name'] : ($row['product_name_from_product'] ?? $row['article'] ?? 'N/A');
        $row['product_name_display'] = $product_name;
        $row['rfid_code'] = $row['rfid_code'] ?? '';
        $row['product_photo'] = '';
        $row['order_kind'] = 'repair';
        if ($has_roi_item_images_col && !empty($row['images'])) {
            $dec = @json_decode($row['images'], true);
            if ($dec && !empty($dec['images']) && is_array($dec['images'])) {
                $primary = isset($dec['primary']) ? $dec['primary'] : $dec['images'][0];
                if ($primary !== '' && $primary !== null) {
                    $base_admin = (isset($SiteUrl) ? rtrim($SiteUrl, '/') . '/' : '') . 'admin/';
                    $row['product_photo'] = $base_admin . ltrim((string)$primary, '/');
                }
            }
        }
        $repair_processed[] = $row;
    }
}

$all_items = array_merge($sale_processed, $repair_processed);
usort($all_items, function ($a, $b) {
    $da = strtotime($a['order_date'] ?? '1970-01-01');
    $db = strtotime($b['order_date'] ?? '1970-01-01');
    if ($da !== $db) {
        return $db <=> $da;
    }
    $oid = (int)($b['order_id'] ?? 0) <=> (int)($a['order_id'] ?? 0);
    if ($oid !== 0) {
        return $oid;
    }
    $ka = ($a['order_kind'] ?? 'sale') === 'repair' ? 1 : 0;
    $kb = ($b['order_kind'] ?? 'sale') === 'repair' ? 1 : 0;
    if ($ka !== $kb) {
        return $ka <=> $kb;
    }
    return (int)($a['item_id'] ?? 0) <=> (int)($b['item_id'] ?? 0);
});

$total_records = count($all_items);
$total_pages = $total_records > 0 ? ceil($total_records / $per_page) : 1;
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * $per_page;
$items = array_slice($all_items, $offset, $per_page);

// Calculate total final weight (full result set, not current page)
$total_final_wt = 0;
foreach ($all_items as $it) {
    $total_final_wt += (float)($it['final_weight'] ?? 0);
}

$repair_jwo_by_ro_id = [];
if ($has_repair_order_tables) {
    $rjwo_t = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_repair_jobwork_orders'");
    if ($rjwo_t && mysqli_num_rows($rjwo_t) > 0) {
        mysqli_free_result($rjwo_t);
        foreach (getList("SELECT id, repair_order_id, jobwork_no, status FROM tbl_repair_jobwork_orders") as $rr) {
            $rid = (int)($rr['repair_order_id'] ?? 0);
            if ($rid > 0) {
                $repair_jwo_by_ro_id[$rid] = $rr;
            }
        }
    } elseif ($rjwo_t) {
        mysqli_free_result($rjwo_t);
    }
}

// Get branches for filter
$branches = getListMaster("SELECT id, name FROM tbl_branches WHERE status = 1 ORDER BY name ASC");
$default_branch = !empty($branches) ? $branches[0]['name'] : 'Main Branch';
$customer_options = getList("SELECT DISTINCT name FROM tbl_customers WHERE status = 1 AND TRIM(IFNULL(name,'')) != '' ORDER BY name ASC");
$department_options = getList("SELECT id, dept_name FROM tbl_departments WHERE status = 1 ORDER BY dept_name ASC");
$product_options = getList("SELECT id, name FROM tbl_products WHERE status = 1 AND TRIM(IFNULL(name,'')) != '' ORDER BY name ASC");
$source_options = ['JewelStep', 'WooCommerce', 'Shopify'];

// Item-level JWO status mapping (so only selected/processed items show "Job Work Done")
$jwo_item_done_map = [];
$jwo_item_no_map = [];
$jwo_item_id_map = [];
$jwo_item_jwo_completed_map = [];
$jwo_orders_tbl_check = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
$jwo_items_tbl_check = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_order_items'");
if ($jwo_orders_tbl_check && mysqli_num_rows($jwo_orders_tbl_check) > 0 && $jwo_items_tbl_check && mysqli_num_rows($jwo_items_tbl_check) > 0) {
    mysqli_free_result($jwo_orders_tbl_check);
    mysqli_free_result($jwo_items_tbl_check);
    $jwo_item_rows = getList("
        SELECT 
            jo.id AS jobwork_order_id,
            jo.sale_order_id,
            jo.jobwork_no,
            LOWER(TRIM(IFNULL(jo.status, ''))) AS jwo_status_lc,
            IFNULL(TRIM(ji.barcode), '') AS barcode,
            IFNULL(ji.product_id, 0) AS product_id,
            IFNULL(TRIM(ji.design_no), '') AS design_no
        FROM tbl_jobwork_orders jo
        INNER JOIN tbl_jobwork_order_items ji ON ji.jobwork_order_id = jo.id
    ");
    foreach ($jwo_item_rows as $jr) {
        $so_id = (int)($jr['sale_order_id'] ?? 0);
        $barcode = trim((string)($jr['barcode'] ?? ''));
        $product_id = (int)($jr['product_id'] ?? 0);
        $design_no = trim((string)($jr['design_no'] ?? ''));
        $jwo_no = (string)($jr['jobwork_no'] ?? '');
        $jwo_oid = (int)($jr['jobwork_order_id'] ?? 0);
        $jwo_master_completed = (($jr['jwo_status_lc'] ?? '') === 'completed');
        if ($so_id <= 0) continue;

        // Primary match key: sale-order + barcode
        if ($barcode !== '') {
            $k1 = $so_id . '|B|' . strtolower($barcode);
            $jwo_item_done_map[$k1] = true;
            $jwo_item_no_map[$k1] = $jwo_no;
            $jwo_item_jwo_completed_map[$k1] = $jwo_master_completed;
            if ($jwo_oid > 0) {
                $jwo_item_id_map[$k1] = $jwo_oid;
            }
        }
        // Fallback key when barcode is empty: sale-order + product + design
        $k2 = $so_id . '|P|' . $product_id . '|D|' . strtolower($design_no);
        $jwo_item_done_map[$k2] = true;
        $jwo_item_no_map[$k2] = $jwo_no;
        $jwo_item_jwo_completed_map[$k2] = $jwo_master_completed;
        if ($jwo_oid > 0) {
            $jwo_item_id_map[$k2] = $jwo_oid;
        }
    }
} else {
    if ($jwo_orders_tbl_check) mysqli_free_result($jwo_orders_tbl_check);
    if ($jwo_items_tbl_check) mysqli_free_result($jwo_items_tbl_check);
}
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Sale &amp; Repair Order Process - <?php echo $Proj_Title; ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php';?>
</head>
<style>
body { background: #f4f6fb; /* font-family: 'Segoe UI', Arial, sans-serif; */ }
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
.page-header-actions { display: flex; gap: 10px; align-items: center; }
.btn-process { background: #11294b; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; }
.btn-process:hover { background: #4a2b7c; color: #fff; }
.toolbar { background: #fff; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.toolbar-left, .toolbar-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.btn-filter { background: #fff; border: 1px solid #e2e8f0; color: #64748b; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; }
.btn-filter:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-export { background: #11294b; border: none; color: #fff; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; }
.btn-export:hover { background: #4a2b7c; color: #fff; }
.toolbar-menu-wrap .dropdown-toggle::after { margin-left: 6px; }
.toolbar-btn {
    border: 1px solid #6f56d9;
    color: #4f46e5;
    background: #fff;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
}
.toolbar-btn:hover, .toolbar-btn:focus {
    background: #f5f3ff;
    color: #4338ca;
}
.toolbar-btn.btn-import {
    background: #4f46e5;
    color: #fff;
}
.toolbar-btn.btn-import:hover, .toolbar-btn.btn-import:focus {
    background: #4338ca;
    color: #fff;
}
.toolbar-btn.btn-action {
    border-color: #ec4899;
    color: #be185d;
}
.toolbar-btn.btn-action:hover, .toolbar-btn.btn-action:focus {
    background: #fdf2f8;
    color: #9d174d;
}
.toolbar-menu-wrap { position: relative; }
.toolbar-menu-wrap .dropdown-menu {
    min-width: 190px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
    border-radius: 8px;
    padding: 6px 0;
}
.toolbar-menu-wrap .dropdown-item {
    font-size: 13px;
    color: #475569;
    padding: 8px 14px;
}
.toolbar-menu-wrap .dropdown-item:hover { background: #f8fafc; color: #1e293b; }
.toolbar-menu-wrap .dropdown-item.disabled,
.toolbar-menu-wrap .dropdown-item.disabled:hover {
    color: #94a3b8;
    background: #fff;
    pointer-events: none;
    cursor: not-allowed;
}
.toolbar-menu-wrap.is-open .dropdown-menu { display: block; }

/* advance-filter-global.css: #advancedFilterModal is listed with #filterModal as full-viewport backdrop (not the 860px card). */

/* Filter modal — same centered overlay pattern as transaction-report.php */
.filter-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
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
    overflow: hidden;
    display: flex;
    flex-direction: column;
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
    flex-shrink: 0;
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
    line-height: 1;
}
.filter-modal-close:hover {
    color: #f0f0f0;
}
.filter-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}
#advancedFilterModal .form-control,
#advancedFilterModal .custom-select {
    min-height: 34px;
    font-size: 12px;
}
#advancedFilterModal .form-group label {
    font-size: 12px;
    font-weight: 600;
    color: #435474;
    margin-bottom: 4px;
}
#advancedFilterModal .form-group {
    margin-bottom: 12px;
}
.filter-modal-footer {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    flex-shrink: 0;
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
    color: #fff;
}
.btn-clear {
    background: #fff;
    border: 1px solid #ec4899;
    color: #ec4899;
    padding: 8px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.btn-clear:hover {
    background: #fdf2f8;
    color: #ec4899;
    text-decoration: none;
}
#advancedFilterModal .multi-dd-panel {
    z-index: 1100;
}
.multi-dd { position: relative; }
.multi-dd-display {
    border: 1px solid #ced4da;
    height: 34px;
    border-radius: 8px;
    padding: 7px 30px 7px 10px;
    font-size: 12px;
    color: #475569;
    background: #fff;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    position: relative;
}
.multi-dd-display::after {
    content: "\e92e";
    font-family: feather;
    position: absolute;
    right: 10px;
    top: 8px;
    color: #94a3b8;
}
.multi-dd-panel {
    display: none;
    position: absolute;
    left: 0;
    right: 0;
    top: 36px;
    z-index: 1200;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
    max-height: 230px;
}
.multi-dd.open .multi-dd-panel { display: block; }
.multi-dd-search {
    width: 100%;
    border: none;
    border-bottom: 1px solid #e2e8f0;
    height: 32px;
    padding: 6px 10px;
    font-size: 12px;
}
.multi-dd-search:focus { outline: none; }
.multi-dd-list { max-height: 160px; overflow: auto; padding: 6px 0; }
.multi-dd-option, .multi-dd-selectall {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 10px;
    font-size: 12px;
    color: #475569;
}
.multi-dd-option:hover { background: #f8fafc; }
.table-container { background: #fff; margin: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: auto; }
.table { width: 100%; margin: 0; font-size: 11px; border-collapse: collapse; }

.table tbody td { padding: 2px; border: 1px solid #dee2e6; vertical-align: middle; text-align: center; }
.table tbody tr:hover { background: #f8fafc; }
.status-badge { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block; }
.status-not-initiate, .status-draft { background: #6c757d; color: #fff; }
.status-processing { background: #11294b; color: #fff; }
.status-completed { background: #28a745; color: #fff; }
.status-rejected { background: #dc3545; color: #fff; }
.status-invoice { background: #17a2b8; color: #fff; }
.btn-action { padding: 6px 12px; font-size: 12px; border-radius: 4px; cursor: pointer; margin: 2px; border: none; font-weight: 600; text-decoration: none; display: inline-block; }
.btn-jobwork { background: #11294b; color: #fff; }
.btn-jobwork:hover { background: #4a2b7c; color: #fff; }
.btn-jobwork:disabled { background: #adb5bd; color: #fff; cursor: not-allowed; opacity: 0.9; }
.btn-catalogue { background: #e83e8c; color: #fff; }
.btn-catalogue:hover { background: #d62d7c; color: #fff; }
.action-cell-btns { display: inline-flex; flex-wrap: nowrap; align-items: center; justify-content: center; gap: 4px; margin: 0 auto; }
td[data-col="action"] { white-space: nowrap; vertical-align: middle; }
.action-cell-btns .btn-action { flex-shrink: 0; white-space: nowrap; }
.btn-edit-jwo { background: #fff; color: #11294b; border: 1px solid #11294b; }
.btn-edit-jwo:hover:not(:disabled) { background: #f1f5f9; color: #11294b; }
.btn-edit-jwo:disabled,
.btn-edit-jwo[disabled] {
    background: #adb5bd;
    color: #fff;
    border-color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.95;
}
.img-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; background: #f1f5f9; }
.pagination-container { background: #fff; padding: 12px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin: 0 20px 20px 20px; border-radius: 0 0 8px 8px; }
.info-icon { color: #64748b; cursor: pointer; font-size: 12px; }
/* Product image column (sale order line photo from DB) */
.col-product-image { min-width: 56px; vertical-align: middle; }
.btn-icon-action { width: 32px; height: 32px; padding: 0; border: 1px solid #e2e8f0; border-radius: 4px; background: #fff; color: #11294b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; margin: 1px; font-size: 0.9rem; }
.btn-icon-action:hover { background: #f1edff; border-color: #11294b; }
.th-col-settings { position: relative; }
.btn-col-settings { width: 32px; height: 32px; padding: 0; border: none; background: transparent; color: #64748b; cursor: pointer; border-radius: 4px; }
.btn-col-settings:hover { background: #e2e8f0; color: #11294b; }
#columnSettingsModal .modal-dialog { max-width: 360px; }
#columnSettingsModal .form-check { padding: 6px 0; }
.sop-col-hidden { display: none !important; }
#columnSettingsModal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1050; overflow-x: hidden; overflow-y: auto; }
#columnSettingsModal.show { display: block !important; }
#columnSettingsModal .modal-dialog { position: relative; margin: 1.75rem auto; max-width: 360px; }
#columnSettingsModal .modal-content { background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
.modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1040; }
</style>
<body>
<?php include 'sidebar.php'; ?>
<div class="layout-content">
<div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">
            <!-- Page Header -->
            <div class="page-header-bar">
                <span>Order List</span>
                <div class="page-header-actions">
                    <a href="sale-order.php" class="btn-process">Sale / Repair Order Process</a>
                </div>
            </div>


            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <button type="button" class="btn-filter" id="openFilterModal"><i class="feather icon-filter"></i></button>
                    <button type="button" class="btn-filter" id="btnRefreshRows"><i class="feather icon-refresh-cw"></i></button>

                    <div class="dropdown toolbar-menu-wrap">
                        <button type="button" class="toolbar-btn dropdown-toggle js-toolbar-toggle" aria-expanded="false">Export</button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" id="btnExportExcel"><i class="feather icon-file-text mr-2"></i>Excel</a>
                            <a class="dropdown-item" href="#" id="btnExportPdf"><i class="feather icon-file mr-2"></i>PDF</a>
                        </div>
                    </div>

                    <div class="dropdown toolbar-menu-wrap">
                        <button type="button" class="toolbar-btn btn-import dropdown-toggle js-toolbar-toggle" aria-expanded="false">+ Import</button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#"><i class="feather icon-upload mr-2"></i>Import</a>
                            <a class="dropdown-item" href="#"><i class="feather icon-corner-down-left mr-2"></i>Sample</a>
                        </div>
                    </div>

                    <div class="dropdown toolbar-menu-wrap">
                        <button type="button" class="toolbar-btn btn-action dropdown-toggle js-toolbar-toggle" aria-expanded="false">Action</button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item action-bulk-item" href="#" data-action="jobwork-order">Job Work Order</a>
                            <a class="dropdown-item action-bulk-item" href="#" data-action="create-jobwork-invoice">Create Jobwork Invoice</a>
                            <a class="dropdown-item action-bulk-item" href="#" data-action="sync-order">Sync Order</a>
                            <a class="dropdown-item action-bulk-item" href="#" data-action="circle-transfer">Circle Transfer</a>
                            <a class="dropdown-item action-bulk-item" href="#" data-action="send-notification">Send Notification</a>
                        </div>
                    </div>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="d-flex gap-2 align-items-center">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="width: 200px;">
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($from_date); ?>">
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($to_date); ?>">
                        <button type="submit" class="btn-export">Search</button>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table class="table table-bordered table-hover text-center" id="orderProcessTable">
                    <thead>
                        <tr>
                            <th data-col="check"><input type="checkbox" id="selectAll"></th>
                            <th data-col="product">Product</th>
                            <th data-col="image">Image</th>
                            <th data-col="rfid">RFID Code</th>
                            <th data-col="order_no">Order No. <span style="font-weight:400;opacity:.85">(SO / RO)</span></th>
                            <th data-col="jobwork_order">Jobwork Order No.</th>
                            <th data-col="jobwork_invoice">Jobwork Invoice No</th>
                            <th data-col="customer">Customer Name</th>
                            <th data-col="current_dept">Current Dept.</th>
                            <th data-col="final_wt">Final Wt</th>
                            <th data-col="order_date">Order Date</th>
                            <th data-col="due_date">Due Date</th>
                            <th data-col="tag_no">Tag No</th>
                            <th data-col="status">Status</th>
                            <th data-col="ecom_order_no">Ecommerce Order No</th>
                            <th data-col="source">Source</th>
                            <th data-col="design_no">Design No.</th>
                            <th data-col="current_user">Current User</th>
                            <th data-col="ecom_status">Ecommerce Order Status</th>
                            <th data-col="sale_invoice">Sale Invoice</th>
                            <th data-col="branch">Branch Name</th>
                            <th data-col="info">info</th>
                            <th data-col="action_icons">Actions</th>
                            <th data-col="action" class="th-col-settings">
                                <span>action</span>
                                <button type="button" class="btn-col-settings ml-1" id="btnColumnSettings" title="Show/Hide columns"><i class="feather icon-settings"></i></button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                            $is_repair_row = (($item['order_kind'] ?? 'sale') === 'repair');
                            $order_status = strtolower(trim($item['order_status'] ?? 'draft'));
                            if ($is_repair_row) {
                                $rid_for_st = (int)($item['order_id'] ?? 0);
                                if ($rid_for_st > 0 && !empty($repair_jwo_by_ro_id[$rid_for_st]['status'])) {
                                    $order_status = strtolower(trim((string)$repair_jwo_by_ro_id[$rid_for_st]['status']));
                                    if ($order_status === 'not initiate') {
                                        $order_status = 'draft';
                                    }
                                }
                            }
                            $status_class = 'status-not-initiate';
                            $status_label = 'Not Initiate';
                            if ($order_status === 'rejected') { $status_class = 'status-rejected'; $status_label = 'Rejected'; }
                            elseif ($order_status === 'completed') { $status_class = 'status-completed'; $status_label = 'Completed'; }
                            elseif ($order_status === 'processing') { $status_class = 'status-processing'; $status_label = 'Processing'; }
                            elseif (stripos($order_status, 'invoice') !== false) { $status_class = 'status-invoice'; $status_label = 'Invoice Created'; }
                            elseif ($order_status !== '' && $order_status !== 'draft') { $status_label = ucfirst($order_status); }
                            
                            $product_name = $item['product_name_display'] ?? 'N/A';
                            $img_src = !empty($item['product_photo']) ? $item['product_photo'] : '';
                            $tag_no = !empty($item['barcode']) ? $item['barcode'] : '-';
                            $order_date_fmt = !empty($item['order_date']) ? date('d-m-Y', strtotime($item['order_date'])) : '-';
                            $due_date_fmt = !empty($item['due_date']) ? date('d-m-Y', strtotime($item['due_date'])) : '-';
                            $final_wt = (float)($item['final_weight'] ?? 0);
                            $row_order_id = (int)($item['order_id'] ?? 0);
                            $row_barcode = trim((string)($item['barcode'] ?? ''));
                            $row_product_id = (int)($item['product_id'] ?? 0);
                            $row_design_no = trim((string)($item['design_no'] ?? ''));
                            $row_key_barcode = $row_order_id . '|B|' . strtolower($row_barcode);
                            $row_key_fallback = $row_order_id . '|P|' . $row_product_id . '|D|' . strtolower($row_design_no);

                            $has_jwo = false;
                            $jwo_no = '-';
                            $jwo_edit_id = 0;
                            $jwo_row_master_completed = false;

                            if ($is_repair_row) {
                                $rj = $repair_jwo_by_ro_id[$row_order_id] ?? null;
                                $has_jwo = !empty($rj);
                                if ($has_jwo) {
                                    $jwo_no = (string)($rj['jobwork_no'] ?? '-');
                                    $jwo_edit_id = (int)($rj['id'] ?? 0);
                                    $jwo_row_master_completed = (strtolower(trim((string)($rj['status'] ?? ''))) === 'completed');
                                }
                            } else {
                                $has_jwo = (!empty($row_barcode) && !empty($jwo_item_done_map[$row_key_barcode])) || (!empty($jwo_item_done_map[$row_key_fallback]));
                                if (!empty($row_barcode) && !empty($jwo_item_no_map[$row_key_barcode])) {
                                    $jwo_no = (string)$jwo_item_no_map[$row_key_barcode];
                                } elseif (!empty($jwo_item_no_map[$row_key_fallback])) {
                                    $jwo_no = (string)$jwo_item_no_map[$row_key_fallback];
                                }
                                if (!empty($row_barcode) && !empty($jwo_item_id_map[$row_key_barcode])) {
                                    $jwo_edit_id = (int)$jwo_item_id_map[$row_key_barcode];
                                } elseif (!empty($jwo_item_id_map[$row_key_fallback])) {
                                    $jwo_edit_id = (int)$jwo_item_id_map[$row_key_fallback];
                                }
                                if (!empty($row_barcode) && array_key_exists($row_key_barcode, $jwo_item_jwo_completed_map)) {
                                    $jwo_row_master_completed = (bool) $jwo_item_jwo_completed_map[$row_key_barcode];
                                } elseif (array_key_exists($row_key_fallback, $jwo_item_jwo_completed_map)) {
                                    $jwo_row_master_completed = (bool) $jwo_item_jwo_completed_map[$row_key_fallback];
                                }
                            }

                            // Row-level status rule: if item has no JWO yet, always show Not Initiate.
                            if (!$has_jwo) {
                                $status_class = 'status-not-initiate';
                                $status_label = 'Not Initiate';
                            } elseif ($jwo_row_master_completed && $order_status === 'processing') {
                                // Manufacturing marks JWO Completed via mp-jobwork-order-comments-api.php; SO row may still be processing until sync
                                $status_class = 'status-completed';
                                $status_label = 'Completed';
                            }
                            $edit_jwo_disabled = ($status_label === 'Completed');
                        ?>
                        <tr>
                            <td data-col="check"><input type="checkbox" class="row-checkbox" value="<?php echo (int)$item['item_id']; ?>" data-item-id="<?php echo (int)$item['item_id']; ?>" data-order-id="<?php echo (int)$item['order_id']; ?>" data-order-kind="<?php echo $is_repair_row ? 'repair' : 'sale'; ?>" data-has-jwo="<?php echo $has_jwo ? '1' : '0'; ?>"></td>
                            <td data-col="product" class="text-left"><?php echo htmlspecialchars($product_name); ?></td>
                            <td data-col="image" class="col-product-image">
                                <?php if ($img_src): ?>
                                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="" class="img-thumb">
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-col="rfid"><?php echo htmlspecialchars($item['rfid_code'] ?: '-'); ?></td>
                            <td data-col="order_no"><a href="<?php echo $is_repair_row ? 'repair-order.php' : 'sale-order.php'; ?>?id=<?php echo (int)$item['order_id']; ?>"><?php echo htmlspecialchars($item['order_no']); ?></a> <span style="font-size:10px;color:#64748b;font-weight:600;"><?php echo $is_repair_row ? '(RO)' : '(SO)'; ?></span></td>
                            <td data-col="jobwork_order"><?php echo $has_jwo ? htmlspecialchars($jwo_no) : '-'; ?></td>
                            <td data-col="jobwork_invoice">-</td>
                            <td data-col="customer"><?php echo htmlspecialchars($item['customer_name'] ?? '-'); ?></td>
                            <td data-col="current_dept"><?php echo htmlspecialchars($default_branch); ?></td>
                            <td data-col="final_wt" class="text-right"><?php echo number_format($final_wt, 3); ?></td>
                            <td data-col="order_date"><?php echo $order_date_fmt; ?></td>
                            <td data-col="due_date"><?php echo $due_date_fmt; ?></td>
                            <td data-col="tag_no"><?php echo htmlspecialchars($tag_no); ?></td>
                            <td data-col="status"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_label; ?></span></td>
                            <td data-col="ecom_order_no">-</td>
                            <td data-col="source"><?php echo $is_repair_row ? 'Repair' : 'AuraGold'; ?></td>
                            <td data-col="design_no"><?php echo htmlspecialchars($item['design_no'] ?? '-'); ?></td>
                            <td data-col="current_user"><?php echo htmlspecialchars($item['sales_person'] ?? '-'); ?></td>
                            <td data-col="ecom_status">NA</td>
                            <td data-col="sale_invoice">-</td>
                            <td data-col="branch"><?php echo htmlspecialchars($default_branch); ?></td>
                            <td data-col="info"><i class="feather icon-info info-icon" title="Info"></i></td>
                            <td data-col="action_icons">
                                <button type="button" class="btn-icon-action" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                                <button type="button" class="btn-icon-action" title="List"><i class="feather icon-list"></i></button>
                                <button type="button" class="btn-icon-action" title="Document"><i class="feather icon-file-text"></i></button>
                                <button type="button" class="btn-icon-action" title="User"><i class="feather icon-user"></i></button>
                                <button type="button" class="btn-icon-action" title="Download"><i class="feather icon-download"></i></button>
                                <button type="button" class="btn-icon-action" title="Forward"><i class="feather icon-arrow-right"></i></button>
                            </td>
                            <td data-col="action">
                                <div class="action-cell-btns">
                                <?php if ($is_repair_row): ?>
                                    <?php if ($has_jwo): ?>
                                    <button type="button" class="btn-action btn-jobwork" disabled>Job Work Done</button>
                                    <?php if ($edit_jwo_disabled): ?>
                                    <button type="button" class="btn-action btn-edit-jwo" disabled title="Job work is completed">Edit Job Work Order</button>
                                    <?php else: ?>
                                    <a href="jobwork-order.php?sale_order_id=<?php echo (int)$item['order_id']; ?>&amp;from_repair=1" class="btn-action btn-edit-jwo" title="Open job work order">Edit Job Work Order</a>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <a href="jobwork-order.php?sale_order_id=<?php echo (int)$item['order_id']; ?>&amp;from_repair=1" class="btn-action btn-jobwork">Jobwork Order</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($has_jwo): ?>
                                    <button type="button" class="btn-action btn-jobwork" disabled>Job Work Done</button>
                                    <?php if ($edit_jwo_disabled): ?>
                                    <button type="button" class="btn-action btn-edit-jwo" disabled title="Job work is completed">Edit Job Work Order</button>
                                    <?php else: ?>
                                    <a href="<?php echo $jwo_edit_id > 0 ? 'jobwork-order.php?id=' . (int)$jwo_edit_id . '&amp;sale_order_id=' . (int)$item['order_id'] : 'jobwork-order.php?sale_order_id=' . (int)$item['order_id']; ?>" class="btn-action btn-edit-jwo" title="Open job work order to correct or complete saved details">Edit Job Work Order</a>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <a href="jobwork-order.php?sale_order_id=<?php echo (int)$item['order_id']; ?>" class="btn-action btn-jobwork">Jobwork Order</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button type="button" class="btn-action btn-catalogue">Create Catalogue</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="24" class="text-center py-5 text-muted">No sale or repair order items found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($items)): ?>
                    <tfoot>
                        <tr class="table-footer-total">
                            <td colspan="9" class="text-right"><strong>Total:</strong></td>
                            <td data-col="final_wt" class="text-right"><strong><?php echo number_format($total_final_wt, 3); ?></strong></td>
                            <td colspan="14"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Showing <?php echo $total_records > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php $qp = $_GET; unset($qp['per_page']); $qstr = http_build_query($qp); $qpre = $qstr ? $qstr . '&' : ''; ?>
                    <select class="form-control form-control-sm" style="width: auto;" onchange="location.href='?<?php echo $qpre; ?>per_page='+this.value+'&page=1'">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&laquo;</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
</div>
</div>

<!-- Advanced Filter Modal (centered overlay — same as transaction-report.php) -->
<div id="advancedFilterModal" class="filter-modal" aria-hidden="true">
    <div class="filter-modal-content" role="dialog" aria-modal="true" aria-labelledby="advancedFilterModalTitle">
        <div class="filter-modal-header">
            <h5 id="advancedFilterModalTitle">Advance Filter</h5>
            <button type="button" class="filter-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="filter-modal-body">
            <form method="GET" id="advancedFilterForm">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Order Date</label>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                                <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Due Date</label>
                            <div class="d-flex align-items-center" style="gap:8px;">
                                <input type="date" class="form-control">
                                <input type="date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Branch</label>
                            <select class="custom-select">
                                <option><?php echo htmlspecialchars($default_branch); ?></option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Customer</label>
                            <div class="multi-dd" data-multi-dd>
                                <input type="hidden" name="customer_names" value="<?php echo htmlspecialchars(implode(',', $selected_customer_names)); ?>">
                                <div class="multi-dd-display">Select Customer</div>
                                <div class="multi-dd-panel">
                                    <label class="multi-dd-selectall"><input type="checkbox" data-select-all> Select All</label>
                                    <input type="text" class="multi-dd-search" placeholder="Search">
                                    <div class="multi-dd-list">
                                        <?php foreach ($customer_options as $co): $cn = trim((string)($co['name'] ?? '')); if ($cn === '') continue; ?>
                                        <label class="multi-dd-option">
                                            <input type="checkbox" value="<?php echo htmlspecialchars($cn); ?>" <?php echo in_array($cn, $selected_customer_names, true) ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($cn); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Sales Order No.</label>
                            <input type="text" name="order_no" class="form-control" value="<?php echo htmlspecialchars($order_no); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Status</label>
                            <div class="multi-dd" data-multi-dd>
                                <input type="hidden" name="status_list" value="<?php echo htmlspecialchars(implode(',', $selected_status_list)); ?>">
                                <div class="multi-dd-display">Select Status</div>
                                <div class="multi-dd-panel">
                                    <label class="multi-dd-selectall"><input type="checkbox" data-select-all> Select All</label>
                                    <input type="text" class="multi-dd-search" placeholder="Search">
                                    <div class="multi-dd-list">
                                        <?php foreach (['Completed','Hold','Invoice Created','Not Initiate','Processing','Rejected','Transfered'] as $st): ?>
                                        <label class="multi-dd-option">
                                            <input type="checkbox" value="<?php echo htmlspecialchars($st); ?>" <?php echo in_array($st, $selected_status_list, true) ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($st); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Current Dept.</label>
                            <div class="multi-dd" data-multi-dd>
                                <input type="hidden" name="department_ids" value="<?php echo htmlspecialchars(implode(',', $selected_department_ids)); ?>">
                                <div class="multi-dd-display">Select Department</div>
                                <div class="multi-dd-panel">
                                    <label class="multi-dd-selectall"><input type="checkbox" data-select-all> Select All</label>
                                    <input type="text" class="multi-dd-search" placeholder="Search">
                                    <div class="multi-dd-list">
                                        <?php foreach ($department_options as $dep): ?>
                                        <label class="multi-dd-option">
                                            <input type="checkbox" value="<?php echo (int)$dep['id']; ?>" <?php echo in_array((int)$dep['id'], $selected_department_ids, true) ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($dep['dept_name'] ?? ''); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Product Name</label>
                            <div class="multi-dd" data-multi-dd>
                                <input type="hidden" name="product_ids" value="<?php echo htmlspecialchars(implode(',', $selected_product_ids)); ?>">
                                <div class="multi-dd-display">Select Product</div>
                                <div class="multi-dd-panel">
                                    <label class="multi-dd-selectall"><input type="checkbox" data-select-all> Select All</label>
                                    <input type="text" class="multi-dd-search" placeholder="Search">
                                    <div class="multi-dd-list">
                                        <?php foreach ($product_options as $po): ?>
                                        <label class="multi-dd-option">
                                            <input type="checkbox" value="<?php echo (int)$po['id']; ?>" <?php echo in_array((int)$po['id'], $selected_product_ids, true) ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($po['name'] ?? ''); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Source</label>
                            <div class="multi-dd" data-multi-dd>
                                <input type="hidden" name="source_list" value="<?php echo htmlspecialchars(implode(',', $selected_source_list)); ?>">
                                <div class="multi-dd-display">Select Source</div>
                                <div class="multi-dd-panel">
                                    <label class="multi-dd-selectall"><input type="checkbox" data-select-all> Select All</label>
                                    <input type="text" class="multi-dd-search" placeholder="Search">
                                    <div class="multi-dd-list">
                                        <?php foreach ($source_options as $src): ?>
                                        <label class="multi-dd-option">
                                            <input type="checkbox" value="<?php echo htmlspecialchars($src); ?>" <?php echo in_array($src, $selected_source_list, true) ? 'checked' : ''; ?>>
                                            <span><?php echo htmlspecialchars($src); ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Design No</label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tag No.</label>
                            <input type="text" name="search" class="form-control" placeholder="Tag / RFID / Product search" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label>RFID Code</label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Jobwork Order No</label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Jobwork Inv. No.</label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Ecommerce Order No</label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="form-group col-md-6"></div>
                    </div>
                <div class="filter-modal-footer">
                    <button type="submit" class="btn-apply">Apply Filter</button>
                    <a href="sale-order-process.php" class="btn-clear">Clear Filter</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Settings Modal (Show/Hide columns) -->
<div class="modal fade" id="columnSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="feather icon-settings mr-2"></i> Show / Hide columns</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Toggle columns to show or hide in the table.</p>
                <div id="columnSettingsList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="columnSettingsApply">Apply</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
    updateBulkActionState();
});
document.querySelectorAll('.row-checkbox').forEach(function(cb) {
    cb.addEventListener('change', updateBulkActionState);
});
function updateBulkActionState() {
    var selected = Array.from(document.querySelectorAll('.row-checkbox:checked'));
    var hasDoneItem = selected.some(function(cb) { return (cb.getAttribute('data-has-jwo') || '0') === '1'; });
    var jwoAction = document.querySelector('.action-bulk-item[data-action="jobwork-order"]');
    if (!jwoAction) return;
    jwoAction.classList.toggle('disabled', hasDoneItem);
    jwoAction.setAttribute('aria-disabled', hasDoneItem ? 'true' : 'false');
    jwoAction.setAttribute('title', hasDoneItem ? 'One or more selected items already have Job Work Order.' : '');
}
document.getElementById('openFilterModal')?.addEventListener('click', function() {
    var modal = document.getElementById('advancedFilterModal');
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-modal', 'true');
    modal.removeAttribute('aria-hidden');
});
document.getElementById('btnRefreshRows')?.addEventListener('click', function() { window.location.reload(); });
document.getElementById('btnExportExcel')?.addEventListener('click', function(e) {
    e.preventDefault();
    alert('Excel export can be connected here.');
});
document.getElementById('btnExportPdf')?.addEventListener('click', function(e) {
    e.preventDefault();
    alert('PDF export can be connected here.');
});
document.querySelectorAll('.action-bulk-item').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        var action = this.getAttribute('data-action');
        var selected = Array.from(document.querySelectorAll('.row-checkbox:checked'));
        if (!selected.length) {
            alert('Please select at least one item.');
            return;
        }
        if (action === 'jobwork-order') {
            if (this.classList.contains('disabled')) {
                alert('Cannot create Job Work Order because selected rows include already processed item(s).');
                return;
            }
            var kinds = selected.map(function(cb) { return cb.getAttribute('data-order-kind') || 'sale'; });
            var allSale = kinds.every(function(k) { return k === 'sale'; });
            var allRepair = kinds.every(function(k) { return k === 'repair'; });
            if (!allSale && !allRepair) {
                alert('Please select only Sale Order rows or only Repair Order rows for Job Work (do not mix SO and RO in one action).');
                return;
            }
            var itemIds = selected.map(function(cb) { return cb.getAttribute('data-item-id'); }).filter(Boolean);
            var orderIds = selected.map(function(cb) { return cb.getAttribute('data-order-id'); }).filter(Boolean);
            var firstOrderId = orderIds.length ? orderIds[0] : 0;
            var target;
            if (allRepair) {
                target = 'jobwork-order.php?sale_order_id=' + encodeURIComponent(firstOrderId) + '&from_repair=1&selected_item_ids=' + encodeURIComponent(itemIds.join(',')) + '&selected_order_ids=' + encodeURIComponent(orderIds.join(',')) + '&jwo_queue_total=' + encodeURIComponent(String(itemIds.length));
            } else {
                target = 'jobwork-order.php?sale_order_id=' + encodeURIComponent(firstOrderId) + '&selected_item_ids=' + encodeURIComponent(itemIds.join(',')) + '&selected_order_ids=' + encodeURIComponent(orderIds.join(',')) + '&jwo_queue_total=' + encodeURIComponent(String(itemIds.length));
            }
            window.location.href = target;
            return;
        }
        alert('This action is not connected yet.');
    });
});
// Multi checkbox dropdowns for filter modal
(function() {
    function updateDisplay(dd) {
        var hidden = dd.querySelector('input[type="hidden"]');
        var display = dd.querySelector('.multi-dd-display');
        var checks = Array.from(dd.querySelectorAll('.multi-dd-option input[type="checkbox"]:checked'));
        var labels = checks.map(function(c) {
            var sp = c.closest('.multi-dd-option')?.querySelector('span');
            return sp ? sp.textContent.trim() : '';
        }).filter(Boolean);
        if (hidden) hidden.value = checks.map(function(c) { return c.value; }).join(',');
        if (!display) return;
        if (!labels.length) {
            display.textContent = display.getAttribute('data-placeholder') || 'Select';
        } else if (labels.length === 1) {
            display.textContent = labels[0];
        } else {
            display.textContent = labels.length + ' selected';
        }
        var selectAll = dd.querySelector('[data-select-all]');
        if (selectAll) {
            var all = dd.querySelectorAll('.multi-dd-option input[type="checkbox"]');
            selectAll.checked = all.length > 0 && labels.length === all.length;
        }
    }
    function initMultiDd(dd) {
        var display = dd.querySelector('.multi-dd-display');
        var panel = dd.querySelector('.multi-dd-panel');
        var search = dd.querySelector('.multi-dd-search');
        var selectAll = dd.querySelector('[data-select-all]');
        if (!display || !panel) return;
        if (!display.getAttribute('data-placeholder')) display.setAttribute('data-placeholder', display.textContent.trim());
        display.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.multi-dd.open').forEach(function(other) { if (other !== dd) other.classList.remove('open'); });
            dd.classList.toggle('open');
            if (dd.classList.contains('open') && search) search.focus();
        });
        panel.addEventListener('click', function(e) {
            // Keep dropdown open while selecting multiple checkboxes/options.
            e.stopPropagation();
        });
        dd.querySelectorAll('.multi-dd-option input[type="checkbox"]').forEach(function(cb) {
            cb.addEventListener('click', function(e) { e.stopPropagation(); });
            cb.addEventListener('change', function() { updateDisplay(dd); });
        });
        if (selectAll) {
            selectAll.addEventListener('click', function(e) { e.stopPropagation(); });
            selectAll.addEventListener('change', function() {
                dd.querySelectorAll('.multi-dd-option input[type="checkbox"]').forEach(function(cb) { cb.checked = selectAll.checked; });
                updateDisplay(dd);
            });
        }
        if (search) {
            search.addEventListener('click', function(e) { e.stopPropagation(); });
            search.addEventListener('input', function() {
                var q = (search.value || '').toLowerCase().trim();
                dd.querySelectorAll('.multi-dd-option').forEach(function(op) {
                    var txt = (op.textContent || '').toLowerCase();
                    op.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }
        updateDisplay(dd);
    }
    document.querySelectorAll('[data-multi-dd]').forEach(initMultiDd);
    document.addEventListener('click', function() {
        document.querySelectorAll('.multi-dd.open').forEach(function(dd) { dd.classList.remove('open'); });
    });
})();
// Advanced filter modal: close button + backdrop click
(function() {
    var modal = document.getElementById('advancedFilterModal');
    if (!modal) return;
    function hideFilterModal() {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
    }
    var closeBtn = modal.querySelector('.filter-modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideFilterModal();
        });
    }
    modal.addEventListener('click', function(e) {
        if (e.target === modal) hideFilterModal();
    });
})();

// Dropdown fallback for toolbar actions when bootstrap dropdown plugin is unavailable.
(function() {
    var wraps = Array.from(document.querySelectorAll('.toolbar-menu-wrap'));
    if (!wraps.length) return;
    function closeAll(exceptWrap) {
        wraps.forEach(function(w) {
            if (exceptWrap && w === exceptWrap) return;
            w.classList.remove('is-open');
            var btn = w.querySelector('.js-toolbar-toggle');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }
    wraps.forEach(function(wrap) {
        var btn = wrap.querySelector('.js-toolbar-toggle');
        if (!btn) return;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var willOpen = !wrap.classList.contains('is-open');
            closeAll(wrap);
            wrap.classList.toggle('is-open', willOpen);
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
        wrap.querySelectorAll('.dropdown-item').forEach(function(item) {
            item.addEventListener('click', function() {
                closeAll(null);
            });
        });
    });
    document.addEventListener('click', function() { closeAll(null); });
})();
updateBulkActionState();

(function() {
    var COLUMN_KEYS = [
        { key: 'check', label: 'Checkbox' },
        { key: 'product', label: 'Product' },
        { key: 'image', label: 'Image' },
        { key: 'rfid', label: 'RFID Code' },
        { key: 'order_no', label: 'Order No. (SO / RO)' },
        { key: 'jobwork_order', label: 'Jobwork Order No.' },
        { key: 'jobwork_invoice', label: 'Jobwork Invoice No' },
        { key: 'customer', label: 'Customer Name' },
        { key: 'current_dept', label: 'Current Dept.' },
        { key: 'final_wt', label: 'Final Wt' },
        { key: 'order_date', label: 'Order Date' },
        { key: 'due_date', label: 'Due Date' },
        { key: 'tag_no', label: 'Tag No' },
        { key: 'status', label: 'Status' },
        { key: 'ecom_order_no', label: 'Ecommerce Order No' },
        { key: 'source', label: 'Source' },
        { key: 'design_no', label: 'Design No.' },
        { key: 'current_user', label: 'Current User' },
        { key: 'ecom_status', label: 'Ecommerce Order Status' },
        { key: 'sale_invoice', label: 'Sale Invoice' },
        { key: 'branch', label: 'Branch Name' },
        { key: 'info', label: 'Info' },
        { key: 'action_icons', label: 'Actions (icons)' },
        { key: 'action', label: 'Action (Jobwork / Edit / Catalogue)' }
    ];
    var STORAGE_KEY = 'sale_order_process_visible_cols';

    function getStoredVisible() {
        try {
            var s = localStorage.getItem(STORAGE_KEY);
            if (s) return JSON.parse(s);
        } catch (e) {}
        return null;
    }

    function setStoredVisible(obj) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(obj)); } catch (e) {}
    }

    function applyColumnVisibility(visibleMap) {
        var table = document.getElementById('orderProcessTable');
        if (!table) return;
        COLUMN_KEYS.forEach(function(c) {
            var show = visibleMap[c.key] !== false;
            var sel = '[data-col="' + c.key + '"]';
            table.querySelectorAll('th' + sel + ', td' + sel).forEach(function(el) {
                el.classList.toggle('sop-col-hidden', !show);
            });
        });
    }

    function showColumnModal() {
        var modal = document.getElementById('columnSettingsModal');
        if (!modal) return;
        modal.classList.add('show');
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'columnSettingsBackdrop';
        document.body.appendChild(backdrop);
    }

    function hideColumnModal() {
        var modal = document.getElementById('columnSettingsModal');
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('modal-open');
        var backdrop = document.getElementById('columnSettingsBackdrop');
        if (backdrop) backdrop.remove();
    }

    document.getElementById('btnColumnSettings')?.addEventListener('click', function(e) {
        e.preventDefault();
        var stored = getStoredVisible();
        var visible = (stored && typeof stored === 'object' && !Array.isArray(stored)) ? stored : {};
        var list = document.getElementById('columnSettingsList');
        if (!list) return;
        list.innerHTML = '';
        COLUMN_KEYS.forEach(function(c) {
            var isChecked = visible[c.key] !== false;
            var div = document.createElement('div');
            div.className = 'form-check';
            div.innerHTML = '<input type="checkbox" class="form-check-input col-visibility-cb" id="col_' + c.key + '" data-col="' + c.key + '" ' + (isChecked ? 'checked' : '') + '><label class="form-check-label" for="col_' + c.key + '">' + c.label + '</label>';
            list.appendChild(div);
        });
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#columnSettingsModal').modal('show');
        } else {
            showColumnModal();
        }
    });

    document.getElementById('columnSettingsApply')?.addEventListener('click', function() {
        var visible = {};
        document.querySelectorAll('#columnSettingsModal .col-visibility-cb').forEach(function(cb) {
            var key = cb.getAttribute('data-col');
            if (key) visible[key] = !!cb.checked;
        });
        var atLeastOne = Object.keys(visible).some(function(k) { return visible[k]; });
        if (!atLeastOne) {
            alert('Please keep at least one column visible.');
            return;
        }
        setStoredVisible(visible);
        applyColumnVisibility(visible);
        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#columnSettingsModal').modal('hide');
        } else {
            hideColumnModal();
        }
    });

    document.querySelectorAll('#columnSettingsModal .close, #columnSettingsModal [data-dismiss="modal"], #columnSettingsModal .btn-secondary').forEach(function(btn) {
        if (btn) btn.addEventListener('click', hideColumnModal);
    });
    document.getElementById('columnSettingsModal')?.addEventListener('click', function(e) {
        if (e.target === this) hideColumnModal();
    });

    // Apply stored visibility on load only if valid and at least one column visible (never hide all)
    var stored = getStoredVisible();
    if (stored && typeof stored === 'object' && !Array.isArray(stored)) {
        var anyVisible = Object.keys(stored).some(function(k) { return stored[k]; });
        if (anyVisible) applyColumnVisibility(stored);
    }

})();
</script>
</body>
</html>
