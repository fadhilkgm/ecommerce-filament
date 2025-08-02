<?php

namespace App\Models;

use App\HasUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUser;

    protected $fillable = [
        'order_number',
        'user_id',
        'shop_id',
        'customer_email',
        'customer_name',
        'customer_phone',
        'billing_address',
        'shipping_address',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'notes',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    // Keep the user() method for backward compatibility
    public function user(): BelongsTo
    {
        return $this->customer();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Create order from cart
     */
    public static function createFromCart(Cart $cart, array $customerData, array $billingAddress, ?array $shippingAddress = null): self
    {
        $order = self::create([
            'order_number' => self::generateOrderNumber(),
            'user_id' => $cart->user_id,
            'shop_id' => $cart->shop_id,
            'customer_email' => $customerData['email'],
            'customer_name' => $customerData['name'],
            'customer_phone' => $customerData['phone'] ?? null,
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress ?? $billingAddress,
            'subtotal' => $cart->total_amount,
            'total_amount' => $cart->total_amount,
        ]);

        // Create order items from cart items
        foreach ($cart->items as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_variant_id' => $cartItem->product_variant_id,
                'product_name' => $cartItem->product->name,
                'product_sku' => $cartItem->variant?->sku,
                'variant_attributes' => $cartItem->variant_attributes,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'total_price' => $cartItem->total_price,
            ]);
        }

        return $order;
    }
}
