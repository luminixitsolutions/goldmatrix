<?php
if (!isset($mfg) || !is_array($mfg)) {
    $mfg = auragold_manufacturing_dashboard();
}
$k = $mfg['kpi'];

function mfg_empty_table($rows) {
    return empty($rows);
}

function mfg_esc($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<style>
.mfg-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
.mfg-head h1 { font-size: 1.35rem; font-weight: 700; color: #1e293b; margin: 0; }
.mfg-badge {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 8px 18px;
    border-radius: 10px;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
}
.mfg-kpi-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; max-width: 520px; }
@media (min-width: 992px) {
    .mfg-kpi-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); max-width: none; }
}
.mfg-kpi {
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.mfg-kpi .gauge {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 18px;
    flex-shrink: 0;
    background: #fff;
}
.mfg-kpi .lbl { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; line-height: 1.3; }
.mfg-kpi .sub { font-size: 11px; color: #94a3b8; margin-top: 2px; }
.kpi-ip { background: linear-gradient(145deg, #f5f3ff 0%, #ede9fe 100%); }
.kpi-ip .gauge { border: 5px solid #a78bfa; color: #5b21b6; }
.kpi-del { background: linear-gradient(145deg, #ecfdf5 0%, #d1fae5 100%); }
.kpi-del .gauge { border: 5px solid #34d399; color: #047857; }
.kpi-hold { background: linear-gradient(145deg, #fffbeb 0%, #fef3c7 100%); }
.kpi-hold .gauge { border: 5px solid #fbbf24; color: #b45309; }
.kpi-ni { background: linear-gradient(145deg, #fff1f2 0%, #ffe4e6 100%); }
.kpi-ni .gauge { border: 5px solid #fb7185; color: #be123c; }

.mfg-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    min-height: 220px;
    display: flex;
    flex-direction: column;
}
.mfg-panel-h {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .06em;
    color: #475569;
}
.mfg-panel-body { flex: 1; padding: 0; overflow: auto; }
.mfg-panel-body .empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 160px;
    color: #94a3b8;
    font-size: 14px;
}
.mfg-table { width: 100%; font-size: 13px; margin: 0; }
.mfg-table thead th {
    background: #f5f3ff;
    color: #5b21b6;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 10px 12px;
    border: none;
}
.mfg-table td { padding: 8px 12px; border-color: #f1f5f9; vertical-align: middle; }
.mfg-foot {
    margin-top: 20px;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
@media (max-width: 767.98px) {
    .mfg-dashboard-root {
        max-width: 100%;
        overflow-x: hidden;
    }
    .mfg-kpi-grid {
        max-width: none;
        width: 100%;
    }
    .mfg-head {
        flex-direction: column;
        align-items: stretch;
        gap: 14px;
        margin-bottom: 14px;
    }
    .mfg-head h1 { font-size: 1.15rem; }
    .mfg-head-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        width: 100%;
    }
    .mfg-head-actions .mfg-badge,
    .mfg-head-actions .btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        white-space: normal;
        font-size: 0.78rem;
        padding: 8px 6px;
        line-height: 1.25;
    }
    .mfg-kpi .lbl { white-space: normal; overflow-wrap: anywhere; }
    .mfg-kpi .sub { white-space: normal; }
    .mfg-foot {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="mfg-dashboard-root">

<div class="mfg-head">
    <div>
        <h1>Manufacturing Dashboard</h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap mfg-head-actions">
        <span class="mfg-badge">Manufacturing</span>
        <a class="btn btn-outline-secondary btn-sm" href="dashboards-hub.php"><i class="feather icon-grid"></i> All dashboards</a>
        <a class="btn btn-outline-primary btn-sm" href="jobwork-order.php"><i class="feather icon-file-text"></i> Jobwork order</a>
        <a class="btn btn-outline-primary btn-sm" href="manufacturing-process.php"><i class="feather icon-settings"></i> Process</a>
    </div>
</div>

<div class="mfg-kpi-grid">
    <div class="mfg-kpi kpi-ip">
        <div class="gauge"><?php echo (int) $k['in_progress']; ?></div>
        <div>
            <div class="lbl">Jobs In Progress</div>
            <div class="sub">Open · not overdue · not on hold</div>
        </div>
    </div>
    <div class="mfg-kpi kpi-del">
        <div class="gauge"><?php echo (int) $k['delayed']; ?></div>
        <div>
            <div class="lbl">Delayed Job Orders</div>
            <div class="sub">Past due &amp; still open</div>
        </div>
    </div>
    <div class="mfg-kpi kpi-hold">
        <div class="gauge"><?php echo (int) $k['on_hold']; ?></div>
        <div>
            <div class="lbl">Jobs On Hold</div>
            <div class="sub">Status contains hold</div>
        </div>
    </div>
    <div class="mfg-kpi kpi-ni">
        <div class="gauge"><?php echo (int) $k['not_initiate']; ?></div>
        <div>
            <div class="lbl">Order Not Initiate</div>
            <div class="sub">Draft / pending / empty status</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="mfg-panel h-100">
            <div class="mfg-panel-h">JOBS IN PROGRESS</div>
            <div class="mfg-panel-body">
                <?php if (mfg_empty_table($mfg['list_in_progress'] ?? [])): ?>
                    <div class="empty">No records Available</div>
                <?php else: ?>
                    <table class="table mfg-table mb-0">
                        <thead><tr><th>Jobwork</th><th>Customer</th><th>Sale order</th><th>Due</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($mfg['list_in_progress'] as $row): ?>
                            <tr>
                                <td><?php echo mfg_esc($row['jobwork_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['sale_order_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['due_date'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['status'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="mfg-panel h-100">
            <div class="mfg-panel-h">JOBS IN WORKSTATION / TOTAL ORDER</div>
            <div class="mfg-panel-body">
                <?php if (empty($mfg['workstation_rows'])): ?>
                    <div class="empty">No records Available</div>
                <?php else: ?>
                    <table class="table mfg-table mb-0">
                        <thead><tr><th>Workstation / Department</th><th class="text-end">Orders</th></tr></thead>
                        <tbody>
                        <?php foreach ($mfg['workstation_rows'] as $wr): ?>
                            <tr>
                                <td><?php echo mfg_esc($wr['dept_label'] ?? $wr['dept_key'] ?? ''); ?></td>
                                <td class="text-end"><?php echo (int) ($wr['order_count'] ?? 0); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($mfg['total_jobwork'])): ?>
                        <div class="px-3 py-2 small text-muted border-top">Total jobwork orders: <strong><?php echo (int) $mfg['total_jobwork']; ?></strong></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="mfg-panel h-100">
            <div class="mfg-panel-h">JOBS ON HOLD</div>
            <div class="mfg-panel-body">
                <?php if (mfg_empty_table($mfg['list_on_hold'] ?? [])): ?>
                    <div class="empty">No records Available</div>
                <?php else: ?>
                    <table class="table mfg-table mb-0">
                        <thead><tr><th>Jobwork</th><th>Customer</th><th>Sale order</th><th>Due</th></tr></thead>
                        <tbody>
                        <?php foreach ($mfg['list_on_hold'] as $row): ?>
                            <tr>
                                <td><?php echo mfg_esc($row['jobwork_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['sale_order_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['due_date'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="mfg-panel h-100">
            <div class="mfg-panel-h">DELAYED JOB ORDER</div>
            <div class="mfg-panel-body">
                <?php if (mfg_empty_table($mfg['list_delayed'] ?? [])): ?>
                    <div class="empty">No records Available</div>
                <?php else: ?>
                    <table class="table mfg-table mb-0">
                        <thead><tr><th>Jobwork</th><th>Customer</th><th>Sale order</th><th>Due</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($mfg['list_delayed'] as $row): ?>
                            <tr>
                                <td><?php echo mfg_esc($row['jobwork_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['customer_name'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['sale_order_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['due_date'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['status'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="mfg-panel h-100">
            <div class="mfg-panel-h">RECENT SALE ORDER</div>
            <div class="mfg-panel-body">
                <?php if (mfg_empty_table($mfg['recent_sale_orders'] ?? [])): ?>
                    <div class="empty">No records Available</div>
                <?php else: ?>
                    <table class="table mfg-table mb-0">
                        <thead><tr><th>Customer Name</th><th>Tag No.</th><th>Sale Order No</th></tr></thead>
                        <tbody>
                        <?php foreach ($mfg['recent_sale_orders'] as $row): ?>
                            <tr>
                                <td><?php echo mfg_esc($row['customer_name'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['tag_no'] ?? '') ?: '—'; ?></td>
                                <td><?php echo mfg_esc($row['order_no'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="mfg-panel h-100">
            <div class="mfg-panel-h">COMPLETED ORDER</div>
            <div class="mfg-panel-body">
                <?php if (mfg_empty_table($mfg['completed_orders'] ?? [])): ?>
                    <div class="empty">No records Available</div>
                <?php else: ?>
                    <table class="table mfg-table mb-0">
                        <thead><tr><th>Customer Name</th><th>Tag No.</th><th>Sale Order No</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($mfg['completed_orders'] as $row): ?>
                            <tr>
                                <td><?php echo mfg_esc($row['customer_name'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['tag_no'] ?? '') ?: '—'; ?></td>
                                <td><?php echo mfg_esc($row['order_no'] ?? ''); ?></td>
                                <td><?php echo mfg_esc($row['status'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="mfg-foot">
    <span>AuraGold · Jobwork: <?php echo (int) ($mfg['total_jobwork'] ?? 0); ?> · Sale orders: <?php echo (int) ($mfg['total_sale_orders'] ?? 0); ?></span>
    <span><a href="sale-order.php" class="text-muted" style="text-decoration:none;">Sale orders</a></span>
</div>
</div>
