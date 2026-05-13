<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/dashboard_helpers.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

$DASHBOARD_PAGE_TITLE = 'Manufacturing Dashboard';
$mfg = auragold_manufacturing_dashboard();
require __DIR__ . '/includes/dashboard_shell_top.php';
require __DIR__ . '/includes/partials/dashboard_manufacturing_home.php';
require __DIR__ . '/includes/dashboard_shell_bottom.php';
