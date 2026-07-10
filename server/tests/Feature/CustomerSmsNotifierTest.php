<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\SmsTemplate;
use App\Services\CustomerSmsNotifier;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Records sends instead of calling Twilio. */
class RecordingTwilioService extends TwilioService
{
    /** @var array<int, array{to: string, body: string, context: ?string}> */
    public array $sent = [];

    public function sendSms(string $to, string $body, ?string $context = null): ?string
    {
        $this->sent[] = ['to' => $to, 'body' => $body, 'context' => $context];

        return 'SM_fake';
    }
}

class CustomerSmsNotifierTest extends TestCase
{
    use RefreshDatabase;

    private RecordingTwilioService $twilio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->twilio = new RecordingTwilioService();
        $this->app->instance(TwilioService::class, $this->twilio);

        config()->set('twilio.notifications.enabled', true);
    }

    private function confirmedCustomer(): Customer
    {
        return Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '(804) 555-1212',
            'status' => 'active',
            'sms_consent_status' => Customer::SMS_CONFIRMED,
            'sms_consent_at' => now(),
        ]);
    }

    private function makeJob(Customer $customer, array $attrs = []): Job
    {
        $property = Property::create(['customer_id' => $customer->id, 'address' => '1 Elm St']);

        return Job::create(array_merge([
            'customer_id' => $customer->id,
            'property_id' => $property->id,
            'title' => 'Weekly mow',
            'status' => 'scheduled',
            'scheduled_date' => '2026-07-15',
        ], $attrs));
    }

    public function test_it_sends_when_channel_on_template_active_and_customer_opted_in(): void
    {
        SmsTemplate::where('key', 'job_scheduled')->update(['is_active' => true]);
        $customer = $this->confirmedCustomer();
        $job = $this->makeJob($customer);
        $this->twilio->sent = []; // ignore the send from creation; test the method in isolation

        app(CustomerSmsNotifier::class)->jobScheduled($job);

        $this->assertCount(1, $this->twilio->sent);
        $this->assertStringContainsString('Jane', $this->twilio->sent[0]['body']);
        $this->assertSame('job_scheduled', $this->twilio->sent[0]['context']);
    }

    public function test_it_does_not_send_when_the_template_is_inactive(): void
    {
        SmsTemplate::where('key', 'job_scheduled')->update(['is_active' => false]);
        $customer = $this->confirmedCustomer();

        app(CustomerSmsNotifier::class)->jobScheduled($this->makeJob($customer));

        $this->assertCount(0, $this->twilio->sent);
    }

    public function test_it_does_not_send_when_the_customer_has_not_opted_in(): void
    {
        SmsTemplate::where('key', 'job_scheduled')->update(['is_active' => true]);
        $customer = Customer::create([
            'first_name' => 'Pending',
            'last_name' => 'Person',
            'phone' => '8045551212',
            'status' => 'active',
            'sms_consent_status' => Customer::SMS_PENDING,
        ]);

        app(CustomerSmsNotifier::class)->jobScheduled($this->makeJob($customer));

        $this->assertCount(0, $this->twilio->sent);
    }

    public function test_it_does_not_send_when_the_channel_kill_switch_is_off(): void
    {
        config()->set('twilio.notifications.enabled', false);
        SmsTemplate::where('key', 'job_scheduled')->update(['is_active' => true]);

        app(CustomerSmsNotifier::class)->jobScheduled($this->makeJob($this->confirmedCustomer()));

        $this->assertCount(0, $this->twilio->sent);
    }

    public function test_scheduling_a_job_through_the_observer_texts_the_customer(): void
    {
        SmsTemplate::where('key', 'job_scheduled')->update(['is_active' => true]);
        $customer = $this->confirmedCustomer();

        // Creating a scheduled job fires JobObserver::created.
        $this->makeJob($customer);

        $this->assertCount(1, $this->twilio->sent);
        $this->assertSame('job_scheduled', $this->twilio->sent[0]['context']);
    }

    public function test_completing_a_job_texts_the_customer(): void
    {
        SmsTemplate::whereIn('key', ['job_scheduled', 'job_completed'])->update(['is_active' => true]);
        $customer = $this->confirmedCustomer();
        $job = $this->makeJob($customer);
        $this->twilio->sent = [];

        $job->update(['status' => 'completed']);

        $this->assertCount(1, $this->twilio->sent);
        $this->assertSame('job_completed', $this->twilio->sent[0]['context']);
    }
}
