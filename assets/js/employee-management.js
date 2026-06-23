(function (window) {
    'use strict';

    var API = 'ajax/employee-management.php';

    function qs(sel, root) { return (root || document).querySelector(sel); }
    function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    function showAlert(el, msg, ok) {
        if (!el) return;
        el.textContent = msg || '';
        el.className = 'em-alert show ' + (ok ? 'em-alert-success' : 'em-alert-error');
        setTimeout(function () { el.classList.remove('show'); }, 4000);
    }

    function post(action, data, isFormData) {
        var body = data || {};
        if (!isFormData) {
            body.action = action;
            body = new URLSearchParams(body);
        } else {
            body.append('action', action);
        }
        return fetch(API, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function get(params) {
        var q = new URLSearchParams(params || {});
        return fetch(API + '?' + q.toString(), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function openModal(id) {
        var m = document.getElementById(id);
        if (m) m.classList.add('show');
    }
    function closeModal(id) {
        var m = document.getElementById(id);
        if (m) m.classList.remove('show');
    }

    function bindModalClose() {
        qsa('.em-modal-backdrop').forEach(function (backdrop) {
            backdrop.addEventListener('click', function (e) {
                if (e.target === backdrop || e.target.closest('.em-close')) {
                    backdrop.classList.remove('show');
                }
            });
        });
    }

    function bindTabs(root) {
        qsa('.em-tabs .em-tab', root).forEach(function (tab) {
            tab.addEventListener('click', function () {
                var group = tab.closest('.em-tabs-group');
                if (!group) return;
                qsa('.em-tab', group).forEach(function (t) { t.classList.remove('active'); });
                qsa('.em-tab-panel', group).forEach(function (p) { p.classList.remove('active'); });
                tab.classList.add('active');
                var panel = group.querySelector('#' + tab.getAttribute('data-panel'));
                if (panel) panel.classList.add('active');
            });
        });
    }

    function badgeForStatus(status) {
        var s = String(status || '').toLowerCase();
        if (s === 'active' || s === 'present' || s === 'approved' || s === 'completed' || s === 'paid') return 'em-badge-green';
        if (s === 'pending' || s === 'open' || s === 'draft' || s === 'in progress') return 'em-badge-yellow';
        if (s === 'rejected' || s === 'absent' || s === 'cancelled') return 'em-badge-red';
        return 'em-badge-gray';
    }

    window.EmApp = {
        API: API,
        qs: qs,
        qsa: qsa,
        showAlert: showAlert,
        post: post,
        get: get,
        openModal: openModal,
        closeModal: closeModal,
        bindModalClose: bindModalClose,
        bindTabs: bindTabs,
        badgeForStatus: badgeForStatus,
        reload: function () { window.location.reload(); }
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindModalClose();
        bindTabs(document);
    });
})(window);
