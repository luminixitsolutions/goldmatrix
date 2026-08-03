<?php
/**
 * Full-screen brand loader (Jewelsteps-style card from reference video).
 * Used by dashboard shell, header-script, and sidebar.
 *
 * Skip: set $GLOBALS['DASHBOARD_SKIP_PAGE_LOADER'] = true before layout, or define('AURAGOLD_SKIP_BRAND_PAGE_LOADER', true).
 */
if (!function_exists('auragold_brand_page_loader_should_show')) {
    function auragold_brand_page_loader_should_show(): bool
    {
        if (defined('AURAGOLD_SKIP_BRAND_PAGE_LOADER') && AURAGOLD_SKIP_BRAND_PAGE_LOADER) {
            return false;
        }
        if (!empty($GLOBALS['DASHBOARD_SKIP_PAGE_LOADER'])) {
            return false;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}

if (!function_exists('auragold_brand_page_loader_css')) {
    function auragold_brand_page_loader_css(): string
    {
        return <<<'HTML'
<style id="auragold-brand-page-loader-css">
    html.auragold-page-loading,
    html.auragold-page-loading body {
        overflow: hidden !important;
        height: 100% !important;
    }
    #auragold-page-loader {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483000 !important;
        display: flex !important;
        align-items: center;
        justify-content: center;
        padding: 24px;
        box-sizing: border-box;
        background:
            radial-gradient(1.5px 1.5px at 10% 16%, rgba(255,255,255,0.5) 50%, transparent 51%),
            radial-gradient(1px 1px at 22% 48%, rgba(253,224,71,0.45) 50%, transparent 51%),
            radial-gradient(1.5px 1.5px at 38% 22%, rgba(255,255,255,0.45) 50%, transparent 51%),
            radial-gradient(1px 1px at 58% 70%, rgba(255,255,255,0.35) 50%, transparent 51%),
            radial-gradient(1.5px 1.5px at 74% 28%, rgba(253,224,71,0.4) 50%, transparent 51%),
            radial-gradient(1px 1px at 88% 58%, rgba(255,255,255,0.4) 50%, transparent 51%),
            radial-gradient(1px 1px at 16% 82%, rgba(255,255,255,0.3) 50%, transparent 51%),
            radial-gradient(1.5px 1.5px at 92% 12%, rgba(255,255,255,0.35) 50%, transparent 51%),
            radial-gradient(ellipse 90% 60% at 50% 45%, rgba(99, 70, 180, 0.45) 0%, transparent 60%),
            linear-gradient(145deg, #1a1040 0%, #2d1b69 36%, #4c1d95 68%, #3b1877 100%);
        transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.5s step-end;
    }
    #auragold-page-loader.auragold-page-loader--done {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    #auragold-page-loader .auragold-page-loader__card {
        width: min(360px, 100%);
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 28px 64px rgba(15, 10, 40, 0.45);
        padding: 40px 32px 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-sizing: border-box;
        animation: auragold-loader-card-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    @keyframes auragold-loader-card-in {
        from { opacity: 0; transform: translateY(12px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    #auragold-page-loader .auragold-page-loader__mark {
        position: relative;
        width: 118px;
        height: 118px;
        flex-shrink: 0;
        margin-bottom: 20px;
    }
    /* Outer gold arc */
    #auragold-page-loader .auragold-page-loader__ring-gold {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 2.5px solid transparent;
        border-top-color: #f5c518;
        border-right-color: #fbbf24;
        clip-path: polygon(50% 50%, 100% 0, 100% 100%, 55% 100%);
        animation: auragold-spin 1.25s linear infinite;
        box-sizing: border-box;
    }
    /* Inner purple arc — opposite direction */
    #auragold-page-loader .auragold-page-loader__ring-purple {
        position: absolute;
        inset: 5px;
        border-radius: 50%;
        border: 2.5px solid transparent;
        border-bottom-color: #7c5cbf;
        border-left-color: #a78bfa;
        clip-path: polygon(50% 50%, 0 0, 0 100%, 45% 100%);
        animation: auragold-spin-rev 1.55s linear infinite;
        box-sizing: border-box;
    }
    @keyframes auragold-spin {
        to { transform: rotate(360deg); }
    }
    @keyframes auragold-spin-rev {
        to { transform: rotate(-360deg); }
    }
    #auragold-page-loader .auragold-page-loader__disc {
        position: absolute;
        inset: 14px;
        border-radius: 50%;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 8px 22px rgba(45, 27, 105, 0.16);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #auragold-page-loader .auragold-page-loader__logo {
        width: 78%;
        height: 78%;
        object-fit: contain;
        object-position: center;
        display: block;
    }
    #auragold-page-loader .auragold-page-loader__suite {
        margin: 0 0 6px;
        padding: 0;
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #d4a017;
    }
    #auragold-page-loader .auragold-page-loader__brand {
        margin: 0 0 14px;
        padding: 0;
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: #1a1040;
        line-height: 1.15;
    }
    #auragold-page-loader .auragold-page-loader__status {
        margin: 0 0 18px;
        padding: 0;
        min-height: 1.4em;
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        font-size: 13px;
        font-weight: 400;
        color: #94a3b8;
        line-height: 1.4;
        transition: opacity 0.25s ease;
    }
    #auragold-page-loader .auragold-page-loader__status.is-fading {
        opacity: 0;
    }
    #auragold-page-loader .auragold-page-loader__dots {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 11px;
        margin: 0 0 22px;
        list-style: none;
        padding: 0;
    }
    #auragold-page-loader .auragold-page-loader__dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #e9d5ff;
        opacity: 0.7;
        transition: transform 0.28s ease, background 0.28s ease, opacity 0.28s ease, box-shadow 0.28s ease;
    }
    #auragold-page-loader .auragold-page-loader__dot.is-done {
        background: #fbbf24;
        opacity: 0.85;
    }
    #auragold-page-loader .auragold-page-loader__dot.is-active {
        width: 10px;
        height: 10px;
        background: #6d28d9;
        opacity: 1;
        box-shadow: 0 0 0 5px rgba(109, 40, 217, 0.22);
    }
    #auragold-page-loader .auragold-page-loader__track {
        width: 100%;
        height: 6px;
        border-radius: 999px;
        background: #ede9fe;
        overflow: hidden;
        position: relative;
    }
    #auragold-page-loader .auragold-page-loader__bar {
        display: block;
        height: 100%;
        width: 6%;
        border-radius: inherit;
        background: linear-gradient(90deg, #f5c518 0%, #f5c518 12px, #7c3aed 12px, #6d28d9 100%);
        transition: width 0.45s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
</style>
HTML;
    }
}

if (!function_exists('auragold_brand_page_loader_after_body_html')) {
    function auragold_brand_page_loader_after_body_html(): string
    {
        $logo = 'assets/img/logo.png';
        if (function_exists('auragold_asset_url')) {
            $logo = auragold_asset_url('assets/img/logo.png');
        }
        $logoEsc = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div id="auragold-page-loader" role="progressbar" aria-busy="true" aria-valuemin="0" aria-valuemax="100" aria-valuenow="6" aria-label="Loading">
    <div class="auragold-page-loader__card">
        <div class="auragold-page-loader__mark">
            <div class="auragold-page-loader__ring-gold" aria-hidden="true"></div>
            <div class="auragold-page-loader__ring-purple" aria-hidden="true"></div>
            <div class="auragold-page-loader__disc">
                <img class="auragold-page-loader__logo" src="{$logoEsc}" width="72" height="72" alt="">
            </div>
        </div>
        <p class="auragold-page-loader__suite">Jewellery ERP Suite</p>
        <p class="auragold-page-loader__brand">Gold Matrix</p>
        <p class="auragold-page-loader__status" id="auragold-page-loader-status">Authenticating your session…</p>
        <ul class="auragold-page-loader__dots" id="auragold-page-loader-dots" aria-hidden="true">
            <li class="auragold-page-loader__dot is-active"></li>
            <li class="auragold-page-loader__dot"></li>
            <li class="auragold-page-loader__dot"></li>
            <li class="auragold-page-loader__dot"></li>
        </ul>
        <div class="auragold-page-loader__track">
            <span class="auragold-page-loader__bar" id="auragold-page-loader-bar"></span>
        </div>
    </div>
</div>
<script>
(function () {
    document.documentElement.classList.add('auragold-page-loading');
    if (document.body) {
        document.body.classList.add('auragold-page-loading');
    }
})();
</script>
HTML;
    }
}

if (!function_exists('auragold_brand_page_loader_js')) {
    function auragold_brand_page_loader_js(): string
    {
        return <<<'HTML'
<script>
(function () {
    /* Must wait for body HTML — this file is often printed in <head>. */
    function boot() {
        var minMs = 2800;
        var t0 = typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
        var done = false;
        var step = 0;
        var statuses = [
            'Authenticating your session…',
            'Preparing counters & vouchers…',
            'Loading masters & rates…',
            'Almost ready…'
        ];
        var widths = [14, 38, 62, 86];
        var root = document.getElementById('auragold-page-loader');
        if (!root) {
            return;
        }
        var statusEl = document.getElementById('auragold-page-loader-status');
        var barEl = document.getElementById('auragold-page-loader-bar');
        var dots = root.querySelectorAll('.auragold-page-loader__dot');
        var tickTimer = null;

        document.documentElement.classList.add('auragold-page-loading');
        document.body.classList.add('auragold-page-loading');

        function setProgress(pct, label) {
            if (barEl) {
                barEl.style.width = pct + '%';
            }
            root.setAttribute('aria-valuenow', String(pct));
            if (statusEl && label && statusEl.textContent !== label) {
                statusEl.classList.add('is-fading');
                setTimeout(function () {
                    statusEl.textContent = label;
                    statusEl.classList.remove('is-fading');
                }, 160);
            }
        }

        function paintStep(i) {
            step = i;
            setProgress(widths[i] || 86, statuses[i] || statuses[statuses.length - 1]);
            for (var d = 0; d < dots.length; d++) {
                dots[d].classList.remove('is-active', 'is-done');
                if (d < i) {
                    dots[d].classList.add('is-done');
                } else if (d === i) {
                    dots[d].classList.add('is-active');
                }
            }
        }

        paintStep(0);
        tickTimer = setInterval(function () {
            if (done) {
                return;
            }
            var next = Math.min(step + 1, statuses.length - 1);
            paintStep(next);
            if (next >= statuses.length - 1 && tickTimer) {
                clearInterval(tickTimer);
                tickTimer = null;
            }
        }, 650);

        function finish() {
            if (done) {
                return;
            }
            done = true;
            if (tickTimer) {
                clearInterval(tickTimer);
                tickTimer = null;
            }
            setProgress(100, 'Ready');
            for (var d = 0; d < dots.length; d++) {
                dots[d].classList.remove('is-active');
                dots[d].classList.add('is-done', 'is-active');
            }
            document.documentElement.classList.remove('auragold-page-loading');
            document.body.classList.remove('auragold-page-loading');
            root.setAttribute('aria-busy', 'false');
            root.classList.add('auragold-page-loader--done');
            setTimeout(function () {
                if (root && root.parentNode) {
                    root.parentNode.removeChild(root);
                }
            }, 520);
        }

        function scheduleFinish() {
            var now = typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
            var wait = minMs - (now - t0);
            if (wait > 0) {
                setTimeout(finish, wait);
            } else {
                finish();
            }
        }

        if (document.readyState === 'complete') {
            scheduleFinish();
        } else {
            window.addEventListener('load', scheduleFinish);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
HTML;
    }
}
