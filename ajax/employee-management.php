<?php

session_start();
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auragold_employee_management_schema.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['Admin']) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

auragold_ensure_branch_id_on_settings_tables($conn);
$branch_id = auragold_em_resolve_branch_id(isset($_REQUEST['branch_id']) ? (int) $_REQUEST['branch_id'] : null);
auragold_em_seed_defaults($conn, $branch_id);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string) ($_REQUEST['action'] ?? ''));

$respond = static function (array $payload) {
    echo json_encode($payload);
    exit;
};

if ($method === 'GET' && $action === 'dashboard') {
    $respond(['success' => true, 'stats' => auragold_em_dashboard_stats($conn, $branch_id)]);
}

if ($method === 'GET' && $action === 'employees') {
    $respond(['success' => true, 'employees' => auragold_em_get_employees($conn, $branch_id)]);
}

if ($method === 'GET' && $action === 'masters') {
    $respond([
        'success' => true,
        'departments' => auragold_em_get_master_list($conn, 'tbl_employee_departments', $branch_id),
        'designations' => auragold_em_get_master_list($conn, 'tbl_employee_designations', $branch_id),
        'shifts' => auragold_em_get_master_list($conn, 'tbl_employee_shifts', $branch_id),
        'leave_types' => auragold_em_get_master_list($conn, 'tbl_employee_leave_types', $branch_id),
    ]);
}

if ($method === 'GET' && $action === 'documents') {
    $emp = isset($_GET['employee_id']) ? (int) $_GET['employee_id'] : 0;
    $respond(['success' => true, 'documents' => auragold_em_get_documents($conn, $branch_id, $emp)]);
}

if ($method === 'GET' && $action === 'attendance') {
    $date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
    $respond(['success' => true, 'board' => auragold_em_get_attendance_board($conn, $branch_id, $date), 'server_time' => date('d M Y H:i:s')]);
}

if ($method === 'GET' && $action === 'leave') {
    $status = trim((string) ($_GET['status'] ?? ''));
    $respond(['success' => true, 'leave' => auragold_em_get_leave_requests($conn, $branch_id, $status)]);
}

if ($method === 'GET' && $action === 'payroll') {
    $month = trim((string) ($_GET['month'] ?? ''));
    $respond(['success' => true, 'payroll' => auragold_em_get_payroll($conn, $branch_id, $month)]);
}

if ($method === 'GET' && $action === 'tasks') {
    $status = trim((string) ($_GET['status'] ?? ''));
    $respond(['success' => true, 'tasks' => auragold_em_get_tasks($conn, $branch_id, $status)]);
}

if ($method === 'GET' && $action === 'performance') {
    $respond(['success' => true, 'performance' => auragold_em_get_performance($conn, $branch_id)]);
}

if ($method === 'GET' && $action === 'reports') {
    $from = trim((string) ($_GET['from'] ?? ''));
    $to = trim((string) ($_GET['to'] ?? ''));
    $respond(['success' => true, 'reports' => auragold_em_get_reports($conn, $branch_id, $from, $to)]);
}

if ($method !== 'POST') {
    $respond(['success' => false, 'message' => 'Invalid request.']);
}

$data = $_POST;
$action = trim((string) ($data['action'] ?? ''));

switch ($action) {
    case 'save_employee':
        $result = auragold_em_save_employee($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'delete_employee':
        $result = auragold_em_delete_employee($conn, (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'save_master':
        $result = auragold_em_save_master_row($conn, '', $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'delete_master':
        $result = auragold_em_delete_master_row($conn, (string) ($data['master_type'] ?? ''), (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'save_document':
        if (!empty($_FILES['document_file']['tmp_name']) && is_uploaded_file($_FILES['document_file']['tmp_name'])) {
            $dir = dirname(__DIR__) . '/uploads/employee_documents';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $orig = basename((string) $_FILES['document_file']['name']);
            $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $orig);
            $target = $dir . '/' . time() . '_' . $safe;
            if (@move_uploaded_file($_FILES['document_file']['tmp_name'], $target)) {
                $data['file_name'] = $orig;
                $data['file_path'] = 'uploads/employee_documents/' . basename($target);
            }
        }
        $result = auragold_em_save_document($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'delete_document':
        $result = auragold_em_delete_row($conn, 'documents', (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'save_attendance':
        $result = auragold_em_save_attendance($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'punch_in':
        $result = auragold_em_punch_in(
            $conn,
            $branch_id,
            (int) ($data['employee_id'] ?? 0),
            trim((string) ($data['attendance_date'] ?? date('Y-m-d')))
        );
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'punch_in_at' => $result['punch_in_at'] ?? '']);
        break;
    case 'punch_out':
        $result = auragold_em_punch_out($conn, $branch_id, (int) ($data['employee_id'] ?? 0));
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'punch_out_at' => $result['punch_out_at'] ?? '']);
        break;
    case 'save_leave':
        $result = auragold_em_save_leave($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'leave_status':
        $approver = trim((string) ($_SESSION['Admin']['name'] ?? $_SESSION['username'] ?? 'Admin'));
        $result = auragold_em_update_leave_status($conn, (int) ($data['id'] ?? 0), $branch_id, (string) ($data['status'] ?? ''), $approver);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'delete_leave':
        $result = auragold_em_delete_row($conn, 'leave', (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'save_payroll':
        $result = auragold_em_save_payroll($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'delete_payroll':
        $result = auragold_em_delete_row($conn, 'payroll', (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'save_task':
        $result = auragold_em_save_task($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'delete_task':
        $result = auragold_em_delete_row($conn, 'tasks', (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'complete_task':
        $tid = (int) ($data['id'] ?? 0);
        if ($tid <= 0) {
            $respond(['success' => false, 'message' => 'Invalid task.']);
        }
        $ok = @mysqli_query($conn, "UPDATE tbl_employee_tasks SET status = 'Completed', completed_at = NOW() WHERE id = $tid AND branch_id = " . (int) $branch_id . ' LIMIT 1');
        $respond(['success' => (bool) $ok, 'message' => $ok ? 'Task marked completed.' : 'Could not update task.']);
        break;
    case 'save_performance':
        $result = auragold_em_save_performance($conn, $branch_id, $data);
        $respond(['success' => $result['ok'], 'message' => $result['message'], 'id' => $result['id'] ?? 0]);
        break;
    case 'delete_performance':
        $result = auragold_em_delete_row($conn, 'performance', (int) ($data['id'] ?? 0), $branch_id);
        $respond(['success' => $result['ok'], 'message' => $result['message']]);
        break;
    case 'generate_payroll':
        $month = trim((string) ($data['payroll_month'] ?? date('Y-m')));
        $created = 0;
        foreach (auragold_em_get_employees($conn, $branch_id, 'Active') as $emp) {
            $eid = (int) ($emp['id'] ?? 0);
            if ($eid <= 0) {
                continue;
            }
            $exists = getRecord("SELECT id FROM tbl_employee_payroll WHERE employee_id = $eid AND payroll_month = '" . auragold_em_esc($conn, $month) . "' AND record_status = 1 LIMIT 1");
            if ($exists) {
                continue;
            }
            $basic = (float) ($emp['basic_salary'] ?? 0);
            auragold_em_save_payroll($conn, $branch_id, [
                'employee_id' => $eid,
                'payroll_month' => $month,
                'basic_salary' => $basic,
                'allowances' => 0,
                'deductions' => 0,
                'net_salary' => $basic,
                'status' => 'Draft',
            ]);
            $created++;
        }
        $respond(['success' => true, 'message' => $created . ' payroll row(s) generated for ' . $month . '.', 'created' => $created]);
        break;
    default:
        $respond(['success' => false, 'message' => 'Unknown action.']);
}
