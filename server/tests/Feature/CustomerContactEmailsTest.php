<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerContactEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $emails = []): Customer
    {
        return Customer::create(array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
            'email' => 'primary@example.com',
        ], $emails));
    }

    public function test_a_dedicated_address_is_used_for_its_own_stream(): void
    {
        $customer = $this->customer([
            'estimate_email' => 'quotes@example.com',
            'billing_email' => 'ap@example.com',
            'service_email' => 'site@example.com',
        ]);

        $this->assertSame('quotes@example.com', $customer->emailFor('estimate'));
        $this->assertSame('ap@example.com', $customer->emailFor('billing'));
        $this->assertSame('site@example.com', $customer->emailFor('service'));
    }

    public function test_a_blank_stream_falls_back_to_the_primary_email(): void
    {
        $customer = $this->customer(['billing_email' => 'ap@example.com']);

        $this->assertSame('ap@example.com', $customer->emailFor('billing'));
        $this->assertSame('primary@example.com', $customer->emailFor('estimate'));
        $this->assertSame('primary@example.com', $customer->emailFor('service'));
    }

    public function test_an_unknown_stream_falls_back_rather_than_failing(): void
    {
        $this->assertSame('primary@example.com', $this->customer()->emailFor('marketing'));
    }
}
