<?php
session_start();
require_once '../config.php';
require_once __DIR__ . '/../includes/auragold_require_login.php';
auragold_require_login_or_exit();
require_once __DIR__ . '/../includes/location-helpers.php';

header('Content-Type: application/json');

auragold_ensure_customer_ledger_location_columns($conn);

$search_term = isset($_GET['q']) ? esc($_GET['q']) : '';
$branch_id_hint = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
if ($branch_id_hint <= 0 && function_exists('auragold_effective_branch_id')) {
    $branch_id_hint = (int) auragold_effective_branch_id();
}
$branch_label = '';
if ($branch_id_hint > 0 && isset($conn_master) && $conn_master instanceof mysqli) {
    $br = @getRecordMaster('SELECT name FROM tbl_branches WHERE id = ' . (int) $branch_id_hint . ' LIMIT 1');
    if ($br && !empty($br['name'])) {
        $branch_label = trim((string) $br['name']);
    }
}

if (strlen($search_term) < 2) {
    echo json_encode(['status' => 'success', 'customers' => []]);
    exit;
}

// Search customers by name, alternate_name, mobile_no, or mail_id
$query = "
    SELECT 
        id, 
        name, 
        alternate_name,
        mobile_no,
        mail_id,
        first_name,
        last_name,
        billing_address1,
        billing_address2,
        billing_state,
        national_id,
        gstin
    FROM tbl_customers 
    WHERE status = 1 
    AND (
        name LIKE '%$search_term%' 
        OR alternate_name LIKE '%$search_term%'
        OR mobile_no LIKE '%$search_term%'
        OR mail_id LIKE '%$search_term%'
        OR first_name LIKE '%$search_term%'
        OR last_name LIKE '%$search_term%'
    )
    ORDER BY name ASC
    LIMIT 20
";

$customers = getList($query);

$results = [];
foreach ($customers as $customer) {
    $addr = trim(
        preg_replace(
            '/\s+/',
            ' ',
            trim(($customer['billing_address1'] ?? '') . ' ' . ($customer['billing_address2'] ?? ''))
        )
    );
    $results[] = [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'alternate_name' => $customer['alternate_name'] ?? '',
        'mobile_no' => $customer['mobile_no'] ?? '',
        'mail_id' => $customer['mail_id'] ?? '',
        'billing_state' => isset($customer['billing_state']) ? trim((string) $customer['billing_state']) : '',
        'gstin' => isset($customer['gstin']) ? strtoupper(preg_replace('/\s+/', '', (string) $customer['gstin'])) : '',
        'address' => $addr,
        'national_id' => $customer['national_id'] ?? '',
        'display_text' => $customer['name'] .
            ($customer['alternate_name'] ? ' (' . $customer['alternate_name'] . ')' : '') .
            ($customer['mobile_no'] ? ' - ' . $customer['mobile_no'] : '') .
            ($branch_label !== '' ? ' — ' . $branch_label : '')
    ];
}

echo json_encode([
    'status' => 'success',
    'customers' => $results
]);
?>

