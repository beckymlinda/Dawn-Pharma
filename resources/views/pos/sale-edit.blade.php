@extends('layouts.app')
@section('content')

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Edit Sale #{{ $sale->invoice_no }}</h3>
    <div class="d-flex gap-2">
      @if($sale->is_edited)
        <span class="badge bg-danger"><i class="bi bi-pencil-fill"></i> Previously Edited</span>
      @endif
      <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form action="{{ route('sales.update', $sale->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
      <!-- Left Column - Sale Details -->
      <div class="col-md-4">
        <div class="card mb-3">
          <div class="card-header">
            <h5 class="mb-0">Sale Details</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Invoice Number</label>
              <input type="text" class="form-control" value="{{ $sale->invoice_no }}" readonly>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Customer Name</label>
              <input type="text" name="customer_name" class="form-control" value="{{ $sale->customer_name }}" placeholder="Enter customer name">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Contact Number</label>
              <input type="text" name="contact_number" class="form-control" value="{{ $sale->contact_number ?? '' }}" placeholder="Enter contact number">
            </div>
            
            <div class="mb-3">
              <label class="form-label">Payment Method *</label>
              <select name="payment_method" class="form-select" required>
                <option value="cash" {{ $sale->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="card" {{ $sale->payment_method == 'card' ? 'selected' : '' }}>Card</option>
                <option value="cheque" {{ $sale->payment_method == 'cheque' ? 'selected' : '' }}>Cheque</option>
                <option value="bank_transfer" {{ $sale->payment_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="airtel_money" {{ $sale->payment_method == 'airtel_money' ? 'selected' : '' }}>Airtel Money</option>
                <option value="mpamba" {{ $sale->payment_method == 'mpamba' ? 'selected' : '' }}>TNM Mpamba</option>
                <option value="national_bank" {{ $sale->payment_method == 'national_bank' ? 'selected' : '' }}>National Bank</option>
                <option value="standard_bank" {{ $sale->payment_method == 'standard_bank' ? 'selected' : '' }}>Standard Bank</option>
                <option value="cash_and_card" {{ $sale->payment_method == 'cash_and_card' ? 'selected' : '' }}>Cash and Card</option>
                <option value="advance" {{ $sale->payment_method == 'advance' ? 'selected' : '' }}>Advance</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Payment Status *</label>
              <select name="payment_status" class="form-select" required>
                <option value="Paid" {{ $sale->payment_status == 'Paid' ? 'selected' : '' }}>Paid</option>
                <option value="Due" {{ $sale->payment_status == 'Due' ? 'selected' : '' }}>Due</option>
              </select>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Original Date</label>
              <input type="text" class="form-control" value="{{ $sale->created_at->format('d-m-Y H:i') }}" readonly>
            </div>
          </div>
        </div>

        <!-- Summary Card -->
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">Summary</h5>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span>Items:</span>
              <strong id="totalItems">{{ $sale->items->sum('quantity') }}</strong>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <span>Total Amount:</span>
              <strong id="totalAmount">MWK {{ number_format($sale->total_amount, 2) }}</strong>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
              <span class="text-success">New Total:</span>
              <strong class="text-success" id="newTotalAmount">MWK {{ number_format($sale->total_amount, 2) }}</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Items -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sale Items</h5>
            <button type="button" class="btn btn-primary btn-sm" onclick="addNewItem()">
              <i class="bi bi-plus"></i> Add Item
            </button>
          </div>
          <div class="card-body">
            <div id="itemsContainer">
              <!-- Existing Items -->
              @foreach($sale->items as $index => $item)
                <div class="row mb-3 item-row" data-index="{{ $index }}">
                  <div class="col-md-4">
                    <label class="form-label">Product *</label>
                    <select name="items[{{ $index }}][product_id]" class="form-select product-select" required onchange="updateProductDetails({{ $index }})">
                      @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }} 
                                data-price="{{ $product->selling_price }}" data-stock="{{ $product->current_stock }}">
                          {{ $product->name }} (Stock: {{ $product->current_stock }})
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Quantity *</label>
                    <input type="number" name="items[{{ $index }}][quantity]" 
                           class="form-control quantity-input" 
                           value="{{ $item->quantity }}" 
                           min="0.01" step="0.01" required
                           onchange="updateItemTotal({{ $index }})">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Price *</label>
                    <input type="number" name="items[{{ $index }}][price]" 
                           class="form-control price-input" 
                           value="{{ $item->price }}" 
                           min="0" step="0.01" required
                           onchange="updateItemTotal({{ $index }})">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Subtotal</label>
                    <input type="text" class="form-control subtotal-input" 
                           value="MWK {{ number_format($item->subtotal, 2) }}" readonly>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeItem({{ $index }})">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              @endforeach
            </div>
            
            <!-- Add New Item Template (hidden) -->
            <div id="newItemTemplate" style="display: none;">
              <div class="row mb-3 item-row" data-index="NEW_INDEX">
                <div class="col-md-4">
                  <label class="form-label">Product *</label>
                  <select name="items[NEW_INDEX][product_id]" class="form-select product-select" required onchange="updateProductDetails(NEW_INDEX)">
                    @foreach($products as $product)
                      <option value="{{ $product->id }}" 
                              data-price="{{ $product->selling_price }}" data-stock="{{ $product->current_stock }}">
                        {{ $product->name }} (Stock: {{ $product->current_stock }})
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Quantity *</label>
                  <input type="number" name="items[NEW_INDEX][quantity]" 
                         class="form-control quantity-input" 
                         value="1" min="0.01" step="0.01" required
                         onchange="updateItemTotal(NEW_INDEX)">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Price *</label>
                  <input type="number" name="items[NEW_INDEX][price]" 
                         class="form-control price-input" 
                         value="0" min="0" step="0.01" required
                         onchange="updateItemTotal(NEW_INDEX)">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Subtotal</label>
                  <input type="text" class="form-control subtotal-input" 
                           value="MWK 0.00" readonly>
                </div>
                <div class="col-md-1">
                  <label class="form-label">&nbsp;</label>
                  <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeItem(NEW_INDEX)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-3">
      <div class="col-12">
        <div class="d-flex justify-content-between">
          <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Cancel
          </a>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check"></i> Update Sale
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
let newItemIndex = {{ $sale->items->count() }};

function addNewItem() {
  const template = document.getElementById('newItemTemplate').innerHTML;
  const container = document.getElementById('itemsContainer');
  
  // Replace NEW_INDEX with actual index
  const newHtml = template.replace(/NEW_INDEX/g, newItemIndex);
  
  // Create temporary div and append
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = newHtml;
  const newRow = tempDiv.firstElementChild;
  
  container.appendChild(newRow);
  
  // Initialize the new row
  updateItemTotal(newItemIndex);
  
  newItemIndex++;
}

function removeItem(index) {
  const row = document.querySelector(`[data-index="${index}"]`);
  if (row) {
    row.remove();
    updateTotals();
  }
}

function updateProductDetails(index) {
  const select = document.querySelector(`[data-index="${index}"] .product-select`);
  const priceInput = document.querySelector(`[data-index="${index}"] .price-input`);
  
  if (select && priceInput) {
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    const stock = selectedOption.getAttribute('data-stock');
    
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
    
    subtotalInput.value = 'MWK ' + subtotal.toFixed(2);
    updateTotals();
  }
}

function updateTotals() {
  const rows = document.querySelectorAll('.item-row');
  let totalItems = 0;
  let totalAmount = 0;
  
  rows.forEach(row => {
    const quantityInput = row.querySelector('.quantity-input');
    const subtotalInput = row.querySelector('.subtotal-input');
    
    if (quantityInput && subtotalInput) {
      const quantity = parseFloat(quantityInput.value) || 0;
      const subtotalText = subtotalInput.value;
      const subtotal = parseFloat(subtotalText.replace('MWK ', '').replace(',', '')) || 0;
      
      totalItems += quantity;
      totalAmount += subtotal;
    }
  });
  
  document.getElementById('totalItems').textContent = totalItems.toFixed(2);
  document.getElementById('totalAmount').textContent = 'MWK ' + totalAmount.toFixed(2);
  document.getElementById('newTotalAmount').textContent = 'MWK ' + totalAmount.toFixed(2);
}

// Initialize totals on page load
document.addEventListener('DOMContentLoaded', function() {
  updateTotals();
});
</script>

@endsection
