<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
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

        User::factory()->create([
            'nama' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::create([
            'nama' => 'izuna',
            'email' => 'farelmaestro2@gmail.com',
            'password' => Hash::make('izuna123'),
            'role_id' => 1, // pastikan role_id 1 ada, atau sesuaikan jika perlu
        ]);
    }
}
