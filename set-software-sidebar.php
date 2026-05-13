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
?>
<!-- Left Set Software sidebar (common include) -->
<aside class="set-software-sidebar" id="set-software-nav-aside">
    <div class="set-software-sidebar-title"><?php echo $auragold_set_ss_title; ?></div>
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
    function initSetSoftwareCollapse(wrap) {
        var tab = wrap.querySelector('.set-software-collapse-tab');
        if (!tab || tab.dataset.ssCollapseBound) return;
        tab.dataset.ssCollapseBound = '1';
        var icon = tab.querySelector('i');
        var aside = wrap.querySelector('.set-software-sidebar');
        var titleShow = tab.getAttribute('data-auragold-title-show') || 'Show menu';
        var titleHide = tab.getAttribute('data-auragold-title-hide') || 'Hide menu';
        function apply(collapsed) {
            wrap.classList.toggle('set-software-sidebar-collapsed', collapsed);
            tab.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            tab.title = collapsed ? titleShow : titleHide;
            if (aside) aside.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
            if (icon) {
                icon.className = 'feather ' + (collapsed ? 'icon-chevron-right' : 'icon-chevron-left');
            }
            try { localStorage.setItem('setSoftwareSidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
        var stored = null;
        try { stored = localStorage.getItem('setSoftwareSidebarCollapsed'); } catch (e) {}
        if (stored === '1') apply(true);
        tab.addEventListener('click', function () {
            apply(!wrap.classList.contains('set-software-sidebar-collapsed'));
        });
    }
    document.querySelectorAll('.set-software-wrapper').forEach(initSetSoftwareCollapse);
})();
</script>
