<?php

namespace App\Console\Commands;

use App\Models\ChemicalLog;
use App\Models\Job;
use App\Models\JobMedia;
use App\Models\JobService;
use App\Models\Message;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\TimeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeJobs extends Command
{
    protected $signature = 'jobs:purge
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete ALL jobs and their related data (routes, stops, media, services, time logs, chemical logs, job messages) for a clean test slate. Leaves customers, properties, crews, and employee chat intact.';

    public function handle(): int
    {
        // Counts shown to the operator before anything is touched.
        $counts = [
            'Jobs'           => Job::count(),
            'Routes'         => Route::count(),
            'Route stops'    => RouteStop::count(),
            'Job media'      => JobMedia::count(),
            'Job services'   => JobService::count(),
            'Time logs'      => TimeLog::count(),
            'Chemical logs'  => ChemicalLog::count(),
            'Job messages'   => Message::whereNotNull('job_id')->count(),
        ];

        if ($counts['Jobs'] === 0 && $counts['Routes'] === 0) {
            $this->info('Nothing to purge — no jobs or routes exist.');
            return self::SUCCESS;
        }

        $this->warn('This will permanently delete:');
        $this->table(['Records', 'Count'], collect($counts)->map(
            fn ($n, $label) => [$label, $n]
        )->values()->all());

        if (! $this->option('force') && ! $this->confirm('Delete all of the above? This cannot be undone.')) {
            $this->info('Aborted. Nothing was deleted.');
            return self::SUCCESS;
        }

        DB::transaction(function () {
            // Child rows first. route_stops / time_logs / chemical_logs / messages
            // are nullOnDelete (would orphan), so delete them explicitly. job_media
            // and job_services would cascade, but we delete them here for clear counts.
            RouteStop::query()->delete();
            Route::query()->delete();
            JobMedia::query()->delete();
            JobService::query()->delete();
            TimeLog::query()->delete();
            ChemicalLog::query()->delete();
            Message::whereNotNull('job_id')->delete();

            // Parent last.
            Job::query()->delete();
        });

        $this->newLine();
        $this->info('✓ Purged all jobs and related data. You can now seed/create real jobs.');
        $this->line('Note: any job-photo files under storage are not removed by this command.');

        return self::SUCCESS;
    }
}
