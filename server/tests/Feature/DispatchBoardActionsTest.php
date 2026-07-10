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
            ->set('newJob.title', 'On-the-fly mow')
            ->set('newJob.property_id', $this->property->id)
            ->set('newJob.scheduled_date', '2026-07-20')
            ->set('newJob.crew_id', $crew->id)
            ->set('newJob.service_ids', [$service->id])
            ->call('createNewJob')
            ->assertHasNoErrors();

        $job = Job::firstWhere('title', 'On-the-fly mow');
        $this->assertNotNull($job);
        $this->assertSame($crew->id, $job->crew_id);
        $this->assertSame($this->customer->id, $job->customer_id);

        // Service attached as a TBD line.
        $line = $job->jobServices()->sole();
        $this->assertSame($service->id, $line->service_id);
        $this->assertNull($line->price);

        // Placed straight onto the crew's route for that date.
        $this->assertDatabaseHas('route_stops', ['job_id' => $job->id]);
        $route = Route::whereDate('route_date', '2026-07-20')->where('crew_id', $crew->id)->first();
        $this->assertNotNull($route);
    }

    public function test_new_job_requires_a_customer_and_title(): void
    {
        Livewire::test(DispatchBoard::class)
            ->call('openNewJobModal')
            ->set('newJob.title', '')
            ->call('createNewJob')
            ->assertHasErrors(['newJob.customer_id', 'newJob.title']);
    }
}
