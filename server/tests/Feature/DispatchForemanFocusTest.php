<?php

namespace Tests\Feature;

use App\Livewire\DispatchBoard;
use App\Models\Crew;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Job;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DispatchForemanFocusTest extends TestCase
{
    use RefreshDatabase;

    public function test_selecting_a_foreman_narrows_the_panel_to_their_crews_jobs(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $customer = Customer::create(['first_name' => 'Nell', 'last_name' => 'Frost', 'status' => 'active']);
        // Both properties need coordinates or their stops never reach the map.
        $propA = Property::create([
            'customer_id' => $customer->id, 'address' => '10 Aspen Dr',
            'latitude' => 37.55, 'longitude' => -77.46, 'geocoded_at' => now(),
        ]);
        $propB = Property::create([
            'customer_id' => $customer->id, 'address' => '20 Birch Dr',
            'latitude' => 37.56, 'longitude' => -77.47, 'geocoded_at' => now(),
        ]);

        $foremanA = Employee::create(['first_name' => 'Abe', 'last_name' => 'Lane', 'status' => 'active']);
        $crewA = Crew::create(['name' => 'Crew A', 'status' => 'active', 'foreman_id' => $foremanA->id]);
        $crewB = Crew::create(['name' => 'Crew B', 'status' => 'active']);

        $date = '2026-08-12';
        $jobA = Job::create([
            'customer_id' => $customer->id, 'property_id' => $propA->id, 'title' => 'Mow A',
            'status' => 'scheduled', 'crew_id' => $crewA->id, 'scheduled_date' => $date,
        ]);
        $jobB = Job::create([
            'customer_id' => $customer->id, 'property_id' => $propB->id, 'title' => 'Mow B',
            'status' => 'scheduled', 'crew_id' => $crewB->id, 'scheduled_date' => $date,
        ]);

        $component = Livewire::test(DispatchBoard::class)->set('date', $date);

        // Unfocused: both crews' stops are on the board.
        $this->assertNull($component->instance()->focusedCrewId);
        $titles = array_column($component->instance()->stops, 'customer_name');
        $this->assertCount(2, $titles, 'both crews show before a foreman is picked');

        // Focused on Crew A's foreman: only Crew A's stop survives.
        $component->call('selectForeman', $foremanA->id);
        $this->assertSame($crewA->id, $component->instance()->focusedCrewId);
        $stops = $component->instance()->stops;
        $this->assertCount(1, $stops);
        $this->assertSame($jobA->id, $stops[0]['job_id'] ?? null);

        $component->assertSee('Showing Crew A only');

        // Clearing restores the full board.
        $component->call('clearSelection');
        $this->assertNull($component->instance()->focusedCrewId);
        $this->assertCount(2, $component->instance()->stops);
        $component->assertDontSee('Showing Crew A only');
    }

    public function test_a_foreman_who_leads_no_crew_does_not_blank_the_board(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'is_admin' => true]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));

        $customer = Customer::create(['first_name' => 'Pat', 'last_name' => 'Nye', 'status' => 'active']);
        $property = Property::create([
            'customer_id' => $customer->id, 'address' => '1 Cedar Ct',
            'latitude' => 37.55, 'longitude' => -77.46, 'geocoded_at' => now(),
        ]);
        $crew = Crew::create(['name' => 'Crew A', 'status' => 'active']);
        $stray = Employee::create(['first_name' => 'Sam', 'last_name' => 'Nolan', 'status' => 'active']);

        Job::create([
            'customer_id' => $customer->id, 'property_id' => $property->id, 'title' => 'Mow',
            'status' => 'scheduled', 'crew_id' => $crew->id, 'scheduled_date' => '2026-08-12',
        ]);

        $component = Livewire::test(DispatchBoard::class)
            ->set('date', '2026-08-12')
            ->call('selectForeman', $stray->id);

        $this->assertNull(
            $component->instance()->focusedCrewId,
            'an employee leading no crew focuses nothing rather than filtering to nowhere',
        );
        $this->assertCount(1, $component->instance()->stops);
    }
}
