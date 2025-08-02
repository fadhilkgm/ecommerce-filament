<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\ProductVariantAttribute;
use App\Models\ProductVariantImage;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing shop or create default jewelry shop
        $shop = Shop::first() ?? Shop::create([
            'name' => 'YAAL Jewelry',
            'description' => "Exquisite handcrafted jewelry for life's precious moments"
        ]);

        // Create jewelry categories without images (images will be null initially)
        $categories = [
            [
                'name' => 'Rings',
                'description' => 'Engagement rings, wedding bands, and statement pieces'
            ],
            [
                'name' => 'Necklaces',
                'description' => 'Elegant necklaces, pendants, and chains'
            ],
            [
                'name' => 'Earrings',
                'description' => 'Studs, hoops, and drop earrings'
            ],
            [
                'name' => 'Bracelets',
                'description' => 'Delicate bracelets and bangles'
            ]
        ];

        $createdCategories = [];
        foreach ($categories as $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'image' => null, // Set images to null initially
                'shop_id' => $shop->id,
            ]);
            $createdCategories[$categoryData['name']] = $category;
        }

        // Create jewelry products for each category without images (images will be null initially)
        $products = [
            // Rings
            ['name' => 'Classic Diamond Solitaire Ring', 'price' => 2499.99, 'cost_price' => 1800.0, 'stock' => 5, 'category' => 'Rings'],
            ['name' => 'Vintage Rose Gold Band', 'price' => 899.99, 'cost_price' => 650.0, 'stock' => 8, 'category' => 'Rings'],
            ['name' => 'Emerald Cut Engagement Ring', 'price' => 3299.99, 'cost_price' => 2400.0, 'stock' => 3, 'category' => 'Rings'],
            ['name' => 'Simple Gold Wedding Band', 'price' => 599.99, 'cost_price' => 420.0, 'stock' => 12, 'category' => 'Rings'],
            ['name' => 'Sapphire Halo Ring', 'price' => 1899.99, 'cost_price' => 1350.0, 'stock' => 6, 'category' => 'Rings'],
            ['name' => 'Stackable Diamond Ring', 'price' => 799.99, 'cost_price' => 560.0, 'stock' => 10, 'category' => 'Rings'],
            // Necklaces
            ['name' => 'Pearl Strand Necklace', 'price' => 1299.99, 'cost_price' => 900.0, 'stock' => 7, 'category' => 'Necklaces'],
            ['name' => 'Diamond Tennis Necklace', 'price' => 4599.99, 'cost_price' => 3200.0, 'stock' => 2, 'category' => 'Necklaces'],
            ['name' => 'Gold Chain Necklace', 'price' => 699.99, 'cost_price' => 480.0, 'stock' => 15, 'category' => 'Necklaces'],
            ['name' => 'Heart Pendant Necklace', 'price' => 449.99, 'cost_price' => 310.0, 'stock' => 20, 'category' => 'Necklaces'],
            ['name' => 'Layered Chain Set', 'price' => 299.99, 'cost_price' => 200.0, 'stock' => 25, 'category' => 'Necklaces'],
            ['name' => 'Vintage Locket Necklace', 'price' => 899.99, 'cost_price' => 630.0, 'stock' => 8, 'category' => 'Necklaces'],
            // Earrings
            ['name' => 'Diamond Stud Earrings', 'price' => 1599.99, 'cost_price' => 1120.0, 'stock' => 12, 'category' => 'Earrings'],
            ['name' => 'Gold Hoop Earrings', 'price' => 399.99, 'cost_price' => 280.0, 'stock' => 18, 'category' => 'Earrings'],
            ['name' => 'Pearl Drop Earrings', 'price' => 699.99, 'cost_price' => 490.0, 'stock' => 10, 'category' => 'Earrings'],
            ['name' => 'Chandelier Earrings', 'price' => 1299.99, 'cost_price' => 910.0, 'stock' => 6, 'category' => 'Earrings'],
            ['name' => 'Simple Silver Studs', 'price' => 199.99, 'cost_price' => 140.0, 'stock' => 30, 'category' => 'Earrings'],
            ['name' => 'Gemstone Drop Earrings', 'price' => 899.99, 'cost_price' => 630.0, 'stock' => 8, 'category' => 'Earrings'],
            // Bracelets
            ['name' => 'Tennis Bracelet', 'price' => 2299.99, 'cost_price' => 1610.0, 'stock' => 4, 'category' => 'Bracelets'],
            ['name' => 'Gold Chain Bracelet', 'price' => 599.99, 'cost_price' => 420.0, 'stock' => 15, 'category' => 'Bracelets'],
            ['name' => 'Charm Bracelet', 'price' => 799.99, 'cost_price' => 560.0, 'stock' => 12, 'category' => 'Bracelets'],
            ['name' => 'Pearl Bracelet', 'price' => 499.99, 'cost_price' => 350.0, 'stock' => 18, 'category' => 'Bracelets'],
            ['name' => 'Cuff Bracelet', 'price' => 399.99, 'cost_price' => 280.0, 'stock' => 20, 'category' => 'Bracelets'],
            ['name' => 'Delicate Chain Bracelet', 'price' => 299.99, 'cost_price' => 210.0, 'stock' => 25, 'category' => 'Bracelets'],
        ];

        // Get product attributes
        $colorAttribute = ProductAttribute::where('name', 'color')->first();
        $sizeAttribute = ProductAttribute::where('name', 'size')->first();

        foreach ($products as $index => $productData) {
            // Create product with a primary image
            $product = Product::create([
                'name' => $productData['name'],
                'price' => $productData['price'],
                'cost_price' => $productData['cost_price'],
                'stock' => $productData['stock'],
                'image' => "https://via.placeholder.com/600x600/D4AF37/FFFFFF?text=" . urlencode($productData['name']),
                'shop_id' => $shop->id,
                'category_id' => $createdCategories[$productData['category']]->id,
                'user_id' => 1,  // Assuming user ID 1 exists
            ]);

            // Create multiple variants for some products to showcase variant functionality
            if ($index < 6) { // First 6 products get multiple variants
                $colors = ['gold', 'silver', 'rose-gold'];
                $sizes = ['S', 'M', 'L'];
                
                $variantIndex = 1;
                foreach ($colors as $color) {
                    foreach ($sizes as $size) {
                        $variant = ProductVariant::create([
                            'product_id' => $product->id,
                            'sku' => "SKU-{$product->id}-{$variantIndex}",
                            'price' => $productData['price'] + ($color === 'rose-gold' ? 100 : 0),
                            'stock' => rand(2, 8),
                            'shop_id' => $shop->id,
                        ]);

                        // Add color attribute
                        if ($colorAttribute) {
                            ProductVariantAttribute::create([
                                'product_variant_id' => $variant->id,
                                'product_attribute_id' => $colorAttribute->id,
                                'value' => $color,
                            ]);
                        }

                        // Add size attribute
                        if ($sizeAttribute) {
                            ProductVariantAttribute::create([
                                'product_variant_id' => $variant->id,
                                'product_attribute_id' => $sizeAttribute->id,
                                'value' => $size,
                            ]);
                        }

                        // Add sample images for variants (using placeholder URLs)
                        ProductVariantImage::create([
                            'product_variant_id' => $variant->id,
                            'name' => "{$product->name} - {$color} {$size}",
                            'alt' => "{$product->name} in {$color} color, size {$size}",
                            'images' => [
                                "https://via.placeholder.com/600x600/D4AF37/FFFFFF?text=Jewelry+{$variantIndex}",
                                "https://via.placeholder.com/600x600/C0C0C0/000000?text=Jewelry+{$variantIndex}",
                            ],
                        ]);

                        $variantIndex++;
                    }
                }
            } else {
                // Create single variant for other products
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => "SKU-{$product->id}-001",
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'shop_id' => $shop->id,
                ]);

                // Add default attributes
                if ($colorAttribute) {
                    ProductVariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'product_attribute_id' => $colorAttribute->id,
                        'value' => 'gold',
                    ]);
                }

                // Add sample images (using placeholder URLs)
                ProductVariantImage::create([
                    'product_variant_id' => $variant->id,
                    'name' => $product->name,
                    'alt' => $product->name,
                    'images' => [
                        "https://via.placeholder.com/600x600/D4AF37/FFFFFF?text=Jewelry+Product",
                        "https://via.placeholder.com/600x600/C0C0C0/000000?text=Jewelry+Product",
                    ],
                ]);
            }
        }

        $this->command->info('Created 4 jewelry categories and 24 jewelry products successfully!');
    }
}
