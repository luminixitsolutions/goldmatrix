<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auragold_product_catalog_scope.php';
require_once __DIR__ . '/../includes/auragold_product_branch_local_schema.php';

header('Content-Type: application/json; charset=utf-8');

$working = (int) ($_SESSION['working_branch_id'] ?? $_SESSION['branch_id'] ?? 0);
if ($working <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Session branch not found.']);
    exit;
}

$branch = getRecordMaster('SELECT id, main_branch_id FROM tbl_branches WHERE id = ' . $working . ' LIMIT 1');
if (!$branch || (int) ($branch['main_branch_id'] ?? 0) <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'This action is only available when logged in to a sub-branch.']);
    exit;
}

$main_id = (int) $branch['main_branch_id'];
$scope = auragold_sql_products_scope_for_branch($main_id);
$where = "($scope) AND status IN (0, 1)";

$search_term = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
if ($search_term !== '') {
    $s = esc($search_term);
    $where .= " AND (name LIKE '%$s%' OR alternate_name LIKE '%$s%' OR article LIKE '%$s%')";
}

$products = getList("SELECT id, name, article, status FROM tbl_products WHERE $where ORDER BY name ASC LIMIT 8000");

$sub_id = (int) $branch['id'];
auragold_ensure_tbl_product_branches_is_active($conn);
$pb_soft = auragold_tbl_product_branches_has_is_active($conn);
$active_map = [];
$tb = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product_branches'");
if ($tb && mysqli_num_rows($tb) > 0) {
    mysqli_free_result($tb);
    $active_sql = $pb_soft
        ? "SELECT product_id FROM tbl_product_branches WHERE branch_id = $sub_id AND IFNULL(is_active, 1) = 1"
        : "SELECT product_id FROM tbl_product_branches WHERE branch_id = $sub_id";
    $active_rows = getList($active_sql);
    foreach ($active_rows as $ar) {
        $active_map[(int) $ar['product_id']] = true;
    }
} elseif ($tb) {
    mysqli_free_result($tb);
}

$out = [];
foreach ($products as $p) {
    $pid = (int) $p['id'];
    $out[] = [
        'id' => $pid,
        'name' => $p['name'],
        'article' => $p['article'] ?? '',
        'status' => (int) ($p['status'] ?? 1),
        'active_here' => !empty($active_map[$pid]),
    ];
}

echo json_encode(['status' => 'success', 'products' => $out]);
