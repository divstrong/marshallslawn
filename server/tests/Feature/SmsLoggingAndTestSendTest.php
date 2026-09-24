<?php

namespace Tests\Feature;

use App\Filament\Resources\SmsLogResource\Pages\ListSmsLogs;
use App\Livewire\CustomerChatPanel;
use App\Livewire\SmsTemplateManager;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Role;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

/** A TwilioService that looks configured and always succeeds. */
class FakeConfiguredTwilio extends TwilioService
{
    /** @var array<int, array{to: string, body: string, context: ?string, customer_id: ?int}> */
    public array $sent = [];

    public function isConfigured(): bool
    {
        return true;
    }

    public function sendSms(string $to, string $body, ?string $context = null, ?int $customerId = null): ?string
    {
        $this->sent[] = ['to' => $to, 'body' => $body, 'context' => $context, 'customer_id' => $customerId];

        return 'SM_fake_' . count($this->sent);
    }
}

class SmsLoggingAndTestSendTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-auth-token';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('twilio.auth_token', $this->token);
    }

    private function customer(string $consent = Customer::SMS_CONFIRMED): Customer
    {
        return Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '(804) 555-1212',
            'status' => 'active',
            'sms_consent_status' => $consent,
            'sms_consent_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- logging

    public function test_a_send_with_no_credentials_is_recorded_as_skipped(): void
    {
        config()->set('twilio.account_sid', null);
        config()->set('twilio.auth_token', null);

        $customer = $this->customer();

        $sid = app(TwilioService::class)
            ->sendSms('(804) 555-1212', 'Hello there', 'job_scheduled', $customer->id);

        $this->assertNull($sid);

        $log = SmsLog::sole();
        $this->assertSame(SmsLog::STATUS_SKIPPED, $log->status);
        $this->assertSame('not_configured', $log->reason);
        $this->assertSame('job_scheduled', $log->template_key);
        $this->assertSame('+18045551212', $log->to_number);
        $this->assertSame($customer->id, $log->customer_id);
    }

    public function test_an_unusable_number_is_recorded_rather_than_silently_dropped(): void
    {
        app(TwilioService::class)->sendSms('123', 'Hello there', 'job_scheduled');

        $log = SmsLog::sole();
        $this->assertSame(SmsLog::STATUS_SKIPPED, $log->status);
        $this->assertSame('invalid_number', $log->reason);
    }

    // -------------------------------------------------------- status callback

    public function test_the_status_callback_walks_a_log_row_to_delivered(): void
    {
        $log = SmsLog::create([
            'template_key' => 'job_scheduled',
            'to_number' => '+18045551212',
            'body' => 'Hello there',
            'message_sid' => 'SM123',
            'status' => SmsLog::STATUS_QUEUED,
        ]);

        $this->postSigned('/webhooks/twilio/status', [
            'MessageSid' => 'SM123',
            'MessageStatus' => 'delivered',
        ])->assertNoContent();

        $log->refresh();
        $this->assertSame(SmsLog::STATUS_DELIVERED, $log->status);
        $this->assertNotNull($log->delivered_at);
    }

    public function test_a_failure_callback_records_the_carrier_error_code(): void
    {
        SmsLog::create([
            'to_number' => '+18045551212',
            'body' => 'Hello there',
            'message_sid' => 'SM124',
            'status' => SmsLog::STATUS_SENT,
        ]);

        $this->postSigned('/webhooks/twilio/status', [
            'MessageSid' => 'SM124',
            'MessageStatus' => 'undelivered',
            'ErrorCode' => '30006',
        ])->assertNoContent();

        $log = SmsLog::where('message_sid', 'SM124')->sole();
        $this->assertSame(SmsLog::STATUS_UNDELIVERED, $log->status);
        $this->assertSame('30006', $log->error_code);
    }

    public function test_a_late_callback_does_not_drag_a_delivered_row_backwards(): void
    {
        SmsLog::create([
            'to_number' => '+18045551212',
            'body' => 'Hello there',
            'message_sid' => 'SM125',
            'status' => SmsLog::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        $this->postSigned('/webhooks/twilio/status', [
            'MessageSid' => 'SM125',
            'MessageStatus' => 'sent',
        ])->assertNoContent();

        $this->assertSame(
            SmsLog::STATUS_DELIVERED,
            SmsLog::where('message_sid', 'SM125')->sole()->status,
        );
    }

    // ------------------------------------------------------------- test sends

    public function test_a_test_send_delivers_the_template_rendered_with_sample_data(): void
    {
        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        $template = SmsTemplate::where('key', 'job_scheduled')->sole();

        Livewire::test(SmsTemplateManager::class)
            ->set("testNumbers.{$template->id}", '(804) 555-9999')
            ->call('sendTest', $template->id)
            ->assertSet("testResults.{$template->id}.ok", true);

        $this->assertCount(1, $fake->sent);
        $this->assertSame('+18045559999', $fake->sent[0]['to']);
        $this->assertSame('test:job_scheduled', $fake->sent[0]['context']);
        // Sample data is substituted — no raw placeholders go out.
        $this->assertStringNotContainsString('{', $fake->sent[0]['body']);
        $this->assertStringContainsString('Jane', $fake->sent[0]['body']);
    }

    public function test_a_test_send_rejects_an_unusable_number(): void
    {
        $this->app->instance(TwilioService::class, new FakeConfiguredTwilio());

        $template = SmsTemplate::where('key', 'job_scheduled')->sole();

        Livewire::test(SmsTemplateManager::class)
            ->set("testNumbers.{$template->id}", '555')
            ->call('sendTest', $template->id)
            ->assertSet("testResults.{$template->id}.ok", false);
    }

    public function test_a_test_send_works_while_the_customer_channel_is_off(): void
    {
        // Verifying the Twilio setup has to be possible BEFORE arming the channel.
        config()->set('twilio.notifications.enabled', false);

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        $template = SmsTemplate::where('key', 'job_scheduled')->sole();

        Livewire::test(SmsTemplateManager::class)
            ->set("testNumbers.{$template->id}", '8045559999')
            ->call('sendTest', $template->id)
            ->assertSet("testResults.{$template->id}.ok", true);

        $this->assertCount(1, $fake->sent);
    }

    // ----------------------------------------------------------- chat replies

    public function test_an_office_chat_reply_is_texted_to_the_customer(): void
    {
        config()->set('twilio.notifications.enabled', true);

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        $customer = $this->customer();

        Livewire::test(CustomerChatPanel::class, ['customerId' => $customer->id])
            ->set('body', 'We are on our way.')
            ->call('send')
            ->assertSet('undelivered', null);

        $this->assertCount(1, $fake->sent);
        $this->assertSame('We are on our way.', $fake->sent[0]['body']);
        $this->assertSame('chat_reply', $fake->sent[0]['context']);
        $this->assertSame($customer->id, $fake->sent[0]['customer_id']);
    }

    public function test_an_opted_out_customer_is_never_texted_a_chat_reply(): void
    {
        config()->set('twilio.notifications.enabled', true);

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        $customer = $this->customer(Customer::SMS_OPTED_OUT);

        $component = Livewire::test(CustomerChatPanel::class, ['customerId' => $customer->id])
            ->set('body', 'We are on our way.')
            ->call('send');

        $this->assertCount(0, $fake->sent);
        $this->assertStringContainsString('opted out', (string) $component->get('undelivered'));

        // The reply is still kept in the thread — the office said it, it just did not go out.
        $this->assertDatabaseHas('customer_messages', [
            'customer_id' => $customer->id,
            'body' => 'We are on our way.',
        ]);
    }

    // ------------------------------------------------------------ message log

    public function test_the_message_log_lists_sends_and_can_filter_to_problems(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $customer = $this->customer();

        SmsLog::create([
            'customer_id' => $customer->id,
            'template_key' => 'job_scheduled',
            'to_number' => '+18045551212',
            'body' => 'Your Weekly Mow is scheduled.',
            'message_sid' => 'SM_ok',
            'status' => SmsLog::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        SmsLog::create([
            'customer_id' => $customer->id,
            'template_key' => 'invoice_issued',
            'to_number' => '+18045559999',
            'body' => 'Invoice INV-00042 is ready.',
            'message_sid' => 'SM_bad',
            'status' => SmsLog::STATUS_UNDELIVERED,
            'error_code' => '30006',
        ]);

        Livewire::test(ListSmsLogs::class)
            ->assertCanSeeTableRecords(SmsLog::all())
            ->filterTable('problems')
            ->assertCanSeeTableRecords(SmsLog::where('status', SmsLog::STATUS_UNDELIVERED)->get())
            ->assertCanNotSeeTableRecords(SmsLog::where('status', SmsLog::STATUS_DELIVERED)->get());
    }

    // ------------------------------------------------------------ manual send

    private function asAdmin(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    public function test_the_office_can_send_an_ad_hoc_text_and_the_plus_one_is_added(): void
    {
        config()->set('twilio.notifications.enabled', true);
        $this->asAdmin();

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        Livewire::test(ListSmsLogs::class)
            ->callAction('sendSms', [
                'phone' => '(804) 555-7777',
                'body' => 'Running about an hour behind today.',
            ])
            ->assertHasNoActionErrors();

        $this->assertCount(1, $fake->sent);
        $this->assertSame('+18045557777', $fake->sent[0]['to']);
        $this->assertSame('manual', $fake->sent[0]['context']);
        $this->assertNull($fake->sent[0]['customer_id']);
    }

    public function test_an_ad_hoc_text_to_a_known_customer_is_attached_and_mirrored(): void
    {
        config()->set('twilio.notifications.enabled', true);
        $this->asAdmin();

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        $customer = $this->customer();

        Livewire::test(ListSmsLogs::class)
            ->callAction('sendSms', [
                'phone' => '804-555-1212',
                'body' => 'Crew is on the way.',
            ]);

        $this->assertSame($customer->id, $fake->sent[0]['customer_id']);
        $this->assertDatabaseHas('customer_messages', [
            'customer_id' => $customer->id,
            'sender' => CustomerMessage::SENDER_OFFICE,
            'body' => 'Crew is on the way.',
        ]);
    }

    public function test_an_ad_hoc_text_to_an_opted_out_customer_is_blocked(): void
    {
        config()->set('twilio.notifications.enabled', true);
        $this->asAdmin();

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        $this->customer(Customer::SMS_OPTED_OUT);

        Livewire::test(ListSmsLogs::class)
            ->callAction('sendSms', [
                'phone' => '8045551212',
                'body' => 'Crew is on the way.',
            ]);

        $this->assertCount(0, $fake->sent);
    }

    public function test_an_ad_hoc_text_rejects_a_number_that_is_not_ten_digits(): void
    {
        config()->set('twilio.notifications.enabled', true);
        $this->asAdmin();

        $fake = new FakeConfiguredTwilio();
        $this->app->instance(TwilioService::class, $fake);

        Livewire::test(ListSmsLogs::class)
            ->callAction('sendSms', ['phone' => '55512', 'body' => 'Hello'])
            ->assertHasActionErrors(['phone']);

        $this->assertCount(0, $fake->sent);
    }

    // -------------------------------------------------------------- segmenting

    public function test_segment_counting_flags_a_curly_quote_as_ucs2(): void
    {
        $plain = SmsTemplate::segmentInfo(str_repeat('a', 160));
        $this->assertSame(1, $plain['segments']);
        $this->assertSame('GSM-7', $plain['encoding']);

        $overflow = SmsTemplate::segmentInfo(str_repeat('a', 161));
        $this->assertSame(2, $overflow['segments']);

        // One curly apostrophe cuts the single-segment ceiling from 160 to 70.
        $curly = SmsTemplate::segmentInfo(str_repeat('a', 100) . "\u{2019}");
        $this->assertSame('UCS-2', $curly['encoding']);
        $this->assertSame(2, $curly['segments']);
    }

    /**
     * @param  array<string, string>  $params
     */
    private function postSigned(string $path, array $params)
    {
        $url = url($path);
        $signature = (new RequestValidator($this->token))->computeSignature($url, $params);

        return $this->withHeaders(['X-Twilio-Signature' => $signature])->post($path, $params);
    }
}
