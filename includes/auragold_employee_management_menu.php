<?php

/**
 * Employee Management module menu (top nav + left sidebar).
 */
function auragold_employee_management_menu_items()
{
    return [
        [
            'key'   => 'employee_dashboard',
            'file'  => 'employee-dashboard.php',
            'label' => 'Employee Dashboard',
            'icon'  => 'icon-pie-chart',
            'lead'  => 'Overview of workforce metrics, headcount, attendance summary, and quick actions.',
        ],
        [
            'key'   => 'employee_documents',
            'file'  => 'employee-documents.php',
            'label' => 'Employee Documents',
            'icon'  => 'icon-file-text',
            'lead'  => 'Store and manage employee ID proofs, contracts, certificates, and other documents.',
        ],
        [
            'key'   => 'employee_attendance',
            'file'  => 'employee-attendance.php',
            'label' => 'Employee Attendance',
            'icon'  => 'icon-clock',
            'lead'  => 'Track daily attendance, check-in/out, shifts, and monthly attendance registers.',
        ],
        [
            'key'   => 'leave_management',
            'file'  => 'employee-leave-management.php',
            'label' => 'Leave Management',
            'icon'  => 'icon-calendar',
            'lead'  => 'Apply, approve, and monitor employee leave balances and leave requests.',
        ],
        [
            'key'   => 'salary_payroll',
            'file'  => 'employee-salary-payroll.php',
            'label' => 'Salary / Payroll',
            'icon'  => 'icon-dollar-sign',
            'lead'  => 'Manage salary structures, payroll runs, payslips, and payment history.',
        ],
        [
            'key'   => 'employee_tasks',
            'file'  => 'employee-tasks.php',
            'label' => 'Employee Tasks',
            'icon'  => 'icon-check-square',
            'lead'  => 'Assign, track, and complete tasks for employees and teams.',
        ],
        [
            'key'   => 'employee_performance',
            'file'  => 'employee-performance.php',
            'label' => 'Employee Performance',
            'icon'  => 'icon-trending-up',
            'lead'  => 'Record appraisals, KPIs, reviews, and performance ratings.',
        ],
        [
            'key'   => 'employee_reports',
            'file'  => 'employee-reports.php',
            'label' => 'Employee Reports',
            'icon'  => 'icon-bar-chart-2',
            'lead'  => 'Generate attendance, payroll, leave, and performance reports.',
        ],
        [
            'key'   => 'employee_settings',
            'file'  => 'employee-settings.php',
            'label' => 'Employee Settings',
            'icon'  => 'icon-settings',
            'lead'  => 'Configure departments, designations, shifts, leave types, and HR preferences.',
        ],
    ];
}

/**
 * @return string[]
 */
function auragold_employee_management_page_basenames()
{
    $files = [];
    foreach (auragold_employee_management_menu_items() as $item) {
        if (!empty($item['file'])) {
            $files[] = (string) $item['file'];
        }
    }

    return $files;
}

/**
 * @return array<string, array<string, mixed>>|null
 */
function auragold_employee_management_menu_item_by_file($basename)
{
    $bn = trim((string) $basename);
    foreach (auragold_employee_management_menu_items() as $item) {
        if (($item['file'] ?? '') === $bn) {
            return $item;
        }
    }

    return null;
}

function auragold_employee_management_can_view_page($pageKey)
{
    require_once __DIR__ . '/auragold_sidebar_nav_permissions.php';

    return auragold_nav_can_page_keys('employee_management', (string) $pageKey);
}

function auragold_employee_management_can_view_file($basename)
{
    $item = auragold_employee_management_menu_item_by_file($basename);
    if (!$item) {
        return false;
    }

    return auragold_employee_management_can_view_page($item['key']);
}
