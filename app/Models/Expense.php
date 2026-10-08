<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToTenant;

    /** Common running costs of a shop or pharmacy in Malawi */
    public const CATEGORIES = [
        'Rent',
        'Salaries & Wages',
        'Electricity & Water',
        'Transport & Fuel',
        'Airtime & Internet',
        'Shop Supplies',
        'Repairs & Maintenance',
        'Taxes, Licences & Fees',
        'Bank & Mobile Money Charges',
        'Security',
        'Other',
    ];

    protected $fillable = ['expense_date', 'category', 'description', 'amount', 'payment_method', 'user_id'];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
