<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use BelongsToTenant;

    protected $fillable = ['type', 'register_id', 'recipients', 'subject', 'status', 'error', 'triggered_by'];

    public function register()
    {
        return $this->belongsTo(Register::class);
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
