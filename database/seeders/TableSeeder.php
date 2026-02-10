<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [];
        
        // Generate 15 meja dengan kapasitas bervariasi
        for ($i = 1; $i <= 15; $i++) {
            $tables[] = [
                'table_number' => 'T' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'capacity' => $i <= 5 ? 2 : ($i <= 10 ? 4 : 6),
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('tables')->insert($tables);
    }
}
