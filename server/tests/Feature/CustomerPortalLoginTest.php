<?php

namespace Tests\Feature;

use App\Models\Customer;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPortalLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_customer_can_log_into_the_portal(): void
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'status' => 'active',
        ]);

        filament()->setCurrentPanel('portal');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'jane@example.com',
                'password' => 'secret-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_active_customer_can_log_in_with_remember_me_checked(): void
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane-remember@example.com',
            'password' => 'secret-password',
            'status' => 'active',
        ]);

        filament()->setCurrentPanel('portal');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'jane-remember@example.com',
                'password' => 'secret-password',
                'remember' => true,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_logged_in_customer_can_reach_the_portal_dashboard(): void
    {
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane2@example.com',
            'password' => 'secret-password',
            'status' => 'active',
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/portal')
            ->assertSuccessful();
    }
}
