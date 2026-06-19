<?php

namespace App\Services;

use App\Models\Crew;
use App\Models\Job;
use Illuminate\Support\Carbon;

/**
 * Push notifications about job changes to the crew's field staff (issue #14):
 * when a scheduled job is skipped or canceled, its crew's foreman and spray
 * techs are alerted.
 */
class JobNotifier
{
    public function __construct(private readonly ExpoPushService $push)
    {
    }

    /**
     * Notify a crew's foreman + spray techs that a job was skipped or canceled.
     *
     * @param  string  $status  'skipped' or 'cancelled'
     * @param  string|null  $scheduledDate  The job's scheduled date before it was cleared (Y-m-d).
     */
    public function notifySkippedOrCancelled(Job $job, string $status, ?string $scheduledDate = null): void
    {
        if (! $job->crew_id) {
            return;
        }

        $crew = Crew::with(['foreman', 'members.employee'])->find($job->crew_id);
        if (! $crew) {
            return;
        }

        $recipients = collect();
        if ($crew->foreman) {
            $recipients->push($crew->foreman);
        }
        foreach ($crew->members as $member) {
            if ($member->employee) {
                $recipients->push($member->employee);
            }
        }

        $recipients = $recipients
            ->unique('id')
            ->filter(fn ($employee) => in_array($employee->role, ['foreman', 'spray_tech'], true));

        if ($recipients->isEmpty()) {
            return;
        }

        $verb = $status === 'cancelled' ? 'canceled' : 'skipped';
        $date = $scheduledDate ?? $job->scheduled_date?->toDateString();
        $when = $date ? Carbon::parse($date)->format('D, M j') : 'an upcoming day';

        // Build the placeholder values an admin can reference in the template (issue #14).
        $job->loadMissing(['customer', 'property']);
        $customer = $job->customer;
        $customerName = $customer
            ? (trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''))
                ?: ($customer->company_name ?? 'the customer'))
            : 'the customer';

        $vars = [
            'customer_name' => $customerName,
            'client_name' => $customerName,
            'job_title' => $job->title ?: 'A job',
            'scheduled_date' => $when,
            'crew_name' => $crew->name,
            'status' => $verb,
            'address' => $job->property?->address ?? '',
            'city' => $job->property?->city ?? '',
        ];

        // Prefer the admin-managed template; fall back to built-in copy when it's
        // missing or disabled.
        $rendered = \App\Models\NotificationTemplate::render('job_' . $status, $vars);
        $title = $rendered['title'] ?? ('Job ' . $verb);
        $body = $rendered['body'] ?? (($job->title ?: 'A job') . ' (' . $when . ') was ' . $verb . '.');
        $channel = $rendered['channel'] ?? 'alerts';

        foreach ($recipients as $employee) {
            $this->push->sendToEmployee(
                $employee,
                $title,
                $body,
                ['type' => 'job', 'job_id' => $job->id, 'status' => $status],
                $channel,
            );
        }
    }
}
