<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use BelongsToTenant;

    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'notes',
        'contact_person',
        'tax_number',
        'created_by'
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }
}
