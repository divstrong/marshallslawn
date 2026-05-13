<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\RecurringJobTemplate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringJobs extends Command
{
    protected $signature = 'jobs:generate-recurring
                            {--weeks=4 : Number of weeks ahead to generate}';

    protected $description = 'Spawn Job instances from active recurring job templates';

    public function handle(): int
    {
        $weeks = (int) $this->option('weeks');
        $horizon = Carbon::today()->addWeeks($weeks);

        $templates = RecurringJobTemplate::query()
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::today());
            })
            ->get();

        if ($templates->isEmpty()) {
            $this->info('No active templates.');
            return self::SUCCESS;
        }

        $created = 0;
        foreach ($templates as $template) {
            $created += $this->generateForTemplate($template, $horizon);
        }

        $this->info("Created {$created} job(s) across {$templates->count()} template(s).");
        return self::SUCCESS;
    }

    private function generateForTemplate(RecurringJobTemplate $template, Carbon $horizon): int
    {
        $cursor = $template->next_generation_date
            ? Carbon::parse($template->next_generation_date)
            : Carbon::parse($template->start_date);

        // Snap to preferred day of week if set and not aligned.
        if ($template->preferred_day_of_week !== null
            && (int) $cursor->dayOfWeek !== (int) $template->preferred_day_of_week) {
            $cursor = $cursor->next($template->preferred_day_of_week);
        }

        $endLimit = $template->end_date ? Carbon::parse($template->end_date) : null;
        $interval = max(1, (int) $template->interval_days);

        $created = 0;

        while ($cursor->lte($horizon)) {
            if ($endLimit && $cursor->gt($endLimit)) {
                break;
            }

            if ($this->inSeason($cursor, $template) && ! $this->jobExists($template, $cursor)) {
                Job::create([
                    'customer_id' => $template->customer_id,
                    'property_id' => $template->property_id,
                    'crew_id' => $template->crew_id,
                    'recurring_job_template_id' => $template->id,
                    'title' => $template->title,
                    'status' => 'scheduled',
                    'scheduled_date' => $cursor->toDateString(),
                ]);
                $created++;
            }

            $cursor->addDays($interval);
        }

        $template->update(['next_generation_date' => $cursor->toDateString()]);

        return $created;
    }

    private function inSeason(Carbon $date, RecurringJobTemplate $template): bool
    {
        if ($template->season_start_month === null || $template->season_end_month === null) {
            return true;
        }

        $month = (int) $date->month;
        $start = (int) $template->season_start_month;
        $end = (int) $template->season_end_month;

        // Season can wrap year boundary (e.g. Nov–Feb).
        return $start <= $end
            ? ($month >= $start && $month <= $end)
            : ($month >= $start || $month <= $end);
    }

    private function jobExists(RecurringJobTemplate $template, Carbon $date): bool
    {
        return Job::where('recurring_job_template_id', $template->id)
            ->whereDate('scheduled_date', $date->toDateString())
            ->exists();
    }
}
