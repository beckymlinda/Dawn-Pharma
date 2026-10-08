<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SmsRecipient extends Model
{
    use BelongsToTenant;

    protected $fillable = ['phone', 'name', 'is_active', 'added_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** +265991234567 → 0991 234 567, easier to read on screen */
    public function getDisplayPhoneAttribute(): string
    {
        if (preg_match('/^\+265(\d{3})(\d{3})(\d{3})$/', $this->phone, $m)) {
            return "0{$m[1]} {$m[2]} {$m[3]}";
        }

        return $this->phone;
    }
}
