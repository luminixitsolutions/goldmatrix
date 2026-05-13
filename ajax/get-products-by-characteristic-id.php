<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$characteristic_id = isset($_GET['characteristic_id']) ? (int)$_GET['characteristic_id'] : 0;

if ($characteristic_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid characteristic ID']);
    exit;
}

// Return only the single product for this product characteristic (used for product_opening barcode creation)
$query = "
    SELECT
        p.id,
        p.name,
        p.alternate_name,
        p.article,
        p.category_id,
        pc.id as characteristic_id,
        pc.sku_code,
        pc.barcode,
        pc.carat,
        pc.opening_purity,
        pc.opening_weight,
        pc.final_weight,
        pc.rate,
        pc.value,
        pc.making_on,
        pc.diamond_category,
        pc.barcode_prefix,
        pc.barcode_digits,
        m.display_name as metal_name,
        m.id as metal_id
    FROM tbl_product_characteristics pc
    INNER JOIN tbl_products p ON pc.product_id = p.id
    INNER JOIN tbl_metal m ON pc.metal_id = m.id
    WHERE pc.id = $characteristic_id
      AND p.status = 1
      AND pc.status = 1
    ORDER BY p.name ASC, pc.id ASC
";

$products = getList($query);

if (empty($products)) {
    echo json_encode(['success' => false, 'message' => 'Product characteristic not found']);
    exit;
}

echo json_encode(['success' => true, 'products' => $products]);
