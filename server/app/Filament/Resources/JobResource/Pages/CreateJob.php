<?php

namespace App\Filament\Resources\JobResource\Pages;

use App\Filament\Resources\JobResource;
use App\Models\Job;
use App\Models\JobService;
use App\Models\RecurringJobTemplate;
use App\Models\Service;
use App\Services\RecurringJobGenerator;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateJob extends CreateRecord
{
    protected static string $resource = JobResource::class;

    /**
     * Branch on the job "Type": a one-time job creates a single record, while a
     * recurring job spawns a template and materialises its occurrences (issue #13).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $type = $data['job_type'] ?? 'one_time';
        $lines = $this->normaliseServiceLines($data['service_lines'] ?? []);
        $serviceIds = array_column($lines, 'service_id');

        $recur = [
            'frequency' => ($data['recur_frequency'] ?? 'weekly') === 'monthly' ? 'monthly' : 'weekly',
            'day_of_week' => $data['recur_day_of_week'] ?? null,
            'indefinite' => (bool) ($data['recur_indefinite'] ?? false),
            'occurrences' => $data['recur_occurrences'] ?? null,
            'start' => $data['recur_start'] ?? now()->toDateString(),
            'end' => $data['recur_end'] ?? null,
        ];

        // Drop the form-only control fields before touching the Job model.
        foreach (['job_type', 'service_lines', 'recur_frequency', 'recur_day_of_week', 'recur_indefinite', 'recur_occurrences', 'recur_start', 'recur_end'] as $key) {
            unset($data[$key]);
        }

        if ($type !== 'recurring') {
            return DB::transaction(function () use ($data, $lines) {
                /** @var Job $job */
                $job = Job::create($data);
                $this->attachServices($job, $lines);

                return $job;
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
                'end_date' => $recur['end'] ?: null,
                'active' => true,
            ]);

            // Indefinite series only materialise a 6-month horizon now; the
            // jobs:generate-recurring command tops them up over time.
            $horizon = $recur['indefinite'] ? $start->copy()->addMonths(6) : null;

            $jobs = app(RecurringJobGenerator::class)->generate($template, $serviceIds, $start, $horizon);

            Notification::make()
                ->title('Recurring job created')
                ->body($jobs->count() . ' occurrence(s) scheduled.')
                ->success()
                ->send();

            if ($jobs->isNotEmpty()) {
                return $jobs->first();
            }

            // Fallback: guarantee Filament gets a record to redirect to.
            $job = Job::create(array_merge($data, [
                'recurring_job_template_id' => $template->id,
                'type' => 'recurring',
                'status' => 'scheduled',
                'scheduled_date' => $start->toDateString(),
            ]));
            $this->attachServices($job, $lines);

            return $job;
        });
    }

    /**
     * Flatten the Services-tab repeater into service lines, resolving the TBD /
     * fixed-price choice into a nullable price (null = TBD). Lines without a
     * service are dropped, and a service is only allowed to appear once.
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return array<int, array{service_id: int, price: float|null, description: string|null}>
     */
    private function normaliseServiceLines(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $serviceId = (int) ($row['service_id'] ?? 0);
            if ($serviceId <= 0 || isset($lines[$serviceId])) {
                continue;
            }

            $lines[$serviceId] = [
                'service_id' => $serviceId,
                'price' => ($row['pricing'] ?? 'tbd') === 'fixed' ? round((float) ($row['price'] ?? 0), 2) : null,
                'description' => filled($row['description'] ?? null) ? $row['description'] : null,
            ];
        }

        return array_values($lines);
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

        if (! empty($serviceIds)) {
            $name = Service::whereKey($serviceIds[0])->value('name');
            if ($name) {
                return $name;
            }
        }

        return 'Recurring job';
    }

    /**
     * @param  array<int, array{service_id: int, price: float|null, description: string|null}>  $lines
     */
    private function attachServices(Job $job, array $lines): void
    {
        foreach (array_values($lines) as $order => $line) {
            JobService::create([
                'job_id' => $job->id,
                'service_id' => $line['service_id'],
                // Null price = TBD; the office quotes it later (issue #52).
                'price' => $line['price'],
                'description' => $line['description'] ?? Service::whereKey($line['service_id'])->value('name'),
                'sort_order' => $order,
            ]);
        }
    }
}
