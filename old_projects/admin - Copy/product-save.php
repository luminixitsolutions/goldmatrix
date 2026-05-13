<?php
session_start();
require_once "config.php";
require_once __DIR__ . '/includes/barcode_prefix_check.php';
require_once __DIR__ . '/includes/auragold_product_branch_login_context.php';
require_once __DIR__ . "/includes/product_opening_save_core.php";

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

$product_id_for_prefix = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

$current_branch_id = 0;
if (!empty($_SESSION['working_branch_id'])) {
    $current_branch_id = (int) $_SESSION['working_branch_id'];
} elseif (!empty($_SESSION['branch_id'])) {
    $current_branch_id = (int) $_SESSION['branch_id'];
}
if ($current_branch_id <= 0 && isset($_POST['branch_id']) && (int) $_POST['branch_id'] > 0) {
    $current_branch_id = (int) $_POST['branch_id'];
}
if ($current_branch_id <= 0 && !empty($_POST['branch_ids']) && is_array($_POST['branch_ids'])) {
    foreach ($_POST['branch_ids'] as $bid) {
        $bid = (int) $bid;
        if ($bid > 0) {
            $current_branch_id = $bid;
            break;
        }
    }
}

$prefixes_to_check = [];
if (!empty($_POST['row']) && is_array($_POST['row'])) {
    foreach ($_POST['row'] as $r) {
        if (empty($r['is_selected'])) {
            continue;
        }
        $p = trim((string) ($r['barcode_prefix'] ?? ''));
        if ($p !== '') {
            $prefixes_to_check[$p] = true;
        }
    }
}
foreach (array_keys($prefixes_to_check) as $prefix) {
    $chk = checkBarcodePrefix($prefix, $current_branch_id, $product_id_for_prefix);
    if (empty($chk['ok'])) {
        $t = (string) ($chk['type'] ?? '');
        if ($t === 'other_branch') {
            $msg = 'Barcode prefix already used in another branch';
        } else {
            $msg = 'Barcode prefix already exists in this branch';
        }
        echo json_encode([
            'status'  => false,
            'message' => $msg,
            'type'    => $t !== '' ? $t : 'current_branch',
        ]);
        exit;
    }
}

$ctx         = auragold_product_opening_mysqli_for_login($conn);
$ctxOk       = !empty($ctx['ok']) && $ctx['link'] instanceof mysqli;
$saveConn    = $ctxOk ? $ctx['link'] : $conn;
$closeAfter  = $ctxOk && !empty($ctx['close_after']);
if (!$ctxOk) {
    $msg = isset($ctx['message']) && (string) $ctx['message'] !== ''
        ? (string) $ctx['message']
        : 'Could not determine which database to save the product. Check branch setup.';
    echo json_encode(['status' => 'error', 'message' => $msg]);
    exit;
}

mysqli_begin_transaction($saveConn);

try {

    $result = auragold_product_opening_save($saveConn, $_POST, []);

    mysqli_commit($saveConn);

} catch (Exception $e) {

    mysqli_rollback($saveConn);

    if ($closeAfter) {
        mysqli_close($saveConn);
    }

    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
    exit;
}

if ($closeAfter) {
    mysqli_close($saveConn);
}

$payload = [
    'status'     => 'success',
    'message'    => 'Product saved successfully',
    'product_id' => $result['product_id'],
];

echo json_encode($payload);
