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
    echo json_encode(['status' => 'error', 'message' => 'This action is only available for a sub-branch.']);
    exit;
}

$main_id = (int) $branch['main_branch_id'];
$sub_id = (int) $branch['id'];

$tb = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product_branches'");
if (!$tb || mysqli_num_rows($tb) === 0) {
    if ($tb) {
        mysqli_free_result($tb);
    }
    echo json_encode(['status' => 'error', 'message' => 'tbl_product_branches is missing in this database.']);
    exit;
}
mysqli_free_result($tb);
auragold_ensure_tbl_product_branches_is_active($conn);
$pb_soft = auragold_tbl_product_branches_has_is_active($conn);

$active_ids = [];
$payload = null;
$raw = file_get_contents('php://input');
if ($raw !== '' && $raw !== false) {
    $payload = json_decode($raw, true);
}
if (is_array($payload) && isset($payload['active_product_ids']) && is_array($payload['active_product_ids'])) {
    foreach ($payload['active_product_ids'] as $pid) {
        $pid = (int) $pid;
        if ($pid > 0) {
            $active_ids[] = $pid;
        }
    }
} elseif (isset($_POST['active_product_ids']) && is_array($_POST['active_product_ids'])) {
    foreach ($_POST['active_product_ids'] as $pid) {
        $pid = (int) $pid;
        if ($pid > 0) {
            $active_ids[] = $pid;
        }
    }
}
$active_ids = array_values(array_unique($active_ids));

$scope = auragold_sql_products_scope_for_branch($main_id);
$main_rows = getList("SELECT id FROM tbl_products WHERE ($scope) AND status IN (0, 1)");
$main_ids = [];
foreach ($main_rows as $mr) {
    $main_ids[] = (int) $mr['id'];
}
$main_ids = array_values(array_unique($main_ids));

$active_allowed = array_values(array_intersect($active_ids, $main_ids));
$to_remove = array_values(array_diff($main_ids, $active_allowed));

mysqli_begin_transaction($conn);

foreach ($to_remove as $pid) {
    $pid = (int) $pid;
    if ($pb_soft) {
        if (!mysqli_query($conn, "UPDATE tbl_product_branches SET is_active = 0 WHERE branch_id = $sub_id AND product_id = $pid")) {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            exit;
        }
    } else {
        if (!mysqli_query($conn, "DELETE FROM tbl_product_branches WHERE branch_id = $sub_id AND product_id = $pid")) {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            exit;
        }
    }
}
foreach ($active_allowed as $pid) {
    $pid = (int) $pid;
    if ($pb_soft) {
        if (!mysqli_query(
            $conn,
            "INSERT INTO tbl_product_branches (product_id, branch_id, is_active) VALUES ($pid, $sub_id, 1)
                ON DUPLICATE KEY UPDATE is_active = 1"
        )) {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            exit;
        }
    } else {
        if (!mysqli_query(
            $conn,
            "INSERT IGNORE INTO tbl_product_branches (product_id, branch_id) VALUES ($pid, $sub_id)"
        )) {
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            exit;
        }
    }
}
mysqli_commit($conn);

echo json_encode([
    'status' => 'success',
    'message' => 'Active products for this branch have been updated.',
    'activated' => count($active_allowed),
]);
