<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function geocode(string $address): ?array
    {
        $key = config('services.google.maps_key');

        if (! $key) {
            Log::warning('GeocodingService called without GOOGLE_MAPS_API_KEY configured.');
            return null;
        }

        $response = Http::timeout(10)->get(self::ENDPOINT, [
            'address' => $address,
            'key' => $key,
        ]);

        if (! $response->successful()) {
            Log::warning('Geocoding HTTP failure', ['status' => $response->status(), 'address' => $address]);
            return null;
        }

        $data = $response->json();
        $status = $data['status'] ?? null;

        if ($status !== 'OK' || empty($data['results'])) {
            Log::info('Geocoding non-OK status', ['status' => $status, 'address' => $address]);
            return null;
        }

        $location = $data['results'][0]['geometry']['location'] ?? null;

        if (! $location || ! isset($location['lat'], $location['lng'])) {
            return null;
        }

        return [
            'lat' => (float) $location['lat'],
            'lng' => (float) $location['lng'],
            'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
        ];
    }

    public function geocodeProperty(Property $property): bool
    {
        $address = $this->buildAddress($property);

        if ($address === '') {
            return false;
        }

        $result = $this->geocode($address);

        if (! $result) {
            return false;
        }

        $property->forceFill([
            'latitude' => $result['lat'],
            'longitude' => $result['lng'],
            'geocoded_at' => now(),
        ])->saveQuietly();

        return true;
    }

    private function buildAddress(Property $property): string
    {
        return trim(implode(', ', array_filter([
            $property->address,
            $property->city,
            $property->state,
            $property->zip,
        ])));
    }
}
