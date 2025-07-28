<?php

namespace Database\Seeders;

use App\Models\MasterData;
use App\Models\MasterDataValue;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shop = Shop::first();

        if (!$shop) {
            $this->command->error('No shop found. Please run DatabaseSeeder first.');
            return;
        }

        // Color Master Data
        $colorMaster = MasterData::create([
            'name' => 'Color',
            'code' => 'COLOR',
            'type' => 'LST',
            'shop_id' => $shop->id,
        ]);

        $colors = ['Red', 'Blue', 'Green', 'White', 'Black', 'Yellow', 'Purple', 'Orange'];
        foreach ($colors as $color) {
            MasterDataValue::create([
                'master_data_id' => $colorMaster->id,
                'master_data_code' => 'COLOR',
                'value' => strtoupper($color),
                'description' => $color,
                'status' => true,
                'shop_id' => $shop->id,
            ]);
        }

        // Size Master Data
        $sizeMaster = MasterData::create([
            'name' => 'Size',
            'code' => 'SIZE',
            'type' => 'LST',
            'shop_id' => $shop->id,
        ]);

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $size) {
            MasterDataValue::create([
                'master_data_id' => $sizeMaster->id,
                'master_data_code' => 'SIZE',
                'value' => $size,
                'description' => $size,
                'status' => true,
                'shop_id' => $shop->id,
            ]);
        }

        // Brand Master Data
        $brandMaster = MasterData::create([
            'name' => 'Brand',
            'code' => 'BRAND',
            'type' => 'LST',
            'shop_id' => $shop->id,
        ]);

        $brands = ['Nike', 'Adidas', 'Puma', 'Reebok', 'Under Armour'];
        foreach ($brands as $brand) {
            MasterDataValue::create([
                'master_data_id' => $brandMaster->id,
                'master_data_code' => 'BRAND',
                'value' => strtoupper($brand),
                'description' => $brand,
                'status' => true,
                'shop_id' => $shop->id,
            ]);
        }

        // Category Master Data
        $categoryMaster = MasterData::create([
            'name' => 'Product Category',
            'code' => 'PRODUCT_CATEGORY',
            'type' => 'LST',
            'shop_id' => $shop->id,
        ]);

        $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports'];
        foreach ($categories as $category) {
            MasterDataValue::create([
                'master_data_id' => $categoryMaster->id,
                'master_data_code' => 'PRODUCT_CATEGORY',
                'value' => strtoupper(str_replace(' ', '_', $category)),
                'description' => $category,
                'status' => true,
                'shop_id' => $shop->id,
            ]);
        }

        // Expense Type Master Data
        $expenseTypeMaster = MasterData::create([
            'name' => 'Expense Type',
            'code' => 'EXPENSE_TYPE',
            'type' => 'LST',
            'shop_id' => $shop->id,
        ]);

        $expenseTypes = ['Direct', 'Indirect'];
        foreach ($expenseTypes as $type) {
            MasterDataValue::create([
                'master_data_id' => $expenseTypeMaster->id,
                'master_data_code' => 'EXPENSE_TYPE',
                'value' => strtoupper($type),
                'description' => $type,
                'status' => true,
                'shop_id' => $shop->id,
            ]);
        }

        // Create Expense Category Master Data
        $expenseCategory = MasterData::create([
            'name' => 'Expense Category',
            'code' => 'EXPENSE_CATEGORY',
            'type' => 'LST',
            'shop_id' => $shop->id,
        ]);

        // Add expense category values
        $categories = [
            'Office',
            'Electricity', 
            'Stationary',
            'Kuri',
            'Petrol'
        ];

        foreach ($categories as $category) {
            MasterDataValue::create([
                'master_data_id' => $expenseCategory->id,
                'master_data_code' => 'EXPENSE_CATEGORY',
                'value' => strtoupper($category),
                'description' => $category,
                'status' => true,
                'shop_id' => $shop->id,
            ]);
        }
    }
}
