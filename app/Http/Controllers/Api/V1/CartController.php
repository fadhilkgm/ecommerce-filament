<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * Get cart contents
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Debug logging
            \Log::info('Cart index request', [
                'user' => auth('sanctum')->user(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);

            $cart = $this->getOrCreateCart($request);

            $cart->load(['items.product', 'items.variant.attributes.attribute']);

            \Log::info('Cart loaded', [
                'cart_id' => $cart->id,
                'items_count' => $cart->items->count(),
                'total_amount' => $cart->total_amount,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cart' => $this->transformCart($cart),
                    'items' => $cart->items->map(fn($item) => $this->transformCartItem($item)),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Cart index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
        // Debug logging
        \Log::info('Add to cart request received', [
            'data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'shop_id' => 'required|exists:shops,id',
        ]);

        if ($validator->fails()) {
            \Log::error('Cart validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);
            $variant = $request->variant_id ? ProductVariant::findOrFail($request->variant_id) : null;

            // Check stock availability
            $availableStock = $variant ? $variant->stock : $product->stock;
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available',
                    'available_stock' => $availableStock
                ], 400);
            }

            // Get or create cart
            $cart = $this->getOrCreateCart($request);

            // Determine price and variant attributes
            $unitPrice = $variant ? $variant->price : $product->price;
            $variantAttributes = null;

            if ($variant) {
                $variant->load('attributes.attribute');
                $variantAttributes = $variant->attributes->map(function ($attr) {
                    return [
                        'name' => $attr->attribute->name,
                        'value' => $attr->value,
                    ];
                })->toArray();
            }

            // Add item to cart
            $cartItem = $cart->addItem(
                $request->product_id,
                $request->variant_id,
                $request->quantity,
                $unitPrice,
                $variantAttributes
            );

            $cartItem->load(['product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => [
                    'cart' => $this->transformCart($cart),
                    'item' => $this->transformCartItem($cartItem),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(Request $request, $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cart = $this->getOrCreateCart($request);
            $cartItem = $cart->items()->findOrFail($itemId);

            // Check stock availability
            $variant = $cartItem->variant;
            $product = $cartItem->product;
            $availableStock = $variant ? $variant->stock : $product->stock;

            if ($availableStock < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock available',
                    'available_stock' => $availableStock
                ], 400);
            }

            $cartItem->updateQuantity($request->quantity);
            $cartItem->load(['product', 'variant']);

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'data' => [
                    'cart' => $this->transformCart($cart->fresh()),
                    'item' => $this->transformCartItem($cartItem),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, $itemId): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart($request);

            if ($cart->removeItem($itemId)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item removed from cart successfully',
                    'data' => [
                        'cart' => $this->transformCart($cart->fresh()),
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear cart
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $cart = $this->getOrCreateCart($request);
            $cart->clear();

            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully',
                'data' => [
                    'cart' => $this->transformCart($cart->fresh()),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create cart for current session/user
     */
    private function getOrCreateCart(Request $request): Cart
    {
        $shopId = $request->get('shop_id', 1);  // Default to shop 1
        
        // Check if customer is authenticated using Sanctum
        $customer = auth('sanctum')->user();
        $customerId = $customer ? $customer->id : null;
        
        \Log::info('Cart: Getting or creating cart', [
            'customer_id' => $customerId,
            'shop_id' => $shopId,
            'has_auth_header' => $request->hasHeader('Authorization'),
        ]);
        
        if ($customerId) {
            // For authenticated users, always look for user cart first
            $cart = Cart::where('shop_id', $shopId)
                ->where('user_id', $customerId)
                ->first();

            if (!$cart) {
                // Create new user cart
                $cart = Cart::create([
                    'session_id' => null,
                    'user_id' => $customerId,
                    'shop_id' => $shopId,
                    'total_items' => 0,
                    'total_amount' => 0.00,
                    'expires_at' => null,
                ]);
                
                \Log::info('Cart: Created new user cart', [
                    'cart_id' => $cart->id,
                    'customer_id' => $customerId,
                ]);
            }
        } else {
            // For guest users, use session-based cart
            $sessionId = md5($request->ip() . $request->userAgent());
            
            $cart = Cart::where('shop_id', $shopId)
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->first();

            if (!$cart) {
                // Create new guest cart
                $cart = Cart::create([
                    'session_id' => $sessionId,
                    'user_id' => null,
                    'shop_id' => $shopId,
                    'total_items' => 0,
                    'total_amount' => 0.00,
                    'expires_at' => now()->addDays(7),  // Session carts expire in 7 days
                ]);
                
                \Log::info('Cart: Created new guest cart', [
                    'cart_id' => $cart->id,
                    'session_id' => $sessionId,
                ]);
            }
        }

        \Log::info('Cart: Using cart', [
            'cart_id' => $cart->id,
            'customer_id' => $customerId,
            'session_id' => $cart->session_id,
        ]);

        return $cart;
    }

    /**
     * Transform cart for API response
     */
    private function transformCart(Cart $cart): array
    {
        return [
            'id' => $cart->id,
            'total_items' => (int) ($cart->total_items ?? 0),
            'total_amount' => (float) $cart->total_amount,
            'updated_at' => $cart->updated_at->toISOString(),
        ];
    }

    /**
     * Transform cart item for API response
     */
    private function transformCartItem($item): array
    {
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
    }
}
