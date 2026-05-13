<?php
/**
 * Stock transfer history: outward lines from tbl_stock that belong to inter-branch transfers,
 * paired with the matching purchase line at the destination (same barcode/product/date window).
 */
require_once __DIR__ . '/includes/session_init.php';
require_once __DIR__ . '/config.php';

$hasPendingTable = false;
$__t = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_stock_transfer_pending'");
if ($__t && mysqli_num_rows($__t) > 0) {
    $hasPendingTable = true;
}
if ($__t) {
    mysqli_free_result($__t);
}

if (!isset($_SESSION['user_id']) || (int) $_SESSION['user_id'] <= 0) {
    header('Location: index.php');
    exit;
}

$refChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock LIKE 'reference_type'");
$hasRefType = ($refChk && mysqli_num_rows($refChk) > 0);
if ($refChk) {
    mysqli_free_result($refChk);
}

$stoneCol = '';
$diamondCol = '';
$metalCostCol = 'ow.value AS metal_cost';
$stoneCostCol = '0 AS stone_cost';
$makingCostCol = '0 AS making_cost';

$sc = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_stock");
if ($sc) {
    $fields = [];
    while ($r = mysqli_fetch_assoc($sc)) {
        $fields[$r['Field']] = true;
    }
    mysqli_free_result($sc);
    if (!empty($fields['stone_wt'])) {
        $stoneCol = 'ow.stone_wt AS stone_wt';
    } else {
        $stoneCol = '0 AS stone_wt';
    }
    if (!empty($fields['diamond_wt'])) {
        $diamondCol = 'ow.diamond_wt AS diamond_wt';
    } else {
        $diamondCol = '0 AS diamond_wt';
    }
    if (!empty($fields['metal_value'])) {
        $metalCostCol = 'COALESCE(ow.metal_value, ow.value, 0) AS metal_cost';
    }
    if (!empty($fields['stone_amt']) || !empty($fields['stone_amount'])) {
        $f = !empty($fields['stone_amt']) ? 'ow.stone_amt' : 'ow.stone_amount';
        $stoneCostCol = 'COALESCE(' . $f . ', 0) AS stone_cost';
    }
    if (!empty($fields['making_amt']) || !empty($fields['making_amount'])) {
        $f = !empty($fields['making_amt']) ? 'ow.making_amt' : 'ow.making_amount';
        $makingCostCol = 'COALESCE(' . $f . ', 0) AS making_cost';
    }
}

$refWhere = '';
if ($hasRefType) {
    $refWhere = " AND ow.reference_type = 'stock_transfer' ";
} else {
    $refWhere = '';
}

if ($hasPendingTable) {
    // One pending row per outward: prefer the line that was received (received_stock_id set), else latest id.
    // Dest join: use received_stock_id; if no pending row, legacy 45s match; if pending but not linked yet, match purchase at destination branch.
    $sql = "
SELECT
    ow.id AS outward_id,
    ow.transaction_date,
    ow.created_at,
    ow.branch_id AS from_branch_id,
    bf.name AS from_branch_name,
    CASE
        WHEN pen.id IS NOT NULL AND pen.to_branch_id IS NOT NULL AND pen.to_branch_id > 0 THEN pen.to_branch_id
        ELSE COALESCE(dest.branch_id, pen.to_branch_id)
    END AS to_branch_id,
    bt.name AS to_branch_name,
    ow.barcode,
    p.name AS product_name,
    COALESCE(dest.final_weight, ow.final_weight) AS gross_wt,
    COALESCE(dest.current_weight, ow.current_weight) AS net_wt,
    COALESCE(dest.current_qty, ow.current_qty) AS qty,
    $diamondCol,
    $stoneCol,
    COALESCE(dest.value, ow.value) AS purchase_value,
    $metalCostCol,
    $stoneCostCol,
    $makingCostCol,
    COALESCE(pen.status, '') AS transfer_pending_status,
    pen.received_stock_id AS transfer_received_stock_id,
    dest.id AS dest_stock_id,
    '' AS against_ref
FROM tbl_stock ow
LEFT JOIN tbl_stock_transfer_pending pen ON pen.id = (
    SELECT p2.id FROM tbl_stock_transfer_pending p2
    WHERE p2.outward_stock_id = ow.id
    ORDER BY (p2.received_stock_id IS NOT NULL AND p2.received_stock_id > 0) DESC, p2.id DESC
    LIMIT 1
)
LEFT JOIN tbl_stock dest ON dest.id = COALESCE(
    NULLIF(pen.received_stock_id, 0),
    IF(pen.id IS NULL,
        (SELECT d.id FROM tbl_stock d
         WHERE d.stock_type = 'purchase'
         AND d.product_id = ow.product_id
         AND BINARY IFNULL(d.barcode,'') = BINARY IFNULL(ow.barcode,'')
         AND DATE(d.transaction_date) = DATE(ow.transaction_date)
         AND d.branch_id <> ow.branch_id
         AND ABS(TIMESTAMPDIFF(SECOND, ow.created_at, d.created_at)) <= 45
         ORDER BY d.id ASC
         LIMIT 1),
        (SELECT d.id FROM tbl_stock d
         WHERE d.stock_type = 'purchase'
         AND pen.id IS NOT NULL
         AND (pen.received_stock_id IS NULL OR pen.received_stock_id = 0)
         AND d.product_id = ow.product_id
         AND BINARY IFNULL(d.barcode,'') = BINARY IFNULL(ow.barcode,'')
         AND d.branch_id = pen.to_branch_id
         AND d.branch_id <> ow.branch_id
         AND ABS(TIMESTAMPDIFF(SECOND, ow.created_at, d.created_at)) <= 86400
         ORDER BY d.id DESC
         LIMIT 1)
    )
)
AND (
    pen.id IS NULL
    OR pen.to_branch_id IS NULL
    OR pen.to_branch_id = 0
    OR dest.branch_id = pen.to_branch_id
)
LEFT JOIN tbl_branches bf ON ow.branch_id = bf.id
LEFT JOIN tbl_branches bt ON bt.id = CASE
    WHEN pen.id IS NOT NULL AND pen.to_branch_id IS NOT NULL AND pen.to_branch_id > 0 THEN pen.to_branch_id
    ELSE COALESCE(dest.branch_id, pen.to_branch_id)
END
LEFT JOIN tbl_products p ON ow.product_id = p.id
WHERE ow.stock_type = 'outward'
$refWhere
AND (dest.id IS NOT NULL OR pen.id IS NOT NULL)
ORDER BY ow.created_at DESC
LIMIT 5000
";
} else {
    $sql = "
SELECT
    ow.id AS outward_id,
    ow.transaction_date,
    ow.created_at,
    ow.branch_id AS from_branch_id,
    bf.name AS from_branch_name,
    dest.branch_id AS to_branch_id,
    bt.name AS to_branch_name,
    ow.barcode,
    p.name AS product_name,
    ow.final_weight AS gross_wt,
    ow.current_weight AS net_wt,
    ow.current_qty AS qty,
    $diamondCol,
    $stoneCol,
    ow.value AS purchase_value,
    $metalCostCol,
    $stoneCostCol,
    $makingCostCol,
    '' AS transfer_pending_status,
    '' AS against_ref
FROM tbl_stock ow
INNER JOIN tbl_stock dest ON dest.id = (
    SELECT d.id FROM tbl_stock d
    WHERE d.stock_type = 'purchase'
    AND d.product_id = ow.product_id
    AND BINARY IFNULL(d.barcode,'') = BINARY IFNULL(ow.barcode,'')
    AND DATE(d.transaction_date) = DATE(ow.transaction_date)
    AND d.branch_id <> ow.branch_id
    AND ABS(TIMESTAMPDIFF(SECOND, ow.created_at, d.created_at)) <= 45
    ORDER BY d.id ASC
    LIMIT 1
)
LEFT JOIN tbl_branches bf ON ow.branch_id = bf.id
LEFT JOIN tbl_branches bt ON dest.branch_id = bt.id
LEFT JOIN tbl_products p ON ow.product_id = p.id
WHERE ow.stock_type = 'outward'
$refWhere
ORDER BY ow.created_at DESC
LIMIT 5000
";
}

$rows = [];
$sqlError = '';
$q = @mysqli_query($conn, $sql);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $rows[] = $r;
    }
    mysqli_free_result($q);
} else {
    $sqlError = mysqli_error($conn);
}

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
            <small class="text-muted">Outward moves with destination branch; <strong>In transit</strong> until receive posts purchase into <code>tbl_stock</code>.</small>
        </div>
        <div class="sth-toolbar-right">
            <a href="stock-transfer.php" class="btn btn-sm btn-primary"><i class="feather icon-shuffle"></i> New transfer</a>
            <a href="stock-receive-history.php" class="btn btn-sm btn-light border" title="Stock added at receiving branch"><i class="feather icon-download"></i> Receive history</a>
            <button type="button" class="btn btn-sm btn-light border" id="sthBtnRefresh" title="Reload" onclick="location.reload();"><i class="feather icon-refresh-cw"></i></button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">Export</button>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="#" id="sthExportCsv">Export CSV</a>
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

        $('#sthExportCsv').on('click', function (e) {
            e.preventDefault();
            if (typeof $.fn.dataTable.ext.buttons !== 'undefined') {
                // fallback: simple CSV from visible rows
            }
            var csv = [];
            var headers = [];
            $('#sthTable thead th').each(function () {
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
            link.download = 'stock-transfer-history.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    });
})();
</script>
</body>
</html>
