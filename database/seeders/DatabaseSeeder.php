<?php

namespace Database\Seeders;

use App\Models\ProductAttribute;
use App\Models\Shop;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@gmail.com',
            'is_admin'=>true,
            'password' => bcrypt('superadmin12345'),
        ]);
        $tenant = Shop::create([
            'name' => 'SAGEY',
            'slug' => 'SAGEY',
            'location'=> 'KKD',
            'phone' => '1234567890',
        ]);

        $tenant->users()->attach($user);

        $this->call([
            MasterDataSeeder::class,
            SettingSeeder::class,
        ]);

        ProductAttribute::create([
            'name'=>'color',
            'shop_id'=>$tenant->id,
            'master_data'=>['red','blue','green','white','black']
        ]);
        ProductAttribute::create([
            'name'=>'size',
            'shop_id'=>$tenant->id,
            'master_data'=>['S','M','L','XL','XXL']
        ]);
    }
}
