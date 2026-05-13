<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
    exit;
}

try {
    $user_id = isset($_SESSION['Admin']['id']) ? (int)$_SESSION['Admin']['id'] : 0;
    
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        $item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
        $characteristic_id = isset($_POST['characteristic_id']) ? (int)$_POST['characteristic_id'] : 0;
        $voucher = isset($_POST['voucher']) ? trim($_POST['voucher']) : '';
    } else {
        $item_id = isset($data['item_id']) ? (int)$data['item_id'] : 0;
        $characteristic_id = isset($data['characteristic_id']) ? (int)$data['characteristic_id'] : 0;
        $voucher = isset($data['voucher']) ? trim($data['voucher']) : '';
    }
    
    $by_characteristic = ($voucher === 'product_opening' && $characteristic_id > 0);
    if (!$by_characteristic && $item_id <= 0) {
        throw new Exception('Invalid item ID');
    }
    if ($by_characteristic && $characteristic_id <= 0) {
        throw new Exception('Invalid characteristic ID');
    }
    
    mysqli_begin_transaction($conn);
    
    try {
        if ($by_characteristic) {
            $delete_sql = "DELETE FROM tbl_stock_journal WHERE product_characteristic_id = $characteristic_id";
        } else {
            $delete_sql = "DELETE FROM tbl_stock_journal WHERE item_id = $item_id";
        }
        
        // Option 2: Soft delete (set status to inactive) - uncomment if preferred
        // $delete_sql = "UPDATE tbl_stock_journal SET status = 'inactive', updated_at = NOW() WHERE item_id = $item_id AND status = 'active'";
        
        if (!mysqli_query($conn, $delete_sql)) {
            throw new Exception('Failed to delete stock journal entries: ' . mysqli_error($conn));
        }
        
        $deleted_count = mysqli_affected_rows($conn);
        
        mysqli_commit($conn);
        
        echo json_encode([
            'status' => 'success',
            'message' => "Successfully deleted $deleted_count stock journal entry/entries",
            'deleted_count' => $deleted_count
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    error_log("Delete Stock Journal Error: " . $e->getMessage());
}
