<?php
/**
 * Save add / reduce weight line against a job work order (Manufacturing / Jobwork Queue).
 */
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request']);
    exit;
}

$jobwork_order_id = isset($_POST['jobwork_order_id']) ? (int)$_POST['jobwork_order_id'] : 0;
$type_raw = isset($_POST['adjustment_type']) ? strtolower(trim((string)$_POST['adjustment_type'])) : '';
$adjustment_type = ($type_raw === 'add') ? 'add' : 'reduce';
$weight_raw = isset($_POST['weight_grams']) ? trim((string)$_POST['weight_grams']) : '';
$remark = isset($_POST['remark']) ? trim((string)$_POST['remark']) : '';
$from_dept_id = isset($_POST['from_dept_id']) ? (int)$_POST['from_dept_id'] : 0;
$from_user_id = isset($_POST['from_user_id']) ? (int)$_POST['from_user_id'] : 0;
$to_dept_id = isset($_POST['to_dept_id']) ? (int)$_POST['to_dept_id'] : 0;
$to_user_id = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;

if ($jobwork_order_id < 1) {
    echo json_encode(['ok' => false, 'message' => 'Job work order is required.']);
    exit;
}

$weight = (float)$weight_raw;
if (!is_finite($weight) || $weight <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Enter a weight greater than zero.']);
    exit;
}
if ($weight > 999999.9999) {
    $weight = 999999.9999;
}
if ($adjustment_type === 'add') {
    if ($from_dept_id < 1) {
        echo json_encode(['ok' => false, 'message' => 'Please select From Dept.']);
        exit;
    }
    if ($to_dept_id < 1) {
        echo json_encode(['ok' => false, 'message' => 'Please select To Dept.']);
        exit;
    }
    if ($from_dept_id === $to_dept_id && $from_user_id === $to_user_id) {
        echo json_encode(['ok' => false, 'message' => 'From and To department/user cannot be the same.']);
        exit;
    }
}

$tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_orders'");
if (!$tbl || mysqli_num_rows($tbl) === 0) {
    if ($tbl) {
        mysqli_free_result($tbl);
    }
    echo json_encode(['ok' => false, 'message' => 'Job work orders table not found']);
    exit;
}
mysqli_free_result($tbl);

$exists = @mysqli_query($conn, 'SELECT id FROM tbl_jobwork_orders WHERE id = ' . $jobwork_order_id . ' LIMIT 1');
if (!$exists || mysqli_num_rows($exists) === 0) {
    if ($exists) {
        mysqli_free_result($exists);
    }
    echo json_encode(['ok' => false, 'message' => 'Job work order not found.']);
    exit;
}
mysqli_free_result($exists);

$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_jobwork_weight_adjustments'");
if (!$chk || mysqli_num_rows($chk) === 0) {
    if ($chk) {
        mysqli_free_result($chk);
    }
    $create = "CREATE TABLE IF NOT EXISTS `tbl_jobwork_weight_adjustments` (
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
      KEY `adjustment_type` (`adjustment_type`),
      KEY `source_department_id` (`source_department_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!@mysqli_query($conn, $create)) {
        echo json_encode(['ok' => false, 'message' => 'Could not create weight adjustments table. Run admin/sql/create_tbl_jobwork_weight_adjustments.sql']);
        exit;
    }
} else {
    mysqli_free_result($chk);
}

$source_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_weight_adjustments LIKE 'source_department_id'");
if (!$source_col || mysqli_num_rows($source_col) === 0) {
    if ($source_col) {
        mysqli_free_result($source_col);
    }
    $alter = 'ALTER TABLE tbl_jobwork_weight_adjustments'
        . ' ADD COLUMN source_department_id int(11) DEFAULT NULL AFTER created_by_user_id,'
        . ' ADD COLUMN source_user_id int(11) DEFAULT NULL AFTER source_department_id,'
        . ' ADD KEY source_department_id (source_department_id)';
    if (!@mysqli_query($conn, $alter)) {
        echo json_encode(['ok' => false, 'message' => 'Could not prepare department stock fields.']);
        exit;
    }
} elseif ($source_col) {
    mysqli_free_result($source_col);
}
$source_user_col = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_jobwork_weight_adjustments LIKE 'source_user_id'");
if (!$source_user_col || mysqli_num_rows($source_user_col) === 0) {
    if ($source_user_col) {
        mysqli_free_result($source_user_col);
    }
    if (!@mysqli_query($conn, 'ALTER TABLE tbl_jobwork_weight_adjustments ADD COLUMN source_user_id int(11) DEFAULT NULL AFTER source_department_id')) {
        echo json_encode(['ok' => false, 'message' => 'Could not prepare department user stock field.']);
        exit;
    }
} elseif ($source_user_col) {
    mysqli_free_result($source_user_col);
}

$uid = 0;
if (!empty($_SESSION['Admin']['id'])) {
    $uid = (int)$_SESSION['Admin']['id'];
} elseif (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
}

$remark_db = $remark === '' ? '' : $remark;

$insert_adjustment = static function (
    mysqli $db,
    int $jwo_id,
    string $type,
    float $grams,
    string $note,
    int $created_by,
    int $dept_id,
    int $user_id
): array {
    $stmt = mysqli_prepare(
        $db,
        'INSERT INTO tbl_jobwork_weight_adjustments'
        . ' (jobwork_order_id, adjustment_type, weight_grams, remark, created_by_user_id, source_department_id, source_user_id)'
        . ' VALUES (?, ?, ?, ?, NULLIF(?, 0), NULLIF(?, 0), NULLIF(?, 0))'
    );
    if (!$stmt) {
        return [false, 0, mysqli_error($db)];
    }
    mysqli_stmt_bind_param($stmt, 'isdsiii', $jwo_id, $type, $grams, $note, $created_by, $dept_id, $user_id);
    $inserted = mysqli_stmt_execute($stmt);
    $insert_id = $inserted ? mysqli_insert_id($db) : 0;
    $insert_error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    return [$inserted, $insert_id, $insert_error];
};

mysqli_begin_transaction($conn);
$ok = true;
$err = '';
$new_id = 0;
$outward_id = 0;

if ($adjustment_type === 'add') {
    $out_note = 'Add weight transfer outward to department ' . $to_dept_id;
    $in_note = 'Add weight transfer inward from department ' . $from_dept_id;
    if ($remark_db !== '') {
        $out_note .= ': ' . $remark_db;
        $in_note .= ': ' . $remark_db;
    }
    [$ok, $outward_id, $err] = $insert_adjustment(
        $conn, $jobwork_order_id, 'reduce', $weight, $out_note, $uid, $from_dept_id, $from_user_id
    );
    if ($ok) {
        [$ok, $new_id, $err] = $insert_adjustment(
            $conn, $jobwork_order_id, 'add', $weight, $in_note, $uid, $to_dept_id, $to_user_id
        );
    }
} else {
    $reduce_dept_id = $from_dept_id;
    if ($reduce_dept_id < 1) {
        $jwo = @mysqli_query($conn, 'SELECT department_id, department_user_id FROM tbl_jobwork_orders WHERE id = ' . $jobwork_order_id . ' LIMIT 1');
        if ($jwo && ($jwo_row = mysqli_fetch_assoc($jwo))) {
            $reduce_dept_id = (int)($jwo_row['department_id'] ?? 0);
            if ($from_user_id < 1) {
                $from_user_id = (int)($jwo_row['department_user_id'] ?? 0);
            }
        }
        if ($jwo) {
            mysqli_free_result($jwo);
        }
    }
    [$ok, $new_id, $err] = $insert_adjustment(
        $conn, $jobwork_order_id, 'reduce', $weight, $remark_db, $uid, $reduce_dept_id, $from_user_id
    );
}

if (!$ok) {
    mysqli_rollback($conn);
    echo json_encode(['ok' => false, 'message' => $err !== '' ? $err : 'Save failed']);
    exit;
}
mysqli_commit($conn);

echo json_encode([
    'ok' => true,
    'message' => $adjustment_type === 'add' ? 'Weight transferred: source outward and destination inward saved.' : 'Reduce weight saved.',
    'id' => (int)$new_id,
    'outward_id' => (int)$outward_id,
    'adjustment_type' => $adjustment_type,
    'weight_grams' => $weight,
]);
