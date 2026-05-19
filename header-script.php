<?php
require_once __DIR__ . '/includes/auragold_ui_font_settings.php';
?>
<link rel="icon" type="image/x-icon" href="assets/img/logo.png">

    <!-- Google Fonts (Set Software → Font Setting, or defaults) -->
    <?php auragold_ui_font_print_google_fonts_link(); ?>

    <!-- Icon fonts -->
    <link rel="stylesheet" href="assets/fonts/fontawesome.css">
    <link rel="stylesheet" href="assets/fonts/ionicons.css">
    <link rel="stylesheet" href="assets/fonts/linearicons.css">
    <link rel="stylesheet" href="assets/fonts/open-iconic.css">
    <link rel="stylesheet" href="assets/fonts/pe-icon-7-stroke.css">
    <link rel="stylesheet" href="assets/fonts/feather.css">

    <!-- Core stylesheets -->
    <link rel="stylesheet" href="assets/css/bootstrap-material.css">
    <link rel="stylesheet" href="assets/css/shreerang-material.css">
    <link rel="stylesheet" href="assets/css/uikit.css">

    <!-- Libs -->
    <link rel="stylesheet" href="assets/libs/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="assets/libs/flot/flot.css">
    <link rel="stylesheet" href="assets/css/newcss.css">
    <?php auragold_ui_font_print_overrides_style(); ?>
    <link rel="stylesheet" href="assets/libs/bootstrap-sweetalert/bootstrap-sweetalert.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="assets/css/advance-filter-global.css">
    <link rel="stylesheet" href="assets/css/product-list-invoice-layout.css?v=5">
    <link rel="stylesheet" href="assets/css/column-drag-icons.css">
    <link rel="stylesheet" href="assets/css/fs-financial-toolbar.css">
    <?php if (!empty($AURAGOLD_REPORT_PAGE)): ?>
    <link rel="stylesheet" href="assets/css/report-pages-mobile.css">
    <?php endif; ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/auragold-financial-export.js" defer></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <!-- DataTables Buttons Extension -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function () {

    /* ========= Ledger Table is initialized in accountledger-report.php ========= */

    /* ========= Initialize Transaction Table ONLY IF EXISTS ========= */
    if ($('#transactionTable').length && !$('#transactionTable').hasClass('no-datatable')) {
        if (!$.fn.DataTable.isDataTable('#transactionTable')) {
            $('#transactionTable').DataTable({
                ordering: false,
                paging: false,
                searching: false,
                info: false
            });
        }
    }

    /* ========= Fix Hidden Tab Issue ========= */
    $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
        $.fn.dataTable
            .tables({ visible: true, api: true })
            .columns.adjust()
            .draw();
    });

});
</script>

<style>
/* DataTables Custom Styling */
.dataTables_wrapper {
    width: 100%;
}
.dataTables_wrapper .datatable-header,
.dataTables_wrapper .dataTables_filter,
.dataTables_wrapper .dt-buttons,
.dataTables_wrapper .dataTables_length {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}
.datatable-header {
    display: flex !important;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    flex-wrap: wrap;
    gap: 10px;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 0;
}
.datatable-search {
    order: 1;
    flex: 1;
}
.datatable-buttons {
    order: 2;
    display: flex !important;
    gap: 8px;
}
.datatable-length {
    order: 3;
}
.datatable-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    flex-wrap: wrap;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}
.dataTables_wrapper .dataTables_filter {
    display: flex !important;
    align-items: center;
}
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px 12px;
    margin-left: 8px;
    min-width: 250px;
    font-size: 14px;
}
.dataTables_wrapper .dataTables_length {
    display: flex !important;
    align-items: center;
}
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 10px;
    margin: 0 5px;
}
.dt-buttons {
    display: flex !important;
    gap: 8px;
}
.dt-buttons .btn {
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 4px;
    display: inline-flex !important;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    text-decoration: none;
}
.dt-buttons .btn-success,
.dt-buttons .buttons-excel {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
    color: #fff !important;
}
.dt-buttons .btn-success:hover,
.dt-buttons .buttons-excel:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
}
.dt-buttons .btn-secondary,
.dt-buttons .buttons-print {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
    color: #fff !important;
}
.dt-buttons .btn-secondary:hover,
.dt-buttons .buttons-print:hover {
    background-color: #5a6268 !important;
    border-color: #545b62 !important;
}
.dataTables_info {
    padding: 8px 0;
    color: #666;
    font-size: 13px;
}
.dataTables_paginate {
    display: flex;
    align-items: center;
    gap: 4px;
}
.dataTables_paginate .paginate_button {
    padding: 6px 12px;
    margin: 0 2px;
    border-radius: 4px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
}
.dataTables_paginate .paginate_button.current {
    background: #11294b !important;
    color: #fff !important;
    border-color: #11294b !important;
}
.dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
    background: #f8f9fa;
    border-color: #ddd;
}
.dataTables_paginate .paginate_button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
/* Ensure table container doesn't hide DataTables controls */
.table-container {
    overflow-x: auto;
    overflow-y: visible;
}
.table-container .dataTables_wrapper {
    overflow: visible;
}

/* Customer / ledger modal tabs — inactive links were inheriting white text on light background */
ul#ledgerTabs.nav-tabs {
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
}
ul#ledgerTabs.nav-tabs .nav-link {
    color: #0f172a !important;
    background: transparent !important;
    border: none !important;
    border-bottom: 3px solid transparent !important;
    border-radius: 0 !important;
    font-weight: 600;
    font-size: 0.72rem;
    text-transform: uppercase;
    padding: 0.55rem 0.75rem;
}
ul#ledgerTabs.nav-tabs .nav-link:hover {
    color: #11294b !important;
}
ul#ledgerTabs.nav-tabs .nav-link.active {
    color: #11294b !important;
    border-bottom-color: #c5a864 !important;
    background: transparent !important;
}
</style>
<?php
require_once __DIR__ . '/includes/brand_page_loader.php';
if (auragold_brand_page_loader_should_show()) {
    echo auragold_brand_page_loader_css();
    echo auragold_brand_page_loader_js();
}
?>
