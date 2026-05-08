/**
 * POS Payments Module
 * Handles payment processing, receipt generation, and printing
 */

// Payments Module Namespace
window.POSPayments = window.POSPayments || {};

// Payments State
POSPayments.state = {
    paymentMethods: [],
    receiptContent: null
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
    
    POSPayments.updatePaymentSummary();
};

/**
 * Update payment summary calculations
 */
POSPayments.updatePaymentSummary = function() {
    const totalPayableElement = document.getElementById('totalPayable');
    const totalPaidElement = document.getElementById('totalPaid');
    const remainingBalanceElement = document.getElementById('remainingBalance');
    
    if (!totalPayableElement) {
        console.log('Total payable element not found');
        return true;
    }
    
    const totalPayable = parseFloat(totalPayableElement.textContent.replace('MWK ', '').replace(',', '')) || 0;
    let totalPaid = 0;
    
    document.querySelectorAll('.payment-amount').forEach(function(input) {
        totalPaid += parseFloat(input.value) || 0;
    });
    
    const remainingBalance = totalPayable - totalPaid;
    
    if (totalPaidElement) totalPaidElement.textContent = POSApp.utils.formatCurrency(totalPaid);
    if (remainingBalanceElement) remainingBalanceElement.textContent = POSApp.utils.formatCurrency(remainingBalance);
    
    const addPaymentBtn = document.getElementById('addPaymentMethodBtn');
    if (addPaymentBtn) {
        addPaymentBtn.style.display = remainingBalance > 0 ? 'block' : 'none';
    }
    
    const finalizeBtn = document.getElementById('finalizePaymentBtn');
    if (finalizeBtn) {
        finalizeBtn.disabled = remainingBalance > 0;
    }
    
    console.log('Payment summary updated - Total:', totalPayable.toFixed(2), 'Paid:', totalPaid.toFixed(2), 'Remaining:', remainingBalance.toFixed(2));
    
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
        const notes = document.getElementById('paymentNotes').value;
        const totalPayableElement = document.getElementById('totalPayable');
        if (!totalPayableElement) {
            alert('Error: Total amount not found');
            return;
        }
        
        const totalPayable = parseFloat(totalPayableElement.textContent.replace('MWK ', '').replace(',', ''));
        
        if (!POSPayments.updatePaymentSummary()) {
            return;
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
            notes: paymentData.notes
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
            headers: {
                'Content-Type': 'application/json',
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
                
                // Generate receipt BEFORE clearing cart (so cart items are available)
                POSPayments.generateReceipt(paymentData);
                
                // Clear cart immediately if backend indicates it was cleared
                if (data.clear_cart && window.POSCart) {
                    console.log('🧹 Clearing cart immediately after successful sale...');
                    window.POSCart.state.items = [];
                    window.POSCart.renderCart();
                    
                    // Also call backend clear as failsafe
                    fetch(POSApp.config.apiEndpoints.cartClear, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': POSApp.utils.getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).catch(function(error) {
                        console.error('Failsafe cart clear failed:', error);
                    });
                }
                
                // Close payment modal
                POSApp.modal.hide('paymentModal');
                
                // Create direct browser print window with receipt
                POSPayments.printReceiptDirectly();
                
                // Clear cart after printing (backup)
                if (window.POSCart) {
                    window.POSCart.clearCartAfterPayment();
                }
                
                // Refresh product data immediately after sale
                console.log('About to refresh product data after successful sale...');
                if (window.POSProducts) {
                    window.POSProducts.refreshProductData().then(function(success) {
                        console.log('Product data refresh result:', success);
                    }).catch(function(error) {
                        console.error('Product data refresh error:', error);
                    });
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
    
    // Header
    receiptContent += '<div style="text-align: center; margin-bottom: 15px; pt-1">';
    receiptContent += '<h1 style="margin: 0; font-size: 24px; font-weight: bold; text-transform: uppercase;">EDUC PHARMACY</h1>';
    receiptContent += '<div style="font-size: 16px; font-weight: bold; margin: 4px 0;">Bwandilo Area 47</div>';
    receiptContent += '<div style="font-size: 16px; margin: 2px 0;">Lilongwe Malawi</div>';
    receiptContent += '<div style="font-size: 16px; font-weight: bold; margin: 4px 0;">0999672775</div>';
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
    
    const totalPaid = paymentData.payments.reduce((acc, p) => acc + p.amount, 0);
    const changeAmount = Math.max(0, totalPaid - totalAmount);

    receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; margin-top: 5px;">';
    receiptContent += '<span>TOTAL PAID:</span>';
    receiptContent += '<span>MWK ' + totalPaid.toFixed(2) + '</span>';
    receiptContent += '</div>';
    
    receiptContent += '<div style="display: flex; justify-content: space-between; font-size: 11px; margin-top: 4px;">';
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
 * Print receipt directly (NON-BLOCKING version)
 */
POSPayments.printReceiptDirectly = function() {
    console.log('=== PRINT RECEIPT DIRECTLY (NON-BLOCKING) ===');

    if (!POSPayments.state.receiptContent) {
        console.error('ERROR: No receipt content available!');
        alert('Error: No receipt content to print');
        return;
    }

    const content = POSPayments.state.receiptContent;
    console.log('Content length:', content.length);

    try {
        // Create a hidden iframe for printing (non-blocking)
        const printFrame = document.createElement('iframe');
        printFrame.style.display = 'none';
        printFrame.style.position = 'absolute';
        printFrame.style.left = '-9999px';
        document.body.appendChild(printFrame);

        const printWindow = printFrame.contentWindow;
        let html = '<!DOCTYPE html><html><head><title>Receipt</title>';
        html += '<style>';
        html += 'body { font-family: monospace; margin: 0; padding: 15px 25px 15px 10px; font-size: 10px; line-height: 1.4; color: #000; width: 260px; }';
        html += '@media print { body { margin: 0; padding: 15px 20px 15px 10px; width: 90%; } }';
        html += '</style></head><body>';
        html += content;
        html += '</body></html>';

        printWindow.document.write(html);
        printWindow.document.close();

        // Print without blocking the main thread
        setTimeout(function() {
            try {
                printWindow.print();
                console.log('Print command sent to browser');

                // Clean up after printing
                setTimeout(function() {
                    document.body.removeChild(printFrame);
                    console.log('Print iframe cleaned up');
                }, 2000);

            } catch (printError) {
                console.error('Print error:', printError);
                document.body.removeChild(printFrame);
            }
        }, 100);

    } catch (error) {
        console.error('ERROR in direct print function:', error);
        alert('Error preparing receipt for printing: ' + error.message);
    }
};

/**
 * Print receipt from modal
 */
POSPayments.printReceipt = function() {
    console.log('=== PRINT RECEIPT FROM MODAL ===');
    
    const receiptPrintArea = document.getElementById('receipt-print-area');
    if (!receiptPrintArea) {
        console.error('ERROR: Receipt print area not found');
        alert('Error: Receipt content not found');
        return;
    }
    
    const content = receiptPrintArea.innerHTML;
    if (!content || content.trim() === '') {
        console.error('ERROR: No receipt content to print');
        alert('Error: No receipt content to print');
        return;
    }
    
    console.log('Printing receipt content, length:', content.length);
    
    // Create a print window
    const printWindow = window.open('', '_blank', 'width=600,height=800,scrollbars=yes,resizable=yes');
    
    if (!printWindow) {
        console.error('ERROR: Print window blocked by browser');
        alert('Print window was blocked. Please allow popups for this site.');
        return;
    }
    
    try {
        let html = '<!DOCTYPE html><html><head><title>Receipt</title>';
        html += '<style>';
        html += 'body { font-family: monospace; margin: 0; padding: 15px 25px 15px 10px; font-size: 10px; line-height: 1.4; color: #000; width: 260px; }';
        html += '@media print { body { margin: 0; padding: 15px 20px 15px 10px; width: 90%; -webkit-print-color-adjust: exact; } }';
        html += '</style></head><body>';
        html += content;
        html += '</body></html>';
        
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.focus();
        
        // Auto print and close after a delay
        setTimeout(function() {
            printWindow.print();
            setTimeout(function() {
                printWindow.close();
            }, 1000);
        }, 500);
        
    } catch (error) {
        console.error('ERROR in print function:', error);
        alert('Error preparing receipt for printing: ' + error.message);
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
        
        // Re-render cart to show empty state
        window.POSCart.renderCart();
        
        console.log('Cart cleared after payment');
    } else {
        console.error('Cart module not available for clearing');
    }
};
