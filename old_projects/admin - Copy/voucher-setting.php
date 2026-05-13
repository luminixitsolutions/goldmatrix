<?php
session_start();
require_once 'config.php';
if (empty($_SESSION['Admin'])) {
    header('Location: index.php');
    exit;
}
auragold_ensure_branch_id_on_settings_tables($conn);
$settings_branch_id = auragold_settings_branch_id();
$settings_by_metal = getVoucherSettings(); // keyed by metal: Gold, Silver, ...
$metals = getVoucherSettingMetals();
$current_metal = 'Gold';
$vs = isset($settings_by_metal[$current_metal]) ? $settings_by_metal[$current_metal] : getVoucherSettingsDefaults();
?>
<!DOCTYPE html>
<html lang="en" class="default-style">
<head>
    <title>Voucher Setting - Set Software - AuraGold</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="favicon.jpeg">
    <?php include 'header-script.php'; ?>
    <link rel="stylesheet" href="set-software-sidebar.css">
    <style>
        /* Voucher Setting – primary #11294b (theme navy) */
        :root {
            --theme-navy: #11294b;
            --voucher-primary: #11294b;
            --voucher-primary-dark: #0d1f38;
            --voucher-primary-light: rgba(17, 41, 75, 0.12);
            --voucher-border: rgba(17, 41, 75, 0.4);
        }
        .voucher-setting-page { padding: 24px; max-width: 900px; margin: 0 auto; }
        .voucher-setting-page .card { border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .voucher-setting-page .card-body { padding: 24px; }
        .voucher-section-title {
            font-weight: 700;
            font-size: 1rem;
            color: var(--theme-navy);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(17, 41, 75, 0.25);
        }
        /* Reusable toggle / tab buttons – pill style */
        .voucher-toggle-group { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .voucher-toggle-btn {
            padding: 8px 18px;
            border-radius: 20px;
            border: 1px solid var(--voucher-border);
            background: var(--voucher-primary-light);
            color: var(--voucher-primary-dark);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .voucher-toggle-btn:hover {
            background: rgba(17, 41, 75, 0.2);
            border-color: var(--voucher-primary);
            color: var(--voucher-primary-dark);
        }
        .voucher-toggle-btn.active-btn {
            background: linear-gradient(135deg, var(--voucher-primary) 0%, var(--voucher-primary-dark) 100%);
            color: #fff;
            border-color: var(--voucher-primary);
        }
        .voucher-section { margin-bottom: 28px; }
        .voucher-section:last-child { margin-bottom: 0; }
        .voucher-page-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 24px; }
        .voucher-save-wrap { margin-top: 24px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .voucher-save-btn { background: linear-gradient(135deg, var(--voucher-primary) 0%, var(--voucher-primary-dark) 100%); color: #fff; border: none; padding: 10px 24px; border-radius: 20px; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .voucher-save-btn:hover { opacity: 0.95; }
        .voucher-save-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .voucher-save-msg { font-size: 13px; }
        .voucher-save-msg.success { color: #059669; }
        .voucher-save-msg.error { color: #dc2626; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="layout-content">
        <div class="container-fluid flex-grow-1" style="padding-top: 0; padding-bottom: 0;">
            <div class="set-software-wrapper">
                <?php include 'set-software-sidebar.php'; ?>
                <div class="set-software-main">
                    <?php include __DIR__ . '/includes/set-software-branch-banner.php'; ?>
                    <div class="voucher-setting-page">
                        <h1 class="voucher-page-title">Voucher Setting</h1>
                        <div class="card">
                            <div class="card-body">
                                <!-- 1. Metal Wise Setting (tabs: switch metal to edit its settings) -->
                                <div class="voucher-section">
                                    <div class="voucher-section-title">Metal Wise Setting</div>
                                    <div class="voucher-toggle-group voucher-metal-tabs" data-group="metalWise">
                                        <button type="button" class="voucher-toggle-btn active-btn" data-value="Gold">Gold</button>
                                        <button type="button" class="voucher-toggle-btn" data-value="Silver">Silver</button>
                                        <button type="button" class="voucher-toggle-btn" data-value="Platinum">Platinum</button>
                                        <button type="button" class="voucher-toggle-btn" data-value="Diamond & Stones">Diamond & Stones</button>
                                        <button type="button" class="voucher-toggle-btn" data-value="Imitation Or Watches">Imitation Or Watches</button>
                                        <button type="button" class="voucher-toggle-btn" data-value="Other Or Services">Other Or Services</button>
                                    </div>
                                </div>

                                <!-- 2. Minimum Amount Column -->
                                <div class="voucher-section">
                                    <div class="voucher-section-title">Minimum Amount Column</div>
                                    <div class="voucher-toggle-group" data-group="minimumAmountColumn">
                                        <?php $val = isset($vs['minimum_amount_column']) ? $vs['minimum_amount_column'] : 'Amount'; ?>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Amount' ? ' active-btn' : ''; ?>" data-value="Amount">Amount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'MakingAmount' ? ' active-btn' : ''; ?>" data-value="MakingAmount">MakingAmount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'NetAmount' ? ' active-btn' : ''; ?>" data-value="NetAmount">NetAmount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'NetAmountWithTax' ? ' active-btn' : ''; ?>" data-value="NetAmountWithTax">NetAmountWithTax</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Rate' ? ' active-btn' : ''; ?>" data-value="Rate">Rate</button>
                                    </div>
                                </div>

                                <!-- 3. Reverse Calculation Result Column -->
                                <div class="voucher-section">
                                    <div class="voucher-section-title">Reverse Calculation Result Column</div>
                                    <div class="voucher-toggle-group" data-group="reverseCalculationResultColumn">
                                        <?php $val = isset($vs['reverse_calculation_result_column']) ? $vs['reverse_calculation_result_column'] : 'MakingRate'; ?>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'DiscountAmount' ? ' active-btn' : ''; ?>" data-value="DiscountAmount">DiscountAmount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'MakingRate' ? ' active-btn' : ''; ?>" data-value="MakingRate">MakingRate</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Rate' ? ' active-btn' : ''; ?>" data-value="Rate">Rate</button>
                                    </div>
                                </div>

                                <!-- 4. Default Discount Type -->
                                <div class="voucher-section">
                                    <div class="voucher-section-title">Default Discount Type</div>
                                    <div class="voucher-toggle-group" data-group="defaultDiscountType">
                                        <?php $val = isset($vs['default_discount_type']) ? $vs['default_discount_type'] : 'Fix'; ?>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Fix' ? ' active-btn' : ''; ?>" data-value="Fix">Fix</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'On Amount' ? ' active-btn' : ''; ?>" data-value="On Amount">On Amount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'On Making Amount' ? ' active-btn' : ''; ?>" data-value="On Making Amount">On Making Amount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'On Diamond Amount' ? ' active-btn' : ''; ?>" data-value="On Diamond Amount">On Diamond Amount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'On Stone Amount' ? ' active-btn' : ''; ?>" data-value="On Stone Amount">On Stone Amount</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'On Net Amount' ? ' active-btn' : ''; ?>" data-value="On Net Amount">On Net Amount</button>
                                    </div>
                                </div>

                                <!-- 5. Default Calculation Type -->
                                <div class="voucher-section">
                                    <div class="voucher-section-title">Default Calculation Type</div>
                                    <div class="voucher-toggle-group" data-group="defaultCalculationType">
                                        <?php $val = isset($vs['default_calculation_type']) ? $vs['default_calculation_type'] : 'Fix'; ?>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Fix' ? ' active-btn' : ''; ?>" data-value="Fix">Fix</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Quantity X Rate' ? ' active-btn' : ''; ?>" data-value="Quantity X Rate">Quantity X Rate</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Carat X Rate' ? ' active-btn' : ''; ?>" data-value="Carat X Rate">Carat X Rate</button>
                                    </div>
                                </div>

                                <!-- 6. Stock Availability Check By -->
                                <div class="voucher-section">
                                    <div class="voucher-section-title">Stock Availability Check By</div>
                                    <div class="voucher-toggle-group" data-group="stockAvailabilityCheckBy">
                                        <?php $val = isset($vs['stock_availability_check_by']) ? $vs['stock_availability_check_by'] : 'Carat'; ?>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Carat' ? ' active-btn' : ''; ?>" data-value="Carat">Carat</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'GrossWt' ? ' active-btn' : ''; ?>" data-value="GrossWt">GrossWt</button>
                                        <button type="button" class="voucher-toggle-btn<?php echo $val === 'Quantity' ? ' active-btn' : ''; ?>" data-value="Quantity">Quantity</button>
                                    </div>
                                </div>

                                <div class="voucher-save-wrap">
                                    <button type="button" class="voucher-save-btn" id="voucherSaveBtn">Save Settings</button>
                                    <span class="voucher-save-msg" id="voucherSaveMsg" aria-live="polite"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        // Settings per metal (from DB). Key = metal name, value = { minimum_amount_column, ... }
        var settingsByMetal = <?php echo json_encode($settings_by_metal); ?>;
        var currentMetal = 'Gold';

        var voucherSettings = {
            minimumAmountColumn: settingsByMetal[currentMetal] ? settingsByMetal[currentMetal].minimum_amount_column : 'Amount',
            reverseCalculationResultColumn: settingsByMetal[currentMetal] ? settingsByMetal[currentMetal].reverse_calculation_result_column : 'MakingRate',
            defaultDiscountType: settingsByMetal[currentMetal] ? settingsByMetal[currentMetal].default_discount_type : 'Fix',
            defaultCalculationType: settingsByMetal[currentMetal] ? settingsByMetal[currentMetal].default_calculation_type : 'Fix',
            stockAvailabilityCheckBy: settingsByMetal[currentMetal] ? settingsByMetal[currentMetal].stock_availability_check_by : 'Carat'
        };

        function getSelectedSettings() {
            return {
                minimumAmountColumn: voucherSettings.minimumAmountColumn,
                reverseCalculationResultColumn: voucherSettings.reverseCalculationResultColumn,
                defaultDiscountType: voucherSettings.defaultDiscountType,
                defaultCalculationType: voucherSettings.defaultCalculationType,
                stockAvailabilityCheckBy: voucherSettings.stockAvailabilityCheckBy
            };
        }

        function applySettingsToUI() {
            var groupMap = {
                minimumAmountColumn: 'minimumAmountColumn',
                reverseCalculationResultColumn: 'reverseCalculationResultColumn',
                defaultDiscountType: 'defaultDiscountType',
                defaultCalculationType: 'defaultCalculationType',
                stockAvailabilityCheckBy: 'stockAvailabilityCheckBy'
            };
            document.querySelectorAll('.voucher-toggle-group').forEach(function(container) {
                var group = container.getAttribute('data-group');
                if (group === 'metalWise') {
                    container.querySelectorAll('.voucher-toggle-btn').forEach(function(btn) {
                        btn.classList.toggle('active-btn', btn.getAttribute('data-value') === currentMetal);
                    });
                    return;
                }
                var key = groupMap[group];
                if (!key || !voucherSettings[key]) return;
                var value = voucherSettings[key];
                container.querySelectorAll('.voucher-toggle-btn').forEach(function(btn) {
                    btn.classList.toggle('active-btn', btn.getAttribute('data-value') === value);
                });
            });
        }

        function loadMetal(metal) {
            currentMetal = metal;
            var s = settingsByMetal[metal];
            if (s) {
                voucherSettings.minimumAmountColumn = s.minimum_amount_column || 'Amount';
                voucherSettings.reverseCalculationResultColumn = s.reverse_calculation_result_column || 'MakingRate';
                voucherSettings.defaultDiscountType = s.default_discount_type || 'Fix';
                voucherSettings.defaultCalculationType = s.default_calculation_type || 'Fix';
                voucherSettings.stockAvailabilityCheckBy = s.stock_availability_check_by || 'Carat';
            }
            applySettingsToUI();
            console.log('Voucher Setting – now editing:', currentMetal, getSelectedSettings());
        }

        function setActiveInGroup(container, value) {
            var group = container.getAttribute('data-group');
            if (!group) return;
            if (group === 'metalWise') {
                loadMetal(value);
                return;
            }
            var key = { minimumAmountColumn: 1, reverseCalculationResultColumn: 1, defaultDiscountType: 1, defaultCalculationType: 1, stockAvailabilityCheckBy: 1 }[group];
            if (key) voucherSettings[key] = value;

            var buttons = container.querySelectorAll('.voucher-toggle-btn');
            buttons.forEach(function(btn) {
                btn.classList.toggle('active-btn', btn.getAttribute('data-value') === value);
            });
            console.log('Voucher Setting –', currentMetal, getSelectedSettings());
        }

        document.querySelectorAll('.voucher-toggle-group').forEach(function(group) {
            group.addEventListener('click', function(e) {
                var btn = e.target.closest('.voucher-toggle-btn');
                if (!btn) return;
                var value = btn.getAttribute('data-value');
                setActiveInGroup(group, value);
            });
        });

        // Save current metal's settings to database
        var saveBtn = document.getElementById('voucherSaveBtn');
        var saveMsg = document.getElementById('voucherSaveMsg');
        if (saveBtn && saveMsg) {
            saveBtn.addEventListener('click', function() {
                var opts = getSelectedSettings();
                var formData = new FormData();
                formData.append('metal_wise', currentMetal);
                formData.append('minimum_amount_column', opts.minimumAmountColumn);
                formData.append('reverse_calculation_result_column', opts.reverseCalculationResultColumn);
                formData.append('default_discount_type', opts.defaultDiscountType);
                formData.append('default_calculation_type', opts.defaultCalculationType);
                formData.append('stock_availability_check_by', opts.stockAvailabilityCheckBy);
                var sb = document.getElementById('settingsBranchId');
                if (sb) formData.append('settings_branch_id', sb.value);

                saveBtn.disabled = true;
                saveMsg.textContent = '';
                saveMsg.className = 'voucher-save-msg';

                fetch('ajax/save-voucher-settings.php', { method: 'POST', body: formData, credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        saveBtn.disabled = false;
                        if (data.status === 'success') {
                            saveMsg.textContent = data.message || 'Settings saved for ' + currentMetal + '.';
                            saveMsg.className = 'voucher-save-msg success';
                            if (settingsByMetal[currentMetal]) {
                                settingsByMetal[currentMetal].minimum_amount_column = opts.minimumAmountColumn;
                                settingsByMetal[currentMetal].reverse_calculation_result_column = opts.reverseCalculationResultColumn;
                                settingsByMetal[currentMetal].default_discount_type = opts.defaultDiscountType;
                                settingsByMetal[currentMetal].default_calculation_type = opts.defaultCalculationType;
                                settingsByMetal[currentMetal].stock_availability_check_by = opts.stockAvailabilityCheckBy;
                            }
                        } else {
                            saveMsg.textContent = data.message || 'Save failed.';
                            saveMsg.className = 'voucher-save-msg error';
                        }
                    })
                    .catch(function(err) {
                        saveBtn.disabled = false;
                        saveMsg.textContent = 'Error: ' + (err.message || 'Request failed');
                        saveMsg.className = 'voucher-save-msg error';
                    });
            });
        }

        applySettingsToUI();
    })();
    </script>
</body>
</html>
