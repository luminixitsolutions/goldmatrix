<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid id']);
    exit;
}

$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_order_items'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    echo json_encode(['ok' => true, 'items' => []]);
    exit;
}
mysqli_free_result($chk);

$items = function_exists('getList')
    ? getList("SELECT * FROM tbl_jobwork_order_items WHERE jobwork_order_id = $id ORDER BY id ASC")
    : [];
if (!is_array($items)) {
    $items = [];
}

echo json_encode(['ok' => true, 'items' => $items]);
