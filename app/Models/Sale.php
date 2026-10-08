<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SaleItem;
use App\Models\Payment;

class Sale extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'register_id', 'user_id', 'invoice_no', 'customer_name', 'contact_number', 'location',
        'payment_status', 'payment_method', 'total_amount', 'total_paid',
        'sell_due', 'total_items', 'is_edited', 'edit_log', 'edited_at', 'edited_by'
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edit_log' => 'array',
        'edited_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'sell_due' => 'decimal:2'
    ];

    protected static function booted()
    {
        // Record who made the sale
        static::creating(function (Sale $sale) {
            if (empty($sale->user_id) && auth()->check()) {
                $sale->user_id = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }
}
