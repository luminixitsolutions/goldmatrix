<?php
/**
 * Sub-branch only: remove product from this branch (tbl_product_branches row).
 * Does not delete tbl_products or stock.
 */
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    echo json_encode(['status' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
if ($product_id <= 0) {
    echo json_encode(['status' => false, 'message' => 'Invalid product ID']);
    exit;
}

$working = (int) ($_SESSION['working_branch_id'] ?? $_SESSION['branch_id'] ?? 0);
if ($working <= 0 || !$conn_master) {
    echo json_encode(['status' => false, 'message' => 'Branch context not found.']);
    exit;
}

$branch = getRecordMaster('SELECT id, main_branch_id FROM tbl_branches WHERE id = ' . $working . ' LIMIT 1');
if (!$branch || (int) ($branch['main_branch_id'] ?? 0) <= 0) {
    echo json_encode(['status' => false, 'message' => 'This action is only available on a sub-branch.']);
    exit;
}

$tb = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product_branches'");
if (!$tb || mysqli_num_rows($tb) === 0) {
    if ($tb) {
        mysqli_free_result($tb);
    }
    echo json_encode(['status' => false, 'message' => 'Product branch assignment table is not available.']);
    exit;
}
mysqli_free_result($tb);

$sub_id = (int) $branch['id'];
$exists = getRecord(
    "SELECT 1 AS ok FROM tbl_product_branches WHERE branch_id = $sub_id AND product_id = $product_id LIMIT 1"
);
if (!$exists) {
    echo json_encode(['status' => false, 'message' => 'This product is not assigned to your branch.']);
    exit;
}

if (!mysqli_query(
    $conn,
    "DELETE FROM tbl_product_branches WHERE branch_id = $sub_id AND product_id = $product_id"
)) {
    echo json_encode(['status' => false, 'message' => mysqli_error($conn)]);
    exit;
}

echo json_encode([
    'status'  => true,
    'message' => 'Product removed from this branch. It remains on the main branch.',
]);
