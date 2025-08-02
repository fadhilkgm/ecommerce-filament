<?php

namespace App\Models;

use App\HasUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasUser;

    protected $fillable = [
        'session_id',
        'user_id',
        'shop_id',
        'total_amount',
        'total_items',
        'expires_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Calculate and update cart totals
     */
    public function updateTotals(): void
    {
        $this->load('items');
        
        $this->total_items = $this->items->sum('quantity');
        $this->total_amount = $this->items->sum('total_price');
        
        $this->save();
    }

    /**
     * Add item to cart or update quantity if exists
     */
    public function addItem(int $productId, ?int $variantId, int $quantity, float $unitPrice, ?array $variantAttributes = null): CartItem
    {
        $cartItem = $this->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->total_price = $cartItem->quantity * $cartItem->unit_price;
            $cartItem->save();
        } else {
            $cartItem = $this->items()->create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'variant_attributes' => $variantAttributes,
            ]);
        }

        $this->updateTotals();

        return $cartItem;
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $cartItemId): bool
    {
        $item = $this->items()->find($cartItemId);
        
        if ($item) {
            $item->delete();
            $this->updateTotals();
            return true;
        }

        return false;
    }

    /**
     * Clear all items from cart
     */
    public function clear(): void
    {
        $this->items()->delete();
        $this->updateTotals();
    }
}
