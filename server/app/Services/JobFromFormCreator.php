<?php

namespace App\Services;

use App\Livewire\JobServiceLines;
use App\Models\Job;
use App\Models\JobService;
use App\Models\RecurringJobTemplate;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns the shared job-form payload into a Job (or a recurring series). Used by
 * the Jobs resource create page, the customer Jobs-tab modal, and the dispatch
 * New Job modal so every entry point behaves identically (issues #52, #54, #55).
 *
 * @phpstan-type ServiceLine array{service_id: ?int, description: ?string, quantity: float, unit_price: ?float, price: ?float}
 */
class JobFromFormCreator
{
    /**
     * @param  array<string, mixed>  $data  Raw form state (control fields included).
     * @param  array<string, mixed>  $overrides  Attributes forced onto every job (e.g. a fixed customer_id).
     * @return array{job: Job, created: Collection<int, Job>}  The record to redirect to and every job created.
     */
    public function create(array $data, array $overrides = []): array
    {
        $type = $data['job_type'] ?? 'one_time';
        $lines = $this->resolveLines($data);
        $serviceIds = array_values(array_filter(array_column($lines, 'service_id')));

        // The service grid on create requires a recurring series to carry at least
        // one line (matching the old repeater's rule).
        if ($type === 'recurring' && $lines === []) {
            throw ValidationException::withMessages([
                'services' => 'A recurring job needs at least one service.',
            ]);
        }

        $recur = [
            'frequency' => ($data['recur_frequency'] ?? 'weekly') === 'monthly' ? 'monthly' : 'weekly',
            'day_of_week' => $data['recur_day_of_week'] ?? null,
            'indefinite' => (bool) ($data['recur_indefinite'] ?? false),
            'occurrences' => $data['recur_occurrences'] ?? null,
            'start' => $data['recur_start'] ?? now()->toDateString(),
        ];

        // Drop the form-only control fields before touching the Job model.
        foreach (['job_type', 'service_lines', 'services_draft_id', 'recur_frequency', 'recur_day_of_week', 'recur_indefinite', 'recur_occurrences', 'recur_start'] as $key) {
            unset($data[$key]);
        }

        $data = array_merge($data, $overrides);

        if ($type !== 'recurring') {
            return DB::transaction(function () use ($data, $lines) {
                $job = Job::create($data);
                $this->attachServices($job, $lines);

                return ['job' => $job, 'created' => new Collection([$job])];
            });
        }

        return DB::transaction(function () use ($data, $lines, $serviceIds, $recur) {
            $start = Carbon::parse($recur['start']);

            $template = RecurringJobTemplate::create([
                'customer_id' => $data['customer_id'],
                'property_id' => $data['property_id'] ?? null,
                'crew_id' => $data['crew_id'] ?? null,
                'service_id' => $serviceIds[0] ?? null,
                'title' => $this->recurringTitle($data, $serviceIds),
                'frequency' => $recur['frequency'],
                'interval_days' => $recur['frequency'] === 'weekly' ? 7 : 30,
                'preferred_day_of_week' => $recur['frequency'] === 'weekly' && $recur['day_of_week'] !== null
                    ? (int) $recur['day_of_week']
                    : null,
                'occurrences' => $recur['indefinite'] ? null : max(1, (int) ($recur['occurrences'] ?? 1)),
                'start_date' => $start->toDateString(),
                // A series ends by visit count (or not at all) — there is no stop date.
                'end_date' => null,
                'active' => true,
            ]);

            // Indefinite series only materialise a 6-month horizon now; the
            // jobs:generate-recurring command tops them up over time.
            $horizon = $recur['indefinite'] ? $start->copy()->addMonths(6) : null;

            $jobs = app(RecurringJobGenerator::class)->generate($template, $serviceIds, $start, $horizon);

            if ($jobs->isNotEmpty()) {
                return ['job' => $jobs->first(), 'created' => $jobs];
            }

            // Fallback: guarantee a record to redirect to.
            $job = Job::create(array_merge($data, [
                'recurring_job_template_id' => $template->id,
                'type' => 'recurring',
                'status' => 'scheduled',
                'scheduled_date' => $start->toDateString(),
            ]));
            $this->attachServices($job, $lines);

            return ['job' => $job, 'created' => new Collection([$job])];
        });
    }

    /**
     * Gather the service lines for a create, from either:
     *   - the draft cache written by the JobServiceLines grid (normal create), or
     *   - a `service_lines` array passed directly (the dispatch New Job modal).
     * The draft cache is consumed (deleted) once read.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, ServiceLine>
     */
    private function resolveLines(array $data): array
    {
        if (! empty($data['service_lines']) && is_array($data['service_lines'])) {
            return $this->normaliseDirectLines($data['service_lines']);
        }

        $draftId = $data['services_draft_id'] ?? null;
        if (! $draftId) {
            return [];
        }

        $rows = Cache::pull(JobServiceLines::draftCacheKey($draftId)) ?? [];

        return $this->normaliseColumnLines($rows);
    }

    /**
     * The draft grid already stores column-shaped rows; keep the valid ones.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, ServiceLine>
     */
    private function normaliseColumnLines(array $rows): array
    {
        $lines = [];
        foreach ($rows as $row) {
            $serviceId = ($row['service_id'] ?? null) ? (int) $row['service_id'] : null;
            $description = filled($row['description'] ?? null) ? $row['description'] : null;

            // Skip a genuinely empty row (no service and no description).
            if (! $serviceId && ! $description) {
                continue;
            }

            $unit = $row['unit_price'] ?? null;
            $qty = (float) ($row['quantity'] ?? 1) ?: 1;

            $lines[] = [
                'service_id' => $serviceId,
                'description' => $description,
                'quantity' => $qty,
                'unit_price' => $unit === null ? null : round((float) $unit, 2),
                'price' => $unit === null ? null : round($qty * (float) $unit, 2),
            ];
        }

        return $lines;
    }

    /**
     * The dispatch modal passes simple rows: ['service_id', 'pricing'=>'tbd'|'fixed', 'price'?].
     * Quantity is 1; a fixed price is the rate.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, ServiceLine>
     */
    private function normaliseDirectLines(array $rows): array
    {
        $lines = [];
        $seen = [];
        foreach ($rows as $row) {
            $serviceId = (int) ($row['service_id'] ?? 0);
            if ($serviceId <= 0 || isset($seen[$serviceId])) {
                continue;
            }
            $seen[$serviceId] = true;

            $fixed = ($row['pricing'] ?? 'tbd') === 'fixed';
            $rate = $fixed ? round((float) ($row['price'] ?? 0), 2) : null;

            $lines[] = [
                'service_id' => $serviceId,
                'description' => null,
                'quantity' => 1.0,
                'unit_price' => $rate,
                'price' => $rate,
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $serviceIds
     */
    private function recurringTitle(array $data, array $serviceIds): string
    {
        if (! empty($data['title'])) {
            return $data['title'];
        }

        if (! empty($serviceIds) && ($name = Service::whereKey($serviceIds[0])->value('name'))) {
            return $name;
        }

        return 'Recurring job';
    }

    /**
     * @param  array<int, ServiceLine>  $lines
     */
    private function attachServices(Job $job, array $lines): void
    {
        foreach (array_values($lines) as $order => $line) {
            JobService::create([
                'job_id' => $job->id,
                'service_id' => $line['service_id'],
                'quantity' => $line['quantity'],
                // Null unit_price/price = TBD; the office quotes it later (issue #52).
                'unit_price' => $line['unit_price'],
                'price' => $line['price'],
                'description' => $line['description']
                    ?? ($line['service_id'] ? Service::whereKey($line['service_id'])->value('name') : null),
                'sort_order' => $order,
            ]);
        }
    }
}
