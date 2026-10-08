<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class NotificationLog extends Model
{
    use BelongsToTenant, Prunable;

    protected $fillable = ['type', 'register_id', 'recipients', 'subject', 'status', 'error', 'triggered_by'];

    /** Sent history older than 30 days is deleted nightly (php artisan model:prune) */
    public function prunable()
    {
        return static::withoutGlobalScopes()->where('created_at', '<', now()->subDays(30));
    }

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
