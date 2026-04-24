<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'type', 'quantity_change', 'new_quantity',
        'unit_cost', 'unit_price', 'date', 'reference_no',
        'customer_supplier', 'notes', 'business_location_id'
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'new_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'date' => 'date'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function businessLocation()
    {
        return $this->belongsTo(Location::class, 'business_location_id');
    }
}
