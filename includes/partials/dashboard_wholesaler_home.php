<?php
/**
 * Wholesaler home dashboard (WHOLESALER type). Expects dashboard_helpers loaded.
 */
$wd = auragold_wholesaler_dashboard_kpis();
$wx = auragold_wholesaler_dashboard_extras();
$labelsJson = json_encode($wd['chart_labels'], JSON_UNESCAPED_UNICODE);
$valuesJson = json_encode($wd['chart_values'], JSON_UNESCAPED_UNICODE);

$url_ledger_all = static function (string $ledgerName): string {
    return 'accountledger-report.php?tab=all&ledger_account=' . rawurlencode($ledgerName) . '&ledger_name=' . rawurlencode($ledgerName);
};

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
.wholesale-dash {
    --rd-navy: #11294b;
    --rd-navy-soft: #1a3a66;
    --rd-gold: #c5a864;
    --rd-gold-light: #e8d5a8;
    --rd-gold-deep: #9a7b3c;
    --rd-teal: #0d9488;
    --rd-teal-bright: #14b8a6;
    --rd-teal-soft: #ccfbf1;
    --rd-violet: #7c3aed;
    --rd-amber: #d97706;
    --rd-emerald: #059669;
    --rd-rose: #e11d48;
    --rd-sky: #0284c7;
    --rd-surface: #ffffff;
    --rd-muted: #64748b;
    --rd-border: rgba(17, 41, 75, 0.08);
    --rd-shadow: 0 4px 24px rgba(17, 41, 75, 0.08);
    --rd-shadow-hover: 0 12px 32px rgba(17, 41, 75, 0.12);
    max-width: 100%;
    padding: 4px 0 8px;
}

.wholesale-dash .rd-hero {
    background: linear-gradient(135deg, #0a1f3d 0%, #11294b 30%, #134e4a 65%, #0f766e 100%);
    border-radius: 20px;
    padding: 22px 26px;
    margin-bottom: 22px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(15, 118, 110, 0.15);
    border: 1px solid rgba(45, 212, 191, 0.18);
}
.wholesale-dash .rd-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(45, 212, 191, 0.22) 0%, rgba(197, 168, 100, 0.12) 45%, transparent 70%);
    pointer-events: none;
}
.wholesale-dash .rd-hero::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #2dd4bf, var(--rd-gold), #2dd4bf, transparent);
}
.wholesale-dash .rd-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.wholesale-dash .rd-hero h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 4px;
    letter-spacing: -0.02em;
}
.wholesale-dash .rd-greeting {
    font-size: 13px;
    color: #99f6e4;
    opacity: 0.95;
}
.wholesale-dash .rd-date {
    font-size: 12px;
    color: rgba(255,255,255,0.65);
    margin-top: 2px;
}
.wholesale-dash .rd-hero-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.wholesale-dash .btn-wd-teal {
    background: linear-gradient(135deg, #2dd4bf 0%, #14b8a6 100%);
    color: #042f2e;
    font-weight: 700;
    font-size: 13px;
    padding: 9px 18px;
    border-radius: 10px;
    border: none;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(20, 184, 166, 0.35);
    transition: transform .15s, box-shadow .15s;
}
.wholesale-dash .btn-wd-teal:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(20, 184, 166, 0.45);
    color: #042f2e;
    text-decoration: none;
}
.wholesale-dash .btn-rd-outline {
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
.wholesale-dash .btn-rd-outline:hover {
    background: rgba(255,255,255,0.18);
    color: #fff;
    text-decoration: none;
}

.wholesale-dash .rd-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.wholesale-dash .rd-summary-item {
    border-radius: 16px;
    padding: 16px 18px;
    border: 1px solid transparent;
    box-shadow: var(--rd-shadow);
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
}
.wholesale-dash .rd-summary-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--rd-shadow-hover);
}
.wholesale-dash .rd-summary-item::after {
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
.wholesale-dash .rd-summary-item .lbl {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: 0.85;
}
.wholesale-dash .rd-summary-item .val {
    font-size: 1.4rem;
    font-weight: 800;
    margin-top: 6px;
    line-height: 1.15;
}
.wholesale-dash .rd-summary-item--week {
    background: linear-gradient(135deg, #faf5ff 0%, #ede9fe 55%, #ddd6fe 100%);
    border-color: rgba(124, 58, 237, 0.2);
}
.wholesale-dash .rd-summary-item--week::after { background: radial-gradient(circle, #a78bfa 0%, transparent 70%); }
.wholesale-dash .rd-summary-item--week .lbl { color: #6d28d9; }
.wholesale-dash .rd-summary-item--week .val { color: #5b21b6; }

.wholesale-dash .rd-summary-item--purchase {
    background: linear-gradient(135deg, #ecfdf5 0%, #99f6e4 55%, #5eead4 100%);
    border-color: rgba(13, 148, 136, 0.25);
}
.wholesale-dash .rd-summary-item--purchase::after { background: radial-gradient(circle, #2dd4bf 0%, transparent 70%); }
.wholesale-dash .rd-summary-item--purchase .lbl { color: #0f766e; }
.wholesale-dash .rd-summary-item--purchase .val { color: #115e59; }

.wholesale-dash .rd-summary-item--partners {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 55%, #fed7aa 100%);
    border-color: rgba(217, 119, 6, 0.2);
}
.wholesale-dash .rd-summary-item--partners::after { background: radial-gradient(circle, #fb923c 0%, transparent 70%); }
.wholesale-dash .rd-summary-item--partners .lbl { color: #c2410c; }
.wholesale-dash .rd-summary-item--partners .val { color: #7c2d12; }

.wholesale-dash .rd-quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 22px;
}
.wholesale-dash .rd-quick-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--rd-surface);
    border: 1px solid var(--rd-border);
    border-radius: 12px;
    text-decoration: none;
    color: var(--rd-navy);
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(17,41,75,0.04);
    transition: all .15s;
}
.wholesale-dash .rd-quick-link:hover {
    box-shadow: var(--rd-shadow);
    color: var(--rd-navy);
    text-decoration: none;
    transform: translateY(-2px);
}
.wholesale-dash .rd-quick-link .ql-icon {
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
.wholesale-dash .rd-quick-link--invoice { border-color: rgba(124,58,237,0.12); }
.wholesale-dash .rd-quick-link--invoice:hover { border-color: rgba(124,58,237,0.35); background: #faf5ff; }
.wholesale-dash .rd-quick-link--invoice .ql-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
.wholesale-dash .rd-quick-link--purchase { border-color: rgba(13,148,136,0.12); }
.wholesale-dash .rd-quick-link--purchase:hover { border-color: rgba(13,148,136,0.35); background: #ecfdf5; }
.wholesale-dash .rd-quick-link--purchase .ql-icon { background: linear-gradient(135deg, #ccfbf1, #99f6e4); color: #0f766e; }
.wholesale-dash .rd-quick-link--consignment { border-color: rgba(217,119,6,0.12); }
.wholesale-dash .rd-quick-link--consignment:hover { border-color: rgba(217,119,6,0.35); background: #fffbeb; }
.wholesale-dash .rd-quick-link--consignment .ql-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
.wholesale-dash .rd-quick-link--order { border-color: rgba(2,132,199,0.12); }
.wholesale-dash .rd-quick-link--order:hover { border-color: rgba(2,132,199,0.35); background: #f0f9ff; }
.wholesale-dash .rd-quick-link--order .ql-icon { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0369a1; }

.wholesale-dash .rd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
.wholesale-dash .rd-kpi {
    border-radius: 16px;
    padding: 16px;
    border: 1px solid transparent;
    box-shadow: var(--rd-shadow);
    transition: transform .18s ease, box-shadow .18s ease;
    position: relative;
    overflow: hidden;
}
.wholesale-dash .rd-kpi::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    border-radius: 16px 0 0 16px;
}
.wholesale-dash .rd-kpi::after {
    content: '';
    position: absolute;
    bottom: -20px;
    right: -20px;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    opacity: 0.25;
    pointer-events: none;
}
.wholesale-dash .rd-kpi:hover {
    transform: translateY(-3px);
    box-shadow: var(--rd-shadow-hover);
}
.wholesale-dash .rd-kpi .icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 12px;
}
.wholesale-dash .rd-kpi .lbl {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--rd-muted);
    line-height: 1.3;
}
.wholesale-dash .rd-kpi .num {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--rd-navy);
    margin-top: 4px;
    line-height: 1.2;
}
.wholesale-dash .rd-kpi .sub { font-size: 11px; color: var(--rd-muted); margin-top: 6px; }
.wholesale-dash .rd-kpi .sub strong { color: #334155; }
.wholesale-dash .rd-kpi .link { font-size: 11px; font-weight: 600; margin-top: 6px; }
.wholesale-dash .rd-kpi .link a { color: var(--rd-teal); text-decoration: none; }
.wholesale-dash .rd-kpi .link a:hover { text-decoration: underline; }

.wholesale-dash .rd-kpi--sales { background: linear-gradient(145deg, #faf5ff 0%, #f3e8ff 100%); border-color: rgba(124,58,237,0.15); }
.wholesale-dash .rd-kpi--sales::before { background: linear-gradient(180deg, #a78bfa, #7c3aed); }
.wholesale-dash .rd-kpi--sales::after { background: #c4b5fd; }
.wholesale-dash .rd-kpi--sales .icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; box-shadow: 0 4px 12px rgba(124,58,237,0.15); }
.wholesale-dash .rd-kpi--sales .num { color: #5b21b6; }

.wholesale-dash .rd-kpi--purchase { background: linear-gradient(145deg, #ecfdf5 0%, #ccfbf1 100%); border-color: rgba(13,148,136,0.15); }
.wholesale-dash .rd-kpi--purchase::before { background: linear-gradient(180deg, #2dd4bf, #0d9488); }
.wholesale-dash .rd-kpi--purchase::after { background: #5eead4; }
.wholesale-dash .rd-kpi--purchase .icon { background: linear-gradient(135deg, #ccfbf1, #99f6e4); color: #0f766e; box-shadow: 0 4px 12px rgba(13,148,136,0.15); }
.wholesale-dash .rd-kpi--purchase .num { color: #115e59; }

.wholesale-dash .rd-kpi--orders { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); border-color: rgba(217,119,6,0.15); }
.wholesale-dash .rd-kpi--orders::before { background: linear-gradient(180deg, #fbbf24, #d97706); }
.wholesale-dash .rd-kpi--orders::after { background: #fcd34d; }
.wholesale-dash .rd-kpi--orders .icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; box-shadow: 0 4px 12px rgba(217,119,6,0.15); }
.wholesale-dash .rd-kpi--orders .num { color: #92400e; }

.wholesale-dash .rd-kpi--cash { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); border-color: rgba(225,29,72,0.15); }
.wholesale-dash .rd-kpi--cash::before { background: linear-gradient(180deg, #fb7185, #e11d48); }
.wholesale-dash .rd-kpi--cash::after { background: #fda4af; }
.wholesale-dash .rd-kpi--cash .icon { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; box-shadow: 0 4px 12px rgba(225,29,72,0.15); }
.wholesale-dash .rd-kpi--cash .num { color: #9f1239; }

.wholesale-dash .rd-kpi--bank { background: linear-gradient(145deg, #eff6ff 0%, #dbeafe 100%); border-color: rgba(37,99,235,0.15); }
.wholesale-dash .rd-kpi--bank::before { background: linear-gradient(180deg, #60a5fa, #2563eb); }
.wholesale-dash .rd-kpi--bank::after { background: #93c5fd; }
.wholesale-dash .rd-kpi--bank .icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; box-shadow: 0 4px 12px rgba(37,99,235,0.15); }
.wholesale-dash .rd-kpi--bank .num { color: #1e3a8a; }

.wholesale-dash .rd-kpi--card { background: linear-gradient(145deg, #fdf2f8 0%, #fce7f3 100%); border-color: rgba(219,39,119,0.15); }
.wholesale-dash .rd-kpi--card::before { background: linear-gradient(180deg, #f472b6, #db2777); }
.wholesale-dash .rd-kpi--card::after { background: #f9a8d4; }
.wholesale-dash .rd-kpi--card .icon { background: linear-gradient(135deg, #fce7f3, #fbcfe8); color: #be185d; box-shadow: 0 4px 12px rgba(219,39,119,0.15); }
.wholesale-dash .rd-kpi--card .num { color: #9d174d; }

.wholesale-dash .rd-panel {
    background: var(--rd-surface);
    border-radius: 18px;
    border: 1px solid var(--rd-border);
    box-shadow: var(--rd-shadow);
    padding: 20px 22px;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.wholesale-dash .rd-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--rd-gold), var(--rd-gold-light), var(--rd-gold));
    opacity: 0.85;
}
.wholesale-dash .rd-panel--chart::before { background: linear-gradient(90deg, #14b8a6, var(--rd-gold), #7c3aed); }
.wholesale-dash .rd-panel--invoices::before { background: linear-gradient(90deg, var(--rd-sky), #38bdf8); }
.wholesale-dash .rd-panel--orders::before { background: linear-gradient(90deg, var(--rd-amber), #fbbf24); }
.wholesale-dash .rd-panel--purchase::before { background: linear-gradient(90deg, #0d9488, #2dd4bf); }
.wholesale-dash .rd-panel--consignment::before { background: linear-gradient(90deg, #d97706, #f59e0b); }

.wholesale-dash .rd-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}
.wholesale-dash .rd-panel-head h2 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--rd-navy);
    margin: 0;
}
.wholesale-dash .rd-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    color: var(--rd-muted);
    border: 1px solid var(--rd-border);
}
.wholesale-dash .rd-badge--teal {
    background: linear-gradient(135deg, #ccfbf1, #99f6e4);
    color: #115e59;
    border-color: rgba(13,148,136,0.25);
}
.wholesale-dash .rd-badge--link {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4338ca;
    border-color: rgba(79,70,229,0.2);
}
.wholesale-dash .rd-chart-wrap { position: relative; height: 280px; width: 100%; }

.wholesale-dash .rd-market {
    background: linear-gradient(160deg, #0f2340 0%, var(--rd-navy) 40%, #134e4a 100%);
    color: #fff;
    border: 1px solid rgba(45, 212, 191, 0.2);
}
.wholesale-dash .rd-market::before {
    background: linear-gradient(90deg, transparent, #2dd4bf, var(--rd-gold), var(--rd-gold-deep), transparent);
    height: 4px;
    opacity: 1;
}
.wholesale-dash .rd-market .rd-panel-head h2 { color: #fff; }
.wholesale-dash .rd-market .rd-badge {
    background: rgba(255,255,255,0.12);
    color: var(--rd-gold-light);
    border-color: rgba(255,255,255,0.15);
}
.wholesale-dash .rd-rate-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    margin-bottom: 8px;
    background: rgba(255,255,255,0.06);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.08);
    transition: background .15s;
}
.wholesale-dash .rd-rate-row:hover { background: rgba(255,255,255,0.12); }
.wholesale-dash .rd-rate-row--18k { border-left: 3px solid #fde68a; }
.wholesale-dash .rd-rate-row--21k { border-left: 3px solid #fbbf24; }
.wholesale-dash .rd-rate-row--22k { border-left: 3px solid #f59e0b; }
.wholesale-dash .rd-rate-row--24k { border-left: 3px solid var(--rd-gold); }
.wholesale-dash .rd-rate-row .karat { font-weight: 700; font-size: 15px; color: var(--rd-gold-light); }
.wholesale-dash .rd-rate-row .rate { font-weight: 800; font-size: 18px; color: #fff; }
.wholesale-dash .rd-rate-row .rate.empty { color: rgba(255,255,255,0.35); font-weight: 600; }
.wholesale-dash .rd-market-foot { font-size: 11px; color: rgba(255,255,255,0.55); margin-top: 14px; }
.wholesale-dash .rd-market-foot a { color: #2dd4bf; }

.wholesale-dash .rd-table { width: 100%; font-size: 13px; margin: 0; }
.wholesale-dash .rd-table thead th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--rd-muted);
    border-bottom: 2px solid #f1f5f9;
    padding: 8px 10px;
    background: transparent;
}
.wholesale-dash .rd-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    color: #334155;
}
.wholesale-dash .rd-table tbody tr:hover td { background: #fafbfc; }
.wholesale-dash .rd-table tbody tr:last-child td { border-bottom: 0; }
.wholesale-dash .rd-table .amt { font-weight: 700; color: var(--rd-navy); text-align: right; white-space: nowrap; }
.wholesale-dash .rd-table .link-cell a { color: var(--rd-navy); font-weight: 600; text-decoration: none; }
.wholesale-dash .rd-table .link-cell a:hover { color: var(--rd-teal); }
.wholesale-dash .rd-status {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 4px 9px;
    border-radius: 20px;
    background: linear-gradient(135deg, #ccfbf1, #99f6e4);
    color: #115e59;
    border: 1px solid rgba(13,148,136,0.2);
}
.wholesale-dash .rd-status--memo {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border-color: rgba(217,119,6,0.2);
}
.wholesale-dash .rd-empty { text-align: center; padding: 28px 16px; color: var(--rd-muted); font-size: 13px; }
.wholesale-dash .rd-foot {
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px solid var(--rd-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #94a3b8;
}
.wholesale-dash .rd-foot a { color: var(--rd-muted); text-decoration: none; }
.wholesale-dash .rd-foot a:hover { color: var(--rd-teal); }

@media (max-width: 1399.98px) {
    .wholesale-dash .rd-kpi-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 991.98px) {
    .wholesale-dash .rd-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .wholesale-dash .rd-quick-links { grid-template-columns: repeat(2, 1fr); }
    .wholesale-dash .rd-summary-strip { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .wholesale-dash .rd-hero { padding: 18px 16px; }
    .wholesale-dash .rd-hero h1 { font-size: 1.2rem; }
    .wholesale-dash .rd-hero-inner { flex-direction: column; align-items: stretch; }
    .wholesale-dash .rd-hero-actions { width: 100%; }
    .wholesale-dash .rd-hero-actions .btn-wd-teal,
    .wholesale-dash .rd-hero-actions .btn-rd-outline { flex: 1; text-align: center; justify-content: center; }
    .wholesale-dash .rd-kpi-grid { grid-template-columns: 1fr; }
    .wholesale-dash .rd-quick-links { grid-template-columns: 1fr; }
    .wholesale-dash .rd-chart-wrap { height: 220px; }
}
</style>

<div class="wholesale-dash">

<div class="rd-hero">
    <div class="rd-hero-inner">
        <div>
            <div class="rd-greeting"><?php echo htmlspecialchars($greeting); ?></div>
            <h1>Wholesaler Dashboard</h1>
            <div class="rd-date"><?php echo htmlspecialchars($todayLabel); ?></div>
        </div>
        <div class="rd-hero-actions">
            <a class="btn-wd-teal" href="purchase-invoice.php"><i class="feather icon-shopping-bag"></i> New Purchase</a>
            <a class="btn-rd-outline" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
        </div>
    </div>
</div>

<?php if ((int) ($wd['customer_type_id'] ?? 0) <= 0): ?>
    <div class="alert alert-warning py-2 mb-3">Customer type <code>WHOLESALER</code> not found in masters. KPIs use all customers until the type exists.</div>
<?php endif; ?>

<div class="rd-summary-strip">
    <div class="rd-summary-item rd-summary-item--week">
        <div class="lbl">This week sales</div>
        <div class="val"><?php echo auragold_fmt_money($wx['sales_week']); ?></div>
    </div>
    <div class="rd-summary-item rd-summary-item--purchase">
        <div class="lbl">This month purchases</div>
        <div class="val"><?php echo auragold_fmt_money($wx['purchases_month']); ?></div>
    </div>
    <div class="rd-summary-item rd-summary-item--partners">
        <div class="lbl">Wholesaler partners</div>
        <div class="val"><?php echo number_format((int) $wx['customers_count']); ?></div>
    </div>
</div>

<div class="rd-quick-links">
    <a class="rd-quick-link rd-quick-link--invoice" href="sale-invoice.php"><span class="ql-icon"><i class="feather icon-file-text"></i></span> Sale Invoice</a>
    <a class="rd-quick-link rd-quick-link--purchase" href="purchase-invoice.php"><span class="ql-icon"><i class="feather icon-download"></i></span> Purchase Invoice</a>
    <a class="rd-quick-link rd-quick-link--consignment" href="consignment-out.php"><span class="ql-icon"><i class="feather icon-share"></i></span> Consignment Out</a>
    <a class="rd-quick-link rd-quick-link--order" href="sale-order.php"><span class="ql-icon"><i class="feather icon-shopping-cart"></i></span> Sale Order</a>
</div>

<div class="rd-kpi-grid">
    <div class="rd-kpi rd-kpi--sales">
        <div class="icon"><i class="feather icon-trending-up"></i></div>
        <div class="lbl">Today’s Sales</div>
        <div class="num"><?php echo auragold_fmt_money($wd['sales_today']); ?></div>
    </div>
    <div class="rd-kpi rd-kpi--purchase">
        <div class="icon"><i class="feather icon-download"></i></div>
        <div class="lbl">Today’s Purchase</div>
        <div class="num"><?php echo auragold_fmt_money($wd['purchase_today']); ?></div>
    </div>
    <div class="rd-kpi rd-kpi--orders">
        <div class="icon"><i class="feather icon-package"></i></div>
        <div class="lbl">Today’s Orders</div>
        <div class="num"><?php echo number_format((int) $wd['orders_today']); ?></div>
    </div>
    <div class="rd-kpi rd-kpi--cash">
        <div class="icon"><i class="feather icon-dollar-sign"></i></div>
        <div class="lbl">Cash In Hand</div>
        <div class="num"><?php echo auragold_fmt_money($wd['cash_today']); ?></div>
        <div class="sub">Balance: <strong><?php echo auragold_fmt_money($wd['balance_cash']); ?></strong></div>
        <div class="link"><a href="<?php echo htmlspecialchars($url_ledger_all('Cash')); ?>">View ledger</a></div>
    </div>
    <div class="rd-kpi rd-kpi--bank">
        <div class="icon"><i class="feather icon-briefcase"></i></div>
        <div class="lbl">Today’s Bank</div>
        <div class="num"><?php echo auragold_fmt_money($wd['bank_today']); ?></div>
        <div class="sub">Balance: <strong><?php echo auragold_fmt_money($wd['balance_bank']); ?></strong></div>
        <div class="link"><a href="<?php echo htmlspecialchars($url_ledger_all('Bank Account')); ?>">View ledger</a></div>
    </div>
    <div class="rd-kpi rd-kpi--card">
        <div class="icon"><i class="feather icon-credit-card"></i></div>
        <div class="lbl">Today’s Card</div>
        <div class="num"><?php echo auragold_fmt_money($wd['card_today']); ?></div>
        <div class="sub">Balance: <strong><?php echo auragold_fmt_money($wd['balance_card']); ?></strong></div>
        <div class="link"><a href="<?php echo htmlspecialchars($url_ledger_all('Card')); ?>">View ledger</a></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="rd-panel rd-panel--chart">
            <div class="rd-panel-head">
                <h2><i class="feather icon-activity" style="color:var(--rd-teal);margin-right:6px;"></i> Wholesale sales overview</h2>
                <span class="rd-badge rd-badge--teal">Last 7 days</span>
            </div>
            <div class="rd-chart-wrap">
                <canvas id="wholesaleSalesChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="rd-panel rd-market">
            <div class="rd-panel-head">
                <h2><i class="feather icon-award" style="color:var(--rd-gold);margin-right:6px;"></i> Gold rates</h2>
                <span class="rd-badge">Live avg</span>
            </div>
            <?php
            $karats = ['18k' => '18K', '21k' => '21K', '22k' => '22K', '24k' => '24K'];
            foreach ($karats as $key => $label):
                $rate = auragold_market_rate($wd['market'][$key] ?? null);
                $isEmpty = ($rate === '—');
            ?>
            <div class="rd-rate-row rd-rate-row--<?php echo htmlspecialchars($key); ?>">
                <span class="karat"><?php echo htmlspecialchars($label); ?></span>
                <span class="rate<?php echo $isEmpty ? ' empty' : ''; ?>"><?php echo $isEmpty ? '—' : htmlspecialchars($rate); ?></span>
            </div>
            <?php endforeach; ?>
            <p class="rd-market-foot mb-0">Avg. rate from recent <strong>Gold</strong> sale lines. <a href="dashboard-gold-rates.php">Full rates</a></p>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="rd-panel rd-panel--invoices">
            <div class="rd-panel-head">
                <h2><i class="feather icon-file-text" style="color:var(--rd-sky);margin-right:6px;"></i> Recent sale invoices</h2>
                <a href="sale-invoice.php" class="rd-badge rd-badge--link" style="text-decoration:none;">View all</a>
            </div>
            <?php if (!empty($wx['recent_invoices'])): ?>
            <div class="table-responsive">
                <table class="rd-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Wholesaler</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wx['recent_invoices'] as $inv): ?>
                        <tr>
                            <td class="link-cell"><a href="sale-invoice.php?id=<?php echo (int) ($inv['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($inv['invoice_no'] ?? '#' . ($inv['id'] ?? ''))); ?></a></td>
                            <td><?php echo htmlspecialchars((string) ($inv['customer_name'] ?? '—')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($inv['invoice_date'] ?? '')); ?></td>
                            <td class="amt"><?php echo auragold_fmt_money($inv['grand_total'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="rd-empty">No wholesaler sale invoices yet. <a href="sale-invoice.php">Create one</a></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="rd-panel rd-panel--orders">
            <div class="rd-panel-head">
                <h2><i class="feather icon-clock" style="color:var(--rd-amber);margin-right:6px;"></i> Pending orders</h2>
                <a href="sale-order.php" class="rd-badge rd-badge--link" style="text-decoration:none;">View all</a>
            </div>
            <?php if (!empty($wx['pending_orders'])): ?>
            <div class="table-responsive">
                <table class="rd-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wx['pending_orders'] as $ord): ?>
                        <tr>
                            <td class="link-cell"><a href="sale-order.php?id=<?php echo (int) ($ord['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($ord['order_no'] ?? '#' . ($ord['id'] ?? ''))); ?></a></td>
                            <td><?php echo htmlspecialchars((string) ($ord['customer_name'] ?? '—')); ?></td>
                            <td><span class="rd-status"><?php echo htmlspecialchars((string) ($ord['status'] ?? 'Open')); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="rd-empty">No pending orders. <a href="sale-order.php">New order</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="rd-panel rd-panel--purchase">
            <div class="rd-panel-head">
                <h2><i class="feather icon-download" style="color:var(--rd-teal);margin-right:6px;"></i> Recent purchase invoices</h2>
                <a href="purchase-invoice.php" class="rd-badge rd-badge--link" style="text-decoration:none;">View all</a>
            </div>
            <?php if (!empty($wx['recent_purchases'])): ?>
            <div class="table-responsive">
                <table class="rd-table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wx['recent_purchases'] as $pur): ?>
                        <tr>
                            <td class="link-cell"><a href="purchase-invoice.php?id=<?php echo (int) ($pur['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($pur['invoice_no'] ?? '#' . ($pur['id'] ?? ''))); ?></a></td>
                            <td><?php echo htmlspecialchars((string) ($pur['supplier_name'] ?? '—')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($pur['invoice_date'] ?? '')); ?></td>
                            <td class="amt"><?php echo auragold_fmt_money($pur['grand_total'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="rd-empty">No purchase invoices yet. <a href="purchase-invoice.php">Record a purchase</a></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="rd-panel rd-panel--consignment">
            <div class="rd-panel-head">
                <h2><i class="feather icon-share" style="color:var(--rd-amber);margin-right:6px;"></i> Active consignments</h2>
                <a href="consignment-out.php" class="rd-badge rd-badge--link" style="text-decoration:none;">View all</a>
            </div>
            <?php if (!empty($wx['active_consignments'])): ?>
            <div class="table-responsive">
                <table class="rd-table">
                    <thead>
                        <tr>
                            <th>Memo no.</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wx['active_consignments'] as $co): ?>
                        <tr>
                            <td class="link-cell"><a href="consignment-out.php?id=<?php echo (int) ($co['id'] ?? 0); ?>"><?php echo htmlspecialchars((string) ($co['consignment_no'] ?? '#' . ($co['id'] ?? ''))); ?></a></td>
                            <td><?php echo htmlspecialchars((string) ($co['customer_name'] ?? '—')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($co['consignment_date'] ?? '')); ?></td>
                            <td class="amt"><?php echo auragold_fmt_money($co['grand_total'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="rd-empty">No active consignments. <a href="consignment-out.php">Create memo out</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="rd-foot">
    <span>Gold Matrix · Wholesaler dashboard</span>
    <span><a href="account-ledger.php">Account ledger</a> · <a href="consignment-out-report.php">Memo items</a> · <a href="dashboard-gold-rates.php">Gold rates</a></span>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('wholesaleSalesChart');
    if (!ctx || typeof Chart === 'undefined') return;
    var labels = <?php echo $labelsJson; ?>;
    var values = <?php echo $valuesJson; ?>;
    var gold = '#c5a864';
    var navy = '#11294b';
    var teal = '#14b8a6';
    var grad = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
    grad.addColorStop(0, 'rgba(20, 184, 166, 0.35)');
    grad.addColorStop(0.5, 'rgba(197, 168, 100, 0.25)');
    grad.addColorStop(1, 'rgba(124, 58, 237, 0.04)');
    var wholesaleChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: teal,
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
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
                    titleColor: teal,
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8
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
                    ticks: { color: '#64748b', font: { size: 11 } },
                    border: { display: false }
                }
            }
        }
    });
    window.addEventListener('resize', function() {
        if (wholesaleChart) wholesaleChart.resize();
    });
})();
</script>
