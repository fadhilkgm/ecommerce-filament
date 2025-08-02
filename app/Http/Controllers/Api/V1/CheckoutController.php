<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    /**
     * Get checkout data (cart items, addresses, etc.)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customer = auth('sanctum')->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            // Get customer's cart
            $cart = Cart::where('user_id', $customer->id)
                ->where('shop_id', $request->get('shop_id', 1))
                ->with(['items.product', 'items.variant'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Get customer addresses
            $shippingAddresses = $customer->addresses()
                ->whereIn('type', ['shipping', 'both'])
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $billingAddresses = $customer->addresses()
                ->whereIn('type', ['billing', 'both'])
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Calculate totals
            $subtotal = $cart->total_amount;
            $taxRate = 0.08; // 8% tax rate (can be configurable)
            $taxAmount = $subtotal * $taxRate;
            $shippingAmount = $subtotal >= 100 ? 0 : 15; // Free shipping over $100
            $total = $subtotal + $taxAmount + $shippingAmount;

            return response()->json([
                'success' => true,
                'data' => [
                    'cart' => [
                        'id' => $cart->id,
                        'total_items' => $cart->total_items,
                        'items' => $cart->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'product' => [
                                    'id' => $item->product->id,
                                    'name' => $item->product->name,
                                    'image' => $item->product->image,
                                ],
                                'variant' => $item->variant ? [
                                    'id' => $item->variant->id,
                                    'sku' => $item->variant->sku,
                                ] : null,
                                'variant_attributes' => $item->variant_attributes,
                                'quantity' => $item->quantity,
                                'unit_price' => (float) $item->unit_price,
                                'total_price' => (float) $item->total_price,
                            ];
                        }),
                    ],
                    'addresses' => [
                        'shipping' => $shippingAddresses->map(fn($addr) => $this->transformAddress($addr)),
                        'billing' => $billingAddresses->map(fn($addr) => $this->transformAddress($addr)),
                    ],
                    'totals' => [
                        'subtotal' => (float) $subtotal,
                        'tax_rate' => $taxRate,
                        'tax_amount' => (float) $taxAmount,
                        'shipping_amount' => (float) $shippingAmount,
                        'total' => (float) $total,
                    ],
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'email' => $customer->email,
                        'phone' => $customer->phone,
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load checkout data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process checkout and create order
     */
    public function processCheckout(Request $request): JsonResponse
    {
        try {
            $customer = auth('sanctum')->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'shipping_address_id' => 'required|exists:customer_addresses,id',
                'billing_address_id' => 'required|exists:customer_addresses,id',
                'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer',
                'notes' => 'nullable|string|max:500',
                'shop_id' => 'required|exists:shops,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get customer's cart
            $cart = Cart::where('user_id', $customer->id)
                ->where('shop_id', $request->shop_id)
                ->with(['items.product', 'items.variant'])
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Verify addresses belong to customer
            $shippingAddress = $customer->addresses()->findOrFail($request->shipping_address_id);
            $billingAddress = $customer->addresses()->findOrFail($request->billing_address_id);

            // Calculate totals
            $subtotal = $cart->total_amount;
            $taxRate = 0.08;
            $taxAmount = $subtotal * $taxRate;
            $shippingAmount = $subtotal >= 100 ? 0 : 15;
            $total = $subtotal + $taxAmount + $shippingAmount;

            DB::beginTransaction();

            try {
                // Create order
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $customer->id,
                    'shop_id' => $request->shop_id,
                    'customer_email' => $customer->email,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'billing_address' => $this->transformAddress($billingAddress),
                    'shipping_address' => $this->transformAddress($shippingAddress),
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $total,
                    'payment_method' => $request->payment_method,
                    'notes' => $request->notes,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ]);

                // Create order items
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

                    // Update product/variant stock
                    if ($cartItem->variant) {
                        $cartItem->variant->decrement('stock', $cartItem->quantity);
                    } else {
                        $cartItem->product->decrement('stock', $cartItem->quantity);
                    }
                }

                // Clear the cart
                $cart->clear();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully',
                    'data' => [
                        'order' => [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'total_amount' => (float) $order->total_amount,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                            'created_at' => $order->created_at->toISOString(),
                        ]
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process checkout',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform address for API response
     */
    private function transformAddress(CustomerAddress $address): array
    {
        return [
            'id' => $address->id,
            'type' => $address->type,
            'label' => $address->label,
            'first_name' => $address->first_name,
            'last_name' => $address->last_name,
            'full_name' => $address->full_name,
            'company' => $address->company,
            'address_line_1' => $address->address_line_1,
            'address_line_2' => $address->address_line_2,
            'city' => $address->city,
            'state' => $address->state,
            'postal_code' => $address->postal_code,
            'country' => $address->country,
            'phone' => $address->phone,
            'is_default' => $address->is_default,
            'formatted_address' => $address->formatted_address,
        ];
    }
}
