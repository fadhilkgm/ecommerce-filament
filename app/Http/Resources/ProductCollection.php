<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => $this->collection,
            'meta' => [
                'pagination' => [
                    'current_page' => $this->currentPage(),
                    'last_page' => $this->lastPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem(),
                    'has_more_pages' => $this->hasMorePages(),
                ],
                'filters' => [
                    'shop_id' => $request->get('shop_id'),
                    'category_id' => $request->get('category_id'),
                    'search' => $request->get('search'),
                ],
            ],
        ];
    }
}