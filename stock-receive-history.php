<?php
/**
 * Stock receive: pending rows in tbl_stock_transfer_pending (in transit), and received
 * lines after ajax/stock-transfer-receive.php posts into tbl_stock.
 */
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/stock_transfer_pending_schema.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

// Main database only (DB_NAME). Ignore session working_db sub-branch connection so pending/receive match tbl_stock_transfer_save staging.
$refChk = @mysqli_query($conn_master, "SHOW COLUMNS FROM tbl_stock LIKE 'reference_type'");
$hasRefType = ($refChk && mysqli_num_rows($refChk) > 0);
if ($refChk) {
    mysqli_free_result($refChk);
}

$stoneColRecv = '';
$diamondColRecv = '';
$metalCostColRecv = 'recv.value AS metal_cost';
$stoneCostColRecv = '0 AS stone_cost';
$makingCostColRecv = '0 AS making_cost';

$stoneColOw = '';
$diamondColOw = '';

$fields = [];
$sc = @mysqli_query($conn_master, 'SHOW COLUMNS FROM tbl_stock');
if ($sc) {
    while ($r = mysqli_fetch_assoc($sc)) {
        $fields[$r['Field']] = true;
    }
    mysqli_free_result($sc);
    if (!empty($fields['stone_wt'])) {
        $stoneColRecv = 'recv.stone_wt AS stone_wt';
        $stoneColOw = 'COALESCE(ow.stone_wt, 0) AS stone_wt';
    } else {
        $stoneColRecv = '0 AS stone_wt';
        $stoneColOw = '0 AS stone_wt';
    }
    if (!empty($fields['diamond_wt'])) {
        $diamondColRecv = 'recv.diamond_wt AS diamond_wt';
        $diamondColOw = 'COALESCE(ow.diamond_wt, 0) AS diamond_wt';
    } else {
        $diamondColRecv = '0 AS diamond_wt';
        $diamondColOw = '0 AS diamond_wt';
    }
    if (!empty($fields['metal_value'])) {
        $metalCostColRecv = 'COALESCE(recv.metal_value, recv.value, 0) AS metal_cost';
    }
    if (!empty($fields['stone_amt']) || !empty($fields['stone_amount'])) {
        $f = !empty($fields['stone_amt']) ? 'recv.stone_amt' : 'recv.stone_amount';
        $stoneCostColRecv = 'COALESCE(' . $f . ', 0) AS stone_cost';
    }
    if (!empty($fields['making_amt']) || !empty($fields['making_amount'])) {
        $f = !empty($fields['making_amt']) ? 'recv.making_amt' : 'recv.making_amount';
        $makingCostColRecv = 'COALESCE(' . $f . ', 0) AS making_cost';
    }
}

$metalCostColPend = 'COALESCE(ow.value, pen.value, 0) AS metal_cost';
if (!empty($fields['metal_value'])) {
    $metalCostColPend = 'COALESCE(ow.metal_value, ow.value, pen.value, 0) AS metal_cost';
}

$stoneCostColPend = '0 AS stone_cost';
$makingCostColPend = '0 AS making_cost';
if (!empty($fields['stone_amt']) || !empty($fields['stone_amount'])) {
    $f = !empty($fields['stone_amt']) ? 'ow.stone_amt' : 'ow.stone_amount';
    $stoneCostColPend = 'COALESCE(' . $f . ', 0) AS stone_cost';
}
if (!empty($fields['making_amt']) || !empty($fields['making_amount'])) {
    $f = !empty($fields['making_amt']) ? 'ow.making_amt' : 'ow.making_amount';
    $makingCostColPend = 'COALESCE(' . $f . ', 0) AS making_cost';
}

$refWhereRecv = '';
if ($hasRefType) {
    $refWhereRecv = " AND recv.reference_type = 'stock_transfer' ";
}

$wb = 0;
if (!empty($_SESSION['working_branch_id'])) {
    $wb = (int) $_SESSION['working_branch_id'];
} elseif (!empty($_SESSION['branch_id'])) {
    $wb = (int) $_SESSION['branch_id'];
}
$branchWhereRecv = '';
$branchWherePend = '';
if ($wb > 0) {
    $branchWhereRecv = ' AND recv.branch_id = ' . $wb . ' ';
    $branchWherePend = ' AND pen.to_branch_id = ' . $wb . ' ';
}

$pendingRows = [];
$receivedRows = [];
$sqlError = '';

if (!auragold_ensure_stock_transfer_pending_table($conn_master)) {
    $sqlError = 'Could not ensure tbl_stock_transfer_pending: ' . mysqli_error($conn_master);
} else {
    $sqlPend = "
SELECT
    pen.id AS pending_id,
    pen.transfer_date,
    pen.created_at,
    pen.to_branch_id,
    bt.name AS to_branch_name,
    pen.from_branch_id,
    bf.name AS from_branch_name,
    pen.barcode,
    p.name AS product_name,
    pen.move_wt AS gross_wt,
    pen.move_wt AS net_wt,
    pen.move_qty AS qty,
    $diamondColOw,
    $stoneColOw,
    COALESCE(pen.value, 0) AS purchase_value,
    $metalCostColPend,
    $stoneCostColPend,
    $makingCostColPend,
    '' AS against_ref
FROM tbl_stock_transfer_pending pen
LEFT JOIN tbl_stock ow ON ow.id = pen.outward_stock_id
LEFT JOIN tbl_branches bt ON pen.to_branch_id = bt.id
LEFT JOIN tbl_branches bf ON pen.from_branch_id = bf.id
LEFT JOIN tbl_products p ON pen.product_id = p.id
WHERE pen.status = 'pending'
$branchWherePend
ORDER BY pen.created_at DESC
LIMIT 5000
";
    $q1 = @mysqli_query($conn_master, $sqlPend);
    if ($q1) {
        while ($r = mysqli_fetch_assoc($q1)) {
            $pendingRows[] = $r;
        }
        mysqli_free_result($q1);
    } else {
        $sqlError = mysqli_error($conn_master);
    }

    $sqlReceivedNew = "
SELECT
    recv.id AS receive_stock_id,
    pen.id AS pending_id,
    pen.received_at,
    recv.transaction_date,
    recv.created_at,
    recv.branch_id AS to_branch_id,
    bt.name AS to_branch_name,
    ow.branch_id AS from_branch_id,
    bf.name AS from_branch_name,
    recv.barcode,
    p.name AS product_name,
    recv.final_weight AS gross_wt,
    recv.current_weight AS net_wt,
    recv.current_qty AS qty,
    $diamondColRecv,
    $stoneColRecv,
    recv.value AS purchase_value,
    $metalCostColRecv,
    $stoneCostColRecv,
    $makingCostColRecv,
    'received_pending' AS receive_source,
    '' AS against_ref
FROM tbl_stock_transfer_pending pen
INNER JOIN tbl_stock recv ON recv.id = pen.received_stock_id
LEFT JOIN tbl_stock ow ON ow.id = pen.outward_stock_id
LEFT JOIN tbl_branches bt ON recv.branch_id = bt.id
LEFT JOIN tbl_branches bf ON ow.branch_id = bf.id
LEFT JOIN tbl_products p ON recv.product_id = p.id
WHERE pen.status = 'received'
$branchWhereRecv
ORDER BY pen.received_at DESC
LIMIT 5000
";
    $q2 = @mysqli_query($conn_master, $sqlReceivedNew);
    if ($q2) {
        while ($r = mysqli_fetch_assoc($q2)) {
            $receivedRows[] = $r;
        }
        mysqli_free_result($q2);
    } elseif ($sqlError === '') {
        $sqlError = mysqli_error($conn_master);
    }

    if ($hasRefType && $sqlError === '') {
        $legacyBranch = $wb > 0 ? ' AND recv.branch_id = ' . $wb . ' ' : '';
        $sqlLegacy = "
SELECT
    recv.id AS receive_stock_id,
    NULL AS pending_id,
    recv.transaction_date,
    recv.created_at,
    recv.branch_id AS to_branch_id,
    bt.name AS to_branch_name,
    ow.branch_id AS from_branch_id,
    bf.name AS from_branch_name,
    recv.barcode,
    p.name AS product_name,
    recv.final_weight AS gross_wt,
    recv.current_weight AS net_wt,
    recv.current_qty AS qty,
    $diamondColRecv,
    $stoneColRecv,
    recv.value AS purchase_value,
    $metalCostColRecv,
    $stoneCostColRecv,
    $makingCostColRecv,
    'legacy' AS receive_source,
    '' AS against_ref
FROM tbl_stock recv
INNER JOIN tbl_stock ow ON ow.id = (
    SELECT o.id FROM tbl_stock o
    WHERE o.stock_type = 'outward'
    AND o.product_id = recv.product_id
    AND BINARY IFNULL(o.barcode,'') = BINARY IFNULL(recv.barcode,'')
    AND DATE(o.transaction_date) = DATE(recv.transaction_date)
    AND o.branch_id <> recv.branch_id
    AND ABS(TIMESTAMPDIFF(SECOND, o.created_at, recv.created_at)) <= 45
    ORDER BY o.id ASC
    LIMIT 1
)
LEFT JOIN tbl_branches bt ON recv.branch_id = bt.id
LEFT JOIN tbl_branches bf ON ow.branch_id = bf.id
LEFT JOIN tbl_products p ON recv.product_id = p.id
WHERE recv.stock_type = 'purchase'
$refWhereRecv
$legacyBranch
AND NOT EXISTS (
    SELECT 1 FROM tbl_stock_transfer_pending p2 WHERE p2.received_stock_id = recv.id
)
ORDER BY recv.created_at DESC
LIMIT 2000
";
        $q3 = @mysqli_query($conn_master, $sqlLegacy);
        if ($q3) {
            while ($r = mysqli_fetch_assoc($q3)) {
                $receivedRows[] = $r;
            }
            mysqli_free_result($q3);
        }
    }

    usort($receivedRows, static function ($a, $b) {
        $ta = strtotime($a['received_at'] ?? $a['created_at'] ?? '1970-01-01');
        $tb = strtotime($b['received_at'] ?? $b['created_at'] ?? '1970-01-01');
        return $tb <=> $ta;
    });
}

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Stock Receive History — AuraGold</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
<?php include __DIR__ . '/header-script.php'; ?>
<style>
    .sth-wrap { padding: 1rem 1.25rem; }
    .sth-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
    }
    .sth-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .sth-table-wrap { overflow: auto; background: #fff; border: 1px solid #e8e6f2; border-radius: 10px; }
    .sth-wrap .table thead th {
        background: #1a2d4a !important;
        color: #fff !important;
        border-color: rgba(255,255,255,0.12) !important;
        white-space: nowrap;
        font-size: 13px;
        vertical-align: middle;
    }
    .sth-wrap .table tbody td { font-size: 13px; vertical-align: middle; }
    .sth-wrap .dataTables_filter input {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 6px 10px;
    }
    .sth-invoice { font-weight: 600; color: #0d9488; }
    .srh-col-product { min-width: 160px; max-width: 280px; }
    .srh-section-title { font-weight: 650; color: #1d2c4f; margin: 1rem 0 0.5rem; font-size: 1rem; }
    .srh-pending-badge { font-size: 11px; font-weight: 600; }
    #srhConfirmSelected:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
</head>
<body>
<div class="layout-wrapper layout-2">
    <div class="layout-inner">
        <div id="layout-sidenav" class="layout-sidenav sidenav sidenav-vertical bg-white logo-dark" aria-hidden="true"></div>
        <div class="layout-container">
            <nav class="layout-navbar navbar navbar-expand-lg align-items-lg-center bg-dark container-p-x" id="layout-navbar" aria-hidden="true"></nav>
            <div class="layout-content">
                <div class="container-fluid flex-grow-1" style="padding-top:0;padding-bottom:0;">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="sth-wrap">
    <nav class="small text-muted mb-2" aria-label="breadcrumb">
        <a href="dashboard.php">Home</a> / <a href="stock-transfer.php">Stock Transfer</a> / <span>Receive History</span>
    </nav>
    <div class="sth-toolbar">
        <div>
            <h5 class="mb-0" style="font-weight:650;color:#1d2c4f;">Stock Receive</h5>
            <small class="text-muted">Lines staged after <strong>Save</strong> appear below; <strong>Receive selected</strong> posts them into <code>tbl_stock</code> at the destination branch<?php echo $wb > 0 ? ' (filtered to your working branch)' : ''; ?>.</small>
        </div>
        <div class="sth-toolbar-right">
            <button type="button" class="btn btn-sm btn-success" id="srhConfirmSelected" disabled title="Post selected staging rows into destination tbl_stock">
                <i class="feather icon-download"></i> Receive selected into stock
            </button>
            <a href="stock-transfer.php" class="btn btn-sm btn-primary"><i class="feather icon-shuffle"></i> New transfer</a>
            <a href="stock-transfer-history.php" class="btn btn-sm btn-light border"><i class="feather icon-list"></i> Transfer history</a>
            <button type="button" class="btn btn-sm btn-light border" id="srhBtnRefresh" title="Reload" onclick="location.reload();"><i class="feather icon-refresh-cw"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">Export</button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="srhExportPendingCsv">Export pending (CSV)</a>
                    <a class="dropdown-item" href="#" id="srhExportReceivedCsv">Export received (CSV)</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($sqlError)): ?>
        <div class="alert alert-danger">Could not load: <?php echo htmlspecialchars($sqlError); ?></div>
    <?php endif; ?>

    <h6 class="srh-section-title mb-0">In transit (staging)</h6>
    <p class="text-muted small mb-2">Rows in <code>tbl_stock_transfer_pending</code> waiting to be received into stock.</p>
    <div class="sth-table-wrap p-2 mb-4">
        <table class="table table-sm table-bordered table-hover mb-0" id="srhPendingTable" style="width:100%;">
            <thead>
                <tr>
                    <th style="width:40px;" class="text-center" data-orderable="false" title="Select lines to receive">
                        <input type="checkbox" id="srhSelectAll" aria-label="Select all pending">
                    </th>
                    <th>Date</th>
                    <th>Staging No</th>
                    <th class="srh-col-product">Product Name</th>
                    <th>Receive at</th>
                    <th>From Branch</th>
                    <th>Barcode</th>
                    <th class="text-right">Net Wt</th>
                    <th class="text-right">Gross Wt</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Diamond</th>
                    <th class="text-right">Stone Wt</th>
                    <th class="text-right">Value</th>
                    <th class="text-right">Metal Cost</th>
                    <th class="text-right">Stone Cost</th>
                    <th class="text-right">Making</th>
                    <th>Against</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingRows as $row): ?>
                    <?php
                    $pendId = (int) ($row['pending_id'] ?? 0);
                    $inv = 'STP-' . str_pad((string) $pendId, 6, '0', STR_PAD_LEFT);
                    $d = !empty($row['transfer_date']) ? $row['transfer_date'] : ($row['created_at'] ?? '');
                    $dShow = $d ? date('d/m/Y', strtotime($d)) : '';
                    ?>
                    <tr data-pending-id="<?php echo $pendId; ?>">
                        <td class="text-center srh-cb-wrap">
                            <?php if ($pendId > 0): ?>
                                <input type="checkbox" class="srh-row-cb" value="<?php echo $pendId; ?>" aria-label="Select staging <?php echo htmlspecialchars($inv); ?>">
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($dShow); ?></td>
                        <td><span class="sth-invoice"><?php echo htmlspecialchars($inv); ?></span></td>
                        <td class="srh-col-product"><?php echo htmlspecialchars($row['product_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['to_branch_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['from_branch_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['barcode'] ?? ''); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['net_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['gross_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['qty'] ?? 0), 0); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['diamond_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['stone_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['purchase_value'] ?? 0), 2); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['metal_cost'] ?? 0), 2); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['stone_cost'] ?? 0), 2); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['making_cost'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($row['against_ref'] ?? ''); ?></td>
                        <td><span class="badge badge-warning srh-pending-badge">In transit</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h6 class="srh-section-title mb-0">Received into stock</h6>
    <p class="text-muted small mb-2">Destination <code>tbl_stock</code> purchase lines (new flow via staging, plus older direct transfers when applicable).</p>
    <div class="sth-table-wrap p-2">
        <table class="table table-sm table-bordered table-hover mb-0" id="srhReceivedTable" style="width:100%;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Receipt No</th>
                    <th class="srh-col-product">Product Name</th>
                    <th>Received at</th>
                    <th>From Branch</th>
                    <th>Barcode</th>
                    <th class="text-right">Net Wt</th>
                    <th class="text-right">Gross Wt</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Diamond</th>
                    <th class="text-right">Stone Wt</th>
                    <th class="text-right">Value</th>
                    <th class="text-right">Metal Cost</th>
                    <th class="text-right">Stone Cost</th>
                    <th class="text-right">Making</th>
                    <th>Against</th>
                    <th>Receipt status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receivedRows as $row): ?>
                    <?php
                    $recvId = (int) ($row['receive_stock_id'] ?? 0);
                    $inv = 'SR-' . str_pad((string) ($row['receive_stock_id'] ?? 0), 6, '0', STR_PAD_LEFT);
                    $d = !empty($row['transaction_date']) ? $row['transaction_date'] : ($row['created_at'] ?? '');
                    $dShow = $d ? date('d/m/Y', strtotime($d)) : '';
                    $src = (string) ($row['receive_source'] ?? '');
                    ?>
                    <tr data-receive-id="<?php echo $recvId; ?>">
                        <td><?php echo htmlspecialchars($dShow); ?></td>
                        <td><span class="sth-invoice"><?php echo htmlspecialchars($inv); ?></span></td>
                        <td class="srh-col-product"><?php echo htmlspecialchars($row['product_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($row['to_branch_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['from_branch_name'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($row['barcode'] ?? ''); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['net_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['gross_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['qty'] ?? 0), 0); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['diamond_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['stone_wt'] ?? 0), 3); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['purchase_value'] ?? 0), 2); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['metal_cost'] ?? 0), 2); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['stone_cost'] ?? 0), 2); ?></td>
                        <td class="text-right"><?php echo number_format((float) ($row['making_cost'] ?? 0), 2); ?></td>
                        <td><?php echo htmlspecialchars($row['against_ref'] ?? ''); ?></td>
                        <td class="text-nowrap">
                            <?php if ($src === 'legacy'): ?>
                                <span class="badge badge-secondary" title="Recorded before staging table">Legacy</span>
                            <?php else: ?>
                                <span class="badge badge-success">In stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small mt-2 mb-0">Pending list: up to 5,000 rows. Received list merges staging-based receipts with older transfer receipts (when reference type is available).</p>
</div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    if (typeof jQuery === 'undefined') return;

    function updateConfirmButtonState($) {
        var pending = $('#srhPendingTable tbody .srh-row-cb:checked').length;
        $('#srhConfirmSelected').prop('disabled', pending === 0);
    }

    function exportTableCsv($, tableSel, filename) {
        var $tbl = $(tableSel);
        if (!$tbl.length) return;
        var dt = $tbl.DataTable();
        var csv = [];
        var headers = [];
        $tbl.find('thead th').each(function () {
            headers.push('"' + $(this).text().replace(/"/g, '""') + '"');
        });
        csv.push(headers.join(','));
        dt.rows({ search: 'applied' }).every(function () {
            var row = [];
            $(this.node()).find('td').each(function () {
                row.push('"' + $(this).text().replace(/"/g, '""').trim() + '"');
            });
            csv.push(row.join(','));
        });
        var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    jQuery(function ($) {
        var dtPend = $('#srhPendingTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            dom: '<"row align-items-center mb-2"<"col-sm-6"l><"col-sm-6 text-right"f>>rtip',
            columnDefs: [{ orderable: false, targets: 0 }],
            language: {
                emptyTable: 'No staging rows — save a transfer first.',
                zeroRecords: 'No Rows To Show'
            }
        });

        $('#srhReceivedTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            dom: '<"row align-items-center mb-2"<"col-sm-6"l><"col-sm-6 text-right"f>>rtip',
            language: {
                emptyTable: 'No received lines yet.',
                zeroRecords: 'No Rows To Show'
            }
        });

        $(document).on('change', '.srh-row-cb', function () {
            updateConfirmButtonState($);
        });

        $('#srhSelectAll').on('change', function () {
            var checked = $(this).prop('checked');
            $('#srhPendingTable tbody tr:visible .srh-row-cb').prop('checked', checked);
            updateConfirmButtonState($);
        });

        $('#srhPendingTable').on('draw.dt', function () {
            $('#srhSelectAll').prop('checked', false);
            updateConfirmButtonState($);
        });

        $('#srhConfirmSelected').on('click', function () {
            var ids = [];
            $('#srhPendingTable tbody .srh-row-cb:checked').each(function () {
                ids.push(parseInt($(this).val(), 10));
            });
            if (!ids.length) {
                alert('Select at least one staging row.');
                return;
            }
            var btn = $(this);
            btn.prop('disabled', true);
            fetch('ajax/stock-transfer-receive.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pending_ids: ids }),
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.success) {
                        alert(data.message || 'Received.');
                        location.reload();
                    } else {
                        alert((data && data.message) ? data.message : 'Receive failed.');
                        btn.prop('disabled', false);
                        updateConfirmButtonState($);
                    }
                })
                .catch(function () {
                    alert('Network error.');
                    btn.prop('disabled', false);
                    updateConfirmButtonState($);
                });
        });

        updateConfirmButtonState($);

        $('#srhExportPendingCsv').on('click', function (e) {
            e.preventDefault();
            exportTableCsv($, '#srhPendingTable', 'stock-receive-pending.csv');
        });
        $('#srhExportReceivedCsv').on('click', function (e) {
            e.preventDefault();
            exportTableCsv($, '#srhReceivedTable', 'stock-receive-received.csv');
        });
    });
})();
</script>
</body>
</html>
