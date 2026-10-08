<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToTenant;

    protected $fillable = ['key', 'value'];

    protected static function booted()
    {
        // Drop the per-request cache used by setting() / business_settings()
        $forget = fn () => app()->forgetInstance('business.settings');
        static::saved($forget);
        static::deleted($forget);
    }
}
