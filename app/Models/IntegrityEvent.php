<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Append-only record of actions that can indicate theft or loss
 * (deleted / edited sales, voids, price overrides, cash shortages, ...).
 */
class IntegrityEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'type', 'severity', 'user_id', 'register_id', 'sale_id', 'invoice_no', 'product_id',
        'amount', 'description', 'details', 'ip_address',
        'reviewed', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'details' => 'array',
        'amount' => 'decimal:2',
        'reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    /** Human labels and what each event type can mean. */
    public const TYPES = [
        'sale_deleted' => ['Sale deleted', 'A completed sale was deleted. Cash may have been kept and the record removed.'],
        'delete_blocked' => ['Blocked delete attempt', 'A user without permission tried to delete a sale.'],
        'sale_edited' => ['Sale edited', 'A completed sale was changed after payment (total, items or payment method).'],
        'edit_blocked' => ['Blocked edit attempt', 'Someone tried to edit a sale they are not allowed to change (another cashier\'s sale, a closed shift, or no permission).'],
        'price_override' => ['Price below set price', 'Item sold for less than its selling price. The POS has no discount feature, so this suggests a tampered request.'],
        'below_cost' => ['Sold below cost', 'Item sold for less than it cost to buy.'],
        'line_void' => ['Item removed from cart', 'An item was removed or its quantity reduced before payment.'],
        'cart_cleared' => ['Cart cleared', 'A cart with items was cleared without completing the sale.'],
        'cash_shortage' => ['Cash shortage', 'Less cash was counted in the drawer than the system expected.'],
        'cash_surplus' => ['Cash surplus', 'More cash was counted than expected. This can mean sales were not recorded.'],
        'cash_not_counted' => ['Closed without cash count', 'The register was closed without counting the cash.'],
        'stock_removed' => ['Stock removed', 'Stock was reduced through a stock adjustment.'],
        'after_hours_sale' => ['Sale outside business hours', 'A sale was made outside the configured business hours.'],
        'sale_without_register' => ['Sale without register', 'A sale was recorded without an open register.'],
        'failed_login' => ['Failed login', 'Someone entered a wrong password for this account.'],
        'offline_stock_shortfall' => ['Offline sale beyond stock', 'A sale made while offline sold more than the system had in stock. Stock was set to zero; recount this product.'],
        'offline_underpaid' => ['Offline sale underpaid', 'A sale made while offline was paid less than the current price (the price may have changed, or the payment was short).'],
        'offline_late_sync' => ['Offline sale after register close', 'A sale made while offline was uploaded after its register was closed, so it is missing from that Z report.'],
    ];

    /** Weight of each severity when scoring cashier risk. */
    public const WEIGHTS = ['low' => 0.5, 'medium' => 2, 'high' => 5, 'critical' => 10];

    /** Default thresholds; overridable from the Loss Prevention settings. */
    public const SETTING_DEFAULTS = [
        'lp_open_time' => '07:00',
        'lp_close_time' => '21:00',
        'lp_cash_tolerance' => '500',
        'lp_blind_close' => '1',
    ];

    /**
     * Whether cashiers must count the drawer (blind) when closing the register.
     * Off means the register closes without a cash count, as it originally did.
     */
    public static function cashCountRequired(): bool
    {
        return self::setting('lp_blind_close') === '1';
    }

    public static function setting(string $key): string
    {
        // Read fresh each time so a change in Settings applies immediately
        $value = Setting::where('key', $key)->value('value');

        return (string) ($value ?? self::SETTING_DEFAULTS[$key]);
    }

    /**
     * Record an event. Never throws: monitoring must not break a sale.
     */
    public static function record(string $type, string $severity, string $description, array $attributes = []): ?self
    {
        try {
            return self::create(array_merge([
                'type' => $type,
                'severity' => $severity,
                'description' => mb_substr($description, 0, 500),
                'user_id' => auth()->id(),
                'ip_address' => request()?->ip(),
                'register_id' => \App\Http\Controllers\RegisterController::getOpenRegister()?->id,
            ], $attributes));
        } catch (\Throwable $e) {
            Log::error('Failed to record integrity event', ['type' => $type, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function getLabelAttribute(): string
    {
        return self::TYPES[$this->type][0] ?? ucwords(str_replace('_', ' ', $this->type));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
