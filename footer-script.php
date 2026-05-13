 <script src="assets/js/pace.js"></script>
    <script src="assets/js/jquery-3.3.1.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/libs/popper/popper.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script src="assets/js/sidenav.js"></script>

<!-- Libs -->
<script src="assets/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="assets/libs/datatables/datatables.js"></script>

<!-- Demo -->
<script src="assets/js/demo.js"></script>
<script src="assets/js/analytics.js"></script>



   
    <script src="assets/js/layout-helpers.js"></script>
    <script src="assets/js/material-ripple.js"></script>

    <!-- Libs -->

    <script src="assets/libs/bootbox/bootbox.js"></script>
    <script src="assets/libs/bootstrap-sweetalert/bootstrap-sweetalert.js"></script>

    <!-- Demo -->
    <script src="assets/js/pages/ui_modals.js"></script>

    <!-- Ledger Details / customer modal: mobile number digits only -->
    <script>
    (function ($) {
        $(document).on('input', '#ledgerMobileNo', function () {
            var digits = String(this.value).replace(/\D/g, '');
            if (this.value !== digits) {
                this.value = digits;
            }
        });
    })(jQuery);
    </script>