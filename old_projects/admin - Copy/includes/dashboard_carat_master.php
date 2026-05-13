<?php
/**
 * Map tbl_metal.id ↔ dashboard slug (tbl_dashboard_metal_rates.metal).
 * IDs align with typical seed data: 1 Gold, 2 Silver, 3 Platinum, 4 Diamond & Stones.
 */

if (!function_exists('auragold_metal_id_to_dashboard_key')) {
    function auragold_metal_id_to_dashboard_key($metal_id): ?string
    {
        $id = (int) $metal_id;
        static $map = [
            1 => 'gold',
            2 => 'silver',
            3 => 'platinum',
            4 => 'diamond',
        ];
        return $map[$id] ?? null;
    }
}

if (!function_exists('auragold_dashboard_key_to_metal_id')) {
    function auragold_dashboard_key_to_metal_id(string $key): int
    {
        static $map = [
            'gold' => 1,
            'silver' => 2,
            'platinum' => 3,
            'diamond' => 4,
        ];
        return (int) ($map[strtolower(trim($key))] ?? 0);
    }
}

if (!function_exists('auragold_dashboard_carat_card_class')) {
    function auragold_dashboard_carat_card_class(string $label, int $idx): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($label)));
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'r' . $idx;
        }
        return 'cc-' . $slug;
    }
}

if (!function_exists('auragold_dashboard_carat_labels_for_save_validation')) {
    /**
     * Allowed carat/purity labels when saving dashboard rates: driven by Carat master for that metal, else built-in defaults.
     *
     * @return list<string>
     */
    function auragold_dashboard_carat_labels_for_save_validation($conn, string $metal_key): array
    {
        $defaults = [
            'gold' => ['24K', '22K', '21K', '18K', '14K', '10K'],
            'silver' => ['999', '958', '925', '875'],
            'platinum' => ['999', '950', '900'],
            'diamond' => ['0.30 ct', '0.50 ct', '0.70 ct', '1.00 ct', '1.50 ct', '2.00 ct'],
        ];
        $fallback = $defaults[strtolower($metal_key)] ?? [];
        if (!$conn instanceof mysqli || !function_exists('getList') || !function_exists('auragold_tbl_has_column') || !auragold_tbl_has_column($conn, 'tbl_carat', 'metal_id')) {
            return $fallback;
        }
        $mid = auragold_dashboard_key_to_metal_id($metal_key);
        if ($mid <= 0) {
            return $fallback;
        }
        $suffix = function_exists('auragold_master_list_sql_suffix') ? auragold_master_list_sql_suffix($conn, 'tbl_carat') : '';
        $list = @getList('SELECT name FROM tbl_carat WHERE status = 1 AND metal_id = ' . (int) $mid . ' ' . $suffix);
        if (!is_array($list) || $list === []) {
            return $fallback;
        }
        $out = [];
        foreach ($list as $r) {
            $n = trim((string) ($r['name'] ?? ''));
            if ($n !== '') {
                $out[] = $n;
            }
        }
        $out = array_values(array_unique($out));
        return $out !== [] ? $out : $fallback;
    }
}

if (!function_exists('auragold_dashboard_apply_carat_master_rows')) {
    /**
     * Replace dashboard rate row/card templates from tbl_carat (metal-wise) when metal_id is set.
     * Legacy rows with NULL metal_id are treated as Gold so existing installs keep working.
     *
     * @param mysqli $conn
     * @param array    $dashboard_metals  Passed by reference; same structure as dashboard.php defaults.
     */
    function auragold_dashboard_apply_carat_master_rows($conn, array &$dashboard_metals): void
    {
        if (!$conn || !function_exists('auragold_tbl_has_column') || !auragold_tbl_has_column($conn, 'tbl_carat', 'metal_id')) {
            return;
        }
        if (!function_exists('getList')) {
            return;
        }
        $suffix = '';
        if (function_exists('auragold_master_list_sql_suffix')) {
            $suffix = auragold_master_list_sql_suffix($conn, 'tbl_carat');
        }
        $sql = 'SELECT c.id, c.name, c.metal_id FROM tbl_carat c WHERE c.status = 1 ' . $suffix . ' ORDER BY c.metal_id ASC, c.id ASC';
        $list = @getList($sql);
        if (!is_array($list) || $list === []) {
            return;
        }

        $byKey = [];
        foreach ($list as $row) {
            $mid = isset($row['metal_id']) && $row['metal_id'] !== '' && $row['metal_id'] !== null
                ? (int) $row['metal_id']
                : 1;
            $key = auragold_metal_id_to_dashboard_key($mid);
            if ($key === null || !isset($dashboard_metals[$key])) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (!isset($byKey[$key])) {
                $byKey[$key] = [];
            }
            $byKey[$key][] = $name;
        }

        foreach ($byKey as $metalKey => $labels) {
            if (!isset($dashboard_metals[$metalKey])) {
                continue;
            }
            $labels = array_values(array_unique($labels));
            if ($labels === []) {
                continue;
            }
            $oldRows = isset($dashboard_metals[$metalKey]['rows']) && is_array($dashboard_metals[$metalKey]['rows'])
                ? $dashboard_metals[$metalKey]['rows']
                : [];
            $oldByCarat = [];
            foreach ($oldRows as $or) {
                $lab = trim((string) ($or['carat'] ?? ''));
                if ($lab !== '') {
                    $oldByCarat[$lab] = $or;
                }
            }
            $newRows = [];
            $i = 0;
            foreach ($labels as $lab) {
                $i++;
                if (isset($oldByCarat[$lab])) {
                    $newRows[] = $oldByCarat[$lab];
                } else {
                    $template = $oldRows[0] ?? null;
                    $nr = [
                        'carat' => $lab,
                        'new_rate' => isset($template['new_rate']) ? (string) $template['new_rate'] : '0.00',
                        'sell_premium' => '—',
                        'conv' => isset($template['conv']) ? (string) $template['conv'] : '1',
                        'current' => isset($template['current']) ? (string) $template['current'] : '0.00',
                    ];
                    $newRows[] = $nr;
                }
            }
            $dashboard_metals[$metalKey]['rows'] = $newRows;

            $cards = [];
            $ci = 0;
            foreach ($labels as $lab) {
                $ci++;
                $matchVal = '0.00';
                foreach ($newRows as $nr) {
                    if (trim((string) ($nr['carat'] ?? '')) === $lab) {
                        $matchVal = (string) ($nr['new_rate'] ?? $nr['current'] ?? '0.00');
                        break;
                    }
                }
                $cards[] = [
                    'label' => $lab,
                    'value' => $matchVal,
                    'class' => auragold_dashboard_carat_card_class($lab, $ci),
                ];
            }
            $dashboard_metals[$metalKey]['cards'] = $cards;
        }
    }
}
