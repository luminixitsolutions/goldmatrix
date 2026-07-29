/**
 * Column drag-reorder + resize (class acr-col-table).
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
            'table.acr-col-table thead th.acr-th-reorder,',
            'table.acr-col-table thead th.acr-th-resizable { position: relative !important; }',
            'table.acr-col-table thead th .acr-th-drag {',
            '  display: inline-flex; align-items: center; justify-content: center;',
            '  vertical-align: middle; margin-left: 0.35rem; cursor: grab; color: #c9a962;',
            '  line-height: 1; flex-shrink: 0;',
            '}',
            'table.acr-col-table thead th .acr-th-drag .feather { width: 0.95rem; height: 0.95rem; }',
            'table.acr-col-table thead th .acr-th-drag:active { cursor: grabbing; }',
            'table.acr-col-table thead th.acr-th-reorder { cursor: grab; }',
            'table.acr-col-table thead th.acr-sortable-drag { opacity: 0.95; background: #fff7ed !important; }',
            'table.acr-col-table thead th .acr-th-resize {',
            '  position: absolute; right: 0; top: 0; bottom: 0; width: 6px;',
            '  cursor: col-resize; z-index: 4;',
            '  background: linear-gradient(90deg, transparent, rgba(15, 23, 42, 0.08));',
            '}',
            'table.acr-col-table thead th .acr-th-resize:hover {',
            '  background: rgba(201, 169, 98, 0.35);',
            '}',
            'table.acr-col-table thead th.acr-col-resizing { user-select: none; }',
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

    function thMinWidth(th, fallback) {
        var attr = th.getAttribute('data-acr-min');
        if (attr != null && attr !== '') {
            var n = parseInt(attr, 10);
            if (!isNaN(n) && n > 0) return n;
        }
        var st = th.style.minWidth || window.getComputedStyle(th).minWidth;
        if (st && st !== 'auto' && st !== '0px') {
            var p = parseInt(st, 10);
            if (!isNaN(p) && p > 0) return p;
        }
        return fallback || 60;
    }

    function applyWidths(table, widths, minWidthDefault) {
        if (!widths || typeof widths !== 'object') return;
        table.querySelectorAll('thead th[data-col]').forEach(function (th) {
            var k = th.getAttribute('data-col');
            if (!k || widths[k] == null) return;
            var floor = thMinWidth(th, minWidthDefault);
            var px = Math.max(floor, parseInt(widths[k], 10) || 0);
            th.style.width = px + 'px';
            th.style.minWidth = px + 'px';
            table.querySelectorAll('tbody td[data-col="' + k + '"], tfoot td[data-col="' + k + '"]').forEach(function (td) {
                td.style.minWidth = px + 'px';
            });
        });
    }

    function collectWidths(table) {
        var w = {};
        table.querySelectorAll('thead th[data-col]').forEach(function (th) {
            var k = th.getAttribute('data-col');
            if (k) w[k] = Math.round(th.getBoundingClientRect().width);
        });
        return w;
    }

    function loadJson(key) {
        try {
            var raw = localStorage.getItem(key);
            if (!raw) return null;
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function loadOrder(key) {
        var o = loadJson(key);
        return Array.isArray(o) ? o : null;
    }

    function loadWidths(key) {
        var o = loadJson(key);
        return o && typeof o === 'object' && !Array.isArray(o) ? o : null;
    }

    function saveOrder(key, order) {
        try {
            localStorage.setItem(key, JSON.stringify(order));
        } catch (e) {}
    }

    function saveWidths(key, widths) {
        try {
            localStorage.setItem(key, JSON.stringify(widths));
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

    function enhanceHeaders(table, fixedFirst, fixedKey, fixedLast, fixedKeys) {
        var tr = theadRow(table);
        if (!tr) return;
        var ths = tr.querySelectorAll('th');
        var lastIdx = ths.length - 1;
        var fixedKeySet = {};
        if (fixedKeys && fixedKeys.length) {
            fixedKeys.forEach(function (k) { if (k) fixedKeySet[k] = true; });
        }
        ths.forEach(function (th, idx) {
            if (th._acrEnhanced) return;
            th._acrEnhanced = true;
            var k = th.getAttribute('data-col');
            var isFixed = (fixedFirst && (idx === 0 || (fixedKey && k === fixedKey)))
                || (fixedLast && idx === lastIdx)
                || (k && fixedKeySet[k]);
            th.classList.toggle('acr-th-fixed', isFixed);
            th.classList.toggle('acr-th-reorder', !isFixed);
            if (!isFixed) {
                th.classList.add('acr-th-resizable');
                if (!th.querySelector('.acr-th-resize')) {
                    var resize = document.createElement('span');
                    resize.className = 'acr-th-resize';
                    resize.title = 'Drag to resize column';
                    resize.setAttribute('aria-hidden', 'true');
                    th.appendChild(resize);
                }
                if (!th.querySelector('.acr-th-drag')) {
                    var drag = document.createElement('span');
                    drag.className = 'acr-th-drag';
                    drag.title = 'Drag to reorder columns';
                    drag.innerHTML = '<i class="feather icon-move" aria-hidden="true"></i>';
                    th.appendChild(drag);
                }
            }
        });
    }

    function bindResize(table, widthsKey, minWidthDefault) {
        if (table._acrResizeBound) return;
        table._acrResizeBound = true;
        table.addEventListener('mousedown', function (e) {
            var handle = e.target.closest ? e.target.closest('.acr-th-resize') : null;
            if (!handle || !table.contains(handle)) return;
            e.preventDefault();
            e.stopPropagation();
            var th = handle.closest('th');
            if (!th) return;
            var startX = e.clientX;
            var startW = th.getBoundingClientRect().width;
            var minW = thMinWidth(th, minWidthDefault);
            function onMove(e2) {
                var dx = e2.clientX - startX;
                var w = Math.max(minW, Math.round(startW + dx));
                th.style.width = w + 'px';
                th.style.minWidth = w + 'px';
                var col = th.getAttribute('data-col');
                if (col) {
                    table.querySelectorAll('tbody td[data-col="' + col + '"], tfoot td[data-col="' + col + '"]').forEach(function (td) {
                        td.style.minWidth = w + 'px';
                    });
                }
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                document.body.style.cursor = '';
                th.classList.remove('acr-col-resizing');
                if (widthsKey) saveWidths(widthsKey, collectWidths(table));
            }
            th.classList.add('acr-col-resizing');
            document.body.style.cursor = 'col-resize';
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    function bindSortable(table, storageKey, fixedFirst, fixedKey, fixedLast) {
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
            // Whole header is draggable; resize handle is filtered out.
            // forceFallback is required for <table>/<th> column reorder in Chromium.
            handle: 'th.acr-th-reorder',
            draggable: 'th.acr-th-reorder',
            filter: '.acr-th-fixed, .acr-th-resize',
            preventOnFilter: true,
            forceFallback: true,
            fallbackOnBody: true,
            fallbackTolerance: 3,
            direction: 'horizontal',
            ghostClass: 'acr-sortable-ghost',
            chosenClass: 'acr-sortable-chosen',
            dragClass: 'acr-sortable-drag',
            onEnd: function () {
                var order = getOrder(table);
                var ok = validPermutation(order, lastGood);
                if (fixedFirst) {
                    var fk = fixedKey || theadRow(table).cells[0].getAttribute('data-col');
                    if (order[0] !== fk) ok = false;
                }
                if (fixedLast && order.length) {
                    var lastKey = lastGood[lastGood.length - 1];
                    if (order[order.length - 1] !== lastKey) ok = false;
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

    function refresh(table) {
        if (!table || table.tagName !== 'TABLE' || !table._acrOpts) return;
        var opts = table._acrOpts;
        ensureDataCols(table);
        applyOrder(table, getOrder(table));
        var w = loadWidths(opts.widthsStorageKey);
        if (w) applyWidths(table, w, opts.minWidth || 60);
    }

    /**
     * @param {string|HTMLElement} selector
     * @param {{ storageKey: string, widthsStorageKey?: string, fixedFirst?: boolean, fixedKey?: string, fixedLast?: boolean, fixedKeys?: string[], minWidth?: number }} opts
     */
    function init(selector, opts) {
        opts = opts || {};
        if (!opts.storageKey) return;
        var table = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!table || table.tagName !== 'TABLE') return;

        opts.widthsStorageKey = opts.widthsStorageKey || (opts.storageKey + '_widths');
        opts.minWidth = opts.minWidth || 60;
        table._acrOpts = opts;

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
        if (opts.fixedLast && order.length && defaultOrder.length) {
            var lastKey = defaultOrder[defaultOrder.length - 1];
            order = order.filter(function (k) { return k !== lastKey; });
            order.push(lastKey);
        }
        if (validPermutation(order, defaultOrder)) {
            applyOrder(table, order);
        }

        enhanceHeaders(table, !!opts.fixedFirst, opts.fixedKey || null, !!opts.fixedLast, opts.fixedKeys || null);
        bindResize(table, opts.widthsStorageKey, opts.minWidth);
        bindSortable(table, opts.storageKey, !!opts.fixedFirst, opts.fixedKey || null, !!opts.fixedLast);

        var savedW = loadWidths(opts.widthsStorageKey);
        if (savedW) applyWidths(table, savedW, opts.minWidth);
    }

    global.AuragoldColReorder = { init: init, refresh: refresh };
})(typeof window !== 'undefined' ? window : this);
