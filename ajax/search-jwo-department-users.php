<?php
/**
 * Search ledger / department users for Job Work Order "Name" field (autocomplete).
 * Same scope as get-department-users.php: all active customers when all_ledgers=1,
 * else job workers mapped to department (when map table exists).
 */
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$search_term = isset($_GET['q']) ? esc($_GET['q']) : '';
$department_id = isset($_GET['department_id']) ? (int) $_GET['department_id'] : 0;
$all_ledgers = isset($_GET['all_ledgers']) && (string) $_GET['all_ledgers'] === '1';

if (strlen($search_term) < 2) {
    echo json_encode(['status' => 'success', 'users' => []]);
    exit;
}

$cust_tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_customers'");
if (!$cust_tbl || mysqli_num_rows($cust_tbl) === 0) {
    if ($cust_tbl) {
        mysqli_free_result($cust_tbl);
    }
    echo json_encode(['status' => 'success', 'users' => [], 'message' => 'Customers table missing']);
    exit;
}
mysqli_free_result($cust_tbl);

$like = $search_term;

$job_worker_type_id = 0;
$jw_result = @mysqli_query($conn, "SELECT id FROM tbl_customer_types WHERE LOWER(name) = 'job worker' AND status = 1 LIMIT 1");
if ($jw_result && mysqli_num_rows($jw_result) > 0) {
    $jw_row = mysqli_fetch_assoc($jw_result);
    $job_worker_type_id = (int) $jw_row['id'];
    mysqli_free_result($jw_result);
} elseif ($jw_result) {
    mysqli_free_result($jw_result);
}

$type_sql = ($job_worker_type_id > 0) ? ' AND c.customer_type_id = ' . (int) $job_worker_type_id : '';

$search_sql = " AND (c.name LIKE '%$like%' OR IFNULL(c.alternate_name,'') LIKE '%$like%' OR IFNULL(c.mobile_no,'') LIKE '%$like%' "
    . "OR IFNULL(c.mail_id,'') LIKE '%$like%' OR IFNULL(c.first_name,'') LIKE '%$like%' OR IFNULL(c.last_name,'') LIKE '%$like%') ";

if ($all_ledgers || $department_id < 1) {
    $users = getList(
        "SELECT c.id, c.name AS user_name, c.mobile_no, c.alternate_name FROM tbl_customers c "
        . "WHERE c.status = 1" . $search_sql
        . "ORDER BY c.name ASC LIMIT 30"
    );
} else {
    $map_tbl = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_department_user_map'");
    if (!$map_tbl || mysqli_num_rows($map_tbl) === 0) {
        if ($map_tbl) {
            mysqli_free_result($map_tbl);
        }
        echo json_encode(['status' => 'success', 'users' => [], 'message' => 'Department user map missing']);
        exit;
    }
    mysqli_free_result($map_tbl);

    $did = (int) $department_id;
    $users = getList(
        "SELECT c.id, c.name AS user_name, c.mobile_no, c.alternate_name FROM tbl_customers c "
        . "INNER JOIN tbl_department_user_map m ON c.id = m.user_id AND m.status = 1 "
        . "WHERE m.department_id = $did AND c.status = 1"
        . $type_sql
        . $search_sql
        . "ORDER BY c.name ASC LIMIT 30"
    );
}

if (!is_array($users)) {
    $users = [];
}

echo json_encode(['status' => 'success', 'users' => $users]);
