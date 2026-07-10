<?php

namespace Tests\Feature;

use App\Models\Crew;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Property;
use App\Models\Route;
use App\Models\RouteStop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRouteAssignerTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Property $property;

    private Crew $crew;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'status' => 'active',
        ]);

        $this->property = Property::create([
            'customer_id' => $this->customer->id,
            'address' => '1 Elm St',
            'city' => 'Richmond',
        ]);

        $this->crew = Crew::create(['name' => 'Crew A', 'status' => 'active']);
    }

    private function makeJob(array $attributes = []): Job
    {
        return Job::create(array_merge([
            'customer_id' => $this->customer->id,
            'property_id' => $this->property->id,
            'title' => 'Mowing',
            'status' => 'scheduled',
        ], $attributes));
    }

    public function test_a_job_created_with_a_crew_and_a_date_lands_on_that_crews_route(): void
    {
        $job = $this->makeJob([
            'crew_id' => $this->crew->id,
            'scheduled_date' => '2026-07-15',
        ]);

        $route = Route::where('crew_id', $this->crew->id)->whereDate('route_date', '2026-07-15')->first();

        $this->assertNotNull($route, 'no route was created for the crew + date');
        $this->assertDatabaseHas('route_stops', [
            'route_id' => $route->id,
            'job_id' => $job->id,
            'status' => 'pending',
            'sort_order' => 1,
        ]);
    }

    public function test_a_job_without_a_crew_stays_off_the_route(): void
    {
        $job = $this->makeJob(['scheduled_date' => '2026-07-15']);

        $this->assertSame(0, RouteStop::where('job_id', $job->id)->count());
    }

    public function test_reassigning_the_crew_moves_the_pending_stop(): void
    {
        $job = $this->makeJob([
            'crew_id' => $this->crew->id,
            'scheduled_date' => '2026-07-15',
        ]);

        $other = Crew::create(['name' => 'Crew B', 'status' => 'active']);
        $job->update(['crew_id' => $other->id]);

        $stops = RouteStop::where('job_id', $job->id)->get();
        $this->assertCount(1, $stops, 'the job should sit on exactly one route');
        $this->assertSame($other->id, $stops->first()->route->crew_id);
    }

    public function test_clearing_the_date_pulls_the_job_back_off_the_route(): void
    {
        $job = $this->makeJob([
            'crew_id' => $this->crew->id,
            'scheduled_date' => '2026-07-15',
        ]);

        $job->update(['scheduled_date' => null]);

        $this->assertSame(0, RouteStop::where('job_id', $job->id)->count());
    }

    public function test_a_completed_jobs_stop_is_left_alone(): void
    {
        $job = $this->makeJob([
            'crew_id' => $this->crew->id,
            'scheduled_date' => '2026-07-15',
        ]);

        RouteStop::where('job_id', $job->id)->update(['status' => 'completed']);
        $job->update(['status' => 'completed']);

        $this->assertSame(1, RouteStop::where('job_id', $job->id)->count());
    }

    public function test_a_started_stop_is_never_moved(): void
    {
        $job = $this->makeJob([
            'crew_id' => $this->crew->id,
            'scheduled_date' => '2026-07-15',
        ]);

        RouteStop::where('job_id', $job->id)->update(['status' => 'in_progress']);

        $other = Crew::create(['name' => 'Crew B', 'status' => 'active']);
        $job->update(['crew_id' => $other->id]);

        // The in-progress stop survives; the job also picks up a stop on the new route.
        $this->assertDatabaseHas('route_stops', ['job_id' => $job->id, 'status' => 'in_progress']);
    }

    public function test_syncing_is_idempotent(): void
    {
        $job = $this->makeJob([
            'crew_id' => $this->crew->id,
            'scheduled_date' => '2026-07-15',
        ]);

        $job->update(['title' => 'Mowing + edging']);
        $job->update(['status' => 'scheduled']);

        $this->assertSame(1, RouteStop::where('job_id', $job->id)->count());
        $this->assertSame(1, Route::where('crew_id', $this->crew->id)->count());
    }
}
