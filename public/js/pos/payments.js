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
    
    console.log('POS Payments module initialized');
};

/**
 * Setup payment modal with current cart data
 */
POSPayments.setupPaymentModal = function() {
    console.log('Setting up payment modal...');
    console.log('Current cart data:', window.POSCart ? window.POSCart.state.items : 'Cart not loaded');
    
    if (!window.POSCart) {
        console.error('Cart module not loaded');
        return;
    }
    
    // Calculate cart total first
    let cartTotal = 0;
    for (let i = 0; i < window.POSCart.state.items.length; i++) {
        const item = window.POSCart.state.items[i];
        console.log('Processing cart item:', item);
        let itemPrice = parseFloat(item.price) || parseFloat(item.selling_price) || 0;
        let itemQty = item.quantity || 1;
        console.log('Item:', item.name, 'Price:', itemPrice, 'Qty:', itemQty);
        cartTotal += itemPrice * itemQty;
    }
    
    console.log('Cart total calculated:', cartTotal);
    
    // Update total payable
    const totalPayableElement = document.getElementById('totalPayable');
    if (totalPayableElement) {
        totalPayableElement.textContent = POSApp.utils.formatCurrency(cartTotal);
    }
    
    // Update modal total amount
    const modalTotalAmountElement = document.getElementById('modalTotalAmount');
    if (modalTotalAmountElement) {
        modalTotalAmountElement.textContent = cartTotal.toFixed(2);
    }
    
    const paymentMethodsList = document.getElementById('paymentMethodsList');
    if (paymentMethodsList) {
        paymentMethodsList.innerHTML = `
            <div class="payment-method-item border rounded p-3 mb-2" data-payment-index="0">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <select class="form-select payment-method-select" onchange="updatePaymentSummary()">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="airtel_money">Airtel Money</option>
                            <option value="mpamba">TNM Mpamba</option>
                            <option value="national_bank">National Bank</option>
                            <option value="standard_bank">Standard Bank</option>
                            <option value="advance">Advance</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="number" class="form-control payment-amount" placeholder="Amount" min="0" step="0.01" value="${cartTotal.toFixed(2)}" onchange="updatePaymentSummary()">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePaymentMethod(0)" style="display: none;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Reset custom fields and state
    POSPayments.state.amountGiven = 0;
    POSPayments.state.changeAmount = 0;
    
    const amountGivenElement = document.getElementById('amountGiven');
    const changeAmountElement = document.getElementById('changeAmount');
    const paymentNotesElement = document.getElementById('paymentNotes');
    
    if (amountGivenElement) amountGivenElement.value = '';
    if (changeAmountElement) changeAmountElement.textContent = '0.00';
    if (paymentNotesElement) paymentNotesElement.value = '';
    
    POSPayments.updatePaymentSummary();
};

/**
 * Update payment summary calculations
 */
POSPayments.getPaymentTotals = function() {
    const totalPayableElement = document.getElementById('totalPayable');
    const totalPayable = totalPayableElement ? parseFloat(totalPayableElement.textContent.replace('MWK ', '').replace(/,/g, '')) || 0 : 0;
    let totalPaid = 0;

    document.querySelectorAll('.payment-amount').forEach(function(input) {
        totalPaid += parseFloat(input.value) || 0;
    });

    return { totalPayable, totalPaid };
};

POSPayments.hasValidPaymentMethod = function() {
    let validMethod = false;

    document.querySelectorAll('.payment-method-item').forEach(function(item) {
        const methodSelect = item.querySelector('.payment-method-select');
        const amountInput = item.querySelector('.payment-amount');
        const methodValue = methodSelect ? methodSelect.value.trim() : '';
        const amountValue = amountInput ? parseFloat(amountInput.value) || 0 : 0;

        if (methodValue && amountValue > 0) {
            validMethod = true;
        }
    });

    // If no explicit payment method entered but user typed an amount in "Amount Given", treat it as valid cash
    if (!validMethod) {
        const amountGivenElement = document.getElementById('amountGiven');
        const amountGiven = amountGivenElement ? parseFloat(amountGivenElement.value) || 0 : 0;
        if (amountGiven > 0) validMethod = true;
    }

    return validMethod;
};

POSPayments.updatePaymentSummary = function() {
    const totals = POSPayments.getPaymentTotals();
    const totalPayable = totals.totalPayable;
    let totalPaid = totals.totalPaid;
    const totalPaidElement = document.getElementById('totalPaid');
    const remainingBalanceElement = document.getElementById('remainingBalance');
    const amountGivenElement = document.getElementById('amountGiven');
    const changeAmountElement = document.getElementById('changeAmount');
    const balanceAlert = document.getElementById('balanceAlert');
    const totalPaidAlert = document.getElementById('totalPaidAlert');
    const requiredAmountAlert = document.getElementById('requiredAmountAlert');
    const finalizeBtn = document.querySelector('#paymentModal [onclick="finalizePayment()"]');

    if (!document.getElementById('totalPayable')) {
        console.log('Total payable element not found');
        return false;
    }

    const amountGiven = amountGivenElement ? parseFloat(amountGivenElement.value) || 0 : 0;

    // Detect explicit cash method amount
    let cashMethodAmount = 0;
    document.querySelectorAll('.payment-method-item').forEach(function(item) {
        const methodSelect = item.querySelector('.payment-method-select');
        const amountInput = item.querySelector('.payment-amount');
        const method = methodSelect ? (methodSelect.value || '').toLowerCase() : '';
        const amt = amountInput ? parseFloat(amountInput.value) || 0 : 0;
        if (method === 'cash' && amt > 0) cashMethodAmount += amt;
    });

    // When user types an Amount Given, prefer that as the cash tender; otherwise use cashMethodAmount.
    // This ensures typing into Amount Given dynamically affects Remaining Balance even when a cash method is prefilled.
    const sumOtherMethods = totalPaid - cashMethodAmount;
    const cashAmountUsed = amountGiven > 0 ? amountGiven : cashMethodAmount;
    const totalPaidIncludingAmountGiven = sumOtherMethods + cashAmountUsed;
    const remainingBalance = Math.max(0, totalPayable - totalPaidIncludingAmountGiven);
    const changeValue = Math.max(0, amountGiven - totalPayable);

    if (totalPaidElement) {
        totalPaidElement.textContent = POSApp.utils.formatCurrency(totalPaidIncludingAmountGiven);
    }
    if (remainingBalanceElement) {
        remainingBalanceElement.textContent = POSApp.utils.formatCurrency(remainingBalance);
    }

    if (changeAmountElement) {
        changeAmountElement.textContent = changeValue.toFixed(2);
        changeAmountElement.style.color = '#0d6efd';
    }

    // Update state so receipt generation uses the most recent values
    POSPayments.state.amountGiven = amountGiven;
    POSPayments.state.changeAmount = changeValue;

    if (balanceAlert) {
        if (totalPaidIncludingAmountGiven < totalPayable) {
            balanceAlert.style.display = 'block';
            if (totalPaidAlert) {
                totalPaidAlert.textContent = POSApp.utils.formatCurrency(totalPaidIncludingAmountGiven).replace('MWK ', '');
            }
            if (requiredAmountAlert) {
                requiredAmountAlert.textContent = POSApp.utils.formatCurrency(totalPayable).replace('MWK ', '');
            }
        } else {
            balanceAlert.style.display = 'none';
        }
    }

    const hasPaymentMethod = POSPayments.hasValidPaymentMethod();
    const isPaymentSufficient = totalPaidIncludingAmountGiven >= totalPayable;
    const canFinalize = isPaymentSufficient && hasPaymentMethod;

    if (finalizeBtn) {
        finalizeBtn.disabled = !canFinalize;
    }

    console.log(
        'Payment summary updated - Total:', totalPayable.toFixed(2),
        'Paid (methods):', totalPaid.toFixed(2),
        'Amount Given:', amountGiven.toFixed(2),
        'Total Paid (including given):', totalPaidIncludingAmountGiven.toFixed(2),
        'Remaining:', remainingBalance.toFixed(2),
        'Change:', changeValue.toFixed(2),
        'ValidMethod:', hasPaymentMethod,
        'Finalizable:', canFinalize
    );

    return true;
};

/**
 * Add new payment method
 */
POSPayments.addPaymentMethod = function() {
    console.log('Adding payment method...');
    const paymentMethodsList = document.getElementById('paymentMethodsList');
    const totalPayableElement = document.getElementById('totalPayable');
    
    if (!paymentMethodsList || !totalPayableElement) return;
    
    const totalPayable = parseFloat(totalPayableElement.textContent.replace('MWK ', '').replace(',', '')) || 0;
    let totalPaid = 0;
    
    document.querySelectorAll('.payment-amount').forEach(function(input) {
        totalPaid += parseFloat(input.value) || 0;
    });
    
    const remainingBalance = totalPayable - totalPaid;
    
    if (remainingBalance <= 0) {
        alert('Total amount already covered');
        return;
    }
    
    const paymentMethodIndex = Date.now();
    
    const newMethodHtml = `
        <div class="payment-method-item border rounded p-3 mb-2" data-payment-index="${paymentMethodIndex}">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select class="form-select payment-method-select" onchange="updatePaymentSummary()">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="airtel_money">Airtel Money</option>
                        <option value="mpamba">TNM Mpamba</option>
                        <option value="national_bank">National Bank</option>
                        <option value="standard_bank">Standard Bank</option>
                        <option value="advance">Advance</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="number" class="form-control payment-amount" placeholder="Amount" min="0" step="0.01" value="${remainingBalance.toFixed(2)}" onchange="updatePaymentSummary()">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePaymentMethod(${paymentMethodIndex})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    paymentMethodsList.insertAdjacentHTML('beforeend', newMethodHtml);
    POSPayments.updatePaymentSummary();
};

/**
 * Remove payment method
 */
POSPayments.removePaymentMethod = function(index) {
    const methodElement = document.querySelector('[data-payment-index="' + index + '"]');
    if (methodElement) {
        methodElement.remove();
        POSPayments.updatePaymentSummary();
    }
};

/**
 * Finalize payment and process sale
 */
POSPayments.finalizePayment = function() {
    console.log('Finalizing payment...');
    
    try {
        if (!POSPayments.updatePaymentSummary()) {
            return false;
        }

        const notesElement = document.getElementById('paymentNotes');
        const notes = notesElement ? notesElement.value : '';
        const totalPayableElement = document.getElementById('totalPayable');
        const balanceAlert = document.getElementById('balanceAlert');
        const totalPaidAlert = document.getElementById('totalPaidAlert');
        const requiredAmountAlert = document.getElementById('requiredAmountAlert');

        if (!totalPayableElement) {
            alert('Error: Total amount not found');
            return false;
        }

        const totalPayable = parseFloat(totalPayableElement.textContent.replace('MWK ', '').replace(/,/g, '')) || 0;
        let totalPaid = 0;
        document.querySelectorAll('.payment-amount').forEach(function(input) {
            totalPaid += parseFloat(input.value) || 0;
        });

        // Detect explicit cash method amount to avoid double-count
        let cashMethodAmount = 0;
        document.querySelectorAll('.payment-method-item').forEach(function(item) {
            const methodSelect = item.querySelector('.payment-method-select');
            const amountInput = item.querySelector('.payment-amount');
            const method = methodSelect ? (methodSelect.value || '').toLowerCase() : '';
            const amt = amountInput ? parseFloat(amountInput.value) || 0 : 0;
            if (method === 'cash' && amt > 0) cashMethodAmount += amt;
        });

        const amountGivenElement = document.getElementById('amountGiven');
        const amountGiven = amountGivenElement ? parseFloat(amountGivenElement.value) || 0 : 0;

        const sumOtherMethods = totalPaid - cashMethodAmount;
        const cashAmountUsed = amountGiven > 0 ? amountGiven : cashMethodAmount;
        const totalPaidIncludingAmountGiven = sumOtherMethods + cashAmountUsed;
        const paymentShortage = totalPayable - totalPaidIncludingAmountGiven;

        const hasPaymentMethod = POSPayments.hasValidPaymentMethod();

        if (!hasPaymentMethod) {
            alert('Please select at least one payment method and enter an amount before completing payment.');
            return false;
        }

        if (paymentShortage > 0) {
            if (balanceAlert) {
                balanceAlert.style.display = 'block';
            }
            if (totalPaidAlert) {
                totalPaidAlert.textContent = POSApp.utils.formatCurrency(totalPaidIncludingAmountGiven).replace('MWK ', '');
            }
            if (requiredAmountAlert) {
                requiredAmountAlert.textContent = POSApp.utils.formatCurrency(totalPayable).replace('MWK ', '');
            }
            alert('Payment incomplete. Remaining balance: MWK ' + paymentShortage.toFixed(2));
            return false;
        }

        const paymentData = {
            invoice_no: 'INV-' + Date.now(),
            customer_name: 'Walk-In Customer',
            contact_number: '',
            payment_status: 'paid',
            notes: notes,
            total_amount: totalPayable,
            payments: []
        };
        
        document.querySelectorAll('.payment-method-item').forEach(function(item, index) {
            const methodSelect = item.querySelector('.payment-method-select');
            const amountInput = item.querySelector('.payment-amount');
            
            if (methodSelect && amountInput) {
                const method = methodSelect.value;
                const amount = parseFloat(amountInput.value) || 0;
                
                if (method && amount > 0) {
                    paymentData.payments.push({
                        method: method,
                        amount: amount
                    });
                }
            }
        });
        
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
        
        const csrfToken = POSApp.utils.getCsrfToken();
        if (!csrfToken) {
            alert('Error: Security token not found');
            return;
        }
        
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
                return response.json().then(function(errorData) {
                    const serverMessage = errorData && errorData.error ? errorData.error : response.statusText;
                    throw new Error('Server error: ' + serverMessage + ' (' + response.status + ')');
                }).catch(function() {
                    throw new Error('Network response was not ok - Status: ' + response.status + ' ' + response.statusText);
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                console.log('Sale saved successfully:', data);
                
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
                if (data.clear_cart && window.POSCart) {
                    window.POSCart.state.items = [];
                    window.POSCart.renderCart();
                    localStorage.removeItem('pos_local_cart');
                }
                if (window.POSCart) {
                    window.POSCart.clearCartAfterPayment();
                }
                
                // Refresh products in background
                if (window.POSProducts) {
                    window.POSProducts.refreshProductData().catch(function(e) {});
                }
                
            } else {
                console.error('Error saving sale:', data.error);
                alert('Error saving sale: ' + data.error);
            }
        })
        .catch(function(error) {
            console.error('Network error during finalization:', error);
            alert('Network error during finalization: ' + error.message + '. Please check your connection and try again.');
        });
        
    } catch (e) {
        console.error('Error finalizing payment:', e);
        alert('Error processing payment. Please try again.');
    }
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
    
    const systemName = (window.systemSettings && window.systemSettings.system_name) ? window.systemSettings.system_name : 'CNC HEALTH CARE';
    const location = (window.systemSettings && window.systemSettings.location) ? window.systemSettings.location : 'Gateway Mall | Shop no. 9';
    const telephone = (window.systemSettings && window.systemSettings.telephone) ? window.systemSettings.telephone : '0990575139';

    // Header
    receiptContent += '<div style="text-align: center; margin-bottom: 15px; pt-1">';
    receiptContent += '<h1 style="margin: 0; font-size: 22px; font-weight: bold; text-transform: uppercase;">' + systemName + '</h1>';
    receiptContent += '<div style="font-size: 14px; margin: 4px 0;">' + location + '</div>';
    receiptContent += '<div style="font-size: 14px; margin: 4px 0;">Tel: ' + telephone + '</div>';
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
    receiptContent += '<div style="font-size: 12px; font-style: italic; margin-bottom: 3px;">Thank you for your purchase!</div>';
    receiptContent += '<div style="font-size: 10px;">Powered by EDUC Consultancy</div>';
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

    // Print immediately
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
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
