<?php
/**
 * Full-screen brand loader (logo + ring + Gold Matrix). Used by dashboard shell, header-script, and sidebar.
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
        position: fixed;
        inset: 0;
        z-index: 2147483000;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 14px;
        background:
            radial-gradient(ellipse 120% 80% at 50% -20%, rgba(251, 191, 36, 0.22) 0%, transparent 55%),
            radial-gradient(ellipse 90% 60% at 100% 100%, rgba(124, 58, 237, 0.35) 0%, transparent 50%),
            linear-gradient(165deg, #1e1b4b 0%, #4c1d95 42%, #6d28d9 78%, #5b21b6 100%);
        transition: opacity 0.55s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.55s step-end;
    }
    #auragold-page-loader.auragold-page-loader--done {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    #auragold-page-loader .auragold-page-loader__mark {
        position: relative;
        width: 72px;
        height: 72px;
        flex-shrink: 0;
    }
    #auragold-page-loader .auragold-page-loader__ring {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        border: 3px solid rgba(251, 191, 36, 0.2);
        border-top-color: #fbbf24;
        border-right-color: rgba(251, 191, 36, 0.65);
        animation: auragold-spin 0.95s linear infinite;
        box-sizing: border-box;
    }
    @keyframes auragold-spin {
        to { transform: rotate(360deg); }
    }
    #auragold-page-loader .auragold-page-loader__disc {
        position: absolute;
        inset: 6px;
        border-radius: 50%;
        overflow: hidden;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    #auragold-page-loader .auragold-page-loader__logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
        display: block;
    }
    #auragold-page-loader .auragold-page-loader__tagline {
        margin: 0;
        padding: 0;
        font-family: Roboto, system-ui, sans-serif;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.08em;
        background: linear-gradient(90deg, #fef3c7 0%, #fbbf24 45%, #fde68a 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        text-shadow: 0 0 24px rgba(251, 191, 36, 0.35);
    }
</style>
HTML;
    }
}

if (!function_exists('auragold_brand_page_loader_after_body_html')) {
    function auragold_brand_page_loader_after_body_html(): string
    {
        return <<<'HTML'
<div id="auragold-page-loader" role="progressbar" aria-busy="true" aria-label="Loading">
    <div class="auragold-page-loader__mark">
        <div class="auragold-page-loader__ring" aria-hidden="true"></div>
        <div class="auragold-page-loader__disc">
            <img class="auragold-page-loader__logo" src="assets/img/logo.png" width="60" height="60" alt="">
        </div>
    </div>
    <p class="auragold-page-loader__tagline">Gold Matrix</p>
</div>
<script>
(function () {
    document.documentElement.classList.add('auragold-page-loading');
    document.body.classList.add('auragold-page-loading');
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
    var minMs = 480;
    var t0 = typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
    var done = false;

    function finish() {
        if (done) {
            return;
        }
        done = true;
        document.documentElement.classList.remove('auragold-page-loading');
        document.body.classList.remove('auragold-page-loading');
        var el = document.getElementById('auragold-page-loader');
        if (!el) {
            return;
        }
        el.setAttribute('aria-busy', 'false');
        el.classList.add('auragold-page-loader--done');
        setTimeout(function () {
            if (el && el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 600);
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
})();
</script>
HTML;
    }
}
