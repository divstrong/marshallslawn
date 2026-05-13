<?php

namespace App\Console\Commands;

use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Service;
use App\Services\GeocodingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SeedTestJobs extends Command
{
    protected $signature = 'jobs:seed-test
                            {--per-day=50 : Number of jobs to create per day}
                            {--days=7 : Number of days starting today}
                            {--crews=5 : Number of crews to distribute jobs across (uses first N by id)}
                            {--pool=150 : Size of the unique property pool to sample from}
                            {--unrouted-pct=0 : Percent of jobs to leave unrouted (0-100)}
                            {--fresh : Delete existing jobs/routes in the date window before seeding}
                            {--no-geocode : Skip geocoding (jobs will appear in lists but not on the map)}';

    protected $description = 'Seed Jobs distributed across crews + days for testing Dispatch + Scheduling';

    public function handle(GeocodingService $geocoder): int
    {
        $perDay = max(1, (int) $this->option('per-day'));
        $days = max(1, (int) $this->option('days'));
        $crewLimit = max(1, (int) $this->option('crews'));
        $poolTarget = max($perDay * 2, (int) $this->option('pool'));
        $unroutedPct = max(0, min(100, (int) $this->option('unrouted-pct')));
        $fresh = (bool) $this->option('fresh');
        $skipGeocode = (bool) $this->option('no-geocode');

        $start = Carbon::today();
        $end = (clone $start)->addDays($days - 1);

        // ---- 1. Optionally clear the window ----------------------------------
        if ($fresh) {
            $routeIds = Route::query()
                ->whereBetween('route_date', [$start->toDateString(), $end->toDateString()])
                ->pluck('id');

            $stopsDeleted = RouteStop::whereIn('route_id', $routeIds)->delete();
            $routesDeleted = Route::whereIn('id', $routeIds)->delete();
            $jobsDeleted = Job::whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])->delete();

            $this->warn("Cleared window [{$start->toDateString()} → {$end->toDateString()}]: {$jobsDeleted} jobs, {$routesDeleted} routes, {$stopsDeleted} stops.");
        }

        // ---- 2. Pick crews ---------------------------------------------------
        $crews = Crew::orderBy('id')->limit($crewLimit)->get();
        if ($crews->isEmpty()) {
            $this->error('No crews found.');
            return self::FAILURE;
        }
        $this->info("Using crews: " . $crews->pluck('name')->implode(', '));

        // ---- 3. Build property pool -----------------------------------------
        $customers = Customer::query()
            ->has('properties')
            ->inRandomOrder()
            ->limit($poolTarget * 2)
            ->get();

        $properties = collect();
        foreach ($customers as $customer) {
            if ($properties->count() >= $poolTarget) break;
            $p = $customer->properties()->orderByDesc('is_primary')->first();
            if ($p && $p->address) {
                $properties->push($p);
            }
        }

        if ($properties->isEmpty()) {
            $this->error('No customers with addressable properties.');
            return self::FAILURE;
        }
        $this->info("Property pool: {$properties->count()}");

        // ---- 4. Geocode any in the pool that lack coordinates ----------------
        if (! $skipGeocode) {
            $needs = $properties->filter(fn ($p) => ! $p->hasCoordinates());
            if ($needs->isNotEmpty()) {
                $this->info("Geocoding {$needs->count()} new properties...");
                $bar = $this->output->createProgressBar($needs->count());
                $bar->start();
                foreach ($needs as $p) {
                    try {
                        if ($geocoder->geocodeProperty($p)) {
                            $p->refresh();
                        }
                    } catch (\Throwable $e) {
                        // swallow; we'll filter on coords below
                    }
                    $bar->advance();
                    usleep(50_000);
                }
                $bar->finish();
                $this->newLine(2);
            }
        }

        $usable = $properties->filter(fn ($p) => $p->hasCoordinates())->values();
        if ($usable->isEmpty()) {
            $this->error('No geocoded properties available — re-run with the GOOGLE_MAPS_API_KEY enabled.');
            return self::FAILURE;
        }
        $this->info("Usable (geocoded) properties: {$usable->count()}");

        // ---- 5. Service-name pool for job titles -----------------------------
        $serviceNames = Service::query()
            ->where(function ($q) {
                foreach (['mow', 'fert', 'leaf', 'mulch', 'spray', 'edge', 'clean', 'aerat', 'seed'] as $term) {
                    $q->orWhere('name', 'LIKE', "%{$term}%");
                }
            })
            ->limit(20)
            ->pluck('name')
            ->all();

        if (empty($serviceNames)) {
            $serviceNames = [
                'Weekly Mowing', 'Fertilizer Treatment', 'Leaf Removal',
                'Mulch Installation', 'Chemical Spraying', 'Bed Edging',
                'Yard Cleanup', 'Core Aeration',
            ];
        }

        $priorities = ['low', 'normal', 'normal', 'normal', 'normal', 'high'];

        // ---- 6. Generate per-day jobs + route stops --------------------------
        $totalToCreate = $perDay * $days;
        $this->info("Generating {$perDay} jobs/day × {$days} days = {$totalToCreate} jobs...");

        $progress = $this->output->createProgressBar($totalToCreate);
        $progress->start();

        $totalJobs = 0;
        $totalStops = 0;
        $totalUnrouted = 0;

        for ($dayOffset = 0; $dayOffset < $days; $dayOffset++) {
            $date = (clone $start)->addDays($dayOffset)->toDateString();

            // Get-or-create Route per chosen crew for this day
            $routes = [];
            foreach ($crews as $crew) {
                $routes[$crew->id] = Route::firstOrCreate(
                    ['route_date' => $date, 'crew_id' => $crew->id],
                    [
                        'name' => Carbon::parse($date)->format('D, M j') . ' — ' . $crew->name,
                        'status' => 'planning',
                    ]
                );
            }

            $sortCursors = []; // per-route running sort_order
            foreach ($routes as $r) {
                $sortCursors[$r->id] = (int) ($r->stops()->max('sort_order') ?? 0);
            }

            for ($i = 0; $i < $perDay; $i++) {
                $property = $usable->random();
                $crew = $crews->random();

                $job = Job::create([
                    'customer_id' => $property->customer_id,
                    'property_id' => $property->id,
                    'crew_id' => $crew->id,
                    'title' => $serviceNames[array_rand($serviceNames)],
                    'priority' => $priorities[array_rand($priorities)],
                    'status' => 'scheduled',
                    'scheduled_date' => $date,
                ]);
                $totalJobs++;

                $leaveUnrouted = $unroutedPct > 0 && random_int(1, 100) <= $unroutedPct;

                if (! $leaveUnrouted) {
                    $route = $routes[$crew->id];
                    $sortCursors[$route->id] += 1;

                    RouteStop::create([
                        'route_id' => $route->id,
                        'job_id' => $job->id,
                        'customer_id' => $property->customer_id,
                        'property_id' => $property->id,
                        'sort_order' => $sortCursors[$route->id],
                        'status' => 'pending',
                    ]);
                    $totalStops++;
                } else {
                    $totalUnrouted++;
                }

                $progress->advance();
            }
        }

        $progress->finish();
        $this->newLine(2);

        $this->info("✓ Created {$totalJobs} jobs.");
        $this->info("✓ Routed {$totalStops} as RouteStops across {$crews->count()} crews × {$days} days.");
        if ($totalUnrouted > 0) {
            $this->info("✓ Left {$totalUnrouted} jobs unrouted (will appear as gray pins on Dispatch).");
        }
        $this->line('Visit /dispatch to view the map, or /scheduling to shuffle assignments.');

        return self::SUCCESS;
    }
}
