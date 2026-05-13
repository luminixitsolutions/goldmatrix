<?php 
session_start();
require_once 'config.php';
require_once __DIR__ . '/includes/auragold_branch_data_scope.php';

/** Metals from master (branch-scoped when logged into a branch), for barcode Metal Type dropdown */
$barcode_metals = getList(
    "SELECT id, display_name, system_name FROM tbl_metal WHERE status = 1 "
    . auragold_master_list_sql_suffix($conn, 'tbl_metal')
    . " ORDER BY id ASC"
);
if (!is_array($barcode_metals)) {
    $barcode_metals = [];
}

auragold_ensure_branch_id_on_settings_tables($conn);
$settings_branch_id = auragold_settings_branch_id();

$barcode_settings = getBarcodeSettings();
$bs = $barcode_settings ?: [
    'label_size_preset' => '100x18',
    'label_width_mm' => 100,
    'label_height_mm' => 18,
    'font_size' => 12,
    'show_product_name' => 1,
    'show_price' => 1,
    'show_barcode_number' => 1,
    'show_product_name_barcode' => 1,
    'show_product_name_qr' => 1,
    'show_price_barcode' => 1,
    'show_price_qr' => 1,
    'show_barcode_number_barcode' => 1,
    'show_barcode_number_qr' => 1,
    'print_copies' => 1,
    'metal_type' => ''
];
$bs_metal = isset($bs['metal_type']) ? trim((string)$bs['metal_type']) : '';
$bs_design_layout = isset($bs['design_layout']) && $bs['design_layout'] !== '' && $bs['design_layout'] !== null ? $bs['design_layout'] : '';
$bs_design_layout_decoded = $bs_design_layout ? @json_decode($bs_design_layout, true) : [];
$bs_design_layout_json_error = json_last_error();
if (!is_array($bs_design_layout_decoded)) {
    $bs_design_layout_decoded = [];
}
// Retry decode when first parse failed (e.g. escaped JSON in DB)
if ($bs_design_layout !== '' && $bs_design_layout !== null && $bs_design_layout_json_error !== JSON_ERROR_NONE) {
    $try2 = @json_decode(stripslashes($bs_design_layout), true);
    if (is_array($try2)) {
        $bs_design_layout_decoded = $try2;
    }
}
$bs_design_layout_qr = isset($bs['design_layout_qr']) && is_string($bs['design_layout_qr']) ? $bs['design_layout_qr'] : '';
$bs_default_print_code = (isset($bs['default_print_code_type']) && $bs['default_print_code_type'] === 'qr') ? 'qr' : 'barcode';
$legacy_show_pn = (int)($bs['show_product_name'] ?? 1);
$legacy_show_pr = (int)($bs['show_price'] ?? 1);
$legacy_show_bn = (int)($bs['show_barcode_number'] ?? 1);
$show_product_name_barcode = isset($bs['show_product_name_barcode']) ? (int)$bs['show_product_name_barcode'] : $legacy_show_pn;
$show_product_name_qr      = isset($bs['show_product_name_qr']) ? (int)$bs['show_product_name_qr'] : $legacy_show_pn;
$show_price_barcode        = isset($bs['show_price_barcode']) ? (int)$bs['show_price_barcode'] : $legacy_show_pr;
$show_price_qr             = isset($bs['show_price_qr']) ? (int)$bs['show_price_qr'] : $legacy_show_pr;
$show_barcode_number_barcode = isset($bs['show_barcode_number_barcode']) ? (int)$bs['show_barcode_number_barcode'] : $legacy_show_bn;
$show_barcode_number_qr      = isset($bs['show_barcode_number_qr']) ? (int)$bs['show_barcode_number_qr'] : $legacy_show_bn;
if ($bs_default_print_code === 'qr') {
    $show_product_name   = $show_product_name_qr;
    $show_price          = $show_price_qr;
    $show_barcode_number = $show_barcode_number_qr;
} else {
    $show_product_name   = $show_product_name_barcode;
    $show_price          = $show_price_barcode;
    $show_barcode_number = $show_barcode_number_barcode;
}
$bs_design_layout_qr_decoded = $bs_design_layout_qr ? @json_decode($bs_design_layout_qr, true) : [];
if (!is_array($bs_design_layout_qr_decoded)) {
    $bs_design_layout_qr_decoded = [];
}
if ($bs_design_layout_qr !== '' && $bs_design_layout_qr !== null && empty($bs_design_layout_qr_decoded)) {
    $tryQr = @json_decode(stripslashes($bs_design_layout_qr), true);
    if (is_array($tryQr)) {
        $bs_design_layout_qr_decoded = $tryQr;
    }
}
$bs['barcode_bar_width'] = isset($bs_design_layout_decoded['barcode_bar_width'])
    ? (int)$bs_design_layout_decoded['barcode_bar_width']
    : ($bs['barcode_bar_width'] ?? 2);
$bs['barcode_bar_height'] = isset($bs_design_layout_decoded['barcode_bar_height'])
    ? (int)$bs_design_layout_decoded['barcode_bar_height']
    : ($bs['barcode_bar_height'] ?? 28);
/* QR size: read from design_layout_qr first so saving barcode layout does not overwrite QR width/height in the form */
$bs['qr_width'] = isset($bs_design_layout_qr_decoded['qr_width'])
    ? (int)$bs_design_layout_qr_decoded['qr_width']
    : (isset($bs_design_layout_decoded['qr_width']) ? (int)$bs_design_layout_decoded['qr_width'] : 60);
$bs['qr_height'] = isset($bs_design_layout_qr_decoded['qr_height'])
    ? (int)$bs_design_layout_qr_decoded['qr_height']
    : (isset($bs_design_layout_decoded['qr_height']) ? (int)$bs_design_layout_decoded['qr_height'] : 60);
$pad_src = ($bs_default_print_code === 'qr' && !empty($bs_design_layout_qr_decoded))
    ? $bs_design_layout_qr_decoded
    : $bs_design_layout_decoded;
$bs['label_pad_top']    = isset($pad_src['label_pad_top']) ? max(0, min(200, (int)$pad_src['label_pad_top'])) : 0;
$bs['label_pad_right']  = isset($pad_src['label_pad_right']) ? max(0, min(200, (int)$pad_src['label_pad_right'])) : 0;
$bs['label_pad_bottom'] = isset($pad_src['label_pad_bottom']) ? max(0, min(200, (int)$pad_src['label_pad_bottom'])) : 0;
$bs['label_pad_left']   = isset($pad_src['label_pad_left']) ? max(0, min(200, (int)$pad_src['label_pad_left'])) : 0;
$render_settings_preview = [
    'label_width_mm'  => (float)($bs['label_width_mm'] ?? 100),
    'label_height_mm' => (float)($bs['label_height_mm'] ?? 18),
    'font_size'       => (int)($bs['font_size'] ?? 12),
    'design_layout'   => $bs_design_layout_decoded,
];
$sample_data_preview = [
    'barcode' => '00002',
    'BarcodeNo' => '00002',
    'ActualPurity' => '99.99%',
    'product_name' => 'Sample Product',
    'price' => '1,234.00',
];
/** Same keys as toolbox data-field — used so canvas preview matches print (not duplicate “Barcode” label text). */
$sample_field_preview = $sample_data_preview;
$sample_field_preview['Barcode'] = $sample_data_preview['BarcodeNo'] ?? $sample_data_preview['barcode'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Barcode Setting - Set Software - AuraGold</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
    <?php include 'header-script.php'; ?>
    <link rel="stylesheet" href="set-software-sidebar.css">
</head>

<style>
html, body {
    overflow-x: hidden !important;
    height: 100vh;
    background: #f4f6fb;
    /* font-family: 'Segoe UI', Arial, sans-serif; */
}

.layout-content {
    height: calc(100vh - 60px);
    overflow: hidden;
    display: flex;
}

/* Set Software: main wrapper (content + right sidebar) */
.set-software-wrapper {
    flex: 1;
    display: flex;
    min-width: 0;
    height: 100%;
    position: relative;
}

/* Left Set Software sidebar (purple) */
.set-software-sidebar {
    width: 240px;
    min-width: 240px;
    background: #11294b;
    color: #fff;
    padding: 16px 0;
    display: flex;
    flex-direction: column;
    position: relative;
    flex-shrink: 0;
    transition: width 0.25s ease, min-width 0.25s ease, opacity 0.2s ease, padding 0.25s ease;
    overflow: hidden;
}

.set-software-wrapper.set-software-sidebar-collapsed .set-software-sidebar {
    width: 0;
    min-width: 0;
    max-width: 0;
    padding-left: 0;
    padding-right: 0;
    opacity: 0;
    pointer-events: none;
    border: none;
}

.set-software-sidebar-title {
    padding: 12px 16px;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255,255,255,0.9);
    border-bottom: 1px solid rgba(255,255,255,0.15);
    margin-bottom: 8px;
}

.set-software-nav-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    font-size: 12px;
    transition: background 0.2s, color 0.2s;
}

.set-software-nav-item:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.set-software-nav-item.active {
    background: rgba(255,255,255,0.2);
    color: #fff;
    font-weight: 600;
}

.set-software-nav-item i {
    margin-right: 8px;
    opacity: 0.9;
}

.set-software-collapse-tab {
    position: absolute;
    left: calc(240px - 28px);
    top: 50%;
    transform: translateY(-50%);
    z-index: 25;
    width: 28px;
    height: 60px;
    margin: 0;
    padding: 0;
    border: none;
    background: #11294b;
    border-radius: 0 6px 6px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
    font-size: 12px;
    box-shadow: 2px 0 6px rgba(0,0,0,0.1);
    transition: left 0.25s ease, background 0.2s ease;
}

.set-software-wrapper.set-software-sidebar-collapsed .set-software-collapse-tab {
    left: 0;
}

/* Main content area (title bar + canvas) */
.set-software-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: #fff;
}

/* Barcode page top bar */
.barcode-top-bar {
    padding: 12px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.barcode-page-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.barcode-top-controls {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.barcode-control-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.barcode-control-group label {
    font-size: 11px;
    color: #64748b;
    white-space: nowrap;
}

.barcode-control-group input,
.barcode-control-group select {
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 11px;
    min-width: 80px;
}

.barcode-check-wrap label {
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    cursor: pointer;
}
.barcode-check-wrap input[type="checkbox"] {
    min-width: auto;
    margin: 0;
}

.barcode-top-actions {
    display: flex;
    gap: 10px;
}

.btn-clone-barcode {
    background: #fff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 11px;
    cursor: pointer;
    font-weight: 500;
}

.btn-clone-barcode:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.btn-save-barcode {
    background: #11294b;
    border: none;
    color: #fff;
    padding: 8px 20px;
    border-radius: 6px;
    font-size: 11px;
    cursor: pointer;
    font-weight: 500;
}

.btn-save-barcode:hover {
    background: #4a2b7c;
}

/* Canvas area */
.barcode-canvas-wrap {
    flex: 1;
    overflow: auto;
    padding: 24px;
    background: #fff;
}

/* Labels container for multiple labels */
.barcode-labels-container {
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.barcode-labels-container .barcode-preview-block {
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 10px;
    cursor: move;
    pointer-events: auto;
}

.barcode-label-2 .barcode-default-inner {
    background: #e8e8e8;
}

.barcode-canvas {
    min-height: 400px;
    background-image:
        linear-gradient(rgba(0,0,0,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,0,0,0.04) 1px, transparent 1px);
    background-size: 12px 12px;
    background-color: #fafafa;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Drop zone layer - receives dropped toolbox fields */
.barcode-canvas-drops {
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    border-radius: 8px;
    z-index: 5;
}

.barcode-canvas-drops.drag-over {
    background: rgba(90, 59, 140, 0.06);
    outline: 2px dashed #11294b;
    outline-offset: -2px;
}

/* Placed field on barcode white block: simple text only, no background highlighter */
.canvas-dropped-item {
    position: absolute;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0 2px;
    font-size: 11px;
    font-weight: normal;
    color: #1e293b;
    background: transparent !important;
    border: none !important;
    border-radius: 0;
    cursor: move;
    white-space: nowrap;
    z-index: 10;
    user-select: none;
    outline: none;
}

.canvas-dropped-item:hover {
    color: #334155;
}

.canvas-dropped-item .canvas-item-text {
    border: none;
    background: none;
}

/* No delete icon in barcode block – delete only from toolbox (right side) */
.canvas-dropped-item .canvas-item-remove {
    display: none !important;
}

.canvas-dropped-item .canvas-item-remove:hover {
    opacity: 1;
}

.canvas-dropped-item .canvas-item-remove svg {
    width: 12px;
    height: 12px;
    fill: #64748b;
}

.canvas-dropped-item .canvas-item-remove:hover svg {
    fill: #dc2626;
}

/* Barcode block: position absolute so it can be dragged; cursor move */
.barcode-preview-block {
    position: absolute;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    min-height: 80px;
    cursor: move;
    z-index: 6;
    user-select: none;
}

/* Wrapper: single centered container (exact match to reference image) */
.barcode-default-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Outer: grey = tag backing only (not part of mm print size). Width/height set in JS from mm + strips + padding. */
.barcode-default-inner {
    display: flex;
    align-items: stretch;
    background: #e8e8e8;
    padding: 14px 18px;
    border-radius: 14px;
    border: 1px dashed #94a3b8;
    width: auto;
    min-width: 128px;
    min-height: 64px;
    box-sizing: border-box;
}
.barcode-default-inner.barcode-tag-backing {
    box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.35);
}

/* Left strip: tag backing only (not printable); grey like outer ends */
.barcode-default-left-strip {
    width: 16px;
    min-width: 16px;
    background: #e8e8e8;
    border-radius: 14px 0 0 14px;
    flex-shrink: 0;
    margin-right: -1px;
}

/* Middle: exact mm print face; padding 0 so #labelCanvas matches label_width_mm × label_height_mm */
.barcode-default-white {
    flex: 1;
    min-width: 0;
    background: #fff;
    border-radius: 0;
    padding: 0;
    margin-right: 0;
    position: relative;
    overflow: hidden;
    box-sizing: border-box;
}
.barcode-default-white.barcode-print-area-mm {
    flex: 0 0 auto;
    outline: 2px dashed rgba(34, 197, 94, 0.7);
    outline-offset: -1px;
}
/* Free-positioning canvas: must not exceed label height (short 18mm labels are ~54px); old min-height:80px made center guide extend below white label */
.barcode-label-canvas {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 0;
    box-sizing: border-box;
    overflow: hidden;
}
/* Visual guide: clipped to canvas; does not extend past barcode label box */
.barcode-label-center-guide {
    position: absolute;
    left: 50%;
    top: 0;
    height: 100%;
    bottom: auto;
    width: 0;
    border-left: 1px dashed rgba(22, 163, 74, 0.55);
    transform: translateX(-50%);
    pointer-events: none;
    z-index: 1;
}
#labelPreview1,
#labelPreview2 {
    position: relative;
}
/* Barcode box: absolute; default 0,0 so block can sit flush left (saved left/top override after load) */
#barcode1,
#barcode2 {
    position: absolute;
    left: 0;
    top: 0;
    z-index: 5;
    margin: 0;
    transform: none !important;
}

/* Drop zone: pass-through by default so barcode and canvas items can be dragged; only receive drops when dragging from toolbox */
.barcode-white-drop-zone {
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    z-index: 2;
    border-radius: 0;
    pointer-events: none;
}
.barcode-white-drop-zone.dragging-active {
    pointer-events: auto;
    z-index: 4;
}
.barcode-white-drop-zone.drag-over,
.barcode-white-drop-zone.dragging-active {
    z-index: 4;
}
.barcode-white-drop-zone.drag-over {
    background: rgba(90, 59, 140, 0.06);
    outline: 2px dashed #a78bfa;
    outline-offset: -2px;
}

/* Right handle: tag backing only (not printable) */
.barcode-default-handle {
    width: 32px;
    min-width: 32px;
    background: #e8e8e8;
    border-radius: 0 14px 14px 0;
    flex-shrink: 0;
    margin-left: -1px;
    box-shadow: none;
}

/* Barcode print: absolute for drag; block stack — stripes clip to layout box, text below */
.barcode-print-wrap {
    position: absolute;
    left: 0;
    top: 0;
    margin: 0;
    padding: 0;
    cursor: move;
    min-height: 20px;
    display: block;
    box-sizing: border-box;
    overflow: visible;
}
.barcode-print-wrap .barcode-stripes {
    display: block;
    box-sizing: border-box;
    width: 100%;
    height: 100%;
    overflow: hidden;
}
.barcode-print-wrap:hover {
    outline: 1px dashed rgba(90, 59, 140, 0.5);
    outline-offset: 1px;
}
.barcode-text {
    margin-top: 2px;
    text-align: left;
    font-size: 9px;
    line-height: 1;
    color: #000;
    width: auto;
    max-width: 100%;
    align-self: flex-start;
    box-sizing: border-box;
}
.barcode-default-white .barcode-stripes {
    display: block;
    min-width: 40px;
    width: 100%;
    min-height: 28px;
    margin: 0;
    background: #fff;
    box-sizing: border-box;
    overflow: hidden;
}
/* Fallback: show black lines when barcode area is empty (before JsBarcode runs or if it fails) */
.barcode-default-white .barcode-stripes:empty {
    background: repeating-linear-gradient(90deg, #000 0px, #000 2px, transparent 2px, transparent 4px);
    min-height: 28px;
}
/* Scale JsBarcode output to the layout box — no content-based overflow */
.barcode-default-white .barcode-stripes svg,
.barcode-default-white .barcode-stripes img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100%;
    min-height: 0;
    display: block;
    vertical-align: middle;
    object-fit: contain;
    object-position: left center;
}

.barcode-preview-block .barcode-label {
    font-size: 12px;
    font-weight: 500;
    color: #1e293b;
    margin-top: 8px;
    text-align: center;
}

/* 100mm x 18mm (and other short labels): flat wide shape, reduced padding, small barcode */
.barcode-default-inner.barcode-label-short {
    padding: 6px 10px;
}
.barcode-default-inner.barcode-label-short .barcode-default-white {
    padding: 0;
}
.barcode-default-inner.barcode-label-short .barcode-print-wrap {
    margin-bottom: 0;
}
.barcode-default-inner.barcode-label-short .barcode-stripes {
    min-height: 18px;
    min-width: 40px;
    width: 100%;
}
.barcode-default-inner.barcode-label-short .barcode-default-handle {
    width: 20px;
    min-width: 20px;
}
.barcode-print-preview-label .barcode-svg-wrap { margin: 0; padding: 0; z-index: 0; display: flex; align-items: center; }
.barcode-print-preview-label .barcode-svg-wrap svg { width: 100%; height: 100%; display: block; }
.barcode-print-preview-label .design-field { margin: 0; padding: 0; z-index: 1; line-height: 1.2; color: #1e293b; }
.barcode-default-inner.barcode-label-short .barcode-default-left-strip {
    width: 10px;
    min-width: 10px;
    border-radius: 10px 0 0 10px;
}

/* Right sidebar: Toolbox + Properties */
.barcode-right-sidebar {
    width: 360px;
    min-width: 360px;
    background: #fff;
    border-left: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.barcode-panel {
    border-bottom: 1px solid #e2e8f0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.barcode-panel:last-child {
    border-bottom: none;
}

.barcode-panel-title {
    padding: 12px 16px;
    font-weight: 600;
    font-size: 12px;
    color: #1e293b;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

/* Toolbox metal tabs */
.toolbox-tabs {
    display: flex;
    flex-wrap: wrap;
    padding: 4px 8px;
    gap: 4px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.toolbox-tab {
    padding: 6px 10px;
    font-size: 11px;
    color: #64748b;
    background: transparent;
    border: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    white-space: nowrap;
}

.toolbox-tab:hover {
    color: #11294b;
}

.toolbox-tab.active {
    color: #11294b;
    font-weight: 600;
    border-bottom-color: #11294b;
}

/* QR Code preview styling */
.qr-code-preview {
    width: 60px;
    height: 60px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qr-code-preview svg {
    width: 50px;
    height: 50px;
}

.barcode-qr-toggle {
    padding: 6px 16px 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 11px;
    color: #ffffff;
}

.barcode-qr-toggle .toggle-option {
    padding: 4px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.barcode-qr-toggle .toggle-option.active {
    background: #11294b;
    color: #fff;
}

.barcode-qr-toggle .toggle-option:not(.active) {
    background: #f1f5f9;
    color: #64748b;
}

.toolbox-fields {
    padding: 6px 12px 6px 12px;
    overflow-y: auto;
    max-height: 320px;
    display: flex;
    flex-wrap: wrap;
    align-content: flex-start;
    gap: 8px 10px;
}

.toolbox-field-item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #11294b;
    background: #ede9fe;
    border: 1px solid #c4b5fd;
    border-radius: 6px;
    cursor: grab;
    white-space: nowrap;
    flex-shrink: 0;
}

.toolbox-field-item.dragging {
    opacity: 0.6;
}

.toolbox-field-item:hover {
    background: #ddd6fe;
    border-color: #a78bfa;
    color: #4c1d95;
}

.toolbox-field-item.selected {
    background: #11294b;
    color: #fff;
    border-color: #11294b;
}

/* Highlight toolbox columns that are already on the label (dropped) */
.toolbox-field-item.on-label {
    background: #c4b5fd;
    border-color: #8b5cf6;
    color: #4c1d95;
}
.toolbox-field-item.on-label:hover {
    background: #a78bfa;
    border-color: #7c3aed;
}

.toolbox-field-item .field-trash {
    display: none;
    align-items: center;
    justify-content: center;
    margin-left: 6px;
    padding: 2px;
    cursor: pointer;
    opacity: 0.6;
}

.toolbox-field-item.on-label .field-trash {
    display: inline-flex;
}

.toolbox-field-item .field-trash:hover {
    opacity: 1;
}

.toolbox-field-item .field-trash svg {
    width: 14px;
    height: 14px;
    fill: currentColor;
}

.toolbox-field-item .field-trash:hover svg {
    fill: #dc2626;
}

.toolbox-field-image {
    border-left: 3px solid #11294b;
    padding-left: 10px;
}

.toolbox-search-wrap {
    padding: 8px 16px 6px;
}
.toolbox-column-search {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    outline: none;
}
.toolbox-column-search:focus {
    border-color: #11294b;
    box-shadow: 0 0 0 2px rgba(17, 41, 75, 0.15);
}
.toolbox-column-search::placeholder {
    color: #94a3b8;
}
.toolbox-field-item.toolbox-search-hidden {
    display: none !important;
}

.toolbox-fields-divider {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 6px 0 4px;
    margin: 4px 0 0;
    border-top: 1px solid #e2e8f0;
    width: 100%;
    flex-basis: 100%;
}

/* Properties */
.properties-body {
    padding: 16px;
    overflow-y: auto;
    max-height: 320px;
}

.prop-row {
    margin-bottom: 14px;
}
.prop-row.prop-row-cols {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    align-items: end;
}
.prop-row.prop-row-cols .prop-field { margin-bottom: 0; }

.prop-row label {
    display: block;
    font-size: 12px;
    color: #64748b;
    margin-bottom: 4px;
    font-weight: 500;
}

.prop-row input,
.prop-row select {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 11px;
    box-sizing: border-box;
}

.prop-row input[type="number"] {
    width: 100%;
    min-width: 0;
}
.prop-row.prop-row-cols input[type="number"] {
    width: 100%;
}
.prop-row .prop-field { width: 100%; }

.prop-hint { font-size: 11px; color: #94a3b8; display: block; margin-top: 4px; }
.move-buttons, .barcode-size-buttons { display: flex; gap: 6px; flex-wrap: wrap; }
.btn-move, .btn-size {
    width: 36px; height: 36px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-size: 18px;
    font-weight: 600;
    line-height: 1;
    color: #334155;
    display: flex; align-items: center; justify-content: center;
}
.btn-move:hover, .btn-size:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }
</style>

<body>
<?php include 'sidebar.php'; ?>

<div class="layout-content">
    <div class="set-software-wrapper">
        <?php include 'set-software-sidebar.php'; ?>

        <!-- Main content -->
        <div class="set-software-main">
            <?php include __DIR__ . '/includes/set-software-branch-banner.php'; ?>
            <div class="barcode-top-bar">
                <h1 class="barcode-page-title">Barcode Setting</h1>
                <div class="barcode-top-controls">
                    <div class="barcode-control-group">
                        <label>No.of Copies</label>
                        <input type="number" value="<?php echo (int)($bs['print_copies'] ?? 1); ?>" min="1" max="100" id="barcodeCopies">
                    </div>
                    <div class="barcode-control-group">
                        <label>Label Size</label>
                        <select id="barcodeLabelSize">
                            <option value="">Select</option>
                            <option value="100x18" <?php echo ($bs['label_size_preset'] ?? '') === '100x18' ? 'selected' : ''; ?>>100mm x 18mm</option>
                            <!-- <option value="100x25" <?php echo ($bs['label_size_preset'] ?? '') === '100x25' ? 'selected' : ''; ?>>100mm x 25mm</option>
                            <option value="100x48" <?php echo ($bs['label_size_preset'] ?? '') === '100x48' ? 'selected' : ''; ?>>100mm x 48mm</option>
                            <option value="120x50" <?php echo ($bs['label_size_preset'] ?? '') === '120x50' ? 'selected' : ''; ?>>120mm x 50mm</option>
                            <option value="250x120" <?php echo ($bs['label_size_preset'] ?? '') === '250x120' ? 'selected' : ''; ?>>250mm x 120mm</option>
                            <option value="64x25" <?php echo ($bs['label_size_preset'] ?? '') === '64x25' ? 'selected' : ''; ?>>64mm x 25mm</option>
                            <option value="81x12" <?php echo ($bs['label_size_preset'] ?? '') === '81x12' ? 'selected' : ''; ?>>81mm x 12mm</option>
                            <option value="zebra-zpl" <?php echo ($bs['label_size_preset'] ?? '') === 'zebra-zpl' ? 'selected' : ''; ?>>Zebra ZPL</option> -->
                            <option value="custom" <?php echo ($bs['label_size_preset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom</option>
                        </select>
                    </div>
                    <div class="barcode-control-group barcode-custom-size-wrap" id="barcodeCustomSizeWrap" style="display: none;">
                        <label>Width (mm)</label>
                        <input type="number" id="barcodeCustomWidthMm" value="<?php echo (float)($bs['label_width_mm'] ?? 100); ?>" min="10" max="500" step="1" style="width: 70px;">
                    </div>
                    <div class="barcode-control-group barcode-custom-size-wrap" id="barcodeCustomHeightWrap" style="display: none;">
                        <label>Height (mm)</label>
                        <input type="number" id="barcodeCustomHeightMm" value="<?php echo (float)($bs['label_height_mm'] ?? 18); ?>" min="10" max="300" step="1" style="width: 70px;">
                    </div>
                    <div class="barcode-control-group">
                        <label>Font Size</label>
                        <input type="number" id="barcodeFontSize" value="<?php echo (int)($bs['font_size'] ?? 12); ?>" min="6" max="72" style="width: 60px;">
                    </div>
                    <div class="barcode-control-group barcode-check-wrap">
                        <label><input type="checkbox" id="barcodeShowProductName" <?php echo ((int)$show_product_name === 1) ? 'checked' : ''; ?>> Product name</label>
                    </div>
                    <div class="barcode-control-group barcode-check-wrap">
                        <label><input type="checkbox" id="barcodeShowPrice" <?php echo ((int)$show_price === 1) ? 'checked' : ''; ?>> Price</label>
                    </div>
                    <div class="barcode-control-group barcode-check-wrap">
                        <label><input type="checkbox" id="barcodeShowBarcodeNo" <?php echo ((int)$show_barcode_number === 1) ? 'checked' : ''; ?>> Barcode no.</label>
                    </div>
                    <div class="barcode-control-group">
                        <label>Metal Type</label>
                        <select id="barcodeMetalType">
                            <option value="" <?php echo $bs_metal === '' ? 'selected' : ''; ?>>Select</option>
                            <?php foreach ($barcode_metals as $_bm) {
                                $_dn = isset($_bm['display_name']) ? trim((string) $_bm['display_name']) : '';
                                if ($_dn === '') {
                                    continue;
                                }
                                $_sn = isset($_bm['system_name']) ? trim((string) $_bm['system_name']) : '';
                                $_sel = ($bs_metal !== '' && ($bs_metal === $_dn || ($_sn !== '' && strcasecmp($bs_metal, $_sn) === 0))) ? ' selected' : '';
                                ?>
                            <option value="<?php echo htmlspecialchars($_dn, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $_sel; ?>><?php echo htmlspecialchars($_dn, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="barcode-control-group">
                        <label>User</label>
                        <select id="barcodeUser">
                            <option>All</option>
                        </select>
                    </div>
                </div>
                <div class="barcode-top-actions">
                    <button type="button" class="btn-clone-barcode">Clone Barcode Setting</button>
                    <div class="barcode-control-group">
                        <label>Default print</label>
                        <select id="defaultPrintCodeType" title="Used when product print URL does not include &amp;code=">
                            <option value="barcode" <?php echo $bs_default_print_code === 'barcode' ? 'selected' : ''; ?>>Barcode</option>
                            <option value="qr" <?php echo $bs_default_print_code === 'qr' ? 'selected' : ''; ?>>QR</option>
                        </select>
                    </div>
                    <button type="button" class="btn-save-barcode" id="btnSaveBarcodeSettings">Save</button>
                </div>
            </div>

            <div class="barcode-canvas-wrap">
                <p class="barcode-canvas-hint" style="font-size:12px;color:#64748b;margin:0 0 10px;line-height:1.45;">Each column (e.g. Barcode) appears once on the label. The scannable barcode graphic is always shown; the Barcode column adds the number as text.</p>
                <p class="barcode-mm-canvas-hint" id="barcodeMmCanvasHint" style="font-size:12px;color:#64748b;margin:0 0 10px;line-height:1.45;"></p>
                <div class="barcode-canvas" id="barcodeCanvas">
                    <div class="barcode-canvas-drops" id="barcodeCanvasDrops"></div>
                    <div class="barcode-labels-container" id="barcodeLabelsContainer">
                        <!-- Label 1 (Left) -->
                        <div class="barcode-preview-block" id="barcodeLabel1">
                            <div class="barcode-default-wrap">
                                <div class="barcode-default-inner">
                                    <div class="barcode-default-left-strip" title="Left strip"></div>
                                    <div class="barcode-default-white" id="labelPreview1">
                                        <div id="labelCanvas1" class="barcode-label-canvas">
                                            <div class="barcode-label-center-guide" aria-hidden="true" title="Center of label (half width)"></div>
                                            <div class="barcode-print-wrap" id="barcode1">
                                                <span class="barcode-stripes"></span>
                                                <div class="barcode-text" id="barcodeText1"><?php echo htmlspecialchars(trim((string)($sample_data_preview['BarcodeNo'] ?? $sample_data_preview['barcode'] ?? '00002')) ?: '00002', ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                            <div class="barcode-white-drop-zone" id="barcodeWhiteDropZone" title="Drop columns here"></div>
                                        </div>
                                    </div>
                                    <div class="barcode-default-handle" title="Handle"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Label 2 (Right) - shown for larger sizes -->
                        <div class="barcode-preview-block barcode-label-2" id="barcodeLabel2" style="display: none;">
                            <div class="barcode-default-wrap">
                                <div class="barcode-default-inner">
                                    <div class="barcode-default-left-strip" title="Left strip"></div>
                                    <div class="barcode-default-white" id="labelPreview2">
                                        <div id="labelCanvas2" class="barcode-label-canvas">
                                            <div class="barcode-label-center-guide" aria-hidden="true" title="Center of label (half width)"></div>
                                            <div class="barcode-print-wrap" id="barcode2">
                                                <span class="barcode-stripes"></span>
                                                <div class="barcode-text" id="barcodeText2"><?php echo htmlspecialchars(trim((string)($sample_data_preview['BarcodeNo'] ?? $sample_data_preview['barcode'] ?? '00002')) ?: '00002', ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                            <div class="barcode-white-drop-zone" id="barcodeWhiteDropZone2" title="Drop columns here"></div>
                                        </div>
                                    </div>
                                    <div class="barcode-default-handle" title="Handle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right sidebar: Toolbox + Properties -->
        <aside class="barcode-right-sidebar">
            <div class="barcode-panel">
                <div class="barcode-panel-title">Toolbox</div>
                <div class="toolbox-tabs">
                    <button type="button" class="toolbox-tab active" data-category="gold">Gold</button>
                    <button type="button" class="toolbox-tab" data-category="silver">Silver</button>
                    <button type="button" class="toolbox-tab" data-category="platinum">Platinum</button>
                    <button type="button" class="toolbox-tab" data-category="diamond">Diamond &amp; Stones</button>
                    <button type="button" class="toolbox-tab" data-category="imitation">Imitation Or Watches</button>
                    <button type="button" class="toolbox-tab" data-category="other">Other Or Services</button>
                </div>
                <div class="barcode-qr-toggle">
                    <span class="toggle-option<?php echo $bs_default_print_code === 'barcode' ? ' active' : ''; ?>" data-type="barcode">Barcode</span>
                    <span class="toggle-option<?php echo $bs_default_print_code === 'qr' ? ' active' : ''; ?>" data-type="qr">QR Code</span>
                </div>
                <div class="toolbox-search-wrap">
                    <input type="text" id="toolboxColumnSearch" class="toolbox-column-search" placeholder="Search columns..." autocomplete="off">
                </div>
                <?php
                $barcode_toolbox_tabs = [
                    ['id' => 'toolboxFieldsGold', 'class' => 'toolbox-fields-gold', 'divider' => 'Gold related columns', 'display' => 'flex'],
                    ['id' => 'toolboxFieldsSilver', 'class' => 'toolbox-fields-silver', 'divider' => 'Silver related columns', 'display' => 'none'],
                    ['id' => 'toolboxFieldsPlatinum', 'class' => 'toolbox-fields-platinum', 'divider' => 'Platinum related columns', 'display' => 'none'],
                    ['id' => 'toolboxFieldsDiamond', 'class' => 'toolbox-fields-diamond', 'divider' => 'Diamond & Stones related columns', 'display' => 'none'],
                    ['id' => 'toolboxFieldsImitation', 'class' => 'toolbox-fields-imitation', 'divider' => 'Imitation/Watches related columns', 'display' => 'none'],
                    ['id' => 'toolboxFieldsOther', 'class' => 'toolbox-fields-other', 'divider' => 'Other/Services related columns', 'display' => 'none'],
                ];
                foreach ($barcode_toolbox_tabs as $bt) {
                    $barcode_toolbox_divider = $bt['divider'];
                ?>
                <div class="toolbox-fields <?php echo htmlspecialchars($bt['class'], ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($bt['id'], ENT_QUOTES, 'UTF-8'); ?>" style="display:<?php echo htmlspecialchars($bt['display'], ENT_QUOTES, 'UTF-8'); ?>;">
                <?php include __DIR__ . '/includes/barcode-toolbox-field-chips.php'; ?>
                </div>
                <?php } ?>
            </div>
            <div class="barcode-panel">
                <div class="barcode-panel-title">Properties</div>
                <div class="properties-body">
                    <div class="prop-row prop-row-cols">
                        <div class="prop-field">
                            <label>Prefix</label>
                            <input type="text" value="" id="propPrefix" placeholder="">
                        </div>
                        <div class="prop-field">
                            <label>Suffix</label>
                            <input type="text" value="" id="propSuffix" placeholder="">
                        </div>
                    </div>
                    <div class="prop-row">
                        <label>Number Of Decimal</label>
                        <input type="number" value="0" min="0" id="propDecimals">
                    </div>
                    <div class="prop-row prop-row-cols">
                        <div class="prop-field">
                            <label>Font</label>
                            <select id="propFont">
                                <option value="Arial">Arial</option>
                                <option value="Segoe UI">Segoe UI</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Microsoft YaHei UI">Microsoft YaHei UI</option>
                                <option value="Tahoma">Tahoma</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Trebuchet MS">Trebuchet MS</option>
                                <option value="Lucida Sans Unicode">Lucida Sans Unicode</option>
                                <option value="Lucida Console">Lucida Console</option>
                                <option value="Consolas">Consolas</option>
                                <option value="Calibri">Calibri</option>
                                <option value="Cambria">Cambria</option>
                                <option value="Candara">Candara</option>
                                <option value="Constantia">Constantia</option>
                                <option value="Corbel">Corbel</option>
                                <option value="Franklin Gothic Medium">Franklin Gothic Medium</option>
                                <option value="Garamond">Garamond</option>
                                <option value="Impact">Impact</option>
                                <option value="Palatino Linotype">Palatino Linotype</option>
                                <option value="Century Gothic">Century Gothic</option>
                                <option value="Comic Sans MS">Comic Sans MS</option>
                                <option value="Helvetica">Helvetica</option>
                                <option value="Helvetica Neue">Helvetica Neue</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Lato">Lato</option>
                                <option value="Source Sans Pro">Source Sans Pro</option>
                                <option value="PT Sans">PT Sans</option>
                                <option value="Oswald">Oswald</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Poppins">Poppins</option>
                                <option value="Ubuntu">Ubuntu</option>
                                <option value="Noto Sans">Noto Sans</option>
                                <option value="sans-serif">sans-serif</option>
                                <option value="serif">serif</option>
                                <option value="monospace">monospace</option>
                            </select>
                        </div>
                        <div class="prop-field">
                            <label>Font Size</label>
                            <input type="number" value="10" min="6" max="72" id="propFontSize">
                        </div>
                    </div>
                    <div class="prop-row prop-row-cols">
                        <div class="prop-field">
                            <label>Label · Padding Top (px)</label>
                            <input type="number" value="<?php echo (int)$bs['label_pad_top']; ?>" min="0" max="200" step="1" id="labelPadTop">
                        </div>
                        <div class="prop-field">
                            <label>Label · Padding Right (px)</label>
                            <input type="number" value="<?php echo (int)$bs['label_pad_right']; ?>" min="0" max="200" step="1" id="labelPadRight">
                        </div>
                    </div>
                    <div class="prop-row prop-row-cols">
                        <div class="prop-field">
                            <label>Label · Padding Bottom (px)</label>
                            <input type="number" value="<?php echo (int)$bs['label_pad_bottom']; ?>" min="0" max="200" step="1" id="labelPadBottom">
                        </div>
                        <div class="prop-field">
                            <label>Label · Padding Left (px)</label>
                            <input type="number" value="<?php echo (int)$bs['label_pad_left']; ?>" min="0" max="200" step="1" id="labelPadLeft">
                        </div>
                    </div>
                    <div class="prop-row">
                        <small class="prop-hint" style="display:block;margin-top:2px;color:#64748b;">Label padding applies to the whole printed label on barcode print; preview here does not change.</small>
                    </div>
                    <div class="prop-row prop-row-cols">
                        <div class="prop-field">
                            <label>Field · Padding Top (px)</label>
                            <input type="number" value="0" min="0" max="200" step="1" id="propPadTop">
                        </div>
                        <div class="prop-field">
                            <label>Field · Padding Right (px)</label>
                            <input type="number" value="0" min="0" max="200" step="1" id="propPadRight">
                        </div>
                    </div>
                    <div class="prop-row prop-row-cols">
                        <div class="prop-field">
                            <label>Field · Padding Bottom (px)</label>
                            <input type="number" value="0" min="0" max="200" step="1" id="propPadBottom">
                        </div>
                        <div class="prop-field">
                            <label>Field · Padding Left (px)</label>
                            <input type="number" value="0" min="0" max="200" step="1" id="propPadLeft">
                        </div>
                    </div>
                    <div class="prop-row">
                        <small class="prop-hint" style="display:block;margin-top:2px;color:#64748b;">Field padding is for the selected text item only on print.</small>
                    </div>
                    <div class="prop-row prop-row-move">
                        <label>Move selected</label>
                        <div class="move-buttons">
                            <button type="button" class="btn-move" id="btnMoveUp" title="Up">↑</button>
                            <button type="button" class="btn-move" id="btnMoveDown" title="Down">↓</button>
                            <button type="button" class="btn-move" id="btnMoveLeft" title="Left">←</button>
                            <button type="button" class="btn-move" id="btnMoveRight" title="Right">→</button>
                        </div>
                        <small class="prop-hint">Or use arrow keys</small>
                    </div>
                    <div class="prop-row prop-row-barcode-size">
                        <label>Barcode size</label>
                        <div class="barcode-size-buttons">
                            <button type="button" class="btn-size" id="btnBarcodeDecrease" title="Smaller: stripe area, bar height, and line thickness" aria-label="Decrease barcode size">&#8722;</button>
                            <button type="button" class="btn-size" id="btnBarcodeIncrease" title="Larger: stripe area, bar height, and line thickness" aria-label="Increase barcode size">+</button>
                            <button type="button" class="btn-size" id="btnBarcodeCenter" title="Center barcode on label" aria-label="Center barcode">&#9675;</button>
                        </div>
                    </div>
                    <div class="prop-row prop-row-cols prop-row-barcode-bar" id="propRowBarcodeBar">
                        <div class="prop-field">
                            <label>Bar width (px)</label>
                            <input type="number" value="<?php echo (int)($bs['barcode_bar_width'] ?? 2); ?>" min="1" max="10" id="propBarcodeBarWidth" title="Thickness of each black line (1–10)">
                        </div>
                        <div class="prop-field">
                            <label>Bar height (px)</label>
                            <input type="number" value="<?php echo (int)($bs['barcode_bar_height'] ?? 28); ?>" min="10" max="200" id="propBarcodeBarHeight" title="Height of barcode lines (10–200)">
                        </div>
                    </div>
                    <div class="prop-row prop-row-cols prop-row-qr-size" id="propRowQrSize" style="display: none;">
                        <div class="prop-field">
                            <label>QR width (px)</label>
                            <input type="number" value="<?php echo (int)($bs['qr_width'] ?? 60); ?>" min="30" max="200" id="propQrWidth" title="QR code width (30–200)">
                        </div>
                        <div class="prop-field">
                            <label>QR height (px)</label>
                            <input type="number" value="<?php echo (int)($bs['qr_height'] ?? 60); ?>" min="30" max="200" id="propQrHeight" title="QR code height (30–200)">
                        </div>
                    </div>
                    <small class="prop-hint prop-hint-barcode" id="propHintBarcode" style="margin-top: 2px;">Bar width = thickness of each black line (1 = thinnest). Bar height = line height. Drag the barcode on the label or use Move arrows to set position; Save applies to print.</small>
                    <small class="prop-hint prop-hint-qr" id="propHintQr" style="margin-top: 2px; display: none;">QR width/height = size of QR code.</small>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php include 'footer-script.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
(function() {
    var savedDesignLayoutBarcodeInit = <?php echo json_encode($bs_design_layout); ?>;
    var savedDesignLayoutQrInit = <?php echo json_encode($bs_design_layout_qr); ?>;
    var persistedLayoutBarcode = savedDesignLayoutBarcodeInit;
    var persistedLayoutQr = (savedDesignLayoutQrInit && String(savedDesignLayoutQrInit).trim()) ? savedDesignLayoutQrInit : '{}';
    var persistedShowProductNameBarcode = <?php echo (int)$show_product_name_barcode; ?>;
    var persistedShowProductNameQr = <?php echo (int)$show_product_name_qr; ?>;
    var persistedShowPriceBarcode = <?php echo (int)$show_price_barcode; ?>;
    var persistedShowPriceQr = <?php echo (int)$show_price_qr; ?>;
    var persistedShowBarcodeNoBarcode = <?php echo (int)$show_barcode_number_barcode; ?>;
    var persistedShowBarcodeNoQr = <?php echo (int)$show_barcode_number_qr; ?>;
    var currentCodeType = <?php echo json_encode($bs_default_print_code === 'qr' ? 'qr' : 'barcode'); ?>;
    var savedDesignLayout = (currentCodeType === 'qr') ? persistedLayoutQr : persistedLayoutBarcode;
    var sampleFieldPreview = <?php echo json_encode($sample_field_preview); ?>;
    var fieldsByCategory = {
        gold: document.getElementById('toolboxFieldsGold'),
        silver: document.getElementById('toolboxFieldsSilver'),
        platinum: document.getElementById('toolboxFieldsPlatinum'),
        diamond: document.getElementById('toolboxFieldsDiamond'),
        imitation: document.getElementById('toolboxFieldsImitation'),
        other: document.getElementById('toolboxFieldsOther')
    };

    function filterToolboxBySearch() {
        var searchInput = document.getElementById('toolboxColumnSearch');
        var q = (searchInput && searchInput.value) ? searchInput.value.trim().toLowerCase() : '';
        var activeCat = document.querySelector('.toolbox-tab.active');
        var cat = activeCat ? activeCat.getAttribute('data-category') : 'gold';
        var panel = fieldsByCategory[cat];
        if (!panel) return;
        panel.querySelectorAll('.toolbox-field-item').forEach(function(item) {
            var field = (item.getAttribute('data-field') || '').toLowerCase();
            var labelEl = item.querySelector('.toolbox-field-label');
            var label = (labelEl ? labelEl.textContent : item.textContent || '').trim().toLowerCase();
            if (q === '') {
                item.classList.remove('toolbox-search-hidden');
            } else {
                var match = field.indexOf(q) >= 0 || label.indexOf(q) >= 0;
                if (match) item.classList.remove('toolbox-search-hidden');
                else item.classList.add('toolbox-search-hidden');
            }
        });
    }
    document.querySelectorAll('.toolbox-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.toolbox-tab').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var cat = this.getAttribute('data-category');
            Object.keys(fieldsByCategory).forEach(function(k) {
                if (fieldsByCategory[k]) {
                    fieldsByCategory[k].style.display = (k === cat) ? 'flex' : 'none';
                }
            });
            /* Clear search when switching metal tab — otherwise prior filter can hide every chip (e.g. Diamond tab looks empty). */
            var ts = document.getElementById('toolboxColumnSearch');
            if (ts) ts.value = '';
            bindToolboxItems();
            filterToolboxBySearch();
        });
    });
    var searchInputEl = document.getElementById('toolboxColumnSearch');
    if (searchInputEl) {
        searchInputEl.addEventListener('input', filterToolboxBySearch);
        searchInputEl.addEventListener('keyup', filterToolboxBySearch);
    }

    function getQrSize() {
        var w = parseInt(document.getElementById('propQrWidth') && document.getElementById('propQrWidth').value, 10);
        var h = parseInt(document.getElementById('propQrHeight') && document.getElementById('propQrHeight').value, 10);
        w = (isNaN(w) || w < 30) ? 60 : Math.min(200, w);
        h = (isNaN(h) || h < 30) ? 60 : Math.min(200, h);
        return { width: w, height: h };
    }
    function updatePropertiesPanelForCodeType() {
        var isBarcode = (currentCodeType === 'barcode');
        var barRow = document.getElementById('propRowBarcodeBar');
        var qrRow = document.getElementById('propRowQrSize');
        var hintBar = document.getElementById('propHintBarcode');
        var hintQr = document.getElementById('propHintQr');
        if (barRow) barRow.style.display = isBarcode ? '' : 'none';
        if (qrRow) qrRow.style.display = isBarcode ? 'none' : '';
        if (hintBar) hintBar.style.display = isBarcode ? '' : 'none';
        if (hintQr) hintQr.style.display = isBarcode ? 'none' : '';
    }
    document.querySelectorAll('.barcode-qr-toggle .toggle-option').forEach(function(opt) {
        opt.addEventListener('click', function() {
            document.querySelectorAll('.barcode-qr-toggle .toggle-option').forEach(function(o) { o.classList.remove('active'); });
            this.classList.add('active');
            var nextType = this.getAttribute('data-type');
            if (nextType === currentCodeType) return;
            flushPendingCanvasPropsToDom();
            flushCheckboxDomToPersisted();
            try {
                var pl = buildBarcodeFormPayload();
                var lp = buildBarcodeLayoutPayloadObject(pl);
                lp.layout_variant = (currentCodeType === 'qr') ? 'qr' : 'barcode';
                if (currentCodeType === 'barcode') {
                    persistedLayoutBarcode = JSON.stringify(lp);
                } else {
                    persistedLayoutQr = JSON.stringify(lp);
                }
            } catch (e) {}
            currentCodeType = nextType;
            applyCheckboxPersistToDom();
            savedDesignLayout = (currentCodeType === 'qr') ? persistedLayoutQr : persistedLayoutBarcode;
            try {
                restoreSavedLayout();
            } catch (e2) {}
            updatePropertiesPanelForCodeType();
            updateBarcodeQrDisplay();
        });
    });

    function updateBarcodeQrDisplay() {
        var qrSize = getQrSize();
        var printWraps = document.querySelectorAll('.barcode-default-inner .barcode-print-wrap');
        printWraps.forEach(function(printWrap, index) {
            var barcodeStripes = printWrap.querySelector('.barcode-stripes');
            var qrCodeEl = printWrap.querySelector('.qr-code-preview');
            if (currentCodeType === 'qr') {
                if (barcodeStripes) { barcodeStripes.style.display = 'none'; barcodeStripes.style.visibility = 'hidden'; }
                if (!qrCodeEl) {
                    qrCodeEl = document.createElement('div');
                    qrCodeEl.className = 'qr-code-preview';
                }
                qrCodeEl.style.cssText = 'width:' + qrSize.width + 'px;height:' + qrSize.height + 'px;background:#fff;display:flex;align-items:center;justify-content:center;visibility:visible;';
                var qrCanvas = qrCodeEl.querySelector('.qr-preview-canvas');
                if (!qrCanvas) {
                    qrCanvas = document.createElement('canvas');
                    qrCanvas.className = 'qr-preview-canvas';
                    qrCodeEl.appendChild(qrCanvas);
                }
                var drawSize = Math.min(qrSize.width, qrSize.height, 150);
                qrCanvas.width = drawSize;
                qrCanvas.height = drawSize;
                qrCodeEl.style.width = qrSize.width + 'px';
                qrCodeEl.style.height = qrSize.height + 'px';
                if (!printWrap.contains(qrCodeEl)) printWrap.appendChild(qrCodeEl);
                qrCodeEl.style.display = 'flex';
                drawQRCode(qrCanvas, '00002');
            } else {
                if (barcodeStripes) { barcodeStripes.style.display = ''; barcodeStripes.style.visibility = ''; }
                if (qrCodeEl) { qrCodeEl.style.display = 'none'; qrCodeEl.style.visibility = 'hidden'; }
            }
        });
    }
    updatePropertiesPanelForCodeType();
    updateBarcodeQrDisplay();

    function drawQRCode(canvas, text) {
        var ctx = canvas.getContext('2d');
        var size = canvas.width;
        var moduleCount = 21;
        var moduleSize = size / moduleCount;
        
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#000000';
        
        function drawModule(row, col) {
            ctx.fillRect(col * moduleSize, row * moduleSize, moduleSize, moduleSize);
        }
        
        function drawFinderPattern(startRow, startCol) {
            for (var r = 0; r < 7; r++) {
                for (var c = 0; c < 7; c++) {
                    if (r === 0 || r === 6 || c === 0 || c === 6 || (r >= 2 && r <= 4 && c >= 2 && c <= 4)) {
                        drawModule(startRow + r, startCol + c);
                    }
                }
            }
        }
        
        drawFinderPattern(0, 0);
        drawFinderPattern(0, 14);
        drawFinderPattern(14, 0);
        
        for (var i = 8; i < 13; i++) {
            if (i % 2 === 0) {
                drawModule(6, i);
                drawModule(i, 6);
            }
        }
        
        var dataPattern = [
            [0,0,0,0,0,0,0,0,1,0,1,1,0,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,0,1,0,0,1,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,1,1,0,1,0,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,1,0,0,1,0,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,0,1,1,0,1,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,1,0,1,0,1,0,0,0,0,0,0,0,0],
            [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
            [1,0,1,0,1,0,1,0,1,1,0,1,0,1,1,0,1,0,1,1,0],
            [0,1,0,1,0,1,0,0,0,0,1,0,1,0,0,1,0,1,0,0,1],
            [1,1,0,0,1,0,1,0,1,0,0,1,0,1,0,1,1,0,1,0,1],
            [0,0,1,1,0,1,0,0,0,1,1,0,1,0,1,0,0,1,0,1,0],
            [1,0,0,0,1,0,1,0,1,0,0,1,0,1,0,1,0,0,1,0,1],
            [0,0,0,0,0,0,0,0,1,1,0,0,1,0,1,0,1,0,0,1,0],
            [0,0,0,0,0,0,0,0,0,0,1,1,0,1,0,1,0,1,1,0,1],
            [0,0,0,0,0,0,0,0,1,0,0,0,1,0,1,0,0,0,0,1,0],
            [0,0,0,0,0,0,0,0,0,1,0,1,0,1,0,1,1,0,1,0,1],
            [0,0,0,0,0,0,0,0,1,0,1,0,1,0,1,0,0,1,0,1,0],
            [0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,1,0,0,1,0,1],
            [0,0,0,0,0,0,0,0,1,1,0,0,1,0,1,0,1,1,0,1,0],
            [0,0,0,0,0,0,0,0,0,0,1,0,0,1,0,1,0,0,1,0,1]
        ];
        
        for (var row = 0; row < moduleCount; row++) {
            for (var col = 0; col < moduleCount; col++) {
                if ((row < 7 && col < 7) || (row < 7 && col >= 14) || (row >= 14 && col < 7)) {
                    continue;
                }
                if (row === 6 || col === 6) {
                    continue;
                }
                if (dataPattern[row] && dataPattern[row][col] === 1) {
                    drawModule(row, col);
                }
            }
        }
    }

    var toolboxTrashSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';

    function clampPadPx(v) {
        var n = parseInt(v, 10);
        if (isNaN(n)) return 0;
        return Math.max(0, Math.min(200, n));
    }
    function loadPaddingInputsFromEl(el) {
        var ids = [['propPadTop', 'data-pad-top'], ['propPadRight', 'data-pad-right'], ['propPadBottom', 'data-pad-bottom'], ['propPadLeft', 'data-pad-left']];
        ids.forEach(function(pair) {
            var inp = document.getElementById(pair[0]);
            if (inp) inp.value = el && el.getAttribute(pair[1]) != null ? String(clampPadPx(el.getAttribute(pair[1]))) : '0';
        });
    }
    function clearPaddingInputs() {
        ['propPadTop', 'propPadRight', 'propPadBottom', 'propPadLeft'].forEach(function(id) {
            var inp = document.getElementById(id);
            if (inp) inp.value = '0';
        });
    }

    /** Copy Properties panel (prefix/suffix/font/padding) onto the selected canvas item before Save so layout JSON matches the UI. */
    function flushPendingCanvasPropsToDom() {
        var sel = document.querySelector('.canvas-dropped-item.selected');
        if (!sel) return;
        var propPrefix = document.getElementById('propPrefix');
        var propSuffix = document.getElementById('propSuffix');
        var propFont = document.getElementById('propFont');
        var propFontSize = document.getElementById('propFontSize');
        if (propPrefix) sel.setAttribute('data-prefix', propPrefix.value || '');
        if (propSuffix) sel.setAttribute('data-suffix', propSuffix.value || '');
        if (propFont) sel.setAttribute('data-font', propFont.value || 'Arial');
        if (propFontSize) sel.setAttribute('data-font-size', propFontSize.value || '10');
        [['propPadTop', 'data-pad-top'], ['propPadRight', 'data-pad-right'], ['propPadBottom', 'data-pad-bottom'], ['propPadLeft', 'data-pad-left']].forEach(function(pair) {
            var inp = document.getElementById(pair[0]);
            if (inp) sel.setAttribute(pair[1], String(clampPadPx(inp.value)));
        });
    }

    /** Fill Properties (prefix/suffix/font/padding) from a canvas text item — used on click and after reload restore. */
    function syncPropertiesPanelFromDroppedItem(el) {
        if (!el || !el.classList || !el.classList.contains('canvas-dropped-item')) return;
        document.querySelectorAll('.canvas-dropped-item').forEach(function(i) { i.classList.remove('selected'); });
        el.classList.add('selected');
        var pp = document.getElementById('propPrefix');
        var ps = document.getElementById('propSuffix');
        var pf = document.getElementById('propFont');
        var pfs = document.getElementById('propFontSize');
        if (pp) pp.value = el.getAttribute('data-prefix') || '';
        if (ps) ps.value = el.getAttribute('data-suffix') || '';
        if (pf) pf.value = el.getAttribute('data-font') || 'Arial';
        if (pfs) pfs.value = el.getAttribute('data-font-size') || '10';
        loadPaddingInputsFromEl(el);
        var fn = el.getAttribute('data-field');
        if (fn) {
            document.querySelectorAll('.toolbox-field-item').forEach(function(t) {
                t.classList.toggle('selected', t.getAttribute('data-field') === fn);
            });
        }
    }

    /** After layout restore, open Properties for the Barcode text field if present (matches how users tune barcode label text first). */
    function syncPropertiesPanelAfterLayoutRestore(rootEl) {
        if (!rootEl) return;
        var el = rootEl.querySelector('.canvas-dropped-item[data-field="Barcode"]')
            || rootEl.querySelector('.canvas-dropped-item');
        if (el) syncPropertiesPanelFromDroppedItem(el);
    }

    function bindToolboxItems() {
        document.querySelectorAll('.toolbox-field-item').forEach(function(item) {
            if (item._bound) return;
            item._bound = true;
            if (!item.querySelector('.field-trash')) {
                var label = item.textContent.trim();
                item.textContent = '';
                var labelSpan = document.createElement('span');
                labelSpan.className = 'toolbox-field-label';
                labelSpan.textContent = label;
                item.appendChild(labelSpan);
                var trashSpan = document.createElement('span');
                trashSpan.className = 'field-trash';
                trashSpan.title = 'Remove from label';
                trashSpan.innerHTML = toolboxTrashSvg;
                item.appendChild(trashSpan);
            }
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', this.getAttribute('data-field') || '');
                e.dataTransfer.effectAllowed = 'copy';
                this.classList.add('dragging');
            });
            item.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
            item.addEventListener('click', function(e) {
                if (e.target.closest('.field-trash')) {
                    e.preventDefault();
                    e.stopPropagation();
                    var fieldName = this.getAttribute('data-field');
                    var lp1 = document.getElementById('labelPreview1');
                    var lp2 = document.getElementById('labelPreview2');
                    [lp1, lp2].forEach(function(cont) {
                        if (!cont) return;
                        var el = cont.querySelector('.canvas-dropped-item[data-field="' + fieldName + '"]');
                        if (el) el.remove();
                    });
                    if (typeof syncToolboxHighlight === 'function') syncToolboxHighlight();
                    return;
                }
                document.querySelectorAll('.toolbox-field-item').forEach(function(i) { i.classList.remove('selected'); });
                this.classList.add('selected');
                document.querySelectorAll('.canvas-dropped-item').forEach(function(i) { i.classList.remove('selected'); });
                var propPrefix = document.getElementById('propPrefix');
                var propSuffix = document.getElementById('propSuffix');
                if (propPrefix) propPrefix.value = '';
                if (propSuffix) propSuffix.value = '';
                clearPaddingInputs();
            });
        });
    }
    bindToolboxItems();

    // Canvas drag and drop
    var canvas = document.getElementById('barcodeCanvas');
    var dropLayer = document.getElementById('barcodeCanvasDrops');
    var dropIdCounter = 0;

    var labelPreview1 = document.getElementById('labelPreview1');
    var labelCanvas1 = document.getElementById('labelCanvas1');
    var labelCanvas2 = document.getElementById('labelCanvas2');
    var whiteDropZone = document.getElementById('barcodeWhiteDropZone');
    var barcodeBox = document.querySelector('.barcode-default-inner');
    var barcodeStripesEl = document.querySelector('.barcode-default-inner .barcode-stripes');
    var labelSizeSelect = document.getElementById('barcodeLabelSize');
    var MM_TO_PX = 3;
    var MAX_DISPLAY_WIDTH_MM = 400;

    function applyLabelSizeToBox() {
        if (!barcodeBox || !labelSizeSelect) return;
        var val = (labelSizeSelect.value || '').trim();
        var labelsContainer = document.getElementById('barcodeLabelsContainer');
        var label2 = document.getElementById('barcodeLabel2');
        if (label2) label2.style.display = 'none';
        var barcodeBox2 = label2 ? label2.querySelector('.barcode-default-inner') : null;
        var barcodeStripes2 = label2 ? label2.querySelector('.barcode-stripes') : null;
        
        function clearMmPrintArea(whiteEl) {
            if (!whiteEl) return;
            whiteEl.style.width = '';
            whiteEl.style.height = '';
            whiteEl.style.flex = '';
            whiteEl.style.minWidth = '';
            whiteEl.style.minHeight = '';
            whiteEl.classList.remove('barcode-print-area-mm');
            whiteEl.removeAttribute('title');
        }
        function clearTagBacking(innerEl) {
            if (!innerEl) return;
            innerEl.style.width = '';
            innerEl.style.minWidth = '';
            innerEl.style.height = '';
            innerEl.style.minHeight = '';
            innerEl.classList.remove('barcode-tag-backing');
            innerEl.removeAttribute('title');
        }
        if (!val) {
            clearTagBacking(barcodeBox);
            if (barcodeBox2) clearTagBacking(barcodeBox2);
            clearMmPrintArea(document.getElementById('labelPreview1'));
            clearMmPrintArea(document.getElementById('labelPreview2'));
            var mmHintClear = document.getElementById('barcodeMmCanvasHint');
            if (mmHintClear) mmHintClear.textContent = '';
            barcodeBox.classList.remove('barcode-label-short');
            if (barcodeStripesEl) {
                barcodeStripesEl.style.width = '';
                barcodeStripesEl.style.minWidth = '';
            }
            if (label2) label2.style.display = 'none';
            if (labelsContainer) labelsContainer.classList.remove('two-labels');
            return;
        }
        var wMm = 100, hMm = 25;
        if (val === 'custom') {
            var cw = document.getElementById('barcodeCustomWidthMm');
            var ch = document.getElementById('barcodeCustomHeightMm');
            wMm = (cw && cw.value !== '') ? parseFloat(cw.value) || 100 : 100;
            hMm = (ch && ch.value !== '') ? parseFloat(ch.value) || 18 : 18;
        } else if (val !== 'zebra-zpl') {
            var parts = val.split('x');
            if (parts.length >= 2) {
                wMm = parseInt(parts[0], 10) || 100;
                hMm = parseInt(parts[1], 10) || 25;
            }
        }
        var displayWMm = wMm > MAX_DISPLAY_WIDTH_MM ? MAX_DISPLAY_WIDTH_MM : wMm;
        var wPx = Math.round(displayWMm * MM_TO_PX);
        var hPx = Math.round(hMm * MM_TO_PX);
        
        var showTwoLabels = false;
        var isShort = (hMm <= 20);
        var stripW = isShort ? 10 : 16;
        var handleW = isShort ? 20 : 32;
        var padH = isShort ? 20 : 36;
        var padV = isShort ? 12 : 28;
        var innerW = padH + stripW + wPx + handleW;
        var innerH = padV + hPx;
        function sizeTagBackingBox(innerEl) {
            if (!innerEl) return;
            innerEl.style.width = innerW + 'px';
            innerEl.style.minWidth = innerW + 'px';
            innerEl.style.height = innerH + 'px';
            innerEl.style.minHeight = innerH + 'px';
            innerEl.classList.add('barcode-tag-backing');
            innerEl.setAttribute('title', 'Grey = tag backing only (not printed). White center = ' + displayWMm + ' × ' + hMm + ' mm label.');
        }
        function applyMmToWhite(whiteEl) {
            if (!whiteEl) return;
            whiteEl.style.width = wPx + 'px';
            whiteEl.style.height = hPx + 'px';
            whiteEl.style.flex = '0 0 auto';
            whiteEl.style.minWidth = wPx + 'px';
            whiteEl.style.minHeight = hPx + 'px';
            whiteEl.classList.add('barcode-print-area-mm');
            whiteEl.setAttribute('title', 'Print area: ' + displayWMm + ' × ' + hMm + ' mm — design inside this white box.');
        }
        sizeTagBackingBox(barcodeBox);
        applyMmToWhite(document.getElementById('labelPreview1'));
        applyMmToWhite(document.getElementById('labelPreview2'));
        var mmHint = document.getElementById('barcodeMmCanvasHint');
        if (mmHint) {
            mmHint.innerHTML = '<strong>White</strong> = <strong>' + displayWMm + ' × ' + hMm + ' mm</strong> printable label (Label Size). <strong>Grey</strong> around it = tag backing only — not part of the mm size.';
        }
        if (isShort) {
            barcodeBox.classList.add('barcode-label-short');
            if (barcodeStripesEl) {
                barcodeStripesEl.style.width = '';
                barcodeStripesEl.style.minWidth = '40px';
            }
        } else {
            barcodeBox.classList.remove('barcode-label-short');
            if (barcodeStripesEl) {
                barcodeStripesEl.style.width = '';
                barcodeStripesEl.style.minWidth = '40px';
            }
        }
        
        if (showTwoLabels && label2 && barcodeBox2) {
            label2.style.display = 'flex';
            if (labelsContainer) labelsContainer.classList.add('two-labels');
            sizeTagBackingBox(barcodeBox2);
            if (isShort) {
                barcodeBox2.classList.add('barcode-label-short');
                if (barcodeStripes2) {
                    barcodeStripes2.style.width = '';
                    barcodeStripes2.style.minWidth = '40px';
                }
            } else {
                barcodeBox2.classList.remove('barcode-label-short');
                if (barcodeStripes2) {
                    barcodeStripes2.style.width = '';
                    barcodeStripes2.style.minWidth = '40px';
                }
            }
            if (typeof positionBarcodeBlocks === 'function') {
                setTimeout(positionBarcodeBlocks, 50);
            }
            var labelPreview2El = document.getElementById('labelPreview2');
            var barcode2El = document.getElementById('barcode2');
            if (labelPreview2El && barcode2El && labelPreview2El.offsetHeight > 0 && !barcode2El.style.top && !barcode2El.style.left) {
                setTimeout(function() {
                    var lp2 = document.getElementById('labelPreview2');
                    var bc2 = document.getElementById('barcode2');
                    if (!lp2 || !bc2 || lp2.offsetHeight <= 0) return;
                    if (bc2.style.top && bc2.style.top !== '') return;
                    var hasSavedBlock2 = false;
                    try {
                        if (typeof savedDesignLayout === 'string' && savedDesignLayout) {
                            var p = JSON.parse(savedDesignLayout);
                            if (p && !Array.isArray(p) && (((p.items2 && p.items2.length) || (p.fields2 && p.fields2.length)) || p.barcode2_top != null)) hasSavedBlock2 = true;
                        }
                    } catch (e) {}
                    if (hasSavedBlock2) return;
                    var w2 = lp2.offsetWidth || 270;
                    var h2 = lp2.offsetHeight || 54;
                    bc2.style.left = Math.max(0, (w2 - (bc2.offsetWidth || 120)) / 2) + 'px';
                    bc2.style.top = Math.max(0, h2 - (bc2.offsetHeight || 30)) + 'px';
                }, 100);
            }
        } else {
            if (label2) label2.style.display = 'none';
            if (labelsContainer) labelsContainer.classList.remove('two-labels');
            if (typeof positionBarcodeBlocks === 'function') {
                setTimeout(positionBarcodeBlocks, 50);
            }
        }
    }

    if (labelSizeSelect) {
        var isCustom = (labelSizeSelect.value || '').trim() === 'custom';
        var wrapW = document.getElementById('barcodeCustomSizeWrap');
        var wrapH = document.getElementById('barcodeCustomHeightWrap');
        if (wrapW) wrapW.style.display = isCustom ? 'flex' : 'none';
        if (wrapH) wrapH.style.display = isCustom ? 'flex' : 'none';
    }

    function getDropLayerRect() {
        return dropLayer.getBoundingClientRect();
    }

    /** Must return labelCanvas1/2 (where .canvas-dropped-item nodes live), not labelPreview — otherwise parent !== drop target and drag logic clones fields. */
    function getLabelContainerAt(x, y) {
        if (!labelPreview1 && !labelPreview2) return labelCanvas1 || labelCanvas2;
        var r1 = labelPreview1 ? labelPreview1.getBoundingClientRect() : null;
        var r2 = labelPreview2 && document.getElementById('barcodeLabel2').style.display !== 'none'
            ? labelPreview2.getBoundingClientRect() : null;
        var in1 = r1 && x >= r1.left && x <= r1.right && y >= r1.top && y <= r1.bottom;
        var in2 = r2 && x >= r2.left && x <= r2.right && y >= r2.top && y <= r2.bottom;
        if (in1 && in2) return (Math.abs(x - (r1.left + r1.width / 2)) <= Math.abs(x - (r2.left + r2.width / 2))) ? labelCanvas1 : labelCanvas2;
        if (in1) return labelCanvas1;
        if (in2) return labelCanvas2;
        if (r1 && r2) {
            var d1 = Math.pow(x - (r1.left + r1.width / 2), 2) + Math.pow(y - (r1.top + r1.height / 2), 2);
            var d2 = Math.pow(x - (r2.left + r2.width / 2), 2) + Math.pow(y - (r2.top + r2.height / 2), 2);
            return d1 <= d2 ? labelCanvas1 : labelCanvas2;
        }
        return labelCanvas1 || labelCanvas2;
    }

    function handleDrop(e, rect, container) {
        e.preventDefault();
        if (!rect || !container) return;
        var left = e.clientX - rect.left;
        var top = e.clientY - rect.top;
        var movingId = e.dataTransfer.getData('application/x-canvas-item');
        var isDropOnCanvas = (container === dropLayer);

        if (labelPreview1 && isDropOnCanvas) {
            container = getLabelContainerAt(e.clientX, e.clientY);
            rect = container.getBoundingClientRect();
            left = e.clientX - rect.left;
            top = e.clientY - rect.top;
            if (left < 0 || top < 0 || left > rect.width || top > rect.height) {
                left = 25;
                top = 20;
            }
        }

        if (movingId) {
            var moving = document.querySelector('.canvas-dropped-item[data-id="' + movingId + '"]');
            if (moving) {
                var oldContainer = moving.parentElement;
                if (oldContainer !== container) {
                    moving.parentNode.removeChild(moving);
                    container.appendChild(moving);
                    /* Only mirror field onto the other label when two blocks are visible (otherwise this created duplicate "Barcode" on every cross-parent drag). */
                    if (typeof isSecondBlockVisible === 'function' && isSecondBlockVisible()) {
                        var isLabel1 = function(c) { return c === labelCanvas1 || c === labelPreview1; };
                        var isLabel2 = function(c) { return c === labelCanvas2 || c === labelPreview2; };
                        if ((isLabel1(oldContainer) || isLabel2(oldContainer)) && (isLabel1(container) || isLabel2(container))) {
                            var fn = moving.getAttribute('data-field');
                            var defLeft = 25, defTop = 40;
                            createDroppedItem(fn, defLeft + 10, defTop + 10, oldContainer, {
                                prefix: moving.getAttribute('data-prefix'),
                                suffix: moving.getAttribute('data-suffix'),
                                font: moving.getAttribute('data-font'),
                                font_size: moving.getAttribute('data-font-size')
                            });
                        }
                    }
                }
                moving.style.left = (left - 10) + 'px';
                moving.style.top = (top - 10) + 'px';
            }
            return;
        }

        var fieldName = e.dataTransfer.getData('text/plain');
        if (fieldName) {
            container.querySelectorAll('.canvas-dropped-item[data-field="' + fieldName + '"]').forEach(function(el, i) {
                if (i > 0) el.remove();
            });
            var existingDrop = container.querySelector('.canvas-dropped-item[data-field="' + fieldName + '"]');
            if (existingDrop) {
                existingDrop.style.left = (left - 10) + 'px';
                existingDrop.style.top = (top - 10) + 'px';
                syncPropertiesPanelFromDroppedItem(existingDrop);
            } else {
                var newEl = createDroppedItem(fieldName, left, top, container);
                syncPropertiesPanelFromDroppedItem(newEl);
                addFieldToOtherBlock(fieldName, container, newEl.getAttribute('data-prefix'), newEl.getAttribute('data-suffix'), newEl.getAttribute('data-font'), newEl.getAttribute('data-font-size'), newEl.getAttribute('data-pad-top'), newEl.getAttribute('data-pad-right'), newEl.getAttribute('data-pad-bottom'), newEl.getAttribute('data-pad-left'));
            }
            syncToolboxHighlight();
        }
    }

    /** Preview sample value for a toolbox field (from PHP sample_field_preview). */
    function resolveSampleFieldValue(fieldName) {
        if (!fieldName || !sampleFieldPreview) return null;
        if (Object.prototype.hasOwnProperty.call(sampleFieldPreview, fieldName)) {
            var v = sampleFieldPreview[fieldName];
            if (v !== null && v !== undefined && String(v) !== '') return String(v);
        }
        return null;
    }
    /**
     * Canvas preview: if Prefix and/or Suffix are set, show only that text (what you typed in Properties).
     * If both are empty, show sample data so you can still preview real values. Printed labels still use renderBarcodeLayout() + DB data.
     */
    function updateDroppedItemDisplay(el) {
        var textEl = el && el.querySelector('.canvas-item-text');
        if (!textEl) return;
        var field = (el.getAttribute('data-field') || '').trim();
        var prefix = (el.getAttribute('data-prefix') || '').trim();
        var suffix = (el.getAttribute('data-suffix') || '').trim();
        if (prefix !== '' || suffix !== '') {
            var parts = [];
            if (prefix !== '') parts.push(prefix);
            if (suffix !== '') parts.push(suffix);
            textEl.textContent = parts.join(' ');
            return;
        }
        var val = resolveSampleFieldValue(field);
        if (val === null) val = field || '';
        textEl.textContent = String(val);
    }
    function applyDroppedItemFont(el) {
        var textEl = el && el.querySelector('.canvas-item-text');
        if (!textEl) return;
        var font = el.getAttribute('data-font') || 'Arial';
        var size = el.getAttribute('data-font-size') || '10';
        textEl.style.fontFamily = font;
        textEl.style.fontSize = (size === '' ? 10 : parseInt(size, 10)) + 'px';
    }

    var labelPreview2 = document.getElementById('labelPreview2');
    
    function syncToolboxHighlight() {
        var onLabelFields = {};
        [labelPreview1, labelPreview2].forEach(function(cont) {
            if (!cont) return;
            cont.querySelectorAll('.canvas-dropped-item').forEach(function(item) {
                var f = item.getAttribute('data-field');
                if (f) onLabelFields[f] = true;
            });
        });
        document.querySelectorAll('.toolbox-field-item').forEach(function(item) {
            var f = item.getAttribute('data-field');
            if (f && onLabelFields[f]) item.classList.add('on-label');
            else item.classList.remove('on-label');
        });
    }
    
    function isSecondBlockVisible() {
        var label2 = document.getElementById('barcodeLabel2');
        return label2 && label2.style.display !== 'none' && labelPreview2;
    }
    
    function addFieldToOtherBlock(fieldName, sourceContainer, prefix, suffix, font, fontSize, padTop, padRight, padBottom, padLeft) {
        if (!isSecondBlockVisible()) return;
        var otherContainer = (sourceContainer === labelCanvas1 || sourceContainer === labelPreview1) ? (labelCanvas2 || labelPreview2) : (labelCanvas1 || labelPreview1);
        if (otherContainer.querySelector('.canvas-dropped-item[data-field="' + fieldName + '"]')) return;
        var defaultLeft = 25;
        var defaultTop = 40;
        createDroppedItem(fieldName, defaultLeft + 10, defaultTop + 10, otherContainer, {
            prefix: prefix,
            suffix: suffix,
            font: font || 'Arial',
            font_size: fontSize || '10',
            pad_top: padTop !== undefined && padTop !== null && padTop !== '' ? padTop : 0,
            pad_right: padRight !== undefined && padRight !== null && padRight !== '' ? padRight : 0,
            pad_bottom: padBottom !== undefined && padBottom !== null && padBottom !== '' ? padBottom : 0,
            pad_left: padLeft !== undefined && padLeft !== null && padLeft !== '' ? padLeft : 0
        });
    }
    
    function removeFieldFromOtherBlock(fieldName, fromContainer) {
        var otherContainer = (fromContainer === labelCanvas1 || fromContainer === labelPreview1) ? (labelCanvas2 || labelPreview2) : (labelCanvas1 || labelPreview1);
        if (!otherContainer) return;
        var other = otherContainer.querySelector('.canvas-dropped-item[data-field="' + fieldName + '"]');
        if (other) other.remove();
    }
    
    function syncToSecondLabel() {
        /* Used for property sync; add/remove sync is done in addFieldToOtherBlock / remove handler */
    }
    
    (function _removedSyncClone() {
        if (!labelPreview2) return;
        var label2 = document.getElementById('barcodeLabel2');
        if (!label2 || label2.style.display === 'none') return;
        var barcodePrintWrap1 = document.getElementById('barcode1');
        var barcodePrintWrap2 = document.getElementById('barcode2');
        if (barcodePrintWrap1 && barcodePrintWrap2) {
            var stripes1 = barcodePrintWrap1.querySelector('.barcode-stripes');
            var stripes2 = barcodePrintWrap2.querySelector('.barcode-stripes');
            if (stripes1 && stripes2) {
                stripes2.style.width = stripes1.style.width;
                stripes2.style.minHeight = stripes1.style.minHeight;
            }
        }
    })();
    
    function createDroppedItem(fieldName, left, top, container, opts) {
        opts = opts || {};
        container = container || labelCanvas1 || labelPreview1;
        var id = 'canvas-item-' + (++dropIdCounter);
        var el = document.createElement('div');
        el.className = 'canvas-dropped-item';
        el.setAttribute('data-field', fieldName);
        el.setAttribute('data-id', id);
        var prefix = opts.prefix !== undefined ? opts.prefix : fieldName;
        var suffix = opts.suffix !== undefined ? opts.suffix : '';
        var font = opts.font !== undefined ? opts.font : (document.getElementById('propFont') && document.getElementById('propFont').value ? document.getElementById('propFont').value : 'Arial');
        var fontSize = opts.font_size !== undefined ? String(opts.font_size) : (document.getElementById('propFontSize') && document.getElementById('propFontSize').value ? document.getElementById('propFontSize').value : '10');
        el.setAttribute('data-prefix', prefix);
        el.setAttribute('data-suffix', suffix);
        el.setAttribute('data-font', font);
        el.setAttribute('data-font-size', fontSize);
        var padTop = opts.pad_top !== undefined ? clampPadPx(opts.pad_top) : 0;
        var padRight = opts.pad_right !== undefined ? clampPadPx(opts.pad_right) : 0;
        var padBottom = opts.pad_bottom !== undefined ? clampPadPx(opts.pad_bottom) : 0;
        var padLeft = opts.pad_left !== undefined ? clampPadPx(opts.pad_left) : 0;
        el.setAttribute('data-pad-top', String(padTop));
        el.setAttribute('data-pad-right', String(padRight));
        el.setAttribute('data-pad-bottom', String(padBottom));
        el.setAttribute('data-pad-left', String(padLeft));
        el.draggable = true;
        var trashSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
        el.innerHTML = '<span class="canvas-item-text">' + (prefix + (suffix ? ' ' + suffix : '')).trim() + '</span><span class="canvas-item-remove" title="Remove">' + trashSvg + '</span>';
        if (opts.restore && (opts.left !== undefined || opts.top !== undefined)) {
            el.style.left = (opts.left !== undefined ? opts.left : (left - 10)) + 'px';
            el.style.top = (opts.top !== undefined ? opts.top : (top - 10)) + 'px';
        } else {
            el.style.left = (left - 10) + 'px';
            el.style.top = (top - 10) + 'px';
        }
        container.appendChild(el);
        updateDroppedItemDisplay(el);
        applyDroppedItemFont(el);

        el.querySelector('.canvas-item-remove').addEventListener('click', function(ev) {
            ev.stopPropagation();
            var fieldName = el.getAttribute('data-field');
            var fromContainer = el.parentElement;
            el.remove();
            removeFieldFromOtherBlock(fieldName, fromContainer);
            syncToolboxHighlight();
        });
        el.addEventListener('click', function(ev) {
            if (ev.target.closest('.canvas-item-remove')) return;
            syncPropertiesPanelFromDroppedItem(el);
        });
        el.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('application/x-canvas-item', id);
            e.dataTransfer.effectAllowed = 'move';
            e.stopPropagation();
        });
        el.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'move';
        });
        el.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var parent = el.parentElement;
            var r = parent && parent.getBoundingClientRect ? parent.getBoundingClientRect() : getDropLayerRect();
            handleDrop(e, r, parent || labelCanvas1 || labelPreview1);
        });
        return el;
    }

    if (whiteDropZone && labelPreview1) {
        whiteDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = (e.dataTransfer.types.indexOf('application/x-canvas-item') >= 0) ? 'move' : 'copy';
            whiteDropZone.classList.add('drag-over');
        });
        whiteDropZone.addEventListener('dragleave', function(e) {
            if (!whiteDropZone.contains(e.relatedTarget)) whiteDropZone.classList.remove('drag-over');
        });
        whiteDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            whiteDropZone.classList.remove('drag-over');
            var rect = (labelCanvas1 || labelPreview1).getBoundingClientRect();
            handleDrop(e, rect, labelCanvas1 || labelPreview1);
        });
    }
    var whiteDropZone2 = document.getElementById('barcodeWhiteDropZone2');
    if (whiteDropZone2 && labelPreview2) {
        whiteDropZone2.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = (e.dataTransfer.types.indexOf('application/x-canvas-item') >= 0) ? 'move' : 'copy';
            whiteDropZone2.classList.add('drag-over');
        });
        whiteDropZone2.addEventListener('dragleave', function(e) {
            if (!whiteDropZone2.contains(e.relatedTarget)) whiteDropZone2.classList.remove('drag-over');
        });
        whiteDropZone2.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            whiteDropZone2.classList.remove('drag-over');
            var rect = (labelCanvas2 || labelPreview2).getBoundingClientRect();
            handleDrop(e, rect, labelCanvas2 || labelPreview2);
        });
    }

    /** Remove duplicate .canvas-dropped-item with same data-field (fixes bad saves / old drag bug). */
    function dedupeCanvasDomFields() {
        [labelCanvas1, labelCanvas2].forEach(function(canvas) {
            if (!canvas) return;
            var seen = {};
            canvas.querySelectorAll('.canvas-dropped-item').forEach(function(el) {
                var f = el.getAttribute('data-field');
                if (!f) return;
                if (seen[f]) el.remove();
                else seen[f] = true;
            });
        });
    }

    /** One text field per column name (saved JSON or items+items2 could list Barcode twice). */
    function dedupeSavedLayoutItems(items) {
        if (!Array.isArray(items)) return items;
        var seen = {};
        return items.filter(function(it) {
            if (!it) return false;
            if (it.type === 'barcode_image') return true;
            var f = it.field;
            if (!f) return true;
            if (seen[f]) return false;
            seen[f] = true;
            return true;
        });
    }
    /** Same rule when saving layout from the canvas DOM. */
    function dedupeDesignLayoutItems(items) {
        if (!items || !items.length) return items;
        var seen = {};
        var out = [];
        for (var i = 0; i < items.length; i++) {
            var it = items[i];
            if (!it) continue;
            if (it.type === 'barcode_image') {
                out.push(it);
                continue;
            }
            if (it.type === 'text' && it.field) {
                if (seen[it.field]) continue;
                seen[it.field] = true;
            }
            out.push(it);
        }
        return out;
    }

    function clampMmGlobal(val, maxVal) {
        return Math.max(0, Math.min(maxVal, Math.round(val * 100) / 100));
    }
    /** Estimated text box size in mm (used only for overlap checks, not for print). */
    function estimateTextBoxMm(lw, lh) {
        var estW = Math.min(50, Math.max(12, lw * 0.45));
        var estH = Math.max(2.5, Math.min(8, lh * 0.5));
        return { w: estW, h: estH };
    }
    /**
     * True if estimated text box overlaps the barcode graphic (not the margin below it).
     * Text starting at or below bB does not overlap — fixes short labels where "just under the bars"
     * was mistaken for overlap and forced to the bottom (clipped by overflow).
     */
    function textOverlapsBarcodeBox(leftMm, topMm, bL, bR, bT, bB, lw, lh) {
        var est = estimateTextBoxMm(lw, lh);
        var horiz = leftMm < bR && (leftMm + est.w) > bL;
        var vert = (topMm + est.h > bT) && (topMm < bB);
        return horiz && vert;
    }
    /**
     * If text overlaps bars, move top to just below barcode — only when it still fits on the label.
     * Otherwise keep the user's position (avoids pushing caption off a 12mm-tall label).
     */
    function resolveTextTopMmBelowBarcodeIfOverlap(leftMm, topMm, bL, bR, bT, bB, labelW, labelH, gapMm) {
        if (bB <= 0 && bT <= 0) return topMm;
        if (!textOverlapsBarcodeBox(leftMm, topMm, bL, bR, bT, bB, labelW, labelH)) return topMm;
        var estH = estimateTextBoxMm(labelW, labelH).h;
        var targetTopMm = bB + gapMm;
        if (targetTopMm + estH <= labelH - 0.25) {
            return clampMmGlobal(targetTopMm, labelH);
        }
        return topMm;
    }
    /** Move dropped field text below barcode when it would draw on top of the bars (preview + after restore). */
    function adjustDroppedTextBelowBarcode(previewEl, canvasEl, labelW, labelH, barcodeId) {
        if (!previewEl || !canvasEl) return;
        var barcodeWrap = document.getElementById(barcodeId);
        if (!barcodeWrap) return;
        var contentW = canvasEl.offsetWidth || 270;
        var contentH = canvasEl.offsetHeight || 54;
        var pxToMmX = labelW / contentW;
        var pxToMmY = labelH / contentH;
        var br = barcodeWrap.getBoundingClientRect();
        var wr = canvasEl.getBoundingClientRect();
        var barcodeLeftMm = clampMmGlobal((br.left - wr.left) * pxToMmX, labelW);
        var barcodeTopMm = clampMmGlobal((br.top - wr.top) * pxToMmY, labelH);
        var stripes = barcodeWrap.querySelector('.barcode-stripes');
        var wPx = stripes ? stripes.offsetWidth : 90;
        var hPx = stripes ? stripes.offsetHeight : 18;
        var barcodeRightMm = barcodeLeftMm + clampMmGlobal(wPx * pxToMmX, labelW);
        var barcodeBottomMm = barcodeTopMm + clampMmGlobal(hPx * pxToMmY, labelH);
        var gapMm = 1.5;
        canvasEl.querySelectorAll('.canvas-dropped-item').forEach(function(item) {
            var left = parseInt(item.style.left, 10);
            var top = parseInt(item.style.top, 10);
            if (isNaN(left)) left = 0;
            if (isNaN(top)) top = 0;
            var leftMm = clampMmGlobal(left * pxToMmX, labelW);
            var topMm = clampMmGlobal(top * pxToMmY, labelH);
            var newTopMm = resolveTextTopMmBelowBarcodeIfOverlap(leftMm, topMm, barcodeLeftMm, barcodeRightMm, barcodeTopMm, barcodeBottomMm, labelW, labelH, gapMm);
            if (Math.abs(newTopMm - topMm) > 0.01) {
                item.style.top = Math.round(newTopMm / pxToMmY) + 'px';
            }
        });
    }

    // Restore saved barcode print design (design_layout: barcode_image + text, all in mm)
    var labelWidthMm = <?php echo json_encode(isset($bs['label_width_mm']) ? (float)$bs['label_width_mm'] : 100); ?>;
    var labelHeightMm = <?php echo json_encode(isset($bs['label_height_mm']) ? (float)$bs['label_height_mm'] : 18); ?>;
    var sampleBarcodeNumber = <?php echo json_encode(trim((string)($sample_data_preview['barcode'] ?? '')) ?: '00002'); ?>;

    /** Offset of el from ancestor (padding edge), or null if ancestor is not in the offsetParent chain. */
    function getElementOffsetInAncestor(el, ancestor) {
        if (!el || !ancestor) return null;
        var l = 0, t = 0, node = el;
        while (node && node !== ancestor) {
            l += node.offsetLeft;
            t += node.offsetTop;
            node = node.offsetParent;
        }
        if (node !== ancestor) return null;
        return { left: l, top: t };
    }

    /**
     * Pin #barcode1 to saved pixel coords. Pass layout object, or omit to parse savedDesignLayout.
     * Uses barcode_left/top when defined (including 0); else legacy barcode_position.
     */
    /** Keep #barcode1/#barcode2 fully inside label canvas (stripes + caption) so nothing paints outside the white label. */
    function clampBarcodeBlockIntoCanvas(canvasEl, barcodeEl) {
        if (!canvasEl || !barcodeEl) return;
        var cw = canvasEl.clientWidth;
        var ch = canvasEl.clientHeight;
        if (cw <= 0 || ch <= 0) return;
        var bw = barcodeEl.offsetWidth;
        var bh = barcodeEl.offsetHeight;
        if (bw <= 0 || bh <= 0) return;
        var l = parseInt(barcodeEl.style.left, 10);
        var t = parseInt(barcodeEl.style.top, 10);
        if (isNaN(l)) l = barcodeEl.offsetLeft;
        if (isNaN(t)) t = barcodeEl.offsetTop;
        var maxL = Math.max(0, cw - bw);
        var maxT = Math.max(0, ch - bh);
        barcodeEl.style.left = Math.max(0, Math.min(maxL, l)) + 'px';
        barcodeEl.style.top = Math.max(0, Math.min(maxT, t)) + 'px';
    }

    function restoreBarcodePosition(layout) {
        var data = layout;
        if (!data) {
            if (!savedDesignLayout || !String(savedDesignLayout).trim()) return;
            try {
                data = JSON.parse(savedDesignLayout);
            } catch (e) {
                return;
            }
        }
        if (!data) return;
        var barcode = document.getElementById('barcode1');
        if (!barcode) return;
        barcode.style.position = 'absolute';
        barcode.style.margin = '0';
        if (data.barcode_left !== undefined && data.barcode_left !== null) {
            barcode.style.left = parseInt(data.barcode_left, 10) + 'px';
        } else if (data.barcode_position && typeof data.barcode_position.left === 'number' && !isNaN(data.barcode_position.left)) {
            barcode.style.left = data.barcode_position.left + 'px';
        }
        if (data.barcode_top !== undefined && data.barcode_top !== null) {
            barcode.style.top = parseInt(data.barcode_top, 10) + 'px';
        } else if (data.barcode_position && typeof data.barcode_position.top === 'number' && !isNaN(data.barcode_position.top)) {
            barcode.style.top = data.barcode_position.top + 'px';
        }
        if (data.barcode_bar_width != null) {
            var pw = document.getElementById('propBarcodeBarWidth');
            if (pw) pw.value = Math.max(1, Math.min(10, parseInt(data.barcode_bar_width, 10) || 2));
        }
        if (data.barcode_bar_height != null) {
            var ph = document.getElementById('propBarcodeBarHeight');
            if (ph) ph.value = Math.max(10, Math.min(200, parseInt(data.barcode_bar_height, 10) || 28));
        }
    }

    function getBarcodeBarOptions() {
        var w = parseInt(document.getElementById('propBarcodeBarWidth') && document.getElementById('propBarcodeBarWidth').value, 10);
        var h = parseInt(document.getElementById('propBarcodeBarHeight') && document.getElementById('propBarcodeBarHeight').value, 10);
        return { width: (isNaN(w) || w < 1) ? 2 : Math.min(10, w), height: (isNaN(h) || h < 10) ? 28 : Math.min(200, h) };
    }
    /** Canvas preview: always reads bar width/height from prop inputs (matches saved design_layout after reload). */
    function renderCanvasBarcode() {
        if (typeof JsBarcode === 'undefined') return;
        var opts = getBarcodeBarOptions();
        var code = String(sampleBarcodeNumber || '00002').trim() || '00002';
        var jsOpts = {
            format: 'CODE128',
            width: opts.width,
            height: opts.height,
            displayValue: false,
            margin: 0,
            marginTop: 0,
            marginBottom: 0,
            marginLeft: 0,
            marginRight: 0,
            background: '#ffffff',
            lineColor: '#000000'
        };
        var stripes1 = labelPreview1 ? labelPreview1.querySelector('.barcode-stripes') : null;
        var stripes2 = labelPreview2 ? labelPreview2.querySelector('.barcode-stripes') : null;
        [stripes1, stripes2].forEach(function(stripesEl, idx) {
            if (!stripesEl) return;
            var wrap = stripesEl.parentElement;
            var canvasEl = idx === 0 ? (document.getElementById('labelCanvas1') || labelPreview1) : (document.getElementById('labelCanvas2') || labelPreview2);
            var cw = (canvasEl && canvasEl.offsetWidth > 0) ? canvasEl.offsetWidth : 270;
            if (wrap && (!wrap.style.width || String(wrap.style.width).trim() === '')) {
                wrap.style.boxSizing = 'border-box';
                wrap.style.width = Math.max(40, Math.round(cw * 0.35)) + 'px';
            }
            stripesEl.innerHTML = '';
            stripesEl.style.display = 'block';
            stripesEl.style.boxSizing = 'border-box';
            stripesEl.style.overflow = 'hidden';
            stripesEl.style.width = '100%';
            var barBoxH = opts.height;
            stripesEl.style.minHeight = barBoxH + 'px';
            stripesEl.style.height = barBoxH + 'px';
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            svg.style.display = 'block';
            stripesEl.appendChild(svg);
            try {
                JsBarcode(svg, code, jsOpts);
            } catch (e) {
                stripesEl.innerHTML = '';
            }
        });
        var bt1 = document.getElementById('barcodeText1');
        var bt2 = document.getElementById('barcodeText2');
        if (bt1) bt1.textContent = code;
        if (bt2) bt2.textContent = code;
    }
    /** Re-render canvas barcodes from saved prop inputs (avoids CSS hardcoded stripe width fighting design_layout). */
    function applySavedBarcodeSize() {
        renderCanvasBarcode();
    }
    function renderBarcode() {
        applySavedBarcodeSize();
    }
    /** After layout restore, show either JsBarcode or QR preview matching the active designer mode (not both). */
    function refreshCodeGraphicAfterLayoutRestore() {
        if (typeof currentCodeType !== 'undefined' && currentCodeType === 'qr') {
            updateBarcodeQrDisplay();
        } else {
            applySavedBarcodeSize();
        }
    }

    function restoreSavedLayout() {
        if (!savedDesignLayout || !labelPreview1) return;
        try {
            var parsed = JSON.parse(savedDesignLayout);
            if (parsed.layout_type != null && String(parsed.layout_type) !== '' && parsed.layout_type !== currentCodeType) {
                return;
            }
            document.querySelectorAll('.canvas-dropped-item').forEach(function(el) {
                el.remove();
            });
            if (parsed.barcode_bar_width != null) {
                var pw = document.getElementById('propBarcodeBarWidth');
                if (pw) pw.value = Math.max(1, Math.min(10, parseInt(parsed.barcode_bar_width, 10) || 2));
            }
            if (parsed.barcode_bar_height != null) {
                var ph = document.getElementById('propBarcodeBarHeight');
                if (ph) ph.value = Math.max(10, Math.min(200, parseInt(parsed.barcode_bar_height, 10) || 28));
            }
            if (parsed.qr_width != null) {
                var qw = document.getElementById('propQrWidth');
                if (qw) qw.value = Math.max(30, Math.min(200, parseInt(parsed.qr_width, 10) || 60));
            }
            if (parsed.qr_height != null) {
                var qh = document.getElementById('propQrHeight');
                if (qh) qh.value = Math.max(30, Math.min(200, parseInt(parsed.qr_height, 10) || 60));
            }
            var arr = Array.isArray(parsed) ? parsed : (parsed.fields || parsed.items || []);
            var arr2 = Array.isArray(parsed) ? [] : (parsed.fields2 || parsed.items2 || []);
            arr = dedupeSavedLayoutItems(arr);
            arr2 = dedupeSavedLayoutItems(arr2);
            var hasPinnedBarcode1Px = (parsed.barcode_left !== undefined && parsed.barcode_left !== null &&
                parsed.barcode_top !== undefined && parsed.barcode_top !== null);
            var hasLegacyBarcodePos = (parsed.barcode_position &&
                typeof parsed.barcode_position.left === 'number' && !isNaN(parsed.barcode_position.left) &&
                typeof parsed.barcode_position.top === 'number' && !isNaN(parsed.barcode_position.top));
            var skipMmBarcode1Position = hasPinnedBarcode1Px || hasLegacyBarcodePos;
            /* Must match save math: px/mm uses #labelCanvas dimensions, not #labelPreview (preview includes padding → wider → drift right each reload). */
            var canvas1Restore = labelCanvas1 || document.getElementById('labelCanvas1');
            var cw = (canvas1Restore && canvas1Restore.offsetWidth > 0) ? canvas1Restore.offsetWidth : (labelPreview1.offsetWidth || 270);
            var ch = (canvas1Restore && canvas1Restore.offsetHeight > 0) ? canvas1Restore.offsetHeight : (labelPreview1.offsetHeight || 54);
            var mmToPxX = cw / (labelWidthMm || 100);
            var mmToPxY = ch / (labelHeightMm || 18);
            var barcodePrintWrap = document.getElementById('barcode1');
            var barcodeStripes = labelPreview1.querySelector('.barcode-stripes');
            if (arr.length > 0) {
                arr.forEach(function(it) {
                    if (!it) return;
                    if (it.type === 'barcode_image') {
                        if (barcodePrintWrap) {
                            barcodePrintWrap.style.position = 'absolute';
                            barcodePrintWrap.style.margin = '0';
                            var wMm = typeof it.width === 'number' ? it.width : (parseFloat(it.width) || 33);
                            var hMm = typeof it.height === 'number' ? it.height : (parseFloat(it.height) || 6);
                            if (!skipMmBarcode1Position) {
                                var leftMm = typeof it.left === 'number' ? it.left : (parseFloat(it.left) || 0);
                                var topMm = typeof it.top === 'number' ? it.top : (parseFloat(it.top) || 0);
                                barcodePrintWrap.style.left = Math.round(leftMm * mmToPxX) + 'px';
                                barcodePrintWrap.style.top = Math.round(topMm * mmToPxY) + 'px';
                            }
                            if (barcodePrintWrap && barcodeStripes) {
                                var wPx = Math.round(wMm * mmToPxX);
                                var hPx = Math.round(hMm * mmToPxY);
                                barcodePrintWrap.style.boxSizing = 'border-box';
                                barcodePrintWrap.style.width = wPx + 'px';
                                barcodePrintWrap.style.overflow = 'visible';
                                barcodeStripes.style.width = '100%';
                                barcodeStripes.style.height = hPx + 'px';
                                barcodeStripes.style.minHeight = hPx + 'px';
                                barcodeStripes.style.overflow = 'hidden';
                            }
                        }
                        return;
                    }
                    if (it.field) {
                        var leftMm = typeof it.left === 'number' ? it.left : (parseFloat(it.left) || 0);
                        var topMm = typeof it.top === 'number' ? it.top : (parseFloat(it.top) || 0);
                        var leftPx = (it.type === 'text' || leftMm <= 200) ? Math.round(leftMm * mmToPxX) : (parseInt(it.left, 10) || 15);
                        var topPx = (it.type === 'text' || topMm <= 100) ? Math.round(topMm * mmToPxY) : (parseInt(it.top, 10) || 20);
                        createDroppedItem(it.field, leftPx, topPx, labelCanvas1 || labelPreview1, {
                            prefix: it.prefix !== undefined ? it.prefix : it.field,
                            suffix: it.suffix !== undefined ? it.suffix : '',
                            font: it.font !== undefined ? it.font : 'Arial',
                            font_size: it.font_size !== undefined ? it.font_size : 10,
                            left: leftPx,
                            top: topPx,
                            restore: true,
                            pad_top: it.pad_top !== undefined ? it.pad_top : 0,
                            pad_right: it.pad_right !== undefined ? it.pad_right : 0,
                            pad_bottom: it.pad_bottom !== undefined ? it.pad_bottom : 0,
                            pad_left: it.pad_left !== undefined ? it.pad_left : 0
                        });
                    }
                });
            }
            if (!skipMmBarcode1Position && !Array.isArray(parsed) && parsed.barcode1_top != null && parsed.barcode1_left != null && barcodePrintWrap) {
                barcodePrintWrap.style.left = Math.round(parseFloat(parsed.barcode1_left) * mmToPxX) + 'px';
                barcodePrintWrap.style.top = Math.round(parseFloat(parsed.barcode1_top) * mmToPxY) + 'px';
            }
            if (labelPreview2 && arr2.length > 0) {
                var canvas2Restore = labelCanvas2 || document.getElementById('labelCanvas2');
                var cw2 = (canvas2Restore && canvas2Restore.offsetWidth > 0) ? canvas2Restore.offsetWidth : (labelPreview2.offsetWidth || 270);
                var ch2 = (canvas2Restore && canvas2Restore.offsetHeight > 0) ? canvas2Restore.offsetHeight : (labelPreview2.offsetHeight || 54);
                var mmToPxX2 = cw2 / (labelWidthMm || 100);
                var mmToPxY2 = ch2 / (labelHeightMm || 18);
                var barcodePrintWrap2 = document.getElementById('barcode2');
                var barcodeStripes2 = labelPreview2.querySelector('.barcode-stripes');
                arr2.forEach(function(it) {
                    if (!it) return;
                    if (it.type === 'barcode_image') {
                        if (barcodePrintWrap2) {
                            barcodePrintWrap2.style.position = 'absolute';
                            barcodePrintWrap2.style.margin = '0';
                            var leftMm = typeof it.left === 'number' ? it.left : (parseFloat(it.left) || 0);
                            var topMm = typeof it.top === 'number' ? it.top : (parseFloat(it.top) || 0);
                            var wMm = typeof it.width === 'number' ? it.width : (parseFloat(it.width) || 33);
                            var hMm = typeof it.height === 'number' ? it.height : (parseFloat(it.height) || 6);
                            barcodePrintWrap2.style.left = Math.round(leftMm * mmToPxX2) + 'px';
                            barcodePrintWrap2.style.top = Math.round(topMm * mmToPxY2) + 'px';
                            if (barcodePrintWrap2 && barcodeStripes2) {
                                var wPx2 = Math.round(wMm * mmToPxX2);
                                var hPx2 = Math.round(hMm * mmToPxY2);
                                barcodePrintWrap2.style.boxSizing = 'border-box';
                                barcodePrintWrap2.style.width = wPx2 + 'px';
                                barcodePrintWrap2.style.overflow = 'visible';
                                barcodeStripes2.style.width = '100%';
                                barcodeStripes2.style.height = hPx2 + 'px';
                                barcodeStripes2.style.minHeight = hPx2 + 'px';
                                barcodeStripes2.style.overflow = 'hidden';
                            }
                        }
                        return;
                    }
                    if (it.field) {
                        var leftMm = typeof it.left === 'number' ? it.left : (parseFloat(it.left) || 0);
                        var topMm = typeof it.top === 'number' ? it.top : (parseFloat(it.top) || 0);
                        var leftPx = Math.round(leftMm * mmToPxX2);
                        var topPx = Math.round(topMm * mmToPxY2);
                        createDroppedItem(it.field, leftPx, topPx, labelCanvas2 || labelPreview2, {
                            prefix: it.prefix !== undefined ? it.prefix : it.field,
                            suffix: it.suffix !== undefined ? it.suffix : '',
                            font: it.font !== undefined ? it.font : 'Arial',
                            font_size: it.font_size !== undefined ? it.font_size : 10,
                            left: leftPx,
                            top: topPx,
                            restore: true,
                            pad_top: it.pad_top !== undefined ? it.pad_top : 0,
                            pad_right: it.pad_right !== undefined ? it.pad_right : 0,
                            pad_bottom: it.pad_bottom !== undefined ? it.pad_bottom : 0,
                            pad_left: it.pad_left !== undefined ? it.pad_left : 0
                        });
                    }
                });
                if (parsed.barcode2_top != null && parsed.barcode2_left != null && barcodePrintWrap2) {
                    barcodePrintWrap2.style.left = Math.round(parseFloat(parsed.barcode2_left) * mmToPxX2) + 'px';
                    barcodePrintWrap2.style.top = Math.round(parseFloat(parsed.barcode2_top) * mmToPxY2) + 'px';
                }
            }
            if (!Array.isArray(parsed) && parsed.barcode2_top != null && parsed.barcode2_left != null && labelPreview2) {
                var parsedRef = parsed;
                var labelWidthMmRef = labelWidthMm;
                var labelHeightMmRef = labelHeightMm;
                setTimeout(function() {
                    var lp2 = document.getElementById('labelPreview2');
                    var c2 = document.getElementById('labelCanvas2');
                    var bc2 = document.getElementById('barcode2');
                    if (!lp2 || !bc2 || lp2.offsetHeight <= 0) return;
                    var cwLate = (c2 && c2.offsetWidth > 0) ? c2.offsetWidth : (lp2.offsetWidth || 270);
                    var chLate = (c2 && c2.offsetHeight > 0) ? c2.offsetHeight : (lp2.offsetHeight || 54);
                    var mmToPxX2 = cwLate / (labelWidthMmRef || 100);
                    var mmToPxY2 = chLate / (labelHeightMmRef || 18);
                    bc2.style.left = Math.round(parseFloat(parsedRef.barcode2_left) * mmToPxX2) + 'px';
                    bc2.style.top = Math.round(parseFloat(parsedRef.barcode2_top) * mmToPxY2) + 'px';
                }, 150);
            }
            syncToolboxHighlight();
            dedupeCanvasDomFields();
            var rootAfterRestore = labelCanvas1 || labelPreview1;
            syncPropertiesPanelAfterLayoutRestore(rootAfterRestore);
            restoreBarcodePosition(parsed);
            refreshCodeGraphicAfterLayoutRestore();
            restoreBarcodePosition(parsed);
            clampBarcodeBlockIntoCanvas(labelCanvas1, document.getElementById('barcode1'));
            clampBarcodeBlockIntoCanvas(labelCanvas2, document.getElementById('barcode2'));
            setTimeout(function() {
                refreshCodeGraphicAfterLayoutRestore();
                setTimeout(function() {
                    restoreBarcodePosition(parsed);
                    clampBarcodeBlockIntoCanvas(labelCanvas1, document.getElementById('barcode1'));
                    clampBarcodeBlockIntoCanvas(labelCanvas2, document.getElementById('barcode2'));
                    if (labelPreview1 && labelCanvas1) adjustDroppedTextBelowBarcode(labelPreview1, labelCanvas1, labelWidthMm, labelHeightMm, 'barcode1');
                    if (labelPreview2 && labelCanvas2) adjustDroppedTextBelowBarcode(labelPreview2, labelCanvas2, labelWidthMm, labelHeightMm, 'barcode2');
                }, 60);
            }, 80);
        } catch (e) { console.warn('Could not restore barcode design', e); }
    }

    /** Apply physical label box size first so mm→px restore uses correct preview dimensions; restore after layout. */
    function initBarcodeDesignerLayoutFromSaved() {
        updatePropertiesPanelForCodeType();
        applyLabelSizeToBox();
        setTimeout(function() {
            restoreSavedLayout();
        }, 50);
    }
    function scheduleBarcodeRenderAfterDomReady() {
        function run() {
            setTimeout(function() {
                refreshCodeGraphicAfterLayoutRestore();
                restoreBarcodePosition();
                clampBarcodeBlockIntoCanvas(labelCanvas1, document.getElementById('barcode1'));
                clampBarcodeBlockIntoCanvas(labelCanvas2, document.getElementById('barcode2'));
            }, 100);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBarcodeDesignerLayoutFromSaved);
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                restoreBarcodePosition();
                refreshCodeGraphicAfterLayoutRestore();
                clampBarcodeBlockIntoCanvas(labelCanvas1, document.getElementById('barcode1'));
                clampBarcodeBlockIntoCanvas(labelCanvas2, document.getElementById('barcode2'));
            }, 150);
        });
    } else {
        initBarcodeDesignerLayoutFromSaved();
        setTimeout(function() {
            restoreBarcodePosition();
            refreshCodeGraphicAfterLayoutRestore();
            clampBarcodeBlockIntoCanvas(labelCanvas1, document.getElementById('barcode1'));
            clampBarcodeBlockIntoCanvas(labelCanvas2, document.getElementById('barcode2'));
        }, 150);
    }
    scheduleBarcodeRenderAfterDomReady();

    var barcode1El = document.getElementById('barcode1');
    var barcode2El = document.getElementById('barcode2');
    if (labelCanvas1 && barcode1El) {
        barcode1El.style.position = 'absolute';
        barcode1El.style.margin = '0';
    }
    if (labelCanvas2 && barcode2El) {
        barcode2El.style.position = 'absolute';
        barcode2El.style.margin = '0';
    }

    function toggleBarcodeNumber() {
        var chk = document.getElementById('barcodeShowBarcodeNo');
        if (!chk) return;
        var show = chk.checked;
        document.querySelectorAll('.barcode-text').forEach(function(el) {
            el.style.display = show ? 'block' : 'none';
        });
    }
    var barcodeShowBarcodeNoEl = document.getElementById('barcodeShowBarcodeNo');
    if (barcodeShowBarcodeNoEl) {
        barcodeShowBarcodeNoEl.addEventListener('change', toggleBarcodeNumber);
    }
    toggleBarcodeNumber();

    (function initBarcode1Drag() {
        var barcodeBox = barcode1El;
        var canvas = labelCanvas1;
        if (!barcodeBox || !canvas) return;
        var isDragging = false, offsetX, offsetY;
        barcodeBox.addEventListener('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            isDragging = true;
            offsetX = e.offsetX;
            offsetY = e.offsetY;
        });
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var rect = canvas.getBoundingClientRect();
            var w = barcodeBox.offsetWidth || 120;
            var h = barcodeBox.offsetHeight || 30;
            var left = e.clientX - rect.left - offsetX;
            var top = e.clientY - rect.top - offsetY;
            var maxLeft = rect.width - w;
            var maxTop = rect.height - h;
            left = maxLeft < 0 ? (rect.width - w) / 2 : Math.max(0, Math.min(maxLeft, left));
            top = maxTop < 0 ? (rect.height - h) / 2 : Math.max(0, Math.min(maxTop, top));
            barcodeBox.style.left = Math.round(left) + 'px';
            barcodeBox.style.top = Math.round(top) + 'px';
        });
        document.addEventListener('mouseup', function() {
            if (!isDragging) return;
            isDragging = false;
            clampBarcodeBlockIntoCanvas(canvas, barcodeBox);
        });
    })();
    (function initBarcode2Drag() {
        var barcodeBox = barcode2El;
        var canvas = labelCanvas2;
        if (!barcodeBox || !canvas) return;
        var isDragging = false, offsetX, offsetY;
        barcodeBox.addEventListener('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            isDragging = true;
            offsetX = e.offsetX;
            offsetY = e.offsetY;
        });
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var rect = canvas.getBoundingClientRect();
            var w = barcodeBox.offsetWidth || 120;
            var h = barcodeBox.offsetHeight || 30;
            var left = e.clientX - rect.left - offsetX;
            var top = e.clientY - rect.top - offsetY;
            var maxLeft = rect.width - w;
            var maxTop = rect.height - h;
            left = maxLeft < 0 ? (rect.width - w) / 2 : Math.max(0, Math.min(maxLeft, left));
            top = maxTop < 0 ? (rect.height - h) / 2 : Math.max(0, Math.min(maxTop, top));
            barcodeBox.style.left = Math.round(left) + 'px';
            barcodeBox.style.top = Math.round(top) + 'px';
        });
        document.addEventListener('mouseup', function() {
            if (!isDragging) return;
            isDragging = false;
            clampBarcodeBlockIntoCanvas(canvas, barcodeBox);
        });
    })();
    function centerBarcode(barcodeBox, canvas) {
        if (!barcodeBox || !canvas) return;
        barcodeBox.style.left = Math.max(0, (canvas.offsetWidth - (barcodeBox.offsetWidth || 120)) / 2) + 'px';
        barcodeBox.style.top = Math.max(0, (canvas.offsetHeight - (barcodeBox.offsetHeight || 30)) / 2) + 'px';
    }

    if (whiteDropZone) {
        document.addEventListener('dragstart', function() { whiteDropZone.classList.add('dragging-active'); });
        document.addEventListener('dragend', function() { whiteDropZone.classList.remove('dragging-active'); });
        document.addEventListener('drop', function() { whiteDropZone.classList.remove('dragging-active'); });
    }
    if (whiteDropZone2) {
        document.addEventListener('dragstart', function() { whiteDropZone2.classList.add('dragging-active'); });
        document.addEventListener('dragend', function() { whiteDropZone2.classList.remove('dragging-active'); });
        document.addEventListener('drop', function() { whiteDropZone2.classList.remove('dragging-active'); });
    }

    dropLayer.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = (e.dataTransfer.types.indexOf('application/x-canvas-item') >= 0) ? 'move' : 'copy';
        dropLayer.classList.add('drag-over');
    });
    dropLayer.addEventListener('dragleave', function(e) {
        if (!dropLayer.contains(e.relatedTarget)) dropLayer.classList.remove('drag-over');
    });
    dropLayer.addEventListener('drop', function(e) {
        e.preventDefault();
        var rect = getDropLayerRect();
        handleDrop(e, rect, dropLayer);
    });

    canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = (e.dataTransfer.types.indexOf('application/x-canvas-item') >= 0) ? 'move' : 'copy';
    }, true);
    canvas.addEventListener('drop', function(e) {
        if (e.target.closest('#barcodeWhiteDropZone') || e.target.closest('#barcodeWhiteDropZone2') || e.target.closest('.canvas-dropped-item')) return;
        e.preventDefault();
        handleDrop(e, getDropLayerRect(), dropLayer);
    }, true);

    canvas.addEventListener('click', function(e) {
        if (!e.target.closest('.canvas-dropped-item')) {
            document.querySelectorAll('.canvas-dropped-item').forEach(function(i) { i.classList.remove('selected'); });
            document.querySelectorAll('.toolbox-field-item').forEach(function(i) { i.classList.remove('selected'); });
            var prop = document.getElementById('propPrefix');
            if (prop) prop.value = '';
            var suf = document.getElementById('propSuffix');
            if (suf) suf.value = '';
            clearPaddingInputs();
        }
    });

    // When Prefix, Suffix, Font or Font Size changes in Properties, update the selected item in barcode preview
    (function() {
        var propPrefix = document.getElementById('propPrefix');
        var propSuffix = document.getElementById('propSuffix');
        var propFont = document.getElementById('propFont');
        var propFontSize = document.getElementById('propFontSize');
        function syncPropsToSelected() {
            var sel = document.querySelector('.canvas-dropped-item.selected');
            if (!sel) return;
            sel.setAttribute('data-prefix', propPrefix ? propPrefix.value : '');
            sel.setAttribute('data-suffix', propSuffix ? propSuffix.value : '');
            updateDroppedItemDisplay(sel);
        }
        function syncFontToSelected() {
            var sel = document.querySelector('.canvas-dropped-item.selected');
            if (!sel) return;
            sel.setAttribute('data-font', propFont ? propFont.value : 'Arial');
            sel.setAttribute('data-font-size', propFontSize ? propFontSize.value : '10');
            applyDroppedItemFont(sel);
            syncToSecondLabel();
        }
        if (propPrefix) { 
            propPrefix.addEventListener('input', function() { syncPropsToSelected(); syncToSecondLabel(); }); 
            propPrefix.addEventListener('change', function() { syncPropsToSelected(); syncToSecondLabel(); }); 
        }
        if (propSuffix) { 
            propSuffix.addEventListener('input', function() { syncPropsToSelected(); syncToSecondLabel(); }); 
            propSuffix.addEventListener('change', function() { syncPropsToSelected(); syncToSecondLabel(); }); 
        }
        if (propFont) { propFont.addEventListener('change', syncFontToSelected); }
        if (propFontSize) { propFontSize.addEventListener('input', syncFontToSelected); propFontSize.addEventListener('change', syncFontToSelected); }
    })();

    (function() {
        var padMap = [['propPadTop', 'data-pad-top'], ['propPadRight', 'data-pad-right'], ['propPadBottom', 'data-pad-bottom'], ['propPadLeft', 'data-pad-left']];
        function syncPaddingToSelected() {
            var sel = document.querySelector('.canvas-dropped-item.selected');
            if (!sel) return;
            padMap.forEach(function(pair) {
                var inp = document.getElementById(pair[0]);
                if (inp) sel.setAttribute(pair[1], String(clampPadPx(inp.value)));
            });
            syncToSecondLabel();
        }
        padMap.forEach(function(pair) {
            var inp = document.getElementById(pair[0]);
            if (inp) {
                inp.addEventListener('input', syncPaddingToSelected);
                inp.addEventListener('change', syncPaddingToSelected);
            }
        });
    })();

    // Move selected item: buttons + arrow keys
    var MOVE_STEP = 8;
    function moveSelected(dx, dy) {
        var sel = document.querySelector('.canvas-dropped-item.selected');
        if (!sel) return;
        var l = parseInt(sel.style.left, 10) || 0;
        var t = parseInt(sel.style.top, 10) || 0;
        sel.style.left = (l + dx) + 'px';
        sel.style.top = (t + dy) + 'px';
        syncToSecondLabel();
    }
    document.getElementById('btnMoveUp').addEventListener('click', function() { moveSelected(0, -MOVE_STEP); });
    document.getElementById('btnMoveDown').addEventListener('click', function() { moveSelected(0, MOVE_STEP); });
    document.getElementById('btnMoveLeft').addEventListener('click', function() { moveSelected(-MOVE_STEP, 0); });
    document.getElementById('btnMoveRight').addEventListener('click', function() { moveSelected(MOVE_STEP, 0); });
    document.addEventListener('keydown', function(e) {
        var sel = document.querySelector('.canvas-dropped-item.selected');
        if (!sel) return;
        if (e.key === 'ArrowUp') { e.preventDefault(); moveSelected(0, -MOVE_STEP); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); moveSelected(0, MOVE_STEP); }
        else if (e.key === 'ArrowLeft') { e.preventDefault(); moveSelected(-MOVE_STEP, 0); }
        else if (e.key === 'ArrowRight') { e.preventDefault(); moveSelected(MOVE_STEP, 0); }
    });

    // Barcode blocks: both draggable independently (mouse drag)
    var barcodeBlock1 = document.getElementById('barcodeLabel1');
    var barcodeBlock2 = document.getElementById('barcodeLabel2');
    var canvasRect = canvas.getBoundingClientRect();
    
    function positionBarcodeBlocks() {
        var r = canvas.getBoundingClientRect();
        var label2 = document.getElementById('barcodeLabel2');
        var showTwo = label2 && label2.style.display !== 'none';
        
        if (barcodeBlock1) {
            var w1 = barcodeBlock1.offsetWidth;
            var h1 = barcodeBlock1.offsetHeight;
            if (showTwo) {
                barcodeBlock1.style.left = (r.width / 4 - w1 / 2) + 'px';
            } else {
                barcodeBlock1.style.left = (r.width / 2 - w1 / 2) + 'px';
            }
            barcodeBlock1.style.top = (r.height / 2 - h1 / 2) + 'px';
        }
        
        if (barcodeBlock2 && showTwo) {
            var w2 = barcodeBlock2.offsetWidth;
            var h2 = barcodeBlock2.offsetHeight;
            barcodeBlock2.style.left = (r.width * 3 / 4 - w2 / 2) + 'px';
            barcodeBlock2.style.top = (r.height / 2 - h2 / 2) + 'px';
        }
    }
    setTimeout(positionBarcodeBlocks, 50);
    window.addEventListener('resize', positionBarcodeBlocks);
    
    function makeBarcodeBlockDraggable(block) {
        if (!block) return;
        var dragging = false, startX, startY, startLeft, startTop;
        
        block.addEventListener('mousedown', function(e) {
            if (e.target.closest('.canvas-dropped-item')) return;
            if (e.target.closest('.barcode-print-wrap')) return;
            dragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseInt(block.style.left, 10) || 0;
            startTop = parseInt(block.style.top, 10) || 0;
            block.style.zIndex = '100';
        });
        
        document.addEventListener('mousemove', function(e) {
            if (!dragging) return;
            block.style.left = (startLeft + (e.clientX - startX)) + 'px';
            block.style.top = (startTop + (e.clientY - startY)) + 'px';
        });
        
        document.addEventListener('mouseup', function() { 
            if (dragging) {
                dragging = false;
                block.style.zIndex = '';
            }
        });
    }
    
    makeBarcodeBlockDraggable(barcodeBlock1);
    makeBarcodeBlockDraggable(barcodeBlock2);

    // Barcode size: +/− change stripe width and Bar height (JsBarcode line height) — both affect visible size
    var barcodeStripesEl = document.querySelector('.barcode-default-inner .barcode-stripes');
    var BARCODE_SIZE_MIN = 80, BARCODE_SIZE_MAX = 400, BARCODE_SIZE_STEP = 12;
    var BARCODE_HEIGHT_STEP = 8;
    function getBarcodeStripesWidth() {
        if (!barcodeStripesEl) return 120;
        var w = parseInt(barcodeStripesEl.style.width, 10);
        if (!w) w = 120;
        return w;
    }
    function setBarcodeStripesWidth(w) {
        w = Math.max(BARCODE_SIZE_MIN, Math.min(BARCODE_SIZE_MAX, w));
        document.querySelectorAll('.barcode-default-inner .barcode-stripes').forEach(function(el) {
            if (el) el.style.width = w + 'px';
        });
    }
    function bumpPropBarcodeBarHeight(delta) {
        var ph = document.getElementById('propBarcodeBarHeight');
        if (!ph) return;
        var v = parseInt(ph.value, 10);
        if (isNaN(v) || v < 10) v = 28;
        ph.value = String(Math.max(10, Math.min(200, v + delta)));
    }
    function bumpPropBarcodeBarWidth(delta) {
        var pw = document.getElementById('propBarcodeBarWidth');
        if (!pw) return;
        var v = parseInt(pw.value, 10);
        if (isNaN(v) || v < 1) v = 2;
        pw.value = String(Math.max(1, Math.min(10, v + delta)));
    }
    document.getElementById('btnBarcodeDecrease').addEventListener('click', function() {
        setBarcodeStripesWidth(getBarcodeStripesWidth() - BARCODE_SIZE_STEP);
        bumpPropBarcodeBarHeight(-BARCODE_HEIGHT_STEP);
        bumpPropBarcodeBarWidth(-1);
        onBarSizeChange();
    });
    document.getElementById('btnBarcodeIncrease').addEventListener('click', function() {
        setBarcodeStripesWidth(getBarcodeStripesWidth() + BARCODE_SIZE_STEP);
        bumpPropBarcodeBarHeight(BARCODE_HEIGHT_STEP);
        bumpPropBarcodeBarWidth(1);
        onBarSizeChange();
    });
    var btnBarcodeCenter = document.getElementById('btnBarcodeCenter');
    if (btnBarcodeCenter && typeof centerBarcode === 'function') {
        btnBarcodeCenter.addEventListener('click', function() {
            centerBarcode(barcode1El, labelCanvas1);
            centerBarcode(barcode2El, labelCanvas2);
            clampBarcodeBlockIntoCanvas(labelCanvas1, barcode1El);
            clampBarcodeBlockIntoCanvas(labelCanvas2, barcode2El);
            syncToSecondLabel();
        });
    }
    var propBarWidth = document.getElementById('propBarcodeBarWidth');
    var propBarHeight = document.getElementById('propBarcodeBarHeight');
    function onBarSizeChange() {
        renderBarcode();
        clampBarcodeBlockIntoCanvas(labelCanvas1, barcode1El);
        clampBarcodeBlockIntoCanvas(labelCanvas2, barcode2El);
        syncToSecondLabel();
    }
    if (propBarWidth) {
        propBarWidth.addEventListener('change', onBarSizeChange);
        propBarWidth.addEventListener('input', onBarSizeChange);
    }
    if (propBarHeight) {
        propBarHeight.addEventListener('change', onBarSizeChange);
        propBarHeight.addEventListener('input', onBarSizeChange);
    }
    var propQrWidth = document.getElementById('propQrWidth');
    var propQrHeight = document.getElementById('propQrHeight');
    function onQrSizeChange() { updateBarcodeQrDisplay(); }
    if (propQrWidth) {
        propQrWidth.addEventListener('change', onQrSizeChange);
        propQrWidth.addEventListener('input', onQrSizeChange);
    }
    if (propQrHeight) {
        propQrHeight.addEventListener('change', onQrSizeChange);
        propQrHeight.addEventListener('input', onQrSizeChange);
    }

    // Label size change: show/hide custom inputs and resize box (applyLabelSizeToBox defined earlier, before restore)
    if (labelSizeSelect) {
        labelSizeSelect.addEventListener('change', function() {
            var isCustom = (labelSizeSelect.value || '') === 'custom';
            var wrapW = document.getElementById('barcodeCustomSizeWrap');
            var wrapH = document.getElementById('barcodeCustomHeightWrap');
            if (wrapW) wrapW.style.display = isCustom ? 'flex' : 'none';
            if (wrapH) wrapH.style.display = isCustom ? 'flex' : 'none';
            applyLabelSizeToBox();
        });
    }
    var customW = document.getElementById('barcodeCustomWidthMm');
    var customH = document.getElementById('barcodeCustomHeightMm');
    if (customW) customW.addEventListener('change', applyLabelSizeToBox);
    if (customH) customH.addEventListener('change', applyLabelSizeToBox);

    function flushCheckboxDomToPersisted() {
        var pn = document.getElementById('barcodeShowProductName');
        var pr = document.getElementById('barcodeShowPrice');
        var bn = document.getElementById('barcodeShowBarcodeNo');
        if (!pn || !pr || !bn) return;
        var vPn = pn.checked ? 1 : 0;
        var vPr = pr.checked ? 1 : 0;
        var vBn = bn.checked ? 1 : 0;
        if (currentCodeType === 'barcode') {
            persistedShowProductNameBarcode = vPn;
            persistedShowPriceBarcode = vPr;
            persistedShowBarcodeNoBarcode = vBn;
        } else {
            persistedShowProductNameQr = vPn;
            persistedShowPriceQr = vPr;
            persistedShowBarcodeNoQr = vBn;
        }
    }
    function applyCheckboxPersistToDom() {
        var pn = document.getElementById('barcodeShowProductName');
        var pr = document.getElementById('barcodeShowPrice');
        var bn = document.getElementById('barcodeShowBarcodeNo');
        if (!pn || !pr || !bn) return;
        if (currentCodeType === 'barcode') {
            pn.checked = !!persistedShowProductNameBarcode;
            pr.checked = !!persistedShowPriceBarcode;
            bn.checked = !!persistedShowBarcodeNoBarcode;
        } else {
            pn.checked = !!persistedShowProductNameQr;
            pr.checked = !!persistedShowPriceQr;
            bn.checked = !!persistedShowBarcodeNoQr;
        }
    }

    function buildBarcodeFormPayload() {
        flushCheckboxDomToPersisted();
        var labelSize = (document.getElementById('barcodeLabelSize').value || '100x18').trim();
        var metalSelect = document.getElementById('barcodeMetalType');
        var payload = {
            label_size_preset: labelSize,
            label_width_mm: labelSize === 'custom' ? (parseFloat(document.getElementById('barcodeCustomWidthMm').value) || 100) : (labelSize.split('x')[0] || 100),
            label_height_mm: labelSize === 'custom' ? (parseFloat(document.getElementById('barcodeCustomHeightMm').value) || 18) : (labelSize.split('x')[1] || 18),
            font_size: parseInt(document.getElementById('barcodeFontSize').value, 10) || 12,
            show_product_name_barcode: persistedShowProductNameBarcode,
            show_product_name_qr: persistedShowProductNameQr,
            show_price_barcode: persistedShowPriceBarcode,
            show_price_qr: persistedShowPriceQr,
            show_barcode_number_barcode: persistedShowBarcodeNoBarcode,
            show_barcode_number_qr: persistedShowBarcodeNoQr,
            print_copies: parseInt(document.getElementById('barcodeCopies').value, 10) || 1,
            metal_type: metalSelect ? (metalSelect.value || '') : ''
        };
        if (labelSize && labelSize !== 'custom') {
            var p = labelSize.split('x');
            if (p.length >= 2) {
                payload.label_width_mm = parseFloat(p[0]) || 100;
                payload.label_height_mm = parseFloat(p[1]) || 18;
            }
        }
        return payload;
    }

    function buildBarcodeLayoutPayloadObject(payload) {
        var designItems = [];
        var designItems2 = [];
        var labelPreview1El = document.getElementById('labelPreview1');
        var labelPreview2El = document.getElementById('labelPreview2');
        var labelCanvas1El = document.getElementById('labelCanvas1');
        var labelCanvas2El = document.getElementById('labelCanvas2');
        var labelW = payload.label_width_mm || 100;
        var labelH = payload.label_height_mm || 18;
        var contentW1 = (labelCanvas1El || labelPreview1El) ? ((labelCanvas1El || labelPreview1El).offsetWidth || 270) : 270;
        var contentH1 = (labelCanvas1El || labelPreview1El) ? ((labelCanvas1El || labelPreview1El).offsetHeight || 54) : 54;
        var pxToMmX1 = labelW / contentW1;
        var pxToMmY1 = labelH / contentH1;
        var barcodeLeftMm = 0, barcodeRightMm = 0, barcodeTopMm = 0, barcodeBottomMm = 0;
        var barcode1TopMm = 0, barcode1LeftMm = 0, barcode2TopMm = 0, barcode2LeftMm = 0;
        if (labelPreview1El) {
            var barcodeWrap1 = document.getElementById('barcode1');
            var barcodeStripes1 = labelPreview1El.querySelector('.barcode-stripes');
            var rectRef1 = labelCanvas1El || labelPreview1El;
            if (barcodeWrap1 && barcodeStripes1 && rectRef1) {
                var posCanvas1 = labelCanvas1El || rectRef1;
                var off1 = getElementOffsetInAncestor(barcodeWrap1, posCanvas1);
                var leftPx1 = off1 ? off1.left : (function() {
                    var br = barcodeWrap1.getBoundingClientRect();
                    var wr = rectRef1.getBoundingClientRect();
                    return br.left - wr.left;
                })();
                var topPx1 = off1 ? off1.top : (function() {
                    var br = barcodeWrap1.getBoundingClientRect();
                    var wr = rectRef1.getBoundingClientRect();
                    return br.top - wr.top;
                })();
                barcode1LeftMm = clampMmGlobal(leftPx1 * pxToMmX1, labelW);
                barcode1TopMm = clampMmGlobal(topPx1 * pxToMmY1, labelH);
                var wPx1, hPx1;
                if (typeof currentCodeType !== 'undefined' && currentCodeType === 'qr' && barcodeWrap1) {
                    var qrEl1 = barcodeWrap1.querySelector('.qr-code-preview');
                    if (qrEl1 && qrEl1.offsetWidth > 0 && qrEl1.offsetHeight > 0) {
                        wPx1 = qrEl1.offsetWidth;
                        hPx1 = qrEl1.offsetHeight;
                    } else {
                        var qs1 = getQrSize();
                        wPx1 = qs1.width;
                        hPx1 = qs1.height;
                    }
                } else {
                    wPx1 = barcodeStripes1.offsetWidth || 90;
                    hPx1 = barcodeStripes1.offsetHeight || 18;
                }
                var barLeft = barcode1LeftMm;
                var barTop = barcode1TopMm;
                var barW = clampMmGlobal(wPx1 * pxToMmX1, labelW);
                var barH = clampMmGlobal(hPx1 * pxToMmY1, labelH);
                barcodeLeftMm = barLeft;
                barcodeRightMm = barLeft + barW;
                barcodeTopMm = barTop;
                barcodeBottomMm = barTop + barH;
                designItems.push({
                    type: 'barcode_image',
                    left: barLeft,
                    top: barTop,
                    width: barW,
                    height: barH
                });
            }
            var gapMm = 1.5;
            labelPreview1El.querySelectorAll('.canvas-dropped-item').forEach(function(item) {
                var left = parseInt(item.style.left, 10);
                var top = parseInt(item.style.top, 10);
                if (isNaN(left)) left = 0;
                if (isNaN(top)) top = 0;
                var leftMm = clampMmGlobal(left * pxToMmX1, labelW);
                var topMm = clampMmGlobal(top * pxToMmY1, labelH);
                if (barcodeBottomMm > 0) {
                    topMm = resolveTextTopMmBelowBarcodeIfOverlap(leftMm, topMm, barcodeLeftMm, barcodeRightMm, barcodeTopMm, barcodeBottomMm, labelW, labelH, gapMm);
                }
                // Keep on-screen preview in sync with what we save (mm → px)
                var newTopPx = Math.round(topMm / pxToMmY1);
                item.style.top = newTopPx + 'px';
                designItems.push({
                    type: 'text',
                    field: item.getAttribute('data-field') || '',
                    left: leftMm,
                    top: topMm,
                    prefix: item.getAttribute('data-prefix') || '',
                    suffix: item.getAttribute('data-suffix') || '',
                    font: item.getAttribute('data-font') || 'Arial',
                    font_size: item.getAttribute('data-font-size') || '10',
                    pad_top: clampPadPx(item.getAttribute('data-pad-top')),
                    pad_right: clampPadPx(item.getAttribute('data-pad-right')),
                    pad_bottom: clampPadPx(item.getAttribute('data-pad-bottom')),
                    pad_left: clampPadPx(item.getAttribute('data-pad-left'))
                });
            });
        }
        if (labelPreview2El) {
            var contentW2 = (labelCanvas2El || labelPreview2El) ? ((labelCanvas2El || labelPreview2El).offsetWidth || 270) : 270;
            var contentH2 = (labelCanvas2El || labelPreview2El) ? ((labelCanvas2El || labelPreview2El).offsetHeight || 54) : 54;
            var pxToMmX2 = labelW / contentW2;
            var pxToMmY2 = labelH / contentH2;
            var barcodeWrap2 = document.getElementById('barcode2');
            var barcodeStripes2 = labelPreview2El.querySelector('.barcode-stripes');
            var rectRef2 = labelCanvas2El || labelPreview2El;
            if (barcodeWrap2 && barcodeStripes2 && rectRef2) {
                var posCanvas2 = labelCanvas2El || rectRef2;
                var off2 = getElementOffsetInAncestor(barcodeWrap2, posCanvas2);
                var leftPx2 = off2 ? off2.left : (function() {
                    var br = barcodeWrap2.getBoundingClientRect();
                    var wr = rectRef2.getBoundingClientRect();
                    return br.left - wr.left;
                })();
                var topPx2 = off2 ? off2.top : (function() {
                    var br = barcodeWrap2.getBoundingClientRect();
                    var wr = rectRef2.getBoundingClientRect();
                    return br.top - wr.top;
                })();
                barcode2LeftMm = clampMmGlobal(leftPx2 * pxToMmX2, labelW);
                barcode2TopMm = clampMmGlobal(topPx2 * pxToMmY2, labelH);
                var wPx2, hPx2;
                if (typeof currentCodeType !== 'undefined' && currentCodeType === 'qr' && barcodeWrap2) {
                    var qrEl2 = barcodeWrap2.querySelector('.qr-code-preview');
                    if (qrEl2 && qrEl2.offsetWidth > 0 && qrEl2.offsetHeight > 0) {
                        wPx2 = qrEl2.offsetWidth;
                        hPx2 = qrEl2.offsetHeight;
                    } else {
                        var qs2 = getQrSize();
                        wPx2 = qs2.width;
                        hPx2 = qs2.height;
                    }
                } else {
                    wPx2 = barcodeStripes2.offsetWidth || 90;
                    hPx2 = barcodeStripes2.offsetHeight || 18;
                }
                designItems2.push({
                    type: 'barcode_image',
                    left: barcode2LeftMm,
                    top: barcode2TopMm,
                    width: clampMmGlobal(wPx2 * pxToMmX2, labelW),
                    height: clampMmGlobal(hPx2 * pxToMmY2, labelH)
                });
            }
            var barcodeLeftMm2 = 0, barcodeRightMm2 = 0, barcodeTopMm2 = 0, barcodeBottomMm2 = 0;
            if (designItems2.length) {
                var bi = designItems2[0];
                barcodeLeftMm2 = bi.left; barcodeRightMm2 = bi.left + bi.width;
                barcodeTopMm2 = bi.top; barcodeBottomMm2 = bi.top + bi.height;
            }
            labelPreview2El.querySelectorAll('.canvas-dropped-item').forEach(function(item) {
                var left = parseInt(item.style.left, 10);
                var top = parseInt(item.style.top, 10);
                if (isNaN(left)) left = 0;
                if (isNaN(top)) top = 0;
                var leftMm = clampMmGlobal(left * pxToMmX2, labelW);
                var topMm = clampMmGlobal(top * pxToMmY2, labelH);
                var gap2 = 1.5;
                if (barcodeBottomMm2 > 0) {
                    topMm = resolveTextTopMmBelowBarcodeIfOverlap(leftMm, topMm, barcodeLeftMm2, barcodeRightMm2, barcodeTopMm2, barcodeBottomMm2, labelW, labelH, gap2);
                }
                // Keep on-screen preview in sync with what we save (mm → px)
                var newTopPx2 = Math.round(topMm / pxToMmY2);
                item.style.top = newTopPx2 + 'px';
                designItems2.push({
                    type: 'text',
                    field: item.getAttribute('data-field') || '',
                    left: leftMm,
                    top: topMm,
                    prefix: item.getAttribute('data-prefix') || '',
                    suffix: item.getAttribute('data-suffix') || '',
                    font: item.getAttribute('data-font') || 'Arial',
                    font_size: item.getAttribute('data-font-size') || '10',
                    pad_top: clampPadPx(item.getAttribute('data-pad-top')),
                    pad_right: clampPadPx(item.getAttribute('data-pad-right')),
                    pad_bottom: clampPadPx(item.getAttribute('data-pad-bottom')),
                    pad_left: clampPadPx(item.getAttribute('data-pad-left'))
                });
            });
        }
        designItems = dedupeDesignLayoutItems(designItems);
        if (designItems2.length) designItems2 = dedupeDesignLayoutItems(designItems2);
        var layoutPayload = designItems2.length > 0 ? {
            items: designItems,
            items2: designItems2,
            barcode1_top: barcode1TopMm,
            barcode1_left: barcode1LeftMm,
            barcode2_top: barcode2TopMm,
            barcode2_left: barcode2LeftMm
        } : { items: designItems, barcode1_top: barcode1TopMm, barcode1_left: barcode1LeftMm };
        var isQrSave = (typeof currentCodeType !== 'undefined' && currentCodeType === 'qr');
        if (isQrSave) {
            var qw = parseInt(document.getElementById('propQrWidth') && document.getElementById('propQrWidth').value, 10);
            var qh = parseInt(document.getElementById('propQrHeight') && document.getElementById('propQrHeight').value, 10);
            layoutPayload.qr_width = (isNaN(qw) || qw < 30) ? 60 : Math.min(200, qw);
            layoutPayload.qr_height = (isNaN(qh) || qh < 30) ? 60 : Math.min(200, qh);
        } else {
            var barW = parseInt(document.getElementById('propBarcodeBarWidth') && document.getElementById('propBarcodeBarWidth').value, 10);
            var barH = parseInt(document.getElementById('propBarcodeBarHeight') && document.getElementById('propBarcodeBarHeight').value, 10);
            layoutPayload.barcode_bar_width = (isNaN(barW) || barW < 1) ? 2 : Math.min(10, barW);
            layoutPayload.barcode_bar_height = (isNaN(barH) || barH < 10) ? 28 : Math.min(200, barH);
        }
        function clampLabelPadPx(v) {
            var n = parseInt(v, 10);
            if (isNaN(n) || n < 0) return 0;
            return Math.min(200, n);
        }
        var lpT = document.getElementById('labelPadTop');
        var lpR = document.getElementById('labelPadRight');
        var lpB = document.getElementById('labelPadBottom');
        var lpL = document.getElementById('labelPadLeft');
        layoutPayload.label_pad_top = lpT ? clampLabelPadPx(lpT.value) : 0;
        layoutPayload.label_pad_right = lpR ? clampLabelPadPx(lpR.value) : 0;
        layoutPayload.label_pad_bottom = lpB ? clampLabelPadPx(lpB.value) : 0;
        layoutPayload.label_pad_left = lpL ? clampLabelPadPx(lpL.value) : 0;
        var bc1Save = document.getElementById('barcode1');
        var labelCanvas1Save = document.getElementById('labelCanvas1');
        if (bc1Save && labelCanvas1Save) {
            var pinOff = getElementOffsetInAncestor(bc1Save, labelCanvas1Save);
            if (pinOff) {
                layoutPayload.barcode_left = Math.round(pinOff.left);
                layoutPayload.barcode_top = Math.round(pinOff.top);
            } else {
                layoutPayload.barcode_left = Math.round(bc1Save.offsetLeft);
                layoutPayload.barcode_top = Math.round(bc1Save.offsetTop);
            }
            layoutPayload.barcode_position = { left: layoutPayload.barcode_left, top: layoutPayload.barcode_top };
        }
        layoutPayload.layout_type = (typeof currentCodeType !== 'undefined' && currentCodeType) ? currentCodeType : 'barcode';
        layoutPayload.fields = designItems.slice(0);
        if (designItems2.length > 0) {
            layoutPayload.fields2 = designItems2.slice(0);
        }
        return layoutPayload;
    }

    document.getElementById('btnSaveBarcodeSettings').addEventListener('click', function() {
        var saveBtn = document.getElementById('btnSaveBarcodeSettings');
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Please wait...'; }
        function resetSaveButton() { if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; } }

        setTimeout(function() {
        flushPendingCanvasPropsToDom();
        var payload = buildBarcodeFormPayload();
        var layoutPayload = buildBarcodeLayoutPayloadObject(payload);
        layoutPayload.layout_variant = (typeof currentCodeType !== 'undefined' && currentCodeType === 'qr') ? 'qr' : 'barcode';
        if (typeof currentCodeType !== 'undefined' && currentCodeType === 'barcode') {
            persistedLayoutBarcode = JSON.stringify(layoutPayload);
        } else {
            persistedLayoutQr = JSON.stringify(layoutPayload);
        }
        payload.design_layout = persistedLayoutBarcode;
        payload.design_layout_qr = persistedLayoutQr;
        var dpcEl = document.getElementById('defaultPrintCodeType');
        payload.default_print_code_type = (dpcEl && dpcEl.value === 'qr') ? 'qr' : 'barcode';
        var formData = new FormData();
        Object.keys(payload).forEach(function(k) { formData.append(k, payload[k]); });
        var sb = document.getElementById('settingsBranchId');
        if (sb) formData.append('settings_branch_id', sb.value);
        var previewEl = document.querySelector('.barcode-preview-block .barcode-default-inner');
        function doSave() {
            fetch('ajax/save-barcode-settings.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    resetSaveButton();
                    if (data.success) {
                        alert(data.message || 'Barcode settings saved.');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Save failed.');
                    }
                })
                .catch(function() {
                    resetSaveButton();
                    alert('Request failed');
                });
        }
        if (previewEl && typeof html2canvas === 'function') {
            html2canvas(previewEl, { scale: 2, useCORS: true, logging: false, backgroundColor: '#ffffff' })
                .then(function(canvas) {
                    var dataUrl = canvas.toDataURL('image/png');
                    formData.append('preview_image', dataUrl);
                    doSave();
                })
                .catch(function() { doSave(); });
        } else {
            doSave();
        }
        }, 0);
    });

    // Init barcode in "Print preview" section (same as Stock Journal print)
    (function initPrintPreviewBarcode() {
        var previewBarcode = document.querySelector('.barcode-print-preview-label .barcode');
        if (!previewBarcode || typeof JsBarcode === 'undefined') return;
        var barcodeValue = previewBarcode.getAttribute('data-barcode') || '00002';
        var barcodeNumber = String(barcodeValue).trim() || barcodeValue;
        var barOpts = getBarcodeBarOptions ? getBarcodeBarOptions() : { width: 2, height: 50 };
        try {
            JsBarcode(previewBarcode, barcodeNumber, {
                format: 'CODE128',
                width: barOpts.width,
                height: barOpts.height,
                displayValue: false,
                margin: 0,
                background: '#ffffff',
                lineColor: '#000000'
            });
        } catch (e) {}
    })();

    /** Label padding inputs: always sync from saved design_layout JSON (runs even if main layout restore throws). */
    (function restoreLabelPadInputsFromSavedLayout() {
        if (!savedDesignLayout || typeof savedDesignLayout !== 'string' || !savedDesignLayout.trim()) return;
        try {
            var p = JSON.parse(savedDesignLayout);
            if (!p || typeof p !== 'object') return;
            var pairs = [
                ['label_pad_top', 'labelPadTop'],
                ['label_pad_right', 'labelPadRight'],
                ['label_pad_bottom', 'labelPadBottom'],
                ['label_pad_left', 'labelPadLeft']
            ];
            pairs.forEach(function(row) {
                var key = row[0], id = row[1];
                if (p[key] === undefined || p[key] === null) return;
                var el = document.getElementById(id);
                if (el) el.value = String(Math.max(0, Math.min(200, parseInt(p[key], 10) || 0)));
            });
        } catch (e) {}
    })();
})();
</script>
</body>
</html>

