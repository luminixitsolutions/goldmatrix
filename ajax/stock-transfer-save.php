<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/stock_transfer_pending_schema.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
    exit;
}

$from_branch = isset($payload['from_branch_id']) ? (int) $payload['from_branch_id'] : 0;
$to_branch   = isset($payload['to_branch_id']) ? (int) $payload['to_branch_id'] : 0;
$transfer_date = isset($payload['transfer_date']) ? trim((string) $payload['transfer_date']) : '';
$stock_ids   = isset($payload['stock_ids']) && is_array($payload['stock_ids']) ? $payload['stock_ids'] : [];

if ($from_branch <= 0 || $to_branch <= 0) {
    echo json_encode(['success' => false, 'message' => 'Select source and destination branch.']);
    exit;
}
if ($from_branch === $to_branch) {
    echo json_encode(['success' => false, 'message' => 'Source and destination branch must be different.']);
    exit;
}
if (empty($stock_ids)) {
    echo json_encode(['success' => false, 'message' => 'No items to transfer.']);
    exit;
}

if ($transfer_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transfer_date)) {
    $transfer_date = date('Y-m-d');
}

$has_sj = false;
$sj_check = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'stock_journal_id'");
if ($sj_check && mysqli_num_rows($sj_check) > 0) {
    $has_sj = true;
}
if ($sj_check) {
    mysqli_free_result($sj_check);
}

$has_reference = false;
$ref_chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock WHERE Field IN ('reference_id','reference_type')");
if ($ref_chk && mysqli_num_rows($ref_chk) >= 2) {
    $has_reference = true;
}
if ($ref_chk) {
    mysqli_free_result($ref_chk);
}

$has_status_col = false;
$st_chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'status'");
if ($st_chk && mysqli_num_rows($st_chk) > 0) {
    $has_status_col = true;
}
if ($st_chk) {
    mysqli_free_result($st_chk);
}

$has_updated_at = false;
$ua_chk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'updated_at'");
if ($ua_chk && mysqli_num_rows($ua_chk) > 0) {
    $has_updated_at = true;
}
if ($ua_chk) {
    mysqli_free_result($ua_chk);
}

// Optional second database: duplicate staging rows only if AURAGOLD_MIRROR_STOCK_DB is set (e.g. auragold_branch1).
// Default is off — transfers stay in this connection's database; destination is tbl_stock_transfer_pending.to_branch_id / receive uses branch_id on tbl_stock.
$mirrorDbRaw = getenv('AURAGOLD_MIRROR_STOCK_DB');
$mirrorDbName = ($mirrorDbRaw === false || $mirrorDbRaw === null) ? '' : trim((string) $mirrorDbRaw);
if ($mirrorDbName === '' || strcasecmp($mirrorDbName, 'none') === 0 || strcasecmp($mirrorDbName, 'off') === 0) {
    $mirrorDbName = '';
}

$mirror_ops = [];

if (!auragold_ensure_stock_transfer_pending_table($conn)) {
    echo json_encode(['success' => false, 'message' => 'Could not create tbl_stock_transfer_pending: ' . mysqli_error($conn)]);
    exit;
}

mysqli_begin_transaction($conn);

try {
    $processed = 0;
    foreach ($stock_ids as $sid) {
        $stock_id = (int) $sid;
        if ($stock_id <= 0) {
            continue;
        }

        $lock_sql = 'SELECT * FROM tbl_stock WHERE id = ' . $stock_id . ($has_status_col ? ' AND status = 1' : '') . ' FOR UPDATE';
        $lock_q = mysqli_query($conn, $lock_sql);
        $stock_row = ($lock_q && mysqli_num_rows($lock_q) > 0) ? mysqli_fetch_assoc($lock_q) : null;
        if ($lock_q) {
            mysqli_free_result($lock_q);
        }
        if (!$stock_row) {
            throw new Exception('Stock row not found: ' . $stock_id);
        }
        $src_branch_id = isset($stock_row['branch_id']) && $stock_row['branch_id'] !== null && $stock_row['branch_id'] !== ''
            ? (int) $stock_row['branch_id'] : 0;
        if ($src_branch_id !== 0 && $src_branch_id !== $from_branch) {
            throw new Exception('Stock #' . $stock_id . ' is not at the source branch.');
        }
        if (in_array($stock_row['stock_type'] ?? '', ['outward'], true)) {
            throw new Exception('Invalid stock type for transfer: #' . $stock_id);
        }

        $ow_prod_id = (int) $stock_row['product_id'];
        $ow_char_id = (isset($stock_row['product_characteristic_id']) && $stock_row['product_characteristic_id'] !== '' && $stock_row['product_characteristic_id'] !== null)
            ? (int) $stock_row['product_characteristic_id'] : null;
        $ow_barcode_esc = mysqli_real_escape_string($conn, (string) ($stock_row['barcode'] ?? ''));
        $ow_metal_id = (int) ($stock_row['metal_id'] ?? 0);
        if ($ow_metal_id <= 0) {
            $ow_metal_id = 1;
        }
        $ow_purity = (float) ($stock_row['opening_purity'] ?? 100);
        if ($ow_purity <= 0) {
            $ow_purity = 100.0;
        }
        $ow_rate = (float) ($stock_row['rate'] ?? 0);
        $cw = (float) ($stock_row['current_weight'] ?? 0);
        $cq = (float) ($stock_row['current_qty'] ?? 0);
        $move_wt = $cw > 0 ? $cw : (float) ($stock_row['opening_weight'] ?? 0);
        $move_qty = $cq > 0 ? $cq : (float) ($stock_row['opening_qty'] ?? 1);
        if ($move_wt <= 0) {
            $move_wt = (float) ($stock_row['final_weight'] ?? 0);
        }
        if ($move_qty <= 0 && $move_wt > 0) {
            $move_qty = 1;
        }
        if ($move_wt <= 0 && $move_qty <= 0) {
            throw new Exception('Stock #' . $stock_id . ' has no available quantity.');
        }
        $ow_value = (float) ($stock_row['value'] ?? 0);
        if ($ow_value <= 0 && $ow_rate > 0 && $move_wt > 0) {
            $ow_value = $ow_rate * $move_wt;
        }

        $td_esc = mysqli_real_escape_string($conn, $transfer_date);

        // Outward at source branch (full line movement)
        $ow_cols = "product_id, product_characteristic_id, barcode, branch_id, metal_id, opening_weight, opening_purity, opening_qty, final_weight, rate, value, current_weight, current_qty, stock_type, transaction_date, created_at";
        $char_sql = $ow_char_id !== null ? (string) $ow_char_id : 'NULL';
        $ow_vals = "$ow_prod_id, $char_sql, '$ow_barcode_esc', $from_branch, $ow_metal_id, $move_wt, $ow_purity, $move_qty, $move_wt, $ow_rate, $ow_value, $move_wt, $move_qty, 'outward', '$td_esc', NOW()";
        if ($has_status_col) {
            $ow_cols .= ", status";
            $ow_vals .= ", 1";
        }
        if ($has_sj) {
            $ow_cols .= ", stock_journal_id";
            $ow_vals .= ", NULL";
        }
        if ($has_reference) {
            $ow_cols .= ", reference_id, reference_type";
            $ow_vals .= ", NULL, 'stock_transfer'";
        }
        if (!mysqli_query($conn, "INSERT INTO tbl_stock ($ow_cols) VALUES ($ow_vals)")) {
            throw new Exception('Outward insert failed: ' . mysqli_error($conn));
        }

        $outward_id = (int) mysqli_insert_id($conn);

        $src_id = (int) $stock_row['id'];
        // Clear opening/final on source so the line cannot be listed for transfer again (matches list filter).
        $upd_src = "UPDATE tbl_stock SET current_weight = 0, current_qty = 0, opening_weight = 0, opening_qty = 0, final_weight = 0, value = 0" . ($has_updated_at ? ", updated_at = NOW()" : "") . " WHERE id = $src_id";
        if (!mysqli_query($conn, $upd_src)) {
            throw new Exception('Source stock update failed: ' . mysqli_error($conn));
        }

        // Stage in tbl_stock_transfer_pending — destination tbl_stock is created on Receive only.
        $barcode_sql = (($stock_row['barcode'] ?? '') === '') ? 'NULL' : "'" . $ow_barcode_esc . "'";
        $pending_sql = "
            INSERT INTO tbl_stock_transfer_pending (
                from_branch_id, to_branch_id, product_id, product_characteristic_id, barcode, metal_id, opening_purity,
                move_qty, move_wt, rate, value, transfer_date, source_stock_id, outward_stock_id, status
            ) VALUES (
                $from_branch, $to_branch, $ow_prod_id, $char_sql, $barcode_sql, $ow_metal_id, $ow_purity,
                $move_qty, $move_wt, $ow_rate, $ow_value, '$td_esc', $src_id, $outward_id, 'pending'
            )
        ";
        if (!mysqli_query($conn, $pending_sql)) {
            throw new Exception('Transfer staging (tbl_stock_transfer_pending) failed: ' . mysqli_error($conn));
        }

        if ($mirrorDbName !== '' && strcasecmp($mirrorDbName, (string) (defined('DB_NAME') ? DB_NAME : '')) !== 0) {
            $mirror_ops[] = ['pending' => preg_replace('/\s+/', ' ', trim($pending_sql))];
        }

        $processed++;
    }

    if ($processed === 0) {
        throw new Exception('No valid stock lines processed.');
    }

    mysqli_commit($conn);

    $mirrorWarning = '';
    if ($mirrorDbName !== '' && strcasecmp($mirrorDbName, (string) (defined('DB_NAME') ? DB_NAME : '')) !== 0 && !empty($mirror_ops)) {
        $mconn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, $mirrorDbName);
        if ($mconn) {
            mysqli_set_charset($mconn, 'utf8mb4');
            auragold_ensure_stock_transfer_pending_table($mconn);
            mysqli_begin_transaction($mconn);
            try {
                foreach ($mirror_ops as $op) {
                    if (empty($op['pending']) || !mysqli_query($mconn, $op['pending'])) {
                        throw new Exception('tbl_stock_transfer_pending: ' . mysqli_error($mconn));
                    }
                }
                mysqli_commit($mconn);
            } catch (Exception $me) {
                mysqli_rollback($mconn);
                $mirrorWarning = 'Also saved to ' . $mirrorDbName . ' failed: ' . $me->getMessage();
            }
            mysqli_close($mconn);
        } else {
            $mirrorWarning = 'Also saved to ' . $mirrorDbName . ' failed: could not connect (' . mysqli_connect_error() . ')';
        }
    }

    $dbLabel = defined('DB_NAME') ? (string) DB_NAME : '';
    $out = [
        'success'   => true,
        'message'   => 'Transferred ' . $processed . ' item(s). Staged in tbl_stock_transfer_pending — receive at Stock Receive History to post into tbl_stock.',
        'count'     => $processed,
        'database'  => $dbLabel,
    ];
    if ($mirrorDbName !== '' && strcasecmp($mirrorDbName, $dbLabel) !== 0) {
        $out['mirror_database'] = $mirrorDbName;
        $out['mirror_attempted'] = !empty($mirror_ops);
        if ($mirrorWarning !== '') {
            $out['mirror_warning'] = $mirrorWarning;
        }
    }
    echo json_encode($out);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
