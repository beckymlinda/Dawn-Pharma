<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Report - {{ setting('system_name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; margin: 24px; }
        .header { text-align: center; margin-bottom: 16px; }
        .header h1 { font-size: 18px; margin: 0 0 4px; }
        .header div { font-size: 11px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        td.num, th.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f5f5f5; }
        .footer { margin-top: 12px; font-size: 10px; color: #666; text-align: center; }
        .no-print { margin-bottom: 12px; }
        @media print { .no-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('stock.report') }}">Back to stock report</a>
    </div>

    <div class="header">
        <h1>{{ setting('system_name') }} - Stock Report</h1>
        @if(setting('location'))<div>{{ setting('location') }}</div>@endif
        <div>Generated {{ now()->format('d M Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th class="num">Selling Price</th>
                <th class="num">Current Stock</th>
                <th class="num">Stock Value (Cost)</th>
                <th class="num">Stock Value (Selling)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name ?? 'N/A' }}</td>
                    <td class="num">{{ formatCurrency($product->selling_price) }}</td>
                    <td class="num">{{ number_format($product->current_stock, 2) }}</td>
                    <td class="num">{{ formatCurrency($product->current_stock * $product->purchase_price) }}</td>
                    <td class="num">{{ formatCurrency($product->current_stock * $product->selling_price) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center">No products found</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total</td>
                <td class="num">{{ number_format($products->sum('current_stock'), 2) }}</td>
                <td class="num">{{ formatCurrency($products->sum(fn ($p) => $p->current_stock * $p->purchase_price)) }}</td>
                <td class="num">{{ formatCurrency($products->sum(fn ($p) => $p->current_stock * $p->selling_price)) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">Powered by {{ setting('powered_by') }}</div>

    <script>window.addEventListener('load', () => window.print());</script>
</body>
</html>
