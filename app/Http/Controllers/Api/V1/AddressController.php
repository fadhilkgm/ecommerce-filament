<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated customer
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

            $addresses = $customer->addresses()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $addresses->map(fn($address) => $this->transformAddress($address))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch addresses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new address
     */
    public function store(Request $request): JsonResponse
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
                'type' => 'required|in:shipping,billing,both',
                'label' => 'nullable|string|max:50',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'company' => 'nullable|string|max:255',
                'address_line_1' => 'required|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $address = $customer->addresses()->create($validator->validated());

            // Set as default if requested or if it's the first address
            if ($request->is_default || $customer->addresses()->where('type', $address->type)->count() === 1) {
                $address->setAsDefault();
            }

            return response()->json([
                'success' => true,
                'message' => 'Address created successfully',
                'data' => $this->transformAddress($address->fresh())
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing address
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $customer = auth('sanctum')->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $address = $customer->addresses()->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:shipping,billing,both',
                'label' => 'nullable|string|max:50',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'company' => 'nullable|string|max:255',
                'address_line_1' => 'required|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'country' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'is_default' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $address->update($validator->validated());

            // Set as default if requested
            if ($request->is_default) {
                $address->setAsDefault();
            }

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'data' => $this->transformAddress($address->fresh())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $customer = auth('sanctum')->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $address = $customer->addresses()->findOrFail($id);
            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set an address as default
     */
    public function setDefault(Request $request, $id): JsonResponse
    {
        try {
            $customer = auth('sanctum')->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }

            $address = $customer->addresses()->findOrFail($id);
            $address->setAsDefault();

            return response()->json([
                'success' => true,
                'message' => 'Default address updated successfully',
                'data' => $this->transformAddress($address->fresh())
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default address',
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
            'created_at' => $address->created_at->toISOString(),
        ];
    }
}
