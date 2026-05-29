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
 * Catalogue headers for Jewellery Catalogue grid (tbl_jewelry_catalogue).
 *
 * @param array<string, mixed> $opts Same filter keys as stock fetch (metal_id, branch_id, product_id, category_id, q, …)
 * @return list<array<string, mixed>>
 */
function auragold_jewelry_catalogue_grid_fetch(mysqli $conn, array $opts = [], string $siteUrl = ''): array
{
    auragold_ensure_jewelry_catalogue_table($conn);

    $metal_filter = (int) ($opts['metal_id'] ?? 0);
    $branch_filter = (int) ($opts['branch_id'] ?? 0);
    $product_filter = (int) ($opts['product_id'] ?? 0);
    $category_filter = (int) ($opts['category_id'] ?? 0);
    $search = mb_strtolower(trim((string) ($opts['q'] ?? '')));
    $barcode_f = trim((string) ($opts['barcode'] ?? ''));
    $design_f = trim((string) ($opts['design_no'] ?? ''));

    $where = ' WHERE jc.status = 1 ';
    if ($metal_filter > 0) {
        $where .= ' AND jc.metal_id = ' . $metal_filter;
    }
    if ($branch_filter > 0 && function_exists('auragold_tbl_has_column')
        && auragold_tbl_has_column($conn, 'tbl_jewelry_catalogue', 'branch_id')) {
        $where .= ' AND (jc.branch_id = ' . $branch_filter . ' OR jc.branch_id IS NULL OR jc.branch_id = 0)';
    }
    if ($product_filter > 0) {
        $where .= ' AND jc.product_id = ' . $product_filter;
    }
    if ($category_filter > 0) {
        $where .= ' AND jc.category_id = ' . $category_filter;
    }
    if ($barcode_f !== '') {
        $esc = mysqli_real_escape_string($conn, $barcode_f);
        $where .= " AND jc.barcode LIKE '%{$esc}%'";
    }
    if ($design_f !== '') {
        $esc = mysqli_real_escape_string($conn, $design_f);
        $where .= " AND jc.design_no LIKE '%{$esc}%'";
    }
    if ($branch_filter <= 0 && function_exists('auragold_effective_branch_list_scope_sql')) {
        $scope = auragold_effective_branch_list_scope_sql($conn, 'tbl_jewelry_catalogue');
        if ($scope !== '') {
            $where .= str_replace('branch_id', 'jc.branch_id', $scope);
        }
    }

    $join = '';
    $metalSel = "'' AS metal_name";
    $prodSel = "'' AS product_name";
    if (function_exists('gas_tbl_exists')) {
        if (gas_tbl_exists($conn, 'tbl_metal')) {
            $join .= ' LEFT JOIN tbl_metal m ON m.id = jc.metal_id ';
            $metalSel = "COALESCE(NULLIF(TRIM(m.display_name),''), NULLIF(TRIM(m.system_name),''), '') AS metal_name";
        }
        if (gas_tbl_exists($conn, 'tbl_products')) {
            $join .= ' LEFT JOIN tbl_products p ON p.id = jc.product_id ';
            $prodSel = "COALESCE(NULLIF(TRIM(p.name),''), '') AS product_name";
        }
    }

    $sql = "SELECT jc.*, {$metalSel}, {$prodSel}
            FROM tbl_jewelry_catalogue jc
            {$join}
            {$where}
            ORDER BY jc.id DESC
            LIMIT 5000";
    $rows = getList($sql);
    if (!is_array($rows)) {
        return [];
    }

    $out = [];
    foreach ($rows as $row) {
        $item = auragold_jewelry_catalogue_row_to_grid_item($row, $siteUrl);
        if ($search !== '') {
            $hay = mb_strtolower(implode(' ', [
                $item['barcode'] ?? '',
                $item['title'] ?? '',
                $item['product_name'] ?? '',
                $item['design_no'] ?? '',
                $item['metal_name'] ?? '',
            ]));
            if (mb_strpos($hay, $search) === false) {
                continue;
            }
        }
        $out[] = $item;
    }

    return $out;
}

/**
 * @param array<string, mixed> $row DB row with optional metal_name, product_name
 * @return array<string, mixed>
 */
function auragold_jewelry_catalogue_row_to_grid_item(array $row, string $siteUrl = ''): array
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

    $thumb = '';
    foreach ($images as $img) {
        if (!is_array($img)) {
            continue;
        }
        $url = trim((string) ($img['url'] ?? ''));
        if ($url === '' && !empty($img['path']) && function_exists('gas_public_url_for_stored_path')) {
            $url = gas_public_url_for_stored_path((string) $img['path'], $siteUrl);
        }
        if ($url !== '') {
            $thumb = $url;
            break;
        }
    }

    $barcode = trim((string) ($row['barcode'] ?? ''));
    $designNo = trim((string) ($row['design_no'] ?? ''));
    $title = trim((string) ($row['title'] ?? ''));
    $productName = trim((string) ($row['product_name'] ?? ''));
    if ($productName === '' && $title !== '') {
        $productName = $title;
    }
    $metalName = trim((string) ($row['metal_name'] ?? ''));
    $wt = (float) ($row['weight'] ?? 0);
    $amount = (float) ($row['amount'] ?? 0);
    $bomCount = count($bom);

    return [
        'catalogue_id' => (int) ($row['id'] ?? 0),
        'stock_id' => 0,
        'barcode' => $barcode !== '' ? $barcode : ('JC-' . (int) ($row['id'] ?? 0)),
        'metal_id' => (int) ($row['metal_id'] ?? 0),
        'metal_name' => $metalName,
        'product_name' => $productName,
        'article' => $designNo,
        'carat' => '',
        'category' => '',
        'branch_name' => '',
        'current_qty' => 1,
        'current_weight' => $wt,
        'qty_label' => '1',
        'weight_label' => function_exists('gas_fmt_num') ? gas_fmt_num($wt, 3) : number_format($wt, 3, '.', ''),
        'thumb_url' => $thumb,
        'image_urls' => $images,
        'subtitle' => $designNo,
        'title' => $title !== '' ? $title : ($productName !== '' ? $productName . ' — ' . $metalName : $metalName),
        'active' => 'Active',
        'jewelry_catalogue' => 'Yes',
        'design_no' => $designNo !== '' ? $designNo : ('Catalogue #' . (int) ($row['id'] ?? 0)),
        'variants' => trim((string) ($row['sku'] ?? '')),
        'bill_of_material' => $bomCount > 0
            ? (($bomCount === 1 && !empty($bom[0]['description']))
                ? (string) $bom[0]['description']
                : ($bomCount . ' item(s)'))
            : '',
        'amount' => $amount,
        'amount_label' => function_exists('gas_fmt_money') ? gas_fmt_money($amount) : number_format($amount, 2, '.', ''),
        'location' => '',
        'is_catalogue_only' => true,
    ];
}

/**
 * Attach catalogue_id to stock rows; append catalogue-only entries.
 *
 * @param list<array<string, mixed>> $stockItems
 * @param list<array<string, mixed>> $catalogueItems
 * @return list<array<string, mixed>>
 */
function auragold_jewelry_catalog_merge_stock_and_catalogue(array $stockItems, array $catalogueItems): array
{
    $byBarcode = [];
    foreach ($stockItems as $idx => $it) {
        $bc = strtolower(trim((string) ($it['barcode'] ?? '')));
        if ($bc !== '') {
            $byBarcode[$bc] = $idx;
        }
    }

    $merged = $stockItems;
    $catalogueOnly = [];

    foreach ($catalogueItems as $cat) {
        $cid = (int) ($cat['catalogue_id'] ?? 0);
        $bc = strtolower(trim((string) ($cat['barcode'] ?? '')));
        if ($bc !== '' && isset($byBarcode[$bc])) {
            $merged[$byBarcode[$bc]]['catalogue_id'] = $cid;
            $merged[$byBarcode[$bc]]['is_catalogue_only'] = false;
            if (empty($merged[$byBarcode[$bc]]['thumb_url']) && !empty($cat['thumb_url'])) {
                $merged[$byBarcode[$bc]]['thumb_url'] = $cat['thumb_url'];
            }
            continue;
        }
        $catalogueOnly[] = $cat;
    }

    // Catalogue-only designs first (newest first — grid_fetch is ORDER BY id DESC).
    return array_merge($catalogueOnly, $merged);
}

/**
 * Active catalogue design numbers for product modal dropdown.
 *
 * @return list<array{id:int,design_no:string,title:string,metal_id:int,product_name:string}>
 */
function auragold_jewelry_catalogue_list_design_nos(mysqli $conn, int $metalId = 0): array
{
    auragold_ensure_jewelry_catalogue_table($conn);

    $where = ' WHERE jc.status = 1 AND jc.design_no IS NOT NULL AND TRIM(jc.design_no) <> \'\' ';
    if ($metalId > 0) {
        $where .= ' AND jc.metal_id = ' . (int) $metalId;
    }
    if (function_exists('auragold_effective_branch_list_scope_sql')) {
        $scope = auragold_effective_branch_list_scope_sql($conn, 'tbl_jewelry_catalogue');
        if ($scope !== '') {
            $where .= str_replace('branch_id', 'jc.branch_id', $scope);
        }
    }

    $join = '';
    $prodSel = "'' AS product_name";
    if (function_exists('gas_tbl_exists') && gas_tbl_exists($conn, 'tbl_products')) {
        $join = ' LEFT JOIN tbl_products p ON p.id = jc.product_id ';
        $prodSel = "COALESCE(NULLIF(TRIM(p.name),''), '') AS product_name";
    }

    $sql = "SELECT jc.id, jc.design_no, jc.title, jc.metal_id, {$prodSel}
            FROM tbl_jewelry_catalogue jc
            {$join}
            {$where}
            ORDER BY jc.id DESC
            LIMIT 2000";
    $rows = getList($sql);
    if (!is_array($rows)) {
        return [];
    }

    $out = [];
    foreach ($rows as $row) {
        $dn = trim((string) ($row['design_no'] ?? ''));
        if ($dn === '') {
            continue;
        }
        $title = trim((string) ($row['title'] ?? ''));
        $productName = trim((string) ($row['product_name'] ?? ''));
        $label = $dn;
        if ($title !== '') {
            $label .= ' — ' . $title;
        } elseif ($productName !== '') {
            $label .= ' — ' . $productName;
        }
        $out[] = [
            'id' => (int) ($row['id'] ?? 0),
            'design_no' => $dn,
            'title' => $title,
            'metal_id' => (int) ($row['metal_id'] ?? 0),
            'product_name' => $productName,
            'label' => $label,
        ];
    }

    return $out;
}

/**
 * @param array<string, mixed> $item
 * @param array<string, mixed> $catalogue
 * @return array<string, mixed>
 */
function auragold_jewelry_catalogue_bom_item_to_modal_row(array $item, array $catalogue): array
{
    if (!empty($item['_modal']) && is_array($item['_modal'])) {
        $md = $item['_modal'];
        $catDn = trim((string) ($catalogue['design_no'] ?? ''));
        if ($catDn !== '' && trim((string) ($md['design_no'] ?? '')) === '') {
            $md['design_no'] = $catDn;
        }

        return $md;
    }

    $catDn = trim((string) ($catalogue['design_no'] ?? ''));
    $desc = trim((string) ($item['description'] ?? ''));
    $qty = trim((string) ($item['quantity'] ?? '1'));
    if ($qty === '' || !is_numeric($qty)) {
        $qty = '1';
    }

    return [
        'product_name' => $desc,
        'barcode' => trim((string) ($item['barcode'] ?? '')),
        'design_no' => trim((string) ($item['design_no'] ?? '')) !== '' ? trim((string) $item['design_no']) : $catDn,
        'quantity' => (float) $qty,
        'gross_wt' => (float) ($item['gross_wt'] ?? 0),
        'final_wt' => (float) ($item['final_wt'] ?? 0),
        'net_wt' => (float) ($item['net_wt'] ?? 0),
        'pure_wt' => (float) ($item['pure_wt'] ?? 0),
        'making_amount' => (float) ($item['making'] ?? 0),
        'tax' => (float) ($item['tax'] ?? 0),
        'metal_id' => (int) ($catalogue['metal_id'] ?? 0),
        'amount' => (float) ($item['making'] ?? 0),
        'calculation_type' => 'Rate X Gross Wt',
    ];
}

/**
 * Expand BOM (incl. merged group_items) to modal row payloads for product selection.
 *
 * @param list<array<string, mixed>> $bom
 * @return list<array<string, mixed>>
 */
function auragold_jewelry_catalogue_bom_to_modal_rows(array $bom, array $catalogue): array
{
    $out = [];
    foreach ($bom as $item) {
        if (!is_array($item)) {
            continue;
        }
        if (!empty($item['group_items']) && is_array($item['group_items'])) {
            foreach ($item['group_items'] as $gi) {
                if (!is_array($gi)) {
                    continue;
                }
                $out[] = auragold_jewelry_catalogue_bom_item_to_modal_row($gi, $catalogue);
            }
            continue;
        }
        $out[] = auragold_jewelry_catalogue_bom_item_to_modal_row($item, $catalogue);
    }

    return $out;
}

/**
 * Load catalogue header + BOM lines for sale invoice product modal.
 *
 * @return array<string, mixed>|null
 */
function auragold_jewelry_catalogue_get_for_modal(mysqli $conn, string $designNo = '', int $catalogueId = 0): ?array
{
    auragold_ensure_jewelry_catalogue_table($conn);
    $designNo = trim($designNo);
    $row = null;
    if ($catalogueId > 0) {
        $row = getRecord('SELECT * FROM tbl_jewelry_catalogue WHERE id = ' . (int) $catalogueId . ' AND status = 1 LIMIT 1');
    } elseif ($designNo !== '') {
        $esc = mysqli_real_escape_string($conn, $designNo);
        $row = getRecord("SELECT * FROM tbl_jewelry_catalogue WHERE status = 1 AND design_no = '" . $esc . "' ORDER BY id DESC LIMIT 1");
    }
    if (!$row) {
        return null;
    }

    $catalogue = auragold_jewelry_catalogue_normalize_db_row($row);
    $modalRows = auragold_jewelry_catalogue_bom_to_modal_rows($catalogue['bom'] ?? [], $catalogue);

    return [
        'catalogue' => $catalogue,
        'modal_rows' => $modalRows,
        'design_no' => (string) ($catalogue['design_no'] ?? ''),
        'title' => (string) ($catalogue['title'] ?? ''),
        'metal_id' => (int) ($catalogue['metal_id'] ?? 0),
        'weight' => (string) ($catalogue['weight'] ?? ''),
        'amount' => (string) ($catalogue['amount'] ?? ''),
    ];
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
 * True if another active catalogue row uses this design no.
 */
function auragold_jewelry_catalogue_design_no_taken(mysqli $conn, string $designNo, int $excludeId = 0): bool
{
    $designNo = trim($designNo);
    if ($designNo === '') {
        return false;
    }
    $esc = mysqli_real_escape_string($conn, $designNo);
    $sql = "SELECT id FROM tbl_jewelry_catalogue WHERE status = 1 AND design_no = '" . $esc . "'";
    if ($excludeId > 0) {
        $sql .= ' AND id <> ' . (int) $excludeId;
    }
    $sql .= ' LIMIT 1';
    $row = getRecord($sql);

    return is_array($row) && !empty($row['id']);
}

/**
 * @return array{exists:bool,message:string,design_no:string}
 */
function auragold_jewelry_catalogue_check_design_no(mysqli $conn, string $designNo, int $excludeId = 0): array
{
    auragold_ensure_jewelry_catalogue_table($conn);
    $designNo = trim($designNo);
    if ($designNo === '') {
        return ['exists' => false, 'message' => '', 'design_no' => ''];
    }
    $exists = auragold_jewelry_catalogue_design_no_taken($conn, $designNo, $excludeId);
    $msg = $exists
        ? ('Design No. "' . $designNo . '" already exists. Please enter a different number.')
        : '';

    return ['exists' => $exists, 'message' => $msg, 'design_no' => $designNo];
}

/**
 * Auto next number when blank; error if user-entered duplicate.
 *
 * @return array{design_no:string,error:string}
 */
function auragold_jewelry_catalogue_prepare_design_no(mysqli $conn, string $designNo, int $excludeId, bool $userProvided): array
{
    $designNo = trim($designNo);
    if ($designNo === '') {
        $designNo = auragold_next_jewelry_catalogue_design_no($conn, $excludeId);
        $cfg = auragold_jewelry_catalogue_bill_series_config($conn);
        $guard = 0;
        while ($guard < 5000 && auragold_jewelry_catalogue_design_no_taken($conn, $designNo, $excludeId)) {
            $designNo = auragold_bump_jewelry_catalogue_design_no($conn, $designNo, $cfg);
            $guard++;
        }

        return ['design_no' => $designNo, 'error' => ''];
    }
    if (auragold_jewelry_catalogue_design_no_taken($conn, $designNo, $excludeId)) {
        return [
            'design_no' => $designNo,
            'error' => 'Design No. "' . $designNo . '" already exists. Please enter a different number.',
        ];
    }

    return ['design_no' => $designNo, 'error' => ''];
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
    $designNoInput = trim((string) ($payload['design_no'] ?? ''));
    $userProvidedDesign = ($designNoInput !== '');
    $designNo = $designNoInput;
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
            $userProvidedDesign = false;
        }
        $prepared = auragold_jewelry_catalogue_prepare_design_no($conn, $designNo, $id, $userProvidedDesign);
        if ($prepared['error'] !== '') {
            return ['success' => false, 'message' => $prepared['error']];
        }
        $designNo = $prepared['design_no'];
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

    $prepared = auragold_jewelry_catalogue_prepare_design_no($conn, $designNo, 0, $userProvidedDesign);
    if ($prepared['error'] !== '') {
        return ['success' => false, 'message' => $prepared['error']];
    }
    $designNo = $prepared['design_no'];
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
