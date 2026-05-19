<?php

/** Requires $auragold_voucher_ds_kind (non-empty) — diamond/stone allocation panels under payments. */
if (empty($auragold_voucher_ds_kind) || !is_string($auragold_voucher_ds_kind)) {
    return;
}

$auragold_jwo_ds_usage_wrap = in_array(($auragold_voucher_ds_kind ?? ''), ['jobwork_order', 'jobwork_invoice'], true);
$auragold_ds_usage_subtitle = (($auragold_voucher_ds_kind ?? '') === 'jobwork_invoice')
    ? 'Table shows which diamonds and stones are used on this job work invoice (allocated stock).'
    : 'Table shows which diamonds and stones are used on this job work order (allocated stock).';
$diamond_stone_card_hidden = $auragold_jwo_ds_usage_wrap ? '' : ' hidden';

?>
<?php if ($auragold_jwo_ds_usage_wrap): ?>
                                    <div class="card mb-3 border auragold-jwo-diamond-stone-usage-card">
                                        <div class="card-header py-2" style="background: linear-gradient(90deg, #f8fafc, #eef2ff); border-bottom: 1px solid #e2e8f0;">
                                            <strong style="color: #11294b;">Diamond &amp; gemstone usage</strong>
                                            <div class="small text-muted mb-0"><?php echo htmlspecialchars($auragold_ds_usage_subtitle, ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                        <div class="card-body pt-3 pb-2">
<?php endif; ?>
                                    <div id="saleOrderDiamondLinesCard" class="<?php echo $auragold_jwo_ds_usage_wrap ? 'mb-3' : 'mt-3'; ?> sale-order-diamond-lines-card"<?php echo $diamond_stone_card_hidden; ?>>
                                        <label class="mb-2 d-block font-weight-bold" style="font-size: 0.85rem; color: #11294b;">Diamonds used</label>
                                        <div class="table-responsive border rounded shadow-sm" style="background: #fff;">
                                            <table class="table table-sm table-bordered mb-0 sale-order-diamond-lines-table" style="font-size: 0.8rem;">
                                                <thead style="background: #11294b; color: #fff;">
                                                    <tr>
                                                        <th>Barcode</th>
                                                        <th>Product</th>
                                                        <th>Category</th>
                                                        <th class="text-right">Qty</th>
                                                        <th class="text-right">Weight</th>
                                                        <th style="width: 140px;">Status</th>
                                                        <th class="text-center" style="width: 48px;" title="Remove line">Del</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="saleOrderDiamondLinesTbody"></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div id="saleOrderStoneLinesCard" class="mt-3 sale-order-stone-lines-card"<?php echo $diamond_stone_card_hidden; ?>>
                                        <label class="mb-2 d-block font-weight-bold" style="font-size: 0.85rem; color: #0f766e;">Gemstones / stones used</label>
                                        <div class="table-responsive border rounded shadow-sm" style="background: #fff;">
                                            <table class="table table-sm table-bordered mb-0 sale-order-stone-lines-table" style="font-size: 0.8rem;">
                                                <thead style="background: #0f766e; color: #fff;">
                                                    <tr>
                                                        <th>Barcode</th>
                                                        <th>Product</th>
                                                        <th>Category</th>
                                                        <th class="text-right">Qty</th>
                                                        <th class="text-right">Weight</th>
                                                        <th style="width: 140px;">Status</th>
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
