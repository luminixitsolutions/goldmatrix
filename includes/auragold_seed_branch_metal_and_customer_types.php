<?php
/**
 * After a new tbl_branches row: default tbl_metal (scoped by branch_id when present)
 * and tbl_customer_types (idempotent by `name`, matching UNIQUE `name`; optional branch_id on insert).
 */
if (!function_exists('auragold_schema_table_reachable')) {
    function auragold_schema_table_reachable(mysqli $conn, string $table): bool {
        $t = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
        if ($t === '' || !($conn instanceof mysqli)) {
            return false;
        }
        $r = @mysqli_query($conn, 'SELECT 1 FROM `' . str_replace('`', '``', $t) . '` LIMIT 1');
        if ($r) {
            mysqli_free_result($r);
        }
        return (bool) $r;
    }
}

if (!function_exists('auragold_metal_default_rows_for_new_branch')) {
    /**
     * @return list<array{display_name:string, hsn_code:?string, system_name:?string, created_by:?int}>
     */
    function auragold_metal_default_rows_for_new_branch(): array {
        return [
            ['display_name' => 'Gold', 'hsn_code' => '12345', 'system_name' => 'ewew', 'created_by' => 0],
            ['display_name' => 'Silver', 'hsn_code' => null, 'system_name' => null, 'created_by' => null],
            ['display_name' => 'Platinum', 'hsn_code' => null, 'system_name' => null, 'created_by' => null],
            ['display_name' => 'Diamond & Stones', 'hsn_code' => null, 'system_name' => null, 'created_by' => null],
            ['display_name' => 'Imitation Or Watches', 'hsn_code' => null, 'system_name' => null, 'created_by' => null],
            ['display_name' => 'Other Or Services', 'hsn_code' => null, 'system_name' => null, 'created_by' => null],
        ];
    }
}

if (!function_exists('auragold_customer_type_default_rows_for_new_branch')) {
    /**
     * @return list<array{name:string,code:string,sort:int}>
     */
    function auragold_customer_type_default_rows_for_new_branch(): array {
        return [
            ['name' => 'Customer', 'code' => 'CUSTOMER', 'sort' => 1],
            ['name' => 'WholeSaler', 'code' => 'WHOLESALER', 'sort' => 2],
            ['name' => 'Job Worker', 'code' => 'JOB_WORKER', 'sort' => 3],
            ['name' => 'Employee', 'code' => 'EMPLOYEE', 'sort' => 4],
            ['name' => 'Sales Person', 'code' => 'SALES_PERSON', 'sort' => 5],
            ['name' => 'Supplier', 'code' => 'SUPPLIER', 'sort' => 6],
            ['name' => 'Qbo Account', 'code' => 'QBO_ACCOUNT', 'sort' => 7],
            ['name' => 'Retailer', 'code' => 'RETAILER', 'sort' => 8],
        ];
    }
}

if (!function_exists('auragold_seed_metal_and_customer_types_for_new_branch')) {
    /**
     * @param mysqli $conn             Operational (branch) database
     * @param int    $registryBranchId New tbl_branches.id
     */
    function auragold_seed_metal_and_customer_types_for_new_branch(mysqli $conn, int $registryBranchId): void {
        $registryBranchId = (int) $registryBranchId;
        if ($registryBranchId < 1) {
            return;
        }
        if (!function_exists('auragold_tbl_has_column')) {
            require_once __DIR__ . '/auragold_branch_data_scope.php';
        }
        if (!function_exists('auragold_tbl_has_column')) {
            return;
        }

        $bid = (int) $registryBranchId;

        // --- tbl_metal ---
        if (auragold_schema_table_reachable($conn, 'tbl_metal')) {
            $hasBranch   = auragold_tbl_has_column($conn, 'tbl_metal', 'branch_id');
            $metals      = auragold_metal_default_rows_for_new_branch();
            if ($hasBranch) {
                $cntR = @mysqli_query($conn, 'SELECT COUNT(*) AS c FROM `tbl_metal` WHERE `branch_id` = ' . (int) $bid);
            } else {
                $cntR = @mysqli_query($conn, 'SELECT COUNT(*) AS c FROM `tbl_metal`');
            }
            $cct = 0;
            if ($cntR && ($rw = mysqli_fetch_assoc($cntR))) {
                $cct = (int) ($rw['c'] ?? 0);
            }
            if ($cntR) {
                mysqli_free_result($cntR);
            }
            if ($cct === 0) {
                foreach ($metals as $m) {
                    $escName = mysqli_real_escape_string($conn, (string) $m['display_name']);
                    if ($hasBranch) {
                        $qEx = "SELECT 1 FROM `tbl_metal` WHERE `display_name` = '{$escName}' AND `branch_id` = " . (int) $bid . ' LIMIT 1';
                    } else {
                        $qEx = "SELECT 1 FROM `tbl_metal` WHERE `display_name` = '{$escName}' LIMIT 1";
                    }
                    $eR = @mysqli_query($conn, $qEx);
                    if ($eR) {
                        if (mysqli_num_rows($eR) > 0) {
                            mysqli_free_result($eR);
                            continue;
                        }
                        mysqli_free_result($eR);
                    }

                    $hp = [];
                    $vp = [];
                    if ($hasBranch) {
                        $hp[] = '`branch_id`';
                        $vp[] = (string) (int) $bid;
                    }
                    $hp[] = '`display_name`';
                    $vp[] = '\'' . $escName . '\'';
                    $hp[] = '`hsn_code`';
                    if ($m['hsn_code'] !== null && (string) $m['hsn_code'] !== '') {
                        $vp[] = '\'' . mysqli_real_escape_string($conn, (string) $m['hsn_code']) . '\'';
                    } else {
                        $vp[] = 'NULL';
                    }
                    $hp[] = '`system_name`';
                    if ($m['system_name'] !== null && (string) $m['system_name'] !== '') {
                        $vp[] = '\'' . mysqli_real_escape_string($conn, (string) $m['system_name']) . '\'';
                    } else {
                        $vp[] = 'NULL';
                    }
                    $hp[] = '`status`';
                    $vp[] = '1';
                    if (auragold_tbl_has_column($conn, 'tbl_metal', 'created_by')) {
                        $hp[] = '`created_by`';
                        if (isset($m['created_by']) && $m['created_by'] !== null) {
                            $vp[] = (string) (int) $m['created_by'];
                        } else {
                            $vp[] = 'NULL';
                        }
                    }
                    if (auragold_tbl_has_column($conn, 'tbl_metal', 'modified_by')) {
                        $hp[] = '`modified_by`';
                        $vp[] = 'NULL';
                    }
                    if (auragold_tbl_has_column($conn, 'tbl_metal', 'created_at')) {
                        $hp[] = '`created_at`';
                        $vp[] = 'NOW()';
                    }
                    if (auragold_tbl_has_column($conn, 'tbl_metal', 'updated_at')) {
                        $hp[] = '`updated_at`';
                        $vp[] = 'NULL';
                    }
                    @mysqli_query(
                        $conn,
                        'INSERT INTO `tbl_metal` (' . implode(',', $hp) . ') VALUES (' . implode(',', $vp) . ')'
                    );
                }
            }
        }

        // --- tbl_customer_types (merge missing) ---
        if (auragold_schema_table_reachable($conn, 'tbl_customer_types')) {
            $hasCBranch = auragold_tbl_has_column($conn, 'tbl_customer_types', 'branch_id');
            $types      = auragold_customer_type_default_rows_for_new_branch();
            foreach ($types as $t) {
                $cEsc = mysqli_real_escape_string($conn, (string) $t['code']);
                $nEsc = mysqli_real_escape_string($conn, (string) $t['name']);
                // Schema uses UNIQUE KEY `name` (`name`). After sub-branch provision we copy types from the
                // main DB; those rows often keep the main branch's `branch_id`. A code lookup scoped to
                // this branch then misses and INSERT duplicates `name` (e.g. "Customer").
                $r = @mysqli_query(
                    $conn,
                    'SELECT 1 FROM `tbl_customer_types` WHERE LOWER(`name`) = LOWER(\'' . $nEsc . '\') LIMIT 1'
                );
                if ($r && mysqli_num_rows($r) > 0) {
                    mysqli_free_result($r);
                    continue;
                }
                if ($r) {
                    mysqli_free_result($r);
                }
                $hp2  = ['`name`', '`code`', '`status`', '`sort_order`'];
                $vp2  = ['\'' . $nEsc . '\'', '\'' . $cEsc . '\'', '1', (string) (int) $t['sort']];
                if ($hasCBranch) {
                    $hp2[] = '`branch_id`';
                    $vp2[] = (string) (int) $bid;
                }
                if (auragold_tbl_has_column($conn, 'tbl_customer_types', 'created_at')) {
                    $hp2[] = '`created_at`';
                    $vp2[] = 'NOW()';
                }
                if (auragold_tbl_has_column($conn, 'tbl_customer_types', 'updated_at')) {
                    $hp2[] = '`updated_at`';
                    $vp2[] = 'NULL';
                }
                @mysqli_query(
                    $conn,
                    'INSERT INTO `tbl_customer_types` (' . implode(',', $hp2) . ') VALUES (' . implode(',', $vp2) . ')'
                );
            }
        }
    }
}
