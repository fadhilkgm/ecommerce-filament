<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'balance',
        'payment_type',
        'shop_id',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            $customer->shop_id = Filament::getTenant()->id;
        });
    }
}
