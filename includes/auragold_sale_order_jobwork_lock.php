<?php
/**
 * Sale order lock when Job Work Order(s) exist against the sale order.
 * Deletion order: Jobwork Queue activity → Job Work Order → Sale Order update/delete.
 */

if (!function_exists('auragold_sale_order_linked_jobwork_orders')) {
    /**
     * @return list<array{id:int,jobwork_no:string,jobwork_queue_no:string}>
     */
    function auragold_sale_order_linked_jobwork_orders($conn, $sale_order_id)
    {
        $sale_order_id = (int) $sale_order_id;
        if ($sale_order_id < 1 || !$conn) {
            return [];
        }
        $tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
        if (!$tbl || mysqli_num_rows($tbl) === 0) {
            if ($tbl) {
                mysqli_free_result($tbl);
            }
            return [];
        }
        mysqli_free_result($tbl);

        $sel = 'id, jobwork_no';
        $cq = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'jobwork_queue_no'");
        $has_qn = ($cq && mysqli_num_rows($cq) > 0);
        if ($cq) {
            mysqli_free_result($cq);
        }
        if ($has_qn) {
            $sel .= ', jobwork_queue_no';
        }

        $rows = getList(
            'SELECT ' . $sel . ' FROM tbl_jobwork_orders WHERE sale_order_id = ' . $sale_order_id . ' ORDER BY id ASC'
        );
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) ($r['id'] ?? 0),
                'jobwork_no' => trim((string) ($r['jobwork_no'] ?? '')),
                'jobwork_queue_no' => $has_qn ? trim((string) ($r['jobwork_queue_no'] ?? '')) : '',
            ];
        }

        return $out;
    }
}

if (!function_exists('auragold_sale_order_has_linked_jobwork_order')) {
    function auragold_sale_order_has_linked_jobwork_order($conn, $sale_order_id)
    {
        return count(auragold_sale_order_linked_jobwork_orders($conn, $sale_order_id)) > 0;
    }
}

if (!function_exists('auragold_sale_order_jobwork_save_blocked_tip')) {
    function auragold_sale_order_jobwork_save_blocked_tip($conn, $sale_order_id)
    {
        $jwos = auragold_sale_order_linked_jobwork_orders($conn, $sale_order_id);
        if (empty($jwos)) {
            return '';
        }
        $nos = [];
        foreach ($jwos as $j) {
            $label = $j['jobwork_no'] !== '' ? $j['jobwork_no'] : ('JWO #' . $j['id']);
            if ($j['jobwork_queue_no'] !== '') {
                $label .= ' (Queue: ' . $j['jobwork_queue_no'] . ')';
            }
            $nos[] = $label;
        }
        $list = implode(', ', $nos);

        return 'This sale order is linked to Job Work Order(s): ' . $list
            . '. Delete Jobwork Queue records first (Manufacturing Process / Jobwork Queue), then delete the Job Work Order. After that you can save or delete this sale order.';
    }
}

if (!function_exists('auragold_jobwork_order_has_queue_activity')) {
    function auragold_jobwork_order_has_queue_activity($conn, $jobwork_order_id)
    {
        $jobwork_order_id = (int) $jobwork_order_id;
        if ($jobwork_order_id < 1 || !$conn) {
            return false;
        }
        $tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
        if (!$tbl || mysqli_num_rows($tbl) === 0) {
            if ($tbl) {
                mysqli_free_result($tbl);
            }
            return false;
        }
        mysqli_free_result($tbl);
        $row = getRecord(
            'SELECT COUNT(*) AS c FROM tbl_jobwork_queue_activity WHERE jobwork_order_id = ' . $jobwork_order_id
        );

        return $row && (int) ($row['c'] ?? 0) > 0;
    }
}

if (!function_exists('auragold_jobwork_order_delete_queue_records')) {
    /**
     * Remove all Jobwork Queue (JWQ) data for a job work order before deleting the JWO master.
     */
    function auragold_jobwork_order_delete_queue_records($conn, $jobwork_order_id)
    {
        $jobwork_order_id = (int) $jobwork_order_id;
        if ($jobwork_order_id < 1 || !$conn) {
            return;
        }

        $queue_tables = [
            'tbl_jobwork_queue_activity',
            'tbl_jobwork_queue_diamond_stock_issue',
            'tbl_jobwork_queue_diamond_stock',
        ];
        foreach ($queue_tables as $t) {
            $tq = @mysqli_query($conn, "SHOW TABLES LIKE '" . mysqli_real_escape_string($conn, $t) . "'");
            if ($tq && mysqli_num_rows($tq) > 0) {
                mysqli_free_result($tq);
                @mysqli_query($conn, 'DELETE FROM `' . $t . '` WHERE jobwork_order_id = ' . $jobwork_order_id);
            } elseif ($tq) {
                mysqli_free_result($tq);
            }
        }
    }
}

if (!function_exists('auragold_sale_order_ids_with_jobwork_orders')) {
    /**
     * @return array<int,true> sale_order_id => true
     */
    function auragold_sale_order_ids_with_jobwork_orders($conn)
    {
        if (!$conn) {
            return [];
        }
        $tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
        if (!$tbl || mysqli_num_rows($tbl) === 0) {
            if ($tbl) {
                mysqli_free_result($tbl);
            }
            return [];
        }
        mysqli_free_result($tbl);
        $rows = getList(
            'SELECT DISTINCT sale_order_id FROM tbl_jobwork_orders WHERE sale_order_id IS NOT NULL AND sale_order_id > 0'
        );
        $map = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $sid = (int) ($r['sale_order_id'] ?? 0);
                if ($sid > 0) {
                    $map[$sid] = true;
                }
            }
        }

        return $map;
    }
}
