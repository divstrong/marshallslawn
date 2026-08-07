<?php

namespace Tests\Feature;

use App\Filament\Resources\JobResource\Pages\CreateJob;
use App\Livewire\JobServiceLines;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

        $this->customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $this->property = Property::create(['customer_id' => $this->customer->id, 'address' => '1 Elm St']);
    }

    /** Seed the draft cache the way the JobServiceLines grid would, for a form instance. */
    private function seedDraft(string $draftId, array $rows): void
    {
        Cache::put(JobServiceLines::draftCacheKey($draftId), $rows, now()->addMinutes(60));
    }

    public function test_a_job_is_created_with_the_grid_lines_including_qty_rate_and_tbd(): void
    {
        $mowing = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);
        $mulch = Service::create(['name' => 'Mulch', 'category' => 'Lawn', 'default_price' => null, 'is_active' => true]);

        $component = Livewire::test(CreateJob::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'status' => 'pending',
                'job_type' => 'one_time',
            ]);

        // The grid buffers rows into the draft cache keyed by the form's draft id.
        $draftId = $component->get('data.services_draft_id');
        $this->seedDraft($draftId, [
            ['service_id' => $mowing->id, 'description' => 'Front + back', 'quantity' => 2, 'unit_price' => 45, 'price' => 90],
            ['service_id' => $mulch->id, 'description' => 'Mulch', 'quantity' => 1, 'unit_price' => null, 'price' => null],
        ]);

        $component->call('create')->assertHasNoFormErrors();

        $job = Job::sole();

        // Nobody types a title any more — it is derived from the services.
        $this->assertSame('Mowing +1 more', $job->title);

        $this->assertDatabaseHas('job_services', [
            'job_id' => $job->id, 'service_id' => $mowing->id, 'quantity' => 2, 'unit_price' => 45, 'price' => 90,
        ]);
        $tbd = $job->jobServices()->where('service_id', $mulch->id)->sole();
        $this->assertNull($tbd->price, 'a TBD line stores a null price');

        // Only priced lines count toward the job total.
        $this->assertSame(90.0, $job->fresh()->total());
    }

    public function test_priority_is_not_required_and_services_are_optional(): void
    {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'status' => 'pending',
                'job_type' => 'one_time',
                'priority' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // A service job with no lines yet still gets a usable label.
        $this->assertDatabaseHas('service_jobs', [
            'customer_id' => $this->customer->id,
            'kind' => Job::KIND_SERVICE,
            'title' => 'Service job',
        ]);
    }

    public function test_a_recurring_job_requires_at_least_one_service(): void
    {
        Livewire::test(CreateJob::class)
            ->fillForm([
                'customer_id' => $this->customer->id,
                'property_id' => $this->property->id,
                'status' => 'pending',
                'job_type' => 'recurring',
                'recur_frequency' => 'weekly',
                'recur_occurrences' => 4,
                'recur_start' => '2026-07-13',
            ])
            // No draft lines seeded -> the shared creator rejects a service-less series.
            ->call('create')
            ->assertHasErrors('services');

        $this->assertDatabaseCount('service_jobs', 0);
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
