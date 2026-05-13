<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/location-helpers.php';
require_once __DIR__ . '/../includes/ensure_customer_ledger_branch_column.php';

header('Content-Type: application/json');

auragold_ensure_customer_ledger_location_columns($conn);
auragold_ensure_customer_ledger_branch_column($conn);

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
if ($customer_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid customer id']);
    exit;
}

$customer = getRecord("SELECT * FROM tbl_customers WHERE id = $customer_id AND status = 1 LIMIT 1");
if (!$customer) {
    echo json_encode(['status' => 'error', 'message' => 'Customer not found']);
    exit;
}

// Fetch opening balance for the requested branch (one opening row per branch; legacy NULL/0 = main).
$customer_name = $customer['name'] ?? '';
$opening_record = null;
$branch_param_explicit = array_key_exists('branch_id', $_GET);
$req_branch = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
if (!$branch_param_explicit && function_exists('auragold_effective_branch_id')) {
    $req_branch = (int) auragold_effective_branch_id();
}
$main_bid = function_exists('auragold_settings_main_branch_id') ? (int) auragold_settings_main_branch_id() : 0;
if (!empty($customer_name)) {
    $esc_name = mysqli_real_escape_string($conn, $customer_name);
    if ($req_branch > 0) {
        if ($main_bid > 0 && $req_branch === $main_bid) {
            $opening_record = getRecord(
                "SELECT balance_amount, debit_amount, credit_amount, branch_id FROM tbl_customer_ledger WHERE (customer_id = $customer_id OR customer_name = '$esc_name') AND status = 1 AND transaction_type = 'opening' AND (branch_id = " . (int) $req_branch . " OR branch_id IS NULL OR branch_id = 0) ORDER BY (branch_id IS NOT NULL AND branch_id > 0) DESC, transaction_date DESC, id DESC LIMIT 1"
            );
        } else {
            $opening_record = getRecord(
                "SELECT balance_amount, debit_amount, credit_amount, branch_id FROM tbl_customer_ledger WHERE (customer_id = $customer_id OR customer_name = '$esc_name') AND status = 1 AND transaction_type = 'opening' AND COALESCE(branch_id, 0) = " . (int) $req_branch . " ORDER BY transaction_date DESC, id DESC LIMIT 1"
            );
        }
    } else {
        if ($branch_param_explicit) {
            $opening_record = getRecord(
                "SELECT balance_amount, debit_amount, credit_amount, branch_id FROM tbl_customer_ledger WHERE (customer_id = $customer_id OR customer_name = '$esc_name') AND status = 1 AND transaction_type = 'opening' AND (branch_id IS NULL OR branch_id = 0) ORDER BY transaction_date DESC, id DESC LIMIT 1"
            );
        } else {
            $opening_record = getRecord("SELECT balance_amount, debit_amount, credit_amount, branch_id FROM tbl_customer_ledger WHERE (customer_id = $customer_id OR customer_name = '$esc_name') AND status = 1 AND transaction_type = 'opening' ORDER BY transaction_date DESC, id DESC LIMIT 1");
        }
    }
}
$opening_balance = 0;
$opening_type = 'Credit';
if ($opening_record && isset($opening_record['balance_amount'])) {
    $ob = (float)$opening_record['balance_amount'];
    $opening_balance = abs($ob);
    $opening_type = ($ob >= 0) ? 'Debit' : 'Credit';
} elseif ($opening_record && ((float)($opening_record['debit_amount'] ?? 0) > 0 || (float)($opening_record['credit_amount'] ?? 0) > 0)) {
    $dr = (float)($opening_record['debit_amount'] ?? 0);
    $cr = (float)($opening_record['credit_amount'] ?? 0);
    $opening_balance = $dr > 0 ? $dr : $cr;
    $opening_type = $dr > 0 ? 'Debit' : 'Credit';
}
$customer['opening_balance'] = $opening_balance;
$customer['opening_type'] = $opening_type;
if ($req_branch > 0) {
    $customer['opening_branch_id'] = $req_branch;
} else {
    $customer['opening_branch_id'] = ($opening_record && isset($opening_record['branch_id']) && (int) $opening_record['branch_id'] > 0)
        ? (int) $opening_record['branch_id'] : 0;
}

// Decode JSON columns safely
$share_holders = [];
if (!empty($customer['share_holders_data'])) {
    $decoded = json_decode($customer['share_holders_data'], true);
    if (is_array($decoded)) $share_holders = $decoded;
}
$customer['share_holders'] = $share_holders;

$share_holder_documents = [];
if (!empty($customer['share_holder_documents'])) {
    $dd = json_decode((string) $customer['share_holder_documents'], true);
    if (is_array($dd)) {
        $share_holder_documents = $dd;
    }
}
$customer['share_holder_documents'] = $share_holder_documents;

echo json_encode([
    'status' => 'success',
    'customer' => $customer
]);

