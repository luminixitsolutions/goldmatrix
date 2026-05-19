<?php
/**
 * Stock transfer history: outward lines from tbl_stock that belong to inter-branch transfers,
 * paired with the matching purchase line at the destination (same barcode/product/date window).
 */
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/stock_transfer_history_fetch.php';

$result = auragold_stock_transfer_history_fetch($conn);
$rows = $result['rows'];
$sqlError = $result['error'];

?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Stock Transfer History — AuraGold</title>
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
    .sth-invoice { font-weight: 600; color: #1e40af; }
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
        <a href="dashboard.php">Home</a> / <a href="stock-transfer.php">Stock Transfer</a> / <span>History</span>
    </nav>
    <div class="sth-toolbar">
        <div>
            <h5 class="mb-0" style="font-weight:650;color:#1d2c4f;">Stock Transfer History</h5>
        </div>
        <div class="sth-toolbar-right">
            <a href="stock-transfer.php" class="btn btn-sm btn-primary"><i class="feather icon-shuffle"></i> New transfer</a>
            <a href="stock-receive-history.php" class="btn btn-sm btn-light border" title="Stock added at receiving branch"><i class="feather icon-download"></i> Receive history</a>
            <button type="button" class="btn btn-sm btn-light border" id="sthBtnRefresh" title="Reload" onclick="location.reload();"><i class="feather icon-refresh-cw"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">Export</button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="sthExportExcel"><i class="feather icon-file-text text-success mr-2"></i>Excel</a>
                    <a class="dropdown-item" href="#" id="sthExportPdf"><i class="feather icon-file text-danger mr-2"></i>PDF</a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($sqlError)): ?>
        <div class="alert alert-danger">Could not load history: <?php echo htmlspecialchars($sqlError); ?></div>
    <?php endif; ?>

    <div class="sth-table-wrap p-2">
        <table class="table table-sm table-bordered table-hover mb-0" id="sthTable" style="width:100%;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice No</th>
                    <th>Product Name</th>
                    <th>Transfer To</th>
                    <th>From Branch</th>
                    <th>Barcode</th>
                    <th class="text-right">Net Wt</th>
                    <th class="text-right">Gross Wt</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Diamond</th>
                    <th class="text-right">Stone Wt</th>
                    <th class="text-right">Purchase</th>
                    <th class="text-right">Metal Cost</th>
                    <th class="text-right">Stone Cost</th>
                    <th class="text-right">Making</th>
                    <th>Against</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $inv = 'ST-' . str_pad((string) ($row['outward_id'] ?? 0), 6, '0', STR_PAD_LEFT);
                    $d = !empty($row['transaction_date']) ? $row['transaction_date'] : ($row['created_at'] ?? '');
                    $dShow = $d ? date('d/m/Y', strtotime($d)) : '';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($dShow); ?></td>
                        <td><span class="sth-invoice"><?php echo htmlspecialchars($inv); ?></span></td>
                        <td><?php echo htmlspecialchars($row['product_name'] ?? ''); ?></td>
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
                        <td><?php
                            $pst = strtolower(trim((string) ($row['transfer_pending_status'] ?? '')));
                            $rxId = (int) ($row['transfer_received_stock_id'] ?? 0);
                            $destId = (int) ($row['dest_stock_id'] ?? 0);
                            $inTransit = ($pst === 'pending' && $rxId <= 0 && $destId <= 0);
                            if ($inTransit) {
                                echo '<span class="badge badge-warning">In transit</span>';
                            } else {
                                echo '<span class="badge badge-success">Received</span>';
                            }
                        ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted small mt-2 mb-0">Showing up to 5,000 most recent transfers. Use search to filter.</p>
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
    jQuery(function ($) {
        var dt = $('#sthTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            dom: '<"row align-items-center mb-2"<"col-sm-6"l><"col-sm-6 text-right"f>>rtip',
            language: {
                emptyTable: 'No Rows To Show',
                zeroRecords: 'No Rows To Show'
            }
        });

        $('#sthExportExcel').on('click', function (e) {
            e.preventDefault();
            window.location.href = 'ajax/export-stock-transfer-history-excel.php';
        });
        $('#sthExportPdf').on('click', function (e) {
            e.preventDefault();
            window.location.href = 'ajax/export-stock-transfer-history-pdf.php';
        });
    });
})();
</script>
</body>
</html>
