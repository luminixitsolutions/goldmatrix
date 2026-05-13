<?php
/**
 * Echo after footer-script / jQuery. Requires $auragold_voucher_runtime_client (array) set by auragold_voucher_runtime_bootstrap() or payload_only().
 */
if (!isset($auragold_voucher_runtime_client) || !is_array($auragold_voucher_runtime_client)) {
    $auragold_voucher_runtime_client = [];
}
?>
<script src="assets/js/auragold-voucher-runtime-apply.js"></script>
<script>
window.AURAGOLD_VOUCHER_RUNTIME = <?php echo json_encode($auragold_voucher_runtime_client, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
document.addEventListener('DOMContentLoaded', function () {
    if (window.AuragoldVoucherRuntime && typeof window.AuragoldVoucherRuntime.applyTransactionVoucherRuntime === 'function') {
        window.AuragoldVoucherRuntime.applyTransactionVoucherRuntime(window.AURAGOLD_VOUCHER_RUNTIME || {});
    }
});
</script>
