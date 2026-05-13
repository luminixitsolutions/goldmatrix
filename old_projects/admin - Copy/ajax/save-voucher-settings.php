<?php
session_start();
require_once dirname(__DIR__) . '/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$table = 'tbl_voucher_settings';
$chk = @mysqli_query($conn, "SHOW TABLES LIKE '$table'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) mysqli_free_result($chk);
    echo json_encode(['status' => 'error', 'message' => 'Voucher settings table not found. Please run sql/create_tbl_voucher_settings.sql first.']);
    exit;
}
mysqli_free_result($chk);

auragold_ensure_branch_id_on_settings_tables($conn);
$settings_bid = auragold_settings_branch_id();
$has_branch_col = auragold_tbl_has_column($conn, $table, 'branch_id');

// Allowed values (whitelist) to avoid injection
$metal_wise_options = ['Gold', 'Silver', 'Platinum', 'Diamond & Stones', 'Imitation Or Watches', 'Other Or Services'];
$minimum_amount_options = ['Amount', 'MakingAmount', 'NetAmount', 'NetAmountWithTax', 'Rate'];
$reverse_calc_options = ['DiscountAmount', 'MakingRate', 'Rate'];
$discount_type_options = ['Fix', 'On Amount', 'On Making Amount', 'On Diamond Amount', 'On Stone Amount', 'On Net Amount'];
$calculation_type_options = ['Fix', 'Quantity X Rate', 'Carat X Rate'];
$stock_check_options = ['Carat', 'GrossWt', 'Quantity'];

$metal_wise = isset($_POST['metal_wise']) ? trim($_POST['metal_wise']) : 'Gold';
if (!in_array($metal_wise, $metal_wise_options, true)) {
    $metal_wise = 'Gold';
}

$minimum_amount_column = isset($_POST['minimum_amount_column']) ? trim($_POST['minimum_amount_column']) : 'Amount';
if (!in_array($minimum_amount_column, $minimum_amount_options, true)) {
    $minimum_amount_column = 'Amount';
}

$reverse_calculation_result_column = isset($_POST['reverse_calculation_result_column']) ? trim($_POST['reverse_calculation_result_column']) : 'MakingRate';
if (!in_array($reverse_calculation_result_column, $reverse_calc_options, true)) {
    $reverse_calculation_result_column = 'MakingRate';
}

$default_discount_type = isset($_POST['default_discount_type']) ? trim($_POST['default_discount_type']) : 'Fix';
if (!in_array($default_discount_type, $discount_type_options, true)) {
    $default_discount_type = 'Fix';
}

$default_calculation_type = isset($_POST['default_calculation_type']) ? trim($_POST['default_calculation_type']) : 'Fix';
if (!in_array($default_calculation_type, $calculation_type_options, true)) {
    $default_calculation_type = 'Fix';
}

$stock_availability_check_by = isset($_POST['stock_availability_check_by']) ? trim($_POST['stock_availability_check_by']) : 'Carat';
if (!in_array($stock_availability_check_by, $stock_check_options, true)) {
    $stock_availability_check_by = 'Carat';
}

$mw_esc = mysqli_real_escape_string($conn, $metal_wise);
$branch_where = ($has_branch_col && $settings_bid > 0) ? (' AND branch_id = ' . (int) $settings_bid) : '';
$existing = getRecord("SELECT id FROM $table WHERE metal_wise = '$mw_esc' $branch_where LIMIT 1");

if ($existing && !empty($existing['id'])) {
    if ($has_branch_col && $settings_bid > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE $table SET minimum_amount_column=?, reverse_calculation_result_column=?, default_discount_type=?, default_calculation_type=?, stock_availability_check_by=?, updated_at=NOW() WHERE metal_wise=? AND branch_id=?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        $bid = (int) $settings_bid;
        mysqli_stmt_bind_param($stmt, 'ssssssi', $minimum_amount_column, $reverse_calculation_result_column, $default_discount_type, $default_calculation_type, $stock_availability_check_by, $metal_wise, $bid);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE $table SET minimum_amount_column=?, reverse_calculation_result_column=?, default_discount_type=?, default_calculation_type=?, stock_availability_check_by=?, updated_at=NOW() WHERE metal_wise=?");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'ssssss', $minimum_amount_column, $reverse_calculation_result_column, $default_discount_type, $default_calculation_type, $stock_availability_check_by, $metal_wise);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} else {
    if ($has_branch_col && $settings_bid > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO $table (branch_id, metal_wise, minimum_amount_column, reverse_calculation_result_column, default_discount_type, default_calculation_type, stock_availability_check_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        $bid = (int) $settings_bid;
        mysqli_stmt_bind_param($stmt, 'issssss', $bid, $metal_wise, $minimum_amount_column, $reverse_calculation_result_column, $default_discount_type, $default_calculation_type, $stock_availability_check_by);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO $table (metal_wise, minimum_amount_column, reverse_calculation_result_column, default_discount_type, default_calculation_type, stock_availability_check_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            exit;
        }
        mysqli_stmt_bind_param($stmt, 'ssssss', $metal_wise, $minimum_amount_column, $reverse_calculation_result_column, $default_discount_type, $default_calculation_type, $stock_availability_check_by);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($ok) {
    echo json_encode(['status' => 'success', 'message' => 'Voucher settings saved successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save voucher settings.']);
}
