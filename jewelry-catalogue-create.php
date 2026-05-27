<?php
/**
 * Jewellery Catalogue — create / edit (from sale order process or standalone).
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/jewelry_catalog_stock_include.php';
require_once __DIR__ . '/includes/jewelry_catalogue_create_include.php';

if (!function_exists('auragold_nav_show_php_href')) {
    require_once __DIR__ . '/includes/auragold_sidebar_nav_permissions.php';
}

auragold_ensure_jewelry_catalogue_table($conn);

$jcc_title = function_exists('auragold_t')
    ? auragold_t('inv.jewellery_catalogue')
    : 'Jewellery Catalogue';
$page_heading = 'Jewellery Catelog';

$catalogue_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$order_kind = isset($_GET['order_kind']) ? trim((string) $_GET['order_kind']) : 'sale';
$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;

if ($catalogue_id > 0) {
    $jcc_row = auragold_jewelry_catalogue_load_by_id($conn, $catalogue_id);
} elseif ($order_id > 0 && $item_id > 0) {
    $jcc_row = auragold_jewelry_catalogue_prefill_from_order($conn, $order_kind, $order_id, $item_id);
} else {
    $jcc_row = auragold_jewelry_catalogue_blank_row();
}

if (!$jcc_row) {
    $jcc_row = auragold_jewelry_catalogue_blank_row();
}

$catalogue_id_loaded = (int) ($jcc_row['id'] ?? 0);
if ($catalogue_id_loaded <= 0 && trim((string) ($jcc_row['design_no'] ?? '')) === '') {
    $jcc_row['design_no'] = auragold_next_jewelry_catalogue_design_no($conn);
}

$jcc_masters = auragold_jewelry_catalog_filter_masters($conn);
$jcc_metals = $jcc_masters['metals'] ?? [];
$jcc_products = $jcc_masters['products'] ?? [];
$jcc_categories = $jcc_masters['categories'] ?? [];

$jcc_row_json = json_encode($jcc_row, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if ($jcc_row_json === false) {
    $jcc_row_json = '{}';
}

$back_url = 'jewelry-catalogue.php';
if (!empty($_GET['return'])) {
    $ret = trim((string) $_GET['return']);
    if ($ret !== '' && strpos($ret, '://') === false && strpos($ret, '..') === false) {
        $back_url = $ret;
    }
} elseif ($order_id > 0) {
    $back_url = 'sale-order-process.php';
}
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title><?php echo htmlspecialchars($page_heading, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(auragold_app_name(), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/header-script.php'; ?>
    <style>
        :root {
            --jcc-navy: #11294b;
            --jcc-pink: #e83e8c;
            --jcc-pink-dark: #d62d7c;
        }
        .jcc-wrap { padding: 12px 16px 32px; max-width: 1400px; }
        .jcc-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .jcc-top h1 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--jcc-navy);
        }
        .jcc-top-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .jcc-btn-icon {
            width: 36px; height: 36px; border-radius: 6px;
            border: 1px solid #cbd5e1; background: #fff; color: var(--jcc-navy);
            display: inline-flex; align-items: center; justify-content: center;
        }
        .jcc-btn-new {
            background: var(--jcc-pink); color: #fff; border: none;
            border-radius: 6px; font-weight: 600; padding: 0.4rem 1rem;
        }
        .jcc-btn-new:hover { background: var(--jcc-pink-dark); color: #fff; }
        .jcc-btn-save {
            background: #2563eb; color: #fff; border: none;
            border-radius: 6px; font-weight: 600; padding: 0.4rem 1.1rem;
        }
        .jcc-btn-save:hover { background: #1d4ed8; color: #fff; }
        .jcc-btn-close {
            background: #fff; color: var(--jcc-navy); border: 1px solid #cbd5e1;
            border-radius: 6px; width: 36px; height: 36px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .jcc-layout { display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-start; }
        .jcc-main { flex: 1 1 520px; min-width: 0; }
        .jcc-side {
            flex: 0 0 280px; max-width: 100%;
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 10px; padding: 14px;
        }
        .jcc-side h3 { font-size: 0.95rem; font-weight: 700; color: var(--jcc-navy); margin: 0 0 10px; }
        .jcc-upload-zone {
            border: 2px dashed #cbd5e1; border-radius: 10px;
            min-height: 200px; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: #94a3b8; cursor: pointer; background: #fff;
            position: relative; overflow: hidden;
        }
        .jcc-upload-zone:hover { border-color: var(--jcc-pink); color: var(--jcc-pink); }
        .jcc-upload-zone input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer;
        }
        .jcc-img-preview { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .jcc-img-thumb {
            width: 72px; height: 72px; object-fit: cover;
            border-radius: 6px; border: 1px solid #e2e8f0;
        }
        .jcc-img-wrap { position: relative; }
        .jcc-img-remove {
            position: absolute; top: -6px; right: -6px;
            width: 20px; height: 20px; border-radius: 50%;
            background: #dc2626; color: #fff; border: none;
            font-size: 12px; line-height: 1; cursor: pointer;
        }
        .jcc-card {
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 10px; padding: 16px; margin-bottom: 16px;
        }
        .jcc-label { font-size: 0.8125rem; font-weight: 600; color: #475569; margin-bottom: 4px; }
        .jcc-label .req { color: #dc2626; }
        .jcc-bom-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 10px;
        }
        .jcc-bom-head h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--jcc-navy); }
        .jcc-add-item {
            display: block; width: 100%; border: 2px dashed var(--jcc-pink);
            background: #fff5f9; color: var(--jcc-pink); border-radius: 10px;
            padding: 14px; text-align: center; font-weight: 600;
            cursor: pointer; margin-bottom: 12px;
        }
        .jcc-add-item:hover { background: #ffe8f3; }
        .jcc-bom-table-wrap { overflow-x: auto; }
        .jcc-bom-table { width: 100%; font-size: 0.8125rem; border-collapse: collapse; }
        .jcc-bom-table th {
            background: #f1f5f9; padding: 8px 6px; white-space: nowrap;
            border-bottom: 1px solid #e2e8f0; font-weight: 600;
        }
        .jcc-bom-table td {
            padding: 6px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;
        }
        .jcc-bom-table input { width: 100%; min-width: 70px; font-size: 0.8125rem; }
        .jcc-bom-empty { text-align: center; color: #94a3b8; padding: 24px; }
        .jcc-cat-plus {
            border: none; background: #2563eb; color: #fff;
            width: 32px; height: 32px; border-radius: 6px;
            display: inline-flex; align-items: center; justify-content: center;
        }
        @media (max-width: 991px) {
            .jcc-side { flex: 1 1 100%; }
        }
    </style>
    <link rel="stylesheet" href="assets/css/jewelry-catalogue-product-modal.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/jewelry-catalogue-product-modal.css'); ?>">
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="layout-content">
    <div class="jcc-wrap container-fluid">
        <div class="jcc-top">
            <h1><?php echo htmlspecialchars($page_heading, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="jcc-top-actions">
                <button type="button" class="jcc-btn-icon" id="jccBtnPrint" title="Print"><i class="feather icon-file-text"></i></button>
                <button type="button" class="jcc-btn-new" id="jccBtnNew">New</button>
                <button type="button" class="jcc-btn-save" id="jccBtnSave">Save</button>
                <a href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>" class="jcc-btn-close" title="Close"><i class="feather icon-x"></i></a>
            </div>
        </div>

        <form id="jccForm" autocomplete="off">
            <input type="hidden" id="jccId" value="<?php echo (int) ($jcc_row['id'] ?? 0); ?>">
            <input type="hidden" id="jccSaleOrderId" value="<?php echo (int) ($jcc_row['sale_order_id'] ?? 0); ?>">
            <input type="hidden" id="jccSaleItemId" value="<?php echo (int) ($jcc_row['sale_order_item_id'] ?? 0); ?>">
            <input type="hidden" id="jccRepairOrderId" value="<?php echo (int) ($jcc_row['repair_order_id'] ?? 0); ?>">
            <input type="hidden" id="jccRepairItemId" value="<?php echo (int) ($jcc_row['repair_order_item_id'] ?? 0); ?>">

            <div class="jcc-layout">
                <div class="jcc-main">
                    <div class="jcc-card">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label class="jcc-label" for="jccMetal">Metal</label>
                                <select class="form-control form-control-sm" id="jccMetal">
                                    <option value="">Select</option>
                                    <?php foreach ($jcc_metals as $m): ?>
                                    <option value="<?php echo (int) $m['id']; ?>"<?php echo (int) ($jcc_row['metal_id'] ?? 0) === (int) $m['id'] ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="jcc-label" for="jccProduct">Product</label>
                                <select class="form-control form-control-sm" id="jccProduct">
                                    <option value="">Select</option>
                                    <?php foreach ($jcc_products as $p): ?>
                                    <option value="<?php echo (int) $p['id']; ?>"<?php echo (int) ($jcc_row['product_id'] ?? 0) === (int) $p['id'] ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="jcc-label" for="jccCategory">Categories</label>
                                <div class="d-flex" style="gap:6px;">
                                    <select class="form-control form-control-sm" id="jccCategory">
                                        <option value="">Select</option>
                                        <?php foreach ($jcc_categories as $c): ?>
                                        <option value="<?php echo (int) $c['id']; ?>"<?php echo (int) ($jcc_row['category_id'] ?? 0) === (int) $c['id'] ? ' selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <a href="accounting-masters.php" class="jcc-cat-plus" title="Masters" target="_blank"><i class="feather icon-plus"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="jcc-label" for="jccTitle">Title <span class="req">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="jccTitle" value="<?php echo htmlspecialchars($jcc_row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="jcc-label" for="jccShortDesc">Short Desc. <span class="req">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="jccShortDesc" value="<?php echo htmlspecialchars($jcc_row['short_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label class="jcc-label" for="jccBarcode">Barcode</label>
                                <input type="text" class="form-control form-control-sm" id="jccBarcode" value="<?php echo htmlspecialchars($jcc_row['barcode'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="jcc-label" for="jccDesignNo">Design No.</label>
                                <input type="text" class="form-control form-control-sm" id="jccDesignNo"
                                    value="<?php echo htmlspecialchars($jcc_row['design_no'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    placeholder="Auto from Bill Series (e.g. JC-1)"
                                    title="From Bill Series master for Jewellery Catalogue voucher type">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="jcc-label" for="jccSku">SKU</label>
                                <input type="text" class="form-control form-control-sm" id="jccSku" value="<?php echo htmlspecialchars($jcc_row['sku'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label class="jcc-label" for="jccWeight">Weight <span class="req">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="jccWeight" inputmode="decimal" value="<?php echo htmlspecialchars($jcc_row['weight'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label class="jcc-label" for="jccAmount">Amount <span class="req">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="jccAmount" inputmode="decimal" value="<?php echo htmlspecialchars($jcc_row['amount'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-9 form-group">
                                <label class="jcc-label" for="jccFullDesc">Full Desc.</label>
                                <textarea class="form-control form-control-sm" id="jccFullDesc" rows="4"><?php echo htmlspecialchars($jcc_row['full_desc'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="jcc-card">
                        <div class="jcc-bom-head">
                            <h3>Bill of Material</h3>
                            <label class="mb-0" style="font-size:0.8125rem;">
                                <input type="checkbox" id="jccFillDmd"<?php echo !empty($jcc_row['fill_dmd_gms_rate']) ? ' checked' : ''; ?>>
                                Fill DMD/GMS Rate
                            </label>
                        </div>
                        <button type="button" class="jcc-add-item" id="jccAddBomRow">
                            <i class="feather icon-gift"></i> Add Item (Shift + Q)
                        </button>
                        <div class="jcc-bom-table-wrap">
                            <table class="jcc-bom-table" id="jccBomTable">
                                <thead>
                                    <tr>
                                        <th style="width:36px;"></th>
                                        <th>Variants</th>
                                        <th>Barcode</th>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Gross Wt.</th>
                                        <th>Final Wt.</th>
                                        <th>Net Wt.</th>
                                        <th>Pure Wt</th>
                                        <th>Making</th>
                                        <th>Design No.</th>
                                        <th>Tax</th>
                                    </tr>
                                </thead>
                                <tbody id="jccBomBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="jcc-side">
                    <h3>Images</h3>
                    <div class="jcc-upload-zone" id="jccUploadZone">
                        <input type="file" id="jccImageInput" accept="image/*" multiple>
                        <i class="feather icon-upload" style="font-size:2rem;"></i>
                        <span>Upload</span>
                    </div>
                    <div class="jcc-img-preview" id="jccImgPreview"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
window.JCC_INITIAL = <?php echo $jcc_row_json; ?>;
window.JCC_RETURN_URL = <?php echo json_encode($back_url, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script>
(function () {
    var state = {
        id: 0,
        images: [],
        bom: [],
        sale_order_id: 0,
        sale_order_item_id: 0,
        repair_order_id: 0,
        repair_order_item_id: 0
    };

    function esc(s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function initFromServer() {
        var init = window.JCC_INITIAL || {};
        state.id = parseInt(init.id, 10) || 0;
        state.images = Array.isArray(init.images) ? init.images.slice() : [];
        state.bom = Array.isArray(init.bom) ? init.bom.slice() : [];
        state.sale_order_id = parseInt(init.sale_order_id, 10) || 0;
        state.sale_order_item_id = parseInt(init.sale_order_item_id, 10) || 0;
        state.repair_order_id = parseInt(init.repair_order_id, 10) || 0;
        state.repair_order_item_id = parseInt(init.repair_order_item_id, 10) || 0;
        document.getElementById('jccId').value = state.id;
        document.getElementById('jccSaleOrderId').value = state.sale_order_id;
        document.getElementById('jccSaleItemId').value = state.sale_order_item_id;
        document.getElementById('jccRepairOrderId').value = state.repair_order_id;
        document.getElementById('jccRepairItemId').value = state.repair_order_item_id;
        if (init.design_no) {
            var dnEl = document.getElementById('jccDesignNo');
            if (dnEl && !dnEl.value.trim()) {
                dnEl.value = init.design_no;
            }
        }
        renderImages();
        renderBom();
    }

    function renderImages() {
        var box = document.getElementById('jccImgPreview');
        if (!box) return;
        box.innerHTML = '';
        state.images.forEach(function (img, idx) {
            var url = img.url || img.path || '';
            if (!url) return;
            var wrap = document.createElement('div');
            wrap.className = 'jcc-img-wrap';
            wrap.innerHTML = '<img src="' + esc(url) + '" alt="" class="jcc-img-thumb">'
                + '<button type="button" class="jcc-img-remove" data-idx="' + idx + '" aria-label="Remove">&times;</button>';
            box.appendChild(wrap);
        });
        box.querySelectorAll('.jcc-img-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var i = parseInt(btn.getAttribute('data-idx'), 10);
                if (!isNaN(i)) {
                    state.images.splice(i, 1);
                    renderImages();
                }
            });
        });
    }

    function bomField(row, key, placeholder) {
        var v = row[key] != null ? row[key] : '';
        return '<input type="text" class="form-control form-control-sm jcc-bom-inp" data-key="' + esc(key) + '" value="' + esc(v) + '" placeholder="' + esc(placeholder || '') + '">';
    }

    function renderBom() {
        var tbody = document.getElementById('jccBomBody');
        if (!tbody) return;
        if (!state.bom.length) {
            tbody.innerHTML = '<tr><td colspan="12" class="jcc-bom-empty">No Rows To Show</td></tr>';
            return;
        }
        var html = '';
        state.bom.forEach(function (row, idx) {
            html += '<tr data-idx="' + idx + '">'
                + '<td><button type="button" class="btn btn-sm btn-link text-danger jcc-bom-del" data-idx="' + idx + '">&times;</button></td>'
                + '<td>' + bomField(row, 'variants', '') + '</td>'
                + '<td>' + bomField(row, 'barcode', '') + '</td>'
                + '<td>' + bomField(row, 'description', '') + '</td>'
                + '<td>' + bomField(row, 'quantity', '') + '</td>'
                + '<td>' + bomField(row, 'gross_wt', '') + '</td>'
                + '<td>' + bomField(row, 'final_wt', '') + '</td>'
                + '<td>' + bomField(row, 'net_wt', '') + '</td>'
                + '<td>' + bomField(row, 'pure_wt', '') + '</td>'
                + '<td>' + bomField(row, 'making', '') + '</td>'
                + '<td>' + bomField(row, 'design_no', '') + '</td>'
                + '<td>' + bomField(row, 'tax', '') + '</td>'
                + '</tr>';
        });
        tbody.innerHTML = html;
        tbody.querySelectorAll('.jcc-bom-del').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var i = parseInt(btn.getAttribute('data-idx'), 10);
                if (!isNaN(i)) {
                    state.bom.splice(i, 1);
                    renderBom();
                }
            });
        });
        tbody.querySelectorAll('.jcc-bom-inp').forEach(function (inp) {
            inp.addEventListener('change', syncBomFromDom);
            inp.addEventListener('blur', syncBomFromDom);
        });
    }

    function syncBomFromDom() {
        var tbody = document.getElementById('jccBomBody');
        if (!tbody) return;
        var rows = tbody.querySelectorAll('tr[data-idx]');
        var next = [];
        rows.forEach(function (tr) {
            var row = {};
            tr.querySelectorAll('.jcc-bom-inp').forEach(function (inp) {
                var k = inp.getAttribute('data-key');
                if (k) row[k] = inp.value;
            });
            next.push(row);
        });
        state.bom = next;
    }

    function appendBomItems(items) {
        syncBomFromDom();
        (items || []).forEach(function (it) {
            if (!it) return;
            state.bom.push({
                variants: it.variants || '',
                barcode: it.barcode || '',
                description: it.description || '',
                quantity: it.quantity != null ? String(it.quantity) : '',
                gross_wt: it.gross_wt != null ? String(it.gross_wt) : '',
                final_wt: it.final_wt != null ? String(it.final_wt) : '',
                net_wt: it.net_wt != null ? String(it.net_wt) : '',
                pure_wt: it.pure_wt != null ? String(it.pure_wt) : '',
                making: it.making != null ? String(it.making) : '',
                design_no: it.design_no || '',
                tax: it.tax != null ? String(it.tax) : ''
            });
        });
        renderBom();
    }

    window.JCC_BOM_BRIDGE = { appendBomItems: appendBomItems };

    function openBomProductModal() {
        if (typeof window.jccOpenBomProductModal === 'function') {
            window.jccOpenBomProductModal();
        } else if (typeof window.openProductModal === 'function') {
            window.openProductModal();
        } else {
            alert('Product modal is not available. Please refresh the page.');
        }
    }

    function uploadImages(files) {
        if (!files || !files.length) return;
        Array.prototype.forEach.call(files, function (file) {
            var fd = new FormData();
            fd.append('image', file);
            fetch('ajax/upload-jewelry-catalogue-image.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        state.images.push({ path: data.path, url: data.url });
                        renderImages();
                    } else {
                        alert((data && data.message) || 'Upload failed.');
                    }
                })
                .catch(function () { alert('Upload failed.'); });
        });
    }

    function collectPayload() {
        syncBomFromDom();
        var imagePaths = state.images.map(function (img) {
            return { path: img.path || '', url: img.url || '' };
        });
        return {
            id: parseInt(document.getElementById('jccId').value, 10) || 0,
            metal_id: parseInt(document.getElementById('jccMetal').value, 10) || 0,
            product_id: parseInt(document.getElementById('jccProduct').value, 10) || 0,
            category_id: parseInt(document.getElementById('jccCategory').value, 10) || 0,
            title: document.getElementById('jccTitle').value.trim(),
            short_desc: document.getElementById('jccShortDesc').value.trim(),
            full_desc: document.getElementById('jccFullDesc').value,
            barcode: document.getElementById('jccBarcode').value.trim(),
            design_no: document.getElementById('jccDesignNo').value.trim(),
            sku: document.getElementById('jccSku').value.trim(),
            weight: document.getElementById('jccWeight').value.trim(),
            amount: document.getElementById('jccAmount').value.trim(),
            fill_dmd_gms_rate: document.getElementById('jccFillDmd').checked ? 1 : 0,
            images: imagePaths,
            bom: state.bom,
            sale_order_id: parseInt(document.getElementById('jccSaleOrderId').value, 10) || 0,
            sale_order_item_id: parseInt(document.getElementById('jccSaleItemId').value, 10) || 0,
            repair_order_id: parseInt(document.getElementById('jccRepairOrderId').value, 10) || 0,
            repair_order_item_id: parseInt(document.getElementById('jccRepairItemId').value, 10) || 0
        };
    }

    function saveCatalogue() {
        var payload = collectPayload();
        var btn = document.getElementById('jccBtnSave');
        if (btn) btn.disabled = true;
        fetch('ajax/save-jewelry-catalogue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (btn) btn.disabled = false;
                if (!data || !data.success) {
                    alert((data && data.message) || 'Save failed.');
                    return;
                }
                alert(data.message || 'Saved.');
                if (data.design_no) {
                    var dn = document.getElementById('jccDesignNo');
                    if (dn) dn.value = data.design_no;
                }
                if (data.id) {
                    document.getElementById('jccId').value = data.id;
                    state.id = data.id;
                    if (window.history && window.history.replaceState) {
                        var u = new URL(window.location.href);
                        u.searchParams.set('id', String(data.id));
                        u.searchParams.delete('order_id');
                        u.searchParams.delete('item_id');
                        u.searchParams.delete('order_kind');
                        window.history.replaceState({}, '', u.toString());
                    }
                }
            })
            .catch(function () {
                if (btn) btn.disabled = false;
                alert('Save failed.');
            });
    }

    document.getElementById('jccBtnSave').addEventListener('click', function (e) {
        e.preventDefault();
        saveCatalogue();
    });
    document.getElementById('jccBtnNew').addEventListener('click', function () {
        window.location.href = 'jewelry-catalogue-create.php';
    });
    document.getElementById('jccAddBomRow').addEventListener('click', function (e) {
        e.preventDefault();
        openBomProductModal();
    });
    document.getElementById('jccImageInput').addEventListener('change', function () {
        uploadImages(this.files);
        this.value = '';
    });
    document.addEventListener('keydown', function (e) {
        if (e.shiftKey && (e.key === 'Q' || e.key === 'q')) {
            var modal = document.getElementById('productSelectionModal');
            if (modal && modal.classList.contains('show')) return;
            e.preventDefault();
            openBomProductModal();
        }
    });

    var titleEl = document.getElementById('jccTitle');
    var shortEl = document.getElementById('jccShortDesc');
    var productEl = document.getElementById('jccProduct');
    if (productEl && titleEl) {
        productEl.addEventListener('change', function () {
            var opt = productEl.options[productEl.selectedIndex];
            if (!opt || !opt.text) return;
            if (!titleEl.value.trim()) titleEl.value = opt.text;
            if (shortEl && !shortEl.value.trim()) shortEl.value = opt.text;
        });
    }

    initFromServer();
})();
</script>
<?php include __DIR__ . '/includes/jewelry_catalogue_product_modal_inc.php'; ?>
</body>
</html>
