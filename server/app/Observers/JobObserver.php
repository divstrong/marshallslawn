<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\CustomerSmsNotifier;
use App\Services\JobNotifier;
use App\Services\JobRouteAssigner;

class JobObserver
{
    public function __construct(
        private readonly JobNotifier $notifier,
        private readonly JobRouteAssigner $routeAssigner,
        private readonly CustomerSmsNotifier $sms,
    ) {
    }

    public function created(Job $job): void
    {
        $this->routeAssigner->sync($job);

        // A job created already scheduled → tell the customer (issue: Twilio).
        if ($job->scheduled_date && ! in_array($job->status, ['completed', 'cancelled'], true)) {
            $this->sms->jobScheduled($job);
        }
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

        $this->sendCustomerSms($job);

        if (! $job->wasChanged('status')) {
            return;
        }

        if (! in_array($job->status, ['skipped', 'cancelled'], true)) {
            return;
        }

        $this->notifier->notifySkippedOrCancelled($job, $job->status);
    }

    /**
     * Fire the appropriate customer SMS for a job change (issue: Twilio). Each is
     * a no-op unless the channel + template are active and the customer opted in.
     */
    private function sendCustomerSms(Job $job): void
    {
        // Completed.
        if ($job->wasChanged('status') && $job->status === 'completed') {
            $this->sms->jobCompleted($job);

            return;
        }

        // Canceled / skipped.
        if ($job->wasChanged('status') && in_array($job->status, ['cancelled', 'skipped'], true)) {
            $this->sms->jobRescheduledOrCanceled($job, 'canceled');

            return;
        }

        // Newly scheduled, or moved to a different date.
        if ($job->wasChanged('scheduled_date')
            && $job->scheduled_date
            && ! in_array($job->status, ['completed', 'cancelled'], true)) {
            // First-time scheduling reads as "scheduled"; a shift reads as "rescheduled".
            $originalDate = $job->getOriginal('scheduled_date');
            if ($originalDate) {
                $this->sms->jobRescheduledOrCanceled($job, 'rescheduled');
            } else {
                $this->sms->jobScheduled($job);
            }
        }
    }
}
