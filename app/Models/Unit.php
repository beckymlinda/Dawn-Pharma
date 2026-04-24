<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name','short_name','allow_decimal'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
