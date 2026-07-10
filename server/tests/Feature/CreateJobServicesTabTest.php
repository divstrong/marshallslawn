<?php

namespace Tests\Feature;

use App\Filament\Resources\JobResource\Pages\CreateJob;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateJobServicesTabTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        filament()->setCurrentPanel('admin');

        $this->customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
        ]);

        $this->property = Property::create([
            'customer_id' => $this->customer->id,
            'address' => '1 Elm St',
        ]);
    }

    public function test_a_job_can_be_created_with_service_lines_including_a_tbd_price(): void
    {
        $mowing = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45.00, 'is_active' => true]);
        $mulch = Service::create(['name' => 'Mulch install', 'category' => 'Landscaping', 'default_price' => 0, 'is_active' => true]);

        Livewire::test(CreateJob::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'title' => 'Spring visit',
                'status' => 'pending',
                'job_type' => 'one_time',
                'service_lines' => [
                    ['service_id' => $mowing->id, 'pricing' => 'fixed', 'price' => '45.00', 'description' => 'Front + back'],
                    ['service_id' => $mulch->id, 'pricing' => 'tbd', 'description' => null],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $job = Job::firstWhere('title', 'Spring visit');
        $this->assertNotNull($job);

        $this->assertDatabaseHas('job_services', [
            'job_id' => $job->id,
            'service_id' => $mowing->id,
            'price' => 45.00,
        ]);

        $tbd = $job->jobServices()->where('service_id', $mulch->id)->sole();
        $this->assertNull($tbd->price, 'a TBD line must store a null price, not 0.00');

        // Only quoted lines count toward the job total.
        $this->assertSame(45.0, $job->fresh()->total());
    }

    public function test_priority_is_not_required(): void
    {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'title' => 'No priority set',
                'status' => 'pending',
                'job_type' => 'one_time',
                'priority' => null,
                'service_lines' => [],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('service_jobs', ['title' => 'No priority set']);
    }

    public function test_a_recurring_job_requires_at_least_one_service_line(): void
    {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'title' => 'Weekly mow',
                'status' => 'pending',
                'job_type' => 'recurring',
                'recur_frequency' => 'weekly',
                'recur_occurrences' => 4,
                'recur_start' => '2026-07-13',
                'service_lines' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['service_lines']);
    }

    public function test_a_customer_can_be_created_inline_from_the_job_form(): void
    {
        $id = \App\Filament\Resources\JobResource::createInlineCustomer([
            'first_name' => 'New',
            'last_name' => 'Customer',
            'address' => '9 Oak Ave',
            'city' => 'Richmond',
        ]);

        $this->assertDatabaseHas('customers', ['id' => $id, 'last_name' => 'Customer', 'status' => 'active']);
        $this->assertDatabaseHas('properties', ['customer_id' => $id, 'address' => '9 Oak Ave', 'is_primary' => true]);
    }
}
