<?php

namespace App\Console\Commands;

use App\Models\RecurringJobTemplate;
use App\Services\RecurringJobGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringJobs extends Command
{
    protected $signature = 'jobs:generate-recurring
                            {--weeks=4 : Number of weeks ahead to generate}';

    protected $description = 'Top up Job instances from active, indefinite recurring job templates';

    public function handle(RecurringJobGenerator $generator): int
    {
        $weeks = (int) $this->option('weeks');
        $horizon = Carbon::today()->addWeeks($weeks);

        // Fixed-count series are fully materialised when they're created, so this
        // rolling top-up only concerns indefinite/ongoing templates (issue #13).
        $templates = RecurringJobTemplate::query()
            ->where('active', true)
            ->whereNull('occurrences')
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::today());
            })
            ->get();

        if ($templates->isEmpty()) {
            $this->info('No active indefinite templates.');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($templates as $template) {
            $serviceIds = array_values(array_filter([$template->service_id]));
            $created += $generator->generate($template, $serviceIds, null, $horizon)->count();
        }

        $this->info("Created {$created} job(s) across {$templates->count()} template(s).");

        return self::SUCCESS;
    }
}
