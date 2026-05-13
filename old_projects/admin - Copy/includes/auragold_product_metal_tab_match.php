<?php
/**
 * Sale invoice metal tabs use branch-scoped tbl_metal rows (and dedupe by display_name).
 * Product opening may save pc.metal_id pointing at another row with the same display_name.
 * Match characteristics to the active tab by display_name so search / lists stay consistent.
 */
if (!function_exists('auragold_sql_pc_metal_matches_tab_metal')) {
    function auragold_sql_pc_metal_matches_tab_metal(int $metal_id): string {
        if ($metal_id <= 0) {
            return '';
        }
        $mtab = getRecord('SELECT display_name FROM tbl_metal WHERE id = ' . (int) $metal_id . ' LIMIT 1');
        $dn = $mtab ? trim((string) ($mtab['display_name'] ?? '')) : '';
        if ($dn === '') {
            return ' AND pc.metal_id = ' . (int) $metal_id;
        }
        $dn_esc = esc($dn);
        return " AND pc.metal_id IN (SELECT id FROM tbl_metal WHERE status = 1 AND LOWER(TRIM(display_name)) = LOWER(TRIM('$dn_esc')))";
    }
}
