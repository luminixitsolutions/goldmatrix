<?php
/**
 * Stock journal Excel import helpers (product opening bulk upload).
 * Used by ajax/import-stock-journal-excel.php — not a standalone endpoint.
 */

// Aliases (requested API names)
if (!function_exists('validateBarcode')) {
    function validateBarcode(mysqli $conn, string $candidate, array &$used_accumulator): bool
    {
        return auragold_sj_excel_validate_barcode($conn, $candidate, $used_accumulator);
    }
}
if (!function_exists('generateUniqueBarcode')) {
    function generateUniqueBarcode(mysqli $conn, int $product_id, int $characteristic_id, array &$used_accumulator): string
    {
        return auragold_sj_excel_generate_unique_barcode($conn, [
            'product_id' => $product_id,
            'characteristic_id' => $characteristic_id,
            'barcode' => '',
        ], $used_accumulator);
    }
}
if (!function_exists('saveExcelImage')) {
    function saveExcelImage(string $binary, string $ext): ?string
    {
        return auragold_sj_excel_save_image_bytes($binary, $ext);
    }
}

if (!function_exists('auragold_sj_excel_validate_barcode')) {
    /**
     * True if barcode is non-empty, not in $used_accumulator, and not already in inventory tables.
     *
     * @param array<int,string> $used_accumulator
     */
    function auragold_sj_excel_validate_barcode(mysqli $conn, string $candidate, array &$used_accumulator): bool
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return false;
        }
        if (in_array($candidate, $used_accumulator, true)) {
            return false;
        }
        if (function_exists('auragold_barcode_exists_in_system') && auragold_barcode_exists_in_system($conn, $candidate)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('auragold_sj_excel_generate_unique_barcode')) {
    /**
     * Resolves a unique barcode using product characteristic prefix/digits (same rules as invoice lines).
     *
     * @param array<string,mixed> $item product_id, characteristic_id, barcode (optional)
     * @param array<int,string> $used_accumulator
     */
    function auragold_sj_excel_generate_unique_barcode(mysqli $conn, array $item, array &$used_accumulator): string
    {
        require_once __DIR__ . '/invoice_item_unique_barcode.php';

        return auragold_resolve_unique_invoice_item_barcode($conn, $item, $used_accumulator);
    }
}

if (!function_exists('auragold_sj_excel_save_image_bytes')) {
    /**
     * Excel import: writes image bytes to uploads/temp_excel/ only (not DB / not tbl_products).
     * Final persist happens when Save Stock Journal runs (stock_journal + tbl_stock_journal_images).
     */
    function auragold_sj_excel_save_image_bytes(string $binary, string $ext): ?string
    {
        $ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $base = dirname(__DIR__) . '/uploads/temp_excel';
        if (!is_dir($base)) {
            @mkdir(dirname(__DIR__) . '/uploads', 0755, true);
            @mkdir($base, 0775, true);
        }
        $name = 'temp_' . time() . '_' . random_int(1000, 9999) . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $full = $base . '/' . $name;
        if (@file_put_contents($full, $binary) === false) {
            return null;
        }
        return 'uploads/temp_excel/' . $name;
    }
}

if (!function_exists('auragold_sj_excel_copy_abs_to_temp_excel')) {
    /**
     * Copy an arbitrary readable image file (e.g. sheet drawing in sys temp) into uploads/temp_excel/.
     *
     * @return string|null relative path uploads/temp_excel/…
     */
    function auragold_sj_excel_copy_abs_to_temp_excel(string $absPath): ?string
    {
        if ($absPath === '' || !is_file($absPath) || !is_readable($absPath)) {
            return null;
        }
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION) ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $base = dirname(__DIR__) . '/uploads/temp_excel';
        if (!is_dir($base)) {
            @mkdir(dirname(__DIR__) . '/uploads', 0755, true);
            @mkdir($base, 0775, true);
        }
        $name = 'temp_dwg_' . time() . '_' . random_int(1000, 9999) . '_' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $ext;
        $dest = $base . '/' . $name;
        if (!@copy($absPath, $dest) && !@file_put_contents($dest, (string) @file_get_contents($absPath))) {
            return null;
        }
        return 'uploads/temp_excel/' . $name;
    }
}

if (!function_exists('auragold_sj_excel_ws_cell')) {
    /**
     * PhpSpreadsheet 5+ removed Worksheet::getCellByColumnAndRow. Column index is 1-based (A=1).
     */
    function auragold_sj_excel_ws_cell(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $columnIndex1Based, int $row): \PhpOffice\PhpSpreadsheet\Cell\Cell
    {
        $addr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex1Based) . $row;

        return $sheet->getCell($addr);
    }
}

if (!function_exists('auragold_sj_excel_normalize_header')) {
    function auragold_sj_excel_normalize_header(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/\s+/', '_', $h);
        $h = preg_replace('/[^a-z0-9_]/', '', $h);

        return $h;
    }
}

if (!function_exists('auragold_sj_excel_map_headers')) {
    /**
     * @param array<int,string> $headers row 1 values by column index 1..N
     * @return array<string,int|array<int,int>> canonical field => column index, plus image_columns => list of image-related columns (sorted: Photo/URL columns before generic "Images")
     */
    function auragold_sj_excel_map_headers(array $headers): array
    {
        $map = [];
        $aliases = [
            'quantity' => ['metal_qty', 'metalqty', 'pcs', 'qty', 'quantity', 'metal_qty'],
            'gross_weight' => ['gross_weight', 'gross_wt', 'grosswt', 'gross'],
            'purity' => ['purity', 'purity_pct', 'puritypercent'],
            'less_weight' => ['less_weight', 'dweight', 'd_weight', 'less', 'dwt', 'dweight2'],
            'design_no' => ['design_no', 'designno', 'design', 'des_no'],
            'huid_no' => ['huid_no', 'huid', 'huidno'],
            'rfid_code' => ['rfid', 'rfid_code', 'rfidcode', 'code'],
            'barcode' => ['barcode', 'barcode_no', 'tag'],
        ];
        // Lower number = read this column first for URL/base64/path (template "Images" is often instructions only).
        $image_header_priority = [
            'photo' => 10,
            'picture' => 20,
            'image' => 30,
            'img' => 35,
            'images' => 90,
        ];
        $image_candidates = [];
        foreach ($headers as $colIdx => $raw) {
            $n = auragold_sj_excel_normalize_header((string) $raw);
            if ($n === '') {
                continue;
            }
            if (isset($image_header_priority[$n])) {
                $image_candidates[] = [
                    'col' => (int) $colIdx,
                    'pri' => $image_header_priority[$n],
                ];
            }
            foreach ($aliases as $field => $list) {
                if (in_array($n, $list, true)) {
                    if (!isset($map[$field])) {
                        $map[$field] = (int) $colIdx;
                    }
                    break;
                }
            }
        }
        usort($image_candidates, static function (array $a, array $b): int {
            if ($a['pri'] !== $b['pri']) {
                return $a['pri'] <=> $b['pri'];
            }

            return $a['col'] <=> $b['col'];
        });
        $cols = [];
        foreach ($image_candidates as $c) {
            $cols[] = (int) $c['col'];
        }
        if ($cols !== []) {
            $map['image_columns'] = $cols;
            $map['image'] = $cols[0];
        }

        return $map;
    }
}

if (!function_exists('auragold_sj_excel_sj_cell_f')) {
    function auragold_sj_excel_sj_cell_f(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $col, int $row): float
    {
        if ($col < 1) {
            return 0.0;
        }
        $v = auragold_sj_excel_ws_cell($sheet, $col, $row)->getCalculatedValue();
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_string($v)) {
            $v = str_replace([',', ' '], ['', ''], $v);
        }
        if (!is_numeric($v)) {
            return 0.0;
        }
        $f = (float) $v;
        if (is_nan($f) || is_infinite($f)) {
            return 0.0;
        }

        return $f;
    }
}

if (!function_exists('auragold_sj_excel_sj_cell_s')) {
    function auragold_sj_excel_sj_cell_s(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $col, int $row): string
    {
        if ($col < 1) {
            return '';
        }
        $v = auragold_sj_excel_ws_cell($sheet, $col, $row)->getValue();

        return trim(preg_replace('/\s+/', ' ', (string) $v));
    }
}

if (!function_exists('auragold_sj_excel_map_stock_journals_extended')) {
    /**
     * @param array<int,string> $headers 1..N
     * @return array<string,int> logical field => column index
     */
    function auragold_sj_excel_map_stock_journals_extended(array $headers): array
    {
        $out = [];
        $caratCols = [];
        $put = static function (string $k, int $c) use (&$out) {
            if (!isset($out[$k])) {
                $out[$k] = $c;
            }
        };
        $normMap = [
            'id' => 'excel_id', 'vouchertype' => 'voucher_type', 'vouchertypeid' => 'voucher_type', 'voucher' => 'voucher_type',
            'rfidcode' => 'rfid_code', 'category' => 'category', 'calculation' => 'calculation', 'product' => 'product_name_in',
            'location' => 'location', 'pkt_wt' => 'pkt_wt', 'pkt_less_wt' => 'pkt_less_wt', 'pktlesswt' => 'pkt_less_wt',
            'dweight' => 'less_diamond', 'd_weight' => 'less_diamond', 'net_wt' => 'net_wt', 'netwt' => 'net_wt', 'net' => 'net_wt',
            'quantity' => 'qty_line', 'rate' => 'rate', 'amount' => 'amount', 'metal_qty' => 'qty_metal', 'metalqty' => 'qty_metal',
            'purity' => 'purity', 'purity_wt' => 'pure_wt_in', 'puritywt' => 'pure_wt_in',
            'wastage_per' => 'wastage_per', 'wastageper' => 'wastage_per', 'wastage_wt' => 'wastage_wt', 'wastagewt' => 'wastage_wt',
            'metal_rate' => 'metal_rate', 'metalrate' => 'metal_rate', 'metal_value' => 'metal_value', 'metalvalue' => 'metal_value',
            'metal_cost' => 'metal_cost', 'metalcost' => 'metal_cost',
            'requested_purity' => 'requested_purity', 'requested' => 'requested', 'setting_charge' => 'setting_charge',
            'final_wt' => 'final_wt', 'alloy_wt' => 'alloy_wt', 'alloywt' => 'alloy_wt',
            'discount_type' => 'discount_type', 'discount_per' => 'discount_per', 'discountper' => 'discount_per',
            'discount_amount' => 'discount_amount', 'discount' => 'discount', 'making_type' => 'making_type',
            'making_rate' => 'making_rate', 'making_discount_amt' => 'making_discount_amt', 'making_amount' => 'making_amount',
            'making_actual_value' => 'making_actual_value', 'making_cost' => 'making_cost', 'makingcost' => 'making_cost',
            'minimum' => 'minimum', 'minimum_price' => 'minimum_price', 'minimum_code' => 'minimum_code',
            'stone_charge_type' => 'stone_charge_type', 'stone_rate' => 'stone_rate', 'stone_amount' => 'stone_amount', 'stone_cost' => 'stone_cost',
            'diamond_amount' => 'diamond_amount', 'purchase_amount' => 'purchase_amount', 'sale_amount' => 'sale_amount',
            'sale_amount_with_tax' => 'sale_amount_with', 'net_amt' => 'net_amount', 'tax_type' => 'tax_type',
            'other_charge_type' => 'other_charge_type', 'other_weight' => 'other_weight', 'other_rate' => 'other_rate', 'other_info' => 'other_info',
            'other_amount' => 'other_amount', 'hallmark' => 'hallmark', 'hallmark_amount' => 'hallmark_amount', 'hallmark_rate' => 'hallmark_rate',
            'net_amt_tax' => 'net_amt_tax', 'reverse' => 'reverse', 'loss_wt' => 'gold_loss_1', 'loss_wt_per' => 'gold_loss_2', 'losswt' => 'gold_loss_1', 'losswtper' => 'gold_loss_2',
            'lossvalue' => 'loss_value', 'gross_wt' => 'gross_diamond', 'grosswt' => 'gross_diamond', 'gross' => 'gross_diamond',
        ];
        foreach ($headers as $c => $raw) {
            $c = (int) $c;
            $r = trim((string) $raw);
            $n = auragold_sj_excel_normalize_header($r);
            if ($n === '') {
                continue;
            }
            if ($n === 'carat') {
                $caratCols[] = $c;
                continue;
            }
            if (preg_match('/product\\s*name/i', $r) !== 0) {
                $put('product_name_in', $c);
                continue;
            }
            $rU = strtoupper($r);
            if (strpos($rU, 'TAX') !== false) {
                if (strpos($rU, 'TYPE') !== false && stripos($r, 'Type') !== false) {
                    $put('tax_type', $c);
                    continue;
                }
                if (strpos($r, '%') !== false) {
                    $put('tax_percent', $c);
                    continue;
                }
                if (preg_match('/^\\s*Tax\\s*$/i', $r) !== 0) {
                    $put('tax_amount', $c);
                    continue;
                }
            }
            if (isset($normMap[$n])) {
                $put($normMap[$n], $c);
                continue;
            }
            if (in_array($n, ['gross_wt', 'grosswt', 'gross'], true) || ($n !== 'gross' && strncmp($n, 'gross_', 6) === 0)) {
                $put('gross_diamond', $c);
            } elseif ($n === 'weight' || $n === 'metal_weight') {
                $put('weight_metal', $c);
            }
        }
        if (count($caratCols) > 0) {
            $out['stone_weight_carat'] = (int) $caratCols[0];
        }
        if (count($caratCols) > 1) {
            $out['karat_carat'] = (int) $caratCols[1];
        }

        return $out;
    }
}

if (!function_exists('auragold_sj_excel_stock_journal_making_amount')) {
    /**
     * Mirrors stock-journal modal: if Making Amount column is blank but Making Rate + type exist,
     * derive amount (e.g. Fix + 1000 → 1000). Non-zero explicit Making Amount wins.
     */
    function auragold_sj_excel_stock_journal_making_amount(
        float $explicitMaking,
        string $makingType,
        float $makingRate,
        float $makingDiscountAmt,
        float $netWt,
        float $quantity,
        float $metalValue
    ): float {
        $explicitMaking = round($explicitMaking, 6);
        if ($explicitMaking !== 0.0 && abs($explicitMaking) > 0.0000001) {
            $out = $explicitMaking - $makingDiscountAmt;

            return max(0.0, $out);
        }
        if ($makingRate <= 0) {
            return 0.0;
        }
        $t = trim($makingType);
        if ($t === '') {
            $t = 'Fix';
        }
        $tu = strtolower($t);
        $amt = 0.0;
        if ($tu === 'fix' || $tu === 'mrp' || $tu === 'm.kt' || $tu === 'm_kt') {
            $amt = $makingRate;
        } elseif ($tu === 'per gram') {
            $amt = $netWt * $makingRate;
        } elseif ($tu === 'per piece') {
            $q = ($quantity > 0 ? $quantity : 1.0);
            $amt = $q * $makingRate;
        } elseif ($tu === 'per kilogram' || $tu === 'per kg') {
            $amt = ($netWt / 1000.0) * $makingRate;
        } elseif ($tu === 'per percent' || $tu === 'percentage') {
            $amt = $metalValue * ($makingRate / 100.0);
        } else {
            $amt = $makingRate;
        }
        $amt -= $makingDiscountAmt;

        return max(0.0, $amt);
    }
}

if (!function_exists('auragold_sj_excel_parse_image_cell')) {
    /**
     * Returns binary data + extension hint, or null.
     *
     * @return array{0:string,1:string}|null [binary, ext]
     */
    function auragold_sj_excel_parse_image_cell(string $cellText): ?array
    {
        $t = trim($cellText);
        if ($t === '') {
            return null;
        }
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/i', $t, $m)) {
            $bin = base64_decode($m[2], true);
            if ($bin !== false && $bin !== '') {
                $ext = strtolower($m[1]);
                if ($ext === 'jpeg') {
                    $ext = 'jpg';
                }

                return [$bin, $ext];
            }
        }
        if (preg_match('#^https?://#i', $t)) {
            $ctx = stream_context_create(['http' => ['timeout' => 15], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $data = @file_get_contents($t, false, $ctx);
            if ($data !== false && strlen($data) > 10) {
                $ext = 'jpg';
                if (preg_match('#\.(jpe?g|png|webp|gif)(\?|$)#i', $t, $mm)) {
                    $ext = strtolower($mm[1]);
                    if ($ext === 'jpeg') {
                        $ext = 'jpg';
                    }
                }

                return [$data, $ext];
            }
        }
        // Local filename relative to project root, uploads, or excel_images (Excel bundle folder)
        $candidates = [
            dirname(__DIR__) . '/' . ltrim($t, '/'),
            dirname(__DIR__) . '/uploads/' . basename($t),
            dirname(__DIR__) . '/excel_images/' . ltrim($t, '/'),
            dirname(__DIR__) . '/excel_images/' . basename($t),
        ];
        foreach ($candidates as $p) {
            if (is_file($p) && is_readable($p)) {
                $bin = @file_get_contents($p);
                if ($bin !== false && $bin !== '') {
                    $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION)) ?: 'jpg';

                    return [$bin, $ext];
                }
            }
        }

        return null;
    }
}

if (!function_exists('auragold_sj_excel_drawing_export_temp')) {
    /**
     * Copy drawing pixels to a readable temp file (PhpSpreadsheet often keeps xlsx images as zip://…# paths, not is_readable).
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing $drawing
     * @return string|null absolute path
     */
    function auragold_sj_excel_drawing_export_temp($drawing): ?string
    {
        if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
            $res = $drawing->getImageResource();
            if (!$res) {
                return null;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'sjxl_') . '.png';
            $rf = $drawing->getRenderingFunction();
            $ok = false;
            if ($rf === 'imagejpeg' && function_exists('imagejpeg')) {
                $tmp = preg_replace('/\.png$/', '.jpg', $tmp);
                $ok = @imagejpeg($res, $tmp, 90);
            } elseif ($rf === 'imagegif' && function_exists('imagegif')) {
                $tmp = preg_replace('/\.png$/', '.gif', $tmp);
                $ok = @imagegif($res, $tmp);
            } elseif (function_exists('imagepng')) {
                $ok = @imagepng($res, $tmp);
            }
            if ($ok && is_readable($tmp)) {
                return $tmp;
            }
            if (is_file($tmp)) {
                @unlink($tmp);
            }

            return null;
        }

        if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
            $path = $drawing->getPath();
            if ($path === '') {
                return null;
            }
            $ext = strtolower($drawing->getExtension() ?: 'png');
            $ext = preg_replace('/[^a-z0-9]/', '', $ext);
            if ($ext === '' || strlen($ext) > 5) {
                $ext = 'png';
            }

            $bin = null;
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/is', $path, $m)) {
                $bin = base64_decode($m[2], true);
                if ($bin !== false && $bin !== '') {
                    $e = strtolower((string) $m[1]);
                    if ($e === 'jpeg' || $e === 'jpg') {
                        $ext = 'jpg';
                    } elseif (in_array($e, ['png', 'gif', 'webp'], true)) {
                        $ext = $e;
                    }
                } else {
                    $bin = null;
                }
            } elseif (str_starts_with($path, 'zip://') || str_starts_with($path, 'file://')) {
                $bin = @file_get_contents($path);
            } elseif (method_exists($drawing, 'getIsURL') && $drawing->getIsURL() && filter_var($path, FILTER_VALIDATE_URL)) {
                $bin = @file_get_contents($path);
            } elseif (is_readable($path)) {
                $tmp = tempnam(sys_get_temp_dir(), 'sjxl_');
                if ($tmp && @copy($path, $tmp)) {
                    return $tmp;
                }

                return null;
            }

            if ($bin !== null && $bin !== '') {
                $tmp = tempnam(sys_get_temp_dir(), 'sjxl_') . '.' . $ext;
                if (@file_put_contents($tmp, $bin) !== false && is_readable($tmp)) {
                    return $tmp;
                }
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }

        return null;
    }
}

if (!function_exists('auragold_sj_excel_collect_drawings_by_row')) {
    /**
     * Map sheet row number (1-based) => list of temp file paths (JPEG/PNG) for save handler.
     *
     * @return array<int, array<int,string>>
     */
    function auragold_sj_excel_collect_drawings_by_row(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): array
    {
        $byRow = [];
        $collections = [
            $sheet->getDrawingCollection(),
            $sheet->getInCellDrawingCollection(),
        ];
        foreach ($collections as $coll) {
            foreach ($coll as $drawing) {
                $coord = method_exists($drawing, 'getCoordinates') ? (string) $drawing->getCoordinates() : '';
                $coord = str_replace('$', '', $coord);
                if ($coord === '' && method_exists($drawing, 'getCoordinates2')) {
                    $coord = str_replace('$', '', (string) $drawing->getCoordinates2());
                }
                if ($coord === '' || !preg_match('/^([A-Z]+)(\d+)$/i', $coord, $m)) {
                    continue;
                }
                $row = (int) $m[2];
                $tmp = auragold_sj_excel_drawing_export_temp($drawing);
                if ($tmp && is_readable($tmp)) {
                    if (!isset($byRow[$row])) {
                        $byRow[$row] = [];
                    }
                    $byRow[$row][] = $tmp;
                }
            }
        }

        return $byRow;
    }
}
