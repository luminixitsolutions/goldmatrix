<?php
/**
 * Permanently delete a branch: sub-branch (admin + scope rules) or main branch (superadmin only, cascades subs).
 */
session_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session_login_type.php';
require_once dirname(__DIR__) . '/includes/branch_working_context.php';
require_once dirname(__DIR__) . '/includes/ensure_tbl_settings.php';
require_once dirname(__DIR__) . '/includes/branch_delete_cascade.php';
require_once dirname(__DIR__) . '/includes/branch_db_auto_credentials.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['Admin'])) {
    echo json_encode(['ok' => false, 'message' => 'Not logged in']);
    exit;
}

if (!auragold_session_is_admin_login_type()) {
    echo json_encode(['ok' => false, 'message' => 'Only admin users can delete branches.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'message' => 'Invalid request method']);
    exit;
}

$password = isset($_REQUEST['password']) ? trim((string) $_REQUEST['password']) : '';
if ($password === '') {
    echo json_encode(['ok' => false, 'message' => 'Enter the master password to confirm deletion.']);
    exit;
}

if (!auragold_ensure_tbl_settings_branch_password($conn_master)) {
    echo json_encode(['ok' => false, 'message' => 'Could not read branch security settings.']);
    exit;
}

$srow = getRecordMaster('SELECT branch_password_hash FROM tbl_settings ORDER BY id ASC LIMIT 1');
$hash = $srow && isset($srow['branch_password_hash']) ? trim((string) $srow['branch_password_hash']) : '';
if ($hash === '' || !password_verify($password, $hash)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid password']);
    exit;
}

$branchId = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($branchId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid branch']);
    exit;
}

$target = getRecordMaster('SELECT id, name, main_branch_id, db_name FROM tbl_branches WHERE id = ' . $branchId . ' LIMIT 1');
if (!$target) {
    echo json_encode(['ok' => false, 'message' => 'Branch not found']);
    exit;
}

$isMain = (int) ($target['main_branch_id'] ?? 0) === 0;

if ($isMain) {
    if (!auragold_session_is_superadmin()) {
        echo json_encode(['ok' => false, 'message' => 'Only a superadmin can delete a main branch.']);
        exit;
    }
    $result = auragold_branch_delete_main_branch_cascade($conn_master, $conn, $branchId);
    if (empty($result['ok'])) {
        echo json_encode([
            'ok'      => false,
            'message' => $result['message'] ?? 'Could not delete main branch.',
            'detail'  => $result['subs'] ?? null,
        ]);
        exit;
    }
    $payload = [
        'ok'            => true,
        'message'       => $result['message'] ?? 'Main branch deleted.',
        'users_updated' => (int) ($result['users_updated'] ?? 0),
        'data_deleted'  => $result['data_deleted'] ?? null,
        'db_drop'       => $result['db_drop'] ?? null,
        'subs_deleted'  => $result['subs'] ?? [],
    ];
    $dbDrop = $result['db_drop'] ?? null;
    if (is_array($dbDrop) && empty($dbDrop['ok'])) {
        $payload['warning'] = 'Main branch removed, but the dedicated database could not be dropped: ' . ($dbDrop['message'] ?? 'unknown error');
    }
    echo json_encode($payload);
    exit;
}

$scope_main = auragold_session_restrict_sub_branch_ops_main_id();
if ($scope_main > 0 && (int) $target['main_branch_id'] !== $scope_main) {
    echo json_encode(['ok' => false, 'message' => 'You can only delete sub-branches under your main branch.']);
    exit;
}

$result = auragold_branch_delete_sub_branch_core($conn_master, $conn, $branchId);
if (empty($result['ok'])) {
    echo json_encode([
        'ok'      => false,
        'message' => $result['message'] ?? 'Could not delete branch.',
        'detail'  => $result['appReport'] ?? null,
    ]);
    exit;
}

$appReport = $result['appReport'] ?? ['tables' => [], 'errors' => []];
$dbDrop = $result['db_drop'] ?? null;

$payload = [
    'ok'            => true,
    'message'       => $result['message'] ?? 'Branch permanently deleted.',
    'users_updated' => (int) ($result['users_updated'] ?? 0),
    'data_deleted'  => $appReport,
    'db_drop'       => $dbDrop,
];
if (is_array($dbDrop) && empty($dbDrop['ok'])) {
    $payload['warning'] = 'Branch record removed, but the dedicated database could not be dropped: ' . ($dbDrop['message'] ?? 'unknown error');
}

echo json_encode($payload);
