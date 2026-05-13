<?php
ob_start();
session_start();
require_once '../config.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Create journal voucher tables if they do not exist
$t1 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_journal_vouchers'");
if (!$t1 || mysqli_num_rows($t1) === 0) {
    $create_main = "CREATE TABLE IF NOT EXISTS `tbl_journal_vouchers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `voucher_no` varchar(50) NOT NULL,
        `voucher_date` date NOT NULL,
        `comment` text DEFAULT NULL,
        `credit_wt` decimal(15,4) DEFAULT 0.0000,
        `debit_wt` decimal(15,4) DEFAULT 0.0000,
        `debit_total` decimal(15,2) DEFAULT 0.00,
        `credit_total` decimal(15,2) DEFAULT 0.00,
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
        echo json_encode(['status' => 'error', 'message' => 'Could not create tbl_journal_vouchers: ' . mysqli_error($conn)]);
        exit;
    }
}
$t2 = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_journal_voucher_items'");
if (!$t2 || mysqli_num_rows($t2) === 0) {
    $create_items = "CREATE TABLE IF NOT EXISTS `tbl_journal_voucher_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `voucher_id` int(11) NOT NULL,
        `branch_id` int(11) DEFAULT NULL,
        `branch_name` varchar(100) DEFAULT NULL,
        `account_ledger` varchar(200) NOT NULL,
        `cr_dr` varchar(10) NOT NULL DEFAULT 'Dr',
        `against` varchar(200) DEFAULT NULL,
        `ref_no` varchar(100) DEFAULT NULL,
        `ref_date` date DEFAULT NULL,
        `amount` decimal(15,2) DEFAULT 0.00,
        `metal` varchar(50) DEFAULT NULL,
        `purity_wt` decimal(15,4) DEFAULT 0.0000,
        `status` tinyint(1) DEFAULT 1,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `voucher_id` (`voucher_id`),
        KEY `branch_id` (`branch_id`),
        CONSTRAINT `fk_journal_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `tbl_journal_vouchers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!mysqli_query($conn, $create_items)) {
        echo json_encode(['status' => 'error', 'message' => 'Could not create tbl_journal_voucher_items: ' . mysqli_error($conn)]);
        exit;
    }
}

// esc() is defined in config.php

mysqli_begin_transaction($conn);

try {
    $voucher_id = isset($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : 0;
    $voucher_no = esc($_POST['voucher_no'] ?? '');
    $voucher_date = esc($_POST['voucher_date'] ?? date('Y-m-d'));
    $comment = esc($_POST['comment'] ?? '');
    $credit_wt = (float)($_POST['credit_wt'] ?? 0);
    $debit_wt = (float)($_POST['debit_wt'] ?? 0);
    $debit_total = (float)($_POST['debit_total'] ?? 0);
    $credit_total = (float)($_POST['credit_total'] ?? 0);
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

    if ($voucher_id > 0) {
        $exists = getRecord("SELECT id FROM tbl_journal_vouchers WHERE id = $voucher_id");
        if (!$exists) {
            throw new Exception('Voucher not found.');
        }
        $upd = "UPDATE tbl_journal_vouchers SET voucher_date = '$voucher_date', comment = " . ($comment ? "'$comment'" : 'NULL') . ", credit_wt = $credit_wt, debit_wt = $debit_wt, debit_total = $debit_total, credit_total = $credit_total, updated_at = NOW() WHERE id = $voucher_id";
        if (!mysqli_query($conn, $upd)) {
            throw new Exception('Update failed: ' . mysqli_error($conn));
        }
        mysqli_query($conn, "DELETE FROM tbl_journal_voucher_items WHERE voucher_id = $voucher_id");
    } else {
        $dup = getRecord("SELECT id FROM tbl_journal_vouchers WHERE voucher_no = '$voucher_no'");
        if ($dup) {
            throw new Exception('Voucher number already exists.');
        }
        $created_by = isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL');
        $ins = "INSERT INTO tbl_journal_vouchers (voucher_no, voucher_date, comment, credit_wt, debit_wt, debit_total, credit_total, status, created_by, created_at) VALUES ('$voucher_no', '$voucher_date', " . ($comment ? "'$comment'" : 'NULL') . ", $credit_wt, $debit_wt, $debit_total, $credit_total, 'draft', " . (is_numeric($created_by) ? $created_by : 'NULL') . ", NOW())";
        if (!mysqli_query($conn, $ins)) {
            throw new Exception('Insert failed: ' . mysqli_error($conn));
        }
        $voucher_id = (int)mysqli_insert_id($conn);
    }

    foreach ($items as $it) {
        $branch_id = isset($it['branch_id']) ? (int)$it['branch_id'] : 0;
        $branch_id_sql = ($branch_id > 0) ? $branch_id : 'NULL';
        $branch_name = esc($it['branch_name'] ?? '');
        $account_ledger = esc($it['account_ledger'] ?? '');
        $cr_dr = in_array($it['cr_dr'] ?? '', ['Cr', 'Dr']) ? $it['cr_dr'] : 'Dr';
        $against = esc($it['against'] ?? '');
        $ref_no = esc($it['ref_no'] ?? '');
        $ref_date = !empty($it['ref_date']) ? "'" . esc($it['ref_date']) . "'" : 'NULL';
        $amount = (float)($it['amount'] ?? 0);
        $metal = esc($it['metal'] ?? '');
        $purity_wt = (float)($it['purity_wt'] ?? 0);

        $ins_item = "INSERT INTO tbl_journal_voucher_items (voucher_id, branch_id, branch_name, account_ledger, cr_dr, against, ref_no, ref_date, amount, metal, purity_wt, status) VALUES ($voucher_id, $branch_id_sql, " . ($branch_name ? "'$branch_name'" : 'NULL') . ", '$account_ledger', '$cr_dr', " . ($against ? "'$against'" : 'NULL') . ", " . ($ref_no ? "'$ref_no'" : 'NULL') . ", $ref_date, $amount, " . ($metal ? "'$metal'" : 'NULL') . ", $purity_wt, 1)";
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
