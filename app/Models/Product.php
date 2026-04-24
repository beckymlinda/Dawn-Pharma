<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'business_location', 'unit_id', 'category_id', 'brand_id',
        'supplier_id', 'purchase_price', 'last_purchase_price', 'selling_price', 
        'current_stock', 'product_type', 'tax', 'sku', 'image', 'description', 
        'alert_quantity', 'expiry_date', 'last_purchase_date'
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockHistories()
    {
        return $this->hasMany(StockHistory::class);
    }

    public function businessLocation()
    {
        return $this->belongsTo(Location::class, 'business_location_id');
    }
}
