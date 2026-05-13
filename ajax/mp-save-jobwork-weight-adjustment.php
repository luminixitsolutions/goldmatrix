<?php
/**
 * Save add / reduce weight line against a job work order (Manufacturing / Jobwork Queue).
 */
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request']);
    exit;
}

$jobwork_order_id = isset($_POST['jobwork_order_id']) ? (int)$_POST['jobwork_order_id'] : 0;
$type_raw = isset($_POST['adjustment_type']) ? strtolower(trim((string)$_POST['adjustment_type'])) : '';
$adjustment_type = ($type_raw === 'add') ? 'add' : 'reduce';
$weight_raw = isset($_POST['weight_grams']) ? trim((string)$_POST['weight_grams']) : '';
$remark = isset($_POST['remark']) ? trim((string)$_POST['remark']) : '';

if ($jobwork_order_id < 1) {
    echo json_encode(['ok' => false, 'message' => 'Job work order is required.']);
    exit;
}

$weight = (float)$weight_raw;
if (!is_finite($weight) || $weight <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Enter a weight greater than zero.']);
    exit;
}
if ($weight > 999999.9999) {
    $weight = 999999.9999;
}

$tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
if (!$tbl || mysqli_num_rows($tbl) === 0) {
    if ($tbl) {
        mysqli_free_result($tbl);
    }
    echo json_encode(['ok' => false, 'message' => 'Job work orders table not found']);
    exit;
}
mysqli_free_result($tbl);

$exists = @mysqli_query($conn, 'SELECT id FROM tbl_jobwork_orders WHERE id = ' . $jobwork_order_id . ' LIMIT 1');
if (!$exists || mysqli_num_rows($exists) === 0) {
    if ($exists) {
        mysqli_free_result($exists);
    }
    echo json_encode(['ok' => false, 'message' => 'Job work order not found.']);
    exit;
}
mysqli_free_result($exists);

$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_weight_adjustments'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    $create = "CREATE TABLE IF NOT EXISTS `tbl_jobwork_weight_adjustments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `jobwork_order_id` int(11) NOT NULL,
      `adjustment_type` enum('add','reduce') NOT NULL DEFAULT 'reduce',
      `weight_grams` decimal(12,4) NOT NULL DEFAULT 0.0000,
      `remark` varchar(500) DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `created_by_user_id` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `jobwork_order_id` (`jobwork_order_id`),
      KEY `adjustment_type` (`adjustment_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!@mysqli_query($conn, $create)) {
        echo json_encode(['ok' => false, 'message' => 'Could not create weight adjustments table. Run admin/sql/create_tbl_jobwork_weight_adjustments.sql']);
        exit;
    }
} else {
    mysqli_free_result($chk);
}

$uid = 0;
if (!empty($_SESSION['Admin']['id'])) {
    $uid = (int)$_SESSION['Admin']['id'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
}

$remark_db = $remark === '' ? '' : $remark;

if ($uid > 0) {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO tbl_jobwork_weight_adjustments (jobwork_order_id, adjustment_type, weight_grams, remark, created_by_user_id) VALUES (?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'isdsi', $jobwork_order_id, $adjustment_type, $weight, $remark_db, $uid);
} else {
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO tbl_jobwork_weight_adjustments (jobwork_order_id, adjustment_type, weight_grams, remark) VALUES (?, ?, ?, ?)'
    );
    if (!$stmt) {
        echo json_encode(['ok' => false, 'message' => 'Database error']);
        exit;
    }
    mysqli_stmt_bind_param($stmt, 'isds', $jobwork_order_id, $adjustment_type, $weight, $remark_db);
}

$ok = mysqli_stmt_execute($stmt);
$new_id = $ok ? mysqli_insert_id($conn) : 0;
$err = mysqli_stmt_error($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    echo json_encode(['ok' => false, 'message' => $err !== '' ? $err : 'Save failed']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => $adjustment_type === 'add' ? 'Add weight saved.' : 'Reduce weight saved.',
    'id' => (int)$new_id,
    'adjustment_type' => $adjustment_type,
    'weight_grams' => $weight,
]);
