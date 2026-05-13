<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$sale_order_id = isset($_POST['sale_order_id']) ? (int)$_POST['sale_order_id'] : 0;
/** When 1, always INSERT a new tbl_material_issues row (do not reuse existing for same sale order). */
$force_new_jwo = isset($_POST['force_new_jwo']) && ($_POST['force_new_jwo'] === '1' || $_POST['force_new_jwo'] === 1 || $_POST['force_new_jwo'] === true);
$jwo_status = isset($_POST['jwo_status']) ? trim($_POST['jwo_status']) : 'Processing';
$department_id = (isset($_POST['department_id']) && $_POST['department_id'] !== '') ? (int)$_POST['department_id'] : null;
$priority = isset($_POST['priority']) ? trim($_POST['priority']) : 'Medium';
$jwo_id = isset($_POST['jwo_id']) ? (int)$_POST['jwo_id'] : 0;
$department_user_id_provided = isset($_POST['department_user_id']);
$department_user_id = null;
if ($department_user_id_provided) {
    $v_du = trim((string)$_POST['department_user_id']);
    $department_user_id = ($v_du !== '' && (int)$v_du > 0) ? (int)$v_du : null;
}

$sales_person_post = isset($_POST['sales_person']) ? trim((string)$_POST['sales_person']) : '';

$items_json = isset($_POST['items']) ? $_POST['items'] : '';
$items = [];
if (is_string($items_json) && $items_json !== '') {
    $items = json_decode($items_json, true);
}
if (!is_array($items)) {
    $items = [];
}

$standalone_mi = ($sale_order_id < 1);
$header_order_date = isset($_POST['header_order_date']) ? trim((string)$_POST['header_order_date']) : '';
$header_due_date = isset($_POST['header_due_date']) ? trim((string)$_POST['header_due_date']) : '';
$header_customer_name = isset($_POST['header_customer_name']) ? trim((string)$_POST['header_customer_name']) : '';

$tbl_master = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_material_issues'");
$tbl_items = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_material_issue_items'");
if (!$tbl_master || mysqli_num_rows($tbl_master) === 0 || !$tbl_items || mysqli_num_rows($tbl_items) === 0) {
    if ($tbl_master) {
        mysqli_free_result($tbl_master);
    }
    if ($tbl_items) {
        mysqli_free_result($tbl_items);
    }
    echo json_encode(['status' => 'error', 'message' => 'Material issue tables not found. Please run admin/sql/create_tbl_material_issues.sql']);
    exit;
}
mysqli_free_result($tbl_master);
mysqli_free_result($tbl_items);

$has_mi_branch   = auragold_ensure_table_branch_id_column($conn, 'tbl_material_issues');
$hdr_mi_branch   = auragold_transaction_header_branch_id();
$mi_scope_sql    = ($has_mi_branch && $hdr_mi_branch > 0) ? (' AND branch_id = ' . (int) $hdr_mi_branch) : '';

$mi_so_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_material_issues LIKE 'sale_order_id'");
if ($mi_so_col && mysqli_num_rows($mi_so_col) > 0) {
    $mi_cr = mysqli_fetch_assoc($mi_so_col);
    mysqli_free_result($mi_so_col);
    if (!empty($mi_cr['Null']) && strtoupper((string)$mi_cr['Null']) === 'NO') {
        @mysqli_query($conn, 'ALTER TABLE `tbl_material_issues` MODIFY COLUMN `sale_order_id` int(11) DEFAULT NULL');
    }
} elseif ($mi_so_col) {
    mysqli_free_result($mi_so_col);
}

$qc_mi_req = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_material_issue_items LIKE 'requested_purity'");
if (!$qc_mi_req || mysqli_num_rows($qc_mi_req) === 0) {
    if ($qc_mi_req) {
        mysqli_free_result($qc_mi_req);
    }
    @mysqli_query($conn, "ALTER TABLE `tbl_material_issue_items` ADD COLUMN `requested_purity` decimal(12,4) DEFAULT NULL AFTER `purity_weight`, ADD COLUMN `requested_wt` decimal(12,4) DEFAULT NULL AFTER `requested_purity`, ADD COLUMN `alloy_wt` decimal(12,4) DEFAULT NULL AFTER `requested_wt`");
} elseif ($qc_mi_req) {
    mysqli_free_result($qc_mi_req);
}

$map_chk_req = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_department_user_map'");
$has_department_user_map = ($map_chk_req && mysqli_num_rows($map_chk_req) > 0);
if ($map_chk_req) {
    mysqli_free_result($map_chk_req);
}

if ($has_department_user_map) {
    if (!$department_user_id_provided || $department_user_id === null || (int)$department_user_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Name is required']);
        exit;
    }
    if ($department_id === null || (int)$department_id < 1) {
        echo json_encode(['status' => 'error', 'message' => 'Department is required']);
        exit;
    }
}
if ($department_id !== null && (int)$department_id > 0) {
    $department_id = (int)$department_id;
} else {
    $department_id = null;
}

if (!$standalone_mi && $jwo_id < 1 && !$force_new_jwo) {
    $row_existing = getRecord("SELECT id FROM tbl_material_issues WHERE sale_order_id = $sale_order_id$mi_scope_sql LIMIT 1");
    if ($row_existing && !empty($row_existing['id'])) {
        $jwo_id = (int)$row_existing['id'];
    }
}

if ($standalone_mi) {
    $grand_from_items = 0;
    foreach ($items as $item) {
        $grand_from_items += (float)($item['net_amt_with_tax'] ?? $item['net_amount'] ?? 0);
    }
    $grand_from_items = round($grand_from_items, 2);
    $sale_order = [
        'id' => 0,
        'order_no' => '',
        'customer_name' => $header_customer_name,
        'order_date' => $header_order_date !== '' ? $header_order_date : null,
        'due_date' => $header_due_date !== '' ? $header_due_date : null,
        'grand_total' => $grand_from_items,
        'status' => '',
    ];
} else {
    $sale_order = getRecord("SELECT id, order_no, customer_name, order_date, due_date, grand_total, status FROM tbl_sale_orders WHERE id = $sale_order_id");
    if (!$sale_order) {
        echo json_encode(['status' => 'error', 'message' => 'Sale order not found']);
        exit;
    }
    if (auragold_tbl_has_column($conn, 'tbl_sale_orders', 'branch_id')) {
        $sob = getRecord('SELECT branch_id FROM tbl_sale_orders WHERE id = ' . (int) $sale_order_id . ' LIMIT 1');
        $sbi = (int) ($sob['branch_id'] ?? 0);
        $hdr = (int) $hdr_mi_branch;
        if ($sbi > 0 && $hdr > 0 && $sbi !== $hdr) {
            echo json_encode(['status' => 'error', 'message' => 'Sale order belongs to another branch.']);
            exit;
        }
    }
}

$cd_so = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'department_id'");
if ($cd_so && mysqli_num_rows($cd_so) === 0) {
    mysqli_free_result($cd_so);
    @mysqli_query($conn, "ALTER TABLE `tbl_sale_orders` ADD COLUMN `department_id` int(11) DEFAULT NULL AFTER `customer_name`");
} elseif ($cd_so) {
    mysqli_free_result($cd_so);
}

function auragold_sync_sale_order_department($conn, $sale_order_id, $department_id) {
    $c = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'department_id'");
    if (!$c || mysqli_num_rows($c) === 0) {
        if ($c) {
            mysqli_free_result($c);
        }
        return;
    }
    mysqli_free_result($c);
    $sid = (int)$sale_order_id;
    if ($department_id !== null && (int)$department_id > 0) {
        mysqli_query($conn, "UPDATE tbl_sale_orders SET department_id = " . (int)$department_id . " WHERE id = $sid");
    } else {
        mysqli_query($conn, "UPDATE tbl_sale_orders SET department_id = NULL WHERE id = $sid");
    }
}

function auragold_sync_sale_order_sales_person($conn, $sale_order_id, $sales_person) {
    $c = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'sales_person'");
    if (!$c || mysqli_num_rows($c) === 0) {
        if ($c) {
            mysqli_free_result($c);
        }
        return;
    }
    mysqli_free_result($c);
    $sid = (int)$sale_order_id;
    $sp = mysqli_real_escape_string($conn, $sales_person);
    if ($sales_person !== '') {
        mysqli_query($conn, "UPDATE tbl_sale_orders SET sales_person = '$sp' WHERE id = $sid");
    } else {
        mysqli_query($conn, "UPDATE tbl_sale_orders SET sales_person = NULL WHERE id = $sid");
    }
}

function auragold_material_issue_no_taken($conn, $no_esc, $branch_scope_sql = '') {
    $a = getRecord("SELECT id FROM tbl_material_issues WHERE material_issue_no = '$no_esc' $branch_scope_sql LIMIT 1");
    if ($a) {
        return true;
    }
    $tr = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_repair_material_issues'");
    if ($tr && mysqli_num_rows($tr) > 0) {
        mysqli_free_result($tr);
        $b = getRecord("SELECT id FROM tbl_repair_material_issues WHERE material_issue_no = '$no_esc' LIMIT 1");
        return (bool)$b;
    }
    if ($tr) {
        mysqli_free_result($tr);
    }
    return false;
}

$sale_order_no = mysqli_real_escape_string($conn, $sale_order['order_no']);
$customer_name = mysqli_real_escape_string($conn, $sale_order['customer_name'] ?? '');
$order_date = !empty($sale_order['order_date']) ? mysqli_real_escape_string($conn, $sale_order['order_date']) : 'NULL';
$due_date = !empty($sale_order['due_date']) ? "'" . mysqli_real_escape_string($conn, $sale_order['due_date']) . "'" : 'NULL';
$grand_total = (float)($sale_order['grand_total'] ?? 0);

if ($jwo_id > 0) {
    $jwo = getRecord("SELECT id, sale_order_id, status, department_id, department_user_id FROM tbl_material_issues WHERE id = $jwo_id");
    if (!$jwo) {
        echo json_encode(['status' => 'error', 'message' => 'Material issue not found']);
        exit;
    }
    try {
        auragold_branch_require_document_access($conn, 'tbl_material_issues', $jwo_id);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    $jwo_so_stored = isset($jwo['sale_order_id']) && $jwo['sale_order_id'] !== '' && $jwo['sale_order_id'] !== null ? (int)$jwo['sale_order_id'] : 0;
    $post_so_cmp = $standalone_mi ? 0 : (int)$sale_order_id;
    if ($jwo_so_stored !== $post_so_cmp) {
        echo json_encode(['status' => 'error', 'message' => 'Sale order mismatch']);
        exit;
    }
    $new_status = mysqli_real_escape_string($conn, $jwo_status);
    $grand_total = 0;
    foreach ($items as $item) {
        $grand_total += (float)($item['net_amt_with_tax'] ?? $item['net_amount'] ?? 0);
    }
    $grand_total = round($grand_total, 2);
    $upd = "UPDATE tbl_material_issues SET status = '$new_status', grand_total = $grand_total, updated_at = NOW()";
    if ($department_id !== null && $department_id > 0) {
        $upd .= ", department_id = " . (int)$department_id;
    }
    $priority_esc = mysqli_real_escape_string($conn, $priority);
    $upd .= ", priority = '$priority_esc'";
    if ($department_user_id_provided) {
        if ($department_user_id !== null && $department_user_id > 0) {
            $upd .= ", department_user_id = " . (int)$department_user_id;
        } else {
            $upd .= ", department_user_id = NULL";
        }
    }
    if ($standalone_mi) {
        $hdr_cn_esc = mysqli_real_escape_string($conn, $header_customer_name);
        $upd .= ", customer_name = '$hdr_cn_esc'";
        if ($header_order_date !== '') {
            $upd .= ", order_date = '" . mysqli_real_escape_string($conn, $header_order_date) . "'";
        }
        if ($header_due_date !== '') {
            $upd .= ", due_date = '" . mysqli_real_escape_string($conn, $header_due_date) . "'";
        }
    }
    if ($has_mi_branch && $hdr_mi_branch > 0) {
        $rb = getRecord('SELECT branch_id FROM tbl_material_issues WHERE id = ' . (int) $jwo_id . ' LIMIT 1');
        if ($rb && (int) ($rb['branch_id'] ?? 0) <= 0) {
            $upd .= ', branch_id = ' . (int) $hdr_mi_branch;
        }
    }
    $upd .= " WHERE id = $jwo_id";
    mysqli_query($conn, $upd);
    mysqli_query($conn, "DELETE FROM tbl_stock_journal WHERE comment LIKE 'auragold_doc|src=mi|hid=" . (int) $jwo_id . "|%'");
    mysqli_query($conn, "DELETE FROM tbl_material_issue_items WHERE material_issue_id = $jwo_id");
    foreach ($items as $item) {
        $product_id = (int)($item['product_id'] ?? 0);
        $characteristic_id = isset($item['characteristic_id']) && $item['characteristic_id'] !== '' ? (int)$item['characteristic_id'] : null;
        $barcode = mysqli_real_escape_string($conn, $item['barcode'] ?? '');
        $product_name = mysqli_real_escape_string($conn, $item['product_name'] ?? '');
        $design_no = mysqli_real_escape_string($conn, $item['design_no'] ?? '');
        $carat = mysqli_real_escape_string($conn, $item['carat'] ?? '');
        $quantity = (float)($item['quantity'] ?? 1);
        $gross_weight = (float)($item['gross_weight'] ?? 0);
        $less_weight = (float)($item['less_weight'] ?? 0);
        $purity = (float)($item['purity'] ?? 0);
        $purity_weight = (float)($item['purity_weight'] ?? 0);
        $requested_purity = (float)($item['requested_purity'] ?? 0);
        $requested_wt = (float)($item['requested_wt'] ?? 0);
        $alloy_wt = (float)($item['alloy_wt'] ?? 0);
        $final_weight = (float)($item['final_weight'] ?? 0);
        $net_weight = (float)($item['net_weight'] ?? 0);
        $pure_weight = (float)($item['pure_weight'] ?? 0);
        $rate = (float)($item['rate'] ?? 0);
        $making_amount = (float)($item['making_amount'] ?? 0);
        $amount = (float)($item['amount'] ?? 0);
        $tax_amount = (float)($item['tax'] ?? 0);
        $net_amount = (float)($item['net_amount'] ?? 0);
        $net_amt_with_tax = (float)($item['net_amt_with_tax'] ?? 0);
        $char_sql = $characteristic_id !== null ? $characteristic_id : 'NULL';
        $barcode_sql = $barcode !== '' ? "'$barcode'" : 'NULL';
        $design_sql = $design_no !== '' ? "'$design_no'" : 'NULL';
        $carat_sql = $carat !== '' ? "'$carat'" : 'NULL';
        $ins_item = "INSERT INTO tbl_material_issue_items (material_issue_id, product_id, product_characteristic_id, barcode, product_name, design_no, carat, quantity, gross_weight, less_weight, purity, purity_weight, requested_purity, requested_wt, alloy_wt, final_weight, net_weight, pure_weight, rate, making_amount, amount, tax_amount, net_amount, net_amt_with_tax, status, created_at) VALUES ($jwo_id, $product_id, $char_sql, $barcode_sql, '$product_name', $design_sql, $carat_sql, $quantity, $gross_weight, $less_weight, $purity, $purity_weight, $requested_purity, $requested_wt, $alloy_wt, $final_weight, $net_weight, $pure_weight, $rate, $making_amount, $amount, $tax_amount, $net_amount, $net_amt_with_tax, 1, NOW())";
        mysqli_query($conn, $ins_item);
        $mi_line_id = (int) mysqli_insert_id($conn);
        require_once dirname(__DIR__) . '/includes/stock_history_audit_journal.php';
        $mih_now = getRecord("SELECT material_issue_no, order_date FROM tbl_material_issues WHERE id = " . (int) $jwo_id . " LIMIT 1");
        $mi_doc_now = trim((string) ($mih_now['material_issue_no'] ?? ''));
        $mi_dt_now = substr(trim((string) ($mih_now['order_date'] ?? '')), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mi_dt_now)) {
            $mi_dt_now = $header_order_date !== '' ? substr($header_order_date, 0, 10) : '';
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mi_dt_now) && !empty($sale_order['order_date'])) {
                $mi_dt_now = substr(trim((string) $sale_order['order_date']), 0, 10);
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mi_dt_now)) {
                $mi_dt_now = date('Y-m-d');
            }
        }
        auragold_stock_history_audit_for_document_barcode_line($conn, 'Material Issue', $mi_doc_now, $mi_dt_now, 'MI', (int) $jwo_id, $mi_line_id, 'mi', array_merge($item, [
            'product_id' => $product_id,
            'product_characteristic_id' => $characteristic_id !== null ? (int) $characteristic_id : 0,
        ]));
    }
    if (!$standalone_mi && $sale_order_id > 0 && strtolower($new_status) === 'completed') {
        mysqli_query($conn, "UPDATE tbl_sale_orders SET status = 'completed', updated_at = NOW() WHERE id = $sale_order_id");
    }
    if (!$standalone_mi && $sale_order_id > 0) {
        auragold_sync_sale_order_department($conn, $sale_order_id, $department_id);
        auragold_sync_sale_order_sales_person($conn, $sale_order_id, $sales_person_post);
    }
    require_once __DIR__ . '/../includes/auragold_notifications.php';
    $rmi = @getRecord('SELECT material_issue_no, customer_name, order_date, due_date FROM tbl_material_issues WHERE id = ' . (int) $jwo_id . ' LIMIT 1');
    if (is_array($rmi)) {
        $dd = isset($rmi['due_date']) && $rmi['due_date'] !== null && trim((string) $rmi['due_date']) !== ''
            ? substr(trim((string) $rmi['due_date']), 0, 10) : '';
        $od = substr(trim((string) ($rmi['order_date'] ?? '')), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $od)) {
            $od = date('Y-m-d');
        }
        auragold_notify_document_saved($conn, [
            'label' => 'Material Issue',
            'verb' => 'updated',
            'number' => trim((string) ($rmi['material_issue_no'] ?? '')),
            'party' => trim((string) ($rmi['customer_name'] ?? '')),
            'doc_date' => $od,
            'due_date' => $dd,
            'ref_id' => (int) $jwo_id,
        ]);
    }
    echo json_encode(['status' => 'success', 'message' => 'Material issue updated', 'jwo_id' => $jwo_id]);
    exit;
}

if (count($items) === 1) {
    $grand_total = 0;
    foreach ($items as $item) {
        $grand_total += (float)($item['net_amt_with_tax'] ?? $item['net_amount'] ?? 0);
    }
    $grand_total = round($grand_total, 2);
}

$cfg_mi = function_exists('getMaterialIssueBillSeriesConfig')
    ? getMaterialIssueBillSeriesConfig($conn)
    : ['prefix' => 'MI-', 'suffix' => '', 'start_count' => 1, 'from_series_table' => false];
$material_issue_no = function_exists('getNextMaterialIssueNo') ? getNextMaterialIssueNo($conn) : 'MI-1';
$material_issue_no_esc = mysqli_real_escape_string($conn, $material_issue_no);
$guard_no = 0;
while (auragold_material_issue_no_taken($conn, $material_issue_no_esc, $mi_scope_sql) && $guard_no < 5000) {
    $material_issue_no = function_exists('bumpMaterialIssueNo') ? bumpMaterialIssueNo($conn, $material_issue_no, $cfg_mi) : ($material_issue_no . '-1');
    $material_issue_no_esc = mysqli_real_escape_string($conn, $material_issue_no);
    $guard_no++;
}

$status_esc = mysqli_real_escape_string($conn, $jwo_status);
$sid_sql = $standalone_mi ? 'NULL' : (string)(int)$sale_order_id;
$ins_master = "INSERT INTO tbl_material_issues (material_issue_no, sale_order_id, sale_order_no, customer_name, order_date, due_date, grand_total, status, "
    . ($has_mi_branch ? 'branch_id, ' : '')
    . "created_at) VALUES ('$material_issue_no_esc', $sid_sql, '$sale_order_no', '$customer_name', " . ($order_date !== 'NULL' ? "'$order_date'" : 'NULL') . ", $due_date, $grand_total, '$status_esc', "
    . ($has_mi_branch ? ((int) $hdr_mi_branch > 0 ? (int) $hdr_mi_branch : 'NULL') . ', ' : '')
    . 'NOW())';
if (!mysqli_query($conn, $ins_master)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create material issue: ' . mysqli_error($conn)]);
    exit;
}

$new_jwo_id = mysqli_insert_id($conn);

if ($department_id !== null && $department_id > 0) {
    mysqli_query($conn, "UPDATE tbl_material_issues SET department_id = " . (int)$department_id . " WHERE id = $new_jwo_id");
}
$priority_esc = mysqli_real_escape_string($conn, $priority);
mysqli_query($conn, "UPDATE tbl_material_issues SET priority = '$priority_esc' WHERE id = $new_jwo_id");
if ($department_user_id_provided) {
    if ($department_user_id !== null && $department_user_id > 0) {
        mysqli_query($conn, "UPDATE tbl_material_issues SET department_user_id = " . (int)$department_user_id . " WHERE id = $new_jwo_id");
    } else {
        mysqli_query($conn, "UPDATE tbl_material_issues SET department_user_id = NULL WHERE id = $new_jwo_id");
    }
}

foreach ($items as $item) {
    $product_id = (int)($item['product_id'] ?? 0);
    $characteristic_id = isset($item['characteristic_id']) && $item['characteristic_id'] !== '' ? (int)$item['characteristic_id'] : null;
    $barcode = mysqli_real_escape_string($conn, $item['barcode'] ?? '');
    $product_name = mysqli_real_escape_string($conn, $item['product_name'] ?? '');
    $design_no = mysqli_real_escape_string($conn, $item['design_no'] ?? '');
    $carat = mysqli_real_escape_string($conn, $item['carat'] ?? '');
    $quantity = (float)($item['quantity'] ?? 1);
    $gross_weight = (float)($item['gross_weight'] ?? 0);
    $less_weight = (float)($item['less_weight'] ?? 0);
    $purity = (float)($item['purity'] ?? 0);
    $purity_weight = (float)($item['purity_weight'] ?? 0);
    $requested_purity = (float)($item['requested_purity'] ?? 0);
    $requested_wt = (float)($item['requested_wt'] ?? 0);
    $alloy_wt = (float)($item['alloy_wt'] ?? 0);
    $final_weight = (float)($item['final_weight'] ?? 0);
    $net_weight = (float)($item['net_weight'] ?? 0);
    $pure_weight = (float)($item['pure_weight'] ?? 0);
    $rate = (float)($item['rate'] ?? 0);
    $making_amount = (float)($item['making_amount'] ?? 0);
    $amount = (float)($item['amount'] ?? 0);
    $tax_amount = (float)($item['tax'] ?? 0);
    $net_amount = (float)($item['net_amount'] ?? 0);
    $net_amt_with_tax = (float)($item['net_amt_with_tax'] ?? 0);

    $char_sql = $characteristic_id !== null ? $characteristic_id : 'NULL';
    $barcode_sql = $barcode !== '' ? "'$barcode'" : 'NULL';
    $design_sql = $design_no !== '' ? "'$design_no'" : 'NULL';
    $carat_sql = $carat !== '' ? "'$carat'" : 'NULL';

    $ins_item = "INSERT INTO tbl_material_issue_items (material_issue_id, product_id, product_characteristic_id, barcode, product_name, design_no, carat, quantity, gross_weight, less_weight, purity, purity_weight, requested_purity, requested_wt, alloy_wt, final_weight, net_weight, pure_weight, rate, making_amount, amount, tax_amount, net_amount, net_amt_with_tax, status, created_at) VALUES ($new_jwo_id, $product_id, $char_sql, $barcode_sql, '$product_name', $design_sql, $carat_sql, $quantity, $gross_weight, $less_weight, $purity, $purity_weight, $requested_purity, $requested_wt, $alloy_wt, $final_weight, $net_weight, $pure_weight, $rate, $making_amount, $amount, $tax_amount, $net_amount, $net_amt_with_tax, 1, NOW())";
    mysqli_query($conn, $ins_item);
    $mi_line_id = (int) mysqli_insert_id($conn);
    require_once dirname(__DIR__) . '/includes/stock_history_audit_journal.php';
    $mi_dt_new = $standalone_mi
        ? ($header_order_date !== '' ? substr($header_order_date, 0, 10) : date('Y-m-d'))
        : (!empty($sale_order['order_date']) ? substr(trim((string) $sale_order['order_date']), 0, 10) : date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mi_dt_new)) {
        $mi_dt_new = date('Y-m-d');
    }
    auragold_stock_history_audit_for_document_barcode_line($conn, 'Material Issue', $material_issue_no, $mi_dt_new, 'MI', (int) $new_jwo_id, $mi_line_id, 'mi', array_merge($item, [
        'product_id' => $product_id,
        'product_characteristic_id' => $characteristic_id !== null ? (int) $characteristic_id : 0,
    ]));
}

if (!$standalone_mi && $sale_order_id > 0) {
    auragold_sync_sale_order_department($conn, $sale_order_id, $department_id);
    auragold_sync_sale_order_sales_person($conn, $sale_order_id, $sales_person_post);
    mysqli_query($conn, "UPDATE tbl_sale_orders SET status = 'processing' WHERE id = $sale_order_id");
}

require_once __DIR__ . '/../includes/auragold_notifications.php';
$rmi_new = @getRecord('SELECT material_issue_no, customer_name, order_date, due_date FROM tbl_material_issues WHERE id = ' . (int) $new_jwo_id . ' LIMIT 1');
if (is_array($rmi_new)) {
    $dd = isset($rmi_new['due_date']) && $rmi_new['due_date'] !== null && trim((string) $rmi_new['due_date']) !== ''
        ? substr(trim((string) $rmi_new['due_date']), 0, 10) : '';
    $od = substr(trim((string) ($rmi_new['order_date'] ?? '')), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $od)) {
        $od = date('Y-m-d');
    }
    auragold_notify_document_saved($conn, [
        'label' => 'Material Issue',
        'verb' => 'created',
        'number' => trim((string) ($rmi_new['material_issue_no'] ?? '')),
        'party' => trim((string) ($rmi_new['customer_name'] ?? '')),
        'doc_date' => $od,
        'due_date' => $dd,
        'ref_id' => (int) $new_jwo_id,
    ]);
}

echo json_encode([
    'status' => 'success',
    'message' => 'Material issue created',
    'jwo_id' => $new_jwo_id,
    'job_work_no' => $material_issue_no,
    'jobwork_no' => $material_issue_no,
    'material_issue_no' => $material_issue_no,
    'sale_order_id' => $standalone_mi ? 0 : $sale_order_id
]);
