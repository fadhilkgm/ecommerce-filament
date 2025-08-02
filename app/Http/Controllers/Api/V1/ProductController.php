<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Get products list for frontend
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with(['shop', 'category']);
            
            // Apply filters
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
            
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            if ($request->filled('search')) {
                $query->where('name', 'like', "%{$request->search}%");
            }
            
            // Apply price range filters
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['name', 'price', 'stock', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            }
            
            // Pagination
            $perPage = min($request->get('per_page', 12), 50);
            $products = $query->paginate($perPage);
            
            // Transform products
            $transformedProducts = $products->getCollection()->map(function ($product) {
                return $this->transformProduct($product);
            });
            
            return response()->json([
                'success' => true,
                'data' => $transformedProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single product details
     */
    public function show($id): JsonResponse
    {
        try {
            $product = Product::with([
                'shop', 
                'category', 
                'variants.attributes.attribute',
                'variants.images'
            ])->findOrFail($id);
                
            return response()->json([
                'success' => true,
                'data' => $this->transformProduct($product, true)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    
    /**
     * Transform product data for API response
     */
    private function transformProduct($product, $detailed = false): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'cost_price' => (float) $product->cost_price,
            'stock' => (int) $product->stock,
            'in_stock' => $product->stock > 0,
            'description' => $product->description,
            'shop' => $product->shop ? [
                'id' => $product->shop->id,
                'name' => $product->shop->name,
            ] : null,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
            ] : null,
            'image' => $this->generateImageUrl($product->image),
            'created_at' => $product->created_at->toISOString(),
            'updated_at' => $product->updated_at->toISOString(),
        ];
        
        if ($detailed) {
            $data['variants'] = $product->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'price' => (float) $variant->price,
                    'stock' => (int) $variant->stock,
                    'attributes' => $variant->attributes->map(function ($variantAttribute) {
                        return [
                            'id' => $variantAttribute->id,
                            'name' => $variantAttribute->attribute->name ?? 'Unknown',
                            'value' => $variantAttribute->value,
                        ];
                    }),
                    'images' => $variant->images->map(function ($image) {
                        $imageUrls = [];
                        if (is_array($image->images)) {
                            foreach ($image->images as $imagePath) {
                                $imageUrls[] = $this->generateImageUrl($imagePath);
                            }
                        }
                        return [
                            'id' => $image->id,
                            'name' => $image->name,
                            'alt' => $image->alt,
                            'urls' => $imageUrls,
                        ];
                    }),
                ];
            });
            
            // Collect all images from variants for the main product gallery
            $allImages = collect([$this->generateImageUrl($product->image)])->filter();
            foreach ($product->variants as $variant) {
                foreach ($variant->images as $image) {
                    if (is_array($image->images)) {
                        foreach ($image->images as $imagePath) {
                            $allImages->push($this->generateImageUrl($imagePath));
                        }
                    }
                }
            }
            $data['images'] = $allImages->unique()->values()->toArray();
        }
        
        return $data;
    }

    /**
     * Generate full URL for image path
     */
    private function generateImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        // If already a full URL, return as is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // Generate full URL from relative path
        return url('storage/' . ltrim($imagePath, '/'));
    }

}