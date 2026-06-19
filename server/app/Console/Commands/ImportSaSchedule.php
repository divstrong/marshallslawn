<?php

namespace App\Console\Commands;

use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Route;
use App\Models\RouteStop;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import scheduled jobs from a Service Autopilot "Scheduling View" export (.xls).
 *
 * Each row already maps to a customer + property we imported earlier (matched by
 * client name + service address). For every row we create a Job assigned to a crew
 * on its ScheduleDate, and — unless --no-route is passed — add it to that crew's
 * route for the day so it shows up as an assigned stop in Dispatch/Scheduling.
 */
class ImportSaSchedule extends Command
{
    protected $signature = 'jobs:import-sa-schedule
                            {file : Path to the SA Scheduling View .xls export}
                            {--crew= : Force all jobs onto this crew id (otherwise the row\'s AssignedTo name is matched)}
                            {--no-route : Create jobs without adding them to a route}
                            {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Import scheduled mowing jobs from a Service Autopilot Scheduling export';

    /** SA truncates service names in this export; map the ones we know to clean titles. */
    private const TITLE_MAP = [
        'lawn mowin' => 'Weekly Mowing',
        'weekly mowi' => 'Weekly Mowing',
        'pulling we' => 'Pulling Weeds',
    ];

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $route = ! $this->option('no-route');
        $forceCrew = $this->option('crew') ? Crew::find((int) $this->option('crew')) : null;
        if ($this->option('crew') && ! $forceCrew) {
            $this->error("--crew={$this->option('crew')} not found.");
            return self::FAILURE;
        }

        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        array_shift($rows); // header

        $norm = fn ($s) => strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $s));

        $created = 0;
        $routed = 0;
        $skipped = 0;
        $unmatched = [];
        // Per-route running sort_order cursor so stops append cleanly.
        $sortCursors = [];

        $this->info(($dryRun ? '[DRY RUN] ' : '') . 'Processing ' . count($rows) . ' rows...');

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $r) {
                $first = trim((string) ($r[1] ?? ''));
                $last = trim((string) ($r[2] ?? ''));
                if ($first === '' && $last === '') {
                    continue;
                }

                $svcAddr = trim((string) ($r[25] ?? ''));
                $saService = trim((string) ($r[12] ?? ''));
                $dateRaw = trim((string) ($r[13] ?? ''));
                $rate = $r[31] ?? null;
                $assigned = trim((string) ($r[28] ?? ''));

                // ---- Match the customer + property we already imported -------------
                $customer = Customer::where('first_name', $first)->where('last_name', $last)->first();
                if (! $customer) {
                    $unmatched[] = "{$last}, {$first} — no customer";
                    $skipped++;
                    continue;
                }

                $properties = $customer->properties;
                $property = $properties->first(fn ($p) => $norm($p->address) === $norm($svcAddr))
                    ?? $properties->first();
                if (! $property) {
                    $unmatched[] = "{$last}, {$first} — no property";
                    $skipped++;
                    continue;
                }

                // ---- Resolve crew + date + title -----------------------------------
                $crew = $forceCrew ?? Crew::where('name', 'LIKE', $assigned . '%')->orderBy('id')->first();
                if (! $crew) {
                    $unmatched[] = "{$last}, {$first} — crew '{$assigned}' not found";
                    $skipped++;
                    continue;
                }

                try {
                    $date = Carbon::parse($dateRaw)->toDateString();
                } catch (\Throwable $e) {
                    $unmatched[] = "{$last}, {$first} — bad date '{$dateRaw}'";
                    $skipped++;
                    continue;
                }

                $title = self::TITLE_MAP[strtolower($saService)]
                    ?? (stripos($saService, 'mow') !== false ? 'Weekly Mowing' : ($saService ?: 'Service'));

                $price = is_numeric($rate) ? round((float) $rate, 2) : null;

                // ---- Idempotency: don't double-create on a re-run ------------------
                $exists = Job::where('property_id', $property->id)
                    ->where('crew_id', $crew->id)
                    ->whereDate('scheduled_date', $date)
                    ->where('title', $title)
                    ->exists();
                if ($exists) {
                    $skipped++;
                    $this->line("  skip (exists): {$last}, {$first} — {$title} {$date}");
                    continue;
                }

                $this->line(sprintf('  + %-22s %-13s %s  crew #%d  $%s', "{$last}, {$first}", $title, $date, $crew->id, $price ?? '—'));

                if ($dryRun) {
                    $created++;
                    if ($route) {
                        $routed++;
                    }
                    continue;
                }

                $job = Job::create([
                    'customer_id' => $customer->id,
                    'property_id' => $property->id,
                    'crew_id' => $crew->id,
                    'title' => $title,
                    'price' => $price,
                    'status' => 'scheduled',
                    'scheduled_date' => $date,
                ]);
                $created++;

                if ($route) {
                    $r2 = Route::firstOrCreate(
                        ['route_date' => $date, 'crew_id' => $crew->id],
                        ['name' => Carbon::parse($date)->format('D, M j') . ' — ' . $crew->name, 'status' => 'planning']
                    );

                    $key = $r2->id;
                    if (! isset($sortCursors[$key])) {
                        $sortCursors[$key] = (int) ($r2->stops()->max('sort_order') ?? 0);
                    }
                    $sortCursors[$key]++;

                    RouteStop::create([
                        'route_id' => $r2->id,
                        'job_id' => $job->id,
                        'customer_id' => $customer->id,
                        'property_id' => $property->id,
                        'sort_order' => $sortCursors[$key],
                        'status' => 'pending',
                    ]);
                    $routed++;
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Aborted, rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Jobs created: {$created} | routed: {$routed} | skipped: {$skipped}");
        if ($unmatched) {
            $this->warn('Unmatched / skipped rows:');
            foreach ($unmatched as $u) {
                $this->line("  - {$u}");
            }
        }

        return self::SUCCESS;
    }
}
