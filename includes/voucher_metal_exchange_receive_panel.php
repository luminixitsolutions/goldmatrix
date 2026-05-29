<?php

/** Metal exchange issued on Material Issue — receive back on Material Receive. */
if (($auragold_voucher_ds_kind ?? '') !== 'material_receive') {
    return;
}

?>
<div class="card mb-3 border auragold-mr-metal-exchange-card" style="border-color: #c9a227 !important;">
    <div class="card-header py-2" style="background: linear-gradient(90deg, #fffbeb, #fef3c7); border-bottom: 1px solid #e2e8f0;">
        <strong style="color: #11294b;">Metal exchange — receive gold / silver</strong>
    </div>
    <div class="card-body pt-2 pb-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
            <span class="small text-muted">Receive from Material Issue / order</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-warning btn-sm" id="mrReceiveMeSelectAll">Select all</button>
                <button type="button" class="btn btn-warning btn-sm" id="mrReceiveMeQueueBtn" style="color:#11294b;">Add selected to receive</button>
            </div>
        </div>
        <div class="table-responsive border rounded shadow-sm" style="background:#fff;">
            <table class="table table-sm table-bordered mb-0" style="font-size:0.8rem;">
                <thead style="background:#92400e;color:#fff;">
                    <tr>
                        <th class="text-center" style="width:36px;"><input type="checkbox" id="mrReceiveMeHdrChk" title="Select all"></th>
                        <th>Source</th>
                        <th>Metal</th>
                        <th>Product</th>
                        <th>Barcode</th>
                        <th class="text-right">Issued Wt</th>
                        <th class="text-right">Received</th>
                        <th class="text-right">Balance</th>
                        <th class="text-right" style="min-width:72px;">Receive Wt</th>
                        <th style="width:100px;">Status</th>
                    </tr>
                </thead>
                <tbody id="mrIssuedMetalExchangeTbody"></tbody>
            </table>
        </div>
        <p class="small text-muted mb-0 mt-2" id="mrIssuedMetalExchangeEmpty" style="display:none;">
            No metal exchange lines yet. Use the <strong>Metal Exchange</strong> icon above (same as the MetalExchange card), or add metal on
            Sale Order / Material Issue and save. Lines from the payment card appear here automatically.
        </p>
    </div>
</div>
