<?php

/**
 * Ensure tbl_jewelry_catalogue exists (jewellery catalog create/edit).
 */
if (!function_exists('auragold_ensure_jewelry_catalogue_table')) {
    function auragold_ensure_jewelry_catalogue_table(mysqli $conn): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $sqlFile = dirname(__DIR__) . '/sql/create_tbl_jewelry_catalogue.sql';
        if (!is_file($sqlFile)) {
            return;
        }
        $sql = (string) file_get_contents($sqlFile);
        if ($sql === '') {
            return;
        }
        @mysqli_multi_query($conn, $sql);
        while (@mysqli_more_results($conn)) {
            @mysqli_next_result($conn);
        }
        $done = true;
    }
}
