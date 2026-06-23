<?php

namespace App\Services\Geo;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    /**
     * Geocodifica un immobile provando più varianti dell'indirizzo, dalla più
     * precisa (via + numero) alla città. Vincola al paese e gestisce i prefissi
     * tipici italiani ("Lido di", "Marina di"). Best-effort: null se nulla matcha.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocodeAddress(?string $address, ?string $city, ?string $region, ?string $country): ?array
    {
        $country = $country ? strtolower(trim($country)) : null;
        $cityCore = $city ? trim(preg_replace('/^(lido di|marina di|lido|porto)\s+/i', '', $city)) : null;

        $candidates = array_values(array_unique(array_filter([
            $this->join([$address, $city]),
            $this->join([$address, $cityCore]),
            $this->join([$address, $city, $region]),
            $this->join([$cityCore, $region]),
            $city,
            $cityCore,
        ])));

        foreach ($candidates as $i => $q) {
            if ($i > 0) {
                usleep(1_100_000); // rispetta il limite di ~1 req/s di Nominatim
            }
            if ($coords = $this->geocode($q, $country)) {
                return $coords;
            }
        }

        return null;
    }

    /**
     * Singola query a Nominatim (OpenStreetMap).
     *
     * @return array{lat: float, lng: float}|null
     */
    public function geocode(string $query, ?string $countryCode = null): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        try {
            $params = ['q' => $query, 'format' => 'jsonv2', 'limit' => 1];
            if ($countryCode) {
                $params['countrycodes'] = $countryCode;
            }

            $res = Http::withHeaders([
                'User-Agent' => 'HostUp/1.0 (https://hostup.it)',
                'Accept-Language' => 'it',
            ])->timeout(8)->get('https://nominatim.openstreetmap.org/search', $params);

            $data = $res->json();
            if (! empty($data[0]['lat']) && ! empty($data[0]['lon'])) {
                return ['lat' => (float) $data[0]['lat'], 'lng' => (float) $data[0]['lon']];
            }
        } catch (\Throwable) {
            // best-effort
        }

        return null;
    }

    private function join(array $parts): string
    {
        return collect($parts)->map(fn ($p) => trim((string) $p))->filter()->implode(', ');
    }
}
