<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
    ];

    public function registers()
    {
        return $this->hasMany(Register::class);
    }
}
