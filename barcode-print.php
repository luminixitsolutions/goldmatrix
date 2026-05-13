<?php
session_start();
require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

/** CSS px → mm at 96dpi (1in = 25.4mm, 1in = 96px) — single source for bar/QR size from px. */
$PX_TO_MM = 0.264583;

$settings = getBarcodeSettings();
$print_code_kind = isset($_GET['code']) ? strtolower(trim((string)$_GET['code'])) : '';
if ($print_code_kind !== 'qr' && $print_code_kind !== 'barcode') {
    $print_code_kind = ($settings && isset($settings['default_print_code_type']) && $settings['default_print_code_type'] === 'qr') ? 'qr' : 'barcode';
}
/* Default stock: 100 mm × 50 mm (4 in × 2 in); match Windows printer custom paper or use 50×100 mm if rotated. */
$label_width_mm  = $settings ? (float)($settings['label_width_mm'] ?? 100) : 100;
$label_height_mm = $settings ? (float)($settings['label_height_mm'] ?? 50) : 50;
$font_size       = $settings ? (int)($settings['font_size'] ?? 12) : 12;
$legacy_pn = $settings ? (int)($settings['show_product_name'] ?? 1) : 1;
$legacy_pr = $settings ? (int)($settings['show_price'] ?? 1) : 1;
$legacy_bn = $settings ? (int)($settings['show_barcode_number'] ?? 1) : 1;
if ($print_code_kind === 'qr') {
    $show_product_name   = $settings ? (int)($settings['show_product_name_qr'] ?? $legacy_pn) : 1;
    $show_price          = $settings ? (int)($settings['show_price_qr'] ?? $legacy_pr) : 1;
    $show_barcode_number = $settings ? (int)($settings['show_barcode_number_qr'] ?? $legacy_bn) : 1;
} else {
    $show_product_name   = $settings ? (int)($settings['show_product_name_barcode'] ?? $legacy_pn) : 1;
    $show_price          = $settings ? (int)($settings['show_price_barcode'] ?? $legacy_pr) : 1;
    $show_barcode_number = $settings ? (int)($settings['show_barcode_number_barcode'] ?? $legacy_bn) : 1;
}
$print_copies    = $settings ? max(1, min(100, (int)($settings['print_copies'] ?? 1))) : 1;
$design_layout   = [];
$barcode_bar_height_px = 28;
$barcode_bar_width_px  = 2;
$label_pad_top    = 0;
$label_pad_right  = 0;
$label_pad_bottom = 0;
$label_pad_left   = 0;
$barcode1_left_mm = null;
$barcode1_top_mm  = null;
$decoded_snapshot = null;
$raw_layout = '';
if ($settings) {
    if ($print_code_kind === 'qr') {
        $raw_layout = trim((string)($settings['design_layout_qr'] ?? ''));
    } else {
        $raw_layout = trim((string)($settings['design_layout'] ?? ''));
    }
}
if ($print_code_kind === 'qr' && $raw_layout === '') {
    die('QR layout is empty - not loaded from DB');
}
if ($print_code_kind === 'barcode' && $raw_layout === '') {
    die('Barcode layout is empty - not loaded from DB');
}
if ($settings && $raw_layout !== '') {
    $decoded = json_decode($raw_layout, true);
    if (!is_array($decoded)) {
        $decoded = json_decode(stripslashes($raw_layout), true);
    }
    if (!is_array($decoded)) {
        $snippet = htmlspecialchars(substr($raw_layout, 0, 500), ENT_QUOTES, 'UTF-8');
        $kind = ($print_code_kind === 'qr') ? 'QR' : 'Barcode';
        die('Invalid ' . $kind . ' layout JSON: ' . $snippet);
    }
    if (isset($decoded['label_pad_top'])) {
        $label_pad_top = max(0, min(200, (int)$decoded['label_pad_top']));
    }
    if (isset($decoded['label_pad_right'])) {
        $label_pad_right = max(0, min(200, (int)$decoded['label_pad_right']));
    }
    if (isset($decoded['label_pad_bottom'])) {
        $label_pad_bottom = max(0, min(200, (int)$decoded['label_pad_bottom']));
    }
    if (isset($decoded['label_pad_left'])) {
        $label_pad_left = max(0, min(200, (int)$decoded['label_pad_left']));
    }
    if (isset($decoded['barcode1_left'])) {
        $barcode1_left_mm = max(0.0, (float)$decoded['barcode1_left']);
    }
    if (isset($decoded['barcode1_top'])) {
        $barcode1_top_mm = max(0.0, (float)$decoded['barcode1_top']);
    }
    if (isset($decoded['barcode_bar_height'])) {
        $barcode_bar_height_px = max(10, min(200, (int)$decoded['barcode_bar_height']));
    }
    if (isset($decoded['barcode_bar_width'])) {
        $barcode_bar_width_px = max(1, min(10, (int)$decoded['barcode_bar_width']));
    }
    if (isset($decoded['items']) && is_array($decoded['items'])) {
        $design_layout = $decoded['items'];
    } elseif (isset($decoded['fields']) && is_array($decoded['fields'])) {
        $design_layout = $decoded['fields'];
    } else {
        $design_layout = $decoded;
    }
    $decoded_snapshot = $decoded;
}

$barcodes_param = isset($_GET['barcodes']) ? $_GET['barcodes'] : '';
$barcodes = !empty($barcodes_param) ? explode(',', $barcodes_param) : [];
if (empty($barcodes) && isset($_GET['barcode'])) {
    $barcodes = [$_GET['barcode']];
}
$barcodes = array_filter(array_map('trim', $barcodes));

$product_names = [];
$prices = [];
if (!empty($_GET['product_names'])) $product_names = array_map('trim', explode(',', $_GET['product_names']));
if (!empty($_GET['prices'])) $prices = array_map('trim', explode(',', $_GET['prices']));

// Optional row data from Product List (before Save) - overrides DB for this barcode
$overrides = [];
$override_keys = ['gross_wt', 'less_wt', 'purity', 'final_wt', 'net_wt', 'pure_wt', 'product_name', 'design_no', 'amount', 'net_amt', 'rate', 'making_amount'];
foreach ($override_keys as $k) {
    if (isset($_GET[$k]) && (string)$_GET[$k] !== '') $overrides[$k] = $_GET[$k];
}

$items = [];
foreach ($barcodes as $i => $barcode) {
    $barcode = trim((string) $barcode);
    $row_data = getBarcodePrintData($barcode);
    $row_data['product_name'] = $row_data['product_name'] ?? ($row_data['ProductName'] ?? '');
    if (isset($product_names[$i]) && $product_names[$i] !== '') $row_data['product_name'] = $product_names[$i];
    $row_data['price'] = $row_data['NetAmount'] ?? $row_data['Amount'] ?? '';
    if (isset($prices[$i]) && $prices[$i] !== '') $row_data['price'] = $prices[$i];
    // Encode and human-readable line must use the same string as the print URL (scan / chosen row), not DB-only formatting.
    $row_data['BarcodeNo'] = $barcode;
    $row_data['Barcode']   = $barcode;
    // Apply overrides from Product List (unsaved row data) so label shows current values
    foreach ($overrides as $k => $v) {
        if ($k === 'product_name') { $row_data['product_name'] = $v; $row_data['ProductName'] = $v; continue; }
        if ($k === 'net_amt') { $row_data['NetAmount'] = $v; $row_data['Amount'] = $v; continue; }
        if ($k === 'amount') { $row_data['Amount'] = $v; $row_data['NetAmount'] = $v; continue; }
        if ($k === 'design_no') { $row_data['DesignNo'] = $v; continue; }
        if ($k === 'rate') { $row_data['Rate'] = $v; continue; }
        if ($k === 'making_amount') { $row_data['MakingAmount'] = $v; continue; }
        if (in_array($k, ['gross_wt', 'less_wt', 'purity', 'final_wt', 'net_wt', 'pure_wt'], true)) {
            $num = is_numeric($v) ? (float)$v : $v;
            if ($k === 'gross_wt') $row_data['GrossWt'] = $num;
            elseif ($k === 'less_wt') $row_data['LessWt'] = $num;
            elseif ($k === 'purity') { $row_data['Purity'] = $num; $row_data['ActualPurity'] = $num; }
            elseif ($k === 'final_wt') $row_data['FinalWt'] = $num;
            elseif ($k === 'net_wt') $row_data['NetWt'] = $num;
            elseif ($k === 'pure_wt') { $row_data['PureWt'] = $num; $row_data['PurityWt'] = $num; }
            continue;
        }
        $row_data[$k] = $v;
    }
    for ($c = 0; $c < $print_copies; $c++) {
        $items[] = ['barcode' => $barcode, 'row' => $row_data];
    }
}

if (empty($items)) {
    die('No barcodes provided');
}

$item_count = count($items);
$print_total_height_mm = $item_count * $label_height_mm;

$use_design = true;
// Zero inner padding so screen/print match and mm layout is not shifted (label fields use absolute mm).
$label_inner_pad_style = 'padding:0;';
$label_inner_pad_style_simple = 'padding:0;';
// Bar height from settings (JsBarcode height is in px) → mm for simple template / design barcode block
$barcode_height_mm = round($barcode_bar_height_px * $PX_TO_MM, 2);
$barcode_height_mm = max(5, min(50, $barcode_height_mm));
foreach ($design_layout as $el) {
    if (isset($el['type']) && $el['type'] === 'barcode_image' && isset($el['height'])) {
        $barcode_height_mm = (float)$el['height'];
        break;
    }
}
$simple_barcode_w_mm = min(35, $label_width_mm * 0.35);
$simple_qr_w_px = 60;
$simple_qr_h_px = 60;
if (is_array($decoded_snapshot)) {
    if (isset($decoded_snapshot['qr_width'])) {
        $simple_qr_w_px = max(30, min(200, (int)$decoded_snapshot['qr_width']));
    }
    if (isset($decoded_snapshot['qr_height'])) {
        $simple_qr_h_px = max(30, min(200, (int)$decoded_snapshot['qr_height']));
    }
}
$simple_print_qr = ($print_code_kind === 'qr' && !$use_design);
if ($simple_print_qr) {
    $qw_mm = max(8, min((float)$label_width_mm * 0.95, round($simple_qr_w_px * $PX_TO_MM, 2)));
    $qh_mm = max(5, min(50, round($simple_qr_h_px * $PX_TO_MM, 2)));
    $side_mm = min($qw_mm, $qh_mm);
    $simple_barcode_w_mm = $side_mm;
    $barcode_height_mm    = $side_mm;
}
if ($print_code_kind !== 'qr') {
    $barcode_height_mm = max(5, min($barcode_height_mm, 30));
}
$render_settings = [
    'label_width_mm'     => $label_width_mm,
    'label_height_mm'    => $label_height_mm,
    'font_size'          => $font_size,
    'design_layout'      => $design_layout,
    'render_code_as'     => ($print_code_kind === 'qr') ? 'qr' : 'barcode',
    'px_to_mm'           => $PX_TO_MM,
    'barcode_height_mm'  => $barcode_height_mm,
    'design_left_inset_mm' => 0.0,
    'show_barcode_number' => $show_barcode_number,
];
if ($barcode1_left_mm !== null) {
    $render_settings['barcode1_left_mm'] = $barcode1_left_mm;
}
if ($barcode1_top_mm !== null) {
    $render_settings['barcode1_top_mm'] = $barcode1_top_mm;
}

if (!empty($_GET['debug_qr_layout']) || !empty($_GET['debug_barcode_layout'])) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<pre>';
    echo 'print_code_kind: ' . htmlspecialchars($print_code_kind, ENT_QUOTES, 'UTF-8') . "\n";
    echo 'raw_layout length: ' . (int) strlen($raw_layout) . "\n\n";
    print_r($design_layout);
    echo '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcodes</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <?php if ($print_code_kind === 'qr'): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <?php endif; ?>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff;
            height: auto !important;
            display: block;
        }
        .print-controls {
            background: #fff; padding: 15px; margin-bottom: 12px; border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex; gap: 10px; align-items: center;
        }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; }
        .btn-primary { background: #11294b; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .barcode-container {
            display: block !important;
            margin: 0;
            padding: 0;
        }
        .barcode-label,
        .barcode-item {
            width: <?php echo $label_width_mm; ?>mm;
            height: <?php echo $label_height_mm; ?>mm !important;
            min-width: <?php echo $label_width_mm; ?>mm;
            min-height: <?php echo $label_height_mm; ?>mm;
            max-width: <?php echo $label_width_mm; ?>mm;
            max-height: <?php echo $label_height_mm; ?>mm !important;
            position: relative;
            box-sizing: border-box;
            overflow: hidden !important;
            background: #fff;
            margin: 0;
            border: 1px solid #1e293b;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .barcode-label-inner {
            position: relative;
            display: block;
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            margin: 0;
            overflow: hidden;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .barcode-print--simple .barcode-label-inner {
            padding: 0 !important;
        }
        .barcode-svg-wrap {
            position: absolute;
            left: 0;
            margin: 0;
            padding: 0;
            z-index: 2;
            overflow: hidden;
            display: block;
            box-sizing: border-box;
            image-rendering: crisp-edges;
            image-rendering: pixelated;
        }
        /* QR: center without flex (flex breaks absolute mm alignment in some print engines) */
        .barcode-svg-wrap.barcode-svg-wrap--qr {
            display: block;
        }
        .barcode-svg-wrap.barcode-svg-wrap--qr .qr-print-host {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            display: block;
            max-width: 100%;
            max-height: 100%;
        }
        .barcode-svg-wrap .qr-print-host img,
        .barcode-svg-wrap .qr-print-host canvas {
            width: auto !important;
            height: auto !important;
            max-width: 100% !important;
            max-height: 100% !important;
        }
        /* Fill mm box; JsBarcode intrinsic aspect scales uniformly inside container */
        .barcode-svg-wrap:not(.barcode-svg-wrap--qr) svg,
        .barcode-print-wrap svg {
            width: 100% !important;
            height: 100% !important;
            margin: 0;
            display: block;
            shape-rendering: crispEdges;
            image-rendering: pixelated;
        }
        .barcode-print-preview-label {
            position: relative;
            box-sizing: border-box;
        }
        .barcode-print-wrap {
            position: absolute;
            overflow: hidden;
            box-sizing: border-box;
        }
        /* Simple template: row is left-aligned (flex:1 on the wrap was centering the SVG inside a wide box = fake left gap) */
        .barcode-print--simple .barcode-simple-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
        }
        .barcode-print--simple .barcode-simple-main {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
            gap: 2mm;
            width: 100%;
            box-sizing: border-box;
        }
        .barcode-print--simple .barcode-svg-wrap:not(.barcode-svg-wrap--qr) {
            position: relative !important;
            left: auto !important;
            top: auto !important;
            flex: 0 0 auto;
            max-width: 100%;
            min-width: 0;
        }
        .barcode-print--simple .barcode-svg-wrap.barcode-svg-wrap--qr {
            justify-content: flex-start !important;
        }
        .barcode-print--simple .barcode-price {
            position: relative !important;
            flex: 0 0 auto;
            text-align: left;
        }
        .barcode-print--simple .barcode-text {
            width: 100%;
            text-align: left;
        }
        .barcode-print--simple .barcode-product-name {
            width: 100%;
            text-align: left;
        }
        .design-field {
            position: absolute;
            color: #1e293b;
            white-space: nowrap;
            word-break: break-all;
            z-index: 0;
            font-size: <?php echo (int) $font_size; ?>px;
            box-sizing: border-box;
            background: #fff;
            padding: 0 !important;
            margin: 0 !important;
            line-height: 1 !important;
        }
        .barcode-product-name, .barcode-price, .barcode-text { margin: 0; padding: 0; }

        @media print {

            /* Must match printer custom paper size (e.g. 100×50 mm or 50×100 mm if rotated). */
            @page {
                margin: 0;
                size: <?php echo $label_width_mm; ?>mm <?php echo $label_height_mm; ?>mm;
            }

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                height: auto !important;
                overflow: hidden !important;
            }

            html {
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
            }

            body {
                width: 100% !important;
                max-width: none !important;
                min-height: 0 !important;
                font-family: Arial, sans-serif !important;
                display: block !important;
            }

            * {
                box-sizing: border-box !important;
            }

            .print-controls {
                display: none !important;
            }

            .design-field {
                padding: 0 !important;
                margin: 0 !important;
                line-height: 1 !important;
            }

            .barcode-label-inner {
                position: relative !important;
                display: block !important;
                width: 100% !important;
                height: 100% !important;
                margin: 0 !important;
                box-sizing: border-box !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .barcode-print--design .barcode-label-inner {
                padding: 0 !important;
            }
            .barcode-print--simple .barcode-label-inner {
                padding: 0 !important;
            }

            .barcode-svg-wrap:not(.barcode-svg-wrap--qr),
            .barcode-print--simple .barcode-svg-wrap:not(.barcode-svg-wrap--qr) {
                display: block !important;
                image-rendering: crisp-edges !important;
                image-rendering: pixelated !important;
            }

            .barcode-svg-wrap.barcode-svg-wrap--qr {
                display: block !important;
            }
            .barcode-svg-wrap.barcode-svg-wrap--qr .qr-print-host {
                position: absolute !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
                display: block !important;
                max-width: 100% !important;
                max-height: 100% !important;
            }

            .barcode-svg-wrap:not(.barcode-svg-wrap--qr) svg,
            .barcode-print-wrap svg {
                width: 100% !important;
                height: 100% !important;
                shape-rendering: crispEdges;
                image-rendering: pixelated;
            }

            .barcode-print-preview-label {
                position: relative !important;
                box-sizing: border-box !important;
            }

            .barcode-label,
            .barcode-item {
                width: <?php echo $label_width_mm; ?>mm !important;
                height: <?php echo $label_height_mm; ?>mm !important;
                max-height: <?php echo $label_height_mm; ?>mm !important;
                margin: 0;
                padding: 0;
                overflow: hidden;
                box-sizing: border-box;
                border: none !important;
                outline: none !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .barcode-print--design svg {
                transform: rotate(0deg) !important;
            }

            .barcode-print--simple .barcode-svg-wrap svg {
                transform: rotate(0deg) !important;
            }

            .barcode-container {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                overflow: hidden !important;
            }

            .barcode-label {
                display: block !important;
                transform: scale(1) !important;
                zoom: 1 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

        }
    </style>
</head>
<body class="barcode-print <?php echo $use_design ? 'barcode-print--design' : 'barcode-print--simple'; ?>">
    <div class="print-controls no-print">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
        <button class="btn btn-secondary" onclick="window.close()">Close</button>
        <span style="margin-left: auto; color: #64748b;"><?php echo count($items); ?> label(s) · Label size: <strong><?php echo $label_width_mm; ?>mm × <?php echo $label_height_mm; ?>mm</strong></span>
        <span class="print-tip" style="font-size: 12px; color: #64748b;">Chrome print: <strong>Margins → None</strong>, <strong>Scale 100%</strong>, turn off <strong>Fit to page</strong>. In Windows, create a <strong>custom paper</strong> matching this label: <strong><?php echo $label_width_mm; ?>×<?php echo $label_height_mm; ?> mm</strong> (e.g. 100×50 mm / 4×2 in, or 50×100 mm if rotated — must match <code>@page</code>). Grey side bands mean the driver paper size does not match — fix the custom size.</span>
    </div>
    <div class="barcode-container" id="barcodeContainer">
        <?php foreach ($items as $item):
            $productData = array_merge($item['row'], ['barcode' => $item['barcode']]);
            if ($use_design):
                $inner_html = renderBarcodeLayout($productData, $render_settings);
        ?>
            <div class="barcode-label barcode-item" style="width:<?php echo $label_width_mm; ?>mm;height:<?php echo $label_height_mm; ?>mm;">
                <div class="barcode-label-inner barcode-print-preview-label" style="position:relative;width:<?php echo $label_width_mm; ?>mm;height:<?php echo $label_height_mm; ?>mm;box-sizing:border-box;<?php echo htmlspecialchars($label_inner_pad_style, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo $inner_html; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="barcode-label barcode-item" style="width:<?php echo $label_width_mm; ?>mm;height:<?php echo $label_height_mm; ?>mm;">
                <div class="barcode-label-inner" style="<?php echo htmlspecialchars($label_inner_pad_style_simple, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="barcode-simple-block" style="<?php echo ($barcode1_top_mm !== null && $barcode1_top_mm > 0) ? 'margin-top:' . round($barcode1_top_mm, 2) . 'mm;' : ''; ?>">
                        <?php if ($show_product_name): ?>
                            <div class="barcode-product-name" style="font-size:<?php echo $font_size; ?>px;"><?php echo htmlspecialchars($item['row']['product_name'] !== '' ? $item['row']['product_name'] : ''); ?></div>
                        <?php endif; ?>
                        <div class="barcode-simple-main">
                            <div class="barcode-svg-wrap barcode-svg-wrap--qr" style="width:<?php echo round($simple_barcode_w_mm, 2); ?>mm;height:<?php echo round($barcode_height_mm, 2); ?>mm;">
                                <?php if ($simple_print_qr): ?>
                                <div class="qr-print-host" data-barcode="<?php echo htmlspecialchars($item['barcode']); ?>" style="width:100%;height:100%;"></div>
                                <?php else: ?>
                                <svg class="barcode-svg" data-barcode="<?php echo htmlspecialchars($item['barcode']); ?>"></svg>
                                <?php endif; ?>
                            </div>
                            <?php if ($show_price): ?>
                                <div class="barcode-price" style="font-size:<?php echo $font_size; ?>px;"><?php echo htmlspecialchars($item['row']['price'] !== '' ? $item['row']['price'] : ''); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if ($show_barcode_number): ?>
                            <div class="barcode-text" style="font-size:<?php echo $font_size; ?>px;"><?php echo htmlspecialchars($item['row']['BarcodeNo'] ?? $item['barcode']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <script>
        function auragoldFillQrPrintHosts() {
            if (typeof QRCode === 'undefined') return;
            document.querySelectorAll('.qr-print-host').forEach(function(host) {
                var barcodeValue = host.getAttribute('data-barcode');
                if (!barcodeValue) return;
                var text = String(barcodeValue).trim();
                if (!text) return;
                host.innerHTML = '';
                var par = host.parentElement;
                var w = Math.max(48, par ? par.offsetWidth : 120);
                var h = Math.max(48, par ? par.offsetHeight : w);
                var size = Math.round(Math.min(w, h));
                try {
                    new QRCode(host, { text: text, width: size, height: size });
                } catch (e) {
                    host.innerHTML = '<span style="font-size:10px;">' + String(text).replace(/</g, '&lt;') + '</span>';
                }
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            var printQr = <?php echo $print_code_kind === 'qr' ? 'true' : 'false'; ?>;
            if (printQr && typeof QRCode !== 'undefined') {
                auragoldFillQrPrintHosts();
            }
            /* Bar width/height from Barcode Setting (same as set-software canvas); matches design preview scaling. */
            var barW = <?php echo (int) max(1, min(10, $barcode_bar_width_px)); ?>;
            var barH = <?php echo (int) max(10, min(200, $barcode_bar_height_px)); ?>;
            document.querySelectorAll('.barcode-svg').forEach(function(el) {
                var barcodeValue = el.getAttribute('data-barcode');
                if (!barcodeValue) return;
                var code = String(barcodeValue).trim();
                if (!code) return;
                try {
                    JsBarcode(el, code, {
                        format: "CODE128",
                        renderer: "svg",
                        width: barW,
                        height: barH,
                        displayValue: false,
                        margin: 0,
                        background: "#fff",
                        lineColor: "#000"
                    });
                } catch (e) {
                    el.parentElement.innerHTML = '<span style="font-size:10px;">' + (String(barcodeValue).replace(/</g, '&lt;')) + '</span>';
                }
            });
        });
        /* Re-measure mm box right before print so QR pixel size matches printed layout (avoids screen vs paper mismatch). */
        window.addEventListener('beforeprint', function() {
            if (<?php echo $print_code_kind === 'qr' ? 'true' : 'false'; ?> && typeof QRCode !== 'undefined') {
                auragoldFillQrPrintHosts();
            }
        });
    </script>
</body>
</html>
