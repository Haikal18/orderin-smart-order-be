<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Budi Pelayan',
                'email' => 'pelayan@orderin.com',
                'password' => Hash::make('password'),
                'role' => 'pelayan',
            ],
            [
                'name' => 'Siti Pelayan',
                'email' => 'pelayan2@orderin.com',
                'password' => Hash::make('password'),
                'role' => 'pelayan',
            ],
            [
                'name' => 'Andi Kasir',
                'email' => 'kasir@orderin.com',
                'password' => Hash::make('password'),
                'role' => 'kasir',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
