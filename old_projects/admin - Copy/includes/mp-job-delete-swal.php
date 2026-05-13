<?php
/** Manufacturing Process — delete job card: SweetAlert confirmation + AJAX delete */
?>
<style>
.sweet-alert.mp-delete-jwo-swal .sa-icon {
    display: none !important;
}
.sweet-alert.mp-delete-jwo-swal {
    padding-top: 28px !important;
    border-radius: 12px !important;
}
.sweet-alert.mp-delete-jwo-swal h2 {
    color: #0f172a !important;
    font-weight: 700 !important;
    font-size: 1.2rem !important;
    margin-top: 4px !important;
}
.sweet-alert.mp-delete-jwo-swal p.lead {
    color: #64748b !important;
    font-size: 0.92rem !important;
    margin-top: 8px !important;
}
.sweet-alert.mp-delete-jwo-swal .sa-button-container {
    border-top: 1px solid #e2e8f0 !important;
    margin-top: 18px !important;
    padding-top: 14px !important;
    text-align: center !important;
}
.sweet-alert.mp-delete-jwo-swal button.cancel {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    color: #64748b !important;
    font-weight: 700 !important;
    font-size: 0.95rem !important;
    letter-spacing: 0.06em !important;
}
.sweet-alert.mp-delete-jwo-swal button.confirm {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    color: #dc2626 !important;
    font-weight: 700 !important;
    font-size: 0.95rem !important;
    letter-spacing: 0.06em !important;
    border-left: 1px solid #e2e8f0 !important;
    margin-left: 6px !important;
    padding-left: 18px !important;
    border-radius: 0 !important;
}
.mp-delete-jwo-swal-icon {
    text-align: center;
    margin-bottom: 6px;
}
.mp-delete-jwo-swal-icon i {
    font-size: 52px;
    color: #dc2626;
    line-height: 1;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var grid = document.getElementById('mpJobCardsGrid');
    if (!grid || grid._mpDeleteJwoBound) {
        return;
    }
    grid._mpDeleteJwoBound = true;
    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.mp-job-delete-btn');
        if (!btn || !grid.contains(btn)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        var jwoId = btn.getAttribute('data-jwo-id');
        if (!jwoId) {
            return;
        }
        var card = btn.closest('.mp-job-card');
        function doDelete() {
            var fd = new FormData();
            fd.append('jobwork_order_id', jwoId);
            btn.disabled = true;
            fetch('ajax/mp-delete-jobwork-order.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    btn.disabled = false;
                    if (!data || !data.ok) {
                        if (typeof swal === 'function') {
                            swal({
                                title: 'Cannot delete',
                                text: data && data.message ? data.message : 'Delete failed',
                                type: 'error',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            alert(data && data.message ? data.message : 'Delete failed');
                        }
                        return;
                    }
                    if (card && card.parentNode) {
                        card.parentNode.removeChild(card);
                    }
                    var pag = document.getElementById('mpCardsPaginationText');
                    if (pag && grid) {
                        var n = grid.querySelectorAll('.mp-job-card[data-jwo-id]').length;
                        pag.textContent = n === 0 ? 'No entries' : ('Showing ' + n + ' job work order' + (n === 1 ? '' : 's'));
                    }
                    if (typeof filterByDepartmentAndUser === 'function') {
                        filterByDepartmentAndUser();
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                    alert('Delete failed');
                });
        }
        if (typeof swal === 'function') {
            swal({
                html: true,
                title: '<div class="mp-delete-jwo-swal-icon"><i class="feather icon-trash-2"></i></div><div>Deleting</div>',
                text: 'Are you sure you want to delete from this list',
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'DELETE',
                cancelButtonText: 'CANCEL',
                confirmButtonClass: 'confirm',
                cancelButtonClass: 'cancel',
                customClass: 'mp-delete-jwo-swal'
            }, function (isConfirm) {
                if (isConfirm) {
                    doDelete();
                }
            });
        } else if (confirm('Are you sure you want to delete from this list?')) {
            doDelete();
        }
    });
});
</script>
