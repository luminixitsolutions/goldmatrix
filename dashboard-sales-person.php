<?php
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/dashboard_helpers.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

$sp = isset($_GET['sp']) ? trim((string) $_GET['sp']) : 'ALL';
$period = isset($_GET['period']) ? trim((string) $_GET['period']) : 'this_month';
$allowedPeriod = ['today', 'this_week', 'this_month', 'last_month'];
if (!in_array($period, $allowedPeriod, true)) {
    $period = 'this_month';
}

$sd = auragold_salesperson_dashboard_data($sp, $period);

$DASHBOARD_PAGE_TITLE = 'Salesperson Dashboard';
require __DIR__ . '/includes/dashboard_shell_top.php';
require __DIR__ . '/includes/partials/dashboard_salesperson_home.php';
require __DIR__ . '/includes/dashboard_shell_bottom.php';
