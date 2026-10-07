/**
 * POS Product Grid
 * Tappable product tiles next to the cart, so the cashier can sell without
 * typing. Filtered by the search box and by category chips; tiles show price,
 * stock (amber when low, disabled when out) and how many are already in the cart.
 */

window.POSGrid = window.POSGrid || {};

POSGrid.LOW_STOCK = 5;          // used when a product has no alert quantity of its own
POSGrid.MAX_TILES = 120;        // keep the page fast with very large catalogues

POSGrid.state = {
    category: '',
    query: '',
};

POSGrid.esc = function (value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
};

POSGrid.products = function () {
    return (window.POSProducts && POSProducts.state.productsData) || window.productsData || [];
};

POSGrid.inCart = function (productId) {
    const items = (window.POSCart && POSCart.state.items) || [];
    return items.reduce(function (sum, item) {
        const id = item.product_id || item.id || (item.product && item.product.id);
        return String(id) === String(productId) ? sum + (parseFloat(item.quantity) || 0) : sum;
    }, 0);
};

POSGrid.categoryName = function (product) {
    return (product.category && product.category.name) || '';
};

POSGrid.renderCategories = function () {
    const box = document.getElementById('posCategoryChips');
    if (!box) return;

    const names = Array.from(new Set(POSGrid.products().map(POSGrid.categoryName).filter(Boolean))).sort();
    if (names.length < 2) {
        box.innerHTML = '';
        box.style.display = 'none';
        return;
    }
    box.style.display = '';
    box.innerHTML = [''].concat(names).map(function (name) {
        const active = name === POSGrid.state.category;
        return '<button type="button" role="tab" aria-selected="' + active + '" class="pos-chip' + (active ? ' active' : '') +
            '" data-category="' + POSGrid.esc(name) + '">' + (name ? POSGrid.esc(name) : 'All') + '</button>';
    }).join('');
};

POSGrid.render = function () {
    const grid = document.getElementById('posProductGrid');
    if (!grid) return;

    const query = POSGrid.state.query.trim().toLowerCase();
    const category = POSGrid.state.category;
    const matches = POSGrid.products().filter(function (p) {
        if (category && POSGrid.categoryName(p) !== category) return false;
        if (!query) return true;
        return (p.name || '').toLowerCase().includes(query) || (p.sku || '').toLowerCase().includes(query);
    });

    if (!matches.length) {
        grid.innerHTML = '<div class="pos-grid-empty"><i class="bi bi-search"></i><div>No products match' +
            (query ? ' "' + POSGrid.esc(POSGrid.state.query) + '"' : '') + '</div></div>';
        return;
    }

    // Keep keyboard focus on the same tile after re-drawing
    const focused = document.activeElement && document.activeElement.closest && document.activeElement.closest('.pos-tile');
    const focusedId = focused && grid.contains(focused) ? focused.dataset.productId : null;

    const formatPrice = window.POSApp && POSApp.utils ? POSApp.utils.formatCurrency : function (v) { return 'MWK ' + v; };
    grid.innerHTML = matches.slice(0, POSGrid.MAX_TILES).map(function (p) {
        const stock = parseFloat(p.current_stock) || 0;
        const low = parseFloat(p.alert_quantity) > 0 ? parseFloat(p.alert_quantity) : POSGrid.LOW_STOCK;
        const inCart = POSGrid.inCart(p.id);
        const out = stock <= 0;
        let stockLabel = out ? 'Out of stock' : stock + ' in stock';
        let stockClass = out ? 'out' : (stock <= low ? 'low' : '');

        return '<button type="button" class="pos-tile ' + stockClass + (inCart ? ' in-cart' : '') + '" data-product-id="' + p.id + '"' +
            (out ? ' disabled aria-disabled="true"' : '') +
            ' title="' + POSGrid.esc(p.name) + (p.sku ? ' (' + POSGrid.esc(p.sku) + ')' : '') + '">' +
            (inCart ? '<span class="pos-tile-count" aria-label="' + inCart + ' in cart">' + inCart + '</span>' : '') +
            '<span class="pos-tile-name">' + POSGrid.esc(p.name) + '</span>' +
            '<span class="pos-tile-price">' + formatPrice(p.selling_price) + '</span>' +
            '<span class="pos-tile-stock">' + stockLabel + '</span>' +
            '</button>';
    }).join('') + (matches.length > POSGrid.MAX_TILES
        ? '<div class="pos-grid-more">' + (matches.length - POSGrid.MAX_TILES) + ' more: type in the search box to narrow down</div>'
        : '');

    if (focusedId) {
        const again = grid.querySelector('.pos-tile[data-product-id="' + focusedId + '"]');
        if (again) again.focus();
    }
};

POSGrid.init = function () {
    const grid = document.getElementById('posProductGrid');
    if (!grid) return;

    grid.addEventListener('click', function (event) {
        const tile = event.target.closest('.pos-tile');
        if (!tile || tile.disabled || !window.POSProducts) return;
        POSProducts.addToCartClick(tile.dataset.productId, 1);
    });

    const chips = document.getElementById('posCategoryChips');
    if (chips) {
        chips.addEventListener('click', function (event) {
            const chip = event.target.closest('.pos-chip');
            if (!chip) return;
            POSGrid.state.category = chip.dataset.category;
            POSGrid.renderCategories();
            POSGrid.render();
        });
    }

    const search = document.getElementById('cartSearch');
    if (search) {
        search.addEventListener('input', function () {
            POSGrid.state.query = search.value;
            POSGrid.render();
        });
    }

    POSGrid.renderCategories();
    POSGrid.render();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', POSGrid.init);
} else {
    POSGrid.init();
}
