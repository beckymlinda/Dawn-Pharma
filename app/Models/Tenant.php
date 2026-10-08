<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * A shop using the system. Everything the shop owns carries its tenant_id.
 */
class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'status', 'plan', 'paid_until', 'grace_days',
        'contact_name', 'contact_phone', 'contact_email', 'notes', 'suspended_reason',
    ];

    protected $casts = [
        'paid_until' => 'date',
        'grace_days' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function payments()
    {
        return $this->hasMany(TenantPayment::class)->orderByDesc('paid_on')->orderByDesc('id');
    }

    public function paymentSubmissions()
    {
        return $this->hasMany(PaymentSubmission::class)->latest();
    }

    /**
     * Record a subscription payment and move paid-until forward. A renewal continues
     * from the current end date; a shop that has lapsed starts again today.
     * A suspended shop becomes active again, since it has now paid.
     */
    public function recordPayment(int $months, ?float $amount, string $recordedBy, array $extra = []): TenantPayment
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($months, $amount, $recordedBy, $extra) {
            $from = $this->paid_until && $this->paid_until->gte(Carbon::today()) ? $this->paid_until->copy() : Carbon::today();
            $to = $from->copy()->addMonthsNoOverflow($months);

            $payment = $this->payments()->create($extra + [
                'paid_on' => Carbon::today(),
                'months' => $months,
                'amount' => $amount ?? $this->monthlyPrice() * $months,
                'plan' => $this->plan,
                'period_from' => $from,
                'period_to' => $to,
                'recorded_by' => $recordedBy,
            ]);

            $this->update(['paid_until' => $to, 'status' => 'active', 'suspended_reason' => null]);

            return $payment;
        });
    }

    /** Monthly price of the shop's plan (0 when it has none) */
    public function monthlyPrice(): float
    {
        return (float) config("tenancy.plans.{$this->plan}.price", 0);
    }

    /** Last day the shop can still be used (paid-until + grace), or null when there is no end date */
    public function blockedAfter(): ?Carbon
    {
        return $this->paid_until?->copy()->addDays($this->grace_days ?? config('tenancy.default_grace_days'))->endOfDay();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /** Past the paid-until date and the grace period */
    public function isExpired(): bool
    {
        return $this->blockedAfter() !== null && now()->gt($this->blockedAfter());
    }

    /** Past the paid-until date but still inside the grace period */
    public function inGracePeriod(): bool
    {
        return $this->paid_until !== null && now()->gt($this->paid_until->copy()->endOfDay()) && !$this->isExpired();
    }

    public function canBeUsed(): bool
    {
        return !$this->isSuspended() && !$this->isExpired();
    }

    /** Days until paid-until (negative when overdue), or null when there is no end date */
    public function daysLeft(): ?int
    {
        return $this->paid_until ? (int) now()->startOfDay()->diffInDays($this->paid_until->copy()->startOfDay(), false) : null;
    }

    /** active | due_soon | grace | expired | suspended */
    public function billingState(): string
    {
        if ($this->isSuspended()) {
            return 'suspended';
        }
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->inGracePeriod()) {
            return 'grace';
        }
        if ($this->daysLeft() !== null && $this->daysLeft() <= config('tenancy.warn_days_before')) {
            return 'due_soon';
        }

        return 'active';
    }

    public function planName(): string
    {
        return config("tenancy.plans.{$this->plan}.name") ?? ucfirst((string) $this->plan ?: 'None');
    }

    /** The shop's link: opening it selects the shop in that browser and shows its login */
    public function url(): string
    {
        return url('/' . $this->slug);
    }
}
