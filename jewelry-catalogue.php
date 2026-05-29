<?php
/**
 * Jewellery Catalogue — grid + list views, advance filter, create actions.
 */
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/jewelry_catalog_stock_include.php';
if (!function_exists('auragold_nav_show_php_href')) {
    require_once __DIR__ . '/includes/auragold_sidebar_nav_permissions.php';
}

$jcat_no_image = 'no_image.jpg';
if (!empty($_SERVER['SCRIPT_NAME'])) {
    $jcat_sd = str_replace('\\', '/', dirname((string) $_SERVER['SCRIPT_NAME']));
    if ($jcat_sd !== '' && $jcat_sd !== '/' && $jcat_sd !== '.') {
        $jcat_no_image = rtrim($jcat_sd, '/') . '/no_image.jpg';
    }
}
$jcat_title = function_exists('auragold_t')
    ? auragold_t('inv.jewellery_catalogue')
    : 'Jewellery Catalogue';

$jcat_masters = auragold_jewelry_catalog_filter_masters($conn);
$jcat_branches = $jcat_masters['branches'] ?? [];
$jcat_metals = $jcat_masters['metals'] ?? [];
$jcat_products = $jcat_masters['products'] ?? [];
$jcat_categories = $jcat_masters['categories'] ?? [];
$jcat_articles = $jcat_masters['articles'] ?? [];
$jcat_carats = $jcat_masters['carats'] ?? [];

$jcat_can_sale_order = !function_exists('auragold_nav_show_php_href') || auragold_nav_show_php_href('sale-order.php');
$jcat_can_sale_quot = !function_exists('auragold_nav_show_php_href') || auragold_nav_show_php_href('sale-quotations.php');
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title><?php echo htmlspecialchars($jcat_title, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars(auragold_app_name(), ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include __DIR__ . '/header-script.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <link rel="stylesheet" href="assets/css/advance-filter-global.css">
    <style>
        :root {
            --jcat-navy: #11294b;
            --jcat-navy-mid: #1a3a6a;
            --jcat-gold: #c9a24a;
            --jcat-gold-dark: #a8842f;
            --jcat-gold-light: #e8d48a;
            --jcat-gold-pale: #faf6eb;
        }
        .jcat-wrap { padding: 12px 16px 28px; overflow: visible; }
        .jcat-page-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--jcat-navy);
            margin: 0 0 12px;
        }
        .jcat-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 10px 0 14px;
            border-bottom: 1px solid #d6dbea;
            overflow: visible;
            position: relative;
            z-index: 20;
        }
        .jcat-search {
            flex: 1 1 200px;
            max-width: 380px;
            position: relative;
        }
        .jcat-search input {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #c8d0e2;
            padding: 0.45rem 2.4rem 0.45rem 1rem;
            font-size: 0.9375rem;
            color: var(--jcat-navy);
        }
        .jcat-search input:focus {
            outline: none;
            border-color: var(--jcat-navy);
            box-shadow: 0 0 0 2px rgba(17, 41, 75, 0.12);
        }
        .jcat-search .feather {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--jcat-gold-dark);
            pointer-events: none;
        }
        .jcat-toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-left: auto;
            overflow: visible;
        }
        .jcat-create-dropdown { position: relative; }
        .jcat-create-dropdown .dropdown-menu {
            z-index: 1100;
            min-width: 240px;
            margin-top: 4px;
            border: 1px solid #d6dbea;
            border-radius: 10px;
            box-shadow: 0 10px 28px rgba(17, 41, 75, 0.18);
            padding: 6px 0;
        }
        .jcat-create-dropdown .dropdown-menu.show { display: block; }
        .jcat-create-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            color: var(--jcat-navy);
        }
        .jcat-create-dropdown .dropdown-item i.feather {
            width: 18px;
            height: 18px;
            color: var(--jcat-gold-dark);
            flex-shrink: 0;
        }
        .jcat-create-dropdown .dropdown-item:hover,
        .jcat-create-dropdown .dropdown-item:focus {
            background: var(--jcat-gold-pale);
            color: var(--jcat-navy);
        }
        .jcat-create-dropdown .dropdown-item.disabled,
        .jcat-create-dropdown .dropdown-item:disabled {
            color: #94a3b8;
            pointer-events: none;
            opacity: 0.65;
        }
        .jcat-btn-outline {
            border: 1px solid var(--jcat-navy);
            color: var(--jcat-navy);
            background: #fff;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8125rem;
            padding: 0.35rem 0.75rem;
        }
        .jcat-btn-outline:hover {
            background: var(--jcat-gold-pale);
            border-color: var(--jcat-navy);
            color: var(--jcat-navy);
        }
        .jcat-icon-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--jcat-navy);
            border-radius: 8px;
            background: #fff;
            color: var(--jcat-navy);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 0;
        }
        .jcat-icon-btn:hover { background: var(--jcat-gold-pale); }
        .jcat-icon-btn.active {
            background: var(--jcat-navy);
            border-color: var(--jcat-navy);
            color: var(--jcat-gold-light);
        }
        .jcat-filter-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: var(--jcat-gold);
            color: var(--jcat-navy);
            font-size: 10px;
            font-weight: 700;
            line-height: 16px;
            text-align: center;
            display: none;
        }
        .jcat-filter-badge.show { display: block; }
        .jcat-metal-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .jcat-metal-tabs .btn {
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8125rem;
            border-color: #c8d0e2;
            color: var(--jcat-navy);
        }
        .jcat-metal-tabs .btn:hover {
            background: var(--jcat-gold-pale);
            border-color: var(--jcat-gold);
            color: var(--jcat-navy);
        }
        .jcat-metal-tabs .btn.active {
            background: var(--jcat-gold);
            border-color: var(--jcat-gold-dark);
            color: var(--jcat-navy);
        }
        .jcat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        .jcat-grid.d-none { display: none !important; }
        .jcat-card {
            background: #fff;
            border: 1px solid #d6dbea;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(17, 41, 75, 0.06);
            position: relative;
            transition: box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .jcat-card:hover {
            border-color: var(--jcat-gold);
            box-shadow: 0 6px 18px rgba(17, 41, 75, 0.12);
        }
        .jcat-card.jcat-card-editable { cursor: pointer; }
        .jcat-table tbody tr.jcat-row-editable { cursor: pointer; }
        .jcat-table tbody tr.jcat-row-editable:hover td { background: var(--jcat-gold-pale); }
        .jcat-card-img {
            position: relative;
            aspect-ratio: 1;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .jcat-card-img img.jcat-thumb { width: 100%; height: 100%; object-fit: cover; }
        .jcat-card-img img.jcat-no-image {
            max-width: 72%;
            max-height: 72%;
            object-fit: contain;
        }
        .jcat-card-img.jcat-show-no-label::before {
            content: 'No Image';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-weight: 700;
            color: #94a3b8;
            z-index: 1;
        }
        .jcat-card-img.jcat-show-no-label img { display: none; }
        .jcat-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: var(--jcat-navy);
            color: var(--jcat-gold-light);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            z-index: 2;
            border: 1px solid rgba(201, 162, 74, 0.45);
        }
        .jcat-card-check {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 2;
            width: 18px;
            height: 18px;
        }
        .jcat-card-body { padding: 10px 12px; }
        .jcat-card-title { font-weight: 700; font-size: 0.9rem; margin: 0 0 4px; color: var(--jcat-navy); }
        .jcat-card-sub { color: var(--jcat-gold-dark); font-size: 0.8125rem; font-weight: 600; margin: 0; }
        .jcat-card.jcat-card-highlight,
        .jcat-row-editable.jcat-card-highlight {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.45);
        }
        .jcat-list-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: auto;
            background: #fff;
        }
        .jcat-list-wrap.d-none { display: none !important; }
        .jcat-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            table-layout: fixed;
        }
        .jcat-table thead th {
            background: linear-gradient(180deg, var(--jcat-navy) 0%, var(--jcat-navy-mid) 100%);
            color: var(--jcat-gold-light);
            font-weight: 700;
            padding: 10px 12px;
            border-bottom: 2px solid var(--jcat-gold-dark);
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
            min-width: 48px;
            user-select: none;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .jcat-table thead th .jcat-th-inner {
            display: flex;
            align-items: center;
            gap: 4px;
            padding-right: 4px;
        }
        .jcat-table thead th .jcat-drag-hint {
            opacity: 0.9;
            cursor: grab;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
        }
        .jcat-table thead th .jcat-drag-hint i.feather { width: 14px; height: 14px; }
        .jcat-table thead th.jcat-col-lock .jcat-drag-hint { display: none; }
        .jcat-resize-handle {
            position: absolute;
            right: 0;
            top: 0;
            width: 6px;
            height: 100%;
            cursor: col-resize;
            z-index: 3;
        }
        .jcat-table thead th.jcat-col-lock .jcat-resize-handle { display: none; }
        .jcat-table tbody td,
        .jcat-table tfoot td {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .jcat-table tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #e8ecf2;
            vertical-align: middle;
        }
        .jcat-table tbody tr:hover td { background: var(--jcat-gold-pale); }
        .jcat-table .jcat-list-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
        }
        .jcat-table .jcat-list-thumb.jcat-no-image {
            object-fit: contain;
            padding: 4px;
        }
        .jcat-design-no { color: var(--jcat-gold-dark); font-weight: 600; }
        .jcat-active-pill {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: var(--jcat-gold-pale);
            color: var(--jcat-navy);
            border: 1px solid rgba(201, 162, 74, 0.35);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .jcat-foot-row td {
            font-weight: 700;
            background: var(--jcat-gold-pale) !important;
            color: var(--jcat-navy);
            border-top: 2px solid var(--jcat-gold);
        }
        .jcat-footer {
            margin-top: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            color: #64748b;
            font-size: 0.875rem;
        }
        .jcat-empty, .jcat-loading {
            text-align: center;
            padding: 48px 16px;
            color: #64748b;
            font-weight: 600;
        }
        .jcat-filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px 14px;
        }
        @media (max-width: 992px) {
            .jcat-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 576px) {
            .jcat-filter-grid { grid-template-columns: 1fr; }
        }
        .jcat-filter-grid .filter-field {
            grid-template-columns: 1fr;
            align-items: start;
        }
        .jcat-filter-grid .filter-field label { margin-bottom: 4px; }
        #jcatFilterOverlay .filter-modal { width: min(1100px, calc(100vw - 24px)); }
        #jcatFilterOverlay .filter-modal-head {
            color: var(--jcat-navy);
            background: linear-gradient(180deg, #fff 0%, var(--jcat-gold-pale) 100%);
        }
        #jcatFilterOverlay .filter-field label { color: var(--jcat-navy); }
        #jcatFilterApply {
            background: var(--jcat-navy);
            border-color: var(--jcat-navy);
            color: var(--jcat-gold-light);
        }
        #jcatFilterApply:hover {
            background: var(--jcat-navy-mid);
            border-color: var(--jcat-navy-mid);
            color: #fff;
        }
        #jcatFilterClear {
            border-color: var(--jcat-gold-dark);
            color: var(--jcat-gold-dark);
        }
        #jcatFilterClear:hover {
            background: var(--jcat-gold-pale);
            color: var(--jcat-navy);
        }
        .jcat-wrap .dropdown-menu .dropdown-item:active,
        .jcat-wrap .dropdown-menu .dropdown-item:focus {
            background: var(--jcat-gold-pale);
            color: var(--jcat-navy);
        }

        /* Mobile only — desktop/tablet (≥768px) unchanged */
        @media (max-width: 767.98px) {
            .jcat-wrap {
                padding: 8px 10px 20px;
            }
            .jcat-page-title {
                font-size: 1.1rem;
                margin-bottom: 8px;
            }
            .jcat-toolbar {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
                padding-bottom: 10px;
            }
            .jcat-search {
                flex: 1 1 auto;
                max-width: none;
                width: 100%;
            }
            .jcat-toolbar-right {
                margin-left: 0;
                width: 100%;
                justify-content: flex-start;
                gap: 6px;
            }
            .jcat-toolbar-right .jcat-btn-outline {
                font-size: 0.75rem;
                padding: 0.32rem 0.55rem;
            }
            .jcat-toolbar-right #jcatSync {
                flex: 1 1 auto;
                min-width: 0;
                white-space: normal;
                text-align: center;
                line-height: 1.2;
            }
            .jcat-icon-btn {
                width: 34px;
                height: 34px;
                flex-shrink: 0;
            }
            .jcat-metal-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                gap: 6px;
                margin: 8px -2px 10px;
                padding-bottom: 4px;
                scrollbar-width: thin;
            }
            .jcat-metal-tabs .btn {
                flex-shrink: 0;
                font-size: 0.75rem;
                padding: 0.3rem 0.65rem;
            }
            .jcat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }
            .jcat-grid .jcat-empty,
            .jcat-grid .jcat-loading {
                grid-column: 1 / -1;
            }
            .jcat-card {
                border-radius: 10px;
            }
            .jcat-card-body {
                padding: 8px;
            }
            .jcat-card-title {
                font-size: 0.75rem;
                line-height: 1.25;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            .jcat-card-sub {
                font-size: 0.6875rem;
                line-height: 1.3;
            }
            .jcat-badge {
                top: 4px;
                left: 4px;
                font-size: 0.625rem;
                padding: 0.15rem 0.35rem;
                max-width: calc(100% - 28px);
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .jcat-card-check {
                top: 4px;
                right: 4px;
                width: 16px;
                height: 16px;
            }
            .jcat-footer {
                font-size: 0.8125rem;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="layout-content">
    <div class="jcat-wrap container-fluid flex-grow-1">
        <h1 class="jcat-page-title"><?php echo htmlspecialchars($jcat_title, ENT_QUOTES, 'UTF-8'); ?></h1>

        <div class="jcat-toolbar">
            <div class="jcat-search">
                <label for="jcatSearch" class="sr-only">Search</label>
                <input type="search" id="jcatSearch" placeholder="Search" autocomplete="off">
                <i class="feather icon-search" aria-hidden="true"></i>
            </div>
            <div class="jcat-toolbar-right">
                <button type="button" class="jcat-btn-outline btn btn-sm" id="jcatImport" title="Import">Import</button>
                <button type="button" class="jcat-icon-btn" id="jcatBtnFilter" title="Advance Filter" aria-label="Advance Filter">
                    <i class="feather icon-filter"></i>
                    <span class="jcat-filter-badge" id="jcatFilterBadge">0</span>
                </button>
                <button type="button" class="jcat-icon-btn" id="jcatRefresh" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
                <button type="button" class="jcat-btn-outline btn btn-sm" id="jcatSync">Sync Jewellery Catalogue</button>
                <div class="dropdown jcat-create-dropdown" id="jcatCreateDropdown">
                    <button type="button" class="jcat-btn-outline btn btn-sm dropdown-toggle" id="jcatCreateBtn"
                        aria-haspopup="true" aria-expanded="false">Create</button>
                    <div class="dropdown-menu dropdown-menu-right" id="jcatCreateMenu" aria-labelledby="jcatCreateBtn">
                        <a class="dropdown-item<?php echo $jcat_can_sale_order ? '' : ' disabled'; ?>" href="#"
                            id="jcatNewSaleOrder" role="button"
                            <?php echo $jcat_can_sale_order ? '' : ' aria-disabled="true" tabindex="-1"'; ?>>
                            <i class="feather icon-file-text"></i> New Sale Order
                        </a>
                        <a class="dropdown-item<?php echo $jcat_can_sale_quot ? '' : ' disabled'; ?>" href="#"
                            id="jcatNewSaleQuotation" role="button"
                            <?php echo $jcat_can_sale_quot ? '' : ' aria-disabled="true" tabindex="-1"'; ?>>
                            <i class="feather icon-file"></i> Create Sale Quotation
                        </a>
                        <a class="dropdown-item<?php echo $jcat_can_sale_quot ? '' : ' disabled'; ?>"
                            href="<?php echo $jcat_can_sale_quot ? 'sale-quotations.php' : '#'; ?>">
                            <i class="feather icon-layers"></i> Catalogue Quotation
                        </a>
                        <div class="dropdown-divider"></div>
                        <button type="button" class="dropdown-item text-muted" id="jcatDeleteSelected" disabled>
                            <i class="feather icon-trash-2"></i> Delete Catalogue
                        </button>
                        <button type="button" class="dropdown-item" id="jcatUpdateRecords">
                            <i class="feather icon-refresh-cw"></i> Update Records
                        </button>
                    </div>
                </div>
                <a href="jewelry-catalogue-create.php?return=jewelry-catalogue.php" class="jcat-btn-outline btn btn-sm" id="jcatBtnAdd">+ Add</a>
                <button type="button" class="jcat-icon-btn active" id="jcatViewGrid" title="Grid view" aria-pressed="true"><i class="feather icon-grid"></i></button>
                <button type="button" class="jcat-icon-btn" id="jcatViewList" title="List view" aria-pressed="false"><i class="feather icon-list"></i></button>
            </div>
        </div>

        <div class="jcat-metal-tabs" id="jcatMetalTabs" role="tablist">
            <button type="button" class="btn btn-outline-secondary btn-sm active" data-metal-id="0">All</button>
        </div>

        <div id="jcatGrid" class="jcat-grid" aria-live="polite">
            <div class="jcat-loading" id="jcatLoading">
                <div class="spinner-border text-secondary" role="status"></div>
                <div>Loading catalogue…</div>
            </div>
        </div>

        <div id="jcatListWrap" class="jcat-list-wrap d-none" aria-live="polite">
            <table class="jcat-table" id="jcatTable">
                <thead>
                    <tr id="jcatHeaderRow">
                        <th class="jcat-col-lock" data-col="_cb" style="width:40px;">
                            <span class="jcat-th-inner"><input type="checkbox" id="jcatCheckAll" title="Select all"></span>
                        </th>
                        <th data-col="imageUrls" style="width:72px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>imageUrls</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="active" style="width:80px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>active</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="jewelryCatalogue" style="width:120px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>jewelryCatalogue</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="productName" style="width:140px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>Product Name</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="designNo" style="width:110px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>Design No</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="variants" style="width:90px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>Variants</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="billOfMaterial" style="width:120px;">
                            <span class="jcat-th-inner"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>Bill Of Material</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="weight" class="text-right" style="width:88px;">
                            <span class="jcat-th-inner" style="justify-content:flex-end;"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>Weight</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                        <th data-col="amount" class="text-right" style="width:88px;">
                            <span class="jcat-th-inner" style="justify-content:flex-end;"><span class="jcat-drag-hint" title="Drag to reorder"><i class="feather icon-move"></i></span>Amount</span>
                            <span class="jcat-resize-handle" title="Resize column"></span>
                        </th>
                    </tr>
                </thead>
                <tbody id="jcatTableBody"></tbody>
                <tfoot>
                    <tr class="jcat-foot-row" id="jcatFooterRow">
                        <td data-col="_cb"></td>
                        <td data-col="imageUrls"></td>
                        <td data-col="active"></td>
                        <td data-col="jewelryCatalogue"></td>
                        <td data-col="productName"></td>
                        <td data-col="designNo"></td>
                        <td data-col="variants"></td>
                        <td data-col="billOfMaterial" class="text-right">Total</td>
                        <td data-col="weight" class="text-right" id="jcatFootWt">0.000</td>
                        <td data-col="amount" class="text-right" id="jcatFootAmt">0.00</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="jcat-footer">
            <span id="jcatSummary">Showing 0 entries</span>
            <label>
                Show
                <select id="jcatPerPage" class="custom-select custom-select-sm d-inline-block w-auto ml-1">
                    <option value="25" selected>25 Items</option>
                    <option value="50">50 Items</option>
                    <option value="100">100 Items</option>
                    <option value="500">All Items</option>
                </select>
            </label>
        </div>
    </div>
</div>

<div id="jcatFilterOverlay" class="filter-modal-overlay" aria-hidden="true">
    <div class="filter-modal" role="dialog" aria-labelledby="jcatFilterTitle">
        <div class="filter-modal-head">
            <span id="jcatFilterTitle">Advance Filter</span>
            <button type="button" class="filter-modal-close" id="jcatFilterClose" aria-label="Close">&times;</button>
        </div>
        <div class="filter-modal-body">
            <form id="jcatFilterForm" class="jcat-filter-grid" autocomplete="off" onsubmit="return false;">
                <div class="filter-field">
                    <label for="jcatFBranch">Branch</label>
                    <select id="jcatFBranch" name="branch_id" class="form-control form-control-sm">
                        <option value="">All branches</option>
                        <?php foreach ($jcat_branches as $br) {
                            $bid = (int) ($br['id'] ?? 0);
                            echo '<option value="' . $bid . '">' . htmlspecialchars((string) ($br['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="jcatFMetal">Metal</label>
                    <select id="jcatFMetal" name="metal_id" class="form-control form-control-sm">
                        <option value="">Select Metal</option>
                        <?php foreach ($jcat_metals as $m) {
                            $mid = (int) ($m['id'] ?? 0);
                            echo '<option value="' . $mid . '">' . htmlspecialchars((string) ($m['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="jcatFProduct">Product</label>
                    <select id="jcatFProduct" name="product_id" class="form-control form-control-sm">
                        <option value="">Select Product Name</option>
                        <?php foreach ($jcat_products as $pr) {
                            $pid = (int) ($pr['id'] ?? 0);
                            echo '<option value="' . $pid . '">' . htmlspecialchars((string) ($pr['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="jcatFArticle">Article</label>
                    <select id="jcatFArticle" name="article" class="form-control form-control-sm">
                        <option value="">Select Article</option>
                        <?php foreach ($jcat_articles as $ar) {
                            $art = trim((string) ($ar['article'] ?? ''));
                            if ($art === '') continue;
                            echo '<option value="' . htmlspecialchars($art, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($art, ENT_QUOTES, 'UTF-8') . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="jcatFCategory">Category</label>
                    <select id="jcatFCategory" name="category_id" class="form-control form-control-sm">
                        <option value="">Select Category</option>
                        <?php foreach ($jcat_categories as $cat) {
                            $cid = (int) ($cat['id'] ?? 0);
                            echo '<option value="' . $cid . '">' . htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="jcatFKarat">Gold Karat</label>
                    <select id="jcatFKarat" class="form-control form-control-sm">
                        <option value="">Select Gold Karat</option>
                        <?php foreach ($jcat_carats as $cr) {
                            $cn = trim((string) ($cr['name'] ?? ''));
                            if ($cn === '') continue;
                            echo '<option value="' . htmlspecialchars($cn, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($cn, ENT_QUOTES, 'UTF-8') . '</option>';
                        } ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="jcatFLocation">Location</label>
                    <input type="text" id="jcatFLocation" name="location" class="form-control form-control-sm" placeholder="">
                </div>
                <div class="filter-field">
                    <label for="jcatFDesign">Design No.</label>
                    <input type="text" id="jcatFDesign" name="design_no" class="form-control form-control-sm">
                </div>
                <div class="filter-field">
                    <label for="jcatFBarcode">Barcode No.</label>
                    <input type="text" id="jcatFBarcode" name="barcode" class="form-control form-control-sm">
                </div>
                <div class="filter-field">
                    <label for="jcatFRfid">RFID Code</label>
                    <input type="text" id="jcatFRfid" name="rfid_code" class="form-control form-control-sm">
                </div>
                <div class="filter-field">
                    <label for="jcatFGross">Gross Wt</label>
                    <input type="text" id="jcatFGross" name="gross_wt" class="form-control form-control-sm">
                </div>
                <div class="filter-field">
                    <label for="jcatFComment">Comment</label>
                    <input type="text" id="jcatFComment" name="comment" class="form-control form-control-sm">
                </div>
            </form>
        </div>
        <div class="filter-modal-actions" style="padding:0 14px 14px;display:flex;gap:8px;justify-content:flex-end;">
            <button type="button" class="btn btn-sm btn-primary" id="jcatFilterApply">Apply Filter</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="jcatFilterClear">Clear Filter</button>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer-script.php'; ?>
<script>
(function () {
    var NO_IMG = <?php echo json_encode($jcat_no_image, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES); ?>;
    var API = 'ajax/list-jewelry-catalog-stock.php';
    var allItems = [];
    var metalId = 0;
    var viewMode = localStorage.getItem('jcat_view') || 'grid';
    var page = 1;
    var perPage = 25;
    var searchTimer = null;
    var selectedBarcodes = {};

    var $grid = document.getElementById('jcatGrid');
    var $listWrap = document.getElementById('jcatListWrap');
    var $tableBody = document.getElementById('jcatTableBody');
    var $loading = document.getElementById('jcatLoading');
    var $tabs = document.getElementById('jcatMetalTabs');
    var $search = document.getElementById('jcatSearch');
    var $summary = document.getElementById('jcatSummary');
    var $perPage = document.getElementById('jcatPerPage');
    var $filterOverlay = document.getElementById('jcatFilterOverlay');
    var $filterForm = document.getElementById('jcatFilterForm');
    var $filterBadge = document.getElementById('jcatFilterBadge');
    var $btnGrid = document.getElementById('jcatViewGrid');
    var $btnList = document.getElementById('jcatViewList');

    function sortCatalogueItemsFirst(items) {
        return (items || []).slice().sort(function (a, b) {
            var aOnly = a.is_catalogue_only ? 1 : 0;
            var bOnly = b.is_catalogue_only ? 1 : 0;
            if (aOnly !== bOnly) return bOnly - aOnly;
            var aId = parseInt(a.catalogue_id, 10) || 0;
            var bId = parseInt(b.catalogue_id, 10) || 0;
            if (aId !== bId) return bId - aId;
            return (parseInt(b.stock_id, 10) || 0) - (parseInt(a.stock_id, 10) || 0);
        });
    }

    function highlightCatalogueId() {
        try {
            return parseInt(new URLSearchParams(window.location.search).get('catalogue_id'), 10) || 0;
        } catch (e) {
            return 0;
        }
    }

    function esc(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    window.jcatImgError = function (img) {
        if (!img) return;
        if (img.dataset.jcatFallback === '1') {
            img.style.display = 'none';
            if (img.parentElement) img.parentElement.classList.add('jcat-show-no-label');
            return;
        }
        img.dataset.jcatFallback = '1';
        img.classList.add('jcat-no-image');
        img.classList.remove('jcat-thumb');
        img.src = NO_IMG;
    };

    function jcatImgHtml(thumb, listClass) {
        var hasThumb = thumb && String(thumb).trim();
        var src = hasThumb ? String(thumb).trim() : NO_IMG;
        var cls = (listClass || 'jcat-thumb') + (hasThumb ? '' : ' jcat-no-image');
        return '<img class="' + cls + '" src="' + esc(src) + '" alt="" loading="lazy" onerror="window.jcatImgError(this)">';
    }

    function stockBadge(item) {
        return esc(item.design_no || item.barcode || '');
    }

    function getFiltered() {
        var q = ($search && $search.value) ? $search.value.trim().toLowerCase() : '';
        var karatF = document.getElementById('jcatFKarat');
        var karatVal = karatF && karatF.value ? karatF.value.trim().toLowerCase() : '';
        return allItems.filter(function (it) {
            if (metalId > 0 && parseInt(it.metal_id, 10) !== metalId) return false;
            if (karatVal && String(it.variants || it.carat || '').toLowerCase().indexOf(karatVal) === -1) return false;
            if (!q) return true;
            var hay = [it.barcode, it.product_name, it.article, it.metal_name, it.category, it.design_no].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    }

    function getSlice(filtered) {
        var total = filtered.length;
        var pages = Math.max(1, Math.ceil(total / perPage));
        if (page > pages) page = pages;
        var start = (page - 1) * perPage;
        return { slice: filtered.slice(start, start + perPage), total: total, start: start };
    }

    function updateSummary(total, start, sliceLen) {
        if (!$summary) return;
        var from = total ? start + 1 : 0;
        var to = Math.min(start + sliceLen, total);
        $summary.textContent = 'Showing ' + from + ' to ' + to + ' of ' + total + ' entries';
    }

    function jcatCatalogueEditHref(it) {
        var cid = parseInt(it.catalogue_id, 10) || 0;
        if (cid > 0) {
            return 'jewelry-catalogue-create.php?id=' + cid + '&return=jewelry-catalogue.php';
        }
        return '';
    }

    function bindCatalogueEditClicks(root) {
        if (!root) return;
        root.querySelectorAll('.jcat-card-editable, .jcat-row-editable').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (e.target.closest('input, a, button, label')) return;
                var href = el.getAttribute('data-edit-href');
                if (href) window.location.href = href;
            });
        });
    }

    function renderGrid(slice, filtered, start) {
        if (!$grid) return;
        $grid.classList.remove('d-none');
        if ($listWrap) $listWrap.classList.add('d-none');
        if (!slice.length) {
            $grid.innerHTML = '<div class="jcat-empty">No catalogue items match your filters.</div>';
            updateSummary(filtered.length, start, 0);
            return;
        }
        var html = '';
        slice.forEach(function (it) {
            var bc = it.barcode || '';
            var checked = selectedBarcodes[bc] ? ' checked' : '';
            var thumb = (it.thumb_url && String(it.thumb_url).trim()) ? it.thumb_url : '';
            var editHref = jcatCatalogueEditHref(it);
            var cardCls = editHref ? ' jcat-card-editable' : '';
            var editAttr = editHref ? ' data-edit-href="' + esc(editHref) + '"' : '';
            html += '<article class="jcat-card' + cardCls + '" data-barcode="' + esc(bc) + '" data-catalogue-id="' + (parseInt(it.catalogue_id, 10) || 0) + '"' + editAttr + '>'
                + '<div class="jcat-card-img">' + jcatImgHtml(thumb, 'jcat-thumb')
                + '<span class="jcat-badge">' + stockBadge(it) + '</span>'
                + '<input type="checkbox" class="jcat-card-check jcat-row-check" data-barcode="' + esc(bc) + '"' + checked + '></div>'
                + '<div class="jcat-card-body">'
                + '<h2 class="jcat-card-title">' + esc(it.product_name ? it.product_name + ' - ' + it.metal_name : it.title) + '</h2>'
                + '<p class="jcat-card-sub">' + esc(it.amount_label || '0.00') + ' | ' + esc(it.weight_label || '') + '</p>'
                + '</div></article>';
        });
        $grid.innerHTML = html;
        bindRowChecks($grid);
        bindCatalogueEditClicks($grid);
        updateSummary(filtered.length, start, slice.length);
    }

    function renderList(slice, filtered, start) {
        if (!$tableBody || !$listWrap) return;
        $listWrap.classList.remove('d-none');
        if ($grid) $grid.classList.add('d-none');
        var totWt = 0;
        var totAmt = 0;
        filtered.forEach(function (it) {
            totWt += parseFloat(it.current_weight) || 0;
            totAmt += parseFloat(it.amount) || 0;
        });
        document.getElementById('jcatFootWt').textContent = totWt.toFixed(3);
        document.getElementById('jcatFootAmt').textContent = totAmt.toFixed(2);

        if (!slice.length) {
            $tableBody.innerHTML = '<tr><td colspan="10" class="text-center py-4">No catalogue items match your filters.</td></tr>';
            updateSummary(filtered.length, start, 0);
            return;
        }
        var html = '';
        slice.forEach(function (it) {
            var bc = it.barcode || '';
            var checked = selectedBarcodes[bc] ? ' checked' : '';
            var thumb = (it.thumb_url && String(it.thumb_url).trim()) ? it.thumb_url : '';
            var activeCls = it.active === 'Active' ? 'jcat-active-pill' : '';
            var editHref = jcatCatalogueEditHref(it);
            var rowCls = editHref ? ' jcat-row-editable' : '';
            var editAttr = editHref ? ' data-edit-href="' + esc(editHref) + '"' : '';
            html += '<tr class="' + rowCls.trim() + '" data-barcode="' + esc(bc) + '" data-catalogue-id="' + (parseInt(it.catalogue_id, 10) || 0) + '"' + editAttr + '>'
                + '<td data-col="_cb"><input type="checkbox" class="jcat-row-check" data-barcode="' + esc(bc) + '"' + checked + '></td>'
                + '<td data-col="imageUrls">' + jcatImgHtml(thumb, 'jcat-list-thumb') + '</td>'
                + '<td data-col="active"><span class="' + activeCls + '">' + esc(it.active || '') + '</span></td>'
                + '<td data-col="jewelryCatalogue">' + esc(it.jewelry_catalogue || 'Yes') + '</td>'
                + '<td data-col="productName">' + esc(it.product_name || '') + '</td>'
                + '<td data-col="designNo" class="jcat-design-no">' + esc(it.design_no || '') + '</td>'
                + '<td data-col="variants">' + esc(it.variants || '') + '</td>'
                + '<td data-col="billOfMaterial">' + esc(it.bill_of_material || '') + '</td>'
                + '<td data-col="weight" class="text-right">' + esc(it.weight_label || '') + '</td>'
                + '<td data-col="amount" class="text-right">' + esc(it.amount_label || '0.00') + '</td>'
                + '</tr>';
        });
        $tableBody.innerHTML = html;
        jcatSyncColumnLayout();
        bindRowChecks($listWrap);
        bindCatalogueEditClicks($listWrap);
        updateSummary(filtered.length, start, slice.length);
    }

    var JCAT_SO_STORAGE_KEY = 'auragold_jcat_sale_order_items';
    var JCAT_SQ_STORAGE_KEY = 'auragold_jcat_sale_quotation_items';

    function getSelectedCatalogItems() {
        var out = [];
        Object.keys(selectedBarcodes).forEach(function (bc) {
            for (var i = 0; i < allItems.length; i++) {
                if (allItems[i].barcode === bc) {
                    out.push(allItems[i]);
                    break;
                }
            }
        });
        return out;
    }

    function buildCatalogSelectionPayload(picked) {
        return picked.map(function (it) {
            return {
                barcode: it.barcode || '',
                metal_id: it.metal_id || 0,
                current_qty: parseFloat(it.current_qty) || 0,
                current_weight: parseFloat(it.current_weight) || 0,
                product_name: it.product_name || '',
                design_no: it.design_no || ''
            };
        });
    }

    function saveCatalogSelectionAndNavigate(storageKey, url, canAccess) {
        if (!canAccess) return;
        var picked = getSelectedCatalogItems();
        if (!picked.length) {
            alert('Please select at least one catalogue item using the checkboxes.');
            return;
        }
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(buildCatalogSelectionPayload(picked)));
        } catch (e) {
            alert('Could not save selection. Try selecting fewer items.');
            return;
        }
        window.location.href = url;
    }

    function goToSaleOrderWithSelection() {
        saveCatalogSelectionAndNavigate(
            JCAT_SO_STORAGE_KEY,
            'sale-order.php?from_jewelry_catalog=1',
            <?php echo $jcat_can_sale_order ? 'true' : 'false'; ?>
        );
    }

    function goToSaleQuotationWithSelection() {
        saveCatalogSelectionAndNavigate(
            JCAT_SQ_STORAGE_KEY,
            'sale-quotations.php?from_jewelry_catalog=1',
            <?php echo $jcat_can_sale_quot ? 'true' : 'false'; ?>
        );
    }

    function syncDeleteCatalogueBtn() {
        var delBtn = document.getElementById('jcatDeleteSelected');
        if (!delBtn) return;
        var n = Object.keys(selectedBarcodes).length;
        delBtn.disabled = n === 0;
        delBtn.classList.toggle('text-muted', n === 0);
        delBtn.classList.toggle('text-danger', n > 0);
    }

    function bindRowChecks(root) {
        if (!root) return;
        root.querySelectorAll('.jcat-row-check').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var bc = cb.getAttribute('data-barcode') || '';
                if (cb.checked) selectedBarcodes[bc] = true;
                else delete selectedBarcodes[bc];
                syncDeleteCatalogueBtn();
            });
        });
    }

    function initCreateDropdown() {
        var dd = document.getElementById('jcatCreateDropdown');
        var btn = document.getElementById('jcatCreateBtn');
        var menu = document.getElementById('jcatCreateMenu');
        if (!dd || !btn || !menu) return;

        function closeMenu() {
            dd.classList.remove('show');
            menu.classList.remove('show');
            btn.setAttribute('aria-expanded', 'false');
        }
        function openMenu() {
            dd.classList.add('show');
            menu.classList.add('show');
            btn.setAttribute('aria-expanded', 'true');
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (menu.classList.contains('show')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
        document.addEventListener('click', function (e) {
            if (!dd.contains(e.target)) {
                closeMenu();
            }
        });
        menu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    function renderView() {
        var filtered = getFiltered();
        var p = getSlice(filtered);
        if (viewMode === 'list') renderList(p.slice, filtered, p.start);
        else renderGrid(p.slice, filtered, p.start);
    }

    function setView(mode) {
        viewMode = mode === 'list' ? 'list' : 'grid';
        localStorage.setItem('jcat_view', viewMode);
        if ($btnGrid) {
            $btnGrid.classList.toggle('active', viewMode === 'grid');
            $btnGrid.setAttribute('aria-pressed', viewMode === 'grid' ? 'true' : 'false');
        }
        if ($btnList) {
            $btnList.classList.toggle('active', viewMode === 'list');
            $btnList.setAttribute('aria-pressed', viewMode === 'list' ? 'true' : 'false');
        }
        renderView();
    }

    function buildMetalTabs(metals) {
        if (!$tabs) return;
        var html = '<button type="button" class="btn btn-outline-secondary btn-sm' + (metalId === 0 ? ' active' : '') + '" data-metal-id="0">All</button>';
        (metals || []).forEach(function (m) {
            var id = parseInt(m.id, 10) || 0;
            if (id <= 0) return;
            html += '<button type="button" class="btn btn-outline-secondary btn-sm' + (metalId === id ? ' active' : '') + '" data-metal-id="' + id + '">'
                + esc(m.name || '') + '</button>';
        });
        $tabs.innerHTML = html;
        $tabs.querySelectorAll('button[data-metal-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                metalId = parseInt(btn.getAttribute('data-metal-id'), 10) || 0;
                page = 1;
                $tabs.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                renderView();
            });
        });
    }

    function countActiveFilters() {
        if (!$filterForm) return 0;
        var n = 0;
        $filterForm.querySelectorAll('input, select').forEach(function (el) {
            if (el.id === 'jcatFKarat') {
                if (el.value && el.value.trim()) n++;
                return;
            }
            if (el.name && el.value && String(el.value).trim() !== '') n++;
        });
        return n;
    }

    function updateFilterBadge() {
        var n = countActiveFilters();
        if ($filterBadge) {
            $filterBadge.textContent = String(n);
            $filterBadge.classList.toggle('show', n > 0);
        }
    }

    function buildApiUrl() {
        var url = API + '?limit=5000';
        if (!$filterForm) return url;
        var fd = new FormData($filterForm);
        fd.forEach(function (val, key) {
            if (val && String(val).trim() !== '') {
                url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(String(val).trim());
            }
        });
        var sq = $search && $search.value ? $search.value.trim() : '';
        if (sq) url += '&q=' + encodeURIComponent(sq);
        if (metalId > 0) url += '&metal_id=' + metalId;
        return url;
    }

    function loadCatalog() {
        if ($loading && $grid) {
            $grid.classList.remove('d-none');
            $grid.innerHTML = '';
            $grid.appendChild($loading);
            $loading.style.display = '';
        }
        fetch(buildApiUrl(), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if ($loading) $loading.style.display = 'none';
                if (!data || !data.success) {
                    var msg = esc((data && data.message) || 'Could not load catalogue.');
                    if ($grid) $grid.innerHTML = '<div class="jcat-empty">' + msg + '</div>';
                    return;
                }
                allItems = sortCatalogueItemsFirst(data.items || []);
                buildMetalTabs(data.metals || []);
                page = 1;
                var hi = highlightCatalogueId();
                if (hi > 0) {
                    metalId = 0;
                    if ($tabs) {
                        $tabs.querySelectorAll('button').forEach(function (b) { b.classList.remove('active'); });
                        var allBtn = $tabs.querySelector('button[data-metal-id="0"]');
                        if (allBtn) allBtn.classList.add('active');
                    }
                }
                renderView();
                if (hi > 0) {
                    var target = document.querySelector('[data-catalogue-id="' + hi + '"]');
                    if (target && typeof target.scrollIntoView === 'function') {
                        target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        target.classList.add('jcat-card-highlight');
                        setTimeout(function () { target.classList.remove('jcat-card-highlight'); }, 2500);
                    }
                }
            })
            .catch(function () {
                if ($loading) $loading.style.display = 'none';
                if ($grid) $grid.innerHTML = '<div class="jcat-empty">Network error while loading catalogue.</div>';
            });
    }

    function openFilter() {
        if ($filterOverlay) {
            $filterOverlay.classList.add('show');
            $filterOverlay.setAttribute('aria-hidden', 'false');
        }
    }
    function closeFilter() {
        if ($filterOverlay) {
            $filterOverlay.classList.remove('show');
            $filterOverlay.setAttribute('aria-hidden', 'true');
        }
    }

    document.getElementById('jcatBtnFilter').addEventListener('click', openFilter);
    document.getElementById('jcatFilterClose').addEventListener('click', closeFilter);
    $filterOverlay.addEventListener('click', function (e) {
        if (e.target === $filterOverlay) closeFilter();
    });
    document.getElementById('jcatFilterApply').addEventListener('click', function () {
        var mf = document.getElementById('jcatFMetal');
        if (mf && mf.value) metalId = parseInt(mf.value, 10) || 0;
        updateFilterBadge();
        page = 1;
        closeFilter();
        loadCatalog();
    });
    document.getElementById('jcatFilterClear').addEventListener('click', function () {
        if ($filterForm) $filterForm.reset();
        metalId = 0;
        document.getElementById('jcatFKarat').value = '';
        updateFilterBadge();
        page = 1;
        closeFilter();
        loadCatalog();
    });

    document.getElementById('jcatRefresh').addEventListener('click', loadCatalog);
    document.getElementById('jcatUpdateRecords').addEventListener('click', loadCatalog);
    document.getElementById('jcatSync').addEventListener('click', function () { loadCatalog(); });
    document.getElementById('jcatImport').addEventListener('click', function () {
        alert('Import — use Stock Journal Excel import from Utilities.');
    });
    document.getElementById('jcatDeleteSelected').addEventListener('click', function () {
        var n = Object.keys(selectedBarcodes).length;
        if (!n) { alert('Select items using the checkboxes first.'); return; }
        alert('Delete Catalogue: ' + n + ' item(s) selected. This action is not linked to stock delete yet.');
    });

    if ($search) {
        $search.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                page = 1;
                loadCatalog();
            }, 350);
        });
    }
    if ($perPage) {
        $perPage.addEventListener('change', function () {
            perPage = parseInt($perPage.value, 10) || 25;
            page = 1;
            renderView();
        });
    }
    document.getElementById('jcatCheckAll').addEventListener('change', function (e) {
        var on = e.target.checked;
        var filtered = getFiltered();
        var p = getSlice(filtered);
        p.slice.forEach(function (it) {
            if (on) selectedBarcodes[it.barcode] = true;
            else delete selectedBarcodes[it.barcode];
        });
        renderView();
        syncDeleteCatalogueBtn();
    });

    $btnGrid.addEventListener('click', function () { setView('grid'); });
    $btnList.addEventListener('click', function () { setView('list'); });

    var JCAT_DEFAULT_ORDER = ['_cb', 'imageUrls', 'active', 'jewelryCatalogue', 'productName', 'designNo', 'variants', 'billOfMaterial', 'weight', 'amount'];
    var JCAT_STORAGE_ORDER = 'jcat_list_col_order';
    var JCAT_STORAGE_WIDTHS = 'jcat_list_col_widths';
    var $jcatTable = document.getElementById('jcatTable');
    var $jcatHeaderRow = document.getElementById('jcatHeaderRow');
    var $jcatFooterRow = document.getElementById('jcatFooterRow');
    var jcatResizing = null;

    function jcatLoadJson(key, fallback) {
        try {
            var r = localStorage.getItem(key);
            return r ? JSON.parse(r) : fallback;
        } catch (e) { return fallback; }
    }
    function jcatSaveJson(key, val) {
        try { localStorage.setItem(key, JSON.stringify(val)); } catch (e) {}
    }
    function jcatApplyColumnOrder(order) {
        if (!Array.isArray(order) || !order.length || !$jcatHeaderRow || !$tableBody) return;
        var ths = Array.prototype.slice.call($jcatHeaderRow.querySelectorAll('th[data-col]'));
        var map = {};
        ths.forEach(function (th) { map[th.getAttribute('data-col')] = th; });
        order.forEach(function (key) {
            var th = map[key];
            if (th) $jcatHeaderRow.appendChild(th);
        });
        $tableBody.querySelectorAll('tr').forEach(function (tr) {
            if (tr.querySelector('td[colspan]')) return;
            var tds = {};
            tr.querySelectorAll('td[data-col]').forEach(function (td) {
                tds[td.getAttribute('data-col')] = td;
            });
            order.forEach(function (key) {
                var td = tds[key];
                if (td) tr.appendChild(td);
            });
        });
        if ($jcatFooterRow) {
            var ftds = {};
            $jcatFooterRow.querySelectorAll('td[data-col]').forEach(function (td) {
                ftds[td.getAttribute('data-col')] = td;
            });
            order.forEach(function (key) {
                var td = ftds[key];
                if (td) $jcatFooterRow.appendChild(td);
            });
        }
    }
    function jcatApplyWidths(widths) {
        if (!widths || typeof widths !== 'object' || !$jcatHeaderRow) return;
        $jcatHeaderRow.querySelectorAll('th[data-col]').forEach(function (th) {
            var k = th.getAttribute('data-col');
            if (widths[k] != null && widths[k] > 20) {
                th.style.width = widths[k] + 'px';
                var col = th.cellIndex;
                if ($tableBody) {
                    $tableBody.querySelectorAll('tr').forEach(function (tr) {
                        var c = tr.children[col];
                        if (c) c.style.width = widths[k] + 'px';
                    });
                }
                if ($jcatFooterRow) {
                    var fc = $jcatFooterRow.children[col];
                    if (fc) fc.style.width = widths[k] + 'px';
                }
            }
        });
    }
    function jcatSyncColumnLayout() {
        if (!$jcatHeaderRow) return;
        var order = jcatLoadJson(JCAT_STORAGE_ORDER, JCAT_DEFAULT_ORDER);
        if (order && order.length) jcatApplyColumnOrder(order);
        jcatApplyWidths(jcatLoadJson(JCAT_STORAGE_WIDTHS, {}));
    }
    function initJcatTableColumns() {
        if (!$jcatTable || !$jcatHeaderRow) return;
        jcatSyncColumnLayout();
        if (typeof Sortable !== 'undefined') {
            Sortable.create($jcatHeaderRow, {
                animation: 150,
                handle: '.jcat-th-inner',
                draggable: 'th:not(.jcat-col-lock)',
                filter: '.jcat-resize-handle',
                preventOnFilter: false,
                onEnd: function () {
                    var keys = Array.prototype.map.call($jcatHeaderRow.querySelectorAll('th[data-col]'), function (th) {
                        return th.getAttribute('data-col');
                    });
                    jcatSaveJson(JCAT_STORAGE_ORDER, keys);
                    jcatApplyColumnOrder(keys);
                }
            });
        }
        $jcatHeaderRow.querySelectorAll('.jcat-resize-handle').forEach(function (handle) {
            handle.addEventListener('mousedown', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
                var th = handle.closest('th');
                if (!th) return;
                jcatResizing = { th: th, startX: e.pageX, startW: th.offsetWidth };
            });
        });
        document.addEventListener('mousemove', function (e) {
            if (!jcatResizing || !$tableBody) return;
            var dx = e.pageX - jcatResizing.startX;
            var nw = Math.max(48, jcatResizing.startW + dx);
            jcatResizing.th.style.width = nw + 'px';
            var col = jcatResizing.th.cellIndex;
            $tableBody.querySelectorAll('tr').forEach(function (tr) {
                var c = tr.children[col];
                if (c) c.style.width = nw + 'px';
            });
            if ($jcatFooterRow) {
                var fc = $jcatFooterRow.children[col];
                if (fc) fc.style.width = nw + 'px';
            }
        });
        document.addEventListener('mouseup', function () {
            if (!jcatResizing) return;
            var widths = jcatLoadJson(JCAT_STORAGE_WIDTHS, {});
            widths[jcatResizing.th.getAttribute('data-col')] = jcatResizing.th.offsetWidth;
            jcatSaveJson(JCAT_STORAGE_WIDTHS, widths);
            jcatResizing = null;
        });
    }

    initJcatTableColumns();
    initCreateDropdown();
    syncDeleteCatalogueBtn();
    function jcatCloseCreateMenu() {
        var menu = document.getElementById('jcatCreateMenu');
        var dd = document.getElementById('jcatCreateDropdown');
        if (menu) menu.classList.remove('show');
        if (dd) dd.classList.remove('show');
    }

    var jcatNewSo = document.getElementById('jcatNewSaleOrder');
    if (jcatNewSo) {
        jcatNewSo.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            jcatCloseCreateMenu();
            goToSaleOrderWithSelection();
        });
    }
    var jcatNewSq = document.getElementById('jcatNewSaleQuotation');
    if (jcatNewSq) {
        jcatNewSq.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            jcatCloseCreateMenu();
            goToSaleQuotationWithSelection();
        });
    }
    setView(viewMode);
    loadCatalog();
})();
</script>
</body>
</html>
