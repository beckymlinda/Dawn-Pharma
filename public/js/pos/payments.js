/**
 * POS Payments Module
 * Handles payment processing, receipt generation, and printing
 */

// Payments Module Namespace
window.POSPayments = window.POSPayments || {};

// Payments State
POSPayments.state = {
    paymentMethods: [],
    receiptContent: null,
    amountGiven: 0,
    changeAmount: 0
};

// Payments Initialization
POSPayments.init = function() {
    console.log('POS Payments module initializing...');
    
    // Make functions globally available for onclick handlers
    window.updatePaymentSummary = POSPayments.updatePaymentSummary;
    window.addPaymentMethod = POSPayments.addPaymentMethod;
    window.removePaymentMethod = POSPayments.removePaymentMethod;
    window.finalizePayment = POSPayments.finalizePayment;
    window.printReceipt = POSPayments.printReceipt;
    window.clearCartAfterPayment = POSPayments.clearCartAfterPayment;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', POSPayments.bindPaymentModal);
    } else {
        POSPayments.bindPaymentModal();
    }
    
    console.log('POS Payments module initialized');
};

/**
 * Payment methods offered at the till: the ones switched on in Settings → Payment
 * methods, in the shop's order (the page sets window.POS_PAYMENT_METHODS). Keys
 * match the server's PaymentMethods. The first five are tiles; the rest are under "More…".
 */
POSPayments.METHODS = (window.POS_PAYMENT_METHODS && window.POS_PAYMENT_METHODS.length ? window.POS_PAYMENT_METHODS : [
    { key: 'cash', label: 'Cash', icon: 'bi-cash-stack' },
    { key: 'airtel_money', label: 'Airtel Money', icon: 'bi-phone' },
    { key: 'mpamba', label: 'TNM Mpamba', icon: 'bi-phone-fill' },
    { key: 'card', label: 'Card', icon: 'bi-credit-card' },
    { key: 'bank_transfer', label: 'Bank transfer', icon: 'bi-bank' },
    { key: 'national_bank', label: 'National Bank', icon: 'bi-bank2' },
    { key: 'standard_bank', label: 'Standard Bank', icon: 'bi-building' },
    { key: 'cheque', label: 'Cheque', icon: 'bi-journal-text' },
    { key: 'advance', label: 'Advance', icon: 'bi-wallet2' }
]).map(function(m, i) { return { key: m.key, label: m.label, icon: m.icon || 'bi-wallet', tile: i < 5 }; });

/** Names come from the shop's settings, so escape them before putting them in HTML */
POSPayments.esc = function(text) {
    return String(text).replace(/[&<>"']/g, function(c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
};

POSPayments.methodLabel = function(key) {
    const method = POSPayments.METHODS.find(function(m) { return m.key === key; });
    return method ? method.label : key;
};

/** Total of the cart, from the cart itself */
POSPayments.cartTotal = function() {
    const items = (window.POSCart && window.POSCart.state.items) || [];
    return items.reduce(function(sum, item) {
        return sum + (parseFloat(item.price) || parseFloat(item.selling_price) || 0) * (parseFloat(item.quantity) || 1);
    }, 0);
};

POSPayments.round2 = function(value) {
    return Math.round((parseFloat(value) || 0) * 100) / 100;
};

/**
 * Open the payment window: show the amount due and the items, start with cash.
 */
POSPayments.setupPaymentModal = function() {
    if (!window.POSCart) {
        console.error('Cart module not loaded');
        return;
    }

    const total = POSPayments.round2(POSPayments.cartTotal());
    const items = window.POSCart.state.items || [];

    // A leftover "added to cart" message must not sit over the payment window
    document.querySelectorAll('.pos-toast').forEach(function(t) { t.remove(); });

    // Keep the cart footer in step (other code reads it)
    const totalPayableElement = document.getElementById('totalPayable');
    if (totalPayableElement) totalPayableElement.textContent = POSApp.utils.formatCurrency(total);

    POSPayments.state.total = total;
    POSPayments.state.mode = 'single';
    POSPayments.state.method = 'cash';
    POSPayments.state.splitLines = [];
    POSPayments.state.amountGiven = 0;
    POSPayments.state.changeAmount = 0;
    POSPayments.setSubmitting(false);

    document.getElementById('payDue').textContent = POSApp.utils.formatCurrency(total);
    const units = items.reduce(function(sum, item) { return sum + (parseFloat(item.quantity) || 1); }, 0);
    document.getElementById('payItemCount').textContent = units + (units === 1 ? ' item' : ' items');

    const esc = function(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function(c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
    document.getElementById('payItems').innerHTML = items.map(function(item) {
        const name = item.name || (item.product && item.product.name) || 'Item';
        const qty = parseFloat(item.quantity) || 1;
        const price = parseFloat(item.price) || parseFloat(item.selling_price) || 0;
        return '<div class="pay-item"><span class="pay-item-name">' + esc(qty) + ' × ' + esc(name) + '</span>' +
            '<span class="pay-item-total">' + POSApp.utils.formatCurrency(qty * price) + '</span></div>';
    }).join('');

    const cashInput = document.getElementById('amountGiven');
    if (cashInput) cashInput.value = '';

    POSPayments.renderMethods();
    POSPayments.renderQuickCash();
    POSPayments.showMode('single');
    // Also after the opening animation, in case the shown event came early
    setTimeout(POSPayments.focusCash, 400);
};

/** The method tiles for a single payment (five tiles + "More…" for the rest, so it fits on two rows) */
POSPayments.renderMethods = function() {
    const box = document.getElementById('payMethods');
    if (!box) return;
    const current = POSPayments.state.method;
    const tiles = POSPayments.METHODS.filter(function(m) { return m.tile; }).map(function(m) {
        const active = m.key === current;
        return '<button type="button" role="radio" aria-checked="' + active + '" class="pay-method' + (active ? ' active' : '') + '" data-method="' + m.key + '">' +
            '<i class="bi ' + POSPayments.esc(m.icon) + '"></i><span>' + POSPayments.esc(m.label) + '</span></button>';
    }).join('');
    const more = POSPayments.METHODS.filter(function(m) { return !m.tile; });
    const moreActive = more.some(function(m) { return m.key === current; });
    // "More…" only when the shop has more than five methods switched on
    const select = !more.length ? '' : '<select class="pay-method pay-more-select' + (moreActive ? ' active' : '') + '" aria-label="Other payment methods">' +
        '<option value=""' + (moreActive ? '' : ' selected') + ' disabled>More…</option>' +
        more.map(function(m) { return '<option value="' + m.key + '"' + (m.key === current ? ' selected' : '') + '>' + POSPayments.esc(m.label) + '</option>'; }).join('') +
        '</select>';
    box.innerHTML = tiles + select;
};

/**
 * Quick cash buttons: what a customer can actually hand over with Malawian notes
 * (500, 1,000, 2,000, 5,000): the bill rounded up to the next 1,000, 2,000 and
 * 5,000, plus one bigger round amount. E.g. 3,000 -> Exact, 4,000, 5,000, 10,000.
 */
POSPayments.quickCashAmounts = function(total) {
    const up = function(step) { return Math.ceil(total / step) * step; };
    const fives = up(5000);
    const candidates = [up(1000), up(2000), fives, fives + 5000, up(10000) + 10000];
    // Nobody pays a 2,000 bill with 20,000: keep suggestions within about five times the bill
    const limit = Math.max(total * 5, 10000);
    const above = Array.from(new Set(candidates.filter(function(v) { return v > total && v <= limit; }))).sort(function(a, b) { return a - b; });
    return [total].concat(above.slice(0, 3));
};

POSPayments.renderQuickCash = function() {
    const box = document.getElementById('payQuickCash');
    if (!box) return;
    box.innerHTML = POSPayments.quickCashAmounts(POSPayments.round2(POSPayments.state.total)).map(function(value, i) {
        return '<button type="button" class="pay-quick-btn" data-amount="' + value + '">' +
            (i === 0 ? 'Exact' : Number(value).toLocaleString('en-US')) + '</button>';
    }).join('');
};

POSPayments.showMode = function(mode) {
    POSPayments.state.mode = mode;
    document.getElementById('paySingle').classList.toggle('d-none', mode !== 'single');
    document.getElementById('paySplit').classList.toggle('d-none', mode !== 'split');
    document.getElementById('paySplitToggle').classList.toggle('d-none', mode === 'split');

    if (mode === 'split' && POSPayments.state.splitLines.length === 0) {
        // Cash first with the full amount: the cashier lowers it to what was paid in cash,
        // the next line fills itself with the rest ("auto"), and they choose its method
        POSPayments.state.splitLines = [
            { method: 'cash', amount: POSPayments.state.total, auto: false },
            { method: '', amount: 0, auto: true }
        ];
    }
    if (mode === 'split') POSPayments.renderSplitLines();
    POSPayments.updatePaymentSummary();
};

/** Split payment lines: method + amount each */
POSPayments.renderSplitLines = function() {
    const list = document.getElementById('paymentMethodsList');
    if (!list) return;
    const options = function(selected) {
        return '<option value="" disabled' + (selected ? '' : ' selected') + '>Choose method…</option>' +
            POSPayments.METHODS.map(function(m) {
                return '<option value="' + m.key + '"' + (m.key === selected ? ' selected' : '') + '>' + POSPayments.esc(m.label) + '</option>';
            }).join('');
    };
    list.innerHTML = POSPayments.state.splitLines.map(function(line, i) {
        return '<div class="pay-split-line payment-method-item" data-line="' + i + '">' +
            '<select class="form-select payment-method-select' + (line.method ? '' : ' needs-choice') + '" data-line="' + i + '" aria-label="Payment method ' + (i + 1) + '">' + options(line.method) + '</select>' +
            '<div class="input-group"><span class="input-group-text">MWK</span>' +
            '<input type="number" class="form-control payment-amount' + (line.auto ? ' is-auto' : '') + '" data-line="' + i + '" min="0" step="1" inputmode="decimal" placeholder="0" value="' + (line.amount === '' ? '' : line.amount) + '" aria-label="Amount ' + (i + 1) + '"' + (line.auto ? ' title="Filled with the remaining balance"' : '') + '></div>' +
            (POSPayments.state.splitLines.length > 2 ? '<button type="button" class="btn btn-outline-danger pay-remove" data-line="' + i + '" aria-label="Remove"><i class="bi bi-x-lg"></i></button>' : '') +
            '</div>';
    }).join('');
};

/**
 * Split payment: the "auto" line always holds what is left after the other lines.
 * Its input is updated in place, so the cashier keeps typing where they are.
 */
POSPayments.fillAutoLine = function() {
    const lines = POSPayments.state.splitLines;
    const autoIndex = lines.findIndex(function(l) { return l.auto; });
    if (autoIndex === -1) return;
    const others = lines.reduce(function(sum, l, i) { return i === autoIndex ? sum : sum + (parseFloat(l.amount) || 0); }, 0);
    lines[autoIndex].amount = POSPayments.round2(Math.max(0, POSPayments.state.total - others));
    const input = document.querySelector('.payment-amount[data-line="' + autoIndex + '"]');
    if (input && document.activeElement !== input) input.value = lines[autoIndex].amount;
};

/**
 * What the customer is paying, from the current choices:
 *   payments    what is sent to the server (cash counted at the sale amount, never the change)
 *   paid        total offered by the customer
 *   remaining   still to pay (0 when covered)
 *   change      cash to hand back
 *   problem     why the sale cannot be completed yet, or ''
 */
POSPayments.computeSettlement = function() {
    const total = POSPayments.state.total || 0;
    const result = { total: total, payments: [], paid: 0, remaining: 0, change: 0, amountGiven: 0, problem: '' };

    if (total <= 0) {
        result.problem = 'The cart is empty.';
        return result;
    }

    if (POSPayments.state.mode === 'single') {
        const method = POSPayments.state.method;
        if (method === 'cash') {
            const input = document.getElementById('amountGiven');
            const typed = input && input.value !== '' ? parseFloat(input.value) : null;
            const given = typed === null ? total : POSPayments.round2(typed);   // empty = exact cash
            result.amountGiven = given;
            result.paid = given;
            result.change = POSPayments.round2(Math.max(0, given - total));
            result.remaining = POSPayments.round2(Math.max(0, total - given));
        } else {
            result.amountGiven = total;
            result.paid = total;
        }
        result.payments = [{ method: method, amount: total }];
    } else {
        let cash = 0;
        let unchosen = 0;
        POSPayments.state.splitLines.forEach(function(line) {
            const amount = POSPayments.round2(line.amount);
            if (amount > 0 && !line.method) {
                unchosen += amount;
                return;
            }
            if (amount > 0) {
                result.payments.push({ method: line.method, amount: amount });
                result.paid += amount;
                if (line.method === 'cash') cash += amount;
            }
        });
        result.paid = POSPayments.round2(result.paid);
        const over = POSPayments.round2(result.paid - total);
        result.remaining = Math.max(0, -over);
        // Only cash can be handed back as change; anything else must match exactly
        result.change = over > 0 ? Math.min(over, cash) : 0;
        result.amountGiven = result.paid;
        result.paid = POSPayments.round2(result.paid + unchosen);
        result.remaining = Math.max(0, POSPayments.round2(total - result.paid));
        result.amountGiven = result.paid;
        if (unchosen > 0) {
            result.problem = 'Choose the payment method for ' + POSApp.utils.formatCurrency(unchosen);
        } else if (over > 0 && over > cash) {
            result.problem = 'Only cash can be more than the amount due. Reduce the other amounts.';
        } else if (result.payments.length < 2) {
            result.problem = 'Lower the cash amount, then choose the method for the rest.';
        }
    }

    if (!result.problem && result.remaining > 0) {
        result.problem = 'Still to pay: ' + POSApp.utils.formatCurrency(result.remaining);
    }
    return result;
};

/** Refresh change / still-to-pay and the Complete button */
POSPayments.updatePaymentSummary = function() {
    if (!document.getElementById('payResult')) return false;
    const s = POSPayments.computeSettlement();
    const method = POSPayments.state.method;

    POSPayments.state.amountGiven = s.amountGiven;
    POSPayments.state.changeAmount = s.change;

    const single = POSPayments.state.mode === 'single';
    document.getElementById('payCashSection').classList.toggle('d-none', !(single && method === 'cash'));
    document.getElementById('payOtherNote').classList.toggle('d-none', !(single && method !== 'cash'));
    if (single && method !== 'cash') {
        document.querySelector('#payOtherNote span').textContent =
            'Confirm ' + POSApp.utils.formatCurrency(s.total) + ' was received by ' + POSPayments.methodLabel(method) + ' before completing the sale.';
    }

    const result = document.getElementById('payResult');
    const label = document.getElementById('payResultLabel');
    const value = document.getElementById('payResultValue');
    result.classList.remove('is-change', 'is-due', 'is-even');
    if (s.remaining > 0) {
        result.classList.add('is-due');
        label.textContent = 'Still to pay';
        value.textContent = POSApp.utils.formatCurrency(s.remaining);
    } else if (s.change > 0) {
        result.classList.add('is-change');
        label.textContent = 'Change to give';
        value.textContent = POSApp.utils.formatCurrency(s.change);
    } else {
        result.classList.add('is-even');
        label.textContent = single && method === 'cash' ? 'Change' : 'Paid in full';
        value.textContent = single && method === 'cash' ? POSApp.utils.formatCurrency(0) : POSPayments.methodLabel(single ? method : 'split');
        if (!single) value.textContent = s.payments.length + ' methods';
    }
    document.getElementById('payPaidLine').innerHTML = s.paid > 0
        ? '<span class="text-muted">Received</span><br><strong>' + POSApp.utils.formatCurrency(s.paid) + '</strong>' : '';

    // Highlight the quick cash amount that matches what was typed
    const input = document.getElementById('amountGiven');
    document.querySelectorAll('.pay-quick-btn').forEach(function(btn) {
        btn.classList.toggle('active', input && input.value !== '' && parseFloat(input.value) === parseFloat(btn.dataset.amount));
    });

    const btn = document.getElementById('payCompleteBtn');
    const canComplete = !s.problem && !POSPayments.state.submitting;
    btn.disabled = !canComplete;
    document.getElementById('payCompleteText').textContent = POSPayments.state.submitting
        ? 'Saving…'
        : (s.problem && s.remaining <= 0 ? s.problem : 'Complete sale · ' + POSApp.utils.formatCurrency(s.total));
    btn.title = s.problem || '';
    return true;
};

POSPayments.setSubmitting = function(on) {
    POSPayments.state.submitting = !!on;
    const btn = document.getElementById('payCompleteBtn');
    if (btn) {
        btn.classList.toggle('is-saving', !!on);
        if (on) btn.disabled = true;
    }
    if (!on && document.getElementById('payResult')) POSPayments.updatePaymentSummary();
};

/** Wire up the payment window once */
POSPayments.bindPaymentModal = function() {
    const modal = document.getElementById('paymentModal');
    if (!modal || modal.dataset.bound) return;
    modal.dataset.bound = '1';

    modal.addEventListener('click', function(e) {
        // Only the tile buttons; the "More…" dropdown is handled on change (clicking it must not redraw it)
        const tile = e.target.closest('button.pay-method');
        if (tile) {
            POSPayments.state.method = tile.dataset.method;
            POSPayments.renderMethods();
            POSPayments.updatePaymentSummary();
            if (tile.dataset.method === 'cash') document.getElementById('amountGiven').focus();
            return;
        }
        const quick = e.target.closest('.pay-quick-btn');
        if (quick) {
            const input = document.getElementById('amountGiven');
            input.value = quick.dataset.amount;
            POSPayments.updatePaymentSummary();
            return;
        }
        if (e.target.closest('#paySplitToggle')) { POSPayments.showMode('split'); return; }
        if (e.target.closest('#paySingleToggle')) { POSPayments.state.splitLines = []; POSPayments.showMode('single'); return; }
        if (e.target.closest('#payAddLine')) {
            // Amounts typed so far stay; the new line takes what is left
            POSPayments.state.splitLines.forEach(function(l) { l.auto = false; });
            POSPayments.state.splitLines.push({ method: '', amount: '', auto: true });
            POSPayments.fillAutoLine();
            POSPayments.renderSplitLines();
            POSPayments.updatePaymentSummary();
            return;
        }
        const remove = e.target.closest('.pay-remove');
        if (remove) {
            POSPayments.state.splitLines.splice(+remove.dataset.line, 1);
            // The last line takes up the slack again
            POSPayments.state.splitLines.forEach(function(l, i, all) { l.auto = i === all.length - 1; });
            POSPayments.fillAutoLine();
            POSPayments.renderSplitLines();
            POSPayments.updatePaymentSummary();
        }
    });

    modal.addEventListener('input', function(e) {
        if (e.target.id === 'amountGiven') {
            POSPayments.updatePaymentSummary();
        } else if (e.target.classList.contains('payment-amount')) {
            const line = POSPayments.state.splitLines[+e.target.dataset.line];
            line.amount = e.target.value;
            if (line.auto) {
                // The cashier typed in the automatic line: it is theirs now
                line.auto = false;
                e.target.classList.remove('is-auto');
            }
            POSPayments.fillAutoLine();
            POSPayments.updatePaymentSummary();
        }
    });
    modal.addEventListener('change', function(e) {
        if (e.target.classList.contains('pay-more-select')) {
            POSPayments.state.method = e.target.value;
            POSPayments.renderMethods();
            POSPayments.updatePaymentSummary();
            return;
        }
        if (e.target.classList.contains('payment-method-select')) {
            POSPayments.state.splitLines[+e.target.dataset.line].method = e.target.value;
            e.target.classList.remove('needs-choice');
            POSPayments.updatePaymentSummary();
        }
    });

    // Enter completes the sale while the payment window is open (when it can be completed)
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Enter' || !modal.classList.contains('show') || e.target.matches('select, button, a')) return;
        e.preventDefault();
        const btn = document.getElementById('payCompleteBtn');
        if (!btn.disabled) POSPayments.finalizePayment();
    });

    modal.addEventListener('shown.bs.modal', POSPayments.focusCash);
};

/** Put the cursor in "Cash received" so the cashier can type straight away */
POSPayments.focusCash = function() {
    const input = document.getElementById('amountGiven');
    if (input && !input.closest('.d-none')) input.focus();
};

/** Kept for code that still calls them */
POSPayments.addPaymentMethod = function() { POSPayments.showMode('split'); };
POSPayments.removePaymentMethod = function(index) {
    POSPayments.state.splitLines.splice(index, 1);
    POSPayments.state.splitLines.forEach(function(l, i, all) { l.auto = i === all.length - 1; });
    POSPayments.fillAutoLine();
    POSPayments.renderSplitLines();
    POSPayments.updatePaymentSummary();
};

/**
 * Finalize payment and process sale
 */
POSPayments.finalizePayment = function() {
    console.log('Finalizing payment...');

    try {
        if (POSPayments.state.submitting) {
            return false;   // already saving: ignore double clicks / Enter
        }

        const settlement = POSPayments.computeSettlement();
        if (settlement.problem) {
            POSPayments.updatePaymentSummary();
            return false;
        }

        // A cart restored from earlier, or stock that dropped since items were added (e.g. sales
        // saved offline on this till that the server does not know about yet)
        if (window.POSOffline && POSOffline.stockShortages) {
            const shortages = POSOffline.stockShortages(window.POSCart ? window.POSCart.state.items : []);
            if (shortages.length) {
                alert('Not enough stock for this sale:\n\n' + shortages.join('\n') + '\n\nReduce the quantity in the cart and try again.');
                return false;
            }
        }

        const totalPayable = settlement.total;
        const notesElement = document.getElementById('paymentNotes');
        const notes = notesElement ? notesElement.value : '';

        const paymentData = {
            // Unique per sale, even across tills; the server uses it to ignore a repeated upload
            invoice_no: 'INV-' + Date.now() + String(Math.floor(Math.random() * 1000)).padStart(3, '0'),
            customer_name: 'Walk-In Customer',
            contact_number: '',
            payment_status: 'paid',
            notes: notes,
            total_amount: totalPayable,
            payments: settlement.payments
        };
        POSPayments.state.amountGiven = settlement.amountGiven;
        POSPayments.state.changeAmount = settlement.change;
        POSPayments.setSubmitting(true);

        const isSplitPayment = paymentData.payments.length > 1;
        
        const requestData = {
            invoice_no: paymentData.invoice_no,
            customer_name: paymentData.customer_name,
            contact_number: paymentData.contact_number,
            payment_status: paymentData.payment_status,
            notes: paymentData.notes,
            items: window.POSCart ? window.POSCart.state.items : []
        };
        
        if (isSplitPayment) {
            requestData.is_split_payment = true;
            requestData.payments = paymentData.payments;
        } else {
            requestData.payment_method = paymentData.payments[0].method;
        }
        
        // No connection at all: save the sale on this device straight away
        if (window.POSOffline && POSOffline.isOffline()) {
            POSPayments.completeOffline(paymentData, requestData);
            POSPayments.setSubmitting(false);
            return;
        }

        const csrfToken = POSApp.utils.getCsrfToken();
        if (!csrfToken) {
            POSPayments.setSubmitting(false);
            alert('Error: Security token not found');
            return;
        }

        let saleSaved = false;
        fetch(POSApp.config.apiEndpoints.finalize, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestData)
        })
        .then(function(response) {
            if (!response.ok) {
                // Server unreachable (gateway/timeout): treat like being offline
                if (window.POSOffline && POSOffline.isUnreachableStatus(response.status)) {
                    const unreachable = new Error('Server unreachable (' + response.status + ')');
                    unreachable.unreachable = true;
                    throw unreachable;
                }
                // Keep the server's reason (e.g. "Insufficient stock for product: ...") for the cashier
                return response.json().catch(function() { return {}; }).then(function(errorData) {
                    const refused = new Error(errorData && errorData.error ? errorData.error : 'The server could not save the sale (' + response.status + ').');
                    refused.refused = true;
                    throw refused;
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                saleSaved = true;
                console.log('Sale saved successfully:', data);
                POSPayments.completeSale(paymentData);

                // Refresh products in background
                if (window.POSProducts) {
                    window.POSProducts.refreshProductData().catch(function(e) {});
                }

            } else {
                console.error('Error saving sale:', data.error);
                alert('Sale not completed: ' + data.error);
            }
        })
        .catch(function(error) {
            // fetch() itself failing (TypeError) means the request never got an answer.
            // The sale is saved on this device; if the server did receive it, the upload
            // later is recognised by its invoice number and not recorded twice.
            const noAnswer = error instanceof TypeError || error.unreachable;
            if (!saleSaved && noAnswer && window.POSOffline) {
                console.warn('Server unreachable, saving sale offline:', error);
                POSPayments.completeOffline(paymentData, requestData);
                return;
            }
            console.error('Sale not completed:', error);
            alert(error.refused
                ? 'Sale not completed: ' + error.message
                : 'Sale not completed: ' + error.message + '. Please check your connection and try again.');
        })
        .finally(function() {
            POSPayments.setSubmitting(false);
        });
        
    } catch (e) {
        POSPayments.setSubmitting(false);
        console.error('Error finalizing payment:', e);
        alert('Error processing payment. Please try again.');
    }
};

/**
 * After a sale is recorded (on the server, or saved offline): print the receipt,
 * open the drawer and clear the cart.
 */
POSPayments.completeSale = function(paymentData) {
    // Generate receipt and PRINT IMMEDIATELY (before any cleanup)
    POSPayments.generateReceipt(paymentData);
    POSPayments.showReceiptAndPrint();

    // Now do cleanup after print dialog is open
    POSApp.modal.hide('paymentModal');

    // Open cash drawer if there is change
    if (POSPayments.state.changeAmount > 0) {
        POSPayments.openCashDrawer();
    }

    // Clear cart locally
    if (window.POSCart) {
        window.POSCart.state.items = [];
        window.POSCart.renderCart();
        localStorage.removeItem('pos_local_cart');
        window.POSCart.clearCartAfterPayment();
    }
};

/**
 * Record a sale on this device while the server cannot be reached
 * (uploaded later by public/js/pos/offline.js).
 */
POSPayments.completeOffline = function(paymentData, requestData) {
    // Only sell what this device knows is in stock (server stock less sales already saved here)
    const shortages = POSOffline.stockShortages(requestData.items);
    if (shortages.length) {
        alert('Sale not completed: not enough stock.\n\n' + shortages.join('\n') + '\n\nReduce the quantity in the cart and try again.');
        return;
    }

    if (!POSOffline.queueSale(requestData, paymentData.total_amount)) {
        alert('This device could not save the sale (storage full or blocked). The sale was NOT recorded.');
        return;
    }

    // Lower the stock shown on this till by the sale just saved
    if (window.POSProducts && Array.isArray(POSProducts.state.productsData)) {
        POSOffline.applyStock(POSProducts.state.productsData);
        POSOffline.renderStock();
    }

    POSPayments.completeSale(paymentData);
    POSApp.utils.showNotification('📴 No connection: sale ' + paymentData.invoice_no + ' saved on this device. It will upload automatically.', 'warning');
};

/**
 * Generate receipt content
 */
POSPayments.generateReceipt = function(paymentData) {
    console.log('Generating receipt for:', paymentData);
    
    if (!window.POSCart) {
        console.error('Cart module not available for receipt generation');
        return;
    }
    
    // Calculate totals
    let totalAmount = 0;
    for (let i = 0; i < window.POSCart.state.items.length; i++) {
        const item = window.POSCart.state.items[i];
        const itemPrice = parseFloat(item.price) || 0;
        const itemQty = item.quantity || 1;
        totalAmount += itemPrice * itemQty;
    }
    
    const dateStr = POSApp.utils.formatDate();
    
    let receiptContent = '';
    receiptContent += '<div style="width: 100%; font-family: monospace; font-size: 14px; margin: 0; padding: 0; line-height: 1.4; color: #000;">';
    
    // Business details come from System Settings (see config/business.php); empty ones are left off
    const settings = window.systemSettings || {};
    const esc = (value) => String(value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    // Header
    receiptContent += '<div style="text-align: center; margin-bottom: 15px; pt-1">';
    // Uploaded logo, already shrunk and black-and-white on the server (a few KB, no download at print time)
    if (settings.receipt_logo && /^data:image\//.test(settings.receipt_logo)) {
        receiptContent += '<img src="' + settings.receipt_logo + '" alt="" style="display: block; margin: 0 auto 6px; max-width: 120px; max-height: 45px;">';
    }
    receiptContent += '<h1 style="margin: 0; font-size: 22px; font-weight: bold; text-transform: uppercase;">' + esc(settings.system_name || '') + '</h1>';
    if (settings.location) {
        receiptContent += '<div style="font-size: 14px; margin: 4px 0;">' + esc(settings.location) + '</div>';
    }
    if (settings.telephone) {
        receiptContent += '<div style="font-size: 14px; margin: 4px 0;">Tel: ' + esc(settings.telephone) + '</div>';
    }
    if (settings.tax_number) {
        receiptContent += '<div style="font-size: 12px; margin: 4px 0;">TPIN: ' + esc(settings.tax_number) + '</div>';
    }
    receiptContent += '</div>';
    
    // Top Thick Border
    receiptContent += '<div style="border-top: 2px solid #000; margin-bottom: 10px;"></div>';
    
    // Date and Invoice
    receiptContent += '<div style="margin-bottom: 10px; font-size: 10px;">';
    receiptContent += '<div style="margin: 2px 0;"><strong>Customer:</strong> ' + (paymentData.customer_name || 'Walk-In Customer') + '</div>';
    receiptContent += '<div style="margin: 2px 0;"><strong>Date:</strong> ' + dateStr + '</div>';
    receiptContent += '<div style="margin: 2px 0;"><strong>Invoice:</strong> ' + (paymentData.invoice_no || 'N/A') + '</div>';
    receiptContent += '</div>';
    
    // Items Header
    receiptContent += '<table style="width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 5px;">';
    receiptContent += '<thead>';
    receiptContent += '<tr style="border-top: 1px dashed #000; border-bottom: 1px dashed #000;">';
    receiptContent += '<th style="text-align: left; padding: 8px 0; text-transform: uppercase;">ITEM</th>';
    receiptContent += '<th style="text-align: center; padding: 8px 0; text-transform: uppercase;">QTY</th>';
    receiptContent += '<th style="text-align: right; padding: 8px 0; text-transform: uppercase;">PRICE</th>';
    receiptContent += '<th style="text-align: right; padding: 8px 0; text-transform: uppercase;">TOTAL</th>';
    receiptContent += '</tr>';
    receiptContent += '</thead>';
    receiptContent += '<tbody>';
    
    // Items
    for (let i = 0; i < window.POSCart.state.items.length; i++) {
        const item = window.POSCart.state.items[i];
        const itemName = item.name || 'Unknown Product';
        const itemPrice = parseFloat(item.price) || parseFloat(item.selling_price) || 0;
        const itemQty = item.quantity || 1;
        const itemTotal = itemPrice * itemQty;
        
        receiptContent += '<tr>';
        receiptContent += '<td style="text-align: left; padding: 3px 0; color: #333;">' + itemName + '</td>';
        receiptContent += '<td style="text-align: center; padding: 3px 0;">' + itemQty + '</td>';
        receiptContent += '<td style="text-align: right; padding: 3px 0;">' + itemPrice.toFixed(2) + '</td>';
        receiptContent += '<td style="text-align: right; padding: 3px 0;">' + itemTotal.toFixed(2) + '</td>';
        receiptContent += '</tr>';
    }
    
    receiptContent += '</tbody></table>';
    
    // Summary
    receiptContent += '<div style="border-top: 1px dashed #000; margin-top: 5px; margin-bottom: 10px;"></div>';
    
    receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 5px;">';
    receiptContent += '<span>SUBTOTAL:</span>';
    receiptContent += '<span>MWK ' + totalAmount.toFixed(2) + '</span>';
    receiptContent += '</div>';
    
    // Payment details
    for (let j = 0; j < paymentData.payments.length; j++) {
        const payment = paymentData.payments[j];
        const paymentMethod = payment.method.toUpperCase().replace('_', ' ');
        
        receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 10px; border-bottom: 1px solid #000; padding-bottom: 3px; margin-bottom: 4px;">';
        receiptContent += '<span>' + paymentMethod + ':</span>';
        receiptContent += '<span>MWK ' + payment.amount.toFixed(2) + '</span>';
        receiptContent += '</div>';
    }
    
    // Determine amount given and change robustly from DOM and paymentData
    const totalPaid = paymentData.payments.reduce((acc, p) => acc + p.amount, 0);
    const amountGivenElement = document.getElementById('amountGiven');
    const domAmountGiven = amountGivenElement ? parseFloat(amountGivenElement.value) || 0 : 0;
    // Prefer explicit DOM amountGiven when present, otherwise use state or payment totals
    const amountGiven = domAmountGiven > 0 ? domAmountGiven : (POSPayments.state.amountGiven || totalPaid);
    const changeAmount = Math.max(0, amountGiven - totalAmount);

    // Persist to state so other flows (drawer open, printing) use same values
    POSPayments.state.amountGiven = amountGiven;
    POSPayments.state.changeAmount = changeAmount;

    receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; margin-top: 5px;">';
    receiptContent += '<span>TOTAL PAYABLE:</span>';
    receiptContent += '<span>MWK ' + totalAmount.toFixed(2) + '</span>';
    receiptContent += '</div>';

    receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 4px;">';
    receiptContent += '<span>AMOUNT GIVEN:</span>';
    receiptContent += '<span>MWK ' + amountGiven.toFixed(2) + '</span>';
    receiptContent += '</div>';
    
    receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; margin-top: 4px; border-top: 1px solid #000; padding-top: 2px;">';
    receiptContent += '<span>CHANGE:</span>';
    receiptContent += '<span>MWK ' + changeAmount.toFixed(2) + '</span>';
    receiptContent += '</div>';
    
    // Footer
    receiptContent += '<div style="border-top: 1px dashed #000; margin: 10px 0;"></div>';
    receiptContent += '<div style="text-align: center; margin-top: 5px;">';
    if (settings.receipt_footer) {
        receiptContent += '<div style="font-size: 12px; font-style: italic; margin-bottom: 3px;">' + esc(settings.receipt_footer) + '</div>';
    }
    if (settings.powered_by) {
        receiptContent += '<div style="font-size: 10px;">Powered by ' + esc(settings.powered_by) + '</div>';
    }
    receiptContent += '</div>';
    
    receiptContent += '<div style="border-bottom: 2px solid #000; margin-top: 10px;"></div>';
    
    receiptContent += '</div>';
    
    POSPayments.state.receiptContent = receiptContent;
    console.log('Receipt generated successfully');
};

/**
 * Open browser print dialog directly (no visible window)
 */
POSPayments.showReceiptAndPrint = function() {
    if (!POSPayments.state.receiptContent) return;

    // Remove any previous print frame
    var oldFrame = document.getElementById('receiptPrintFrame');
    if (oldFrame) oldFrame.remove();

    // Create hidden iframe
    var iframe = document.createElement('iframe');
    iframe.id = 'receiptPrintFrame';
    iframe.style.position = 'fixed';
    iframe.style.right = '0';
    iframe.style.bottom = '0';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><title>Receipt</title>');
    doc.write('<style>');
    doc.write('body { font-family: monospace; margin: 0; padding: 15px 25px 15px 10px; font-size: 10px; line-height: 1.4; color: #000; width: 260px; }');
    doc.write('img { filter: grayscale(1) contrast(1.2); }');
    doc.write('@media print { body { margin: 0; padding: 15px 20px 15px 10px; width: 90%; } img { filter: grayscale(1) contrast(1.2); } }');
    doc.write('</style></head><body>');
    doc.write(POSPayments.state.receiptContent);
    doc.write('</body></html>');
    doc.close();

    // Print as soon as the logo has been drawn (it is embedded, so this is instant; never wait more than 1s)
    var printed = false;
    var printNow = function() {
        if (printed) return;
        printed = true;
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };
    var pending = Array.prototype.filter.call(doc.images, function(img) { return !img.complete; });
    if (!pending.length) {
        printNow();
    } else {
        pending.forEach(function(img) {
            img.onload = img.onerror = function() {
                if (Array.prototype.every.call(doc.images, function(i) { return i.complete; })) printNow();
            };
        });
        setTimeout(printNow, 1000);
    }
};

/**
 * Print receipt (used by manual Print button)
 */
POSPayments.printReceipt = function() {
    POSPayments.showReceiptAndPrint();
};

/**
 * Open cash drawer
 * This function attempts to open a USB cash drawer.
 * Many USB drawers act as a Virtual COM port. Sending any character (e.g. ASCII 7) triggers it.
 */
POSPayments.openCashDrawer = async function() {
    console.log('=== OPENING CASH DRAWER ===');
    
    // Method 1: Web Serial API (Modern browsers)
    if ('serial' in navigator) {
        try {
            // First, check if we already have permission for a port
            const ports = await navigator.serial.getPorts();
            let port = ports[0];
            
            if (!port) {
                console.log('No authorized serial ports found. Requesting permission...');
                // Note: requestPort must be triggered by a user gesture. 
                // Since this is called after a button click (finalizePayment), it might work.
                // But some browsers might be strict.
                try {
                    port = await navigator.serial.requestPort();
                } catch (e) {
                    console.warn('User cancelled port selection or browser blocked it:', e);
                    return;
                }
            }
            
            if (port) {
                await port.open({ baudRate: 9600 });
                const writer = port.writable.getWriter();
                
                // Common pulse commands for cash drawers:
                // ASCII 7 (Bell), or ESC p 0 25 250 (\x1b\x70\x00\x19\xfa) for Epson/Star
                const data = new Uint8Array([7, 27, 112, 0, 25, 250]); 
                await writer.write(data);
                
                writer.releaseLock();
                await port.close();
                console.log('Cash drawer trigger command sent successfully');
            }
        } catch (err) {
            console.error('Error with Web Serial drawer trigger:', err);
            // Fallback: If it's a printer-connected drawer, the receipt print job might handle it
        }
    } else {
        console.warn('Web Serial API not supported. Please use a modern browser (Chrome/Edge).');
    }
};

/**
 * Clear cart after payment completion
 */
POSPayments.clearCartAfterPayment = function() {
    console.log('Clearing cart after payment completion...');
    
    if (window.POSCart) {
        // Clear cart state
        window.POSCart.state.items = [];
        window.POSCart.state.justPaid = false;
        
        // Reset payment state
        POSPayments.state.amountGiven = 0;
        POSPayments.state.changeAmount = 0;
        
        // Reset modal fields if they exist
        const amountGivenElement = document.getElementById('amountGiven');
        const changeAmountElement = document.getElementById('changeAmount');
        if (amountGivenElement) amountGivenElement.value = '';
        if (changeAmountElement) changeAmountElement.textContent = '0.00';
        
        // Re-render cart to show empty state
        window.POSCart.renderCart();
        
        console.log('Cart cleared after payment');
    } else {
        console.error('Cart module not available for clearing');
    }
};

// app.js starts before this file has loaded, so wire up the payment window here as well
// (bindPaymentModal only runs once)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', POSPayments.bindPaymentModal);
} else {
    POSPayments.bindPaymentModal();
}
