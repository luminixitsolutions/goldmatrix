<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Create contra voucher tables if they do not exist
$t1 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_contra_vouchers'");
if (!$t1 || mysqli_num_rows($t1) === 0) {
    $create_main = "CREATE TABLE IF NOT EXISTS `tbl_contra_vouchers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `voucher_no` varchar(50) NOT NULL,
        `voucher_date` date NOT NULL,
        `total_amount` decimal(15,2) DEFAULT 0.00,
        `comment` text DEFAULT NULL,
        `status` varchar(20) DEFAULT 'draft',
        `created_by` int(11) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `voucher_no` (`voucher_no`),
        KEY `voucher_date` (`voucher_date`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!mysqli_query($conn, $create_main)) {
        echo json_encode(['status' => 'error', 'message' => 'Could not create tbl_contra_vouchers: ' . mysqli_error($conn)]);
        exit;
    }
}
$t2 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_contra_voucher_items'");
if (!$t2 || mysqli_num_rows($t2) === 0) {
    $create_items = "CREATE TABLE IF NOT EXISTS `tbl_contra_voucher_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `voucher_id` int(11) NOT NULL,
        `bank_cash_ac` varchar(100) NOT NULL,
        `ref_no` varchar(100) DEFAULT NULL,
        `ref_date` date DEFAULT NULL,
        `transaction_type` varchar(20) NOT NULL DEFAULT 'withdrawal',
        `amount` decimal(15,2) DEFAULT 0.00,
        `comment` text DEFAULT NULL,
        `status` tinyint(1) DEFAULT 1,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `voucher_id` (`voucher_id`),
        CONSTRAINT `fk_contra_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_contra_vouchers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!mysqli_query($conn, $create_items)) {
        echo json_encode(['status' => 'error', 'message' => 'Could not create tbl_contra_voucher_items: ' . mysqli_error($conn)]);
        exit;
    }
}

mysqli_begin_transaction($conn);

try {
    $voucher_id = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
    $voucher_no = esc($_POST['voucher_no'] ?? '');
    $voucher_date = esc($_POST['voucher_date'] ?? date('Y-m-d'));
    $comment = esc($_POST['comment'] ?? '');
    $items = isset($_POST['items']) ? $_POST['items'] : [];

    if (is_string($items)) {
        $items = json_decode($items, true);
    }
    if (!is_array($items)) {
        $items = [];
    }

    if (empty($voucher_no)) {
        throw new Exception('Voucher number is required.');
    }

    $total_amount = 0;
    foreach ($items as $it) {
        $total_amount += (float)(isset($it['amount']) ? $it['amount'] : 0);
    }

    if ($voucher_id > 0) {
        $exists = getRecord("SELECT id FROM tbl_contra_vouchers WHERE id = $voucher_id");
        if (!$exists) {
            throw new Exception('Voucher not found.');
        }
        mysqli_query($conn, "UPDATE tbl_contra_vouchers SET voucher_date = '$voucher_date', total_amount = $total_amount, comment = " . ($comment ? "'$comment'" : 'NULL') . ", updated_at = NOW() WHERE id = $voucher_id");
        mysqli_query($conn, "DELETE FROM tbl_contra_voucher_items WHERE voucher_id = $voucher_id");
    } else {
        $dup = getRecord("SELECT id FROM tbl_contra_vouchers WHERE voucher_no = '$voucher_no'");
        if ($dup) {
            throw new Exception('Voucher number already exists.');
        }
        $created_by = isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL');
        $ins = "INSERT INTO tbl_contra_vouchers (voucher_no, voucher_date, total_amount, comment, status, created_by, created_at) VALUES ('$voucher_no', '$voucher_date', $total_amount, " . ($comment ? "'$comment'" : 'NULL') . ", 'draft', " . (is_numeric($created_by) ? $created_by : 'NULL') . ", NOW())";
        if (!mysqli_query($conn, $ins)) {
            throw new Exception('Insert failed: ' . mysqli_error($conn));
        }
        $voucher_id = (int)mysqli_insert_id($conn);
    }

    foreach ($items as $it) {
        $bank_cash_ac = esc($it['bank_cash_ac'] ?? '');
        $ref_no = esc($it['ref_no'] ?? '');
        $ref_date = !empty($it['ref_date']) ? esc($it['ref_date']) : null;
        $transaction_type = esc($it['transaction_type'] ?? 'withdrawal');
        if (!in_array($transaction_type, ['deposit', 'withdrawal'])) {
            $transaction_type = 'withdrawal';
        }
        $amount = (float)(isset($it['amount']) ? $it['amount'] : 0);
        $item_comment = esc($it['comment'] ?? '');

        $ref_date_sql = $ref_date ? "'$ref_date'" : 'NULL';
        $ins_item = "INSERT INTO tbl_contra_voucher_items (voucher_id, bank_cash_ac, ref_no, ref_date, transaction_type, amount, comment, status) VALUES ($voucher_id, '$bank_cash_ac', " . ($ref_no ? "'$ref_no'" : 'NULL') . ", $ref_date_sql, '$transaction_type', $amount, " . ($item_comment ? "'$item_comment'" : 'NULL') . ", 1)";
        if (!mysqli_query($conn, $ins_item)) {
            throw new Exception('Item insert failed: ' . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);
    echo json_encode(['status' => 'success', 'message' => 'Saved successfully.', 'voucher_id' => $voucher_id]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
