<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = ['name','short_name','allow_decimal'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
