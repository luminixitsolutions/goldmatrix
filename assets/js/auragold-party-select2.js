/**
 * Searchable customer / supplier Select2 for voucher pages.
 * Config: window.AURAGOLD_PARTY_SELECT2 = { placeholder, searchUrl, partyId, partyName, ... }
 */
(function () {
    'use strict';

    function cfg() {
        var c = window.AURAGOLD_PARTY_SELECT2 || {};
        return {
            partyId: c.partyId || '#customerId',
            partyName: c.partyName || '#customerName',
            billingState: c.billingState || '#customerBillingState',
            gstin: c.gstin || '#customerGstin',
            wrapClass: c.wrapClass || 'auragold-party-select2-wrap',
            containerClass: c.containerClass || 'auragold-party-select2-container',
            dropdownClass: c.dropdownClass || 'auragold-party-select2-dropdown',
            searchUrl: c.searchUrl || 'ajax/search-customers.php',
            placeholder: c.placeholder || 'Select customer...',
            emptyLabel: c.emptyLabel || 'Customer',
            noResultsText: c.noResultsText || 'No account found',
            inputTooShortText: c.inputTooShortText || 'Type to search…'
        };
    }

    function hasSelect2() {
        return typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2;
    }

    function $partySel() {
        if (typeof jQuery === 'undefined') return null;
        return jQuery(cfg().partyId);
    }

    function syncPartyNameHidden(name) {
        var el = document.querySelector(cfg().partyName);
        if (el) {
            el.value = String(name || '').trim();
        }
    }

    function applyPartyMeta(data) {
        var c = cfg();
        var billingState = data && data.billing_state != null ? String(data.billing_state) : '';
        var gstin = data && data.gstin != null ? String(data.gstin).trim().replace(/\s+/g, '').toUpperCase() : '';
        var cbs = document.querySelector(c.billingState);
        if (cbs) {
            cbs.value = billingState;
        }
        if (typeof window.customerState !== 'undefined') {
            window.customerState = billingState || '';
        }
        if (gstin) {
            var cg = document.querySelector(c.gstin);
            if (cg) {
                cg.value = gstin;
            }
        }
    }

    function runAfterPick(partyId, partyName, meta) {
        var cid = partyId ? String(parseInt(partyId, 10)) : '';
        if (!cid || cid === 'NaN' || cid === '0') {
            syncPartyNameHidden('');
            if (typeof window.selectedCustomerId !== 'undefined') {
                window.selectedCustomerId = null;
            }
            applyPartyMeta({});
            if (typeof window.updateSaleInvoiceAddItemButtonState === 'function') {
                window.updateSaleInvoiceAddItemButtonState();
            }
            if (window.AURAGOLD_PARTY_SELECT2 && typeof window.AURAGOLD_PARTY_SELECT2.onClear === 'function') {
                window.AURAGOLD_PARTY_SELECT2.onClear();
            }
            if (typeof loadCustomerBalance === 'function') {
                loadCustomerBalance();
            }
            return;
        }

        syncPartyNameHidden(partyName || '');
        if (typeof window.selectedCustomerId !== 'undefined') {
            window.selectedCustomerId = cid;
        }
        applyPartyMeta(meta || {});

        if (typeof window.saleInvoiceApplyCustomerGstinFromServer === 'function') {
            window.saleInvoiceApplyCustomerGstinFromServer(cid);
        }
        if (typeof window.updateSaleInvoiceAddItemButtonState === 'function') {
            window.updateSaleInvoiceAddItemButtonState();
        }
        if (window.AURAGOLD_PARTY_SELECT2 && typeof window.AURAGOLD_PARTY_SELECT2.onPick === 'function') {
            window.AURAGOLD_PARTY_SELECT2.onPick(cid, partyName, meta || {});
        }

        if (typeof loadCustomerBalance === 'function') {
            loadCustomerBalance();
        }

        setTimeout(function () {
            if (typeof window.auragoldSaleInvoiceRefreshGstForAllRows === 'function') {
                window.auragoldSaleInvoiceRefreshGstForAllRows();
            }
        }, 100);
    }

    function destroyAuragoldPartySelect2() {
        var $s = $partySel();
        if (!$s || !$s.length || !hasSelect2()) return;
        if ($s.hasClass('select2-hidden-accessible')) {
            $s.off('.auragoldParty');
            $s.select2('destroy');
        }
    }

    function initAuragoldPartySelect2(overrideCfg) {
        if (overrideCfg) {
            window.AURAGOLD_PARTY_SELECT2 = jQuery.extend({}, window.AURAGOLD_PARTY_SELECT2 || {}, overrideCfg);
        }
        var c = cfg();
        var el = document.querySelector(c.partyId);
        if (!el || el.tagName !== 'SELECT' || !hasSelect2()) return;

        var emptyOpt = el.querySelector('option[value=""]');
        if (emptyOpt && emptyOpt.textContent) {
            var ph = String(emptyOpt.textContent).trim();
            if (ph) {
                c.placeholder = ph;
            }
            if (ph.toLowerCase().indexOf('supplier') >= 0) {
                c.emptyLabel = 'Supplier';
                c.noResultsText = 'No supplier found';
            }
        }

        var $s = jQuery(el);
        destroyAuragoldPartySelect2();

        var branchId = (typeof window.AURAGOLD_WORKING_BRANCH_ID !== 'undefined') ? window.AURAGOLD_WORKING_BRANCH_ID : '';

        $s.select2({
            placeholder: c.placeholder,
            allowClear: true,
            width: '100%',
            minimumInputLength: 0,
            dropdownParent: jQuery(document.body),
            containerCssClass: c.containerClass,
            dropdownCssClass: c.dropdownClass,
            language: {
                inputTooShort: function () { return c.inputTooShortText; },
                searching: function () { return 'Searching…'; },
                noResults: function () { return c.noResultsText; }
            },
            ajax: {
                url: c.searchUrl,
                dataType: 'json',
                delay: 280,
                data: function (params) {
                    var payload = { q: params.term || '', format: 'select2' };
                    if (branchId) {
                        payload.branch_id = branchId;
                    }
                    return payload;
                },
                processResults: function (data) {
                    var rows = (data && data.results) ? data.results : [];
                    return {
                        results: rows,
                        pagination: { more: !!(data && data.pagination && data.pagination.more) }
                    };
                },
                cache: true
            },
            templateResult: function (item) {
                if (!item.id) return item.text;
                var name = item.name || item.text || '';
                var $wrap = jQuery('<div>');
                $wrap.append(jQuery('<div>').css({ fontWeight: '600' }).text(name));
                if (item.mobile_no) {
                    $wrap.append(jQuery('<div>').css({ fontSize: '0.8rem', color: '#64748b' }).text(item.mobile_no));
                }
                if (item.alternate_name) {
                    $wrap.append(jQuery('<div>').css({ fontSize: '0.75rem', color: '#94a3b8' }).text(item.alternate_name));
                }
                return $wrap;
            },
            templateSelection: function (item) {
                return item.name || item.text || item.id || '';
            }
        });

        var skipNextPartyChange = false;

        $s.on('select2:select.auragoldParty', function (e) {
            var row = (e && e.params && e.params.data) ? e.params.data : {};
            var pid = String(row.id || $s.val() || '').trim();
            var pname = row.name || row.text || '';
            skipNextPartyChange = true;
            if ($s.hasClass('select2-hidden-accessible')) {
                $s.select2('close');
            }
            runAfterPick(pid, pname, row);
        });

        $s.on('select2:clear.auragoldParty', function () {
            skipNextPartyChange = true;
            runAfterPick('', '', {});
        });

        // Programmatic value changes (edit load, new customer save) — skip when user pick already handled.
        $s.on('change.auragoldParty', function () {
            if (skipNextPartyChange) {
                skipNextPartyChange = false;
                return;
            }
            var data = $s.select2('data');
            var row = (data && data.length) ? data[0] : null;
            var pid = String($s.val() || '').trim();
            if (!pid) {
                runAfterPick('', '', {});
                return;
            }
            var pname = row ? (row.name || row.text || '') : '';
            if (!pname && pid) {
                var opt = el.options[el.selectedIndex];
                pname = opt ? String(opt.text || '').trim() : '';
            }
            runAfterPick(pid, pname, row || {});
        });

        jQuery('.top-navbar').off('mouseenter.auragoldParty').on('mouseenter.auragoldParty', function () {
            if ($s.hasClass('select2-hidden-accessible')) {
                $s.select2('close');
            }
        });
    }

    function setAuragoldPartyValue(partyId, displayName, meta) {
        var c = cfg();
        var el = document.querySelector(c.partyId);
        if (!el || el.tagName !== 'SELECT') return;
        var pid = partyId ? String(parseInt(partyId, 10)) : '';
        if (!pid || pid === 'NaN' || pid === '0') {
            if (hasSelect2() && jQuery(el).hasClass('select2-hidden-accessible')) {
                jQuery(el).val(null).trigger('change');
            } else {
                el.value = '';
                syncPartyNameHidden('');
            }
            return;
        }

        var label = String(displayName || '').trim() || (c.emptyLabel + ' #' + pid);
        var $s = jQuery(el);
        if (!$s.find('option[value="' + pid + '"]').length) {
            $s.append(new Option(label, pid, true, true));
        }
        if (hasSelect2() && $s.hasClass('select2-hidden-accessible')) {
            $s.val(pid).trigger('change');
        } else {
            el.value = pid;
            syncPartyNameHidden(label);
            runAfterPick(pid, label, meta || {});
        }
    }

    function getAuragoldPartySearchTerm() {
        var c = cfg();
        var openField = document.querySelector('.' + c.wrapClass + ' .select2-container--open .select2-search__field');
        if (openField) {
            return String(openField.value || '').trim();
        }
        return '';
    }

    function preloadFromHiddenFields() {
        var c = cfg();
        var el = document.querySelector(c.partyId);
        var nameEl = document.querySelector(c.partyName);
        if (!el || el.tagName !== 'SELECT' || !el.value) return;
        var nm = nameEl ? String(nameEl.value || '').trim() : '';
        if (el.value && (nm || el.options.length <= 2)) {
            setAuragoldPartyValue(el.value, nm);
        }
    }

    function bindEnterOpensCustomerModal() {
        var c = cfg();
        jQuery(document).off('keydown.auragoldPartyEnter').on('keydown.auragoldPartyEnter', '.' + c.wrapClass + ' .select2-search__field', function (e) {
            if (e.key !== 'Enter') return;
            var term = getAuragoldPartySearchTerm();
            var el = document.querySelector(c.partyId);
            var pid = el ? String(el.value || '').trim() : '';
            if (pid || !term) return;
            if (!document.getElementById('customerCreationModal')) return;
            e.preventDefault();
            var $s = $partySel();
            if ($s && $s.hasClass('select2-hidden-accessible')) {
                $s.select2('close');
            }
            if (typeof initNewLedgerModalDefaults === 'function') {
                initNewLedgerModalDefaults();
            }
            jQuery('#customerCreationModal').modal('show');
            setTimeout(function () {
                var ledgerNameField = jQuery('#ledgerName');
                if (ledgerNameField.length) {
                    ledgerNameField.val(term);
                    if (typeof handleNameInput === 'function') {
                        handleNameInput(ledgerNameField[0]);
                    }
                    ledgerNameField.focus();
                }
            }, 300);
        });
    }

    window.initAuragoldPartySelect2 = initAuragoldPartySelect2;
    window.destroyAuragoldPartySelect2 = destroyAuragoldPartySelect2;
    window.setAuragoldPartyValue = setAuragoldPartyValue;
    window.getAuragoldPartySearchTerm = getAuragoldPartySearchTerm;
    window.setSaleInvoiceCustomerValue = setAuragoldPartyValue;
    window.getSaleInvoiceCustomerSearchTerm = getAuragoldPartySearchTerm;
    window.initSaleInvoiceCustomerSelect2 = initAuragoldPartySelect2;
    window.destroySaleInvoiceCustomerSelect2 = destroyAuragoldPartySelect2;

    function bootAuragoldPartySelect2() {
        if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.select2) {
            setTimeout(bootAuragoldPartySelect2, 40);
            return;
        }
        jQuery(function () {
            initAuragoldPartySelect2();
            preloadFromHiddenFields();
            bindEnterOpensCustomerModal();
        });
    }

    bootAuragoldPartySelect2();
})();
