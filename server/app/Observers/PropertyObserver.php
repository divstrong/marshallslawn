<?php

namespace App\Observers;

use App\Models\Property;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\DB;

class PropertyObserver
{
    public function __construct(private GeocodingService $geocoder)
    {
    }

    /**
     * A customer's first property is their primary one — with a single property
     * there is nothing else it could be. Any later property starts secondary
     * unless it was explicitly flagged.
     */
    public function creating(Property $property): void
    {
        if (! $property->customer_id) {
            return;
        }

        $isFirst = ! Property::where('customer_id', $property->customer_id)->exists();

        if ($isFirst) {
            $property->is_primary = true;
        } elseif ($property->is_primary === null) {
            $property->is_primary = false;
        }
    }

    public function saved(Property $property): void
    {
        $this->enforceSinglePrimary($property);
        $this->geocodeIfNeeded($property);
    }

    /**
     * Promote a survivor when the primary property is removed, so a customer with
     * properties always has exactly one primary.
     */
    public function deleted(Property $property): void
    {
        if (! $property->is_primary || ! $property->customer_id) {
            return;
        }

        $heir = Property::where('customer_id', $property->customer_id)
            ->orderBy('id')
            ->first();

        // Query builder, not the model: this must not re-enter the observer.
        if ($heir) {
            DB::table('properties')->where('id', $heir->id)->update(['is_primary' => true]);
        }
    }

    /**
     * One primary per customer. Uses the query builder so demoting the siblings
     * doesn't fire this observer again.
     */
    private function enforceSinglePrimary(Property $property): void
    {
        if (! $property->is_primary || ! $property->customer_id) {
            return;
        }

        DB::table('properties')
            ->where('customer_id', $property->customer_id)
            ->where('id', '!=', $property->id)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    private function geocodeIfNeeded(Property $property): void
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
