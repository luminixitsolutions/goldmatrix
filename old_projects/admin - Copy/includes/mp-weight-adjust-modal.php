<?php
/** Add / Reduce weight: opens full Jobwork Queue overlay (same as Transfer) with inline weight strip — manufacturing + jobwork-queue page */
?>
<script>
(function () {
    window.jwqToggleWeightStrip = function (show, weightMode) {
        var strip = document.getElementById('jwqWeightAdjustStrip');
        if (!strip) {
            return;
        }
        var modeHid = document.getElementById('jwqWeightAdjustMode');
        var titleEl = document.getElementById('jwqWeightAdjustTitle');
        var grams = document.getElementById('jwqWeightAdjustGrams');
        var remark = document.getElementById('jwqWeightAdjustRemark');
        if (show) {
            strip.style.display = '';
            strip.setAttribute('aria-hidden', 'false');
            var wm = (weightMode === 'add') ? 'add' : 'reduce';
            if (modeHid) {
                modeHid.value = wm;
            }
            if (titleEl) {
                titleEl.textContent = wm === 'add' ? 'Add Weight' : 'Reduce Weight';
            }
            if (grams) {
                grams.value = '';
            }
            if (remark) {
                remark.value = '';
            }
            try {
                strip.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } catch (e1) {}
        } else {
            strip.style.display = 'none';
            strip.setAttribute('aria-hidden', 'true');
        }
    };

    function handleWeightButtonClick(btn, e) {
        var mode = (btn.getAttribute('data-weight-mode') || 'reduce').toLowerCase();
        if (btn.closest('#jwqModalOverlay')) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var jwoEl = document.getElementById('jwqCurrentJwoId');
            var jwoId = jwoEl ? parseInt(jwoEl.value || '0', 10) : 0;
            if (jwoId < 1) {
                alert('No job work order loaded.');
                return;
            }
            window.jwqToggleWeightStrip(true, mode);
            return;
        }
        if (typeof jwqOpenModal !== 'function') {
            alert('Jobwork Queue modal is not available.');
            return;
        }
        jwqOpenModal(btn, { forWeight: true, weightMode: mode });
    }

    function mpWeightAdjOpen(mode, jwoId, jobworkNo) {
        mode = (mode === 'add') ? 'add' : 'reduce';
        var idNum = parseInt(jwoId, 10);
        if (isNaN(idNum) || idNum < 1) {
            alert('Select a job work order first.');
            return;
        }
        var grid = document.getElementById('mpJobCardsGrid');
        var sel = '.mp-weight-btn[data-jwo-id="' + idNum + '"][data-weight-mode="' + mode + '"]';
        var btn = grid ? grid.querySelector(sel) : null;
        if (!btn && grid) {
            btn = grid.querySelector('.mp-weight-btn[data-jwo-id="' + idNum + '"]');
        }
        if (btn && typeof jwqOpenModal === 'function') {
            jwqOpenModal(btn, { forWeight: true, weightMode: mode });
            return;
        }
        alert('Open the job from the manufacturing grid to adjust weight.');
    }

    function initMpWeightAdjustModal() {
        var grid = document.getElementById('mpJobCardsGrid');
        if (grid && !grid._mpWeightBound) {
            grid._mpWeightBound = true;
            grid.addEventListener('click', function (e) {
                var btn = e.target.closest('.mp-weight-btn');
                if (!btn || !grid.contains(btn)) {
                    return;
                }
                handleWeightButtonClick(btn, e);
            });
        }

        document.querySelectorAll('.mp-weight-btn').forEach(function (btn) {
            if (btn.closest('#mpJobCardsGrid')) {
                return;
            }
            if (btn._mpWeightDirectBound) {
                return;
            }
            btn._mpWeightDirectBound = true;
            btn.addEventListener('click', function (e) {
                handleWeightButtonClick(btn, e);
            });
        });

        var saveBtn = document.getElementById('jwqWeightAdjustSaveBtn');
        if (saveBtn && !saveBtn._mpWeightSaveBound) {
            saveBtn._mpWeightSaveBound = true;
            saveBtn.addEventListener('click', function () {
                var hidId = document.getElementById('jwqCurrentJwoId');
                var hidMode = document.getElementById('jwqWeightAdjustMode');
                var wEl = document.getElementById('jwqWeightAdjustGrams');
                var rEl = document.getElementById('jwqWeightAdjustRemark');
                var id = hidId ? parseInt(hidId.value || '0', 10) : 0;
                var mode = (hidMode && hidMode.value === 'add') ? 'add' : 'reduce';
                var w = wEl ? parseFloat(String(wEl.value || '').replace(/,/g, '')) : NaN;
                if (id < 1) {
                    alert('Invalid job work order.');
                    return;
                }
                if (!isFinite(w) || w <= 0) {
                    alert('Enter a valid weight greater than zero.');
                    return;
                }
                var fd = new FormData();
                fd.append('jobwork_order_id', String(id));
                fd.append('adjustment_type', mode);
                fd.append('weight_grams', String(w));
                fd.append('remark', rEl ? String(rEl.value || '').trim() : '');
                saveBtn.disabled = true;
                fetch('ajax/mp-save-jobwork-weight-adjustment.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        saveBtn.disabled = false;
                        if (!data || !data.ok) {
                            alert(data && data.message ? data.message : 'Save failed');
                            return;
                        }
                        if (typeof window.jwqToggleWeightStrip === 'function') {
                            window.jwqToggleWeightStrip(false);
                        }
                        if (typeof window.mpReloadManufacturingQueueTable === 'function') {
                            window.mpReloadManufacturingQueueTable();
                        }
                        alert(data.message || 'Saved.');
                    })
                    .catch(function () {
                        saveBtn.disabled = false;
                        alert('Save failed');
                    });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', initMpWeightAdjustModal);
    window.mpWeightAdjOpen = mpWeightAdjOpen;
})();
</script>
