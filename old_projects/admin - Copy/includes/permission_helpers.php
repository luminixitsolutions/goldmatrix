<?php

require_once __DIR__ . '/permission_definitions.php';

/**
 * Load grant map for a tbl_users id from master DB (single branch scope).
 *
 * @param int $branchId 0 = default / all-branch fallback rows.
 *
 * @return array<string, int> perm_key => 0|1
 */
function auragold_permission_grants_map_for_user_branch($conn, $userId, $branchId = 0)
{
    $userId   = (int) $userId;
    $branchId = (int) $branchId;
    if ($userId <= 0 || !$conn || !($conn instanceof mysqli)) {
        return [];
    }
    $rows = [];
    $res  = mysqli_query(
        $conn,
        'SELECT perm_key, granted FROM tbl_user_permission_grants WHERE user_id = ' . $userId . ' AND branch_id = ' . $branchId
    );
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $k = (string) ($row['perm_key'] ?? '');
            if ($k !== '') {
                $rows[$k] = (int) ($row['granted'] ?? 0) ? 1 : 0;
            }
        }
    }
    return $rows;
}

/**
 * @deprecated Use auragold_permission_grants_map_for_user_branch($conn, $userId, 0).
 *
 * @return array<string, int> perm_key => 0|1
 */
function auragold_permission_grants_map_for_user($conn, $userId)
{
    return auragold_permission_grants_map_for_user_branch($conn, $userId, 0);
}

/**
 * Effective grant map for permission checks: uses rows for the session’s effective branch when present, else branch_id 0.
 *
 * @return array<string, int>|null null = legacy “no rows” allow-all
 */
function auragold_permission_effective_map_for_session_user($conn, $userId)
{
    $userId = (int) $userId;
    if ($userId <= 0 || !$conn || !($conn instanceof mysqli)) {
        return [];
    }
    if (!function_exists('auragold_effective_branch_id')) {
        require_once __DIR__ . '/auragold_branch_data_scope.php';
    }
    $eff = (int) auragold_effective_branch_id();

    $cntB = auragold_permission_grants_count_for_user_branch($conn, $userId, $eff);
    if ($eff > 0 && $cntB > 0) {
        return auragold_permission_grants_map_for_user_branch($conn, $userId, $eff);
    }
    return auragold_permission_grants_map_for_user_branch($conn, $userId, 0);
}

/**
 * Whether the logged-in tbl_users account may perform $permKey.
 * Branch / non-tbl_users sessions: always true (not user-wise restricted here).
 * If the user has no rows in tbl_user_permission_grants: allow all (legacy).
 * If any row exists: missing key = denied; explicit 1 = allow, 0 = deny.
 */
function auragold_user_can($permKey)
{
    if (!function_exists('auragold_session_is_admin_login_type')) {
        require_once __DIR__ . '/session_login_type.php';
    }
    $src = isset($_SESSION['login_source']) ? (string) $_SESSION['login_source'] : '';
    if ($src !== 'user') {
        return true;
    }
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        return true;
    }
    global $conn;
    if (empty($conn) || !($conn instanceof mysqli)) {
        return true;
    }
    if (!function_exists('auragold_ensure_user_permissions_table')) {
        require_once __DIR__ . '/permissions_schema.php';
    }
    auragold_ensure_user_permissions_table($conn);

    $cres = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM tbl_user_permission_grants WHERE user_id = ' . $uid);
    $cnt  = 0;
    if ($cres && ($cr = mysqli_fetch_assoc($cres))) {
        $cnt = (int) ($cr['c'] ?? 0);
    }
    if ($cnt === 0) {
        return true;
    }

    $key = trim((string) $permKey);
    if ($key === '') {
        return false;
    }
    $map = auragold_permission_effective_map_for_session_user($conn, $uid);
    if (!array_key_exists($key, $map)) {
        return false;
    }
    return !empty($map[$key]);
}

/**
 * @return int
 */
function auragold_permission_grants_count_for_user_branch($conn, $userId, $branchId)
{
    $userId   = (int) $userId;
    $branchId = (int) $branchId;
    if ($userId <= 0 || !$conn || !($conn instanceof mysqli)) {
        return 0;
    }
    $res = mysqli_query(
        $conn,
        'SELECT COUNT(*) AS c FROM tbl_user_permission_grants WHERE user_id = ' . $userId . ' AND branch_id = ' . $branchId
    );
    if ($res && ($r = mysqli_fetch_assoc($res))) {
        return (int) ($r['c'] ?? 0);
    }
    return 0;
}
