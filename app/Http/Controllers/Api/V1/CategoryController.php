<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            // Filter by shop_id if provided
            if ($request->has('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }

            $categories = $query->orderBy('name')->get();

            // Transform categories to include proper image URLs
            $transformedCategories = $categories->map(function ($category) {
                return $this->transformCategory($category);
            });

            return response()->json([
                'success' => true,
                'data' => $transformedCategories,
                'message' => 'Categories retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        try {
            $category = Category::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transformCategory($category),
                'message' => 'Category retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Transform category data for API response
     */
    private function transformCategory($category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'image' => $this->generateImageUrl($category->image),
            'shop_id' => $category->shop_id,
            'created_at' => $category->created_at->toISOString(),
            'updated_at' => $category->updated_at->toISOString(),
        ];
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
