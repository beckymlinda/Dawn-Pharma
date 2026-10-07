<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static function booted()
    {
        // Drop the per-request cache used by setting() / business_settings()
        $forget = fn () => app()->forgetInstance('business.settings');
        static::saved($forget);
        static::deleted($forget);
    }
}
