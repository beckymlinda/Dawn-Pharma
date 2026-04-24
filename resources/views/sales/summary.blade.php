@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Sales Summary</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="loadPaymentMethodSummary()">
                <i class="bi bi-arrow-clockwise"></i> Load Summary
            </button>
            <button class="btn btn-warning" onclick="testAllPayments()">
                <i class="bi bi-bug"></i> Test Payments
            </button>
            <a href="/sales/summary/test-payments" class="btn btn-info" target="_blank">
                <i class="bi bi-link-45deg"></i> Test API
            </a>
            <button class="btn btn-secondary" onclick="exportToExcel()">
                <i class="bi bi-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Date Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="summary_type" class="form-label">Summary Type</label>
                    <select id="summary_type" class="form-select">
                        <option value="payment_method">By Payment Method</option>
                        <option value="daily">Daily</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-success w-100" onclick="loadPaymentMethodSummary()">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summaryCards">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Sales</h5>
                    <h3 class="card-text">MWK <span id="totalSalesCard">0</span></h3>
                    <small><span id="totalTransactionsCard">0</span> transactions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Split Payments</h5>
                    <h3 class="card-text">MWK <span id="splitPaymentsTotal">0</span></h3>
                    <small><span id="splitPaymentsCount">0</span> split payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Payment Methods</h5>
                    <h3 class="card-text"><span id="paymentMethodsCount">0</span></h3>
                    <small>different methods used</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Avg Transaction</h5>
                    <h3 class="card-text">MWK <span id="avgTransaction">0</span></h3>
                    <small>per transaction</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Split Payments Details -->
    <div class="card mt-4" id="splitPaymentsCard" style="display: none;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Split Payments Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Split Payment Methods Used:</h6>
                    <div id="splitPaymentMethods"></div>
                </div>
                <div class="col-md-6">
                    <h6>Split Payment Statistics:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Total Split Sales:</strong> <span id="splitTotalSales">0</span></li>
                        <li><strong>Split Sales Amount:</strong> MWK <span id="splitTotalAmount">0</span></li>
                        <li><strong>Percentage of Total:</strong> <span id="splitPercentage">0</span>%</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Table -->
    <div class="card mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Payment Method Summary</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Total Amount (MWK)</th>
                            <th>Transactions</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr>
                            <td colspan="4" class="text-center">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Totals Card -->
    <div class="card mt-4" id="totalsCard" style="display: none;">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Summary Totals</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Total Sales</h6>
                    <h3 class="text-success">MWK <span id="totalSales">0</span></h3>
                </div>
                <div class="col-md-6">
                    <h6>Total Transactions</h6>
                    <h3 class="text-info"><span id="totalTransactions">0</span></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadPaymentMethodSummary() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const summaryType = document.getElementById('summary_type').value;
    
    fetch('/sales/summary/payment-methods', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            start_date: startDate,
            end_date: endDate,
            summary_type: summaryType
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data); // Debug: Log full response
        
        if (data.success) {
            // Show debug information
            if (data.debug) {
                console.log('Debug Info:', data.debug);
                alert(`Debug Info:\nPayments Found: ${data.debug.payments_count}\nRaw Total: MWK ${data.debug.raw_total.toLocaleString()}\nMethods: ${data.debug.payment_methods_found.join(', ')}`);
            }
            
            renderSummaryTable(data.summary);
            renderTotalsCard(data.totals);
            renderSummaryCards(data); // Add this line
        } else {
            alert('Failed to load summary: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading summary');
    });
}

function renderSummaryTable(summary) {
    const tbody = document.getElementById('summaryTableBody');
    tbody.innerHTML = '';
    
    if (summary.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No data found</td></tr>';
        return;
    }
    
    const totalAmount = summary.reduce((sum, item) => sum + parseFloat(item.total_amount), 0);
    
    summary.forEach(item => {
        const percentage = totalAmount > 0 ? ((item.total_amount / totalAmount) * 100).toFixed(1) : 0;
        const methodLabel = item.method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        tbody.innerHTML += `
            <tr>
                <td><strong>${methodLabel}</strong></td>
                <td class="text-end">MWK ${parseFloat(item.total_amount).toLocaleString()}</td>
                <td class="text-center">${item.count}</td>
                <td class="text-end">${percentage}%</td>
            </tr>
        `;
    });
}

function renderTotalsCard(totals) {
    document.getElementById('totalsCard').style.display = 'block';
    document.getElementById('totalSales').textContent = parseFloat(totals.total_revenue).toLocaleString();
    document.getElementById('totalTransactions').textContent = totals.total_transactions.toLocaleString();
}

function renderSummaryCards(data) {
    // Update summary cards with new API structure
    document.getElementById('totalSalesCard').textContent = parseFloat(data.totals.total_revenue).toLocaleString();
    document.getElementById('totalTransactionsCard').textContent = data.totals.total_transactions.toLocaleString();
    
    // Split payments card - calculate from payment data
    const splitPaymentSales = data.summary.filter(item => {
        // Count sales that have multiple payment methods (same sale_id appears multiple times)
        return data.breakdown && Object.keys(data.breakdown).some(method => {
            // This is a simplified check - in real implementation, you'd need to check sale_id counts
            return false; // For now, disable split payments card as we're using payments directly
        });
    });
    
    document.getElementById('splitPaymentsTotal').textContent = '0';
    document.getElementById('splitPaymentsCount').textContent = '0';
    
    // Payment methods count
    document.getElementById('paymentMethodsCount').textContent = data.summary.length;
    
    // Average transaction
    const avgTransaction = data.totals.total_transactions > 0 ? 
        data.totals.total_revenue / data.totals.total_transactions : 0;
    document.getElementById('avgTransaction').textContent = parseFloat(avgTransaction).toLocaleString();
    
    // Hide split payments card since we're using payments table directly
    document.getElementById('splitPaymentsCard').style.display = 'none';
}

function renderSplitPaymentsDetails(splitSummary, totalSales) {
    // Update split payment statistics
    document.getElementById('splitTotalSales').textContent = splitSummary.count;
    document.getElementById('splitTotalAmount').textContent = parseFloat(splitSummary.total_amount).toLocaleString();
    
    const percentage = totalSales > 0 ? ((splitSummary.total_amount / totalSales) * 100).toFixed(1) : 0;
    document.getElementById('splitPercentage').textContent = percentage;
    
    // Update split payment methods
    const methodsContainer = document.getElementById('splitPaymentMethods');
    methodsContainer.innerHTML = '';
    
    splitSummary.payment_methods.forEach(method => {
        const methodLabel = method.method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        methodsContainer.innerHTML += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>${methodLabel}</span>
                <span class="badge bg-primary">MWK ${parseFloat(method.total_amount).toLocaleString()} (${method.count})</span>
            </div>
        `;
    });
}

function testAllPayments() {
    fetch('/sales/summary/test-payments', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Test Results:', data);
        
        if (data.success) {
            const results = data.test_results;
            alert(`Payment Test Results:\n\n` +
                  `Total Payments in DB: ${results.total_payments_in_db}\n` +
                  `Total Amount in DB: MWK ${results.total_amount_in_db.toLocaleString()}\n\n` +
                  `Today's Payments (Simple): ${results.today_payments_simple}\n` +
                  `Today's Amount (Simple): MWK ${results.today_amount_simple.toLocaleString()}\n` +
                  `Today's Methods: ${results.today_methods_simple.join(', ')}\n\n` +
                  `Check console for detailed payment info.`);
            
            console.log('Today\'s Payment Details:', results.today_payment_details);
        } else {
            alert('Test failed: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Test Error:', error);
        alert('Error testing payments');
    });
}

function exportToExcel() {
    // TODO: Implement Excel export functionality
    alert('Excel export coming soon!');
}
</script>
@endsection
