@extends('layouts.app')
@section('content')

<!-- Select2 CSS for searchable dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<style>
  .modern-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  }
  .modern-card .card-header {
    background-color: white;
    border-bottom: 2px solid #f8f9fa;
    border-radius: 12px 12px 0 0;
    padding: 1rem 1.25rem;
  }
  .payment-row, .item-row {
    background-color: #f8fafc;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    border: 1px solid #e2e8f0;
  }
  .select2-container--bootstrap-5 .select2-selection {
    min-height: 38px;
  }
</style>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="mb-0 fw-bold text-primary">Edit Sale #{{ $sale->invoice_no }}</h3>
      <p class="text-muted mb-0">Update products and payment methods</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Sale
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error') || $errors->any())
    <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ session('error') ?? 'Please check the form for errors.' }}
      @if($errors->any())
        <ul class="mb-0 mt-2">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      @endif
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('sales.update', $sale->id) }}" method="POST" id="editSaleForm">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
      <!-- Left Column - Sale Items -->
      <div class="col-lg-8">
        <div class="card modern-card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 me-2 text-primary"></i>Sale Items</h5>
            <button type="button" class="btn btn-primary btn-sm" onclick="addNewItem()">
              <i class="bi bi-plus-lg"></i> Add Product
            </button>
          </div>
          <div class="card-body">
            <div id="itemsContainer">
              @foreach($sale->items as $index => $item)
                <div class="item-row" data-index="{{ $index }}">
                  <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                      <label class="form-label text-muted small fw-bold">Search Product</label>
                      <select name="items[{{ $index }}][product_id]" class="form-select select2-product" required onchange="updateProductDetails({{ $index }})">
                        <option value="">Select a product...</option>
                        @foreach($products as $product)
                          <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }} 
                                  data-price="{{ $product->selling_price }}" data-stock="{{ $product->current_stock }}">
                            {{ $product->name }} (Stock: {{ $product->current_stock }}) - MWK {{ number_format($product->selling_price) }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-2">
                      <label class="form-label text-muted small fw-bold">Quantity</label>
                      <input type="number" name="items[{{ $index }}][quantity]" 
                             class="form-control quantity-input" 
                             value="{{ $item->quantity }}" 
                             min="0.01" step="0.01" required
                             onchange="updateItemTotal({{ $index }})" onkeyup="updateItemTotal({{ $index }})">
                    </div>
                    <div class="col-md-2">
                      <label class="form-label text-muted small fw-bold">Unit Price</label>
                      <input type="number" name="items[{{ $index }}][price]" 
                             class="form-control price-input" 
                             value="{{ $item->price }}" 
                             min="0" step="0.01" required
                             onchange="updateItemTotal({{ $index }})" onkeyup="updateItemTotal({{ $index }})">
                    </div>
                    <div class="col-md-2">
                      <label class="form-label text-muted small fw-bold">Subtotal</label>
                      <input type="text" class="form-control subtotal-input bg-white text-primary fw-bold" 
                             value="{{ number_format($item->subtotal, 2) }}" readonly>
                    </div>
                    <div class="col-md-1">
                      <button type="button" class="btn btn-outline-danger w-100" onclick="removeItem({{ $index }})" title="Remove Item">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
            
            <!-- Add New Item Template (hidden) -->
            <div id="newItemTemplate" class="d-none">
              <div class="item-row" data-index="NEW_INDEX">
                <div class="row g-3 align-items-end">
                  <div class="col-md-5">
                    <label class="form-label text-muted small fw-bold">Search Product</label>
                    <select name="items[NEW_INDEX][product_id]" class="form-select select2-template" required disabled onchange="updateProductDetails(NEW_INDEX)">
                      <option value="">Select a product...</option>
                      @foreach($products as $product)
                        <option value="{{ $product->id }}" 
                                data-price="{{ $product->selling_price }}" data-stock="{{ $product->current_stock }}">
                          {{ $product->name }} (Stock: {{ $product->current_stock }}) - MWK {{ number_format($product->selling_price) }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">Quantity</label>
                    <input type="number" name="items[NEW_INDEX][quantity]" 
                           class="form-control quantity-input" 
                           value="1" min="0.01" step="0.01" required disabled
                           onchange="updateItemTotal(NEW_INDEX)" onkeyup="updateItemTotal(NEW_INDEX)">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">Unit Price</label>
                    <input type="number" name="items[NEW_INDEX][price]" 
                           class="form-control price-input" 
                           value="0" min="0" step="0.01" required disabled
                           onchange="updateItemTotal(NEW_INDEX)" onkeyup="updateItemTotal(NEW_INDEX)">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold">Subtotal</label>
                    <input type="text" class="form-control subtotal-input bg-white text-primary fw-bold" 
                             value="0.00" readonly disabled>
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger w-100" onclick="removeItem(NEW_INDEX)" title="Remove Item">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Payments -->
      <div class="col-lg-4">
        <div class="card modern-card h-100">
          <div class="card-header bg-primary text-white" style="border-radius: 12px 12px 0 0;">
            <h5 class="mb-0 fw-bold"><i class="bi bi-credit-card me-2"></i>Payment Details</h5>
          </div>
          <div class="card-body">
            
            <div class="bg-light p-3 rounded-3 mb-4 text-center border">
              <span class="text-muted d-block mb-1">New Total Amount</span>
              <h2 class="text-primary fw-bold mb-0">MWK <span id="grandTotalDisplay">0.00</span></h2>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold mb-0">Payment Methods</h6>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPaymentMethod()">
                <i class="bi bi-plus"></i> Add Split
              </button>
            </div>

            <div id="paymentsContainer">
              @php
                 $payments = $sale->payments && $sale->payments->count() > 0 ? $sale->payments : collect([ (object)['method' => $sale->payment_method === 'split' ? 'cash' : $sale->payment_method, 'amount' => $sale->total_amount] ]);
              @endphp

              @foreach($payments as $index => $payment)
                <div class="payment-row" data-payment-index="{{ $index }}">
                  <div class="row g-2 align-items-end">
                    <div class="col-7">
                      <label class="form-label text-muted small fw-bold mb-1">Method</label>
                      <select name="payments[{{ $index }}][method]" class="form-select form-select-sm" required>
                        <option value="cash" {{ $payment->method == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="card" {{ $payment->method == 'card' ? 'selected' : '' }}>Card</option>
                        <option value="cheque" {{ $payment->method == 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="bank_transfer" {{ $payment->method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="airtel_money" {{ $payment->method == 'airtel_money' ? 'selected' : '' }}>Airtel Money</option>
                        <option value="mpamba" {{ $payment->method == 'mpamba' ? 'selected' : '' }}>TNM Mpamba</option>
                        <option value="national_bank" {{ $payment->method == 'national_bank' ? 'selected' : '' }}>National Bank</option>
                        <option value="standard_bank" {{ $payment->method == 'standard_bank' ? 'selected' : '' }}>Standard Bank</option>
                        <option value="advance" {{ $payment->method == 'advance' ? 'selected' : '' }}>Advance</option>
                      </select>
                    </div>
                    <div class="col-5">
                      <label class="form-label text-muted small fw-bold mb-1">Amount</label>
                      <div class="input-group input-group-sm">
                        <input type="number" name="payments[{{ $index }}][amount]" 
                               class="form-control payment-amount" 
                               value="{{ $payment->amount }}" 
                               min="0.01" step="0.01" required
                               onchange="updatePaymentStatus()" onkeyup="updatePaymentStatus()">
                        @if($index > 0)
                        <button type="button" class="btn btn-outline-danger" onclick="removePaymentMethod({{ $index }})" title="Remove">
                          <i class="bi bi-x"></i>
                        </button>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            <!-- Payment Row Template -->
            <div id="newPaymentTemplate" class="d-none">
              <div class="payment-row" data-payment-index="PAY_INDEX">
                <div class="row g-2 align-items-end">
                  <div class="col-7">
                    <label class="form-label text-muted small fw-bold mb-1">Method</label>
                    <select name="payments[PAY_INDEX][method]" class="form-select form-select-sm" required disabled>
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
                  <div class="col-5">
                    <label class="form-label text-muted small fw-bold mb-1">Amount</label>
                    <div class="input-group input-group-sm">
                      <input type="number" name="payments[PAY_INDEX][amount]" 
                             class="form-control payment-amount" 
                             value="0" 
                             min="0.01" step="0.01" required disabled
                             onchange="updatePaymentStatus()" onkeyup="updatePaymentStatus()">
                      <button type="button" class="btn btn-outline-danger" onclick="removePaymentMethod(PAY_INDEX)" title="Remove">
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="border-top pt-3 mt-2">
              <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Total Provided:</span>
                <strong id="totalProvidedDisplay" class="text-dark">MWK 0.00</strong>
              </div>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Difference:</span>
                <strong id="balanceDisplay" class="text-danger">MWK 0.00</strong>
              </div>
            </div>
            
            <div id="paymentAlert" class="alert alert-warning mt-3 py-2 d-none" style="font-size: 0.85rem;">
              <i class="bi bi-exclamation-triangle"></i> Provided amounts must match the new total.
            </div>

            <button type="submit" class="btn btn-success w-100 mt-4 py-2 fw-bold" id="updateBtn">
              <i class="bi bi-check2-circle me-2"></i>Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let newItemIndex = {{ $sale->items->count() }};
let newPaymentIndex = {{ $payments->count() }};
let currentTotalAmount = 0;

// Initialize Select2
function initSelect2() {
  $('.select2-product').select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Search for a product...'
  });
}

document.addEventListener('DOMContentLoaded', function() {
  initSelect2();
  updateTotals();
  
  // Prevent form submission if totals don't match
  document.getElementById('editSaleForm').addEventListener('submit', function(e) {
    const provided = parseFloat(getProvidedTotal().toFixed(2));
    const required = parseFloat(currentTotalAmount.toFixed(2));
    if (Math.abs(provided - required) > 0.01) {
      e.preventDefault();
      document.getElementById('paymentAlert').classList.remove('d-none');
      document.getElementById('paymentAlert').innerHTML = '<i class="bi bi-exclamation-triangle"></i> The sum of payment amounts must exactly match the new total.';
    }
  });
});

// Item Management
function addNewItem() {
  const template = document.getElementById('newItemTemplate').innerHTML;
  const container = document.getElementById('itemsContainer');
  
  // Replace NEW_INDEX with actual index
  let newHtml = template.replace(/NEW_INDEX/g, newItemIndex);
  // Remove the select2-template class and add select2-product
  newHtml = newHtml.replace('select2-template', 'select2-product');
  
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = newHtml;
  const newRow = tempDiv.firstElementChild;
  
  // Enable all disabled inputs in the cloned template
  newRow.querySelectorAll('input, select').forEach(el => el.removeAttribute('disabled'));
  
  container.appendChild(newRow);
  
  // Initialize Select2 on the new element
  $(newRow).find('.select2-product').select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Search for a product...'
  });
  
  updateItemTotal(newItemIndex);
  newItemIndex++;
}

function removeItem(index) {
  const row = document.querySelector(`.item-row[data-index="${index}"]`);
  if (row) {
    // Destroy select2 instance before removing
    $(row).find('.select2-product').select2('destroy');
    row.remove();
    updateTotals();
  }
}

function updateProductDetails(index) {
  const select = document.querySelector(`[data-index="${index}"] select`);
  const priceInput = document.querySelector(`[data-index="${index}"] .price-input`);
  
  if (select && priceInput && select.selectedIndex > 0) {
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    priceInput.value = price;
    updateItemTotal(index);
  }
}

function updateItemTotal(index) {
  const quantityInput = document.querySelector(`[data-index="${index}"] .quantity-input`);
  const priceInput = document.querySelector(`[data-index="${index}"] .price-input`);
  const subtotalInput = document.querySelector(`[data-index="${index}"] .subtotal-input`);
  
  if (quantityInput && priceInput && subtotalInput) {
    const quantity = parseFloat(quantityInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    const subtotal = quantity * price;
    
    // Format safely (no commas for easy parsing later if needed, but here it's readonly display)
    subtotalInput.value = subtotal.toFixed(2);
    updateTotals();
  }
}

function updateTotals() {
  const rows = document.querySelectorAll('#itemsContainer .item-row');
  let totalAmount = 0;
  
  rows.forEach(row => {
    const quantityInput = row.querySelector('.quantity-input');
    const priceInput = row.querySelector('.price-input');
    if (quantityInput && priceInput) {
      const quantity = parseFloat(quantityInput.value) || 0;
      const price = parseFloat(priceInput.value) || 0;
      totalAmount += (quantity * price);
    }
  });
  
  currentTotalAmount = totalAmount;
  
  // Format with commas for display
  document.getElementById('grandTotalDisplay').textContent = totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  
  // Auto-update first payment amount if only one payment exists
  const paymentRows = document.querySelectorAll('.payment-row');
  if (paymentRows.length === 1) {
    const amountInput = paymentRows[0].querySelector('.payment-amount');
    if (amountInput) {
      amountInput.value = totalAmount.toFixed(2);
    }
  }
  
  updatePaymentStatus();
}

// Payment Management
function addPaymentMethod() {
  const template = document.getElementById('newPaymentTemplate').innerHTML;
  const container = document.getElementById('paymentsContainer');
  
  const newHtml = template.replace(/PAY_INDEX/g, newPaymentIndex);
  
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = newHtml;
  const newRow = tempDiv.firstElementChild;
  
  // Enable all disabled inputs in the cloned template
  newRow.querySelectorAll('input, select').forEach(el => el.removeAttribute('disabled'));
  
  container.appendChild(newRow);
  
  // Auto-fill amount with remaining balance
  const amountInput = newRow.querySelector('.payment-amount');
  const provided = getProvidedTotal();
  const balance = currentTotalAmount - provided;
  if (amountInput && balance > 0) {
    amountInput.value = balance.toFixed(2);
  }
  
  newPaymentIndex++;
  updatePaymentStatus();
}

function removePaymentMethod(index) {
  const row = document.querySelector(`.payment-row[data-payment-index="${index}"]`);
  if (row) {
    row.remove();
    updatePaymentStatus();
  }
}

function getProvidedTotal() {
  let total = 0;
  document.querySelectorAll('.payment-amount').forEach(input => {
    // Only check inputs that are inside the actual container, not the template
    if (!input.closest('#newPaymentTemplate')) {
      total += parseFloat(input.value) || 0;
    }
  });
  return total;
}

function updatePaymentStatus() {
  const provided = getProvidedTotal();
  const diff = currentTotalAmount - provided;
  
  document.getElementById('totalProvidedDisplay').textContent = 'MWK ' + provided.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  
  const balanceDisplay = document.getElementById('balanceDisplay');
  const alertBox = document.getElementById('paymentAlert');
  const updateBtn = document.getElementById('updateBtn');
  
  if (Math.abs(diff) < 0.01) {
    balanceDisplay.textContent = 'MWK 0.00';
    balanceDisplay.className = 'text-success fw-bold';
    alertBox.classList.add('d-none');
    updateBtn.disabled = false;
  } else {
    balanceDisplay.textContent = 'MWK ' + diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    balanceDisplay.className = diff > 0 ? 'text-danger fw-bold' : 'text-warning fw-bold';
    alertBox.classList.remove('d-none');
    updateBtn.disabled = true;
  }
}
</script>

@endsection
