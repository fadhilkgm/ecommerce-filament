<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'balance',
        'payment_type',
        'shop_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'balance' => 'decimal:2',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->where('type', 'shipping');
    }

    public function billingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->where('type', 'billing');
    }

    public function getDefaultShippingAddressAttribute(): ?CustomerAddress
    {
        return $this->shippingAddresses()->where('is_default', true)->first();
    }

    public function getDefaultBillingAddressAttribute(): ?CustomerAddress
    {
        return $this->billingAddresses()->where('is_default', true)->first();
    }
}
