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
        $serviceIds = array_values(array_filter(array_map('intval', (array) ($data['services'] ?? []))));

        $recur = [
            'frequency' => ($data['recur_frequency'] ?? 'weekly') === 'monthly' ? 'monthly' : 'weekly',
            'day_of_week' => $data['recur_day_of_week'] ?? null,
            'indefinite' => (bool) ($data['recur_indefinite'] ?? false),
            'occurrences' => $data['recur_occurrences'] ?? null,
            'start' => $data['recur_start'] ?? now()->toDateString(),
            'end' => $data['recur_end'] ?? null,
        ];

        // Drop the form-only control fields before touching the Job model.
        foreach (['job_type', 'services', 'recur_frequency', 'recur_day_of_week', 'recur_indefinite', 'recur_occurrences', 'recur_start', 'recur_end'] as $key) {
            unset($data[$key]);
        }

        if ($type !== 'recurring') {
            return DB::transaction(function () use ($data, $serviceIds) {
                /** @var Job $job */
                $job = Job::create($data);
                $this->attachServices($job, $serviceIds);

                return $job;
            });
        }

        return DB::transaction(function () use ($data, $serviceIds, $recur) {
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
            $this->attachServices($job, $serviceIds);

            return $job;
        });
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
     * @param  array<int, int>  $serviceIds
     */
    private function attachServices(Job $job, array $serviceIds): void
    {
        $order = 0;
        foreach (array_values(array_unique(array_filter($serviceIds))) as $serviceId) {
            JobService::create([
                'job_id' => $job->id,
                'service_id' => $serviceId,
                'sort_order' => $order++,
            ]);
        }
    }
}
