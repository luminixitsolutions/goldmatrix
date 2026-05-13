<?php
/**
 * Jobwork Order Manufacturing List — one row per jobwork order line (tbl_jobwork_order_items).
 */
session_start();
require_once __DIR__ . '/config.php';

function jwm_format_spent_time($seconds) {
    $sec = (int) $seconds;
    if ($sec <= 0) {
        return '—';
    }
    $h = (int) floor($sec / 3600);
    $m = (int) floor(($sec % 3600) / 60);
    $s = $sec % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

function jwm_parse_image_urls($jsonOrRaw, $baseUrl) {
    $out = [];
    $raw = trim((string) $jsonOrRaw);
    if ($raw === '') {
        return $out;
    }
    $dec = @json_decode($raw, true);
    if (is_array($dec)) {
        if (!empty($dec['images']) && is_array($dec['images'])) {
            foreach ($dec['images'] as $u) {
                if ($u !== '' && $u !== null) {
                    $out[] = $u;
                }
            }
        } elseif (isset($dec[0])) {
            foreach ($dec as $u) {
                if (is_string($u) && $u !== '') {
                    $out[] = $u;
                }
            }
        }
    }
    $base = rtrim((string) $baseUrl, '/');
    $admin = $base . '/admin/';
    foreach ($out as &$u) {
        if (strpos($u, 'http://') === 0 || strpos($u, 'https://') === 0) {
            continue;
        }
        $u = $admin . ltrim((string) $u, '/');
    }
    unset($u);
    return array_slice(array_unique($out), 0, 6);
}

$conn = $conn ?? null;
$has_jwo = false;
$has_ji = false;
if ($conn) {
    $t1 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
    $t2 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_order_items'");
    $has_jwo = ($t1 && mysqli_num_rows($t1) > 0);
    $has_ji = ($t2 && mysqli_num_rows($t2) > 0);
    if ($t1) {
        mysqli_free_result($t1);
    }
    if ($t2) {
        mysqli_free_result($t2);
    }
}

$jwo_cols = [];
$ji_cols = [];
if ($has_jwo) {
    $cq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_jobwork_orders');
    if ($cq) {
        while ($r = mysqli_fetch_assoc($cq)) {
            $jwo_cols[$r['Field']] = true;
        }
        mysqli_free_result($cq);
    }
}
if ($has_ji) {
    $cq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_jobwork_order_items');
    if ($cq) {
        while ($r = mysqli_fetch_assoc($cq)) {
            $ji_cols[$r['Field']] = true;
        }
        mysqli_free_result($cq);
    }
}

$so_has_branch = false;
$so_has_ref = false;
$so_has_against = false;
$so_has_customer = false;
$so_has_department = false;
$ro_has_department = false;
$has_soi = false;
$has_ro = false;
$has_roi = false;
$has_rjwo = false;
$has_rji = false;
$soi_cols = [];
$roi_cols = [];
$rjwo_cols = [];
$rji_cols = [];
if ($conn) {
    foreach (['branch_id' => &$so_has_branch, 'ref_no' => &$so_has_ref, 'against_of' => &$so_has_against, 'customer_id' => &$so_has_customer] as $f => &$v) {
        $c = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE '$f'");
        $v = ($c && mysqli_num_rows($c) > 0);
        if ($c) {
            mysqli_free_result($c);
        }
    }
    unset($v);
    $c = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'department_id'");
    $so_has_department = ($c && mysqli_num_rows($c) > 0);
    if ($c) {
        mysqli_free_result($c);
    }
    $c = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_repair_orders LIKE 'department_id'");
    $ro_has_department = ($c && mysqli_num_rows($c) > 0);
    if ($c) {
        mysqli_free_result($c);
    }
    foreach (['tbl_sale_order_items' => &$has_soi, 'tbl_repair_orders' => &$has_ro, 'tbl_repair_order_items' => &$has_roi, 'tbl_repair_jobwork_orders' => &$has_rjwo, 'tbl_repair_jobwork_order_items' => &$has_rji] as $tbl => &$hv) {
        $t = @mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
        $hv = ($t && mysqli_num_rows($t) > 0);
        if ($t) {
            mysqli_free_result($t);
        }
    }
    unset($hv);
    if ($has_rjwo) {
        $cq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_repair_jobwork_orders');
        if ($cq) {
            while ($r = mysqli_fetch_assoc($cq)) {
                $rjwo_cols[$r['Field']] = true;
            }
            mysqli_free_result($cq);
        }
    }
    if ($has_rji) {
        $cq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_repair_jobwork_order_items');
        if ($cq) {
            while ($r = mysqli_fetch_assoc($cq)) {
                $rji_cols[$r['Field']] = true;
            }
            mysqli_free_result($cq);
        }
    }
    if ($has_soi) {
        $cq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_sale_order_items');
        if ($cq) {
            while ($r = mysqli_fetch_assoc($cq)) {
                $soi_cols[$r['Field']] = true;
            }
            mysqli_free_result($cq);
        }
    }
    if ($has_roi) {
        $cq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_repair_order_items');
        if ($cq) {
            while ($r = mysqli_fetch_assoc($cq)) {
                $roi_cols[$r['Field']] = true;
            }
            mysqli_free_result($cq);
        }
    }
}

$has_inv_tbl = false;
if ($conn) {
    $ti = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_invoices'");
    $has_inv_tbl = ($ti && mysqli_num_rows($ti) > 0);
    if ($ti) {
        mysqli_free_result($ti);
    }
}

$search = isset($_GET['search']) ? esc($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? esc($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? esc($_GET['to_date']) : '';
$status_filter = isset($_GET['status']) ? esc($_GET['status']) : '';
$jw_no = isset($_GET['jw_no']) ? esc($_GET['jw_no']) : '';

$filter_count = 0;
if ($from_date !== '') {
    $filter_count++;
}
if ($to_date !== '') {
    $filter_count++;
}
if ($status_filter !== '') {
    $filter_count++;
}
if ($jw_no !== '') {
    $filter_count++;
}
if ($search !== '') {
    $filter_count++;
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 25;
$per_page = max(10, min(100, $per_page));
$page = max(1, $page);

$all_rows = [];
$total_records = 0;

if ($has_jwo && $has_ji && $conn && function_exists('getList')) {
    $tag_expr = "COALESCE(NULLIF(TRIM(ji.barcode),''), '')";
    if (!empty($ji_cols['barcode_no'])) {
        $tag_expr = "COALESCE(NULLIF(TRIM(ji.barcode_no),''), NULLIF(TRIM(ji.barcode),''), '')";
    }

    $mfg_sel = !empty($jwo_cols['manufacturing_time_seconds']) ? 'j.manufacturing_time_seconds' : '0';
    $prio_sel = !empty($jwo_cols['priority']) ? 'j.priority' : "'Medium'";
    $dept_join = '';
    $dept_sel = 'NULL AS current_dept';
    if (!empty($jwo_cols['department_id'])) {
        $dept_join = ' LEFT JOIN tbl_departments d ON d.id = j.department_id ';
        $dept_sel = 'd.dept_name AS current_dept';
    }
    $assign_join = '';
    $assign_sel = 'NULL AS assign_name';
    if (!empty($jwo_cols['department_user_id'])) {
        $assign_join = ' LEFT JOIN tbl_customers cu ON cu.id = j.department_user_id ';
        $assign_sel = 'cu.name AS assign_name';
    }

    $acc_sel = 'NULL AS account_no';
    $acc_join = '';
    if ($so_has_customer) {
        $acc_join = ' LEFT JOIN tbl_customers cust ON cust.id = so.customer_id ';
        $acc_sel = 'cust.bank_account_no AS account_no';
    }

    $so_join = ' LEFT JOIN tbl_sale_orders so ON so.id = j.sale_order_id ';
    $against_expr = "'' AS against_ref";
    if ($so_has_ref || $so_has_against) {
        $parts = [];
        if ($so_has_ref) {
            $parts[] = "NULLIF(TRIM(so.ref_no),'')";
        }
        if ($so_has_against) {
            $parts[] = "NULLIF(TRIM(so.against_of),'')";
        }
        if (!empty($parts)) {
            $against_expr = 'COALESCE(' . implode(', ', $parts) . ", '') AS against_ref";
        }
    }

    $branch_join = '';
    $branch_sel = "'' AS branch_name";
    if ($so_has_branch) {
        $branch_join = ' LEFT JOIN tbl_branches br ON br.id = so.branch_id ';
        $branch_sel = 'IFNULL(br.name, \'\') AS branch_name';
    }

    $inv_join = '';
    $inv_sel = "'' AS jobwork_invoice_no";
    if ($has_inv_tbl) {
        $inv_join = ' LEFT JOIN tbl_jobwork_invoices jwi ON jwi.jobwork_order_id = j.id ';
        $inv_sel = 'IFNULL(jwi.invoice_no, \'\') AS jobwork_invoice_no';
    }

    $ji_img_sel = !empty($ji_cols['images']) ? 'ji.images AS ji_images' : 'NULL AS ji_images';
    $j_queue_img = !empty($jwo_cols['jobwork_queue_images']) ? 'j.jobwork_queue_images AS queue_images' : 'NULL AS queue_images';

    $where = ' WHERE 1=1 ';
    if ($from_date !== '') {
        $where .= " AND j.order_date >= '$from_date' ";
    }
    if ($to_date !== '') {
        $where .= " AND j.order_date <= '$to_date' ";
    }
    if ($status_filter !== '') {
        $where .= " AND LOWER(TRIM(j.status)) = LOWER('" . $status_filter . "') ";
    }
    if ($jw_no !== '') {
        $where .= " AND j.jobwork_no LIKE '%" . $jw_no . "%' ";
    }
    if ($search !== '') {
        $where .= " AND (j.jobwork_no LIKE '%" . $search . "%' OR j.sale_order_no LIKE '%" . $search . "%' OR j.customer_name LIKE '%" . $search . "%' OR ji.product_name LIKE '%" . $search . "%' OR ji.design_no LIKE '%" . $search . "%' OR ji.barcode LIKE '%" . $search . "%' OR pc.sku_code LIKE '%" . $search . "%') ";
    }
    // Job work orders created without a department — these appear here; department-assigned orders appear on manufacturing-process.php only.
    if (!empty($jwo_cols['department_id'])) {
        $where .= ' AND (j.department_id IS NULL OR j.department_id = 0) ';
    }

    $sql = "
        SELECT
            ji.id AS line_id,
            j.id AS jobwork_order_id,
            j.sale_order_id,
            j.jobwork_no,
            j.sale_order_no,
            j.customer_name,
            j.order_date,
            j.due_date,
            j.status AS jwo_status,
            $prio_sel AS priority,
            $mfg_sel AS manufacturing_time_seconds,
            ji.product_name,
            ji.design_no,
            $tag_expr AS tag_no,
            " . (!empty($ji_cols['status']) ? 'ji.status' : '1') . " AS item_active,
            $ji_img_sel,
            $j_queue_img,
            pc.sku_code AS rfid_code,
            $inv_sel,
            $against_expr,
            $dept_sel,
            $assign_sel,
            $acc_sel,
            $branch_sel,
            'jwo' AS list_source,
            0 AS repair_order_id,
            '' AS repair_order_no
        FROM tbl_jobwork_order_items ji
        INNER JOIN tbl_jobwork_orders j ON ji.jobwork_order_id = j.id
        $so_join
        $acc_join
        $branch_join
        $inv_join
        $dept_join
        $assign_join
        LEFT JOIN tbl_products p ON p.id = ji.product_id
        LEFT JOIN tbl_product_characteristics pc ON pc.id = ji.product_characteristic_id
        $where
        ORDER BY j.order_date DESC, j.id DESC, ji.id ASC
    ";

    $all_rows = getList($sql);
    if (!is_array($all_rows)) {
        $all_rows = [];
    }
}

// Repair Job Work Order lines (RJWO) — same outsource list; unassigned department when column exists
if ($has_rjwo && $has_rji && $conn && function_exists('getList')) {
    $rji_tag = "COALESCE(NULLIF(TRIM(rji.barcode),''), '')";
    if (!empty($rji_cols['barcode_no'])) {
        $rji_tag = "COALESCE(NULLIF(TRIM(rji.barcode_no),''), NULLIF(TRIM(rji.barcode),''), '')";
    }
    $prio_rj = !empty($rjwo_cols['priority']) ? 'rj.priority' : "'Medium'";
    $mfg_rj = !empty($rjwo_cols['manufacturing_time_seconds']) ? 'rj.manufacturing_time_seconds' : '0';
    $rj_dept_join = '';
    $rj_dept_sel = 'NULL AS current_dept';
    if (!empty($rjwo_cols['department_id'])) {
        $rj_dept_join = ' LEFT JOIN tbl_departments rd ON rd.id = rj.department_id ';
        $rj_dept_sel = 'rd.dept_name AS current_dept';
    }
    $rj_assign_join = '';
    $rj_assign_sel = 'NULL AS assign_name';
    if (!empty($rjwo_cols['department_user_id'])) {
        $rj_assign_join = ' LEFT JOIN tbl_customers rcu ON rcu.id = rj.department_user_id ';
        $rj_assign_sel = 'rcu.name AS assign_name';
    }
    $rj_queue_img = !empty($rjwo_cols['jobwork_queue_images']) ? 'rj.jobwork_queue_images AS queue_images' : 'NULL AS queue_images';
    $rji_img_sel = !empty($rji_cols['images']) ? 'rji.images AS ji_images' : 'NULL AS ji_images';
    $rji_status_sel = !empty($rji_cols['status']) ? 'rji.status' : '1';

    $ro_join_rj = '';
    $rj_against_expr = "'' AS against_ref";
    $rj_acc_join = '';
    $rj_acc_sel = 'NULL AS account_no';
    if ($has_ro) {
        $ro_join_rj = ' LEFT JOIN tbl_repair_orders ro ON ro.id = rj.repair_order_id ';
        $rj_against_expr = "COALESCE(NULLIF(TRIM(ro.ref_no),''), NULLIF(TRIM(ro.against_of),''), '') AS against_ref";
        $rcc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_repair_orders LIKE 'customer_id'");
        if ($rcc && mysqli_num_rows($rcc) > 0) {
            mysqli_free_result($rcc);
            $rj_acc_join = ' LEFT JOIN tbl_customers rcust ON rcust.id = ro.customer_id ';
            $rj_acc_sel = 'rcust.bank_account_no AS account_no';
        } elseif ($rcc) {
            mysqli_free_result($rcc);
        }
    }

    $rj_where = ' WHERE 1=1 ';
    // RJWO is tracked on this outsource list (not on manufacturing-process.php); do not hide when a dept label is set.
    if ($from_date !== '') {
        $rj_where .= " AND rj.order_date >= '$from_date' ";
    }
    if ($to_date !== '') {
        $rj_where .= " AND rj.order_date <= '$to_date' ";
    }
    if ($status_filter !== '') {
        $rj_where .= " AND LOWER(TRIM(IFNULL(rj.status,''))) = LOWER('" . $status_filter . "') ";
    }
    if ($jw_no !== '') {
        $rj_where .= " AND rj.jobwork_no LIKE '%" . $jw_no . "%' ";
    }
    if ($search !== '') {
        $rj_where .= " AND (rj.jobwork_no LIKE '%" . $search . "%' OR rj.repair_order_no LIKE '%" . $search . "%' OR rj.customer_name LIKE '%" . $search . "%' OR rji.product_name LIKE '%" . $search . "%' OR rji.design_no LIKE '%" . $search . "%' OR rji.barcode LIKE '%" . $search . "%' OR pc.sku_code LIKE '%" . $search . "%') ";
    }

    $sql_rj = "
        SELECT
            rji.id AS line_id,
            rj.id AS jobwork_order_id,
            0 AS sale_order_id,
            IFNULL(rj.jobwork_no,'') AS jobwork_no,
            IFNULL(rj.repair_order_no,'') AS sale_order_no,
            rj.customer_name,
            rj.order_date,
            rj.due_date,
            rj.status AS jwo_status,
            $prio_rj AS priority,
            $mfg_rj AS manufacturing_time_seconds,
            rji.product_name,
            rji.design_no,
            $rji_tag AS tag_no,
            $rji_status_sel AS item_active,
            $rji_img_sel,
            $rj_queue_img,
            pc.sku_code AS rfid_code,
            '' AS jobwork_invoice_no,
            $rj_against_expr,
            $rj_dept_sel,
            $rj_assign_sel,
            $rj_acc_sel,
            '' AS branch_name,
            'rjwo' AS list_source,
            rj.repair_order_id AS repair_order_id,
            IFNULL(rj.repair_order_no,'') AS repair_order_no
        FROM tbl_repair_jobwork_order_items rji
        INNER JOIN tbl_repair_jobwork_orders rj ON rji.repair_jobwork_order_id = rj.id
        $ro_join_rj
        $rj_acc_join
        $rj_dept_join
        $rj_assign_join
        LEFT JOIN tbl_products p ON p.id = rji.product_id
        LEFT JOIN tbl_product_characteristics pc ON pc.id = rji.product_characteristic_id
        $rj_where
        ORDER BY rj.order_date DESC, rj.id DESC, rji.id ASC
    ";
    $rj_rows = getList($sql_rj);
    if (is_array($rj_rows) && count($rj_rows) > 0) {
        $all_rows = array_merge($all_rows, $rj_rows);
    }
}

// Sale order lines: department not chosen on sale order (when column exists) and no jobwork order yet
if ($conn && function_exists('getList') && $has_soi) {
    $soi_tag = "COALESCE(NULLIF(TRIM(soi.barcode),''), '')";
    if (!empty($soi_cols['barcode_no'])) {
        $soi_tag = "COALESCE(NULLIF(TRIM(soi.barcode_no),''), NULLIF(TRIM(soi.barcode),''), '')";
    }
    $soi_img_sel = !empty($soi_cols['images']) ? 'soi.images AS ji_images' : 'NULL AS ji_images';
    $soi_status_sel = !empty($soi_cols['status']) ? 'soi.status' : '1';
    $so_branch_join = '';
    $so_branch_sel = "'' AS branch_name";
    if ($so_has_branch) {
        $so_branch_join = ' LEFT JOIN tbl_branches br ON br.id = so.branch_id ';
        $so_branch_sel = "IFNULL(br.name, '') AS branch_name";
    }
    $so_acc_join = '';
    $so_acc_sel = 'NULL AS account_no';
    if ($so_has_customer) {
        $so_acc_join = ' LEFT JOIN tbl_customers cust ON cust.id = so.customer_id ';
        $so_acc_sel = 'cust.bank_account_no AS account_no';
    }
    $so_against_expr = "'' AS against_ref";
    if ($so_has_ref || $so_has_against) {
        $parts = [];
        if ($so_has_ref) {
            $parts[] = "NULLIF(TRIM(so.ref_no),'')";
        }
        if ($so_has_against) {
            $parts[] = "NULLIF(TRIM(so.against_of),'')";
        }
        if (!empty($parts)) {
            $so_against_expr = 'COALESCE(' . implode(', ', $parts) . ", '') AS against_ref";
        }
    }
    $so_where = ' WHERE 1=1 ';
    if ($has_jwo) {
        $so_where .= ' AND NOT EXISTS (SELECT 1 FROM tbl_jobwork_orders j WHERE j.sale_order_id = so.id) ';
    }
    if ($so_has_department) {
        $so_where .= ' AND (so.department_id IS NULL OR so.department_id = 0) ';
    }
    if ($from_date !== '') {
        $so_where .= " AND so.order_date >= '$from_date' ";
    }
    if ($to_date !== '') {
        $so_where .= " AND so.order_date <= '$to_date' ";
    }
    if ($status_filter !== '') {
        $so_where .= " AND LOWER(TRIM(IFNULL(so.status,''))) = LOWER('" . $status_filter . "') ";
    }
    if ($search !== '') {
        $so_where .= " AND (so.order_no LIKE '%" . $search . "%' OR so.customer_name LIKE '%" . $search . "%' OR soi.product_name LIKE '%" . $search . "%' OR soi.design_no LIKE '%" . $search . "%' OR soi.barcode LIKE '%" . $search . "%' OR pc.sku_code LIKE '%" . $search . "%') ";
    }
    $sql_so = "
        SELECT
            soi.id AS line_id,
            0 AS jobwork_order_id,
            so.id AS sale_order_id,
            '' AS jobwork_no,
            IFNULL(so.order_no,'') AS sale_order_no,
            so.customer_name,
            so.order_date,
            so.due_date,
            IFNULL(so.status,'draft') AS jwo_status,
            'Medium' AS priority,
            0 AS manufacturing_time_seconds,
            soi.product_name,
            soi.design_no,
            $soi_tag AS tag_no,
            $soi_status_sel AS item_active,
            $soi_img_sel,
            NULL AS queue_images,
            pc.sku_code AS rfid_code,
            '' AS jobwork_invoice_no,
            $so_against_expr,
            NULL AS current_dept,
            NULL AS assign_name,
            $so_acc_sel,
            $so_branch_sel,
            'sale' AS list_source,
            0 AS repair_order_id,
            '' AS repair_order_no
        FROM tbl_sale_order_items soi
        INNER JOIN tbl_sale_orders so ON so.id = soi.order_id
        $so_acc_join
        $so_branch_join
        LEFT JOIN tbl_products p ON p.id = soi.product_id
        LEFT JOIN tbl_product_characteristics pc ON pc.id = soi.product_characteristic_id
        $so_where
        ORDER BY so.order_date DESC, so.id DESC, soi.id ASC
    ";
    $sale_rows = getList($sql_so);
    if (is_array($sale_rows) && count($sale_rows) > 0) {
        $all_rows = array_merge($all_rows, $sale_rows);
    }
}

// Repair order lines: department not chosen (when column exists) and repair jobwork not created yet
if ($conn && function_exists('getList') && $has_roi && $has_ro) {
    $roi_tag = "COALESCE(NULLIF(TRIM(roi.barcode),''), '')";
    if (!empty($roi_cols['barcode_no'])) {
        $roi_tag = "COALESCE(NULLIF(TRIM(roi.barcode_no),''), NULLIF(TRIM(roi.barcode),''), '')";
    }
    $roi_img_sel = !empty($roi_cols['images']) ? 'roi.images AS ji_images' : 'NULL AS ji_images';
    $roi_status_sel = !empty($roi_cols['status']) ? 'roi.status' : '1';
    $ro_acc_join = '';
    $ro_acc_sel = 'NULL AS account_no';
    $rcc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_repair_orders LIKE 'customer_id'");
    if ($rcc && mysqli_num_rows($rcc) > 0) {
        mysqli_free_result($rcc);
        $ro_acc_join = ' LEFT JOIN tbl_customers cust ON cust.id = ro.customer_id ';
        $ro_acc_sel = 'cust.bank_account_no AS account_no';
    } elseif ($rcc) {
        mysqli_free_result($rcc);
    }
    $ro_against_expr = "COALESCE(NULLIF(TRIM(ro.ref_no),''), NULLIF(TRIM(ro.against_of),''), '') AS against_ref";
    $ro_where = ' WHERE 1=1 ';
    if ($has_rjwo) {
        $ro_where .= ' AND NOT EXISTS (SELECT 1 FROM tbl_repair_jobwork_orders rj WHERE rj.repair_order_id = ro.id) ';
    }
    if ($ro_has_department) {
        $ro_where .= ' AND (ro.department_id IS NULL OR ro.department_id = 0) ';
    }
    if ($from_date !== '') {
        $ro_where .= " AND ro.order_date >= '$from_date' ";
    }
    if ($to_date !== '') {
        $ro_where .= " AND ro.order_date <= '$to_date' ";
    }
    if ($status_filter !== '') {
        $ro_where .= " AND LOWER(TRIM(IFNULL(ro.status,''))) = LOWER('" . $status_filter . "') ";
    }
    if ($search !== '') {
        $ro_where .= " AND (ro.order_no LIKE '%" . $search . "%' OR ro.customer_name LIKE '%" . $search . "%' OR roi.product_name LIKE '%" . $search . "%' OR roi.design_no LIKE '%" . $search . "%' OR roi.barcode LIKE '%" . $search . "%' OR pc.sku_code LIKE '%" . $search . "%') ";
    }
    $sql_ro = "
        SELECT
            roi.id AS line_id,
            0 AS jobwork_order_id,
            0 AS sale_order_id,
            '' AS jobwork_no,
            '' AS sale_order_no,
            ro.customer_name,
            ro.order_date,
            ro.due_date,
            IFNULL(ro.status,'draft') AS jwo_status,
            'Medium' AS priority,
            0 AS manufacturing_time_seconds,
            roi.product_name,
            roi.design_no,
            $roi_tag AS tag_no,
            $roi_status_sel AS item_active,
            $roi_img_sel,
            NULL AS queue_images,
            pc.sku_code AS rfid_code,
            '' AS jobwork_invoice_no,
            $ro_against_expr,
            NULL AS current_dept,
            NULL AS assign_name,
            $ro_acc_sel,
            '' AS branch_name,
            'repair' AS list_source,
            ro.id AS repair_order_id,
            IFNULL(ro.order_no,'') AS repair_order_no
        FROM tbl_repair_order_items roi
        INNER JOIN tbl_repair_orders ro ON ro.id = roi.order_id
        $ro_acc_join
        LEFT JOIN tbl_products p ON p.id = roi.product_id
        LEFT JOIN tbl_product_characteristics pc ON pc.id = roi.product_characteristic_id
        $ro_where
        ORDER BY ro.order_date DESC, ro.id DESC, roi.id ASC
    ";
    $repair_rows = getList($sql_ro);
    if (is_array($repair_rows) && count($repair_rows) > 0) {
        $all_rows = array_merge($all_rows, $repair_rows);
    }
}

usort($all_rows, function ($a, $b) {
    $ta = strtotime((string) ($a['order_date'] ?? ''));
    $tb = strtotime((string) ($b['order_date'] ?? ''));
    if ($ta === $tb) {
        $la = (string) ($a['list_source'] ?? 'jwo');
        $lb = (string) ($b['list_source'] ?? 'jwo');
        if ($la !== $lb) {
            return strcmp($la, $lb);
        }
        return ((int) ($b['line_id'] ?? 0)) <=> ((int) ($a['line_id'] ?? 0));
    }
    return $tb <=> $ta;
});
foreach ($all_rows as &$___jwm_row) {
    $___ls = $___jwm_row['list_source'] ?? 'jwo';
    $___jwm_row['row_uid'] = $___ls . '-' . (int) ($___jwm_row['line_id'] ?? 0);
}
unset($___jwm_row);

$jwm_can_list = ($has_jwo && $has_ji) || ($has_rjwo && $has_rji) || $has_soi || ($has_roi && $has_ro);

$total_records = count($all_rows);
$total_pages = $total_records > 0 ? (int) ceil($total_records / $per_page) : 1;
$page = min($page, max(1, $total_pages));
$offset = ($page - 1) * $per_page;
$page_rows = array_slice($all_rows, $offset, $per_page);

$base_web = isset($SiteUrl) ? rtrim((string) $SiteUrl, '/') : '';

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Jobwork Order Manufacturing List — <?php echo htmlspecialchars($Proj_Title); ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php'; ?>
</head>
<style>
body { background: #f4f6fb; }
.page-header-bar {
    background: linear-gradient(135deg, #5b4bce 0%, #7c5ba8 100%);
    color: #fff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    font-size: 13px;
    border-radius: 10px;
    margin: 16px 20px 0 20px;
}
.page-header-bar .ph-title {
    background: rgba(255,255,255,.15);
    padding: 6px 14px;
    border-radius: 8px;
}
.toolbar { background: #fff; padding: 12px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin: 12px 20px 0 20px; border-radius: 8px 8px 0 0; }
.toolbar-left, .toolbar-right { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.btn-filter { position: relative; background: #fff; border: 1px solid #e2e8f0; color: #64748b; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 12px; }
.filter-badge { position: absolute; top: -6px; right: -6px; background: #ef4444; color: #fff; font-size: 10px; min-width: 18px; height: 18px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
.btn-export { background: #5b4bce; border: none; color: #fff; padding: 6px 12px; border-radius: 8px; cursor: pointer; font-size: 12px; }
.toolbar-menu-wrap { position: relative; }
.toolbar-menu-wrap .dropdown-toggle::after { margin-left: 6px; }
.toolbar-btn { border: 1px solid #6f56d9; color: #4f46e5; background: #fff; border-radius: 8px; font-size: 12px; font-weight: 600; padding: 6px 12px; }
.toolbar-menu-wrap .dropdown-menu { min-width: 160px; border: 1px solid #e2e8f0; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12); border-radius: 8px; padding: 6px 0; }
.toolbar-menu-wrap.is-open .dropdown-menu { display: block; }
.table-container { background: #fff; margin: 0 20px; border-radius: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: auto; max-width: calc(100vw - 40px); }
.table { width: 100%; margin: 0; font-size: 11px; border-collapse: collapse; min-width: 1400px; }
.table thead th { position: sticky; top: 0; background: #f8fafc; z-index: 1; border: 1px solid #e2e8f0; padding: 8px 6px; white-space: nowrap; }
.table tbody td { padding: 6px; border: 1px solid #dee2e6; vertical-align: middle; text-align: center; }
.table tbody tr:hover { background: #f8fafc; }
.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; display: inline-block; }
.status-not-initiate, .status-draft { background: #6c757d; color: #fff; }
.status-processing { background: #11294b; color: #fff; }
.status-completed { background: #28a745; color: #fff; }
.btn-action { padding: 4px 8px; font-size: 11px; border-radius: 4px; cursor: pointer; margin: 1px; border: none; font-weight: 600; text-decoration: none; display: inline-block; }
.btn-catalogue { background: #e83e8c; color: #fff; }
.btn-invoice { background: #0d9488; color: #fff; }
.btn-print { background: #6366f1; color: #fff; }
.btn-open { background: #11294b; color: #fff; }
.action-cell-btns { display: inline-flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 4px; }
.img-thumb { width: 36px; height: 36px; object-fit: cover; border-radius: 4px; background: #f1f5f9; margin: 1px; }
.pagination-container { background: #fff; padding: 12px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin: 0 20px 20px 20px; border-radius: 0 0 8px 8px; flex-wrap: wrap; gap: 8px; }
.jwm-col-hidden { display: none !important; }
.th-sort-hint { font-size: 9px; color: #94a3b8; font-weight: 400; }
.btn-col-settings { width: 28px; height: 28px; padding: 0; border: none; background: transparent; color: #64748b; cursor: pointer; border-radius: 4px; vertical-align: middle; }
#columnSettingsModal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 1050; overflow: auto; }
#columnSettingsModal.show { display: block !important; }
#columnSettingsModal .modal-dialog { position: relative; margin: 1.75rem auto; max-width: 380px; }
#columnSettingsModal .modal-content { background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
.modal-backdrop { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45); z-index: 1040; }
.filter-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1060; align-items: center; justify-content: center; }
.filter-modal.active { display: flex; }
.filter-modal-content { background: #fff; border-radius: 8px; width: min(640px, calc(100vw - 32px)); max-height: 90vh; overflow: auto; }
.filter-modal-header { background: #5b4bce; color: #fff; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; }
.filter-modal-body { padding: 16px; }
.filter-modal-close { background: none; border: none; color: #fff; font-size: 22px; cursor: pointer; }
.text-left { text-align: left !important; }
.active-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.active-yes { background: #22c55e; }
.active-no { background: #cbd5e1; }
</style>
<body>
<?php include 'sidebar.php'; ?>
<div class="layout-content">
<div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">

    <div class="page-header-bar">
        <span class="ph-title">Manufacturing — unassigned dept. JWO · open SO · open RO</span>
        <a href="jobwork-order.php" class="btn btn-sm text-white border border-light" style="border-radius:8px;">Open Jobwork Order</a>
    </div>

    <div class="toolbar">
        <div class="toolbar-left">
            <button type="button" class="btn-filter" id="openFilterModal" title="Filters">
                <i class="feather icon-filter"></i>
                <?php if ($filter_count > 0): ?><span class="filter-badge"><?php echo (int) $filter_count; ?></span><?php endif; ?>
            </button>
            <button type="button" class="btn-filter" onclick="location.reload()" title="Refresh"><i class="feather icon-refresh-cw"></i></button>

            <div class="dropdown toolbar-menu-wrap">
                <button type="button" class="toolbar-btn dropdown-toggle js-toolbar-toggle" aria-expanded="false">Export</button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="#" id="btnExportCsv"><i class="feather icon-file-text mr-2"></i>CSV</a>
                </div>
            </div>

            <button type="button" class="btn-filter" id="btnColumnLayout" title="Column layout"><i class="feather icon-grid"></i></button>
        </div>
        <div class="toolbar-right">
            <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
                <?php
                foreach (['from_date', 'to_date', 'status', 'jw_no'] as $pk) {
                    if (isset($_GET[$pk]) && (string) $_GET[$pk] !== '') {
                        echo '<input type="hidden" name="' . htmlspecialchars($pk) . '" value="' . htmlspecialchars((string) $_GET[$pk]) . '">';
                    }
                }
                ?>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>" style="width:200px;">
                <input type="hidden" name="per_page" value="<?php echo (int) $per_page; ?>">
                <button type="submit" class="btn-export">Search</button>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table class="table table-bordered table-hover" id="jwmTable">
            <thead>
                <tr>
                    <th data-col="check"><input type="checkbox" id="selectAll"></th>
                    <th data-col="active">active <span class="th-sort-hint">●</span></th>
                    <th data-col="jobwork_no">Jobwork Order No. <span class="th-sort-hint">↕</span></th>
                    <th data-col="jobwork_invoice">Jobwork Invoice No. <span class="th-sort-hint">↕</span></th>
                    <th data-col="sale_order_no">SO / RO No. <span class="th-sort-hint">↕</span></th>
                    <th data-col="against_ref">Against Ref. No. <span class="th-sort-hint">↕</span></th>
                    <th data-col="spent_time">Spent Time <span class="th-sort-hint">↕</span></th>
                    <th data-col="rfid">RFID Code <span class="th-sort-hint">↕</span></th>
                    <th data-col="customer">Customer Name <span class="th-sort-hint">↕</span></th>
                    <th data-col="assign">Assign To <span class="th-sort-hint">↕</span></th>
                    <th data-col="account_no">Account No <span class="th-sort-hint">↕</span></th>
                    <th data-col="short_desc">Short Description <span class="th-sort-hint">↕</span></th>
                    <th data-col="order_dt">Order Dt. <span class="th-sort-hint">↕</span></th>
                    <th data-col="due_dt">Due Date <span class="th-sort-hint">↕</span></th>
                    <th data-col="branch">Branch Name <span class="th-sort-hint">↕</span></th>
                    <th data-col="dept">Current Dept. <span class="th-sort-hint">↕</span></th>
                    <th data-col="tag_no">Tag No. <span class="th-sort-hint">↕</span></th>
                    <th data-col="design_no">Design No. <span class="th-sort-hint">↕</span></th>
                    <th data-col="status">Status <span class="th-sort-hint">↕</span></th>
                    <th data-col="priority">Priority <span class="th-sort-hint">↕</span></th>
                    <th data-col="create_catelog">createCatelog</th>
                    <th data-col="invoice">Invoice</th>
                    <th data-col="print">Print</th>
                    <th data-col="image_urls">imageUrls</th>
                    <th data-col="action" class="text-nowrap">
                        action
                        <button type="button" class="btn-col-settings ml-1" id="btnColumnSettings" title="Columns"><i class="feather icon-settings"></i></button>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$jwm_can_list): ?>
                <tr><td colspan="25" class="text-center text-muted py-5">No list sources available. Add jobwork tables (<code>create_tbl_jobwork_orders.sql</code>) and/or sale / repair order tables.</td></tr>
            <?php elseif (empty($page_rows)): ?>
                <tr><td colspan="25" class="text-center text-muted py-5">No Rows To Show</td></tr>
            <?php else: ?>
                <?php foreach ($page_rows as $row):
                    $jid = (int) ($row['jobwork_order_id'] ?? 0);
                    $soid = (int) ($row['sale_order_id'] ?? 0);
                    $rid = (int) ($row['repair_order_id'] ?? 0);
                    $ls = (string) ($row['list_source'] ?? 'jwo');
                    $spent = jwm_format_spent_time($row['manufacturing_time_seconds'] ?? 0);
                    $od = !empty($row['order_date']) ? date('d-m-Y', strtotime($row['order_date'])) : '—';
                    $dd = !empty($row['due_date']) ? date('d-m-Y', strtotime($row['due_date'])) : '—';
                    $st = strtolower(trim((string) ($row['jwo_status'] ?? 'draft')));
                    $status_class = 'status-draft';
                    $status_label = ucfirst($st ?: 'draft');
                    if ($st === 'completed') { $status_class = 'status-completed'; }
                    elseif ($st === 'processing') { $status_class = 'status-processing'; }
                    elseif ($st === 'draft' || $st === '') { $status_class = 'status-not-initiate'; $status_label = 'Draft'; }

                    $ia = isset($row['item_active']) ? (int) $row['item_active'] : 1;
                    $active_ok = ($ia === 1);
                    $imgs = jwm_parse_image_urls($row['ji_images'] ?? '', $base_web);
                    if (empty($imgs)) {
                        $imgs = jwm_parse_image_urls($row['queue_images'] ?? '', $base_web);
                    }
                ?>
                <tr>
                    <td data-col="check"><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['row_uid'] ?? (($row['list_source'] ?? 'jwo') . '-' . (int) ($row['line_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>"></td>
                    <td data-col="active" title="<?php echo $active_ok ? 'Active' : 'Inactive'; ?>"><span class="active-dot <?php echo $active_ok ? 'active-yes' : 'active-no'; ?>"></span></td>
                    <td data-col="jobwork_no">
                        <?php if ($ls === 'jwo' && $jid > 0): ?>
                            <a href="jobwork-order.php?id=<?php echo $jid; ?>"><?php echo htmlspecialchars($row['jobwork_no'] ?? '—'); ?></a>
                        <?php elseif ($ls === 'rjwo' && $jid > 0): ?>
                            <a href="repair-job-work-order.php?id=<?php echo $jid; ?>"><?php echo htmlspecialchars($row['jobwork_no'] ?? '—'); ?></a>
                        <?php elseif ($ls === 'sale'): ?>
                            <span class="badge badge-secondary" style="font-size:9px;vertical-align:middle;">SO</span> <span class="text-muted">Pending JWO</span>
                        <?php elseif ($ls === 'repair'): ?>
                            <span class="badge badge-info" style="font-size:9px;vertical-align:middle;">RO</span> <span class="text-muted">Pending RJWO</span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td data-col="jobwork_invoice"><?php echo htmlspecialchars(!empty($row['jobwork_invoice_no']) ? (string) $row['jobwork_invoice_no'] : '—'); ?></td>
                    <td data-col="sale_order_no"><?php echo htmlspecialchars(($ls === 'repair' || $ls === 'rjwo') ? (string) ($row['repair_order_no'] ?? $row['sale_order_no'] ?? '—') : (string) ($row['sale_order_no'] ?? '—')); ?></td>
                    <td data-col="against_ref" class="text-left"><?php echo htmlspecialchars(!empty($row['against_ref']) ? (string) $row['against_ref'] : '—'); ?></td>
                    <td data-col="spent_time"><?php echo htmlspecialchars($spent); ?></td>
                    <td data-col="rfid"><?php echo htmlspecialchars(!empty($row['rfid_code']) ? (string) $row['rfid_code'] : '—'); ?></td>
                    <td data-col="customer" class="text-left"><?php echo htmlspecialchars($row['customer_name'] ?? '—'); ?></td>
                    <td data-col="assign"><?php echo htmlspecialchars(!empty($row['assign_name']) ? (string) $row['assign_name'] : '—'); ?></td>
                    <td data-col="account_no"><?php echo htmlspecialchars(!empty($row['account_no']) ? (string) $row['account_no'] : '—'); ?></td>
                    <td data-col="short_desc" class="text-left"><?php echo htmlspecialchars($row['product_name'] ?? '—'); ?></td>
                    <td data-col="order_dt"><?php echo htmlspecialchars($od); ?></td>
                    <td data-col="due_dt"><?php echo htmlspecialchars($dd); ?></td>
                    <td data-col="branch"><?php echo htmlspecialchars(!empty($row['branch_name']) ? (string) $row['branch_name'] : '—'); ?></td>
                    <td data-col="dept"><?php echo htmlspecialchars(!empty($row['current_dept']) ? (string) $row['current_dept'] : '—'); ?></td>
                    <td data-col="tag_no"><?php echo htmlspecialchars(!empty($row['tag_no']) ? (string) $row['tag_no'] : '—'); ?></td>
                    <td data-col="design_no"><?php echo htmlspecialchars(!empty($row['design_no']) ? (string) $row['design_no'] : '—'); ?></td>
                    <td data-col="status"><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></span></td>
                    <td data-col="priority"><?php echo htmlspecialchars($row['priority'] ?? 'Medium'); ?></td>
                    <td data-col="create_catelog">
                        <button type="button" class="btn-action btn-catalogue" title="Create catalogue">createCatelog</button>
                    </td>
                    <td data-col="invoice">
                        <?php if ($jid > 0 && $ls === 'jwo'): ?>
                        <a class="btn-action btn-invoice" href="jobwork-invoice.php?Jobwork_order_id=<?php echo $jid; ?>">Invoice</a>
                        <?php elseif ($jid > 0 && $ls === 'rjwo'): ?>
                        <a class="btn-action btn-invoice" href="jobwork-invoice.php?repair_jobwork_order_id=<?php echo $jid; ?>">Create Invoice</a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-col="print">
                        <?php if ($jid > 0 && $ls === 'jwo'): ?>
                        <a class="btn-action btn-print" target="_blank" href="manufacturing-jobwork-slip-print.php?id=<?php echo $jid; ?>">Print</a>
                        <?php elseif ($jid > 0 && $ls === 'rjwo'): ?>
                        <a class="btn-action btn-print" target="_blank" href="jobwork-order-print.php?rjwo_id=<?php echo $jid; ?>">Print</a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-col="image_urls">
                        <?php if (!empty($imgs)): ?>
                            <?php foreach ($imgs as $iu): ?>
                                <img src="<?php echo htmlspecialchars($iu); ?>" alt="" class="img-thumb" loading="lazy">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-col="action">
                        <div class="action-cell-btns">
                            <?php if ($ls === 'jwo' && $jid > 0): ?>
                            <a class="btn-action btn-open" href="jobwork-order.php?id=<?php echo $jid; ?>">JWO</a>
                            <?php if ($soid > 0): ?>
                            <a class="btn-action btn-open" href="sale-order.php?id=<?php echo $soid; ?>" title="Sale order">SO</a>
                            <?php endif; ?>
                            <?php elseif ($ls === 'rjwo' && $jid > 0): ?>
                            <a class="btn-action btn-open" href="repair-job-work-order.php?id=<?php echo $jid; ?>" title="Repair job work order">RJWO</a>
                            <?php if ($rid > 0): ?>
                            <a class="btn-action btn-open" href="repair-order.php?id=<?php echo $rid; ?>" title="Repair order">RO</a>
                            <?php endif; ?>
                            <?php elseif ($ls === 'sale' && $soid > 0): ?>
                            <a class="btn-action btn-open" href="sale-order.php?id=<?php echo $soid; ?>" title="Sale order">SO</a>
                            <a class="btn-action btn-open" href="jobwork-order.php?sale_order_id=<?php echo $soid; ?>" title="Create job work order">JWO</a>
                            <?php elseif ($ls === 'repair' && $rid > 0): ?>
                            <a class="btn-action btn-open" href="repair-order.php?id=<?php echo $rid; ?>" title="Repair order">RO</a>
                            <a class="btn-action btn-open" href="jobwork-order.php?sale_order_id=<?php echo $rid; ?>&from_repair=1" title="Repair job work order">RJWO</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        <div class="pagination-info text-muted small">
            Showing <?php echo $total_records > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php
            $qbase = $_GET;
            unset($qbase['page']);
            $qpre = http_build_query($qbase);
            $qglue = $qpre ? $qpre . '&' : '';
            ?>
            <select class="form-control form-control-sm" style="width:auto" onchange="location.href='?'+'<?php echo htmlspecialchars($qglue, ENT_QUOTES); ?>'+'per_page='+this.value+'&page=1'">
                <option value="10" <?php echo $per_page === 10 ? 'selected' : ''; ?>>10</option>
                <option value="25" <?php echo $per_page === 25 ? 'selected' : ''; ?>>25</option>
                <option value="50" <?php echo $per_page === 50 ? 'selected' : ''; ?>>50</option>
                <option value="100" <?php echo $per_page === 100 ? 'selected' : ''; ?>>100</option>
            </select>
            <nav>
                <ul class="pagination mb-0 pagination-sm">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => 1])), ENT_QUOTES); ?>">&laquo; First</a></li>
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])), ENT_QUOTES); ?>">&lsaquo; Prev</a></li>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])), ENT_QUOTES); ?>">Next &rsaquo;</a></li>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => $total_pages])), ENT_QUOTES); ?>">Last &raquo;</a></li>
                </ul>
            </nav>
        </div>
    </div>

</div>
</div>

<div id="advancedFilterModal" class="filter-modal" aria-hidden="true">
    <div class="filter-modal-content" role="dialog">
        <div class="filter-modal-header">
            <strong>Advance Filter</strong>
            <button type="button" class="filter-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="filter-modal-body">
            <form method="get" id="advFilterForm">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <div class="form-group">
                    <label>Order date from / to</label>
                    <div class="d-flex" style="gap:8px;">
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($from_date); ?>">
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Jobwork Order No.</label>
                    <input type="text" name="jw_no" class="form-control form-control-sm" value="<?php echo htmlspecialchars($jw_no); ?>" placeholder="Contains…">
                </div>
                <div class="form-group">
                    <label>Status (jobwork)</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All</option>
                        <?php foreach (['draft', 'processing', 'completed'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php echo $status_filter === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Rows per page</label>
                    <select name="per_page" class="form-control form-control-sm" style="max-width:120px;">
                        <?php foreach ([10,25,50,100] as $pp): ?>
                        <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="job-work-order-manufacturing.php" class="btn btn-outline-secondary btn-sm ml-2">Clear</a>
            </form>
        </div>
    </div>
</div>

<div class="modal" id="columnSettingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Visible columns</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="max-height:60vh;overflow:auto;">
                <div id="columnSettingsList"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="columnSettingsApply">Apply</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var table = document.getElementById('jwmTable');
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(function(cb) { cb.checked = this.checked; }, this);
    });

    document.getElementById('openFilterModal')?.addEventListener('click', function() {
        var m = document.getElementById('advancedFilterModal');
        if (m) { m.classList.add('active'); m.setAttribute('aria-hidden', 'false'); }
    });
    document.querySelector('#advancedFilterModal .filter-modal-close')?.addEventListener('click', function() {
        var m = document.getElementById('advancedFilterModal');
        if (m) { m.classList.remove('active'); m.setAttribute('aria-hidden', 'true'); }
    });
    document.getElementById('advancedFilterModal')?.addEventListener('click', function(e) {
        if (e.target === this) { this.classList.remove('active'); this.setAttribute('aria-hidden', 'true'); }
    });

    document.getElementById('btnColumnLayout')?.addEventListener('click', function() {
        document.getElementById('btnColumnSettings')?.click();
    });

    var COLUMN_KEYS = [
        { key: 'check', label: 'Select' },
        { key: 'active', label: 'active' },
        { key: 'jobwork_no', label: 'Jobwork Order No.' },
        { key: 'jobwork_invoice', label: 'Jobwork Invoice No.' },
        { key: 'sale_order_no', label: 'SO / RO No.' },
        { key: 'against_ref', label: 'Against Ref. No.' },
        { key: 'spent_time', label: 'Spent Time' },
        { key: 'rfid', label: 'RFID Code' },
        { key: 'customer', label: 'Customer Name' },
        { key: 'assign', label: 'Assign To' },
        { key: 'account_no', label: 'Account No' },
        { key: 'short_desc', label: 'Short Description' },
        { key: 'order_dt', label: 'Order Dt.' },
        { key: 'due_dt', label: 'Due Date' },
        { key: 'branch', label: 'Branch Name' },
        { key: 'dept', label: 'Current Dept.' },
        { key: 'tag_no', label: 'Tag No.' },
        { key: 'design_no', label: 'Design No.' },
        { key: 'status', label: 'Status' },
        { key: 'priority', label: 'Priority' },
        { key: 'create_catelog', label: 'createCatelog' },
        { key: 'invoice', label: 'Invoice' },
        { key: 'print', label: 'Print' },
        { key: 'image_urls', label: 'imageUrls' },
        { key: 'action', label: 'action' }
    ];
    var STORAGE_KEY = 'jwm_visible_cols';

    function getStored() {
        try {
            var s = localStorage.getItem(STORAGE_KEY);
            if (s) return JSON.parse(s);
        } catch (e) {}
        return null;
    }
    function setStored(o) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(o)); } catch (e) {}
    }
    function applyCols(map) {
        if (!table) return;
        COLUMN_KEYS.forEach(function(c) {
            var show = map[c.key] !== false;
            table.querySelectorAll('th[data-col="' + c.key + '"], td[data-col="' + c.key + '"]').forEach(function(el) {
                el.classList.toggle('jwm-col-hidden', !show);
            });
        });
    }

    function showModal() {
        var modal = document.getElementById('columnSettingsModal');
        var list = document.getElementById('columnSettingsList');
        if (!modal || !list) return;
        var stored = getStored() || {};
        list.innerHTML = '';
        COLUMN_KEYS.forEach(function(c) {
            var on = stored[c.key] !== false;
            var div = document.createElement('div');
            div.className = 'form-check mb-1';
            div.innerHTML = '<input type="checkbox" class="form-check-input col-visibility-cb" id="cc_' + c.key + '" data-col="' + c.key + '" ' + (on ? 'checked' : '') + '><label class="form-check-label" for="cc_' + c.key + '">' + c.label + '</label>';
            list.appendChild(div);
        });
        modal.classList.add('show');
        modal.style.display = 'block';
        var bd = document.getElementById('jwmColBackdrop');
        if (!bd) {
            bd = document.createElement('div');
            bd.className = 'modal-backdrop fade show';
            bd.id = 'jwmColBackdrop';
            document.body.appendChild(bd);
        }
        bd.onclick = hideModal;
    }
    function hideModal() {
        var modal = document.getElementById('columnSettingsModal');
        if (modal) { modal.classList.remove('show'); modal.style.display = 'none'; }
        var bd = document.getElementById('jwmColBackdrop');
        if (bd) bd.remove();
    }

    document.getElementById('btnColumnSettings')?.addEventListener('click', function(e) { e.preventDefault(); showModal(); });
    document.getElementById('columnSettingsApply')?.addEventListener('click', function() {
        var vis = {};
        document.querySelectorAll('#columnSettingsModal .col-visibility-cb').forEach(function(cb) {
            vis[cb.getAttribute('data-col')] = !!cb.checked;
        });
        if (!Object.keys(vis).some(function(k) { return vis[k]; })) {
            alert('Keep at least one column visible.');
            return;
        }
        setStored(vis);
        applyCols(vis);
        hideModal();
    });
    document.querySelectorAll('#columnSettingsModal .close, #columnSettingsModal [data-dismiss="modal"]').forEach(function(b) {
        b.addEventListener('click', hideModal);
    });

    var st = getStored();
    if (st && typeof st === 'object' && Object.keys(st).some(function(k) { return st[k]; })) {
        applyCols(st);
    }

    document.getElementById('btnExportCsv')?.addEventListener('click', function(e) {
        e.preventDefault();
        if (!table) return;
        var rows = table.querySelectorAll('tr');
        var lines = [];
        rows.forEach(function(tr) {
            var cells = tr.querySelectorAll('th, td');
            var parts = [];
            cells.forEach(function(cell) {
                if (cell.classList.contains('jwm-col-hidden')) return;
                parts.push('"' + (cell.innerText || '').replace(/"/g, '""').trim() + '"');
            });
            if (parts.length) lines.push(parts.join(','));
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'jobwork-manufacturing-list.csv';
        a.click();
        URL.revokeObjectURL(a.href);
    });

    (function toolbarDd() {
        var wraps = Array.from(document.querySelectorAll('.toolbar-menu-wrap'));
        if (!wraps.length) return;
        function closeAll(ex) {
            wraps.forEach(function(w) {
                if (ex && w === ex) return;
                w.classList.remove('is-open');
                var b = w.querySelector('.js-toolbar-toggle');
                if (b) b.setAttribute('aria-expanded', 'false');
            });
        }
        wraps.forEach(function(wrap) {
            var btn = wrap.querySelector('.js-toolbar-toggle');
            if (!btn) return;
            btn.addEventListener('click', function(ev) {
                ev.preventDefault(); ev.stopPropagation();
                var open = !wrap.classList.contains('is-open');
                closeAll(wrap);
                wrap.classList.toggle('is-open', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
        document.addEventListener('click', function() { closeAll(null); });
    })();
})();
</script>
</body>
</html>
