<?php

namespace App\Models;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Model;

class ProductVariantAttribute extends Model
{
    protected $fillable = ['product_variant_id', 'product_attribute_id', 'value'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id'); // Use 'product_variant_id' explicitly
    }

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}

