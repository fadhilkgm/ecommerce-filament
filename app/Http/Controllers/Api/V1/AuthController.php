<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new customer
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'shop_id' => 'required|exists:shops,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?: null,
                'address' => $request->address ?: null,
                'shop_id' => $request->shop_id,
                'email_verified_at' => now(), // Auto-verify for now, can be changed later
            ]);

            $token = $customer->createToken('auth_token')->plainTextToken;

            // Transfer guest cart to authenticated user
            $this->transferGuestCart($request, $customer);

            return response()->json([
                'success' => true,
                'message' => 'Customer registered successfully',
                'data' => [
                    'customer' => $this->transformCustomer($customer),
                    'token' => $token,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login customer
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customer = Customer::where('email', $request->email)->first();

            if (!$customer || !Hash::check($request->password, $customer->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $token = $customer->createToken('auth_token')->plainTextToken;

            // Transfer guest cart to authenticated user
            $this->transferGuestCart($request, $customer);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'customer' => $this->transformCustomer($customer),
                    'token' => $token,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout customer
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated customer
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $customer = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => $this->transformCustomer($customer),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get customer data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer guest cart items to authenticated user cart
     */
    private function transferGuestCart(Request $request, Customer $customer): void
    {
        try {
            $shopId = $request->get('shop_id', 1);
            $sessionId = md5($request->ip() . $request->userAgent());

            // Find guest cart
            $guestCart = Cart::where('shop_id', $shopId)
                ->where('session_id', $sessionId)
                ->whereNull('user_id')
                ->first();

            if (!$guestCart || $guestCart->items->isEmpty()) {
                \Log::info('AuthController: No guest cart found or cart is empty', [
                    'session_id' => $sessionId,
                    'customer_id' => $customer->id,
                ]);
                return;
            }

            // Find or create user cart
            $userCart = Cart::where('shop_id', $shopId)
                ->where('user_id', $customer->id)
                ->first();

            if (!$userCart) {
                // Convert guest cart to user cart
                $guestCart->update([
                    'user_id' => $customer->id,
                    'session_id' => null,
                    'expires_at' => null,
                ]);

                \Log::info('AuthController: Converted guest cart to user cart', [
                    'cart_id' => $guestCart->id,
                    'customer_id' => $customer->id,
                    'items_count' => $guestCart->items->count(),
                ]);
            } else {
                // Merge guest cart items into user cart
                $guestCart->load('items');
                
                foreach ($guestCart->items as $guestItem) {
                    $existingItem = $userCart->items()
                        ->where('product_id', $guestItem->product_id)
                        ->where('product_variant_id', $guestItem->product_variant_id)
                        ->first();

                    if ($existingItem) {
                        // Update quantity of existing item
                        $existingItem->quantity += $guestItem->quantity;
                        $existingItem->total_price = $existingItem->quantity * $existingItem->unit_price;
                        $existingItem->save();
                    } else {
                        // Move item to user cart
                        $guestItem->update(['cart_id' => $userCart->id]);
                    }
                }

                // Update user cart totals
                $userCart->updateTotals();

                // Delete empty guest cart
                $guestCart->delete();

                \Log::info('AuthController: Merged guest cart into user cart', [
                    'user_cart_id' => $userCart->id,
                    'guest_cart_id' => $guestCart->id,
                    'customer_id' => $customer->id,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('AuthController: Failed to transfer guest cart', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Transform customer for API response
     */
    private function transformCustomer(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'balance' => (float) $customer->balance,
            'created_at' => $customer->created_at->toISOString(),
        ];
    }
}
