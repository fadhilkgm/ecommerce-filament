<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\Setting;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip if product with same name already exists
        $existingProduct = Product::where('name', $row['name'])
            ->where('shop_id', Filament::getTenant()->id)
            ->first();
            
        if ($existingProduct) {
            return null;
        }

        // Find or create category
        $category = Category::firstOrCreate([
            'name' => $row['category'] ?? 'Uncategorized', 
            'shop_id' => Filament::getTenant()->id
        ]);

        // Check product property setting
        $setting = Setting::where('code', 'PRODUCT_PROPERTY')->first();
        $useVariants = $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;

        // Create product directly
        $product = Product::create([
            'name' => $row['name'] ?? 'Unknown Product',
            'price' => $row['price'] ?? 0,
            'cost_price' => $row['cost_price'] ?? null,
            'category_id' => $category->id,
            'shop_id' => Filament::getTenant()->id,
            'user_id' => auth()->id() ?? 1,
            'stock' => !$useVariants ? ($row['stock'] ?? 0) : null,
        ]);

        // Create variant if using variants
        if ($useVariants) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => Str::slug($row['name']) . '-' . random_int(1000, 9999),
                'stock' => $row['stock'] ?? 0,
                'shop_id' => Filament::getTenant()->id,
            ]);
        }

        return $product;
    }
}