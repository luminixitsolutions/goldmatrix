<?php
if (!isset($sd) || !is_array($sd)) {
    $sd = auragold_salesperson_dashboard_data($sp ?? 'ALL', $period ?? 'this_month');
}
$k = $sd['kpi'];
$labelsJson = json_encode($sd['chart_labels'] ?? [], JSON_UNESCAPED_UNICODE);
$valuesJson = json_encode($sd['chart_values'] ?? [], JSON_UNESCAPED_UNICODE);
$opts = $sd['salesperson_options'] ?? [];
$selSp = isset($sp) ? (string) $sp : 'ALL';
$selPeriod = isset($period) ? (string) $period : 'this_month';
?>
<style>
.sp-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
.sp-head h1 { font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0; }
.sp-badge {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 18px;
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
}
.sp-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px; margin-bottom: 18px; }
.sp-filters label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; display: block; margin-bottom: 4px; }
.sp-filters .form-select { min-width: 200px; border-radius: 10px; border: 1px solid #e2e8f0; }
.sp-kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 20px; }
.sp-kpi {
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.sp-kpi .ic {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    background: #fff;
}
.sp-kpi .lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .03em; }
.sp-kpi .num { font-size: 22px; font-weight: 800; color: #0f172a; }
.sp-kpi-p { background: linear-gradient(145deg, #f5f3ff 0%, #ede9fe 100%); }
.sp-kpi-p .ic { border: 4px solid #a78bfa; color: #6d28d9; }
.sp-kpi-o { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); }
.sp-kpi-o .ic { border: 4px solid #fbbf24; color: #b45309; }
.sp-kpi-b { background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%); }
.sp-kpi-b .ic { border: 4px solid #93c5fd; color: #1d4ed8; }
.sp-kpi-g { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); }
.sp-kpi-g .ic { border: 4px solid #6ee7b7; color: #047857; }
.sp-kpi-r { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); }
.sp-kpi-r .ic { border: 4px solid #fb7185; color: #be123c; }
.sp-chart-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    padding: 16px 18px 8px;
    margin-bottom: 20px;
}
.sp-chart-card h2 {
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .08em;
    color: #475569;
    margin: 0 0 12px;
}
.sp-chart-inner { height: 300px; position: relative; }
.sp-lb { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 6px 18px rgba(15, 23, 42, 0.05); }
.sp-lb-h {
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .06em;
    color: #5b21b6;
    background: #f5f3ff;
    border-bottom: 1px solid #ede9fe;
}
.sp-lb .table { margin: 0; font-size: 13px; }
.sp-lb thead th { background: #faf5ff; color: #6d28d9; font-size: 11px; text-transform: uppercase; border: none; }
.sp-empty { text-align: center; color: #94a3b8; padding: 36px 16px; font-size: 14px; }
</style>

<div class="sp-head">
    <div>
        <h1>Salesperson Dashboard</h1>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">Totals and chart respect <strong>Sales person</strong> and <strong>date</strong> filters. Leaderboards use all staff for the same period.</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="sp-badge">Salesperson Dashboard</span>
        <a class="btn btn-outline-secondary btn-sm" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
    </div>
</div>

<form class="sp-filters" method="get" action="dashboard-sales-person.php" id="spDashForm">
    <div>
        <label for="spSel">Sales Person</label>
        <select name="sp" id="spSel" class="form-select form-select-sm" onchange="document.getElementById('spDashForm').submit()">
            <option value="ALL"<?php echo strtoupper($selSp) === 'ALL' ? ' selected' : ''; ?>>ALL</option>
            <?php foreach ($opts as $name): ?>
                <option value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $selSp === $name ? ' selected' : ''; ?>>
                    <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="periodSel">Period</label>
        <select name="period" id="periodSel" class="form-select form-select-sm" onchange="document.getElementById('spDashForm').submit()">
            <option value="today"<?php echo $selPeriod === 'today' ? ' selected' : ''; ?>>Today</option>
            <option value="this_week"<?php echo $selPeriod === 'this_week' ? ' selected' : ''; ?>>This Week</option>
            <option value="this_month"<?php echo $selPeriod === 'this_month' ? ' selected' : ''; ?>>This Month</option>
            <option value="last_month"<?php echo $selPeriod === 'last_month' ? ' selected' : ''; ?>>Last Month</option>
        </select>
    </div>
</form>

<div class="sp-kpi-row">
    <div class="sp-kpi sp-kpi-p">
        <div class="ic"><i class="feather icon-package"></i></div>
        <div>
            <div class="lbl">Total Sales</div>
            <div class="num"><?php echo number_format($k['total_sales'], 2); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi-o">
        <div class="ic"><i class="feather icon-layers"></i></div>
        <div>
            <div class="lbl">Total Making</div>
            <div class="num"><?php echo number_format($k['total_making'], 2); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi-b">
        <div class="ic"><i class="feather icon-file-text"></i></div>
        <div>
            <div class="lbl">Total Invoices</div>
            <div class="num"><?php echo number_format((int) $k['total_invoices']); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi-g">
        <div class="ic"><i class="feather icon-trending-up"></i></div>
        <div>
            <div class="lbl">Today’s Sales</div>
            <div class="num"><?php echo number_format($k['today_sales'], 2); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi-r">
        <div class="ic"><i class="feather icon-scissors"></i></div>
        <div>
            <div class="lbl">Today’s Making</div>
            <div class="num"><?php echo number_format($k['today_making'], 2); ?></div>
        </div>
    </div>
</div>

<div class="sp-chart-card">
    <h2><?php
        $pk = $sd['bounds']['period_key'] ?? 'month';
        echo $pk === 'week' ? 'WEEKLY SALES PERFORMANCE' : ($pk === 'today' ? 'DAILY SALES PERFORMANCE' : 'MONTHLY SALES PERFORMANCE');
    ?></h2>
    <div class="sp-chart-inner">
        <canvas id="spSalesChart"></canvas>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="sp-lb h-100">
            <div class="sp-lb-h">TOP SALES PERFORMERS (LEADERBOARD)</div>
            <?php if (empty($sd['top_performers'])): ?>
                <div class="sp-empty">No records Available</div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Sr No.</th><th>Name</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php $i = 1; foreach ($sd['top_performers'] as $row): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end"><?php echo number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="sp-lb h-100">
            <div class="sp-lb-h">WEAK SALES PERFORMERS (NEED ATTENTION)</div>
            <?php if (empty($sd['weak_performers'])): ?>
                <div class="sp-empty">No records Available</div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead><tr><th>Sr No.</th><th>Name</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    <?php $j = 1; foreach ($sd['weak_performers'] as $row): ?>
                        <tr>
                            <td><?php echo $j++; ?></td>
                            <td><?php echo htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end"><?php echo number_format((float) ($row['amount'] ?? 0), 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('spSalesChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?php echo $labelsJson; ?>;
    var values = <?php echo $valuesJson; ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.12)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#7c3aed',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return 'Amt: ' + (ctx.parsed.y != null ? Number(ctx.parsed.y).toFixed(2) : '0.00');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.06)' },
                    ticks: { color: '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', maxRotation: 0 }
                }
            }
        }
    });
})();
</script>
