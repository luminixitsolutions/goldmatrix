<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$limit = isset($_GET['limit']) ? min(500, (int)$_GET['limit']) : 100;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = "1=1";
$params = [];
if ($search !== '') {
    $esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (voucher_no LIKE '%$esc%' OR customer_name LIKE '%$esc%' OR ref_no LIKE '%$esc%')";
}

$vouchers = getList("
    SELECT id, voucher_no, customer_name, ref_no, voucher_date, total_amount, total_gold, total_silver,
           sales_person, against, against_of, currency, comment, status, created_at
    FROM tbl_payment_vouchers
    WHERE $where
    ORDER BY id DESC
    LIMIT $limit
");

echo json_encode([
    'status' => 'success',
    'vouchers' => $vouchers
]);
