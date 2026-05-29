/**
 * Material Receive: receive metal exchange (gold/silver) issued on Material Issue.
 */
(function () {
    'use strict';

    window.__pendingMaterialReceiveMetalExchange = window.__pendingMaterialReceiveMetalExchange || [];

    function mrMeFmt(n, dec) {
        var x = parseFloat(n);
        if (!isFinite(x)) {
            return '';
        }
        return x.toFixed(dec != null ? dec : 3);
    }

    function mrMeNormalizePaymentRow(p) {
        if (!p || (p.type || '') !== 'metal-exchange') {
            return null;
        }
        var mid = parseInt(p.metal_exchange_metal_id || p.metal_id || '0', 10) || 0;
        var pcid = parseInt(p.metal_exchange_product_id || p.product_id || '0', 10) || 0;
        if (mid < 1 || pcid < 1) {
            return null;
        }
        var gw = parseFloat(p.metal_exchange_gross_wt || p.gross_weight || '0') || 0;
        var pw = parseFloat(p.metal_exchange_purity_wt || p.purity_weight || '0') || 0;
        if (gw <= 0.0000001) {
            gw = parseFloat(p.quantity || '0') || 0;
        }
        if (gw <= 0.0000001) {
            return null;
        }
        if (pw <= 0.0000001) {
            pw = gw;
        }
        return {
            issue_stock_id: parseInt(p.metal_exchange_source_stock_id || '0', 10) || 0,
            material_issue_id: 0,
            barcode: p.metal_exchange_item_code || p.item_code || '',
            product_name: p.metal_exchange_product_name || p.product_name || '',
            metal_id: mid,
            product_characteristic_id: pcid,
            metal_name: p.metal_name || 'Gold',
            issued_gross: gw,
            issued_pure: pw,
            issued_weight: gw,
            received_gross: 0,
            balance_gross: gw,
            balance_weight: gw,
            purity: p.purity_carat || p.purity || '',
            reference_status: 'to_receive',
            me_source: 'payment_card',
            issue_source_label: 'Payment card',
            from_payment: true
        };
    }

    function mrMeRows() {
        return Array.isArray(window.MATERIAL_RECEIVE_ISSUED_METAL_EXCHANGE)
            ? window.MATERIAL_RECEIVE_ISSUED_METAL_EXCHANGE.slice()
            : [];
    }

    /** Keep PHP-embedded rows when AJAX refresh returns empty (avoids flash-then-hide). */
    function mrApplyMetalExchangeFromServer(data) {
        if (!data || !data.ok) {
            return;
        }
        var incoming = Array.isArray(data.metal_exchange) ? data.metal_exchange : [];
        var current = mrMeRows();
        if (incoming.length > 0) {
            window.MATERIAL_RECEIVE_ISSUED_METAL_EXCHANGE = incoming;
        } else if (current.length > 0) {
            return;
        } else {
            window.MATERIAL_RECEIVE_ISSUED_METAL_EXCHANGE = [];
        }
    }

    window.mrApplyMetalExchangeFromServer = mrApplyMetalExchangeFromServer;

    function mrMePendingKey(line) {
        return (
            String(line.metal_exchange_metal_id || line.metal_id || '') +
            '|' +
            String(line.metal_exchange_product_id || line.product_characteristic_id || '') +
            '|' +
            String(line.metal_exchange_item_code || line.barcode || '').toLowerCase() +
            '|' +
            String(line.source_issue_stock_id || line.issue_stock_id || '')
        );
    }

    function mrMeUpsertPending(line) {
        var list = window.__pendingMaterialReceiveMetalExchange;
        var key = mrMePendingKey(line);
        var found = -1;
        for (var i = 0; i < list.length; i++) {
            if (mrMePendingKey(list[i]) === key) {
                found = i;
                break;
            }
        }
        if (found >= 0) {
            list[found].metal_exchange_gross_wt = String(
                (parseFloat(list[found].metal_exchange_gross_wt) || 0) + (parseFloat(line.metal_exchange_gross_wt) || 0)
            );
            list[found].metal_exchange_purity_wt = String(
                (parseFloat(list[found].metal_exchange_purity_wt) || 0) + (parseFloat(line.metal_exchange_purity_wt) || 0)
            );
        } else {
            list.push(line);
        }
    }

    /** Modal / external: queue ME receive without showing a payment card. */
    function mrMeQueuePaymentLine(payment) {
        if (!payment || (payment.type || '') !== 'metal-exchange') {
            return;
        }
        var gw = parseFloat(payment.metal_exchange_gross_wt || '0') || 0;
        var pw = parseFloat(payment.metal_exchange_purity_wt || '0') || gw;
        if (gw <= 0.0000001) {
            return;
        }
        mrMeUpsertPending({
            metal_exchange_metal_id: payment.metal_exchange_metal_id || '',
            metal_exchange_product_id: payment.metal_exchange_product_id || '',
            metal_exchange_product_name: payment.metal_exchange_product_name || '',
            metal_exchange_gross_wt: String(gw),
            metal_exchange_purity_wt: String(pw),
            metal_exchange_rate: payment.metal_exchange_rate || '0',
            metal_exchange_item_code: payment.metal_exchange_item_code || '',
            metal_exchange_source_stock_id: payment.metal_exchange_source_stock_id || '',
            purity_carat: payment.purity_carat || '1',
            quantity: payment.quantity || 1
        });
    }

    function mrMePendingToSavePayments() {
        return (window.__pendingMaterialReceiveMetalExchange || []).map(function (line) {
            line = mrMeResolvePendingIds(Object.assign({}, line));
            return {
                type: 'metal-exchange',
                payment_type: 'metal-exchange',
                deposit_into: 'Metal Exchange',
                amount: 0,
                quantity: parseFloat(line.quantity) || 1,
                purity_carat: line.purity_carat != null ? String(line.purity_carat) : '1',
                metal_exchange_metal_id: line.metal_exchange_metal_id || '',
                metal_exchange_product_id: line.metal_exchange_product_id || '',
                metal_exchange_product_name: line.metal_exchange_product_name || '',
                metal_exchange_gross_wt: line.metal_exchange_gross_wt || '0',
                metal_exchange_purity_wt: line.metal_exchange_purity_wt || '0',
                metal_exchange_rate: line.metal_exchange_rate || '0',
                metal_exchange_item_code: line.metal_exchange_item_code || '',
                metal_exchange_source_stock_id: line.metal_exchange_source_stock_id || line.source_issue_stock_id || ''
            };
        });
    }

    window.mrMeQueuePaymentLine = mrMeQueuePaymentLine;
    window.mrMePendingMetalExchangeForSave = mrMePendingToSavePayments;

    function mrMeStatusBadge(st, queued) {
        if (queued) {
            return '<span class="badge badge-success">Queued</span>';
        }
        var s = String(st || '').toLowerCase();
        if (s === 'fully_received' || s === 'received') {
            return '<span class="badge badge-secondary">Received</span>';
        }
        if (s === 'partial') {
            return '<span class="badge badge-info">Partial receive</span>';
        }
        return '<span class="badge badge-success">To receive</span>';
    }

    function mrMePendingReceiveBadgeHtml() {
        return (
            '<span class="badge badge-secondary" style="background:#fde047;color:#422006;font-size:0.7rem;">' +
            'Pending receive — saves on Save</span>'
        );
    }

    function mrMePendingMatchesRow(p, r) {
        var sid = parseInt(p.metal_exchange_source_stock_id || p.source_issue_stock_id || '0', 10) || 0;
        var rid = parseInt(r.issue_stock_id, 10) || 0;
        if (sid > 0 && rid > 0 && sid === rid) {
            return true;
        }
        var pbc = (p.mr_display_barcode || p.metal_exchange_item_code || '').toLowerCase();
        var rbc = (r.barcode || '').toLowerCase();
        return pbc !== '' && rbc !== '' && pbc === rbc;
    }

    function mrMeResolvePendingIds(line) {
        var mid = parseInt(line.metal_exchange_metal_id || '0', 10) || 0;
        var pcid = parseInt(line.metal_exchange_product_id || '0', 10) || 0;
        if (mid >= 1 && pcid >= 1) {
            return line;
        }
        var sid = parseInt(line.metal_exchange_source_stock_id || line.source_issue_stock_id || '0', 10) || 0;
        var bc = (line.metal_exchange_item_code || line.mr_display_barcode || '').toLowerCase();
        mrMeRows().forEach(function (r) {
            var rid = parseInt(r.issue_stock_id, 10) || 0;
            if (sid > 0 && rid === sid) {
                if (mid < 1 && r.metal_id) {
                    line.metal_exchange_metal_id = String(r.metal_id);
                }
                if (pcid < 1 && r.product_characteristic_id) {
                    line.metal_exchange_product_id = String(r.product_characteristic_id);
                }
                if (!line.metal_exchange_product_name && r.product_name) {
                    line.metal_exchange_product_name = r.product_name;
                }
                if (!line.mr_display_metal && r.metal_name) {
                    line.mr_display_metal = r.metal_name;
                }
                if (!line.mr_display_product && r.product_name) {
                    line.mr_display_product = r.product_name;
                }
                if (!line.mr_display_barcode && r.barcode) {
                    line.mr_display_barcode = r.barcode;
                }
            } else if (bc && (r.barcode || '').toLowerCase() === bc) {
                if (mid < 1 && r.metal_id) {
                    line.metal_exchange_metal_id = String(r.metal_id);
                }
                if (pcid < 1 && r.product_characteristic_id) {
                    line.metal_exchange_product_id = String(r.product_characteristic_id);
                }
            }
        });
        return line;
    }

    function mrMeAppendPendingRow(tbody, p) {
        var gw = parseFloat(p.metal_exchange_gross_wt) || 0;
        var pw = parseFloat(p.metal_exchange_purity_wt) || gw;
        var trP = document.createElement('tr');
        trP.className = 'mr-me-pending-receive-row';
        trP.style.background = '#fffbeb';
        var tdEmptyChk = document.createElement('td');
        tdEmptyChk.className = 'text-center';
        trP.appendChild(tdEmptyChk);
        var pendCells = [
            p.mr_display_source || 'Material issue',
            p.mr_display_metal || p.metal_exchange_metal_name || '—',
            p.mr_display_product || p.metal_exchange_product_name || '—',
            p.mr_display_barcode || p.metal_exchange_item_code || '—',
            mrMeFmt(gw, 3),
            mrMeFmt(pw, 3),
            '—',
            mrMeFmt(gw, 3)
        ];
        pendCells.forEach(function (txt, idx) {
            var td = document.createElement('td');
            if (idx >= 3) {
                td.className = 'text-right';
            }
            td.textContent = txt;
            trP.appendChild(td);
        });
        var tdPendSt = document.createElement('td');
        tdPendSt.innerHTML = mrMePendingReceiveBadgeHtml();
        trP.appendChild(tdPendSt);
        tbody.appendChild(trP);
    }

    function mrMeBindRow(tr, r) {
        var bal = parseFloat(r.balance_gross != null ? r.balance_gross : r.balance_weight) || 0;
        var stockId = parseInt(r.issue_stock_id, 10) || 0;
        tr.setAttribute('data-issue-stock-id', String(stockId > 0 ? stockId : ''));
        tr.setAttribute('data-metal-id', String(r.metal_id || ''));
        tr.setAttribute('data-product-characteristic-id', String(r.product_characteristic_id || ''));
        tr.setAttribute('data-product-name', r.product_name || '');
        tr.setAttribute('data-metal-name', r.metal_name || '');
        tr.setAttribute('data-balance-gross', String(bal));
        tr.setAttribute('data-issued-gross', String(r.issued_gross || r.issued_weight || 0));
        tr.setAttribute('data-issued-pure', String(r.issued_pure || 0));
        tr.setAttribute('data-purity', String(r.purity != null ? r.purity : ''));
        tr.setAttribute('data-barcode', r.barcode || '');
    }

    function mrMeRenderTable() {
        var tbody = document.getElementById('mrIssuedMetalExchangeTbody');
        var emptyEl = document.getElementById('mrIssuedMetalExchangeEmpty');
        if (!tbody) {
            return;
        }
        var rows = mrMeRows();
        var pending = (window.__pendingMaterialReceiveMetalExchange || []).slice();
        var pendingMatched = {};
        tbody.innerHTML = '';
        if (emptyEl) {
            emptyEl.style.display = rows.length || pending.length ? 'none' : 'block';
        }
        rows.forEach(function (r) {
            var bal = parseFloat(r.balance_gross != null ? r.balance_gross : r.balance_weight);
            if (!isFinite(bal)) {
                bal = parseFloat(r.issued_gross != null ? r.issued_gross : r.issued_weight) || 0;
            }
            var tr = document.createElement('tr');
            tr.className = 'mr-me-issued-receive-row';
            mrMeBindRow(tr, r);
            var disabled = bal <= 0.0000001;
            if (disabled) {
                tr.classList.add('text-muted');
            }
            var chk = document.createElement('input');
            chk.type = 'checkbox';
            chk.className = 'mr-me-issued-receive-chk';
            chk.disabled = disabled;
            var tdChk = document.createElement('td');
            tdChk.className = 'text-center';
            tdChk.appendChild(chk);
            tr.appendChild(tdChk);
            var cells = [
                r.issue_source_label || r.me_source || '—',
                r.metal_name || '—',
                r.product_name || '—',
                r.barcode || '—',
                mrMeFmt(r.issued_gross || r.issued_weight, 3),
                mrMeFmt(r.received_gross, 3),
                mrMeFmt(bal, 3)
            ];
            cells.forEach(function (txt, idx) {
                var td = document.createElement('td');
                if (idx >= 3) {
                    td.className = 'text-right';
                }
                td.textContent = txt;
                tr.appendChild(td);
            });
            var tdWt = document.createElement('td');
            tdWt.className = 'text-right';
            var inp = document.createElement('input');
            inp.type = 'number';
            inp.step = '0.001';
            inp.min = '0';
            inp.className = 'form-control form-control-sm text-right mr-me-issued-receive-wt';
            inp.value = disabled ? '' : mrMeFmt(bal, 3);
            inp.disabled = disabled;
            tdWt.appendChild(inp);
            tr.appendChild(tdWt);
            var tdSt = document.createElement('td');
            tdSt.innerHTML = mrMeStatusBadge(
                r.reference_status,
                tr.getAttribute('data-queued-receive') === '1'
            );
            tr.appendChild(tdSt);
            tbody.appendChild(tr);
            pending.forEach(function (p, pidx) {
                if (!mrMePendingMatchesRow(p, r)) {
                    return;
                }
                pendingMatched[pidx] = true;
                mrMeAppendPendingRow(tbody, p);
            });
        });
        pending.forEach(function (p, pidx) {
            if (pendingMatched[pidx]) {
                return;
            }
            mrMeAppendPendingRow(tbody, p);
        });
    }

    function mrMeBuildPendingLine(row, recvGross) {
        var issuedGross = parseFloat(row.getAttribute('data-issued-gross') || '0') || 0;
        var issuedPure = parseFloat(row.getAttribute('data-issued-pure') || '0') || 0;
        var recvPure = recvGross;
        if (issuedGross > 0.0000001 && issuedPure > 0.0000001) {
            recvPure = Math.round(issuedPure * (recvGross / issuedGross) * 10000) / 10000;
        }
        var srcLabel = '';
        if (row.cells && row.cells.length > 1) {
            srcLabel = (row.cells[1].textContent || '').trim();
        }
        return {
            metal_exchange_metal_id: row.getAttribute('data-metal-id') || '',
            metal_exchange_product_id: row.getAttribute('data-product-characteristic-id') || '',
            metal_exchange_product_name: row.getAttribute('data-product-name') || '',
            metal_exchange_gross_wt: String(recvGross),
            metal_exchange_purity_wt: String(recvPure),
            metal_exchange_rate: '0',
            metal_exchange_item_code: row.getAttribute('data-barcode') || '',
            metal_exchange_source_stock_id: row.getAttribute('data-issue-stock-id') || '',
            source_issue_stock_id: row.getAttribute('data-issue-stock-id') || '',
            purity_carat: row.getAttribute('data-purity') || '1',
            quantity: 1,
            mr_display_source: srcLabel || 'Material issue',
            mr_display_metal: row.getAttribute('data-metal-name') || '',
            mr_display_product: row.getAttribute('data-product-name') || '',
            mr_display_barcode: row.getAttribute('data-barcode') || ''
        };
    }

    function mrMeApplyPartialBalance(tr, recvGross) {
        var bal = parseFloat(tr.getAttribute('data-balance-gross') || '0') || 0;
        var newBal = Math.max(0, Math.round((bal - recvGross) * 1000) / 1000);
        tr.setAttribute('data-balance-gross', String(newBal));
        var cells = tr.cells;
        if (cells && cells.length >= 8) {
            cells[6].textContent = mrMeFmt(newBal, 3);
        }
        var inp = tr.querySelector('.mr-me-issued-receive-wt');
        if (inp) {
            inp.value = newBal > 0.0000001 ? mrMeFmt(newBal, 3) : '';
            inp.disabled = newBal <= 0.0000001;
        }
        var chk = tr.querySelector('.mr-me-issued-receive-chk');
        if (chk) {
            chk.checked = false;
            chk.disabled = newBal <= 0.0000001;
        }
        if (newBal <= 0.0000001) {
            tr.classList.add('text-muted');
        }
        var stCell = tr.cells[tr.cells.length - 1];
        if (stCell) {
            stCell.innerHTML = mrMeStatusBadge(newBal <= 0.0000001 ? 'fully_received' : 'partial', false);
        }
    }

    function mrMeQueueSelected() {
        var tbody = document.getElementById('mrIssuedMetalExchangeTbody');
        if (!tbody) {
            return 0;
        }
        var added = 0;
        tbody.querySelectorAll('tr.mr-me-issued-receive-row').forEach(function (tr) {
            var chk = tr.querySelector('.mr-me-issued-receive-chk');
            if (!chk || !chk.checked) {
                return;
            }
            var bal = parseFloat(tr.getAttribute('data-balance-gross') || '0') || 0;
            if (bal <= 0.0000001) {
                return;
            }
            var inp = tr.querySelector('.mr-me-issued-receive-wt');
            var recv = inp ? parseFloat(inp.value) : bal;
            if (!isFinite(recv) || recv <= 0.0000001) {
                recv = bal;
            }
            if (recv > bal + 0.0001) {
                window.alert(
                    'Receive weight cannot exceed balance (' + mrMeFmt(bal, 3) + ') for ' + (tr.getAttribute('data-product-name') || 'metal')
                );
                recv = bal;
            }
            var stockId = parseInt(tr.getAttribute('data-issue-stock-id') || '0', 10);
            var barcode = (tr.getAttribute('data-barcode') || '').trim();
            if (stockId < 1 && !barcode) {
                window.alert('Invalid issued line (missing stock / barcode).');
                return;
            }
            var line = mrMeResolvePendingIds(mrMeBuildPendingLine(tr, recv));
            mrMeUpsertPending(line);
            added++;
        });
        if (added < 1) {
            window.alert('Select at least one issued line with balance and enter receive weight.');
            return 0;
        }
        mrMeRenderTable();
        if (typeof window.updateSummaryPanel === 'function') {
            window.updateSummaryPanel();
        }
        return added;
    }

    function mrMeRefreshFromServer(cb) {
        var el = document.getElementById('jwoSaleOrderId');
        var soid = el ? parseInt(el.getAttribute('data-sale-order-id') || '0', 10) : 0;
        if (soid < 1 && window.jwoSaleOrderIdParam) {
            soid = parseInt(String(window.jwoSaleOrderIdParam), 10) || 0;
        }
        if (soid < 1) {
            if (typeof cb === 'function') {
                cb();
            }
            return;
        }
        var qs =
            'sale_order_id=' +
            encodeURIComponent(String(soid)) +
            (window.jwoFromRepair ? '&from_repair=1' : '') +
            '&_=' +
            (Date.now ? Date.now() : 0);
        fetch('ajax/list-material-receive-issued-reference.php?' + qs)
            .then(function (r) {
                if (!r.ok) {
                    return r.text().then(function (t) {
                        throw new Error(t || 'HTTP ' + r.status);
                    });
                }
                return r.json();
            })
            .then(function (data) {
                mrApplyMetalExchangeFromServer(data);
                mrMeRenderTable();
                if (typeof cb === 'function') {
                    cb();
                }
            })
            .catch(function (err) {
                console.warn('Material Receive ME list failed', err);
                if (typeof cb === 'function') {
                    cb();
                }
            });
    }

    function mrMeRefreshPaymentCardRows() {
        /* receive table is server-driven only; no payment-card merge */
    }

    function mrMeInit() {
        var cfg = window.AURAGOLD_VOUCHER_DS || {};
        if ((cfg.voucherKind || '') !== 'material_receive') {
            return;
        }
        var soid = 0;
        var el = document.getElementById('jwoSaleOrderId');
        if (el) {
            soid = parseInt(el.getAttribute('data-sale-order-id') || '0', 10) || 0;
        }
        if (soid < 1 && window.jwoSaleOrderIdParam) {
            soid = parseInt(String(window.jwoSaleOrderIdParam), 10) || 0;
        }
        mrMeRenderTable();
        var qBtn = document.getElementById('mrReceiveMeQueueBtn');
        if (qBtn) {
            qBtn.addEventListener('click', function () {
                mrMeQueueSelected();
            });
        }
        var selBtn = document.getElementById('mrReceiveMeSelectAll');
        var hdr = document.getElementById('mrReceiveMeHdrChk');
        function toggleAll(on) {
            var tbody = document.getElementById('mrIssuedMetalExchangeTbody');
            if (!tbody) {
                return;
            }
            tbody.querySelectorAll('.mr-me-issued-receive-chk:not(:disabled)').forEach(function (c) {
                c.checked = !!on;
            });
        }
        if (selBtn) {
            selBtn.addEventListener('click', function () {
                toggleAll(true);
            });
        }
        if (hdr) {
            hdr.addEventListener('change', function () {
                toggleAll(hdr.checked);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', mrMeInit);
    window.mrRenderMaterialReceiveIssuedMetalExchange = mrMeRenderTable;
    window.mrRefreshMaterialReceiveIssuedMetalExchange = mrMeRefreshFromServer;
})();
