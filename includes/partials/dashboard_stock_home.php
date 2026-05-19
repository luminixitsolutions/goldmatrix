<?php
if (!isset($stk) || !is_array($stk)) {
    $stk = auragold_stock_dashboard_jewelsteps();
}
if (!function_exists('stk_stock_img_url')) {
    function stk_stock_img_url($imagesJson) {
        if ($imagesJson === null || $imagesJson === '') {
            return '';
        }
        $j = json_decode((string) $imagesJson, true);
        if (is_array($j)) {
            if (!empty($j['primary'])) {
                return (string) $j['primary'];
            }
            if (isset($j[0])) {
                return (string) $j[0];
            }
            foreach ($j as $v) {
                if (is_string($v) && $v !== '') {
                    return $v;
                }
            }
        }
        return '';
    }
}
$k = $stk['kpi'];
$metals = $k['metals'] ?? [];
$metalById = [];
foreach ($metals as $m) {
    $metalById[(int) ($m['id'] ?? 0)] = $m;
}
$metalCardIds = [1, 2, 4, 5, 6];
$metalCards = [];
foreach ($metalCardIds as $mid) {
    $metalCards[] = $metalById[$mid] ?? ['id' => $mid, 'name' => '—', 'w' => 0, 'q' => 0];
}

$mcb = $stk['metal_chart_branchwise'] ?? [];
$branchChartLabelsJson = json_encode($mcb['branch_labels'] ?? [], JSON_UNESCAPED_UNICODE);
$branchChartDatasetsJson = json_encode($mcb['datasets'] ?? [], JSON_UNESCAPED_UNICODE);
?>
<style>
.stk-head { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; }
.stk-head h1 { font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0; }
.stk-tile {
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 14px;
    padding: 14px 16px;
    min-height: 110px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
}
.stk-tile .txt .t { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.stk-tile .txt .v { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 4px; }
.stk-tile .txt .q { font-size: 12px; color: #94a3b8; margin-top: 2px; }
.stk-tile .txt .q strong { color: #475569; }
.stk-ic {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.ic-p { background: #ede9fe; color: #6d28d9; }
.ic-y { background: #fef3c7; color: #b45309; }
.ic-g { background: #d1fae5; color: #047857; }
.ic-r { background: #ffe4e6; color: #be123c; }
.ic-au { background: #fef9c3; color: #a16207; }
.ic-ag { background: #dbeafe; color: #1d4ed8; }
.ic-dm { background: #e0e7ff; color: #4338ca; }
.ic-im { background: #ecfccb; color: #4d7c0f; }
.ic-ot { background: #ffe4e6; color: #9f1239; }
.stk-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 16px;
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    margin-bottom: 16px;
}
.stk-panel h3 {
    font-size: 13px;
    font-weight: 800;
    color: #334155;
    margin: 0 0 12px;
}
.stk-chart-h { height: 280px; position: relative; }
.stk-metal-bw-table { font-size: 12px; margin-top: 12px; }
.stk-metal-bw-table th { font-weight: 700; color: #64748b; }
.stk-low table { font-size: 13px; }
.stk-low .w-cell { color: #2563eb; font-weight: 600; text-align: right; }
.stk-low .q-cell { color: #dc2626; font-weight: 700; text-align: right; }
.stk-low .thumb {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background: #f1f5f9;
}
.karat-row {
    margin-bottom: 14px;
}
.karat-row .kr-h {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    margin-bottom: 6px;
}
.karat-row .kr-title { font-weight: 700; color: #6d28d9; }
.karat-row .kr-num { font-weight: 800; color: #0f172a; font-size: 15px; }
.karat-row .progress { height: 14px; border-radius: 8px; background: #f1f5f9; }
.karat-row .progress-bar { background: linear-gradient(90deg, #fb923c, #f97316); }
.stk-foot {
    margin-top: 18px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
@media (max-width: 991.98px) {
    .stk-chart-h { height: 240px; }
}
@media (max-width: 575.98px) {
    .stk-chart-h { height: 200px; }
}
@media (max-width: 767.98px) {
    .stk-dashboard-root {
        max-width: 100%;
        overflow-x: hidden;
    }
    .stk-head {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
        margin-bottom: 12px;
    }
    .stk-head h1 { font-size: 1.15rem; }
    .stk-head-actions {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 6px;
        width: 100%;
    }
    .stk-head-actions .btn {
        min-height: 44px;
        font-size: 0.68rem;
        padding: 6px 4px;
        white-space: normal;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .stk-tile {
        min-height: 0;
        align-items: flex-start;
    }
    .stk-tile .txt .t {
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .stk-foot {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="stk-dashboard-root">

<div class="stk-head">
    <div>
        <h1>Stock Dashboard</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap stk-head-actions">
        <a class="btn btn-outline-primary btn-sm" href="gold-silver-analysis.php"><i class="feather icon-bar-chart-2"></i> Gold / Silver analysis</a>
        <a class="btn btn-outline-secondary btn-sm" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
        <a class="btn btn-outline-secondary btn-sm" href="stock-history.php"><i class="feather icon-list"></i> Stock history</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="stk-tile">
                    <div class="txt">
                        <div class="t">Total Products</div>
                        <div class="v"><?php echo number_format((int) $k['total_products']); ?></div>
                        <div class="q">Qty. <strong><?php echo number_format((float) $k['total_products_qty'], 2); ?></strong></div>
                    </div>
                    <div class="stk-ic ic-p"><i class="feather icon-package"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stk-tile">
                    <div class="txt">
                        <div class="t">Zero Stock</div>
                        <div class="v"><?php echo number_format((int) $k['zero_stock_lines']); ?></div>
                        <div class="q">Qty. <strong><?php echo number_format((float) $k['zero_stock_qty'], 2); ?></strong></div>
                    </div>
                    <div class="stk-ic ic-y"><i class="feather icon-box"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stk-tile">
                    <div class="txt">
                        <div class="t">Inward Stock</div>
                        <div class="v"><?php echo number_format((float) $k['inward_weight'], 3); ?></div>
                        <div class="q">Qty. <strong><?php echo number_format((float) $k['inward_qty'], 2); ?></strong></div>
                    </div>
                    <div class="stk-ic ic-g"><i class="feather icon-log-in"></i></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="stk-tile">
                    <div class="txt">
                        <div class="t">Outward Stock</div>
                        <div class="v"><?php echo number_format((float) $k['outward_weight'], 3); ?></div>
                        <div class="q">Qty. <strong><?php echo number_format((float) $k['outward_qty'], 2); ?></strong></div>
                    </div>
                    <div class="stk-ic ic-r"><i class="feather icon-log-out"></i></div>
                </div>
            </div>
            <?php
            $tileIc = ['ic-au', 'ic-ag', 'ic-dm', 'ic-im', 'ic-ot'];
            $tileIcon = ['icon-award', 'icon-layers', 'icon-circle', 'icon-heart', 'icon-settings'];
            foreach ($metalCards as $idx => $mc):
                $nm = htmlspecialchars((string) ($mc['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                $ic = $tileIc[$idx] ?? 'ic-p';
                $fi = $tileIcon[$idx] ?? 'icon-package';
            ?>
            <div class="col-12 col-md-4">
                <div class="stk-tile">
                    <div class="txt">
                        <div class="t"><?php echo $nm; ?></div>
                        <div class="v"><?php echo number_format((float) ($mc['w'] ?? 0), 3); ?></div>
                        <div class="q">Qty. <strong><?php echo number_format((float) ($mc['q'] ?? 0), 2); ?></strong></div>
                    </div>
                    <div class="stk-ic <?php echo $ic; ?>"><i class="feather <?php echo $fi; ?>"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="stk-panel mt-3">
            <h3>Karatwise Stock</h3>
            <?php if (empty($stk['karatwise'])): ?>
                <p class="text-muted small mb-0">No gold karat breakdown (link stock lines to product characteristics with carat).</p>
            <?php else:
                $kwList = $stk['karatwise'];
                $maxKw = 0.0001;
                foreach ($kwList as $x) {
                    $maxKw = max($maxKw, abs((float) ($x['weight'] ?? 0)));
                }
                foreach ($kwList as $kr):
                $w = (float) ($kr['weight'] ?? 0);
                $q = (float) ($kr['qty'] ?? 0);
                $pct = 0;
                if ($w > 0) {
                    $pct = min(100, (abs($w) / $maxKw) * 100);
                } elseif ($w < 0) {
                    $pct = 100;
                }
            ?>
            <div class="karat-row">
                <div class="kr-h">
                    <span class="kr-title"><?php echo htmlspecialchars((string) ($kr['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="kr-num"><?php echo number_format($w, 3); ?> / <?php echo number_format($q, 0); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo (float) $pct; ?>%;" aria-valuenow="<?php echo (float) $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="stk-panel">
            <h3>Metal Wise Stock</h3>
            <div class="small text-muted mb-2">Weight by metal per branch. Metal names use each branch’s metal master (<code>tbl_metal</code> matched to stock branch).</div>
            <?php if (empty($mcb['branch_labels'] ?? []) || empty($mcb['datasets'] ?? [])): ?>
                <p class="text-muted small mb-0">No stock rows for this scope.</p>
            <?php else: ?>
            <div class="stk-chart-h">
                <canvas id="stkMetalChart"></canvas>
            </div>
            <?php endif; ?>
            <?php
            $bwTable = $mcb['table_rows'] ?? [];
            if (!empty($bwTable)):
            ?>
            <div class="table-responsive stk-metal-bw-table">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Metal</th>
                            <th class="text-end">Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bwTable as $tr): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($tr['branch_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($tr['metal_display'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end" style="font-weight:600;color:#7c3aed;"><?php echo number_format((float) ($tr['weight'] ?? 0), 3); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <div class="stk-panel stk-low">
            <h3>Low Stock</h3>
            <div class="small text-muted mb-2">Products with at least one line at qty ≤ 1 or weight ≤ 0. Weight and qty are <strong>branch totals</strong> for that item (all stock lines). The note shows how many such low lines exist.</div>
            <?php if (empty($stk['low_stock'])): ?>
                <p class="text-muted small mb-0">No low-stock rows (qty ≤ 1 or weight ≤ 0).</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Item</th><th>Branch</th><th class="text-end">Weight</th><th class="text-end">Qty</th></tr></thead>
                        <tbody>
                        <?php foreach ($stk['low_stock'] as $ls):
                            $img = stk_stock_img_url($ls['images'] ?? '');
                            $lc = (int) ($ls['low_line_count'] ?? 1);
                            $pn = htmlspecialchars((string) ($ls['product_name'] ?? ''), ENT_QUOTES, 'UTF-8');
                            $bn = htmlspecialchars((string) ($ls['branch_name'] ?? '—'), ENT_QUOTES, 'UTF-8');
                            $tw = (float) ($ls['total_weight'] ?? $ls['current_weight'] ?? 0);
                            $tq = (float) ($ls['total_qty'] ?? $ls['current_qty'] ?? 0);
                            $lcNote = $lc > 1
                                ? ' <span class="text-muted" style="font-weight:500;font-size:12px;">(' . $lc . ' low lines)</span>'
                                : '';
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($img !== ''): ?>
                                            <img class="thumb" src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                                        <?php else: ?>
                                            <span class="stk-ic ic-p" style="width:36px;height:36px;font-size:16px;"><i class="feather icon-image"></i></span>
                                        <?php endif; ?>
                                        <span style="font-weight:600;color:#db2777;"><?php echo $pn . $lcNote; ?></span>
                                    </div>
                                </td>
                                <td class="text-muted" style="font-size:13px;"><?php echo $bn; ?></td>
                                <td class="w-cell"><?php echo number_format($tw, 3); ?></td>
                                <td class="q-cell"><?php echo number_format($tq, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="stk-foot">
    <span>Total value: <strong><?php echo number_format((float) ($stk['totals']['value'] ?? 0), 2); ?></strong> · Rows: <?php echo (int) ($stk['totals']['rows'] ?? 0); ?></span>
    <span><a href="stock-history.php" class="text-muted" style="text-decoration:none;">Details</a></span>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('stkMetalChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var branchLabels = <?php echo $branchChartLabelsJson; ?>;
    var datasets = <?php echo $branchChartDatasetsJson; ?>;
    if (!branchLabels.length || !datasets.length) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: branchLabels,
            datasets: datasets.map(function(ds) {
                ds.borderWidth = 1;
                ds.borderColor = 'rgba(255,255,255,0.6)';
                return ds;
            })
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        footer: function(items) {
                            if (!items || !items.length) return '';
                            var sum = 0;
                            items.forEach(function(it) { sum += parseFloat(it.parsed.y) || 0; });
                            return 'Branch total: ' + sum.toFixed(3);
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { maxRotation: 45, minRotation: 0, font: { size: 11 } }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.06)' },
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
})();
</script>
