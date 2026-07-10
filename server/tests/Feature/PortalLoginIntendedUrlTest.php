<?php

namespace Tests\Feature;

use App\Models\Customer;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalLoginIntendedUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_who_first_hit_an_admin_url_still_lands_in_the_portal(): void
    {
        Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'status' => 'active',
        ]);

        // Landing on the admin panel while logged out stashes `url.intended` in the
        // session that both panels share.
        $this->get('/')->assertRedirect('/login');
        $this->assertNotNull(session('url.intended'));

        filament()->setCurrentPanel('portal');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => 'jane@example.com',
                'password' => 'secret-password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/portal');

        $this->assertAuthenticated('customer');
    }

    public function test_admin_panel_still_honours_its_own_intended_url(): void
    {
        // Staff need a role before Filament will let them into the admin panel.
        $role = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $user = \App\Models\User::factory()->create(['role_id' => $role->id]);

        $this->get('/customers')->assertRedirect('/login');

        filament()->setCurrentPanel('admin');

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect('/customers');
    }
}
