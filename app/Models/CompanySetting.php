<?php

namespace App\Models;

use App\HasUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasUser;

    protected $fillable = [
        'shop_id',
        'key',
        'value',
        'type',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * Get the typed value based on the type field
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            default => $this->value,
        };
    }

    /**
     * Set the value with proper type conversion
     */
    public function setTypedValue($value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };
    }

    /**
     * Get a single setting value
     */
    public static function getValue(string $key, $default = null, ?int $shopId = null)
    {
        $query = static::where('key', $key);
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        
        $setting = $query->first();
        
        return $setting ? $setting->typed_value : $default;
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value, string $type = 'string', ?int $shopId = null): self
    {
        $shopId = $shopId ?? auth()->user()?->shop_id ?? 1;
        
        $setting = static::updateOrCreate(
            ['shop_id' => $shopId, 'key' => $key],
            ['type' => $type]
        );
        
        $setting->setTypedValue($value);
        $setting->save();
        
        return $setting;
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings(?int $shopId = null): array
    {
        $query = static::query();
        
        if ($shopId) {
            $query->where('shop_id', $shopId);
        }
        
        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->typed_value];
        })->toArray();
    }
}