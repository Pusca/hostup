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

        $this->call([
            ChannelSeeder::class,
            AmenitySeeder::class,
            DemoSeeder::class,
        ]);
    }
}
