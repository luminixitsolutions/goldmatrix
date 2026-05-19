<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';

// Require active user session; redirect to login if not logged in
if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/branch_profile_schema.php';
require_once __DIR__ . '/includes/auragold_branch_data_scope.php';
require_once __DIR__ . '/includes/dashboard_currency_display.php';
auragold_ensure_tbl_branches_profile_columns(isset($conn_master) ? $conn_master : null);

/** Which branch’s dashboard rates to show and save (0 = legacy global rows). Branch logins are always scoped to their branch. */
$dash_rates_branch_id = isset($_GET['branch']) ? (int) $_GET['branch'] : 0;
$dash_effective_branch = function_exists('auragold_effective_branch_id') ? (int) auragold_effective_branch_id() : 0;
if ($dash_effective_branch > 0) {
    $dash_rates_branch_id = $dash_effective_branch;
}
$dash_branch_list = [];
if (isset($conn_master) && $conn_master && function_exists('getListMaster')) {
    $dash_branch_list = getListMaster('SELECT id, name FROM tbl_branches WHERE status = 1 ORDER BY name ASC');
}
if (!is_array($dash_branch_list)) {
    $dash_branch_list = [];
}

$currencies = getList("SELECT id, name, symbol, is_base FROM tbl_currency WHERE status = 1 ORDER BY is_base DESC, name ASC");
if (!is_array($currencies)) {
    $currencies = [];
}
$base_currency_row = auragold_dashboard_resolve_base_currency($currencies);
$base_currency_id = $base_currency_row ? (int) ($base_currency_row['id'] ?? 0) : 0;
$base_currency_label = $base_currency_row
    ? trim((string) ($base_currency_row['symbol'] ?? '')) ?: trim((string) ($base_currency_row['name'] ?? ''))
    : 'AED';
if ($base_currency_label === '') {
    $base_currency_label = 'AED';
}

$pref_currency_id = 0;
$dash_bid = auragold_dashboard_branch_id_for_currency_preferences();
if ($dash_bid > 0 && isset($conn_master) && $conn_master) {
    $brCur = @mysqli_query(
        $conn_master,
        'SELECT profile_base_currency_id FROM tbl_branches WHERE id = ' . (int) $dash_bid . ' LIMIT 1'
    );
    if ($brCur && ($brRow = mysqli_fetch_assoc($brCur))) {
        $pref_currency_id = (int) ($brRow['profile_base_currency_id'] ?? 0);
    }
    if ($brCur) {
        mysqli_free_result($brCur);
    }
}

$display_res = auragold_dashboard_resolve_display_currency($currencies, $base_currency_row, $base_currency_id, $pref_currency_id);
$display_currency_row = $display_res['row'];
$display_currency_id = (int) ($display_res['id'] ?? 0);

$currency = $display_currency_row
    ? trim((string) ($display_currency_row['symbol'] ?? '')) ?: trim((string) ($display_currency_row['name'] ?? ''))
    : 'AED';
if ($currency === '') {
    $currency = 'AED';
}

$dashboard_exchange_rates = auragold_dashboard_currency_exchange_map(isset($conn) ? $conn : null);
$dash_currency_payload = [
    'baseId'  => $base_currency_id,
    'rates'   => $dashboard_exchange_rates,
    'currencies' => array_map(static function ($c) {
        $sym = trim((string) ($c['symbol'] ?? ''));
        $nm = trim((string) ($c['name'] ?? ''));
        return [
            'id'     => (int) ($c['id'] ?? 0),
            'name'   => $nm,
            'symbol' => $sym !== '' ? $sym : $nm,
        ];
    }, $currencies),
    'displayCurrencyId' => $display_currency_id,
];
$rates_updated = date('d-m-Y  g:i A');

/** Metal-wise & carat-wise defaults; merged from tbl_dashboard_metal_rates when present. */
$dashboard_metals = [
    'gold' => [
        'label'       => 'Gold',
        'short'       => 'Au',
        'hero_class'  => 'metal-accent-gold',
        'source_url'  => 'https://igold.ae/gold-rate',
        'ounce_rate'  => '0',
        'headline_rate' => '544.57',
        'headline_carat' => '24K',
        'table_carat_label' => 'Carat / Purity',
        'rows' => [
            ['carat' => '24K', 'new_rate' => '544.57', 'sell_premium' => '—', 'conv' => '1', 'current' => '544.57'],
            ['carat' => '22K', 'new_rate' => '504.20', 'sell_premium' => '—', 'conv' => '1', 'current' => '504.20'],
            ['carat' => '21K', 'new_rate' => '481.26', 'sell_premium' => '—', 'conv' => '1', 'current' => '481.26'],
            ['carat' => '18K', 'new_rate' => '412.51', 'sell_premium' => '—', 'conv' => '1', 'current' => '412.51'],
            ['carat' => '14K', 'new_rate' => '321.50', 'sell_premium' => '—', 'conv' => '1', 'current' => '321.50'],
            ['carat' => '10K', 'new_rate' => '229.40', 'sell_premium' => '—', 'conv' => '1', 'current' => '229.40'],
        ],
        'cards' => [
            ['label' => '24K', 'value' => '544.57', 'class' => 'c24'],
            ['label' => '22K', 'value' => '504.20', 'class' => 'c22'],
            ['label' => '21K', 'value' => '481.26', 'class' => 'c21'],
            ['label' => '18K', 'value' => '412.51', 'class' => 'c18'],
            ['label' => '14K', 'value' => '321.50', 'class' => 'c14'],
            ['label' => '10K', 'value' => '229.40', 'class' => 'c10'],
        ],
    ],
    'silver' => [
        'label'       => 'Silver',
        'short'       => 'Ag',
        'hero_class'  => 'metal-accent-silver',
        'source_url'  => 'https://www.kitco.com/charts/livesilver.html',
        'ounce_rate'  => '0',
        'headline_rate' => '3.85',
        'headline_carat' => '999',
        'table_carat_label' => 'Purity',
        'rows' => [
            ['carat' => '999', 'new_rate' => '3.85', 'sell_premium' => '—', 'conv' => '1', 'current' => '3.85'],
            ['carat' => '958', 'new_rate' => '3.69', 'sell_premium' => '—', 'conv' => '1', 'current' => '3.69'],
            ['carat' => '925', 'new_rate' => '3.56', 'sell_premium' => '—', 'conv' => '1', 'current' => '3.56'],
            ['carat' => '875', 'new_rate' => '3.37', 'sell_premium' => '—', 'conv' => '1', 'current' => '3.37'],
        ],
        'cards' => [
            ['label' => '999', 'value' => '3.85', 'class' => 's999'],
            ['label' => '958', 'value' => '3.69', 'class' => 's958'],
            ['label' => '925', 'value' => '3.56', 'class' => 's925'],
            ['label' => '875', 'value' => '3.37', 'class' => 's875'],
        ],
    ],
    'platinum' => [
        'label'       => 'Platinum',
        'short'       => 'Pt',
        'hero_class'  => 'metal-accent-platinum',
        'source_url'  => '',
        'ounce_rate'  => '0',
        'headline_rate' => '0.00',
        'headline_carat' => '950',
        'table_carat_label' => 'Purity',
        'rows' => [
            ['carat' => '999', 'new_rate' => '0.00', 'sell_premium' => '—', 'conv' => '1', 'current' => '0.00'],
            ['carat' => '950', 'new_rate' => '0.00', 'sell_premium' => '—', 'conv' => '1', 'current' => '0.00'],
            ['carat' => '900', 'new_rate' => '0.00', 'sell_premium' => '—', 'conv' => '1', 'current' => '0.00'],
        ],
        'cards' => [
            ['label' => '999', 'value' => '0.00', 'class' => 'pt999'],
            ['label' => '950', 'value' => '0.00', 'class' => 'pt950'],
            ['label' => '900', 'value' => '0.00', 'class' => 'pt900'],
        ],
    ],
    'diamond' => [
        'label'       => 'Diamond',
        'short'       => 'Di',
        'hero_class'  => 'metal-accent-diamond',
        'source_url'  => '—',
        'ounce_rate'  => '—',
        'headline_rate' => '12,500',
        'headline_carat' => '1.00 ct RAP',
        'table_carat_label' => 'Size / Carat',
        'rows' => [
            ['carat' => '0.30 ct', 'new_rate' => '2,800', 'sell_premium' => '—', 'conv' => '1', 'current' => '2,800'],
            ['carat' => '0.50 ct', 'new_rate' => '5,200', 'sell_premium' => '—', 'conv' => '1', 'current' => '5,200'],
            ['carat' => '0.70 ct', 'new_rate' => '7,900', 'sell_premium' => '—', 'conv' => '1', 'current' => '7,900'],
            ['carat' => '1.00 ct', 'new_rate' => '12,500', 'sell_premium' => '—', 'conv' => '1', 'current' => '12,500'],
            ['carat' => '1.50 ct', 'new_rate' => '21,000', 'sell_premium' => '—', 'conv' => '1', 'current' => '21,000'],
            ['carat' => '2.00 ct', 'new_rate' => '38,000', 'sell_premium' => '—', 'conv' => '1', 'current' => '38,000'],
        ],
        'cards' => [
            ['label' => '0.30', 'value' => '2,800', 'class' => 'd03'],
            ['label' => '0.50', 'value' => '5,200', 'class' => 'd05'],
            ['label' => '0.70', 'value' => '7,900', 'class' => 'd07'],
            ['label' => '1.00', 'value' => '12,500', 'class' => 'd10'],
            ['label' => '1.50', 'value' => '21,000', 'class' => 'd15'],
            ['label' => '2.00', 'value' => '38,000', 'class' => 'd20'],
        ],
    ],
];

require_once __DIR__ . '/includes/dashboard_carat_master.php';
require_once __DIR__ . '/includes/auragold_dashboard_metal_images.php';
if (isset($conn) && $conn) {
    auragold_dashboard_apply_carat_master_rows($conn, $dashboard_metals);
}

require_once __DIR__ . '/includes/dashboard_metal_rates_db.php';
$__db_rates = auragold_load_dashboard_metals_from_db(isset($conn) ? $conn : null, $dashboard_metals, $dash_rates_branch_id);
$dashboard_metals = $__db_rates['metals'];
if (!empty($__db_rates['rates_updated'])) {
    $rates_updated = $__db_rates['rates_updated'];
}

if (isset($conn) && $conn) {
    require_once __DIR__ . '/includes/auragold_metal_dashboard_image_schema.php';
    auragold_ensure_tbl_metal_dashboard_images($conn);
    $dashboard_metals = auragold_dashboard_filter_metals_by_master_visibility($conn, $dashboard_metals);
}

/** Today's gold strip (24K–18K): tbl_settings matches sale invoices; dashboard cards fill gaps. Skip global tbl_settings when viewing a branch-specific rate sheet. */
$dash_today_gold_bar = ['24K' => null, '22K' => null, '21K' => null, '18K' => null];
if ($dash_rates_branch_id <= 0 && isset($conn) && $conn) {
    $tchk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_settings'");
    if ($tchk && mysqli_num_rows($tchk) > 0) {
        mysqli_free_result($tchk);
        // Use SELECT * so missing gold_rate_* columns (older DBs) do not throw mysqli_sql_exception.
        $srow = getRecord('SELECT * FROM tbl_settings LIMIT 1');
        if (is_array($srow)) {
            foreach (['24K' => 'gold_rate_24k', '22K' => 'gold_rate_22k', '21K' => 'gold_rate_21k', '18K' => 'gold_rate_18k'] as $gk => $col) {
                if (array_key_exists($col, $srow) && $srow[$col] !== '' && $srow[$col] !== null) {
                    $dash_today_gold_bar[$gk] = (float) $srow[$col];
                }
            }
        }
    } elseif ($tchk) {
        mysqli_free_result($tchk);
    }
}
if (isset($dashboard_metals['gold']['cards']) && is_array($dashboard_metals['gold']['cards'])) {
    foreach ($dashboard_metals['gold']['cards'] as $c) {
        $lab = trim((string) ($c['label'] ?? ''));
        if (!in_array($lab, ['24K', '22K', '21K', '18K'], true)) {
            continue;
        }
        if ($dash_today_gold_bar[$lab] === null) {
            $dash_today_gold_bar[$lab] = (float) str_replace([',', ' '], '', (string) ($c['value'] ?? '0'));
        }
    }
}

/** Hero + ticker: only Masters images (Carat / Metal upload or URL). No stock placeholders. */
$dash_dashboard_metal_img_urls = [];
$dash_carat_row_imgs = [];
if (isset($conn) && $conn) {
    $dash_dashboard_metal_img_urls = array_merge(
        auragold_dashboard_metal_images_from_carats($conn),
        auragold_dashboard_metal_images_from_tbl_metal($conn)
    );
    $dash_carat_row_imgs = auragold_dashboard_carat_images_map_by_metal_key($conn);
}

/** One-line marquee: Gold | Silver | Diamond — bold karat/purity, faint values, · between pairs. */
$dash_mq_h = static function ($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$dash_mq_join_pairs = static function (array $pairs) {
    return implode('<span class="dash-mq-sep-in" aria-hidden="true">·</span>', $pairs);
};
$dash_mq_img_snippet = static function ($url, string $cssClass) use ($dash_mq_h): string {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    return '<img class="' . $dash_mq_h($cssClass) . '" src="' . $dash_mq_h($url) . '" alt="" width="28" height="28" loading="lazy" decoding="async">';
};
$dash_mq_pair_src = static function ($metalKey, $lab, array $rowImgs, array $metalImgs): string {
    $lab = trim((string) $lab);
    if ($lab !== '' && !empty($rowImgs[$metalKey][$lab])) {
        return (string) $rowImgs[$metalKey][$lab];
    }
    return trim((string) ($metalImgs[$metalKey] ?? ''));
};
$dash_marquee_blocks = [];
$dash_mq_build_pair = static function (string $metalKey, string $lab, string $val) use (
    $dash_mq_h,
    $dash_mq_img_snippet,
    $dash_mq_pair_src,
    $dash_carat_row_imgs,
    $dash_dashboard_metal_img_urls
): string {
    $mk = strtolower($metalKey);
    $layouts = [
        'gold' => ['wrap' => 'dash-mq-pair dash-mq-pair-gold', 'img' => 'dash-mq-gold-img'],
        'silver' => ['wrap' => 'dash-mq-pair dash-mq-pair-silver', 'img' => 'dash-mq-silver-img'],
        'platinum' => ['wrap' => 'dash-mq-pair dash-mq-pair-platinum', 'img' => 'dash-mq-platinum-img'],
        'diamond' => ['wrap' => 'dash-mq-pair dash-mq-pair-diamond', 'img' => 'dash-mq-diamond-img'],
    ];
    $L = isset($layouts[$mk]) ? $layouts[$mk] : ['wrap' => 'dash-mq-pair dash-mq-pair-custom', 'img' => 'dash-mq-custom-img'];
    $src = $dash_mq_pair_src($metalKey, $lab, $dash_carat_row_imgs, $dash_dashboard_metal_img_urls);
    $imgHtml = $dash_mq_img_snippet($src, $L['img']);
    return '<span class="' . $dash_mq_h($L['wrap']) . '">'
        . $imgHtml
        . '<span class="dash-mq-lab">' . $dash_mq_h($lab) . '</span>'
        . '<span class="dash-mq-val">' . $dash_mq_h($val) . '</span>'
        . '</span>';
};
$__pairs_g = [];
/** Gold pairs: only when this metal is enabled in Masters; labels from Carat master where set. */
if (isset($dashboard_metals['gold'])) {
    $__gold_mq_label = trim((string) ($dashboard_metals['gold']['label'] ?? '')) ?: 'Gold';
    $__gold_marquee_cards = (isset($dashboard_metals['gold']['cards']) && is_array($dashboard_metals['gold']['cards']))
        ? $dashboard_metals['gold']['cards']
        : [];
    if ($__gold_marquee_cards !== []) {
        foreach ($__gold_marquee_cards as $__gc) {
            $__lab = trim((string) ($__gc['label'] ?? ''));
            if ($__lab === '') {
                continue;
            }
            $__val = trim((string) ($__gc['value'] ?? '0'));
            if ($dash_rates_branch_id <= 0 && array_key_exists($__lab, $dash_today_gold_bar) && $dash_today_gold_bar[$__lab] !== null) {
                $__val = number_format((float) $dash_today_gold_bar[$__lab], 2);
            }
            $__pairs_g[] = $dash_mq_build_pair('gold', $__lab, $__val);
        }
    } else {
        foreach (['24K', '22K', '21K', '18K'] as $__gk) {
            $__gv = $dash_today_gold_bar[$__gk] ?? null;
            if ($__gv === null) {
                $__gv = 0.0;
            }
            $__pairs_g[] = $dash_mq_build_pair('gold', $__gk, number_format((float) $__gv, 2));
        }
    }
    if ($__pairs_g !== []) {
        $dash_marquee_blocks[] = '<span class="dash-mq-section"><span class="dash-mq-metal">' . $dash_mq_h($__gold_mq_label) . '</span>' . $dash_mq_join_pairs($__pairs_g) . '</span>';
    }
}
foreach ($dashboard_metals as $mq_key => $mq_dm) {
    if ($mq_key === 'gold') {
        continue;
    }
    $__pairs_x = [];
    $__mq_cards = (isset($mq_dm['cards']) && is_array($mq_dm['cards'])) ? $mq_dm['cards'] : [];
    foreach ($__mq_cards as $__c) {
        $__lab = trim((string) ($__c['label'] ?? ''));
        if ($__lab === '') {
            continue;
        }
        $__val = trim((string) ($__c['value'] ?? '0'));
        $__pairs_x[] = $dash_mq_build_pair((string) $mq_key, $__lab, $__val);
    }
    if ($__pairs_x === []) {
        continue;
    }
    $__sec_lab = trim((string) ($mq_dm['label'] ?? '')) ?: $mq_key;
    $dash_marquee_blocks[] = '<span class="dash-mq-section"><span class="dash-mq-metal">' . $dash_mq_h($__sec_lab) . '</span>' . $dash_mq_join_pairs($__pairs_x) . '</span>';
}
$dash_marquee_html = implode('<span class="dash-mq-pipe" aria-hidden="true">|</span>', $dash_marquee_blocks);
?>
<!DOCTYPE html>
<html lang="en" class="default-style">

<head>
    <title>Dashboard — AuraGold</title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, user-scalable=no, 
          minimum-scale=1.0, maximum-scale=1.0">

    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include 'header-script.php';?>
    <style>
        html.default-style, html.default-style body {
            background: linear-gradient(160deg, #eef2f7 0%, #e8ecf4 45%, #f4f6fa 100%) !important;
            min-height: 100vh;
        }
        .layout-wrapper.layout-2 {
            min-height: 100vh;
            background: transparent;
        }
        .layout-content {
            min-height: calc(100vh - 48px) !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .dash-hero {
            position: relative;
            border-radius: 24px;
            padding: 28px 32px;
            margin-bottom: 24px;
            overflow: hidden;
            background: linear-gradient(125deg, #1a1f35 0%, #2d3560 48%, #1f2847 100%);
            box-shadow: 0 20px 50px rgba(26, 31, 53, 0.35);
            color: #fff;
        }
        .dash-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 90% 20%, rgba(212, 175, 55, 0.18), transparent 55%),
                radial-gradient(ellipse 60% 50% at 10% 80%, rgba(147, 197, 253, 0.12), transparent 50%);
            pointer-events: none;
        }
        .dash-hero-inner { position: relative; z-index: 1; }
        .dash-hero h1 {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 6px 0;
        }
        .dash-hero p {
            margin: 0;
            opacity: 0.88;
            font-size: 0.95rem;
        }
        .dash-hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.1);
            font-size: 0.85rem;
            backdrop-filter: blur(8px);
        }
        .dash-currency-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 14px;
        }
        .dash-currency-bar label {
            margin: 0;
            font-size: 0.88rem;
            opacity: 0.92;
        }
        .dash-currency-bar select {
            max-width: 260px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.12);
            color: #fff;
            font-size: 0.9rem;
            padding: 6px 10px;
        }
        .dash-currency-bar select option {
            color: #1a1f35;
        }

        .dash-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 991px) {
            .dash-summary { grid-template-columns: 1fr; }
        }
        .dash-summary-card {
            border-radius: 20px;
            padding: 20px 22px;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .dash-summary-card:has(.dash-summary-metal-img) {
            padding-right: 92px;
        }
        .dash-summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px rgba(0,0,0,0.16);
        }
        .dash-summary-card.metal-accent-gold {
            background: linear-gradient(135deg, #c9a227 0%, #8b6914 100%);
        }
        .dash-summary-card.metal-accent-silver {
            background: linear-gradient(135deg, #7c8ea3 0%, #4a5a6f 100%);
        }
        .dash-summary-card.metal-accent-diamond {
            background: linear-gradient(135deg, #5b7c9a 0%, #3d5a78 100%);
        }
        .dash-summary-card.metal-accent-platinum {
            background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        }
        .dash-summary-card.metal-accent-other {
            background: linear-gradient(135deg, #6d7a8c 0%, #475569 100%);
        }
        .dash-summary-card .dash-metal-icon {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.15rem;
            opacity: 0.94;
            line-height: 1;
            pointer-events: none;
        }
        .dash-summary-card .dash-summary-metal-img {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: clamp(48px, 26vw, 72px);
            height: clamp(48px, 26vw, 72px);
            max-width: 72px;
            max-height: 72px;
            object-fit: contain;
            pointer-events: none;
            opacity: 0.94;
            filter: drop-shadow(0 2px 10px rgba(0, 0, 0, 0.22));
            flex-shrink: 0;
        }
        .dash-summary-card .sym {
            font-size: 0.75rem;
            opacity: 0.9;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .dash-summary-card .metal-name { font-weight: 700; font-size: 1.05rem; margin-top: 4px; }
        .dash-summary-card .big-rate {
            font-size: 1.85rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-top: 10px;
            line-height: 1.2;
        }
        .dash-summary-card .sub {
            font-size: 0.82rem;
            opacity: 0.92;
            margin-top: 6px;
        }

        .white-box {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 10px 28px rgba(0,0,0,0.07);
            border: 1px solid rgba(236, 233, 255, 0.9);
            padding: 22px;
        }

        .sec-title {
            font-weight: 650;
            color: #1d2c4f;
            font-size: 17px;
            margin-bottom: 14px;
        }

        .label-small {
            font-size: 13px;
            color: #4b5272;
            font-weight: 550;
        }

        .input-box {
            border-radius: 12px;
            border: 1px solid #dad7ef;
            height: 42px;
        }

        .table { font-size: 14px; }
        .table th {
            background: #f1edff !important;
            font-weight: 650;
            color: #4d5673;
            vertical-align: middle;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background: #fafaff;
        }

        .icon-btn {
            background: #eee9ff;
            border: none;
            border-radius: 10px;
            padding: 5px 10px;
            font-size: 16px;
            color: #6c50ff;
            cursor: pointer;
        }
        .icon-btn:hover { background: #e4ddff; }

        .btn-save {
            background: linear-gradient(135deg, #6f55ff 0%, #5540d6 100%);
            color: #fff;
            border-radius: 12px;
            border: none;
            padding: 8px 26px;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(111, 85, 255, 0.35);
        }
        .btn-save:hover { filter: brightness(1.05); color: #fff; }

        .rate-card {
            background: #fff;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 8px 22px rgba(0,0,0,0.06);
            border: 1px solid #ececf8;
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 96px;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .rate-card > .dash-snap-carat-img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            flex-shrink: 0;
            pointer-events: none;
        }
        .rate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(0,0,0,0.09);
        }

        .circle {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 3px solid;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 15px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .c24{border-color:#7fb4c9;color:#4a707e}
        .c22{border-color:#e0b147;color:#bc8f25}
        .c21{border-color:#4aa772;color:#2b6f4e}
        .c18{border-color:#5a9c5b;color:#316d36}
        .c14{border-color:#d4a629;color:#b08718}
        .c10{border-color:#d15063;color:#aa2f44}

        .s999{border-color:#c0cad6;color:#5a6575}
        .s958{border-color:#9eb0c4;color:#4a5a6a}
        .s925{border-color:#7a9ab8;color:#3d5a75}
        .s875{border-color:#6b8fa3;color:#2f4d5c}

        .pt999{border-color:#cbd5e1;color:#475569}
        .pt950{border-color:#94a3b8;color:#334155}
        .pt900{border-color:#64748b;color:#1e293b}

        .circle[class^="cc-"] { border-color: #94a3b8; color: #475569; }

        .d03{border-color:#93c5fd;color:#2563eb}
        .d05{border-color:#7dd3fc;color:#0284c7}
        .d07{border-color:#67e8f9;color:#0e7490}
        .d10{border-color:#a5b4fc;color:#4f46e5}
        .d15{border-color:#c4b5fd;color:#6d28d9}
        .d20{border-color:#e9d5ff;color:#7c3aed}

        .cc-other{border-color:#64748b;color:#334155}

        .card-value { font-size: 20px; font-weight: 700; color: #2c3b60; }
        .card-date { font-size: 12px; color: #868686; margin-top: 4px; }
        .card-unit { font-size: 11px; color: #9ca3af; margin-top: 2px; }

        .metal-tabs-wrap {
            background: #fff;
            border-radius: 22px;
            padding: 8px 10px 0;
            box-shadow: 0 10px 28px rgba(0,0,0,0.06);
            border: 1px solid #e8e6f5;
            margin-bottom: 20px;
        }
        /*
         * Theme (shreerang-material.css) sets .nav-tabs .nav-link:not(.active) { color: #fff } for dark navbars.
         * On this white card that hides Gold/Silver — only the active tab looked visible.
         */
        .metal-tabs-wrap .nav-tabs .nav-link:not(.active) {
            color: #5c6478 !important;
        }
        .metal-tabs-wrap .nav-tabs .nav-link:not(.active):hover,
        .metal-tabs-wrap .nav-tabs .nav-link:not(.active):focus {
            color: #3d4a6b !important;
        }
        .metal-tabs-wrap .nav-link {
            border-radius: 14px !important;
            padding: 12px 22px;
            font-weight: 650;
            color: #5c6478;
            border: none !important;
            margin: 0 4px;
        }
        .metal-tabs-wrap .nav-link:hover { color: #3d4a6b; background: #f5f4ff; }
        .metal-tabs-wrap .nav-link.active {
            color: #fff !important;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }
        .metal-tabs-wrap .nav-item-gold .nav-link.active {
            background: linear-gradient(135deg, #c9a227 0%, #a67c00 100%) !important;
            box-shadow: 0 8px 20px rgba(201, 162, 39, 0.4);
        }
        .metal-tabs-wrap .nav-item-silver .nav-link.active {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
            box-shadow: 0 8px 20px rgba(100, 116, 139, 0.35);
        }
        .metal-tabs-wrap .nav-item-diamond .nav-link.active {
            background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%) !important;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.35);
        }

        .rate-input {
            max-width: 118px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 600;
        }
        .rate-hint-gold {
            font-size: 13px;
            color: #5c6478;
            line-height: 1.45;
            margin-bottom: 10px;
            padding: 12px 14px;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-radius: 14px;
            border: 1px solid rgba(234, 179, 8, 0.25);
        }

        .metal-tabs-wrap .nav-link i {
            margin-right: 6px;
            opacity: 0.92;
        }

        .dash-gold-analytics-card {
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 55%, #fffbeb 100%) !important;
            border: 1px solid rgba(234, 179, 8, 0.22) !important;
            box-shadow: 0 14px 40px rgba(180, 140, 40, 0.12) !important;
        }
        .dash-analytics-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: -0.02em;
        }
        .dash-stat-pill {
            background: #fff;
            border-radius: 16px;
            padding: 14px 16px;
            border: 1px solid #e8e0d5;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }
        .dash-stat-pill .label {
            display: block;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .dash-stat-pill .value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .dash-stat-pill .value.positive { color: #15803d; }
        .dash-stat-pill .value.negative { color: #b91c1c; }
        .dash-gold-chart-wrap {
            position: relative;
            height: 260px;
        }
        .dash-gold-chart-mode .btn {
            border-radius: 10px !important;
            font-weight: 600;
            font-size: 0.82rem;
        }
        .dash-gold-chart-mode .btn.active {
            background: linear-gradient(135deg, #c9a227 0%, #a16207 100%) !important;
            color: #fff !important;
            border-color: transparent !important;
        }

        .dash-rate-form-card {
            border: 1px solid rgba(99, 102, 241, 0.12) !important;
            background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%) !important;
        }
        .dash-rate-form-head {
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #eef0f7;
        }
        .dash-rate-form-head h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 4px 0;
        }
        .dash-rate-form-head p {
            margin: 0;
            font-size: 0.88rem;
            color: #64748b;
        }
        .dash-rate-conversions {
            margin-bottom: 14px;
        }
        .dash-rate-conversions label {
            display: block;
            font-weight: 650;
            font-size: 0.95rem;
            color: #1e3a8a;
            margin-bottom: 8px;
        }
        .dash-rate-conversions select.form-control {
            border-radius: 8px;
            border: 1px solid #93c5fd;
            max-width: 100%;
        }
        .dash-rates-table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e8eaf2;
        }
        .dash-rates-table thead th {
            background: linear-gradient(180deg, #f1f5f9 0%, #e8edf5 100%) !important;
            color: #334155 !important;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 12px 10px !important;
            border-bottom: 1px solid #dce3ee !important;
            vertical-align: middle !important;
        }
        .dash-rates-table tbody td {
            padding: 12px 10px !important;
            vertical-align: middle !important;
            border-color: #eef1f7 !important;
        }
        .dash-rates-table tbody tr:hover {
            background: rgba(99, 102, 241, 0.04) !important;
        }
        .dash-rates-table tbody td:first-child {
            font-weight: 700;
            color: #475569;
        }
        .dash-rates-table .form-control {
            border-radius: 10px;
            border: 1px solid #d8e0ed;
            font-weight: 600;
        }
        .dash-rates-table .icon-btn {
            border-radius: 12px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            color: #4f46e5;
        }
        .btn-dash-save-all {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 50%, #7c3aed 100%);
            color: #fff !important;
            border: none;
            border-radius: 14px;
            padding: 12px 28px;
            font-weight: 700;
            letter-spacing: 0.02em;
            box-shadow: 0 10px 28px rgba(79, 70, 229, 0.35);
        }
        .btn-dash-save-all:hover {
            filter: brightness(1.06);
            color: #fff !important;
        }

        .today-rate-panel {
            border: 1px solid rgba(234, 179, 8, 0.2) !important;
            background: linear-gradient(165deg, #fffdf8 0%, #ffffff 45%, #fffef6 100%) !important;
        }
        .today-rate-panel .sec-title {
            font-size: 1.12rem;
            font-weight: 750;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .today-rate-panel .sec-title i {
            font-size: 1.25rem;
            color: #ca8a04;
        }
        .today-gold-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }
        @media (max-width: 575px) {
            .today-gold-grid { grid-template-columns: 1fr; }
        }
        .gold-snap-card {
            display: flex;
            align-items: stretch;
            gap: 0;
            padding: 0 !important;
            min-height: 0;
            border-radius: 18px !important;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.95) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06) !important;
            background: #fff !important;
        }
        .gold-snap-card .dash-snap-carat-img {
            width: 56px;
            min-width: 56px;
            align-self: center;
            max-height: 88px;
            object-fit: contain;
            padding: 6px 4px;
            flex-shrink: 0;
            pointer-events: none;
        }
        .gold-snap-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.1) !important;
        }
        .gold-snap-card .circle {
            width: 76px;
            min-height: 100%;
            border-radius: 0 !important;
            border: none !important;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        .gold-snap-card .c24 { background: linear-gradient(180deg, #e0f2fe 0%, #bae6fd 100%); color: #0369a1; }
        .gold-snap-card .c22 { background: linear-gradient(180deg, #fef9c3 0%, #fde68a 100%); color: #a16207; }
        .gold-snap-card .c21 { background: linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; }
        .gold-snap-card .c18 { background: linear-gradient(180deg, #d1fae5 0%, #86efac 100%); color: #14532d; }
        .gold-snap-card .c14 { background: linear-gradient(180deg, #fef3c7 0%, #fcd34d 100%); color: #92400e; }
        .gold-snap-card .c10 { background: linear-gradient(180deg, #ffe4e6 0%, #fecdd3 100%); color: #9f1239; }
        .gold-snap-body {
            flex: 1;
            padding: 14px 16px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .gold-snap-body .card-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
        }
        .gold-snap-body .card-unit {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 2px;
        }
        .gold-snap-body .card-date {
            margin-top: 8px;
            font-size: 0.72rem;
            color: #94a3b8;
        }

        .dash-today-gold-bar {
            border: 1px solid #1a1a1a;
            background: #fff;
            padding: 0;
            border-radius: 4px;
            margin-bottom: 18px;
            overflow: hidden;
        }
        .dash-marquee-viewport {
            overflow: hidden;
            width: 100%;
        }
        .dash-marquee-track {
            display: flex;
            width: max-content;
            animation: dash-marquee-scroll 55s linear infinite;
        }
        .dash-marquee-track:hover {
            animation-play-state: paused;
        }
        .dash-marquee-group {
            flex: 0 0 auto;
            padding: 11px 36px;
            white-space: nowrap;
            font-size: 0.92rem;
            color: #0f172a;
            line-height: 1;
        }
        .dash-mq-section {
            display: inline-flex;
            align-items: center;
            flex-wrap: nowrap;
            vertical-align: middle;
            gap: 0;
        }
        .dash-mq-metal {
            display: block;
            flex-shrink: 0;
            align-self: center;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.02em;
            margin-right: 8px;
            line-height: 1.15;
        }
        .dash-today-gold-bar .dash-mq-gold-img,
        .dash-today-gold-bar .dash-mq-silver-img,
        .dash-today-gold-bar .dash-mq-platinum-img,
        .dash-today-gold-bar .dash-mq-diamond-img,
        .dash-today-gold-bar .dash-mq-custom-img {
            display: inline-block;
            height: 22px;
            width: auto;
            max-width: 36px;
            max-height: 22px;
            vertical-align: middle;
            margin-right: 6px;
            object-fit: contain;
            flex-shrink: 0;
        }
        @media (max-width: 575.98px) {
            .dash-today-gold-bar .dash-mq-gold-img,
            .dash-today-gold-bar .dash-mq-silver-img,
            .dash-today-gold-bar .dash-mq-platinum-img,
            .dash-today-gold-bar .dash-mq-diamond-img,
            .dash-today-gold-bar .dash-mq-custom-img {
                height: 18px;
                max-height: 18px;
                max-width: 28px;
            }
        }
        .dash-mq-pair {
            display: inline-block;
            white-space: nowrap;
            margin-right: 2px;
        }
        .dash-mq-pair-gold {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .dash-mq-pair-gold .dash-mq-gold-img {
            margin-right: 0;
        }
        .dash-mq-pair-silver {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .dash-mq-pair-silver .dash-mq-silver-img {
            margin-right: 0;
        }
        .dash-mq-pair-platinum {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .dash-mq-pair-platinum .dash-mq-platinum-img {
            margin-right: 0;
        }
        .dash-mq-pair-diamond {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .dash-mq-pair-diamond .dash-mq-diamond-img {
            margin-right: 0;
        }
        .dash-mq-pair-custom {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .dash-mq-pair-custom .dash-mq-custom-img {
            margin-right: 0;
        }
        .dash-mq-lab {
            font-weight: 700;
            color: #0f172a;
            margin-right: 5px;
        }
        .dash-mq-val {
            font-weight: 500;
            color: #94a3b8;
        }
        .dash-mq-sep-in {
            display: inline-block;
            padding: 0 7px;
            color: #cbd5e1;
            font-weight: 400;
            font-size: 0.88em;
            vertical-align: middle;
        }
        .dash-mq-pipe {
            display: inline-block;
            padding: 0 14px;
            color: #64748b;
            font-weight: 400;
            font-size: 1.08em;
            vertical-align: middle;
        }
        @media (max-width: 767.98px) {
            .dash-mobile-root {
                max-width: 100%;
                overflow-x: hidden;
            }
            .dash-branch-rates-bar {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            .dash-branch-rates-bar .form-control {
                max-width: 100% !important;
            }
            .metal-tabs-wrap .nav-link {
                padding: 10px 12px;
                margin: 0 2px;
                font-size: 0.82rem;
            }
            .dash-gold-chart-wrap {
                height: 220px;
            }
            .dash-marquee-group {
                font-size: 0.82rem;
                padding: 9px 20px;
            }
        }
        /*
         * Below lg, layout-content forces .row { margin: 0 }, so Bootstrap’s negative row margin
         * no longer cancels column horizontal padding — rate cards stay inset vs .dash-summary.
         * Drop horizontal padding on tab columns so white panels match headline card width.
         */
        @media (max-width: 991.98px) {
            .dash-mobile-root .metal-tabs-wrap,
            .dash-mobile-root #metalRateTabContent {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .dash-mobile-root #metalRateTabContent .tab-pane > .row {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .dash-mobile-root #metalRateTabContent .tab-pane > .row > [class*="col-"] {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .dash-mobile-root #metalRateTabContent .tab-pane > .row > [class*="col-"]:not(:last-child) {
                margin-bottom: 1rem;
            }
            .dash-mobile-root #metalRateTabContent .dash-gold-analytics-card .row {
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
            .dash-mobile-root #metalRateTabContent .dash-gold-analytics-card .row > [class*="col-"] {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            .dash-mobile-root #metalRateTabContent .dash-gold-analytics-card .row > [class*="col-"]:not(:last-child) {
                margin-bottom: 0.75rem;
            }
            .dash-mobile-root #metalRateTabContent .white-box .row > [class*="col-"] {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
        }
        @keyframes dash-marquee-scroll {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }
    </style>
</head>

<body>

<div class="layout-wrapper layout-2">
    <div class="layout-inner">
        <div id="layout-sidenav" class="layout-sidenav sidenav sidenav-vertical bg-white logo-dark" aria-hidden="true"></div>
        <div class="layout-container">
            <nav class="layout-navbar navbar navbar-expand-lg align-items-lg-center bg-dark container-p-x" id="layout-navbar" aria-hidden="true"></nav>
            <div class="layout-content">
                <div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">

<?php include 'sidebar.php'; ?>

<div class="row">
<div class="col-12 p-3">

<div class="dash-mobile-root">

<div class="dash-today-gold-bar" role="region" aria-label="Metal rates">
  <div class="dash-marquee-viewport">
    <div class="dash-marquee-track">
      <div class="dash-marquee-group"><?= $dash_marquee_html !== '' ? $dash_marquee_html : '—'; ?></div>
      <div class="dash-marquee-group" aria-hidden="true"><?= $dash_marquee_html !== '' ? $dash_marquee_html : '—'; ?></div>
    </div>
  </div>
</div>

<?php if ($dash_effective_branch <= 0 && !empty($dash_branch_list)) { ?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 px-1 dash-branch-rates-bar">
  <label for="dashBranchRatesSelect" class="mb-0 small font-weight-bold text-secondary">Rates for branch</label>
  <select id="dashBranchRatesSelect" class="form-control form-control-sm" style="max-width: 320px;">
    <option value="0"<?= (int) $dash_rates_branch_id === 0 ? ' selected' : ''; ?>>Default (shared / legacy)</option>
    <?php foreach ($dash_branch_list as $br) {
        $bid = (int) ($br['id'] ?? 0);
        if ($bid <= 0) {
            continue;
        }
        ?>
    <option value="<?= $bid ?>"<?= (int) $dash_rates_branch_id === $bid ? ' selected' : ''; ?>><?= htmlspecialchars((string) ($br['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
    <?php } ?>
  </select>
  <span class="small text-muted">Gold, silver, and diamond saves apply to this branch.</span>
</div>
<?php } elseif ($dash_effective_branch > 0) { ?>
<p class="small text-muted mb-3 px-1"><strong>Branch:</strong> <?php
    $bn = '';
    foreach ($dash_branch_list as $br) {
        if ((int) ($br['id'] ?? 0) === (int) $dash_rates_branch_id) {
            $bn = (string) ($br['name'] ?? '');
            break;
        }
    }
    echo $bn !== '' ? htmlspecialchars($bn, ENT_QUOTES, 'UTF-8') : ('#' . (int) $dash_rates_branch_id);
?></p>
<?php } ?>

<!--<div class="dash-hero">
  <div class="dash-hero-inner">
    <h1>Live metal &amp; stone rates</h1>
    <p class="js-dash-hero-desc">Gold, silver, and diamond — metal-wise and carat-wise reference snapshots below. Rates are stored in <strong class="js-base-cur-label"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></strong>; choose a display currency to convert the headline figures using Masters → Currency Exchange Rate.</p>
    <div class="dash-currency-bar">
      <label for="dashDisplayCurrency">Display currency</label>
      <select id="dashDisplayCurrency" autocomplete="off">
        <?php foreach ($currencies as $c) {
            $cid = (int) ($c['id'] ?? 0);
            $lab = trim((string) ($c['symbol'] ?? '')) ?: trim((string) ($c['name'] ?? ''));
            if ($lab === '') {
                continue;
            }
            $sel = ($base_currency_id > 0 && $cid === $base_currency_id) ? ' selected' : '';
        ?>
        <option value="<?= $cid ?>"<?= $sel ?>><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(trim((string) ($c['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
        <?php } ?>
        <?php if (empty($currencies)) { ?>
        <option value="0"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></option>
        <?php } ?>
      </select>
    </div>
    <div class="dash-hero-badge">
      <i class="ion ion-ios-time"></i>
      <span>Last display update: <?= htmlspecialchars($rates_updated, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
  </div>
</div>-->

<?php
$dash_metal_icons = [
    'gold'     => 'fa fa-coins',
    'silver'   => 'fa fa-ring',
    'platinum' => 'fa fa-circle',
    'diamond'  => 'fa fa-gem',
];
?>
<select id="dashDisplayCurrency" class="sr-only" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;pointer-events:none;" aria-hidden="true" tabindex="-1" autocomplete="off">
<?php foreach ($currencies as $c) {
    $cid = (int) ($c['id'] ?? 0);
    $lab = trim((string) ($c['symbol'] ?? '')) ?: trim((string) ($c['name'] ?? ''));
    if ($lab === '') {
        continue;
    }
    $sel = ($display_currency_id > 0 && $cid === $display_currency_id) ? ' selected' : '';
?>
  <option value="<?= $cid ?>"<?= $sel ?>><?= htmlspecialchars($lab, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(trim((string) ($c['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
<?php } ?>
<?php if (empty($currencies)) { ?>
  <option value="<?= (int) $base_currency_id ?>"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></option>
<?php } ?>
</select>

<div class="dash-summary">
  <?php foreach ($dashboard_metals as $mk => $m) {
      $head_raw = (string) ($m['headline_rate'] ?? '0');
      $head_base = (float) str_replace([',', ' '], '', $head_raw);
      $is_dm_head = ($mk === 'diamond');
      $metal_thumb_url = $dash_dashboard_metal_img_urls[$mk] ?? '';
  ?>
  <div class="dash-summary-card <?= htmlspecialchars($m['hero_class'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($metal_thumb_url !== '') { ?>
    <img class="dash-summary-metal-img" src="<?= htmlspecialchars($metal_thumb_url, ENT_QUOTES, 'UTF-8') ?>" alt="" width="72" height="72" loading="lazy" decoding="async">
    <?php } else {
        $metal_icon = $dash_metal_icons[$mk] ?? 'fa fa-circle';
    ?>
    <i class="<?= htmlspecialchars($metal_icon, ENT_QUOTES, 'UTF-8') ?> dash-metal-icon" aria-hidden="true"></i>
    <?php } ?>
    <div class="sym"><?= htmlspecialchars($m['short'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="metal-name"><?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="big-rate"><span class="js-dash-num" data-is-diamond="<?= $is_dm_head ? '1' : '0' ?>" data-base="<?= htmlspecialchars((string) $head_base, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($m['headline_rate'], ENT_QUOTES, 'UTF-8') ?></span> <span class="js-dash-cur" style="font-size:1rem;font-weight:600;opacity:.95"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></span></div>
    <div class="sub"><?= htmlspecialchars($m['headline_carat'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($rates_updated, ENT_QUOTES, 'UTF-8') ?></div>
  </div>
  <?php } ?>
</div>

<div class="metal-tabs-wrap">
  <ul class="nav nav-tabs border-0 justify-content-center flex-wrap" id="metalRateTabs" role="tablist">
    <?php
    $i = 0;
    foreach ($dashboard_metals as $key => $m) {
        $active = $i === 0 ? ' active' : '';
        $coreTabs = ['gold', 'silver', 'platinum', 'diamond'];
        if (in_array((string) $key, $coreTabs, true)) {
            $itemClass = 'nav-item nav-item-' . (string) $key;
        } else {
            $itemClass = 'nav-item nav-item-extra-' . preg_replace('/[^a-zA-Z0-9_-]/', 'x', (string) $key);
        }
    ?>
    <?php $tab_icon = $dash_metal_icons[$key] ?? 'fa fa-circle'; ?>
    <li class="<?= htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8') ?>" role="presentation">
      <a class="nav-link<?= $active ?>" id="tab-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" data-toggle="tab" href="#pane-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" role="tab"><i class="<?= htmlspecialchars($tab_icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?></a>
    </li>
    <?php
        $i++;
    }
    ?>
  </ul>
</div>

<div class="tab-content" id="metalRateTabContent">
  <?php
  $i = 0;
  foreach ($dashboard_metals as $key => $m) {
      $show = $i === 0 ? ' show active' : '';
  ?>
  <div class="tab-pane fade<?= $show ?>" id="pane-<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" role="tabpanel">
    <?php
    $snapshot_title = ($key === 'gold')
        ? 'Today Gold Rate'
        : (($key === 'silver')
            ? 'Today Silver Rate'
            : (($key === 'platinum')
                ? 'Today Platinum Rate'
                : (($key === 'diamond')
                    ? 'Today Diamond Rate'
                    : ('Today ' . trim((string) ($m['label'] ?? '')) . ' rate'))));
    ?>
    <div class="row g-4">
      <div class="col-12 col-lg-6">
        <div class="white-box dash-rate-form-card">
          <div class="dash-rate-form-head">
            <?php
            $dash_rate_conv_uid = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $key);
            ?>
            <div class="dash-rate-conversions">
              <label for="dashRateConversionUrls-<?= htmlspecialchars($dash_rate_conv_uid, ENT_QUOTES, 'UTF-8') ?>">Rate Conversions</label>
              <select id="dashRateConversionUrls-<?= htmlspecialchars($dash_rate_conv_uid, ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm" autocomplete="off"
                onchange="var u=this.value;if(u){window.open(u,'_blank','noopener,noreferrer');}this.selectedIndex=0;">
                <option value="">Select URL…</option>
                <option value="https://dubaicityofgold.com/">https://dubaicityofgold.com/</option>
                <option value="https://igold.ae/gold-rate">https://igold.ae/gold-rate</option>
                <option value="https://ae.fkjewellers.com/pages/today-gold-price-in-uae-gold-rate">https://ae.fkjewellers.com/pages/today-gold-price-in-uae-gold-rate</option>
                <option value="https://www.kitco.com/">https://www.kitco.com/</option>
                <option value="https://goldprice.org/">https://goldprice.org/</option>
              </select>
            </div>
            <?php if ($key === 'gold'): ?>
            <h3>Gold rate sheet</h3>
            <p>Enter values in <strong class="js-base-cur-label"><?= htmlspecialchars($base_currency_label, ENT_QUOTES, 'UTF-8') ?></strong> per gram. Saving updates the dashboard, snapshots, and analysis.</p>
            <?php else: ?>
            <h3><?= htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') ?> — rate conversions</h3>
            <p class="mb-0">Amounts in <strong class="js-base-cur-label"><?= htmlspecialchars($base_currency_label, ENT_QUOTES, 'UTF-8') ?></strong> (base currency).</p>
            <?php endif; ?>
          </div>

          <div class="table-responsive">
            <table class="table table-striped text-center mb-0 dash-rates-table">
              <thead>
                <tr>
                  <th class="text-left"><?= htmlspecialchars($m['table_carat_label'], ENT_QUOTES, 'UTF-8') ?></th>
                  <th>New rate</th>
                  <th>Sell premium</th>
                  <th>Conv.</th>
                  <th>Current</th>
                  <th style="width:52px;"></th>
                </tr>
              </thead>
              <tbody>
                <?php
                $row_idx = 0;
                foreach ($m['rows'] as $row) {
                    $row_idx++;
                    $carat_key = htmlspecialchars($row['carat'], ENT_QUOTES, 'UTF-8');
                    $karat_num = null;
                    if ($key === 'gold' && preg_match('/(\d+)/', (string) $row['carat'], $km)) {
                        $karat_num = (int) $km[1];
                    }
                    $is_diamond = ($key === 'diamond');
                    $prem = $row['sell_premium'];
                    $prem_is_dash = ($prem === '—' || $prem === '-' || $prem === '');
                    $prem_input_val = $prem_is_dash ? '' : preg_replace('/[^\d.-]/', '', (string) $prem);
                ?>
                <tr<?= $karat_num !== null ? ' data-karat="' . (int) $karat_num . '"' : '' ?> data-metal="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" data-carat-label="<?= htmlspecialchars($row['carat'], ENT_QUOTES, 'UTF-8') ?>">
                  <td><?= $carat_key ?></td>
                  <td>
                    <?php if ($is_diamond) { ?>
                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-center rate-input input-box js-rate-new" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>_new_<?= (int) $row_idx ?>" value="<?= htmlspecialchars($row['new_rate'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                    <?php } else { ?>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-center rate-input input-box js-rate-new" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>_new_<?= (int) $row_idx ?>" value="<?= htmlspecialchars(preg_replace('/[^\d.]/', '', (string) $row['new_rate']), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                    <?php } ?>
                  </td>
                  <td>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-center rate-input input-box js-rate-premium" name="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>_prem_<?= (int) $row_idx ?>" value="<?= htmlspecialchars($prem_input_val, ENT_QUOTES, 'UTF-8') ?>" placeholder="—" autocomplete="off">
                  </td>
                  <td><?= htmlspecialchars($row['conv'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="js-rate-current"><?= htmlspecialchars($row['current'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><button type="button" class="icon-btn js-save-metal-rates" title="Save to database">💾</button></td>
                </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>

          <button type="button" class="btn btn-dash-save-all mt-4 float-end js-save-metal-rates">
            <i class="fa fa-check mr-1" aria-hidden="true"></i> Save all rates
          </button>
          <div class="clearfix"></div>
        </div>
      </div>

      <div class="col-12 col-lg-6">
        <div class="white-box h-100<?= $key === 'gold' ? ' today-rate-panel' : '' ?>">
          <div class="sec-title">
            <?php if ($key === 'gold'): ?>
            <i class="fa fa-sun" aria-hidden="true"></i>
            <?php elseif ($key === 'silver'): ?>
            <i class="fa fa-ring" aria-hidden="true"></i>
            <?php elseif ($key === 'platinum'): ?>
            <i class="fa fa-circle" aria-hidden="true"></i>
            <?php elseif ($key === 'diamond'): ?>
            <i class="fa fa-gem" aria-hidden="true"></i>
            <?php else: ?>
            <i class="fa fa-layer-group" aria-hidden="true"></i>
            <?php endif; ?>
            <?= htmlspecialchars($snapshot_title, ENT_QUOTES, 'UTF-8') ?>
          </div>
          <?php if ($key === 'gold'): ?>
          <div class="today-gold-grid">
            <?php foreach ($m['cards'] as $c) {
                $cv_raw = (string) ($c['value'] ?? '0');
                $cv_base = (float) str_replace([',', ' '], '', $cv_raw);
            ?>
            <div class="gold-snap-card rate-card">
              <div class="circle <?= htmlspecialchars($c['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php
              $snap_img = trim((string) ($c['image_url'] ?? ''));
              if ($snap_img !== '') {
              ?>
              <img class="dash-snap-carat-img" src="<?= htmlspecialchars($snap_img, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async">
              <?php } ?>
              <div class="gold-snap-body">
                <div class="card-value"><span class="js-gold-snapshot js-dash-num" data-karat-label="<?= htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8') ?>" data-is-diamond="0" data-base="<?= htmlspecialchars((string) $cv_base, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['value'], ENT_QUOTES, 'UTF-8') ?></span> <span class="js-dash-cur"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="card-unit">per gram (ref.)</div>
                <div class="card-date"><i class="fa fa-clock mr-1" aria-hidden="true"></i><?= htmlspecialchars($rates_updated, ENT_QUOTES, 'UTF-8') ?></div>
              </div>
            </div>
            <?php } ?>
          </div>
          <?php else: ?>
          <div class="row g-3">
            <?php foreach ($m['cards'] as $c) {
                $cv_raw = (string) ($c['value'] ?? '0');
                $cv_base = (float) str_replace([',', ' '], '', $cv_raw);
                $is_dm_card = ($key === 'diamond');
            ?>
            <div class="col-md-6">
              <div class="rate-card">
                <?php
                $rc_img = trim((string) ($c['image_url'] ?? ''));
                if ($rc_img !== '') {
                ?>
                <img class="dash-snap-carat-img" src="<?= htmlspecialchars($rc_img, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async">
                <?php } ?>
                <div class="circle <?= htmlspecialchars($c['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8') ?></div>
                <div>
                  <div class="card-value"><span class="js-dash-num" data-is-diamond="<?= $is_dm_card ? '1' : '0' ?>" data-base="<?= htmlspecialchars((string) $cv_base, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['value'], ENT_QUOTES, 'UTF-8') ?></span> <span class="js-dash-cur"><?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?></span></div>
                  <div class="card-unit"><?= $key === 'diamond' ? 'per carat (ref.)' : 'per gram (ref.)' ?></div>
                  <div class="card-date"><?= htmlspecialchars($rates_updated, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
            </div>
            <?php } ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if ($key === 'gold'): ?>
    <div class="dash-gold-analytics-card white-box mt-4">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
          <h3 class="dash-analytics-title mb-1">Gold rate analysis</h3>
          <p class="text-muted small mb-0">24K trend from saved rates — yesterday vs today and last 7 / 30 days</p>
        </div>
        <div class="btn-group btn-group-sm dash-gold-chart-mode" role="group" aria-label="Chart range">
          <button type="button" class="btn btn-light border active js-gold-chart-range" data-range="week">7 days</button>
          <button type="button" class="btn btn-light border js-gold-chart-range" data-range="month">30 days</button>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <div class="dash-stat-pill">
            <span class="label">Yesterday (24K)</span>
            <span class="value js-gold-stat-yesterday">—</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="dash-stat-pill">
            <span class="label">Today (24K)</span>
            <span class="value js-gold-stat-today">—</span>
          </div>
        </div>
        <div class="col-md-4">
          <div class="dash-stat-pill">
            <span class="label">Change vs yesterday</span>
            <span class="value js-gold-stat-change">—</span>
          </div>
        </div>
      </div>
      <div class="dash-gold-chart-wrap mt-3">
        <canvas id="goldRateChart" height="110"></canvas>
        <p class="text-muted small mt-2 mb-0 js-gold-chart-empty text-center d-none">Save gold rates to build history and unlock the chart.</p>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php
      $i++;
  }
  ?>
</div>
</div>

</div>
</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer-script.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
window.AURAGOLD_DASH_CURRENCY = <?= json_encode($dash_currency_payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.AURAGOLD_DASH_BASE_LABEL = <?= json_encode($base_currency_label, JSON_UNESCAPED_UNICODE) ?>;
window.AURAGOLD_DASH_BRANCH_ID = <?= (int) $dash_rates_branch_id ?>;
</script>
<script>
(function () {
    var STORAGE_KEY = 'auragold_dashboard_currency_id';

    function findCur(id) {
        var list = (window.AURAGOLD_DASH_CURRENCY && window.AURAGOLD_DASH_CURRENCY.currencies) ? window.AURAGOLD_DASH_CURRENCY.currencies : [];
        var sid = String(id);
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].id) === sid) {
                return list[i];
            }
        }
        return null;
    }

    function rateFor(currencyId) {
        var r = window.AURAGOLD_DASH_CURRENCY && window.AURAGOLD_DASH_CURRENCY.rates;
        if (!r) {
            return 0;
        }
        var v = r[currencyId];
        if (v == null) {
            v = r[String(currencyId)];
        }
        return parseFloat(v, 10) || 0;
    }

    function convertBaseToDisplay(baseNum, currencyId) {
        var bid = window.AURAGOLD_DASH_CURRENCY ? window.AURAGOLD_DASH_CURRENCY.baseId : 0;
        if (!currencyId || String(currencyId) === String(bid)) {
            return baseNum;
        }
        var rt = rateFor(currencyId);
        if (rt <= 0) {
            return baseNum;
        }
        return baseNum / rt;
    }

    function formatDisp(n, isDiamond) {
        if (isDiamond) {
            var x = Math.round(n * 100) / 100;
            return (Math.abs(x - Math.round(x)) < 1e-6) ? String(Math.round(x)) : x.toFixed(2);
        }
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function applyDashCurrency() {
        var sel = document.getElementById('dashDisplayCurrency');
        if (!sel) {
            return;
        }
        var cid = sel.value;
        var cur = findCur(cid);
        var code = cur ? (cur.symbol || cur.name || '') : (window.AURAGOLD_DASH_BASE_LABEL || '');
        var baseLabel = window.AURAGOLD_DASH_BASE_LABEL || '';

        document.querySelectorAll('.js-base-cur-label').forEach(function (el) {
            el.textContent = baseLabel;
        });

        document.querySelectorAll('.js-dash-cur').forEach(function (el) {
            el.textContent = code || baseLabel;
        });

        document.querySelectorAll('.js-dash-num').forEach(function (el) {
            var isDm = el.getAttribute('data-is-diamond') === '1';
            var base = parseFloat(el.getAttribute('data-base'), 10);
            if (el.classList.contains('js-gold-snapshot') && typeof jQuery !== 'undefined') {
                var lbl = el.getAttribute('data-karat-label') || '';
                var $inp = jQuery('#pane-gold tbody tr').filter(function () {
                    return jQuery(this).attr('data-carat-label') === lbl;
                }).find('.js-rate-new').first();
                if ($inp.length) {
                    var pv = parseFloat(String($inp.val()).replace(/,/g, ''), 10);
                    if (!isNaN(pv)) {
                        base = pv;
                    }
                }
            }
            if (isNaN(base)) {
                base = 0;
            }
            var disp = convertBaseToDisplay(base, cid);
            el.textContent = formatDisp(disp, isDm);
        });
    }

    window.applyDashCurrencyDisplay = applyDashCurrency;
    window.auragoldDashConvertBaseToDisplay = convertBaseToDisplay;
    window.auragoldDashFindCur = findCur;
    window.auragoldDashGetDisplayCurrencyId = function () {
        var s = document.getElementById('dashDisplayCurrency');
        return s ? s.value : null;
    };

    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('dashDisplayCurrency');
        if (!sel) {
            return;
        }
        try {
            var pref = window.AURAGOLD_DASH_CURRENCY && window.AURAGOLD_DASH_CURRENCY.displayCurrencyId;
            if (pref && String(pref) !== '0') {
                sel.value = String(pref);
            } else {
                var saved = localStorage.getItem(STORAGE_KEY);
                if (saved !== null && saved !== '') {
                    var ok = false;
                    Array.prototype.forEach.call(sel.options, function (o) {
                        if (o.value === saved) {
                            ok = true;
                        }
                    });
                    if (ok) {
                        sel.value = saved;
                    }
                }
            }
        } catch (e) {}
        sel.addEventListener('change', function () {
            try {
                localStorage.setItem(STORAGE_KEY, sel.value);
            } catch (e2) {}
            applyDashCurrency();
            if (typeof window.refreshGoldDashboardAnalytics === 'function') {
                window.refreshGoldDashboardAnalytics();
            }
        });
        applyDashCurrency();
    });
})();
</script>
<script>
(function () {
    var goldChart = null;
    var goldRange = 'week';
    var lastPayload = null;

    function money2(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function curCode() {
        var cid = window.auragoldDashGetDisplayCurrencyId ? window.auragoldDashGetDisplayCurrencyId() : null;
        var c = window.auragoldDashFindCur ? window.auragoldDashFindCur(cid) : null;
        return c ? (c.symbol || c.name || '') : '';
    }

    function sliceRange(series, range) {
        if (!series || !series.length) {
            return [];
        }
        var n = range === 'month' ? 30 : 7;
        if (series.length <= n) {
            return series.slice();
        }
        return series.slice(-n);
    }

    function fmtDay(dstr) {
        var p = String(dstr).split('-');
        if (p.length !== 3) {
            return dstr;
        }
        var mo = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return parseInt(p[2], 10) + ' ' + mo[parseInt(p[1], 10) - 1];
    }

    function renderChart(series) {
        var canvas = document.getElementById('goldRateChart');
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }
        var conv = window.auragoldDashConvertBaseToDisplay;
        var cid = window.auragoldDashGetDisplayCurrencyId ? window.auragoldDashGetDisplayCurrencyId() : null;
        if (!conv) {
            return;
        }
        var emptyEl = document.querySelector('.js-gold-chart-empty');
        var data = sliceRange(series || [], goldRange);
        if (emptyEl) {
            if (!data.length) {
                emptyEl.classList.remove('d-none');
            } else {
                emptyEl.classList.add('d-none');
            }
        }
        var labels = data.map(function (x) {
            return fmtDay(x.date);
        });
        var vals = data.map(function (x) {
            return conv(parseFloat(x.rate, 10), cid);
        });

        if (goldChart) {
            goldChart.destroy();
            goldChart = null;
        }
        if (!data.length) {
            return;
        }

        var ctx = canvas.getContext('2d');
        goldChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '24K',
                    data: vals,
                    borderColor: '#b8860b',
                    backgroundColor: 'rgba(201, 162, 39, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 6,
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
                            label: function (ctx2) {
                                return money2(ctx2.parsed.y) + ' ' + curCode();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: 'rgba(15, 23, 42, 0.06)' },
                        ticks: {
                            callback: function (v) {
                                return money2(v);
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 45, minRotation: 0 }
                    }
                }
            }
        });
    }

    function updateStats(payload) {
        var conv = window.auragoldDashConvertBaseToDisplay;
        var cid = window.auragoldDashGetDisplayCurrencyId ? window.auragoldDashGetDisplayCurrencyId() : null;
        if (!conv) {
            return;
        }
        var yT = document.querySelector('.js-gold-stat-yesterday');
        var tT = document.querySelector('.js-gold-stat-today');
        var cT = document.querySelector('.js-gold-stat-change');
        if (!yT || !tT || !cT) {
            return;
        }
        var cc = curCode();
        var t = conv(parseFloat(payload.today24Base, 10), cid);
        tT.textContent = money2(t) + (cc ? ' ' + cc : '');

        if (payload.yesterday24Base !== null && payload.yesterday24Base !== undefined && !isNaN(parseFloat(payload.yesterday24Base, 10))) {
            yT.textContent = money2(conv(parseFloat(payload.yesterday24Base, 10), cid)) + (cc ? ' ' + cc : '');
        } else {
            yT.textContent = '—';
        }

        if (payload.changePct === null || payload.changePct === undefined || payload.yesterday24Base === null) {
            cT.textContent = '—';
            cT.className = 'value js-gold-stat-change';
        } else {
            var p = parseFloat(payload.changePct, 10);
            cT.textContent = (p >= 0 ? '+' : '') + p.toFixed(2) + '%';
            cT.className = 'value js-gold-stat-change ' + (p >= 0 ? 'positive' : 'negative');
        }
    }

    function loadRemote() {
        var br = (typeof window.AURAGOLD_DASH_BRANCH_ID !== 'undefined' && window.AURAGOLD_DASH_BRANCH_ID !== null)
            ? String(window.AURAGOLD_DASH_BRANCH_ID) : '0';
        fetch('ajax/dashboard-gold-analytics.php?branch=' + encodeURIComponent(br), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data || !data.ok) {
                    return;
                }
                lastPayload = data;
                updateStats(data);
                renderChart(data.series);
            })
            .catch(function () {});
    }

    window.refreshGoldDashboardAnalytics = function () {
        if (lastPayload) {
            updateStats(lastPayload);
            renderChart(lastPayload.series);
        } else {
            loadRemote();
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        loadRemote();
        document.querySelectorAll('.js-gold-chart-range').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.js-gold-chart-range').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');
                goldRange = btn.getAttribute('data-range') || 'week';
                if (lastPayload) {
                    renderChart(lastPayload.series);
                }
            });
        });
    });
})();
</script>
<script>
(function ($) {
    function syncGoldSnapshot($pane) {
        $pane.find('tbody tr[data-karat]').each(function () {
            var $tr = $(this);
            var label = $.trim($tr.attr('data-carat-label') || $tr.find('td:first').text());
            var v = $tr.find('.js-rate-new').val();
            $tr.find('.js-rate-current').text(v);
            var $snap = $pane.find('.js-gold-snapshot[data-karat-label="' + label + '"]');
            $snap.text(v);
            var pv = parseFloat(String(v).replace(/,/g, ''), 10);
            if (!isNaN(pv)) {
                $snap.attr('data-base', String(pv));
            }
            if (typeof window.applyDashCurrencyDisplay === 'function') {
                window.applyDashCurrencyDisplay();
            }
        });
    }

    $('#pane-gold').on('input', '.js-rate-new', function () {
        var $tr = $(this).closest('tr');
        var v = $(this).val();
        $tr.find('.js-rate-current').text(v);
        var label = $.trim($tr.attr('data-carat-label') || $tr.find('td:first').text());
        var $snap = $('#pane-gold .js-gold-snapshot[data-karat-label="' + label + '"]');
        $snap.text(v);
        var pv = parseFloat(String(v).replace(/,/g, ''), 10);
        if (!isNaN(pv)) {
            $snap.attr('data-base', String(pv));
        }
        if (typeof window.applyDashCurrencyDisplay === 'function') {
            window.applyDashCurrencyDisplay();
        }
    });

    $('#pane-gold').on('click', '#goldFillFrom24k', function () {
        var $pane = $('#pane-gold');
        var $r24 = $pane.find('tr[data-karat="24"] .js-rate-new').first();
        var raw = $r24.val();
        var v = parseFloat(String(raw).replace(/,/g, ''), 10);
        if (isNaN(v) || v <= 0) {
            window.alert('Enter today’s 24K rate (' + (window.AURAGOLD_DASH_BASE_LABEL || 'base') + ' per gram) in the 24K row first.');
            return;
        }
        $pane.find('tbody tr[data-karat]').each(function () {
            var k = parseInt($(this).attr('data-karat'), 10);
            if (!k || k < 1 || k > 24) {
                return;
            }
            var nv = (v * k / 24).toFixed(2);
            $(this).find('.js-rate-new').val(nv);
        });
        syncGoldSnapshot($pane);
    });

    function collectMetalPane($pane) {
        var metal = ($pane.attr('id') || '').replace('pane-', '');
        var source = $pane.find('.js-meta-source').length ? ($pane.find('.js-meta-source').val() || '') : '';
        var ounce = $pane.find('.js-meta-ounce').length ? ($pane.find('.js-meta-ounce').val() || '0') : '0';
        var branchId = (typeof window.AURAGOLD_DASH_BRANCH_ID !== 'undefined' && window.AURAGOLD_DASH_BRANCH_ID !== null)
            ? parseInt(window.AURAGOLD_DASH_BRANCH_ID, 10) : 0;
        if (isNaN(branchId)) {
            branchId = 0;
        }
        var rows = [];
        $pane.find('tbody tr[data-carat-label]').each(function () {
            var carat = $(this).attr('data-carat-label');
            var rate = $(this).find('.js-rate-new').val();
            var premInp = $(this).find('.js-rate-premium');
            var sell_premium = premInp.length ? String(premInp.val() || '').trim() : '';
            rows.push({ carat: carat, rate: rate, conv: '1', sell_premium: sell_premium });
        });
        return { metal: metal, branch_id: branchId, source_url: source, ounce_rate: ounce, rows: rows };
    }

    function saveMetalRates($pane) {
        var payload = collectMetalPane($pane);
        if (!payload.rows.length) {
            window.alert('No rate rows to save.');
            return;
        }
        var $btns = $pane.find('.js-save-metal-rates').prop('disabled', true);
        $.ajax({
            url: 'ajax/save-dashboard-rates.php',
            method: 'POST',
            contentType: 'application/json; charset=UTF-8',
            data: JSON.stringify(payload),
            dataType: 'json'
        }).done(function (res) {
            if (res && res.status === 'ok') {
                window.location.reload();
            } else {
                window.alert((res && res.message) ? res.message : 'Save failed.');
            }
        }).fail(function (xhr) {
            var msg = 'Could not save rates.';
            try {
                var j = JSON.parse(xhr.responseText);
                if (j && j.message) msg = j.message;
            } catch (e) {}
            window.alert(msg);
        }).always(function () {
            $btns.prop('disabled', false);
        });
    }

    $(document).on('click', '.js-save-metal-rates', function () {
        var $pane = $(this).closest('.tab-pane');
        if (!$pane.length) return;
        saveMetalRates($pane);
    });

    $(document).on('change', '#dashBranchRatesSelect', function () {
        var u = new URL(window.location.href);
        u.searchParams.set('branch', $(this).val() || '0');
        window.location.href = u.toString();
    });
})(jQuery);
</script>
</body>
</html>
