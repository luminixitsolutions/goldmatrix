<?php
/**
 * Common Set Software sidebar – include in set-software.php, masters.php, etc.
 * Highlights current page via $current_page (basename of PHP_SELF).
 */
require_once __DIR__ . '/includes/session_login_type.php';
$current_page = basename($_SERVER['PHP_SELF']);
// Branches: admin + not sub-branch (unless superadmin / superbranch). Masters &c. unchanged.
$show_branches_menu = !empty($_SESSION['Admin']) && auragold_session_is_admin_login_type()
    && auragold_session_may_see_set_software_branches_menu();
$region_sub_pages = ['master-country.php', 'master-state.php', 'master-city.php'];
$region_nav_open = in_array($current_page, $region_sub_pages, true);
$ewaybill_sub_pages = ['ewaybill-api-settings.php', 'ewaybill-authentication.php'];
$ewaybill_nav_open = in_array($current_page, $ewaybill_sub_pages, true);
$auragold_set_ss_title = function_exists('auragold_t')
    ? htmlspecialchars(auragold_t('set_software.title'), ENT_QUOTES, 'UTF-8')
    : 'Set Software';
$auragold_t_ss = static function ($key) {
    return function_exists('auragold_t')
        ? htmlspecialchars(auragold_t($key), ENT_QUOTES, 'UTF-8')
        : htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
};
$auragold_collapse_show = $auragold_t_ss('set_software.collapse_show');
$auragold_collapse_hide = $auragold_t_ss('set_software.collapse_hide');
$auragold_ss_menu_open = function_exists('auragold_t')
    ? htmlspecialchars(auragold_t('set_software.open_menu'), ENT_QUOTES, 'UTF-8')
    : 'Open Set Software menu';
$auragold_ss_menu_close = function_exists('auragold_t')
    ? htmlspecialchars(auragold_t('set_software.close_menu'), ENT_QUOTES, 'UTF-8')
    : 'Close menu';
?>
<div class="set-software-drawer-backdrop" id="setSoftwareDrawerBackdrop" aria-hidden="true"></div>
<!-- Left Set Software sidebar (common include) -->
<aside class="set-software-sidebar" id="set-software-nav-aside" aria-label="<?php echo $auragold_set_ss_title; ?>">
    <div class="set-software-sidebar-mobile-head d-lg-none">
        <div class="set-software-sidebar-title"><?php echo $auragold_set_ss_title; ?></div>
        <button type="button" class="set-software-drawer-close" id="setSoftwareDrawerClose" aria-label="<?php echo $auragold_ss_menu_close; ?>">
            <i class="feather icon-x" aria-hidden="true"></i>
        </button>
    </div>
    <div class="set-software-sidebar-title d-none d-lg-block"><?php echo $auragold_set_ss_title; ?></div>
    <a href="set-software.php" class="set-software-nav-item<?php echo ($current_page === 'set-software.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-hash"></i> <?php echo $auragold_t_ss('set_software.barcode_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="font-settings.php" class="set-software-nav-item<?php echo ($current_page === 'font-settings.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-type"></i> <?php echo $auragold_t_ss('set_software.font_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="language-settings.php" class="set-software-nav-item<?php echo ($current_page === 'language-settings.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-globe"></i> <?php echo $auragold_t_ss('set_software.language_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="mail-settings.php" class="set-software-nav-item<?php echo ($current_page === 'mail-settings.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-mail"></i> <?php echo $auragold_t_ss('set_software.mail_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="mobile-menu-settings.php" class="set-software-nav-item<?php echo ($current_page === 'mobile-menu-settings.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-smartphone"></i> <?php echo $auragold_t_ss('set_software.mobile_menu_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="masters.php" class="set-software-nav-item<?php echo ($current_page === 'masters.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-grid"></i> <?php echo $auragold_t_ss('set_software.masters'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <details class="set-software-nav-group"<?php echo $region_nav_open ? ' open' : ''; ?>>
        <summary class="set-software-nav-summary">
            <span><i class="feather icon-map-pin"></i> <?php echo $auragold_t_ss('set_software.region'); ?></span>
            <i class="feather icon-chevron-down set-software-nav-summary-chevron"></i>
        </summary>
        <div class="set-software-nav-sub">
            <a href="master-country.php" class="set-software-nav-sub-item<?php echo ($current_page === 'master-country.php') ? ' active' : ''; ?>"><?php echo $auragold_t_ss('set_software.region_country'); ?></a>
            <a href="master-state.php" class="set-software-nav-sub-item<?php echo ($current_page === 'master-state.php') ? ' active' : ''; ?>"><?php echo $auragold_t_ss('set_software.region_state'); ?></a>
            <a href="master-city.php" class="set-software-nav-sub-item<?php echo ($current_page === 'master-city.php') ? ' active' : ''; ?>"><?php echo $auragold_t_ss('set_software.region_city'); ?></a>
        </div>
    </details>
    <?php if ($show_branches_menu): ?>
    <a href="branches.php" class="set-software-nav-item<?php echo ($current_page === 'branches.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-layers"></i> <?php echo $auragold_t_ss('set_software.branches'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <?php endif; ?>
    <a href="accounting-masters.php" class="set-software-nav-item<?php echo ($current_page === 'accounting-masters.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-book"></i> <?php echo $auragold_t_ss('set_software.accounting_masters'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="voucher-setting.php" class="set-software-nav-item<?php echo ($current_page === 'voucher-setting.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-file-text"></i> <?php echo $auragold_t_ss('set_software.voucher_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="bill-series.php" class="set-software-nav-item<?php echo ($current_page === 'bill-series.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-hash"></i> <?php echo $auragold_t_ss('set_software.bill_series'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="invoice-print-settings.php" class="set-software-nav-item<?php echo ($current_page === 'invoice-print-settings.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-printer"></i> <?php echo $auragold_t_ss('set_software.invoice_print_setting'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <a href="reward-point-coupons-referral.php" class="set-software-nav-item<?php echo ($current_page === 'reward-point-coupons-referral.php') ? ' active' : ''; ?>">
        <span><i class="feather icon-award" aria-hidden="true"></i> <?php echo $auragold_t_ss('set_software.reward_point_coupons_referral'); ?></span>
        <i class="feather icon-chevron-right"></i>
    </a>
    <details class="set-software-nav-group"<?php echo $ewaybill_nav_open ? ' open' : ''; ?>>
        <summary class="set-software-nav-summary">
            <span><i class="feather icon-package" aria-hidden="true"></i> <?php echo $auragold_t_ss('set_software.eway_bill_group'); ?></span>
            <i class="feather icon-chevron-down set-software-nav-summary-chevron"></i>
        </summary>
        <div class="set-software-nav-sub">
            <a href="ewaybill-api-settings.php" class="set-software-nav-sub-item<?php echo ($current_page === 'ewaybill-api-settings.php') ? ' active' : ''; ?>"><?php echo $auragold_t_ss('set_software.eway_bill_api'); ?></a>
            <a href="ewaybill-authentication.php" class="set-software-nav-sub-item<?php echo ($current_page === 'ewaybill-authentication.php') ? ' active' : ''; ?>"><?php echo $auragold_t_ss('set_software.eway_bill_auth'); ?></a>
        </div>
    </details>
</aside>
<button type="button" class="set-software-collapse-tab" title="<?php echo $auragold_collapse_hide; ?>" aria-expanded="true" aria-controls="set-software-nav-aside" data-auragold-title-show="<?php echo $auragold_collapse_show; ?>" data-auragold-title-hide="<?php echo $auragold_collapse_hide; ?>"><i class="feather icon-chevron-left"></i></button>
<script>
(function () {
    var ssMenuOpenLabel = <?php echo json_encode($auragold_ss_menu_open, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var ssMenuTitle = <?php echo json_encode($auragold_set_ss_title, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function ssIsMobile() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function initSetSoftwareCollapse(wrap) {
        var tab = wrap.querySelector('.set-software-collapse-tab');
        if (!tab || tab.dataset.ssCollapseBound) return;
        tab.dataset.ssCollapseBound = '1';
        var icon = tab.querySelector('i');
        var aside = wrap.querySelector('.set-software-sidebar');
        var titleShow = tab.getAttribute('data-auragold-title-show') || 'Show menu';
        var titleHide = tab.getAttribute('data-auragold-title-hide') || 'Hide menu';
        function apply(collapsed) {
            if (ssIsMobile()) {
                collapsed = false;
            }
            wrap.classList.toggle('set-software-sidebar-collapsed', collapsed);
            tab.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            tab.title = collapsed ? titleShow : titleHide;
            if (aside) aside.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
            if (icon) {
                icon.className = 'feather ' + (collapsed ? 'icon-chevron-right' : 'icon-chevron-left');
            }
            if (!ssIsMobile()) {
                try { localStorage.setItem('setSoftwareSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
            }
        }
        var stored = null;
        try { stored = localStorage.getItem('setSoftwareSidebarCollapsed'); } catch (e) {}
        if (stored === '1' && !ssIsMobile()) apply(true);
        tab.addEventListener('click', function () {
            if (ssIsMobile()) return;
            apply(!wrap.classList.contains('set-software-sidebar-collapsed'));
        });
    }

    function initSetSoftwareMobileDrawer(wrap) {
        if (wrap.dataset.ssMobileBound) return;
        wrap.dataset.ssMobileBound = '1';
        var backdrop = document.getElementById('setSoftwareDrawerBackdrop');
        var aside = document.getElementById('set-software-nav-aside');
        var closeBtn = document.getElementById('setSoftwareDrawerClose');
        var openBtn = document.getElementById('setSoftwareMobileMenuBtn');

        if (!openBtn) {
            var main = wrap.querySelector('.set-software-main');
            if (main) {
                var bar = document.createElement('div');
                bar.className = 'set-software-mobile-toolbar d-lg-none';
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'set-software-mobile-menu-btn';
                btn.id = 'setSoftwareMobileMenuBtn';
                btn.setAttribute('aria-label', ssMenuOpenLabel);
                btn.setAttribute('aria-expanded', 'false');
                btn.setAttribute('aria-controls', 'set-software-nav-aside');
                btn.innerHTML = '<i class="feather icon-menu" aria-hidden="true"></i><span></span>';
                btn.querySelector('span').textContent = ssMenuTitle;
                bar.appendChild(btn);
                main.insertBefore(bar, main.firstChild);
                openBtn = btn;
            }
        }

        function openDrawer() {
            if (!ssIsMobile()) return;
            document.body.classList.add('set-software-drawer-open');
            wrap.classList.remove('set-software-sidebar-collapsed');
            if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
            if (backdrop) backdrop.setAttribute('aria-hidden', 'false');
            if (aside) aside.setAttribute('aria-hidden', 'false');
        }
        function closeDrawer() {
            document.body.classList.remove('set-software-drawer-open');
            if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
            if (backdrop) backdrop.setAttribute('aria-hidden', 'true');
        }

        if (openBtn) {
            openBtn.addEventListener('click', function () {
                if (document.body.classList.contains('set-software-drawer-open')) closeDrawer();
                else openDrawer();
            });
        }
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
        if (aside) {
            aside.addEventListener('click', function (e) {
                var link = e.target.closest('a.set-software-nav-item, a.set-software-nav-sub-item');
                if (link && ssIsMobile()) closeDrawer();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('set-software-drawer-open')) {
                closeDrawer();
            }
        });
        var mq = window.matchMedia('(max-width: 991.98px)');
        var onMq = function () {
            if (!ssIsMobile()) closeDrawer();
        };
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', onMq);
        } else if (typeof mq.addListener === 'function') {
            mq.addListener(onMq);
        }
    }

    function bootSetSoftwareNav() {
        document.querySelectorAll('.set-software-wrapper').forEach(function (wrap) {
            initSetSoftwareCollapse(wrap);
            initSetSoftwareMobileDrawer(wrap);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootSetSoftwareNav);
    } else {
        bootSetSoftwareNav();
    }
})();
</script>
