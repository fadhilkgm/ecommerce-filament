<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $shopId = $request->header('X-Shop-ID') ?? 1; // Default to shop 1 for now
            
            $banners = Banner::where('shop_id', $shopId)
                ->active()
                ->ordered()
                ->get()
                ->map(function ($banner) {
                    return [
                        'id' => $banner->id,
                        'title' => $banner->title,
                        'url' => $banner->image ? asset('storage/' . $banner->image) : null,
                        'link_url' => $banner->url,
                        'sort_order' => $banner->sort_order,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $banners,
                'message' => 'Banners retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve banners',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}