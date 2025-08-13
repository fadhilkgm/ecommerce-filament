<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ContentManagement extends Model
{
    protected $table = 'content_management';

    protected $fillable = [
        'title',
        'code',
        'type',
        'content',
        'images',
        'link_url',
        'sort_order',
        'is_enabled',
        'meta_data',
        'shop_id'
    ];

    protected $casts = [
        'images' => 'array',
        'meta_data' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function shop(){
        return $this->belongsTo(Shop::class);
    }

    // Scopes
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Helper methods
    public static function getBannerImages()
    {
        return self::enabled()
            ->byCode('BANNER_IMAGES')
            ->ordered()
            ->first();
    }

    public static function getContentByCode($code)
    {
        return self::enabled()
            ->byCode($code)
            ->first();
    }
}
