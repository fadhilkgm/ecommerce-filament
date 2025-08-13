<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContentManagement;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function getBannerImages()
    {
        $banners = ContentManagement::enabled()
            ->byCode('BANNER_IMAGES')
            ->ordered()
            ->first();

        if (!$banners || !$banners->images) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $images = collect($banners->images)->map(function ($image) use ($banners) {
            return [
                'url' => asset('storage/' . $image),
                'link_url' => $banners->link_url,
                'title' => $banners->title,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    public function getContentByCode($code)
    {
        $content = ContentManagement::enabled()
            ->byCode(strtoupper($code))
            ->first();

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'title' => $content->title,
                'content' => $content->content,
                'type' => $content->type,
                'images' => $content->images ? collect($content->images)->map(fn($img) => asset('storage/' . $img)) : null,
                'meta_data' => $content->meta_data,
            ]
        ]);
    }

    public function getAllContent()
    {
        $contents = ContentManagement::enabled()
            ->ordered()
            ->get()
            ->map(function ($content) {
                return [
                    'code' => $content->code,
                    'title' => $content->title,
                    'type' => $content->type,
                    'content' => $content->content,
                    'images' => $content->images ? collect($content->images)->map(fn($img) => asset('storage/' . $img)) : null,
                    'link_url' => $content->link_url,
                    'meta_data' => $content->meta_data,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $contents
        ]);
    }
}
