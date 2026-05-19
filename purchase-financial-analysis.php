<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

$pa_fields = [
    'invoice_no' => 'Invoice No.',
    'branch' => 'Branch',
    'date' => 'Date',
    'barcode' => 'Barcode',
    'product' => 'Product',
    'location' => 'Location',
    'gross_wt' => 'Gross Wt',
    'final_wt' => 'Final Wt',
    'pcs' => 'Pcs',
    'stone_wt' => 'Stone Wt',
    'metal_amt' => 'Metal Amt.',
    'making_amt' => 'Making Amt.',
    'stone_amt' => 'Stone Amt.',
    'purchase_amt' => 'Purchase Amt.',
    'ledger_name' => 'Ledger Name',
    'grand_total' => 'Grand Total',
    'discount' => 'Discount',
    'cash' => 'Cash',
    'bank' => 'Bank',
    'cheque' => 'Cheque',
    'upi' => 'Upi',
    'round_off' => 'Round OFF Value',
    'card' => 'Card',
    'metal_exch_amt' => 'Metal Exch. Amt',
    'metal_exch_wt' => 'Metal Exch. Wt',
    'old_jew_amt' => 'Old Jew. Amt',
    'old_jew_wt' => 'Old Jew. Wt',
    'balance_amt' => 'Balance Amt.',
    'comment' => 'Comment',
    'currency' => 'Currency',
    'category' => 'Category',
    'article' => 'Article',
    'national_id' => 'National Id',
    'mobile_no' => 'Mobile No.',
];

/** @var array<int, array<string, string>> */
$pa_rows = [
    [
        'invoice_no' => 'PQ-2025-01042',
        'branch' => 'Main Branch',
        'date' => '11-03-2026',
        'barcode' => 'B00000003',
        'product' => '18K Necklace — Rope 45cm',
        'location' => 'Showroom A',
        'gross_wt' => '10.000',
        'final_wt' => '9.850',
        'pcs' => '1',
        'stone_wt' => '0.000',
        'metal_amt' => '2380.00',
        'making_amt' => '1000.00',
        'stone_amt' => '0.00',
        'purchase_amt' => '3380.00',
        'ledger_name' => 'Purchase — 18K Jewellery',
        'grand_total' => '3380.00',
        'discount' => '0.00',
        'cash' => '3380.00',
        'bank' => '0.00',
        'cheque' => '0.00',
        'upi' => '0.00',
        'round_off' => '-0.25',
        'card' => '0.00',
        'metal_exch_amt' => '0.00',
        'metal_exch_wt' => '0.000',
        'old_jew_amt' => '0.00',
        'old_jew_wt' => '0.000',
        'balance_amt' => '0.00',
        'comment' => 'Cash purchase — supplier invoice attached',
        'currency' => 'AED',
        'category' => '18K Gold',
        'article' => 'ART-18K-NK-8842',
        'national_id' => '784-1990-1234567-1',
        'mobile_no' => '+971 50 123 4567',
    ],
    [
        'invoice_no' => 'PQ-2025-01038',
        'branch' => 'Main Branch',
        'date' => '09-03-2026',
        'barcode' => 'B00000017',
        'product' => '22K Bangle Pair — Baby',
        'location' => 'Vault',
        'gross_wt' => '32.400',
        'final_wt' => '31.200',
        'pcs' => '2',
        'stone_wt' => '0.000',
        'metal_amt' => '8920.00',
        'making_amt' => '1200.00',
        'stone_amt' => '0.00',
        'purchase_amt' => '10120.00',
        'ledger_name' => 'Purchase — 22K Gold',
        'grand_total' => '10120.00',
        'discount' => '200.00',
        'cash' => '0.00',
        'bank' => '10120.00',
        'cheque' => '0.00',
        'upi' => '0.00',
        'round_off' => '0.00',
        'card' => '0.00',
        'metal_exch_amt' => '0.00',
        'metal_exch_wt' => '0.000',
        'old_jew_amt' => '0.00',
        'old_jew_wt' => '0.000',
        'balance_amt' => '0.00',
        'comment' => 'B2B — 30-day credit',
        'currency' => 'AED',
        'category' => '22K Gold',
        'article' => 'ART-22K-BN-6612',
        'national_id' => '—',
        'mobile_no' => '+971 4 555 8899',
    ],
    [
        'invoice_no' => 'PQ-2025-01029',
        'branch' => 'Dubai Mall Kiosk',
        'date' => '06-03-2026',
        'barcode' => 'B00000024',
        'product' => 'Diamond Studs 0.50ct',
        'location' => '—',
        'gross_wt' => '4.200',
        'final_wt' => '3.980',
        'pcs' => '1',
        'stone_wt' => '0.100',
        'metal_amt' => '980.00',
        'making_amt' => '350.00',
        'stone_amt' => '4200.00',
        'purchase_amt' => '5530.00',
        'ledger_name' => 'Purchase — Diamond & 18K',
        'grand_total' => '5530.00',
        'discount' => '0.00',
        'cash' => '0.00',
        'bank' => '0.00',
        'cheque' => '5530.00',
        'upi' => '0.00',
        'round_off' => '0.10',
        'card' => '0.00',
        'metal_exch_amt' => '0.00',
        'metal_exch_wt' => '0.000',
        'old_jew_amt' => '0.00',
        'old_jew_wt' => '0.000',
        'balance_amt' => '0.00',
        'comment' => 'Certified diamonds — GIA',
        'currency' => 'AED',
        'category' => 'Diamond Studs',
        'article' => 'ART-DIA-ER-5510',
        'national_id' => '784-1985-9876543-2',
        'mobile_no' => '+971 55 987 6543',
    ],
    [
        'invoice_no' => 'PQ-2025-01015',
        'branch' => 'Main Branch',
        'date' => '02-03-2026',
        'barcode' => 'B00000031',
        'product' => '925 Silver Oxidized Set',
        'location' => 'Showroom B',
        'gross_wt' => '125.600',
        'final_wt' => '122.800',
        'pcs' => '1',
        'stone_wt' => '0.000',
        'metal_amt' => '680.00',
        'making_amt' => '220.00',
        'stone_amt' => '0.00',
        'purchase_amt' => '900.00',
        'ledger_name' => 'Purchase — Silver',
        'grand_total' => '900.00',
        'discount' => '0.00',
        'cash' => '450.00',
        'bank' => '0.00',
        'cheque' => '0.00',
        'upi' => '450.00',
        'round_off' => '0.00',
        'card' => '0.00',
        'metal_exch_amt' => '0.00',
        'metal_exch_wt' => '0.000',
        'old_jew_amt' => '0.00',
        'old_jew_wt' => '0.000',
        'balance_amt' => '0.00',
        'comment' => 'Split: cash + UPI',
        'currency' => 'AED',
        'category' => '925 Silver',
        'article' => 'ART-AG-SET-9900',
        'national_id' => '—',
        'mobile_no' => '—',
    ],
    [
        'invoice_no' => 'PQ-2025-01008',
        'branch' => 'Main Branch',
        'date' => '28-02-2026',
        'barcode' => 'B00000008',
        'product' => 'Scrap Gold Lot — 22K mix',
        'location' => 'Melting',
        'gross_wt' => '156.800',
        'final_wt' => '155.200',
        'pcs' => '1',
        'stone_wt' => '0.000',
        'metal_amt' => '44200.00',
        'making_amt' => '0.00',
        'stone_amt' => '0.00',
        'purchase_amt' => '44200.00',
        'ledger_name' => 'Purchase — Old Gold / Scrap',
        'grand_total' => '44200.00',
        'discount' => '0.00',
        'cash' => '0.00',
        'bank' => '0.00',
        'cheque' => '0.00',
        'upi' => '0.00',
        'round_off' => '0.00',
        'card' => '0.00',
        'metal_exch_amt' => '42000.00',
        'metal_exch_wt' => '155.200',
        'old_jew_amt' => '0.00',
        'old_jew_wt' => '0.000',
        'balance_amt' => '2200.00',
        'comment' => 'Metal exchange AED 42,000; balance AED 2,200 pending',
        'currency' => 'AED',
        'category' => '22K Scrap',
        'article' => 'LOT-SCRAP-0228',
        'national_id' => '784-1992-1122334-3',
        'mobile_no' => '+971 52 334 5566',
    ],
    [
        'invoice_no' => 'PQ-2025-00991',
        'branch' => 'Dubai Mall Kiosk',
        'date' => '22-02-2026',
        'barcode' => 'B00000044',
        'product' => 'Emerald Ring 18K',
        'location' => 'Showroom A',
        'gross_wt' => '8.600',
        'final_wt' => '8.100',
        'pcs' => '1',
        'stone_wt' => '0.850',
        'metal_amt' => '1950.00',
        'making_amt' => '480.00',
        'stone_amt' => '3200.00',
        'purchase_amt' => '5630.00',
        'ledger_name' => 'Purchase — Gemstone',
        'grand_total' => '5630.00',
        'discount' => '150.00',
        'cash' => '0.00',
        'bank' => '0.00',
        'cheque' => '0.00',
        'upi' => '0.00',
        'round_off' => '-0.05',
        'card' => '5480.00',
        'metal_exch_amt' => '0.00',
        'metal_exch_wt' => '0.000',
        'old_jew_amt' => '300.00',
        'old_jew_wt' => '4.200',
        'balance_amt' => '0.00',
        'comment' => 'Card + old jewellery trade-in',
        'currency' => 'AED',
        'category' => '18K + Gemstone',
        'article' => 'ART-EM-RG-2200',
        'national_id' => '784-1995-5566778-4',
        'mobile_no' => '+971 56 778 9900',
    ],
];

$eff_pa_br = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
if ($eff_pa_br > 0 && function_exists('auragold_branch_name_map_by_ids')) {
    $pa_br_map = auragold_branch_name_map_by_ids([$eff_pa_br]);
    $pa_br_name = isset($pa_br_map[$eff_pa_br]) ? trim((string) $pa_br_map[$eff_pa_br]) : '';
    if ($pa_br_name !== '') {
        $pa_rows = array_values(array_filter($pa_rows, static function ($r) use ($pa_br_name) {
            return isset($r['branch']) && strcasecmp(trim((string) $r['branch']), $pa_br_name) === 0;
        }));
    }
}

$pa_numeric_keys = [
    'gross_wt', 'final_wt', 'pcs', 'stone_wt', 'metal_amt', 'making_amt', 'stone_amt', 'purchase_amt',
    'grand_total', 'discount', 'cash', 'bank', 'cheque', 'upi', 'round_off', 'card',
    'metal_exch_amt', 'metal_exch_wt', 'old_jew_amt', 'old_jew_wt', 'balance_amt',
];

$pa_totals = [];
foreach ($pa_numeric_keys as $k) {
    $pa_totals[$k] = 0.0;
}
foreach ($pa_rows as $row) {
    foreach ($pa_numeric_keys as $k) {
        if (isset($row[$k])) {
            $pa_totals[$k] += (float) $row[$k];
        }
    }
}

function pa_format_total(string $key, float $v): string {
    if (in_array($key, ['gross_wt', 'final_wt', 'stone_wt', 'metal_exch_wt', 'old_jew_wt'], true)) {
        return number_format($v, 3, '.', '');
    }
    if ($key === 'pcs') {
        return (string) (int) round($v);
    }
    if ($key === 'round_off') {
        return number_format($v, 2, '.', '');
    }
    return number_format($v, 2, '.', '');
}

$row_count = count($pa_rows);
$default_range = '01-04-2025 - 31-03-2026';

$DASHBOARD_PAGE_TITLE = 'Purchase Analysis';
$DASHBOARD_EXTRA_CSS = <<<'HTML'
<style>
    .pa-wrap {
        max-width: 100%;
        --pa-gold: #c9a227;
        --pa-gold-mid: #b8941f;
        --pa-gold-dark: #8b6914;
        --pa-navy: #11294b;
        --pa-navy-deep: #0c1f38;
    }
    .pa-page-title {
        font-weight: 700;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
        background: linear-gradient(135deg, #e8c547 0%, var(--pa-gold-mid) 45%, var(--pa-gold-dark) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
    }
    @supports not (background-clip: text) {
        .pa-page-title { color: var(--pa-gold-dark); -webkit-text-fill-color: var(--pa-gold-dark); }
    }
    .pa-toolbar .form-control.pa-date-range {
        max-width: 260px;
        border: 1px solid rgba(201, 162, 39, 0.45);
        border-radius: 8px;
        font-size: 13px;
    }
    .pa-toolbar .input-group-text { border-color: rgba(201, 162, 39, 0.45) !important; }
    .btn-pa-outline {
        border: 1px solid var(--pa-gold-mid) !important;
        color: var(--pa-gold-dark) !important;
        background: #fff !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 0.4rem 0.85rem;
    }
    .btn-pa-outline:hover { background: #fffbf0 !important; border-color: var(--pa-gold) !important; }
    .btn-pa-primary {
        background: linear-gradient(180deg, #d4af37 0%, var(--pa-gold-mid) 55%, var(--pa-gold-dark) 100%) !important;
        border: 1px solid var(--pa-gold-dark) !important;
        color: #fff !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 0.4rem 1rem;
        text-shadow: 0 1px 0 rgba(0,0,0,.12);
    }
    .btn-pa-primary:hover { filter: brightness(1.05); color: #fff !important; }
    .pa-badge {
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
    .pa-filter-wrap { position: relative; display: inline-block; }
    .pa-table-outer {
        background: #fff;
        border-radius: 12px;
        border: 1px solid rgba(201, 162, 39, 0.25);
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(17, 41, 75, 0.08);
    }
    .pa-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .pa-table-main {
        margin-bottom: 0;
        font-size: 13px;
        min-width: max-content;
    }
    .pa-table-main thead th {
        background: linear-gradient(180deg, var(--pa-navy) 0%, var(--pa-navy-deep) 100%);
        font-weight: 700;
        color: #ffffff !important;
        border-color: rgba(255,255,255,.12);
        border-bottom: 2px solid var(--pa-gold-dark) !important;
        white-space: nowrap;
        padding: 10px 12px;
        vertical-align: middle;
    }
    .pa-table-main thead th .pa-col-settings {
        margin-left: 6px;
        opacity: 0.9;
        cursor: default;
    }
    .pa-table-main tbody td {
        padding: 8px 12px;
        vertical-align: middle;
        border-color: #eef0f3;
        white-space: nowrap;
    }
    .pa-table-main tbody tr:nth-child(even) td { background: #fafbfc; }
    .pa-table-main tbody tr:hover td { background: #fff9ec !important; }
    .pa-table-main tfoot td {
        padding: 10px 12px;
        font-weight: 700;
        background: #e0f2fe !important;
        color: #0c4a6e;
        border-color: #bae6fd;
    }
    .pa-num { text-align: right; font-variant-numeric: tabular-nums; }
    .pa-footer-bar {
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
    .pa-pager { display: flex; align-items: center; gap: 6px; }
    .pa-pager button {
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        color: #64748b;
    }
    .pa-pager button:disabled { opacity: 0.45; cursor: not-allowed; }
    .pa-export-dd { position: relative; display: inline-block; }
    .pa-export-dd > summary { list-style: none; cursor: pointer; user-select: none; }
    .pa-export-dd > summary::-webkit-details-marker { display: none; }
    .pa-export-menu {
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
    .pa-export-menu a {
        display: block;
        padding: 8px 14px;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
    }
    .pa-export-menu a:hover { background: #fffbf0; color: var(--pa-gold-dark); }
    .pa-product-link { color: #2563eb; font-weight: 600; }
</style>
HTML;

$DASHBOARD_FS_PAGE = true;
require __DIR__ . '/includes/dashboard_shell_top.php';
?>
<div class="pa-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
        <h1 class="pa-page-title mb-0">Purchase Analysis</h1>
        <div class="pa-toolbar d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-white border-end-0"><i class="feather icon-calendar" style="color:#a67c1a;"></i></span>
                <input type="text" class="form-control pa-date-range border-start-0" id="paDateRange" value="<?php echo htmlspecialchars($default_range); ?>" readonly aria-label="Date range">
            </div>
            <div class="pa-filter-wrap" title="Filters (placeholder)">
                <button type="button" class="btn btn-pa-outline position-relative" id="paFilter" aria-label="Filter">
                    <i class="feather icon-filter"></i>
                    <span class="pa-badge">2</span>
                </button>
            </div>
            <button type="button" class="btn btn-pa-outline" id="paRefresh" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
            <details class="pa-export-dd" data-fs-root="#paMainTable" data-fs-file="purchase-financial-analysis" data-fs-title="Purchase Financial Analysis">
                <summary class="btn btn-pa-primary">Export <i class="feather icon-chevron-down" style="font-size:14px;vertical-align:middle;"></i></summary>
                <div class="pa-export-menu">
                    <a href="#" class="fs-export-xls">Excel</a>
                    <a href="#" class="fs-export-pdf">PDF</a>
                </div>
            </details>
        </div>
    </div>

    <div class="pa-table-outer">
        <div class="pa-table-scroll">
            <table id="paMainTable" class="table pa-table-main acr-col-table">
                <thead>
                    <tr>
                        <?php
                        $pa_col_i = 0;
                        $pa_total_cols = count($pa_fields);
                        foreach ($pa_fields as $key => $label):
                            $pa_col_i++;
                        ?>
                        <th data-col="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($label); ?>
                            
                            <?php if ($pa_col_i === $pa_total_cols): ?>
                            <i class="feather icon-settings pa-col-settings" title="Column settings (placeholder)"></i>
                            <?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pa_rows as $row): ?>
                    <tr>
                        <?php foreach (array_keys($pa_fields) as $key): ?>
                        <?php
                        $val = isset($row[$key]) ? $row[$key] : '';
                        $is_num = in_array($key, $pa_numeric_keys, true);
                        $is_product = ($key === 'product');
                        ?>
                        <td data-col="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $is_num ? 'pa-num' : ''; ?><?php echo $is_product ? ' pa-product-link' : ''; ?>"><?php echo htmlspecialchars($val); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <?php foreach (array_keys($pa_fields) as $key): ?>
                        <?php
                        $dck = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                        if ($key === 'product') {
                            echo '<td data-col="' . $dck . '"><strong>Total</strong></td>';
                        } elseif (in_array($key, $pa_numeric_keys, true)) {
                            echo '<td data-col="' . $dck . '" class="pa-num">' . htmlspecialchars(pa_format_total($key, $pa_totals[$key])) . '</td>';
                        } elseif (in_array($key, ['invoice_no', 'branch', 'date', 'barcode', 'location'], true)) {
                            echo '<td data-col="' . $dck . '"></td>';
                        } else {
                            echo '<td data-col="' . $dck . '">—</td>';
                        }
                        ?>
                        <?php endforeach; ?>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="pa-footer-bar">
            <span>Showing <strong>1</strong> to <strong><?php echo (int) $row_count; ?></strong> of <strong><?php echo (int) $row_count; ?></strong> entries</span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="mb-0 small">Show</label>
                <select class="form-control form-control-sm" style="width:auto; min-width:120px;" disabled aria-label="Page size">
                    <option>All Items</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
            <div class="pa-pager">
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
    document.getElementById('paRefresh').addEventListener('click', function () {
        window.location.reload();
    });
    document.getElementById('paFilter').addEventListener('click', function () {
        alert('Filter panel can be connected to date, branch, supplier, and invoice filters.');
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="assets/js/auragold-col-reorder.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.AuragoldColReorder) {
        AuragoldColReorder.init('#paMainTable', { storageKey: 'auragold_colorder_purchase_financial', fixedFirst: true });
    }
});
</script>
<?php
require __DIR__ . '/includes/dashboard_shell_bottom.php';
