/**
 * Shared helpers for voucher diamond/stone queues on invoice-like pages (same globals as sale-order modals).
 */
(function () {
    'use strict';

    window.auragoldVoucherDiamondStoneAppendPendingToOrderData = function (orderData) {
        if (!orderData || typeof orderData !== 'object') {
            return;
        }
        orderData.pending_diamond_allocations = Array.isArray(window.__pendingSaleOrderDiamondLines)
            ? window.__pendingSaleOrderDiamondLines.slice()
            : [];
        orderData.pending_stone_allocations = Array.isArray(window.__pendingSaleOrderStoneLines)
            ? window.__pendingSaleOrderStoneLines.slice()
            : [];
    };

    window.auragoldVoucherDiamondStonePopulateFromOrder = function (order) {
        window.__auragoldVoucherDbId = order && order.id ? parseInt(order.id, 10) || 0 : 0;
        window.__saleOrderDiamondIssueRows =
            order && Array.isArray(order.diamond_issues) ? order.diamond_issues.slice() : [];
        if (typeof window.renderSaleOrderDiamondLinesPanel === 'function') {
            window.renderSaleOrderDiamondLinesPanel();
        }
        window.__saleOrderStoneIssueRows =
            order && Array.isArray(order.stone_issues) ? order.stone_issues.slice() : [];
        if (typeof window.renderSaleOrderStoneLinesPanel === 'function') {
            window.renderSaleOrderStoneLinesPanel();
        }
    };

    /** Pass saved document id from AJAX response (invoice_id / order_id / quotation id). */
    window.auragoldVoucherDiamondStoneOnSaveSuccess = function (savedId) {
        window.__pendingSaleOrderDiamondLines = [];
        window.__pendingSaleOrderStoneLines = [];
        var id = parseInt(savedId, 10) || 0;
        if (id && typeof window.refreshSaleOrderDiamondIssuesFromServer === 'function') {
            window.refreshSaleOrderDiamondIssuesFromServer(id);
        }
        if (id && typeof window.refreshSaleOrderStoneIssuesFromServer === 'function') {
            window.refreshSaleOrderStoneIssuesFromServer(id);
        }
        if (id) {
            window.__auragoldVoucherDbId = id;
        }
        window.__saleOrderDiamondShowSelectedOnly = false;
        if (typeof window.refreshSaleOrderDiamondModalBanner === 'function') {
            window.refreshSaleOrderDiamondModalBanner();
        }
    };

    /** When building postData from orderData, JSON-stringify pending queues like items/payments. */
    window.auragoldVoucherDiamondStonePostDataShouldStringify = function (key) {
        return (
            key === 'items' ||
            key === 'payments' ||
            key === 'pending_diamond_allocations' ||
            key === 'pending_stone_allocations'
        );
    };
})();
