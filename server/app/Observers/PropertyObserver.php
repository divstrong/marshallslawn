<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\GeocodingService;

class PropertyObserver
{
    public function __construct(private GeocodingService $geocoder)
    {
    }

    public function saved(Property $property): void
    {
        $addressFields = ['address', 'city', 'state', 'zip'];

        $addressChanged = false;
        foreach ($addressFields as $field) {
            if ($property->wasChanged($field)) {
                $addressChanged = true;
                break;
            }
        }

        $neverGeocoded = $property->geocoded_at === null;
        $missingCoords = $property->latitude === null || $property->longitude === null;

        if (! $addressChanged && ! ($neverGeocoded && $missingCoords)) {
            return;
        }

        try {
            $this->geocoder->geocodeProperty($property);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
