<?php
/**
 * Apply $_SESSION working_* from tbl_branches (after login or switch).
 * working_db stores database, user, password (and db_name/db_user/db_pass keys); config.php switches $conn when set.
 * Requires config.php (getRecordMaster, DB_*) and branch_credentials.php.
 */
require_once __DIR__ . '/branch_credentials.php';
require_once __DIR__ . '/subdomain_branch.php';
require_once __DIR__ . '/branch_create_db_after_save.php';

/**
 * For tbl_branches logins: registry main row id this account belongs to (main row or parent main of a sub).
 * 0 = not a branch credential session or unknown.
 */
function auragold_branch_login_scope_main_id() {
    $login_source = isset($_SESSION['login_source']) ? (string) $_SESSION['login_source'] : '';
    if ($login_source !== 'branch') {
        return 0;
    }
    $acc_bid = (int) ($_SESSION['Admin']['id'] ?? 0);
    if ($acc_bid <= 0) {
        return 0;
    }
    $me = getRecordMaster('SELECT id, main_branch_id FROM tbl_branches WHERE id = ' . $acc_bid . ' LIMIT 1');
    if (!$me) {
        return 0;
    }
    $mid = (int) ($me['main_branch_id'] ?? 0);
    return $mid === 0 ? (int) $me['id'] : $mid;
}

function auragold_can_user_open_branch_row(array $row) {
    $login_source = isset($_SESSION['login_source']) ? (string) $_SESSION['login_source'] : '';
    $row_main_id  = (int) ($row['main_branch_id'] ?? 0);
    $row_id       = (int) ($row['id'] ?? 0);

    if ($login_source !== 'branch') {
        return true;
    }

    $account_bid = (int) ($_SESSION['Admin']['id'] ?? 0);
    if ($account_bid <= 0) {
        return true;
    }

    $acc = getRecordMaster('SELECT id, main_branch_id FROM tbl_branches WHERE id = ' . $account_bid . ' LIMIT 1');
    if (!$acc) {
        return true;
    }

    $acc_main          = (int) ($acc['main_branch_id'] ?? 0);
    $acc_is_main_row   = ($acc_main === 0);
    $acc_scope_main_id = $acc_is_main_row ? $account_bid : $acc_main;

    if ($row_main_id === 0) {
        return $row_id === $acc_scope_main_id;
    }
    return $row_main_id === $acc_scope_main_id;
}

/**
 * @param int $branchRowId 0 = clear working context (use registry / DB_NAME only)
 * @return array{ok:bool,message:string}
 */
function auragold_apply_branch_working_context($branchRowId) {
    $branchRowId = (int) $branchRowId;
    if ($branchRowId <= 0) {
        unset($_SESSION['working_db'], $_SESSION['working_branch_id'], $_SESSION['working_branch_name']);
        $_SESSION['db_name'] = defined('DB_NAME') ? (string) DB_NAME : '';
        return ['ok' => true, 'message' => ''];
    }

    $row = getRecordMaster('SELECT * FROM tbl_branches WHERE id = ' . $branchRowId . ' LIMIT 1');
    if (!$row) {
        return ['ok' => false, 'message' => 'Branch not found. You are signed in using the main database.'];
    }
    if (!auragold_can_user_open_branch_row($row)) {
        return ['ok' => false, 'message' => 'You cannot open that branch with this account. You are signed in using the main database.'];
    }

    $row_id = (int) ($row['id'] ?? 0);
    $name   = trim((string) ($row['name'] ?? ''));
    if ($name === '') {
        $name = 'Branch #' . $row_id;
    }

    global $conn_master;
    $registry = ($conn_master instanceof mysqli) ? $conn_master : null;
    $creds    = $registry
        ? auragold_resolve_branch_operational_credentials($row, $registry)
        : auragold_branch_row_db_credentials($row);
    $db_name = $creds['db_name'];
    $db_user = $creds['db_user'];
    $db_pass = $creds['db_pass'];

    if ($db_name === '') {
        unset($_SESSION['working_db']);
        $_SESSION['working_branch_id']   = $row_id;
        $_SESSION['working_branch_name'] = $name;
        $_SESSION['db_name']             = defined('DB_NAME') ? (string) DB_NAME : '';
        return ['ok' => true, 'message' => ''];
    }

    $_SESSION['working_branch_id']   = $row_id;
    $_SESSION['working_branch_name'] = $name;

    $test = auragold_mysqli_connect_branch_or_registry(DB_HOST, $db_name, $db_user, $db_pass);
    if (!$test) {
        unset($_SESSION['working_db'], $_SESSION['working_branch_id'], $_SESSION['working_branch_name'], $_SESSION['db_name']);
        $err = mysqli_connect_error();
        $hint = (stripos((string) $err, 'Unknown database') !== false)
            ? 'Create the database (e.g. “Create DB & tables” on Branches) or pick Main on login.'
            : 'Check db_users / db_password for this branch.';
        return [
            'ok'      => false,
            'message' => 'Could not open branch database “' . $db_name . '”. ' . $hint . ' You are signed in using the main database.',
        ];
    }
    mysqli_close($test);

    // Session must store credentials that actually work (main/registry account when per-branch MySQL user was never created).
    $sessionUser = (string) DB_USER;
    $sessionPass = (string) DB_PASS;
    // With PHP 8.1+ mysqli, connect failures can throw (mysqli_sql_exception). @ no longer swallows that—probe with try/catch.
    $regProbe = null;
    if (function_exists('auragold_mysqli_connect_operational') && (string) $db_name !== '') {
        $regProbe = auragold_mysqli_connect_operational(
            (string) DB_HOST,
            $sessionUser,
            $sessionPass,
            (string) $db_name
        );
    } else {
        try {
            $regProbe = @mysqli_connect((string) DB_HOST, $sessionUser, $sessionPass, (string) $db_name);
        } catch (Throwable $e) {
            $regProbe = null;
        }
    }
    if ($regProbe) {
        mysqli_close($regProbe);
    } else {
        $sessionUser = $db_user !== '' ? $db_user : (string) DB_USER;
        $sessionPass = $db_user !== '' ? $db_pass : (string) DB_PASS;
    }

    $_SESSION['working_db'] = [
        'database' => $db_name,
        'user'     => $sessionUser,
        'password' => $sessionPass,
        'db_name'  => $db_name,
        'db_user'  => $sessionUser,
        'db_pass'  => $sessionPass,
    ];
    $_SESSION['db_name'] = $db_name;

    return ['ok' => true, 'message' => ''];
}

/**
 * Registry main row id (tbl_branches.id with main_branch_id = 0) for the current session.
 * Uses branch-credential scope first, then resolves tbl_users / working context from effective branch row.
 *
 * @return int >0 main id, or 0 if nothing can be resolved
 */
if (!function_exists('auragold_session_resolved_registry_main_id_for_branch_list')) {
    function auragold_session_resolved_registry_main_id_for_branch_list(): int {
        $bScope = auragold_branch_login_scope_main_id();
        if ($bScope > 0) {
            return $bScope;
        }
        require_once __DIR__ . '/auragold_branch_data_scope.php';
        $eff = (int) auragold_effective_branch_id();
        if ($eff <= 0) {
            return auragold_registry_main_branch_id_for_login();
        }
        $row = getRecordMaster('SELECT id, main_branch_id FROM tbl_branches WHERE id = ' . $eff . ' LIMIT 1');
        if (!$row) {
            return auragold_registry_main_branch_id_for_login();
        }
        $mb = (int) ($row['main_branch_id'] ?? 0);
        return $mb === 0 ? (int) $row['id'] : $mb;
    }
}

/**
 * Map session working_db (MySQL database name) to the registry top-level main id (tbl_branches).
 * Used so Branches list shows only that main row + its sub-branches while in branch DB context.
 *
 * @return int >0 main row id, or 0
 */
if (!function_exists('auragold_registry_main_id_from_session_working_db')) {
    function auragold_registry_main_id_from_session_working_db(): int {
        if (empty($_SESSION['working_db']) || !is_array($_SESSION['working_db'])) {
            return 0;
        }
        $dbname = trim((string) ($_SESSION['working_db']['database'] ?? $_SESSION['working_db']['db_name'] ?? ''));
        if ($dbname === '' || !function_exists('getRecordMaster') || !function_exists('esc')) {
            return 0;
        }
        $row = getRecordMaster(
            "SELECT id, main_branch_id FROM tbl_branches WHERE LOWER(TRIM(db_name)) = LOWER('" . esc($dbname) . "') LIMIT 1"
        );
        if (!$row) {
            return 0;
        }
        $rowId = (int) ($row['id'] ?? 0);
        if ($rowId <= 0) {
            return 0;
        }
        $parentMain = (int) ($row['main_branch_id'] ?? 0);
        return $parentMain === 0 ? $rowId : $parentMain;
    }
}

/**
 * Branches page: 0 = show every main and sub (superadmin at registry / no branch context). Otherwise one main + subs.
 */
if (!function_exists('auragold_branches_page_list_scope_main_id')) {
    function auragold_branches_page_list_scope_main_id(): int {
        $fromWorking = auragold_registry_main_id_from_session_working_db();
        if ($fromWorking > 0) {
            return $fromWorking;
        }
        if (function_exists('auragold_session_is_superadmin') && auragold_session_is_superadmin()) {
            return 0;
        }
        return auragold_session_resolved_registry_main_id_for_branch_list();
    }
}

/**
 * Non-superadmin: registry main id that sub-branch actions must belong to. Superadmin: 0 (no restriction).
 */
if (!function_exists('auragold_session_restrict_sub_branch_ops_main_id')) {
    function auragold_session_restrict_sub_branch_ops_main_id(): int {
        if (function_exists('auragold_session_is_superadmin') && auragold_session_is_superadmin()) {
            return 0;
        }
        $b = auragold_branch_login_scope_main_id();
        if ($b > 0) {
            return $b;
        }
        return auragold_session_resolved_registry_main_id_for_branch_list();
    }
}

/**
 * Sub-branches share a login portal with their main: after logout, ?branch_entry= should be the
 * registry main row (main_branch_id = 0), not the sub-branch id.
 *
 * @param int $registryBranchId tbl_branches.id for current context
 * @return int Main branch id for login URL, or $registryBranchId if already main / unknown
 */
if (!function_exists('auragold_registry_main_branch_id_for_logout_entry')) {
    function auragold_registry_main_branch_id_for_logout_entry(int $registryBranchId): int {
        $registryBranchId = (int) $registryBranchId;
        if ($registryBranchId <= 0 || !function_exists('getRecordMaster')) {
            return $registryBranchId;
        }
        $row = getRecordMaster(
            'SELECT id, IFNULL(main_branch_id, 0) AS mb FROM tbl_branches WHERE id = ' . $registryBranchId . ' LIMIT 1'
        );
        if (!$row || empty($row['id'])) {
            return $registryBranchId;
        }
        $parentMain = (int) ($row['mb'] ?? 0);
        return $parentMain > 0 ? $parentMain : (int) $row['id'];
    }
}

/**
 * Registry tbl_branches.id for post-logout redirects (?branch_entry=… / portal folder).
 * Prefers the DB actually in use (working_db or mysqli) over branch_id, which may still point at main
 * when the operational connection came from the HTTP host (subdomain) without working_db.
 * Sub-branch sessions resolve to the parent main id for branch_entry (shared login entry).
 *
 * @return int
 */
if (!function_exists('auragold_resolve_logout_branch_entry_id')) {
    function auragold_resolve_logout_branch_entry_id(): int {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return 0;
        }
        $raw = 0;
        $wdb = $_SESSION['working_db'] ?? null;
        if (is_array($wdb)) {
            $dbname = trim((string) ($wdb['database'] ?? $wdb['db_name'] ?? ''));
            if ($dbname !== '' && function_exists('getRecordMaster') && function_exists('esc')) {
                $row = getRecordMaster(
                    "SELECT id FROM tbl_branches WHERE LOWER(TRIM(db_name)) = LOWER('" . esc($dbname) . "') LIMIT 1"
                );
                if ($row && !empty($row['id'])) {
                    $raw = (int) $row['id'];
                }
            }
        }
        if ($raw <= 0 && function_exists('auragold_effective_branch_id')) {
            $e = (int) auragold_effective_branch_id();
            if ($e > 0) {
                $raw = $e;
            }
        }
        if ($raw <= 0) {
            global $conn;
            if (isset($conn) && $conn instanceof mysqli) {
                $rs = @mysqli_query($conn, 'SELECT DATABASE() AS db');
                if ($rs && ($dbRow = mysqli_fetch_assoc($rs))) {
                    mysqli_free_result($rs);
                    $dbname = trim((string) ($dbRow['db'] ?? ''));
                    if ($dbname !== '' && function_exists('getRecordMaster') && function_exists('esc')) {
                        $map = getRecordMaster(
                            "SELECT id FROM tbl_branches WHERE LOWER(TRIM(db_name)) = LOWER('" . esc($dbname) . "') LIMIT 1"
                        );
                        if ($map && !empty($map['id'])) {
                            $raw = (int) $map['id'];
                        }
                    }
                }
            }
        }
        if ($raw <= 0) {
            $raw = (int) ($_SESSION['working_branch_id'] ?? $_SESSION['branch_id'] ?? 0);
        }
        return auragold_registry_main_branch_id_for_logout_entry($raw);
    }
}
