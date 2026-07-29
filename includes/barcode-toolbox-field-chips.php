<?php
/**
 * Barcode Setting toolbox chips — column set/order/labels match common-modal.php
 * Show/Hide Columns (modal-col-* / data-column). Set $barcode_toolbox_divider before include.
 */
if (!isset($barcode_toolbox_divider)) {
    $barcode_toolbox_divider = 'All columns';
}
?>
                    <!-- Extra image / attachment fields (label designer; not in modal table list) -->
                    <div class="toolbox-field-item toolbox-field-image" data-field="CompanyName">CompanyName</div>
                    <div class="toolbox-field-item toolbox-field-image" data-field="CompanyLogo">CompanyLogo</div>
                    <div class="toolbox-field-item toolbox-field-image" data-field="Photo">Photo</div>
                    <div class="toolbox-field-item toolbox-field-image" data-field="ProductImage">ProductImage</div>
                    <div class="toolbox-field-item toolbox-field-image" data-field="AttachImage">AttachImage</div>
                    <div class="toolbox-field-item toolbox-field-image" data-field="ImageUrl">ImageUrl</div>
                    <div class="toolbox-field-item toolbox-field-image toolbox-field-strip" data-field="StripLine" title="Horizontal strip line">StripLine</div>
                    <div class="toolbox-field-item toolbox-field-image toolbox-field-white-strip" data-field="WhiteStrip" title="Blank white rectangular strip">White Strip</div>
                    <div class="toolbox-fields-divider"><?php echo htmlspecialchars($barcode_toolbox_divider, ENT_QUOTES, 'UTF-8'); ?></div>
                    <!-- Same order as common-modal.php table settings (lines 128–415) -->
                    <div class="toolbox-field-item" data-field="Select">Select</div>
                    <div class="toolbox-field-item" data-field="Id">Id</div>
                    <div class="toolbox-field-item" data-field="RFIDCode">RFIDCode</div>
                    <div class="toolbox-field-item" data-field="VoucherType">Voucher Type</div>
                    <div class="toolbox-field-item" data-field="Barcode" data-search="barcode barcode no barcodeno barcode number">Barcode</div>
                    <div class="toolbox-field-item" data-field="DesignNo">Design No</div>
                    <div class="toolbox-field-item" data-field="HUIDNo">HUID No</div>
                    <div class="toolbox-field-item" data-field="Category">Category</div>
                    <div class="toolbox-field-item" data-field="Calculation">Calculation</div>
                    <div class="toolbox-field-item" data-field="Product">Product</div>
                    <div class="toolbox-field-item" data-field="Location">Location</div>
                    <div class="toolbox-field-item" data-field="PktWt">Pkt. Wt.</div>
                    <div class="toolbox-field-item" data-field="PktLessWt">Pkt. Less Wt.</div>
                    <div class="toolbox-field-item" data-field="GrossWt">Gross Wt.</div>
                    <div class="toolbox-field-item" data-field="StoneWeight">Carat / Stone Wt.</div>
                    <div class="toolbox-field-item" data-field="LessWt">Less Wt.</div>
                    <div class="toolbox-field-item" data-field="NetWt">Net Wt.</div>
                    <div class="toolbox-field-item" data-field="Quantity">Quantity</div>
                    <div class="toolbox-field-item" data-field="Rate">Rate</div>
                    <div class="toolbox-field-item" data-field="Amount">Amount</div>
                    <div class="toolbox-field-item" data-field="MetalQty">Metal Qty</div>
                    <div class="toolbox-field-item" data-field="MetalWeight">Weight</div>
                    <div class="toolbox-field-item" data-field="Carat">Karat</div>
                    <div class="toolbox-field-item" data-field="Purity">Purity</div>
                    <div class="toolbox-field-item" data-field="PurityWt">Purity Wt</div>
                    <div class="toolbox-field-item" data-field="GoldLoss1">Gold Loss 1</div>
                    <div class="toolbox-field-item" data-field="GoldLoss2">Gold Loss 2</div>
                    <div class="toolbox-field-item" data-field="MetalLossValue">Loss Value</div>
                    <div class="toolbox-field-item" data-field="WastagePer">Wastage Per.</div>
                    <div class="toolbox-field-item" data-field="WastageWt">Wastage Wt.</div>
                    <div class="toolbox-field-item" data-field="MetalRate">Metal Rate</div>
                    <div class="toolbox-field-item" data-field="MetalValue">Metal Value</div>
                    <div class="toolbox-field-item" data-field="MetalCost">Metal Cost</div>
                    <div class="toolbox-field-item" data-field="RequestedPurity">Requested Purity</div>
                    <div class="toolbox-field-item" data-field="Requested">Requested</div>
                    <div class="toolbox-field-item" data-field="SettingCharge">Setting Charge</div>
                    <div class="toolbox-field-item" data-field="FinalWt">Final Wt.</div>
                    <div class="toolbox-field-item" data-field="AlloyWt">Alloy Wt.</div>
                    <div class="toolbox-field-item" data-field="DiscountType">Discount Type</div>
                    <div class="toolbox-field-item" data-field="DiscountPer">Discount Per.</div>
                    <div class="toolbox-field-item" data-field="DiscountAmount">Discount Amount</div>
                    <div class="toolbox-field-item" data-field="Discount">Discount</div>
                    <div class="toolbox-field-item" data-field="MakingType">Making Type</div>
                    <div class="toolbox-field-item" data-field="MakingRate">Making Rate</div>
                    <div class="toolbox-field-item" data-field="MakingDiscountAmt">Making Discount Amt.</div>
                    <div class="toolbox-field-item" data-field="MakingAmount">Making Amount</div>
                    <div class="toolbox-field-item" data-field="MakingActualValue">Making Actual Value</div>
                    <div class="toolbox-field-item" data-field="MakingCost">Making Cost</div>
                    <div class="toolbox-field-item" data-field="MinimumPrice">Minimum Price</div>
                    <div class="toolbox-field-item" data-field="MinimumCode">Minimum Code</div>
                    <div class="toolbox-field-item" data-field="StoneChargeType">Stone Charge Type</div>
                    <div class="toolbox-field-item" data-field="StoneRate">Stone Rate</div>
                    <div class="toolbox-field-item" data-field="StoneAmount">Stone Amount</div>
                    <div class="toolbox-field-item" data-field="StoneCost">Stone Cost</div>
                    <div class="toolbox-field-item" data-field="DiamondAmount">Diamond Amount</div>
                    <div class="toolbox-field-item" data-field="PurchaseAmount">Purchase Amount</div>
                    <div class="toolbox-field-item" data-field="SaleAmount">Sale Amount</div>
                    <div class="toolbox-field-item" data-field="SaleAmountWith">Sale Amount With Tax</div>
                    <div class="toolbox-field-item" data-field="NetAmt">Net Amt</div>
                    <div class="toolbox-field-item" data-field="TaxType">Tax Type</div>
                    <div class="toolbox-field-item" data-field="TaxPer">Tax %</div>
                    <div class="toolbox-field-item" data-field="Tax">Tax</div>
                    <div class="toolbox-field-item" data-field="OtherChargeType">Other Charge Type</div>
                    <div class="toolbox-field-item" data-field="OtherWeight">Other Weight</div>
                    <div class="toolbox-field-item" data-field="OtherRate">Other Rate</div>
                    <div class="toolbox-field-item" data-field="OtherInfo">Other Info</div>
                    <div class="toolbox-field-item" data-field="OtherAmount">Other Amount</div>
                    <div class="toolbox-field-item" data-field="HallmarkAmount">Hallmark Amount</div>
                    <div class="toolbox-field-item" data-field="HallmarkRate">Hallmark Rate</div>
                    <div class="toolbox-field-item" data-field="NetAmtPlusTax">Net Amt+Tax</div>
                    <div class="toolbox-field-item" data-field="Reverse">Reverse</div>
