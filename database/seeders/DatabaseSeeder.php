<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@hostup.it'],
            ['name' => 'Admin HostUp', 'password' => Hash::make('password')],
        );

        // Solo dati di riferimento essenziali (no immobili demo in produzione).
        // Per caricare i 5 immobili demo: php artisan db:seed --class=DemoSeeder
        $this->call([
            ChannelSeeder::class,
            AmenitySeeder::class,
        ]);
    }
}
