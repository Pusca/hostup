<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Availability;
use App\Models\Property;
use App\Models\PropertyPhoto;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $amenityIds = Amenity::pluck('id')->all();

        $properties = [
            [
                'title' => 'Attico Vista Mare — Polignano',
                'subtitle' => 'Terrazza panoramica sulle grotte',
                'type' => 'apartment',
                'city' => 'Polignano a Mare',
                'region' => 'Puglia',
                'max_guests' => 4, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 2,
                'base_price' => 180, 'cleaning_fee' => 45, 'min_nights' => 2,
                'description' => "Attico luminoso a pochi passi dal centro storico, con terrazza privata affacciata sul mare. Arredi di design, cucina completa e tutti i comfort per una vacanza indimenticabile.",
            ],
            [
                'title' => 'Trullo del Ulivo — Valle d\'Itria',
                'subtitle' => 'Autenticità pugliese tra gli ulivi',
                'type' => 'villa',
                'city' => 'Locorotondo',
                'region' => 'Puglia',
                'max_guests' => 6, 'bedrooms' => 3, 'beds' => 4, 'bathrooms' => 2,
                'base_price' => 240, 'cleaning_fee' => 60, 'min_nights' => 3,
                'description' => "Antico trullo ristrutturato con piscina privata immerso negli ulivi secolari. L'esperienza perfetta per chi cerca relax, natura e tradizione.",
            ],
            [
                'title' => 'Loft di Design — Centro Storico',
                'subtitle' => 'Stile e comodità nel cuore della città',
                'type' => 'loft',
                'city' => 'Lecce',
                'region' => 'Puglia',
                'max_guests' => 2, 'bedrooms' => 1, 'beds' => 1, 'bathrooms' => 1,
                'base_price' => 110, 'cleaning_fee' => 30, 'min_nights' => 1,
                'description' => "Loft elegante a due passi da Piazza Sant'Oronzo. Ideale per coppie, perfetto per scoprire la Firenze del Sud a piedi.",
            ],
            [
                'title' => 'Villa Ginestra — con Piscina',
                'subtitle' => 'Privacy e comfort per famiglie',
                'type' => 'villa',
                'city' => 'Ostuni',
                'region' => 'Puglia',
                'max_guests' => 8, 'bedrooms' => 4, 'beds' => 5, 'bathrooms' => 3,
                'base_price' => 320, 'cleaning_fee' => 80, 'min_nights' => 4,
                'description' => "Ampia villa con giardino e piscina a sfioro vista Città Bianca. Spazi generosi, barbecue e zona pranzo all'aperto per momenti in famiglia.",
            ],
            [
                'title' => 'Dimora Marina — Fronte Porto',
                'subtitle' => 'Risvegli sul mare ogni mattina',
                'type' => 'apartment',
                'city' => 'Gallipoli',
                'region' => 'Puglia',
                'max_guests' => 5, 'bedrooms' => 2, 'beds' => 3, 'bathrooms' => 2,
                'base_price' => 150, 'cleaning_fee' => 40, 'min_nights' => 2,
                'description' => "Appartamento fronte porto con balcone vista barche. A pochi minuti dalle spiagge più belle del Salento.",
            ],
        ];

        $sort = 0;
        foreach ($properties as $i => $data) {
            $property = Property::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($data['title'])],
                array_merge($data, ['status' => 'published', 'sort_order' => $sort++]),
            );

            // Photos (reliable placeholder source, varied per property)
            $property->photos()->delete();
            for ($p = 1; $p <= 6; $p++) {
                PropertyPhoto::create([
                    'property_id' => $property->id,
                    'path' => "https://picsum.photos/seed/hostup{$property->id}-{$p}/1200/800",
                    'sort_order' => $p,
                    'is_cover' => $p === 1,
                ]);
            }

            // Amenities (random-ish subset)
            $property->amenities()->sync(
                collect($amenityIds)->shuffle()->take(7)->all()
            );

            $this->seedAvailability($property);
        }
    }

    private function seedAvailability(Property $property): void
    {
        $start = Carbon::today();
        $end = Carbon::today()->addDays(365);

        Availability::where('property_id', $property->id)->delete();

        $rows = [];
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $isWeekend = $day->isWeekend();
            $price = (float) $property->base_price * ($isWeekend ? 1.25 : 1.0);

            $rows[] = [
                'property_id' => $property->id,
                'date' => $day->toDateString(),
                'status' => 'available',
                'price' => round($price, 2),
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert in chunks
        foreach (array_chunk($rows, 200) as $chunk) {
            Availability::insert($chunk);
        }
    }
}
