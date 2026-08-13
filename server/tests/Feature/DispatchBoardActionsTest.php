<?php

namespace Tests\Feature;

use App\Livewire\DispatchBoard;
use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\Route;
use App\Models\RouteStop;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DispatchBoardActionsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        // Dispatch board is gated on the "Dispatch" permission.
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'status' => 'active']);
        $this->property = Property::create(['customer_id' => $this->customer->id, 'address' => '1 Elm St']);
    }

    public function test_reassigning_a_stop_moves_it_onto_the_target_crews_route_and_updates_the_job(): void
    {
        $crewA = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $crewB = Crew::create(['name' => 'Crew B', 'status' => 'active']);

        $job = Job::create([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'title' => 'Mow',
            'status' => 'scheduled',
            'crew_id' => $crewA->id,
            'scheduled_date' => '2026-07-15',
        ]);

        // JobRouteAssigner already put it on Crew A's route.
        $stop = RouteStop::where('job_id', $job->id)->sole();

        Livewire::test(DispatchBoard::class)
            ->call('reassignStopToCrew', $stop->id, $crewB->id);

        $stop->refresh();
        $this->assertSame($crewB->id, $stop->route->crew_id, 'stop should now sit on Crew B\'s route');
        $this->assertSame($crewB->id, $job->fresh()->crew_id, 'job crew should follow the stop');
    }

    public function test_new_job_modal_creates_a_job_and_routes_it_to_the_chosen_crew(): void
    {
        $crew = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $service = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);

        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            ->set('newJob.property_id', $this->property->id)
            ->call('addNewJobService', $service->id)
            ->set('newJob.scheduled_date', '2026-07-20')
            ->set('newJob.crew_id', $crew->id)
            // A date plus a crew makes the job Scheduled without anyone picking that.
            ->assertSet('newJob.status', 'scheduled')
            ->call('createNewJob')
            ->assertHasNoErrors();

        $job = Job::sole();
        // The label is derived from the services, not typed.
        $this->assertSame('Mowing', $job->title);
        $this->assertSame(Job::KIND_SERVICE, $job->kind);
        $this->assertSame('scheduled', $job->status);
        $this->assertSame($crew->id, $job->crew_id);
        $this->assertSame($this->customer->id, $job->customer_id);

        // The line carries the service default rate, seeded when it was added.
        $line = $job->jobServices()->sole();
        $this->assertSame($service->id, $line->service_id);
        $this->assertSame(45.0, (float) $line->price);

        // Placed straight onto the crew's route for that date.
        $this->assertDatabaseHas('route_stops', ['job_id' => $job->id]);
        $route = Route::whereDate('route_date', '2026-07-20')->where('crew_id', $crew->id)->first();
        $this->assertNotNull($route);
    }

    public function test_new_job_requires_a_customer(): void
    {
        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('createNewJob')
            ->assertHasErrors(['newJob.customer_id']);
    }

    public function test_the_modal_requires_at_least_one_service(): void
    {
        // The job type toggle is gone: every job raised from the board is a service
        // job, so its scope cannot be left empty.
        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            ->set('newJob.property_id', $this->property->id)
            ->call('createNewJob')
            ->assertHasErrors(['newJobServices']);

        $this->assertSame(0, Job::count());
    }

    public function test_a_service_rate_can_be_overridden_or_left_to_be_quoted(): void
    {
        $mowing = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);
        $mulch = Service::create(['name' => 'Mulching', 'category' => 'Beds', 'default_price' => 90, 'is_active' => true]);

        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            ->set('newJob.property_id', $this->property->id)
            ->call('addNewJobService', $mowing->id)
            ->call('addNewJobService', $mulch->id)
            // Default rates land in the rows...
            ->assertSet('newJobServices.0.price', '45.00')
            ->assertSet('newJobServices.1.price', '90.00')
            // ...then one is repriced for this property and the other left blank.
            ->set('newJobServices.0.price', '62.50')
            ->set('newJobServices.1.price', '')
            ->call('createNewJob')
            ->assertHasNoErrors();

        $job = Job::sole();
        $lines = $job->jobServices()->orderBy('sort_order')->get();
        $this->assertSame(62.5, (float) $lines[0]->price);
        $this->assertNull($lines[1]->price, 'a blank rate stays TBD');
        $this->assertSame(62.5, $job->total(), 'the total counts only the priced line');
    }

    public function test_a_waiting_job_is_filed_without_a_date_or_crew(): void
    {
        $crew = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $service = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);

        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            ->set('newJob.property_id', $this->property->id)
            ->call('addNewJobService', $service->id)
            ->set('newJob.scheduled_date', '2026-07-20')
            ->set('newJob.crew_id', $crew->id)
            // Waiting is an explicit filing choice, so it survives a later date change.
            ->set('newJob.status', 'waiting_list')
            ->set('newJob.scheduled_date', '2026-07-21')
            ->assertSet('newJob.status', 'waiting_list')
            ->call('createNewJob')
            ->assertHasNoErrors();

        $job = Job::sole();
        $this->assertSame('waiting_list', $job->status);
        $this->assertNull($job->scheduled_date, 'a waiting job holds no date');
        $this->assertNull($job->crew_id, 'a waiting job holds no crew');
        $this->assertDatabaseMissing('route_stops', ['job_id' => $job->id]);
    }

    public function test_services_are_added_to_a_list_one_pick_at_a_time_and_can_be_removed(): void
    {
        $mowing = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);
        $mulch = Service::create(['name' => 'Mulching', 'category' => 'Beds', 'default_price' => 90, 'is_active' => true]);
        $retired = Service::create(['name' => 'Retired', 'category' => 'Lawn', 'default_price' => 10, 'is_active' => false]);

        $component = Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            // Values arrive from the browser as strings.
            ->call('addNewJobService', (string) $mowing->id)
            ->call('addNewJobService', (string) $mulch->id)
            ->assertSet('newJobServices.0.service_id', $mowing->id)
            ->assertSet('newJobServices.1.service_id', $mulch->id);

        // Re-picking the same service doesn't duplicate it, and the placeholder
        // option (value="") and inactive services are both ignored.
        $component
            ->call('addNewJobService', (string) $mowing->id)
            ->call('addNewJobService', '')
            ->call('addNewJobService', (string) $retired->id);
        $this->assertCount(2, $component->get('newJobServices'));

        $component
            ->call('removeNewJobService', $mowing->id)
            ->assertSet('newJobServices.0.service_id', $mulch->id)
            ->call('createNewJob')
            ->assertHasNoErrors();

        $line = Job::sole()->jobServices()->sole();
        $this->assertSame($mulch->id, $line->service_id);
    }

    public function test_a_customer_added_inline_becomes_the_jobs_selection(): void
    {
        $component = Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->set('newJobCustomerSearch', 'Rhonda Vasquez')
            ->call('toggleNewCustomerForm')
            // The typed search seeds the name so it isn't retyped.
            ->assertSet('newCustomer.first_name', 'Rhonda')
            ->assertSet('newCustomer.last_name', 'Vasquez')
            ->set('newCustomer.phone', '804-555-0100')
            ->call('createNewJobCustomer')
            ->assertHasNoErrors();

        $customer = Customer::where('last_name', 'Vasquez')->sole();
        $this->assertSame('active', $customer->status);

        $component
            ->assertSet('newJob.customer_id', $customer->id)
            ->assertSet('showNewCustomerForm', false)
            // No property on file yet, so that form is waiting.
            ->assertSet('showNewPropertyForm', true);
    }

    public function test_a_property_added_inline_becomes_the_jobs_selection_and_is_primary_when_first(): void
    {
        $newCustomer = Customer::create(['first_name' => 'Ann', 'last_name' => 'Poole', 'status' => 'active']);
        $service = Service::create(['name' => 'Mowing', 'category' => 'Lawn', 'default_price' => 45, 'is_active' => true]);

        $component = Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $newCustomer->id)
            ->assertSet('newJob.property_id', null)
            ->call('toggleNewPropertyForm')
            ->set('newProperty.address', '742 Evergreen Ter')
            ->set('newProperty.city', 'Richmond')
            ->set('newProperty.state', 'VA')
            ->set('newProperty.zip', '23220')
            ->call('createNewJobProperty')
            ->assertHasNoErrors();

        $property = Property::where('address', '742 Evergreen Ter')->sole();
        $this->assertSame($newCustomer->id, $property->customer_id);
        $this->assertTrue($property->is_primary, 'the first property on file is the primary one');

        $component
            ->assertSet('newJob.property_id', $property->id)
            ->assertSet('showNewPropertyForm', false)
            // And the job saves against it once a service gives it a scope.
            ->call('addNewJobService', $service->id)
            ->call('createNewJob')
            ->assertHasNoErrors();

        $this->assertSame($property->id, Job::sole()->property_id);
    }

    public function test_an_extra_property_added_inline_is_selected_but_not_made_primary(): void
    {
        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            // Seeded with the customer's existing property.
            ->assertSet('newJob.property_id', $this->property->id)
            ->call('toggleNewPropertyForm')
            ->set('newProperty.address', '2 Oak Ave')
            ->call('createNewJobProperty')
            ->assertHasNoErrors()
            ->assertSet('newJob.property_id', Property::where('address', '2 Oak Ave')->value('id'));

        $this->assertFalse(
            Property::where('address', '2 Oak Ave')->sole()->is_primary,
            'an added property must not steal primary from the existing one',
        );
    }

    public function test_the_inline_customer_form_requires_a_name(): void
    {
        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('toggleNewCustomerForm')
            ->call('createNewJobCustomer')
            ->assertHasErrors(['newCustomer.first_name', 'newCustomer.last_name']);

        $this->assertSame(1, Customer::count(), 'nothing is created from an empty form');
    }

    public function test_the_modal_reveals_the_job_fields_one_step_at_a_time(): void
    {
        $customer = Customer::create(['first_name' => 'Ed', 'last_name' => 'Nunez', 'status' => 'active']);
        Property::create(['customer_id' => $customer->id, 'address' => '9 Birch Rd']);

        $component = Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            // Customer only: no property step, no job fields.
            ->assertSee('Customer')
            ->assertDontSee('Scheduled date')
            ->assertDontSee('Property');

        $component
            ->call('selectNewJobCustomer', $customer->id)
            ->assertSee('Property')
            // The customer's only property is pre-picked, so the rest opens up.
            ->assertSee('Scheduled date')
            ->assertSee('Priority');

        // Deselecting the property folds the job fields away again.
        $component
            ->set('newJob.property_id', '')
            ->assertSee('Property')
            ->assertDontSee('Scheduled date');
    }

    public function test_clearing_the_customer_resets_the_property_step(): void
    {
        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->call('selectNewJobCustomer', $this->customer->id)
            ->assertSet('newJob.property_id', $this->property->id)
            ->call('clearNewJobCustomer')
            ->assertSet('newJob.customer_id', null)
            ->assertSet('newJob.property_id', null)
            ->assertSet('newJobCustomerSearch', '');
    }
}
