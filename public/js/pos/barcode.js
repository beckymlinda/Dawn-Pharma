/**
 * POS Barcode Scanner Module
 * Handles hardware barcode scanner input and SKU-based product lookup.
 *
 * How hardware scanners work:
 *   - They send characters very fast (like a keyboard), ending with Enter.
 *   - We detect rapid input (interval < 50ms between chars) as a scanner.
 *   - Manual typing is slower, so it falls back to normal text search.
 */

window.POSBarcodeScanner = window.POSBarcodeScanner || {};

// ─── State ────────────────────────────────────────────────────────────────────
POSBarcodeScanner.state = {
    isActive: false,
    buffer: '',
    lastKeyTime: 0,
    scannerThreshold: 50,   // ms – characters arriving faster than this = scanner
    minBarcodeLength: 3,    // ignore accidental single keystrokes
    scanTimeout: null,
    beepEnabled: true,
    lastScanTime: 0,
    scanCooldown: 800,      // ms – ignore duplicate scans within this window
};

// ─── Initialise ───────────────────────────────────────────────────────────────
POSBarcodeScanner.init = function () {
    console.log('[Barcode] Module initialising…');
    POSBarcodeScanner.attachGlobalListener();
    POSBarcodeScanner.restoreState();
    console.log('[Barcode] Module ready. Active:', POSBarcodeScanner.state.isActive);
};

// ─── Persist scanner mode across page reloads ─────────────────────────────────
POSBarcodeScanner.restoreState = function () {
    try {
        const saved = localStorage.getItem('pos_barcode_active');
        if (saved === 'true') {
            POSBarcodeScanner.activate(true /* silent */);
        }
    } catch (e) { /* localStorage unavailable */ }
};

POSBarcodeScanner.saveState = function () {
    try {
        localStorage.setItem('pos_barcode_active', POSBarcodeScanner.state.isActive ? 'true' : 'false');
    } catch (e) { /* ignore */ }
};

// ─── Toggle / Activate / Deactivate ──────────────────────────────────────────
POSBarcodeScanner.toggle = function () {
    if (POSBarcodeScanner.state.isActive) {
        POSBarcodeScanner.deactivate();
    } else {
        POSBarcodeScanner.activate();
    }
};

POSBarcodeScanner.activate = function (silent) {
    POSBarcodeScanner.state.isActive = true;
    POSBarcodeScanner.state.buffer = '';
    POSBarcodeScanner.updateUI();
    POSBarcodeScanner.saveState();
    if (!silent) {
        POSBarcodeScanner.playActivationSound();
        if (window.POSApp && POSApp.utils && POSApp.utils.showNotification) {
            POSApp.utils.showNotification('Barcode Scanner activated. Ready to scan!', 'success');
        }
    }
    console.log('[Barcode] Activated');
};

POSBarcodeScanner.deactivate = function () {
    POSBarcodeScanner.state.isActive = false;
    POSBarcodeScanner.state.buffer = '';
    clearTimeout(POSBarcodeScanner.state.scanTimeout);
    POSBarcodeScanner.updateUI();
    POSBarcodeScanner.saveState();
    if (window.POSApp && POSApp.utils && POSApp.utils.showNotification) {
        POSApp.utils.showNotification('Barcode Scanner deactivated', 'info');
    }
    console.log('[Barcode] Deactivated');
};

// ─── UI update ────────────────────────────────────────────────────────────────
POSBarcodeScanner.updateUI = function () {
    const btn        = document.getElementById('barcodeToggleBtn');
    const btnText    = document.getElementById('barcodeBtnText');
    const statusBar  = document.getElementById('barcodeScannerStatus');
    const wrapper    = document.getElementById('searchInputWrapper');
    const modeIcon   = document.getElementById('searchModeIcon');

    if (POSBarcodeScanner.state.isActive) {
        // Header button → active style
        if (btn) {
            btn.classList.add('barcode-active');
            btn.title = 'Disable Barcode Scanner';
        }
        if (btnText) btnText.textContent = 'Scanner ON';

        // Status bar
        if (statusBar) statusBar.style.display = 'flex';

        // Search input border
        if (wrapper) wrapper.classList.add('barcode-mode-active');

        // Icon switch
        if (modeIcon) {
            modeIcon.className = 'bi bi-upc-scan search-icon barcode-scan-icon';
        }
    } else {
        if (btn) {
            btn.classList.remove('barcode-active');
            btn.title = 'Toggle Barcode Scanner Mode';
        }
        if (btnText) btnText.textContent = 'Scanner';

        if (statusBar) statusBar.style.display = 'none';

        if (wrapper) wrapper.classList.remove('barcode-mode-active');

        if (modeIcon) {
            modeIcon.className = 'bi bi-search search-icon';
        }
    }
};

// ─── Flash feedback on a successful scan ──────────────────────────────────────
POSBarcodeScanner.flashSuccess = function () {
    const wrapper = document.getElementById('searchInputWrapper');
    if (!wrapper) return;
    wrapper.classList.add('barcode-scan-flash');
    setTimeout(function () { wrapper.classList.remove('barcode-scan-flash'); }, 600);
};

POSBarcodeScanner.flashError = function () {
    const wrapper = document.getElementById('searchInputWrapper');
    if (!wrapper) return;
    wrapper.classList.add('barcode-scan-error');
    setTimeout(function () { wrapper.classList.remove('barcode-scan-error'); }, 600);
};

// ─── Beep / sound ─────────────────────────────────────────────────────────────
POSBarcodeScanner.playActivationSound = function () {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.2);
    } catch (e) { /* audio not available */ }
};

POSBarcodeScanner.playSuccessBeep = function () {
    if (!POSBarcodeScanner.state.beepEnabled) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.value = 1200;
        gain.gain.setValueAtTime(0.4, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.15);
    } catch (e) { /* ignore */ }
};

POSBarcodeScanner.playErrorBeep = function () {
    if (!POSBarcodeScanner.state.beepEnabled) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [350, 280].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sawtooth';
            osc.frequency.value = freq;
            const t = ctx.currentTime + i * 0.15;
            gain.gain.setValueAtTime(0.3, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.12);
            osc.start(t);
            osc.stop(t + 0.12);
        });
    } catch (e) { /* ignore */ }
};

// ─── Global keyboard listener ─────────────────────────────────────────────────
POSBarcodeScanner.attachGlobalListener = function () {
    document.addEventListener('keydown', POSBarcodeScanner.handleGlobalKeydown);
};

POSBarcodeScanner.handleGlobalKeydown = function (e) {
    if (!POSBarcodeScanner.state.isActive) return;

    // Ignore if focus is in a non-search input / textarea (e.g. payment fields)
    const tag = document.activeElement ? document.activeElement.tagName : '';
    const activeId = document.activeElement ? document.activeElement.id : '';
    const isSearchInput = (activeId === 'cartSearch' || activeId === 'barcodeInput');
    if ((tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') && !isSearchInput) {
        return;
    }

    const now = Date.now();
    const timeSinceLast = now - POSBarcodeScanner.state.lastKeyTime;
    POSBarcodeScanner.state.lastKeyTime = now;

    // Enter = commit the buffer as a barcode
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = POSBarcodeScanner.state.buffer.trim();
        POSBarcodeScanner.state.buffer = '';
        clearTimeout(POSBarcodeScanner.state.scanTimeout);

        if (barcode.length >= POSBarcodeScanner.state.minBarcodeLength) {
            // Cooldown guard – ignore duplicate rapid scans
            if (now - POSBarcodeScanner.state.lastScanTime < POSBarcodeScanner.state.scanCooldown) {
                console.log('[Barcode] Scan cooldown, ignoring duplicate:', barcode);
                return;
            }
            POSBarcodeScanner.state.lastScanTime = now;
            POSBarcodeScanner.processScan(barcode);
        }
        // Clear the visible search box too
        const searchInput = document.getElementById('cartSearch');
        if (searchInput) searchInput.value = '';
        return;
    }

    // Escape = clear buffer
    if (e.key === 'Escape') {
        POSBarcodeScanner.state.buffer = '';
        clearTimeout(POSBarcodeScanner.state.scanTimeout);
        const searchInput = document.getElementById('cartSearch');
        if (searchInput) searchInput.value = '';
        return;
    }

    // Accumulate printable characters
    if (e.key && e.key.length === 1) {
        // If a very long gap – user is typing manually, let normal search handle it
        if (POSBarcodeScanner.state.buffer.length > 0 && timeSinceLast > 300) {
            POSBarcodeScanner.state.buffer = '';
        }
        POSBarcodeScanner.state.buffer += e.key;

        // Auto-clear buffer after 1.5 s of inactivity (incomplete scan)
        clearTimeout(POSBarcodeScanner.state.scanTimeout);
        POSBarcodeScanner.state.scanTimeout = setTimeout(function () {
            POSBarcodeScanner.state.buffer = '';
        }, 1500);
    }
};

// ─── Process a complete scanned barcode ───────────────────────────────────────
POSBarcodeScanner.processScan = function (barcode) {
    console.log('[Barcode] Processing scan:', barcode);

    // Try to show what was scanned briefly in the search box
    const searchInput = document.getElementById('cartSearch');
    if (searchInput) {
        searchInput.value = barcode;
        searchInput.placeholder = 'Scanning: ' + barcode;
        setTimeout(function () {
            if (searchInput) {
                searchInput.value = '';
                searchInput.placeholder = 'Search products…';
            }
        }, 1200);
    }

    // 1. Try exact SKU match first
    const product = POSBarcodeScanner.findBySku(barcode);
    if (product) {
        POSBarcodeScanner.addProductToCart(product);
        return;
    }

    // 2. Try name partial match as fallback
    const nameMatches = POSBarcodeScanner.findByName(barcode);
    if (nameMatches.length === 1) {
        POSBarcodeScanner.addProductToCart(nameMatches[0]);
        return;
    }
    if (nameMatches.length > 1) {
        // Show dropdown with the matches so cashier can pick
        if (window.POSSearch && POSSearch.displayResults) {
            POSSearch.displayResults(nameMatches);
        }
        if (window.POSApp && POSApp.utils && POSApp.utils.showNotification) {
            POSApp.utils.showNotification('Multiple products found for "' + barcode + '" – please select one', 'warning');
        }
        POSBarcodeScanner.flashError();
        return;
    }

    // 3. Nothing found
    console.warn('[Barcode] No product found for:', barcode);
    POSBarcodeScanner.flashError();
    POSBarcodeScanner.playErrorBeep();
    if (window.POSApp && POSApp.utils && POSApp.utils.showNotification) {
        POSApp.utils.showNotification('No product found for barcode: ' + barcode, 'danger');
    }
};

// ─── Product lookup helpers ───────────────────────────────────────────────────
POSBarcodeScanner.getProducts = function () {
    if (window.POSProducts && POSProducts.state && POSProducts.state.productsData) {
        return POSProducts.state.productsData;
    }
    return [];
};

POSBarcodeScanner.findBySku = function (barcode) {
    const upper = barcode.toUpperCase().trim();
    const products = POSBarcodeScanner.getProducts();
    for (var i = 0; i < products.length; i++) {
        const p = products[i];
        if (p.sku && p.sku.toUpperCase().trim() === upper) {
            return p;
        }
    }
    return null;
};

POSBarcodeScanner.findByName = function (query) {
    const lower = query.toLowerCase().trim();
    const products = POSBarcodeScanner.getProducts();
    const results = [];
    for (var i = 0; i < products.length; i++) {
        const p = products[i];
        if (p.name && p.name.toLowerCase().indexOf(lower) !== -1) {
            results.push(p);
        }
    }
    return results;
};

// ─── Add to cart ──────────────────────────────────────────────────────────────
POSBarcodeScanner.addProductToCart = function (product) {
    console.log('[Barcode] Adding product to cart:', product.name, '(SKU:', product.sku, ')');

    POSBarcodeScanner.flashSuccess();
    POSBarcodeScanner.playSuccessBeep();

    if (window.POSProducts && POSProducts.addToCartClick) {
        POSProducts.addToCartClick(product.id, 1);
    } else {
        console.error('[Barcode] POSProducts.addToCartClick not available');
    }
};

// ─── Auto-init ────────────────────────────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', POSBarcodeScanner.init);
} else {
    POSBarcodeScanner.init();
}
