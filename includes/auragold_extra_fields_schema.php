<?php

/**
 * Extra / user-defined fields per metal type (branch-scoped when branch_id column exists).
 */

if (!function_exists('auragold_extra_field_metals')) {
    function auragold_extra_field_metals(): array
    {
        if (function_exists('getVoucherSettingMetals')) {
            return getVoucherSettingMetals();
        }

        return ['Gold', 'Silver', 'Platinum', 'Diamond & Stones', 'Imitation Or Watches', 'Other Or Services'];
    }
}

if (!function_exists('auragold_resolve_extra_fields_branch_id')) {
    function auragold_resolve_extra_fields_branch_id(?int $explicit = null): int
    {
        if ($explicit !== null && $explicit > 0) {
            if (!function_exists('auragold_settings_branch_id_valid') || auragold_settings_branch_id_valid($explicit)) {
                return $explicit;
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings_branch_id'])) {
            $p = (int) $_POST['settings_branch_id'];
            if ($p > 0 && (!function_exists('auragold_settings_branch_id_valid') || auragold_settings_branch_id_valid($p))) {
                return $p;
            }
        }
        if (isset($_GET['branch_id'])) {
            $g = (int) $_GET['branch_id'];
            if ($g > 0 && (!function_exists('auragold_settings_branch_id_valid') || auragold_settings_branch_id_valid($g))) {
                return $g;
            }
        }

        return function_exists('auragold_settings_branch_id') ? (int) auragold_settings_branch_id() : 0;
    }
}

if (!function_exists('auragold_ensure_tbl_extra_fields')) {
    function auragold_ensure_tbl_extra_fields($conn): bool
    {
        if (!$conn instanceof mysqli) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `tbl_extra_fields` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `branch_id` int DEFAULT NULL COMMENT 'FK tbl_branches.id',
            `metal_type` varchar(64) NOT NULL DEFAULT 'Gold',
            `display_name` varchar(255) NOT NULL DEFAULT '',
            `field_type` varchar(16) NOT NULL DEFAULT 'text' COMMENT 'text or dropdown',
            `dropdown_options_json` text COMMENT 'JSON array of option strings',
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `sort_order` int NOT NULL DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_extra_fields_branch_metal` (`branch_id`, `metal_type`),
            KEY `idx_extra_fields_metal_status` (`metal_type`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return (bool) @mysqli_query($conn, $sql);
    }
}

if (!function_exists('auragold_extra_field_normalize_type')) {
    function auragold_extra_field_normalize_type(string $type): string
    {
        $type = strtolower(trim($type));

        return $type === 'dropdown' ? 'dropdown' : 'text';
    }
}

if (!function_exists('auragold_extra_field_normalize_metal')) {
    function auragold_extra_field_normalize_metal(string $metal): string
    {
        $metal = trim($metal);
        $allowed = auragold_extra_field_metals();
        if (in_array($metal, $allowed, true)) {
            return $metal;
        }

        return 'Gold';
    }
}

if (!function_exists('auragold_extra_field_parse_options')) {
    /**
     * @param mixed $raw JSON string or array
     * @return string[]
     */
    function auragold_extra_field_parse_options($raw): array
    {
        if (is_array($raw)) {
            $list = $raw;
        } else {
            $json = trim((string) $raw);
            if ($json === '') {
                return [];
            }
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return [];
            }
            $list = $decoded;
        }

        $out = [];
        foreach ($list as $item) {
            $v = trim((string) $item);
            if ($v !== '' && !in_array($v, $out, true)) {
                $out[] = $v;
            }
        }

        return $out;
    }
}

if (!function_exists('auragold_extra_field_row_from_db')) {
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    function auragold_extra_field_row_from_db(array $row): array
    {
        $options = auragold_extra_field_parse_options($row['dropdown_options_json'] ?? '');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'branch_id' => isset($row['branch_id']) ? (int) $row['branch_id'] : null,
            'metal_type' => auragold_extra_field_normalize_metal((string) ($row['metal_type'] ?? 'Gold')),
            'display_name' => trim((string) ($row['display_name'] ?? '')),
            'field_type' => auragold_extra_field_normalize_type((string) ($row['field_type'] ?? 'text')),
            'dropdown_options' => $options,
            'dropdown_options_json' => $options !== [] ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
            'status' => (int) ($row['status'] ?? 1) === 1 ? 1 : 0,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
        ];
    }
}

if (!function_exists('auragold_get_extra_fields')) {
    /**
     * @return array<int, array<string, mixed>>
     */
    function auragold_get_extra_fields($conn, int $branch_id, ?string $metal_type = null): array
    {
        if (!$conn instanceof mysqli) {
            return [];
        }
        auragold_ensure_tbl_extra_fields($conn);

        $where = ['1=1'];
        if (auragold_tbl_has_column($conn, 'tbl_extra_fields', 'branch_id')) {
            if ($branch_id > 0) {
                $where[] = '(branch_id = ' . (int) $branch_id . ' OR branch_id IS NULL)';
            }
        }
        if ($metal_type !== null && trim($metal_type) !== '') {
            $metal_esc = mysqli_real_escape_string($conn, auragold_extra_field_normalize_metal($metal_type));
            $where[] = "metal_type = '$metal_esc'";
        }

        $sql = 'SELECT * FROM `tbl_extra_fields` WHERE ' . implode(' AND ', $where)
            . ' ORDER BY sort_order ASC, id ASC';
        $rows = getList($sql);
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = auragold_extra_field_row_from_db($row);
        }

        return $out;
    }
}

if (!function_exists('auragold_get_extra_field_by_id')) {
    function auragold_get_extra_field_by_id($conn, int $id, int $branch_id = 0): ?array
    {
        if (!$conn instanceof mysqli || $id <= 0) {
            return null;
        }
        auragold_ensure_tbl_extra_fields($conn);

        $sql = 'SELECT * FROM `tbl_extra_fields` WHERE id = ' . (int) $id . ' LIMIT 1';
        $row = getRecord($sql);
        if (!$row || !is_array($row)) {
            return null;
        }
        if (auragold_tbl_has_column($conn, 'tbl_extra_fields', 'branch_id') && $branch_id > 0) {
            $bid = isset($row['branch_id']) ? (int) $row['branch_id'] : 0;
            if ($bid > 0 && $bid !== $branch_id) {
                return null;
            }
        }

        return auragold_extra_field_row_from_db($row);
    }
}

if (!function_exists('auragold_save_extra_field')) {
    /**
     * @param array<string, mixed> $data
     * @return array{ok:bool,message:string,id?:int,field?:array<string,mixed>}
     */
    function auragold_save_extra_field($conn, int $branch_id, array $data): array
    {
        if (!$conn instanceof mysqli) {
            return ['ok' => false, 'message' => 'Database unavailable.'];
        }
        auragold_ensure_tbl_extra_fields($conn);

        $id = isset($data['id']) ? (int) $data['id'] : 0;
        $metal = auragold_extra_field_normalize_metal((string) ($data['metal_type'] ?? 'Gold'));
        $display = trim((string) ($data['display_name'] ?? ''));
        $field_type = auragold_extra_field_normalize_type((string) ($data['field_type'] ?? 'text'));
        $status = !empty($data['status']) ? 1 : 0;
        $options = auragold_extra_field_parse_options($data['dropdown_options'] ?? []);

        if ($display === '') {
            return ['ok' => false, 'message' => 'Display name is required.'];
        }
        if ($field_type === 'dropdown' && $options === []) {
            return ['ok' => false, 'message' => 'Add at least one dropdown option.'];
        }

        $metal_esc = mysqli_real_escape_string($conn, $metal);
        $display_esc = mysqli_real_escape_string($conn, $display);
        $type_esc = mysqli_real_escape_string($conn, $field_type);
        $options_json = $field_type === 'dropdown' && $options !== []
            ? mysqli_real_escape_string($conn, json_encode($options, JSON_UNESCAPED_UNICODE))
            : null;

        $has_branch = auragold_tbl_has_column($conn, 'tbl_extra_fields', 'branch_id');
        if ($has_branch && $branch_id <= 0) {
            return ['ok' => false, 'message' => 'Branch not resolved. Refresh the page and try again.'];
        }

        if ($id > 0) {
            $existing = auragold_get_extra_field_by_id($conn, $id, $branch_id);
            if ($existing === null) {
                return ['ok' => false, 'message' => 'Extra field not found.'];
            }
            $set = [
                "metal_type = '$metal_esc'",
                "display_name = '$display_esc'",
                "field_type = '$type_esc'",
                'status = ' . (int) $status,
            ];
            if ($options_json !== null) {
                $set[] = "dropdown_options_json = '$options_json'";
            } else {
                $set[] = 'dropdown_options_json = NULL';
            }
            $sql = 'UPDATE `tbl_extra_fields` SET ' . implode(', ', $set) . ' WHERE id = ' . (int) $id . ' LIMIT 1';
            if (!@mysqli_query($conn, $sql)) {
                return ['ok' => false, 'message' => 'Could not update: ' . mysqli_error($conn)];
            }
            $saved_id = $id;
        } else {
            $cols = ['metal_type', 'display_name', 'field_type', 'dropdown_options_json', 'status'];
            $vals = ["'$metal_esc'", "'$display_esc'", "'$type_esc'", $options_json !== null ? "'$options_json'" : 'NULL', (int) $status];
            if ($has_branch) {
                array_unshift($cols, 'branch_id');
                array_unshift($vals, (string) (int) $branch_id);
            }
            $sql = 'INSERT INTO `tbl_extra_fields` (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', $vals) . ')';
            if (!@mysqli_query($conn, $sql)) {
                return ['ok' => false, 'message' => 'Could not save: ' . mysqli_error($conn)];
            }
            $saved_id = (int) mysqli_insert_id($conn);
        }

        $field = auragold_get_extra_field_by_id($conn, $saved_id, $branch_id);

        return [
            'ok' => true,
            'message' => $id > 0 ? 'Extra field updated.' : 'Extra field saved.',
            'id' => $saved_id,
            'field' => $field,
        ];
    }
}

if (!function_exists('auragold_get_active_extra_fields_by_metal_map')) {
    /**
     * Active extra fields keyed by metal_type (Gold, Silver, …) for product modal columns.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    function auragold_get_active_extra_fields_by_metal_map($conn, int $branch_id): array
    {
        $map = [];
        foreach (auragold_extra_field_metals() as $metal) {
            $rows = auragold_get_extra_fields($conn, $branch_id, $metal);
            $active = [];
            foreach ($rows as $row) {
                if ((int) ($row['status'] ?? 0) === 1) {
                    $active[] = $row;
                }
            }
            if ($active !== []) {
                $map[$metal] = $active;
            }
        }

        return $map;
    }
}

if (!function_exists('auragold_delete_extra_field')) {
    function auragold_delete_extra_field($conn, int $id, int $branch_id = 0): array
    {
        if (!$conn instanceof mysqli || $id <= 0) {
            return ['ok' => false, 'message' => 'Invalid field.'];
        }
        auragold_ensure_tbl_extra_fields($conn);

        $existing = auragold_get_extra_field_by_id($conn, $id, $branch_id);
        if ($existing === null) {
            return ['ok' => false, 'message' => 'Extra field not found.'];
        }

        if (!@mysqli_query($conn, 'DELETE FROM `tbl_extra_fields` WHERE id = ' . (int) $id . ' LIMIT 1')) {
            return ['ok' => false, 'message' => 'Could not delete: ' . mysqli_error($conn)];
        }

        return ['ok' => true, 'message' => 'Extra field deleted.'];
    }
}
