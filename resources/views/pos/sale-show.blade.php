@extends('layouts.app')
@section('content')

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Sale #{{ $sale->invoice_no }}</h3>
    <div class="d-flex gap-2">
      @if($sale->is_edited)
        <span class="badge bg-danger"><i class="bi bi-pencil-fill"></i> Edited</span>
      @endif
      <a href="{{ route('sales.edit', $sale->id) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil"></i> Edit
      </a>
      <button class="btn btn-danger btn-sm" onclick="confirmDelete({{ $sale->id }})">
        <i class="bi bi-trash"></i> Delete
      </button>
    </div>
  </div>
  
  <div class="card mb-3">
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p><strong>Customer:</strong> {{ $sale->customer_name }}</p>
          <p><strong>Date:</strong> {{ $sale->created_at->format('d-m-Y H:i') }}</p>
          <p><strong>Payment Method:</strong> 
            @if($sale->payment_method === 'split_payment')
                @if($sale->payments && $sale->payments->count() > 0)
                    @foreach($sale->payments as $payment)
                        {{ ucfirst($payment->method) }}: MWK {{ number_format($payment->amount, 2) }}{{ !$loop->last ? ' + ' : '' }}
                    @endforeach
                @else
                    Split Payment
                @endif
            @else
                {{ ucfirst($sale->payment_method) }}
            @endif
          </p>
        </div>
        <div class="col-md-6">
          <p><strong>Total:</strong> MWK {{ number_format($sale->total_amount,2) }}</p>
          <p><strong>Status:</strong> <span class="badge bg-{{ $sale->payment_status === 'Paid' ? 'success' : 'warning' }}">{{ $sale->payment_status }}</span></p>
          @if($sale->is_edited)
            <p><strong>Edited:</strong> {{ $sale->edited_at->format('d-m-Y H:i') }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if($sale->is_edited && $sale->edit_log)
  @php
    $editLog = is_string($sale->edit_log) ? json_decode($sale->edit_log, true) : $sale->edit_log;
  @endphp
  <div class="card mb-3 border-warning">
    <div class="card-header bg-warning text-dark">
      <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit History</h5>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h6>Original Details</h6>
          <ul class="list-unstyled">
            <li><strong>Original Total:</strong> MWK {{ number_format($editLog['original_total'], 2) }}</li>
            <li><strong>Original Payment:</strong> {{ ucfirst($editLog['original_payment_method']) }}</li>
            <li><strong>Original Items:</strong> {{ count($editLog['original_items']) }}</li>
          </ul>
        </div>
        <div class="col-md-6">
          <h6>New Details</h6>
          <ul class="list-unstyled">
            <li><strong>New Total:</strong> MWK {{ number_format($editLog['new_total'], 2) }}</li>
            <li><strong>New Payment:</strong> {{ ucfirst($editLog['new_payment_method']) }}</li>
            <li><strong>New Items:</strong> {{ count($editLog['new_items']) }}</li>
          </ul>
        </div>
      </div>
      
      @if($editLog['total_change'] != 0)
      <div class="alert alert-info mt-3">
        <strong>Total Change:</strong> 
        MWK {{ number_format($editLog['total_change'], 2) }}
        {{ $editLog['total_change'] > 0 ? '(Increase)' : '(Decrease)' }}
      </div>
      @endif
      
      @if(isset($editLog['original_items']) && isset($editLog['new_items']))
      <div class="mt-3">
        <h6>Item Changes</h6>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Product</th>
                <th class="text-center">Original Qty</th>
                <th class="text-center">New Qty</th>
                <th class="text-end">Original Price</th>
                <th class="text-end">New Price</th>
              </tr>
            </thead>
            <tbody>
              @php
                $originalItems = collect($editLog['original_items'])->keyBy('product_id');
                $newItems = collect($editLog['new_items'])->keyBy('product_id');
                $allProducts = $originalItems->keys()->merge($newItems->keys())->unique();
              @endphp
              
              @foreach($allProducts as $productId)
                @php
                  $original = $originalItems->get($productId);
                  $new = $newItems->get($productId);
                  $productName = $original['product_name'] ?? $new['product_name'] ?? 'Unknown';
                  $originalQty = $original['original_quantity'] ?? 0;
                  $newQty = $new['new_quantity'] ?? 0;
                  $originalPrice = $original['original_price'] ?? 0;
                  $newPrice = $new['new_price'] ?? 0;
                  $hasChange = $originalQty != $newQty || $originalPrice != $newPrice;
                @endphp
                
                <tr class="{{ $hasChange ? 'table-warning' : '' }}">
                  <td>{{ $productName }}</td>
                  <td class="text-center">{{ $originalQty }}</td>
                  <td class="text-center">{{ $newQty }}</td>
                  <td class="text-end">MWK {{ number_format($originalPrice, 2) }}</td>
                  <td class="text-end">MWK {{ number_format($newPrice, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      @endif
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-body p-0">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>Product</th>
            <th class="text-center">Qty</th>
            <th class="text-end">Price</th>
            <th class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sale->items as $item)
            <tr>
              <td>{{ $item->product->name ?? '—' }}</td>
              <td class="text-center">{{ $item->quantity }}</td>
              <td class="text-end">MWK {{ number_format($item->price,2) }}</td>
              <td class="text-end">MWK {{ number_format($item->subtotal,2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
function confirmDelete(saleId) {
  if (confirm('Are you sure you want to delete this sale? This action cannot be undone.')) {
    // Create a form and submit it for deletion
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/sales/${saleId}`;
    form.style.display = 'none';
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = csrfToken.getAttribute('content');
      form.appendChild(csrfInput);
    }
    
    // Add method override for DELETE
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'DELETE';
    form.appendChild(methodInput);
    
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

@endsection
