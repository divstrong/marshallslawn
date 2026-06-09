<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobService;
use App\Models\RecurringJobTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Materialises Job occurrences from a RecurringJobTemplate (issue #13).
 *
 * Supports weekly (every interval_days, optionally snapped to a preferred day of
 * week) and monthly (same day-of-month) frequencies, a fixed occurrence count, or
 * an indefinite/ongoing series bounded by an optional end date.
 */
class RecurringJobGenerator
{
    /** Hard ceiling so an indefinite series can never spin out of control in one pass. */
    public const MAX_OCCURRENCES = 260;

    /**
     * Compute the schedule dates for a template starting from $from.
     *
     * @return array<int, Carbon>
     */
    public function occurrenceDates(RecurringJobTemplate $template, Carbon $from, ?Carbon $horizon = null): array
    {
        $frequency = $template->frequency ?: 'weekly';
        $cursor = $from->copy()->startOfDay();

        // Weekly series can snap onto a preferred day of week.
        if ($frequency === 'weekly'
            && $template->preferred_day_of_week !== null
            && (int) $cursor->dayOfWeek !== (int) $template->preferred_day_of_week) {
            $cursor = $cursor->next((int) $template->preferred_day_of_week);
        }

        $end = $template->end_date ? Carbon::parse($template->end_date)->endOfDay() : null;
        $limit = $template->occurrences; // null = indefinite
        $max = $limit ?? self::MAX_OCCURRENCES;
        $step = max(1, (int) ($template->interval_days ?: 7));

        $dates = [];
        while (count($dates) < $max) {
            if ($end && $cursor->gt($end)) {
                break;
            }
            // For an indefinite series we only generate up to the requested horizon now;
            // the scheduled command tops it up over time.
            if ($limit === null && $horizon && $cursor->gt($horizon)) {
                break;
            }

            $dates[] = $cursor->copy();

            $cursor = $frequency === 'monthly'
                ? $cursor->copy()->addMonthsNoOverflow(1)
                : $cursor->copy()->addDays($step);
        }

        return $dates;
    }

    /**
     * Generate (idempotently) the Job occurrences for a template and attach services.
     *
     * @param  array<int, int>  $serviceIds  Services to attach to every occurrence.
     * @return Collection<int, Job>  The jobs created during this pass.
     */
    public function generate(
        RecurringJobTemplate $template,
        array $serviceIds = [],
        ?Carbon $from = null,
        ?Carbon $horizon = null,
    ): Collection {
        $from ??= Carbon::parse($template->next_generation_date ?: $template->start_date);
        $dates = $this->occurrenceDates($template, $from, $horizon);

        $services = $serviceIds ?: array_values(array_filter([$template->service_id]));

        $created = collect();
        $last = null;

        foreach ($dates as $date) {
            $last = $date;

            $exists = Job::where('recurring_job_template_id', $template->id)
                ->whereDate('scheduled_date', $date->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            $job = Job::create([
                'customer_id' => $template->customer_id,
                'property_id' => $template->property_id,
                'crew_id' => $template->crew_id,
                'recurring_job_template_id' => $template->id,
                'title' => $template->title,
                'type' => 'recurring',
                'status' => 'scheduled',
                'scheduled_date' => $date->toDateString(),
            ]);

            $this->attachServices($job, $services);
            $created->push($job);
        }

        if ($last) {
            $next = ($template->frequency ?: 'weekly') === 'monthly'
                ? $last->copy()->addMonthsNoOverflow(1)
                : $last->copy()->addDays(max(1, (int) ($template->interval_days ?: 7)));

            $template->update(['next_generation_date' => $next->toDateString()]);
        }

        return $created;
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
