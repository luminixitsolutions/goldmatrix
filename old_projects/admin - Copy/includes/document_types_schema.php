<?php
/**
 * Share holder / ledger document type master — table created via PHP (no separate SQL file).
 */
function auragold_ensure_tbl_document_types($conn): void
{
    static $done = [];
    if (!$conn instanceof mysqli) {
        return;
    }
    $key = spl_object_hash($conn);
    if (!empty($done[$key])) {
        return;
    }

    $t = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_document_types'");
    if (!$t || mysqli_num_rows($t) === 0) {
        if ($t) {
            mysqli_free_result($t);
        }
        @mysqli_query(
            $conn,
            "CREATE TABLE IF NOT EXISTS `tbl_document_types` (
                `id` int NOT NULL AUTO_INCREMENT,
                `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` int DEFAULT NULL,
                `modified_by` int DEFAULT NULL,
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } else {
        mysqli_free_result($t);
    }

    if (function_exists('auragold_ensure_table_branch_id_column')) {
        auragold_ensure_table_branch_id_column($conn, 'tbl_document_types');
    }

    $done[$key] = true;
}
