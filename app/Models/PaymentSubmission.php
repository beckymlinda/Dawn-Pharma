<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Proof of payment uploaded by a shop, waiting for the software owner's review.
 * Not filtered automatically: shops read theirs through Tenant::paymentSubmissions().
 */
class PaymentSubmission extends Model
{
    protected $fillable = [
        'tenant_id', 'submitted_by', 'submitted_by_name', 'submitted_by_email', 'months', 'amount', 'account',
        'reference', 'paid_on', 'proof_path', 'proof_name', 'note', 'status', 'reviewed_by', 'reviewed_at',
        'review_note', 'tenant_payment_id',
    ];

    protected $hidden = ['notify_error'];

    protected $casts = [
        'paid_on' => 'date',
        'reviewed_at' => 'datetime',
        'notified_at' => 'datetime',
        'amount' => 'decimal:2',
        'months' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payment()
    {
        return $this->belongsTo(TenantPayment::class, 'tenant_payment_id');
    }

    public function accountLabel(): string
    {
        $account = config("tenancy.payment_accounts.{$this->account}");

        return $account ? $account['label'] . ' ' . $account['number'] : $this->account;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
