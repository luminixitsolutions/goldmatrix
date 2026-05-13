/**
 * Column drag-reorder + vertical borders (class acr-col-table).
 * Requires Sortable 1.x (load Sortable.min.js before this file).
 */
(function (global) {
    'use strict';

    var STYLE_ID = 'auragold-col-reorder-css';

    function injectCss() {
        if (document.getElementById(STYLE_ID)) return;
        var s = document.createElement('style');
        s.id = STYLE_ID;
        s.textContent = [
            'table.acr-col-table { border-collapse: collapse; }',
            'table.acr-col-table thead th,',
            'table.acr-col-table tbody td,',
            'table.acr-col-table tfoot td { border-left: 1px solid #cbd5e1; }',
            'table.acr-col-table thead th:first-child,',
            'table.acr-col-table tbody td:first-child,',
            'table.acr-col-table tfoot td:first-child { border-left: none; }',
            '.table thead th.acr-th-reorder { position: relative !important; }',
            'table.acr-col-table thead th.acr-th-reorder .acr-th-drag {',
            '  display: inline-flex; align-items: center; justify-content: center;',
            '  vertical-align: middle; margin-left: 0.35rem; cursor: grab; color: #c9a962;',
            '  line-height: 1; flex-shrink: 0;',
            '}',
            'table.acr-col-table thead th.acr-th-reorder .acr-th-drag .feather { width: 0.95rem; height: 0.95rem; }',
            'table.acr-col-table thead th.acr-th-reorder .acr-th-drag:active { cursor: grabbing; }',
            '.acr-sortable-ghost { opacity: 0.45; }',
            '.acr-sortable-chosen { opacity: 0.9; }'
        ].join('\n');
        document.head.appendChild(s);
    }

    function theadRow(table) {
        return table.querySelector('thead tr');
    }

    function ensureDataCols(table) {
        var trh = theadRow(table);
        if (!trh) return;
        var n = trh.cells.length;
        var i;
        for (i = 0; i < n; i++) {
            var th = trh.cells[i];
            if (!th.getAttribute('data-col')) {
                th.setAttribute('data-col', 'c' + i);
            }
        }
        var keys = [];
        for (i = 0; i < n; i++) {
            keys.push(trh.cells[i].getAttribute('data-col'));
        }
        function syncTr(tr) {
            if (tr.querySelector('td[colspan]')) return;
            var tds = tr.querySelectorAll('td');
            if (tds.length !== n) return;
            for (var j = 0; j < n; j++) {
                if (!tds[j].getAttribute('data-col')) {
                    tds[j].setAttribute('data-col', keys[j]);
                }
            }
        }
        table.querySelectorAll('tbody tr').forEach(syncTr);
        table.querySelectorAll('tfoot tr').forEach(syncTr);
    }

    function getOrder(table) {
        var tr = theadRow(table);
        return Array.prototype.map.call(tr.querySelectorAll('th[data-col]'), function (th) {
            return th.getAttribute('data-col');
        });
    }

    function normalizeOrder(saved, currentKeys) {
        if (!saved || !saved.length) return currentKeys.slice();
        var set = {};
        currentKeys.forEach(function (k) { set[k] = true; });
        var out = [];
        saved.forEach(function (k) {
            if (set[k]) {
                out.push(k);
                delete set[k];
            }
        });
        currentKeys.forEach(function (k) {
            if (set[k]) out.push(k);
        });
        return out;
    }

    function applyOrder(table, order) {
        var tr = theadRow(table);
        if (!tr) return;
        var map = {};
        tr.querySelectorAll('th[data-col]').forEach(function (th) {
            map[th.getAttribute('data-col')] = th;
        });
        order.forEach(function (k) {
            if (map[k]) tr.appendChild(map[k]);
        });

        function syncTr(trel) {
            if (trel.querySelector('td[colspan]')) return;
            var by = {};
            trel.querySelectorAll('td[data-col]').forEach(function (td) {
                var k = td.getAttribute('data-col');
                if (k && by[k] == null) by[k] = td;
            });
            order.forEach(function (k) {
                if (by[k]) trel.appendChild(by[k]);
            });
        }
        table.querySelectorAll('tbody tr').forEach(syncTr);
        table.querySelectorAll('tfoot tr').forEach(syncTr);
    }

    function enhanceHeaders(table, fixedFirst, fixedKey) {
        var tr = theadRow(table);
        if (!tr) return;
        var ths = tr.querySelectorAll('th');
        ths.forEach(function (th, idx) {
            var k = th.getAttribute('data-col');
            var isFixed = fixedFirst && (idx === 0 || (fixedKey && k === fixedKey));
            th.classList.toggle('acr-th-fixed', isFixed);
            th.classList.toggle('acr-th-reorder', !isFixed);
            if (isFixed) return;
            if (th.querySelector('.acr-th-drag')) return;
            var drag = document.createElement('span');
            drag.className = 'acr-th-drag';
            drag.title = 'Drag to reorder columns';
            drag.innerHTML = '<i class="feather icon-move" aria-hidden="true"></i>';
            th.appendChild(drag);
        });
    }

    function loadOrder(key) {
        try {
            var raw = localStorage.getItem(key);
            if (!raw) return null;
            var o = JSON.parse(raw);
            return Array.isArray(o) ? o : null;
        } catch (e) {
            return null;
        }
    }

    function saveOrder(key, order) {
        try {
            localStorage.setItem(key, JSON.stringify(order));
        } catch (e) {}
    }

    function validPermutation(order, keys) {
        if (order.length !== keys.length) return false;
        var need = {};
        keys.forEach(function (k) { need[k] = 0; });
        order.forEach(function (k) {
            if (Object.prototype.hasOwnProperty.call(need, k)) need[k]++;
        });
        return keys.every(function (k) { return need[k] === 1; });
    }

    function bindSortable(table, storageKey, fixedFirst, fixedKey) {
        if (typeof Sortable === 'undefined') return;
        var tr = theadRow(table);
        if (!tr) return;
        if (tr._acrSortable) {
            tr._acrSortable.destroy();
            tr._acrSortable = null;
        }
        var lastGood;
        function refreshLastGood() {
            lastGood = getOrder(table).slice();
        }
        refreshLastGood();
        tr._acrSortable = Sortable.create(tr, {
            animation: 150,
            handle: '.acr-th-drag',
            draggable: 'th.acr-th-reorder',
            filter: '.acr-th-fixed',
            preventOnFilter: false,
            ghostClass: 'acr-sortable-ghost',
            chosenClass: 'acr-sortable-chosen',
            onEnd: function () {
                var order = getOrder(table);
                var ok = validPermutation(order, lastGood);
                if (fixedFirst) {
                    var fk = fixedKey || theadRow(table).cells[0].getAttribute('data-col');
                    if (order[0] !== fk) ok = false;
                }
                if (!ok) {
                    applyOrder(table, lastGood);
                    refreshLastGood();
                    return;
                }
                applyOrder(table, order);
                saveOrder(storageKey, order);
                refreshLastGood();
            }
        });
    }

    /**
     * @param {string|HTMLElement} selector
     * @param {{ storageKey: string, fixedFirst?: boolean, fixedKey?: string }} opts
     */
    function init(selector, opts) {
        opts = opts || {};
        var table = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!table || table.tagName !== 'TABLE') return;

        injectCss();
        table.classList.add('acr-col-table');

        var theadRows = table.querySelectorAll('thead tr');
        if (theadRows.length !== 1) {
            return;
        }

        ensureDataCols(table);
        var defaultOrder = getOrder(table);
        var saved = loadOrder(opts.storageKey);
        var order = normalizeOrder(saved, defaultOrder);
        if (validPermutation(order, defaultOrder)) {
            applyOrder(table, order);
        }

        enhanceHeaders(table, !!opts.fixedFirst, opts.fixedKey || null);
        bindSortable(table, opts.storageKey, !!opts.fixedFirst, opts.fixedKey || null);
    }

    global.AuragoldColReorder = { init: init };
})(typeof window !== 'undefined' ? window : this);
