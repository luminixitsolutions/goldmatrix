<?php
/**
 * Manufacturing — Jobwork Queue modal: persist queue number (bill series), optional line weights,
 * auto-loss (reduce weight adjustment), and To Dept / To User transfer.
 */
ob_start();
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
error_log('mp-save-jobwork-queue POST: ' . print_r($_POST, true));

register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int)($e['type'] ?? 0), $fatalTypes, true)) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    $msg = 'Fatal: ' . (string)($e['message'] ?? 'Unknown fatal error');
    $file = (string)($e['file'] ?? '');
    $line = (int)($e['line'] ?? 0);
    echo json_encode([
        'ok' => false,
        'message' => $msg,
        'debug_file' => $file,
        'debug_line' => $line,
    ]);
    exit;
});

function mp_jwq_json_out(array $payload): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    $flags = JSON_UNESCAPED_UNICODE;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($payload, $flags);
    echo ($json !== false) ? $json : '{"ok":false,"message":"JSON encode failed"}';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mp_jwq_json_out(['ok' => false, 'message' => 'Invalid request']);
}

$jobwork_order_id = isset($_POST['jobwork_order_id']) ? (int)$_POST['jobwork_order_id'] : 0;
if ($jobwork_order_id < 1) {
    mp_jwq_json_out(['ok' => false, 'message' => 'Job work order id required']);
}

$tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
if (!$tbl || mysqli_num_rows($tbl) === 0) {
    if ($tbl) {
        mysqli_free_result($tbl);
    }
    mp_jwq_json_out(['ok' => false, 'message' => 'Job work orders table not found']);
}
mysqli_free_result($tbl);

$cq = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'jobwork_queue_no'");
if (!$cq || mysqli_num_rows($cq) === 0) {
    if ($cq) {
        mysqli_free_result($cq);
    }
    @mysqli_query($conn, "ALTER TABLE `tbl_jobwork_orders` ADD COLUMN `jobwork_queue_no` varchar(50) NOT NULL DEFAULT '' COMMENT 'Jobwork Queue No from bill series (Jobwork Queue voucher)' AFTER `jobwork_no`");
} elseif ($cq) {
    mysqli_free_result($cq);
}

$to_dept = isset($_POST['to_dept_id']) ? (int)$_POST['to_dept_id'] : 0;
if ($to_dept < 1) {
    mp_jwq_json_out(['ok' => false, 'message' => 'Please select destination department (To Dept.).']);
}
$to_user = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
$from_dept_post = isset($_POST['from_dept_id']) ? (int)$_POST['from_dept_id'] : 0;
$from_user_post = isset($_POST['from_user_id']) ? (int)$_POST['from_user_id'] : 0;
$queue_lines_raw = isset($_POST['queue_lines']) ? trim((string)$_POST['queue_lines']) : '';

$queue_no = null;
if (function_exists('ensureJobworkQueueNoForOrder')) {
    $queue_no = ensureJobworkQueueNoForOrder($conn, $jobwork_order_id);
}

$cd = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'department_id'");
$has_dept = ($cd && mysqli_num_rows($cd) > 0);
if ($cd) {
    mysqli_free_result($cd);
}
$cu = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_orders LIKE 'department_user_id'");
$has_user = ($cu && mysqli_num_rows($cu) > 0);
if ($cu) {
    mysqli_free_result($cu);
}

if (!$has_dept) {
    mp_jwq_json_out(['ok' => false, 'message' => 'Job work order has no department_id column. Run alter scripts.']);
}

$jwo_before = function_exists('getRecord')
    ? getRecord('SELECT id, sale_order_id, jobwork_queue_no, department_id, department_user_id FROM tbl_jobwork_orders WHERE id = ' . (int)$jobwork_order_id . ' LIMIT 1')
    : null;
if (!$jwo_before) {
    mp_jwq_json_out(['ok' => false, 'message' => 'Job work order not found.']);
}

$from_dept_for_loss = $from_dept_post > 0 ? $from_dept_post : (int)($jwo_before['department_id'] ?? 0);
$from_user_for_loss = $from_user_post > 0 ? $from_user_post : ((isset($jwo_before['department_user_id']) && $jwo_before['department_user_id'] !== null && $jwo_before['department_user_id'] !== '') ? (int)$jwo_before['department_user_id'] : 0);
$is_transfer = ($to_dept > 0 && $from_dept_for_loss > 0 && ($to_dept !== $from_dept_for_loss || $to_user !== $from_user_for_loss));

$auto_loss_on = false;
if ($is_transfer && $from_dept_for_loss > 0) {
    $ddr = @mysqli_query($conn, 'SELECT auto_loss FROM tbl_departments WHERE id = ' . (int)$from_dept_for_loss . ' LIMIT 1');
    if ($ddr && ($drow = mysqli_fetch_assoc($ddr))) {
        $auto_loss_on = ((int)($drow['auto_loss'] ?? 0) === 1);
    }
    if ($ddr) {
        mysqli_free_result($ddr);
    }
}

$queue_lines = [];
if ($queue_lines_raw !== '') {
    $decoded = json_decode($queue_lines_raw, true);
    if (is_array($decoded)) {
        $queue_lines = $decoded;
    }
}

$ji_cols = [];
$icq = @mysqli_query($conn, 'SHOW COLUMNS FROM tbl_jobwork_order_items');
if ($icq) {
    while ($r = mysqli_fetch_assoc($icq)) {
        $ji_cols[$r['Field']] = true;
    }
    mysqli_free_result($icq);
}

$wchk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_weight_adjustments'");
if (!$wchk || mysqli_num_rows($wchk) === 0) {
    if ($wchk) {
        mysqli_free_result($wchk);
    }
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `tbl_jobwork_weight_adjustments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `jobwork_order_id` int(11) NOT NULL,
      `adjustment_type` enum('add','reduce') NOT NULL DEFAULT 'reduce',
      `weight_grams` decimal(12,4) NOT NULL DEFAULT 0.0000,
      `remark` varchar(500) DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `created_by_user_id` int(11) DEFAULT NULL,
      `source_department_id` int(11) DEFAULT NULL,
      `source_user_id` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `jobwork_order_id` (`jobwork_order_id`),
      KEY `adjustment_type` (`adjustment_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} elseif ($wchk) {
    mysqli_free_result($wchk);
}
$wsrc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_weight_adjustments LIKE 'source_department_id'");
if (!$wsrc || mysqli_num_rows($wsrc) === 0) {
    @mysqli_query($conn, 'ALTER TABLE tbl_jobwork_weight_adjustments ADD COLUMN source_department_id int(11) DEFAULT NULL AFTER created_by_user_id, ADD COLUMN source_user_id int(11) DEFAULT NULL AFTER source_department_id');
}
if ($wsrc) {
    mysqli_free_result($wsrc);
}

function mp_jwq_item_orig_wt(array $row) {
    $f = isset($row['final_weight']) ? (float)$row['final_weight'] : 0.0;
    if ($f > 0.0000001) {
        return $f;
    }
    $n = isset($row['net_weight']) ? (float)$row['net_weight'] : 0.0;
    if ($n > 0.0000001) {
        return $n;
    }
    $g = isset($row['gross_weight']) ? (float)$row['gross_weight'] : 0.0;
    if ($g > 0.0000001) {
        return $g;
    }
    return 0.0;
}

/** Sum line weights (final → net → gross) and quantities — same basis as manufacturing cards after transfer. */
function mp_jwq_totals_from_items($conn, $jobwork_order_id, array $ji_cols) {
    if (empty($ji_cols)) {
        return [0.0, 0.0];
    }
    $whens = [];
    if (!empty($ji_cols['final_weight'])) {
        $whens[] = 'WHEN COALESCE(ji.final_weight, 0) > 0.0000001 THEN ji.final_weight';
    }
    if (!empty($ji_cols['net_weight'])) {
        $whens[] = 'WHEN COALESCE(ji.net_weight, 0) > 0.0000001 THEN ji.net_weight';
    }
    if (!empty($ji_cols['gross_weight'])) {
        $whens[] = 'WHEN COALESCE(ji.gross_weight, 0) > 0.0000001 THEN ji.gross_weight';
    }
    if (empty($whens)) {
        return [0.0, 0.0];
    }
    $case = 'CASE ' . implode(' ', $whens) . ' ELSE 0 END';
    $sql = 'SELECT COALESCE(SUM(' . $case . '), 0) AS sw, COALESCE(SUM(COALESCE(ji.quantity, 0)), 0) AS sq FROM tbl_jobwork_order_items ji WHERE ji.jobwork_order_id = ' . (int)$jobwork_order_id;
    $r = function_exists('getRecord') ? getRecord($sql) : null;
    if (!$r) {
        return [0.0, 0.0];
    }
    return [(float)($r['sw'] ?? 0), (float)($r['sq'] ?? 0)];
}

$use_tx = function_exists('mysqli_begin_transaction');
if ($use_tx) {
    @mysqli_begin_transaction($conn);
}

$tx_ok = true;
$tx_err = '';
$diamond_stats = ['saved_rows' => 0, 'excluded_stock_ids' => []];
$diamond_issue_upserted = 0;
$last_insert_error = '';
$calculated_diamond_weight = [];
$tx_step = 'init';

require_once dirname(__DIR__) . '/includes/mp-jobwork-queue-diamond-stock.php';
mp_jwq_ensure_diamond_issue_table($conn);
$issue_tbl = mp_jwq_diamond_issue_table_name();

$diamond_stock_raw = isset($_POST['jwq_diamond_stock_lines']) ? trim((string) $_POST['jwq_diamond_stock_lines']) : '';
if ($diamond_stock_raw === '' && isset($_POST['material_diamond_stock'])) {
    $diamond_stock_raw = trim((string) $_POST['material_diamond_stock']);
}
if ($diamond_stock_raw === '' && isset($_POST['diamond_stock'])) {
    $diamond_stock_raw = trim((string) $_POST['diamond_stock']);
}
if ($diamond_stock_raw === '' && isset($_POST['material_rows'])) {
    $diamond_stock_raw = trim((string) $_POST['material_rows']);
}
$diamond_stock_rows = [];
$diamond_duplicate_stock_ids = [];
if ($diamond_stock_raw !== '') {
    $diamond_dec = json_decode($diamond_stock_raw, true);
    if (is_array($diamond_dec)) {
        $diamond_stock_rows = $diamond_dec;
    }
}
if (!empty($diamond_stock_rows) && is_array($diamond_stock_rows)) {
    $seen_sid = [];
    $filtered = [];
    foreach ($diamond_stock_rows as $dsr) {
        if (!is_array($dsr)) {
            continue;
        }
        $sid = (int) ($dsr['stock_id'] ?? 0);
        if ($sid > 0) {
            if (isset($seen_sid[$sid])) {
                $diamond_duplicate_stock_ids[] = $sid;
                continue;
            }
            $seen_sid[$sid] = true;
        }
        $filtered[] = $dsr;
    }
    $diamond_stock_rows = $filtered;
    $diamond_duplicate_stock_ids = array_values(array_unique(array_map('intval', $diamond_duplicate_stock_ids)));
}
$diamond_stock_rows = array_values(array_filter(is_array($diamond_stock_rows) ? $diamond_stock_rows : [], static function ($dsr): bool {
    if (!is_array($dsr)) {
        return false;
    }
    $sid = (int) ($dsr['stock_id'] ?? 0);
    $bc = trim((string) ($dsr['barcode'] ?? ''));

    return $sid > 0 && $bc !== '';
}));
error_log('DECODED DIAMOND ROWS: ' . print_r($diamond_stock_rows, true));

$first_item_id = 0;
if (function_exists('getRecord')) {
    $fi = getRecord('SELECT id FROM tbl_jobwork_order_items WHERE jobwork_order_id = ' . (int) $jobwork_order_id . ' ORDER BY id ASC LIMIT 1');
    if ($fi && isset($fi['id'])) {
        $first_item_id = (int) $fi['id'];
    }
}
if ($first_item_id > 0 && !empty($diamond_stock_rows)) {
    foreach ($diamond_stock_rows as $k => $dsr) {
        if (!is_array($dsr)) {
            continue;
        }
        $iid = (int) ($dsr['jobwork_order_item_id'] ?? 0);
        if ($iid < 1) {
            $diamond_stock_rows[$k]['jobwork_order_item_id'] = $first_item_id;
        }
    }
}

$issued_sum_by_item = [];
if (!empty($diamond_stock_rows)) {
    foreach ($diamond_stock_rows as $dsr) {
        if (!is_array($dsr)) {
            continue;
        }
        $iid = (int) ($dsr['jobwork_order_item_id'] ?? 0);
        $ww = (float) ($dsr['weight'] ?? 0);
        if ($iid > 0 && $ww > 0.0000001) {
            $issued_sum_by_item[$iid] = ($issued_sum_by_item[$iid] ?? 0) + $ww;
        }
    }
}
$existing_issue_rows = function_exists('getList')
    ? getList("SELECT jobwork_order_item_id, COALESCE(SUM(weight),0) AS sw FROM `$issue_tbl` WHERE jobwork_order_id = " . (int) $jobwork_order_id . ' GROUP BY jobwork_order_item_id')
    : [];
if (is_array($existing_issue_rows)) {
    foreach ($existing_issue_rows as $er) {
        $iid = (int) ($er['jobwork_order_item_id'] ?? 0);
        $sw = (float) ($er['sw'] ?? 0);
        if ($iid > 0 && $sw > 0.0000001) {
            $issued_sum_by_item[$iid] = max($issued_sum_by_item[$iid] ?? 0, $sw);
        }
    }
}

if (!empty($ji_cols) && !empty($queue_lines)) {
    foreach ($queue_lines as $li) {
        if (!is_array($li)) {
            continue;
        }
        $item_id = (int)($li['item_id'] ?? 0);
        if ($item_id < 1) {
            continue;
        }
        $itrow = function_exists('getRecord')
            ? getRecord('SELECT * FROM tbl_jobwork_order_items WHERE id = ' . $item_id . ' AND jobwork_order_id = ' . (int)$jobwork_order_id . ' LIMIT 1')
            : null;
        if (!$itrow) {
            continue;
        }
        $orig = mp_jwq_item_orig_wt($itrow);
        $new_total = isset($li['total_wt']) ? (float)$li['total_wt'] : null;
        $new_metal = isset($li['metal_wt']) ? (float)$li['metal_wt'] : null;
        $new_diamond = null;
        if (isset($li['diamond_wt'])) {
            $new_diamond = (float)$li['diamond_wt'];
        } elseif (isset($li['diamond_weight'])) {
            $new_diamond = (float)$li['diamond_weight'];
        }
        $new_dust = null;
        if (isset($li['dust_wastage_wt'])) {
            $new_dust = (float)$li['dust_wastage_wt'];
        } elseif (isset($li['wastage_wt'])) {
            $new_dust = (float)$li['wastage_wt'];
        }
        if ($new_dust !== null && !is_finite($new_dust)) {
            $new_dust = null;
        }
        $new_loss_line = isset($li['loss']) ? (float)$li['loss'] : null;
        if ($new_loss_line !== null && !is_finite($new_loss_line)) {
            $new_loss_line = null;
        }
        if (isset($issued_sum_by_item[$item_id]) && $issued_sum_by_item[$item_id] > 0.0000001) {
            // Never overwrite issued-diamond sum with queue_lines client value (can be stale/0).
            $new_diamond = (float) $issued_sum_by_item[$item_id];
        }
        if ($new_total === null || !is_finite($new_total)) {
            continue;
        }
        if ($new_metal === null || !is_finite($new_metal)) {
            $new_metal = $new_total;
        }

        $loss = 0.0;
        if ($auto_loss_on && $orig > 0.0000001 && $new_total < $orig - 0.0000001) {
            $loss = $orig - $new_total;
        }

        $sets = [];
        if (!empty($ji_cols['final_weight'])) {
            $sets[] = 'final_weight = ' . round($new_total, 4);
        }
        if (!empty($ji_cols['net_weight'])) {
            $sets[] = 'net_weight = ' . round($new_metal, 4);
        }
        if ($new_diamond !== null && is_finite($new_diamond)) {
            if (!empty($ji_cols['diamond_weight'])) {
                $sets[] = 'diamond_weight = ' . round($new_diamond, 4);
            } elseif (!empty($ji_cols['diamond_wt'])) {
                $sets[] = 'diamond_wt = ' . round($new_diamond, 4);
            }
        }
        if ($new_dust !== null && is_finite($new_dust) && $new_dust >= 0) {
            if (!empty($ji_cols['wastage_wt'])) {
                $sets[] = 'wastage_wt = ' . round($new_dust, 4);
            }
        }
        if (!empty($ji_cols['less_weight'])) {
            $lw = isset($itrow['less_weight']) ? (float)$itrow['less_weight'] : 0.0;
            if ($new_dust !== null && is_finite($new_dust) && $new_dust >= 0 && empty($ji_cols['wastage_wt'])) {
                $lw = round($new_dust, 4);
            }
            if ($loss > 0.0000001) {
                $sets[] = 'less_weight = ' . round($lw + $loss, 4);
            } elseif ($new_dust !== null && is_finite($new_dust) && $new_dust >= 0 && empty($ji_cols['wastage_wt'])) {
                $sets[] = 'less_weight = ' . round($new_dust, 4);
            }
        }
        if (!empty($ji_cols['gold_loss_1'])) {
            $gl = isset($itrow['gold_loss_1']) ? (float)$itrow['gold_loss_1'] : 0.0;
            if ($new_loss_line !== null && is_finite($new_loss_line) && $new_loss_line >= 0) {
                $gl = round($new_loss_line, 4);
            }
            if ($loss > 0.0000001) {
                $sets[] = 'gold_loss_1 = ' . round($gl + $loss, 4);
            } elseif ($new_loss_line !== null && is_finite($new_loss_line) && $new_loss_line >= 0) {
                $sets[] = 'gold_loss_1 = ' . round($new_loss_line, 4);
            }
        } elseif (!empty($ji_cols['loss_wt']) && $new_loss_line !== null && is_finite($new_loss_line) && $new_loss_line >= 0) {
            $sets[] = 'loss_wt = ' . round($new_loss_line, 4);
        }
        if (!empty($sets)) {
            $tx_step = 'update_jobwork_order_item_weights';
            $sql_up = 'UPDATE tbl_jobwork_order_items SET ' . implode(', ', $sets) . ' WHERE id = ' . $item_id . ' AND jobwork_order_id = ' . (int)$jobwork_order_id . ' LIMIT 1';
            if (!@mysqli_query($conn, $sql_up)) {
                $tx_ok = false;
                $tx_err = 'Could not update job work order line weights. DB: ' . mysqli_error($conn);
                break;
            }
        }

        if ($loss > 0.0000001) {
            $loss_val = round($loss, 4);
            if ($loss_val > 999999.9999) {
                $loss_val = 999999.9999;
            }
            $remark = 'Auto loss on transfer (from dept ' . $from_dept_for_loss . ' → dept ' . $to_dept . ')';
            $remark_esc = mysqli_real_escape_string($conn, $remark);
            $src_d_sql = $from_dept_for_loss > 0 ? (string)(int)$from_dept_for_loss : 'NULL';
            $src_u_sql = $from_user_for_loss > 0 ? (string)(int)$from_user_for_loss : 'NULL';
            $tx_step = 'insert_auto_loss_adjustment';
            $ins_w = 'INSERT INTO tbl_jobwork_weight_adjustments (jobwork_order_id, adjustment_type, weight_grams, remark, source_department_id, source_user_id) VALUES ('
                . (int)$jobwork_order_id . ", 'reduce', " . $loss_val . ", '" . $remark_esc . "', " . $src_d_sql . ', ' . $src_u_sql . ')';
            if (!@mysqli_query($conn, $ins_w)) {
                $tx_ok = false;
                $tx_err = 'Could not save auto loss weight entry. DB: ' . mysqli_error($conn);
                break;
            }
        }
    }
}
if ($tx_ok && !empty($diamond_stock_rows)) {
    $tx_step = 'apply_diamond_stock_consumption';
    $diamond_stats = ['saved_rows' => 0, 'excluded_stock_ids' => []];
    $diamond_lines = $diamond_stock_rows;
    foreach ($diamond_lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $stock_id = (int) ($line['stock_id'] ?? 0);
        $barcode = trim((string) ($line['barcode'] ?? ''));
        $product_name = trim((string) ($line['product_name'] ?? ''));
        $weight = (float) ($line['weight'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        $jobwork_order_item_id = (int) ($line['jobwork_order_item_id'] ?? 0);
        if ($first_item_id > 0 && $jobwork_order_item_id < 1) {
            $jobwork_order_item_id = $first_item_id;
        }
        $diamond_category = trim((string) ($line['diamond_category'] ?? 'Diamond'));
        if ($diamond_category === '') {
            $diamond_category = 'Diamond';
        }
        error_log(
            'DIAMOND SAVE ROW => stock_id=' . $stock_id
            . ' barcode=' . $barcode
            . ' weight=' . $weight
            . ' qty=' . $qty
            . ' item_id=' . $jobwork_order_item_id
        );
        if ($stock_id <= 0 || $barcode === '') {
            continue;
        }
        $diamond_one = [
            'stock_id' => $stock_id,
            'barcode' => $barcode,
            'product_name' => $product_name,
            'weight' => $weight,
            'qty' => $qty,
            'jobwork_order_item_id' => $jobwork_order_item_id,
            'diamond_category' => $diamond_category,
            'added_by_dept_id' => (int) ($line['added_by_dept_id'] ?? 0),
            'added_by_user_id' => (int) ($line['added_by_user_id'] ?? 0),
        ];
        mp_jwq_apply_diamond_stock_consumption(
            $conn,
            $jobwork_order_id,
            [$diamond_one],
            $tx_ok,
            $tx_err,
            $from_dept_for_loss,
            $to_dept,
            $from_user_for_loss,
            $to_user,
            $diamond_stats
        );
        if (!$tx_ok) {
            break;
        }
    }
}
if ($tx_ok && !empty($diamond_stock_rows) && function_exists('mp_jwq_upsert_diamond_issue_rows_from_payload')) {
    $tx_step = 'upsert_diamond_issue_rows_from_payload';
    $diamond_issue_upserted = mp_jwq_upsert_diamond_issue_rows_from_payload(
        $conn,
        $jobwork_order_id,
        $diamond_stock_rows,
        $first_item_id,
        $from_dept_for_loss,
        $to_dept,
        $from_user_for_loss,
        $to_user,
        $tx_ok,
        $tx_err,
        $last_insert_error
    );
}
if ($tx_ok) {
    $calculated_diamond_weight = mp_jwq_recalculate_line_diamond_weights($conn, $jobwork_order_id);
}

if ($tx_ok) {
    $parts = [];
    if ($is_transfer) {
        $parts[] = 'department_id = ' . $to_dept;
    } elseif ($from_dept_for_loss > 0) {
        $parts[] = 'department_id = ' . $from_dept_for_loss;
    }
    if ($has_user) {
        if ($is_transfer && $to_user > 0) {
            $parts[] = 'department_user_id = ' . $to_user;
        } elseif (!$is_transfer && $from_user_for_loss > 0) {
            $parts[] = 'department_user_id = ' . $from_user_for_loss;
        } else {
            $parts[] = 'department_user_id = NULL';
        }
    }
    if (!empty($parts)) {
        $tx_step = 'update_jobwork_order_department_user';
        $sql = 'UPDATE tbl_jobwork_orders SET ' . implode(', ', $parts) . ' WHERE id = ' . $jobwork_order_id;
        if (!@mysqli_query($conn, $sql)) {
            $tx_ok = false;
            $tx_err = 'Could not update job work order. DB: ' . mysqli_error($conn);
        }
    }
}

$jwo_row = $jwo_before;
if ($tx_ok && $is_transfer && $jwo_row && !empty($jwo_row['sale_order_id'])) {
    $soid = (int)$jwo_row['sale_order_id'];
    $cd_so = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_sale_orders LIKE 'department_id'");
    if ($cd_so && mysqli_num_rows($cd_so) > 0) {
        mysqli_free_result($cd_so);
        @mysqli_query($conn, 'UPDATE tbl_sale_orders SET department_id = ' . (int)$to_dept . ' WHERE id = ' . $soid);
    } elseif ($cd_so) {
        mysqli_free_result($cd_so);
    }
}

if ($tx_ok) {
    $jwo_row = function_exists('getRecord') ? getRecord('SELECT sale_order_id, jobwork_queue_no FROM tbl_jobwork_orders WHERE id = ' . (int)$jobwork_order_id . ' LIMIT 1') : $jwo_before;
}

if ($jwo_row && isset($jwo_row['jobwork_queue_no'])) {
    $queue_no = trim((string)$jwo_row['jobwork_queue_no']);
}

$dept_for_resp = $is_transfer ? (int)$to_dept : (int)$from_dept_for_loss;
$user_for_resp = $is_transfer ? (int)$to_user : (int)$from_user_for_loss;
$dept_name = '';
$dr = function_exists('getRecord') ? getRecord('SELECT dept_name FROM tbl_departments WHERE id = ' . $dept_for_resp . ' LIMIT 1') : null;
if ($dr && isset($dr['dept_name'])) {
    $dept_name = trim((string)$dr['dept_name']);
}

$worker_name = '';
if ($user_for_resp > 0) {
    $wr = function_exists('getRecord') ? getRecord('SELECT name FROM tbl_customers WHERE id = ' . $user_for_resp . ' LIMIT 1') : null;
    if ($wr && isset($wr['name'])) {
        $worker_name = trim((string)$wr['name']);
    }
}

$qn_log = ($queue_no !== null && $queue_no !== '') ? trim((string)$queue_no) : '';
if ($qn_log === '' && $jwo_row && isset($jwo_row['jobwork_queue_no'])) {
    $qn_log = trim((string)$jwo_row['jobwork_queue_no']);
}

$act_chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
if (!$act_chk || mysqli_num_rows($act_chk) === 0) {
    if ($act_chk) {
        mysqli_free_result($act_chk);
    }
    @mysqli_query($conn, 'CREATE TABLE IF NOT EXISTS `tbl_jobwork_queue_activity` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `jobwork_order_id` int(11) NOT NULL,
      `jobwork_queue_no` varchar(50) NOT NULL DEFAULT \'\',
      `from_dept_id` int(11) DEFAULT NULL,
      `from_user_id` int(11) DEFAULT NULL,
      `to_dept_id` int(11) DEFAULT NULL,
      `to_user_id` int(11) DEFAULT NULL,
      `activity_action` varchar(32) DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `jobwork_order_id` (`jobwork_order_id`),
      KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
} elseif ($act_chk) {
    mysqli_free_result($act_chk);
}
$aca = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_queue_activity LIKE 'activity_action'");
if (!$aca || mysqli_num_rows($aca) === 0) {
    @mysqli_query($conn, 'ALTER TABLE tbl_jobwork_queue_activity ADD COLUMN activity_action varchar(32) DEFAULT NULL COMMENT \'jobwork_create|department_transfer\' AFTER to_user_id');
}
if ($aca) {
    mysqli_free_result($aca);
}
$acf = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_queue_activity LIKE 'from_dept_id'");
if (!$acf || mysqli_num_rows($acf) === 0) {
    @mysqli_query($conn, 'ALTER TABLE tbl_jobwork_queue_activity ADD COLUMN from_dept_id int(11) DEFAULT NULL AFTER jobwork_queue_no, ADD COLUMN from_user_id int(11) DEFAULT NULL AFTER from_dept_id');
}
if ($acf) {
    mysqli_free_result($acf);
}

$act_twcol = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_queue_activity LIKE 'total_wt_after'");
if (!$act_twcol || mysqli_num_rows($act_twcol) === 0) {
    @mysqli_query(
        $conn,
        "ALTER TABLE tbl_jobwork_queue_activity ADD COLUMN total_wt_after decimal(12,4) DEFAULT NULL COMMENT 'Line weight total after transfer' AFTER activity_action, "
        . 'ADD COLUMN total_qty_after decimal(12,4) DEFAULT NULL AFTER total_wt_after'
    );
}
if ($act_twcol) {
    mysqli_free_result($act_twcol);
}

$from_dept_logged = (int)($jwo_before['department_id'] ?? 0);
$from_user_logged = isset($jwo_before['department_user_id']) && $jwo_before['department_user_id'] !== null && $jwo_before['department_user_id'] !== ''
    ? (int)$jwo_before['department_user_id'] : 0;

if ($tx_ok) {
    $joid = (int)$jobwork_order_id;
    if ($is_transfer && function_exists('getNextJobworkQueueNo')) {
        $ptc = function_exists('getRecord')
            ? getRecord(
                'SELECT COUNT(*) AS c FROM tbl_jobwork_queue_activity WHERE jobwork_order_id = ' . $joid
                . " AND LOWER(TRIM(IFNULL(activity_action,''))) = 'department_transfer'"
            )
            : null;
        $prior_transfer_ct = ($ptc && isset($ptc['c'])) ? (int)$ptc['c'] : 0;
        if ($prior_transfer_ct > 0) {
            $new_qn = getNextJobworkQueueNo($conn);
            $esc_n = mysqli_real_escape_string($conn, $new_qn);
            if (@mysqli_query($conn, "UPDATE tbl_jobwork_orders SET jobwork_queue_no = '" . $esc_n . "' WHERE id = " . $joid)) {
                $qn_log = $new_qn;
                $queue_no = $new_qn;
            }
        }
    }
    $qn_esc = mysqli_real_escape_string($conn, $qn_log);
    $fd_sql = $from_dept_logged > 0 ? (string)(int)$from_dept_logged : 'NULL';
    $fu_sql = $from_user_logged > 0 ? (string)(int)$from_user_logged : 'NULL';
    $td_sql = $to_dept > 0 ? (string)(int)$to_dept : 'NULL';
    $tu_sql = $to_user > 0 ? (string)(int)$to_user : 'NULL';
    $act_name = $is_transfer ? 'department_transfer' : 'jobwork_create';
    $tw_ins = 'NULL';
    $tq_ins = 'NULL';
    if ($is_transfer) {
        list($sw_act, $sq_act) = mp_jwq_totals_from_items($conn, $jobwork_order_id, $ji_cols);
        if ($sw_act > 0.0000001) {
            $tw_ins = (string)round($sw_act, 4);
        }
        if ($sq_act > 0.0000001) {
            $tq_ins = (string)round($sq_act, 4);
        }
    }
    $tx_step = 'insert_jobwork_queue_activity';
    $ins_act = 'INSERT INTO tbl_jobwork_queue_activity (jobwork_order_id, jobwork_queue_no, from_dept_id, from_user_id, to_dept_id, to_user_id, activity_action, total_wt_after, total_qty_after) VALUES ('
        . $joid . ', \'' . $qn_esc . '\', ' . $fd_sql . ', ' . $fu_sql . ', ' . $td_sql . ', ' . $tu_sql . ", '" . $act_name . "', "
        . $tw_ins . ', ' . $tq_ins . ')';
    if (!@mysqli_query($conn, $ins_act)) {
        $tx_ok = false;
        $tx_err = 'Could not log queue activity. DB: ' . mysqli_error($conn);
    }
}

if ($use_tx) {
    if ($tx_ok) {
        @mysqli_commit($conn);
    } else {
        @mysqli_rollback($conn);
    }
}

$saved_diamond_rows_report = !empty($diamond_stock_rows) ? (int) $diamond_issue_upserted : (isset($diamond_stats['saved_rows']) ? (int) $diamond_stats['saved_rows'] : 0);
$saved_diamond_barcodes = [];
if ($tx_ok && function_exists('getList')) {
    $bcr = getList("SELECT DISTINCT TRIM(IFNULL(barcode,'')) AS b FROM `$issue_tbl` WHERE jobwork_order_id = " . (int) $jobwork_order_id . " AND stock_id > 0 AND TRIM(IFNULL(barcode,'')) <> '' ORDER BY b");
    if (is_array($bcr)) {
        foreach ($bcr as $br) {
            if (!is_array($br)) {
                continue;
            }
            $b = trim((string) ($br['b'] ?? ''));
            if ($b !== '') {
                $saved_diamond_barcodes[] = $b;
            }
        }
    }
}

if (!$tx_ok) {
    if ($tx_err === '') {
        $tx_err = 'Save failed at step: ' . $tx_step;
    }
    $last_db_err = function_exists('mp_jwq_get_last_db_error') ? mp_jwq_get_last_db_error() : '';
    if ($last_db_err === '') {
        $last_db_err = trim((string) mysqli_error($conn));
    }
    mp_jwq_json_out([
        'ok' => false,
        'message' => $tx_err,
        'debug_step' => $tx_step,
        'received_diamond_raw' => $diamond_stock_raw,
        'received_diamond_count' => is_array($diamond_stock_rows) ? count($diamond_stock_rows) : 0,
        'duplicate_stock_ids' => $diamond_duplicate_stock_ids,
        'saved_diamond_rows' => $saved_diamond_rows_report,
        'saved_diamond_barcodes' => [],
        'last_insert_error' => $last_insert_error,
        'calculated_diamond_weight' => is_array($calculated_diamond_weight) ? $calculated_diamond_weight : [],
        'last_db_error' => $last_db_err,
    ]);
}

$sale_order_id_out = 0;
if ($jwo_row && isset($jwo_row['sale_order_id'])) {
    $sale_order_id_out = (int)$jwo_row['sale_order_id'];
}

$jwo_total_wt_out = 'NA';
$wt_parts = [];
if (!empty($ji_cols['final_weight'])) {
    $wt_parts[] = 'NULLIF(ji.final_weight,0)';
}
if (!empty($ji_cols['net_weight'])) {
    $wt_parts[] = 'NULLIF(ji.net_weight,0)';
}
if (!empty($ji_cols['gross_weight'])) {
    $wt_parts[] = 'NULLIF(ji.gross_weight,0)';
}
if (!empty($wt_parts)) {
    $wt_inner = 'COALESCE(' . implode(', ', $wt_parts) . ', 0)';
    $sumr = function_exists('getRecord')
        ? getRecord('SELECT COALESCE(SUM(' . $wt_inner . '), 0) AS w FROM tbl_jobwork_order_items ji WHERE ji.jobwork_order_id = ' . (int)$jobwork_order_id)
        : null;
    if ($sumr && isset($sumr['w'])) {
        $wval = (float)$sumr['w'];
        if ($wval > 0) {
            $jwo_total_wt_out = rtrim(rtrim(number_format($wval, 3, '.', ''), '0'), '.');
            if ($jwo_total_wt_out === '') {
                $jwo_total_wt_out = '0';
            }
        }
    }
}

$jwo_floor_transfer_ct = 0;
$tchk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_queue_activity'");
if ($tchk && mysqli_num_rows($tchk) > 0) {
    mysqli_free_result($tchk);
    $tc = function_exists('getRecord')
        ? getRecord(
            'SELECT COUNT(*) AS c FROM tbl_jobwork_queue_activity WHERE jobwork_order_id = ' . (int)$jobwork_order_id
            . " AND (LOWER(TRIM(IFNULL(activity_action,''))) = 'department_transfer'"
            . " OR ((activity_action IS NULL OR TRIM(IFNULL(activity_action,'')) = '')"
            . ' AND IFNULL(from_dept_id,0) > 0'
            . ' AND (IFNULL(from_dept_id,0) <> IFNULL(to_dept_id,0) OR IFNULL(from_user_id,0) <> IFNULL(to_user_id,0))))'
        )
        : null;
    if ($tc && isset($tc['c'])) {
        $jwo_floor_transfer_ct = (int)$tc['c'];
    }
} elseif ($tchk) {
    mysqli_free_result($tchk);
}
if ($jwo_floor_transfer_ct < 1) {
    $jwo_total_wt_out = 'NA';
}

$jwo_card_secondary_out = 'NA';
if ($jwo_floor_transfer_ct > 0) {
    $ji0 = function_exists('getRecord')
        ? getRecord('SELECT purity, carat FROM tbl_jobwork_order_items WHERE jobwork_order_id = ' . (int)$jobwork_order_id . ' ORDER BY id ASC LIMIT 1')
        : null;
    if ($ji0 && is_array($ji0)) {
        $lp0 = isset($ji0['purity']) ? (float)$ji0['purity'] : 0.0;
        if ($lp0 > 0.0000001) {
            $jwo_card_secondary_out = rtrim(rtrim(number_format($lp0, 2, '.', ''), '0'), '.');
            if ($jwo_card_secondary_out === '') {
                $jwo_card_secondary_out = 'NA';
            }
        } else {
            $lc0 = isset($ji0['carat']) ? trim((string)$ji0['carat']) : '';
            $jwo_card_secondary_out = $lc0 !== '' ? $lc0 : 'NA';
        }
    }
}

$last_db_ok = function_exists('mp_jwq_get_last_db_error') ? mp_jwq_get_last_db_error() : '';

mp_jwq_json_out([
    'ok' => true,
    'jobwork_queue_no' => $queue_no !== null && $queue_no !== '' ? $queue_no : '',
    'sale_order_id' => $sale_order_id_out,
    'message' => $is_transfer ? 'Transfer saved.' : 'Job work queue saved.',
    'transferred' => $is_transfer,
    'department_id' => $dept_for_resp,
    'department_user_id' => $user_for_resp > 0 ? $user_for_resp : 0,
    'dept_name' => $dept_name,
    'worker_name' => $worker_name,
    'jwo_total_wt' => $jwo_total_wt_out,
    'jwo_has_floor_transfer' => $jwo_floor_transfer_ct > 0 ? 1 : 0,
    'jwo_card_secondary' => $jwo_card_secondary_out,
    'received_diamond_raw' => $diamond_stock_raw,
    'received_diamond_count' => is_array($diamond_stock_rows) ? count($diamond_stock_rows) : 0,
    'duplicate_stock_ids' => $diamond_duplicate_stock_ids,
    'saved_diamond_rows' => $saved_diamond_rows_report,
    'saved_diamond_barcodes' => $saved_diamond_barcodes,
    'last_insert_error' => $last_insert_error,
    'calculated_diamond_weight' => $calculated_diamond_weight,
    'last_db_error' => $last_db_ok,
    'excluded_stock_ids' => isset($diamond_stats['excluded_stock_ids']) && is_array($diamond_stats['excluded_stock_ids']) ? array_values(array_map('intval', $diamond_stats['excluded_stock_ids'])) : [],
]);
