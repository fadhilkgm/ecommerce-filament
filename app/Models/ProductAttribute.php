<?php

namespace App\Models;

use App\HasUser;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasUser;

    protected $fillable = ['name','master_data','shop_id'];


    protected $casts = [
        'master_data' => 'array',
    ];


    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
