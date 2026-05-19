<?php

/**
 * Mail / SMTP settings for outbound email (single row id=1).
 * Safe to call on each request; creates table and default row when missing.
 */

if (!function_exists('auragold_ensure_mail_settings_table')) {
    function auragold_ensure_mail_settings_table($link): bool
    {
        if (!$link instanceof mysqli) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `tbl_auragold_mail_settings` (
            `id` tinyint unsigned NOT NULL DEFAULT 1,
            `smtp_host` varchar(255) NOT NULL DEFAULT '' COMMENT 'Outgoing server',
            `smtp_port` smallint unsigned NOT NULL DEFAULT 465,
            `smtp_encryption` varchar(8) NOT NULL DEFAULT 'ssl' COMMENT 'ssl, tls, none',
            `smtp_username` varchar(255) NOT NULL DEFAULT '',
            `smtp_password` varchar(512) DEFAULT NULL,
            `from_name` varchar(255) NOT NULL DEFAULT '',
            `from_email` varchar(255) NOT NULL DEFAULT '',
            `incoming_host` varchar(255) NOT NULL DEFAULT '' COMMENT 'IMAP/POP server (client reference)',
            `imap_port` smallint unsigned NOT NULL DEFAULT 993,
            `pop3_port` smallint unsigned NOT NULL DEFAULT 995,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!@mysqli_query($link, $sql)) {
            return false;
        }

        @mysqli_query($link, 'INSERT IGNORE INTO `tbl_auragold_mail_settings` (`id`) VALUES (1)');

        return true;
    }
}

if (!function_exists('auragold_get_mail_settings_row')) {
    /**
     * @return array<string, mixed>
     */
    function auragold_get_mail_settings_row($link): array
    {
        $defaults = [
            'smtp_host'       => '',
            'smtp_port'     => 465,
            'smtp_encryption' => 'ssl',
            'smtp_username'   => '',
            'smtp_password'   => '',
            'from_name'       => '',
            'from_email'      => '',
            'incoming_host'   => '',
            'imap_port'       => 993,
            'pop3_port'       => 995,
        ];
        if (!$link instanceof mysqli) {
            return $defaults;
        }
        auragold_ensure_mail_settings_table($link);
        $r = @mysqli_query($link, 'SELECT * FROM `tbl_auragold_mail_settings` WHERE `id` = 1 LIMIT 1');
        if (!$r || mysqli_num_rows($r) === 0) {
            if ($r) {
                mysqli_free_result($r);
            }

            return $defaults;
        }
        $row = mysqli_fetch_assoc($r);
        mysqli_free_result($r);
        if (!is_array($row)) {
            return $defaults;
        }

        return array_merge($defaults, $row);
    }
}

if (!function_exists('auragold_save_mail_settings_from_post')) {
    /**
     * @param array<string, mixed> $m sanitized POST mail[...] map
     * @return bool
     */
    function auragold_save_mail_settings_from_post($link, array $m): bool
    {
        if (!$link instanceof mysqli) {
            return false;
        }
        if (!auragold_ensure_mail_settings_table($link)) {
            return false;
        }

        $smtp_host = isset($m['smtp_host']) ? trim((string) $m['smtp_host']) : '';
        $smtp_port = isset($m['smtp_port']) ? (int) $m['smtp_port'] : 465;
        if ($smtp_port < 1 || $smtp_port > 65535) {
            $smtp_port = 465;
        }
        $enc = isset($m['smtp_encryption']) ? strtolower(trim((string) $m['smtp_encryption'])) : 'ssl';
        if (!in_array($enc, ['ssl', 'tls', 'none'], true)) {
            $enc = 'ssl';
        }
        $smtp_username = isset($m['smtp_username']) ? trim((string) $m['smtp_username']) : '';
        $pwd_in = isset($m['smtp_password']) ? (string) $m['smtp_password'] : '';
        $from_name = isset($m['from_name']) ? trim((string) $m['from_name']) : '';
        $from_email = isset($m['from_email']) ? trim((string) $m['from_email']) : '';
        $incoming_host = isset($m['incoming_host']) ? trim((string) $m['incoming_host']) : '';
        $imap_port = isset($m['imap_port']) ? (int) $m['imap_port'] : 993;
        $pop3_port = isset($m['pop3_port']) ? (int) $m['pop3_port'] : 995;
        if ($imap_port < 1 || $imap_port > 65535) {
            $imap_port = 993;
        }
        if ($pop3_port < 1 || $pop3_port > 65535) {
            $pop3_port = 995;
        }

        $e = static function ($link, string $s): string {
            return mysqli_real_escape_string($link, $s);
        };

        $pwd_sql = '';
        if (trim($pwd_in) !== '') {
            $pwd_sql = ", `smtp_password` = '" . $e($link, $pwd_in) . "'";
        }

        $sql = "UPDATE `tbl_auragold_mail_settings` SET
            `smtp_host` = '" . $e($link, $smtp_host) . "',
            `smtp_port` = " . (int) $smtp_port . ",
            `smtp_encryption` = '" . $e($link, $enc) . "',
            `smtp_username` = '" . $e($link, $smtp_username) . "',
            `from_name` = '" . $e($link, $from_name) . "',
            `from_email` = '" . $e($link, $from_email) . "',
            `incoming_host` = '" . $e($link, $incoming_host) . "',
            `imap_port` = " . (int) $imap_port . ",
            `pop3_port` = " . (int) $pop3_port . "
            " . $pwd_sql . "
            WHERE `id` = 1 LIMIT 1";

        return (bool) @mysqli_query($link, $sql);
    }
}
