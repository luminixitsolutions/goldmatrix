<?php
/**
 * Manufacturing Outsource — full job work order lines (Jewelsteps-style list).
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/jwm_material_links.php';
require_once __DIR__ . '/includes/jwm_list_helpers.php';
require_once __DIR__ . '/includes/manufacturing_outsource_memo_list.php';

$conn = $conn ?? null;

$active_tab = isset($_GET['tab']) ? trim((string) $_GET['tab']) : 'outsource';
if ($active_tab !== 'memo') {
    $active_tab = 'outsource';
}
$memo_tab_label = 'Memo In & Out';

/** Preserve filters when switching tabs or departments. */
$mo_build_query = static function (array $overrides = []) {
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return http_build_query($q);
};

$departments = [];
$tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_departments'");
if ($tbl && mysqli_num_rows($tbl) > 0) {
    mysqli_free_result($tbl);
    $departments = function_exists('getList') ? getList("SELECT id, dept_name FROM tbl_departments WHERE status = 1 ORDER BY dept_name ASC") : [];
}
if (!is_array($departments)) {
    $departments = [];
}

$job_worker_type_id = 0;
$jw_result = @mysqli_query($conn, "SELECT id FROM tbl_customer_types WHERE LOWER(name) = 'job worker' AND status = 1 LIMIT 1");
if ($jw_result && mysqli_num_rows($jw_result) > 0) {
    $jw_row = mysqli_fetch_assoc($jw_result);
    $job_worker_type_id = (int) $jw_row['id'];
    mysqli_free_result($jw_result);
} elseif ($jw_result) {
    mysqli_free_result($jw_result);
}

$department_users = [];
foreach ($departments as $dept) {
    $dept_id = (int) $dept['id'];
    $department_users[$dept_id] = [];
    $map_tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_department_user_map'");
    if ($map_tbl && mysqli_num_rows($map_tbl) > 0) {
        mysqli_free_result($map_tbl);
        $users_query = "
            SELECT c.id, c.name
            FROM tbl_customers c
            INNER JOIN tbl_department_user_map dum ON c.id = dum.user_id AND dum.status = 1
            WHERE dum.department_id = $dept_id AND c.status = 1
            " . ($job_worker_type_id > 0 ? "AND c.customer_type_id = $job_worker_type_id" : '') . "
            ORDER BY c.name ASC
        ";
        $users_result = @mysqli_query($conn, $users_query);
        if ($users_result) {
            while ($user_row = mysqli_fetch_assoc($users_result)) {
                $department_users[$dept_id][] = $user_row;
            }
            mysqli_free_result($users_result);
        }
    }
}

$has_jwo = false;
$has_ji = false;
$has_rjwo = false;
$has_rji = false;
if ($conn) {
    foreach (['tbl_jobwork_orders' => &$has_jwo, 'tbl_jobwork_order_items' => &$has_ji, 'tbl_repair_jobwork_orders' => &$has_rjwo, 'tbl_repair_jobwork_order_items' => &$has_rji] as $t => &$hv) {
        $tq = @mysqli_query($conn, "SHOW TABLES LIKE '$t'");
        $hv = ($tq && mysqli_num_rows($tq) > 0);
        if ($tq) {
            mysqli_free_result($tq);
        }
    }
    unset($hv);
}

$jwo_cols = [];
$ji_cols = [];
$rjwo_cols = [];
$rji_cols = [];
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

$so_has_branch = false;
$so_has_ref = false;
$so_has_against = false;
$so_has_customer = false;
$soi_cols = [];
$roi_cols = [];
$has_soi = false;
$has_roi = false;
$has_ro = false;
$has_mi = false;
$has_mr = false;
$has_rmi = false;
$has_rmr = false;
$has_inv_tbl = false;
$has_activity = false;
$has_stock_journal = false;

if ($conn) {
    foreach (['branch_id' => &$so_has_branch, 'ref_no' => &$so_has_ref, 'against_of' => &$so_has_against, 'customer_id' => &$so_has_customer] as $f => &$v) {
        $c = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE '$f'");
        $v = ($c && mysqli_num_rows($c) > 0);
        if ($c) {
            mysqli_free_result($c);
        }
    }
    unset($v);
    foreach (['tbl_sale_order_items' => &$has_soi, 'tbl_repair_orders' => &$has_ro, 'tbl_repair_order_items' => &$has_roi] as $tbl => &$hv) {
        $t = @mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
        $hv = ($t && mysqli_num_rows($t) > 0);
        if ($t) {
            mysqli_free_result($t);
        }
    }
    unset($hv);
    foreach (['tbl_material_issues' => &$has_mi, 'tbl_material_receives' => &$has_mr, 'tbl_repair_material_issues' => &$has_rmi, 'tbl_repair_material_receives' => &$has_rmr, 'tbl_jobwork_invoices' => &$has_inv_tbl, 'tbl_jobwork_queue_activity' => &$has_activity, 'tbl_stock_journal' => &$has_stock_journal] as $tbl => &$hv) {
        $t = @mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
        $hv = ($t && mysqli_num_rows($t) > 0);
        if ($t) {
            mysqli_free_result($t);
        }
    }
    unset($hv);
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

$search = isset($_GET['search']) ? esc($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? esc($_GET['from_date']) : '';
$to_date = isset($_GET['to_date']) ? esc($_GET['to_date']) : '';
$status_filter = isset($_GET['status']) ? esc($_GET['status']) : '';
$jw_no = isset($_GET['jw_no']) ? esc($_GET['jw_no']) : '';
$filter_dept_id = isset($_GET['dept_id']) ? (int) $_GET['dept_id'] : 0;
$filter_user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$filter_count = 0;
foreach ([$from_date, $to_date, $status_filter, $jw_no, $search] as $fv) {
    if ($fv !== '') {
        $filter_count++;
    }
}
if ($filter_dept_id > 0) {
    $filter_count++;
}
if ($filter_user_id > 0) {
    $filter_count++;
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 25;
$per_page = max(10, min(200, $per_page));
$page = max(1, $page);

$all_rows = [];

if ($has_jwo && $has_ji && $conn && function_exists('getList')) {
    $tag_expr = "COALESCE(NULLIF(TRIM(ji.barcode),''), '')";
    if (!empty($ji_cols['barcode_no'])) {
        $tag_expr = "COALESCE(NULLIF(TRIM(ji.barcode_no),''), NULLIF(TRIM(ji.barcode),''), '')";
    }

    $prio_sel = !empty($jwo_cols['priority']) ? 'j.priority' : "'Medium'";
    $dept_join = '';
    $dept_sel = 'NULL AS current_dept';
    $dept_id_sel = '0 AS department_id';
    if (!empty($jwo_cols['department_id'])) {
        $dept_join = ' LEFT JOIN tbl_departments d ON d.id = j.department_id ';
        $dept_sel = 'd.dept_name AS current_dept';
        $dept_id_sel = 'IFNULL(j.department_id, 0) AS department_id';
    }
    $assign_join = '';
    $assign_sel = 'NULL AS assign_name';
    $user_id_sel = '0 AS department_user_id';
    if (!empty($jwo_cols['department_user_id'])) {
        $assign_join = ' LEFT JOIN tbl_customers cu ON cu.id = j.department_user_id ';
        $assign_sel = 'cu.name AS assign_name';
        $user_id_sel = 'IFNULL(j.department_user_id, 0) AS department_user_id';
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

    $gross_sel = !empty($ji_cols['gross_weight']) ? 'IFNULL(ji.gross_weight, 0)' : '0';

    $mi_sel = "'' AS material_issue_nos";
    if ($has_mi) {
        $mi_sel = "(SELECT GROUP_CONCAT(DISTINCT mi.material_issue_no ORDER BY mi.id DESC SEPARATOR ', ')
            FROM tbl_material_issues mi WHERE mi.sale_order_id = j.sale_order_id AND j.sale_order_id > 0) AS material_issue_nos";
    }
    $mr_sel = "'' AS material_receive_nos";
    if ($has_mr) {
        $mr_sel = "(SELECT GROUP_CONCAT(DISTINCT mr.material_receive_no ORDER BY mr.id DESC SEPARATOR ', ')
            FROM tbl_material_receives mr WHERE mr.sale_order_id = j.sale_order_id AND j.sale_order_id > 0) AS material_receive_nos";
    }

    $stock_in_sel = '0 AS is_stock_in';
    if ($has_inv_tbl && $has_stock_journal) {
        $stock_in_sel = "(SELECT CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END
            FROM tbl_jobwork_invoices jwi2
            INNER JOIN tbl_stock_journal sj ON sj.status = 'active'
                AND sj.comment LIKE CONCAT('auragold_jwi|jwi_id=', jwi2.id, '|%')
            WHERE jwi2.jobwork_order_id = j.id) AS is_stock_in";
    }

    $current_user_sel = 'NULL AS current_user_name';
    if ($has_activity) {
        $current_user_sel = "(SELECT cu2.name FROM tbl_jobwork_queue_activity a
            LEFT JOIN tbl_customers cu2 ON cu2.id = COALESCE(NULLIF(a.to_user_id,0), NULLIF(a.from_user_id,0))
            WHERE a.jobwork_order_id = j.id ORDER BY a.id DESC LIMIT 1) AS current_user_name";
    }

    $ji_img_sel = !empty($ji_cols['images']) ? 'ji.images AS ji_images' : 'NULL AS ji_images';
    $j_sale_img_sel = jwm_sql_sale_order_item_images($tag_expr, !empty($soi_cols['images']), !empty($ji_cols['product_id']));
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
    if ($filter_dept_id > 0 && !empty($jwo_cols['department_id'])) {
        $where .= ' AND j.department_id = ' . (int) $filter_dept_id . ' ';
    }
    if ($filter_user_id > 0 && !empty($jwo_cols['department_user_id'])) {
        $where .= ' AND j.department_user_id = ' . (int) $filter_user_id . ' ';
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
            ji.product_name,
            ji.design_no,
            $tag_expr AS tag_no,
            $gross_sel AS gross_wt,
            " . (!empty($ji_cols['status']) ? 'ji.status' : '1') . " AS item_active,
            $ji_img_sel,
            $j_sale_img_sel,
            NULL AS repair_item_images,
            $j_queue_img,
            pc.sku_code AS rfid_code,
            $inv_sel,
            $against_expr,
            $dept_sel,
            $dept_id_sel,
            $user_id_sel,
            $assign_sel,
            $acc_sel,
            $branch_sel,
            $mi_sel,
            $mr_sel,
            $stock_in_sel,
            $current_user_sel,
            'Sale Order' AS source_label,
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

if ($has_rjwo && $has_rji && $conn && function_exists('getList')) {
    $rji_tag = "COALESCE(NULLIF(TRIM(rji.barcode),''), '')";
    if (!empty($rji_cols['barcode_no'])) {
        $rji_tag = "COALESCE(NULLIF(TRIM(rji.barcode_no),''), NULLIF(TRIM(rji.barcode),''), '')";
    }
    $prio_rj = !empty($rjwo_cols['priority']) ? 'rj.priority' : "'Medium'";
    $rj_dept_join = '';
    $rj_dept_sel = 'NULL AS current_dept';
    $rj_dept_id_sel = '0 AS department_id';
    if (!empty($rjwo_cols['department_id'])) {
        $rj_dept_join = ' LEFT JOIN tbl_departments rd ON rd.id = rj.department_id ';
        $rj_dept_sel = 'rd.dept_name AS current_dept';
        $rj_dept_id_sel = 'IFNULL(rj.department_id, 0) AS department_id';
    }
    $rj_assign_join = '';
    $rj_assign_sel = 'NULL AS assign_name';
    $rj_current_user_sel = 'NULL AS current_user_name';
    $rj_user_id_sel = '0 AS department_user_id';
    if (!empty($rjwo_cols['department_user_id'])) {
        $rj_assign_join = ' LEFT JOIN tbl_customers rcu ON rcu.id = rj.department_user_id ';
        $rj_assign_sel = 'rcu.name AS assign_name';
        $rj_current_user_sel = 'rcu.name AS current_user_name';
        $rj_user_id_sel = 'IFNULL(rj.department_user_id, 0) AS department_user_id';
    }
    $rj_queue_img = !empty($rjwo_cols['jobwork_queue_images']) ? 'rj.jobwork_queue_images AS queue_images' : 'NULL AS queue_images';
    $rji_img_sel = !empty($rji_cols['images']) ? 'rji.images AS ji_images' : 'NULL AS ji_images';
    $rj_repair_img_sel = jwm_sql_repair_order_item_images($rji_tag, !empty($roi_cols['images']), !empty($rji_cols['product_id']));
    $rji_status_sel = !empty($rji_cols['status']) ? 'rji.status' : '1';
    $rj_gross = !empty($rji_cols['gross_weight']) ? 'IFNULL(rji.gross_weight, 0)' : '0';

    $rj_mi_sel = "'' AS material_issue_nos";
    if ($has_rmi && $has_ro) {
        $rj_mi_sel = "(SELECT GROUP_CONCAT(DISTINCT rmi.material_issue_no ORDER BY rmi.id DESC SEPARATOR ', ')
            FROM tbl_repair_material_issues rmi WHERE rmi.repair_order_id = rj.repair_order_id AND rj.repair_order_id > 0) AS material_issue_nos";
    }
    $rj_mr_sel = "'' AS material_receive_nos";
    if ($has_rmr && $has_ro) {
        $rj_mr_sel = "(SELECT GROUP_CONCAT(DISTINCT rmr.material_receive_no ORDER BY rmr.id DESC SEPARATOR ', ')
            FROM tbl_repair_material_receives rmr WHERE rmr.repair_order_id = rj.repair_order_id AND rj.repair_order_id > 0) AS material_receive_nos";
    }

    $rj_where = ' WHERE 1=1 ';
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
    if ($filter_dept_id > 0 && !empty($rjwo_cols['department_id'])) {
        $rj_where .= ' AND rj.department_id = ' . (int) $filter_dept_id . ' ';
    }
    if ($filter_user_id > 0 && !empty($rjwo_cols['department_user_id'])) {
        $rj_where .= ' AND rj.department_user_id = ' . (int) $filter_user_id . ' ';
    }

    $ro_join_rj = $has_ro ? ' LEFT JOIN tbl_repair_orders ro ON ro.id = rj.repair_order_id ' : '';
    $rj_against_expr = $has_ro
        ? "COALESCE(NULLIF(TRIM(ro.ref_no),''), NULLIF(TRIM(ro.against_of),''), '') AS against_ref"
        : "'' AS against_ref";
    $rj_acc_join = '';
    $rj_acc_sel = 'NULL AS account_no';
    if ($has_ro) {
        $rcc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_repair_orders LIKE 'customer_id'");
        if ($rcc && mysqli_num_rows($rcc) > 0) {
            mysqli_free_result($rcc);
            $rj_acc_join = ' LEFT JOIN tbl_customers rcust ON rcust.id = ro.customer_id ';
            $rj_acc_sel = 'rcust.bank_account_no AS account_no';
        } elseif ($rcc) {
            mysqli_free_result($rcc);
        }
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
            rji.product_name,
            rji.design_no,
            $rji_tag AS tag_no,
            $rj_gross AS gross_wt,
            $rji_status_sel AS item_active,
            $rji_img_sel,
            NULL AS sale_item_images,
            $rj_repair_img_sel,
            $rj_queue_img,
            pc.sku_code AS rfid_code,
            '' AS jobwork_invoice_no,
            $rj_against_expr,
            $rj_dept_sel,
            $rj_dept_id_sel,
            $rj_user_id_sel,
            $rj_assign_sel,
            $rj_acc_sel,
            '' AS branch_name,
            $rj_mi_sel,
            $rj_mr_sel,
            0 AS is_stock_in,
            $rj_current_user_sel,
            'Repair Order' AS source_label,
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

usort($all_rows, function ($a, $b) {
    $ta = strtotime((string) ($a['order_date'] ?? ''));
    $tb = strtotime((string) ($b['order_date'] ?? ''));
    if ($ta === $tb) {
        return ((int) ($b['line_id'] ?? 0)) <=> ((int) ($a['line_id'] ?? 0));
    }
    return $tb <=> $ta;
});
foreach ($all_rows as &$___mo_row) {
    $___ls = $___mo_row['list_source'] ?? 'jwo';
    $___mo_row['row_uid'] = $___ls . '-' . (int) ($___mo_row['line_id'] ?? 0);
    if (empty($___mo_row['current_user_name']) && !empty($___mo_row['assign_name'])) {
        $___mo_row['current_user_name'] = $___mo_row['assign_name'];
    }
}
unset($___mo_row);

$mfg_can_list = ($has_jwo && $has_ji) || ($has_rjwo && $has_rji);
$total_records = count($all_rows);
$total_pages = $total_records > 0 ? (int) ceil($total_records / $per_page) : 1;
$page = min($page, max(1, $total_pages));
$offset = ($page - 1) * $per_page;
$page_rows = array_slice($all_rows, $offset, $per_page);

$memo_rows_all = [];
if ($active_tab === 'memo') {
    $memo_rows_all = mfg_outsource_load_memo_rows($conn, $filter_dept_id, $filter_user_id, $search);
}
$memo_total_records = count($memo_rows_all);
$memo_total_pages = $memo_total_records > 0 ? (int) ceil($memo_total_records / $per_page) : 1;
$memo_page = isset($_GET['memo_page']) ? (int) $_GET['memo_page'] : $page;
$memo_page = max(1, min($memo_page, max(1, $memo_total_pages)));
$memo_offset = ($memo_page - 1) * $per_page;
$memo_page_rows = array_slice($memo_rows_all, $memo_offset, $per_page);
$memo_columns = function_exists('mfg_memo_column_defs') ? mfg_memo_column_defs() : [];

$base_web = isset($SiteUrl) ? rtrim((string) $SiteUrl, '/') : '';
$page_title = function_exists('auragold_t') ? auragold_t('ord.manufacturing_outsource') : 'Manufacturing Outsource';
if ($page_title === 'ord.manufacturing_outsource') {
    $page_title = 'Manufacturing Outsource';
}

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title><?php echo htmlspecialchars($page_title); ?> — <?php echo htmlspecialchars($Proj_Title); ?></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php'; ?>
    <link rel="stylesheet" href="assets/css/mfg-pages-mobile.css">
</head>
<style>
/* Gold Matrix brand — navy + gold (matches manufacturing-process.php) */
:root {
    --gm-navy: #11294b;
    --gm-navy-dark: #0a1f36;
    --gm-navy-mid: #1a3a5c;
    --gm-navy-light: #2d4a6e;
    --gm-gold: #c9a24a;
    --gm-gold-light: #e8d48a;
    --gm-gold-pale: #faf6eb;
    --gm-gold-deep: #8b6914;
    --gm-surface: #f4f6f9;
    --gm-border: #d6dbea;
}
body.manufacturing-outsource-page { background: var(--gm-surface); }
.mo-shell {
    display: flex;
    height: calc(100vh - 58px);
    min-height: 480px;
    margin: 8px 12px 12px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--gm-border);
    background: #f7f8fc;
    box-shadow: 0 1px 4px rgba(17, 41, 75, 0.08);
}
.mo-dept-panel {
    width: 220px;
    flex-shrink: 0;
    border-right: 1px solid var(--gm-border);
    background: #f7f8fc;
    padding: 8px 8px 0;
}
.mo-dept-list-box {
    border: 1px solid var(--gm-border);
    border-radius: 10px;
    overflow: hidden;
    background: #f8f9fd;
    max-height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
}
.mo-dept-title {
    background: linear-gradient(90deg, var(--gm-navy-dark) 0%, var(--gm-navy) 55%, var(--gm-navy-mid) 100%);
    color: var(--gm-gold-light);
    height: 34px;
    padding: 0 12px;
    display: flex;
    align-items: center;
    font-size: 13px;
    font-weight: 700;
}
.mo-dept-list { list-style: none; margin: 0; padding: 4px 0 8px; overflow-y: auto; flex: 1; }
.mo-dept-list > li > a {
    display: flex;
    align-items: center;
    gap: 8px;
    justify-content: space-between;
    padding: 6px 10px;
    color: #4c5b7b;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}
.mo-dept-list > li > a:hover { background: var(--gm-gold-pale); color: var(--gm-navy); }
.mo-dept-list > li > a.active {
    background: rgba(201, 162, 74, 0.18);
    color: var(--gm-navy);
}
.mo-dept-list .arrow { color: #9ca8c3; font-size: 12px; margin-left: auto; }
.mo-dept-list li.open > a .arrow { transform: rotate(90deg); display: inline-block; }
.mo-dept-user-list { list-style: none; margin: 0; padding: 0; display: none; background: #f0f2fa; border-top: 1px solid #e2e6f0; }
.mo-dept-list li.open .mo-dept-user-list { display: block; }
.mo-dept-user-list a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px 6px 24px;
    font-size: 12px;
    font-weight: 500;
    color: #5a6a8a;
    text-decoration: none;
    border-bottom: 1px solid #e8ebf2;
}
.mo-dept-user-list a:hover { background: rgba(201, 162, 74, 0.12); color: var(--gm-navy); }
.mo-dept-user-list a.active { background: rgba(17, 41, 75, 0.1); color: var(--gm-navy); font-weight: 600; }
.mo-dept-user-list a i { color: #8a9ab8; font-size: 14px; }
.mo-dept-user-list a.active i, .mo-dept-user-list a:hover i { color: var(--gm-gold-deep); }
.mo-main { flex: 1; display: flex; flex-direction: column; min-width: 0; overflow: hidden; background: #fff; }
.mo-tabs {
    display: flex;
    gap: 6px;
    padding: 10px 14px 0;
    border-bottom: 1px solid var(--gm-border);
    background: linear-gradient(180deg, #fff 0%, var(--gm-gold-pale) 100%);
}
.mo-tab {
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 700;
    border-radius: 8px 8px 0 0;
    background: linear-gradient(180deg, var(--gm-navy-mid) 0%, var(--gm-navy) 100%);
    color: var(--gm-gold-light);
    border: 1px solid var(--gm-navy);
    text-decoration: none;
}
.mo-tab.secondary {
    background: #fff;
    color: #5a6a8a;
    border-color: var(--gm-border);
    font-weight: 600;
}
.mo-tab.secondary:hover { background: var(--gm-gold-pale); color: var(--gm-navy); }
.mo-tab.is-active { background: linear-gradient(180deg, var(--gm-navy-mid) 0%, var(--gm-navy) 100%); color: var(--gm-gold-light); border-color: var(--gm-navy); }
.mo-tab.secondary.is-active { background: linear-gradient(180deg, var(--gm-navy-mid) 0%, var(--gm-navy) 100%); color: var(--gm-gold-light); border-color: var(--gm-navy); }
.mo-panel { display: none; flex: 1 1 auto; flex-direction: column; min-height: 0; min-width: 0; width: 100%; overflow: hidden; }
.mo-panel.is-active { display: flex; }
.mo-table-wrap { flex: 1 1 auto; width: 100%; min-width: 0; overflow: auto; -webkit-overflow-scrolling: touch; }
#moTable { width: 100%; min-width: 2200px; }
.mo-memo-table { width: 100%; min-width: 1400px; table-layout: fixed; border-collapse: collapse; }
.mo-memo-table thead th,
.mo-memo-table tbody td { white-space: nowrap; padding: 8px 10px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; }
.mo-memo-table thead th { position: sticky; top: 0; z-index: 2; }
.mo-memo-table thead th.mo-memo-th-interactive { position: sticky; padding-right: 12px; }
.mo-memo-col-drag-handle {
    display: inline-block; cursor: grab; color: #94a3b8; font-size: 12px; line-height: 1;
    margin-right: 4px; vertical-align: middle; user-select: none;
}
.mo-memo-col-drag-handle:active { cursor: grabbing; }
.mo-memo-table thead th.mo-memo-th-dragging { opacity: 0.75; }
.mo-memo-table thead th.mo-memo-th-drag-over { box-shadow: inset 0 -3px 0 var(--gm-gold); background: #fdf8e8 !important; }
.mo-memo-col-resizer {
    position: absolute; top: 0; right: 0; width: 8px; height: 100%;
    cursor: col-resize; z-index: 4; user-select: none;
}
.mo-memo-col-resizer:hover { background: rgba(201, 162, 74, 0.25); }
body.mo-memo-col-resizing { cursor: col-resize !important; user-select: none !important; }
body.mo-memo-col-resizing * { cursor: col-resize !important; }
.mo-memo-table .mo-clarity-link { color: #1565c0; font-weight: 600; cursor: pointer; }
.mo-memo-table .mo-remark-cell { text-align: left !important; font-size: 10px; line-height: 1.3; white-space: normal; min-width: 120px; }
.mo-memo-wt-out { color: var(--gm-navy); font-weight: 700; }
.mo-memo-wt-in { color: #2e7d32; font-weight: 700; }
.mo-memo-wt-pending { color: #c62828; font-weight: 700; }
.mo-memo-status-closed { color: #2e7d32; font-weight: 600; }
.mo-memo-status-partial { color: #e65100; font-weight: 600; }
.mo-memo-status-out { color: var(--gm-navy); font-weight: 600; }
.mo-memo-status-in { color: #1565c0; font-weight: 600; }
.mo-flow-issue { color: var(--gm-navy); font-weight: 700; font-size: 9px; display: block; margin-bottom: 2px; }
.mo-flow-receive { color: #2e7d32; font-weight: 700; font-size: 9px; display: block; margin-bottom: 2px; }
.mo-toolbar {
    padding: 10px 14px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    border-bottom: 1px solid var(--gm-border);
    background: #fff;
}
.mo-toolbar-left, .mo-toolbar-right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.mo-btn-action {
    background: linear-gradient(180deg, var(--gm-navy-mid) 0%, var(--gm-navy) 100%);
    color: var(--gm-gold-light);
    border: 1px solid var(--gm-navy-dark);
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.mo-btn-action:hover { background: var(--gm-navy); color: #fff; }
.mo-btn-filter {
    position: relative;
    background: var(--gm-gold-pale);
    border: 1px solid rgba(17, 41, 75, 0.25);
    color: var(--gm-navy);
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
}
.mo-btn-filter:hover { border-color: var(--gm-gold); background: #fdf8e8; }
.mo-filter-badge {
    position: absolute; top: -6px; right: -6px;
    background: #c62828; color: #fff;
    font-size: 10px; min-width: 18px; height: 18px; border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center; font-weight: 700;
}
.mo-table-wrap { background: #fff; }
.mo-table { width: 100%; margin: 0; font-size: 11px; border-collapse: collapse; }
.mo-table thead th {
    position: sticky; top: 0; z-index: 2;
    background: linear-gradient(180deg, #f8fafc 0%, var(--gm-gold-pale) 100%);
    border: 1px solid var(--gm-border);
    padding: 8px 6px; white-space: nowrap;
    font-weight: 700; color: var(--gm-navy);
}
.mo-table tbody td { padding: 6px; border: 1px solid #dee2e6; vertical-align: middle; text-align: center; }
.mo-table tbody tr:hover { background: var(--gm-gold-pale); }
.mo-table a:not(.btn-mo) { color: var(--gm-navy); font-weight: 600; text-decoration: none; }
.mo-table a:not(.btn-mo):hover { color: var(--gm-gold-deep); text-decoration: underline; }
.mo-table a.btn-mo:hover { text-decoration: none; }
.mo-priority-high { color: #2e7d32; font-weight: 700; }
.mo-priority-low { color: var(--gm-navy-light); font-weight: 600; }
.status-badge { padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block; border: 1px solid transparent; }
.status-processing { background: var(--gm-navy); color: #fff; border-color: var(--gm-navy-dark); }
.status-completed { background: #e8f5e9; color: #2e7d32; border-color: #a5d6a7; }
.status-draft, .status-not-initiate { background: #f1f5f9; color: #64748b; border-color: #cbd5e1; }
.btn-mo {
    padding: 4px 10px; font-size: 10px; border-radius: 5px;
    cursor: pointer; font-weight: 700; text-decoration: none;
    display: inline-block; white-space: nowrap; border: 1px solid transparent;
}
.btn-catalogue {
    color: var(--gm-navy);
    border-color: var(--gm-gold);
    background: linear-gradient(180deg, #fdf8e8 0%, var(--gm-gold-pale) 100%);
}
.btn-catalogue:hover { background: var(--gm-gold-pale); }
.btn-stock-in {
    background: linear-gradient(180deg, var(--gm-navy-mid) 0%, var(--gm-navy) 100%);
    color: #fff !important;
    border-color: var(--gm-navy-dark);
}
.btn-stock-in:hover { background: var(--gm-navy); color: #fff !important; }
.btn-transfer {
    background: var(--gm-gold-pale);
    color: var(--gm-navy);
    border-color: rgba(17, 41, 75, 0.35);
}
.btn-transfer:hover { border-color: var(--gm-gold); background: #fdf8e8; }
.btn-mat-issue {
    background: var(--gm-navy);
    color: #fff !important;
    border-color: var(--gm-navy-dark);
}
.btn-mat-issue:hover { background: var(--gm-navy-mid); color: #fff !important; }
.btn-mat-receive {
    background: var(--gm-navy-light);
    color: #fff !important;
    border-color: var(--gm-navy);
}
.btn-mat-receive:hover { background: var(--gm-navy-mid); color: #fff !important; }
.img-thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 4px; background: #f1f5f9; border: 1px solid var(--gm-border); }
.active-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.active-yes { background: #22c55e; box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.25); }
.active-no { background: #cbd5e1; }
.mo-footer {
    padding: 10px 14px; border-top: 1px solid var(--gm-border);
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 8px; background: #fff; font-size: 12px; color: #5a6a8a;
    flex-shrink: 0;
}
body.manufacturing-outsource-page .layout-content {
    height: calc(100vh - 58px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
body.manufacturing-outsource-page .layout-content > .mo-shell {
    flex: 1;
    min-height: 0;
    margin: 8px 12px 12px;
    height: auto;
}
.mo-footer .page-link { color: var(--gm-navy); }
.mo-footer .page-item.active .page-link { background: var(--gm-navy); border-color: var(--gm-navy); color: var(--gm-gold-light); }
.text-left { text-align: left !important; }
.filter-modal { display: none; position: fixed; inset: 0; background: rgba(10, 31, 54, 0.45); z-index: 1060; align-items: center; justify-content: center; }
.filter-modal.active { display: flex; }
.filter-modal-content { background: #fff; border-radius: 12px; width: min(640px, calc(100vw - 32px)); max-height: 90vh; overflow: auto; border: 1px solid var(--gm-navy); box-shadow: 0 14px 34px rgba(17, 41, 75, 0.28); }
.filter-modal-header {
    background: linear-gradient(90deg, var(--gm-navy-dark) 0%, var(--gm-navy) 50%, var(--gm-navy-mid) 100%);
    color: var(--gm-gold-light);
    padding: 12px 16px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid var(--gm-gold);
}
.filter-modal-close { background: none; border: none; color: var(--gm-gold-light); font-size: 22px; cursor: pointer; }
.filter-modal .btn-primary { background: var(--gm-navy); border-color: var(--gm-navy-dark); }
.filter-modal .btn-primary:hover { background: var(--gm-navy-mid); }
.mo-col-hidden { display: none !important; }
.mo-page-title-muted { color: var(--gm-navy-light); font-weight: 600; font-size: 12px; }
</style>
<body class="manufacturing-outsource-page mfg-page">
<?php include 'sidebar.php'; ?>
<div class="layout-content">
<div class="mo-shell">
    <aside class="mo-dept-panel">
        <div class="mo-dept-list-box">
        <div class="mo-dept-title">All Department</div>
        <ul class="mo-dept-list" id="moDeptList">
            <li>
                <a href="manufacturing-outsource.php?<?php echo htmlspecialchars($mo_build_query(['dept_id' => null, 'user_id' => null, 'page' => 1, 'memo_page' => 1]), ENT_QUOTES); ?>" class="<?php echo ($filter_dept_id === 0 && $filter_user_id === 0) ? 'active' : ''; ?>">
                    <i class="feather icon-grid"></i><span>All Department</span>
                </a>
            </li>
            <?php foreach ($departments as $d):
                $dept_id = (int) $d['id'];
                $users = $department_users[$dept_id] ?? [];
                $dept_active = ($filter_dept_id === $dept_id && $filter_user_id === 0);
                $q_dept = $_GET;
                $q_dept['dept_id'] = $dept_id;
                unset($q_dept['user_id']);
                $q_dept['page'] = 1;
                $q_dept['memo_page'] = 1;
            ?>
            <li class="<?php echo ($filter_dept_id === $dept_id) ? 'open' : ''; ?>">
                <a href="?<?php echo htmlspecialchars(http_build_query($q_dept), ENT_QUOTES); ?>" class="<?php echo $dept_active ? 'active' : ''; ?>">
                    <span><?php echo htmlspecialchars($d['dept_name']); ?></span>
                    <?php if (!empty($users)): ?><span class="arrow">&#8250;</span><?php endif; ?>
                </a>
                <?php if (!empty($users)): ?>
                <ul class="mo-dept-user-list">
                    <?php foreach ($users as $user):
                        $q_user = $q_dept;
                        $q_user['user_id'] = (int) $user['id'];
                        $user_active = ($filter_dept_id === $dept_id && $filter_user_id === (int) $user['id']);
                    ?>
                    <li>
                        <a href="?<?php echo htmlspecialchars(http_build_query($q_user), ENT_QUOTES); ?>" class="<?php echo $user_active ? 'active' : ''; ?>">
                            <i class="feather icon-user"></i>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        </div>
    </aside>

    <div class="mo-main">
        <div class="mo-tabs">
            <a href="manufacturing-outsource.php?<?php echo htmlspecialchars($mo_build_query(['tab' => 'outsource', 'page' => 1, 'memo_page' => 1]), ENT_QUOTES); ?>"
               class="mo-tab text-decoration-none <?php echo $active_tab === 'outsource' ? 'is-active' : 'secondary'; ?>"><?php echo htmlspecialchars($page_title); ?></a>
            <a href="manufacturing-outsource.php?<?php echo htmlspecialchars($mo_build_query(['tab' => 'memo', 'page' => 1, 'memo_page' => 1]), ENT_QUOTES); ?>"
               class="mo-tab text-decoration-none <?php echo $active_tab === 'memo' ? 'is-active' : 'secondary'; ?>"><?php echo htmlspecialchars($memo_tab_label); ?></a>
        </div>

        <div class="mo-toolbar">
            <div class="mo-toolbar-left">
                <input type="search" class="form-control form-control-sm" id="moQuickSearch" placeholder="Search…" value="<?php echo htmlspecialchars($search); ?>" style="width:200px;">
                <div class="dropdown">
                    <button type="button" class="mo-btn-action dropdown-toggle" data-toggle="dropdown">Action</button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="jobwork-order.php">New Jobwork Order</a>
                        <a class="dropdown-item" href="#" id="moExportCsv" data-export-target="<?php echo $active_tab === 'memo' ? 'memo' : 'outsource'; ?>">Export CSV</a>
                        <?php if ($active_tab === 'memo'): ?>
                        <a class="dropdown-item" href="#" id="moExportMemoExcel"><i class="feather icon-file mr-1"></i>Export Excel</a>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="button" class="mo-btn-filter" id="openFilterModal" title="Filters">
                    <i class="feather icon-filter"></i>
                    <?php if ($filter_count > 0): ?><span class="mo-filter-badge"><?php echo (int) $filter_count; ?></span><?php endif; ?>
                </button>
                <button type="button" class="mo-btn-filter" onclick="location.reload()" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
            </div>
            <div class="mo-toolbar-right">
                <span class="mo-page-title-muted"><?php echo htmlspecialchars($active_tab === 'memo' ? $memo_tab_label : $page_title); ?></span>
            </div>
        </div>

        <div class="mo-panel <?php echo $active_tab === 'outsource' ? 'is-active' : ''; ?>" id="moPanelOutsource" data-panel="outsource">
        <div class="mo-table-wrap">
            <table class="mo-table" id="moTable">
                <thead>
                    <tr>
                        <th data-col="check"><input type="checkbox" id="selectAll"></th>
                        <th data-col="active">active</th>
                        <th data-col="image_urls">imageUrls</th>
                        <th data-col="product_name">Product Name</th>
                        <th data-col="rfid">RFID Code</th>
                        <th data-col="jobwork_no">Jobwork Order No</th>
                        <th data-col="jobwork_invoice">Jobwork Invoice No.</th>
                        <th data-col="sale_order_no">Sale Order No.</th>
                        <th data-col="mat_issue">Material Issue No.</th>
                        <th data-col="mat_receive">Material Recieve No.</th>
                        <th data-col="against_ref">Against Ref. No.</th>
                        <th data-col="customer">Customer Name</th>
                        <th data-col="dept">Current Dept.</th>
                        <th data-col="assign">Assign To</th>
                        <th data-col="account_no">Account No</th>
                        <th data-col="gross_wt">Gross Wt</th>
                        <th data-col="current_user">Current User</th>
                        <th data-col="design_no">Design No.</th>
                        <th data-col="order_dt">Order Dt.</th>
                        <th data-col="due_dt">Due Date</th>
                        <th data-col="tag_no">Tag No</th>
                        <th data-col="source">Source</th>
                        <th data-col="branch">Branch Name</th>
                        <th data-col="status">Status</th>
                        <th data-col="priority">Priority</th>
                        <th data-col="create_catelog">createCatelog</th>
                        <th data-col="is_stock_in">isStockIn</th>
                        <th data-col="transfer">Transfer</th>
                        <th data-col="material_issue">MaterialIssue</th>
                        <th data-col="material_receive">MaterialRecieve</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$mfg_can_list): ?>
                    <tr><td colspan="30" class="text-center text-muted py-5">Job work tables not found. Run <code>sql/create_tbl_jobwork_orders.sql</code>.</td></tr>
                <?php elseif (empty($page_rows)): ?>
                    <tr><td colspan="30" class="text-center text-muted py-5">No Rows To Show</td></tr>
                <?php else: ?>
                    <?php foreach ($page_rows as $row):
                        $jid = (int) ($row['jobwork_order_id'] ?? 0);
                        $soid = (int) ($row['sale_order_id'] ?? 0);
                        $rid = (int) ($row['repair_order_id'] ?? 0);
                        $ls = (string) ($row['list_source'] ?? 'jwo');
                        $od = !empty($row['order_date']) ? date('d-m-Y', strtotime($row['order_date'])) : '—';
                        $dd = !empty($row['due_date']) ? date('d-m-Y', strtotime($row['due_date'])) : '—';
                        $st = strtolower(trim((string) ($row['jwo_status'] ?? 'draft')));
                        $status_class = 'status-draft';
                        $status_label = ucfirst($st ?: 'draft');
                        if ($st === 'completed') {
                            $status_class = 'status-completed';
                        } elseif ($st === 'processing') {
                            $status_class = 'status-processing';
                        } elseif ($st === 'draft' || $st === '') {
                            $status_class = 'status-not-initiate';
                            $status_label = 'Draft';
                        }
                        $ia = isset($row['item_active']) ? (int) $row['item_active'] : 1;
                        $active_ok = ($ia === 1);
                        $imgs = jwm_row_image_urls($row, $base_web);
                        $mat_issue_url = jwm_material_issue_url($ls, $jid, $soid, $rid);
                        $mat_receive_url = jwm_material_receive_url($ls, $jid, $soid, $rid);
                        $prio = trim((string) ($row['priority'] ?? 'Medium'));
                        $prio_class = (stripos($prio, 'high') !== false) ? 'mo-priority-high' : ((stripos($prio, 'low') !== false) ? 'mo-priority-low' : '');
                        $gross = (float) ($row['gross_wt'] ?? 0);
                        $gross_disp = $gross > 0 ? rtrim(rtrim(number_format($gross, 3, '.', ''), '0'), '.') : '—';
                        $is_stock = !empty($row['is_stock_in']);
                        $jwo_href = ($ls === 'rjwo') ? 'repair-job-work-order.php?id=' . $jid : 'jobwork-order.php?id=' . $jid;
                        $transfer_href = 'jobwork-queue.php?jwo_id=' . $jid;
                        $stock_href = ($ls === 'jwo' && $jid > 0) ? 'jobwork-invoice.php?Jobwork_order_id=' . $jid : (($ls === 'rjwo' && $jid > 0) ? 'jobwork-invoice.php?repair_jobwork_order_id=' . $jid : '');
                        $mi_nos = trim((string) ($row['material_issue_nos'] ?? ''));
                        $mr_nos = trim((string) ($row['material_receive_nos'] ?? ''));
                        $so_display = ($ls === 'repair' || $ls === 'rjwo')
                            ? (string) ($row['repair_order_no'] ?? $row['sale_order_no'] ?? '—')
                            : (string) ($row['sale_order_no'] ?? '—');
                    ?>
                    <tr data-dept-id="<?php echo (int) ($row['department_id'] ?? 0); ?>" data-user-id="<?php echo (int) ($row['department_user_id'] ?? 0); ?>">
                        <td data-col="check"><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($row['row_uid'] ?? '', ENT_QUOTES); ?>"></td>
                        <td data-col="active"><span class="active-dot <?php echo $active_ok ? 'active-yes' : 'active-no'; ?>" title="<?php echo $active_ok ? 'Active' : 'Inactive'; ?>"></span></td>
                        <td data-col="image_urls">
                            <?php if (!empty($imgs)): ?>
                                <?php foreach (array_slice($imgs, 0, 2) as $iu): ?>
                                    <a href="<?php echo htmlspecialchars($iu); ?>" target="_blank" rel="noopener"><img src="<?php echo htmlspecialchars($iu); ?>" alt="" class="img-thumb" loading="lazy"></a>
                                <?php endforeach; ?>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td data-col="product_name" class="text-left"><?php echo htmlspecialchars($row['product_name'] ?? '—'); ?></td>
                        <td data-col="rfid"><?php echo htmlspecialchars(!empty($row['rfid_code']) ? (string) $row['rfid_code'] : '—'); ?></td>
                        <td data-col="jobwork_no">
                            <?php if ($jid > 0): ?>
                                <a href="<?php echo htmlspecialchars($jwo_href); ?>"><?php echo htmlspecialchars($row['jobwork_no'] ?? '—'); ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td data-col="jobwork_invoice"><?php echo htmlspecialchars(!empty($row['jobwork_invoice_no']) ? (string) $row['jobwork_invoice_no'] : '—'); ?></td>
                        <td data-col="sale_order_no"><?php echo htmlspecialchars($so_display !== '' ? $so_display : '—'); ?></td>
                        <td data-col="mat_issue" class="text-left"><?php echo htmlspecialchars($mi_nos !== '' ? $mi_nos : '—'); ?></td>
                        <td data-col="mat_receive" class="text-left"><?php echo htmlspecialchars($mr_nos !== '' ? $mr_nos : '—'); ?></td>
                        <td data-col="against_ref" class="text-left"><?php echo htmlspecialchars(!empty($row['against_ref']) ? (string) $row['against_ref'] : '—'); ?></td>
                        <td data-col="customer" class="text-left"><?php echo htmlspecialchars($row['customer_name'] ?? '—'); ?></td>
                        <td data-col="dept"><?php echo htmlspecialchars(!empty($row['current_dept']) ? (string) $row['current_dept'] : '—'); ?></td>
                        <td data-col="assign"><?php echo htmlspecialchars(!empty($row['assign_name']) ? (string) $row['assign_name'] : '—'); ?></td>
                        <td data-col="account_no"><?php echo htmlspecialchars(!empty($row['account_no']) ? (string) $row['account_no'] : '—'); ?></td>
                        <td data-col="gross_wt"><?php echo htmlspecialchars($gross_disp); ?></td>
                        <td data-col="current_user"><?php echo htmlspecialchars(!empty($row['current_user_name']) ? (string) $row['current_user_name'] : '—'); ?></td>
                        <td data-col="design_no"><?php echo htmlspecialchars(!empty($row['design_no']) ? (string) $row['design_no'] : '—'); ?></td>
                        <td data-col="order_dt"><?php echo htmlspecialchars($od); ?></td>
                        <td data-col="due_dt"><?php echo htmlspecialchars($dd); ?></td>
                        <td data-col="tag_no"><?php echo htmlspecialchars(!empty($row['tag_no']) ? (string) $row['tag_no'] : '—'); ?></td>
                        <td data-col="source"><?php echo htmlspecialchars($row['source_label'] ?? '—'); ?></td>
                        <td data-col="branch"><?php echo htmlspecialchars(!empty($row['branch_name']) ? (string) $row['branch_name'] : '—'); ?></td>
                        <td data-col="status"><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></span></td>
                        <td data-col="priority"><span class="<?php echo $prio_class; ?>"><?php echo htmlspecialchars($prio); ?></span></td>
                        <td data-col="create_catelog"><button type="button" class="btn-mo btn-catalogue" title="Create catalogue">createCatelog</button></td>
                        <td data-col="is_stock_in">
                            <?php if ($stock_href !== ''): ?>
                                <a class="btn-mo btn-stock-in" href="<?php echo htmlspecialchars($stock_href); ?>" title="<?php echo $is_stock ? 'Stocked in' : 'Stock In via Jobwork Invoice'; ?>"><?php echo $is_stock ? 'Stocked In' : 'Stock In'; ?></a>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td data-col="transfer">
                            <?php if ($jid > 0): ?>
                                <a class="btn-mo btn-transfer" href="<?php echo htmlspecialchars($transfer_href); ?>">Transfer</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td data-col="material_issue">
                            <?php if ($mat_issue_url !== ''): ?>
                                <a class="btn-mo btn-mat-issue" href="<?php echo htmlspecialchars($mat_issue_url); ?>">MaterialIssue</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td data-col="material_receive">
                            <?php if ($mat_receive_url !== ''): ?>
                                <a class="btn-mo btn-mat-receive" href="<?php echo htmlspecialchars($mat_receive_url); ?>">MaterialRecieve</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mo-footer" id="moFooterOutsource">
        <span>Showing <?php echo $total_records > 0 ? $offset + 1 : 0; ?> to <?php echo min($offset + $per_page, $total_records); ?> of <?php echo $total_records; ?> entries</span>
            <div class="d-flex align-items-center gap-2">
                <?php
                $qbase = $_GET;
                unset($qbase['page']);
                $qpre = http_build_query($qbase);
                $qglue = $qpre ? $qpre . '&' : '';
                ?>
                <label class="mb-0">Show
                    <select class="form-control form-control-sm d-inline-block" style="width:auto" onchange="location.href='?'+'<?php echo htmlspecialchars($qglue, ENT_QUOTES); ?>'+'per_page='+this.value+'&page=1'">
                        <?php foreach ([10, 25, 50, 100, 200] as $pp): ?>
                        <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <nav>
                    <ul class="pagination mb-0 pagination-sm">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => max(1, $page - 1)])), ENT_QUOTES); ?>">Previous</a></li>
                        <li class="page-item disabled"><span class="page-link"><?php echo (int) $page; ?> / <?php echo max(1, $total_pages); ?></span></li>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['page' => min($total_pages, $page + 1)])), ENT_QUOTES); ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
    </div>
        </div>

        <div class="mo-panel <?php echo $active_tab === 'memo' ? 'is-active' : ''; ?>" id="moPanelMemo" data-panel="memo">
        <div class="mo-table-wrap">
            <table class="mo-table mo-memo-table" id="moMemoTable">
                <thead>
                    <tr>
                        <?php foreach ($memo_columns as $mcol): ?>
                        <th data-col="<?php echo htmlspecialchars($mcol['key'], ENT_QUOTES); ?>" class="mo-memo-th-interactive" title="Drag handle to reorder · drag edge to resize"><?php echo htmlspecialchars($mcol['label']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($memo_page_rows)): ?>
                    <tr><td colspan="<?php echo max(1, count($memo_columns)); ?>" class="text-center text-muted py-5">No memo issue / receive records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($memo_page_rows as $mrow):
                        $clarity = (string) ($mrow['clarity'] ?? '');
                        $status = (string) ($mrow['memo_status'] ?? '—');
                        $status_cls = 'mo-memo-status-out';
                        if ($status === 'Closed') {
                            $status_cls = 'mo-memo-status-closed';
                        } elseif ($status === 'Partial') {
                            $status_cls = 'mo-memo-status-partial';
                        } elseif ($status === 'In') {
                            $status_cls = 'mo-memo-status-in';
                        }
                    ?>
                    <tr>
                        <?php foreach ($memo_columns as $mcol):
                            $ck = $mcol['key'];
                            $disp = '—';
                            if ($ck === 'remark') {
                                $rh = (string) ($mrow['remark_html'] ?? '');
                                $disp = $rh !== '' ? $rh : '—';
                            } elseif ($ck === 'clarity') {
                                $disp = $clarity !== '' ? $clarity : '—';
                            } elseif ($ck === 'stone_size') {
                                $v = trim((string) ($mrow['stone_size'] ?? ''));
                                $disp = $v !== '' ? $v : '—';
                            } elseif ($ck === 'issue_wt') {
                                $v = trim((string) ($mrow['issue_wt'] ?? ''));
                                $disp = $v !== '' ? $v : '—';
                            } elseif ($ck === 'receive_wt') {
                                $v = trim((string) ($mrow['receive_wt'] ?? ''));
                                $disp = $v !== '' ? $v : '—';
                            } elseif ($ck === 'pending_wt') {
                                $v = trim((string) ($mrow['pending_wt'] ?? ''));
                                $disp = $v !== '' ? $v : '—';
                            } elseif ($ck === 'memo_status') {
                                $disp = $status;
                            } else {
                                $v = trim((string) ($mrow[$ck] ?? ''));
                                $disp = $v !== '' ? $v : '—';
                            }
                        ?>
                        <td data-col="<?php echo htmlspecialchars($ck, ENT_QUOTES); ?>"
                            class="<?php
                                echo $ck === 'product' || $ck === 'remark' ? 'text-left ' : '';
                                echo $ck === 'remark' ? 'mo-remark-cell ' : '';
                                echo $ck === 'issue_wt' ? 'mo-memo-wt-out ' : '';
                                echo $ck === 'receive_wt' ? 'mo-memo-wt-in ' : '';
                                echo $ck === 'pending_wt' ? 'mo-memo-wt-pending ' : '';
                                echo $ck === 'memo_status' ? $status_cls . ' ' : '';
                            ?>">
                            <?php if ($ck === 'remark' && $disp !== '—'): ?>
                                <?php echo $disp; ?>
                            <?php elseif ($ck === 'clarity' && $clarity !== ''): ?>
                                <span class="mo-clarity-link"><?php echo htmlspecialchars($clarity); ?></span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($disp); ?>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mo-footer" id="moFooterMemo">
            <span>Showing <?php echo $memo_total_records > 0 ? $memo_offset + 1 : 0; ?> to <?php echo min($memo_offset + $per_page, $memo_total_records); ?> of <?php echo $memo_total_records; ?> entries</span>
            <div class="d-flex align-items-center gap-2">
                <?php
                $qmemo = $_GET;
                $qmemo['tab'] = 'memo';
                unset($qmemo['memo_page']);
                $qmemo_pre = http_build_query($qmemo);
                $qmemo_glue = $qmemo_pre ? $qmemo_pre . '&' : '';
                ?>
                <label class="mb-0">Show
                    <select class="form-control form-control-sm d-inline-block" style="width:auto" onchange="location.href='?'+'<?php echo htmlspecialchars($qmemo_glue, ENT_QUOTES); ?>'+'per_page='+this.value+'&memo_page=1&tab=memo'">
                        <?php foreach ([10, 25, 50, 100, 200] as $pp): ?>
                        <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <nav>
                    <ul class="pagination mb-0 pagination-sm">
                        <li class="page-item <?php echo $memo_page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['tab' => 'memo', 'memo_page' => max(1, $memo_page - 1)])), ENT_QUOTES); ?>">Previous</a></li>
                        <li class="page-item disabled"><span class="page-link"><?php echo (int) $memo_page; ?> / <?php echo max(1, $memo_total_pages); ?></span></li>
                        <li class="page-item <?php echo $memo_page >= $memo_total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, ['tab' => 'memo', 'memo_page' => min($memo_total_pages, $memo_page + 1)])), ENT_QUOTES); ?>">Next</a></li>
                    </ul>
                </nav>
            </div>
        </div>
        </div>

</div>
</div>

<div id="advancedFilterModal" class="filter-modal" aria-hidden="true">
    <div class="filter-modal-content">
        <div class="filter-modal-header">
            <strong>Advance Filter</strong>
            <button type="button" class="filter-modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="p-3">
            <form method="get" id="advFilterForm">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab, ENT_QUOTES); ?>">
                <?php if ($filter_dept_id > 0): ?><input type="hidden" name="dept_id" value="<?php echo (int) $filter_dept_id; ?>"><?php endif; ?>
                <?php if ($filter_user_id > 0): ?><input type="hidden" name="user_id" value="<?php echo (int) $filter_user_id; ?>"><?php endif; ?>
                <input type="hidden" name="search" id="advSearchHidden" value="<?php echo htmlspecialchars($search); ?>">
                <div class="form-group">
                    <label>Order date from / to</label>
                    <div class="d-flex" style="gap:8px;">
                        <input type="date" name="from_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($from_date); ?>">
                        <input type="date" name="to_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Jobwork Order No.</label>
                    <input type="text" name="jw_no" class="form-control form-control-sm" value="<?php echo htmlspecialchars($jw_no); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
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
                        <?php foreach ([10, 25, 50, 100, 200] as $pp): ?>
                        <option value="<?php echo $pp; ?>" <?php echo $per_page === $pp ? 'selected' : ''; ?>><?php echo $pp; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                <a href="manufacturing-outsource.php?tab=<?php echo urlencode($active_tab); ?>" class="btn btn-outline-secondary btn-sm ml-2">Clear</a>
            </form>
        </div>
    </div>
</div>

<?php include 'footer-script.php'; ?>
<script>
(function() {
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(function(cb) { cb.checked = this.checked; }, this);
    });
    document.getElementById('openFilterModal')?.addEventListener('click', function() {
        var m = document.getElementById('advancedFilterModal');
        if (m) { m.classList.add('active'); }
    });
    document.querySelector('.filter-modal-close')?.addEventListener('click', function() {
        document.getElementById('advancedFilterModal')?.classList.remove('active');
    });
    document.getElementById('advancedFilterModal')?.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
    var qs = document.getElementById('moQuickSearch');
    if (qs) {
        qs.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var u = new URL(window.location.href);
                u.searchParams.set('search', qs.value.trim());
                u.searchParams.set('page', '1');
                u.searchParams.set('memo_page', '1');
                window.location.href = u.toString();
            }
        });
    }
    document.getElementById('moExportCsv')?.addEventListener('click', function(e) {
        e.preventDefault();
        var target = this.getAttribute('data-export-target') || 'outsource';
        var table = target === 'memo' ? document.getElementById('moMemoTable') : document.getElementById('moTable');
        if (!table) return;
        var rows = [];
        table.querySelectorAll('tr').forEach(function(tr) {
            var cells = [];
            tr.querySelectorAll('th, td').forEach(function(td) {
                if (td.getAttribute('data-col') === 'check') return;
                cells.push('"' + (td.innerText || '').replace(/"/g, '""').trim() + '"');
            });
            if (cells.length) rows.push(cells.join(','));
        });
        var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = target === 'memo' ? 'memo-in-out.csv' : 'manufacturing-outsource.csv';
        a.click();
    });
    document.getElementById('moExportMemoExcel')?.addEventListener('click', function(e) {
        e.preventDefault();
        var u = new URL(window.location.href);
        var qs = u.searchParams.toString();
        window.location.href = 'ajax/export-manufacturing-outsource-memo-excel.php' + (qs ? ('?' + qs) : '');
    });

    /* Memo table — column drag reorder + resize (localStorage) */
    (function initMemoColumnLayout() {
        var table = document.getElementById('moMemoTable');
        if (!table) return;
        var headRow = table.querySelector('thead tr');
        if (!headRow) return;
        var STORAGE_ORDER = 'mo_memo_col_order_v1';
        var STORAGE_WIDTHS = 'mo_memo_col_widths_v1';

        function getStoredOrder() {
            try {
                var raw = localStorage.getItem(STORAGE_ORDER);
                if (!raw) return null;
                var o = JSON.parse(raw);
                return Array.isArray(o) ? o : null;
            } catch (err) { return null; }
        }
        function setStoredOrder(order) {
            try { localStorage.setItem(STORAGE_ORDER, JSON.stringify(order)); } catch (err) {}
        }
        function getStoredWidths() {
            try {
                var raw = localStorage.getItem(STORAGE_WIDTHS);
                if (!raw) return {};
                var o = JSON.parse(raw);
                return (o && typeof o === 'object') ? o : {};
            } catch (err) { return {}; }
        }
        function setStoredWidths(w) {
            try { localStorage.setItem(STORAGE_WIDTHS, JSON.stringify(w || {})); } catch (err) {}
        }
        function reorderRowByDataCol(tr, tag, order) {
            order.forEach(function(key) {
                var el = tr.querySelector(tag + '[data-col="' + key + '"]');
                if (el) tr.appendChild(el);
            });
        }
        function applyColumnOrder(order) {
            if (!order || !order.length) return;
            reorderRowByDataCol(headRow, 'th', order);
            table.querySelectorAll('tbody tr').forEach(function(tr) {
                if (!tr.querySelector('td[data-col]')) return;
                reorderRowByDataCol(tr, 'td', order);
            });
        }
        function applyColumnWidths(widths) {
            if (!widths) return;
            Object.keys(widths).forEach(function(key) {
                var px = parseInt(widths[key], 10);
                if (!px || px < 48) return;
                table.querySelectorAll('th[data-col="' + key + '"], td[data-col="' + key + '"]').forEach(function(el) {
                    el.style.width = px + 'px';
                    el.style.minWidth = px + 'px';
                    el.style.maxWidth = px + 'px';
                });
            });
        }
        function bindResizer(resizerEl, colKey) {
            var startX, startW;
            resizerEl.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var thEl = resizerEl.closest('th');
                if (!thEl) return;
                startX = e.pageX;
                startW = thEl.offsetWidth;
                document.body.classList.add('mo-memo-col-resizing');
                function onMove(ev) {
                    var nw = Math.max(48, startW + (ev.pageX - startX));
                    table.querySelectorAll('th[data-col="' + colKey + '"], td[data-col="' + colKey + '"]').forEach(function(el) {
                        el.style.width = nw + 'px';
                        el.style.minWidth = nw + 'px';
                        el.style.maxWidth = nw + 'px';
                    });
                }
                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    document.body.classList.remove('mo-memo-col-resizing');
                    var wmap = getStoredWidths();
                    var cell = table.querySelector('th[data-col="' + colKey + '"]');
                    if (cell) wmap[colKey] = Math.max(48, cell.offsetWidth);
                    setStoredWidths(wmap);
                }
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        }

        headRow.querySelectorAll('th[data-col]').forEach(function(th) {
            var key = th.getAttribute('data-col');
            if (!key) return;
            var handle = document.createElement('span');
            handle.className = 'mo-memo-col-drag-handle';
            handle.setAttribute('draggable', 'true');
            handle.title = 'Drag to reorder column';
            handle.innerHTML = '&#9776;';
            handle.setAttribute('data-mo-drag-col', key);
            var rz = document.createElement('span');
            rz.className = 'mo-memo-col-resizer';
            rz.title = 'Resize column';
            th.insertBefore(handle, th.firstChild);
            th.appendChild(rz);
            bindResizer(rz, key);
        });

        var dragCol = null;
        headRow.querySelectorAll('.mo-memo-col-drag-handle').forEach(function(h) {
            h.addEventListener('dragstart', function(e) {
                dragCol = h.getAttribute('data-mo-drag-col');
                if (!dragCol) return;
                e.dataTransfer.setData('text/plain', dragCol);
                e.dataTransfer.effectAllowed = 'move';
                var th = h.closest('th');
                if (th) th.classList.add('mo-memo-th-dragging');
            });
            h.addEventListener('dragend', function() {
                headRow.querySelectorAll('th').forEach(function(th) {
                    th.classList.remove('mo-memo-th-dragging', 'mo-memo-th-drag-over');
                });
                dragCol = null;
            });
        });
        headRow.querySelectorAll('th[data-col]').forEach(function(th) {
            var key = th.getAttribute('data-col');
            if (!key) return;
            th.addEventListener('dragover', function(e) {
                if (!dragCol || dragCol === key) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                th.classList.add('mo-memo-th-drag-over');
            });
            th.addEventListener('dragleave', function() {
                th.classList.remove('mo-memo-th-drag-over');
            });
            th.addEventListener('drop', function(e) {
                e.preventDefault();
                th.classList.remove('mo-memo-th-drag-over');
                var src = e.dataTransfer.getData('text/plain') || dragCol;
                var tgt = key;
                if (!src || !tgt || src === tgt) return;
                var cur = [];
                headRow.querySelectorAll('th[data-col]').forEach(function(x) {
                    cur.push(x.getAttribute('data-col'));
                });
                var iSrc = cur.indexOf(src);
                var iTgt = cur.indexOf(tgt);
                if (iSrc < 0 || iTgt < 0) return;
                cur.splice(iSrc, 1);
                if (iSrc < iTgt) iTgt--;
                cur.splice(iTgt, 0, src);
                applyColumnOrder(cur);
                setStoredOrder(cur);
            });
        });

        var savedOrder = getStoredOrder();
        if (savedOrder && savedOrder.length) {
            var current = [];
            headRow.querySelectorAll('th[data-col]').forEach(function(x) {
                current.push(x.getAttribute('data-col'));
            });
            var valid = savedOrder.filter(function(k) { return current.indexOf(k) >= 0; });
            current.forEach(function(k) {
                if (valid.indexOf(k) < 0) valid.push(k);
            });
            applyColumnOrder(valid);
        }
        applyColumnWidths(getStoredWidths());
    })();
})();
</script>
</body>
</html>
