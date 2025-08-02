<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantImage extends Model
{
    protected $fillable = [
        'product_variant_id',
        'name',
        'alt',
        'images'
    ];

    public function productVariant(){
        return $this->belongsTo(ProductVariant::class);
    }

    protected $casts = [
        'images' => 'array'
    ];
}
