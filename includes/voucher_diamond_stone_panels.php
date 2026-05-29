<?php

/** Requires $auragold_voucher_ds_kind (non-empty) — diamond/stone allocation panels under payments. */
if (empty($auragold_voucher_ds_kind) || !is_string($auragold_voucher_ds_kind)) {
    return;
}

$auragold_jwo_ds_usage_wrap = in_array(($auragold_voucher_ds_kind ?? ''), ['jobwork_order', 'jobwork_invoice', 'material_issue', 'material_receive'], true);
$auragold_ds_usage_subtitle = 'Table shows which diamonds and stones are allocated on this document (stock deducts on Save when pending).';
if (($auragold_voucher_ds_kind ?? '') === 'jobwork_invoice') {
    $auragold_ds_usage_subtitle = 'Diamonds/stones issued for this job are returned to stock when you save the jobwork invoice (job complete).';
} elseif (($auragold_voucher_ds_kind ?? '') === 'jobwork_order') {
    $auragold_ds_usage_subtitle = 'Table shows which diamonds and stones are used on this job work order (allocated stock).';
} elseif (($auragold_voucher_ds_kind ?? '') === 'material_issue') {
    $auragold_ds_usage_subtitle = 'Table shows diamonds and stones issued on this material issue (allocated stock).';
} elseif (($auragold_voucher_ds_kind ?? '') === 'material_receive') {
    $auragold_ds_usage_subtitle = '';
}
$auragold_mr_issued_receive_ui = (($auragold_voucher_ds_kind ?? '') === 'material_receive');
$diamond_stone_card_hidden = $auragold_jwo_ds_usage_wrap ? '' : ' hidden';

?>
<?php if ($auragold_jwo_ds_usage_wrap): ?>
                                    <div class="card mb-3 border auragold-jwo-diamond-stone-usage-card">
                                        <div class="card-header py-2" style="background: linear-gradient(90deg, #f8fafc, #eef2ff); border-bottom: 1px solid #e2e8f0;">
                                            <strong style="color: #11294b;">Diamond &amp; gemstone usage</strong>
                                            <?php if (($auragold_ds_usage_subtitle ?? '') !== ''): ?>
                                            <div class="small text-muted mb-0"><?php echo htmlspecialchars($auragold_ds_usage_subtitle, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body pt-3 pb-2">
<?php endif; ?>
                                    <div id="saleOrderDiamondLinesCard" class="<?php echo $auragold_jwo_ds_usage_wrap ? 'mb-3' : 'mt-3'; ?> sale-order-diamond-lines-card"<?php echo $diamond_stone_card_hidden; ?>>
                                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                                            <label class="mb-0 font-weight-bold" style="font-size: 0.85rem; color: #11294b;">Diamonds used</label>
                                            <?php if (!empty($auragold_mr_issued_receive_ui)): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="mrReceiveDiamondSelectAll">Select all</button>
                                                <button type="button" class="btn btn-primary btn-sm" id="mrReceiveDiamondQueueBtn">Add selected to receive</button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="table-responsive border rounded shadow-sm" style="background: #fff;">
                                            <table class="table table-sm table-bordered mb-0 sale-order-diamond-lines-table" style="font-size: 0.8rem;">
                                                <thead style="background: #11294b; color: #fff;">
                                                    <tr>
                                                        <?php if (!empty($auragold_mr_issued_receive_ui)): ?>
                                                        <th class="text-center" style="width:36px;"><input type="checkbox" id="mrReceiveDiamondHdrChk" title="Select all"></th>
                                                        <?php endif; ?>
                                                        <th>Barcode</th>
                                                        <th>Product</th>
                                                        <th>Category</th>
                                                        <?php if (!empty($auragold_mr_issued_receive_ui)): ?>
                                                        <th class="text-right">Issued Wt</th>
                                                        <th class="text-right">Received</th>
                                                        <th class="text-right">Balance</th>
                                                        <th class="text-right" style="min-width:72px;">Receive Wt</th>
                                                        <?php else: ?>
                                                        <th class="text-right">Qty</th>
                                                        <th class="text-right">Weight</th>
                                                        <?php endif; ?>
                                                        <th style="width: 120px;">Status</th>
                                                        <?php if (empty($auragold_mr_issued_receive_ui)): ?>
                                                        <th class="text-center" style="width: 48px;" title="Remove line">Del</th>
                                                        <?php endif; ?>
                                                    </tr>
                                                </thead>
                                                <tbody id="saleOrderDiamondLinesTbody"></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div id="saleOrderStoneLinesCard" class="mt-3 sale-order-stone-lines-card"<?php echo $diamond_stone_card_hidden; ?>>
                                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                                            <label class="mb-0 font-weight-bold" style="font-size: 0.85rem; color: #0f766e;">Gemstones / stones used</label>
                                            <?php if (!empty($auragold_mr_issued_receive_ui)): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-success btn-sm" id="mrReceiveStoneSelectAll">Select all</button>
                                                <button type="button" class="btn btn-success btn-sm" id="mrReceiveStoneQueueBtn">Add selected to receive</button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="table-responsive border rounded shadow-sm" style="background: #fff;">
                                            <table class="table table-sm table-bordered mb-0 sale-order-stone-lines-table" style="font-size: 0.8rem;">
                                                <thead style="background: #0f766e; color: #fff;">
                                                    <tr>
                                                        <?php if (!empty($auragold_mr_issued_receive_ui)): ?>
                                                        <th class="text-center" style="width:36px;"><input type="checkbox" id="mrReceiveStoneHdrChk" title="Select all"></th>
                                                        <?php endif; ?>
                                                        <th>Barcode</th>
                                                        <th>Product</th>
                                                        <th>Category</th>
                                                        <?php if (!empty($auragold_mr_issued_receive_ui)): ?>
                                                        <th class="text-right">Issued Wt</th>
                                                        <th class="text-right">Received</th>
                                                        <th class="text-right">Balance</th>
                                                        <th class="text-right" style="min-width:72px;">Receive Wt</th>
                                                        <?php else: ?>
                                                        <th class="text-right">Qty</th>
                                                        <th class="text-right">Weight</th>
                                                        <?php endif; ?>
                                                        <th style="width: 120px;">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="saleOrderStoneLinesTbody"></tbody>
                                            </table>
                                        </div>
                                    </div>
<?php if ($auragold_jwo_ds_usage_wrap): ?>
                                        </div>
                                    </div>
<?php endif; ?>
<?php require __DIR__ . '/voucher_metal_exchange_receive_panel.php'; ?>
