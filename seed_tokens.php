<?php
// seed_tokens.php

require '/var/www/vendor/autoload.php';

$app = require '/var/www/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Wallet;

$lines = [];

for ($i = 1; $i <= 100; $i++) {
    $user = User::firstOrCreate(
        ['email' => "loadtest{$i}@test.local"],
        [
            'name' => "LoadTest {$i}",
            'password' => bcrypt('Password123!')
        ]
    );

    Wallet::updateOrCreate(
        ['user_id' => $user->id],
        [
            'balance' => 10000000,
            'is_active' => true
        ]
    );

    $lines[] = $user->createToken('loadtest')->plainTextToken;
}

$filePath = '/var/www/tokens.csv';
$directory = dirname($filePath);
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}

if (!file_exists($filePath)) {
    touch($filePath);
}

file_put_contents($filePath, implode("\n", $lines) . "\n");

echo "تم إنشاء " . count($lines) . " مستخدماً + محفظة + توكن بنجاح\n";
