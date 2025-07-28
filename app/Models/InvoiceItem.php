<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'category_id',
        'product_id',
        'product_variant_id',
        'invoice_id',
        'quantity',
        'total',
        'unit_price',
    ];


    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
