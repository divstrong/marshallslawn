<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

class TwilioWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-auth-token';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('twilio.auth_token', $this->token);
    }

    /**
     * Post to a Twilio webhook with a valid X-Twilio-Signature computed the same
     * way Twilio computes it, so the controller's signature check passes.
     *
     * @param  array<string, string>  $params
     */
    private function postSigned(string $path, array $params)
    {
        $url = url($path);
        $signature = (new RequestValidator($this->token))->computeSignature($url, $params);

        return $this->withHeaders(['X-Twilio-Signature' => $signature])
            ->post($path, $params);
    }

    private function customerWithPhone(string $phone = '8045551212'): Customer
    {
        return Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => $phone,
            'status' => 'active',
        ]);
    }

    public function test_a_stop_reply_opts_the_customer_out(): void
    {
        $customer = $this->customerWithPhone();

        $this->postSigned('/webhooks/twilio/inbound', [
            'From' => '+18045551212',
            'Body' => 'STOP',
            'MessageSid' => 'SM1',
        ])->assertOk()->assertSee('unsubscribed', false);

        $this->assertSame(Customer::SMS_OPTED_OUT, $customer->fresh()->sms_consent_status);
    }

    public function test_a_yes_reply_confirms_opt_in(): void
    {
        $customer = $this->customerWithPhone();

        $this->postSigned('/webhooks/twilio/inbound', [
            'From' => '+18045551212',
            'Body' => 'YES',
            'MessageSid' => 'SM2',
        ])->assertOk()->assertSee('confirmed', false);

        $this->assertSame(Customer::SMS_CONFIRMED, $customer->fresh()->sms_consent_status);
        $this->assertNotNull($customer->fresh()->sms_consent_at);
    }

    public function test_an_ordinary_reply_is_logged_to_the_customer_chat_thread(): void
    {
        $customer = $this->customerWithPhone();

        $this->postSigned('/webhooks/twilio/inbound', [
            'From' => '+18045551212',
            'Body' => 'Can you come Friday instead?',
            'MessageSid' => 'SM3',
        ])->assertOk();

        $this->assertDatabaseHas('customer_messages', [
            'customer_id' => $customer->id,
            'sender' => CustomerMessage::SENDER_CUSTOMER,
            'body' => 'Can you come Friday instead?',
        ]);
    }

    public function test_a_missing_or_invalid_signature_is_rejected(): void
    {
        $this->customerWithPhone();

        $this->withHeaders(['X-Twilio-Signature' => 'wrong'])
            ->post('/webhooks/twilio/inbound', ['From' => '+18045551212', 'Body' => 'STOP'])
            ->assertForbidden();
    }

    public function test_status_callback_accepts_a_signed_request(): void
    {
        $this->postSigned('/webhooks/twilio/status', [
            'MessageSid' => 'SM9',
            'MessageStatus' => 'delivered',
        ])->assertNoContent();
    }
}
