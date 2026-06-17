<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\Availability;
use App\Models\Property;
use App\Models\PropertyPhoto;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PropertyJesoloSeeder extends Seeder
{
    public function run(): void
    {
        $property = Property::updateOrCreate(
            ['slug' => 'villa-pineta-jesolo'],
            [
                'title' => 'Villa nella Pineta — Jesolo',
                'subtitle' => 'Immersa tra i pini marittimi, a due passi dal mare',
                'type' => 'villa',
                'status' => 'published',
                'address' => 'Viale Belgio 161',
                'city' => 'Lido di Jesolo',
                'region' => 'Veneto',
                'country' => 'IT',
                'max_guests' => 6,
                'bedrooms' => 3,
                'beds' => 4,
                'bathrooms' => 1,
                // Prezzi PLACEHOLDER — da confermare con il proprietario
                'base_price' => 150,
                'cleaning_fee' => 60,
                'min_nights' => 3,
                'check_in_time' => '16:00',
                'check_out_time' => '10:00',
                'sort_order' => 0,
                'description' => implode("\n\n", [
                    "Villa indipendente immersa nella quiete della pineta di Jesolo, in Viale Belgio. Una vacanza all'insegna del relax, tra i profumi dei pini marittimi e a pochi minuti dalla spiaggia.",
                    "La casa dispone di 3 camere da letto (fino a 6 posti letto), ampio soggiorno con zona pranzo, cucina attrezzata e bagno con doccia. Climatizzata, luminosa e curata in ogni dettaglio.",
                    "All'esterno un grande giardino privato recintato con portico coperto, lettini prendisole e zona pranzo all'aperto. 3 posti auto privati all'interno della proprietà.",
                    "La posizione ideale per famiglie e gruppi che cercano comodità, natura e mare.",
                ]),
            ],
        );

        // Photos
        $property->photos()->delete();
        for ($i = 1; $i <= 16; $i++) {
            $n = sprintf('%02d', $i);
            PropertyPhoto::create([
                'property_id' => $property->id,
                'path' => "properties/villa-pineta-jesolo/{$n}.jpg",
                'sort_order' => $i,
                'is_cover' => $i === 1,
            ]);
        }

        // Amenities (visibili dalle foto / dichiarate)
        $slugs = ['wi-fi', 'aria-condizionata', 'cucina-attrezzata', 'parcheggio-gratuito', 'tv', 'balcone-terrazza', 'lavatrice'];
        $ids = Amenity::whereIn('slug', $slugs)->pluck('id')->all();
        $property->amenities()->sync($ids);

        // Availability — 365 giorni
        Availability::where('property_id', $property->id)->delete();
        $rows = [];
        foreach (CarbonPeriod::create(Carbon::today(), Carbon::today()->addDays(365)) as $day) {
            $rows[] = [
                'property_id' => $property->id,
                'date' => $day->toDateString(),
                'status' => 'available',
                'price' => $day->isWeekend() ? 180 : 150,
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            Availability::insert($chunk);
        }

        // I 5 immobili demo restano nel DB ma in bozza (non pubblici)
        Property::where('slug', '!=', 'villa-pineta-jesolo')->update(['status' => 'draft']);
    }
}
