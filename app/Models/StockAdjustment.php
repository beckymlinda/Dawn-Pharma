<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'adjustment_type',
        'quantity',
        'previous_quantity',
        'new_quantity',
        'previous_price',
        'new_price',
        'new_expiry_date',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'previous_quantity' => 'decimal:2',
        'new_quantity' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'new_expiry_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAdjustmentTypeTextAttribute()
    {
        return $this->adjustment_type === 'add' ? 'Stock In' : 'Stock Out';
    }

    public function getQuantityChangeAttribute()
    {
        return $this->adjustment_type === 'add' 
            ? '+' . $this->quantity 
            : '-' . $this->quantity;
    }
}
