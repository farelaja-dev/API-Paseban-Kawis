<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeder untuk Role
        Role::insert([
            ['id' => 1, 'nama_role' => 'admin'],
            ['id' => 2, 'nama_role' => 'user']
        ]);

        // Seeder untuk User
        User::insert([
            [
                'nama' => 'izuna',
                'email' => 'farelmaestro2@gmail.com',
                'password' => Hash::make('izuna123'),
                'role_id' => 1,
                'telepon' => '0811111111',
                'foto' => 'https://example.com/foto1.jpg',
            ],
            [
                'nama' => 'izuna2',
                'email' => 'farelmaestro3@gmail.com',
                'password' => Hash::make('izuna1234'),
                'role_id' => 2,
                'telepon' => '08123456789',
                'foto' => 'https://example.com/foto2.jpg',
            ]
        ]);
    }
}
