<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'location_id',
        'opened_at',
        'closed_at',
        'opening_cash',
        'closing_cash',
        'expected_cash',
        'counted_cash',
        'cash_variance',
    ];

    protected $casts = [
     
    'user_id' => 'integer',
     
    'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted()
    {
        // Email the Z report whenever a register goes from open to closed,
        // whichever controller action closed it.
        static::updated(function (Register $register) {
            if ($register->wasChanged('closed_at') && $register->closed_at && !$register->getOriginal('closed_at')) {
                \App\Jobs\SendZReportEmail::dispatchAfterResponse($register->id, auth()->id());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get total cash from sales
     */
    public function getTotalCashAttribute()
    {
        return $this->sales()->where('payment_method', 'cash')->sum('total_amount');
    }

    /**
     * Get total card slips
     */
    public function getTotalCardAttribute()
    {
        return $this->sales()->where('payment_method', 'card')->sum('total_amount');
    }

    /**
     * Get total cheques
     */
    public function getTotalChequesAttribute()
    {
        return $this->sales()->where('payment_method', 'cheque')->sum('total_amount');
    }

    /**
     * Get total bank transfers
     */
    public function getTotalBankTransferAttribute()
    {
        return $this->sales()->where('payment_method', 'bank_transfer')->sum('total_amount');
    }

    /**
     * Get total advance payments
     */
    public function getTotalAdvanceAttribute()
    {
        return $this->sales()->where('payment_method', 'advance')->sum('total_amount');
    }
}
