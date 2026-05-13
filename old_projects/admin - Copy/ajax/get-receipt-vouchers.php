<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Get vouchers from tbl_receipt_vouchers
$vouchers = getList("
    SELECT 
        rv.*,
        (SELECT COUNT(*) FROM tbl_receipt_voucher_items WHERE voucher_id = rv.id) as item_count
    FROM tbl_receipt_vouchers rv 
    ORDER BY rv.id DESC 
    LIMIT $limit OFFSET $offset
");

// Get items for each voucher
foreach ($vouchers as &$voucher) {
    $items = getList("SELECT * FROM tbl_receipt_voucher_items WHERE voucher_id = {$voucher['id']} ORDER BY id ASC");
    $voucher['items'] = $items;
}

echo json_encode([
    'status' => 'success',
    'vouchers' => $vouchers,
    'count' => count($vouchers)
]);
?>
