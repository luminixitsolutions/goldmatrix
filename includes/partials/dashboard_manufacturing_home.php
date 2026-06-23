<?php
/**
 * Manufacturing / jobwork home dashboard. Expects dashboard_helpers loaded.
 */
if (!isset($mfg) || !is_array($mfg)) {
    $mfg = auragold_manufacturing_dashboard();
}
$mx = auragold_manufacturing_dashboard_extras();
$k = $mfg['kpi'];

$wsLabels = [];
$wsValues = [];
foreach ($mfg['workstation_rows'] ?? [] as $wr) {
    $wsLabels[] = (string) ($wr['dept_label'] ?? $wr['dept_key'] ?? '—');
    $wsValues[] = (int) ($wr['order_count'] ?? 0);
}
$wsLabelsJson = json_encode($wsLabels, JSON_UNESCAPED_UNICODE);
$wsValuesJson = json_encode($wsValues, JSON_UNESCAPED_UNICODE);

if (!function_exists('mfg_esc')) {
    function mfg_esc($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

$greetingHour = (int) date('G');
if ($greetingHour < 12) {
    $greeting = 'Good morning';
} elseif ($greetingHour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}
$todayLabel = date('l, d M Y');
?>
<style>
.mfg-dash {
    --mfg-navy: #11294b;
    --mfg-violet: #6d28d9;
    --mfg-violet-soft: #ede9fe;
    --mfg-indigo: #4338ca;
    --mfg-gold: #c5a864;
    --mfg-gold-light: #e8d5a8;
    --mfg-emerald: #059669;
    --mfg-amber: #d97706;
    --mfg-rose: #e11d48;
    --mfg-sky: #0284c7;
    --mfg-surface: #ffffff;
    --mfg-muted: #64748b;
    --mfg-border: rgba(17, 41, 75, 0.08);
    --mfg-shadow: 0 4px 24px rgba(17, 41, 75, 0.08);
    --mfg-shadow-hover: 0 12px 32px rgba(17, 41, 75, 0.12);
    max-width: 100%;
    padding: 4px 0 8px;
}

.mfg-dash .mfg-hero {
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 35%, var(--mfg-navy) 70%, #1e3a5f 100%);
    border-radius: 20px;
    padding: 22px 26px;
    margin-bottom: 22px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(49, 46, 129, 0.2);
    border: 1px solid rgba(167, 139, 250, 0.2);
}
.mfg-dash .mfg-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(167, 139, 250, 0.28) 0%, rgba(197, 168, 100, 0.1) 50%, transparent 70%);
    pointer-events: none;
}
.mfg-dash .mfg-hero::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #a78bfa, var(--mfg-gold), #a78bfa, transparent);
}
.mfg-dash .mfg-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.mfg-dash .mfg-hero h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px;
    letter-spacing: -0.02em;
}
.mfg-dash .mfg-greeting {
    font-size: 13px;
    color: #c4b5fd;
    opacity: 0.95;
}
.mfg-dash .mfg-date {
    font-size: 12px;
    color: rgba(255,255,255,0.65);
    margin-top: 2px;
}
.mfg-dash .mfg-hero-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.mfg-dash .btn-mfg-violet {
    background: linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    padding: 9px 18px;
    border-radius: 10px;
    border: none;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
    transition: transform .15s, box-shadow .15s;
}
.mfg-dash .btn-mfg-violet:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(124, 58, 237, 0.45);
    color: #fff;
    text-decoration: none;
}
.mfg-dash .btn-mfg-outline {
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
.mfg-dash .btn-mfg-outline:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
    text-decoration: none;
}

.mfg-dash .mfg-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.mfg-dash .mfg-summary-item {
    border-radius: 16px;
    padding: 16px 18px;
    border: 1px solid transparent;
    box-shadow: var(--mfg-shadow);
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
}
.mfg-dash .mfg-summary-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--mfg-shadow-hover);
}
.mfg-dash .mfg-summary-item::after {
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
.mfg-dash .mfg-summary-item .lbl {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: 0.85;
}
.mfg-dash .mfg-summary-item .val {
    font-size: 1.4rem;
    font-weight: 800;
    margin-top: 6px;
    line-height: 1.15;
}
.mfg-dash .mfg-summary-item--jobs {
    background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 55%, #ddd6fe 100%);
    border-color: rgba(124, 58, 237, 0.2);
}
.mfg-dash .mfg-summary-item--jobs::after { background: radial-gradient(circle, #a78bfa 0%, transparent 70%); }
.mfg-dash .mfg-summary-item--jobs .lbl { color: #6d28d9; }
.mfg-dash .mfg-summary-item--jobs .val { color: #5b21b6; }

.mfg-dash .mfg-summary-item--orders {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 55%, #bfdbfe 100%);
    border-color: rgba(37, 99, 235, 0.2);
}
.mfg-dash .mfg-summary-item--orders::after { background: radial-gradient(circle, #60a5fa 0%, transparent 70%); }
.mfg-dash .mfg-summary-item--orders .lbl { color: #2563eb; }
.mfg-dash .mfg-summary-item--orders .val { color: #1e3a8a; }

.mfg-dash .mfg-summary-item--due {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 55%, #fed7aa 100%);
    border-color: rgba(217, 119, 6, 0.2);
}
.mfg-dash .mfg-summary-item--due::after { background: radial-gradient(circle, #fb923c 0%, transparent 70%); }
.mfg-dash .mfg-summary-item--due .lbl { color: #c2410c; }
.mfg-dash .mfg-summary-item--due .val { color: #7c2d12; }

.mfg-dash .mfg-quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 22px;
}
.mfg-dash .mfg-quick-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--mfg-surface);
    border: 1px solid var(--mfg-border);
    border-radius: 12px;
    text-decoration: none;
    color: var(--mfg-navy);
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(17,41,75,0.04);
    transition: all .15s;
}
.mfg-dash .mfg-quick-link:hover {
    box-shadow: var(--mfg-shadow);
    color: var(--mfg-navy);
    text-decoration: none;
    transform: translateY(-2px);
}
.mfg-dash .mfg-quick-link .ql-icon {
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
.mfg-dash .mfg-quick-link--jobwork { border-color: rgba(124,58,237,0.12); }
.mfg-dash .mfg-quick-link--jobwork:hover { border-color: rgba(124,58,237,0.35); background: #faf5ff; }
.mfg-dash .mfg-quick-link--jobwork .ql-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
.mfg-dash .mfg-quick-link--process { border-color: rgba(5,150,105,0.12); }
.mfg-dash .mfg-quick-link--process:hover { border-color: rgba(5,150,105,0.35); background: #ecfdf5; }
.mfg-dash .mfg-quick-link--process .ql-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
.mfg-dash .mfg-quick-link--queue { border-color: rgba(217,119,6,0.12); }
.mfg-dash .mfg-quick-link--queue:hover { border-color: rgba(217,119,6,0.35); background: #fffbeb; }
.mfg-dash .mfg-quick-link--queue .ql-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
.mfg-dash .mfg-quick-link--sale { border-color: rgba(2,132,199,0.12); }
.mfg-dash .mfg-quick-link--sale:hover { border-color: rgba(2,132,199,0.35); background: #f0f9ff; }
.mfg-dash .mfg-quick-link--sale .ql-icon { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0369a1; }

.mfg-dash .mfg-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
.mfg-dash .mfg-kpi {
    border-radius: 16px;
    padding: 16px;
    border: 1px solid transparent;
    box-shadow: var(--mfg-shadow);
    transition: transform .18s ease, box-shadow .18s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 14px;
}
.mfg-dash .mfg-kpi::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    border-radius: 16px 0 0 16px;
}
.mfg-dash .mfg-kpi:hover {
    transform: translateY(-3px);
    box-shadow: var(--mfg-shadow-hover);
}
.mfg-dash .mfg-kpi .gauge {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 20px;
    flex-shrink: 0;
}
.mfg-dash .mfg-kpi .lbl {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--mfg-muted);
    line-height: 1.3;
}
.mfg-dash .mfg-kpi .sub {
    font-size: 10px;
    color: #94a3b8;
    margin-top: 3px;
    line-height: 1.3;
}

.mfg-dash .mfg-kpi--ip { background: linear-gradient(145deg, #faf5ff 0%, #f3e8ff 100%); border-color: rgba(124,58,237,0.15); }
.mfg-dash .mfg-kpi--ip::before { background: linear-gradient(180deg, #a78bfa, #7c3aed); }
.mfg-dash .mfg-kpi--ip .gauge { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #5b21b6; box-shadow: 0 4px 12px rgba(124,58,237,0.15); }

.mfg-dash .mfg-kpi--del { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); border-color: rgba(225,29,72,0.15); }
.mfg-dash .mfg-kpi--del::before { background: linear-gradient(180deg, #fb7185, #e11d48); }
.mfg-dash .mfg-kpi--del .gauge { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; box-shadow: 0 4px 12px rgba(225,29,72,0.15); }

.mfg-dash .mfg-kpi--hold { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); border-color: rgba(217,119,6,0.15); }
.mfg-dash .mfg-kpi--hold::before { background: linear-gradient(180deg, #fbbf24, #d97706); }
.mfg-dash .mfg-kpi--hold .gauge { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; box-shadow: 0 4px 12px rgba(217,119,6,0.15); }

.mfg-dash .mfg-kpi--ni { background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%); border-color: rgba(100,116,139,0.15); }
.mfg-dash .mfg-kpi--ni::before { background: linear-gradient(180deg, #94a3b8, #64748b); }
.mfg-dash .mfg-kpi--ni .gauge { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; box-shadow: 0 4px 12px rgba(100,116,139,0.12); }

.mfg-dash .mfg-panel {
    background: var(--mfg-surface);
    border-radius: 18px;
    border: 1px solid var(--mfg-border);
    box-shadow: var(--mfg-shadow);
    height: 100%;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.mfg-dash .mfg-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--mfg-gold), var(--mfg-gold-light), var(--mfg-gold));
    opacity: 0.85;
}
.mfg-dash .mfg-panel--chart::before { background: linear-gradient(90deg, #7c3aed, var(--mfg-gold), #059669); }
.mfg-dash .mfg-panel--progress::before { background: linear-gradient(90deg, #7c3aed, #a78bfa); }
.mfg-dash .mfg-panel--workstation::before { background: linear-gradient(90deg, #2563eb, #60a5fa); }
.mfg-dash .mfg-panel--delayed::before { background: linear-gradient(90deg, #e11d48, #fb7185); }
.mfg-dash .mfg-panel--hold::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.mfg-dash .mfg-panel--recent::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.mfg-dash .mfg-panel--done::before { background: linear-gradient(90deg, #059669, #34d399); }

.mfg-dash .mfg-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 12px;
}
.mfg-dash .mfg-panel-head h2 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--mfg-navy);
    margin: 0;
}
.mfg-dash .mfg-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    color: var(--mfg-muted);
    border: 1px solid var(--mfg-border);
}
.mfg-dash .mfg-badge--violet {
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    color: #5b21b6;
    border-color: rgba(124,58,237,0.2);
}
.mfg-dash .mfg-badge--link {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4338ca;
    border-color: rgba(79,70,229,0.2);
    text-decoration: none;
}
.mfg-dash .mfg-panel-body {
    flex: 1;
    padding: 0 20px 18px;
    overflow: auto;
}
.mfg-dash .mfg-chart-wrap {
    position: relative;
    height: 260px;
    padding: 0 12px 12px;
}

.mfg-dash .mfg-table {
    width: 100%;
    font-size: 13px;
    margin: 0;
}
.mfg-dash .mfg-table thead th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--mfg-muted);
    border-bottom: 2px solid #f1f5f9;
    padding: 8px 10px;
    background: transparent;
    white-space: nowrap;
}
.mfg-dash .mfg-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    color: #334155;
}
.mfg-dash .mfg-table tbody tr:hover td { background: #fafbfc; }
.mfg-dash .mfg-table tbody tr:last-child td { border-bottom: 0; }
.mfg-dash .mfg-table .link-cell a {
    color: var(--mfg-navy);
    font-weight: 600;
    text-decoration: none;
}
.mfg-dash .mfg-table .link-cell a:hover { color: var(--mfg-violet); }
.mfg-dash .mfg-table .due-over { color: #be123c; font-weight: 700; }
.mfg-dash .mfg-table .due-soon { color: #b45309; font-weight: 600; }
.mfg-dash .mfg-table .cnt { text-align: right; font-weight: 700; color: var(--mfg-navy); }

.mfg-dash .mfg-status {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 4px 9px;
    border-radius: 20px;
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    color: #5b21b6;
    border: 1px solid rgba(124,58,237,0.2);
}
.mfg-dash .mfg-status--hold {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border-color: rgba(217,119,6,0.2);
}
.mfg-dash .mfg-status--done {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    border-color: rgba(5,150,105,0.2);
}
.mfg-dash .mfg-status--late {
    background: linear-gradient(135deg, #ffe4e6, #fecdd3);
    color: #9f1239;
    border-color: rgba(225,29,72,0.2);
}
.mfg-dash .mfg-empty {
    text-align: center;
    padding: 32px 16px;
    color: var(--mfg-muted);
    font-size: 13px;
}
.mfg-dash .mfg-ws-foot {
    padding: 10px 20px 16px;
    font-size: 12px;
    color: var(--mfg-muted);
    border-top: 1px solid #f8fafc;
}
.mfg-dash .mfg-foot {
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px solid var(--mfg-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #94a3b8;
}
.mfg-dash .mfg-foot a { color: var(--mfg-muted); text-decoration: none; }
.mfg-dash .mfg-foot a:hover { color: var(--mfg-violet); }

@media (max-width: 991.98px) {
    .mfg-dash .mfg-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .mfg-dash .mfg-quick-links { grid-template-columns: repeat(2, 1fr); }
    .mfg-dash .mfg-summary-strip { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .mfg-dash .mfg-hero { padding: 18px 16px; }
    .mfg-dash .mfg-hero h1 { font-size: 1.2rem; }
    .mfg-dash .mfg-hero-inner { flex-direction: column; align-items: stretch; }
    .mfg-dash .mfg-hero-actions { width: 100%; }
    .mfg-dash .mfg-hero-actions .btn-mfg-violet,
    .mfg-dash .mfg-hero-actions .btn-mfg-outline { flex: 1; text-align: center; justify-content: center; }
    .mfg-dash .mfg-kpi-grid { grid-template-columns: 1fr; }
    .mfg-dash .mfg-quick-links { grid-template-columns: 1fr; }
    .mfg-dash .mfg-chart-wrap { height: 220px; }
}
</style>

<div class="mfg-dash">

<div class="mfg-hero">
    <div class="mfg-hero-inner">
        <div>
            <div class="mfg-greeting"><?php echo mfg_esc($greeting); ?></div>
            <h1>Manufacturing Dashboard</h1>
            <div class="mfg-date"><?php echo mfg_esc($todayLabel); ?></div>
        </div>
        <div class="mfg-hero-actions">
            <a class="btn-mfg-violet" href="jobwork-order.php"><i class="feather icon-settings"></i> New Jobwork</a>
            <a class="btn-mfg-outline" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
        </div>
    </div>
</div>

<?php if (empty($mfg['has_jobwork'])): ?>
    <div class="alert alert-info py-2 mb-3">Jobwork orders table not found — showing sale order activity only.</div>
<?php endif; ?>

<div class="mfg-summary-strip">
    <div class="mfg-summary-item mfg-summary-item--jobs">
        <div class="lbl">Total jobwork orders</div>
        <div class="val"><?php echo number_format((int) ($mfg['total_jobwork'] ?? 0)); ?></div>
    </div>
    <div class="mfg-summary-item mfg-summary-item--orders">
        <div class="lbl">Pending sale orders</div>
        <div class="val"><?php echo number_format((int) ($mx['pending_sale_orders'] ?? 0)); ?></div>
    </div>
    <div class="mfg-summary-item mfg-summary-item--due">
        <div class="lbl">Due this week</div>
        <div class="val"><?php echo number_format((int) ($mx['jobs_due_week'] ?? 0)); ?></div>
    </div>
</div>

<div class="mfg-quick-links">
    <a class="mfg-quick-link mfg-quick-link--jobwork" href="jobwork-order.php"><span class="ql-icon"><i class="feather icon-settings"></i></span> Jobwork Order</a>
    <a class="mfg-quick-link mfg-quick-link--process" href="manufacturing-process.php"><span class="ql-icon"><i class="feather icon-refresh-cw"></i></span> Mfg Process</a>
    <a class="mfg-quick-link mfg-quick-link--queue" href="jobwork-queue.php"><span class="ql-icon"><i class="feather icon-layers"></i></span> Jobwork Queue</a>
    <a class="mfg-quick-link mfg-quick-link--sale" href="sale-order.php"><span class="ql-icon"><i class="feather icon-shopping-cart"></i></span> Sale Order</a>
</div>

<div class="mfg-kpi-grid">
    <div class="mfg-kpi mfg-kpi--ip">
        <div class="gauge"><?php echo (int) ($k['in_progress'] ?? 0); ?></div>
        <div>
            <div class="lbl">In Progress</div>
            <div class="sub">Active · not overdue</div>
        </div>
    </div>
    <div class="mfg-kpi mfg-kpi--del">
        <div class="gauge"><?php echo (int) ($k['delayed'] ?? 0); ?></div>
        <div>
            <div class="lbl">Delayed</div>
            <div class="sub">Past due date</div>
        </div>
    </div>
    <div class="mfg-kpi mfg-kpi--hold">
        <div class="gauge"><?php echo (int) ($k['on_hold'] ?? 0); ?></div>
        <div>
            <div class="lbl">On Hold</div>
            <div class="sub">Waiting / paused</div>
        </div>
    </div>
    <div class="mfg-kpi mfg-kpi--ni">
        <div class="gauge"><?php echo (int) ($k['not_initiate'] ?? 0); ?></div>
        <div>
            <div class="lbl">Not Initiated</div>
            <div class="sub">Draft / pending</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="mfg-panel mfg-panel--chart">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-bar-chart-2" style="color:var(--mfg-violet);margin-right:6px;"></i> Orders by workstation</h2>
                <span class="mfg-badge mfg-badge--violet">By department</span>
            </div>
            <div class="mfg-chart-wrap">
                <canvas id="mfgWorkstationChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="mfg-panel mfg-panel--workstation">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-cpu" style="color:var(--mfg-sky);margin-right:6px;"></i> Workstation load</h2>
            </div>
            <div class="mfg-panel-body">
                <?php if (!empty($mfg['workstation_rows'])): ?>
                <table class="mfg-table">
                    <thead><tr><th>Department</th><th class="text-right">Jobs</th></tr></thead>
                    <tbody>
                    <?php foreach ($mfg['workstation_rows'] as $wr): ?>
                        <tr>
                            <td><?php echo mfg_esc($wr['dept_label'] ?? $wr['dept_key'] ?? '—'); ?></td>
                            <td class="cnt"><?php echo (int) ($wr['order_count'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="mfg-empty">No workstation data yet.</div>
                <?php endif; ?>
            </div>
            <?php if (!empty($mfg['total_jobwork'])): ?>
            <div class="mfg-ws-foot">Total jobwork: <strong><?php echo (int) $mfg['total_jobwork']; ?></strong> · Completed this month: <strong><?php echo (int) ($mx['jobs_completed_month'] ?? 0); ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="mfg-panel mfg-panel--progress">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-activity" style="color:var(--mfg-violet);margin-right:6px;"></i> Jobs in progress</h2>
                <a href="jobwork-order.php" class="mfg-badge mfg-badge--link">View all</a>
            </div>
            <div class="mfg-panel-body">
                <?php if (!empty($mfg['list_in_progress'])): ?>
                <div class="table-responsive">
                    <table class="mfg-table">
                        <thead>
                            <tr>
                                <th>Jobwork</th>
                                <th>Customer</th>
                                <th>Sale order</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mfg['list_in_progress'] as $row):
                            $due = (string) ($row['due_date'] ?? '');
                            $dueClass = '';
                            if ($due !== '' && $due < date('Y-m-d')) {
                                $dueClass = 'due-over';
                            } elseif ($due !== '' && $due <= date('Y-m-d', strtotime('+3 days'))) {
                                $dueClass = 'due-soon';
                            }
                        ?>
                            <tr>
                                <td class="link-cell"><a href="jobwork-order.php?id=<?php echo (int) ($row['id'] ?? 0); ?>"><?php echo mfg_esc($row['jobwork_no'] ?? ''); ?></a></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? '—'); ?></td>
                                <td><?php echo mfg_esc($row['sale_order_no'] ?? '—'); ?></td>
                                <td class="<?php echo mfg_esc($dueClass); ?>"><?php echo mfg_esc($due ?: '—'); ?></td>
                                <td><span class="mfg-status"><?php echo mfg_esc($row['status'] ?? 'Open'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="mfg-empty">No jobs in progress. <a href="jobwork-order.php">Create jobwork</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="mfg-panel mfg-panel--delayed">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-alert-circle" style="color:var(--mfg-rose);margin-right:6px;"></i> Delayed jobs</h2>
                <span class="mfg-badge" style="background:#ffe4e6;color:#be123c;border-color:rgba(225,29,72,0.2);"><?php echo (int) ($k['delayed'] ?? 0); ?> overdue</span>
            </div>
            <div class="mfg-panel-body">
                <?php if (!empty($mfg['list_delayed'])): ?>
                <div class="table-responsive">
                    <table class="mfg-table">
                        <thead><tr><th>Jobwork</th><th>Customer</th><th>Due</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($mfg['list_delayed'], 0, 8) as $row): ?>
                            <tr>
                                <td class="link-cell"><a href="jobwork-order.php?id=<?php echo (int) ($row['id'] ?? 0); ?>"><?php echo mfg_esc($row['jobwork_no'] ?? ''); ?></a></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? '—'); ?></td>
                                <td class="due-over"><?php echo mfg_esc($row['due_date'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="mfg-empty">No delayed jobs — all on track.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="mfg-panel mfg-panel--hold">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-pause-circle" style="color:var(--mfg-amber);margin-right:6px;"></i> Jobs on hold</h2>
            </div>
            <div class="mfg-panel-body">
                <?php if (!empty($mfg['list_on_hold'])): ?>
                <div class="table-responsive">
                    <table class="mfg-table">
                        <thead><tr><th>Jobwork</th><th>Customer</th><th>Sale order</th><th>Due</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($mfg['list_on_hold'], 0, 8) as $row): ?>
                            <tr>
                                <td class="link-cell"><a href="jobwork-order.php?id=<?php echo (int) ($row['id'] ?? 0); ?>"><?php echo mfg_esc($row['jobwork_no'] ?? ''); ?></a></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? '—'); ?></td>
                                <td><?php echo mfg_esc($row['sale_order_no'] ?? '—'); ?></td>
                                <td><?php echo mfg_esc($row['due_date'] ?? '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="mfg-empty">No jobs on hold.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="mfg-panel mfg-panel--recent">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-shopping-cart" style="color:var(--mfg-sky);margin-right:6px;"></i> Recent sale orders</h2>
                <a href="sale-order.php" class="mfg-badge mfg-badge--link">View all</a>
            </div>
            <div class="mfg-panel-body">
                <?php if (!empty($mfg['recent_sale_orders'])): ?>
                <div class="table-responsive">
                    <table class="mfg-table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Tag no.</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($mfg['recent_sale_orders'], 0, 8) as $row): ?>
                            <tr>
                                <td class="link-cell"><a href="sale-order.php?id=<?php echo (int) ($row['id'] ?? 0); ?>"><?php echo mfg_esc($row['order_no'] ?? ''); ?></a></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? '—'); ?></td>
                                <td><?php echo mfg_esc($row['tag_no'] ?? '') ?: '—'; ?></td>
                                <td><?php echo mfg_esc($row['order_date'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="mfg-empty">No sale orders yet. <a href="sale-order.php">New order</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="mfg-panel mfg-panel--done">
            <div class="mfg-panel-head">
                <h2><i class="feather icon-check-circle" style="color:var(--mfg-emerald);margin-right:6px;"></i> Completed sale orders</h2>
                <span class="mfg-badge" style="background:#d1fae5;color:#065f46;border-color:rgba(5,150,105,0.2);"><?php echo number_format((int) ($mfg['total_sale_orders'] ?? 0)); ?> total orders</span>
            </div>
            <div class="mfg-panel-body">
                <?php if (!empty($mfg['completed_orders'])): ?>
                <div class="table-responsive">
                    <table class="mfg-table">
                        <thead><tr><th>Order</th><th>Customer</th><th>Tag no.</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($mfg['completed_orders'], 0, 10) as $row): ?>
                            <tr>
                                <td class="link-cell"><a href="sale-order.php?id=<?php echo (int) ($row['id'] ?? 0); ?>"><?php echo mfg_esc($row['order_no'] ?? ''); ?></a></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? '—'); ?></td>
                                <td><?php echo mfg_esc($row['tag_no'] ?? '') ?: '—'; ?></td>
                                <td><?php echo mfg_esc($row['order_date'] ?? ''); ?></td>
                                <td><span class="mfg-status mfg-status--done"><?php echo mfg_esc($row['status'] ?? 'Done'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="mfg-empty">No completed orders yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mfg-foot">
    <span>Gold Matrix · Manufacturing dashboard · Jobwork: <?php echo (int) ($mfg['total_jobwork'] ?? 0); ?> · Sale orders: <?php echo (int) ($mfg['total_sale_orders'] ?? 0); ?></span>
    <span><a href="jobwork-queue.php">Jobwork queue</a> · <a href="manufacturing-process.php">Process</a> · <a href="sale-order.php">Sale orders</a></span>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('mfgWorkstationChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?php echo $wsLabelsJson; ?>;
    var values = <?php echo $wsValuesJson; ?>;
    if (!labels.length) {
        labels = ['No data'];
        values = [0];
    }
    var violet = '#7c3aed';
    var gold = '#c5a864';
    var navy = '#11294b';
    var colors = labels.map(function(_, i) {
        var palette = ['#7c3aed','#a78bfa','#c5a864','#059669','#0284c7','#d97706','#e11d48','#6366f1'];
        return palette[i % palette.length];
    });
    var mfgChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Orders',
                data: values,
                backgroundColor: colors.map(function(c) { return c + 'cc'; }),
                borderColor: colors,
                borderWidth: 1.5,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: navy,
                    titleColor: gold,
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, color: '#64748b', font: { size: 11 } },
                    grid: { color: 'rgba(17,41,75,0.06)' },
                    border: { display: false }
                },
                x: {
                    ticks: { color: '#64748b', font: { size: 10 }, maxRotation: 45, minRotation: 0 },
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
    window.addEventListener('resize', function() {
        if (mfgChart) mfgChart.resize();
    });
})();
</script>
