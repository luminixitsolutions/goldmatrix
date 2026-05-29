/**
 * Jewellery catalogue Design No. dropdown in product selection modal (sale invoice, etc.).
 */
(function () {
    'use strict';

    var designListLoaded = false;
    var designListLoading = false;
    var catalogFetchInFlight = false;
    var select2LoadPromise = null;

    function getDesignSelect() {
        return document.getElementById('modalProductDesignNo');
    }

    function getProductListBody() {
        return document.getElementById('productListBody');
    }

    function getTableOverlay() {
        return document.getElementById('catalogDesignLoadOverlay');
    }

    function getAddRowFn() {
        if (typeof window.addEmptyProductRow === 'function') return window.addEmptyProductRow;
        return null;
    }

    function $designSelect() {
        var sel = getDesignSelect();
        if (!sel || typeof jQuery === 'undefined') return null;
        return jQuery(sel);
    }

    function hasSelect2() {
        return typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2;
    }

    function ensureSelect2Loaded() {
        if (hasSelect2()) return Promise.resolve(true);
        if (select2LoadPromise) return select2LoadPromise;

        select2LoadPromise = new Promise(function (resolve) {
            if (typeof jQuery === 'undefined') {
                resolve(false);
                return;
            }
            if (!document.getElementById('auragold-select2-css')) {
                var link = document.createElement('link');
                link.id = 'auragold-select2-css';
                link.rel = 'stylesheet';
                link.href = 'assets/libs/select2/select2.css';
                document.head.appendChild(link);
            }
            if (document.getElementById('auragold-select2-js')) {
                document.getElementById('auragold-select2-js').addEventListener('load', function () {
                    resolve(hasSelect2());
                });
                return;
            }
            var script = document.createElement('script');
            script.id = 'auragold-select2-js';
            script.src = 'assets/libs/select2/select2.js';
            script.onload = function () { resolve(hasSelect2()); };
            script.onerror = function () { resolve(false); };
            document.head.appendChild(script);
        });

        return select2LoadPromise;
    }

    function destroyDesignSelect2() {
        var $s = $designSelect();
        if (!$s || !hasSelect2()) return;
        if ($s.hasClass('select2-hidden-accessible')) {
            $s.off('change.catalogDesign');
            $s.select2('destroy');
        }
    }

    function initDesignSelect2() {
        var sel = getDesignSelect();
        if (!sel || !hasSelect2()) return;
        var $s = jQuery(sel);
        if ($s.hasClass('select2-hidden-accessible')) return;

        var $modal = jQuery('#productSelectionModal');
        $s.select2({
            placeholder: 'Select design no',
            allowClear: true,
            width: '100%',
            dropdownParent: $modal.length ? $modal : jQuery('body'),
            minimumResultsForSearch: 0
        });
        $s.on('change.catalogDesign', function () {
            onDesignNoChange();
        });
    }

    function syncDesignSelect2Value(value) {
        var sel = getDesignSelect();
        var $s = $designSelect();
        if (!sel || !$s || !hasSelect2() || !$s.hasClass('select2-hidden-accessible')) return;
        sel._catalogSuppressChange = true;
        $s.val(value || '').trigger('change.select2');
        sel._catalogSuppressChange = false;
    }

    function setDesignSelectBusy(busy) {
        var sel = getDesignSelect();
        if (!sel) return;
        sel.disabled = !!busy;
        var $s = $designSelect();
        if ($s && hasSelect2() && $s.hasClass('select2-hidden-accessible')) {
            $s.prop('disabled', !!busy).trigger('change.select2');
        }
    }

    function showPleaseWait(designNo) {
        var dn = String(designNo || '').trim();
        setDesignSelectBusy(true);
        var overlay = getTableOverlay();
        if (overlay) {
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
            var sub = overlay.querySelector('.catalog-design-load-overlay__sub');
            if (sub) {
                sub.textContent = dn ? ('Loading design ' + dn + '…') : 'Loading catalogue items…';
            }
            void overlay.offsetHeight;
        }
    }

    function hidePleaseWait() {
        setDesignSelectBusy(false);
        var overlay = getTableOverlay();
        if (overlay) {
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
        }
    }

    function populateDesignSelect(items, keepValue) {
        var sel = getDesignSelect();
        if (!sel) return Promise.resolve();

        var prev = keepValue ? (sel.value || '') : '';
        destroyDesignSelect2();
        sel._catalogSuppressChange = true;
        sel.innerHTML = '';
        var ph = document.createElement('option');
        ph.value = '';
        ph.textContent = 'Select design no';
        sel.appendChild(ph);
        (items || []).forEach(function (it) {
            if (!it || !it.design_no) return;
            var opt = document.createElement('option');
            opt.value = String(it.design_no);
            opt.textContent = it.label || it.design_no;
            if (it.id) opt.setAttribute('data-catalogue-id', String(it.id));
            if (it.metal_id) opt.setAttribute('data-metal-id', String(it.metal_id));
            sel.appendChild(opt);
        });
        if (prev) {
            sel.value = prev;
            if (sel.value !== prev) sel.value = '';
        }
        sel._catalogSuppressChange = false;

        return ensureSelect2Loaded().then(function (ok) {
            if (!ok) return;
            initDesignSelect2();
            if (prev && sel.value === prev) {
                syncDesignSelect2Value(prev);
            }
        });
    }

    function loadDesignNumbers(force) {
        var sel = getDesignSelect();
        if (!sel) return Promise.resolve();
        if (catalogFetchInFlight) return Promise.resolve();
        if (designListLoaded && !force) return Promise.resolve();
        if (designListLoading) return Promise.resolve();

        designListLoading = true;
        return fetch('ajax/list-jewelry-catalogue-design-nos.php', {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                designListLoading = false;
                if (!data || !data.success) return;
                designListLoaded = true;
                return populateDesignSelect(data.items || [], true);
            })
            .catch(function () {
                designListLoading = false;
            });
    }

    function switchModalMetalTab(metalId) {
        if (!metalId) return;
        var modal = document.getElementById('productSelectionModal');
        if (!modal) return;
        var btn = modal.querySelector('.category-tab-btn[data-metal-id="' + metalId + '"]');
        if (btn) {
            btn.click();
            return;
        }
        if (typeof window.filterProductsByMetal === 'function') {
            window.filterProductsByMetal(String(metalId));
        }
    }

    function showCatalogEmptyMessage(text) {
        var tbody = getProductListBody();
        if (!tbody) return;
        tbody.innerHTML = ''
            + '<tr class="catalog-design-empty">'
            + '<td colspan="73" class="text-center text-muted py-4">' + text + '</td>'
            + '</tr>';
    }

    function resolveRowMetalId(md, catalogueMetalId) {
        if (md && md.metal_id != null && String(md.metal_id).trim() !== '' && String(md.metal_id) !== '0') {
            return String(md.metal_id);
        }
        if (catalogueMetalId) return String(catalogueMetalId);
        return '';
    }

    function loadCatalogueIntoModal(designNo) {
        designNo = String(designNo || '').trim();
        if (!designNo) return Promise.resolve();
        if (catalogFetchInFlight) return Promise.resolve();

        var addRowFn = getAddRowFn();
        if (!addRowFn || typeof window.applyModalRowDataToSelectionRow !== 'function') {
            hidePleaseWait();
            alert('Product modal is not ready. Please refresh the page.');
            return Promise.resolve();
        }

        catalogFetchInFlight = true;
        showPleaseWait(designNo);

        return new Promise(function (resolve) {
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    resolve();
                });
            });
        }).then(function () {
            return fetch(
                'ajax/get-jewelry-catalogue-for-modal.php?design_no=' + encodeURIComponent(designNo),
                { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } }
            );
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                catalogFetchInFlight = false;
                hidePleaseWait();

                if (!data || !data.success) {
                    showCatalogEmptyMessage((data && data.message) || 'Could not load catalogue.');
                    alert((data && data.message) || 'Could not load catalogue.');
                    return;
                }

                var rows = data.modal_rows || [];
                if (!rows.length) {
                    showCatalogEmptyMessage('No Bill of Material items found for this design.');
                    alert('This catalogue has no Bill of Material items.');
                    return;
                }

                var tbody = getProductListBody();
                if (!tbody) return;

                tbody.innerHTML = '';
                var metalsUsed = {};

                rows.forEach(function (md) {
                    addRowFn();
                    var row = tbody.querySelector('.product-row:last-of-type');
                    if (!row) return;
                    var rowMetal = resolveRowMetalId(md, data.metal_id);
                    if (rowMetal) {
                        row.setAttribute('data-metal-id', rowMetal);
                        metalsUsed[rowMetal] = true;
                    }
                    row.setAttribute('data-catalogue-design', designNo);
                    window.applyModalRowDataToSelectionRow(row, md);
                });

                var firstMetal = Object.keys(metalsUsed)[0] || (data.metal_id ? String(data.metal_id) : '');
                if (firstMetal) {
                    switchModalMetalTab(firstMetal);
                } else if (typeof window.filterProductsByMetal === 'function') {
                    var active = document.querySelector('#productSelectionModal .category-tab-btn.active');
                    var mid = active ? active.getAttribute('data-metal-id') : '';
                    window.filterProductsByMetal(mid || '');
                }

                var gn = document.getElementById('modalGroupName');
                if (gn && data.title) {
                    gn.value = data.title;
                }

                var visible = tbody.querySelectorAll('tr.product-row:not([style*="display: none"])').length;
                if (visible === 0 && Object.keys(metalsUsed).length > 0) {
                    switchModalMetalTab(Object.keys(metalsUsed)[0]);
                }
            })
            .catch(function () {
                catalogFetchInFlight = false;
                hidePleaseWait();
                showCatalogEmptyMessage('Network error while loading catalogue.');
                alert('Could not load catalogue design.');
            });
    }

    function onDesignNoChange() {
        var sel = getDesignSelect();
        if (!sel || sel._catalogSuppressChange) return;
        var val = (sel.value || '').trim();
        if (!val) {
            if (catalogFetchInFlight) return;
            hidePleaseWait();
            return;
        }
        loadCatalogueIntoModal(val);
    }

    function bindDesignNoUi() {
        var sel = getDesignSelect();
        if (!sel || sel._catalogDesignBound) return;
        sel._catalogDesignBound = true;

        ensureSelect2Loaded().then(function (ok) {
            if (ok) {
                initDesignSelect2();
                return;
            }
            sel.addEventListener('change', onDesignNoChange);
        });
    }

    function onModalShown() {
        designListLoaded = false;
        hidePleaseWait();
        bindDesignNoUi();
        loadDesignNumbers(true);
    }

    function init() {
        bindDesignNoUi();
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('shown.bs.modal', '#productSelectionModal', onModalShown);
            jQuery(document).on('hidden.bs.modal', '#productSelectionModal', function () {
                catalogFetchInFlight = false;
                hidePleaseWait();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.auragoldLoadJewelryCatalogDesignNumbers = loadDesignNumbers;
    window.auragoldLoadJewelryCatalogIntoModal = loadCatalogueIntoModal;
    window.auragoldHideCatalogDesignLoader = hidePleaseWait;
    window.auragoldShowCatalogDesignLoader = showPleaseWait;
})();
