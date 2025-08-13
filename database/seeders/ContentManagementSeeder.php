<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ContentManagement::create([
            'title' => 'Homepage Banner Images',
            'code' => 'BANNER_IMAGES',
            'type' => 'banner',
            'content' => null,
            'images' => [], // Will be populated via admin panel
            'link_url' => null,
            'sort_order' => 1,
            'is_enabled' => true,
        ]);

        \App\Models\ContentManagement::create([
            'title' => 'Privacy Policy',
            'code' => 'PRIVACY_POLICY',
            'type' => 'content',
            'content' => '<h1>Privacy Policy</h1><p>Your privacy is important to us. This privacy policy explains how we collect, use, and protect your information.</p>',
            'images' => null,
            'link_url' => null,
            'sort_order' => 2,
            'is_enabled' => true,
        ]);

        \App\Models\ContentManagement::create([
            'title' => 'Terms and Conditions',
            'code' => 'TERMS_CONDITIONS',
            'type' => 'content',
            'content' => '<h1>Terms and Conditions</h1><p>By using our service, you agree to these terms and conditions.</p>',
            'images' => null,
            'link_url' => null,
            'sort_order' => 3,
            'is_enabled' => true,
        ]);

        \App\Models\ContentManagement::create([
            'title' => 'Return Policy',
            'code' => 'RETURN_POLICY',
            'type' => 'content',
            'content' => '<h1>Return Policy</h1><p>We accept returns within 30 days of purchase. Items must be in original condition.</p>',
            'images' => null,
            'link_url' => null,
            'sort_order' => 4,
            'is_enabled' => true,
        ]);

        \App\Models\ContentManagement::create([
            'title' => 'Shipping Information',
            'code' => 'SHIPPING_INFO',
            'type' => 'content',
            'content' => '<h1>Shipping Information</h1><p>We offer fast and reliable shipping options to get your orders to you quickly.</p>',
            'images' => null,
            'link_url' => null,
            'sort_order' => 5,
            'is_enabled' => true,
        ]);
    }
}
