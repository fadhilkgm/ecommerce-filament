<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
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

        $settings = [
            [
                'name' => 'Product Property',
                'code' => 'PRODUCT_PROPERTY',
                'type' => 'boolean',
                'value' => 'false',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create([
                'name' => $setting['name'],
                'code' => $setting['code'],
                'type' => $setting['type'],
                'value' => $setting['value'],
                'shop_id' => $shop->id,
            ]);
        }
    }
}

