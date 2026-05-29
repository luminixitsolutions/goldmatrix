<?php
/**
 * Searchable party (customer / supplier) Select2 field for voucher pages.
 */
if (!function_exists('auragold_party_select2_defaults')) {
    function auragold_party_select2_defaults(): array
    {
        return [
            'party_id' => 'customerId',
            'party_name' => 'customerName',
            'billing_state' => 'customerBillingState',
            'show_billing_state' => true,
            'show_add_btn' => true,
            'add_btn_id' => 'addCustomerBtn',
            'add_btn_title' => 'Add / Edit Customer',
            'placeholder' => 'Select customer...',
            'required' => true,
            'readonly' => false,
            'value_id' => '',
            'value_name' => '',
            'wrap_class' => 'auragold-party-select2-wrap',
        ];
    }
}

if (!function_exists('auragold_echo_party_select2_styles')) {
    function auragold_echo_party_select2_styles(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        echo '<style>
.auragold-party-select2-wrap { flex: 1; min-width: 0; }
.auragold-party-select2-wrap .select2-container { width: 100% !important; max-width: 100%; }
.auragold-party-select2-wrap .select2-container--default .select2-selection--single {
    height: calc(1.5em + 0.5rem + 2px);
    min-height: calc(1.5em + 0.5rem + 2px);
    border: 1px solid #ced4da;
    border-radius: 0.2rem;
    font-size: 0.875rem;
}
.auragold-party-select2-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: calc(1.5em + 0.5rem);
    padding-left: 0.5rem;
    color: #495057;
}
.auragold-party-select2-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.5rem + 2px);
}
.select2-container.auragold-party-select2-container { z-index: 50 !important; }
.select2-dropdown.auragold-party-select2-dropdown { z-index: 50 !important; }
.top-navbar, #auragoldTopNav { position: relative; z-index: 1200 !important; }
.modal-backdrop { z-index: 1240 !important; }
.modal { z-index: 1250 !important; }
body.modal-open .top-navbar,
body.modal-open #auragoldTopNav { z-index: 1030 !important; }
</style>';
    }
}

if (!function_exists('auragold_echo_party_select2_scripts')) {
    function auragold_echo_party_select2_scripts(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $v = @filemtime(__DIR__ . '/../assets/js/auragold-party-select2.js');
        echo '<link rel="stylesheet" href="assets/libs/select2/select2.css">' . "\n";
        echo '<script src="assets/libs/select2/select2.js"></script>' . "\n";
        echo '<script src="assets/js/auragold-party-select2.js?v=' . (int) $v . '"></script>' . "\n";
    }
}

if (!function_exists('auragold_echo_party_select2_assets')) {
    function auragold_echo_party_select2_assets(): void
    {
        auragold_echo_party_select2_styles();
        auragold_echo_party_select2_scripts();
    }
}

if (!function_exists('auragold_party_select2_field')) {
    function auragold_party_select2_field(array $args = []): void
    {
        $a = array_merge(auragold_party_select2_defaults(), $args);
        $partyId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $a['party_id']) ?: 'customerId';
        $partyName = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $a['party_name']) ?: 'customerName';
        $billingState = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $a['billing_state']) ?: 'customerBillingState';
        $wrapClass = htmlspecialchars((string) $a['wrap_class'], ENT_QUOTES, 'UTF-8');
        $placeholder = htmlspecialchars((string) $a['placeholder'], ENT_QUOTES, 'UTF-8');
        $valueId = (int) ($a['value_id'] ?? 0);
        $valueName = htmlspecialchars((string) ($a['value_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $req = !empty($a['required']) ? ' required' : '';
        $dis = !empty($a['readonly']) ? ' disabled' : '';
        $addBtnId = htmlspecialchars((string) ($a['add_btn_id'] ?? 'addCustomerBtn'), ENT_QUOTES, 'UTF-8');
        $addTitle = htmlspecialchars((string) ($a['add_btn_title'] ?? 'Add / Edit Customer'), ENT_QUOTES, 'UTF-8');
        echo '<div style="display:flex;align-items:stretch;gap:4px;">';
        echo '<div class="' . $wrapClass . '">';
        echo '<select class="form-control form-control-sm" id="' . htmlspecialchars($partyId, ENT_QUOTES, 'UTF-8') . '" name="customer_id"' . $req . $dis . '>';
        echo '<option value="">' . $placeholder . '</option>';
        if ($valueId > 0 && $valueName !== '') {
            echo '<option value="' . $valueId . '" selected>' . $valueName . '</option>';
        } elseif ($valueId > 0) {
            echo '<option value="' . $valueId . '" selected>#' . $valueId . '</option>';
        }
        echo '</select>';
        echo '<input type="hidden" id="' . htmlspecialchars($partyName, ENT_QUOTES, 'UTF-8') . '" name="customer_name" value="' . $valueName . '">';
        if (!empty($a['show_billing_state'])) {
            echo '<input type="hidden" id="' . htmlspecialchars($billingState, ENT_QUOTES, 'UTF-8') . '" name="customer_billing_state" value="">';
        }
        echo '</div>';
        if (!empty($a['show_add_btn']) && empty($a['readonly'])) {
            echo '<button type="button" class="btn btn-sm btn-outline-secondary p-0" id="' . $addBtnId . '" title="' . $addTitle . '" style="width:32px;min-width:32px;line-height:1;align-self:stretch;"><i class="feather icon-plus"></i></button>';
        }
        echo '</div>';
    }
}
