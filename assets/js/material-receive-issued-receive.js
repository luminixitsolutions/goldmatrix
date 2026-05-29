/**
 * Material Receive: partial receive of diamonds/stones issued on Material Issue.
 */
(function () {
    'use strict';

    function mrFmtNum(n, dec) {
        var x = parseFloat(n);
        if (!isFinite(x)) {
            return '';
        }
        return x.toFixed(dec != null ? dec : 3);
    }

    function mrIsMaterialReceive() {
        var cfg = window.AURAGOLD_VOUCHER_DS || {};
        return (cfg.voucherKind || '') === 'material_receive';
    }

    function mrSaleOrderId() {
        var el = document.getElementById('jwoSaleOrderId');
        var id = el ? parseInt(el.getAttribute('data-sale-order-id') || '0', 10) : 0;
        if (id < 1 && window.jwoSaleOrderIdParam) {
            id = parseInt(String(window.jwoSaleOrderIdParam), 10) || 0;
        }
        return id;
    }

    function mrRefreshIssuedFromServer(cb) {
        var soid = mrSaleOrderId();
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
                if (data && data.ok) {
                    window.MATERIAL_RECEIVE_ISSUED_DIAMONDS = Array.isArray(data.diamonds) ? data.diamonds : [];
                    window.MATERIAL_RECEIVE_ISSUED_STONES = Array.isArray(data.stones) ? data.stones : [];
                    if (typeof window.mrApplyMetalExchangeFromServer === 'function') {
                        window.mrApplyMetalExchangeFromServer(data);
                    } else {
                        window.MATERIAL_RECEIVE_ISSUED_METAL_EXCHANGE = Array.isArray(data.metal_exchange)
                            ? data.metal_exchange
                            : [];
                    }
                    window.__materialIssueReferenceDiamondRows = window.MATERIAL_RECEIVE_ISSUED_DIAMONDS.slice();
                    window.__materialIssueReferenceStoneRows = window.MATERIAL_RECEIVE_ISSUED_STONES.slice();
                }
                if (typeof window.mrRenderMaterialReceiveIssuedMetalExchange === 'function') {
                    window.mrRenderMaterialReceiveIssuedMetalExchange();
                }
                if (typeof cb === 'function') {
                    cb();
                }
            })
            .catch(function () {
                if (typeof cb === 'function') {
                    cb();
                }
            });
    }

    function mrUpsertPending(pendingKey, line) {
        if (!Array.isArray(window[pendingKey])) {
            window[pendingKey] = [];
        }
        var sid = parseInt(line.source_issue_id, 10) || 0;
        var list = window[pendingKey];
        var found = -1;
        for (var i = 0; i < list.length; i++) {
            if (parseInt(list[i].source_issue_id, 10) === sid) {
                found = i;
                break;
            }
        }
        if (found >= 0) {
            list[found] = line;
        } else {
            list.push(line);
        }
    }

    function mrQueueSelectedIssued(gem) {
        var isDiamond = gem === 'diamond';
        var tbodyId = isDiamond ? 'saleOrderDiamondLinesTbody' : 'saleOrderStoneLinesTbody';
        var tbody = document.getElementById(tbodyId);
        if (!tbody) {
            return 0;
        }
        var pendingKey = isDiamond ? '__pendingSaleOrderDiamondLines' : '__pendingSaleOrderStoneLines';
        var catKey = isDiamond ? 'diamond_category' : 'stone_category';
        var added = 0;
        tbody.querySelectorAll('tr.mr-issued-receive-row').forEach(function (tr) {
            var chk = tr.querySelector('.mr-issued-receive-chk');
            if (!chk || !chk.checked) {
                return;
            }
            var bal = parseFloat(tr.getAttribute('data-balance-weight') || '0') || 0;
            if (bal <= 0.0000001) {
                return;
            }
            var inp = tr.querySelector('.mr-issued-receive-wt');
            var recvWt = inp ? parseFloat(inp.value) : bal;
            if (!isFinite(recvWt) || recvWt <= 0.0000001) {
                recvWt = bal;
            }
            if (recvWt > bal + 0.0001) {
                window.alert(
                    'Receive weight cannot exceed balance (' + mrFmtNum(bal, 3) + ') for ' + (tr.getAttribute('data-barcode') || '')
                );
                recvWt = bal;
            }
            var issuedQty = parseFloat(tr.getAttribute('data-issued-qty') || '0') || 0;
            var issuedWt = parseFloat(tr.getAttribute('data-issued-weight') || '0') || 0;
            var recvQty = 0;
            if (issuedQty > 0.0000001 && issuedWt > 0.0000001) {
                recvQty = Math.round(issuedQty * (recvWt / issuedWt) * 10000) / 10000;
            }
            mrUpsertPending(pendingKey, {
                source_issue_id: parseInt(tr.getAttribute('data-issue-line-id') || '0', 10),
                stock_id: parseInt(tr.getAttribute('data-stock-id') || '0', 10),
                barcode: tr.getAttribute('data-barcode') || '',
                product_name: tr.getAttribute('data-product-name') || '',
                diamond_category: isDiamond ? tr.getAttribute('data-category') || '' : '',
                stone_category: !isDiamond ? tr.getAttribute('data-category') || '' : '',
                allocate_weight: recvWt,
                allocate_qty: recvQty,
            });
            added++;
        });
        if (added < 1) {
            window.alert('Select at least one issued line with balance and enter receive weight.');
            return 0;
        }
        if (isDiamond && typeof window.renderSaleOrderDiamondLinesPanel === 'function') {
            window.renderSaleOrderDiamondLinesPanel();
        }
        if (!isDiamond && typeof window.renderSaleOrderStoneLinesPanel === 'function') {
            window.renderSaleOrderStoneLinesPanel();
        }
        return added;
    }

    function mrBindIssuedRow(tr, r, gem) {
        var bal = parseFloat(r.balance_weight != null ? r.balance_weight : 0) || 0;
        var issuedWt = parseFloat(r.issued_weight != null ? r.issued_weight : r.weight) || 0;
        var recvWt = parseFloat(r.received_weight != null ? r.received_weight : 0) || 0;
        var issueId = parseInt(r.issue_line_id != null ? r.issue_line_id : r.issue_id || r.id, 10) || 0;
        tr.className = 'mr-issued-receive-row';
        tr.setAttribute('data-issue-line-id', String(issueId));
        tr.setAttribute('data-stock-id', String(parseInt(r.stock_id, 10) || 0));
        tr.setAttribute('data-barcode', r.barcode || '');
        tr.setAttribute('data-product-name', r.product_name || '');
        tr.setAttribute(
            'data-category',
            gem === 'diamond' ? r.diamond_category || '' : r.stone_category || ''
        );
        tr.setAttribute('data-issued-weight', String(issuedWt));
        tr.setAttribute('data-issued-qty', String(parseFloat(r.issued_qty != null ? r.issued_qty : r.qty) || 0));
        tr.setAttribute('data-balance-weight', String(bal));
        if (bal <= 0.0000001) {
            tr.style.opacity = '0.65';
        }

        var tdChk = document.createElement('td');
        tdChk.className = 'text-center';
        var chk = document.createElement('input');
        chk.type = 'checkbox';
        chk.className = 'mr-issued-receive-chk';
        chk.disabled = bal <= 0.0000001;
        tdChk.appendChild(chk);
        tr.appendChild(tdChk);

        function tdText(txt, cls) {
            var td = document.createElement('td');
            if (cls) {
                td.className = cls;
            }
            td.textContent = txt;
            tr.appendChild(td);
            return td;
        }

        tdText(r.barcode || '');
        tdText(r.product_name || '');
        tdText(gem === 'diamond' ? r.diamond_category || '' : r.stone_category || '');
        tdText(mrFmtNum(issuedWt, 3), 'text-right');
        tdText(mrFmtNum(recvWt, 3), 'text-right');
        tdText(mrFmtNum(bal, 3), 'text-right');

        var tdInp = document.createElement('td');
        tdInp.className = 'text-right';
        var inp = document.createElement('input');
        inp.type = 'number';
        inp.step = '0.001';
        inp.min = '0';
        inp.max = String(bal > 0 ? bal : 0);
        inp.className = 'form-control form-control-sm text-right mr-issued-receive-wt';
        inp.style.maxWidth = '88px';
        inp.style.display = 'inline-block';
        inp.value = bal > 0.0000001 ? String(bal) : '0';
        inp.disabled = bal <= 0.0000001;
        tdInp.appendChild(inp);
        tr.appendChild(tdInp);

        var tdSt = document.createElement('td');
        var st = r.reference_status || 'to_receive';
        var label = 'To receive';
        var bg = '#059669';
        if (st === 'fully_received') {
            label = 'Received';
            bg = '#64748b';
        } else if (st === 'partial') {
            label = 'Partial receive';
            bg = '#b45309';
        } else if (tr.getAttribute('data-queued-receive') === '1') {
            label = 'Queued';
            bg = '#059669';
        }
        tdSt.innerHTML =
            '<span class="badge" style="background:' + bg + ';font-size:0.7rem;">' + label + '</span>';
        tr.appendChild(tdSt);
    }

    window.mrRenderIssuedDiamondRows = function (tbody, issuedRef) {
        if (!tbody || !mrIsMaterialReceive()) {
            return false;
        }
        issuedRef.forEach(function (r) {
            var tr = document.createElement('tr');
            mrBindIssuedRow(tr, r, 'diamond');
            tbody.appendChild(tr);
        });
        return issuedRef.length > 0;
    };

    window.mrRenderIssuedStoneRows = function (tbody, issuedRef) {
        if (!tbody || !mrIsMaterialReceive()) {
            return false;
        }
        issuedRef.forEach(function (r) {
            var tr = document.createElement('tr');
            mrBindIssuedRow(tr, r, 'stone');
            tbody.appendChild(tr);
        });
        return issuedRef.length > 0;
    };

    function mrBindToolbar() {
        var dHdr = document.getElementById('mrReceiveDiamondHdrChk');
        var sHdr = document.getElementById('mrReceiveStoneHdrChk');
        var dAll = document.getElementById('mrReceiveDiamondSelectAll');
        var sAll = document.getElementById('mrReceiveStoneSelectAll');
        var dBtn = document.getElementById('mrReceiveDiamondQueueBtn');
        var sBtn = document.getElementById('mrReceiveStoneQueueBtn');

        function toggleAll(gem, on) {
            var tbody = document.getElementById(
                gem === 'diamond' ? 'saleOrderDiamondLinesTbody' : 'saleOrderStoneLinesTbody'
            );
            if (!tbody) {
                return;
            }
            tbody.querySelectorAll('.mr-issued-receive-chk:not(:disabled)').forEach(function (c) {
                c.checked = !!on;
            });
        }

        if (dHdr && !dHdr._mrBound) {
            dHdr._mrBound = true;
            dHdr.addEventListener('change', function () {
                toggleAll('diamond', dHdr.checked);
            });
        }
        if (sHdr && !sHdr._mrBound) {
            sHdr._mrBound = true;
            sHdr.addEventListener('change', function () {
                toggleAll('stone', sHdr.checked);
            });
        }
        if (dAll && !dAll._mrBound) {
            dAll._mrBound = true;
            dAll.addEventListener('click', function () {
                toggleAll('diamond', true);
            });
        }
        if (sAll && !sAll._mrBound) {
            sAll._mrBound = true;
            sAll.addEventListener('click', function () {
                toggleAll('stone', true);
            });
        }
        if (dBtn && !dBtn._mrBound) {
            dBtn._mrBound = true;
            dBtn.addEventListener('click', function () {
                mrQueueSelectedIssued('diamond');
            });
        }
        if (sBtn && !sBtn._mrBound) {
            sBtn._mrBound = true;
            sBtn.addEventListener('click', function () {
                mrQueueSelectedIssued('stone');
            });
        }
    }

    var _origOnSave = window.auragoldVoucherDiamondStoneOnSaveSuccess;
    window.auragoldVoucherDiamondStoneOnSaveSuccess = function (savedId) {
        if (typeof _origOnSave === 'function') {
            _origOnSave(savedId);
        }
        if (!mrIsMaterialReceive()) {
            return;
        }
        mrRefreshIssuedFromServer(function () {
            if (typeof window.auragoldMaterialReceiveApplyIssuedFromSaleOrder === 'function') {
                window.auragoldMaterialReceiveApplyIssuedFromSaleOrder();
            }
        });
    };

    function mrInitIssuedReferenceOnLoad() {
        mrBindToolbar();
        if (mrIsMaterialReceive() && mrSaleOrderId() > 0) {
            mrRefreshIssuedFromServer();
        }
    }

    document.addEventListener('DOMContentLoaded', mrInitIssuedReferenceOnLoad);
    if (document.readyState !== 'loading') {
        mrInitIssuedReferenceOnLoad();
    }

    window.mrRefreshMaterialReceiveIssuedReference = mrRefreshIssuedFromServer;
})();
