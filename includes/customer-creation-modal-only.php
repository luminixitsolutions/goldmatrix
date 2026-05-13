<?php
if (!isset($countries_ledger)) {
    $countries_ledger = getList("SELECT id, name FROM tbl_countries WHERE status = 1 ORDER BY name ASC");
}
require_once __DIR__ . '/international-dial-codes.php';
require_once __DIR__ . '/ledger-modal-document-types-script.php';
?>
<!-- Right Side Customer Creation Modal -->
<div class="modal fade right" id="customerCreationModal" tabindex="-1" role="dialog" aria-labelledby="customerCreationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-right modal-xl" role="document" style="max-width: 90%; width: 90%; margin: 0; height: 100vh;">
        <div class="modal-content" style="height: 100vh; border-radius: 0; border: none;">
            <div class="modal-header" style="background: #11294b; color: #fff; border: none; padding: 1rem 1.5rem;">
                <h5 class="modal-title" id="customerCreationModalLabel">Ledger Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 1;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0; height: calc(100vh - 60px); overflow-y: auto; background: #f8fafc;">
                <form id="customerCreationForm" method="post" style="height: 100%;" enctype="multipart/form-data">
                    <input type="hidden" id="ledgerCustomerId" name="customer_id" value="">
                    <div style="padding: 1.5rem; max-width: 1400px; margin: 0 auto;">
                        <!-- Top Action Buttons -->
                        <div class="d-flex justify-content-end mb-2">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="clearCustomerForm()" style="margin-right: 0.5rem; padding: 0.4rem 0.75rem; font-size: 0.85rem;">Clear</button>
                            <button type="button" class="btn btn-primary btn-sm" id="customerModalSaveBtn" onclick="saveCustomer()" style="margin-right: 0.5rem; background: #11294b; border: none; padding: 0.4rem 0.75rem; font-size: 0.85rem;">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">Close</button>
                        </div>

                        <!-- Ledger Photo and Basic Info -->
                        <div class="row mb-3">
                            <div class="col-md-2">
                                <div style="text-align: center;">
                                    <div style="width: 120px; height: 120px; border-radius: 50%; background: #f1f5f9; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative; cursor: pointer;" onclick="document.getElementById('ledgerPhotoInput').click();">
                                        <i class="feather icon-camera" style="font-size: 1.5rem; color: #94a3b8;"></i>
                                        <input type="file" id="ledgerPhotoInput" name="ledger_photo" accept="image/*" style="display: none;" onchange="previewLedgerPhoto(this);">
                                    </div>
                                    <div id="ledgerPhotoPreview" style="display: none; width: 120px; height: 120px; border-radius: 50%; margin: 0 auto; overflow: hidden; border: 2px solid #c5a864;">
                                        <img id="ledgerPhotoImg" src="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div class="form-check mt-2" style="text-align: center;">
                                        <input class="form-check-input" type="checkbox" id="ledgerNameCapital" name="ledger_name_capital" style="width: 0.9rem; height: 0.9rem;">
                                        <label class="form-check-label" for="ledgerNameCapital" style="font-size: 0.75rem;">Ledger Name Capital</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Name *</label>
                                            <input type="text" class="form-control" id="ledgerName" name="name" required oninput="handleNameInput(this)">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Alternate Name</label>
                                            <input type="text" class="form-control" id="ledgerAlternateName" name="alternate_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" class="form-control" id="ledgerFirstName" name="first_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" id="ledgerLastName" name="last_name">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Mobile No <span style="color: red;">*</span></label>
                                            <div class="input-group">
                                                <select class="form-control" id="mobileCountryCode" name="mobile_country_code" style="max-width: 96px; font-size: 0.85rem; padding: 0.4rem 0.5rem; height: 32px;">
                                                    <?php auragold_render_dial_code_select('971'); ?>
                                                </select>
                                                <input type="text" class="form-control" id="ledgerMobileNo" name="mobile_no" placeholder="Mobile No" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Phone No</label>
                                            <div class="input-group">
                                                <select class="form-control" id="phoneCountryCode" name="phone_country_code" style="max-width: 96px; font-size: 0.85rem; padding: 0.4rem 0.5rem; height: 32px;">
                                                    <?php auragold_render_dial_code_select('971'); ?>
                                                </select>
                                                <input type="text" class="form-control" id="ledgerPhoneNo" name="phone_no" placeholder="Phone No" inputmode="numeric" autocomplete="tel">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Mail ID</label>
                                            <div class="input-group">
                                                <i class="feather icon-mail" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8;"></i>
                                                <input type="email" class="form-control" id="ledgerMailId" name="mail_id" placeholder="Email" style="padding-left: 35px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identity No</label>
                                            <input type="text" class="form-control" id="ledgerIdentityNo" name="identity_no">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>National Id</label>
                                            <div class="input-group">
                                                <i class="feather icon-credit-card" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8;"></i>
                                                <input type="text" class="form-control" id="ledgerNationalId" name="national_id" style="padding-left: 35px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Trade No</label>
                                            <div class="input-group">
                                                <i class="feather icon-briefcase" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8;"></i>
                                                <input type="text" class="form-control" id="ledgerTradeNo" name="trade_no" style="padding-left: 35px;">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identity Issue Date</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="identityIssueDate" name="identity_issue_date">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" style="padding: 0.4rem 0.5rem; height: 32px; border: 1px solid #e2e8f0; border-left: none; background: #f8fafc;"><i class="feather icon-calendar" style="font-size: 0.85rem;"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Identity Expiry Date</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="identityExpiryDate" name="identity_expiry_date">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" style="padding: 0.4rem 0.5rem; height: 32px; border: 1px solid #e2e8f0; border-left: none; background: #f8fafc;"><i class="feather icon-calendar" style="font-size: 0.85rem;"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Special Day</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="specialDay" name="special_day">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" style="padding: 0.4rem 0.5rem; height: 32px; border: 1px solid #e2e8f0; border-left: none; background: #f8fafc;"><i class="feather icon-calendar" style="font-size: 0.85rem;"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Customer Type *</label>
                                            <div class="input-group">
                                                <i class="feather icon-users" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8; font-size: 0.9rem;"></i>
                                                <select class="form-control" id="customerType" name="customer_type_id" style="padding-left: 32px;">
                                                    <option value="">Select Customer Type</option>
                                                    <?php 
                                                    $customer_types = getList("SELECT id, name FROM tbl_customer_types WHERE status = 1 ORDER BY name ASC");
                                                    foreach($customer_types as $type) {
                                                        echo '<option value="'.$type['id'].'">'.htmlspecialchars($type['name']).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Registration No</label>
                                            <input type="text" class="form-control" id="registrationNo" name="registration_no">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Registration Date</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="registrationDate" name="registration_date">
                                                <div class="input-group-append">
                                                    <span class="input-group-text" style="padding: 0.4rem 0.5rem; height: 32px; border: 1px solid #e2e8f0; border-left: none; background: #f8fafc;"><i class="feather icon-calendar" style="font-size: 0.85rem;"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>GSTIN <span style="font-weight:400;color:#64748b;">(e-Way)</span></label>
                                            <input type="text" class="form-control" id="ledgerGstin" name="gstin" maxlength="15" placeholder="Buyer GSTIN (15 chars)" autocomplete="off" style="text-transform:uppercase;">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Nationality</label>
                                            <div class="input-group">
                                                <i class="feather icon-flag" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8; font-size: 0.9rem;"></i>
                                                <select class="form-control" id="nationality" name="nationality_id" style="padding-left: 32px;">
                                                    <option value="">Select Nationality</option>
                                                    <?php 
                                                    $nationalities = getList("SELECT id, name FROM tbl_nationalities WHERE status = 1 ORDER BY name ASC");
                                                    foreach($nationalities as $nationality) {
                                                        echo '<option value="'.$nationality['id'].'">'.htmlspecialchars($nationality['name']).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Country</label>
                                            <div class="input-group">
                                                <i class="feather icon-flag" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8; font-size: 0.9rem;"></i>
                                                <select class="form-control" id="country" name="country_id" style="padding-left: 32px;">
                                                    <option value="">Select Country</option>
                                                    <?php 
                                                    $countries = getList("SELECT id, name FROM tbl_countries WHERE status = 1 ORDER BY name ASC");
                                                    foreach($countries as $country) {
                                                        echo '<option value="'.$country['id'].'">'.htmlspecialchars($country['name']).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>State</label>
                                            <select class="form-control" id="ledgerState" name="ledger_state_id">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>City</label>
                                            <select class="form-control" id="ledgerCity" name="ledger_city_id">
                                                <option value="">Select City</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Select Group</label>
                                            <div class="input-group">
                                                <i class="feather icon-users" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8; font-size: 0.9rem;"></i>
                                                <select class="form-control" id="ledgerGroup" name="group_id" style="padding-left: 32px;">
                                                    <option value="">Select Group</option>
                                                    <?php 
                                                    foreach($ledger_groups as $group) {
                                                        echo '<option value="'.$group['id'].'">'.htmlspecialchars($group['name']).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Sundry Debtors *</label>
                                            <div class="input-group">
                                                <i class="feather icon-users" style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); z-index: 10; color: #94a3b8; font-size: 0.9rem;"></i>
                                                <select class="form-control" id="ledgerSundryDebtors" name="sundry_debtors_id" required style="padding-left: 32px;">
                                                    <!-- <option value="">Select</option> -->
                                                    <?php 
                                                    foreach($sundry_options as $option) {
                                                        echo '<option value="'.$option['id'].'">'.htmlspecialchars($option['name']).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ledgerKYC" name="kyc">
                                                <label class="form-check-label" for="ledgerKYC">KYC</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="checkbox" id="ledgerAML" name="aml">
                                                <label class="form-check-label" for="ledgerAML">AML</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label" style="margin-right: 10px;">Bill to Bill:</label>
                                                <input class="form-check-input" type="radio" id="billToBillYes" name="bill_to_bill" value="1">
                                                <label class="form-check-label" for="billToBillYes" style="margin-right: 15px;">Yes</label>
                                                <input class="form-check-input" type="radio" id="billToBillNo" name="bill_to_bill" value="0" checked>
                                                <label class="form-check-label" for="billToBillNo">No</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address and Bank Details Tabs -->
                        <ul class="nav nav-tabs mb-3" id="ledgerTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="billing-tab" data-toggle="tab" href="#billing-address" role="tab">Billing Address</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="shipping-tab" data-toggle="tab" href="#shipping-address" role="tab">Shipping Address</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="item-type-tax-tab" data-toggle="tab" href="#item-type-tax" role="tab">Item Type Tax</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="share-holders-tab" data-toggle="tab" href="#share-holders" role="tab">Share Holders</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="notes-tab" data-toggle="tab" href="#notes" role="tab">Notes</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="nominee-tab" data-toggle="tab" href="#nominee" role="tab">Nominee</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="default-settings-tab" data-toggle="tab" href="#default-settings" role="tab">Default Settings</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="ledgerTabContent">
                            <!-- Billing Address Tab -->
                            <div class="tab-pane fade show active" id="billing-address" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Address 1</label>
                                            <input type="text" class="form-control" id="billingAddress1" name="billing_address1">
                                        </div>
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" id="billingAddress2" name="billing_address2">
                                        </div>
                                        <div class="form-group">
                                            <label>Country *</label>
                                            <select class="form-control" id="billingCountry" name="billing_country" required>
                                                <option value="">Select Country</option>
                                                <?php foreach ($countries_ledger as $co) {
                                                    $nm = htmlspecialchars($co['name'], ENT_QUOTES, 'UTF-8');
                                                    $cid = (int) $co['id'];
                                                    echo '<option value="' . $nm . '" data-country-id="' . $cid . '">' . $nm . '</option>';
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>State *</label>
                                            <select class="form-control" id="billingState" name="billing_state" required>
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>City</label>
                                            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                                <select class="form-control" id="billingCity" name="billing_city" style="flex: 1; min-width: 0;">
                                                    <option value="">Select City</option>
                                                </select>
                                                <button type="button" class="btn btn-light border rounded-circle city-info-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" title="Cities load for the selected state. Use + to add a new city under this state." tabindex="-1">
                                                    <i class="feather icon-info" style="font-size: 1rem; color: #64748b;"></i>
                                                </button>
                                                <button type="button" class="btn btn-light border rounded-circle city-add-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" data-target="billing" title="Add city under selected state">
                                                    <i class="feather icon-plus" style="font-size: 1rem; color: #11294b;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Zip Code</label>
                                            <input type="text" class="form-control" id="billingZipCode" name="billing_zip_code">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bank Details</label>
                                        </div>
                                        <div class="form-group">
                                            <label>Acc.No.</label>
                                            <input type="text" class="form-control" id="bankAccountNo" name="bank_account_no" placeholder="Account No">
                                        </div>
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" class="form-control" id="bankName" name="bank_name" placeholder="Bank Name">
                                        </div>
                                        <div class="form-group">
                                            <label>IFSC Code</label>
                                            <input type="text" class="form-control" id="bankIfscCode" name="bank_ifsc_code" placeholder="IFSC Code">
                                        </div>
                                        <div class="form-group">
                                            <label>Branch</label>
                                            <input type="text" class="form-control" id="bankBranch" name="bank_branch">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Other Tabs (Placeholder) -->
                            <div class="tab-pane fade" id="shipping-address" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Address 1</label>
                                            <input type="text" class="form-control" id="shippingAddress1" name="shipping_address1">
                                        </div>
                                        <div class="form-group">
                                            <label>Address 2</label>
                                            <input type="text" class="form-control" id="shippingAddress2" name="shipping_address2">
                                        </div>
                                        <div class="form-group">
                                            <label>Country</label>
                                            <select class="form-control" id="shippingCountry" name="shipping_country">
                                                <option value="">Select Country</option>
                                                <?php foreach ($countries_ledger as $co) {
                                                    $nm = htmlspecialchars($co['name'], ENT_QUOTES, 'UTF-8');
                                                    $cid = (int) $co['id'];
                                                    echo '<option value="' . $nm . '" data-country-id="' . $cid . '">' . $nm . '</option>';
                                                } ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>State</label>
                                            <select class="form-control" id="shippingState" name="shipping_state">
                                                <option value="">Select State</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>City</label>
                                            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                                <select class="form-control" id="shippingCity" name="shipping_city" style="flex: 1; min-width: 0;">
                                                    <option value="">Select City</option>
                                                </select>
                                                <button type="button" class="btn btn-light border rounded-circle city-info-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" title="Cities load for the selected state. Use + to add a new city under this state." tabindex="-1">
                                                    <i class="feather icon-info" style="font-size: 1rem; color: #64748b;"></i>
                                                </button>
                                                <button type="button" class="btn btn-light border rounded-circle city-add-btn d-inline-flex align-items-center justify-content-center" style="width: 34px; height: 34px; flex-shrink: 0; padding: 0;" data-target="shipping" title="Add city under selected state">
                                                    <i class="feather icon-plus" style="font-size: 1rem; color: #11294b;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Zip Code</label>
                                            <input type="text" class="form-control" id="shippingZipCode" name="shipping_zip_code">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="item-type-tax" role="tabpanel">
                                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <table class="item-tax-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%;">Item Name</th>
                                                <th style="width: 30%;">Default Input Type</th>
                                                <th style="width: 30%;">Default Output Type</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>AMOUNT</td>
                                                <td>
                                                    <select name="item_tax[AMOUNT][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[AMOUNT][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Gold</td>
                                                <td>
                                                    <select name="item_tax[Gold][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[Gold][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>GOLD - MAKING</td>
                                                <td>
                                                    <select name="item_tax[GOLD_MAKING][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[GOLD_MAKING][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Silver</td>
                                                <td>
                                                    <select name="item_tax[Silver][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[Silver][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>SILVER - MAKING</td>
                                                <td>
                                                    <select name="item_tax[SILVER_MAKING][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[SILVER_MAKING][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Diamond & Stones</td>
                                                <td>
                                                    <select name="item_tax[Diamond_Stones][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[Diamond_Stones][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Imitation Or Watches</td>
                                                <td>
                                                    <select name="item_tax[Imitation_Watches][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[Imitation_Watches][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>LOOSE - DIAMOND</td>
                                                <td>
                                                    <select name="item_tax[LOOSE_DIAMOND][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[LOOSE_DIAMOND][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>CERTIFIED - DIAMOND</td>
                                                <td>
                                                    <select name="item_tax[CERTIFIED_DIAMOND][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[CERTIFIED_DIAMOND][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Other Or Services</td>
                                                <td>
                                                    <select name="item_tax[Other_Services][input_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="item_tax[Other_Services][output_type]" class="form-control">
                                                        <option value="VAT" selected>VAT</option>
                                                        <option value="TAX BAH">TAX BAH</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="share-holders" role="tabpanel">
                                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <!-- Share Holders Table -->
                                    <div style="margin-bottom: 1.5rem;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <h6 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #1e293b;">Share Holders</h6>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <button type="button" class="btn btn-sm" id="addShareHolderBtn" style="background: #c5a864; color: #fff; border: none; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer;">
                                                    <i class="feather icon-plus" style="font-size: 0.85rem;"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; padding: 0.4rem 0.75rem; border-radius: 4px; cursor: pointer;">
                                                    <i class="feather icon-settings" style="font-size: 0.85rem;"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div style="overflow-x: auto;">
                                            <table class="table" id="shareHoldersTable" style="margin-bottom: 0; font-size: 0.85rem;">
                                                <thead style="background: #11294b; color: #fff;">
                                                    <tr>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; user-select: none;" onclick="sortShareHoldersTable(0)">
                                                            Name
                                                            <i class="feather icon-arrow-up" style="font-size: 0.7rem; margin-left: 0.25rem;"></i>
                                                            <i class="feather icon-arrow-down" style="font-size: 0.7rem; margin-left: 0.1rem;"></i>
                                                        </th>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; user-select: none;" onclick="sortShareHoldersTable(1)">
                                                            Nationality
                                                            <i class="feather icon-arrow-up" style="font-size: 0.7rem; margin-left: 0.25rem;"></i>
                                                            <i class="feather icon-arrow-down" style="font-size: 0.7rem; margin-left: 0.1rem;"></i>
                                                        </th>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer; user-select: none;" onclick="sortShareHoldersTable(2)">
                                                            Share Per.
                                                            <i class="feather icon-arrow-up" style="font-size: 0.7rem; margin-left: 0.25rem;"></i>
                                                            <i class="feather icon-arrow-down" style="font-size: 0.7rem; margin-left: 0.1rem;"></i>
                                                        </th>
                                                        <th style="padding: 0.6rem 1rem; font-weight: 600; font-size: 0.85rem; border: none; width: 60px; text-align: center;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="shareHoldersTableBody">
                                                    <!-- Rows will be added dynamically -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <!-- Upload Document Section -->
                                    <div style="margin-top: 1.5rem;">
                                        <?php require __DIR__ . '/customer-share-holder-documents-markup.php'; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="notes" role="tabpanel">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea class="form-control" id="ledgerNotes" name="notes" rows="5"></textarea>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nominee" role="tabpanel">
                                <p>Nominee content goes here</p>
                            </div>

                            <div class="tab-pane fade" id="default-settings" role="tabpanel">
                                <p>Default Settings content goes here</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/customer-share-holder-documents-prefill.js"></script>
<script src="js/customer-ledger-address.js"></script>
