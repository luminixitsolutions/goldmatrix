<?php
/**
 * user_role: set on Branch Login (index.php) from selected branch — Main (id 0) = admin menu; sub id = branch menu.
 * tbl_users logins: always admin. switch_branch.php updates branch_id only, never user_role.
 * login_type: credential source (tbl_users vs tbl_branches) — not used for sidebar; use user_role.
 */
if (!function_exists('auragold_session_user_role')) {
    function auragold_session_user_role() {
        $r = isset($_SESSION['user_role']) ? trim((string) $_SESSION['user_role']) : '';
        if ($r === 'admin' || $r === 'branch') {
            return $r;
        }
        $src = isset($_SESSION['login_source']) ? (string) $_SESSION['login_source'] : '';
        if ($src === 'user') {
            return 'admin';
        }
        if ($src === 'branch') {
            return ((int) ($_SESSION['branch_is_main'] ?? 0) === 1) ? 'admin' : 'branch';
        }
        return 'branch';
    }

    function auragold_session_login_type() {
        $lt = isset($_SESSION['login_type']) ? trim((string) $_SESSION['login_type']) : '';
        if ($lt === 'admin' || $lt === 'branch') {
            return $lt;
        }
        return auragold_session_user_role() === 'admin' ? 'admin' : 'branch';
    }

    function auragold_session_is_admin_login_type() {
        return auragold_session_user_role() === 'admin';
    }
}

require_once __DIR__ . '/auragold_superadmin.php';

if (!function_exists('auragold_session_may_see_set_software_branches_menu')) {
    /**
     * "Branches" under Set Software: superadmin, superbranch template, or an effective main-branch row only — not a sub-branch context.
     * (tbl_users logins stay user_role=admin, so the effective row must be checked on top of that.)
     */
    function auragold_session_may_see_set_software_branches_menu(): bool {
        if (function_exists('auragold_session_is_superadmin') && auragold_session_is_superadmin()) {
            return true;
        }
        $su = (string) ($_SESSION['Admin']['Username'] ?? $_SESSION['Admin']['username'] ?? '');
        if (strcasecmp(trim($su), 'superbranch') === 0) {
            return true;
        }
        require_once __DIR__ . '/auragold_branch_data_scope.php';
        $eff = (int) auragold_effective_branch_id();
        if ($eff <= 0) {
            return true;
        }
        if (!function_exists('getRecordMaster')) {
            return true;
        }
        $row = getRecordMaster('SELECT id, main_branch_id FROM tbl_branches WHERE id = ' . (int) $eff . ' LIMIT 1');
        if (!$row) {
            return true;
        }
        return (int) ($row['main_branch_id'] ?? 0) === 0;
    }
}
