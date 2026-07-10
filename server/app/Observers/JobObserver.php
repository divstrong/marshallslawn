<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\JobNotifier;
use App\Services\JobRouteAssigner;

class JobObserver
{
    public function __construct(
        private readonly JobNotifier $notifier,
        private readonly JobRouteAssigner $routeAssigner,
    ) {
    }

    public function created(Job $job): void
    {
        $this->routeAssigner->sync($job);
    }

    /**
     * When a job's status transitions to skipped or cancelled, alert the crew's
     * field staff (issue #14). Note: mass updates via the query builder do not
     * fire model events — callers that bypass the model (e.g. the dispatch Skip
     * action) notify explicitly via JobNotifier.
     */
    public function updated(Job $job): void
    {
        // A job that now has both a crew and a date belongs on that crew's route
        // without a detour through the Unassigned pile (issue #52).
        if ($job->wasChanged(['crew_id', 'scheduled_date', 'status'])) {
            $this->routeAssigner->sync($job);
        }

        if (! $job->wasChanged('status')) {
            return;
        }

        if (! in_array($job->status, ['skipped', 'cancelled'], true)) {
            return;
        }

        $this->notifier->notifySkippedOrCancelled($job, $job->status);
    }
}
