<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function transactions()
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function getBalance()
    {
        return $this->transactions->where('type', 'debit')->sum('amount') - $this->transactions->where('type', 'credit')->sum('amount');
    }

    public function getCredit()
    {
        return $this->transactions->where('type', 'credit')->sum('amount');
    }

    public function getDebit()
    {
        return $this->transactions->where('type', 'debit')->sum('amount');
    }
}
