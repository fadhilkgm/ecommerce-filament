<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterData extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'shop_id',
    ];

    public function values()
    {
        return $this->hasMany(MasterDataValue::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
