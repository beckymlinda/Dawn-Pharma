<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
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
