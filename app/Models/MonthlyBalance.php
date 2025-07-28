<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBalance extends Model
{
    protected $fillable = [
        'month',
        'opening_balance',
        'credits',
        'debits',
        'closing_balance',
        'shop_id',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
