<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            ['code' => 'direct',  'name' => 'Prenotazione diretta', 'color' => '#0ea5e9'],
            ['code' => 'airbnb',  'name' => 'Airbnb',               'color' => '#ff385c'],
            ['code' => 'booking', 'name' => 'Booking.com',          'color' => '#003580'],
            ['code' => 'vrbo',    'name' => 'Vrbo',                 'color' => '#1668e3'],
        ];

        foreach ($channels as $c) {
            Channel::updateOrCreate(['code' => $c['code']], $c);
        }
    }
}
