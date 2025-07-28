<?php

namespace App\Models;

use App\HasUser;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUser;

    protected $fillable = [
        'name',
        'price',
        'cost_price',
        'shop_id',
        'category_id',
        'stock',
        'user_id',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }


    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributes() {
        return $this->hasManyThrough(ProductVariantAttribute::class, ProductVariant::class);
    }


}
