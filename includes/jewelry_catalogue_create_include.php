<?php

require_once __DIR__ . '/jewelry_catalogue_create_schema.php';
require_once __DIR__ . '/jewelry_catalog_stock_include.php';

/**
 * Resolve tbl_voucher_types.id for Jewellery Catalogue (bill-series.php voucher type).
 */
function auragold_jewelry_catalogue_voucher_type_id(mysqli $conn): int
{
    $names = [
        'jewellery catalogue',
        'jewelry catalogue',
        'jewellery catalog',
        'jewelry catalog',
        'jewellery catelog',
        'jewelry catelog',
    ];
    foreach ($names as $nm) {
        $esc = mysqli_real_escape_string($conn, $nm);
        $r = getRecord(
            "SELECT id FROM tbl_voucher_types WHERE status = 1 AND LOWER(TRIM(name)) = '$esc' LIMIT 1"
        );
        if ($r && !empty($r['id'])) {
            return (int) $r['id'];
        }
        $r2 = getRecord(
            "SELECT id FROM tbl_voucher_types WHERE status = 1 AND LOWER(TRIM(COALESCE(type_of_voucher,''))) = '$esc' LIMIT 1"
        );
        if ($r2 && !empty($r2['id'])) {
            return (int) $r2['id'];
        }
    }

    return 0;
}

/**
 * Bill series for Jewellery Catalogue (tbl_bill_series via bill-series.php).
 *
 * @return array{prefix:string,suffix:string,start_count:int,from_series_table:bool,voucher_type_id?:int}
 */
function auragold_jewelry_catalogue_bill_series_config(mysqli $conn): array
{
    $legacy = ['prefix' => 'JC-', 'suffix' => '', 'start_count' => 1, 'from_series_table' => false];
    $vtId = auragold_jewelry_catalogue_voucher_type_id($conn);

    $tableCheck = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_bill_series'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        if ($tableCheck) {
            mysqli_free_result($tableCheck);
        }
        return $legacy;
    }
    mysqli_free_result($tableCheck);

    if ($vtId <= 0) {
        return $legacy;
    }

    $series = function_exists('auragold_bill_series_row_for_voucher_type')
        ? auragold_bill_series_row_for_voucher_type($conn, $vtId)
        : null;
    if (!$series || trim((string) ($series['prefix'] ?? '')) === '') {
        return $legacy;
    }

    return [
        'prefix' => (string) $series['prefix'],
        'suffix' => (string) ($series['suffix'] ?? ''),
        'start_count' => (int) ($series['start_count'] ?? 0),
        'from_series_table' => true,
        'voucher_type_id' => $vtId,
    ];
}

/**
 * Next design number: prefix + serial + suffix (e.g. JC-1, JC-2) from bill series + max in tbl_jewelry_catalogue.
 */
function auragold_next_jewelry_catalogue_design_no(mysqli $conn, int $excludeCatalogueId = 0): string
{
    $cfg = auragold_jewelry_catalogue_bill_series_config($conn);
    $prefix = (string) $cfg['prefix'];
    $suffix = (string) $cfg['suffix'];
    $startEff = max(1, (int) ($cfg['start_count'] ?? 1));

    $prefixEsc = mysqli_real_escape_string($conn, $prefix);
    $sql = "SELECT design_no FROM tbl_jewelry_catalogue
            WHERE status = 1 AND design_no IS NOT NULL AND TRIM(design_no) <> ''
            AND design_no LIKE '" . $prefixEsc . "%'";
    if ($excludeCatalogueId > 0) {
        $sql .= ' AND id <> ' . $excludeCatalogueId;
    }
    $branchId = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
    if ($branchId <= 0 && !empty($_SESSION['working_branch_id'])) {
        $branchId = (int) $_SESSION['working_branch_id'];
    }
    if ($branchId > 0 && function_exists('auragold_tbl_has_column')
        && auragold_tbl_has_column($conn, 'tbl_jewelry_catalogue', 'branch_id')) {
        $sql .= ' AND (branch_id = ' . $branchId . ' OR branch_id IS NULL OR branch_id = 0)';
    }
    $rows = getList($sql);
    if (!is_array($rows)) {
        $rows = [];
    }

    $maxNum = 0;
    $regex = '/^' . preg_quote($prefix, '/') . '(\d+)' . preg_quote($suffix, '/') . '$/i';
    foreach ($rows as $row) {
        $dn = trim((string) ($row['design_no'] ?? ''));
        if ($dn !== '' && preg_match($regex, $dn, $m)) {
            $maxNum = max($maxNum, (int) $m[1]);
        }
    }
    $nextNum = max($maxNum + 1, $startEff);

    return $prefix . $nextNum . $suffix;
}

/**
 * Bump design no on duplicate (save collision).
 */
function auragold_bump_jewelry_catalogue_design_no(mysqli $conn, string $designNo, array $cfg): string
{
    $prefix = (string) ($cfg['prefix'] ?? 'JC-');
    $suffix = (string) ($cfg['suffix'] ?? '');
    $regex = '/^' . preg_quote($prefix, '/') . '(\d+)' . preg_quote($suffix, '/') . '$/i';
    if (preg_match($regex, trim($designNo), $m)) {
        return $prefix . ((int) $m[1] + 1) . $suffix;
    }

    return auragold_next_jewelry_catalogue_design_no($conn);
}

/**
 * Assign next design no when empty (new catalogue).
 */
function auragold_jewelry_catalogue_ensure_design_no(mysqli $conn, string $designNo, int $catalogueId = 0): string
{
    $designNo = trim($designNo);
    if ($designNo !== '') {
        return $designNo;
    }

    return auragold_next_jewelry_catalogue_design_no($conn, $catalogueId);
}

/**
 * @return array<string, mixed>
 */
function auragold_jewelry_catalogue_blank_row(): array
{
    return [
        'id' => 0,
        'metal_id' => 0,
        'product_id' => 0,
        'category_id' => 0,
        'title' => '',
        'short_desc' => '',
        'full_desc' => '',
        'barcode' => '',
        'design_no' => '',
        'sku' => '',
        'weight' => '',
        'amount' => '',
        'images' => [],
        'bom' => [],
        'fill_dmd_gms_rate' => 0,
        'sale_order_id' => 0,
        'sale_order_item_id' => 0,
        'repair_order_id' => 0,
        'repair_order_item_id' => 0,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function auragold_jewelry_catalogue_load_by_id(mysqli $conn, int $id): ?array
{
    auragold_ensure_jewelry_catalogue_table($conn);
    if ($id <= 0) {
        return null;
    }
    $row = getRecord('SELECT * FROM tbl_jewelry_catalogue WHERE id = ' . $id . ' AND status = 1 LIMIT 1');
    if (!$row) {
        return null;
    }
    return auragold_jewelry_catalogue_normalize_db_row($row);
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function auragold_jewelry_catalogue_normalize_db_row(array $row): array
{
    $images = [];
    if (!empty($row['images_json'])) {
        $dec = json_decode((string) $row['images_json'], true);
        if (is_array($dec)) {
            $images = $dec;
        }
    }
    $bom = [];
    if (!empty($row['bom_json'])) {
        $dec = json_decode((string) $row['bom_json'], true);
        if (is_array($dec)) {
            $bom = $dec;
        }
    }

    return [
        'id' => (int) ($row['id'] ?? 0),
        'metal_id' => (int) ($row['metal_id'] ?? 0),
        'product_id' => (int) ($row['product_id'] ?? 0),
        'category_id' => (int) ($row['category_id'] ?? 0),
        'title' => (string) ($row['title'] ?? ''),
        'short_desc' => (string) ($row['short_desc'] ?? ''),
        'full_desc' => (string) ($row['full_desc'] ?? ''),
        'barcode' => (string) ($row['barcode'] ?? ''),
        'design_no' => (string) ($row['design_no'] ?? ''),
        'sku' => (string) ($row['sku'] ?? ''),
        'weight' => $row['weight'] !== null && $row['weight'] !== '' ? (string) $row['weight'] : '',
        'amount' => $row['amount'] !== null && $row['amount'] !== '' ? number_format((float) $row['amount'], 2, '.', '') : '',
        'images' => $images,
        'bom' => $bom,
        'fill_dmd_gms_rate' => (int) ($row['fill_dmd_gms_rate'] ?? 0),
        'sale_order_id' => (int) ($row['sale_order_id'] ?? 0),
        'sale_order_item_id' => (int) ($row['sale_order_item_id'] ?? 0),
        'repair_order_id' => (int) ($row['repair_order_id'] ?? 0),
        'repair_order_item_id' => (int) ($row['repair_order_item_id'] ?? 0),
    ];
}

/**
 * Prefill from sale / repair order line (new catalogue).
 *
 * @return array<string, mixed>
 */
function auragold_jewelry_catalogue_prefill_from_order(
    mysqli $conn,
    string $orderKind,
    int $orderId,
    int $itemId
): array {
    $out = auragold_jewelry_catalogue_blank_row();
    if ($orderId <= 0 || $itemId <= 0) {
        return $out;
    }

    $orderKind = strtolower(trim($orderKind));
    if ($orderKind === 'repair') {
        $item = getRecord(
            'SELECT roi.*, ro.order_no, p.category_id, p.name AS product_master_name, pc.metal_id, pc.sku_code
             FROM tbl_repair_order_items roi
             INNER JOIN tbl_repair_orders ro ON roi.order_id = ro.id
             LEFT JOIN tbl_products p ON roi.product_id = p.id
             LEFT JOIN tbl_product_characteristics pc ON roi.product_characteristic_id = pc.id
             WHERE roi.id = ' . $itemId . ' AND roi.order_id = ' . $orderId . ' LIMIT 1'
        );
        if (!$item) {
            return $out;
        }
        $out['repair_order_id'] = $orderId;
        $out['repair_order_item_id'] = $itemId;
    } else {
        $item = getRecord(
            'SELECT soi.*, so.order_no, p.category_id, p.name AS product_master_name, pc.metal_id, pc.sku_code
             FROM tbl_sale_order_items soi
             INNER JOIN tbl_sale_orders so ON soi.order_id = so.id
             LEFT JOIN tbl_products p ON soi.product_id = p.id
             LEFT JOIN tbl_product_characteristics pc ON soi.product_characteristic_id = pc.id
             WHERE soi.id = ' . $itemId . ' AND soi.order_id = ' . $orderId . ' LIMIT 1'
        );
        if (!$item) {
            return $out;
        }
        $out['sale_order_id'] = $orderId;
        $out['sale_order_item_id'] = $itemId;
    }

    $productName = trim((string) ($item['product_name'] ?? ''));
    if ($productName === '') {
        $productName = trim((string) ($item['product_master_name'] ?? ''));
    }

    $out['product_id'] = (int) ($item['product_id'] ?? 0);
    $out['category_id'] = (int) ($item['category_id'] ?? 0);
    $out['metal_id'] = (int) ($item['metal_id'] ?? 0);
    $out['title'] = $productName;
    $out['short_desc'] = $productName;
    $out['barcode'] = trim((string) ($item['barcode'] ?? ''));
    $out['design_no'] = trim((string) ($item['design_no'] ?? ''));
    $out['sku'] = trim((string) ($item['sku_code'] ?? ''));
    $fw = (float) ($item['final_weight'] ?? 0);
    $out['weight'] = $fw > 0 ? number_format($fw, 3, '.', '') : '';
    $amt = (float) ($item['amount'] ?? 0);
    if ($amt <= 0) {
        $amt = (float) ($item['net_amount'] ?? 0);
    }
    $out['amount'] = $amt > 0 ? number_format($amt, 2, '.', '') : '';

    if ($out['metal_id'] <= 0 && $out['product_id'] > 0) {
        $pcRow = getRecord(
            'SELECT metal_id FROM tbl_product_characteristics
             WHERE product_id = ' . (int) $out['product_id'] . ' AND status = 1
             ORDER BY id ASC LIMIT 1'
        );
        if ($pcRow && !empty($pcRow['metal_id'])) {
            $out['metal_id'] = (int) $pcRow['metal_id'];
        }
    }

    if (!empty($item['images']) && function_exists('auragold_uploads_public_url')) {
        $dec = @json_decode((string) $item['images'], true);
        if ($dec && !empty($dec['images']) && is_array($dec['images'])) {
            foreach ($dec['images'] as $imgPath) {
                if ($imgPath === '' || $imgPath === null) {
                    continue;
                }
                $out['images'][] = [
                    'path' => (string) $imgPath,
                    'url' => auragold_uploads_public_url((string) $imgPath),
                ];
            }
        }
    }

    auragold_ensure_jewelry_catalogue_table($conn);
    $existing = null;
    if ($orderKind === 'repair') {
        $existing = getRecord(
            'SELECT id FROM tbl_jewelry_catalogue
             WHERE repair_order_item_id = ' . $itemId . ' AND status = 1 ORDER BY id DESC LIMIT 1'
        );
    } else {
        $existing = getRecord(
            'SELECT id FROM tbl_jewelry_catalogue
             WHERE sale_order_item_id = ' . $itemId . ' AND status = 1 ORDER BY id DESC LIMIT 1'
        );
    }
    if ($existing && !empty($existing['id'])) {
        $loaded = auragold_jewelry_catalogue_load_by_id($conn, (int) $existing['id']);
        if ($loaded) {
            return $loaded;
        }
    }

    if (trim((string) ($out['design_no'] ?? '')) === '') {
        $out['design_no'] = auragold_next_jewelry_catalogue_design_no($conn);
    }

    return $out;
}

/**
 * @param array<string, mixed> $payload
 * @return array{success: bool, message: string, id?: int}
 */
function auragold_jewelry_catalogue_save(mysqli $conn, array $payload): array
{
    auragold_ensure_jewelry_catalogue_table($conn);

    $title = trim((string) ($payload['title'] ?? ''));
    $shortDesc = trim((string) ($payload['short_desc'] ?? ''));
    if ($title === '') {
        return ['success' => false, 'message' => 'Title is required.'];
    }
    if ($shortDesc === '') {
        return ['success' => false, 'message' => 'Short description is required.'];
    }

    $weightRaw = trim((string) ($payload['weight'] ?? ''));
    if ($weightRaw === '' || !is_numeric($weightRaw)) {
        return ['success' => false, 'message' => 'Weight is required.'];
    }
    $amountRaw = trim((string) ($payload['amount'] ?? ''));
    if ($amountRaw === '' || !is_numeric($amountRaw)) {
        return ['success' => false, 'message' => 'Amount is required.'];
    }

    $id = (int) ($payload['id'] ?? 0);
    $metalId = (int) ($payload['metal_id'] ?? 0);
    $productId = (int) ($payload['product_id'] ?? 0);
    $categoryId = (int) ($payload['category_id'] ?? 0);
    $barcode = trim((string) ($payload['barcode'] ?? ''));
    $designNo = trim((string) ($payload['design_no'] ?? ''));
    $cfgDesign = auragold_jewelry_catalogue_bill_series_config($conn);
    $sku = trim((string) ($payload['sku'] ?? ''));
    $fullDesc = (string) ($payload['full_desc'] ?? '');
    $fillDmd = !empty($payload['fill_dmd_gms_rate']) ? 1 : 0;
    $saleOrderId = (int) ($payload['sale_order_id'] ?? 0);
    $saleItemId = (int) ($payload['sale_order_item_id'] ?? 0);
    $repairOrderId = (int) ($payload['repair_order_id'] ?? 0);
    $repairItemId = (int) ($payload['repair_order_item_id'] ?? 0);

    $images = $payload['images'] ?? [];
    if (!is_array($images)) {
        $images = [];
    }
    $bom = $payload['bom'] ?? [];
    if (!is_array($bom)) {
        $bom = [];
    }

    $branchId = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
    if ($branchId <= 0 && !empty($_SESSION['working_branch_id'])) {
        $branchId = (int) $_SESSION['working_branch_id'];
    } elseif ($branchId <= 0 && !empty($_SESSION['branch_id'])) {
        $branchId = (int) $_SESSION['branch_id'];
    }

    $createdBy = 0;
    if (!empty($_SESSION['Admin']['id'])) {
        $createdBy = (int) $_SESSION['Admin']['id'];
    } elseif (!empty($_SESSION['user_id'])) {
        $createdBy = (int) $_SESSION['user_id'];
    }

    $imagesJson = mysqli_real_escape_string($conn, json_encode(array_values($images), JSON_UNESCAPED_UNICODE));
    $bomJson = mysqli_real_escape_string($conn, json_encode(array_values($bom), JSON_UNESCAPED_UNICODE));

    $esc = static function ($v) use ($conn) {
        return mysqli_real_escape_string($conn, (string) $v);
    };

    $sets = [
        'metal_id = ' . ($metalId > 0 ? $metalId : 'NULL'),
        'product_id = ' . ($productId > 0 ? $productId : 'NULL'),
        'category_id = ' . ($categoryId > 0 ? $categoryId : 'NULL'),
        "title = '" . $esc($title) . "'",
        "short_desc = '" . $esc($shortDesc) . "'",
        "full_desc = '" . $esc($fullDesc) . "'",
        "barcode = " . ($barcode !== '' ? "'" . $esc($barcode) . "'" : 'NULL'),
        "design_no = " . ($designNo !== '' ? "'" . $esc($designNo) . "'" : 'NULL'),
        "sku = " . ($sku !== '' ? "'" . $esc($sku) . "'" : 'NULL'),
        'weight = ' . (float) $weightRaw,
        'amount = ' . (float) $amountRaw,
        "images_json = '" . $imagesJson . "'",
        "bom_json = '" . $bomJson . "'",
        'fill_dmd_gms_rate = ' . $fillDmd,
        'sale_order_id = ' . ($saleOrderId > 0 ? $saleOrderId : 'NULL'),
        'sale_order_item_id = ' . ($saleItemId > 0 ? $saleItemId : 'NULL'),
        'repair_order_id = ' . ($repairOrderId > 0 ? $repairOrderId : 'NULL'),
        'repair_order_item_id = ' . ($repairItemId > 0 ? $repairItemId : 'NULL'),
        'branch_id = ' . ($branchId > 0 ? $branchId : 'NULL'),
        'updated_at = NOW()',
    ];

    if ($id > 0) {
        if ($designNo === '') {
            $existingDn = getRecord('SELECT design_no FROM tbl_jewelry_catalogue WHERE id = ' . $id . ' LIMIT 1');
            $designNo = trim((string) ($existingDn['design_no'] ?? ''));
        }
        if ($designNo === '') {
            $designNo = auragold_next_jewelry_catalogue_design_no($conn, $id);
        }
        $sets = array_filter($sets, static function ($s) {
            return strpos($s, 'design_no =') !== 0;
        });
        $sets[] = "design_no = '" . $esc($designNo) . "'";

        $sql = 'UPDATE tbl_jewelry_catalogue SET ' . implode(', ', $sets) . ' WHERE id = ' . $id . ' LIMIT 1';
        if (!mysqli_query($conn, $sql)) {
            return ['success' => false, 'message' => 'Could not update catalogue: ' . mysqli_error($conn)];
        }
        return ['success' => true, 'message' => 'Catalogue saved.', 'id' => $id, 'design_no' => $designNo];
    }

    $designNo = auragold_jewelry_catalogue_ensure_design_no($conn, $designNo, 0);
    $guard = 0;
    while ($guard < 5000) {
        $dup = getRecord(
            "SELECT id FROM tbl_jewelry_catalogue WHERE status = 1 AND design_no = '" . $esc($designNo) . "' LIMIT 1"
        );
        if (!$dup) {
            break;
        }
        $designNo = auragold_bump_jewelry_catalogue_design_no($conn, $designNo, $cfgDesign);
        $guard++;
    }
    $sets = array_filter($sets, static function ($s) {
        return strpos($s, 'design_no =') !== 0;
    });
    $sets[] = "design_no = '" . $esc($designNo) . "'";

    $sql = 'INSERT INTO tbl_jewelry_catalogue SET ' . implode(', ', $sets)
        . ', status = 1, created_by = ' . ($createdBy > 0 ? $createdBy : 'NULL')
        . ', created_at = NOW()';
    if (!mysqli_query($conn, $sql)) {
        return ['success' => false, 'message' => 'Could not save catalogue: ' . mysqli_error($conn)];
    }
    $newId = (int) mysqli_insert_id($conn);

    return ['success' => true, 'message' => 'Catalogue created.', 'id' => $newId, 'design_no' => $designNo];
}
