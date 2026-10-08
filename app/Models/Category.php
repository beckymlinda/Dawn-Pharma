<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = ['name','category_code','description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
