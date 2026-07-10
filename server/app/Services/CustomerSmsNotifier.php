<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\Setting;
use App\Models\SmsTemplate;
use Illuminate\Support\Carbon;

/**
 * Sends the customer-facing SMS notifications (issue: Twilio integration). Every
 * send is gated three ways:
 *   1. the config kill-switch (twilio.notifications.enabled),
 *   2. the per-event template being active (Settings → Notifications), and
 *   3. the customer having confirmed SMS opt-in and a phone number.
 * If any gate is closed, nothing is sent — silently and safely.
 */
class CustomerSmsNotifier
{
    public function __construct(private readonly TwilioService $twilio)
    {
    }

    public function jobScheduled(Job $job): void
    {
        $this->sendForJob($job, 'job_scheduled');
    }

    public function jobCompleted(Job $job): void
    {
        $this->sendForJob($job, 'job_completed');
    }

    /**
     * @param  string  $disposition  'rescheduled' or 'canceled' — surfaced as {status}.
     */
    public function jobRescheduledOrCanceled(Job $job, string $disposition): void
    {
        $this->sendForJob($job, 'job_rescheduled', ['status' => $disposition]);
    }

    public function invoiceIssued(Invoice $invoice): void
    {
        if (! config('twilio.notifications.enabled')) {
            return;
        }

        $customer = $invoice->customer;
        if (! $customer instanceof Customer || ! $customer->canReceiveSms()) {
            return;
        }

        $body = SmsTemplate::render('invoice_issued', [
            'name' => $this->firstName($customer),
            'company' => $this->company(),
            'invoice_number' => $invoice->invoice_number ?? ('#' . $invoice->id),
            'amount' => $this->money($invoice->total ?? 0),
            'link' => $this->invoiceLink($invoice),
        ]);

        if ($body === null) {
            return;
        }

        $this->twilio->sendSms($customer->phone, $body, 'invoice_issued');
    }

    /**
     * @param  array<string, string|null>  $extra  Extra placeholder vars.
     */
    private function sendForJob(Job $job, string $key, array $extra = []): void
    {
        if (! config('twilio.notifications.enabled')) {
            return;
        }

        $customer = $job->customer;
        if (! $customer instanceof Customer || ! $customer->canReceiveSms()) {
            return;
        }

        $body = SmsTemplate::render($key, array_merge([
            'name' => $this->firstName($customer),
            'company' => $this->company(),
            'service' => $job->title ?: 'your service',
            'date' => $job->scheduled_date ? Carbon::parse($job->scheduled_date)->format('D, M j') : 'soon',
        ], $extra));

        if ($body === null) {
            return;
        }

        $this->twilio->sendSms($customer->phone, $body, $key);
    }

    private function firstName(Customer $customer): string
    {
        return $customer->first_name ?: ($customer->company_name ?: 'there');
    }

    private function company(): string
    {
        return Setting::get('company_name', "Marshall's Lawn & Landscape");
    }

    private function money(float|int|string $amount): string
    {
        return '$' . number_format((float) $amount, 2);
    }

    private function invoiceLink(Invoice $invoice): string
    {
        return $invoice->share_token
            ? route('invoice.public', $invoice->share_token)
            : url('/portal/invoices');
    }
}
