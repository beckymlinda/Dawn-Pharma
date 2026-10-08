<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sale;
use App\Models\Product;

class SaleItem extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'quantity', 'price', 'subtotal', 'purchase_cost'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
