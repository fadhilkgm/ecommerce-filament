<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'stock', 'price', 'shop_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariantAttribute::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($productVariant) {
            if (auth()->check()) {
                $productVariant->shop_id = Filament::getTenant()->id;
            }
        });
    }
}
