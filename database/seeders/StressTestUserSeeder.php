<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StressTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 150; $i++) {
            User::factory()->create([
                'name' => "Stress User {$i}",
                'email' => "stress_user_{$i}@test.com",
                'password' => Hash::make('Test@1234'),
            ]);
        }
    }
}
