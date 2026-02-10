<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $foods = [
            // Makanan
            [
                'name' => 'Nasi Goreng',
                'category' => 'food',
                'price' => 25000,
                'is_available' => true,
                'description' => 'Nasi goreng spesial dengan telur dan ayam',
            ],
            [
                'name' => 'Mie Goreng',
                'category' => 'food',
                'price' => 22000,
                'is_available' => true,
                'description' => 'Mie goreng dengan sayuran segar',
            ],
            [
                'name' => 'Capcay',
                'category' => 'food',
                'price' => 28000,
                'is_available' => true,
                'description' => 'Tumis sayuran dengan saus spesial',
            ],
            [
                'name' => 'Ayam Goreng',
                'category' => 'food',
                'price' => 30000,
                'is_available' => true,
                'description' => 'Ayam goreng renyah dengan nasi',
            ],
            [
                'name' => 'Sate Ayam',
                'category' => 'food',
                'price' => 35000,
                'is_available' => true,
                'description' => 'Sate ayam 10 tusuk dengan bumbu kacang',
            ],
            [
                'name' => 'Gado-Gado',
                'category' => 'food',
                'price' => 20000,
                'is_available' => true,
                'description' => 'Sayuran dengan bumbu kacang',
            ],
            
            // Minuman
            [
                'name' => 'Es Teh Manis',
                'category' => 'beverage',
                'price' => 5000,
                'is_available' => true,
                'description' => 'Teh manis dingin',
            ],
            [
                'name' => 'Es Jeruk',
                'category' => 'beverage',
                'price' => 8000,
                'is_available' => true,
                'description' => 'Jus jeruk segar',
            ],
            [
                'name' => 'Kopi Hitam',
                'category' => 'beverage',
                'price' => 10000,
                'is_available' => true,
                'description' => 'Kopi hitam original',
            ],
            [
                'name' => 'Cappuccino',
                'category' => 'beverage',
                'price' => 18000,
                'is_available' => true,
                'description' => 'Kopi susu dengan foam',
            ],
            [
                'name' => 'Jus Alpukat',
                'category' => 'beverage',
                'price' => 15000,
                'is_available' => true,
                'description' => 'Jus alpukat segar',
            ],
            [
                'name' => 'Air Mineral',
                'category' => 'beverage',
                'price' => 5000,
                'is_available' => true,
                'description' => 'Air mineral botol',
            ],
            
            // Dessert
            [
                'name' => 'Es Krim Vanilla',
                'category' => 'dessert',
                'price' => 12000,
                'is_available' => true,
                'description' => 'Es krim vanilla 2 scoop',
            ],
            [
                'name' => 'Pisang Goreng',
                'category' => 'dessert',
                'price' => 10000,
                'is_available' => true,
                'description' => 'Pisang goreng crispy dengan madu',
            ],
            [
                'name' => 'Puding Coklat',
                'category' => 'dessert',
                'price' => 8000,
                'is_available' => true,
                'description' => 'Puding coklat lembut',
            ],
        ];

        foreach ($foods as $food) {
            $food['created_at'] = now();
            $food['updated_at'] = now();
            DB::table('foods')->insert($food);
        }
    }
}
