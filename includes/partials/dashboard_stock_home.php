<?php
if (!isset($stk) || !is_array($stk)) {
    $stk = auragold_stock_dashboard_jewelsteps();
}
$sx = auragold_stock_dashboard_extras();

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

$metalPieLabels = [];
$metalPieValues = [];
foreach ($stk['metal_chart'] ?? [] as $mc) {
    $w = (float) ($mc['weight'] ?? 0);
    if ($w > 0) {
        $metalPieLabels[] = (string) ($mc['label'] ?? '—');
        $metalPieValues[] = $w;
    }
}
$metalPieLabelsJson = json_encode($metalPieLabels, JSON_UNESCAPED_UNICODE);
$metalPieValuesJson = json_encode($metalPieValues, JSON_UNESCAPED_UNICODE);

$greetingHour = (int) date('G');
if ($greetingHour < 12) {
    $greeting = 'Good morning';
} elseif ($greetingHour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}
$todayLabel = date('l, d M Y');

if (!function_exists('stk_fmt_w')) {
    function stk_fmt_w($n) {
        return number_format((float) $n, 3);
    }
}
if (!function_exists('stk_fmt_q')) {
    function stk_fmt_q($n) {
        return number_format((float) $n, 2);
    }
}
if (!function_exists('stk_esc')) {
    function stk_esc($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
?>
<style>
.stk-dash {
    --stk-navy: #11294b;
    --stk-emerald: #059669;
    --stk-emerald-bright: #10b981;
    --stk-emerald-soft: #d1fae5;
    --stk-gold: #c5a864;
    --stk-gold-light: #e8d5a8;
    --stk-amber: #d97706;
    --stk-rose: #e11d48;
    --stk-sky: #0284c7;
    --stk-violet: #7c3aed;
    --stk-surface: #ffffff;
    --stk-muted: #64748b;
    --stk-border: rgba(17, 41, 75, 0.08);
    --stk-shadow: 0 4px 24px rgba(17, 41, 75, 0.08);
    --stk-shadow-hover: 0 12px 32px rgba(17, 41, 75, 0.12);
    max-width: 100%;
    padding: 4px 0 8px;
}

.stk-dash .stk-hero {
    background: linear-gradient(135deg, #064e3b 0%, #065f46 30%, var(--stk-navy) 65%, #1e3a5f 100%);
    border-radius: 20px;
    padding: 22px 26px;
    margin-bottom: 18px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(5, 150, 105, 0.18);
    border: 1px solid rgba(52, 211, 153, 0.2);
}
.stk-dash .stk-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 280px;
    height: 280px;
    background: radial-gradient(circle, rgba(52, 211, 153, 0.25) 0%, rgba(197, 168, 100, 0.12) 50%, transparent 70%);
    pointer-events: none;
}
.stk-dash .stk-hero::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #34d399, var(--stk-gold), #34d399, transparent);
}
.stk-dash .stk-hero-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.stk-dash .stk-hero h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 4px; letter-spacing: -0.02em; }
.stk-dash .stk-greeting { font-size: 13px; color: #a7f3d0; opacity: 0.95; }
.stk-dash .stk-date { font-size: 12px; color: rgba(255,255,255,0.65); margin-top: 2px; }
.stk-dash .stk-hero-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.stk-dash .btn-stk-emerald {
    background: linear-gradient(135deg, #34d399 0%, #059669 100%);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
    padding: 9px 18px;
    border-radius: 10px;
    border: none;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);
    transition: transform .15s, box-shadow .15s;
}
.stk-dash .btn-stk-emerald:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(5, 150, 105, 0.45);
    color: #fff;
    text-decoration: none;
}
.stk-dash .btn-stk-outline {
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
.stk-dash .btn-stk-outline:hover { background: rgba(255,255,255,0.18); color: #fff; text-decoration: none; }

.stk-dash .stk-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.stk-dash .stk-summary-item {
    border-radius: 16px;
    padding: 16px 18px;
    border: 1px solid transparent;
    box-shadow: var(--stk-shadow);
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
}
.stk-dash .stk-summary-item:hover { transform: translateY(-2px); box-shadow: var(--stk-shadow-hover); }
.stk-dash .stk-summary-item::after {
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
.stk-dash .stk-summary-item .lbl {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: 0.85;
}
.stk-dash .stk-summary-item .val {
    font-size: 1.35rem;
    font-weight: 800;
    margin-top: 6px;
    line-height: 1.15;
}
.stk-dash .stk-summary-item--value {
    background: linear-gradient(135deg, #ecfdf5 0%, #a7f3d0 55%, #6ee7b7 100%);
    border-color: rgba(5, 150, 105, 0.25);
}
.stk-dash .stk-summary-item--value::after { background: radial-gradient(circle, #34d399 0%, transparent 70%); }
.stk-dash .stk-summary-item--value .lbl { color: #047857; }
.stk-dash .stk-summary-item--value .val { color: #064e3b; }

.stk-dash .stk-summary-item--weight {
    background: linear-gradient(135deg, #fffbeb 0%, #ffedd5 55%, #fed7aa 100%);
    border-color: rgba(217, 119, 6, 0.2);
}
.stk-dash .stk-summary-item--weight::after { background: radial-gradient(circle, #fb923c 0%, transparent 70%); }
.stk-dash .stk-summary-item--weight .lbl { color: #c2410c; }
.stk-dash .stk-summary-item--weight .val { color: #7c2d12; }

.stk-dash .stk-summary-item--alert {
    background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 55%, #fecdd3 100%);
    border-color: rgba(225, 29, 72, 0.2);
}
.stk-dash .stk-summary-item--alert::after { background: radial-gradient(circle, #fb7185 0%, transparent 70%); }
.stk-dash .stk-summary-item--alert .lbl { color: #be123c; }
.stk-dash .stk-summary-item--alert .val { color: #9f1239; }

.stk-dash .stk-quick-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 22px;
}
.stk-dash .stk-quick-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--stk-surface);
    border: 1px solid var(--stk-border);
    border-radius: 12px;
    text-decoration: none;
    color: var(--stk-navy);
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(17,41,75,0.04);
    transition: all .15s;
}
.stk-dash .stk-quick-link:hover {
    box-shadow: var(--stk-shadow);
    color: var(--stk-navy);
    text-decoration: none;
    transform: translateY(-2px);
}
.stk-dash .stk-quick-link .ql-icon {
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
.stk-dash .stk-quick-link--journal { border-color: rgba(5,150,105,0.12); }
.stk-dash .stk-quick-link--journal:hover { border-color: rgba(5,150,105,0.35); background: #ecfdf5; }
.stk-dash .stk-quick-link--journal .ql-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
.stk-dash .stk-quick-link--opening { border-color: rgba(124,58,237,0.12); }
.stk-dash .stk-quick-link--opening:hover { border-color: rgba(124,58,237,0.35); background: #faf5ff; }
.stk-dash .stk-quick-link--opening .ql-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
.stk-dash .stk-quick-link--history { border-color: rgba(2,132,199,0.12); }
.stk-dash .stk-quick-link--history:hover { border-color: rgba(2,132,199,0.35); background: #f0f9ff; }
.stk-dash .stk-quick-link--history .ql-icon { background: linear-gradient(135deg, #e0f2fe, #bae6fd); color: #0369a1; }
.stk-dash .stk-quick-link--analysis { border-color: rgba(217,119,6,0.12); }
.stk-dash .stk-quick-link--analysis:hover { border-color: rgba(217,119,6,0.35); background: #fffbeb; }
.stk-dash .stk-quick-link--analysis .ql-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }

.stk-dash .stk-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 14px;
}
.stk-dash .stk-kpi {
    border-radius: 16px;
    padding: 16px;
    border: 1px solid transparent;
    box-shadow: var(--stk-shadow);
    transition: transform .18s ease, box-shadow .18s ease;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.stk-dash .stk-kpi::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    border-radius: 16px 0 0 16px;
}
.stk-dash .stk-kpi:hover { transform: translateY(-3px); box-shadow: var(--stk-shadow-hover); }
.stk-dash .stk-kpi .txt .t {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--stk-muted);
}
.stk-dash .stk-kpi .txt .v { font-size: 1.25rem; font-weight: 800; margin-top: 4px; line-height: 1.2; }
.stk-dash .stk-kpi .txt .q { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.stk-dash .stk-kpi .txt .q strong { color: #475569; }
.stk-dash .stk-kpi .ic {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.stk-dash .stk-kpi--products { background: linear-gradient(145deg, #faf5ff 0%, #f3e8ff 100%); border-color: rgba(124,58,237,0.15); }
.stk-dash .stk-kpi--products::before { background: linear-gradient(180deg, #a78bfa, #7c3aed); }
.stk-dash .stk-kpi--products .ic { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
.stk-dash .stk-kpi--products .v { color: #5b21b6; }

.stk-dash .stk-kpi--zero { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); border-color: rgba(217,119,6,0.15); }
.stk-dash .stk-kpi--zero::before { background: linear-gradient(180deg, #fbbf24, #d97706); }
.stk-dash .stk-kpi--zero .ic { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
.stk-dash .stk-kpi--zero .v { color: #92400e; }

.stk-dash .stk-kpi--in { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); border-color: rgba(5,150,105,0.15); }
.stk-dash .stk-kpi--in::before { background: linear-gradient(180deg, #34d399, #059669); }
.stk-dash .stk-kpi--in .ic { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; }
.stk-dash .stk-kpi--in .v { color: #065f46; }

.stk-dash .stk-kpi--out { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); border-color: rgba(225,29,72,0.15); }
.stk-dash .stk-kpi--out::before { background: linear-gradient(180deg, #fb7185, #e11d48); }
.stk-dash .stk-kpi--out .ic { background: linear-gradient(135deg, #ffe4e6, #fecdd3); color: #be123c; }
.stk-dash .stk-kpi--out .v { color: #9f1239; }

.stk-dash .stk-metal-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 22px;
}
.stk-dash .stk-metal-card {
    border-radius: 14px;
    padding: 14px;
    border: 1px solid transparent;
    box-shadow: var(--stk-shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    transition: transform .15s;
}
.stk-dash .stk-metal-card:hover { transform: translateY(-2px); }
.stk-dash .stk-metal-card .nm { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--stk-muted); letter-spacing: .03em; }
.stk-dash .stk-metal-card .wt { font-size: 1.1rem; font-weight: 800; margin-top: 3px; }
.stk-dash .stk-metal-card .qt { font-size: 10px; color: #94a3b8; margin-top: 2px; }
.stk-dash .stk-metal-card .mi {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}
.stk-dash .stk-metal-card--gold { background: linear-gradient(145deg, #fffbeb, #fef3c7); border-color: rgba(217,119,6,0.15); }
.stk-dash .stk-metal-card--gold .mi { background: #fde68a; color: #a16207; }
.stk-dash .stk-metal-card--gold .wt { color: #92400e; }
.stk-dash .stk-metal-card--silver { background: linear-gradient(145deg, #eff6ff, #dbeafe); border-color: rgba(37,99,235,0.15); }
.stk-dash .stk-metal-card--silver .mi { background: #bfdbfe; color: #1d4ed8; }
.stk-dash .stk-metal-card--silver .wt { color: #1e3a8a; }
.stk-dash .stk-metal-card--diamond { background: linear-gradient(145deg, #eef2ff, #e0e7ff); border-color: rgba(79,70,229,0.15); }
.stk-dash .stk-metal-card--diamond .mi { background: #c7d2fe; color: #4338ca; }
.stk-dash .stk-metal-card--diamond .wt { color: #3730a3; }
.stk-dash .stk-metal-card--platinum { background: linear-gradient(145deg, #f8fafc, #f1f5f9); border-color: rgba(100,116,139,0.15); }
.stk-dash .stk-metal-card--platinum .mi { background: #e2e8f0; color: #475569; }
.stk-dash .stk-metal-card--platinum .wt { color: #334155; }
.stk-dash .stk-metal-card--other { background: linear-gradient(145deg, #fff1f2, #ffe4e6); border-color: rgba(225,29,72,0.12); }
.stk-dash .stk-metal-card--other .mi { background: #fecdd3; color: #be123c; }
.stk-dash .stk-metal-card--other .wt { color: #9f1239; }

.stk-dash .stk-panel {
    background: var(--stk-surface);
    border-radius: 18px;
    border: 1px solid var(--stk-border);
    box-shadow: var(--stk-shadow);
    height: 100%;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    margin-bottom: 0;
}
.stk-dash .stk-panel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--stk-gold), var(--stk-gold-light), var(--stk-gold));
    opacity: 0.85;
}
.stk-dash .stk-panel--chart::before { background: linear-gradient(90deg, #059669, var(--stk-gold), #7c3aed); }
.stk-dash .stk-panel--karat::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.stk-dash .stk-panel--metal::before { background: linear-gradient(90deg, #eab308, #f59e0b); }
.stk-dash .stk-panel--low::before { background: linear-gradient(90deg, #e11d48, #fb7185); }
.stk-dash .stk-panel--branch::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
.stk-dash .stk-panel--journal::before { background: linear-gradient(90deg, #059669, #34d399); }

.stk-dash .stk-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px 12px;
}
.stk-dash .stk-panel-head h2 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--stk-navy);
    margin: 0;
}
.stk-dash .stk-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    color: var(--stk-muted);
    border: 1px solid var(--stk-border);
}
.stk-dash .stk-badge--emerald {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
    border-color: rgba(5,150,105,0.2);
}
.stk-dash .stk-badge--link {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #4338ca;
    border-color: rgba(79,70,229,0.2);
    text-decoration: none;
}
.stk-dash .stk-panel-body { flex: 1; padding: 0 20px 18px; overflow: auto; }
.stk-dash .stk-panel-note { font-size: 11px; color: var(--stk-muted); padding: 0 20px 12px; line-height: 1.45; }
.stk-dash .stk-chart-wrap { position: relative; height: 280px; padding: 0 12px 16px; }
.stk-dash .stk-chart-wrap--sm { height: 220px; }

.stk-dash .karat-row { margin-bottom: 14px; }
.stk-dash .karat-row .kr-h {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    margin-bottom: 6px;
}
.stk-dash .karat-row .kr-title { font-weight: 700; color: #b45309; }
.stk-dash .karat-row .kr-num { font-weight: 800; color: var(--stk-navy); font-size: 14px; }
.stk-dash .karat-row .progress { height: 12px; border-radius: 8px; background: #f1f5f9; }
.stk-dash .karat-row .progress-bar { background: linear-gradient(90deg, #fbbf24, #f59e0b, #d97706); border-radius: 8px; }

.stk-dash .stk-table { width: 100%; font-size: 13px; margin: 0; }
.stk-dash .stk-table thead th {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--stk-muted);
    border-bottom: 2px solid #f1f5f9;
    padding: 8px 10px;
    background: transparent;
}
.stk-dash .stk-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    color: #334155;
}
.stk-dash .stk-table tbody tr:hover td { background: #fafbfc; }
.stk-dash .stk-table tbody tr:last-child td { border-bottom: 0; }
.stk-dash .stk-table .w-cell { color: var(--stk-violet); font-weight: 700; text-align: right; }
.stk-dash .stk-table .q-cell { color: var(--stk-rose); font-weight: 700; text-align: right; }
.stk-dash .stk-table .amt { font-weight: 700; color: var(--stk-navy); text-align: right; }
.stk-dash .stk-table .link-cell a { color: var(--stk-navy); font-weight: 600; text-decoration: none; }
.stk-dash .stk-table .link-cell a:hover { color: var(--stk-emerald); }

.stk-dash .stk-thumb {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: cover;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
}
.stk-dash .stk-thumb-ph {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    font-size: 16px;
    flex-shrink: 0;
}
.stk-dash .stk-product-cell { display: flex; align-items: center; gap: 10px; }
.stk-dash .stk-product-name { font-weight: 600; color: #db2777; }
.stk-dash .stk-line-note { font-size: 11px; color: var(--stk-muted); font-weight: 500; }

.stk-dash .stk-empty {
    text-align: center;
    padding: 32px 16px;
    color: var(--stk-muted);
    font-size: 13px;
}
.stk-dash .stk-foot {
    margin-top: 24px;
    padding-top: 14px;
    border-top: 1px solid var(--stk-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 12px;
    color: #94a3b8;
}
.stk-dash .stk-foot a { color: var(--stk-muted); text-decoration: none; }
.stk-dash .stk-foot a:hover { color: var(--stk-emerald); }

@media (max-width: 1399.98px) {
    .stk-dash .stk-metal-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 991.98px) {
    .stk-dash .stk-kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .stk-dash .stk-metal-grid { grid-template-columns: repeat(2, 1fr); }
    .stk-dash .stk-quick-links { grid-template-columns: repeat(2, 1fr); }
    .stk-dash .stk-summary-strip { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .stk-dash .stk-hero { padding: 18px 16px; }
    .stk-dash .stk-hero h1 { font-size: 1.2rem; }
    .stk-dash .stk-hero-inner { flex-direction: column; align-items: stretch; }
    .stk-dash .stk-hero-actions { width: 100%; }
    .stk-dash .stk-hero-actions .btn-stk-emerald,
    .stk-dash .stk-hero-actions .btn-stk-outline { flex: 1; text-align: center; justify-content: center; }
    .stk-dash .stk-kpi-grid { grid-template-columns: 1fr; }
    .stk-dash .stk-metal-grid { grid-template-columns: 1fr; }
    .stk-dash .stk-quick-links { grid-template-columns: 1fr; }
    .stk-dash .stk-chart-wrap { height: 220px; }
}
</style>

<div class="stk-dash">

<div class="stk-hero">
    <div class="stk-hero-inner">
        <div>
            <div class="stk-greeting"><?php echo stk_esc($greeting); ?></div>
            <h1>Stock Dashboard</h1>
            <div class="stk-date"><?php echo stk_esc($todayLabel); ?></div>
        </div>
        <div class="stk-hero-actions">
            <a class="btn-stk-emerald" href="stock-journal.php"><i class="feather icon-book"></i> Stock Journal</a>
            <a class="btn-stk-outline" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
        </div>
    </div>
</div>

<div class="stk-summary-strip">
    <div class="stk-summary-item stk-summary-item--value">
        <div class="lbl">Total stock value</div>
        <div class="val"><?php echo stk_fmt_q($stk['totals']['value'] ?? 0); ?></div>
    </div>
    <div class="stk-summary-item stk-summary-item--weight">
        <div class="lbl">Total weight (gm)</div>
        <div class="val"><?php echo stk_fmt_w($stk['totals']['weight'] ?? 0); ?></div>
    </div>
    <div class="stk-summary-item stk-summary-item--alert">
        <div class="lbl">Low stock alerts</div>
        <div class="val"><?php echo number_format((int) ($sx['low_stock_count'] ?? 0)); ?></div>
    </div>
</div>

<div class="stk-quick-links">
    <a class="stk-quick-link stk-quick-link--journal" href="stock-journal.php"><span class="ql-icon"><i class="feather icon-book"></i></span> Stock Journal</a>
    <a class="stk-quick-link stk-quick-link--opening" href="product-opening.php"><span class="ql-icon"><i class="feather icon-package"></i></span> Product Opening</a>
    <a class="stk-quick-link stk-quick-link--history" href="stock-history.php"><span class="ql-icon"><i class="feather icon-clock"></i></span> Stock History</a>
    <a class="stk-quick-link stk-quick-link--analysis" href="gold-silver-analysis.php"><span class="ql-icon"><i class="feather icon-bar-chart-2"></i></span> Gold / Silver</a>
</div>

<div class="stk-kpi-grid">
    <div class="stk-kpi stk-kpi--products">
        <div class="txt">
            <div class="t">Total products</div>
            <div class="v"><?php echo number_format((int) $k['total_products']); ?></div>
            <div class="q">Qty <strong><?php echo stk_fmt_q($k['total_products_qty']); ?></strong></div>
        </div>
        <div class="ic"><i class="feather icon-package"></i></div>
    </div>
    <div class="stk-kpi stk-kpi--zero">
        <div class="txt">
            <div class="t">Zero stock</div>
            <div class="v"><?php echo number_format((int) $k['zero_stock_lines']); ?></div>
            <div class="q">Qty <strong><?php echo stk_fmt_q($k['zero_stock_qty']); ?></strong></div>
        </div>
        <div class="ic"><i class="feather icon-box"></i></div>
    </div>
    <div class="stk-kpi stk-kpi--in">
        <div class="txt">
            <div class="t">Inward stock</div>
            <div class="v"><?php echo stk_fmt_w($k['inward_weight']); ?></div>
            <div class="q">Qty <strong><?php echo stk_fmt_q($k['inward_qty']); ?></strong></div>
        </div>
        <div class="ic"><i class="feather icon-log-in"></i></div>
    </div>
    <div class="stk-kpi stk-kpi--out">
        <div class="txt">
            <div class="t">Outward stock</div>
            <div class="v"><?php echo stk_fmt_w($k['outward_weight']); ?></div>
            <div class="q">Qty <strong><?php echo stk_fmt_q($k['outward_qty']); ?></strong></div>
        </div>
        <div class="ic"><i class="feather icon-log-out"></i></div>
    </div>
</div>

<?php
$metalThemes = ['gold', 'silver', 'diamond', 'platinum', 'other'];
$metalIcons = ['icon-award', 'icon-layers', 'icon-circle', 'icon-heart', 'icon-settings'];
?>
<div class="stk-metal-grid">
    <?php foreach ($metalCards as $idx => $mc):
        $theme = $metalThemes[$idx] ?? 'other';
        $fi = $metalIcons[$idx] ?? 'icon-package';
    ?>
    <div class="stk-metal-card stk-metal-card--<?php echo stk_esc($theme); ?>">
        <div>
            <div class="nm"><?php echo stk_esc($mc['name'] ?? '—'); ?></div>
            <div class="wt"><?php echo stk_fmt_w($mc['w'] ?? 0); ?></div>
            <div class="qt">Qty <?php echo stk_fmt_q($mc['q'] ?? 0); ?></div>
        </div>
        <div class="mi"><i class="feather <?php echo stk_esc($fi); ?>"></i></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="stk-panel stk-panel--chart">
            <div class="stk-panel-head">
                <h2><i class="feather icon-layers" style="color:var(--stk-emerald);margin-right:6px;"></i> Metal-wise stock by branch</h2>
                <span class="stk-badge stk-badge--emerald"><?php echo (int) ($sx['branch_count'] ?? 0); ?> branches</span>
            </div>
            <?php if (empty($mcb['branch_labels'] ?? []) || empty($mcb['datasets'] ?? [])): ?>
            <div class="stk-empty">No stock rows for this scope.</div>
            <?php else: ?>
            <div class="stk-chart-wrap">
                <canvas id="stkMetalChart"></canvas>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="stk-panel stk-panel--metal">
            <div class="stk-panel-head">
                <h2><i class="feather icon-pie-chart" style="color:#d97706;margin-right:6px;"></i> Metal mix</h2>
            </div>
            <div class="stk-chart-wrap stk-chart-wrap--sm">
                <canvas id="stkMetalPieChart"></canvas>
            </div>
            <div class="stk-panel-note mb-0 pb-3">
                Gold: <strong><?php echo stk_fmt_w($sx['gold_weight']); ?></strong> gm ·
                Silver: <strong><?php echo stk_fmt_w($sx['silver_weight']); ?></strong> gm
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="stk-panel stk-panel--karat">
            <div class="stk-panel-head">
                <h2><i class="feather icon-sliders" style="color:#d97706;margin-right:6px;"></i> Karat-wise gold stock</h2>
            </div>
            <div class="stk-panel-body">
                <?php if (empty($stk['karatwise'])): ?>
                <div class="stk-empty">No gold karat breakdown yet.</div>
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
                        <span class="kr-title"><?php echo stk_esc($kr['title'] ?? ''); ?></span>
                        <span class="kr-num"><?php echo stk_fmt_w($w); ?> / <?php echo number_format($q, 0); ?></span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo (float) $pct; ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="stk-panel stk-panel--branch">
            <div class="stk-panel-head">
                <h2><i class="feather icon-map-pin" style="color:var(--stk-sky);margin-right:6px;"></i> Stock by branch</h2>
            </div>
            <div class="stk-panel-body">
                <?php if (empty($stk['by_branch'])): ?>
                <div class="stk-empty">No branch stock data.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="stk-table">
                        <thead><tr><th>Branch</th><th class="text-right">Weight</th><th class="text-right">Value</th></tr></thead>
                        <tbody>
                        <?php foreach ($stk['by_branch'] as $br): ?>
                            <tr>
                                <td><?php echo stk_esc($br['branch_name'] ?? '—'); ?></td>
                                <td class="w-cell"><?php echo stk_fmt_w($br['sum_current_weight'] ?? 0); ?></td>
                                <td class="amt"><?php echo stk_fmt_q($br['sum_value'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="stk-panel stk-panel--low">
            <div class="stk-panel-head">
                <h2><i class="feather icon-alert-triangle" style="color:var(--stk-rose);margin-right:6px;"></i> Low stock items</h2>
                <span class="stk-badge" style="background:#ffe4e6;color:#be123c;border-color:rgba(225,29,72,0.2);"><?php echo (int) ($sx['low_stock_count'] ?? 0); ?> alerts</span>
            </div>
            <p class="stk-panel-note">Products with qty ≤ 1 or weight ≤ 0 at branch level.</p>
            <div class="stk-panel-body pt-0">
                <?php if (empty($stk['low_stock'])): ?>
                <div class="stk-empty">No low-stock items — inventory looks healthy.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="stk-table">
                        <thead><tr><th>Item</th><th>Branch</th><th class="text-right">Weight</th><th class="text-right">Qty</th></tr></thead>
                        <tbody>
                        <?php foreach ($stk['low_stock'] as $ls):
                            $img = stk_stock_img_url($ls['images'] ?? '');
                            $lc = (int) ($ls['low_line_count'] ?? 1);
                            $pn = (string) ($ls['product_name'] ?? '');
                            $lcNote = $lc > 1 ? ' (' . $lc . ' low lines)' : '';
                        ?>
                            <tr>
                                <td>
                                    <div class="stk-product-cell">
                                        <?php if ($img !== ''): ?>
                                            <img class="stk-thumb" src="<?php echo stk_esc($img); ?>" alt="">
                                        <?php else: ?>
                                            <span class="stk-thumb-ph"><i class="feather icon-image"></i></span>
                                        <?php endif; ?>
                                        <span>
                                            <span class="stk-product-name"><?php echo stk_esc($pn); ?></span>
                                            <?php if ($lcNote !== ''): ?><span class="stk-line-note"><?php echo stk_esc($lcNote); ?></span><?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo stk_esc($ls['branch_name'] ?? '—'); ?></td>
                                <td class="w-cell"><?php echo stk_fmt_w($ls['total_weight'] ?? $ls['current_weight'] ?? 0); ?></td>
                                <td class="q-cell"><?php echo stk_fmt_q($ls['total_qty'] ?? $ls['current_qty'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="stk-panel stk-panel--journal">
            <div class="stk-panel-head">
                <h2><i class="feather icon-activity" style="color:var(--stk-emerald);margin-right:6px;"></i> Recent stock movements</h2>
                <a href="stock-history.php" class="stk-badge stk-badge--link">View history</a>
            </div>
            <div class="stk-panel-body">
                <?php if (empty($sx['recent_journal'])): ?>
                <div class="stk-empty">No recent journal entries.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="stk-table">
                        <thead><tr><th>Ref</th><th>Product</th><th>Date</th><th class="text-right">Wt</th></tr></thead>
                        <tbody>
                        <?php foreach ($sx['recent_journal'] as $sj): ?>
                            <tr>
                                <td class="link-cell"><a href="stock-journal.php"><?php echo stk_esc($sj['sj_invoice_no'] ?? '#' . ($sj['id'] ?? '')); ?></a></td>
                                <td><?php echo stk_esc($sj['product_name'] ?? $sj['barcode'] ?? '—'); ?></td>
                                <td><?php echo stk_esc($sj['sj_date'] ?? ''); ?></td>
                                <td class="w-cell"><?php echo stk_fmt_w($sj['gross_weight'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="stk-foot">
    <span>Gold Matrix · Stock dashboard · Rows: <?php echo (int) ($stk['totals']['rows'] ?? 0); ?> · Branches: <?php echo (int) ($sx['branch_count'] ?? 0); ?></span>
    <span><a href="stock-history.php">Stock history</a> · <a href="gold-silver-analysis.php">Gold / Silver analysis</a></span>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function() {
    if (typeof Chart === 'undefined') return;
    var navy = '#11294b';
    var gold = '#c5a864';
    var emerald = '#059669';

    var ctxBar = document.getElementById('stkMetalChart');
    if (ctxBar) {
        var branchLabels = <?php echo $branchChartLabelsJson; ?>;
        var datasets = <?php echo $branchChartDatasetsJson; ?>;
        if (branchLabels.length && datasets.length) {
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: branchLabels,
                    datasets: datasets.map(function(ds) {
                        ds.borderWidth = 1;
                        ds.borderColor = 'rgba(255,255,255,0.6)';
                        ds.borderRadius = 4;
                        return ds;
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } },
                        tooltip: {
                            backgroundColor: navy,
                            titleColor: gold,
                            bodyColor: '#fff',
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
                        x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 45, font: { size: 11 } } },
                        y: { stacked: true, beginAtZero: true, grid: { color: 'rgba(17,41,75,0.06)' }, ticks: { font: { size: 11 } } }
                    }
                }
            });
        }
    }

    var ctxPie = document.getElementById('stkMetalPieChart');
    if (ctxPie) {
        var pieLabels = <?php echo $metalPieLabelsJson; ?>;
        var pieValues = <?php echo $metalPieValuesJson; ?>;
        if (!pieLabels.length) {
            pieLabels = ['No data'];
            pieValues = [1];
        }
        var pieColors = ['#eab308', '#38bdf8', '#a78bfa', '#94a3b8', '#f472b6', '#34d399', '#fb923c', '#818cf8'];
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieValues,
                    backgroundColor: pieLabels[0] === 'No data' ? ['#e2e8f0'] : pieColors.slice(0, pieLabels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, padding: 8 } },
                    tooltip: {
                        backgroundColor: navy,
                        titleColor: gold,
                        bodyColor: '#fff',
                        callbacks: {
                            label: function(c) {
                                return c.label + ': ' + Number(c.parsed).toFixed(3) + ' gm';
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>
