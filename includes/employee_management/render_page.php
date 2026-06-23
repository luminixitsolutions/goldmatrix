<?php

/**
 * Render Employee Management page body by $employee_page_key.
 * Expects $em bootstrap array from auragold_em_bootstrap_page().
 */

function auragold_em_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function auragold_em_employee_options(array $employees, $selected = 0): string
{
    $html = '<option value="">— Select employee —</option>';
    foreach ($employees as $emp) {
        $id = (int) ($emp['id'] ?? 0);
        $label = auragold_em_h(auragold_em_employee_name($emp) . ' (' . ($emp['employee_code'] ?? '') . ')');
        $sel = ($selected == $id) ? ' selected' : '';
        $html .= '<option value="' . $id . '"' . $sel . '>' . $label . '</option>';
    }
    return $html;
}

function auragold_em_badge(string $status): string
{
    $s = strtolower($status);
    $cls = 'em-badge-gray';
    if (in_array($s, ['active', 'present', 'approved', 'completed', 'paid'], true)) {
        $cls = 'em-badge-green';
    } elseif (in_array($s, ['pending', 'open', 'draft', 'in progress', 'half day'], true)) {
        $cls = 'em-badge-yellow';
    } elseif (in_array($s, ['rejected', 'absent', 'cancelled'], true)) {
        $cls = 'em-badge-red';
    }
    return '<span class="em-badge ' . $cls . '">' . auragold_em_h($status) . '</span>';
}

function auragold_em_render_page(string $pageKey, array $em, $conn): void
{
    $employees = $em['employees'] ?? [];
    $branch_id = (int) ($em['branch_id'] ?? 0);

    switch ($pageKey) {
        case 'employee_dashboard':
            $stats = auragold_em_dashboard_stats($conn, $branch_id);
            $recentLeave = array_slice(auragold_em_get_leave_requests($conn, $branch_id), 0, 5);
            $recentTasks = array_slice(auragold_em_get_tasks($conn, $branch_id), 0, 5);
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-stats">
                <div class="em-stat"><div class="em-stat-label">Active Employees</div><div class="em-stat-value"><?php echo (int) $stats['total_employees']; ?></div></div>
                <div class="em-stat"><div class="em-stat-label">Present Today</div><div class="em-stat-value"><?php echo (int) $stats['present_today']; ?></div></div>
                <div class="em-stat"><div class="em-stat-label">On Leave Today</div><div class="em-stat-value"><?php echo (int) $stats['on_leave_today']; ?></div></div>
                <div class="em-stat"><div class="em-stat-label">Pending Leave</div><div class="em-stat-value"><?php echo (int) $stats['pending_leave']; ?></div></div>
                <div class="em-stat"><div class="em-stat-label">Open Tasks</div><div class="em-stat-value"><?php echo (int) $stats['open_tasks']; ?></div></div>
                <div class="em-stat"><div class="em-stat-label">Payroll This Month</div><div class="em-stat-value"><?php echo auragold_em_h(auragold_em_format_money($stats['payroll_month_total'])); ?></div><div class="em-stat-sub"><?php echo auragold_em_h(date('F Y')); ?></div></div>
            </div>
            <div class="em-card">
                <h3 style="margin:0 0 10px;font-size:1rem;">Quick Links</h3>
                <div class="em-quick-links">
                    <a href="employee-settings.php"><i class="feather icon-user-plus"></i> Add Employee</a>
                    <a href="employee-attendance.php"><i class="feather icon-clock"></i> Mark Attendance</a>
                    <a href="employee-leave-management.php"><i class="feather icon-calendar"></i> Leave Requests</a>
                    <a href="employee-salary-payroll.php"><i class="feather icon-dollar-sign"></i> Payroll</a>
                    <a href="employee-reports.php"><i class="feather icon-bar-chart-2"></i> Reports</a>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;">
                <div class="em-card">
                    <h3 style="margin:0 0 10px;font-size:1rem;">Recent Leave Requests</h3>
                    <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Employee</th><th>Period</th><th>Status</th></tr></thead><tbody>
                    <?php if (empty($recentLeave)): ?><tr><td colspan="3" class="em-empty">No leave requests yet.</td></tr>
                    <?php else: foreach ($recentLeave as $row): ?>
                    <tr><td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td><td><?php echo auragold_em_h(auragold_em_format_date($row['from_date'] ?? '') . ' – ' . auragold_em_format_date($row['to_date'] ?? '')); ?></td><td><?php echo auragold_em_badge((string) ($row['status'] ?? '')); ?></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody></table></div>
                </div>
                <div class="em-card">
                    <h3 style="margin:0 0 10px;font-size:1rem;">Recent Tasks</h3>
                    <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Task</th><th>Employee</th><th>Status</th></tr></thead><tbody>
                    <?php if (empty($recentTasks)): ?><tr><td colspan="3" class="em-empty">No tasks yet.</td></tr>
                    <?php else: foreach ($recentTasks as $row): ?>
                    <tr><td><?php echo auragold_em_h($row['title'] ?? ''); ?></td><td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td><td><?php echo auragold_em_badge((string) ($row['status'] ?? '')); ?></td></tr>
                    <?php endforeach; endif; ?>
                    </tbody></table></div>
                </div>
            </div>
            <?php
            break;

        case 'employee_documents':
            $docs = auragold_em_get_documents($conn, $branch_id);
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-toolbar">
                <div class="em-toolbar-left"><strong><?php echo count($docs); ?></strong> document(s)</div>
                <div class="em-toolbar-right"><button type="button" class="em-btn em-btn-primary" onclick="EmApp.openModal('emDocModal')"><i class="feather icon-plus"></i> Add Document</button></div>
            </div>
            <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Employee</th><th>Type</th><th>Title</th><th>Expiry</th><th>File</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($docs)): ?><tr><td colspan="6" class="em-empty">No documents uploaded yet.</td></tr>
            <?php else: foreach ($docs as $row): ?>
            <tr>
                <td><?php echo auragold_em_h(auragold_em_employee_name($row) . ' (' . ($row['employee_code'] ?? '') . ')'); ?></td>
                <td><?php echo auragold_em_h($row['doc_type'] ?? ''); ?></td>
                <td><?php echo auragold_em_h($row['doc_title'] ?? ''); ?></td>
                <td><?php echo auragold_em_h(auragold_em_format_date($row['expiry_date'] ?? '')); ?></td>
                <td><?php if (!empty($row['file_path'])): ?><a href="<?php echo auragold_em_h($row['file_path']); ?>" target="_blank" rel="noopener"><?php echo auragold_em_h($row['file_name'] ?: 'View'); ?></a><?php else: ?>—<?php endif; ?></td>
                <td class="em-actions"><button type="button" class="danger" data-del-doc="<?php echo (int) $row['id']; ?>">Delete</button></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
            <div id="emDocModal" class="em-modal-backdrop"><div class="em-modal"><div class="em-modal-head"><h3>Add Document</h3><button type="button" class="em-close">&times;</button></div>
            <form id="emDocForm" enctype="multipart/form-data"><div class="em-modal-body"><div class="em-form-grid">
                <div class="em-field"><label>Employee</label><select name="employee_id" required><?php echo auragold_em_employee_options($employees); ?></select></div>
                <div class="em-field"><label>Document Type</label><select name="doc_type"><option value="ID Proof">ID Proof</option><option value="Contract">Contract</option><option value="Certificate">Certificate</option><option value="Other">Other</option></select></div>
                <div class="em-field"><label>Title</label><input type="text" name="doc_title" required></div>
                <div class="em-field"><label>Expiry Date</label><input type="date" name="expiry_date"></div>
                <div class="em-field" style="grid-column:1/-1"><label>Upload File</label><input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
                <div class="em-field" style="grid-column:1/-1"><label>Notes</label><textarea name="notes"></textarea></div>
            </div></div><div class="em-modal-foot"><button type="button" class="em-btn em-btn-light em-close">Cancel</button><button type="submit" class="em-btn em-btn-primary">Save Document</button></div></form></div></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                document.getElementById('emDocForm').addEventListener('submit', function (e) {
                    e.preventDefault();
                    var fd = new FormData(this);
                    EmApp.post('save_document', fd, true).then(function (r) {
                        EmApp.showAlert(alertEl, r.message, r.success);
                        if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                    });
                });
                EmApp.qsa('[data-del-doc]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete this document?')) return;
                        EmApp.post('delete_document', { id: btn.getAttribute('data-del-doc') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success);
                            if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
            });
            </script>
            <?php
            break;

        case 'employee_attendance':
            $today = date('Y-m-d');
            $viewDate = !empty($_GET['date']) ? preg_replace('/[^0-9-]/', '', (string) $_GET['date']) : $today;
            if ($viewDate === '') {
                $viewDate = $today;
            }
            $isToday = ($viewDate === $today);
            $board = auragold_em_get_attendance_board($conn, $branch_id, $viewDate);
            $staleHrs = auragold_em_attendance_stale_hours();
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-card" style="margin-bottom:14px;padding:12px 16px;">
                <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-size:11px;color:#64748b;font-weight:600;text-transform:uppercase;">Current Time (24h)</div>
                        <div class="em-live-clock" id="emLiveClock"><?php echo auragold_em_h(date('d M Y H:i:s')); ?></div>
                    </div>
                    <div style="font-size:12px;color:#64748b;max-width:520px;line-height:1.5;">
                        Day / night shift supported (24-hour punch). If punch out is missing for <strong><?php echo (int) $staleHrs; ?> hours</strong>, the session is marked <strong>Absent</strong> and the employee can punch in again for today.
                    </div>
                </div>
            </div>
            <div class="em-toolbar">
                <div class="em-toolbar-left">
                    <label style="font-size:12px;font-weight:600;">Attendance Date</label>
                    <input type="date" id="emAttDate" value="<?php echo auragold_em_h($viewDate); ?>" style="margin-left:8px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;">
                    <?php if (!$isToday): ?>
                    <span class="em-badge em-badge-yellow" style="margin-left:8px;">View only — punch on today</span>
                    <?php else: ?>
                    <span class="em-badge em-badge-green" style="margin-left:8px;">Today — punch enabled</span>
                    <?php endif; ?>
                </div>
                <div class="em-toolbar-right">
                    <button type="button" class="em-btn em-btn-light" id="emAttGoToday">Today</button>
                </div>
            </div>
            <div class="em-table-wrap">
                <table class="em-table" id="emAttTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Shift</th>
                            <th>Status</th>
                            <th>Punch In (24h)</th>
                            <th>Punch Out (24h)</th>
                            <th>Duration</th>
                            <th style="min-width:200px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($board)): ?>
                    <tr><td colspan="9" class="em-empty">No employees for this branch. Add active users in Settings → Users, or add employees in Employee Settings.</td></tr>
                    <?php else: foreach ($board as $row): ?>
                    <tr data-employee-id="<?php echo (int) $row['employee_id']; ?>">
                        <td><?php echo auragold_em_h($row['employee_code']); ?></td>
                        <td><strong><?php echo auragold_em_h($row['name']); ?></strong></td>
                        <td><?php echo auragold_em_h($row['department_name']); ?></td>
                        <td><?php echo auragold_em_h($row['shift_name']); ?></td>
                        <td><?php echo auragold_em_badge(str_replace('Present (In)', 'Present', (string) $row['status'])); ?><?php if (!empty($row['open_punch'])): ?> <span class="em-badge em-badge-blue">Open</span><?php endif; ?></td>
                        <td class="em-punch-in-cell"><?php echo auragold_em_h($row['punch_in_display']); ?></td>
                        <td class="em-punch-out-cell"><?php echo auragold_em_h($row['punch_out_display']); ?></td>
                        <td class="em-duration-cell"><?php echo auragold_em_h($row['duration']); ?></td>
                        <td class="em-punch-actions">
                            <?php if ($isToday && !empty($row['can_punch_in'])): ?>
                            <button type="button" class="em-btn em-btn-success em-punch-in-btn" data-employee-id="<?php echo (int) $row['employee_id']; ?>"><i class="feather icon-log-in"></i> Punch In</button>
                            <?php endif; ?>
                            <?php if ($isToday && !empty($row['can_punch_out'])): ?>
                            <button type="button" class="em-btn em-btn-primary em-punch-out-btn" data-employee-id="<?php echo (int) $row['employee_id']; ?>"><i class="feather icon-log-out"></i> Punch Out</button>
                            <?php endif; ?>
                            <?php if ($isToday && empty($row['can_punch_in']) && empty($row['can_punch_out']) && ($row['status'] ?? '') === '—'): ?>
                            <span style="font-size:11px;color:#94a3b8;">No punch</span>
                            <?php elseif (!$isToday): ?>
                            <span style="font-size:11px;color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                var clockEl = document.getElementById('emLiveClock');
                if (clockEl) {
                    setInterval(function () {
                        var d = new Date();
                        var p = function (n) { return n < 10 ? '0' + n : '' + n; };
                        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        clockEl.textContent = p(d.getDate()) + ' ' + months[d.getMonth()] + ' ' + d.getFullYear() + ' '
                            + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
                    }, 1000);
                }
                document.getElementById('emAttDate').addEventListener('change', function () {
                    window.location.href = 'employee-attendance.php?date=' + encodeURIComponent(this.value);
                });
                document.getElementById('emAttGoToday').addEventListener('click', function () {
                    window.location.href = 'employee-attendance.php';
                });
                function doPunch(action, employeeId, btn) {
                    if (btn) btn.disabled = true;
                    EmApp.post(action, { employee_id: employeeId, attendance_date: '<?php echo auragold_em_h($today); ?>' }).then(function (r) {
                        EmApp.showAlert(alertEl, r.message, r.success);
                        if (r.success) {
                            setTimeout(function () { EmApp.reload(); }, 600);
                        } else if (btn) {
                            btn.disabled = false;
                        }
                    }).catch(function () {
                        if (btn) btn.disabled = false;
                    });
                }
                EmApp.qsa('.em-punch-in-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        doPunch('punch_in', btn.getAttribute('data-employee-id'), btn);
                    });
                });
                EmApp.qsa('.em-punch-out-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        doPunch('punch_out', btn.getAttribute('data-employee-id'), btn);
                    });
                });
            });
            </script>
            <?php
            break;

        case 'leave_management':
            $leaveRows = auragold_em_get_leave_requests($conn, $branch_id);
            $leaveTypes = $em['leave_types'] ?? [];
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-toolbar"><div class="em-toolbar-left"><strong><?php echo count($leaveRows); ?></strong> leave request(s)</div>
            <div class="em-toolbar-right"><button type="button" class="em-btn em-btn-primary" onclick="EmApp.openModal('emLeaveModal')">Apply Leave</button></div></div>
            <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($leaveRows)): ?><tr><td colspan="7" class="em-empty">No leave requests yet.</td></tr>
            <?php else: foreach ($leaveRows as $row): ?>
            <tr>
                <td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td>
                <td><?php echo auragold_em_h($row['leave_type_name'] ?? '—'); ?></td>
                <td><?php echo auragold_em_h(auragold_em_format_date($row['from_date'] ?? '')); ?></td>
                <td><?php echo auragold_em_h(auragold_em_format_date($row['to_date'] ?? '')); ?></td>
                <td class="num"><?php echo auragold_em_h(number_format((float)($row['days'] ?? 0), 2)); ?></td>
                <td><?php echo auragold_em_badge((string)($row['status'] ?? '')); ?></td>
                <td class="em-actions">
                    <?php if (($row['status'] ?? '') === 'Pending'): ?>
                    <button type="button" data-leave-status="<?php echo (int)$row['id']; ?>" data-status="Approved">Approve</button>
                    <button type="button" class="danger" data-leave-status="<?php echo (int)$row['id']; ?>" data-status="Rejected">Reject</button>
                    <?php endif; ?>
                    <button type="button" class="danger" data-del-leave="<?php echo (int)$row['id']; ?>">Delete</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
            <div id="emLeaveModal" class="em-modal-backdrop"><div class="em-modal"><div class="em-modal-head"><h3>Apply Leave</h3><button type="button" class="em-close">&times;</button></div>
            <form id="emLeaveForm"><div class="em-modal-body"><div class="em-form-grid">
                <div class="em-field"><label>Employee</label><select name="employee_id" required><?php echo auragold_em_employee_options($employees); ?></select></div>
                <div class="em-field"><label>Leave Type</label><select name="leave_type_id"><option value="0">—</option><?php foreach ($leaveTypes as $lt): ?><option value="<?php echo (int)$lt['id']; ?>"><?php echo auragold_em_h($lt['name']); ?></option><?php endforeach; ?></select></div>
                <div class="em-field"><label>From Date</label><input type="date" name="from_date" required></div>
                <div class="em-field"><label>To Date</label><input type="date" name="to_date" required></div>
                <div class="em-field"><label>Days</label><input type="number" step="0.5" min="0.5" name="days" value="1"></div>
                <div class="em-field" style="grid-column:1/-1"><label>Reason</label><textarea name="reason"></textarea></div>
            </div></div><div class="em-modal-foot"><button type="button" class="em-btn em-btn-light em-close">Cancel</button><button type="submit" class="em-btn em-btn-primary">Submit</button></div></form></div></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                document.getElementById('emLeaveForm').addEventListener('submit', function (e) {
                    e.preventDefault();
                    var fd = new FormData(this); var data = {}; fd.forEach(function(v,k){ data[k]=v; });
                    EmApp.post('save_leave', data).then(function (r) { EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700); });
                });
                EmApp.qsa('[data-leave-status]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        EmApp.post('leave_status', { id: btn.getAttribute('data-leave-status'), status: btn.getAttribute('data-status') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
                EmApp.qsa('[data-del-leave]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete leave request?')) return;
                        EmApp.post('delete_leave', { id: btn.getAttribute('data-del-leave') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
            });
            </script>
            <?php
            break;

        case 'salary_payroll':
            $month = !empty($_GET['month']) ? preg_replace('/[^0-9-]/', '', (string) $_GET['month']) : date('Y-m');
            if ($month === '') {
                $month = date('Y-m');
            }
            $payroll = auragold_em_get_payroll($conn, $branch_id, $month);
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-toolbar">
                <div class="em-toolbar-left"><label style="font-size:12px;font-weight:600;">Month</label> <input type="month" id="emPayMonth" value="<?php echo auragold_em_h($month); ?>" style="margin-left:8px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;"></div>
                <div class="em-toolbar-right">
                    <button type="button" class="em-btn em-btn-light" id="emGenPayroll">Generate from Employees</button>
                    <button type="button" class="em-btn em-btn-primary" onclick="EmApp.openModal('emPayModal')">Add Payroll</button>
                </div>
            </div>
            <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Employee</th><th>Month</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Net</th><th>Status</th><th>Actions</th></tr></thead><tbody id="emPayBody">
            <?php if (empty($payroll)): ?><tr><td colspan="8" class="em-empty">No payroll records for this month.</td></tr>
            <?php else: foreach ($payroll as $row): ?>
            <tr>
                <td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td>
                <td><?php echo auragold_em_h($row['payroll_month'] ?? ''); ?></td>
                <td class="num"><?php echo auragold_em_h(auragold_em_format_money($row['basic_salary'] ?? 0)); ?></td>
                <td class="num"><?php echo auragold_em_h(auragold_em_format_money($row['allowances'] ?? 0)); ?></td>
                <td class="num"><?php echo auragold_em_h(auragold_em_format_money($row['deductions'] ?? 0)); ?></td>
                <td class="num"><strong><?php echo auragold_em_h(auragold_em_format_money($row['net_salary'] ?? 0)); ?></strong></td>
                <td><?php echo auragold_em_badge((string)($row['status'] ?? '')); ?></td>
                <td class="em-actions"><button type="button" class="danger" data-del-pay="<?php echo (int)$row['id']; ?>">Delete</button></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
            <div id="emPayModal" class="em-modal-backdrop"><div class="em-modal"><div class="em-modal-head"><h3>Add Payroll</h3><button type="button" class="em-close">&times;</button></div>
            <form id="emPayForm"><div class="em-modal-body"><div class="em-form-grid">
                <div class="em-field"><label>Employee</label><select name="employee_id" required><?php echo auragold_em_employee_options($employees); ?></select></div>
                <div class="em-field"><label>Payroll Month</label><input type="month" name="payroll_month" value="<?php echo auragold_em_h($month); ?>" required></div>
                <div class="em-field"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary" value="0"></div>
                <div class="em-field"><label>Allowances</label><input type="number" step="0.01" name="allowances" value="0"></div>
                <div class="em-field"><label>Deductions</label><input type="number" step="0.01" name="deductions" value="0"></div>
                <div class="em-field"><label>Net Salary</label><input type="number" step="0.01" name="net_salary" value="0"></div>
                <div class="em-field"><label>Payment Date</label><input type="date" name="payment_date"></div>
                <div class="em-field"><label>Status</label><select name="status"><option>Draft</option><option>Paid</option></select></div>
            </div></div><div class="em-modal-foot"><button type="button" class="em-btn em-btn-light em-close">Cancel</button><button type="submit" class="em-btn em-btn-primary">Save</button></div></form></div></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                document.getElementById('emPayForm').addEventListener('submit', function (e) {
                    e.preventDefault(); var fd = new FormData(this); var data = {}; fd.forEach(function(v,k){ data[k]=v; });
                    EmApp.post('save_payroll', data).then(function (r) { EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700); });
                });
                document.getElementById('emGenPayroll').addEventListener('click', function () {
                    EmApp.post('generate_payroll', { payroll_month: document.getElementById('emPayMonth').value }).then(function (r) {
                        EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                    });
                });
                document.getElementById('emPayMonth').addEventListener('change', function () {
                    window.location.href = 'employee-salary-payroll.php?month=' + encodeURIComponent(this.value);
                });
                EmApp.qsa('[data-del-pay]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete payroll row?')) return;
                        EmApp.post('delete_payroll', { id: btn.getAttribute('data-del-pay') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
            });
            </script>
            <?php
            break;

        case 'employee_tasks':
            $tasks = auragold_em_get_tasks($conn, $branch_id);
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-toolbar"><div class="em-toolbar-right"><button type="button" class="em-btn em-btn-primary" onclick="EmApp.openModal('emTaskModal')">Add Task</button></div></div>
            <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Task</th><th>Employee</th><th>Priority</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($tasks)): ?><tr><td colspan="6" class="em-empty">No tasks assigned yet.</td></tr>
            <?php else: foreach ($tasks as $row): ?>
            <tr>
                <td><?php echo auragold_em_h($row['title'] ?? ''); ?><div style="font-size:11px;color:#64748b;"><?php $desc = (string)($row['description'] ?? ''); echo auragold_em_h(strlen($desc) > 60 ? substr($desc, 0, 60) . '…' : $desc); ?></div></td>
                <td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td>
                <td><?php echo auragold_em_badge((string)($row['priority'] ?? '')); ?></td>
                <td><?php echo auragold_em_h(auragold_em_format_date($row['due_date'] ?? '')); ?></td>
                <td><?php echo auragold_em_badge((string)($row['status'] ?? '')); ?></td>
                <td class="em-actions">
                    <?php if (($row['status'] ?? '') !== 'Completed'): ?><button type="button" data-task-done="<?php echo (int)$row['id']; ?>">Complete</button><?php endif; ?>
                    <button type="button" class="danger" data-del-task="<?php echo (int)$row['id']; ?>">Delete</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
            <div id="emTaskModal" class="em-modal-backdrop"><div class="em-modal"><div class="em-modal-head"><h3>Add Task</h3><button type="button" class="em-close">&times;</button></div>
            <form id="emTaskForm"><div class="em-modal-body"><div class="em-form-grid">
                <div class="em-field"><label>Employee</label><select name="employee_id" required><?php echo auragold_em_employee_options($employees); ?></select></div>
                <div class="em-field"><label>Title</label><input type="text" name="title" required></div>
                <div class="em-field"><label>Priority</label><select name="priority"><option>Low</option><option selected>Medium</option><option>High</option></select></div>
                <div class="em-field"><label>Due Date</label><input type="date" name="due_date"></div>
                <div class="em-field"><label>Status</label><select name="status"><option>Open</option><option>In Progress</option><option>Completed</option></select></div>
                <div class="em-field" style="grid-column:1/-1"><label>Description</label><textarea name="description"></textarea></div>
            </div></div><div class="em-modal-foot"><button type="button" class="em-btn em-btn-light em-close">Cancel</button><button type="submit" class="em-btn em-btn-primary">Save Task</button></div></form></div></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                document.getElementById('emTaskForm').addEventListener('submit', function (e) {
                    e.preventDefault(); var fd = new FormData(this); var data = {}; fd.forEach(function(v,k){ data[k]=v; });
                    EmApp.post('save_task', data).then(function (r) { EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700); });
                });
                EmApp.qsa('[data-task-done]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        EmApp.post('complete_task', { id: btn.getAttribute('data-task-done') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
                EmApp.qsa('[data-del-task]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete task?')) return;
                        EmApp.post('delete_task', { id: btn.getAttribute('data-del-task') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
            });
            </script>
            <?php
            break;

        case 'employee_performance':
            $perf = auragold_em_get_performance($conn, $branch_id);
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-toolbar"><div class="em-toolbar-right"><button type="button" class="em-btn em-btn-primary" onclick="EmApp.openModal('emPerfModal')">Add Review</button></div></div>
            <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Employee</th><th>Period</th><th>Review Date</th><th>Rating</th><th>KPI</th><th>Reviewer</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            <?php if (empty($perf)): ?><tr><td colspan="8" class="em-empty">No performance reviews yet.</td></tr>
            <?php else: foreach ($perf as $row): ?>
            <tr>
                <td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td>
                <td><?php echo auragold_em_h($row['review_period'] ?? ''); ?></td>
                <td><?php echo auragold_em_h(auragold_em_format_date($row['review_date'] ?? '')); ?></td>
                <td class="num"><?php echo auragold_em_h(number_format((float)($row['rating'] ?? 0), 1)); ?>/5</td>
                <td class="num"><?php echo auragold_em_h(number_format((float)($row['kpi_score'] ?? 0), 2)); ?></td>
                <td><?php echo auragold_em_h($row['reviewer_name'] ?? ''); ?></td>
                <td><?php echo auragold_em_badge((string)($row['status'] ?? '')); ?></td>
                <td class="em-actions"><button type="button" class="danger" data-del-perf="<?php echo (int)$row['id']; ?>">Delete</button></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody></table></div>
            <div id="emPerfModal" class="em-modal-backdrop"><div class="em-modal"><div class="em-modal-head"><h3>Performance Review</h3><button type="button" class="em-close">&times;</button></div>
            <form id="emPerfForm"><div class="em-modal-body"><div class="em-form-grid">
                <div class="em-field"><label>Employee</label><select name="employee_id" required><?php echo auragold_em_employee_options($employees); ?></select></div>
                <div class="em-field"><label>Review Period</label><input type="text" name="review_period" placeholder="Q1 2026"></div>
                <div class="em-field"><label>Review Date</label><input type="date" name="review_date"></div>
                <div class="em-field"><label>Rating (1-5)</label><input type="number" step="0.1" min="0" max="5" name="rating" value="3"></div>
                <div class="em-field"><label>KPI Score</label><input type="number" step="0.01" name="kpi_score" value="0"></div>
                <div class="em-field"><label>Reviewer</label><input type="text" name="reviewer_name"></div>
                <div class="em-field"><label>Status</label><select name="status"><option>Draft</option><option>Final</option></select></div>
                <div class="em-field" style="grid-column:1/-1"><label>Strengths</label><textarea name="strengths"></textarea></div>
                <div class="em-field" style="grid-column:1/-1"><label>Improvements</label><textarea name="improvements"></textarea></div>
            </div></div><div class="em-modal-foot"><button type="button" class="em-btn em-btn-light em-close">Cancel</button><button type="submit" class="em-btn em-btn-primary">Save Review</button></div></form></div></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                document.getElementById('emPerfForm').addEventListener('submit', function (e) {
                    e.preventDefault(); var fd = new FormData(this); var data = {}; fd.forEach(function(v,k){ data[k]=v; });
                    EmApp.post('save_performance', data).then(function (r) { EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700); });
                });
                EmApp.qsa('[data-del-perf]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Delete review?')) return;
                        EmApp.post('delete_performance', { id: btn.getAttribute('data-del-perf') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
            });
            </script>
            <?php
            break;

        case 'employee_reports':
            $from = date('Y-m-01');
            $to = date('Y-m-d');
            if (!empty($_GET['from'])) { $from = preg_replace('/[^0-9-]/', '', (string) $_GET['from']); }
            if (!empty($_GET['to'])) { $to = preg_replace('/[^0-9-]/', '', (string) $_GET['to']); }
            $rep = auragold_em_get_reports($conn, $branch_id, $from, $to);
            ?>
            <div class="em-toolbar">
                <div class="em-toolbar-left">
                    <label style="font-size:12px;font-weight:600;">From</label> <input type="date" id="emRepFrom" value="<?php echo auragold_em_h($from); ?>" style="margin:0 8px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;">
                    <label style="font-size:12px;font-weight:600;">To</label> <input type="date" id="emRepTo" value="<?php echo auragold_em_h($to); ?>" style="margin-left:8px;padding:6px 8px;border:1px solid #cbd5e1;border-radius:6px;">
                    <button type="button" class="em-btn em-btn-primary" id="emRepRun" style="margin-left:8px;">Run Report</button>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;">
                <div class="em-card"><h3 style="margin:0 0 10px;font-size:1rem;">Attendance Summary</h3><div class="em-table-wrap"><table class="em-table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                <?php if (empty($rep['attendance_summary'])): ?><tr><td colspan="2" class="em-empty">No attendance in range.</td></tr>
                <?php else: foreach ($rep['attendance_summary'] as $r): ?><tr><td><?php echo auragold_em_badge((string)($r['status'] ?? '')); ?></td><td class="num"><?php echo (int)($r['c'] ?? 0); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
                <div class="em-card"><h3 style="margin:0 0 10px;font-size:1rem;">Leave Summary</h3><div class="em-table-wrap"><table class="em-table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                <?php if (empty($rep['leave_summary'])): ?><tr><td colspan="2" class="em-empty">No leave in range.</td></tr>
                <?php else: foreach ($rep['leave_summary'] as $r): ?><tr><td><?php echo auragold_em_badge((string)($r['status'] ?? '')); ?></td><td class="num"><?php echo (int)($r['c'] ?? 0); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
                <div class="em-card"><h3 style="margin:0 0 10px;font-size:1rem;">Payroll</h3><p style="margin:0;font-size:13px;color:#64748b;">Records: <strong><?php echo (int)$rep['payroll_count']; ?></strong></p><p style="margin:8px 0 0;font-size:1.2rem;font-weight:700;color:#11294b;"><?php echo auragold_em_h(auragold_em_format_money($rep['payroll_total'])); ?></p></div>
                <div class="em-card"><h3 style="margin:0 0 10px;font-size:1rem;">Tasks</h3><div class="em-table-wrap"><table class="em-table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                <?php if (empty($rep['task_summary'])): ?><tr><td colspan="2" class="em-empty">No tasks.</td></tr>
                <?php else: foreach ($rep['task_summary'] as $r): ?><tr><td><?php echo auragold_em_badge((string)($r['status'] ?? '')); ?></td><td class="num"><?php echo (int)($r['c'] ?? 0); ?></td></tr><?php endforeach; endif; ?>
                </tbody></table></div></div>
                <div class="em-card"><h3 style="margin:0 0 10px;font-size:1rem;">Performance</h3><p style="margin:0;font-size:13px;color:#64748b;">Reviews: <strong><?php echo (int)$rep['performance_reviews']; ?></strong></p><p style="margin:8px 0 0;font-size:1.2rem;font-weight:700;color:#11294b;">Avg rating <?php echo auragold_em_h(number_format((float)$rep['avg_rating'], 2)); ?>/5</p></div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('emRepRun').addEventListener('click', function () {
                    var f = document.getElementById('emRepFrom').value;
                    var t = document.getElementById('emRepTo').value;
                    window.location.href = 'employee-reports.php?from=' + encodeURIComponent(f) + '&to=' + encodeURIComponent(t);
                });
            });
            </script>
            <?php
            break;

        case 'employee_settings':
            $allEmployees = auragold_em_get_employees($conn, $branch_id);
            $departments = $em['departments'] ?? [];
            $designations = $em['designations'] ?? [];
            $shifts = $em['shifts'] ?? [];
            $leaveTypes = $em['leave_types'] ?? [];
            ?>
            <div id="emAlert" class="em-alert"></div>
            <div class="em-tabs-group">
                <div class="em-tabs">
                    <button type="button" class="em-tab active" data-panel="emTabEmployees">Employees</button>
                    <button type="button" class="em-tab" data-panel="emTabDepartments">Departments</button>
                    <button type="button" class="em-tab" data-panel="emTabDesignations">Designations</button>
                    <button type="button" class="em-tab" data-panel="emTabShifts">Shifts</button>
                    <button type="button" class="em-tab" data-panel="emTabLeaveTypes">Leave Types</button>
                </div>
                <div id="emTabEmployees" class="em-tab-panel active">
                    <div class="em-toolbar"><div class="em-toolbar-right"><button type="button" class="em-btn em-btn-primary" onclick="EmApp.openModal('emEmpModal')">Add Employee</button></div></div>
                    <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Designation</th><th>Phone</th><th>Salary</th><th>Status</th><th>Actions</th></tr></thead><tbody>
                    <?php if (empty($allEmployees)): ?><tr><td colspan="8" class="em-empty">No employees yet. Click Add Employee.</td></tr>
                    <?php else: foreach ($allEmployees as $row): ?>
                    <tr>
                        <td><?php echo auragold_em_h($row['employee_code'] ?? ''); ?></td>
                        <td><?php echo auragold_em_h(auragold_em_employee_name($row)); ?></td>
                        <td><?php echo auragold_em_h($row['department_name'] ?? '—'); ?></td>
                        <td><?php echo auragold_em_h($row['designation_name'] ?? '—'); ?></td>
                        <td><?php echo auragold_em_h($row['phone'] ?? ''); ?></td>
                        <td class="num"><?php echo auragold_em_h(auragold_em_format_money($row['basic_salary'] ?? 0)); ?></td>
                        <td><?php echo auragold_em_badge((string)($row['status'] ?? '')); ?></td>
                        <td class="em-actions"><button type="button" class="danger" data-del-emp="<?php echo (int)$row['id']; ?>">Remove</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody></table></div>
                </div>
                <?php
                $masterTabs = [
                    ['emTabDepartments', 'department', 'Departments', $departments, false],
                    ['emTabDesignations', 'designation', 'Designations', $designations, false],
                    ['emTabShifts', 'shift', 'Shifts', $shifts, true],
                    ['emTabLeaveTypes', 'leave_type', 'Leave Types', $leaveTypes, false, true],
                ];
                foreach ($masterTabs as $mt):
                    $panelId = $mt[0]; $type = $mt[1]; $title = $mt[2]; $rows = $mt[3]; $isShift = !empty($mt[4]); $isLeave = !empty($mt[5]);
                ?>
                <div id="<?php echo auragold_em_h($panelId); ?>" class="em-tab-panel">
                    <div class="em-toolbar"><div class="em-toolbar-right"><button type="button" class="em-btn em-btn-primary" onclick="document.getElementById('<?php echo auragold_em_h($panelId); ?>Form').style.display='block'">Add <?php echo auragold_em_h(rtrim($title, 's')); ?></button></div></div>
                    <form id="<?php echo auragold_em_h($panelId); ?>Form" class="em-card" style="display:none;margin-bottom:14px;" data-master-type="<?php echo auragold_em_h($type); ?>">
                        <div class="em-form-grid">
                            <div class="em-field"><label>Name</label><input type="text" name="name" required></div>
                            <?php if ($isShift): ?>
                            <div class="em-field"><label>Start Time</label><input type="time" name="start_time" value="09:00"></div>
                            <div class="em-field"><label>End Time</label><input type="time" name="end_time" value="18:00"></div>
                            <?php elseif ($isLeave): ?>
                            <div class="em-field"><label>Days / Year</label><input type="number" step="0.5" name="days_per_year" value="12"></div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top:10px;"><button type="submit" class="em-btn em-btn-primary">Save</button></div>
                    </form>
                    <div class="em-table-wrap"><table class="em-table"><thead><tr><th>Name</th><?php if ($isShift): ?><th>Start</th><th>End</th><?php elseif ($isLeave): ?><th>Days/Year</th><?php endif; ?><th>Actions</th></tr></thead><tbody>
                    <?php if (empty($rows)): ?><tr><td colspan="4" class="em-empty">No records.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo auragold_em_h($r['name'] ?? ''); ?></td>
                        <?php if ($isShift): ?><td><?php echo auragold_em_h(substr((string)($r['start_time'] ?? ''), 0, 5)); ?></td><td><?php echo auragold_em_h(substr((string)($r['end_time'] ?? ''), 0, 5)); ?></td>
                        <?php elseif ($isLeave): ?><td class="num"><?php echo auragold_em_h(number_format((float)($r['days_per_year'] ?? 0), 2)); ?></td><?php endif; ?>
                        <td class="em-actions"><button type="button" class="danger" data-del-master="<?php echo auragold_em_h($type); ?>" data-id="<?php echo (int)$r['id']; ?>">Remove</button></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody></table></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="emEmpModal" class="em-modal-backdrop"><div class="em-modal"><div class="em-modal-head"><h3>Add Employee</h3><button type="button" class="em-close">&times;</button></div>
            <form id="emEmpForm"><div class="em-modal-body"><div class="em-form-grid">
                <div class="em-field"><label>Employee Code</label><input type="text" name="employee_code" value="<?php echo auragold_em_h($em['next_employee_code'] ?? ''); ?>"></div>
                <div class="em-field"><label>First Name</label><input type="text" name="first_name" required></div>
                <div class="em-field"><label>Last Name</label><input type="text" name="last_name"></div>
                <div class="em-field"><label>Email</label><input type="email" name="email"></div>
                <div class="em-field"><label>Phone</label><input type="text" name="phone"></div>
                <div class="em-field"><label>Department</label><select name="department_id"><option value="0">—</option><?php foreach ($departments as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo auragold_em_h($d['name']); ?></option><?php endforeach; ?></select></div>
                <div class="em-field"><label>Designation</label><select name="designation_id"><option value="0">—</option><?php foreach ($designations as $d): ?><option value="<?php echo (int)$d['id']; ?>"><?php echo auragold_em_h($d['name']); ?></option><?php endforeach; ?></select></div>
                <div class="em-field"><label>Shift</label><select name="shift_id"><option value="0">—</option><?php foreach ($shifts as $s): ?><option value="<?php echo (int)$s['id']; ?>"><?php echo auragold_em_h($s['name']); ?></option><?php endforeach; ?></select></div>
                <div class="em-field"><label>Joining Date</label><input type="date" name="joining_date"></div>
                <div class="em-field"><label>Basic Salary</label><input type="number" step="0.01" name="basic_salary" value="0"></div>
                <div class="em-field"><label>Status</label><select name="status"><option>Active</option><option>Inactive</option></select></div>
                <div class="em-field" style="grid-column:1/-1"><label>Address</label><textarea name="address"></textarea></div>
            </div></div><div class="em-modal-foot"><button type="button" class="em-btn em-btn-light em-close">Cancel</button><button type="submit" class="em-btn em-btn-primary">Save Employee</button></div></form></div></div>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var alertEl = document.getElementById('emAlert');
                document.getElementById('emEmpForm').addEventListener('submit', function (e) {
                    e.preventDefault(); var fd = new FormData(this); var data = {}; fd.forEach(function(v,k){ data[k]=v; });
                    EmApp.post('save_employee', data).then(function (r) { EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700); });
                });
                EmApp.qsa('[data-del-emp]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Remove employee?')) return;
                        EmApp.post('delete_employee', { id: btn.getAttribute('data-del-emp') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
                EmApp.qsa('form[data-master-type]').forEach(function (form) {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault(); var fd = new FormData(form); var data = { master_type: form.getAttribute('data-master-type') };
                        fd.forEach(function(v,k){ data[k]=v; });
                        EmApp.post('save_master', data).then(function (r) { EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700); });
                    });
                });
                EmApp.qsa('[data-del-master]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!confirm('Remove this item?')) return;
                        EmApp.post('delete_master', { master_type: btn.getAttribute('data-del-master'), id: btn.getAttribute('data-id') }).then(function (r) {
                            EmApp.showAlert(alertEl, r.message, r.success); if (r.success) setTimeout(function(){ EmApp.reload(); }, 700);
                        });
                    });
                });
            });
            </script>
            <?php
            break;

        default:
            echo '<div class="em-empty">Page not configured.</div>';
    }
}
