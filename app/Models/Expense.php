<?php

namespace App\Models;

use App\HasUser;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{

    use HasUser;

    protected $fillable = [
        'name',
        'amount',
        'date',
        'note',
        'category',
        'expense_type',
        'shop_id',
        'user_id',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
