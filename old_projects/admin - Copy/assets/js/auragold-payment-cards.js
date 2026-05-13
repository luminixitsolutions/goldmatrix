/**
 * Auragold — payment lines as cards (shared across invoice / quotation / return / etc.).
 * Expects: #paymentTableBody, #paymentTableFooter, global `payments` array, window.editPayment, window.deletePayment.
 */
(function (global) {
    'use strict';

    function escAttr(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }
    function escHtml(s) {
        return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }
    function currencyCode() {
        var sel = document.getElementById('currency');
        if (sel && sel.value) return String(sel.value).trim();
        return 'AED';
    }
    function cardHeaderTitle(type) {
        if (type === 'cash') return 'Cash';
        if (type === 'bank') return 'Bank';
        if (type === 'cheque') return 'Cheque';
        if (type === 'upi') return 'UPI';
        if (type === 'card') return 'Card';
        if (type === 'metal-exchange') return 'MetalExchange';
        if (type === 'scrap') return 'Scrap';
        return 'Other';
    }
    function saveTypeLabel(type) {
        if (type === 'cash') return 'Cash';
        if (type === 'bank') return 'Bank';
        if (type === 'cheque') return 'Cheque';
        if (type === 'upi') return 'UPI';
        if (type === 'card') return 'Card';
        if (type === 'metal-exchange') return 'M. Exch.';
        if (type === 'scrap') return 'Scrap';
        return 'Other';
    }
    function cardSubtitle(p) {
        var t = p.type;
        if (t === 'metal-exchange') {
            return String(p.metal_exchange_product_name || p.deposit_into || '').trim() || 'Metal exchange';
        }
        if (t === 'scrap') {
            return String(p.scrap_product_name || '').trim() || 'Scrap';
        }
        if (t === 'cheque') {
            var bits = [String(p.deposit_into || '').trim(), String(p.transaction_no || '').trim()].filter(Boolean);
            return bits.join(' · ') || 'Cheque';
        }
        if (t === 'bank' || t === 'upi' || t === 'card') {
            return String(p.deposit_into || p.transaction_no || '').trim() || '—';
        }
        if (t === 'cash') {
            return String(p.deposit_into || '').trim() || 'Cash';
        }
        return String(p.deposit_into || p.transaction_no || '').trim() || '—';
    }
    function metalWeightBadge(p) {
        if (p.type !== 'metal-exchange') return '';
        var w = parseFloat(p.metal_exchange_gross_wt);
        if (isNaN(w) || w <= 0) {
            w = parseFloat(p.quantity);
        }
        if (isNaN(w) || w <= 0) return '';
        var ws = (Math.abs(w - Math.round(w)) < 1e-6) ? String(Math.round(w)) : String(w);
        return '<span class="pos-payment-card-wt" title="Gross wt / qty"><i class="feather icon-layers"></i> ' + escHtml(ws) + '</span>';
    }

    function buildCardElement(payment) {
        var row = document.createElement('div');
        row.className = 'pos-payment-card';
        row.id = payment.id;
        row.setAttribute('data-payment-id', payment.id);
        row.setAttribute('data-payment-type', payment.type || 'cash');
        row.setAttribute('data-deposit-into', payment.deposit_into || '');
        row.setAttribute('data-transaction-no', payment.transaction_no || '');
        row.setAttribute('data-cheque-date', payment.cheque_date || '');
        row.setAttribute('data-purity-carat', payment.purity_carat != null ? String(payment.purity_carat) : '');
        row.setAttribute('data-diamond-category', payment.diamond_category || '');
        row.setAttribute('data-quantity', payment.quantity != null && payment.quantity !== '' ? String(payment.quantity) : '0');
        var previousBalanceAmount = parseFloat(payment.previous_balance_amount || 0);
        var curAmt = parseFloat(payment.amount || 0);
        var totalPaymentAmount = curAmt + previousBalanceAmount;
        row.setAttribute('data-previous-balance-amount', previousBalanceAmount.toFixed(2));
        row.setAttribute('data-current-order-amount', curAmt.toFixed(2));
        var hdr = cardHeaderTitle(payment.type);
        var sub = cardSubtitle(payment);
        var cc = currencyCode();
        var amtTitle = 'Current: ' + curAmt.toFixed(2) + ', Previous balance: ' + previousBalanceAmount.toFixed(2);
        var wtHtml = metalWeightBadge(payment);
        row.innerHTML =
            '<div class="pos-payment-card-hd">' +
            '<span class="pos-payment-card-title">' + escHtml(hdr) + '</span>' +
            '<div class="pos-payment-card-actions">' +
            '<button type="button" class="btn-edit" title="Edit"><i class="feather icon-edit-2"></i></button>' +
            '<button type="button" class="btn-delete" title="Delete"><i class="feather icon-trash-2"></i></button>' +
            '</div></div>' +
            '<div class="pos-payment-card-bd">' +
            '<div class="pos-payment-card-desc">' + escHtml(sub) + '</div>' +
            '<div class="pos-payment-card-amtrow">' +
            '<span data-payment-amount title="' + escAttr(amtTitle) + '">(' + escHtml(cc) + ') ' + escHtml(totalPaymentAmount.toFixed(2)) + '</span>' +
            wtHtml +
            '</div></div>';
        return row;
    }

    function refreshCard(payment) {
        var el = document.getElementById(payment.id);
        if (!el || !el.parentNode) return;
        el.parentNode.replaceChild(buildCardElement(payment), el);
    }

    function collectPaymentsForSave() {
        var arr = typeof global.payments !== 'undefined' && Array.isArray(global.payments) ? global.payments : [];
        var out = [];
        arr.forEach(function (p) {
            var prevBalAmt = parseFloat(p.previous_balance_amount) || 0;
            var currentOrderAmt = parseFloat(p.amount) || 0;
            var totalAmt = currentOrderAmt + prevBalAmt;
            if (totalAmt <= 0) return;
            out.push(Object.assign({}, p, {
                payment_type: saveTypeLabel(p.type),
                deposit_into: p.deposit_into || '',
                transaction_no: p.transaction_no || '',
                cheque_date: p.cheque_date || null,
                purity_carat: p.purity_carat != null ? String(p.purity_carat) : '',
                amount: totalAmt,
                previous_balance_amount: prevBalAmt,
                current_order_amount: currentOrderAmt,
                diamond_category: p.diamond_category || '',
                quantity: parseFloat(p.quantity) || 0
            }));
        });
        return out;
    }

    function updateFooterTotals() {
        var rows = document.querySelectorAll('#paymentTableBody .pos-payment-card');
        var totalAmount = 0;
        var totalQuantity = 0;
        rows.forEach(function (row) {
            var elAmt = row.querySelector('[data-payment-amount]');
            var amt = parseFloat(String((elAmt && elAmt.textContent) || '0').replace(/[^\d.-]/g, '')) || 0;
            var qty = parseFloat(row.getAttribute('data-quantity') || 0) || 0;
            if (!isNaN(amt)) totalAmount += amt;
            if (!isNaN(qty)) totalQuantity += qty;
        });
        if (isNaN(totalAmount)) totalAmount = 0;
        if (isNaN(totalQuantity)) totalQuantity = 0;
        var ta = document.getElementById('paymentTotalAmount');
        var tq = document.getElementById('paymentTotalQuantity');
        if (ta) ta.textContent = totalAmount.toFixed(2);
        if (tq) tq.textContent = totalQuantity.toFixed(2);
    }

    var NS = global.AuragoldPaymentCards = global.AuragoldPaymentCards || {};
    NS.buildCardElement = buildCardElement;
    NS.refreshCard = refreshCard;
    NS.collectPaymentsForSave = collectPaymentsForSave;
    NS.updateFooterTotals = updateFooterTotals;
    NS.saveTypeLabel = saveTypeLabel;

    global.buildPosPaymentCardElement = buildCardElement;
    global.refreshPosPaymentCard = refreshCard;
    global.collectPosPaymentsForSave = collectPaymentsForSave;

    if (!global._auragoldPaymentCardDocClick) {
        global._auragoldPaymentCardDocClick = true;
        document.addEventListener('click', function (ev) {
            var host = document.getElementById('paymentTableBody');
            if (!host || !host.contains(ev.target)) return;
            var t = ev.target.closest && ev.target.closest('.pos-payment-card .btn-delete, .pos-payment-card .btn-edit');
            if (!t) return;
            var card = t.closest('.pos-payment-card');
            if (!card || !host.contains(card)) return;
            var pid = card.getAttribute('data-payment-id') || card.id;
            if (!pid) return;
            ev.preventDefault();
            ev.stopPropagation();
            if (t.classList.contains('btn-delete')) {
                if (typeof global.deletePayment === 'function') global.deletePayment(pid);
            } else if (t.classList.contains('btn-edit') && typeof global.editPayment === 'function') {
                global.editPayment(pid);
            }
        });
    }
})(typeof window !== 'undefined' ? window : this);
