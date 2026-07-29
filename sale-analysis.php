<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auragold_branch_data_scope.php';
require_once __DIR__ . '/includes/auragold_sale_analysis_data.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

/** Column order and labels — populated from sale invoice lines (see auragold_sale_analysis_fetch_rows). */
$sa_fields = [
    'ledger_name' => 'Ledger Name',
    'party' => 'Party',
    'sales_person' => 'Sales Person',
    'invoice_no' => 'Invoice No.',
    'branch' => 'Branch',
    'date' => 'Date',
    'barcode' => 'Barcode',
    'pcs' => 'Pcs',
    'category' => 'Category',
    'product' => 'Product',
    'gross_wt' => 'Gross Wt',
    'final_wt' => 'Final Wt',
    'metal_amt' => 'Metal Amt.',
    'making_amt' => 'Making Amt.',
    'amount' => 'Amount',
    'sales_amt' => 'Sales Amt.',
    'tax_amount' => 'Tax Amount',
    'making_cost' => 'Making Cost',
    'cost_price' => 'Cost Price',
    'profit' => 'Profit',
    'grand_total' => 'Grand Total',
    'discount' => 'Discount',
    'cash' => 'Cash',
    'bank' => 'Bank',
    'transaction_name' => 'Transaction Name',
    'cheque' => 'Cheque',
    'upi' => 'Upi',
    'card' => 'Card',
    'metal_exch_amt' => 'Metal Exch. Amt',
    'metal_exch_wt' => 'Metal Exch. Wt',
    'old_jew_amt' => 'Old Jew. Amt',
    'old_jew_wt' => 'Old Jew. Wt',
    'huid_no' => 'HUID No.',
    'balance_amt' => 'Balance Amt.',
    'comment' => 'Comment',
    'currency' => 'Currency',
    'layaways_status' => 'Layaways Status',
    'advance_payment' => 'Advance Payment',
    'round_off' => 'Round OFF Value',
    'from_prev_balance' => 'From Previous Balance Amount',
    'return_amount' => 'Return Amount',
    'additional_amount' => 'Additional Amount',
    'customer_advance' => 'Customer Advance Amount',
    'fund_transfer' => 'Fund Transfer Amount',
    'sale_order_advance' => 'Sale Order Advance Payment',
    'article' => 'Article',
    'national_id' => 'National Id',
    'mobile_no' => 'Mobile No.',
];

$default_range_label = '';
if (!empty($_GET['sa_from']) && !empty($_GET['sa_to'])) {
    $default_range_label = trim((string) $_GET['sa_from']) . ' - ' . trim((string) $_GET['sa_to']);
} else {
    $todaySa = new DateTimeImmutable('today');
    $ySa     = (int) $todaySa->format('Y');
    $mSa     = (int) $todaySa->format('n');
    $fyStart = $mSa >= 4 ? $ySa : ($ySa - 1);
    $default_range_label = sprintf('01-04-%d - 31-03-%d', $fyStart, $fyStart + 1);
}
$sa_range = auragold_sale_analysis_parse_range($default_range_label);
$default_range = $sa_range['label'];

/** @var array<int, array<string, string>> */
$sa_rows = [];
global $conn;
if (isset($conn) && $conn instanceof mysqli) {
    $sa_rows = auragold_sale_analysis_fetch_rows($conn, $sa_range['from_ymd'], $sa_range['to_ymd']);
}

$row_count = count($sa_rows);
$sa_show_from = $row_count > 0 ? 1 : 0;

$DASHBOARD_PAGE_TITLE = 'Sale Reports';
$DASHBOARD_EXTRA_CSS = <<<'HTML'
<style>
    .sa-wrap {
        max-width: 100%;
        --sa-gold: #c9a227;
        --sa-gold-mid: #b8941f;
        --sa-gold-dark: #8b6914;
        --sa-navy: #11294b;
        --sa-navy-deep: #0c1f38;
    }
    .sa-page-title {
        font-weight: 700;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
        background: linear-gradient(135deg, #e8c547 0%, var(--sa-gold-mid) 45%, var(--sa-gold-dark) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
    }
    @supports not (background-clip: text) {
        .sa-page-title { color: var(--sa-gold-dark); -webkit-text-fill-color: var(--sa-gold-dark); }
    }
    .sa-subnav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 1rem;
    }
    .sa-subnav a {
        display: inline-block;
        padding: 0.35rem 0.9rem;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid rgba(17, 41, 75, 0.15);
        color: #334155;
        background: #fff;
    }
    .sa-subnav a:hover { background: #fffbf0; border-color: var(--sa-gold-mid); color: var(--sa-gold-dark); }
    .sa-subnav a.sa-subnav-active {
        background: linear-gradient(180deg, #5b4b9a 0%, #4338ca 100%);
        border-color: #3730a3;
        color: #fff !important;
    }
    .sa-toolbar .form-control.sa-date-range {
        max-width: 260px;
        border: 1px solid rgba(201, 162, 39, 0.45);
        border-radius: 8px;
        font-size: 13px;
    }
    .sa-toolbar .input-group-text { border-color: rgba(201, 162, 39, 0.45) !important; }
    .btn-sa-outline {
        border: 1px solid var(--sa-gold-mid) !important;
        color: var(--sa-gold-dark) !important;
        background: #fff !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 0.4rem 0.85rem;
    }
    .btn-sa-outline:hover { background: #fffbf0 !important; border-color: var(--sa-gold) !important; }
    .btn-sa-primary {
        background: linear-gradient(180deg, #d4af37 0%, var(--sa-gold-mid) 55%, var(--sa-gold-dark) 100%) !important;
        border: 1px solid var(--sa-gold-dark) !important;
        color: #fff !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 0.4rem 1rem;
        text-shadow: 0 1px 0 rgba(0,0,0,.12);
    }
    .btn-sa-primary:hover { filter: brightness(1.05); color: #fff !important; }
    .sa-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        font-size: 10px;
        font-weight: 700;
        line-height: 18px;
        color: #fff;
        background: #dc2626;
        border-radius: 999px;
    }
    .sa-filter-wrap { position: relative; display: inline-block; }
    .sa-table-outer {
        background: #fff;
        border-radius: 12px;
        border: 1px solid rgba(201, 162, 39, 0.25);
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(17, 41, 75, 0.08);
    }
    .sa-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sa-table-main {
        margin-bottom: 0;
        font-size: 13px;
        min-width: max-content;
    }
    .sa-table-main thead th {
        background: linear-gradient(180deg, var(--sa-navy) 0%, var(--sa-navy-deep) 100%);
        font-weight: 700;
        color: #ffffff !important;
        border-color: rgba(255,255,255,.12);
        border-bottom: 2px solid var(--sa-gold-dark) !important;
        white-space: nowrap;
        padding: 10px 12px;
        vertical-align: middle;
    }
    .sa-table-main tbody td {
        padding: 8px 12px;
        vertical-align: middle;
        border-color: #eef0f3;
        white-space: nowrap;
    }
    .sa-table-main tbody tr:nth-child(even) td { background: #fafbfc; }
    .sa-table-main tbody tr:hover td { background: #fff9ec !important; }
    .sa-num { text-align: right; font-variant-numeric: tabular-nums; }
    .sa-footer-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        background: #f8fafc;
        border-top: 1px solid rgba(201, 162, 39, 0.2);
        font-size: 13px;
        color: #475569;
    }
    .sa-pager { display: flex; align-items: center; gap: 6px; }
    .sa-pager button {
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        color: #64748b;
    }
    .sa-pager button:disabled { opacity: 0.45; cursor: not-allowed; }
    .sa-export-dd { position: relative; display: inline-block; }
    .sa-export-dd > summary { list-style: none; cursor: pointer; user-select: none; }
    .sa-export-dd > summary::-webkit-details-marker { display: none; }
    .sa-export-menu {
        position: absolute;
        right: 0;
        top: 100%;
        margin-top: 4px;
        min-width: 140px;
        padding: 6px 0;
        background: #fff;
        border: 1px solid rgba(201, 162, 39, 0.35);
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0,0,0,.1);
        z-index: 20;
    }
    .sa-export-menu a {
        display: block;
        padding: 8px 14px;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
    }
    .sa-export-menu a:hover { background: #fffbf0; color: var(--sa-gold-dark); }
</style>
HTML;

$DASHBOARD_FS_PAGE = true;
require __DIR__ . '/includes/dashboard_shell_top.php';
?>
<div class="sa-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
        <h1 class="sa-page-title mb-0">Sale Reports</h1>
        <div class="sa-toolbar d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-white border-end-0"><i class="feather icon-calendar" style="color:#a67c1a;"></i></span>
                <input type="text" class="form-control sa-date-range border-start-0" id="saDateRange" value="<?php echo htmlspecialchars($default_range); ?>" readonly aria-label="Date range">
            </div>
            <div class="sa-filter-wrap" title="Filters (placeholder)">
                <button type="button" class="btn btn-sa-outline position-relative" id="saFilter" aria-label="Filter">
                    <i class="feather icon-filter"></i>
                    <span class="sa-badge">2</span>
                </button>
            </div>
            <button type="button" class="btn btn-sa-outline" id="saRefresh" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
            <details class="sa-export-dd" data-fs-root="#saMainTable" data-fs-file="sale-analysis" data-fs-title="Sale Reports">
                <summary class="btn btn-sa-primary">Export <i class="feather icon-chevron-down" style="font-size:14px;vertical-align:middle;"></i></summary>
                <div class="sa-export-menu">
                    <a href="#" class="fs-export-xls">Excel</a>
                    <a href="#" class="fs-export-pdf">PDF</a>
                </div>
            </details>
        </div>
    </div>

    <nav class="sa-subnav" aria-label="Financial statement analysis">
        <a href="sale-analysis.php" class="sa-subnav-active">Sale Reports</a>
        <a href="gold-silver-financial-analysis.php">Gold Silver Analysis</a>
        <a href="diamond-stone-financial-analysis.php">Diamond &amp; Stone Analysis</a>
        <a href="salesperson-performance.php">Salesperson Performance</a>
    </nav>

    <div class="sa-table-outer">
        <div class="sa-table-scroll">
            <table id="saMainTable" class="table sa-table-main acr-col-table">
                <thead>
                    <tr>
                        <?php foreach ($sa_fields as $key => $label): ?>
                        <th data-col="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($label); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($row_count === 0): ?>
                    <tr>
                        <td colspan="<?php echo count($sa_fields); ?>" class="text-center text-muted py-4">No sale invoice lines found for this date range.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($sa_rows as $row): ?>
                    <tr>
                        <?php foreach (array_keys($sa_fields) as $key): ?>
                        <?php
                        $val = isset($row[$key]) ? $row[$key] : '';
                        $is_num = in_array($key, [
                            'gross_wt', 'final_wt', 'metal_amt', 'making_amt', 'amount', 'sales_amt', 'tax_amount',
                            'making_cost', 'cost_price', 'profit', 'grand_total', 'discount', 'cash', 'bank',
                            'cheque', 'upi', 'card', 'metal_exch_amt', 'metal_exch_wt', 'old_jew_amt', 'old_jew_wt',
                            'balance_amt', 'advance_payment', 'round_off', 'from_prev_balance', 'return_amount',
                            'additional_amount', 'customer_advance', 'fund_transfer', 'sale_order_advance',
                        ], true);
                        ?>
                        <td data-col="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $is_num ? 'sa-num' : ''; ?>"><?php echo htmlspecialchars($val); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="sa-footer-bar">
            <span>Showing <strong><?php echo (int) $sa_show_from; ?></strong> to <strong><?php echo (int) $row_count; ?></strong> of <strong><?php echo (int) $row_count; ?></strong> entries</span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="mb-0 small">Show</label>
                <select class="form-control form-control-sm" style="width:auto; min-width:120px;" disabled aria-label="Page size">
                    <option>All Items</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
            <div class="sa-pager">
                <button type="button" disabled aria-label="First">«</button>
                <button type="button" disabled aria-label="Previous">‹</button>
                <button type="button" disabled aria-label="Next">›</button>
                <button type="button" disabled aria-label="Last">»</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var inp = document.getElementById('saDateRange');
    var def = <?php echo json_encode($default_range); ?>;
    document.getElementById('saRefresh').addEventListener('click', function () {
        var v = (inp && inp.value) ? String(inp.value).trim() : def;
        var parts = v.split(/\s+\-\s+/);
        if (parts.length === 2 && parts[0].trim() !== '' && parts[1].trim() !== '') {
            window.location.href = 'sale-analysis.php?sa_from=' + encodeURIComponent(parts[0].trim()) + '&sa_to=' + encodeURIComponent(parts[1].trim());
            return;
        }
        window.location.reload();
    });
    document.getElementById('saFilter').addEventListener('click', function () {
        alert('Filter panel can be connected to date, branch, and product filters.');
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="assets/js/auragold-col-reorder.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.AuragoldColReorder) {
        AuragoldColReorder.init('#saMainTable', { storageKey: 'auragold_colorder_sale_analysis', fixedFirst: true });
    }
});
</script>
<?php
require __DIR__ . '/includes/dashboard_shell_bottom.php';
