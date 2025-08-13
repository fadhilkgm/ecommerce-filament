<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shops = Shop::all();
        
        foreach ($shops as $shop) {
            $this->createDefaultSettings($shop->id);
        }
    }

    private function createDefaultSettings(int $shopId): void
    {
        $defaultSettings = [
            // Company Information
            [
                'key' => 'company_name',
                'value' => 'YAAL Jewelry',
                'type' => 'string',
            ],
            [
                'key' => 'company_address',
                'value' => "123 Business Street\nSuite 100\nNew York, NY 10001\nUnited States",
                'type' => 'text',
            ],
            [
                'key' => 'company_phone',
                'value' => '+1 (555) 123-4567',
                'type' => 'string',
            ],
            [
                'key' => 'company_email',
                'value' => 'support@yaal.com',
                'type' => 'string',
            ],
            [
                'key' => 'company_website',
                'value' => 'https://yaal.com',
                'type' => 'string',
            ],

            // Inventory Management
            [
                'key' => 'use_stock_management',
                'value' => '1',
                'type' => 'boolean',
            ],
            [
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'integer',
            ],
            [
                'key' => 'allow_backorders',
                'value' => '0',
                'type' => 'boolean',
            ],

            // Shipping & Delivery
            [
                'key' => 'free_shipping_threshold',
                'value' => '100.00',
                'type' => 'decimal',
            ],
            [
                'key' => 'default_shipping_cost',
                'value' => '15.00',
                'type' => 'decimal',
            ],

            // Payment Settings
            [
                'key' => 'payment_gateway_enabled',
                'value' => '0',
                'type' => 'boolean',
            ],
            [
                'key' => 'TAX_ENABLED',
                'value' => '0',
                'type' => 'boolean',
            ],
            [
                'key' => 'tax_rate',
                'value' => '0.08',
                'type' => 'decimal',
            ],

            // Notifications
            [
                'key' => 'send_order_confirmations',
                'value' => '1',
                'type' => 'boolean',
            ],
            [
                'key' => 'send_payment_notifications',
                'value' => '1',
                'type' => 'boolean',
            ],
            [
                'key' => 'low_stock_notifications',
                'value' => '1',
                'type' => 'boolean',
            ],

            // General Settings
            [
                'key' => 'currency',
                'value' => 'USD',
                'type' => 'string',
            ],
            [
                'key' => 'UPI_LINK',
                'value' => 'upi://pay?pa=fadhilkgm64@oksbi&pn=Fadhil&aid=uGICAgMDgooyCDw',
                'type' => 'string',
            ],
            [
                'key' => 'timezone',
                'value' => 'America/New_York',
                'type' => 'string',
            ],
        ];

        foreach ($defaultSettings as $setting) {
            CompanySetting::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'key' => $setting['key']
                ],
                $setting
            );
        }
    }
}