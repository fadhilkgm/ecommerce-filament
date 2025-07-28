<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterDataValue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'master_data_id',
        'master_data_code',
        'value',
        'type',
        'description',
        'status',
        'created_by',
        'shop_id'
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function masterData()
    {
        return $this->belongsTo(MasterData::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}