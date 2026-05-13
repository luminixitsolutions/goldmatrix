<?php
/**
 * Map tbl_metal rows to dashboard metal keys (gold|silver|platinum|diamond).
 * Load latest carat row per metal that has an uploaded path or external URL.
 */
if (!function_exists('auragold_dashboard_key_for_metal_row')) {
    function auragold_dashboard_key_for_metal_row(array $m): ?string
    {
        $sn = strtolower(trim((string) ($m['system_name'] ?? '')));
        if (in_array($sn, ['gold', 'silver', 'platinum', 'diamond'], true)) {
            return $sn;
        }
        $dn = strtolower(trim((string) ($m['display_name'] ?? '')));
        if ($dn === '') {
            return null;
        }
        if (strpos($dn, 'diamond') !== false) {
            return 'diamond';
        }
        if (strpos($dn, 'platinum') !== false) {
            return 'platinum';
        }
        if (strpos($dn, 'silver') !== false) {
            return 'silver';
        }
        if (strpos($dn, 'gold') !== false) {
            return 'gold';
        }
        return null;
    }
}

if (!function_exists('auragold_dashboard_metal_images_from_carats')) {
    /**
     * @return array<string,string> keyed by gold|silver|platinum|diamond → absolute URL or admin-relative URL
     */
    function auragold_dashboard_metal_images_from_carats($conn): array
    {
        if (!$conn || !function_exists('auragold_tbl_has_column')) {
            return [];
        }
        if (!auragold_tbl_has_column($conn, 'tbl_carat', 'metal_id')
            || !auragold_tbl_has_column($conn, 'tbl_carat', 'dashboard_image_path')) {
            return [];
        }
        require_once __DIR__ . '/auragold_carat_dashboard_image_schema.php';
        auragold_ensure_tbl_carat_dashboard_images($conn);

        $metals = getList('SELECT id, display_name, system_name FROM tbl_metal WHERE status = 1 ORDER BY id ASC');
        if (!is_array($metals)) {
            return [];
        }
        $suffix = function_exists('auragold_master_list_sql_suffix')
            ? auragold_master_list_sql_suffix($conn, 'tbl_carat')
            : '';
        $out = [];
        foreach ($metals as $mrow) {
            $key = auragold_dashboard_key_for_metal_row($mrow);
            if ($key === null || isset($out[$key])) {
                continue;
            }
            $mid = (int) ($mrow['id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            $sql = 'SELECT dashboard_image_path, dashboard_image_url FROM tbl_carat WHERE status = 1 AND metal_id = ' . $mid
                . " AND (NULLIF(TRIM(COALESCE(dashboard_image_path,'')),'') IS NOT NULL OR NULLIF(TRIM(COALESCE(dashboard_image_url,'')),'') IS NOT NULL)"
                . $suffix
                . ' ORDER BY id DESC LIMIT 1';
            $crow = function_exists('getRecord') ? getRecord($sql) : null;
            if (!is_array($crow)) {
                continue;
            }
            $path = trim((string) ($crow['dashboard_image_path'] ?? ''));
            $extUrl = trim((string) ($crow['dashboard_image_url'] ?? ''));
            if ($path !== '') {
                if (preg_match('#^https?://#i', $path)) {
                    $out[$key] = $path;
                } else {
                    $path = ltrim(str_replace('\\', '/', $path), '/');
                    $out[$key] = $path;
                }
            } elseif ($extUrl !== '') {
                $out[$key] = $extUrl;
            }
        }
        return $out;
    }
}

if (!function_exists('auragold_dashboard_metal_images_from_tbl_metal')) {
    /**
     * @return array<string,string> keyed by gold|silver|platinum|diamond
     */
    function auragold_dashboard_metal_images_from_tbl_metal($conn): array
    {
        if (!$conn || !function_exists('auragold_tbl_has_column')) {
            return [];
        }
        if (!auragold_tbl_has_column($conn, 'tbl_metal', 'dashboard_image_path')) {
            return [];
        }
        require_once __DIR__ . '/auragold_metal_dashboard_image_schema.php';
        auragold_ensure_tbl_metal_dashboard_images($conn);

        $suffix = function_exists('auragold_master_list_sql_suffix')
            ? auragold_master_list_sql_suffix($conn, 'tbl_metal')
            : '';
        $sql = 'SELECT display_name, system_name, dashboard_image_path, dashboard_image_url FROM tbl_metal WHERE status = 1' . $suffix;
        $metals = getList($sql);
        if (!is_array($metals)) {
            return [];
        }
        $out = [];
        foreach ($metals as $mrow) {
            $key = auragold_dashboard_key_for_metal_row($mrow);
            if ($key === null || isset($out[$key])) {
                continue;
            }
            $path = trim((string) ($mrow['dashboard_image_path'] ?? ''));
            $extUrl = trim((string) ($mrow['dashboard_image_url'] ?? ''));
            if ($path !== '') {
                if (preg_match('#^https?://#i', $path)) {
                    $out[$key] = $path;
                } else {
                    $out[$key] = ltrim(str_replace('\\', '/', $path), '/');
                }
            } elseif ($extUrl !== '') {
                $out[$key] = $extUrl;
            }
        }
        return $out;
    }
}
