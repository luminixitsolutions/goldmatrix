<?php
/**
 * Stock receive: pending rows in tbl_stock_transfer_pending (in transit), and received
 * lines after ajax/stock-transfer-receive.php posts into tbl_stock.
 */
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/stock_transfer_pending_schema.php';
require_once __DIR__ . '/includes/stock_receive_history_fetch.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $stXferConn = auragold_stock_transfer_central_mysqli();
} catch (Throwable $e) {
    die('Stock transfer / receive database: ' . htmlspecialchars($e->getMessage()));
}

$srhData = auragold_stock_receive_history_fetch($stXferConn);
$pendingRows = $srhData['pending'];
$receivedRows = $srhData['received'];
$sqlError = $srhData['error'];

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Stock Receive History — <?php echo htmlspecialchars(auragold_app_name(), ENT_QUOTES, 'UTF-8'); ?></title>
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
    .sth-toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; position: relative; z-index: 20; }
    .sth-toolbar-right .dropdown-menu { z-index: 2000; min-width: 11rem; }
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
    <div class="sth-toolbar">
        <div>
            <h5 class="mb-0" style="font-weight:650;color:#1d2c4f;">Stock Receive</h5>
        </div>
        <div class="sth-toolbar-right">
            <button type="button" class="btn btn-sm btn-success" id="srhConfirmSelected" disabled title="Post selected rows into stock at the destination branch">
                <i class="feather icon-download"></i> Receive selected into stock
            </button>
            <a href="stock-transfer.php" class="btn btn-sm btn-primary"><i class="feather icon-shuffle"></i> New transfer</a>
            <a href="stock-transfer-history.php" class="btn btn-sm btn-light border"><i class="feather icon-list"></i> Transfer history</a>
            <button type="button" class="btn btn-sm btn-light border" id="srhBtnRefresh" title="Reload" onclick="location.reload();"><i class="feather icon-refresh-cw"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">Export</button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="srhExportExcel"><i class="feather icon-file-text text-success mr-2"></i>Excel</a>
                    <a class="dropdown-item" href="#" id="srhExportPdf"><i class="feather icon-file text-danger mr-2"></i>PDF</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($sqlError)): ?>
        <div class="alert alert-danger">Could not load: <?php echo htmlspecialchars($sqlError); ?></div>
    <?php endif; ?>

    <h6 class="srh-section-title mb-0">In transit (staging)</h6>
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
                            <?php elseif ($src === 'cross_db'): ?>
                                <span class="badge badge-info" title="Transferred from another branch database">Cross-DB</span>
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

<?php include __DIR__ . '/footer-script.php'; ?>
<script>
(function () {
    if (typeof jQuery === 'undefined') return;

    function updateConfirmButtonState($) {
        var pending = $('#srhPendingTable tbody .srh-row-cb:checked').length;
        $('#srhConfirmSelected').prop('disabled', pending === 0);
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

        $('#srhExportExcel').on('click', function (e) {
            e.preventDefault();
            window.location.href = 'ajax/export-stock-receive-history-excel.php';
        });
        $('#srhExportPdf').on('click', function (e) {
            e.preventDefault();
            window.location.href = 'ajax/export-stock-receive-history-pdf.php';
        });
    });
})();
</script>
</body>
</html>
