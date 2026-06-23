<?php
if (!isset($sd) || !is_array($sd)) {
    $sd = auragold_salesperson_dashboard_data($sp ?? 'ALL', $period ?? 'this_month');
}
$selSp = isset($sp) ? (string) $sp : 'ALL';
$selPeriod = isset($period) ? (string) $period : 'this_month';
$sx = auragold_salesperson_dashboard_extras($selSp, $selPeriod);

$k = $sd['kpi'];
$labelsJson = json_encode($sd['chart_labels'] ?? [], JSON_UNESCAPED_UNICODE);
$valuesJson = json_encode($sd['chart_values'] ?? [], JSON_UNESCAPED_UNICODE);
$opts = $sd['salesperson_options'] ?? [];

$periodLabels = [
    'today' => 'Today',
    'this_week' => 'This Week',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
];
$periodLabel = $periodLabels[$selPeriod] ?? 'This Month';
$pk = $sd['bounds']['period_key'] ?? 'month';
$chartTitle = $pk === 'week' ? 'Weekly sales performance' : ($pk === 'today' ? 'Daily sales performance' : 'Monthly sales performance');

$spDisplay = strtoupper($selSp) === 'ALL' ? 'All sales team' : $selSp;

$greetingHour = (int) date('G');
if ($greetingHour < 12) {
    $greeting = 'Good morning';
} elseif ($greetingHour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}
$todayLabel = date('l, d M Y');

if (!function_exists('sp_fmt')) {
    function sp_fmt($n) {
        return number_format((float) $n, 2);
    }
}
if (!function_exists('sp_esc')) {
    function sp_esc($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
?>
<style>
.sp-dash {
    --sp-navy: #11294b;
    --sp-rose: #e11d48;
    --sp-rose-soft: #ffe4e6;
    --sp-coral: #f43f5e;
    --sp-gold: #c5a864;
    --sp-gold-light: #e8d5a8;
    --sp-violet: #7c3aed;
    --sp-emerald: #059669;
    --sp-sky: #0284c7;
    --sp-amber: #d97706;
    --sp-surface: #ffffff;
    --sp-muted: #64748b;
    --sp-border: rgba(17, 41, 75, 0.08);
    --sp-shadow: 0 4px 24px rgba(17, 41, 75, 0.08);
    --sp-shadow-hover: 0 12px 32px rgba(17, 41, 75, 0.12);
    max-width: 100%;
    padding: 4px 0 8px;
}

.sp-dash .sp-hero {
    background: linear-gradient(135deg, #3f0d1a 0%, #881337 30%, var(--sp-navy) 65%, #1e3a5f 100%);
    border-radius: 20px;
    padding: 22px 26px;
    margin-bottom: 18px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(136, 19, 55, 0.18);
    border: 1px solid rgba(251, 113, 133, 0.2);
}
.sp-dash .sp-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(251, 113, 133, 0.25) 0%, rgba(197, 168, 100, 0.12) 50%, transparent 70%);
    pointer-events: none;
}
.sp-dash .sp-hero::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #fb7185, var(--sp-gold), #fb7185, transparent);
}
.sp-dash .sp-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.sp-dash .sp-hero h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px;
    letter-spacing: -0.02em;
}
.sp-dash .sp-greeting { font-size: 13px; color: #fecdd3; opacity: 0.95; }
.sp-dash .sp-date { font-size: 12px; color: rgba(255,255,255,0.65); margin-top: 2px; }
.sp-dash .sp-hero-sub { font-size: 12px; color: rgba(255,255,255,0.75); margin-top: 6px; }
.sp-dash .sp-hero-sub strong { color: var(--sp-gold-light); }
.sp-dash .sp-hero-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sp-dash .btn-sp-rose {
    background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    padding: 9px 18px;
    border-radius: 10px;
    border: none;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35);
    transition: transform .15s, box-shadow .15s;
}
.sp-dash .btn-sp-rose:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(225, 29, 72, 0.45);
    color: #fff;
    text-decoration: none;
}
.sp-dash .btn-sp-outline {
    background: rgba(255,255,255,0.1);
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 9px 16px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.25);
    text-decoration: none;
    transition: background .15s;
}
.sp-dash .btn-sp-outline:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
    text-decoration: none;
}

.sp-dash .sp-filter-bar {
    background: var(--sp-surface);
    border-radius: 16px;
    border: 1px solid var(--sp-border);
    box-shadow: var(--sp-shadow);
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px;
    position: relative;
    overflow: hidden;
}
.sp-dash .sp-filter-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #fb7185, var(--sp-gold), #fb7185);
    opacity: 0.85;
}
.sp-dash .sp-filter-bar label {
    font-size: 11px;
    font-weight: 700;
    color: var(--sp-muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    display: block;
    margin-bottom: 6px;
}
.sp-dash .sp-filter-bar .form-select {
    min-width: 200px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    font-weight: 600;
    color: var(--sp-navy);
    padding: 8px 12px;
    box-shadow: 0 2px 6px rgba(17,41,75,0.04);
}
.sp-dash .sp-filter-bar .form-select:focus {
    border-color: #fb7185;
    box-shadow: 0 0 0 3px rgba(251,113,133,0.15);
}

.sp-dash .sp-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.sp-dash .sp-summary-item {
    border-radius: 16px;
    padding: 16px 18px;
    border: 1px solid transparent;
    box-shadow: var(--sp-shadow);
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
}
.sp-dash .sp-summary-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--sp-shadow-hover);
}
.sp-dash .sp-summary-item::after {
    content: '';
    position: absolute;
    top: -30px;
    right: -30px;
    width: 90px;
    height: 90px;
    border-radius: 50%;
    opacity: 0.35;
    pointer-events: none;
}
.sp-dash .sp-summary-item .lbl {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: 0.85;
}
.sp-dash .sp-summary-item .val {
    font-size: 1.35rem;
    font-weight: 800;
    margin-top: 6px;
    line-height: 1.15;
}
.sp-dash .sp-summary-item--sales {
    background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 55%, #fecdd3 100%);
    border-color: rgba(225, 29, 72, 0.2);
}
.sp-dash .sp-summary-item--sales::after { background: radial-gradient(circle, #fb7185 0%, transparent 70%); }
.sp-dash .sp-summary-item--sales .lbl { color: #be123c; }
.sp-dash .sp-summary-item--sales .val { color: #9f1239; }

.sp-dash .sp-summary-item--avg {
    background: linear-gradient(135deg, #fffbeb 0%, #ffedd5 55%, #fed7aa 100%);
    border-color: rgba(217, 119, 6, 0.2);
}
.sp-dash .sp-summary-item--avg::after { background: radial-gradient(circle, #fb923c 0%, transparent 70%); }
.sp-dash .sp-summary-item--avg .lbl { color: #c2410c; }
.sp-dash .sp-summary-item--avg .val { color: #7c2d12; }

.sp-dash .sp-summary-item--team {
    background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 55%, #ddd6fe 100%);
    border-color: rgba(124, 58, 237, 0.2);
}
.sp-dash .sp-summary-item--team::after { background: radial-gradient(circle, #a78bfa 0%, transparent 70%); }
.sp-dash .sp-summary-item--team .lbl { color: #6d28d9; }
.sp-dash .sp-summary-item--team .val { color: #5b21b6; }

.sp-dash .sp-quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 22px;
}
.sp-dash .sp-quick-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--sp-surface);
    border: 1px solid var(--sp-border);
    border-radius: 12px;
    text-decoration: none;
    color: var(--sp-navy);
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(17,41,75,0.04);
    transition: all .15s;
}
.sp-dash .sp-quick-link:hover {
    box-shadow: var(--sp-shadow);
    color: var(--sp-navy);
    text-decoration: none;
    transform: translateY(-2px);
}
.sp-dash .sp-quick-link .ql-icon {
    width: 38px;
    height: 38px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(17,41,75,0.08);
}
.sp-dash .sp-quick-link--invoice { border-color: rgba(225,29,72,0.12); }
.sp-dash .sp-quick-link--invoice:hover { border-color: rgba(225,29,72,0.35); background: #fff1f2; }
.sp-dash .sp-quick-link--invoice .ql-icon { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; }
.sp-dash .sp-quick-link--pos { border-color: rgba(197,168,100,0.25); }
.sp-dash .sp-quick-link--pos:hover { border-color: rgba(197,168,100,0.45); background: #fffbeb; }
.sp-dash .sp-quick-link--pos .ql-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.sp-dash .sp-quick-link--order { border-color: rgba(5,150,105,0.12); }
.sp-dash .sp-quick-link--order:hover { border-color: rgba(5,150,105,0.35); background: #ecfdf5; }
.sp-dash .sp-quick-link--order .ql-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
.sp-dash .sp-quick-link--ledger { border-color: rgba(2,132,199,0.12); }
.sp-dash .sp-quick-link--ledger:hover { border-color: rgba(2,132,199,0.35); background: #f0f9ff; }
.sp-dash .sp-quick-link--ledger .ql-icon { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0369a1; }

.sp-dash .sp-kpi-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
.sp-dash .sp-kpi {
    border-radius: 16px;
    padding: 16px;
    border: 1px solid transparent;
    box-shadow: var(--sp-shadow);
    transition: transform .18s ease, box-shadow .18s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 12px;
}
.sp-dash .sp-kpi::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    border-radius: 16px 0 0 16px;
}
.sp-dash .sp-kpi:hover {
    transform: translateY(-3px);
    box-shadow: var(--sp-shadow-hover);
}
.sp-dash .sp-kpi .ic {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.sp-dash .sp-kpi .lbl {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--sp-muted);
    line-height: 1.3;
}
.sp-dash .sp-kpi .num {
    font-size: 1.15rem;
    font-weight: 800;
    margin-top: 3px;
    line-height: 1.2;
}

.sp-dash .sp-kpi--sales { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); border-color: rgba(225,29,72,0.15); }
.sp-dash .sp-kpi--sales::before { background: linear-gradient(180deg, #fb7185, #e11d48); }
.sp-dash .sp-kpi--sales .ic { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; }
.sp-dash .sp-kpi--sales .num { color: #9f1239; }

.sp-dash .sp-kpi--making { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); border-color: rgba(217,119,6,0.15); }
.sp-dash .sp-kpi--making::before { background: linear-gradient(180deg, #fbbf24, #d97706); }
.sp-dash .sp-kpi--making .ic { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
.sp-dash .sp-kpi--making .num { color: #92400e; }

.sp-dash .sp-kpi--invoices { background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%); border-color: rgba(37,99,235,0.15); }
.sp-dash .sp-kpi--invoices::before { background: linear-gradient(180deg, #60a5fa, #2563eb); }
.sp-dash .sp-kpi--invoices .ic { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
.sp-dash .sp-kpi--invoices .num { color: #1e3a8a; }

.sp-dash .sp-kpi--today { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); border-color: rgba(5,150,105,0.15); }
.sp-dash .sp-kpi--today::before { background: linear-gradient(180deg, #34d399, #059669); }
.sp-dash .sp-kpi--today .ic { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
.sp-dash .sp-kpi--today .num { color: #065f46; }

.sp-dash .sp-kpi--tmaking { background: linear-gradient(145deg, #faf5ff 0%, #f3e8ff 100%); border-color: rgba(124,58,237,0.15); }
.sp-dash .sp-kpi--tmaking::before { background: linear-gradient(180deg, #a78bfa, #7c3aed); }
.sp-dash .sp-kpi--tmaking .ic { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
.sp-dash .sp-kpi--tmaking .num { color: #5b21b6; }

.sp-dash .sp-panel {
    background: var(--sp-surface);
    border-radius: 18px;
    border: 1px solid var(--sp-border);
    box-shadow: var(--sp-shadow);
    height: 100%;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.sp-dash .sp-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--sp-gold), var(--sp-gold-light), var(--sp-gold));
    opacity: 0.85;
}
.sp-dash .sp-panel--chart::before { background: linear-gradient(90deg, #e11d48, var(--sp-gold), #7c3aed); }
.sp-dash .sp-panel--top::before { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.sp-dash .sp-panel--weak::before { background: linear-gradient(90deg, #64748b, #94a3b8); }
.sp-dash .sp-panel--recent::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }

.sp-dash .sp-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 12px;
}
.sp-dash .sp-panel-head h2 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--sp-navy);
    margin: 0;
}
.sp-dash .sp-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    color: var(--sp-muted);
    border: 1px solid var(--sp-border);
}
.sp-dash .sp-badge--rose {
    background: linear-gradient(135deg, #ffe4e6, #fecdd3);
    color: #9f1239;
    border-color: rgba(225,29,72,0.2);
}
.sp-dash .sp-badge--link {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4338ca;
    border-color: rgba(79,70,229,0.2);
    text-decoration: none;
}
.sp-dash .sp-panel-body { flex: 1; padding: 0 20px 18px; overflow: auto; }
.sp-dash .sp-chart-wrap { position: relative; height: 280px; padding: 0 12px 16px; }

.sp-dash .sp-table { width: 100%; font-size: 13px; margin: 0; }
.sp-dash .sp-table thead th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--sp-muted);
    border-bottom: 2px solid #f1f5f9;
    padding: 8px 10px;
    background: transparent;
}
.sp-dash .sp-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    color: #334155;
}
.sp-dash .sp-table tbody tr:hover td { background: #fafbfc; }
.sp-dash .sp-table tbody tr:last-child td { border-bottom: 0; }
.sp-dash .sp-table .amt { font-weight: 700; color: var(--sp-navy); text-align: right; white-space: nowrap; }
.sp-dash .sp-table .link-cell a { color: var(--sp-navy); font-weight: 600; text-decoration: none; }
.sp-dash .sp-table .link-cell a:hover { color: var(--sp-rose); }

.sp-dash .sp-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    background: #f1f5f9;
    color: #64748b;
}
.sp-dash .sp-rank--1 { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; box-shadow: 0 2px 8px rgba(217,119,6,0.2); }
.sp-dash .sp-rank--2 { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; }
.sp-dash .sp-rank--3 { background: linear-gradient(135deg, #ffedd5, #fed7aa); color: #9a3412; }

.sp-dash .sp-performer-name {
    font-weight: 600;
    color: var(--sp-navy);
}
.sp-dash .sp-performer-name a {
    color: inherit;
    text-decoration: none;
}
.sp-dash .sp-performer-name a:hover { color: var(--sp-rose); }

.sp-dash .sp-empty {
    text-align: center;
    padding: 32px 16px;
    color: var(--sp-muted);
    font-size: 13px;
}
.sp-dash .sp-foot {
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px solid var(--sp-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #94a3b8;
}
.sp-dash .sp-foot a { color: var(--sp-muted); text-decoration: none; }
.sp-dash .sp-foot a:hover { color: var(--sp-rose); }

@media (max-width: 1399.98px) {
    .sp-dash .sp-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 991.98px) {
    .sp-dash .sp-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .sp-dash .sp-quick-links { grid-template-columns: repeat(2, 1fr); }
    .sp-dash .sp-summary-strip { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .sp-dash .sp-hero { padding: 18px 16px; }
    .sp-dash .sp-hero h1 { font-size: 1.2rem; }
    .sp-dash .sp-hero-inner { flex-direction: column; align-items: stretch; }
    .sp-dash .sp-hero-actions { width: 100%; }
    .sp-dash .sp-hero-actions .btn-sp-rose,
    .sp-dash .sp-hero-actions .btn-sp-outline { flex: 1; text-align: center; justify-content: center; }
    .sp-dash .sp-filter-bar { flex-direction: column; align-items: stretch; }
    .sp-dash .sp-filter-bar .form-select { min-width: 0; width: 100%; }
    .sp-dash .sp-kpi-grid { grid-template-columns: 1fr; }
    .sp-dash .sp-quick-links { grid-template-columns: 1fr; }
    .sp-dash .sp-chart-wrap { height: 220px; }
}
</style>

<div class="sp-dash">

<div class="sp-hero">
    <div class="sp-hero-inner">
        <div>
            <div class="sp-greeting"><?php echo sp_esc($greeting); ?></div>
            <h1>Salesperson Dashboard</h1>
            <div class="sp-date"><?php echo sp_esc($todayLabel); ?></div>
            <div class="sp-hero-sub">Viewing <strong><?php echo sp_esc($spDisplay); ?></strong> · Period: <strong><?php echo sp_esc($periodLabel); ?></strong></div>
        </div>
        <div class="sp-hero-actions">
            <a class="btn-sp-rose" href="pos-sale-invoice.php"><i class="feather icon-shopping-bag"></i> Open POS</a>
            <a class="btn-sp-outline" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
        </div>
    </div>
</div>

<form class="sp-filter-bar" method="get" action="dashboard-sales-person.php" id="spDashForm">
    <div>
        <label for="spSel">Sales person</label>
        <select name="sp" id="spSel" class="form-select form-select-sm" onchange="document.getElementById('spDashForm').submit()">
            <option value="ALL"<?php echo strtoupper($selSp) === 'ALL' ? ' selected' : ''; ?>>All team</option>
            <?php foreach ($opts as $name): ?>
                <option value="<?php echo sp_esc($name); ?>"<?php echo $selSp === $name ? ' selected' : ''; ?>>
                    <?php echo sp_esc($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label for="periodSel">Period</label>
        <select name="period" id="periodSel" class="form-select form-select-sm" onchange="document.getElementById('spDashForm').submit()">
            <option value="today"<?php echo $selPeriod === 'today' ? ' selected' : ''; ?>>Today</option>
            <option value="this_week"<?php echo $selPeriod === 'this_week' ? ' selected' : ''; ?>>This week</option>
            <option value="this_month"<?php echo $selPeriod === 'this_month' ? ' selected' : ''; ?>>This month</option>
            <option value="last_month"<?php echo $selPeriod === 'last_month' ? ' selected' : ''; ?>>Last month</option>
        </select>
    </div>
</form>

<div class="sp-summary-strip">
    <div class="sp-summary-item sp-summary-item--sales">
        <div class="lbl"><?php echo sp_esc($periodLabel); ?> sales</div>
        <div class="val"><?php echo sp_fmt($k['total_sales']); ?></div>
    </div>
    <div class="sp-summary-item sp-summary-item--avg">
        <div class="lbl">Avg. invoice value</div>
        <div class="val"><?php echo sp_fmt($sx['avg_ticket']); ?></div>
    </div>
    <div class="sp-summary-item sp-summary-item--team">
        <div class="lbl">Active sales team</div>
        <div class="val"><?php echo number_format((int) $sx['team_count']); ?></div>
    </div>
</div>

<div class="sp-quick-links">
    <a class="sp-quick-link sp-quick-link--invoice" href="sale-invoice.php"><span class="ql-icon"><i class="feather icon-file-text"></i></span> Sale Invoice</a>
    <a class="sp-quick-link sp-quick-link--pos" href="pos-sale-invoice.php"><span class="ql-icon"><i class="feather icon-shopping-bag"></i></span> POS</a>
    <a class="sp-quick-link sp-quick-link--order" href="sale-order.php"><span class="ql-icon"><i class="feather icon-shopping-cart"></i></span> Sale Order</a>
    <a class="sp-quick-link sp-quick-link--ledger" href="account-ledger.php"><span class="ql-icon"><i class="feather icon-book"></i></span> Ledger</a>
</div>

<div class="sp-kpi-grid">
    <div class="sp-kpi sp-kpi--sales">
        <div class="ic"><i class="feather icon-trending-up"></i></div>
        <div>
            <div class="lbl">Total sales</div>
            <div class="num"><?php echo sp_fmt($k['total_sales']); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi--making">
        <div class="ic"><i class="feather icon-layers"></i></div>
        <div>
            <div class="lbl">Total making</div>
            <div class="num"><?php echo sp_fmt($k['total_making']); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi--invoices">
        <div class="ic"><i class="feather icon-file-text"></i></div>
        <div>
            <div class="lbl">Invoices</div>
            <div class="num"><?php echo number_format((int) $k['total_invoices']); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi--today">
        <div class="ic"><i class="feather icon-zap"></i></div>
        <div>
            <div class="lbl">Today’s sales</div>
            <div class="num"><?php echo sp_fmt($k['today_sales']); ?></div>
        </div>
    </div>
    <div class="sp-kpi sp-kpi--tmaking">
        <div class="ic"><i class="feather icon-scissors"></i></div>
        <div>
            <div class="lbl">Today’s making</div>
            <div class="num"><?php echo sp_fmt($k['today_making']); ?></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="sp-panel sp-panel--chart">
            <div class="sp-panel-head">
                <h2><i class="feather icon-activity" style="color:var(--sp-rose);margin-right:6px;"></i> <?php echo sp_esc($chartTitle); ?></h2>
                <span class="sp-badge sp-badge--rose"><?php echo sp_esc($periodLabel); ?></span>
            </div>
            <div class="sp-chart-wrap">
                <canvas id="spSalesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="sp-panel sp-panel--top">
            <div class="sp-panel-head">
                <h2><i class="feather icon-award" style="color:#d97706;margin-right:6px;"></i> Top performers</h2>
                <span class="sp-badge" style="background:#fef3c7;color:#92400e;border-color:rgba(217,119,6,0.2);">Leaderboard</span>
            </div>
            <div class="sp-panel-body">
                <?php if (!empty($sd['top_performers'])): ?>
                <div class="table-responsive">
                    <table class="sp-table">
                        <thead><tr><th>#</th><th>Name</th><th class="text-right">Sales</th></tr></thead>
                        <tbody>
                        <?php $i = 1; foreach ($sd['top_performers'] as $row):
                            $rankClass = $i <= 3 ? ' sp-rank--' . $i : '';
                            $spName = (string) ($row['name'] ?? '');
                            $spLink = 'dashboard-sales-person.php?sp=' . rawurlencode($spName) . '&period=' . rawurlencode($selPeriod);
                        ?>
                            <tr>
                                <td><span class="sp-rank<?php echo sp_esc($rankClass); ?>"><?php echo $i++; ?></span></td>
                                <td class="sp-performer-name"><a href="<?php echo sp_esc($spLink); ?>"><?php echo sp_esc($spName); ?></a></td>
                                <td class="amt"><?php echo sp_fmt($row['amount'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="sp-empty">No sales data for this period.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="sp-panel sp-panel--weak">
            <div class="sp-panel-head">
                <h2><i class="feather icon-alert-circle" style="color:#64748b;margin-right:6px;"></i> Need attention</h2>
                <span class="sp-badge">Lowest sales</span>
            </div>
            <div class="sp-panel-body">
                <?php if (!empty($sd['weak_performers'])): ?>
                <div class="table-responsive">
                    <table class="sp-table">
                        <thead><tr><th>#</th><th>Name</th><th class="text-right">Sales</th></tr></thead>
                        <tbody>
                        <?php $j = 1; foreach ($sd['weak_performers'] as $row):
                            $spName = (string) ($row['name'] ?? '');
                            $spLink = 'dashboard-sales-person.php?sp=' . rawurlencode($spName) . '&period=' . rawurlencode($selPeriod);
                        ?>
                            <tr>
                                <td><span class="sp-rank"><?php echo $j++; ?></span></td>
                                <td class="sp-performer-name"><a href="<?php echo sp_esc($spLink); ?>"><?php echo sp_esc($spName); ?></a></td>
                                <td class="amt"><?php echo sp_fmt($row['amount'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="sp-empty">No performers to compare yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="sp-panel sp-panel--recent">
            <div class="sp-panel-head">
                <h2><i class="feather icon-file-text" style="color:var(--sp-sky);margin-right:6px;"></i> Recent sale invoices</h2>
                <a href="sale-invoice.php" class="sp-badge sp-badge--link">View all</a>
            </div>
            <div class="sp-panel-body">
                <?php if (!empty($sx['recent_invoices'])): ?>
                <div class="table-responsive">
                    <table class="sp-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Sales person</th>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sx['recent_invoices'] as $inv): ?>
                            <tr>
                                <td class="link-cell"><a href="sale-invoice.php?id=<?php echo (int) ($inv['id'] ?? 0); ?>"><?php echo sp_esc($inv['invoice_no'] ?? '#' . ($inv['id'] ?? '')); ?></a></td>
                                <td><?php echo sp_esc($inv['customer_name'] ?? '—'); ?></td>
                                <td><?php echo sp_esc($inv['sales_person'] ?? '—'); ?></td>
                                <td><?php echo sp_esc($inv['invoice_date'] ?? ''); ?></td>
                                <td class="amt"><?php echo sp_fmt($inv['grand_total'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="sp-empty">No invoices in this period. <a href="sale-invoice.php">Create sale invoice</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="sp-foot">
    <span>Gold Matrix · Salesperson dashboard · <?php echo sp_esc($periodLabel); ?> · <?php echo sp_esc($spDisplay); ?></span>
    <span><a href="sale-invoice.php">Sale invoices</a> · <a href="pos-sale-invoice.php">POS</a></span>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('spSalesChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?php echo $labelsJson; ?>;
    var values = <?php echo $valuesJson; ?>;
    var rose = '#e11d48';
    var gold = '#c5a864';
    var navy = '#11294b';
    var grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
    grad.addColorStop(0, 'rgba(225, 29, 72, 0.35)');
    grad.addColorStop(0.5, 'rgba(197, 168, 100, 0.2)');
    grad.addColorStop(1, 'rgba(124, 58, 237, 0.04)');
    var spChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: rose,
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#fff',
                pointBorderColor: gold,
                pointBorderWidth: 2,
                borderWidth: 2.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: navy,
                    titleColor: gold,
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(c) {
                            return 'Sales: ' + (c.parsed.y != null ? Number(c.parsed.y).toFixed(2) : '0.00');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(17,41,75,0.06)' },
                    ticks: { color: '#64748b', font: { size: 11 } },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b', font: { size: 11 }, maxRotation: 0 },
                    border: { display: false }
                }
            }
        }
    });
    window.addEventListener('resize', function() {
        if (spChart) spChart.resize();
    });
})();
</script>
