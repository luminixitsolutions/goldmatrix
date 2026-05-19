<?php
/**
 * JewelSteps-style wholesaler home (WHOLESALER type). Same layout as retailer dashboard.
 */
$wd = auragold_wholesaler_dashboard_kpis();
$labelsJson = json_encode($wd['chart_labels'], JSON_UNESCAPED_UNICODE);
$valuesJson = json_encode($wd['chart_values'], JSON_UNESCAPED_UNICODE);

if (!function_exists('auragold_fmt_money')) {
    function auragold_fmt_money($n) {
        return number_format((float) $n, 2);
    }
}

if (!function_exists('auragold_market_rate')) {
    function auragold_market_rate($row) {
        if (!$row || !is_array($row)) {
            return '—';
        }
        $v = $row['avg_metal_rate'] ?? $row['max_metal_rate'] ?? null;
        if ($v === null || $v === '') {
            return '—';
        }
        return number_format((float) $v, 2);
    }
}
?>
<style>
.retail-hero {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}
.retail-hero h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.retail-hero .sub {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}
.retail-badge {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 16px;
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
    white-space: nowrap;
}
.retail-kpi {
    border-radius: 14px;
    padding: 16px 18px;
    min-height: 108px;
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
    transition: transform .15s ease, box-shadow .15s ease;
}
.retail-kpi:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 26px rgba(15, 23, 42, 0.1);
}
.retail-kpi .icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.retail-kpi .lbl {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.retail-kpi .num {
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}
.retail-kpi .bal {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}
.retail-kpi .bal strong {
    color: #334155;
}
.retail-kpi .view-all {
    font-size: 11px;
    font-weight: 600;
    margin-top: 6px;
}
.retail-kpi .view-all a { color: #7c3aed; text-decoration: none; }
.retail-kpi .view-all a:hover { text-decoration: underline; }
.kpi-sales { background: linear-gradient(145deg, #f5f3ff 0%, #ede9fe 100%); }
.kpi-sales .icon-wrap { background: #ddd6fe; color: #6d28d9; }
.kpi-purchase { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); }
.kpi-purchase .icon-wrap { background: #fde68a; color: #b45309; }
.kpi-orders { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); }
.kpi-orders .icon-wrap { background: #a7f3d0; color: #047857; }
.kpi-cash { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); }
.kpi-cash .icon-wrap { background: #fecdd3; color: #be123c; }
.kpi-bank { background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%); }
.kpi-bank .icon-wrap { background: #bfdbfe; color: #1d4ed8; }
.kpi-card { background: linear-gradient(145deg, #fdf2f8 0%, #fce7f3 100%); }
.kpi-card .icon-wrap { background: #fbcfe8; color: #be185d; }
.retail-chart-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    padding: 18px 20px 12px;
    min-height: 360px;
}
.retail-chart-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}
.retail-chart-head h2 {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}
.retail-market {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    padding: 18px 20px;
    min-height: 360px;
}
.retail-market h2 {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 16px;
}
.retail-market-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 15px;
}
.retail-market-row:last-child { border-bottom: 0; }
.retail-market-row .k { color: #475569; font-weight: 600; }
.retail-market-row .v { color: #dc2626; font-weight: 700; font-size: 17px; }
.retail-foot {
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #94a3b8;
}
.retail-chart-canvas-wrap {
    position: relative;
    width: 100%;
    min-width: 0;
    height: 280px;
}
@media (max-width: 991.98px) {
    .retail-chart-card {
        min-height: 0;
    }
    .retail-market {
        min-height: 0;
    }
    .retail-chart-canvas-wrap {
        height: 240px;
    }
}
@media (max-width: 575.98px) {
    .retail-chart-canvas-wrap {
        height: 220px;
    }
}
@media (max-width: 767.98px) {
    .retail-dashboard-root {
        max-width: 100%;
        overflow-x: hidden;
    }
    .retail-hero {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
        margin-bottom: 16px;
    }
    .retail-hero h1 {
        font-size: 1.2rem;
    }
    .retail-hero .sub {
        font-size: 12px;
        line-height: 1.45;
    }
    .retail-hero-actions {
        width: 100%;
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: stretch;
        gap: 8px;
    }
    .retail-hero-actions .retail-badge,
    .retail-hero-actions .btn {
        flex: 1 1 0;
        min-width: 0;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        box-sizing: border-box;
    }
    .retail-hero-actions .retail-badge {
        white-space: normal;
        line-height: 1.25;
        padding: 8px 8px;
    }
    .retail-hero-actions .btn {
        white-space: normal;
        line-height: 1.25;
        padding: 8px 8px;
        font-size: 0.8rem;
    }
    .retail-kpi {
        min-height: 0;
        align-items: flex-start;
        padding: 14px 14px;
    }
    .retail-kpi .icon-wrap {
        width: 46px;
        height: 46px;
        font-size: 20px;
    }
    .retail-kpi .lbl {
        white-space: normal;
        line-height: 1.25;
        overflow-wrap: anywhere;
        hyphens: auto;
    }
    .retail-kpi .num {
        font-size: 1.15rem;
        word-break: break-word;
    }
    .retail-kpi > div:last-child {
        min-width: 0;
        flex: 1 1 auto;
    }
    .retail-chart-head {
        flex-direction: column;
        align-items: flex-start;
    }
    .retail-foot {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="retail-dashboard-root">
<div class="retail-hero">
    <div>
        <h1>Wholesaler Dashboard</h1>
        <div class="sub">Today’s sales, purchases, orders, collections, and market rates — customers with type <strong>Wholesaler</strong>.</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap retail-hero-actions">
        <span class="retail-badge">Wholesaler Dashboard</span>
        <a class="btn btn-outline-secondary btn-sm" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
    </div>
</div>

<?php if ((int) ($wd['customer_type_id'] ?? 0) <= 0): ?>
    <div class="alert alert-warning py-2">Customer type <code>WHOLESALER</code> not found in masters. KPIs use all customers until the type exists.</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-12 col-md-4 col-xl-2">
        <div class="retail-kpi kpi-sales h-100">
            <div class="icon-wrap"><i class="feather icon-shopping-cart"></i></div>
            <div>
                <div class="lbl">Today’s Sales</div>
                <div class="num"><?php echo auragold_fmt_money($wd['sales_today']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
        <div class="retail-kpi kpi-purchase h-100">
            <div class="icon-wrap"><i class="feather icon-plus-circle"></i></div>
            <div>
                <div class="lbl">Today’s Purchase</div>
                <div class="num"><?php echo auragold_fmt_money($wd['purchase_today']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
        <div class="retail-kpi kpi-orders h-100">
            <div class="icon-wrap"><i class="feather icon-package"></i></div>
            <div>
                <div class="lbl">Today’s Orders</div>
                <div class="num"><?php echo number_format((int) $wd['orders_today']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
        <div class="retail-kpi kpi-cash h-100">
            <div class="icon-wrap"><i class="feather icon-dollar-sign"></i></div>
            <div>
                <div class="lbl">Today’s Cash In Hand</div>
                <div class="num"><?php echo auragold_fmt_money($wd['cash_today']); ?></div>
                <div class="bal">Current balance: <strong><?php echo auragold_fmt_money($wd['balance_cash']); ?></strong></div>
                <div class="view-all"><a href="sale-invoice.php">View all</a></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
        <div class="retail-kpi kpi-bank h-100">
            <div class="icon-wrap"><i class="feather icon-briefcase"></i></div>
            <div>
                <div class="lbl">Today’s Bank</div>
                <div class="num"><?php echo auragold_fmt_money($wd['bank_today']); ?></div>
                <div class="bal">Current balance: <strong><?php echo auragold_fmt_money($wd['balance_bank']); ?></strong></div>
                <div class="view-all"><a href="sale-invoice.php">View all</a></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-xl-2">
        <div class="retail-kpi kpi-card h-100">
            <div class="icon-wrap"><i class="feather icon-credit-card"></i></div>
            <div>
                <div class="lbl">Today’s Card</div>
                <div class="num"><?php echo auragold_fmt_money($wd['card_today']); ?></div>
                <div class="bal">Current balance: <strong><?php echo auragold_fmt_money($wd['balance_card']); ?></strong></div>
                <div class="view-all"><a href="sale-invoice.php">View all</a></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 align-items-stretch">
    <div class="col-12 col-lg-8">
        <div class="retail-chart-card h-100">
            <div class="retail-chart-head">
                <h2>Sales overview</h2>
                <span class="badge bg-light text-dark border" style="font-weight:600;">This week</span>
            </div>
            <div class="retail-chart-canvas-wrap">
                <canvas id="wholesaleSalesChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="retail-market h-100">
            <h2>Market prices</h2>
            <div class="retail-market-row"><span class="k">18k</span><span class="v"><?php echo htmlspecialchars(auragold_market_rate($wd['market']['18k'] ?? null)); ?></span></div>
            <div class="retail-market-row"><span class="k">21k</span><span class="v"><?php echo htmlspecialchars(auragold_market_rate($wd['market']['21k'] ?? null)); ?></span></div>
            <div class="retail-market-row"><span class="k">22k</span><span class="v"><?php echo htmlspecialchars(auragold_market_rate($wd['market']['22k'] ?? null)); ?></span></div>
            <div class="retail-market-row"><span class="k">24k</span><span class="v"><?php echo htmlspecialchars(auragold_market_rate($wd['market']['24k'] ?? null)); ?></span></div>
            <p class="small text-muted mt-3 mb-0">Average metal rate on recent <strong>Gold</strong> sale lines. <a href="dashboard-gold-rates.php">Details</a></p>
        </div>
    </div>
</div>

<div class="retail-foot">
    <span>AuraGold · Wholesaler dashboard</span>
    <span><a href="account-ledger.php" class="text-muted" style="text-decoration:none;">Account ledger</a></span>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('wholesaleSalesChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?php echo $labelsJson; ?>;
    var values = <?php echo $valuesJson; ?>;
    var wholesaleChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.08)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#7c3aed',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.06)' },
                    ticks: { color: '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                }
            }
        }
    });
    function resizeWholesaleChart() {
        if (wholesaleChart) {
            wholesaleChart.resize();
        }
    }
    window.addEventListener('resize', function() {
        if (window.requestAnimationFrame) {
            window.requestAnimationFrame(resizeWholesaleChart);
        } else {
            resizeWholesaleChart();
        }
    });
})();
</script>
