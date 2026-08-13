<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\JobResource\Pages\CreateJob;
use App\Livewire\EstimateBuilder;
use App\Models\Customer;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerQuickCreateActionsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Property $primary;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create([
            'first_name' => 'Wes',
            'last_name' => 'Alder',
            'email' => 'wes@example.com',
            'status' => 'active',
        ]);
        // Deliberately not the first row, to prove primary wins over insertion order.
        // is_primary must be set explicitly: the column defaults to true.
        Property::create([
            'customer_id' => $this->customer->id,
            'address' => '2 Side Lot',
            'is_primary' => false,
        ]);
        $this->primary = Property::create([
            'customer_id' => $this->customer->id,
            'address' => '1 Main St',
            'is_primary' => true,
        ]);
    }

    public function test_the_customer_view_offers_new_estimate_and_new_job_actions(): void
    {
        Livewire::test(ViewCustomer::class, ['record' => $this->customer->id])
            ->assertOk()
            ->assertActionExists('newEstimate')
            ->assertActionExists('newJob')
            ->assertActionExists('chat');
    }

    public function test_the_new_job_form_arrives_pointed_at_the_customer(): void
    {
        $this->withoutExceptionHandling();

        Livewire::withQueryParams(['customer_id' => $this->customer->id])
            ->test(CreateJob::class)
            ->assertFormSet([
                'customer_id' => $this->customer->id,
                'property_id' => $this->primary->id,
            ]);
    }

    public function test_the_new_job_form_ignores_an_unknown_customer(): void
    {
        Livewire::withQueryParams(['customer_id' => 999999])
            ->test(CreateJob::class)
            ->assertOk()
            ->assertFormSet(['customer_id' => null]);
    }

    public function test_the_estimate_builder_arrives_pointed_at_the_customer(): void
    {
        Livewire::test(EstimateBuilder::class, ['customerId' => $this->customer->id])
            ->assertSet('customerId', $this->customer->id)
            // selectCustomer() ran, so the primary property and share email came too.
            ->assertSet('propertyId', $this->primary->id)
            ->assertSet('shareEmail', 'wes@example.com');
    }

    public function test_the_estimate_builder_still_opens_blank_without_a_customer(): void
    {
        Livewire::test(EstimateBuilder::class)
            ->assertOk()
            ->assertSet('customerId', null)
            ->assertSet('propertyId', null);
    }
}
