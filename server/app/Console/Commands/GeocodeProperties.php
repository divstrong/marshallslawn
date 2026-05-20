<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeProperties extends Command
{
    protected $signature = 'properties:geocode
                            {--missing : Only geocode properties without coordinates}
                            {--limit= : Maximum number of properties to process}';

    protected $description = 'Geocode property addresses via Google Geocoding API';

    public function handle(GeocodingService $geocoder): int
    {
        $query = Property::query();

        if ($this->option('missing')) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No properties to geocode.');
            return self::SUCCESS;
        }

        $this->info("Geocoding {$total} properties...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;

        $query->chunkById(50, function ($properties) use ($geocoder, $bar, &$success, &$failed) {
            foreach ($properties as $property) {
                try {
                    if ($geocoder->geocodeProperty($property)) {
                        $success++;
                    } else {
                        $failed++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed property #{$property->id}: " . $e->getMessage());
                }

                $bar->advance();
                usleep(50_000); // pace requests to stay well under Google's QPS cap
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
