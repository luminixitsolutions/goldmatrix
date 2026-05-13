<?php
/**
 * Switch working database to a branch row's db_name / db_users / db_password (tbl_branches).
 * Main branch users: only own main + its sub-branches. Legacy users: any branch.
 */
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/branch_working_context.php';
require_once __DIR__ . '/includes/login_authenticate.php';

if (empty($_SESSION['Admin'])) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: branches.php');
    exit;
}

$loginSrc = isset($_SESSION['login_source']) ? (string) $_SESSION['login_source'] : '';
if ($loginSrc === 'user') {
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid > 0) {
        $userRow = getRecord('SELECT * FROM tbl_users WHERE id = ' . $uid . ' LIMIT 1');
        if ((!$userRow || !is_array($userRow)) && function_exists('getRecordMaster')) {
            $userRow = getRecordMaster('SELECT * FROM tbl_users WHERE id = ' . $uid . ' LIMIT 1');
        }
        if ($userRow) {
            $perm = auragold_validate_user_branch_switch_target($id, $userRow);
            if (empty($perm['ok'])) {
                $msg = trim((string) ($perm['message'] ?? ''));
                header('Location: branches.php?login_error=' . rawurlencode($msg !== '' ? $msg : 'You cannot switch to that branch.'));
                exit;
            }
        }
    }
}

$result = auragold_apply_branch_working_context($id);
if (!$result['ok']) {
    header('Location: branches.php?login_error=' . rawurlencode($result['message']));
    exit;
}

$_SESSION['branch_id'] = $id;

if (function_exists('session_regenerate_id')) {
    @session_regenerate_id(true);
}
if (function_exists('auragold_session_refresh_live_cookie')) {
    auragold_session_refresh_live_cookie();
}

header('Location: dashboard.php');
exit;
