<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id', 'type', 'quantity_change', 'new_quantity',
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

    protected static function booted()
    {
        // Record who caused the stock movement
        static::creating(function (StockHistory $history) {
            if (empty($history->user_id) && auth()->check()) {
                $history->user_id = auth()->id();
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessLocation()
    {
        return $this->belongsTo(Location::class, 'business_location_id');
    }
}
