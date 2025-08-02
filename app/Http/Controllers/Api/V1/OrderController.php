<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get order history for authenticated customer
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

            $perPage = min($request->get('per_page', 10), 50);
            $orders = $customer->orders()
                ->with(['items.product', 'items.variant'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders->getCollection()->map(fn($order) => $this->transformOrder($order)),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                    'has_more_pages' => $orders->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific order details
     */
    public function show(Request $request, $orderNumber): JsonResponse
    {
        try {
            $customer = auth('sanctum')->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $order = $customer->orders()
                ->with(['items.product', 'items.variant'])
                ->where('order_number', $orderNumber)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformOrderDetails($order)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transform order for API response
     */
    private function transformOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => (float) $order->total_amount,
            'items_count' => $order->items->count(),
            'created_at' => $order->created_at->toISOString(),
            'updated_at' => $order->updated_at->toISOString(),
            'shipped_at' => $order->shipped_at?->toISOString(),
            'delivered_at' => $order->delivered_at?->toISOString(),
        ];
    }

    /**
     * Transform order details for API response
     */
    private function transformOrderDetails(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'payment_reference' => $order->payment_reference,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
            'billing_address' => $order->billing_address,
            'shipping_address' => $order->shipping_address,
            'subtotal' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'discount_amount' => (float) $order->discount_amount,
            'total_amount' => (float) $order->total_amount,
            'notes' => $order->notes,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'variant_attributes' => $item->variant_attributes,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'image' => $item->product->image,
                    ] : null,
                    'variant' => $item->variant ? [
                        'id' => $item->variant->id,
                        'sku' => $item->variant->sku,
                    ] : null,
                ];
            }),
            'created_at' => $order->created_at->toISOString(),
            'updated_at' => $order->updated_at->toISOString(),
            'shipped_at' => $order->shipped_at?->toISOString(),
            'delivered_at' => $order->delivered_at?->toISOString(),
        ];
    }
}
