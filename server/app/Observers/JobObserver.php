<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\JobNotifier;

class JobObserver
{
    public function __construct(private readonly JobNotifier $notifier)
    {
    }

    /**
     * When a job's status transitions to skipped or cancelled, alert the crew's
     * field staff (issue #14). Note: mass updates via the query builder do not
     * fire model events — callers that bypass the model (e.g. the dispatch Skip
     * action) notify explicitly via JobNotifier.
     */
    public function updated(Job $job): void
    {
        if (! $job->wasChanged('status')) {
            return;
        }

        if (! in_array($job->status, ['skipped', 'cancelled'], true)) {
            return;
        }

        $this->notifier->notifySkippedOrCancelled($job, $job->status);
    }
}
