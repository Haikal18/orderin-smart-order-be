<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed dalam urutan yang benar (foreign key dependencies)
        $this->call([
            UserSeeder::class,
            TableSeeder::class,
            FoodSeeder::class,
        ]);
    }
}
