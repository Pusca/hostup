<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            'Wi-Fi' => '📶',
            'Aria condizionata' => '❄️',
            'Cucina attrezzata' => '🍳',
            'Parcheggio gratuito' => '🅿️',
            'Lavatrice' => '🧺',
            'TV' => '📺',
            'Riscaldamento' => '🔥',
            'Piscina' => '🏊',
            'Vista mare' => '🌊',
            'Animali ammessi' => '🐾',
            'Balcone / Terrazza' => '🌅',
            'Self check-in' => '🔑',
        ];

        foreach ($amenities as $name => $icon) {
            Amenity::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'icon' => $icon],
            );
        }
    }
}
