/**
 * Product modal barcode field: space-separated barcodes (e.g. "GD00018 GD00020 GD00021")
 * on Enter / Tab / blur — fetch each barcode sequentially with a loading overlay.
 */
(function (global) {
    'use strict';

    var LOADER_ID = 'productModalBarcodeBatchLoader';
    var STYLES_ID = 'productModalBarcodeBatchLoaderStyles';

    function parseTokens(raw) {
        return String(raw || '').trim().split(/\s+/).filter(function (s) {
            return s.length > 0;
        });
    }

    function batchIsActive() {
        return !!(global.__auragoldModalBarcodeBatch && global.__auragoldModalBarcodeBatch.active);
    }

    function ensureLoaderStyles() {
        if (document.getElementById(STYLES_ID)) return;
        var style = document.createElement('style');
        style.id = STYLES_ID;
        style.textContent = [
            '#productSelectionModal .modal-content { position: relative; }',
            '#' + LOADER_ID + '.product-modal-barcode-batch-loader {',
            '  display: none; position: absolute; left: 0; top: 0; right: 0; bottom: 0;',
            '  z-index: 1090; align-items: center; justify-content: center; flex-direction: column;',
            '  background: rgba(15, 23, 42, 0.55); border-radius: 0.3rem;',
            '}',
            '#' + LOADER_ID + '.product-modal-barcode-batch-loader.is-visible { display: flex; }',
            '#' + LOADER_ID + ' .product-modal-barcode-batch-loader__panel {',
            '  text-align: center; padding: 1.5rem 2rem; background: #1e293b; color: #f8fafc;',
            '  border-radius: 8px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25); max-width: 92%;',
            '}',
            '#' + LOADER_ID + ' .product-modal-barcode-batch-loader__spinner {',
            '  width: 2.5rem; height: 2.5rem; margin: 0 auto 1rem;',
            '  border: 3px solid rgba(248, 250, 252, 0.25); border-top-color: #c5a864;',
            '  border-radius: 50%; animation: productModalBarcodeBatchSpin 0.7s linear infinite;',
            '}',
            '#' + LOADER_ID + ' .product-modal-barcode-batch-loader__text {',
            '  margin: 0; font-size: 0.95rem; font-weight: 600; color: #f1f5f9;',
            '}',
            '@keyframes productModalBarcodeBatchSpin { to { transform: rotate(360deg); } }'
        ].join('\n');
        document.head.appendChild(style);
    }

    function ensureLoaderDom() {
        var modal = document.getElementById('productSelectionModal');
        if (!modal) return null;
        var content = modal.querySelector('.modal-content');
        if (!content) return null;
        var el = document.getElementById(LOADER_ID);
        if (!el) {
            ensureLoaderStyles();
            el = document.createElement('div');
            el.id = LOADER_ID;
            el.className = 'product-modal-barcode-batch-loader';
            el.setAttribute('aria-hidden', 'true');
            el.setAttribute('role', 'status');
            el.innerHTML = '<div class="product-modal-barcode-batch-loader__panel">'
                + '<div class="product-modal-barcode-batch-loader__spinner" aria-hidden="true"></div>'
                + '<p class="product-modal-barcode-batch-loader__text">Loading items…</p>'
                + '</div>';
            content.appendChild(el);
        }
        return el;
    }

    function showLoader(currentIndex, total, barcode) {
        var el = ensureLoaderDom();
        if (!el) return;
        var textEl = el.querySelector('.product-modal-barcode-batch-loader__text');
        if (textEl) {
            var n = Math.max(1, Math.min(currentIndex, total));
            var line = 'Loading items (' + n + ' of ' + total + ')';
            if (barcode) {
                line += ' — ' + barcode;
            }
            textEl.textContent = line + '…';
        }
        el.classList.add('is-visible');
        el.setAttribute('aria-hidden', 'false');
    }

    function hideLoader() {
        var el = document.getElementById(LOADER_ID);
        if (!el) return;
        el.classList.remove('is-visible');
        el.setAttribute('aria-hidden', 'true');
    }

    function clearInput(barcodeInput) {
        if (!barcodeInput) return;
        if (batchIsActive()) return;
        barcodeInput.value = '';
        barcodeInput.style.borderColor = '';
        try {
            barcodeInput.focus();
        } catch (e) {}
    }

    function batchStep() {
        var b = global.__auragoldModalBarcodeBatch;
        if (!b || !b.active) return;
        if (b.idx >= b.list.length) {
            b.active = false;
            hideLoader();
            if (b.input) {
                b.input.value = '';
                b.input.style.borderColor = '';
                try {
                    b.input.focus();
                } catch (e) {}
            }
            global.__auragoldModalBarcodeBatch = null;
            return;
        }
        var bc = b.list[b.idx];
        showLoader(b.idx + 1, b.list.length, bc);
        if (typeof b.fetchOne === 'function') {
            b.fetchOne(bc);
        }
        b.idx++;
    }

    function startBatch(barcodes, inputEl, fetchOne) {
        if (!barcodes || !barcodes.length || typeof fetchOne !== 'function') return;
        if (batchIsActive()) return;
        global.__auragoldModalBarcodeBatch = {
            active: true,
            input: inputEl || null,
            list: barcodes.slice(),
            idx: 0,
            fetchOne: fetchOne
        };
        batchStep();
    }

    function onFetchComplete() {
        if (batchIsActive()) {
            setTimeout(batchStep, 40);
        }
    }

    function onModalHidden(e) {
        var t = e && e.target;
        if (!t || t.id !== 'productSelectionModal') return;
        hideLoader();
        global.__auragoldModalBarcodeBatch = null;
    }

    if (typeof document !== 'undefined') {
        document.addEventListener('hidden.bs.modal', onModalHidden);
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('hidden.bs.modal', '#productSelectionModal', function () {
                hideLoader();
                global.__auragoldModalBarcodeBatch = null;
            });
        }
    }

    global.auragoldParseModalBarcodeTokens = parseTokens;
    global.auragoldModalBarcodeBatchIsActive = batchIsActive;
    global.auragoldClearModalProductBarcodeInput = clearInput;
    global.auragoldStartModalBarcodeBatch = startBatch;
    global.auragoldModalBarcodeBatchOnFetchComplete = onFetchComplete;
    global.auragoldShowModalBarcodeBatchLoader = showLoader;
    global.auragoldHideModalBarcodeBatchLoader = hideLoader;
})(typeof window !== 'undefined' ? window : this);
