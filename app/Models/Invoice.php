<?php

namespace App\Models;

use App\HasUser;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUser;

    protected $fillable = [
        'customer_name',
        'transaction_number',
        'total_discount',
        'status',
        'date',
        'customer_phone',
        'total_amount',
        'attributes',
        'invoice_number',
        'shop_id',
        'user_id',
        'payment_method'
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->shop_id = Filament::getTenant()->id;
        });
    }


    protected $casts = [
        'attributes' => 'array',
        'payment_method'=> 'array'
    ];
}
