<?php
/**
 * Auto-generate auragold_{slug} database name / MySQL user label and random password for new branches.
 * Connections may fall back to registry DB_USER/DB_PASS if the dedicated MySQL user does not exist yet.
 */
require_once __DIR__ . '/branch_create_db_after_save.php';

if (!function_exists('auragold_branch_prod_alnum_password')) {
    /**
     * Random MySQL password for production branches (A–Z, a–z, 0–9), cPanel-friendly.
     */
    function auragold_branch_prod_alnum_password(int $length = 20): string {
        if ($length < 12) {
            $length = 12;
        }
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $l     = strlen($chars);
        $out   = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $l - 1)];
        }
        return $out;
    }
}

if (!function_exists('auragold_branch_slug_from_display_name')) {
    function auragold_branch_slug_from_display_name(string $name): string {
        $s = strtolower(trim($name));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);
        $s = trim($s, '_');
        if ($s === '') {
            $s = 'branch';
        }
        if (strlen($s) > 50) {
            $s = substr($s, 0, 50);
        }
        $s = trim($s, '_');
        return $s !== '' ? $s : 'branch';
    }
}

if (!function_exists('auragold_allocate_unique_branch_db_credentials')) {
    /**
     * Unique db_name (≤64 chars). Stored credentials depend on AURAGOLD_PROJECT / AURAGOLD_DB_PREFIX (config.php):
     * - local: db_name = prefix+slug, db_users = root, db_password = '' (app connects with main DB account).
     * - prod:  db_name = prefix+slug, db_users = same pattern truncated to 32 chars, random A–Z/a–z/0–9 password.
     *
     * @return array{db_name:string,db_users:string,db_password:string}
     */
    function auragold_allocate_unique_branch_db_credentials(mysqli $conn_master, string $branch_name): array {
        $prefix = defined('AURAGOLD_DB_PREFIX') ? (string) AURAGOLD_DB_PREFIX : 'auragold_';
        if ($prefix !== '' && substr($prefix, -1) !== '_') {
            $prefix .= '_';
        }
        $isProd = defined('AURAGOLD_PROJECT') && AURAGOLD_PROJECT === 'prod';

        $slug = auragold_branch_slug_from_display_name($branch_name);
        $base = $prefix . $slug;
        if (strlen($base) > 64) {
            $base = substr($base, 0, 64);
        }

        $candidate = $base;
        for ($n = 0; $n < 500; $n++) {
            if ($n > 0) {
                $suffix = '_' . $n;
                $candidate = substr($base, 0, max(1, 64 - strlen($suffix))) . $suffix;
            }
            if (!auragold_branch_mysql_identifier_ok($candidate)) {
                $candidate = rtrim($prefix, '_') . '_b_' . substr(bin2hex(random_bytes(8)), 0, 16);
                $candidate = substr($candidate, 0, 64);
            }
            $esc = mysqli_real_escape_string($conn_master, $candidate);
            $dup = getRecordMaster("SELECT id FROM tbl_branches WHERE db_name = '$esc' LIMIT 1");
            if (!$dup) {
                $dbName = $candidate;
                if ($isProd) {
                    $userRaw = $dbName;
                    if (strlen($userRaw) > 32) {
                        $userRaw = substr($userRaw, 0, 32);
                    }
                    if (!auragold_branch_mysql_identifier_ok($userRaw)) {
                        $userRaw = 'ag_' . substr(bin2hex(random_bytes(10)), 0, 28);
                    }
                    $dbPass = function_exists('auragold_branch_prod_alnum_password')
                        ? auragold_branch_prod_alnum_password(20) : bin2hex(random_bytes(10));
                } else {
                    $userRaw = 'root';
                    $dbPass  = '';
                }

                return [
                    'db_name'     => $dbName,
                    'db_users'    => $userRaw,
                    'db_password' => $dbPass,
                ];
            }
        }

        $dbName = rtrim($prefix, '_') . '_' . bin2hex(random_bytes(10));
        $dbName = substr($dbName, 0, 64);

        if ($isProd) {
            $userRaw = substr($dbName, 0, 32);
            if (!auragold_branch_mysql_identifier_ok($userRaw)) {
                $userRaw = 'ag_' . substr(bin2hex(random_bytes(10)), 0, 28);
            }
            $dbPass = function_exists('auragold_branch_prod_alnum_password')
                ? auragold_branch_prod_alnum_password(20) : bin2hex(random_bytes(10));
        } else {
            $userRaw = 'root';
            $dbPass  = '';
        }

        return [
            'db_name'     => $dbName,
            'db_users'    => $userRaw,
            'db_password' => $dbPass,
        ];
    }
}

if (!function_exists('auragold_drop_branch_database_if_configured')) {
    /**
     * DROP DATABASE for a dedicated branch schema (not the registry DB).
     *
     * @return array{ok:bool,skipped?:bool,message:string}
     */
    function auragold_drop_branch_database_if_configured(mysqli $conn_master, ?string $dbName): array {
        $dbName = trim((string) $dbName);
        if ($dbName === '' || !auragold_branch_mysql_identifier_ok($dbName)) {
            return ['ok' => true, 'skipped' => true, 'message' => ''];
        }
        if (defined('DB_NAME') && strcasecmp($dbName, (string) DB_NAME) === 0) {
            error_log('AuraGold: refuse DROP DATABASE same as registry: ' . $dbName);
            return ['ok' => true, 'skipped' => true, 'message' => ''];
        }
        if (defined('AURAGOLD_REGISTRY_DB') && strcasecmp($dbName, (string) AURAGOLD_REGISTRY_DB) === 0) {
            return ['ok' => true, 'skipped' => true, 'message' => ''];
        }
        $q = '`' . str_replace('`', '``', $dbName) . '`';
        if (@mysqli_query($conn_master, 'DROP DATABASE IF EXISTS ' . $q)) {
            error_log('AuraGold: dropped branch database `' . $dbName . '`');
            return ['ok' => true, 'message' => 'Database `' . $dbName . '` dropped.'];
        }
        $err = mysqli_error($conn_master);
        error_log('AuraGold: DROP DATABASE failed for `' . $dbName . '`: ' . $err);

        return ['ok' => false, 'message' => $err];
    }
}
