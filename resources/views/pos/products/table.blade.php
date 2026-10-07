@if ($products->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="bg-light text-muted small text-uppercase fw-bold">
                <tr>
                    <th class="ps-4">ID</th>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th class="text-end">Purchase Price</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-center pe-4">Actions</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                @foreach ($products as $product)
                    <tr>
                        <td class="ps-4 text-muted small">#{{ $product->id }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $product->name }}</div>
                            <div class="small text-muted">{{ $product->brand?->name ?? 'No Brand' }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark border fw-medium">{{ $product->sku }}</span></td>
                        <td><span class="badge bg-primary-soft text-primary">{{ $product->category?->name ?? 'General' }}</span></td>
                        <td>
                            @php
                                $stockStatus = 'text-success';
                                if($product->current_stock <= 0) $stockStatus = 'text-danger';
                                elseif($product->current_stock <= $product->alertLevel()) $stockStatus = 'text-warning';
                            @endphp
                            <div class="fw-bold {{ $stockStatus }}">
                                {{ number_format($product->current_stock, 0) }} {{ $product->unit?->short_name ?? 'pcs' }}
                            </div>
                            @if($product->current_stock <= 0)
                                <div class="badge bg-danger-soft text-danger p-0" style="font-size: 10px;">Out of stock</div>
                            @elseif($product->current_stock <= $product->alertLevel())
                                <div class="badge bg-warning-subtle text-warning-emphasis p-0" style="font-size: 10px;">Low stock</div>
                            @endif
                        </td>
                        <td class="text-end fw-medium text-muted">MWK {{ number_format($product->purchase_price, 0) }}</td>
                        <td class="text-end fw-bold text-dark">MWK {{ number_format($product->selling_price, 0) }}</td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-icon border-0 bg-light-warning text-warning" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon border-0 bg-light-danger text-danger" 
                                            onclick="return confirm('Are you sure you want to delete this product?')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
        </div>
        <div>
            {{ $products->appends(request()->query())->links() }}
        </div>
    </div>
@else
    <div class="text-center py-5">
        <img src="https://illustrations.popsy.co/gray/not-found.svg" alt="No data" style="height: 150px;" class="mb-3 opacity-50">
        <h6 class="text-muted">No products found matching your search.</h6>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm mt-2">Add New Product</a>
    </div>
@endif
