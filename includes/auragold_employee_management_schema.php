<?php

/**
 * Employee Management — tables, seed defaults, CRUD helpers.
 */

if (!function_exists('auragold_em_resolve_branch_id')) {
    function auragold_em_resolve_branch_id(?int $explicit = null): int
    {
        if ($explicit !== null && $explicit > 0) {
            if (!function_exists('auragold_settings_branch_id_valid') || auragold_settings_branch_id_valid($explicit)) {
                return $explicit;
            }
        }
        return function_exists('auragold_settings_branch_id') ? (int) auragold_settings_branch_id() : 0;
    }
}

if (!function_exists('auragold_em_esc')) {
    function auragold_em_esc($conn, $value): string
    {
        return mysqli_real_escape_string($conn, (string) $value);
    }
}

if (!function_exists('auragold_em_ensure_tables')) {
    function auragold_em_ensure_tables($conn): bool
    {
        if (!$conn instanceof mysqli) {
            return false;
        }

        $queries = [
            "CREATE TABLE IF NOT EXISTS `tbl_employee_departments` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `name` varchar(120) NOT NULL DEFAULT '',
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_dept_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_designations` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `name` varchar(120) NOT NULL DEFAULT '',
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_desig_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_shifts` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `name` varchar(120) NOT NULL DEFAULT '',
                `start_time` time DEFAULT NULL,
                `end_time` time DEFAULT NULL,
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_shift_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_leave_types` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `name` varchar(120) NOT NULL DEFAULT '',
                `days_per_year` decimal(6,2) NOT NULL DEFAULT 0.00,
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_leave_type_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employees` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `user_id` int unsigned NOT NULL DEFAULT 0,
                `employee_code` varchar(50) NOT NULL DEFAULT '',
                `first_name` varchar(100) NOT NULL DEFAULT '',
                `last_name` varchar(100) NOT NULL DEFAULT '',
                `email` varchar(150) NOT NULL DEFAULT '',
                `phone` varchar(30) NOT NULL DEFAULT '',
                `department_id` int unsigned NOT NULL DEFAULT 0,
                `designation_id` int unsigned NOT NULL DEFAULT 0,
                `shift_id` int unsigned NOT NULL DEFAULT 0,
                `joining_date` date DEFAULT NULL,
                `basic_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
                `address` text,
                `notes` text,
                `status` varchar(20) NOT NULL DEFAULT 'Active',
                `record_status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_emp_branch` (`branch_id`),
                KEY `idx_em_emp_user` (`user_id`),
                KEY `idx_em_emp_code` (`employee_code`),
                KEY `idx_em_emp_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_documents` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `employee_id` int unsigned NOT NULL DEFAULT 0,
                `doc_type` varchar(80) NOT NULL DEFAULT '',
                `doc_title` varchar(200) NOT NULL DEFAULT '',
                `file_name` varchar(255) NOT NULL DEFAULT '',
                `file_path` varchar(500) NOT NULL DEFAULT '',
                `expiry_date` date DEFAULT NULL,
                `notes` text,
                `record_status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_doc_emp` (`employee_id`),
                KEY `idx_em_doc_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_attendance` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `employee_id` int unsigned NOT NULL DEFAULT 0,
                `attendance_date` date NOT NULL,
                `status` varchar(30) NOT NULL DEFAULT 'Present',
                `check_in` time DEFAULT NULL,
                `check_out` time DEFAULT NULL,
                `notes` varchar(255) NOT NULL DEFAULT '',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_em_att_emp_date` (`employee_id`,`attendance_date`),
                KEY `idx_em_att_branch` (`branch_id`),
                KEY `idx_em_att_date` (`attendance_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_leave` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `employee_id` int unsigned NOT NULL DEFAULT 0,
                `leave_type_id` int unsigned NOT NULL DEFAULT 0,
                `from_date` date NOT NULL,
                `to_date` date NOT NULL,
                `days` decimal(6,2) NOT NULL DEFAULT 0.00,
                `reason` text,
                `status` varchar(30) NOT NULL DEFAULT 'Pending',
                `approved_by` varchar(120) NOT NULL DEFAULT '',
                `approved_at` datetime DEFAULT NULL,
                `record_status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_leave_emp` (`employee_id`),
                KEY `idx_em_leave_branch` (`branch_id`),
                KEY `idx_em_leave_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_payroll` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `employee_id` int unsigned NOT NULL DEFAULT 0,
                `payroll_month` varchar(7) NOT NULL DEFAULT '',
                `basic_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
                `allowances` decimal(14,2) NOT NULL DEFAULT 0.00,
                `deductions` decimal(14,2) NOT NULL DEFAULT 0.00,
                `net_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
                `payment_date` date DEFAULT NULL,
                `status` varchar(30) NOT NULL DEFAULT 'Draft',
                `notes` varchar(255) NOT NULL DEFAULT '',
                `record_status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_pay_emp` (`employee_id`),
                KEY `idx_em_pay_branch` (`branch_id`),
                KEY `idx_em_pay_month` (`payroll_month`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_tasks` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `employee_id` int unsigned NOT NULL DEFAULT 0,
                `title` varchar(200) NOT NULL DEFAULT '',
                `description` text,
                `priority` varchar(20) NOT NULL DEFAULT 'Medium',
                `status` varchar(30) NOT NULL DEFAULT 'Open',
                `due_date` date DEFAULT NULL,
                `completed_at` datetime DEFAULT NULL,
                `record_status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_task_emp` (`employee_id`),
                KEY `idx_em_task_branch` (`branch_id`),
                KEY `idx_em_task_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS `tbl_employee_performance` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `branch_id` int NOT NULL DEFAULT 0,
                `employee_id` int unsigned NOT NULL DEFAULT 0,
                `review_period` varchar(80) NOT NULL DEFAULT '',
                `review_date` date DEFAULT NULL,
                `rating` decimal(3,1) NOT NULL DEFAULT 0.0,
                `kpi_score` decimal(6,2) NOT NULL DEFAULT 0.00,
                `strengths` text,
                `improvements` text,
                `reviewer_name` varchar(120) NOT NULL DEFAULT '',
                `status` varchar(30) NOT NULL DEFAULT 'Draft',
                `record_status` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_em_perf_emp` (`employee_id`),
                KEY `idx_em_perf_branch` (`branch_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($queries as $sql) {
            if (!@mysqli_query($conn, $sql)) {
                return false;
            }
        }

        auragold_em_ensure_attendance_punch_schema($conn);
        auragold_em_ensure_employee_user_link_schema($conn);

        return true;
    }
}

if (!function_exists('auragold_em_ensure_employee_user_link_schema')) {
    function auragold_em_ensure_employee_user_link_schema($conn): void
    {
        if (!$conn instanceof mysqli) {
            return;
        }
        $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `tbl_employees` LIKE 'user_id'");
        if ($chk && mysqli_num_rows($chk) === 0) {
            @mysqli_query($conn, 'ALTER TABLE `tbl_employees` ADD COLUMN `user_id` int unsigned NOT NULL DEFAULT 0 AFTER `branch_id`');
        }
        if ($chk) {
            mysqli_free_result($chk);
        }
        $idx = @mysqli_query($conn, "SHOW INDEX FROM `tbl_employees` WHERE Key_name = 'idx_em_emp_user'");
        if ($idx && mysqli_num_rows($idx) === 0) {
            @mysqli_query($conn, 'ALTER TABLE `tbl_employees` ADD KEY `idx_em_emp_user` (`user_id`)');
        }
        if ($idx) {
            mysqli_free_result($idx);
        }
    }
}

if (!function_exists('auragold_em_user_belongs_to_branch')) {
    function auragold_em_user_belongs_to_branch(array $user, int $branch_id): bool
    {
        if ($branch_id <= 0) {
            return true;
        }
        if (!function_exists('auragold_um_parse_branch_ids_string')) {
            require_once __DIR__ . '/user_management_schema.php';
        }
        $ids = auragold_um_parse_branch_ids_string($user['user_branch_ids'] ?? '');
        if ($ids === []) {
            return true;
        }
        return in_array($branch_id, $ids, true);
    }
}

if (!function_exists('auragold_em_sync_users_to_employees')) {
    /**
     * Mirror active Settings → Users into tbl_employees for attendance/payroll modules.
     */
    function auragold_em_sync_users_to_employees($conn, int $branch_id): int
    {
        if (!$conn instanceof mysqli || $branch_id <= 0) {
            return 0;
        }
        auragold_em_ensure_tables($conn);
        auragold_em_ensure_employee_user_link_schema($conn);
        if (!function_exists('auragold_ensure_user_management_columns')) {
            require_once __DIR__ . '/user_management_schema.php';
        }
        auragold_ensure_user_management_columns($conn);

        $users = getList(
            "SELECT id, Fname, Lname, EmailId, Phone, Status, user_role, user_branch_ids
             FROM tbl_users
             WHERE Status = '1'
             ORDER BY id ASC"
        );
        if (!is_array($users)) {
            return 0;
        }

        $synced = 0;
        foreach ($users as $user) {
            if (!auragold_em_user_belongs_to_branch($user, $branch_id)) {
                continue;
            }
            $uid = (int) ($user['id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            $first = trim((string) ($user['Fname'] ?? ''));
            $last = trim((string) ($user['Lname'] ?? ''));
            if ($first === '' && $last === '') {
                $first = 'User';
                $last = (string) $uid;
            }
            $email = trim((string) ($user['EmailId'] ?? ''));
            $phone = trim((string) ($user['Phone'] ?? ''));
            $role = trim((string) ($user['user_role'] ?? ''));
            $notes = $role !== '' ? 'User role: ' . $role : '';

            $existing = getRecord(
                'SELECT id, employee_code FROM tbl_employees
                 WHERE user_id = ' . $uid . ' AND record_status = 1'
                . auragold_em_branch_sql($branch_id)
                . ' LIMIT 1'
            );

            if ($existing) {
                $eid = (int) ($existing['id'] ?? 0);
                $sql = 'UPDATE tbl_employees SET '
                    . "first_name = '" . auragold_em_esc($conn, $first) . "', "
                    . "last_name = '" . auragold_em_esc($conn, $last) . "', "
                    . "email = '" . auragold_em_esc($conn, $email) . "', "
                    . "phone = '" . auragold_em_esc($conn, $phone) . "', "
                    . "status = 'Active', "
                    . 'notes = IF(notes = \'\' OR notes IS NULL, \''
                    . auragold_em_esc($conn, $notes) . '\', notes) '
                    . "WHERE id = $eid AND branch_id = " . (int) $branch_id . ' LIMIT 1';
            } else {
                $code = 'USR' . str_pad((string) $uid, 4, '0', STR_PAD_LEFT);
                $dupCode = getRecord(
                    "SELECT id FROM tbl_employees WHERE employee_code = '" . auragold_em_esc($conn, $code) . "'"
                    . auragold_em_branch_sql($branch_id)
                    . ' LIMIT 1'
                );
                if ($dupCode) {
                    $code = auragold_em_next_employee_code($conn, $branch_id);
                }
                $sql = 'INSERT INTO tbl_employees
                    (branch_id, user_id, employee_code, first_name, last_name, email, phone, status, notes, record_status, joining_date)
                    VALUES ('
                    . (int) $branch_id . ', '
                    . $uid . ", '"
                    . auragold_em_esc($conn, $code) . "', '"
                    . auragold_em_esc($conn, $first) . "', '"
                    . auragold_em_esc($conn, $last) . "', '"
                    . auragold_em_esc($conn, $email) . "', '"
                    . auragold_em_esc($conn, $phone) . "', 'Active', '"
                    . auragold_em_esc($conn, $notes) . "', 1, CURDATE())";
            }

            if (@mysqli_query($conn, $sql)) {
                $synced++;
            }
        }

        return $synced;
    }
}

if (!function_exists('auragold_em_sync_user_to_employee_branches')) {
    function auragold_em_sync_user_to_employee_branches($conn, int $user_id): void
    {
        if (!$conn instanceof mysqli || $user_id <= 0) {
            return;
        }
        if (!function_exists('auragold_ensure_user_management_columns')) {
            require_once __DIR__ . '/user_management_schema.php';
        }
        auragold_ensure_user_management_columns($conn);
        $user = getRecord('SELECT * FROM tbl_users WHERE id = ' . $user_id . ' LIMIT 1');
        if (!$user) {
            return;
        }
        $branchIds = auragold_um_parse_branch_ids_string($user['user_branch_ids'] ?? '');
        if ($branchIds === []) {
            $branchIds = [auragold_em_resolve_branch_id()];
        }
        foreach ($branchIds as $bid) {
            auragold_em_sync_users_to_employees($conn, (int) $bid);
        }
    }
}

if (!function_exists('auragold_em_ensure_attendance_punch_schema')) {
    function auragold_em_ensure_attendance_punch_schema($conn): void
    {
        if (!$conn instanceof mysqli) {
            return;
        }
        $cols = [
            'punch_in_at'  => "ALTER TABLE `tbl_employee_attendance` ADD COLUMN `punch_in_at` datetime DEFAULT NULL AFTER `check_out`",
            'punch_out_at' => "ALTER TABLE `tbl_employee_attendance` ADD COLUMN `punch_out_at` datetime DEFAULT NULL AFTER `punch_in_at`",
        ];
        foreach ($cols as $col => $sql) {
            $chk = @mysqli_query($conn, "SHOW COLUMNS FROM `tbl_employee_attendance` LIKE '$col'");
            if ($chk && mysqli_num_rows($chk) === 0) {
                @mysqli_query($conn, $sql);
            }
            if ($chk) {
                mysqli_free_result($chk);
            }
        }
        $idx = @mysqli_query($conn, "SHOW INDEX FROM `tbl_employee_attendance` WHERE Key_name = 'uq_em_att_emp_date'");
        if ($idx && mysqli_num_rows($idx) > 0) {
            @mysqli_query($conn, 'ALTER TABLE `tbl_employee_attendance` DROP INDEX `uq_em_att_emp_date`');
        }
        if ($idx) {
            mysqli_free_result($idx);
        }
        $idx2 = @mysqli_query($conn, "SHOW INDEX FROM `tbl_employee_attendance` WHERE Key_name = 'idx_em_att_open'");
        if ($idx2 && mysqli_num_rows($idx2) === 0) {
            @mysqli_query($conn, 'ALTER TABLE `tbl_employee_attendance` ADD KEY `idx_em_att_open` (`employee_id`, `punch_out_at`)');
        }
        if ($idx2) {
            mysqli_free_result($idx2);
        }
    }
}

if (!function_exists('auragold_em_attendance_stale_hours')) {
    function auragold_em_attendance_stale_hours(): int
    {
        return 15;
    }
}

if (!function_exists('auragold_em_format_datetime')) {
    function auragold_em_format_datetime($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d M Y H:i:s', $ts) : $value;
    }
}

if (!function_exists('auragold_em_sync_attendance_time_columns')) {
    function auragold_em_sync_attendance_time_columns(array $row): array
    {
        if (!empty($row['punch_in_at'])) {
            $ts = strtotime((string) $row['punch_in_at']);
            if ($ts) {
                $row['check_in'] = date('H:i:s', $ts);
            }
        }
        if (!empty($row['punch_out_at'])) {
            $ts = strtotime((string) $row['punch_out_at']);
            if ($ts) {
                $row['check_out'] = date('H:i:s', $ts);
            }
        }
        return $row;
    }
}

if (!function_exists('auragold_em_close_stale_open_punches')) {
    /**
     * Punch in without punch out for 15+ hours → mark Absent so employee can punch in again today.
     */
    function auragold_em_close_stale_open_punches($conn, int $branch_id): int
    {
        auragold_em_ensure_attendance_punch_schema($conn);
        $hrs = auragold_em_attendance_stale_hours();
        $bs = auragold_em_branch_sql($branch_id);
        $note = auragold_em_esc($conn, 'Auto absent: no punch out within ' . $hrs . ' hours.');
        $sql = "UPDATE tbl_employee_attendance
                SET status = 'Absent',
                    notes = IF(notes = '' OR notes IS NULL, '$note', CONCAT(notes, ' ', '$note'))
                WHERE punch_in_at IS NOT NULL
                  AND punch_out_at IS NULL
                  AND punch_in_at <= DATE_SUB(NOW(), INTERVAL $hrs HOUR)
                  $bs";
        @mysqli_query($conn, $sql);
        return (int) mysqli_affected_rows($conn);
    }
}

if (!function_exists('auragold_em_get_open_punch')) {
    function auragold_em_get_open_punch($conn, int $employee_id, int $branch_id): ?array
    {
        if ($employee_id <= 0) {
            return null;
        }
        auragold_em_close_stale_open_punches($conn, $branch_id);
        $hrs = auragold_em_attendance_stale_hours();
        $row = getRecord(
            "SELECT * FROM tbl_employee_attendance
             WHERE employee_id = $employee_id
               AND punch_in_at IS NOT NULL
               AND punch_out_at IS NULL
               AND punch_in_at > DATE_SUB(NOW(), INTERVAL $hrs HOUR)"
            . auragold_em_branch_sql($branch_id)
            . ' ORDER BY punch_in_at DESC LIMIT 1'
        );
        return is_array($row) ? auragold_em_sync_attendance_time_columns($row) : null;
    }
}

if (!function_exists('auragold_em_has_completed_punch_today')) {
    function auragold_em_has_completed_punch_today($conn, int $employee_id, int $branch_id, string $date = ''): bool
    {
        if ($employee_id <= 0 || !$conn instanceof mysqli) {
            return false;
        }
        if ($date === '') {
            $date = date('Y-m-d');
        }
        $dateEsc = auragold_em_esc($conn, $date);
        $row = getRecord(
            "SELECT id FROM tbl_employee_attendance
             WHERE employee_id = $employee_id
               AND punch_in_at IS NOT NULL
               AND punch_out_at IS NOT NULL
               AND (
                   attendance_date = '$dateEsc'
                   OR DATE(punch_in_at) = '$dateEsc'
                   OR DATE(punch_out_at) = '$dateEsc'
               )"
            . auragold_em_branch_sql($branch_id)
            . ' LIMIT 1'
        );

        return is_array($row) && (int) ($row['id'] ?? 0) > 0;
    }
}

if (!function_exists('auragold_em_punch_in')) {
    function auragold_em_punch_in($conn, int $branch_id, int $employee_id, string $attendance_date = ''): array
    {
        auragold_em_ensure_attendance_punch_schema($conn);
        if ($employee_id <= 0) {
            return ['ok' => false, 'message' => 'Invalid employee.'];
        }
        if ($attendance_date === '') {
            $attendance_date = date('Y-m-d');
        }
        if ($attendance_date !== date('Y-m-d')) {
            return ['ok' => false, 'message' => 'Punch in is only allowed for today (' . date('d M Y') . ').'];
        }
        auragold_em_close_stale_open_punches($conn, $branch_id);
        $open = auragold_em_get_open_punch($conn, $employee_id, $branch_id);
        if ($open) {
            return ['ok' => false, 'message' => 'Already punched in at ' . auragold_em_format_datetime($open['punch_in_at'] ?? '') . '. Please punch out first.'];
        }
        if (auragold_em_has_completed_punch_today($conn, $employee_id, $branch_id, $attendance_date)) {
            return ['ok' => false, 'message' => 'Attendance already completed for today. You can punch in again tomorrow.'];
        }
        $emp = auragold_em_get_employee_by_id($conn, $employee_id, $branch_id);
        if (!$emp) {
            return ['ok' => false, 'message' => 'Employee not found.'];
        }
        $dateEsc = auragold_em_esc($conn, $attendance_date);
        $sql = "INSERT INTO tbl_employee_attendance
                (branch_id, employee_id, attendance_date, status, check_in, punch_in_at, notes)
                VALUES (" . (int) $branch_id . ", $employee_id, '$dateEsc', 'Present', CURTIME(), NOW(), '')";
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not record punch in.'];
        }
        return [
            'ok' => true,
            'message' => 'Punch in recorded at ' . date('d M Y H:i:s') . '.',
            'id' => (int) mysqli_insert_id($conn),
            'punch_in_at' => date('Y-m-d H:i:s'),
        ];
    }
}

if (!function_exists('auragold_em_punch_out')) {
    function auragold_em_punch_out($conn, int $branch_id, int $employee_id): array
    {
        auragold_em_ensure_attendance_punch_schema($conn);
        if ($employee_id <= 0) {
            return ['ok' => false, 'message' => 'Invalid employee.'];
        }
        auragold_em_close_stale_open_punches($conn, $branch_id);
        $open = auragold_em_get_open_punch($conn, $employee_id, $branch_id);
        if (!$open) {
            return ['ok' => false, 'message' => 'No active punch in found. Punch in first or wait — open punches older than ' . auragold_em_attendance_stale_hours() . ' hours are marked absent.'];
        }
        $id = (int) ($open['id'] ?? 0);
        $sql = "UPDATE tbl_employee_attendance
                SET punch_out_at = NOW(),
                    check_out = CURTIME(),
                    status = 'Present'
                WHERE id = $id AND branch_id = " . (int) $branch_id . ' LIMIT 1';
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not record punch out.'];
        }
        return [
            'ok' => true,
            'message' => 'Punch out recorded at ' . date('d M Y H:i:s') . '.',
            'punch_out_at' => date('Y-m-d H:i:s'),
        ];
    }
}

if (!function_exists('auragold_em_attendance_duration')) {
    function auragold_em_attendance_duration(?string $in, ?string $out): string
    {
        if (!$in || !$out) {
            return '—';
        }
        $a = strtotime($in);
        $b = strtotime($out);
        if (!$a || !$b || $b < $a) {
            return '—';
        }
        $mins = (int) round(($b - $a) / 60);
        $h = intdiv($mins, 60);
        $m = $mins % 60;
        return $h . 'h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm';
    }
}

if (!function_exists('auragold_em_get_attendance_board')) {
    /**
     * All active employees with day-wise punch status (supports 24h / night shifts).
     *
     * @return array<int, array<string, mixed>>
     */
    function auragold_em_get_attendance_board($conn, int $branch_id, string $view_date = ''): array
    {
        auragold_em_ensure_attendance_punch_schema($conn);
        auragold_em_close_stale_open_punches($conn, $branch_id);
        if ($view_date === '') {
            $view_date = date('Y-m-d');
        }
        $today = date('Y-m-d');
        $isToday = ($view_date === $today);
        $viewEsc = auragold_em_esc($conn, $view_date);
        $bs = auragold_em_branch_sql($branch_id, 'a');

        $employees = auragold_em_get_employees($conn, $branch_id, 'Active');
        $rows = getList(
            "SELECT a.*, s.name AS shift_name
             FROM tbl_employee_attendance a
             LEFT JOIN tbl_employees e ON e.id = a.employee_id
             LEFT JOIN tbl_employee_shifts s ON s.id = e.shift_id
             WHERE (
                a.attendance_date = '$viewEsc'
                OR DATE(a.punch_in_at) = '$viewEsc'
                OR DATE(a.punch_out_at) = '$viewEsc'
             ) $bs
             ORDER BY a.punch_in_at DESC, a.id DESC"
        );
        if (!is_array($rows)) {
            $rows = [];
        }

        $byEmp = [];
        foreach ($rows as $r) {
            $r = auragold_em_sync_attendance_time_columns($r);
            $eid = (int) ($r['employee_id'] ?? 0);
            if ($eid > 0 && !isset($byEmp[$eid])) {
                $byEmp[$eid] = $r;
            }
        }

        $board = [];
        foreach ($employees as $emp) {
            $eid = (int) ($emp['id'] ?? 0);
            $open = auragold_em_get_open_punch($conn, $eid, $branch_id);
            $day = $byEmp[$eid] ?? null;

            if ($open && $isToday) {
                $openId = (int) ($open['id'] ?? 0);
                $dayId = $day ? (int) ($day['id'] ?? 0) : 0;
                if (!$day || $dayId === $openId || ($openId > 0 && $dayId !== $openId)) {
                    $day = $open;
                }
            }

            $status = $day['status'] ?? '—';
            if ($open && $isToday) {
                $status = 'Present (In)';
            } elseif (!$day) {
                $status = '—';
            }

            $pin = $day['punch_in_at'] ?? ($day && !empty($day['check_in']) ? ($view_date . ' ' . $day['check_in']) : '');
            $pout = $day['punch_out_at'] ?? ($day && !empty($day['check_out']) && empty($day['punch_in_at']) ? ($view_date . ' ' . $day['check_out']) : '');

            if ($open && empty($day['punch_out_at'])) {
                $pin = $open['punch_in_at'] ?? $pin;
                $pout = '';
            }

            $completedToday = $isToday && auragold_em_has_completed_punch_today($conn, $eid, $branch_id, $today);
            $canPunchIn = $isToday && !$open && !$completedToday;
            $canPunchOut = $isToday && (bool) $open;

            $board[] = [
                'employee_id' => $eid,
                'employee_code' => $emp['employee_code'] ?? '',
                'name' => auragold_em_employee_name($emp),
                'department_name' => $emp['department_name'] ?? '—',
                'shift_name' => trim((string) ($emp['shift_name'] ?? '')) !== '' ? trim((string) $emp['shift_name']) : '—',
                'attendance_id' => $day ? (int) ($day['id'] ?? 0) : 0,
                'status' => $status,
                'punch_in_at' => $pin,
                'punch_out_at' => $pout,
                'punch_in_display' => auragold_em_format_datetime($pin),
                'punch_out_display' => auragold_em_format_datetime($pout),
                'duration' => auragold_em_attendance_duration($pin ?: null, $pout ?: null),
                'open_punch' => (bool) $open,
                'can_punch_in' => $canPunchIn,
                'can_punch_out' => $canPunchOut,
                'is_today' => $isToday,
            ];
        }

        return $board;
    }
}

if (!function_exists('auragold_em_seed_defaults')) {
    function auragold_em_seed_defaults($conn, int $branch_id): void
    {
        if (!$conn instanceof mysqli || $branch_id <= 0) {
            return;
        }
        auragold_em_ensure_tables($conn);
        $bid = (int) $branch_id;

        $dept = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_departments WHERE branch_id = $bid");
        if ((int) ($dept['c'] ?? 0) === 0) {
            $defaults = ['Sales', 'Accounts', 'Production', 'Administration'];
            foreach ($defaults as $name) {
                $n = auragold_em_esc($conn, $name);
                @mysqli_query($conn, "INSERT INTO tbl_employee_departments (branch_id, name, status) VALUES ($bid, '$n', 1)");
            }
        }

        $des = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_designations WHERE branch_id = $bid");
        if ((int) ($des['c'] ?? 0) === 0) {
            $defaults = ['Manager', 'Executive', 'Accountant', 'Sales Person', 'Job Worker'];
            foreach ($defaults as $name) {
                $n = auragold_em_esc($conn, $name);
                @mysqli_query($conn, "INSERT INTO tbl_employee_designations (branch_id, name, status) VALUES ($bid, '$n', 1)");
            }
        }

        $sh = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_shifts WHERE branch_id = $bid");
        if ((int) ($sh['c'] ?? 0) === 0) {
            @mysqli_query($conn, "INSERT INTO tbl_employee_shifts (branch_id, name, start_time, end_time, status) VALUES ($bid, 'General Shift', '09:00:00', '18:00:00', 1)");
            @mysqli_query($conn, "INSERT INTO tbl_employee_shifts (branch_id, name, start_time, end_time, status) VALUES ($bid, 'Morning Shift', '08:00:00', '14:00:00', 1)");
        }

        $lt = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_leave_types WHERE branch_id = $bid");
        if ((int) ($lt['c'] ?? 0) === 0) {
            $defaults = [
                ['Casual Leave', 12],
                ['Sick Leave', 10],
                ['Earned Leave', 15],
            ];
            foreach ($defaults as $row) {
                $n = auragold_em_esc($conn, $row[0]);
                $d = (float) $row[1];
                @mysqli_query($conn, "INSERT INTO tbl_employee_leave_types (branch_id, name, days_per_year, status) VALUES ($bid, '$n', $d, 1)");
            }
        }
    }
}

if (!function_exists('auragold_em_next_employee_code')) {
    function auragold_em_next_employee_code($conn, int $branch_id): string
    {
        auragold_em_ensure_tables($conn);
        $bid = max(0, $branch_id);
        $row = getRecord("SELECT employee_code FROM tbl_employees WHERE branch_id = $bid ORDER BY id DESC LIMIT 1");
        $num = 1;
        if ($row && !empty($row['employee_code']) && preg_match('/(\d+)$/', (string) $row['employee_code'], $m)) {
            $num = (int) $m[1] + 1;
        }
        return 'EMP' . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('auragold_em_employee_name')) {
    function auragold_em_employee_name(array $row): string
    {
        $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
        return $name !== '' ? $name : trim((string) ($row['employee_code'] ?? 'Employee'));
    }
}

if (!function_exists('auragold_em_format_date')) {
    function auragold_em_format_date($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d M Y', $ts) : $value;
    }
}

if (!function_exists('auragold_em_format_money')) {
    function auragold_em_format_money($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('auragold_em_branch_sql')) {
    function auragold_em_branch_sql(int $branch_id, string $alias = ''): string
    {
        if ($branch_id <= 0) {
            return '';
        }
        $col = ($alias !== '') ? $alias . '.branch_id' : 'branch_id';
        return ' AND ' . $col . ' = ' . (int) $branch_id;
    }
}

if (!function_exists('auragold_em_get_employees')) {
    function auragold_em_get_employees($conn, int $branch_id, string $status = ''): array
    {
        auragold_em_ensure_tables($conn);
        $sql = "SELECT e.*, d.name AS department_name, g.name AS designation_name, s.name AS shift_name
                FROM tbl_employees e
                LEFT JOIN tbl_employee_departments d ON d.id = e.department_id
                LEFT JOIN tbl_employee_designations g ON g.id = e.designation_id
                LEFT JOIN tbl_employee_shifts s ON s.id = e.shift_id
                WHERE e.record_status = 1" . auragold_em_branch_sql($branch_id, 'e');
        if ($status !== '') {
            $sql .= " AND e.status = '" . auragold_em_esc($conn, $status) . "'";
        }
        $sql .= ' ORDER BY e.first_name ASC, e.last_name ASC, e.id ASC';
        $rows = getList($sql);
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_get_employee_by_id')) {
    function auragold_em_get_employee_by_id($conn, int $id, int $branch_id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $sql = "SELECT e.*, d.name AS department_name, g.name AS designation_name, s.name AS shift_name
                FROM tbl_employees e
                LEFT JOIN tbl_employee_departments d ON d.id = e.department_id
                LEFT JOIN tbl_employee_designations g ON g.id = e.designation_id
                LEFT JOIN tbl_employee_shifts s ON s.id = e.shift_id
                WHERE e.id = $id AND e.record_status = 1" . auragold_em_branch_sql($branch_id, 'e') . ' LIMIT 1';
        $row = getRecord($sql);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('auragold_em_get_master_list')) {
    function auragold_em_get_master_list($conn, string $table, int $branch_id): array
    {
        auragold_em_ensure_tables($conn);
        $allowed = [
            'tbl_employee_departments',
            'tbl_employee_designations',
            'tbl_employee_shifts',
            'tbl_employee_leave_types',
        ];
        if (!in_array($table, $allowed, true)) {
            return [];
        }
        $rows = getList("SELECT * FROM $table WHERE status = 1" . auragold_em_branch_sql($branch_id) . ' ORDER BY name ASC');
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_dashboard_stats')) {
    function auragold_em_dashboard_stats($conn, int $branch_id): array
    {
        auragold_em_ensure_tables($conn);
        $bs = auragold_em_branch_sql($branch_id);
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $total = getRecord("SELECT COUNT(*) AS c FROM tbl_employees WHERE record_status = 1 AND status = 'Active' $bs");
        $hrs = auragold_em_attendance_stale_hours();
        $present = getRecord("SELECT COUNT(DISTINCT employee_id) AS c FROM tbl_employee_attendance WHERE (
                (attendance_date = '$today' AND status = 'Present' AND punch_out_at IS NOT NULL)
                OR (punch_in_at IS NOT NULL AND punch_out_at IS NULL AND punch_in_at > DATE_SUB(NOW(), INTERVAL $hrs HOUR))
            ) $bs");
        $onLeave = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_leave WHERE record_status = 1 AND status = 'Approved' AND from_date <= '$today' AND to_date >= '$today' $bs");
        $pendingLeave = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_leave WHERE record_status = 1 AND status = 'Pending' $bs");
        $openTasks = getRecord("SELECT COUNT(*) AS c FROM tbl_employee_tasks WHERE record_status = 1 AND status IN ('Open','In Progress') $bs");
        $payrollMonth = getRecord("SELECT COALESCE(SUM(net_salary),0) AS s FROM tbl_employee_payroll WHERE record_status = 1 AND payroll_month = '" . date('Y-m') . "' $bs");

        return [
            'total_employees' => (int) ($total['c'] ?? 0),
            'present_today' => (int) ($present['c'] ?? 0),
            'on_leave_today' => (int) ($onLeave['c'] ?? 0),
            'pending_leave' => (int) ($pendingLeave['c'] ?? 0),
            'open_tasks' => (int) ($openTasks['c'] ?? 0),
            'payroll_month_total' => (float) ($payrollMonth['s'] ?? 0),
            'month_attendance' => (int) (getRecord("SELECT COUNT(*) AS c FROM tbl_employee_attendance WHERE attendance_date >= '$monthStart' $bs")['c'] ?? 0),
        ];
    }
}

if (!function_exists('auragold_em_save_employee')) {
    function auragold_em_save_employee($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $code = trim((string) ($data['employee_code'] ?? ''));
        if ($code === '') {
            $code = auragold_em_next_employee_code($conn, $branch_id);
        }
        $fields = [
            'employee_code' => $code,
            'first_name' => trim((string) ($data['first_name'] ?? '')),
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'phone' => trim((string) ($data['phone'] ?? '')),
            'department_id' => (int) ($data['department_id'] ?? 0),
            'designation_id' => (int) ($data['designation_id'] ?? 0),
            'shift_id' => (int) ($data['shift_id'] ?? 0),
            'joining_date' => trim((string) ($data['joining_date'] ?? '')),
            'basic_salary' => (float) ($data['basic_salary'] ?? 0),
            'address' => trim((string) ($data['address'] ?? '')),
            'notes' => trim((string) ($data['notes'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'Active')) ?: 'Active',
        ];
        if ($fields['first_name'] === '') {
            return ['ok' => false, 'message' => 'First name is required.'];
        }
        if ($fields['joining_date'] === '') {
            $fields['joining_date'] = null;
        }

        $setParts = [];
        foreach ($fields as $k => $v) {
            if ($k === 'joining_date' && $v === null) {
                $setParts[] = "`joining_date` = NULL";
            } else {
                $setParts[] = '`' . $k . "` = '" . auragold_em_esc($conn, $v) . "'";
            }
        }

        if ($id > 0) {
            $sql = 'UPDATE tbl_employees SET ' . implode(', ', $setParts) . " WHERE id = $id AND branch_id = " . (int) $branch_id . ' LIMIT 1';
        } else {
            $sql = 'INSERT INTO tbl_employees SET branch_id = ' . (int) $branch_id . ', ' . implode(', ', $setParts) . ', record_status = 1';
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save employee.'];
        }
        return ['ok' => true, 'message' => 'Employee saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_delete_employee')) {
    function auragold_em_delete_employee($conn, int $id, int $branch_id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid employee.'];
        }
        $ok = @mysqli_query($conn, 'UPDATE tbl_employees SET record_status = 0 WHERE id = ' . $id . ' AND branch_id = ' . (int) $branch_id . ' LIMIT 1');
        return ['ok' => (bool) $ok, 'message' => $ok ? 'Employee removed.' : 'Could not remove employee.'];
    }
}

if (!function_exists('auragold_em_save_master_row')) {
    function auragold_em_save_master_row($conn, string $table, int $branch_id, array $data): array
    {
        $map = [
            'department' => 'tbl_employee_departments',
            'designation' => 'tbl_employee_designations',
            'shift' => 'tbl_employee_shifts',
            'leave_type' => 'tbl_employee_leave_types',
        ];
        $key = trim((string) ($data['master_type'] ?? ''));
        if (!isset($map[$key])) {
            return ['ok' => false, 'message' => 'Invalid master type.'];
        }
        $tbl = $map[$key];
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return ['ok' => false, 'message' => 'Name is required.'];
        }
        $nameEsc = auragold_em_esc($conn, $name);
        if ($key === 'shift') {
            $st = trim((string) ($data['start_time'] ?? ''));
            $et = trim((string) ($data['end_time'] ?? ''));
            $stSql = $st !== '' ? "'" . auragold_em_esc($conn, $st) . "'" : 'NULL';
            $etSql = $et !== '' ? "'" . auragold_em_esc($conn, $et) . "'" : 'NULL';
            if ($id > 0) {
                $sql = "UPDATE $tbl SET name = '$nameEsc', start_time = $stSql, end_time = $etSql WHERE id = $id AND branch_id = " . (int) $branch_id;
            } else {
                $sql = "INSERT INTO $tbl (branch_id, name, start_time, end_time, status) VALUES (" . (int) $branch_id . ", '$nameEsc', $stSql, $etSql, 1)";
            }
        } elseif ($key === 'leave_type') {
            $days = (float) ($data['days_per_year'] ?? 0);
            if ($id > 0) {
                $sql = "UPDATE $tbl SET name = '$nameEsc', days_per_year = $days WHERE id = $id AND branch_id = " . (int) $branch_id;
            } else {
                $sql = "INSERT INTO $tbl (branch_id, name, days_per_year, status) VALUES (" . (int) $branch_id . ", '$nameEsc', $days, 1)";
            }
        } else {
            if ($id > 0) {
                $sql = "UPDATE $tbl SET name = '$nameEsc' WHERE id = $id AND branch_id = " . (int) $branch_id;
            } else {
                $sql = "INSERT INTO $tbl (branch_id, name, status) VALUES (" . (int) $branch_id . ", '$nameEsc', 1)";
            }
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save.'];
        }
        return ['ok' => true, 'message' => 'Saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_delete_master_row')) {
    function auragold_em_delete_master_row($conn, string $tableKey, int $id, int $branch_id): array
    {
        $map = [
            'department' => 'tbl_employee_departments',
            'designation' => 'tbl_employee_designations',
            'shift' => 'tbl_employee_shifts',
            'leave_type' => 'tbl_employee_leave_types',
        ];
        if (!isset($map[$tableKey]) || $id <= 0) {
            return ['ok' => false, 'message' => 'Invalid request.'];
        }
        $tbl = $map[$tableKey];
        $ok = @mysqli_query($conn, "UPDATE $tbl SET status = 0 WHERE id = $id AND branch_id = " . (int) $branch_id . ' LIMIT 1');
        return ['ok' => (bool) $ok, 'message' => $ok ? 'Removed.' : 'Could not remove.'];
    }
}

if (!function_exists('auragold_em_save_document')) {
    function auragold_em_save_document($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $employee_id = (int) ($data['employee_id'] ?? 0);
        if ($employee_id <= 0) {
            return ['ok' => false, 'message' => 'Select an employee.'];
        }
        $doc_type = trim((string) ($data['doc_type'] ?? ''));
        $doc_title = trim((string) ($data['doc_title'] ?? ''));
        if ($doc_title === '') {
            return ['ok' => false, 'message' => 'Document title is required.'];
        }
        $expiry = trim((string) ($data['expiry_date'] ?? ''));
        $expirySql = ($expiry !== '') ? "'" . auragold_em_esc($conn, $expiry) . "'" : 'NULL';
        $fields = [
            'employee_id' => $employee_id,
            'doc_type' => auragold_em_esc($conn, $doc_type),
            'doc_title' => auragold_em_esc($conn, $doc_title),
            'file_name' => auragold_em_esc($conn, (string) ($data['file_name'] ?? '')),
            'file_path' => auragold_em_esc($conn, (string) ($data['file_path'] ?? '')),
            'notes' => auragold_em_esc($conn, (string) ($data['notes'] ?? '')),
        ];
        if ($id > 0) {
            $sql = "UPDATE tbl_employee_documents SET employee_id = {$fields['employee_id']}, doc_type = '{$fields['doc_type']}', doc_title = '{$fields['doc_title']}', file_name = '{$fields['file_name']}', file_path = '{$fields['file_path']}', expiry_date = $expirySql, notes = '{$fields['notes']}' WHERE id = $id AND branch_id = " . (int) $branch_id;
        } else {
            $sql = "INSERT INTO tbl_employee_documents (branch_id, employee_id, doc_type, doc_title, file_name, file_path, expiry_date, notes, record_status) VALUES (" . (int) $branch_id . ", {$fields['employee_id']}, '{$fields['doc_type']}', '{$fields['doc_title']}', '{$fields['file_name']}', '{$fields['file_path']}', $expirySql, '{$fields['notes']}', 1)";
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save document.'];
        }
        return ['ok' => true, 'message' => 'Document saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_get_documents')) {
    function auragold_em_get_documents($conn, int $branch_id, int $employee_id = 0): array
    {
        auragold_em_ensure_tables($conn);
        $sql = "SELECT d.*, e.first_name, e.last_name, e.employee_code
                FROM tbl_employee_documents d
                LEFT JOIN tbl_employees e ON e.id = d.employee_id
                WHERE d.record_status = 1" . auragold_em_branch_sql($branch_id, 'd');
        if ($employee_id > 0) {
            $sql .= ' AND d.employee_id = ' . (int) $employee_id;
        }
        $sql .= ' ORDER BY d.id DESC';
        $rows = getList($sql);
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_delete_row')) {
    function auragold_em_delete_row($conn, string $table, int $id, int $branch_id): array
    {
        $allowed = [
            'documents' => 'tbl_employee_documents',
            'leave' => 'tbl_employee_leave',
            'payroll' => 'tbl_employee_payroll',
            'tasks' => 'tbl_employee_tasks',
            'performance' => 'tbl_employee_performance',
        ];
        if (!isset($allowed[$table]) || $id <= 0) {
            return ['ok' => false, 'message' => 'Invalid request.'];
        }
        $tbl = $allowed[$table];
        $ok = @mysqli_query($conn, "UPDATE $tbl SET record_status = 0 WHERE id = $id AND branch_id = " . (int) $branch_id . ' LIMIT 1');
        return ['ok' => (bool) $ok, 'message' => $ok ? 'Removed.' : 'Could not remove.'];
    }
}

if (!function_exists('auragold_em_save_attendance')) {
    function auragold_em_save_attendance($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $employee_id = (int) ($data['employee_id'] ?? 0);
        $date = trim((string) ($data['attendance_date'] ?? date('Y-m-d')));
        if ($employee_id <= 0 || $date === '') {
            return ['ok' => false, 'message' => 'Employee and date are required.'];
        }
        $status = trim((string) ($data['status'] ?? 'Present')) ?: 'Present';
        $check_in = trim((string) ($data['check_in'] ?? ''));
        $check_out = trim((string) ($data['check_out'] ?? ''));
        $notes = auragold_em_esc($conn, (string) ($data['notes'] ?? ''));
        $ci = $check_in !== '' ? "'" . auragold_em_esc($conn, $check_in) . "'" : 'NULL';
        $co = $check_out !== '' ? "'" . auragold_em_esc($conn, $check_out) . "'" : 'NULL';
        $existing = getRecord("SELECT id FROM tbl_employee_attendance WHERE employee_id = $employee_id AND attendance_date = '" . auragold_em_esc($conn, $date) . "' LIMIT 1");
        if ($existing) {
            $id = (int) $existing['id'];
            $sql = "UPDATE tbl_employee_attendance SET status = '" . auragold_em_esc($conn, $status) . "', check_in = $ci, check_out = $co, notes = '$notes' WHERE id = $id AND branch_id = " . (int) $branch_id;
        } else {
            $sql = "INSERT INTO tbl_employee_attendance (branch_id, employee_id, attendance_date, status, check_in, check_out, notes) VALUES (" . (int) $branch_id . ", $employee_id, '" . auragold_em_esc($conn, $date) . "', '" . auragold_em_esc($conn, $status) . "', $ci, $co, '$notes')";
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save attendance.'];
        }
        return ['ok' => true, 'message' => 'Attendance saved.'];
    }
}

if (!function_exists('auragold_em_get_attendance')) {
    function auragold_em_get_attendance($conn, int $branch_id, string $date = '', int $employee_id = 0): array
    {
        auragold_em_ensure_tables($conn);
        if ($date === '') {
            $date = date('Y-m-d');
        }
        $sql = "SELECT a.*, e.first_name, e.last_name, e.employee_code
                FROM tbl_employee_attendance a
                LEFT JOIN tbl_employees e ON e.id = a.employee_id
                WHERE (
                    a.attendance_date = '" . auragold_em_esc($conn, $date) . "'
                    OR DATE(a.punch_in_at) = '" . auragold_em_esc($conn, $date) . "'
                    OR DATE(a.punch_out_at) = '" . auragold_em_esc($conn, $date) . "'
                )" . auragold_em_branch_sql($branch_id, 'a');
        if ($employee_id > 0) {
            $sql .= ' AND a.employee_id = ' . (int) $employee_id;
        }
        $sql .= ' ORDER BY e.first_name ASC';
        $rows = getList($sql);
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_save_leave')) {
    function auragold_em_save_leave($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $employee_id = (int) ($data['employee_id'] ?? 0);
        $leave_type_id = (int) ($data['leave_type_id'] ?? 0);
        $from = trim((string) ($data['from_date'] ?? ''));
        $to = trim((string) ($data['to_date'] ?? ''));
        if ($employee_id <= 0 || $from === '' || $to === '') {
            return ['ok' => false, 'message' => 'Employee and leave dates are required.'];
        }
        $days = (float) ($data['days'] ?? 0);
        if ($days <= 0) {
            $days = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
        }
        $reason = auragold_em_esc($conn, (string) ($data['reason'] ?? ''));
        $status = trim((string) ($data['status'] ?? 'Pending')) ?: 'Pending';
        if ($id > 0) {
            $sql = "UPDATE tbl_employee_leave SET employee_id = $employee_id, leave_type_id = $leave_type_id, from_date = '" . auragold_em_esc($conn, $from) . "', to_date = '" . auragold_em_esc($conn, $to) . "', days = $days, reason = '$reason', status = '" . auragold_em_esc($conn, $status) . "' WHERE id = $id AND branch_id = " . (int) $branch_id;
        } else {
            $sql = "INSERT INTO tbl_employee_leave (branch_id, employee_id, leave_type_id, from_date, to_date, days, reason, status, record_status) VALUES (" . (int) $branch_id . ", $employee_id, $leave_type_id, '" . auragold_em_esc($conn, $from) . "', '" . auragold_em_esc($conn, $to) . "', $days, '$reason', '$status', 1)";
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save leave request.'];
        }
        return ['ok' => true, 'message' => 'Leave request saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_update_leave_status')) {
    function auragold_em_update_leave_status($conn, int $id, int $branch_id, string $status, string $approved_by = ''): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'Invalid leave request.'];
        }
        $st = auragold_em_esc($conn, $status);
        $by = auragold_em_esc($conn, $approved_by);
        $sql = "UPDATE tbl_employee_leave SET status = '$st', approved_by = '$by', approved_at = NOW() WHERE id = $id AND branch_id = " . (int) $branch_id . ' LIMIT 1';
        $ok = @mysqli_query($conn, $sql);
        return ['ok' => (bool) $ok, 'message' => $ok ? 'Leave status updated.' : 'Could not update leave.'];
    }
}

if (!function_exists('auragold_em_get_leave_requests')) {
    function auragold_em_get_leave_requests($conn, int $branch_id, string $status = ''): array
    {
        auragold_em_ensure_tables($conn);
        $sql = "SELECT l.*, e.first_name, e.last_name, e.employee_code, t.name AS leave_type_name
                FROM tbl_employee_leave l
                LEFT JOIN tbl_employees e ON e.id = l.employee_id
                LEFT JOIN tbl_employee_leave_types t ON t.id = l.leave_type_id
                WHERE l.record_status = 1" . auragold_em_branch_sql($branch_id, 'l');
        if ($status !== '') {
            $sql .= " AND l.status = '" . auragold_em_esc($conn, $status) . "'";
        }
        $sql .= ' ORDER BY l.id DESC';
        $rows = getList($sql);
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_save_payroll')) {
    function auragold_em_save_payroll($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $employee_id = (int) ($data['employee_id'] ?? 0);
        $month = trim((string) ($data['payroll_month'] ?? date('Y-m')));
        if ($employee_id <= 0 || $month === '') {
            return ['ok' => false, 'message' => 'Employee and payroll month are required.'];
        }
        $basic = (float) ($data['basic_salary'] ?? 0);
        $allow = (float) ($data['allowances'] ?? 0);
        $ded = (float) ($data['deductions'] ?? 0);
        $net = (float) ($data['net_salary'] ?? ($basic + $allow - $ded));
        $payDate = trim((string) ($data['payment_date'] ?? ''));
        $payDateSql = $payDate !== '' ? "'" . auragold_em_esc($conn, $payDate) . "'" : 'NULL';
        $status = trim((string) ($data['status'] ?? 'Draft')) ?: 'Draft';
        $notes = auragold_em_esc($conn, (string) ($data['notes'] ?? ''));
        if ($id > 0) {
            $sql = "UPDATE tbl_employee_payroll SET employee_id = $employee_id, payroll_month = '" . auragold_em_esc($conn, $month) . "', basic_salary = $basic, allowances = $allow, deductions = $ded, net_salary = $net, payment_date = $payDateSql, status = '" . auragold_em_esc($conn, $status) . "', notes = '$notes' WHERE id = $id AND branch_id = " . (int) $branch_id;
        } else {
            $sql = "INSERT INTO tbl_employee_payroll (branch_id, employee_id, payroll_month, basic_salary, allowances, deductions, net_salary, payment_date, status, notes, record_status) VALUES (" . (int) $branch_id . ", $employee_id, '" . auragold_em_esc($conn, $month) . "', $basic, $allow, $ded, $net, $payDateSql, '" . auragold_em_esc($conn, $status) . "', '$notes', 1)";
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save payroll.'];
        }
        return ['ok' => true, 'message' => 'Payroll saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_get_payroll')) {
    function auragold_em_get_payroll($conn, int $branch_id, string $month = ''): array
    {
        auragold_em_ensure_tables($conn);
        $sql = "SELECT p.*, e.first_name, e.last_name, e.employee_code
                FROM tbl_employee_payroll p
                LEFT JOIN tbl_employees e ON e.id = p.employee_id
                WHERE p.record_status = 1" . auragold_em_branch_sql($branch_id, 'p');
        if ($month !== '') {
            $sql .= " AND p.payroll_month = '" . auragold_em_esc($conn, $month) . "'";
        }
        $sql .= ' ORDER BY p.payroll_month DESC, e.first_name ASC';
        $rows = getList($sql);
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_save_task')) {
    function auragold_em_save_task($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $employee_id = (int) ($data['employee_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($employee_id <= 0 || $title === '') {
            return ['ok' => false, 'message' => 'Employee and task title are required.'];
        }
        $description = auragold_em_esc($conn, (string) ($data['description'] ?? ''));
        $priority = trim((string) ($data['priority'] ?? 'Medium')) ?: 'Medium';
        $status = trim((string) ($data['status'] ?? 'Open')) ?: 'Open';
        $due = trim((string) ($data['due_date'] ?? ''));
        $dueSql = $due !== '' ? "'" . auragold_em_esc($conn, $due) . "'" : 'NULL';
        $completedSql = ($status === 'Completed') ? 'NOW()' : 'NULL';
        if ($id > 0) {
            $sql = "UPDATE tbl_employee_tasks SET employee_id = $employee_id, title = '" . auragold_em_esc($conn, $title) . "', description = '$description', priority = '" . auragold_em_esc($conn, $priority) . "', status = '" . auragold_em_esc($conn, $status) . "', due_date = $dueSql, completed_at = $completedSql WHERE id = $id AND branch_id = " . (int) $branch_id;
        } else {
            $sql = "INSERT INTO tbl_employee_tasks (branch_id, employee_id, title, description, priority, status, due_date, record_status) VALUES (" . (int) $branch_id . ", $employee_id, '" . auragold_em_esc($conn, $title) . "', '$description', '" . auragold_em_esc($conn, $priority) . "', '" . auragold_em_esc($conn, $status) . "', $dueSql, 1)";
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save task.'];
        }
        return ['ok' => true, 'message' => 'Task saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_get_tasks')) {
    function auragold_em_get_tasks($conn, int $branch_id, string $status = ''): array
    {
        auragold_em_ensure_tables($conn);
        $sql = "SELECT t.*, e.first_name, e.last_name, e.employee_code
                FROM tbl_employee_tasks t
                LEFT JOIN tbl_employees e ON e.id = t.employee_id
                WHERE t.record_status = 1" . auragold_em_branch_sql($branch_id, 't');
        if ($status !== '') {
            $sql .= " AND t.status = '" . auragold_em_esc($conn, $status) . "'";
        }
        $sql .= ' ORDER BY t.due_date ASC, t.id DESC';
        $rows = getList($sql);
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_save_performance')) {
    function auragold_em_save_performance($conn, int $branch_id, array $data): array
    {
        auragold_em_ensure_tables($conn);
        $id = (int) ($data['id'] ?? 0);
        $employee_id = (int) ($data['employee_id'] ?? 0);
        if ($employee_id <= 0) {
            return ['ok' => false, 'message' => 'Select an employee.'];
        }
        $period = auragold_em_esc($conn, (string) ($data['review_period'] ?? ''));
        $reviewDate = trim((string) ($data['review_date'] ?? ''));
        $reviewDateSql = $reviewDate !== '' ? "'" . auragold_em_esc($conn, $reviewDate) . "'" : 'NULL';
        $rating = (float) ($data['rating'] ?? 0);
        $kpi = (float) ($data['kpi_score'] ?? 0);
        $strengths = auragold_em_esc($conn, (string) ($data['strengths'] ?? ''));
        $improvements = auragold_em_esc($conn, (string) ($data['improvements'] ?? ''));
        $reviewer = auragold_em_esc($conn, (string) ($data['reviewer_name'] ?? ''));
        $status = trim((string) ($data['status'] ?? 'Draft')) ?: 'Draft';
        if ($id > 0) {
            $sql = "UPDATE tbl_employee_performance SET employee_id = $employee_id, review_period = '$period', review_date = $reviewDateSql, rating = $rating, kpi_score = $kpi, strengths = '$strengths', improvements = '$improvements', reviewer_name = '$reviewer', status = '" . auragold_em_esc($conn, $status) . "' WHERE id = $id AND branch_id = " . (int) $branch_id;
        } else {
            $sql = "INSERT INTO tbl_employee_performance (branch_id, employee_id, review_period, review_date, rating, kpi_score, strengths, improvements, reviewer_name, status, record_status) VALUES (" . (int) $branch_id . ", $employee_id, '$period', $reviewDateSql, $rating, $kpi, '$strengths', '$improvements', '$reviewer', '" . auragold_em_esc($conn, $status) . "', 1)";
        }
        if (!@mysqli_query($conn, $sql)) {
            return ['ok' => false, 'message' => 'Could not save performance review.'];
        }
        return ['ok' => true, 'message' => 'Performance review saved.', 'id' => $id > 0 ? $id : (int) mysqli_insert_id($conn)];
    }
}

if (!function_exists('auragold_em_get_performance')) {
    function auragold_em_get_performance($conn, int $branch_id): array
    {
        auragold_em_ensure_tables($conn);
        $rows = getList("SELECT p.*, e.first_name, e.last_name, e.employee_code
            FROM tbl_employee_performance p
            LEFT JOIN tbl_employees e ON e.id = p.employee_id
            WHERE p.record_status = 1" . auragold_em_branch_sql($branch_id) . ' ORDER BY p.review_date DESC, p.id DESC');
        return is_array($rows) ? $rows : [];
    }
}

if (!function_exists('auragold_em_get_reports')) {
    function auragold_em_get_reports($conn, int $branch_id, string $from = '', string $to = ''): array
    {
        auragold_em_ensure_tables($conn);
        if ($from === '') {
            $from = date('Y-m-01');
        }
        if ($to === '') {
            $to = date('Y-m-d');
        }
        $bs = auragold_em_branch_sql($branch_id);
        $fromEsc = auragold_em_esc($conn, $from);
        $toEsc = auragold_em_esc($conn, $to);

        $attendanceSummary = getList("SELECT status, COUNT(*) AS c FROM tbl_employee_attendance WHERE attendance_date BETWEEN '$fromEsc' AND '$toEsc' $bs GROUP BY status ORDER BY status");
        $leaveSummary = getList("SELECT status, COUNT(*) AS c FROM tbl_employee_leave WHERE record_status = 1 AND from_date <= '$toEsc' AND to_date >= '$fromEsc' $bs GROUP BY status ORDER BY status");
        $payrollSummary = getRecord("SELECT COUNT(*) AS c, COALESCE(SUM(net_salary),0) AS total FROM tbl_employee_payroll WHERE record_status = 1 AND payroll_month >= '" . substr($fromEsc, 0, 7) . "' AND payroll_month <= '" . substr($toEsc, 0, 7) . "' $bs");
        $taskSummary = getList("SELECT status, COUNT(*) AS c FROM tbl_employee_tasks WHERE record_status = 1 $bs GROUP BY status ORDER BY status");
        $perfAvg = getRecord("SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS c FROM tbl_employee_performance WHERE record_status = 1 AND review_date BETWEEN '$fromEsc' AND '$toEsc' $bs");

        return [
            'from' => $from,
            'to' => $to,
            'attendance_summary' => is_array($attendanceSummary) ? $attendanceSummary : [],
            'leave_summary' => is_array($leaveSummary) ? $leaveSummary : [],
            'payroll_count' => (int) ($payrollSummary['c'] ?? 0),
            'payroll_total' => (float) ($payrollSummary['total'] ?? 0),
            'task_summary' => is_array($taskSummary) ? $taskSummary : [],
            'avg_rating' => (float) ($perfAvg['avg_rating'] ?? 0),
            'performance_reviews' => (int) ($perfAvg['c'] ?? 0),
        ];
    }
}

if (!function_exists('auragold_em_bootstrap_page')) {
    function auragold_em_bootstrap_page($conn): array
    {
        if (function_exists('auragold_ensure_branch_id_on_settings_tables')) {
            auragold_ensure_branch_id_on_settings_tables($conn);
        }
        auragold_em_ensure_tables($conn);
        $branch_id = auragold_em_resolve_branch_id();
        auragold_em_seed_defaults($conn, $branch_id);
        auragold_em_sync_users_to_employees($conn, $branch_id);
        return [
            'branch_id' => $branch_id,
            'employees' => auragold_em_get_employees($conn, $branch_id, 'Active'),
            'departments' => auragold_em_get_master_list($conn, 'tbl_employee_departments', $branch_id),
            'designations' => auragold_em_get_master_list($conn, 'tbl_employee_designations', $branch_id),
            'shifts' => auragold_em_get_master_list($conn, 'tbl_employee_shifts', $branch_id),
            'leave_types' => auragold_em_get_master_list($conn, 'tbl_employee_leave_types', $branch_id),
            'next_employee_code' => auragold_em_next_employee_code($conn, $branch_id),
        ];
    }
}
