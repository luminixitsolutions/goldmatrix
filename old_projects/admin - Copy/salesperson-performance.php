<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

/** Product category groups — each has 6 metrics (Jewelsteps-style). */
$sp_groups = [
    'gold' => 'Gold',
    'silver' => 'Silver',
    'platinum' => 'Platinum',
    'diamond_stones' => 'Diamond & Stones',
    'imitation_watches' => 'Imitation Or Watches',
    'other_services' => 'Other Or Services',
];

$sp_metrics = [
    'qty' => 'Qty',
    'gross_wt' => 'GrossWt',
    'net_wt' => 'NetWt',
    'd_ct' => 'D CT',
    'sale_amount' => 'Sale Amount',
    'gross_profit' => 'Gross Profit',
];

$sp_scheme_sub = [
    'no_of_scheme' => 'NO OF SCHEME',
    'scheme_amount' => 'SCHEME AMOUNT',
];

require_once __DIR__ . '/includes/auragold_salesperson_performance_data.php';

$default_range_label = '01-04-2025 - 31-03-2026';
if (!empty($_GET['sp_from']) && !empty($_GET['sp_to'])) {
    $default_range_label = trim((string) $_GET['sp_from']) . ' - ' . trim((string) $_GET['sp_to']);
}
$sp_range = auragold_sale_analysis_parse_range($default_range_label);
$default_range = $sp_range['label'];

/** @var array<int, array<string, mixed>> */
$sp_rows = [];
global $conn;
if (isset($conn) && $conn instanceof mysqli) {
    $sp_rows = auragold_salesperson_performance_fetch_rows(
        $conn,
        $sp_range['from_ymd'],
        $sp_range['to_ymd'],
        $sp_groups
    );
}

$row_count = count($sp_rows);
$sp_table_colspan = 2 + (count($sp_groups) * count($sp_metrics)) + count($sp_scheme_sub);

$sp_default_group_order = array_merge(array_keys($sp_groups), ['_scheme']);
$sp_default_metrics_json = json_encode(array_keys($sp_metrics), JSON_UNESCAPED_UNICODE);
$sp_default_scheme_json = json_encode(array_keys($sp_scheme_sub), JSON_UNESCAPED_UNICODE);
$sp_default_groups_json = json_encode($sp_default_group_order, JSON_UNESCAPED_UNICODE);

$DASHBOARD_PAGE_TITLE = 'Salesperson Performance';
$DASHBOARD_EXTRA_CSS = <<<'HTML'
<style>
    .sp-wrap {
        max-width: 100%;
        --sp-gold: #c9a227;
        --sp-gold-mid: #b8941f;
        --sp-gold-dark: #8b6914;
        --sp-navy: #11294b;
        --sp-navy-deep: #0c1f38;
    }
    .sp-page-title {
        font-weight: 700;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
        background: linear-gradient(135deg, #e8c547 0%, var(--sp-gold-mid) 45%, var(--sp-gold-dark) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        -webkit-text-fill-color: transparent;
    }
    @supports not (background-clip: text) {
        .sp-page-title { color: var(--sp-gold-dark); -webkit-text-fill-color: var(--sp-gold-dark); }
    }
    .sp-subnav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 1rem;
    }
    .sp-subnav a {
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
    .sp-subnav a:hover { background: #fffbf0; border-color: var(--sp-gold-mid); color: var(--sp-gold-dark); }
    .sp-subnav a.sp-subnav-active {
        background: linear-gradient(180deg, #5b4b9a 0%, #4338ca 100%);
        border-color: #3730a3;
        color: #fff !important;
    }
    .sp-toolbar .form-control.sp-date-range {
        max-width: 260px;
        border: 1px solid rgba(201, 162, 39, 0.45);
        border-radius: 8px;
        font-size: 13px;
    }
    .sp-toolbar .input-group-text { border-color: rgba(201, 162, 39, 0.45) !important; }
    .btn-sp-outline {
        border: 1px solid var(--sp-gold-mid) !important;
        color: var(--sp-gold-dark) !important;
        background: #fff !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 0.4rem 0.85rem;
    }
    .btn-sp-outline:hover { background: #fffbf0 !important; border-color: var(--sp-gold) !important; }
    .btn-sp-primary {
        background: linear-gradient(180deg, #d4af37 0%, var(--sp-gold-mid) 55%, var(--sp-gold-dark) 100%) !important;
        border: 1px solid var(--sp-gold-dark) !important;
        color: #fff !important;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        padding: 0.4rem 1rem;
        text-shadow: 0 1px 0 rgba(0,0,0,.12);
    }
    .btn-sp-primary:hover { filter: brightness(1.05); color: #fff !important; }
    .sp-badge {
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
    .sp-filter-wrap { position: relative; display: inline-block; }
    .sp-table-outer {
        background: #fff;
        border-radius: 12px;
        border: 1px solid rgba(201, 162, 39, 0.25);
        overflow: hidden;
        box-shadow: 0 4px 18px rgba(17, 41, 75, 0.08);
    }
    .sp-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sp-table-main {
        margin-bottom: 0;
        font-size: 12px;
        min-width: max-content;
    }
    .sp-table-main thead th {
        background: linear-gradient(180deg, var(--sp-navy) 0%, var(--sp-navy-deep) 100%);
        font-weight: 700;
        color: #ffffff !important;
        border-color: rgba(255,255,255,.12);
        border-bottom: 2px solid var(--sp-gold-dark) !important;
        white-space: nowrap;
        padding: 8px 10px;
        vertical-align: middle;
        text-align: center;
    }
    .sp-table-main thead th.sp-th-fixed {
        text-align: left;
        position: sticky;
        left: 0;
        z-index: 3;
        box-shadow: 2px 0 6px rgba(0,0,0,.08);
    }
    .sp-table-main thead tr:first-child th.sp-th-fixed {
        z-index: 4;
    }
    .sp-table-main thead th.sp-group-hdr {
        font-size: 11px;
        letter-spacing: 0.02em;
    }
    .sp-table-main thead th .sp-col-settings {
        margin-left: 4px;
        opacity: 0.9;
        cursor: default;
    }
    .sp-table-main tbody td {
        padding: 7px 10px;
        vertical-align: middle;
        border-color: #eef0f3;
        white-space: nowrap;
    }
    .sp-table-main tbody td.sp-td-name {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #fff;
        font-weight: 600;
        box-shadow: 2px 0 6px rgba(0,0,0,.06);
    }
    .sp-table-main tbody tr:nth-child(even) td.sp-td-name { background: #fafbfc; }
    .sp-table-main tbody tr:hover td { background: #fff9ec !important; }
    .sp-table-main tbody tr:hover td.sp-td-name { background: #fff3dc !important; }
    .sp-num { text-align: right; font-variant-numeric: tabular-nums; }
    .sp-footer-bar {
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
    .sp-pager { display: flex; align-items: center; gap: 6px; }
    .sp-pager button {
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12px;
        color: #64748b;
    }
    .sp-pager button:disabled { opacity: 0.45; cursor: not-allowed; }
    .sp-export-dd { position: relative; display: inline-block; }
    .sp-export-dd > summary { list-style: none; cursor: pointer; user-select: none; }
    .sp-export-dd > summary::-webkit-details-marker { display: none; }
    .sp-export-menu {
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
    .sp-export-menu a {
        display: block;
        padding: 8px 14px;
        color: #374151;
        text-decoration: none;
        font-size: 13px;
    }
    .sp-export-menu a:hover { background: #fffbf0; color: var(--sp-gold-dark); }
</style>
HTML;

require __DIR__ . '/includes/dashboard_shell_top.php';
?>
<div class="sp-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
        <h1 class="sp-page-title mb-0">Salesperson Performance</h1>
        <div class="sp-toolbar d-flex flex-wrap align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: auto;">
                <span class="input-group-text bg-white border-end-0"><i class="feather icon-calendar" style="color:#a67c1a;"></i></span>
                <input type="text" class="form-control sp-date-range border-start-0" id="spDateRange" value="<?php echo htmlspecialchars($default_range); ?>" readonly aria-label="Date range">
            </div>
            <div class="sp-filter-wrap" title="Filters (placeholder)">
                <button type="button" class="btn btn-sp-outline position-relative" id="spFilter" aria-label="Filter">
                    <i class="feather icon-filter"></i>
                    <span class="sp-badge">2</span>
                </button>
            </div>
            <button type="button" class="btn btn-sp-outline" id="spRefresh" title="Refresh"><i class="feather icon-refresh-cw"></i></button>
            <details class="sp-export-dd" data-fs-root="#spMainTable" data-fs-file="salesperson-performance" data-fs-title="Salesperson Performance">
                <summary class="btn btn-sp-primary">Export <i class="feather icon-chevron-down" style="font-size:14px;vertical-align:middle;"></i></summary>
                <div class="sp-export-menu">
                    <a href="#" class="fs-export-xls">Excel</a>
                    <a href="#" class="fs-export-pdf">PDF</a>
                </div>
            </details>
        </div>
    </div>

    <nav class="sp-subnav" aria-label="Financial statement analysis">
        <a href="sale-analysis.php">Sale Analysis</a>
        <a href="gold-silver-financial-analysis.php">Gold Silver Analysis</a>
        <a href="diamond-stone-financial-analysis.php">Diamond &amp; Stone Analysis</a>
        <a href="salesperson-performance.php" class="sp-subnav-active">Salesperson Performance</a>
    </nav>

    <div class="sp-table-outer">
        <div class="sp-table-scroll">
            <table id="spMainTable" class="table sp-table-main table-bordered"
                data-sp-default-groups="<?php echo htmlspecialchars($sp_default_groups_json, ENT_QUOTES, 'UTF-8'); ?>"
                data-sp-default-metrics="<?php echo htmlspecialchars($sp_default_metrics_json, ENT_QUOTES, 'UTF-8'); ?>"
                data-sp-default-scheme-metrics="<?php echo htmlspecialchars($sp_default_scheme_json, ENT_QUOTES, 'UTF-8'); ?>">
                <thead>
                    <tr>
                        <th rowspan="2" class="sp-th-fixed" data-sp-fixed="1" data-sp-role="name">Sales Person Name</th>
                        <th rowspan="2" data-sp-fixed="1" data-sp-role="bills">No Of Bills</th>
                        <?php foreach ($sp_groups as $slug => $glabel): ?>
                        <th colspan="6" class="sp-group-hdr sp-group-top" data-sp-group="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" data-sp-cols="6">
                            <?php echo htmlspecialchars($glabel); ?> (group)
                            <span class="sp-group-drag" title="Drag to reorder groups"><i class="feather icon-move" aria-hidden="true"></i></span>
                        </th>
                        <?php endforeach; ?>
                        <th colspan="2" class="sp-group-hdr sp-group-top" data-sp-group="_scheme" data-sp-cols="2">
                            SCHEME (group)
                            <span class="sp-group-drag" title="Drag to reorder groups"><i class="feather icon-move" aria-hidden="true"></i></span>
                        </th>
                    </tr>
                    <tr>
                        <?php foreach ($sp_groups as $slug => $_glabel): ?>
                            <?php foreach ($sp_metrics as $mk => $mlabel): ?>
                        <th data-sp-group="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" data-sp-metric="<?php echo htmlspecialchars($mk, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($mlabel); ?>
                            <span class="sp-metric-drag" title="Drag to reorder columns in group (same order for all categories)"><i class="feather icon-move" aria-hidden="true"></i></span>
                        </th>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php foreach ($sp_scheme_sub as $sk => $slabel): ?>
                        <th data-sp-group="_scheme" data-sp-metric="<?php echo htmlspecialchars($sk, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($slabel); ?>
                            <span class="sp-metric-drag" title="Drag to reorder scheme columns"><i class="feather icon-move" aria-hidden="true"></i></span>
                            <?php echo $sk === 'scheme_amount' ? ' <i class="feather icon-settings sp-col-settings" title="Column settings (placeholder)"></i>' : ''; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($row_count === 0): ?>
                    <tr>
                        <td class="text-center text-muted py-4" colspan="<?php echo (int) $sp_table_colspan; ?>">No sale lines in this date range (sales + POS). Adjust the range and use Refresh, or check branch access.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($sp_rows as $row): ?>
                    <tr>
                        <td class="sp-td-name" data-sp-fixed="1" data-sp-role="name"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="sp-num" data-sp-fixed="1" data-sp-role="bills"><?php echo htmlspecialchars($row['bills']); ?></td>
                        <?php foreach ($sp_groups as $slug => $_g): ?>
                            <?php
                            $gdata = isset($row[$slug]) && is_array($row[$slug]) ? $row[$slug] : [];
                            foreach ($sp_metrics as $mk => $_ml):
                                $cell = isset($gdata[$mk]) ? (string) $gdata[$mk] : '';
                            ?>
                        <td class="sp-num" data-sp-group="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" data-sp-metric="<?php echo htmlspecialchars($mk, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <td class="sp-num" data-sp-group="_scheme" data-sp-metric="no_of_scheme"><?php echo htmlspecialchars($row['scheme']['no_of_scheme']); ?></td>
                        <td class="sp-num" data-sp-group="_scheme" data-sp-metric="scheme_amount"><?php echo htmlspecialchars($row['scheme']['scheme_amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="sp-footer-bar">
            <span><?php echo $row_count > 0 ? 'Showing <strong>1</strong> to <strong>' . (int) $row_count . '</strong> of <strong>' . (int) $row_count . '</strong> entries' : 'Showing <strong>0</strong> entries'; ?></span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label class="mb-0 small">Show</label>
                <select class="form-control form-control-sm" style="width:auto; min-width:120px;" disabled aria-label="Page size">
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                    <option>All Items</option>
                </select>
            </div>
            <div class="sp-pager">
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
    var inp = document.getElementById('spDateRange');
    var def = <?php echo json_encode($default_range, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    document.getElementById('spRefresh').addEventListener('click', function () {
        var v = (inp && inp.value) ? String(inp.value).trim() : def;
        var parts = v.split(/\s+\-\s+/);
        if (parts.length === 2 && parts[0].trim() !== '' && parts[1].trim() !== '') {
            window.location.href = 'salesperson-performance.php?sp_from=' + encodeURIComponent(parts[0].trim()) + '&sp_to=' + encodeURIComponent(parts[1].trim());
            return;
        }
        window.location.reload();
    });
    document.getElementById('spFilter').addEventListener('click', function () {
        alert('Filter panel can be connected to date range, branch, and salesperson filters.');
    });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="assets/js/auragold-salesperson-col-reorder.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.AuragoldSalespersonColReorder) {
        AuragoldSalespersonColReorder.init('#spMainTable', { storageKey: 'auragold_sp_perf_columns' });
    }
});
</script>
<?php
require __DIR__ . '/includes/dashboard_shell_bottom.php';
