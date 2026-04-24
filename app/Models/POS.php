<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class POS extends Model
{
    use HasFactory;

    protected $table = 'pos';

    protected $fillable = [
        'date', 'invoice_no', 'customer_name', 'payment_status', 'payment_method',
        'total_amount', 'total_paid', 'return_due', 'shipping_status', 'total_items'
    ];
}
