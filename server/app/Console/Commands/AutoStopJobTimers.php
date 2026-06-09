<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

/**
 * Safety net for job timers left running by accident: any job whose timer has
 * been running longer than the daily maximum is automatically stopped, with its
 * finish time capped to started_at + MAX_TIMER_HOURS.
 */
class AutoStopJobTimers extends Command
{
    protected $signature = 'jobs:autostop-timers';

    protected $description = 'Auto-stop job timers running longer than the 12-hour daily maximum';

    public function handle(): int
    {
        $cutoff = now()->subHours(Job::MAX_TIMER_HOURS);

        $jobs = Job::query()
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->where('started_at', '<=', $cutoff)
            ->get();

        foreach ($jobs as $job) {
            // stopTimer() caps the finish at started_at + MAX_TIMER_HOURS.
            $job->stopTimer();
        }

        $this->info("Auto-stopped {$jobs->count()} job timer(s) past the {$cutoff->diffForHumans()} cutoff.");

        return self::SUCCESS;
    }
}
