<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(998)->create();
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ]);

        User::factory()->create([
            'name' => 'Normal User',
            'email' => 'user@example.com',
            'role' => 'User',
        ]);
    }
}
