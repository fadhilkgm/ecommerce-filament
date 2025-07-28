<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'shop_id',
        'purchase_number',
        'supplier_id',
        'status',
        'date',
        'total_amount',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<\App\Models\Shop, self> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

}
