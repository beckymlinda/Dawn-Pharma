<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'business_location',
        'reference_no',
        'purchase_date',
        'status',
        'payment_status',
        'grand_total',
        'paid_amount',
        'created_by',
    ];

    protected $dates = [
        'purchase_date',
        'created_at',
        'updated_at',
    ];

    public function supplier()
    {
        return $this->belongsTo(Contact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
