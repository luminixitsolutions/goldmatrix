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
            'key'   => 'employee_attendance',
            'file'  => 'employee-attendance.php',
            'label' => 'Employee Attendance',
            'icon'  => 'icon-clock',
            'lead'  => 'Track daily attendance, check-in/out, shifts, and monthly attendance registers.',
        ],
        [
            'key'   => 'employee_attendance_report',
            'file'  => 'employee-attendance-report.php',
            'label' => 'Attendance Report',
            'icon'  => 'icon-calendar',
            'lead'  => 'Date-wise present and absent register — filter by department, employee, and date range.',
        ],
        [
            'key'   => 'employee_advance',
            'file'  => 'employee-advance.php',
            'label' => 'Employee Advance',
            'icon'  => 'icon-pocket',
            'lead'  => 'Submit salary advance requests. Admin approval is done under Advance Request.',
        ],
        [
            'key'   => 'employee_advance_request',
            'file'  => 'employee-advance-request.php',
            'label' => 'Advance Request',
            'icon'  => 'icon-check-circle',
            'lead'  => 'View all advance requests. Admin can approve or reject pending requests (adds amount to this month’s payroll deductions).',
        ],
        [
            'key'   => 'employee_incentive',
            'file'  => 'employee-incentive.php',
            'label' => 'Employee Incentive',
            'icon'  => 'icon-award',
            'lead'  => 'Manage employee incentives, bonuses, and performance-linked rewards.',
        ],
        [
            'key'   => 'employee_reports',
            'file'  => 'employee-reports.php',
            'label' => 'Employee Reports',
            'icon'  => 'icon-bar-chart-2',
            'lead'  => 'Attendance, leave, payroll, tasks, and performance reports — filter by date and employee.',
        ],
        [
            'key'   => 'employee_salary',
            'file'  => 'employee-salary-payroll.php',
            'label' => 'Employee Salary',
            'icon'  => 'icon-credit-card',
            'lead'  => 'Manage salary structures, payroll runs, payslips, and payment history.',
        ],
    ];
}

/**
 * Legacy pages still loadable by direct URL (not shown in main menu).
 *
 * @return array<int, array<string, mixed>>
 */
function auragold_employee_management_legacy_page_items()
{
    return [
        [
            'key'   => 'employee_documents',
            'file'  => 'employee-documents.php',
            'label' => 'Employee Documents',
            'icon'  => 'icon-file-text',
            'lead'  => 'Store and manage employee ID proofs, contracts, certificates, and other documents.',
        ],
        [
            'key'   => 'leave_management',
            'file'  => 'employee-leave-management.php',
            'label' => 'Leave Management',
            'icon'  => 'icon-calendar',
            'lead'  => 'Apply, approve, and monitor employee leave balances and leave requests.',
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
            'key'   => 'employee_settings',
            'file'  => 'employee-settings.php',
            'label' => 'Employee Settings',
            'icon'  => 'icon-settings',
            'lead'  => 'Configure departments, designations, shifts, leave types, and HR preferences.',
        ],
        [
            'key'   => 'employee_tracking',
            'file'  => 'employee-tracking.php',
            'label' => 'Employee Tracking',
            'icon'  => 'icon-map-pin',
            'lead'  => 'Track employee location, field visits, and activity for operations and compliance.',
        ],
        [
            'key'   => 'salary_payroll',
            'file'  => 'employee-salary-payroll.php',
            'label' => 'Employee Salary',
            'icon'  => 'icon-credit-card',
            'lead'  => 'Manage salary structures, payroll runs, payslips, and payment history.',
        ],
    ];
}

/**
 * Menu + legacy page definitions (for layout / file lookup).
 *
 * @return array<int, array<string, mixed>>
 */
function auragold_employee_management_all_page_items()
{
    return array_merge(
        auragold_employee_management_menu_items(),
        auragold_employee_management_legacy_page_items()
    );
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
    foreach (auragold_employee_management_all_page_items() as $item) {
        if (($item['file'] ?? '') === $bn) {
            return $item;
        }
    }

    return null;
}

/**
 * @return array<string, array<string, mixed>>|null
 */
function auragold_employee_management_menu_item_by_key($pageKey)
{
    $key = trim((string) $pageKey);
    foreach (auragold_employee_management_all_page_items() as $item) {
        if (($item['key'] ?? '') === $key) {
            return $item;
        }
    }

    return null;
}

function auragold_employee_management_can_view_page($pageKey)
{
    require_once __DIR__ . '/auragold_sidebar_nav_permissions.php';

    $key = (string) $pageKey;
    if ($key === 'employee_attendance_report') {
        return auragold_nav_can_page_keys('employee_management', 'employee_reports')
            || auragold_nav_can_page_keys('employee_management', 'employee_attendance');
    }
    if (auragold_nav_can_page_keys('employee_management', $key)) {
        return true;
    }
    // Alias: salary page renamed from salary_payroll → employee_salary. Only an
    // explicit legacy grant counts (the old key is no longer in the permission tree,
    // and tree-based checks treat unknown keys as allowed).
    if (($key === 'employee_salary' || $key === 'salary_payroll')
        && auragold_user_can('employee_management.menu')
        && (auragold_user_can('employee_management.salary_payroll.view')
            || auragold_user_can('employee_management.employee_salary.view'))) {
        return true;
    }
    return false;
}

function auragold_employee_management_can_view_file($basename)
{
    $item = auragold_employee_management_menu_item_by_file($basename);
    if (!$item) {
        return false;
    }

    return auragold_employee_management_can_view_page($item['key']);
}
