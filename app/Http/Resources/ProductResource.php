<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'cost_price' => (float) $this->cost_price,
            'stock' => (int) $this->stock,
            'shop' => $this->when($this->relationLoaded('shop'), [
                'id' => $this->shop?->id,
                'name' => $this->shop?->name,
            ]),
            'category' => $this->when($this->relationLoaded('category'), [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            'images' => $this->when($this->relationLoaded('variants'), function () {
                $images = [];
                foreach ($this->variants as $variant) {
                    if ($variant->relationLoaded('images')) {
                        foreach ($variant->images as $image) {
                            $images[] = [
                                'id' => $image->id,
                                'url' => $this->getImageUrl($image->image_path),
                                'alt_text' => $image->alt_text ?? $this->name,
                            ];
                        }
                    }
                }
                return $images;
            }),
            'variants' => $this->when($this->relationLoaded('variants'), function () {
                return $this->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'price' => $variant->price ? (float) $variant->price : null,
                        'stock' => $variant->stock ? (int) $variant->stock : null,
                        'attributes' => $variant->relationLoaded('attributes') 
                            ? $variant->attributes->map(function ($attribute) {
                                return [
                                    'id' => $attribute->id,
                                    'name' => $attribute->name,
                                    'value' => $attribute->value,
                                ];
                            }) 
                            : [],
                        'images' => $variant->relationLoaded('images')
                            ? $variant->images->map(function ($image) {
                                return [
                                    'id' => $image->id,
                                    'url' => $this->getImageUrl($image->image_path),
                                    'alt_text' => $image->alt_text ?? $this->name,
                                ];
                            })
                            : [],
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Generate full URL for image path
     */
    private function getImageUrl(?string $imagePath): string
    {
        if (!$imagePath) {
            // Return a placeholder image URL when no image is available
            return url('images/placeholder-product.svg');
        }

        // If it's already a full URL, return as is
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        // Generate full URL from relative path
        return url('storage/' . ltrim($imagePath, '/'));
    }
}