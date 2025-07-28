<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'category_id',
        'purchase_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit',
        'total',
        'unit_price',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }


    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
