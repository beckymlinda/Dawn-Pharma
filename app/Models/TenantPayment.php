<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A subscription payment by a shop, recorded by the software owner in the admin
 * portal. Not filtered automatically: shops only ever read their own through
 * Tenant::payments().
 */
class TenantPayment extends Model
{
    protected $fillable = ['tenant_id', 'paid_on', 'months', 'amount', 'plan', 'period_from', 'period_to', 'method', 'reference', 'recorded_by'];

    protected $casts = [
        'paid_on' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'amount' => 'decimal:2',
        'months' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
