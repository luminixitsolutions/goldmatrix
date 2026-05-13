<?php
/**
 * Superadmin: usernames listed in AURAGOLD_SUPERADMIN_USERNAMES (config.php).
 */

/**
 * Central super-portal URL(s): full global branch discovery and default-main (login_branch_id 0)
 * superadmin / template "superbranch" flows must use this host in the login "IP address / server URL" field.
 * Non-production also allows localhost for development.
 */
if (!function_exists('auragold_super_portal_login_target_ok')) {
    function auragold_super_portal_login_target_ok(?string $login_target_url): bool {
        $raw = trim((string) $login_target_url);
        if ($raw === '') {
            return false;
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $raw)) {
            $raw = 'https://' . $raw;
        }
        $host = parse_url($raw, PHP_URL_HOST);
        if ($host === false || $host === null || $host === '') {
            return false;
        }
        $host = strtolower(trim((string) $host));
        $allowed = ['main.goldmatrixsoftware.com'];
        if (defined('AURAGOLD_PROJECT') && AURAGOLD_PROJECT !== 'prod') {
            $allowed[] = 'localhost';
            $allowed[] = '127.0.0.1';
        }
        return in_array($host, $allowed, true);
    }
}

if (!function_exists('auragold_session_is_superadmin')) {
    function auragold_session_is_superadmin(): bool {
        if (empty($_SESSION['Admin']) || !is_array($_SESSION['Admin'])) {
            return false;
        }
        $u = trim((string) ($_SESSION['Admin']['Username'] ?? $_SESSION['Admin']['username'] ?? ''));
        if ($u === '') {
            return false;
        }
        if (!defined('AURAGOLD_SUPERADMIN_USERNAMES')) {
            return false;
        }
        $list = array_map('trim', explode(',', (string) AURAGOLD_SUPERADMIN_USERNAMES));
        foreach ($list as $entry) {
            if ($entry !== '' && strcasecmp($u, $entry) === 0) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('auragold_username_is_superadmin')) {
    function auragold_username_is_superadmin(?string $username): bool {
        $u = trim((string) $username);
        if ($u === '' || !defined('AURAGOLD_SUPERADMIN_USERNAMES')) {
            return false;
        }
        $list = array_map('trim', explode(',', (string) AURAGOLD_SUPERADMIN_USERNAMES));
        foreach ($list as $entry) {
            if ($entry !== '' && strcasecmp($u, $entry) === 0) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('auragold_user_row_is_superadmin')) {
    function auragold_user_row_is_superadmin(?array $user): bool {
        if (empty($user) || !is_array($user)) {
            return false;
        }
        $u = '';
        foreach ($user as $k => $v) {
            if (strcasecmp((string) $k, 'Username') === 0 || strcasecmp((string) $k, 'username') === 0) {
                $u = trim((string) $v);
                break;
            }
        }
        return auragold_username_is_superadmin($u);
    }
}
