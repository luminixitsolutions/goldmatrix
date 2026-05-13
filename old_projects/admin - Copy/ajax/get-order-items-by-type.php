<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$exclude_return_id = isset($_GET['exclude_return_id']) ? (int)$_GET['exclude_return_id'] : 0;
$for_sale_return = isset($_GET['for_sale_return']) ? (int)$_GET['for_sale_return'] : 0;

if ($order_id <= 0 || $type === '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid order_id or type']);
    exit;
}

if ($for_sale_return === 1 && function_exists('auragold_ensure_sale_return_item_source_against_id')) {
    auragold_ensure_sale_return_item_source_against_id($conn);
}

$type = esc($type);
$items = [];

if ($type === 'Sale Order') {
    $items = getList("SELECT * FROM tbl_sale_order_items WHERE order_id = $order_id ORDER BY id ASC");
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
    }
} elseif ($type === 'Repair Order') {
    $items = getList("SELECT * FROM tbl_repair_order_items WHERE order_id = $order_id ORDER BY id ASC");
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
    }
} elseif ($type === 'Delivery Note') {
    $tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_delivery_note_items'");
    if ($tbl && mysqli_num_rows($tbl) > 0) {
        mysqli_free_result($tbl);
        $items = getList("SELECT * FROM tbl_delivery_note_items WHERE delivery_note_id = $order_id ORDER BY id ASC");
        foreach ($items as &$item) {
            $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
            $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
            $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
        }
    }
} elseif ($type === 'Sale Invoice') {
    if ($for_sale_return === 1) {
        $pend = function_exists('auragold_sale_return_pending_invoice_item_predicate_sql')
            ? auragold_sale_return_pending_invoice_item_predicate_sql($exclude_return_id, 'si')
            : '1=1';
        $items = getList("SELECT si.* FROM tbl_sale_invoice_items si WHERE si.invoice_id = $order_id AND ($pend) ORDER BY si.id ASC");
    } else {
        $items = getList("SELECT * FROM tbl_sale_invoice_items WHERE invoice_id = $order_id ORDER BY id ASC");
    }
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
        $item['net_amt_with_tax'] = $item['net_amt_with_tax'] ?? $item['net_amt_tax'] ?? null;
        if ($for_sale_return === 1) {
            $item['source_against_item_id'] = isset($item['id']) ? (int)$item['id'] : 0;
        }
    }
    unset($item);
} elseif ($type === 'Sale Quotation') {
    if ($for_sale_return === 1) {
        $pend = function_exists('auragold_sale_return_pending_quotation_item_predicate_sql')
            ? auragold_sale_return_pending_quotation_item_predicate_sql($exclude_return_id, 'sqi')
            : '1=1';
        $items = getList("SELECT sqi.* FROM tbl_sale_quotation_items sqi WHERE sqi.quotation_id = $order_id AND ($pend) ORDER BY sqi.id ASC");
    } else {
        $items = getList("SELECT * FROM tbl_sale_quotation_items WHERE quotation_id = $order_id ORDER BY id ASC");
    }
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
        $item['net_amt_with_tax'] = $item['net_amt_with_tax'] ?? $item['net_amt_tax'] ?? null;
        if ($for_sale_return === 1) {
            $item['source_against_item_id'] = isset($item['id']) ? (int)$item['id'] : 0;
        }
    }
    unset($item);
} elseif ($type === 'Consignment Out') {
    $items = getList("SELECT * FROM tbl_consignment_out_items WHERE consignment_id = $order_id ORDER BY id ASC");
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
    }
    unset($item);
} elseif ($type === 'Consignment In') {
    $items = getList("SELECT * FROM tbl_consignment_in_items WHERE consignment_id = $order_id ORDER BY id ASC");
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
    }
    unset($item);
} elseif ($type === 'Purchase Quotation') {
    $items = getList("SELECT * FROM tbl_purchase_quotation_items WHERE quotation_id = $order_id ORDER BY id ASC");
    foreach ($items as &$item) {
        $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
        $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
        $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
    }
    unset($item);
} elseif ($type === 'Purchase Order') {
    $pot = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_purchase_order_items'");
    if ($pot && mysqli_num_rows($pot) > 0) {
        mysqli_free_result($pot);
        $items = getList("SELECT * FROM tbl_purchase_order_items WHERE order_id = $order_id ORDER BY id ASC");
        foreach ($items as &$item) {
            $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
            $item['product_characteristic_id'] = $item['product_characteristic_id'] ?? $item['characteristic_id'] ?? null;
            $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
        }
        unset($item);
    }
} elseif ($type === 'Task / Event') {
    $items = [];
} elseif ($type === 'Old Jewellery Scrap Invoice') {
    $pot = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_old_jewelry_scrap_invoice_items'");
    if ($pot && mysqli_num_rows($pot) > 0) {
        mysqli_free_result($pot);
        $items = getList("SELECT * FROM tbl_old_jewelry_scrap_invoice_items WHERE invoice_id = $order_id AND (status IS NULL OR status = 1) ORDER BY id ASC");
        foreach ($items as &$item) {
            $item['barcode_no'] = $item['barcode_no'] ?? $item['barcode'] ?? '';
            $item['product_name'] = $item['product_name'] ?? $item['description'] ?? '';
            $item['description'] = $item['description'] ?? $item['product_name'] ?? '';
            $item['gross_weight'] = isset($item['gross_weight']) ? $item['gross_weight'] : ($item['gross_wt'] ?? 0);
            $item['final_weight'] = isset($item['final_weight']) ? $item['final_weight'] : ($item['final_wt'] ?? 0);
            $item['net_weight'] = isset($item['net_weight']) ? $item['net_weight'] : ($item['net_wt'] ?? 0);
            $item['pure_weight'] = isset($item['pure_weight']) ? $item['pure_weight'] : ($item['pure_wt'] ?? 0);
            $item['less_weight'] = isset($item['less_weight']) ? $item['less_weight'] : ($item['less_wt'] ?? 0);
            $item['tax_amount'] = $item['tax_amount'] ?? $item['tax'] ?? 0;
            $item['making_amount'] = $item['making_amount'] ?? $item['making'] ?? 0;
            $item['net_amount'] = $item['net_amount'] ?? $item['net_amt'] ?? 0;
            $item['product_id'] = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            $item['product_characteristic_id'] = isset($item['product_characteristic_id']) ? (int) $item['product_characteristic_id'] : 0;
            $item['metal_id'] = isset($item['metal_id']) ? (int) $item['metal_id'] : 0;
        }
        unset($item);
    } else {
        $items = [];
    }
}

echo json_encode([
    'status' => 'success',
    'items' => $items
]);
?>
