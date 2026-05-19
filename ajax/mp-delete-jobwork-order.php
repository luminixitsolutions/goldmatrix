<?php
/**
 * Manufacturing Process — delete a job work order (items + related rows, then master).
 */
session_start();
require_once __DIR__ . '/../config.php';
if (is_file(__DIR__ . '/../includes/auragold_sale_order_jobwork_lock.php')) {
    require_once __DIR__ . '/../includes/auragold_sale_order_jobwork_lock.php';
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request']);
    exit;
}

$id = isset($_POST['jobwork_order_id']) ? (int)$_POST['jobwork_order_id'] : 0;
if ($id < 1) {
    echo json_encode(['ok' => false, 'message' => 'Invalid job work order']);
    exit;
}

$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    echo json_encode(['ok' => false, 'message' => 'Table not found']);
    exit;
}
mysqli_free_result($chk);

$exists = @mysqli_query($conn, 'SELECT id FROM tbl_jobwork_orders WHERE id = ' . $id . ' LIMIT 1');
if (!$exists || mysqli_num_rows($exists) === 0) {
    if ($exists) {
        mysqli_free_result($exists);
    }
    echo json_encode(['ok' => false, 'message' => 'Job work order not found']);
    exit;
}
mysqli_free_result($exists);

$inv_chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_invoices'");
if ($inv_chk && mysqli_num_rows($inv_chk) > 0) {
    mysqli_free_result($inv_chk);
    $inv = @mysqli_query($conn, 'SELECT id FROM tbl_jobwork_invoices WHERE jobwork_order_id = ' . $id . ' LIMIT 1');
    if ($inv && mysqli_num_rows($inv) > 0) {
        mysqli_free_result($inv);
        echo json_encode(['ok' => false, 'message' => 'Cannot delete: a Jobwork Invoice exists for this order.']);
        exit;
    }
    if ($inv) {
        mysqli_free_result($inv);
    }
} elseif ($inv_chk) {
    mysqli_free_result($inv_chk);
}

mysqli_begin_transaction($conn);

// Jobwork Queue (activity, diamond issue, etc.) must be removed before JWO master.
if (function_exists('auragold_jobwork_order_delete_queue_records')) {
    auragold_jobwork_order_delete_queue_records($conn, $id);
} else {
    $act_chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
    if ($act_chk && mysqli_num_rows($act_chk) > 0) {
        mysqli_free_result($act_chk);
        @mysqli_query($conn, 'DELETE FROM tbl_jobwork_queue_activity WHERE jobwork_order_id = ' . $id);
    } elseif ($act_chk) {
        mysqli_free_result($act_chk);
    }
}

@mysqli_query($conn, 'DELETE FROM tbl_jobwork_order_items WHERE jobwork_order_id = ' . $id);

$extra_tables = ['tbl_jobwork_order_comments', 'tbl_jobwork_weight_adjustments'];
foreach ($extra_tables as $t) {
    $tq = @mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $t) . "'");
    if ($tq && mysqli_num_rows($tq) > 0) {
        mysqli_free_result($tq);
        @mysqli_query($conn, 'DELETE FROM `' . $t . '` WHERE jobwork_order_id = ' . $id);
    } elseif ($tq) {
        mysqli_free_result($tq);
    }
}

$del = @mysqli_query($conn, 'DELETE FROM tbl_jobwork_orders WHERE id = ' . $id . ' LIMIT 1');
$ok = $del && mysqli_affected_rows($conn) > 0;

if ($ok) {
    mysqli_commit($conn);
    echo json_encode(['ok' => true, 'message' => 'Deleted.', 'id' => $id]);
} else {
    mysqli_rollback($conn);
    echo json_encode(['ok' => false, 'message' => 'Could not delete job work order.']);
}
