/**
 * POS Offline Module
 *
 * Keeps the till selling when the internet or the server is unreachable:
 *   - A sale that cannot reach the server is saved on this device (localStorage)
 *     and the receipt prints as normal.
 *   - Saved sales are uploaded automatically when the connection returns
 *     (on the browser's "online" event, every 30 seconds, and on page load).
 *   - The invoice number is the key: the server returns the existing sale if
 *     the same sale is sent twice, so a retry can never double-record a sale.
 *   - A badge in the POS header shows Online / Offline / sales waiting.
 *
 * Each saved sale remembers which cashier made it, and is only uploaded while
 * that cashier is logged in, so it is recorded under the right person.
 */

window.POSOffline = window.POSOffline || {};

POSOffline.QUEUE_KEY = 'pos_offline_sales_v1';
POSOffline.FAILED_KEY = 'pos_offline_failed_v1';
POSOffline.SYNC_EVERY = 30 * 1000;
POSOffline.FINALIZE_URL = '/pos/finalize';

POSOffline.state = {
    syncing: false,
    lastError: null,      // why the last upload attempt stopped (shown in the badge tooltip)
    needsLogin: false,
};

// ─── Storage ────────────────────────────────────────────────────────────────
POSOffline.read = function (key) {
    try {
        const list = JSON.parse(localStorage.getItem(key) || '[]');
        return Array.isArray(list) ? list : [];
    } catch (e) {
        return [];
    }
};

POSOffline.write = function (key, list) {
    try {
        localStorage.setItem(key, JSON.stringify(list));
        return true;
    } catch (e) {
        console.error('[Offline] Could not save to this device:', e);
        return false;
    }
};

POSOffline.currentUserId = function () {
    const meta = document.querySelector('meta[name="pos-user"]');
    return meta ? String(meta.getAttribute('content') || '') : '';
};

/** Saved sales of the cashier who is logged in now. */
POSOffline.pendingForMe = function () {
    const me = POSOffline.currentUserId();
    return POSOffline.read(POSOffline.QUEUE_KEY).filter(function (entry) { return String(entry.user_id) === me; });
};

POSOffline.pendingCount = function () {
    return POSOffline.pendingForMe().length;
};

// ─── Stock on this device ──────────────────────────────────────────────────
/**
 * The server does not know about sales still waiting on this device, so what can
 * be sold here is: the server's stock minus those sales. Each product keeps the
 * server's figure in server_stock and the sellable figure in current_stock.
 *
 * The last server figures are also kept on this device, because a POS page opened
 * offline is an old saved copy whose stock may be out of date.
 */
POSOffline.STOCK_KEY = 'pos_stock_snapshot_v1';

/** Quantity per product in sales saved on this device and not uploaded yet (all cashiers). */
POSOffline.pendingQuantities = function () {
    const totals = {};
    POSOffline.read(POSOffline.QUEUE_KEY).forEach(function (entry) {
        ((entry.data && entry.data.items) || []).forEach(function (item) {
            const id = String(item.product_id);
            totals[id] = (totals[id] || 0) + (parseFloat(item.quantity) || 0);
        });
    });
    return totals;
};

POSOffline.readSnapshot = function () {
    try {
        const snap = JSON.parse(localStorage.getItem(POSOffline.STOCK_KEY) || 'null');
        return snap && snap.stock ? snap : null;
    } catch (e) {
        return null;
    }
};

POSOffline.saveSnapshot = function (products, at) {
    const stock = {};
    products.forEach(function (p) { stock[String(p.id)] = p.server_stock; });
    try {
        localStorage.setItem(POSOffline.STOCK_KEY, JSON.stringify({ at: at, stock: stock }));
    } catch (e) {
        console.warn('[Offline] Could not save stock levels on this device:', e);
    }
};

/**
 * Work out the sellable stock of each product.
 * serverAt: when `products` come straight from the server, the server time of that
 * data (their current_stock is then the server's figure); omit it otherwise.
 */
POSOffline.applyStock = function (products, serverAt) {
    if (!Array.isArray(products)) return;
    const fromServer = typeof serverAt === 'number';

    if (fromServer) {
        // Newer figures saved on this device win over an old copy of the page
        const snap = POSOffline.readSnapshot();
        const useSnap = snap && snap.at > serverAt;
        products.forEach(function (p) {
            const saved = useSnap ? snap.stock[String(p.id)] : undefined;
            p.server_stock = typeof saved === 'number' ? saved : (parseFloat(p.current_stock) || 0);
        });
        if (!useSnap) POSOffline.saveSnapshot(products, serverAt);
    }

    const pending = POSOffline.pendingQuantities();
    products.forEach(function (p) {
        if (typeof p.server_stock !== 'number') p.server_stock = parseFloat(p.current_stock) || 0;
        p.current_stock = Math.max(0, p.server_stock - (pending[String(p.id)] || 0));
    });
};

/** A saved sale was recorded by the server: its stock is now gone there too. */
POSOffline.stockUploaded = function (entry) {
    const products = window.POSProducts && POSProducts.state.productsData;
    const snap = POSOffline.readSnapshot();
    ((entry.data && entry.data.items) || []).forEach(function (item) {
        const id = String(item.product_id);
        const qty = parseFloat(item.quantity) || 0;
        const product = Array.isArray(products) && products.find(function (p) { return String(p.id) === id; });
        if (product && typeof product.server_stock === 'number') {
            product.server_stock = Math.max(0, product.server_stock - qty);
        }
        if (snap && typeof snap.stock[id] === 'number') {
            snap.stock[id] = Math.max(0, snap.stock[id] - qty);
        }
    });
    if (snap) {
        try { localStorage.setItem(POSOffline.STOCK_KEY, JSON.stringify(snap)); } catch (e) {}
    }
};

/** Cart lines asking for more than this device can sell, as messages. */
POSOffline.stockShortages = function (items) {
    const products = (window.POSProducts && POSProducts.state.productsData) || [];
    const wanted = {};
    (items || []).forEach(function (item) {
        const id = String(item.product_id || (item.product && item.product.id));
        wanted[id] = (wanted[id] || 0) + (parseFloat(item.quantity) || 0);
    });
    return Object.keys(wanted).map(function (id) {
        const product = products.find(function (p) { return String(p.id) === id; });
        if (!product) return null;   // not in this till's list: the server checks it
        const available = parseFloat(product.current_stock) || 0;
        if (wanted[id] <= available + 0.0001) return null;
        return product.name + ': ' + available + ' available, ' + wanted[id] + ' in the cart';
    }).filter(Boolean);
};

POSOffline.renderStock = function () {
    if (window.POSGrid) {
        window.POSGrid.renderCategories();
        window.POSGrid.render();
    }
};

// ─── Deciding when to save offline ─────────────────────────────────────────
/**
 * True when a failed request means "the server could not be reached" rather than
 * "the server refused this sale". Only the first kind may be saved for later:
 * a refused sale (e.g. not enough stock) must be shown to the cashier.
 */
POSOffline.isUnreachableStatus = function (status) {
    return status === 0 || status === 408 || status === 502 || status === 503 || status === 504;
};

POSOffline.isOffline = function () {
    return typeof navigator !== 'undefined' && navigator.onLine === false;
};

// ─── Saving a sale ─────────────────────────────────────────────────────────
/**
 * Save a sale on this device for upload later.
 * requestData is exactly what would have been sent to /pos/finalize.
 */
POSOffline.queueSale = function (requestData, paidTotal) {
    const entry = {
        invoice_no: requestData.invoice_no,
        user_id: POSOffline.currentUserId(),
        queued_at: new Date().toISOString(),
        attempts: 0,
        data: Object.assign({}, requestData, {
            offline: true,
            sold_at: new Date().toISOString(),
            paid_total: paidTotal,
            // keep only what the server needs from each cart line
            items: (requestData.items || []).map(function (item) {
                return {
                    product_id: item.product_id || (item.product && item.product.id),
                    quantity: item.quantity,
                    name: item.name || (item.product && item.product.name) || '',
                };
            }),
        }),
    };

    const queue = POSOffline.read(POSOffline.QUEUE_KEY);
    if (!queue.some(function (e) { return e.invoice_no === entry.invoice_no; })) {
        queue.push(entry);
    }
    const saved = POSOffline.write(POSOffline.QUEUE_KEY, queue);
    POSOffline.updateBadge();
    return saved;
};

// ─── Uploading ─────────────────────────────────────────────────────────────
/**
 * Upload this cashier's saved sales, oldest first. Stops at the first sale that
 * cannot reach the server (still offline, logged out) and tries again later.
 * A sale the server refuses is moved to a "needs attention" list instead of
 * being retried forever.
 */
POSOffline.sync = function () {
    if (POSOffline.state.syncing || POSOffline.isOffline()) {
        POSOffline.updateBadge();
        return Promise.resolve();
    }
    const mine = POSOffline.pendingForMe();
    if (!mine.length) {
        POSOffline.updateBadge();
        return Promise.resolve();
    }

    POSOffline.state.syncing = true;
    POSOffline.updateBadge();

    let chain = Promise.resolve(true);
    mine.forEach(function (entry) {
        chain = chain.then(function (keepGoing) {
            return keepGoing ? POSOffline.uploadOne(entry) : false;
        });
    });

    return chain.catch(function (e) {
        console.error('[Offline] Sync error:', e);
    }).then(function () {
        POSOffline.state.syncing = false;
        POSOffline.updateBadge();
        if (window.POSProducts && POSOffline.pendingCount() === 0) {
            window.POSProducts.refreshProductData().catch(function () {});
        }
    });
};

/** Upload one saved sale. Resolves true to continue with the next one. */
POSOffline.uploadOne = function (entry) {
    entry.attempts = (entry.attempts || 0) + 1;
    POSOffline.replaceEntry(entry);

    return fetch(POSOffline.FINALIZE_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(entry.data),
    }).then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (body) {
            if (response.ok && body.success) {
                POSOffline.stockUploaded(entry);
                POSOffline.removeEntry(entry.invoice_no);
                POSOffline.state.lastError = null;
                POSOffline.state.needsLogin = false;
                return true;
            }
            if (response.status === 401 || response.status === 419 || response.redirected) {
                // Logged out while offline: keep the sales until this cashier logs in again
                POSOffline.state.needsLogin = true;
                POSOffline.state.lastError = 'Log in again to upload the saved sales.';
                return false;
            }
            if (POSOffline.isUnreachableStatus(response.status) || response.status >= 500) {
                POSOffline.state.lastError = 'Server not available (' + response.status + ')';
                return false;
            }
            // Refused by the server (e.g. no open register): set aside for a manager
            POSOffline.moveToFailed(entry, body.error || ('Rejected (' + response.status + ')'));
            return true;
        });
    }, function () {
        POSOffline.state.lastError = 'No connection';
        return false;
    });
};

POSOffline.replaceEntry = function (entry) {
    const queue = POSOffline.read(POSOffline.QUEUE_KEY).map(function (e) {
        return e.invoice_no === entry.invoice_no ? entry : e;
    });
    POSOffline.write(POSOffline.QUEUE_KEY, queue);
};

POSOffline.removeEntry = function (invoiceNo) {
    POSOffline.write(POSOffline.QUEUE_KEY, POSOffline.read(POSOffline.QUEUE_KEY).filter(function (e) {
        return e.invoice_no !== invoiceNo;
    }));
};

POSOffline.moveToFailed = function (entry, reason) {
    POSOffline.removeEntry(entry.invoice_no);
    const failed = POSOffline.read(POSOffline.FAILED_KEY);
    failed.push(Object.assign({}, entry, { error: reason, failed_at: new Date().toISOString() }));
    POSOffline.write(POSOffline.FAILED_KEY, failed);
    console.error('[Offline] Sale ' + entry.invoice_no + ' was refused by the server:', reason);
    if (window.POSApp && POSApp.utils) {
        POSApp.utils.showNotification('❌ Saved sale ' + entry.invoice_no + ' could not be uploaded: ' + reason, 'error');
    }
};

// ─── Status badge ──────────────────────────────────────────────────────────
POSOffline.updateBadge = function () {
    const badge = document.getElementById('offlineStatusBadge');
    if (!badge) return;

    const pending = POSOffline.pendingCount();
    const failed = POSOffline.read(POSOffline.FAILED_KEY).filter(function (e) {
        return String(e.user_id) === POSOffline.currentUserId();
    }).length;
    let text, color, title;

    if (POSOffline.state.syncing) {
        text = 'Uploading ' + pending + '…'; color = '#2563eb';
        title = 'Uploading sales saved while offline';
    } else if (POSOffline.isOffline()) {
        text = pending ? 'Offline · ' + pending + ' saved' : 'Offline'; color = '#dc2626';
        title = 'No internet. Keep selling: sales are saved on this device and uploaded when the connection returns.';
    } else if (pending) {
        text = pending + ' waiting'; color = '#d97706';
        title = (POSOffline.state.lastError || 'Waiting to upload') + '. Click to try now.';
    } else {
        text = 'Online'; color = '#16a34a';
        title = 'Connected. All sales are uploaded.';
    }
    if (failed) {
        text += ' · ' + failed + ' failed';
        title += ' ' + failed + ' saved sale(s) were refused by the server; ask a manager to check them.';
    }

    badge.textContent = text;
    badge.title = title;
    badge.style.background = color;
};

// ─── Start ─────────────────────────────────────────────────────────────────
POSOffline.init = function () {
    // Stock in the page, less sales saved on this device (newer saved figures win over an old page)
    if (window.POSProducts && Array.isArray(POSProducts.state.productsData)) {
        POSOffline.applyStock(POSProducts.state.productsData,
            typeof window.productsStockAt === 'number' ? window.productsStockAt : undefined);
        POSOffline.renderStock();
    }

    window.addEventListener('online', function () { POSOffline.sync(); });
    window.addEventListener('offline', POSOffline.updateBadge);
    setInterval(POSOffline.sync, POSOffline.SYNC_EVERY);

    const badge = document.getElementById('offlineStatusBadge');
    if (badge) badge.addEventListener('click', function () { POSOffline.sync(); });

    POSOffline.updateBadge();
    POSOffline.sync();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', POSOffline.init);
} else {
    POSOffline.init();
}
